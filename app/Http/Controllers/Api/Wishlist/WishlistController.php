<?php

namespace App\Http\Controllers\Api\Wishlist;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Wishlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WishlistController extends Controller
{
    public function addToWishlist(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'session_id' => 'nullable|string',
            'user_id' => 'nullable|exists:users,id',
        ]);

        $userId = $request->user_id ?: Auth::id();
        $sessionId = $request->session_id ?: session()->getId();

        // Check if already in wishlist
        $existing = Wishlist::where('product_id', $request->product_id)
            ->where(function ($query) use ($userId, $sessionId) {
                if ($userId) {
                    $query->where('user_id', $userId);
                } else {
                    $query->where('session_id', $sessionId);
                }
            })
            ->first();

        if ($existing) {
            return response()->json(['success' => false, 'message' => 'Product already in wishlist'], 400);
        }

        Wishlist::create([
            'product_id' => $request->product_id,
            'user_id' => $userId,
            'session_id' => $userId ? null : $sessionId,
        ]);

        return response()->json(['success' => true, 'message' => 'Product added to wishlist']);
    }

    public function removeFromWishlist(Request $request, $productId)
    {
        $request->validate([
            'session_id' => 'nullable|string',
            'user_id' => 'nullable|exists:users,id',
        ]);

        $userId = $request->user_id ?: Auth::id();
        $sessionId = $request->session_id ?: session()->getId();

        $wishlist = Wishlist::where('product_id', $productId)
            ->where(function ($query) use ($userId, $sessionId) {
                if ($userId) {
                    $query->where('user_id', $userId);
                } else {
                    $query->where('session_id', $sessionId);
                }
            })
            ->first();

        if (!$wishlist) {
            return response()->json(['success' => false, 'message' => 'Product not in wishlist'], 404);
        }

        $wishlist->delete();

        return response()->json(['success' => true, 'message' => 'Product removed from wishlist']);
    }

    public function getWishlist(Request $request)
    {
        $request->validate([
            'session_id' => 'nullable|string',
            'user_id' => 'nullable|exists:users,id',
        ]);

        $userId = $request->user_id ?: Auth::id();
        $sessionId = $request->session_id ?: session()->getId();

        $wishlists = Wishlist::with(['product' => function ($query) {
            $query->with(['category.parentRecursive', 'variations.attributes.value.attribute']);
        }])
            ->where(function ($query) use ($userId, $sessionId) {
                if ($userId) {
                    $query->where('user_id', $userId);
                } else {
                    $query->where('session_id', $sessionId);
                }
            })
            ->get();

        $products = $wishlists->pluck('product');

        return response()->json(['success' => true, 'data' => $products]);
    }
}