<?php

namespace App\Http\Controllers\Admin\Coupon;
use Inertia\Inertia;
use App\Models\Coupon;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Traits\HasPaginationMeta;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Services\Coupon\CouponService;
use App\Services\Product\ProductService;
use App\Http\Resources\Coupon\CouponResource;
use App\Http\Requests\Cupon\StoreCouponRequest;
use App\Http\Requests\Cupon\UpdateCouponRequest;
use App\Http\Requests\Cupon\StoreProductCouponRequest;

class CouponController extends Controller
{

    use HasPaginationMeta;
    protected $couponService;
    protected $productService;
    public function __construct(CouponService $couponService, ProductService $productService)
    {

        $this->couponService = $couponService;
        $this->productService = $productService;
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {

        $coupons = $this->couponService->getAllCoupons();

        return Inertia::render('Admin/Coupon/Index', [
            'coupons' => $coupons,
            'meta' => [
                'pagination' => $this->buildPaginationMeta($coupons)
            ]
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Admin/Coupon/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCouponRequest $request)
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

            return redirect()->route('admin.coupons.index')->with([
                'status' => 'success',
                'message' => $result['message'],
                'coupon' => $result['coupon']
            ]);
        } catch (\Throwable $th) {
            // Log the error
            Log::error('Failed to create coupon: ' . $th->getMessage());

            // Return error response
            return response()->json([
                'message' => 'Failed to create coupon',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $coupon = $this->couponService->findCoupon($id);


        return response()->json([
            'status' => 'success',
            'data' => new CouponResource($coupon)
        ], Response::HTTP_OK);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $coupon = $this->couponService->findCoupon($id);

        return Inertia::render('Admin/Coupon/Edit', [
            'coupon' => new CouponResource($coupon)
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCouponRequest $request, Coupon $coupon)
    {
        try {
            $validated = $request->validated();

            $this->couponService->update($coupon, $validated);

            return redirect()->route('admin.coupons.index')->with([
                'status' => 'success',
                'message' => 'Coupon updated successfully'
            ]);
        } catch (\Exception $e) {
            return redirect()->back()->with([
                'status' => 'error',
                'message' => 'Failed to update coupon: ' . $e->getMessage()
            ]);
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Coupon $coupon)
    {
        $coupon->products()->detach();
        $coupon->delete();

        return redirect()->route('admin.coupons.index')->with([
            'status' => 'success',
            'message' => 'Coupon deleted successfully'
        ]);
    }


    public function couponAddedToProduct($id, Request $request)
    {
        $coupon = $this->couponService->findCoupon($id);
        $products = $this->productService->getAllProductsForAdmin($request);
    
        // Get the product IDs assigned to the coupon
        $assignedProductIds = DB::table('coupon_product')
            ->where('coupon_id', $id)
            ->whereIn('product_id', $products->pluck('id')->toArray())
            ->pluck('product_id')
            ->toArray();

            //return $assignedProductIds;
    
        // Return the Inertia response with coupon, products, and assigned product IDs
        return Inertia::render('Admin/Coupon/CouponProduct', [
            'coupon' => new CouponResource($coupon),
            'filters' => $request->only(['search']),
            'products' => $products,
            'assignedProductIds' => $assignedProductIds, // Optionally include this for the frontend
            'meta' => [
                'pagination' => $this->buildPaginationMeta($products)
            ]
        ]);
    }


    public function storeProductCoupon(StoreProductCouponRequest $request, $coupon_id)
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

            return redirect()->back()->with([
                'status' => 'success',
                'message' => 'Products associated with coupon successfully'
            ]);
        } catch (\Exception $e) {
            //Log::error('Error associating products with coupon: ' . $e->getMessage());
            return redirect()->back()->with([
                'status' => 'error',
                'message' => 'Failed to associate products with coupon: ' . $e->getMessage()
            ]);
        }
    }



}
