<?php

namespace App\Repository\Cart;

use App\Models\Cart;
use App\Models\CartItem;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CartRepository
{

    /**
     * Find active cart by user ID or session ID
     *
     * @param int|null $userId
     * @param string|null $sessionId
     * @return Cart|null
     */
    // public function findActiveCart($userId = null, $sessionId = null)
    // {
    //     $query = Cart::where('status', 'active');

    //     if ($userId) {
    //         $check =  $query->where('user_id', $userId);
    //         Log::info('User ID: ' . $userId);
    //         Log::info('Check: ' . $check);
    //     } elseif ($sessionId) {
    //         $check1 =  $query->where('session_id', $sessionId);
    //         Log::info('Session ID: ' . $sessionId);
    //         Log::info('Check1: ' . $check1);
    //     } else {
    //         return null;
    //     }

    //     $test = $query->with(['items.product', 'items.variation.attributes.attribute', 'items.variation.attributes.value'])
    //         ->first();
    //     Log::info('Test: ' . $test);
    //     return $test;
    // }

    public function findActiveCart($userId = null, $sessionId = null)
    {
        $query = Cart::where('status', 'active');

        if ($userId) {
            $query->where('user_id', $userId);
            // Log::info('User ID: ' . $userId);
            // Log::info('Query SQL: ' . $query->toSql());
            // Log::info('Bindings: ', $query->getBindings());
        } elseif ($sessionId) {
            $query->where('session_id', $sessionId);
            // Log::info('Session ID: ' . $sessionId);
            //  Log::info('Query SQL: ' . $query->toSql());
            //  Log::info('Bindings: ', $query->getBindings());
        } else {
            return null;
        }

        $cart = $query->with([
            'items.product',
            'items.variation.attributes.attribute',
            'items.variation.attributes.value'
        ])->first();

        //  Log::info('Cart: ', $cart ? $cart->toArray() : []);

        return $cart;
    }


    // public function findActiveCart($userId = null, $sessionId = null)
    // {
    //     return Cart::where('status', 'active')
    //         ->when($userId, function ($query) use ($userId) {
    //             return $query->where('user_id', $userId);
    //         })
    //         ->when(!$userId && $sessionId, function ($query) use ($sessionId) {
    //             return $query->where('session_id', $sessionId);
    //         })
    //         ->first();
    // }



    /**
     * Create a new cart
     */
    public function create(array $data)
    {
        return Cart::create($data);
    }

    /**
     * Add item to cart
     */
    public function addItem(Cart $cart, array $itemData)
    {
        // Check if item already exists in cart
        $existingItem = CartItem::where([
            'cart_id' => $cart->id,
            'product_id' => $itemData['product_id'],
            'product_variation_id' => $itemData['product_variation_id'] ?? null,
        ])->first();

        DB::beginTransaction();
        try {
            if ($existingItem) {
                // Update quantity if item exists
                $existingItem->quantity += $itemData['quantity'];
                $existingItem->save();
                $cartItem = $existingItem;
            } else {
                // Create new item
                $cartItem = CartItem::create([
                    'cart_id' => $cart->id,
                    'product_id' => $itemData['product_id'],
                    'product_variation_id' => $itemData['product_variation_id'] ?? null,
                    'quantity' => $itemData['quantity'],
                    'price' => $itemData['price'],
                    'options' => $itemData['options'] ?? null,
                    'is_pre_order' => $itemData['is_pre_order'] ?? 0,
                ]);
            }

            // Update cart total
            $this->updateCartTotal($cart);

            DB::commit();
            return $cartItem;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update cart item quantity
     *
     * @param Cart $cart
     * @param int $itemId
     * @param int $quantityChange Positive for increase, negative for decrease, 0 for removal
     * @return Cart
     */
    public function updateItemQuantity(Cart $cart, $itemId, $quantityChange)
    {
        $cartItem = CartItem::where('cart_id', $cart->id)
            ->where('id', $itemId)
            ->first();

        if (!$cartItem) {
            return $cart;
        }

        if ($quantityChange === 0) {
            // Remove item completely
            $cartItem->delete();
        } else {
            // Update quantity
            if ($quantityChange > 0) {
                // Increasing quantity
                $cartItem->quantity += $quantityChange;
            } else {
                // Decreasing quantity
                $newQuantity = $cartItem->quantity + $quantityChange; // quantityChange is negative
                $cartItem->quantity = max(0, $newQuantity);

                // Delete if quantity becomes 0
                if ($cartItem->quantity <= 0) {
                    $cartItem->delete();
                } else {
                    $cartItem->save();
                }
            }

            if ($cartItem->exists) {
                $cartItem->save();
            }
        }

        // Update cart total
        $this->updateCartTotal($cart);

        return $cart;
    }

    /**
     * Calculate and update cart total
     *
     * @param Cart $cart
     * @return Cart
     */
    public function updateCartTotal(Cart $cart)
    {
        // Refresh the cart items relationship to get the latest data
        $cart->load('items');

        $total = $cart->items->sum(function ($item) {
            // Convert to float to ensure proper calculation
            return (float)$item->price * (int)$item->quantity;
        });

        // Format to 2 decimal places
        $cart->total = number_format($total, 2, '.', '');
        $cart->updated_at = now();
        $cart->save();

        return $cart;
    }
}
