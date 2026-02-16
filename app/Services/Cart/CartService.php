<?php

namespace App\Services\Cart;



use App\Exceptions\ProductNotAvailableException;
use App\Exceptions\InsufficientStockException;
use App\Models\Cart;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use App\Repository\Cart\CartRepository;
use Illuminate\Support\Facades\Log;
use App\Repository\Product\ProductRepository;
use Illuminate\Support\Str;
use App\Models\Campaign;
use App\Services\Inventory\InventoryService;

class CartService
{
    protected $cartRepository;
    protected $productRepository;
    protected $inventoryService;

    public function __construct(
        CartRepository $cartRepository,
        ProductRepository $productRepository,
        InventoryService $inventoryService
    ) {
        $this->cartRepository = $cartRepository;
        $this->productRepository = $productRepository;
        $this->inventoryService = $inventoryService;
    }

    /**
     * Add product to cart
     */
    public function addToCart(array $data, $userOrSession)
    {
        Log::info('Adding product to cart', [
            'data' => $data,
            'user_or_session' => $userOrSession
        ]);

        // Identify user or session
        $userId = null;
        $sessionId = null;
        if ($userOrSession) {
            if (is_numeric($userOrSession)) {
                $userId = $userOrSession;
                $sessionId = null;
                Log::info(['user_id' => $userId]);
            } else {
                $sessionId = $userOrSession;
                $userId = null;
            }
        }

        // Get active cart to check existing quantities
        $cart = $this->cartRepository->findActiveCart($userId, $sessionId);

        $existingQuantity = 0;
        if ($cart) {
            $existingItem = $cart->items->where('product_id', $data['product_id'])
                ->where('product_variation_id', $data['product_variation_id'] ?? null)
                ->first();
            if ($existingItem) {
                $existingQuantity = $existingItem->quantity;
            }
        }
        $totalRequestedQuantity = $existingQuantity + $data['quantity'];

        // Validate product availability and stock
        $product = $this->productRepository->findById($data['product_id']);

        if (!$product) {
            throw new ProductNotAvailableException('Product not found');
        }

        // For POS/Admin, allow adding unpublished products (check if user is admin/has POS access)
        // For frontend customers, check status
        $isAdminOrPOS = request()->is('api/pos/*') || (auth()->check() && auth()->user()->hasRole(['ADMINISTRATOR', 'MANAGER']));

        if (!$isAdminOrPOS && $product->status !== 'Published') {
            throw new ProductNotAvailableException('Product is not available for sale');
        }

        // Check variation if provided
        $variation = null;
        if (isset($data['product_variation_id'])) {
            $variation = $this->productRepository->findVariationById($data['product_variation_id']);

            if (!$variation) {
                throw new ProductNotAvailableException('Product variation not found');
            }

            // Check if variation is active
            if ($variation->status !== 'active') {
                throw new ProductNotAvailableException('This variation is currently not available');
            }

            if ($variation->product_id != $data['product_id']) {
                throw new ProductNotAvailableException('Selected product variation does not belong to this product');
            }

            if (!$product->is_pre_order && $variation->stock < $totalRequestedQuantity) {
                throw new InsufficientStockException("Insufficient stock for selected variation. You already have {$existingQuantity} in cart.");
            }

            // For variable products, variation price is already the final price (calculated from base + adjustment/percentage/override)
            // For simple products with variations, we'd add product->price + variation->price
            $itemPrice = $product->type === 'variable' ? $variation->price : ($variation->price + $product->price);

            Log::info('Variation pricing', [
                'product_type' => $product->type,
                'variation_price' => $variation->price,
                'product_price' => $product->price,
                'final_item_price' => $itemPrice
            ]);
        } else {
            if (!$product->is_pre_order && $product->stock < $totalRequestedQuantity) {
                throw new InsufficientStockException("Insufficient stock for this product. You already have {$existingQuantity} in cart.");
            }

            $itemPrice = $product->price;
        }

        // Apply campaign discount if available
        $itemPrice = $this->applyCampaignDiscount($data['product_id'], $itemPrice);

        // Get or create cart if not exists
        if (!$cart) {
            $cart = $this->cartRepository->create([
                'user_id' => $userId,
                'session_id' => $sessionId ?? null,
                'status' => 'active'
            ]);
            Log::info('Cart created: ' . $cart->id);
        }

        // Prepare cart item data
        $itemData = [
            'product_id' => $data['product_id'],
            'product_variation_id' => $data['product_variation_id'] ?? null,
            'quantity' => $data['quantity'],
            'price' => $itemPrice,
            'options' => $data['options'] ?? null,
            'is_pre_order' => $product->is_pre_order,
        ];

        // Add item to cart
        $cartItem = $this->cartRepository->addItem($cart, $itemData);

        // Refresh cart with optimized relationships
        $cart->refresh();
        $cart->load([
            'items.product:id,name,slug,feature_image,price,type',
            'items.variation:id,product_id,price,image_path'
        ]);

        return $cart;
    }

    /**
     * Get cart data with V2 inventory stock calculation
     */
    public function getCart($userId = null, $sessionId = null)
    {
        $cart = $this->cartRepository->findActiveCart($userId, $sessionId);

        if (!$cart) {
            return null;
        }

        // Load optimized relationships
        $cart->load([
            'items.product:id,name,slug,feature_image,price,type',
            'items.variation:id,product_id,price,image_path'
        ]);

        // Remove cart items with deleted products
        $cart->items->filter(function ($item) {
            return $item->product === null;
        })->each(function ($item) {
            \Log::info("Removing cart item {$item->id} - Product {$item->product_id} not found (deleted)");
            $item->delete();
        });

        // Reload items after cleanup
        $cart->load('items');

        // V2 Inventory: Calculate available stock for each cart item
        $cart->items->each(function ($item) {
            $availableStock = $this->inventoryService->getTotalStock(
                $item->product_id,
                $item->product_variation_id
            );

            // Get minimum threshold from inventory_stocks table
            $inventoryRecord = \App\Models\InventoryStock::where('product_id', $item->product_id)
                ->where('product_variation_id', $item->product_variation_id)
                ->first();

            $minThreshold = $inventoryRecord?->minimum_threshold ?? 20;

            $item->available_stock = $availableStock;
            $item->minimum_threshold = $minThreshold;
            $item->is_low_stock = $availableStock > 0 && $availableStock <= $minThreshold;
            $item->is_out_of_stock = $availableStock <= 0;
        });

        return $cart;
    }

    /**
     * Update item quantity
     */
    /**
     * Update item quantity in cart
     *
     * @param array $data Contains item_id and quantity
     * @param int|null $userId Authenticated user ID
     * @param string|null $sessionId Guest session ID
     * @return Cart
     * @throws CartNotFoundException, ItemNotFoundException, InsufficientStockException
     */
    public function updateItemQuantity(array $data, $userId = null, $sessionId = null)
    {
        DB::beginTransaction();

        try {
            // Find active cart or throw exception
            $cart = $this->cartRepository->findActiveCart($userId, $sessionId);


            if (!$cart) {
                throw new CartNotFoundException('Cart not found');
            }

            // Find cart item or throw exception
            $cartItem = $cart->items->where('id', $data['item_id'])->first();

            if (!$cartItem) {
                throw new ItemNotFoundException('Item not found in cart');
            }

            // Calculate the new total quantity
            $newQuantity = $cartItem->quantity;

            // Handle quantity change
            if ($data['quantity'] > 0) {
                // Increase quantity
                $this->handleQuantityIncrease($cart, $cartItem, $data['quantity']);
            } else {
                // Decrease quantity
                $this->handleQuantityDecrease($cart, $cartItem, abs($data['quantity']));
            }

            // Refresh cart with optimized relationships
            $cart->refresh();
            $cart->load([
                'items.product:id,name,slug,feature_image,price,type',
                'items.variation:id,product_id,price,image_path'
            ]);

            DB::commit();
            return $cart;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Handle quantity increase
     */
    private function handleQuantityIncrease($cart, $cartItem, $quantityToAdd)
    {
        // Calculate new quantity
        $newQuantity = $cartItem->quantity + $quantityToAdd;

        // Check stock availability
        $currentStock = $this->getAvailableStock($cartItem);

        if ($newQuantity > $currentStock) {
            throw new InsufficientStockException('Requested quantity exceeds available stock');
        }

        // Update quantity
        $this->cartRepository->updateItemQuantity($cart, $cartItem->id, $quantityToAdd);
    }

    /**
     * Handle quantity decrease
     */
    private function handleQuantityDecrease($cart, $cartItem, $quantityToRemove)
    {
        // If removing more than or equal to current quantity, remove item
        if ($quantityToRemove >= $cartItem->quantity) {
            $this->cartRepository->updateItemQuantity($cart, $cartItem->id, 0);
            return;
        }

        // Otherwise, decrease quantity
        $this->cartRepository->updateItemQuantity($cart, $cartItem->id, -$quantityToRemove);
    }

    /**
     * Get available stock for a cart item
     */
    private function getAvailableStock($cartItem)
    {
        if ($cartItem->product_variation_id) {
            $variation = $this->productRepository->findVariationById($cartItem->product_variation_id);
            if (!$variation) {
                throw new ProductNotFoundException('Product variation not found');
            }
            return $variation->product->is_pre_order ? PHP_INT_MAX : $variation->stock;
        } else {
            $product = $this->productRepository->findById($cartItem->product_id);
            if (!$product) {
                throw new ProductNotFoundException('Product not found');
            }
            return $product->is_pre_order ? PHP_INT_MAX : $product->stock;
        }
    }


    /**
     * Remove item from cart
     */
    public function removeItem($itemId)
    {
        DB::beginTransaction();
        try {
            $cartItem = \App\Models\CartItem::findOrFail($itemId);
            $cart = $cartItem->cart;

            // Update cart total
            $cart->total -= $cartItem->price * $cartItem->quantity;
            $cart->save();

            // Delete the item
            $cartItem->delete();

            // If cart is empty, delete it
            if ($cart->items()->count() === 0) {
                $cart->delete();
            }

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Delete entire cart
     */
    public function deleteCart($userId = null, $sessionId = null)
    {
        DB::beginTransaction();
        try {
            // Find the active cart
            $cart = $this->cartRepository->findActiveCart($userId, $sessionId);

            if (!$cart) {
                return ['error' => 'Cart not found'];
            }

            // Delete all cart items first
            $cart->items()->delete();

            // Delete the cart itself
            $cart->delete();

            DB::commit();

            return ['success' => true, 'message' => 'Cart deleted successfully'];
        } catch (\Exception $e) {
            DB::rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function clearCart($userId = null, $sessionId = null)
    {
        DB::beginTransaction();
        try {
            // Find the active cart
            $cart = Cart::where(function($query) use ($userId, $sessionId) {
                    if ($userId) {
                        $query->where('user_id', $userId);
                    }
                    if ($sessionId) {
                        $query->orWhere('session_id', $sessionId);
                    }
                })
                ->where('status', 'active')
                ->first();

            if (!$cart) {
                Log::warning('Cart not found during clearCart', [
                    'user_id' => $userId,
                    'session_id' => $sessionId
                ]);
                DB::commit();
                return ['error' => 'Cart not found'];
            }

            Log::info('Clearing cart', [
                'cart_id' => $cart->id,
                'user_id' => $userId,
                'session_id' => $sessionId
            ]);

            // Delete only incomplete orders associated with this cart
            $incompleteOrdersCount = Order::where('cart_id', $cart->id)
                ->where('status', 'incomplete')
                ->count();

            if ($incompleteOrdersCount > 0) {
                Log::info('Deleting incomplete orders', [
                    'cart_id' => $cart->id,
                    'count' => $incompleteOrdersCount
                ]);

                // Delete incomplete orders with this cart_id
                Order::where('cart_id', $cart->id)
                    ->where('status', 'incomplete')
                    ->delete();

                Log::info('Incomplete orders deleted', [
                    'cart_id' => $cart->id,
                    'count' => $incompleteOrdersCount
                ]);
            }

            // Delete all cart items first
            $cart->items()->delete();

            // Delete the cart itself
            $cart->delete();

            Log::info('Cart cleared successfully', [
                'cart_id' => $cart->id,
                'user_id' => $userId,
                'session_id' => $sessionId,
                'incomplete_orders_deleted' => $incompleteOrdersCount
            ]);

            DB::commit();

            return ['success' => true, 'message' => 'Cart cleared successfully'];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error clearing cart', [
                'user_id' => $userId,
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }



        /**
     * Apply campaign discount to product price
     */
    private function applyCampaignDiscount($productId, $originalPrice)
    {
        $campaign = Campaign::active()
            ->whereHas('products', function ($query) use ($productId) {
                $query->where('products.id', $productId);
            })
            ->first();

        if ($campaign) {
            return $campaign->calculateDiscountedPrice($originalPrice);
        }

        return $originalPrice;
    }


}
