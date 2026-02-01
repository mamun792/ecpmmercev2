<?php

namespace App\Http\Controllers\Admin\Order;

use Inertia\Inertia;
use App\Models\Media;
use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use App\Models\GeneralSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use App\Services\Order\OrderInterface;
use App\Services\Product\ProductService;
use App\Exceptions\InvalidOrderDataException;
use App\Exceptions\InsufficientStockException;
use App\Services\Couriers\Pathao\PathaoService;



class OrderController extends Controller
{
    protected $orderService;
    protected $productService;
    protected $pathaoService;

    public function __construct(OrderInterface $orderService, ProductService $productService, PathaoService $pathaoService)
    {
        $this->orderService = $orderService;
        $this->productService = $productService;
        $this->pathaoService = $pathaoService;
    }

    /**
     * Display paginated list of orders
     *
     * @param Request $request
     *
     *
     */
    public function index(Request $request)
    {
        //Log::info('request', $request->all());
        $filters = [
            'status' => $request->input('status'),
            'payment_status' => $request->input('payment_status'),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
            'customer_search' => $request->input('customer_search'),
            'order_number' => $request->input('order_number'),
            'min_total' => $request->input('min_total'),
            'max_total' => $request->input('max_total'),
        ];

        $perPage = (int) $request->input('per_page', 10);
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');

        $orders = $this->orderService->getOrders([
            'per_page' => $perPage,
            'filters' => $filters,
            'sort_by' => $sortBy,
            'sort_direction' => $sortDirection,
        ]);

        //return $orders;

        // Log::info('orders', [
        //     'orders' => $orders,
        // ]);


        $statusCounts = $this->orderService->getStatusCounts();

        //$cities = $this->pathaoService->getCities();


        //return $orders;

        //return $statusCounts;

        return Inertia::render('Admin/Orders/Index', [
            'orders' => $orders,
            'statusCounts' => $statusCounts,
            // 'cities' => $cities,
        ]);
    }



    /**
     * Display incomplete orders
     */
    public function incompleteOrders(Request $request)
    {
        $filters = [
            'status' => 'incomplete',
            'payment_status' => $request->input('payment_status'),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
            'customer_search' => $request->input('customer_search'),
            'order_number' => $request->input('order_number'),
        ];

        $perPage = (int) $request->input('per_page', 10);
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');

        $orders = $this->orderService->getOrders([
            'per_page' => $perPage,
            'filters' => $filters,
            'sort_by' => $sortBy,
            'sort_direction' => $sortDirection,
        ]);

        return Inertia::render('Admin/Orders/IncompleteOrders', [
            'orders' => $orders,
        ]);
    }

    public function districtWiseOrders()
    {
        $districtCounts = DB::table('orders')
            ->select('shipping_district', DB::raw('COUNT(*) as total_orders'))
            ->whereNotNull('shipping_district')
            ->groupBy('shipping_district')
            ->get();

        // Load district coordinates
        $geoData = collect(json_decode(file_get_contents(storage_path('app/bd_locations.json')), true));

        // Merge coordinates with counts
        $locations = $districtCounts->map(function ($item) use ($geoData) {
            $match = $geoData->firstWhere('district', $item->shipping_district);

            return [
                'district' => $item->shipping_district,
                'total_orders' => $item->total_orders,
                'lat' => $match['lat'] ?? null,
                'lng' => $match['lng'] ?? null,
            ];
        });

        return inertia('Admin/Orders/OrderMap', [
            'locations' => $locations
        ]);
    }



    /**
     * Display the edit form for an order
     *
     * @param int $id
     *
     */
    public function edit($id, Request $request)
    {

        try {
            //$order = Order::with(['items.product', 'items.variation'])->findOrFail($id);
            $order = Order::with([
                'items.product' => function($query) {
                    $query->withTrashed();
                },
                'items.productVariation' => function($query) {
                    $query->withTrashed();
                },
                'items.productVariation.attributes.value' => function($query) {
                    $query->withTrashed();
                },
                'items.productVariation.attributes.value.attribute' => function($query) {
                    $query->withTrashed();
                }
            ])->findOrFail($id);


            // Only allow editing pending, processing, or incomplete orders
            // if (!in_array($order->status, ['pending', 'processing', 'incomplete'])) {
            //     return redirect()->route('orders.show', $id)
            //         ->with('error', 'Cannot edit orders in ' . $order->status . ' status');
            // }

            // Get payment methods for dropdown
            $paymentMethods = [
                'cod' => 'Cash on Delivery',
                'credit_card' => 'Credit Card',
                'paypal' => 'PayPal',
                'bank_transfer' => 'Bank Transfer'
            ];

            // Get order statuses for dropdown
            $orderStatuses = [
                'pending' => 'Pending',
                'processing' => 'Processing',
                'completed' => 'Completed',
                'cancelled' => 'Cancelled',
                'incomplete' => 'Incomplete'
            ];

            // Get payment statuses for dropdown
            $paymentStatuses = [
                'unpaid' => 'Unpaid',
                'paid' => 'Paid',
                'refunded' => 'Refunded'
            ];

            // Get all products
            $products = $this->productService->getAllProductsForAdmin($request);

            // Transform order items to include variation deleted status
            // No need to load again - already loaded with withTrashed() above
            $order->items->each(function($item) {
                // Add is_deleted flag to the product object
                if ($item->product) {
                    $item->product->is_deleted = $item->product->trashed();
                }
                
                if ($item->product_variation_id && $item->productVariation) {
                    // Add is_deleted flag to the productVariation object
                    $item->productVariation->is_deleted = $item->productVariation->trashed();
                }
            });

            return Inertia::render('Admin/Orders/Edit', [
                'order' => $order,
                'products' => $products,
                'paymentMethods' => $paymentMethods,
                'orderStatuses' => $orderStatuses,
                'paymentStatuses' => $paymentStatuses
            ]);

            // return view('orders.edit', compact(
            //     'order',
            //     'paymentMethods',
            //     'orderStatuses',
            //     'paymentStatuses'
            // ));
        } catch (\Exception $e) {
            Log::error('Order edit page error', [
                'order_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->route('orders.index')
                ->with('error', 'Order not found: ' . $e->getMessage());
        }
    }

    /**
     * Update the specified order
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $id)
    {
        //Log::info('request', $request->all());
        //return $request->all();
        try {
            // Prepare the update data
            $updateData = $request->only([
                'customer_email',
                'customer_name',
                'shipping_address',
                'shipping_cost',
                'payment_method',
                'status',
                'payment_status'
            ]);

            // Process item updates if any
            if ($request->has('items')) {
                $itemUpdates = [];
                foreach ($request->input('items') as $itemId => $itemData) {
                    // Skip if the item was not included in the form
                    if (!isset($itemData['included'])) {
                        continue;
                    }

                    // Determine action (update or remove)
                    $action = isset($itemData['remove']) ? 'remove' : 'update';

                    $itemUpdates[] = [
                        'id' => $itemId,
                        'quantity' => $itemData['quantity'] ?? 0,
                        'action' => $action
                    ];
                }

                if (!empty($itemUpdates)) {
                    $updateData['items'] = $itemUpdates;
                }
            }

            // Update the order
            $order = $this->orderService->updateOrder($id, $updateData);

            return response()->json([
                'success' => true,
                'message' => 'Order updated successfully',
                'order' => $order
            ], 200);
        } catch (InsufficientStockException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient stock available'
            ], 422);
        } catch (InvalidOrderDataException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid order data: ' . $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update order: ' . $e->getMessage()
            ], 500);
        }
    }



    public function updateBasicInfo(Request $request, $id)
    {
        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'nullable|string|max:20',
            'customer_email' => 'nullable|email|max:255',
            'shipping_address' => 'required|string',
            'admin_notes' => 'nullable|string',
            'payment_status' => 'required|in:unpaid,paid,refunded,failed',
            'payment_method' => 'required|string|max:50',
            'discount' => 'nullable|numeric|min:0',
            'discount_type' => 'nullable|in:fixed,percentage',
            'shipping_cost' => 'nullable|numeric|min:0',
            'area' => 'nullable|in:inside_dhaka,outside_dhaka',
            'status' => 'nullable|in:pending,processing,cancelled,shipped,delivered,returned,incomplete,on_hold,confirmed',
        ]);

        $order = Order::findOrFail($id);

        $order->update([
            'customer_name' => $validated['customer_name'],
            'customer_phone' => $validated['customer_phone'],
            'customer_email' => $validated['customer_email'],
            'shipping_address' => $validated['shipping_address'],
            'admin_notes' => $validated['admin_notes'] ?? null,
            'payment_status' => $validated['payment_status'],
            'payment_method' => $validated['payment_method'],
            'pos_discount' => $validated['discount'] ?? 0,
            'discount_type' => $validated['discount_type'] ?? 'fixed',
            'shipping_cost' => $validated['shipping_cost'] ?? $order->shipping_cost,
            'area' => $validated['area'] ?? $order->area,
            'status' => $validated['status'] ?? $order->status,
        ]);


        $this->orderService->calculateOrderTotals($order);



        Cache::flush();

        return redirect()->back()
            ->with('success', 'Order basic information updated successfully');
    }

    /**
     * Quick update admin notes only
     *
     * @param Request $request
     * @param int $orderId
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateAdminNotes(Request $request, $orderId)
    {
        try {
            $validated = $request->validate([
                'admin_notes' => 'nullable|string|max:2000',
            ]);

            $order = Order::findOrFail($orderId);
            $order->update([
                'admin_notes' => $validated['admin_notes'] ?? null,
            ]);

            Cache::flush();

            return redirect()->back()
                ->with('success', 'Admin notes updated successfully');
        } catch (\Exception $e) {
            Log::error('Failed to update admin notes', [
                'order_id' => $orderId,
                'error' => $e->getMessage()
            ]);

            return redirect()->back()
                ->with('error', 'Failed to update admin notes: ' . $e->getMessage());
        }
    }


    /**
     * Update courier details for an order
     *
     * @param Request $request
     * @param int $orderId
     *
     */
    public function updateCourierDetails(Request $request, int $orderId)
    {
        try {
            $validated = $request->validate([
                'courier_name' => 'nullable|string|max:255',
                'city_name' => 'nullable|string|max:255',
                'zone_name' => 'nullable|string|max:255',
                'area_name' => 'nullable|string|max:255',
                'city_id' => 'nullable|integer',
                'zone_id' => 'nullable|integer',
                'area_id' => 'nullable|integer',
            ]);

            $order = $this->orderService->updateCourierDetails($orderId, $validated);
            \Artisan::call('cache:clear');

            return redirect()->back()
                ->with('success', 'Courier details updated successfully');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to update courier details: ' . $e->getMessage());
        }
    }


    public function show($id)
    {
        try {
            $order = Order::with([
                'items.product',
                'items.productVariation',
                'items.productVariation.attributes.value' => function($query) {
                    $query->withTrashed();
                },
                'items.productVariation.attributes.value.attribute' => function($query) {
                    $query->withTrashed();
                }
            ])->findOrFail($id);

            $settings = GeneralSetting::select('app_name', 'address', 'store_email', 'store_phone_number')->first();

            $logo = Media::select('logo')->first();

            return Inertia::render('Admin/Orders/Show', [
                'order' => $order,
                'settings' => $settings,
                'logo' => $logo
            ]);
        } catch (\Exception $e) {
            return redirect()->route('orders.index')
                ->with('error', 'Order not found: ' . $e->getMessage());
        }
    }

    /**
     * Remove an order item via AJAX
     *
     * @param int $orderId
     * @param int $itemId
     * @return \Illuminate\Http\JsonResponse
     */
    public function removeItem($orderId, $itemId)
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



    public function destroy($id)
    {

        try {
            $this->orderService->deleteOrder($id);
            // update  caching cache
            $this->updateOrderCache();
            return redirect()->back()
                ->with('success', 'Order deleted successfully');
        } catch (\Exception $e) {
            Log::error('Order deletion failed: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to delete order: ' . $e->getMessage());
        }
    }



    public function OderupdateStatus(Request $request, $orderId)
    {



        try {
            $validated = $request->validate([
                'status' => 'required|string|max:255',
            ]);



            $order = $this->orderService->updateOrderStatus($orderId, $validated['status']);

            // If order status is delivered, automatically mark payment as paid
            if ($validated['status'] === 'delivered' && $order->payment_status !== 'paid') {
                $order->update(['payment_status' => 'paid']);
            }

            // update  caching cache
            // Update caching
            $this->updateOrderCache();


            Log::info('Order status updated', [
                'orderId' => $orderId,
                'status' => $validated['status'],
                'payment_status' => $order->payment_status
            ]);

            return response()->json([
                'message' => 'Order status updated successfully',
                'order' => $order
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update order status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function updateOrderCache()
    {

        Cache::flush();
    }

    public function updateNewOrders(Request $request, $orderId)
    {
        try {
            Log::info('request', $request->all());

            $order = $this->orderService->updateNewOrder($orderId, $request->all());
            Log::info('order', $order->toArray());

            // update caching
            $this->updateOrderCache();

            // Return JSON for Inertia AJAX requests
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Item added to order successfully',
                    'order' => $order
                ], 200);
            }

            return redirect()->back()
                ->with('success', 'Order updated successfully');
        } catch (InsufficientStockException $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient stock available'
                ], 422);
            }
            return redirect()->back()
                ->with('error', 'Insufficient stock available: ' . $e->getMessage());
        } catch (InvalidOrderDataException $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid order data: ' . $e->getMessage()
                ], 422);
            }
            return redirect()->back()
                ->with('error', 'Invalid order data: ' . $e->getMessage());
        } catch (\Exception $e) {
            Log::error('Order update failed', [
                'order_id' => $orderId,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update order: ' . $e->getMessage()
                ], 500);
            }
            return redirect()->back()
                ->with('error', 'Failed to update order status: ' . $e->getMessage());
        }
    }


    public function destroyItem(Order $order, $item)
    {
        try {
            DB::beginTransaction();

            // Find the order item and ensure it belongs to the order
            $orderItem = OrderItem::where('order_id', $order->id)
                ->where('id', $item)
                ->firstOrFail();

            // Restore stock if the order is not incomplete (incomplete orders don't deduct stock)
            if ($order->status !== 'incomplete') {
                $quantity = $orderItem->quantity;

                if ($orderItem->product_variation_id) {
                    // Variable product - restore stock to variation AND main product
                    $variation = $orderItem->productVariation;
                    $product = $orderItem->product;

                    if ($variation) {
                        $variation->stock += $quantity;
                        $variation->sold_stock = max(0, $variation->sold_stock - $quantity);
                        $variation->save();

                        Log::info('Stock restored to variation on item delete', [
                            'order_id' => $order->id,
                            'item_id' => $item,
                            'variation_id' => $variation->id,
                            'quantity_restored' => $quantity,
                            'new_stock' => $variation->stock
                        ]);
                    }

                    // Also restore stock to main product
                    if ($product) {
                        $product->stock += $quantity;
                        $product->sold_stock = max(0, $product->sold_stock - $quantity);
                        $product->save();

                        Log::info('Stock restored to main product on item delete', [
                            'order_id' => $order->id,
                            'item_id' => $item,
                            'product_id' => $product->id,
                            'quantity_restored' => $quantity,
                            'new_stock' => $product->stock
                        ]);
                    }
                } else {
                    // Simple product - restore stock to product
                    $product = $orderItem->product;
                    if ($product) {
                        $product->stock += $quantity;
                        $product->sold_stock = max(0, $product->sold_stock - $quantity);
                        $product->save();

                        Log::info('Stock restored to product on item delete', [
                            'order_id' => $order->id,
                            'item_id' => $item,
                            'product_id' => $product->id,
                            'quantity_restored' => $quantity,
                            'new_stock' => $product->stock
                        ]);
                    }
                }
            }

            // Delete the order item
            $orderItem->delete();

            // Recalculate order totals
            $subtotal = $order->items()->sum('subtotal');
            $discount_total = $order->items()->sum('discount_total');
            $total = $subtotal - $discount_total + $order->shipping_cost;

            // Update the order
            $order->update([
                'subtotal' => $subtotal,
                'discount_total' => $discount_total,
                'total' => $total,
            ]);

            DB::commit();

            // Clear cache
            $this->updateOrderCache();

            return redirect()->back()
                ->with('success', 'Order item removed and stock restored successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to remove order item', [
                'order_id' => $order->id,
                'item_id' => $item,
                'error' => $e->getMessage()
            ]);
            return redirect()->back()
                ->with('error', 'Failed to remove order item: ' . $e->getMessage());
        }
    }


public function downloadInvoice(Order $order)
{
    // Eager load the nested relationships
    $order->load([
        'items.product',
        'items.productVariation',
        'items.productVariation.attributes.value' => function($query) {
            $query->withTrashed();
        },
        'items.productVariation.attributes.value.attribute' => function($query) {
            $query->withTrashed();
        }
    ]);

    // Return or pass to a view or PDF generation
    //return $order;

    //return view('invoices.order', ['order' => $order]);

    // If generating a PDF
    $pdf = Pdf::loadView('invoices.order', ['order' => $order]);
    return $pdf->download('invoice-' . $order->id . '.pdf');
}


    public function bulkDownloadInvoices(Request $request)
    {
        $orderIds = $request->input('order_ids'); // e.g. [3, 1, 2]

        // Fetch orders sorted by ID
        $orders = Order::with([
            'items.product',
            'items.productVariation',
            'items.productVariation.attributes.value' => function($query) {
                $query->withTrashed();
            },
            'items.productVariation.attributes.value.attribute' => function($query) {
                $query->withTrashed();
            }
        ])
        ->whereIn('id', $orderIds)
        ->orderBy('id')
        ->get();

            //return view('invoices.bulk', ['orders' => $orders]);

        // Pass all orders to a combined PDF view
        $pdf = Pdf::loadView('invoices.bulk', ['orders' => $orders]);

        return $pdf->download('bulk-invoices.pdf');
    }



        public function bulkPrintInvoices(Request $request)
    {
        $orderIds = explode(',', $request->query('order_ids')); // e.g. "3,1,2"

        // Fetch orders sorted by ID
        $orders = Order::with([
            'items.product',
            'items.productVariation',
            'items.productVariation.attributes.value' => function($query) {
                $query->withTrashed();
            },
            'items.productVariation.attributes.value.attribute' => function($query) {
                $query->withTrashed();
            }
        ])
        ->whereIn('id', $orderIds)
        ->orderBy('id')
        ->get();

        $settings = GeneralSetting::select('app_name', 'address', 'store_email', 'store_phone_number')->first();
        $logo = Media::select('logo')->first();

        //return $orders;

        // Return view for printing
        return view('invoices.bulk-print', [
            'orders' => $orders,
            'settings' => $settings,
            'logo' => $logo
        ]);
    }

    /**
     * Update order item quantity
     */
    public function updateItemQuantity(Request $request, $orderId, $itemId)
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

            // Check stock availability
            $availableStock = $variation ? $variation->stock : $product->stock;
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

            // Update stock (skip for incomplete orders)
            if ($order->status !== 'incomplete' && $quantityDifference != 0) {
                if ($variation) {
                    // Update variation stock
                    $variation->stock -= $quantityDifference;
                    $variation->sold_stock += $quantityDifference;
                    $variation->save();

                    // Also update main product stock
                    $product->stock -= $quantityDifference;
                    $product->sold_stock += $quantityDifference;
                    $product->save();

                    Log::info('Stock updated for variable product', [
                        'product_id' => $product->id,
                        'variation_id' => $variation->id,
                        'quantity_diff' => $quantityDifference,
                        'product_new_stock' => $product->stock,
                        'variation_new_stock' => $variation->stock
                    ]);
                } else {
                    // Update simple product stock
                    $product->stock -= $quantityDifference;
                    $product->sold_stock += $quantityDifference;
                    $product->save();

                    Log::info('Stock updated for simple product', [
                        'product_id' => $product->id,
                        'quantity_diff' => $quantityDifference,
                        'product_new_stock' => $product->stock
                    ]);
                }
            }

            // Update order item - both subtotal and final_price
            $orderItem->quantity = $validated['quantity'];
            $orderItem->subtotal = $orderItem->quantity * $orderItem->unit_price;
            $orderItem->final_price = $orderItem->subtotal - ($orderItem->discount_total ?? 0);
            $orderItem->save();

            // Recalculate order totals (uses item->subtotal to calculate order total)
            $this->orderService->calculateOrderTotals($order);

            // Refresh order to get updated totals
            $order->refresh();

            DB::commit();

            // Clear cache
            $this->updateOrderCache();

            Log::info('Order item quantity updated', [
                'order_id' => $orderId,
                'item_id' => $itemId,
                'old_quantity' => $orderItem->quantity - $quantityDifference,
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
