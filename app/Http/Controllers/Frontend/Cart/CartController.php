<?php

namespace App\Http\Controllers\Frontend\Cart;

use Inertia\Inertia;
use Inertia\Response;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Services\Cart\CartService;
use App\Http\Requests\Cart\AddToCartRequest;
use App\Http\Requests\Cart\UpdateCartItemRequest;
use App\Http\Resources\Cart\CartResource;
use App\Services\Coupon\CouponService;

class CartController extends Controller
{
    protected $cartService;
    protected $couponService;

    public function __construct(CartService $cartService, CouponService $couponService)
    {
        $this->cartService = $cartService;
        $this->couponService = $couponService;
    }

    /**
     * Get or create a consistent cart session ID
     * Uses cookie to persist across login/logout
     */
    protected function getCartSessionId(Request $request): string
    {
        // First check cookie (persists across login/logout)
        $sessionId = $request->cookie('cart_session_id');
        
        // Fallback to session (for backward compatibility)
        if (!$sessionId) {
            $sessionId = $request->session()->get('cart_session_id');
        }
        
        // Generate new if none exists
        if (!$sessionId) {
            $sessionId = 'anon-' . base64_encode(substr($request->userAgent() ?? 'unknown', 0, 20)) . '-' . Str::random(9);
        }
        
        // Store in both session and cookie
        $request->session()->put('cart_session_id', $sessionId);
        cookie()->queue('cart_session_id', $sessionId, 60 * 24 * 30); // 30 days
        
        return $sessionId;
    }

    /**
     * Display the cart page (Inertia)
     */
    public function index(Request $request): Response
    {
        $userId = auth()->id() ?? null;
        $sessionId = $this->getCartSessionId($request);

        $cart = $this->cartService->getCart($userId, $sessionId);

        return Inertia::render('Frontend/Cart/Index', [
            'cartItems' => $cart && $cart->items ? $cart->items : [],
            'session_id' => $cart->session_id ?? $sessionId,
        ]);
    }

    /**
     * Add product to cart (used from frontend forms)
     */
    public function addToCart(AddToCartRequest $request)
    {
        try {
            $userId = auth()->id() ?? null;
            // Use stored session ID, ignore what frontend sends to ensure consistency
            $sessionId = $this->getCartSessionId($request);
            $userOrSession = $userId ?: $sessionId;

            $cart = $this->cartService->addToCart($request->validated(), $userOrSession);

            return redirect()->back()->with('success', 'Product added to cart successfully')->with('session_id', $cart->session_id);
        } catch (\Exception $e) {
            Log::error('Frontend addToCart error: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Update cart item quantity
     */
    public function updateItemQuantity(UpdateCartItemRequest $request)
    {
        try {
            $userId = auth()->id() ?? null;
            $sessionId = $this->getCartSessionId($request);

            $cart = $this->cartService->updateItemQuantity($request->validated(), $userId, $sessionId);

            return redirect()->back()->with('success', 'Cart updated successfully');
        } catch (\Exception $e) {
            Log::error('Frontend updateItemQuantity error: '.$e->getMessage());
            return redirect()->back()->with('error', 'Unable to update cart');
        }
    }

    /**
     * Remove an item from cart
     */
    public function removeItem(Request $request)
    {
        $request->validate(['item_id' => 'required|integer']);

        try {
            $this->cartService->removeItem($request->item_id);
            return redirect()->back()->with('success', 'Item removed from cart');
        } catch (\Exception $e) {
            Log::error('Frontend removeItem error: '.$e->getMessage());
            return redirect()->back()->with('error', 'Unable to remove item');
        }
    }

    /**
     * Clear the cart
     */
    public function clear(Request $request)
    {
        try {
            $userId = auth()->id() ?? null;
            $sessionId = $this->getCartSessionId($request);
            $this->cartService->deleteCart($userId, $sessionId);

            return redirect()->route('cart.index')->with('success', 'Cart cleared');
        } catch (\Exception $e) {
            Log::error('Frontend clearCart error: '.$e->getMessage());
            return redirect()->back()->with('error', 'Unable to clear cart');
        }
    }

    /**
     * Apply coupon to cart
     */
    public function applyCoupon(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        try {
            $userId = auth()->id() ?? null;
            $sessionId = $this->getCartSessionId($request);
            $cart = $this->cartService->getCart($userId, $sessionId);

            if (!$cart) {
                return redirect()->back()->with('error', 'Cart is empty');
            }

            // Security Note: We determine the cart_id securely from the user's session/auth.
            // We do NOT accept 'cart_ids' from the request payload to prevent users from applying coupons to other carts.
            $result = $this->couponService->applyToCart($request->code, $cart->id);

            // Check if any discount was actually applied
            $totalDiscount = $result['summary']['total_discount'] ?? 0;
            
            if ($totalDiscount <= 0) {
                 return redirect()->back()->with('error', 'Coupon is valid but not applicable to any items in your cart.');
            }

            // Store in session to remember it for this session/cart
            $request->session()->put('applied_coupon', $request->code);

            return redirect()->back()->with('success', 'Coupon applied successfully!');
        } catch (\Exception $e) {
            Log::error('Frontend applyCoupon error: ' . $e->getMessage());
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
