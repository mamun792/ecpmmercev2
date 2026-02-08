<?php

namespace App\Services\Inventory;

use App\Models\Product;
use App\Models\InventoryStock;
use App\Models\InventoryTransaction;
use App\Services\Notification\NotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AutoReorderService
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Check all products for reorder triggers
     */
    public function checkReorderTriggers()
    {
        $criticalProducts = $this->getCriticalStockProducts();
        $lowStockProducts = $this->getLowStockProducts();

        $alerts = [
            'critical' => $criticalProducts,
            'low' => $lowStockProducts,
            'summary' => [
                'critical_count' => $criticalProducts->count(),
                'low_count' => $lowStockProducts->count(),
                'total_affected' => $criticalProducts->count() + $lowStockProducts->count()
            ]
        ];

        // Send notifications if needed
        if ($criticalProducts->count() > 0) {
            $this->sendCriticalStockAlert($criticalProducts);
        }

        if ($lowStockProducts->count() > 0) {
            $this->sendLowStockAlert($lowStockProducts);
        }

        return $alerts;
    }

    /**
     * Get products with critical stock levels (out of stock or below safety stock)
     */
    private function getCriticalStockProducts()
    {
        return InventoryStock::with(['product.category'])
            ->whereRaw('available_quantity <= (minimum_threshold * 0.2)')
            ->orWhere('available_quantity', '<=', 0)
            ->whereHas('product', function($query) {
                $query->whereNull('deleted_at');
            })
            ->get()
            ->map(function ($stock) {
                return [
                    'id' => $stock->product->id,
                    'name' => $stock->product->name,
                    'category' => $stock->product->category?->name ?? 'Uncategorized',
                    'current_stock' => $stock->available_quantity,
                    'minimum_threshold' => $stock->minimum_threshold,
                    'reorder_point' => $stock->reorder_point,
                    'suggested_order_qty' => max($stock->reorder_quantity, $stock->minimum_threshold * 2),
                    'urgency' => 'critical',
                    'days_out_of_stock' => $this->calculateDaysOutOfStock($stock),
                    'average_daily_sales' => $this->getAverageDailySales($stock->product_id)
                ];
            });
    }

    /**
     * Get products with low stock levels
     */
    private function getLowStockProducts()
    {
        return InventoryStock::with(['product.category'])
            ->whereRaw('available_quantity <= minimum_threshold')
            ->whereRaw('available_quantity > (minimum_threshold * 0.2)')
            ->whereHas('product', function($query) {
                $query->whereNull('deleted_at');
            })
            ->get()
            ->map(function ($stock) {
                return [
                    'id' => $stock->product->id,
                    'name' => $stock->product->name,
                    'category' => $stock->product->category?->name ?? 'Uncategorized',
                    'current_stock' => $stock->available_quantity,
                    'minimum_threshold' => $stock->minimum_threshold,
                    'reorder_point' => $stock->reorder_point,
                    'suggested_order_qty' => max($stock->reorder_quantity, $stock->minimum_threshold),
                    'urgency' => 'low',
                    'estimated_days_remaining' => $this->estimateDaysRemaining($stock),
                    'average_daily_sales' => $this->getAverageDailySales($stock->product_id)
                ];
            });
    }

    /**
     * Auto-generate purchase orders for critical items
     */
    public function generateAutoPurchaseOrders()
    {
        $criticalProducts = $this->getCriticalStockProducts();
        $orders = [];

        foreach ($criticalProducts as $product) {
            if ($product['suggested_order_qty'] > 0) {
                $orders[] = [
                    'product_id' => $product['id'],
                    'product_name' => $product['name'],
                    'current_stock' => $product['current_stock'],
                    'order_quantity' => $product['suggested_order_qty'],
                    'urgency' => $product['urgency'],
                    'estimated_cost' => $this->estimatePurchaseCost($product['id'], $product['suggested_order_qty']),
                    'created_at' => now()
                ];
            }
        }

        // Log auto-generated orders
        if (count($orders) > 0) {
            Log::info('Auto-generated purchase orders', [
                'count' => count($orders),
                'orders' => $orders
            ]);
        }

        return $orders;
    }

    /**
     * Update reorder points based on sales history
     */
    public function optimizeReorderPoints()
    {
        $products = Product::with('inventoryStocks')->get();
        $optimizations = [];

        foreach ($products as $product) {
            foreach ($product->inventoryStocks as $stock) {
                $averageDailySales = $this->getAverageDailySales($product->id);
                $leadTimeDays = 7; // Default 7 days, can be made configurable
                $safetyStockDays = 3; // 3 days safety buffer

                $optimalReorderPoint = ($averageDailySales * ($leadTimeDays + $safetyStockDays));
                $optimalReorderQty = $averageDailySales * 30; // 30 days worth

                if ($stock->reorder_point != $optimalReorderPoint || $stock->reorder_quantity != $optimalReorderQty) {
                    $stock->update([
                        'reorder_point' => $optimalReorderPoint,
                        'reorder_quantity' => $optimalReorderQty
                    ]);

                    $optimizations[] = [
                        'product' => $product->name,
                        'old_reorder_point' => $stock->reorder_point,
                        'new_reorder_point' => $optimalReorderPoint,
                        'old_reorder_qty' => $stock->reorder_quantity,
                        'new_reorder_qty' => $optimalReorderQty
                    ];
                }
            }
        }

        return $optimizations;
    }

    private function calculateDaysOutOfStock($stock)
    {
        if ($stock->available_quantity > 0) {
            return 0;
        }

        $lastStockTransaction = InventoryTransaction::where('product_id', $stock->product_id)
            ->where('quantity_change', '<', 0)
            ->where('quantity_after', '<=', 0)
            ->orderBy('created_at', 'desc')
            ->first();

        if ($lastStockTransaction) {
            return now()->diffInDays($lastStockTransaction->created_at);
        }

        return 1; // Default to 1 day if we can't determine
    }

    private function estimateDaysRemaining($stock)
    {
        $averageDailySales = $this->getAverageDailySales($stock->product_id);

        if ($averageDailySales <= 0) {
            return 999; // Infinite if no sales
        }

        return ceil($stock->available_quantity / $averageDailySales);
    }

    private function getAverageDailySales($productId)
    {
        $thirtyDaysAgo = Carbon::now()->subDays(30);

        $totalSold = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('order_items.product_id', $productId)
            ->where('orders.created_at', '>=', $thirtyDaysAgo)
            ->where('orders.status', 'completed')
            ->sum('order_items.quantity');

        return $totalSold / 30;
    }

    private function estimatePurchaseCost($productId, $quantity)
    {
        $product = Product::find($productId);
        return ($product->cost_price ?? $product->price * 0.7) * $quantity;
    }

    private function sendCriticalStockAlert($products)
    {
        $this->notificationService->sendAlert([
            'type' => 'critical_stock',
            'title' => 'Critical Stock Alert',
            'message' => count($products) . ' products are out of stock or critically low',
            'data' => $products->take(5)->toArray()
        ]);
    }

    private function sendLowStockAlert($products)
    {
        $this->notificationService->sendAlert([
            'type' => 'low_stock',
            'title' => 'Low Stock Alert',
            'message' => count($products) . ' products are running low on stock',
            'data' => $products->take(5)->toArray()
        ]);
    }
}
