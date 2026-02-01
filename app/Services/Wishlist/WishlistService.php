<?php

namespace App\Services\Wishlist;

use App\Models\Wishlist;
use App\Models\Product;
use Illuminate\Support\Facades\Log;

class WishlistService
{
    /**
     * Add product to wishlist
     */
    public function addToWishlist(int $productId, $userId = null, $sessionId = null)
    {
        // Check if product exists
        $product = Product::find($productId);
        if (!$product) {
            return ['success' => false, 'message' => 'Product not found'];
        }

        // Check if already in wishlist
        $exists = Wishlist::where('product_id', $productId)
            ->where(function ($query) use ($userId, $sessionId) {
                if ($userId) {
                    $query->where('user_id', $userId);
                } else {
                    $query->where('session_id', $sessionId);
                }
            })
            ->exists();

        if ($exists) {
            return ['success' => false, 'message' => 'Product already in wishlist'];
        }

        Wishlist::create([
            'product_id' => $productId,
            'user_id' => $userId,
            'session_id' => $userId ? null : $sessionId,
        ]);

        return ['success' => true, 'message' => 'Product added to wishlist'];
    }

    /**
     * Remove product from wishlist
     */
    public function removeFromWishlist(int $productId, $userId = null, $sessionId = null)
    {
        $deleted = Wishlist::where('product_id', $productId)
            ->where(function ($query) use ($userId, $sessionId) {
                if ($userId) {
                    $query->where('user_id', $userId);
                } else {
                    $query->where('session_id', $sessionId);
                }
            })
            ->delete();

        if ($deleted) {
            return ['success' => true, 'message' => 'Product removed from wishlist'];
        }

        return ['success' => false, 'message' => 'Product not in wishlist'];
    }

    /**
     * Get wishlist items
     */
    public function getWishlist($userId = null, $sessionId = null)
    {
        return Wishlist::with(['product' => function ($query) {
            $query->with(['category.parentRecursive', 'variations.attributes.value.attribute']);
        }])
        ->where(function ($query) use ($userId, $sessionId) {
            if ($userId) {
                $query->where('user_id', $userId);
            } else {
                $query->where('session_id', $sessionId);
            }
        })
        ->get()
        ->pluck('product');
    }

    /**
     * Get wishlist count
     */
    public function getWishlistCount($userId = null, $sessionId = null)
    {
        return Wishlist::where(function ($query) use ($userId, $sessionId) {
            if ($userId) {
                $query->where('user_id', $userId);
            } else {
                $query->where('session_id', $sessionId);
            }
        })->count();
    }

    /**
     * Check if product is in wishlist
     */
    public function isInWishlist(int $productId, $userId = null, $sessionId = null)
    {
        return Wishlist::where('product_id', $productId)
            ->where(function ($query) use ($userId, $sessionId) {
                if ($userId) {
                    $query->where('user_id', $userId);
                } else {
                    $query->where('session_id', $sessionId);
                }
            })
            ->exists();
    }
}
