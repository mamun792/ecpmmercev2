<?php

namespace App\Http\Controllers\Admin\Order;

use Inertia\Inertia;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\GeneralSetting;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use App\Services\Order\OrderInterface;
use App\Services\Order\OrderFilterService;
use App\Services\Product\ProductService;
use App\Exceptions\InvalidOrderDataException;
use App\Exceptions\InsufficientStockException;
use App\Services\Couriers\Pathao\PathaoService;
use App\Traits\OrderEagerLoading;
use App\DTOs\Order\UpdateOrderDTO;
use App\Http\Requests\Order\UpdateOrderRequest;
use Illuminate\Support\Facades\Gate;

class OrderController extends Controller
{
    use OrderEagerLoading;

    protected OrderInterface $orderService;
    protected ProductService $productService;
    protected PathaoService $pathaoService;
    protected OrderFilterService $filterService;

    public function __construct(
        OrderInterface $orderService,
        ProductService $productService,
        PathaoService $pathaoService,
        OrderFilterService $filterService
    ) {
        $this->orderService = $orderService;
        $this->productService = $productService;
        $this->pathaoService = $pathaoService;
        $this->filterService = $filterService;
    }

    /**
     * Display paginated list of orders with advanced filtering
     * Big Tech Pattern: Clean, maintainable filtering with dedicated service
     *
     * @param Request $request
     */
    public function index(Request $request)
    {
        // Extract and validate filters
        $rawFilters = [
            'status' => $request->input('status'),
            'payment_status' => $request->input('payment_status'),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
            'customer_search' => $request->input('customer_search'),
            'order_number' => $request->input('order_number'),
            'min_total' => $request->input('min_total'),
            'max_total' => $request->input('max_total'),
            'date_preset' => $request->input('date_preset'), // NEW: Quick date filters
            'shipping_area' => $request->input('shipping_area'), // NEW: Area filter
            'has_courier' => $request->input('has_courier'), // NEW: Courier status
        ];

        // Validate and sanitize filters
        $filters = $this->filterService->validateFilters($rawFilters);

        // Pagination and sorting
        $perPage = (int) $request->input('per_page', 10);
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');

        // Get filtered and sorted orders
        $orders = $this->orderService->getOrders([
            'per_page' => $perPage,
            'filters' => $filters,
            'sort_by' => $sortBy,
            'sort_direction' => $sortDirection,
        ]);

        // Get status counts
        $statusCounts = $this->orderService->getStatusCounts();

        // Get filter metadata for UI
        $filterMeta = [
            'active_count' => $this->filterService->getActiveFilterCount($filters),
            'summary' => $this->filterService->getFilterSummary($filters),
            'applied_filters' => $filters,
        ];

        return Inertia::render('Admin/Orders/Index', [
            'orders' => $orders,
            'statusCounts' => $statusCounts,
            'filterMeta' => $filterMeta, // NEW: Filter metadata for UI
        ]);
    }

    /**
     * Show the form for creating a new order
     */
    public function create()
    {
        // Get all products with variations for order creation
        $products = $this->productService->getAllProducts(['with_variations' => true]);

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

        return Inertia::render('Admin/Orders/Create', [
            'products' => $products,
            'paymentMethods' => $paymentMethods,
            'orderStatuses' => $orderStatuses,
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
        // Security: Use selectRaw instead of DB::raw to prevent SQL injection
        $districtCounts = DB::table('orders')
            ->select('shipping_district')
            ->selectRaw('COUNT(*) as total_orders')
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
     */
    public function edit($id, Request $request)
    {
        try {
            // Use trait-based eager loading for consistency
            $order = Order::with($this->getOrderDetailEagerLoads())->findOrFail($id);

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

        // Track changes and log edits
        $fieldsToTrack = [
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
        ];

        // Log changes before updating
        foreach ($fieldsToTrack as $field => $newValue) {
            $oldValue = $order->$field;
            if ($oldValue != $newValue) {
                $order->logEdit($field, $oldValue, $newValue, 'Basic info updated via edit page');
            }
        }

        $order->update($fieldsToTrack);

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
            // Use trait-based eager loading
            $order = Order::with($this->getOrderDetailEagerLoads())->findOrFail($id);

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

            // Get old status before update
            $order = Order::findOrFail($orderId);
            $oldStatus = $order->status;

            // Update status via service
            $order = $this->orderService->updateOrderStatus($orderId, $validated['status']);

            // Dispatch event for status change
            event(new \App\Events\Orders\OrderStatusChanged(
                $order,
                $oldStatus,
                $validated['status'],
                auth()->id()
            ));

            // If order status is delivered, automatically mark payment as paid
            if ($validated['status'] === 'delivered' && $order->payment_status !== 'paid') {
                $order->update(['payment_status' => 'paid']);
            }

            // update  caching cache
            // Update caching
            $this->updateOrderCache();

            Log::info('Order status updated', [
                'orderId' => $orderId,
                'old_status' => $oldStatus,
                'new_status' => $validated['status'],
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

    public function updateNewOrders(Request $request, $orderId)
    {
        try {
            Log::info('request', $request->all());

            $order = $this->orderService->updateNewOrder($orderId, $request->all());
            Log::info('order', $order->toArray());

            // Smart cache invalidation
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

    /**
     * Smart cache invalidation - only invalidates order-related cache
     * Instead of flushing entire cache
     */
    private function updateOrderCache(): void
    {
        Order::invalidateCache();
    }

    /**
     * Get order timeline (status history)
     * AJAX endpoint for order expansion
     */
    public function timeline(int $orderId): JsonResponse
    {
        try {
            $order = Order::findOrFail($orderId);

            // Get status history from database
            $historyRecords = $order->statusHistories()
                ->with('changedBy')
                ->orderBy('created_at', 'asc')
                ->get();

            if ($historyRecords->isNotEmpty()) {
                // Build timeline from actual status history
                $timeline = $historyRecords->map(function ($history) {
                    return [
                        'title' => ucfirst(str_replace('_', ' ', $history->new_status)),
                        'description' => $history->notes ?: 'Status changed from ' . ucfirst(str_replace('_', ' ', $history->previous_status ?? 'new')) . ' to ' . ucfirst(str_replace('_', ' ', $history->new_status)),
                        'status' => $history->new_status,
                        'user' => $history->changedBy ? $history->changedBy->name : 'System',
                        'timestamp' => $history->created_at->diffForHumans(),
                        'created_at' => $history->created_at->toISOString(),
                    ];
                })->values();
            } else {
                // No history records - build timeline from order data
                $timeline = collect();

                // Order creation event
                $timeline->push([
                    'title' => 'Order Created',
                    'description' => 'Order #' . $order->order_number . ' was placed',
                    'status' => 'pending',
                    'user' => $order->createdByUser ? $order->createdByUser->name : 'System',
                    'timestamp' => $order->created_at->diffForHumans(),
                    'created_at' => $order->created_at->toISOString(),
                ]);

                // If status changed from pending
                if ($order->status !== 'pending' && $order->updated_at->gt($order->created_at)) {
                    $timeline->push([
                        'title' => ucfirst(str_replace('_', ' ', $order->status)),
                        'description' => 'Order status updated to ' . ucfirst(str_replace('_', ' ', $order->status)),
                        'status' => $order->status,
                        'user' => $order->updatedByUser ? $order->updatedByUser->name : 'System',
                        'timestamp' => $order->updated_at->diffForHumans(),
                        'created_at' => $order->updated_at->toISOString(),
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'timeline' => $timeline
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch order timeline', [
                'order_id' => $orderId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load timeline'
            ], 500);
        }
    }
}
