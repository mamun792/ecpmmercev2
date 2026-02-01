<?php

namespace App\Http\Controllers\Api\Coupon;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cupon\StoreCouponRequest;
use App\Http\Requests\Cupon\StoreProductCouponRequest;
use App\Http\Requests\Cupon\UpdateCouponRequest;
use App\Http\Resources\Coupon\CouponResource;
use App\Models\Coupon;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use App\Services\Coupon\CouponService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Traits\HasPaginationMeta;

use Illuminate\Support\Facades\DB;
use Throwable;
use App\Exceptions\CouponException;


class CouponController extends Controller
{
    use HasPaginationMeta;
    protected $couponService;
    public function __construct(CouponService $couponService)
    {

        $this->couponService = $couponService;
    }



    public function index(): JsonResponse
    {

        $coupons = $this->couponService->getAllCoupons();

        return response()->json([
            'status' => 'success',
            'message' => 'Coupons retrieved successfully',
            'data' => $coupons,
            'meta' => [
                'pagination' => $this->buildPaginationMeta($coupons)
            ]
        ]);
    }



    // public function store(StoreCouponRequest $request): JsonResponse
    // {
    //     return $this->couponService->create($request->validated());
    // }

    public function store(StoreCouponRequest $request): JsonResponse
    {
        try {
            $result = DB::transaction(function () use ($request) {
                $data = $request->validated();

                // Create the coupon
                $coupon = $this->couponService->create($data);

                // If this is a festival coupon that applies to all products
                if (isset($data['apply_to_all_products']) && $data['apply_to_all_products']) {
                    // Get all product IDs
                    $productIds = Product::pluck('id')->toArray();

                    // Attach all products to this coupon
                    $coupon->products()->attach($productIds);
                }

                return [
                    'message' => 'Coupon created successfully',
                    'coupon' => new CouponResource($coupon)
                ];
            });

            return response()->json($result, 201);
        } catch (\Throwable $th) {
            // Log the error
            \Log::error('Failed to create coupon: ' . $th->getMessage());

            // Return error response
            return response()->json([
                'message' => 'Failed to create coupon',
                'error' => $th->getMessage()
            ], 500);
        }
    }


    public function show(string $id): JsonResponse
    {
        $coupon = $this->couponService->findCoupon($id);


        return response()->json([
            'status' => 'success',
            'data' => new CouponResource($coupon)
        ], Response::HTTP_OK);
    }

    /**
     * Update the specified coupon in storage.
     */
    public function update(UpdateCouponRequest $request, Coupon $coupon): JsonResponse
    {
        try {
            $validated = $request->validated();

            $this->couponService->update($coupon, $validated);

            return response()->json([
                'status' => 'success',
                'message' => 'Coupon updated successfully',
                'data' => new CouponResource($coupon),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error occurred while updating the coupon',
                'errors' => [
                    'message' => $e->getMessage(),
                ],
            ], 500);
        }
    }


    /**
     * Remove the specified coupon from storage.
     */
    public function destroy(Coupon $coupon): JsonResponse
    {
        $coupon->products()->detach();
        $coupon->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * coupon products store
     */

    public function storeProductCoupon(StoreProductCouponRequest $request, $coupon_id): JsonResponse
    {


        // Find the coupon by the coupon_id from the URL
        $coupon = Coupon::find($coupon_id);

        if (!$coupon) {
            return response()->json([
                'status' => 'error',
                'message' => 'Coupon not found',
            ], 404);
        }



        try {
            $validated = $request->validated();


            $this->couponService->associateProducts($coupon, $validated['product_ids'] ?? []);

            return response()->json([
                'status' => 'success',
                'message' => 'Products associated with coupon successfully',
                'data' => new CouponResource($coupon->load('products')),
            ]);
        } catch (\Exception $e) {
            Log::error('Error associating products with coupon: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to associate products with coupon',
                'error' => $e->getMessage(),
            ], 500);
        }
    }



    /**
     * Apply coupon to a cart.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function applyToCart(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string',
            'cart_ids' => 'required|array|min:1',
            'cart_ids.*' => 'required|integer|exists:carts,id',
        ]);

        $results = [];
        $failed = [];

        foreach ($validated['cart_ids'] as $cartId) {
            try {
                $result = $this->couponService->applyToCart($validated['code'], $cartId);
                $results[] = $result;

                Log::info('Coupon applied to cart successfully', [
                    'cart_id' => $cartId,
                    'coupon_code' => $validated['code'],
                    'result' => $result
                ]);
            } catch (CouponException $e) {
                $failed[] = [
                    'cart_id' => $cartId,
                    'message' => $e->getMessage(),
                    'details' => $e->getDetails(),
                ];
            }
        }

        return response()->json([
            'success' => empty($failed),
            'applied' => $results,
            'failed' => $failed
        ], Response::HTTP_OK);
    }
}
