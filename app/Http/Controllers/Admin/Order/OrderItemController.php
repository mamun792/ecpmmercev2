<?php

namespace App\Http\Controllers\Admin\Order;

use App\Exceptions\InsufficientStockException;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\Order\OrderInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderItemController extends Controller
{
    protected OrderInterface $orderService;

    public function __construct(OrderInterface $orderService)
    {
        $this->orderService = $orderService;
    }

    /**
     * Remove an order item via AJAX and restore stock
     */
    public function remove(int $orderId, int $itemId)
    {
        try {
            // Update via service
            $this->orderService->updateOrder($orderId, [
                'items' => [
                    [
                        'id' => $itemId,
                        'action' => 'remove'
                    ]
                ]
            ]);

            // Fetch updated order for response
            $order = Order::findOrFail($orderId);

            return response()->json([
                'success' => true,
                'message' => 'Item removed successfully',
                'data' => [
                    'order' => [
                        'subtotal' => $order->subtotal,
                        'discount_total' => $order->discount_total,
                        'shipping_cost' => $order->shipping_cost,
                        'total' => $order->total,
                        'formatted_subtotal' => number_format($order->subtotal, 2),
                        'formatted_discount_total' => number_format($order->discount_total, 2),
                        'formatted_shipping_cost' => number_format($order->shipping_cost, 2),
                        'formatted_total' => number_format($order->total, 2),
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove item: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete an order item permanently and restore stock using V2 Inventory System
     */
    public function destroy(Order $order, int $itemId)
    {
        try {
            DB::beginTransaction();

            // Find the order item and ensure it belongs to the order
            $orderItem = OrderItem::where('order_id', $order->id)
                ->where('id', $itemId)
                ->firstOrFail();

            // Restore stock if the order is not incomplete (incomplete orders don't deduct stock)
            if ($order->status !== 'incomplete') {
                $quantity = $orderItem->quantity;
                $product = $orderItem->product;
                $variation = $orderItem->productVariation;

                if ($product) {
                    // Use V2 Inventory Service to restore stock with transaction logging
                    $this->orderService->restoreProductStock(
                        $product,
                        $variation,
                        $quantity,
                        $order->id,
                        'Order item deleted'
                    );

                    Log::info('V2 Inventory: Stock restored on item delete', [
                        'order_id' => $order->id,
                        'item_id' => $itemId,
                        'product_id' => $product->id,
                        'variation_id' => $variation?->id,
                        'quantity_restored' => $quantity,
                    ]);
                }
            }

            // Soft delete the order item with audit info
            $orderItem->update([
                'deleted_by' => auth()->id(),
                'deletion_reason' => 'Admin removed item',
            ]);
            $orderItem->delete();

            // Recalculate order totals
            $subtotal = $order->items()->sum('subtotal');
            $discount_total = $order->items()->sum('discount_total');
            $total = $subtotal - $discount_total + $order->shipping_cost;

            // Update the order and log the change
            $order->logEdit('items', 'Item removed: ' . $orderItem->product_name, null, 'Item deleted');
            $order->update([
                'subtotal' => $subtotal,
                'discount_total' => $discount_total,
                'total' => $total,
                'updated_by' => auth()->id(),
            ]);

            DB::commit();

            return redirect()->back()
                ->with('success', 'Order item removed and stock restored successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to remove order item', [
                'order_id' => $order->id,
                'item_id' => $itemId,
                'error' => $e->getMessage()
            ]);
            return redirect()->back()
                ->with('error', 'Failed to remove order item: ' . $e->getMessage());
        }
    }

    /**
     * Update order item quantity with V2 Inventory System
     */
    public function updateQuantity(Request $request, int $orderId, int $itemId)
    {
        try {
            $validated = $request->validate([
                'quantity' => 'required|integer|min:1|max:100'
            ]);

            $order = Order::findOrFail($orderId);
            $orderItem = OrderItem::where('order_id', $orderId)
                ->where('id', $itemId)
                ->firstOrFail();

            // Check if order can be updated (only pending, processing, or incomplete orders)
            if (!in_array($order->status, ['pending', 'processing', 'incomplete'])) {
                if ($request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Cannot update order in ' . $order->status . ' status'
                    ], 422);
                }
                return redirect()->back()->with('error', 'Cannot update order in ' . $order->status . ' status');
            }

            // Get product and variation for stock check
            $product = $orderItem->product;
            $variation = $orderItem->productVariation;

            // Calculate quantity difference
            $quantityDifference = $validated['quantity'] - $orderItem->quantity;
            $oldQuantity = $orderItem->quantity;

            // V2 Inventory: Check stock availability using InventoryService
            $inventoryService = app(\App\Services\Inventory\InventoryService::class);
            $availableStock = $inventoryService->getTotalStock(
                $product->id,
                $variation?->id
            );

            // Fallback to legacy stock if V2 not set up
            if ($availableStock <= 0) {
                $availableStock = $variation ? $variation->stock : $product->stock;
            }

            if ($quantityDifference > 0 && $availableStock < $quantityDifference) {
                if ($request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => "Insufficient stock. Only {$availableStock} items available."
                    ], 422);
                }
                return redirect()->back()->with('error', "Insufficient stock. Only {$availableStock} items available.");
            }

            DB::beginTransaction();

            // Update stock using V2 Inventory System (skip for incomplete orders)
            if ($order->status !== 'incomplete' && $quantityDifference != 0) {
                $adjustmentType = $quantityDifference > 0 ? 'decrease' : 'increase';
                $absQuantity = abs($quantityDifference);
                $reason = $quantityDifference > 0
                    ? 'Order item quantity increased'
                    : 'Order item quantity decreased';

                // Use OrderService for V2 inventory adjustment
                if ($quantityDifference > 0) {
                    $this->orderService->updateProductStock(
                        $product,
                        $variation,
                        $absQuantity,
                        $order->id
                    );
                } else {
                    $this->orderService->restoreProductStock(
                        $product,
                        $variation,
                        $absQuantity,
                        $order->id,
                        $reason
                    );
                }

                Log::info('V2 Inventory: Stock adjusted for quantity update', [
                    'product_id' => $product->id,
                    'variation_id' => $variation?->id,
                    'quantity_diff' => $quantityDifference,
                    'order_id' => $order->id,
                ]);
            }

            // Update order item - both subtotal and final_price
            $orderItem->quantity = $validated['quantity'];
            $orderItem->subtotal = $orderItem->quantity * $orderItem->unit_price;
            $orderItem->final_price = $orderItem->subtotal - ($orderItem->discount_total ?? 0);
            $orderItem->save();

            // Log the quantity change
            $order->logEdit(
                'item_quantity',
                "Item #{$itemId}: {$oldQuantity}",
                "Item #{$itemId}: {$validated['quantity']}",
                'Quantity updated'
            );

            // Recalculate order totals (uses item->subtotal to calculate order total)
            $this->orderService->calculateOrderTotals($order);

            // Refresh order to get updated totals
            $order->refresh();

            DB::commit();

            Log::info('Order item quantity updated', [
                'order_id' => $orderId,
                'item_id' => $itemId,
                'old_quantity' => $oldQuantity,
                'new_quantity' => $validated['quantity']
            ]);

            // Return redirect for Inertia
            return redirect()->back()->with('success', 'Quantity updated successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update order item quantity', [
                'order_id' => $orderId,
                'item_id' => $itemId,
                'error' => $e->getMessage()
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update quantity: ' . $e->getMessage()
                ], 500);
            }
            return redirect()->back()->with('error', 'Failed to update quantity: ' . $e->getMessage());
        }
    }
}
