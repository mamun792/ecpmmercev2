<?php

namespace App\Http\Controllers\Api\Cart;

use App\Models\Cart;
use App\Models\CartItem;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Services\Cart\CartService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Resources\Cart\CartResource;
use App\Http\Requests\Cart\AddToCartRequest;
use App\Exceptions\InsufficientStockException;

use App\Exceptions\ProductNotAvailableException;
use App\Http\Requests\Cart\UpdateCartItemRequest;

class CartController extends Controller
{
    protected $cartService;

    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    /**
     * Add product to cart
     */
    public function addToCart(AddToCartRequest $request)
    {
        // Log::info('Adding product to cart', ['request' => $request->all()]);

        try {
            $userId = $request->user_id ?? null;
            $sessionId = $request->session_id ?? null;
            $userOrSession = $userId ? $userId : $sessionId;
            Log::info(['userOrSession' => $userOrSession]);
            $cart = $this->cartService->addToCart(
                $request->validated(),
                $userOrSession

            );

            return response()->json([
                'success' => true,
                'message' => 'Product added to cart successfully',
                'data' => new CartResource($cart),
                'session_id' => $cart->session_id,
            ], Response::HTTP_OK);
        } catch (ProductNotAvailableException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error_code' => 'PRODUCT_NOT_AVAILABLE'
            ], Response::HTTP_BAD_REQUEST);
        } catch (InsufficientStockException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error_code' => 'INSUFFICIENT_STOCK'
            ], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            Log::error('Add to cart error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while adding to cart',
                'error_code' => 'SERVER_ERROR'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get active cart
     */
    public function getCart(Request $request)
    {
        try {
            $userId = $request->user_id ?? null;
            // Log::info('User ID: ' . $userId);
            $sessionId = $request->session_id ?? null;
            //Log::info('Session ID: ' . $sessionId);

            $cart = $this->cartService->getCart($userId, $sessionId);
            if (!$cart) {
                return response()->json([
                    'success' => true,
                    'message' => 'Cart not found',
                    'error_code' => 'CART_NOT_FOUND'
                ], Response::HTTP_OK);
            }

            return response()->json([
                'success' => true,
                'data' => new CartResource($cart),
                'session_id' => $cart->session_id,
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('Get cart error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving cart',
                'error_code' => 'SERVER_ERROR'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update cart item quantity
     */
    /**
     * Update cart item quantity
     *
     * @param UpdateCartItemRequest $request
     * @return JsonResponse
     */
    public function updateItemQuantity(UpdateCartItemRequest $request)
    {
        try {
            //  $userId = 1; // $request->user()->id;Set to 1 for testing
            $userId = $request->user_id ?? null;
            // $sessionId = $request->session_id ?? null;
            $sessionId = $request->session_id ?? null;

            $cart = $this->cartService->updateItemQuantity(
                $request->validated(),
                $userId,
                $sessionId
            );
            Log::info('Cart updated successfully', [
                'cart_id' => $cart->id,
                'user_id' => $userId,
                'session_id' => $sessionId
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Cart updated successfully',
                'data' => new CartResource($cart),
            ], Response::HTTP_OK);
        } catch (CartNotFoundException | ItemNotFoundException $e) {
            return $this->handleError($e->getMessage(), 'RESOURCE_NOT_FOUND', Response::HTTP_NOT_FOUND);
        } catch (InsufficientStockException $e) {
            return $this->handleError($e->getMessage(), 'INSUFFICIENT_STOCK', Response::HTTP_BAD_REQUEST);
        } catch (ProductNotFoundException $e) {
            return $this->handleError($e->getMessage(), 'PRODUCT_NOT_FOUND', Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            Log::error('Update cart error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);

            return $this->handleError('An error occurred while updating cart', 'SERVER_ERROR', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Handle error response
     */
    private function handleError($message, $errorCode, $statusCode)
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'error_code' => $errorCode
        ], $statusCode);
    }

    /**
     * Remove item from cart
     */
    public function destroy(Request $request)
    {
        $request->validate([
            'session_id' => 'required|string',
        ]);
        try {
            $response = $this->cartService->deleteCart(auth()->id(), $request->session_id);
            return response()->json($response, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function destroyCartItem(Request $request)
    {
        Log::info('Removing cart item', ['request' => $request->all()]);
        DB::beginTransaction();

        try {
            $cartItem = CartItem::findOrFail($request->item_id);
            $cart_id = $cartItem->cart_id;
            $itemPrice = $cartItem->price; // Assuming price is stored in CartItem
            $cartItem->delete();

            // Update the cart's total by subtracting the item's price
            $cart = Cart::findOrFail($cart_id);
            $cart->total -= $itemPrice;
            $cart->save();

            // Check if the cart has any remaining items
            $remainingItems = CartItem::where('cart_id', $cart_id)->count();
            Log::info('Remaining items in cart', ['remainingItems' => $remainingItems]);

            if ($remainingItems === 0) {
                Log::info($cart);
                $cart->delete();
                //$this->cartService->deleteCart($cart->user_id, $cart->session_id);
            }

            DB::commit();
            return response()->json(['message' => 'Cart item removed successfully']);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['error' => 'Failed to remove cart item'], 500);
        }
    }
}
