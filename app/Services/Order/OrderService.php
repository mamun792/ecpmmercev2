<?php

namespace App\Services\Order;

use App\Events\OrderCreated;
use App\Events\OrderUpdated;
use App\Exceptions\InsufficientStockException;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\Campaign;
use Illuminate\Support\Facades\DB;
use App\Repository\Order\OrderRepositoryInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Exceptions\InvalidOrderDataException;
use App\Exceptions\OrderNotFoundException;
use App\Models\Cart;
use App\Services\Cart\CartService;
use App\Services\Coupon\CouponService;
use App\Services\Inventory\InventoryService;
use App\Services\Order\OrderFilterService;
use App\DTOs\InventoryAdjustmentDTO;
use Illuminate\Support\Facades\Cache;
use App\Models\Notification;
use App\Traits\OrderEagerLoading;

class OrderService implements OrderInterface
{
    use OrderEagerLoading;

    protected OrderRepositoryInterface $orderRepository;
    protected CouponService $couponService;
    protected CartService $cartService;
    protected InventoryService $inventoryService;
    protected OrderFilterService $filterService;

    public function __construct(
        OrderRepositoryInterface $orderRepository,
        CouponService $couponService,
        CartService $cartService,
        InventoryService $inventoryService,
        OrderFilterService $filterService
    ) {
        $this->orderRepository = $orderRepository;
        $this->couponService = $couponService;
        $this->cartService = $cartService;
        $this->inventoryService = $inventoryService;
        $this->filterService = $filterService;
    }

    /**
     * Create a new order with proper exception handling
     *
     * @param array $orderData
     * @return Order
     * @throws InsufficientStockException|InvalidOrderDataException|\Exception
     */
    public function createOrder(array $orderData): Order
    {
        try {
            // Validate order data
            $validatedData = $this->validateOrderPayload($orderData);


            // Log input data for traceability
            // Log::info('Order creation started', [
            //     'order_data' => $validatedData
            // ]);

            if (empty($validatedData)) {
                throw new InvalidOrderDataException(
                    'Order data is empty or invalid',
                    ['data' => $orderData]
                );
            }
            //   die();

            return DB::transaction(function () use ($validatedData) {
                // Validate and resolve product variations
                $processedItems = $this->resolveAndValidateOrderItems($validatedData['items'], $validatedData['status'] ?? 'pending');

                $customerDistrict = $this->findCustomerDistrict($validatedData['shipping_address']);

                //Log::info('Matched location', ['matched' => $customerDistrict]);

                // Create order record
                $order = $this->createOrderRecord($validatedData, $customerDistrict);



                // Process and save order items
                $this->processOrderItems($order, $processedItems);

                // Calculate and update order totals
                $this->calculateOrderTotals($order);

                // Log successful order creation
                // Log::info('Order created successfully', [
                //     'order_number' => $order->order_number,
                //     'user_id' => $order->user_id,
                //     'customer_email' => $order->customer_email,
                //     'total' => $order->total
                // ]);

                $itemWithCoupon = collect($validatedData['items'])
                    ->first(fn($item) => !empty($item['coupon_code']));

                // Skip coupon adjustment for incomplete orders
                if ($itemWithCoupon && $order->status !== 'incomplete') {
                    $this->couponService->adjustCouponLimit(
                        $itemWithCoupon['coupon_code']
                    );
                }


                // Dispatch event for cache clearing (including incomplete orders)
                event(new OrderCreated($order)); // Dispatch event to clear cache
                // clear cart

                // Clear cart only if it's not a landing order and not an incomplete order
                if ((empty($validatedData['landing_order']) || $validatedData['landing_order'] == false) && $order->status !== 'incomplete') {
                    $check = $this->cartService->clearCart($order->user_id, $order->session_id);

                    // Log::info('Cart cleared successfully', [
                    //     'session_id' => $order->session_id,
                    //     'user_id' => $order->user_id,
                    //     'check' => $check
                    // ]);
                }

                return $order;
            });
        } catch (InsufficientStockException $e) {
            Log::error('Order creation failed: Insufficient stock', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        } catch (InvalidOrderDataException $e) {
            Log::error('Order creation failed: Invalid data', [
                'message' => $e->getMessage(),
                'errors' => $e->getValidationErrors(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        } catch (\Exception $e) {
            Log::error('Order creation failed: Unexpected error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Create a new order record
     *
     * @param array $validatedData
     * @return Order
     */
    protected function createOrderRecord(array $validatedData, ?string $customerDistrict): Order
    {
        // Log for debugging
        Log::info('Creating order record', [
            'status' => $validatedData['status'] ?? 'pending',
            'session_id' => $validatedData['session_id'] ?? null,
            'has_session_id' => !empty($validatedData['session_id']),
            'is_incomplete' => ($validatedData['status'] ?? 'pending') === 'incomplete'
        ]);

        // Find or create cart if session_id is provided and status is incomplete
        $cartId = null;
        if (!empty($validatedData['session_id']) && ($validatedData['status'] ?? 'pending') === 'incomplete') {
            $cart = Cart::where('session_id', $validatedData['session_id'])
                ->when(!empty($validatedData['user_id']), function ($query) use ($validatedData) {
                    return $query->where('user_id', $validatedData['user_id']);
                })
                ->first();

            if (!$cart) {
                // Create new cart if not found
                $cart = Cart::create([
                    'session_id' => $validatedData['session_id'],
                    'user_id' => $validatedData['user_id'] ?? null,
                    'total' => 0,
                    'status' => 'active'
                ]);
                Log::info('Created new cart for incomplete order', [
                    'cart_id' => $cart->id,
                    'session_id' => $validatedData['session_id'],
                    'user_id' => $validatedData['user_id'] ?? null
                ]);
            }

            $cartId = $cart->id;

            // Log for debugging
            Log::info('Incomplete order cart lookup', [
                'session_id' => $validatedData['session_id'],
                'user_id' => $validatedData['user_id'] ?? null,
                'cart_found_or_created' => true,
                'cart_id' => $cartId
            ]);
        }

        $order = Order::create([
            'order_number' => $this->generateUniqueOrderNumber(),
            'user_id' => $validatedData['user_id'] ?? null,
            'session_id' => $validatedData['session_id'] ?? null,
            'cart_id' => $cartId,
            'customer_email' => $validatedData['customer_email'] ?? null,
            'customer_phone' => $validatedData['customer_phone'],
            'customer_name' => $validatedData['customer_name'],
            'shipping_address' => $validatedData['shipping_address'],
            'shipping_district' => $customerDistrict ?? null,
            'area' => $validatedData['area'] ?? null,
            'customer_notes' => $validatedData['customer_notes'] ?? null,
            'shipping_cost' => $validatedData['shipping_cost'] ?? 0,
            'payment_method' => $validatedData['payment_method'],
            'status' => $validatedData['status'] ?? 'pending',
            'total' => 0, // Will be calculated later
            'subtotal' => 0, // Will be calculated later
            // Persist POS discount fields
            'pos_discount' => $validatedData['pos_discount'] ?? 0,
            'discount_type' => $validatedData['discount_type'] ?? null,
            // 'discount_total' => 0, // Will be calculated later
            'payment_status' => $validatedData['payment_status'] ?? 'unpaid',
        ]);

        // Record initial status in history
        $initialStatus = $validatedData['status'] ?? 'pending';
        $order->recordStatusChange($initialStatus, "Order #{$order->order_number} was placed");

        // Log the created order's cart_id
        Log::info('Order created with cart_id', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'cart_id' => $order->cart_id,
            'status' => $order->status
        ]);

        return $order;
    }

    /**
     * Validate order payload
     *
     * @param array $data
     * @return array
     * @throws InvalidOrderDataException
     */
    protected function validateOrderPayload(array $orderData): array
    {
        $validator = validator($orderData, [
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'session_id' => ['nullable', 'string', 'max:255'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:255'],
            'landing_order' => ['nullable', 'boolean'],
            'status' => ['nullable', 'in:pending,processing,cancelled,shipped,delivered,returned,incomplete,on_hold,confirmed'],
            'customer_name' => ['required', 'string', 'max:255'],
            'shipping_address' => ['required', 'string', 'max:500'],
            'area' => ['nullable', 'string', 'max:255'],
            'customer_notes' => ['nullable', 'string', 'max:1000'],
            'shipping_cost' => ['nullable', 'numeric', 'min:0', 'max:10000'],
            'payment_method' => ['required'],
            'payment_status' => ['required', 'in:unpaid,paid,refunded'],
            // POS discount fields
            'pos_discount' => ['nullable', 'numeric', 'min:0'],
            'discount_type' => ['nullable', 'in:percentage,fixed'],
            // Online coupon fields (root level)
            'coupon_code' => ['nullable', 'string'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.product_variation_id' => ['nullable', 'integer', 'exists:product_variations,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:100'],
            'items.*.coupon_code' => ['nullable', 'string'],
            'items.*.discount_type' => ['nullable', 'string', 'in:percentage,fixed'],
            'items.*.discount_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        // if ($validator->fails()) {
        //     throw new InvalidOrderDataException(
        //         'Invalid order data provided',
        //         $validator->errors()->toArray()
        //     );
        // }

        if ($validator->fails()) {
            throw new InvalidOrderDataException('Invalid order data provided', $validator->errors()->toArray());
        }

        return $validator->validated();
    }

    /**
     * Resolve and validate product variations for order items
     * Uses V2 Inventory System for stock validation
     *
     * @param array $items
     * @param string $orderStatus
     * @return array
     * @throws InsufficientStockException
     */
    protected function resolveAndValidateOrderItems(array $items, string $orderStatus = 'pending'): array
    {
        $processedItems = [];
        $stockErrors = [];

        foreach ($items as $index => $itemData) {
            // Find the product with campaigns
            $product = Product::with('campaigns')->findOrFail($itemData['product_id']);

            // Resolve product variation if provided
            $productVariation = null;
            if (!empty($itemData['product_variation_id'])) {
                try {
                    $productVariation = ProductVariation::where('id', $itemData['product_variation_id'])
                        ->where('product_id', $product->id)
                        ->firstOrFail();
                } catch (\Exception $e) {
                    Log::warning('Product variation not found', [
                        'product_id' => $product->id,
                        'variation_id' => $itemData['product_variation_id'] ?? null
                    ]);
                    $productVariation = null;
                }
            }

            // Skip stock validation for incomplete orders or pre-order products
            if ($orderStatus !== 'incomplete' && !$product->is_pre_order) {
                // V2 Inventory: Use InventoryService for stock check
                $availableStock = $this->inventoryService->getTotalStock(
                    $product->id,
                    $productVariation?->id
                );

                // Fallback to old stock columns if V2 inventory not set up
                if ($availableStock <= 0) {
                    $availableStock = $productVariation ? $productVariation->stock : $product->stock;
                }

                // Validate stock availability
                if ($availableStock < $itemData['quantity']) {
                    $stockErrors[] = [
                        'product' => $product->name,
                        'variation' => $productVariation?->id,
                        'available' => $availableStock,
                        'requested' => $itemData['quantity']
                    ];
                    continue;
                }
            }

            // Price determination logic
            $price = ($productVariation && $productVariation->price > 0)
                ? $product->price + $productVariation->price
                : $product->price;

            // Check for active campaign
            $activeCampaign = null;
            if ($product->campaigns) {
                $now = now();
                foreach ($product->campaigns as $campaign) {
                    $start = \Carbon\Carbon::parse($campaign->start_date);
                    $end = \Carbon\Carbon::parse($campaign->end_date);
                    if ($campaign->status === 'active' && $now->between($start, $end)) {
                        $activeCampaign = $campaign;
                        break;
                    }
                }
            }

            $campaignDiscountType = null;
            $campaignDiscountAmount = 0;
            if ($activeCampaign) {
                $campaignDiscountType = $activeCampaign->discount_type;
                $campaignDiscountAmount = $activeCampaign->discount_amount;
            }

            // Log::info('Product price', [
            //     'product_id' => $product->id,
            //     'variation_id' => $productVariation->id ?? null,
            //     'price' => $price
            // ]);

            // Preserve base price (pre-campaign) then apply campaign to get displayed unit price
            $basePrice = $price;
            if ($activeCampaign) {
                $price = $activeCampaign->calculateDiscountedPrice($price);
            }

            // Prepare processed item with resolved variation
            // Note: We only pass coupon/explicit item discounts via discount_type/discount_amount
            // Campaign discount is already applied to the unit_price above to avoid double-application later
            $processedItems[] = [
                'product' => $product,
                'variation' => $productVariation,
                'quantity' => $itemData['quantity'],
                'base_unit_price' => $basePrice,
                'unit_price' => $price,
                'discount_type' => !empty($itemData['discount_type']) ? $itemData['discount_type'] : null,
                'discount_amount' => (!empty($itemData['discount_amount']) && $itemData['discount_amount'] > 0) ? $itemData['discount_amount'] : 0,
                'additional_data' => $itemData
            ];
        }

        // Throw InsufficientStockException if any stock errors exist
        if (!empty($stockErrors)) {
            Log::info('Stock validation errors', [
                'errors' => $stockErrors
            ]);
            throw new InsufficientStockException(
                'One or more products have insufficient stock',
                422
            );
        }

        return $processedItems;
    }

    /**
     * Process order items with robust variation handling
     *
     * @param Order $order
     * @param array $processedItems
     */
    protected function processOrderItems(Order $order, array $processedItems): void
    {
        foreach ($processedItems as $itemData) {
            $product = $itemData['product'];
            $productVariation = $itemData['variation'] ?? null;
            $quantity = $itemData['quantity'];
            $additionalData = $itemData['additional_data'];

            $orderItem = $this->createOrderItem(
                $order,
                $product,
                $productVariation,
                $itemData
            );

            // Skip stock updates for incomplete orders
            if ($order->status !== 'incomplete') {
                $this->updateProductStock($product, $productVariation, $quantity);
            }
        }
    }

    /**
     * Create individual order item with robust variation handling
     *
     * @param Order $order
     * @param Product $product
     * @param ProductVariation|null $variation
     * @param array $itemData
     * @return OrderItem
     */
    protected function createOrderItem(
        Order $order,
        Product $product,
        ?ProductVariation $variation,
        array $itemData
    ): OrderItem {
        // Calculate item price and discount
        $unitPrice = $itemData['unit_price'];
        $quantity = $itemData['quantity'];
        $subtotal = $unitPrice * $quantity;
        // $subtotal = $unitPrice;
        $discountAmount = 0;


        // Apply discount if available
        if (!empty($itemData['discount_type']) && !empty($itemData['discount_amount'])) {
            // If a coupon was applied (coupon_code present), we compute percentage discounts
            // against the base (pre-campaign) unit price so totals match checkout behavior.
            $baseUnitPrice = $itemData['base_unit_price'] ?? $unitPrice;

            switch ($itemData['discount_type']) {
                case 'percentage':
                    // Percentage discounts apply on the displayed unit price (post-campaign)
                    $discountAmount = (($unitPrice * $itemData['discount_amount']) / 100) * $quantity;
                    break;

                case 'fixed':
                    // fixed amount off per unit (per-unit value provided for fixed coupons), then multiplied by quantity
                    $discountAmount = $itemData['discount_amount'] * $quantity;
                    break;

                default:
                    $discountAmount = 0;
                    break;
            }

            // Ensure discount doesn't exceed subtotal
            $discountAmount = min($discountAmount, $subtotal);
        }

        $finalPrice = $subtotal - $discountAmount;

        // Get campaign info if applicable
        $campaignPrice = $itemData['campaign_price'] ?? null;
        $campaignInfo = $itemData['campaign'] ?? null;

        // Get current stock for snapshot
        $currentStock = $this->inventoryService->getTotalStock($product->id, $variation?->id);

        return OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_variation_id' => $variation?->id,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'subtotal' => $subtotal,
            'discount_type' => $itemData['discount_type'] ?? null,
            'discount_amount' => $itemData['discount_amount'] ?? 0,
            'discount_total' => $discountAmount,
            'final_price' => $finalPrice,
            'coupon_code' => $itemData['additional_data']['coupon_code'] ?? null,
            'options' => $itemData['additional_data']['options'] ?? null,
            'is_pre_order' => $product->is_pre_order,

            // Product snapshot - using actual DB column names
            'product_name' => $product->name,
            'product_code' => $product->product_code ?? null,
            'product_sku' => $product->sku ?? $product->product_code ?? null,
            'product_image_url' => $product->feature_image,
            'product_short_description' => $product->short_description ?? null,
            'product_status' => $product->status ?? 'active',
            'product_type' => $product->product_type ?? 'simple',
            'was_pre_order' => $product->is_pre_order ?? false,

            // Variation snapshot
            'variation_name' => $variation?->name ?? null,
            'variation_sku' => $variation?->sku ?? $variation?->product_code ?? null,
            'variation_image_url' => $variation?->image_path ?? null,
            'variation_attributes' => $variation ? $this->getVariationAttributesSnapshot($variation) : null,

            // Price snapshot
            'base_product_price' => $product->price ?? $unitPrice,
            'variation_price_addition' => $variation?->price_adjustment ?? 0,
            'original_price' => $itemData['base_unit_price'] ?? $unitPrice,
            'cost_price' => $variation?->cost_price ?? $product->cost_price ?? 0,

            // Tax info - default to 0 for NOT NULL columns
            'tax_rate' => $itemData['tax_rate'] ?? 0,
            'tax_amount' => $itemData['tax_amount'] ?? 0,
            'handling_fee' => $itemData['handling_fee'] ?? 0,

            // Campaign snapshot - default to empty string for NOT NULL columns
            'campaign_name' => $campaignInfo['name'] ?? '',
            'campaign_code' => $campaignInfo['code'] ?? '',

            // Stock & inventory snapshot
            'stock_at_order_time' => $currentStock,
            'inventory_location' => $itemData['inventory_location'] ?? 'default',

            // Full data snapshots (JSON)
            'product_data_snapshot' => [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'product_code' => $product->product_code,
                'price' => $product->price,
                'cost_price' => $product->cost_price,
                'category_id' => $product->category_id,
                'brand_id' => $product->brand_id,
                'status' => $product->status,
            ],
            'variation_data_snapshot' => $variation ? [
                'id' => $variation->id,
                'name' => $variation->name,
                'sku' => $variation->sku,
                'price' => $variation->price,
                'cost_price' => $variation->cost_price,
                'price_adjustment' => $variation->price_adjustment,
                'attributes' => $this->getVariationAttributesSnapshot($variation),
            ] : null,
            'snapshot_created_at' => now(),
            'snapshot_version' => 1,
        ]);
    }

    /**
     * Get variation attributes as snapshot array
     */
    protected function getVariationAttributesSnapshot(?ProductVariation $variation): ?array
    {
        if (!$variation) {
            return null;
        }

        try {
            $variation->load('attributeValues.attribute');

            return $variation->attributeValues
                ->filter(fn($value) => $value && $value->attribute)
                ->mapWithKeys(fn($value) => [
                    $value->attribute->name => $value->value
                ])
                ->toArray();
        } catch (\Exception $e) {
            Log::warning('Failed to get variation attributes snapshot', [
                'variation_id' => $variation->id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Update stock for product and its variation using V2 Inventory System
     * Records transaction in inventory_transactions for audit trail
     *
     * @param Product $product
     * @param ProductVariation|null $variation
     * @param int $quantity
     * @param int|null $orderId Reference for transaction logging
     */
    public function updateProductStock(
        Product $product,
        ?ProductVariation $variation,
        int $quantity,
        ?int $orderId = null
    ): void {
        // Skip stock update for pre-order products
        if ($product->is_pre_order) {
            return;
        }

        try {
            // V2 Inventory: Use InventoryService for stock deduction with transaction logging
            $dto = new InventoryAdjustmentDTO(
                productId: $product->id,
                variationId: $variation?->id,
                quantity: $quantity,
                adjustmentType: 'decrease',
                reason: 'Order sale',
                referenceType: 'order',
                referenceId: $orderId,
                userId: auth()->id(),
                location: 'MAIN',
                notes: "Stock deducted for order #{$orderId}"
            );

            $this->inventoryService->adjustStock($dto);

            Log::info('V2 Inventory: Stock deducted for order', [
                'product_id' => $product->id,
                'variation_id' => $variation?->id,
                'quantity' => $quantity,
                'order_id' => $orderId,
            ]);
        } catch (\Exception $e) {
            // Fallback to legacy stock update if V2 fails
            Log::warning('V2 Inventory failed, using legacy stock update', [
                'product_id' => $product->id,
                'error' => $e->getMessage()
            ]);

            if ($variation) {
                $variation->increment('sold_stock', $quantity);
                $variation->decrement('stock', $quantity);
                $product->increment('sold_stock', $quantity);
                $product->decrement('stock', $quantity);
            } else {
                $product->increment('sold_stock', $quantity);
                $product->decrement('stock', $quantity);
            }
        }

        // Smart cache invalidation instead of flush
        Order::invalidateCache();
    }

    /**
     * Restore stock when order item is removed or order cancelled
     * Uses V2 Inventory System with transaction logging
     */
    public function restoreProductStock(
        Product $product,
        ?ProductVariation $variation,
        int $quantity,
        ?int $orderId = null,
        string $reason = 'Order item removed'
    ): void {
        if ($product->is_pre_order) {
            return;
        }

        try {
            $dto = new InventoryAdjustmentDTO(
                productId: $product->id,
                variationId: $variation?->id,
                quantity: $quantity,
                adjustmentType: 'increase',
                reason: $reason,
                referenceType: 'order',
                referenceId: $orderId,
                userId: auth()->id(),
                location: 'MAIN',
                notes: "Stock restored for order #{$orderId}"
            );

            $this->inventoryService->adjustStock($dto);

            Log::info('V2 Inventory: Stock restored for order', [
                'product_id' => $product->id,
                'variation_id' => $variation?->id,
                'quantity' => $quantity,
                'order_id' => $orderId,
                'reason' => $reason,
            ]);
        } catch (\Exception $e) {
            // Fallback to legacy stock restore
            Log::warning('V2 Inventory restore failed, using legacy', [
                'product_id' => $product->id,
                'error' => $e->getMessage()
            ]);

            if ($variation) {
                $variation->decrement('sold_stock', $quantity);
                $variation->increment('stock', $quantity);
                $product->decrement('sold_stock', $quantity);
                $product->increment('stock', $quantity);
            } else {
                $product->decrement('sold_stock', $quantity);
                $product->increment('stock', $quantity);
            }
        }

        Order::invalidateCache();
    }

    /**
     * Calculate order totals including discounts
     *
     * @param Order $order
     * @return void
     */
    public function calculateOrderTotals(Order $order): void
    {
        // Fetch all order items
        $orderItems = $order->items;

        $subtotal = 0;
        $discountTotal = 0;

        // Calculate totals from all items
        foreach ($orderItems as $item) {
            $subtotal += $item->subtotal;
            $discountTotal += $item->discount_total;
        }

        // Apply shipping cost
        $shippingCost = $order->shipping_cost ?? 0;
        // Determine POS discount amount (supports percentage or fixed)
        $posDiscountInput = $order->pos_discount ?? 0;
        $discountType = $order->discount_type ?? null;

        // Base amount for percentage calculation: subtotal minus item discounts plus shipping
        $baseForPos = $subtotal - $discountTotal + $shippingCost;

        if ($discountType === 'percentage') {
            // Treat pos_discount as percentage (0-100)
            $posDiscountAmount = ($baseForPos * min(max(floatval($posDiscountInput), 0), 100)) / 100;
        } else {
            // Fixed amount: cannot exceed the base
            $posDiscountAmount = min(max(floatval($posDiscountInput), 0), max($baseForPos, 0));
        }

        $posDiscountAmount = max(0, $posDiscountAmount);

        // Combine item-level discounts and POS discount into total discount
        $totalDiscount = $discountTotal + $posDiscountAmount;

        // Calculate grand total and ensure it does not go below zero
        $total = $subtotal - $totalDiscount + $shippingCost;
        if ($total < 0) {
            $total = 0;
        }

        // Update order with calculated totals (also reflect POS discount in overall discount_total)
        $order->update([
            'subtotal' => $subtotal,
            'discount_total' => $totalDiscount,
            'total' => $total
        ]);
    }


    protected function findCustomerDistrict($customerAddress) {

        $geoData = collect(json_decode(file_get_contents(public_path('jsondata/bd_locations.js')), true));

        $address = (string)$customerAddress;
        $addressLower = mb_strtolower($address);

        $matched = $geoData->first(function ($location) use ($address, $addressLower) {
            $en = mb_strtolower($location['district'] ?? '');
            $bn = $location['bn'] ?? '';

            // Check English name (case-insensitive)
            if ($en !== '' && mb_stripos($addressLower, $en) !== false) {
                return true;
            }

            // Check Bangla name (no case concept, use raw search)
            if ($bn !== '' && mb_strpos($address, $bn) !== false) {
                return true;
            }

            return false;
        });

        // Always return English name so DB stores English district
        $district = $matched['district'] ?? null;

        //Log::info('Matched location', ['matched' => $matched]);

        return $district;

    }




    /**
     * Generate a unique order number
     *
     * @return string
     */
    protected function generateUniqueOrderNumber(): string
    {
        return 'ORD-' . now()->format('His') . '-' . Str::random(4);
    }

    // clear cart
    public function clearCart($sessionId, $userId)
    {
        // Clear cart items based on session ID or user ID
        Cart::where('session_id', $sessionId)->orWhere('user_id', $userId)->delete();
    }



    /**
     * Get paginated orders list
     *
     * @param array $params
     * @return LengthAwarePaginator
     */
    /**
     * Get paginated orders with advanced filtering
     * Big Tech Pattern: Delegate filtering logic to OrderFilterService
     *
     * @param array $params
     * @return LengthAwarePaginator
     */
    public function getOrders(array $params = []): LengthAwarePaginator
    {
        $perPage = $params['per_page'] ?? 10;
        $sortBy = $params['sort_by'] ?? 'created_at';
        $sortDirection = $params['sort_direction'] ?? 'desc';

        // Extract filters - now includes new filter options
        $filters = [
            'status' => $params['filters']['status'] ?? null,
            'payment_status' => $params['filters']['payment_status'] ?? null,
            'date_from' => $params['filters']['date_from'] ?? null,
            'date_to' => $params['filters']['date_to'] ?? null,
            'customer_search' => $params['filters']['customer_search'] ?? null,
            'order_number' => $params['filters']['order_number'] ?? null,
            'min_total' => $params['filters']['min_total'] ?? null,
            'max_total' => $params['filters']['max_total'] ?? null,
            'date_preset' => $params['filters']['date_preset'] ?? null, // NEW
            'shipping_area' => $params['filters']['shipping_area'] ?? null, // NEW
            'has_courier' => $params['filters']['has_courier'] ?? null, // NEW
        ];

        return $this->orderRepository->getPaginatedOrders(
            $perPage,
            $filters,
            $sortBy,
            $sortDirection
        );
    }


    public function getStatusCounts()
    {
        $statusCounts = Order::query()
            ->select('status', DB::raw('count(*) as count'), DB::raw('SUM(total) as sales'))
            ->groupBy('status')
            ->get()
            ->toArray();

            // ❌ incomplete বাদ দিয়ে total count
        $totalOrderCount = Order::query()
            ->where('status', '!=', 'incomplete')
            ->count();
        $totalSales = Order::query()->sum('total');
        $statusCounts[] = ['status' => 'total', 'count' => $totalOrderCount, 'sales' => $totalSales];

        // Add today's statistics
        $todayOrderCount = Order::query()->whereDate('created_at', today())->count();
        $todaySales = Order::query()->whereDate('created_at', today())->sum('total');
        $statusCounts[] = ['status' => 'today', 'count' => $todayOrderCount, 'sales' => $todaySales];

        return $statusCounts;
    }


    /**
     * Update an existing order with proper exception handling
     *
     * @param int $orderId
     * @param array $updateData
     * @return Order
     * @throws OrderNotFoundException|InsufficientStockException|InvalidOrderDataException|\Exception
     */
    public function updateOrder(int $orderId, array $updateData): Order
    {
        try {
            // Find the order
            $order = $this->orderRepository->findOrderById($orderId);

            // Check if order can be updated (only pending, processing, or incomplete orders)
            if (!in_array($order->status, ['pending', 'processing', 'incomplete'])) {
                throw new InvalidOrderDataException(
                    'Cannot update order in ' . $order->status . ' status',
                    ['status' => 'Order cannot be updated in its current status']
                );
            }

            // Validate update data
            $validatedData = $this->validateOrderUpdatePayload($updateData);

            // Log update attempt
            Log::info('Order update started', [
                'order_id' => $orderId,
                'order_number' => $order->order_number,
                'update_data' => $validatedData
            ]);

            return DB::transaction(function () use ($order, $validatedData) {
                // Update basic order information if provided
                $this->updateOrderBasicInfo($order, $validatedData);

                // Process item updates if provided
                if (isset($validatedData['items'])) {
                    $this->updateOrderItems($order, $validatedData['items']);
                }

                // Recalculate order totals
                $this->calculateOrderTotals($order);

                // If updating from incomplete to pending, handle stock and cart
                if ($order->status === 'incomplete' && isset($validatedData['status']) && $validatedData['status'] === 'pending') {
                    $this->handleIncompleteToPendingTransition($order);
                }

                // Log successful update
                Log::info('Order updated successfully', [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number
                ]);

                event(new OrderUpdated($order)); // Dispatch event for cache invalidation

                return $order;
            });
        } catch (OrderNotFoundException $e) {
            Log::error('Order update failed: Order not found', [
                'order_id' => $orderId,
                'message' => $e->getMessage()
            ]);
            throw $e;
        } catch (InsufficientStockException $e) {
            Log::error('Order update failed: Insufficient stock', [
                'order_id' => $orderId,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        } catch (InvalidOrderDataException $e) {
            Log::error('Order update failed: Invalid data', [
                'order_id' => $orderId,
                'message' => $e->getMessage(),
                'errors' => $e->getValidationErrors(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        } catch (\Exception $e) {
            Log::error('Order update failed: Unexpected error', [
                'order_id' => $orderId,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Validate order update payload
     *
     * @param array $updateData
     * @return array
     * @throws InvalidOrderDataException
     */
    protected function validateOrderUpdatePayload(array $updateData): array
    {
        $validator = validator($updateData, [
            'customer_email' => ['nullable', 'email', 'max:255'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'shipping_address' => ['nullable', 'string', 'max:500'],
            'customer_notes' => ['nullable', 'string', 'max:1000'],
            'shipping_cost' => ['nullable', 'numeric', 'min:0', 'max:10000'],
            'payment_method' => ['nullable', 'in:credit_card,paypal,bank_transfer,cod'],
            // POS discount fields (allow updates)
            'pos_discount' => ['nullable', 'numeric', 'min:0'],
            'discount_type' => ['nullable', 'in:percentage,fixed'],
            'status' => ['nullable', 'in:pending,processing,completed,cancelled,incomplete,on_hold,confirmed'],
            'payment_status' => ['nullable', 'in:unpaid,paid,refunded'],
            'items' => ['nullable', 'array'],
            'items.*.id' => ['required_with:items', 'integer'],
            'items.*.quantity' => ['required_with:items.*.id', 'integer', 'min:1', 'max:100'],
            'items.*.action' => ['required_with:items', 'in:update,remove'],
        ]);

        if ($validator->fails()) {
            throw new InvalidOrderDataException(
                'Invalid order update data provided',
                $validator->errors()->toArray()
            );
        }

        return $validator->validated();
    }

    /**
     * Update basic order information
     *
     * @param Order $order
     * @param array $validatedData
     * @return void
     */
    protected function updateOrderBasicInfo(Order $order, array $validatedData): void
    {
        $updateFields = [
            'customer_email',
            'customer_name',
            'shipping_address',
            'customer_notes',
            'shipping_cost',
            'payment_method',
            'status',
            'payment_status',
            'area',
            'pos_discount',
            'discount_type'
        ];

        $updates = [];
        foreach ($updateFields as $field) {
            if (isset($validatedData[$field])) {
                $updates[$field] = $validatedData[$field];
            }
        }

        if (!empty($updates)) {
            $order->update($updates);
        }
    }

    /**
     * Update order items (quantity changes or removals)
     *
     * @param Order $order
     * @param array $itemUpdates
     * @return void
     * @throws InsufficientStockException
     */
    protected function updateOrderItems(Order $order, array $itemUpdates): void
    {
        $stockErrors = [];

        foreach ($itemUpdates as $itemUpdate) {
            // Find the order item
            $orderItem = OrderItem::where('id', $itemUpdate['id'])
                ->where('order_id', $order->id)
                ->first();

            if (!$orderItem) {
                Log::warning('Order item not found during update', [
                    'order_id' => $order->id,
                    'item_id' => $itemUpdate['id']
                ]);
                continue;
            }

            // Handle item removal
            if ($itemUpdate['action'] === 'remove') {
                $this->removeOrderItem($orderItem);
                continue;
            }

            // Handle quantity update
            if ($itemUpdate['action'] === 'update') {
                $oldQuantity = $orderItem->quantity;
                $newQuantity = $itemUpdate['quantity'];
                $quantityDiff = $newQuantity - $oldQuantity;

                // Skip if no change in quantity
                if ($quantityDiff === 0) {
                    continue;
                }

                // If increasing quantity, check stock availability
                if ($quantityDiff > 0) {
                    // Get product and variation
                    $product = Product::find($orderItem->product_id);
                    $variation = $orderItem->product_variation_id ?
                        ProductVariation::find($orderItem->product_variation_id) : null;

                    // Determine available stock
                    $availableStock = $variation ? $variation->stock : $product->stock;

                    // Check if enough stock available
                    if ($availableStock < $quantityDiff) {
                        $stockErrors[] = [
                            'product' => $product->name,
                            'available' => $availableStock,
                            'requested' => $quantityDiff,
                            'item_id' => $orderItem->id
                        ];
                        continue;
                    }

                    // Update product stock
                    $this->updateProductStock($product, $variation, $quantityDiff);
                } else {
                    // If decreasing quantity, return stock
                    $product = Product::find($orderItem->product_id);
                    $variation = $orderItem->product_variation_id ?
                        ProductVariation::find($orderItem->product_variation_id) : null;

                    // Reverse stock update (negative quantityDiff means returning stock)
                    $this->returnProductStock($product, $variation, abs($quantityDiff));
                }

                // Update order item quantity and recalculate pricing
                $this->updateOrderItemQuantity($orderItem, $newQuantity);
            }
        }

        // Throw exception if any stock errors occurred
        if (!empty($stockErrors)) {
            throw new InsufficientStockException(
                'One or more products have insufficient stock for the requested update',
                422,
                $stockErrors
            );
        }
    }


    /**
     * Update courier details for an order
     *
     * @param int $orderId
     * @param array $data
     * @return Order
     */

    public function updateCourierDetails(int $orderId, array $data): Order
    {
        try {
            DB::beginTransaction();

            $order = Order::findOrFail($orderId);

            $order->update([
                'courier_name' => $data['courier_name'] ?? null,
                'city_name' => $data['city_name'] ?? null,
                'zone_name' => $data['zone_name'] ?? null,
                'area_name' => $data['area_name'] ?? null,
                'city_id' => $data['city_id'] ?? null,
                'zone_id' => $data['zone_id'] ?? null,
                'area_id' => $data['area_id'] ?? null,
            ]);

            DB::commit();
            return $order;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }



    /**
     * Remove an order item and return its stock
     *
     * @param OrderItem $orderItem
     * @return void
     */
    protected function removeOrderItem(OrderItem $orderItem): void
    {
        // Return stock to inventory
        $product = Product::find($orderItem->product_id);
        $variation = $orderItem->product_variation_id ?
            ProductVariation::find($orderItem->product_variation_id) : null;

        $this->returnProductStock($product, $variation, $orderItem->quantity);

        // Delete the order item
        $orderItem->delete();
    }

    // /**
    //  * Return stock to inventory
    //  *
    //  * @param Product $product
    //  * @param ProductVariation|null $variation
    //  * @param int $quantity
    //  * @return void
    //  */
    // protected function returnProductStock(
    //     Product $product,
    //     ?ProductVariation $variation,
    //     int $quantity
    // ): void {
    //     if ($variation) {
    //         $variation->decrement('sold_stock', $quantity);
    //         $variation->increment('stock', $quantity);

    //         $product->decrement('sold_stock', $quantity);
    //         $product->increment('stock', $quantity);
    //     } else {
    //         $product->decrement('sold_stock', $quantity);
    //         $product->increment('stock', $quantity);
    //     }
    // }

    /**
     * Update order item quantity and recalculate pricing
     *
     * @param OrderItem $orderItem
     * @param int $newQuantity
     * @return void
     */
    protected function updateOrderItemQuantity(OrderItem $orderItem, int $newQuantity): void
    {
        // Recalculate subtotal
        $subtotal = $orderItem->unit_price * $newQuantity;

        // Recalculate discount
        $discountAmount = 0;
        if ($orderItem->discount_type === 'percentage' && $orderItem->discount_amount > 0) {
            $discountAmount = ($subtotal * $orderItem->discount_amount) / 100;
        } elseif ($orderItem->discount_type === 'fixed' && $orderItem->discount_amount > 0) {
            $discountAmount = $orderItem->discount_amount * $newQuantity;
        }

        // Ensure discount doesn't exceed subtotal
        $discountAmount = min($discountAmount, $subtotal);

        // Calculate final price
        $finalPrice = $subtotal - $discountAmount;

        // Update order item
        $orderItem->update([
            'quantity' => $newQuantity,
            'subtotal' => $subtotal,
            'discount_total' => $discountAmount,
            'final_price' => $finalPrice
        ]);
    }


    // updateOrderStatus

    /**
     * Adjust product inventory based on order status
     *
     * @param Product $product
     * @param ProductVariation|null $variation
     * @param int $quantity
     * @param string $oldStatus
     * @param string $newStatus
     * @return void
     */
    protected function adjustProductStock(
        Product $product,
        ?ProductVariation $variation,
        int $quantity,
        string $oldStatus,
        string $newStatus
    ): void {
        // Only make adjustments when specific status transitions occur
        if ($this->shouldReturnStock($oldStatus, $newStatus)) {
            $this->returnProductStock($product, $variation, $quantity);
        } elseif ($this->shouldReduceStock($oldStatus, $newStatus)) {
            $this->reduceProductStock($product, $variation, $quantity);
        }
    }

    /**
     * Determine if stock should be returned to inventory
     * Big Tech Style: Unified handling for cancelled & returned status
     *
     * @param string $oldStatus
     * @param string $newStatus
     * @return bool
     */
    private function shouldReturnStock(string $oldStatus, string $newStatus): bool
    {
        // Statuses that release stock back to inventory
        $returningStatuses = ['cancelled', 'returned'];

        // Statuses where stock is already deducted (active orders)
        $activeStatuses = ['pending', 'processing', 'shipped', 'delivered', 'confirmed', 'on_hold'];

        // Return stock when transitioning FROM active status TO cancelled/returned
        // Examples:
        // - pending → cancelled (customer cancelled before shipping)
        // - processing → cancelled (cancelled during processing)
        // - delivered → returned (customer returned product)
        // - shipped → cancelled (cancelled in transit)
        if (in_array($newStatus, $returningStatuses) && in_array($oldStatus, $activeStatuses)) {
            return true;
        }

        // Don't return stock if:
        // - Already cancelled/returned (cancelled → returned, returned → cancelled)
        // - Incomplete orders (no stock was deducted yet)
        return false;
    }

    /**
     * Determine if stock should be reduced
     * Big Tech Style: Handle order reactivation scenarios
     *
     * @param string $oldStatus
     * @param string $newStatus
     * @return bool
     */
    private function shouldReduceStock(string $oldStatus, string $newStatus): bool
    {
        // Statuses where stock is actively allocated
        $activeStatuses = ['pending', 'processing', 'shipped', 'delivered', 'confirmed', 'on_hold'];

        // Statuses where stock has been returned to inventory
        $returningStatuses = ['cancelled', 'returned'];

        // Reduce stock when transitioning FROM cancelled/returned TO active status
        // This handles order reactivation scenarios:
        // - cancelled → pending (admin reactivates cancelled order)
        // - returned → pending (restocking then reselling)
        // - cancelled → processing (order was accidentally cancelled)
        if (in_array($oldStatus, $returningStatuses) && in_array($newStatus, $activeStatuses)) {
            return true;
        }

        // Don't reduce stock if:
        // - Moving between active statuses (pending → processing, etc.)
        // - Already in returning status
        return false;
    }

    /**
     * Return product stock to inventory
     * Big Tech Style: Centralized inventory management with transaction logging
     *
     * @param Product $product
     * @param ProductVariation|null $variation
     * @param int $quantity
     * @return void
     */
    protected function returnProductStock(
        Product $product,
        ?ProductVariation $variation,
        int $quantity
    ): void {
        if ($variation) {
            // Return stock to variation - ensure sold_stock doesn't go negative
            $newVariationSoldStock = max(0, $variation->sold_stock - $quantity);
            $variation->update([
                'sold_stock' => $newVariationSoldStock,
                'stock' => $variation->stock + $quantity
            ]);

            // Return stock to main product - ensure sold_stock doesn't go negative
            $newProductSoldStock = max(0, $product->sold_stock - $quantity);
            $product->update([
                'sold_stock' => $newProductSoldStock,
                'stock' => $product->stock + $quantity
            ]);

            Log::info('Stock returned for variable product', [
                'product_id' => $product->id,
                'variation_id' => $variation->id,
                'quantity' => $quantity,
                'product_new_stock' => $product->stock + $quantity,
                'product_new_sold_stock' => $newProductSoldStock,
                'variation_new_stock' => $variation->stock + $quantity,
                'variation_new_sold_stock' => $newVariationSoldStock
            ]);

            // Log to centralized inventory system (future-ready for full migration)
            try {
                $this->inventoryService->adjustStock(new InventoryAdjustmentDTO(
                    productId: $product->id,
                    location: 'MAIN',
                    adjustmentType: 'increase',
                    quantity: $quantity,
                    reason: 'Order cancelled/returned - stock returned to inventory',
                    variationId: $variation->id,
                    userId: auth()->id() ?? null
                ));
            } catch (\Exception $e) {
                Log::warning('Failed to log inventory transaction for variation', [
                    'error' => $e->getMessage(),
                    'product_id' => $product->id,
                    'variation_id' => $variation->id
                ]);
            }
        } else {
            // Return stock to simple product - ensure sold_stock doesn't go negative
            $newSoldStock = max(0, $product->sold_stock - $quantity);
            $product->update([
                'sold_stock' => $newSoldStock,
                'stock' => $product->stock + $quantity
            ]);

            Log::info('Stock returned for simple product', [
                'product_id' => $product->id,
                'quantity' => $quantity,
                'product_new_stock' => $product->stock + $quantity,
                'product_new_sold_stock' => $newSoldStock
            ]);

            // Log to centralized inventory system (future-ready for full migration)
            try {
                $this->inventoryService->adjustStock(new InventoryAdjustmentDTO(
                    productId: $product->id,
                    location: 'MAIN',
                    adjustmentType: 'increase',
                    quantity: $quantity,
                    reason: 'Order cancelled/returned - stock returned to inventory',
                    variationId: null,
                    userId: auth()->id() ?? null
                ));
            } catch (\Exception $e) {
                Log::warning('Failed to log inventory transaction for product', [
                    'error' => $e->getMessage(),
                    'product_id' => $product->id
                ]);
            }
        }

        // Clear product cache after returning stock
        Cache::flush();
    }

    /**
     * Reduce product stock from inventory
     * Big Tech Style: Centralized inventory management with transaction logging
     *
     * @param Product $product
     * @param ProductVariation|null $variation
     * @param int $quantity
     * @return void
     */
    protected function reduceProductStock(
        Product $product,
        ?ProductVariation $variation,
        int $quantity
    ): void {
        if ($variation) {
            // Reduce stock from variation - ensure stock doesn't go negative
            $newVariationStock = max(0, $variation->stock - $quantity);
            $variation->update([
                'sold_stock' => $variation->sold_stock + $quantity,
                'stock' => $newVariationStock
            ]);

            // Reduce stock from main product - ensure stock doesn't go negative
            $newProductStock = max(0, $product->stock - $quantity);
            $product->update([
                'sold_stock' => $product->sold_stock + $quantity,
                'stock' => $newProductStock
            ]);

            Log::info('Stock reduced for variable product (order reactivated)', [
                'product_id' => $product->id,
                'variation_id' => $variation->id,
                'quantity' => $quantity,
                'product_new_stock' => $newProductStock,
                'product_new_sold_stock' => $product->sold_stock + $quantity,
                'variation_new_stock' => $newVariationStock,
                'variation_new_sold_stock' => $variation->sold_stock + $quantity
            ]);

            // Log to centralized inventory system (future-ready for full migration)
            try {
                $this->inventoryService->adjustStock(new InventoryAdjustmentDTO(
                    productId: $product->id,
                    location: 'MAIN',
                    adjustmentType: 'decrease',
                    quantity: $quantity,
                    reason: 'Order reactivated (cancelled/returned → active) - stock allocated',
                    variationId: $variation->id,
                    userId: auth()->id() ?? null
                ));
            } catch (\Exception $e) {
                Log::warning('Failed to log inventory transaction for variation', [
                    'error' => $e->getMessage(),
                    'product_id' => $product->id,
                    'variation_id' => $variation->id
                ]);
            }
        } else {
            // Reduce stock from simple product - ensure stock doesn't go negative
            $newStock = max(0, $product->stock - $quantity);
            $product->update([
                'sold_stock' => $product->sold_stock + $quantity,
                'stock' => $newStock
            ]);

            Log::info('Stock reduced for simple product (order reactivated)', [
                'product_id' => $product->id,
                'quantity' => $quantity,
                'product_new_stock' => $newStock,
                'product_new_sold_stock' => $product->sold_stock + $quantity
            ]);

            // Log to centralized inventory system (future-ready for full migration)
            try {
                $this->inventoryService->adjustStock(new InventoryAdjustmentDTO(
                    productId: $product->id,
                    location: 'MAIN',
                    adjustmentType: 'decrease',
                    quantity: $quantity,
                    reason: 'Order reactivated (cancelled/returned → active) - stock allocated',
                    variationId: null,
                    userId: auth()->id() ?? null
                ));
            } catch (\Exception $e) {
                Log::warning('Failed to log inventory transaction for product', [
                    'error' => $e->getMessage(),
                    'product_id' => $product->id
                ]);
            }
        }

        // Clear product cache after reducing stock
        Cache::flush();
    }

    /**
     * Handle transition from incomplete to pending status
     *
     * @param Order $order
     * @return void
     */
    protected function handleIncompleteToPendingTransition(Order $order): void
    {
        // Process stock updates for all order items
        foreach ($order->items as $item) {
            $this->updateProductStock($item->product, $item->productVariation, $item->quantity);
        }

        // Clear cart only if it's not a landing order
        if (empty($order->landing_order) || $order->landing_order == false) {
            $this->cartService->clearCart($order->user_id, $order->session_id);
        }

        // Apply coupon adjustments if any items have coupons
        foreach ($order->items as $item) {
            if (!empty($item->coupon_code)) {
                $this->couponService->adjustCouponLimit($item->coupon_code);
            }
        }

        // Dispatch order created event
        event(new OrderCreated($order));
    }

    /**
     * Update order status and handle inventory changes
     *
     * @param int $orderId
     * @param string $newStatus
     * @return Order
     */
    public function updateOrderStatus(int $orderId, string $newStatus): Order
    {
        // Valid status values
        $validStatuses = ['pending', 'processing', 'cancelled', 'shipped', 'delivered', 'returned', 'incomplete', 'on_hold', 'confirmed'];

        if (!in_array($newStatus, $validStatuses)) {
            throw new \InvalidArgumentException("Invalid status: {$newStatus}");
        }

        $order = Order::with('items.product', 'items.productVariation')->findOrFail($orderId);
        $oldStatus = $order->status;

        // Don't allow status changes from delivered except to returned
        if ($oldStatus === 'delivered' && $newStatus !== 'returned') {
            Log::warning('Status change not allowed', [
                'order_id' => $orderId,
                'old_status' => $oldStatus,
                'new_status' => $newStatus
            ]);
            throw new \InvalidArgumentException("Cannot change status from delivered to {$newStatus}");

            // Removed redundant return statement
        }

        // Don't process if status hasn't changed
        if ($oldStatus === $newStatus) {
            return $order;
        }

        // Process inventory changes for each order item
        foreach ($order->items as $item) {
            $this->adjustProductStock(
                $item->product,
                $item->productVariation,
                $item->quantity,
                $oldStatus,
                $newStatus
            );
        }

        // Update the order status
        $order->update(['status' => $newStatus]);

        // Record the status change in history
        $order->recordStatusChange($newStatus, "Status changed from {$oldStatus} to {$newStatus}");

        return $order;
    }


    /**
     * Get order details by order number
     *
     * @param string $orderNumber
     * @return Order|null
     */
    /**
     * Delete an order and adjust inventory if necessary
     *
     * @param int $id
     * @return void
     * @throws \Exception
     */
    public function deleteOrder($id)
    {
        DB::beginTransaction();
        try {
            $order = Order::findOrFail($id);

            if ($order->status !== 'cancelled' && $order->status !== 'returned') {
                $oldStatus = $order->status;
                foreach ($order->items as $item) {
                    $this->adjustProductStock(
                        $item->product,
                        $item->productVariation,
                        $item->quantity,
                        $order->status,
                        'cancelled'
                    );
                }
                $order->update(['status' => 'cancelled']);

                // Record the status change in history
                $order->recordStatusChange('cancelled', "Order cancelled during deletion from {$oldStatus}");
            } else {
                $order->items()->delete();
            }

            // SOFT DELETE: Order is soft deleted (deleted_at timestamp set)
            // Data is preserved for reports, history, and can be restored if needed
            $order->delete();
            Log::info('Order deleted', ['order_id' => $id, 'status' => $order->status]);
            DB::commit();
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            throw new \Exception("Order with ID {$id} not found");
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception("Error deleting order: " . $e->getMessage());
        }
    }

    // updateNewOrder($orderId, $request->all()); mmmamun

    /**
     * Update order with new items or update existing items
     *
     * @param int $orderId
     * @param array $data
     * @return Order
     * @throws OrderNotFoundException|InsufficientStockException|InvalidOrderDataException|\Exception
     */
    public function updateNewOrder(int $orderId, array $data): Order
    {
        try {
            // Find the order
            $order = $this->orderRepository->findOrderById($orderId);

            // Check if order is actually a model and not a collection
            if ($order instanceof \Illuminate\Database\Eloquent\Collection) {
                // If it's a collection, try to get the first item
                if ($order->isEmpty()) {
                    throw new OrderNotFoundException("Order with ID {$orderId} not found");
                }
                $order = $order->first();
            }

            // Check if order can be updated (only pending, processing, or incomplete orders)
            if (!in_array($order->status, ['pending', 'processing', 'incomplete'])) {
                throw new InvalidOrderDataException(
                    'Cannot update order in ' . $order->status . ' status',
                    ['status' => 'Order cannot be updated in its current status']
                );
            }

            // Validate the input data
            $validatedData = $this->validateNewOrderPayload($data);

            // Log update attempt
            Log::info('New order update started', [
                'order_id' => $orderId,
                'order_number' => $order->order_number,
                'update_data' => $validatedData
            ]);

            return DB::transaction(function () use ($order, $validatedData) {
                // Process items
                $this->processNewOrderItems($order, $validatedData['items']);

                // Recalculate order totals
                $this->calculateOrderTotals($order);

                // Log successful update
                Log::info('Order updated successfully', [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number
                ]);

                event(new OrderUpdated($order)); // Dispatch event for cache invalidation

                return $order;
            });
        } catch (OrderNotFoundException $e) {
            Log::error('Order update failed: Order not found', [
                'order_id' => $orderId,
                'message' => $e->getMessage()
            ]);
            throw $e;
        } catch (InsufficientStockException $e) {
            Log::error('Order update failed: Insufficient stock', [
                'order_id' => $orderId,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        } catch (InvalidOrderDataException $e) {
            Log::error('Order update failed: Invalid data', [
                'order_id' => $orderId,
                'message' => $e->getMessage(),
                'errors' => $e->getValidationErrors(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        } catch (\Exception $e) {
            Log::error('Order update failed: Unexpected error', [
                'order_id' => $orderId,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Validate new order update payload
     *
     * @param array $data
     * @return array
     * @throws InvalidOrderDataException
     */
    protected function validateNewOrderPayload(array $data): array
    {
        $validator = validator($data, [
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.product_variation_id' => ['nullable', 'integer', 'exists:product_variations,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:100'],
            'items.*.coupon_code' => ['nullable', 'string'],
            'items.*.discount_type' => ['nullable', 'string', 'in:percentage,fixed'],
            'items.*.discount_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        if ($validator->fails()) {
            throw new InvalidOrderDataException(
                'Invalid order update data provided',
                $validator->errors()->toArray()
            );
        }

        return $validator->validated();
    }

    /**
     * Process new order items - update existing or create new based on product/variation match
     *
     * @param Order $order
     * @param array $items
     * @return void
     * @throws InsufficientStockException
     */
    protected function processNewOrderItems(Order $order, array $items): void
    {
        // First resolve and validate all items to ensure sufficient stock
        $processedItems = $this->resolveAndValidateOrderItems($items, $order->status);

        foreach ($processedItems as $itemData) {
            $product = $itemData['product'];
            $variation = $itemData['variation'] ?? null;
            $quantity = $itemData['quantity'];
            $additionalData = $itemData['additional_data'];

            // Check if an order item with matching product and variation already exists
            $existingItem = OrderItem::where('order_id', $order->id)
                ->where('product_id', $product->id)
                ->where(function ($query) use ($variation) {
                    if ($variation) {
                        $query->where('product_variation_id', $variation->id);
                    } else {
                        $query->whereNull('product_variation_id');
                    }
                })
                ->first();

            if ($existingItem) {
                // Update existing item quantity
                $oldQuantity = $existingItem->quantity;
                $newQuantity = $oldQuantity + $quantity;

                Log::info('BEFORE stock update', [
                    'product_id' => $product->id,
                    'product_stock' => $product->stock,
                    'product_sold_stock' => $product->sold_stock,
                    'variation_id' => $variation ? $variation->id : null,
                    'variation_stock' => $variation ? $variation->stock : null,
                    'variation_sold_stock' => $variation ? $variation->sold_stock : null,
                    'increment_quantity' => $quantity
                ]);

                // Update stock with the increment amount only (skip for incomplete orders)
                // The existing item already has stock deducted, we only deduct the additional quantity
                if ($order->status !== 'incomplete') {
                    $this->updateProductStock($product, $variation, $quantity);
                }

                Log::info('AFTER stock update', [
                    'product_id' => $product->id,
                    'product_stock' => $product->fresh()->stock,
                    'product_sold_stock' => $product->fresh()->sold_stock,
                    'variation_id' => $variation ? $variation->id : null,
                    'variation_stock' => $variation ? $variation->fresh()->stock : null,
                    'variation_sold_stock' => $variation ? $variation->fresh()->sold_stock : null
                ]);

                // Then update order item
                $this->updateOrderItemQuantity($existingItem, $newQuantity);

                Log::info('Updated existing order item', [
                    'order_id' => $order->id,
                    'item_id' => $existingItem->id,
                    'product_id' => $product->id,
                    'variation_id' => $variation ? $variation->id : null,
                    'old_quantity' => $oldQuantity,
                    'new_quantity' => $newQuantity
                ]);
            } else {
                // Create new order item
                $orderItem = $this->createOrderItem($order, $product, $variation, $itemData);

                // Update product stock (skip for incomplete orders)
                if ($order->status !== 'incomplete') {
                    $this->updateProductStock($product, $variation, $quantity);
                }

                Log::info('Created new order item', [
                    'order_id' => $order->id,
                    'item_id' => $orderItem->id,
                    'product_id' => $product->id,
                    'variation_id' => $variation ? $variation->id : null,
                    'quantity' => $quantity
                ]);
            }

            // Apply coupon if provided (skip for incomplete orders)
            if (!empty($additionalData['coupon_code']) && $order->status !== 'incomplete') {
                $this->couponService->adjustCouponLimit($additionalData['coupon_code']);
            }
        }
    }



    // only admin can see notifications
    public function adminNotifications(): array
    {
        $user = auth()->user();

        // Only admins (or super-admins) should see admin notifications
        if (!$user || (! $user->hasRole('admin') && ! $user->hasRole('super-admin'))) {
            return ['count' => 0, 'notifications' => collect()];
        }

        // Count unread notifications
        $unreadCount = Notification::where('is_read', 0)->count();

        // Return last 10 notifications with JSON-decoded data
        $notifications = Notification::orderBy('created_at', 'desc')
            ->take(6)
            ->get()
            ->map(function ($n) {
                $data = $n->data;
                if (is_string($data)) {
                    $decoded = json_decode($data, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $data = $decoded;
                    }
                }

                return [
                    'id' => $n->id,
                    'type' => $n->type,
                    'order_id' => $n->order_id ?? null,
                    'data' => $data,
                    'is_read' => (bool) $n->is_read,
                    'created_at' => $n->created_at,
                ];
            });

        return ['count' => $unreadCount, 'notifications' => $notifications];
    }

    /**
     * Get statistics for sidebar (Today's revenue, orders, pending counts)
     */
    public function getSidebarStats(): array
    {
        // Cache these stats for 5 minutes since they can be heavy
        return Cache::remember('admin.sidebar.stats', 300, function () {
            $today = now()->startOfDay();

            // Get all order counts for better visibility
            $totalOrders = Order::count();
            $todayOrders = Order::where('created_at', '>=', $today)->count();
            $pendingOrders = Order::where('status', 'pending')->count();
            $processingOrders = Order::where('status', 'processing')->count();
            $incompleteOrders = Order::where('status', 'incomplete')->count();

            return [
                'todayRevenue' => (int) Order::where('created_at', '>=', $today)
                    ->whereNotIn('status', ['cancelled', 'returned'])
                    ->sum('total'),
                'todayOrders' => $todayOrders,
                'totalOrders' => $totalOrders,
                'pendingOrders' => $pendingOrders,
                'processingOrders' => $processingOrders,
                'incompleteOrders' => $incompleteOrders,
                // Combined active orders (pending + processing)
                'activeOrders' => $pendingOrders + $processingOrders,
            ];
        });
    }
}
