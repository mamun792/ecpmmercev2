<?php

namespace App\Http\Controllers\Frontend\Wishlist;

use App\Http\Controllers\Controller;
use App\Services\Wishlist\WishlistService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WishlistController extends Controller
{
    protected $wishlistService;

    public function __construct(WishlistService $wishlistService)
    {
        $this->wishlistService = $wishlistService;
    }

    /**
     * Display wishlist page
     */
    public function index(Request $request)
    {
        $userId = auth()->id();
        $sessionId = $request->cookie('cart_session_id') ?? session()->get('cart_session_id');
        
        $wishlistItems = $this->wishlistService->getWishlist($userId, $sessionId);

        return Inertia::render('Frontend/Wishlist/Index', [
            'wishlistItems' => $wishlistItems
        ]);
    }

    /**
     * Add product to wishlist
     */
    public function add(Request $request)
    {
        $request->validate([
            'product_id' => 'required|integer|exists:products,id',
        ]);

        $userId = auth()->id();
        $sessionId = $request->cookie('cart_session_id') ?? session()->get('cart_session_id');

        $result = $this->wishlistService->addToWishlist($request->product_id, $userId, $sessionId);

        if ($result['success']) {
            return back()->with('success', $result['message']);
        }

        return back()->with('error', $result['message']);
    }

    /**
     * Remove product from wishlist
     */
    public function remove(Request $request)
    {
        $request->validate([
            'product_id' => 'required|integer|exists:products,id',
        ]);

        $userId = auth()->id();
        $sessionId = $request->cookie('cart_session_id') ?? session()->get('cart_session_id');

        $result = $this->wishlistService->removeFromWishlist($request->product_id, $userId, $sessionId);

        if ($result['success']) {
            return back()->with('success', $result['message']);
        }

        return back()->with('error', $result['message']);
    }
}
