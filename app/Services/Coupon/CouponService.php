<?php

namespace App\Services\Coupon;

use App\Http\Resources\Coupon\CouponResource;
use App\Models\Coupon;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use App\Models\Cart;
use App\Models\Order;
use App\Exceptions\CouponException;
use Illuminate\Support\Collection;

use Illuminate\Http\JsonResponse;

class CouponService
{

    public function getAllCoupons(): LengthAwarePaginator
    {
        return Coupon::query()
            ->latest()
            ->paginate(10);
    }


    // public function create(array $data): JsonResponse
    // {
    //     try {
    //         // Create the coupon using the validated data
    //         $coupon = Coupon::create($data);

    //         // Prepare the response data, including the created coupon details
    //         $responseData = new CouponResource($coupon);

    //         return response()->json([
    //             'status' => 'success',
    //             'message' => 'Coupon created successfully',
    //             'data' => $responseData,
    //         ], 201);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'Error occurred while creating the coupon',
    //             'errors' => [
    //                 'message' => $e->getMessage(),
    //             ],
    //         ], 500);
    //     }
    // }
    // Update your CouponService create method
    public function create(array $data): Coupon
    {
        // Create and return the coupon model (not a response)
        return Coupon::create($data);
    }

    public function findCoupon(string $id): Coupon
    {
        $coupon = Coupon::findorFail($id);


        if (!$coupon) {
            // custom hanler
            throw new ModelNotFoundException('Coupon not found');
        }

        return $coupon;
    }

    public function update(Coupon $coupon, array $data): void
    {
        $coupon->update($data);
    }

    public function delete(string $id): JsonResponse
    {
        try {
            $coupon = Coupon::findOrFail($id);
            $coupon->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Coupon deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error occurred while deleting the coupon',
                'errors' => [
                    'message' => $e->getMessage(),
                ],
            ], 500);
        }
    }

    public function associateProducts(Coupon $coupon, array $productIds): Coupon
    {
        DB::beginTransaction();

        try {
            // Check if product IDs are provided and valid
            if (!empty($productIds)) {
                $validProductCount = Product::whereIn('id', $productIds)->count();

                if ($validProductCount !== count($productIds)) {
                    throw new \Exception('One or more products do not exist');
                }
            }

            // Sync products with coupon
            $coupon->products()->sync($productIds);

            DB::commit();
            return $coupon;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }



    /**
     * Verify coupon validity
     *
     * @param string $code
     * @return Coupon
     * @throws CouponException
     */
    public function verifyCoupon(string $code): Coupon
    {
        try {
            $coupon = Coupon::where('code', $code)->firstOrFail();

            if (!$coupon->is_active) {
                throw new CouponException('Coupon is inactive', 422);
            }

            if ($coupon->expiry_date && now()->gt($coupon->expiry_date)) {
                throw new CouponException('Coupon has expired', 422);
            }

            if ($coupon->start_date && now()->lt($coupon->start_date)) {
                throw new CouponException('Coupon is not yet active', 422);
            }

            if ($coupon->max_uses && $coupon->uses_count >= $coupon->max_uses) {
                throw new CouponException('Coupon usage limit reached', 422);
            }

            return $coupon;
        } catch (ModelNotFoundException $e) {
            throw new CouponException('Coupon not found', 422);
        } catch (CouponException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new CouponException('An error occurred during coupon verification', 500);
        }
    }

    /**
     * Apply coupon to a list of products (direct order flow)
     *
     * @param string $code
     * @param array $productIds
     * @return array
     * @throws CouponException
     */
    public function applyToProducts(string $code, array $productIds): array
    {
        try {
            // Verify coupon
            $coupon = $this->verifyCoupon($code);

            // Get products
            $products = Product::with('variations')->findMany($productIds);

            if ($products->isEmpty()) {
                throw new CouponException('No products found', 422);
            }

            // Filter eligible products
            $eligibleProducts = $this->filterEligibleProducts($coupon, $products);

            if ($eligibleProducts->isEmpty()) {
                throw new CouponException('Coupon is not applicable to any of the selected products', 422);
            }

            // Calculate discounts for eligible products
            return $this->calculateProductDiscounts($coupon, $eligibleProducts, $products);
        } catch (CouponException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new CouponException('An error occurred while applying the coupon', 500);
        }
    }

    /**
     * Apply coupon to a cart
     *
     * @param string $code
     * @param int $cartId
     * @return array
     * @throws CouponException
     */
    public function applyToCart(string $code, int $cartId): array
    {
        try {
            // Step 1: Verify coupon validity
            $coupon = $this->verifyCoupon($code);

            // Step 2: Load the cart with related products/variations
            $cart = Cart::with(['items.product', 'items.variation'])->findOrFail($cartId);

            if ($cart->items->isEmpty()) {
                throw new CouponException('Cart is empty', 422);
            }

            // Step 3: Calculate and return the discount (with filtering)
            $result = $this->calculateCartDiscountsWithFiltering($coupon, $cart);

            Log::info('Coupon applied successfully', [
                'cart_id' => $cartId,
                'coupon_code' => $code,
                'discounts' => $result
            ]);

            return $result;
        } catch (CouponException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Coupon application failed', [
                'error' => $e->getMessage(),
                'cart_id' => $cartId,
                'coupon_code' => $code
            ]);
            throw new CouponException('An error occurred while applying the coupon', 500);
        }
    }

    /**
     * Filter products eligible for coupon
     *
     * @param Coupon $coupon
     * @param Collection $products
     * @return Collection
     */
    protected function filterEligibleProducts(Coupon $coupon, Collection $products): Collection
    {
        // If coupon applies to all products, return all
        if ($coupon->apply_to_all_products) {
            return $products;
        }

        $applicableProductIds = $coupon->products->pluck('id')->toArray();

        // If not apply to all AND no specific products linked, none are eligible
        if (empty($applicableProductIds)) {
            return collect([]);
        }

        // Filter only eligible products
        $eligibleProducts = $products->filter(function ($product) use ($applicableProductIds) {
            return in_array($product->id, $applicableProductIds);
        });

        return $eligibleProducts;
    }

    /**
     * Calculate discounts for a list of products, separating eligible and ineligible
     *
     * @param Coupon $coupon
     * @param Collection $eligibleProducts
     * @param Collection $allProducts
     * @return array
     */
    protected function calculateProductDiscounts(Coupon $coupon, Collection $eligibleProducts, Collection $allProducts): array
    {
        $result = [];
        $totalOriginalPrice = 0;
        $totalDiscount = 0;
        $ineligibleProducts = [];

        // Create a lookup map for eligible products
        $eligibleProductIds = $eligibleProducts->pluck('id')->toArray();

        foreach ($allProducts as $product) {
            $isEligible = in_array($product->id, $eligibleProductIds);

            if ($product->type === 'simple') {
                // Handle simple product
                $totalPrice = $product->price;

                if ($isEligible) {
                    $discount = $coupon->calculateDiscount($totalPrice);
                    $discountedPrice = $totalPrice - $discount;
                    $totalDiscount += $discount;
                } else {
                    $discount = 0;
                    $discountedPrice = $totalPrice;
                    $ineligibleProducts[] = [
                        'product_id' => $product->id,
                        'name' => $product->name
                    ];
                }

                $result[] = [
                    'product_id' => $product->id,
                    'name' => $product->name,
                    'type' => 'simple',
                    'original_price' => $totalPrice,
                    'discount' => $discount,
                    'discounted_price' => $discountedPrice,
                    'is_eligible' => $isEligible,
                    'variations' => []
                ];

                $totalOriginalPrice += $totalPrice;
            } else if ($product->type === 'variable') {
                // Handle variable product
                $variations = $product->variations;
                $variationResults = [];
                $productTotalPrice = 0;
                $productTotalDiscount = 0;

                foreach ($variations as $variation) {
                    $variationPrice = $variation->price;

                    if ($isEligible) {
                        $discount = $coupon->calculateDiscount($variationPrice);
                        $discountedPrice = $variationPrice - $discount;
                        $productTotalDiscount += $discount;
                    } else {
                        $discount = 0;
                        $discountedPrice = $variationPrice;
                    }

                    $variationResults[] = [
                        'variation_id' => $variation->id,
                        'original_price' => $variationPrice,
                        'discount' => $discount,
                        'discounted_price' => $discountedPrice,
                        'is_eligible' => $isEligible
                    ];

                    $productTotalPrice += $variationPrice;
                }

                if (!$isEligible) {
                    $ineligibleProducts[] = [
                        'product_id' => $product->id,
                        'name' => $product->name
                    ];
                }

                $result[] = [
                    'product_id' => $product->id,
                    'name' => $product->name,
                    'type' => 'variable',
                    'original_price' => $productTotalPrice,
                    'discount' => $productTotalDiscount,
                    'discounted_price' => $productTotalPrice - $productTotalDiscount,
                    'is_eligible' => $isEligible,
                    'variations' => $variationResults
                ];

                $totalOriginalPrice += $productTotalPrice;
                $totalDiscount += $productTotalDiscount;
            }
        }

        $response = [
            'success' => true,
            'message' => empty($ineligibleProducts)
                ? 'Coupon applied successfully to all products'
                : 'Coupon applied partially - some products are not eligible',
            'products' => $result,
            'summary' => [
                'total_original_price' => $totalOriginalPrice,
                'total_discount' => $totalDiscount,
                'total_discounted_price' => $totalOriginalPrice - $totalDiscount
            ]
        ];

        // Add ineligible products to response if any
        if (!empty($ineligibleProducts)) {
            $response['ineligible_products'] = $ineligibleProducts;
        }

        return $response;
    }

    /**
     * Calculate discounts for cart items with filtering for eligible products
     *
     * @param Coupon $coupon
     * @param Cart $cart
     * @return array
     */
    protected function calculateCartDiscountsWithFiltering(Coupon $coupon, Cart $cart): array
    {
        $result = [];
        $totalOriginalPrice = 0;
        $totalDiscount = 0;
        $ineligibleItems = [];
        $applicableProductIds = $coupon->products->pluck('id')->toArray();

        // Logic fix: Check apply_to_all_products flag first
        // If flag is 1 (true) -> Applies to all.
        // If flag is 0 (false) -> MUST be in the applicableProductIds list.
        $applyToAll = $coupon->apply_to_all_products;

        foreach ($cart->items as $item) {
            $product = $item->product;
            $quantity = $item->quantity;
            
            if ($applyToAll) {
                $isEligible = true;
            } else {
                // If not apply to all, strictly check if product ID is in the list
                // If list is empty and applyToAll is false, NO product should be eligible
                $isEligible = !empty($applicableProductIds) && in_array($product->id, $applicableProductIds);
            }

            // Determine unit price from variation or base product
            $unitPrice = $item->product_variation_id
                ? $item->variation->price + $item->product->price
                : $product->price;

            // Apply active campaign price (if any) to align with cart display
            $discountedUnitPrice = $unitPrice;
            if (!empty($product->campaigns)) {
                $now = now();
                foreach ($product->campaigns as $campaign) {
                    $start = \Carbon\Carbon::parse($campaign->start_date);
                    $end = \Carbon\Carbon::parse($campaign->end_date);
                    if ($campaign->status === 'active' && $now->between($start, $end)) {
                        $discountedUnitPrice = $campaign->calculateDiscountedPrice($unitPrice);
                        break;
                    }
                }
            }

            // Calculate totals with quantity
            $totalPrice = $discountedUnitPrice * $quantity;

            $cartItemResult = [
                'cart_item_id' => $item->id,
                'product_id' => $product->id,
                'name' => $product->name,
                'quantity' => $quantity,
                'unit_price' => $discountedUnitPrice,
                'total_price' => $totalPrice,
            ];

            // Only apply discount if product is eligible
            if ($isEligible) {
                // Calculate total discount for this line (considering quantity)
                $discount = $coupon->calculateDiscount($totalPrice);
                $discountedPrice = $totalPrice - $discount;

                // For fixed-type coupons, normalize discount_amount to be per-unit monetary value
                if ($coupon->discount_type === 'fixed') {
                    $perUnitDiscount = $quantity > 0 ? ($discount / $quantity) : 0; // distribute evenly
                    $discountAmountField = $perUnitDiscount;
                } else {
                    // percentage: keep as percentage value (e.g., 10 for 10%) - percent applies to the unit after campaign
                    $discountAmountField = $coupon->discount_value;
                }

                $cartItemResult = array_merge($cartItemResult, [
                    'coupon_code' => $coupon->code,
                    'discount_type' => $coupon->discount_type,
                    'discount_amount' => $discountAmountField,
                    'discount' => $discount,
                    'discounted_price' => $discountedPrice,
                    'is_eligible' => true
                ]);

                $totalDiscount += $discount;
            } else {                $cartItemResult = array_merge($cartItemResult, [
                    'discount' => 0,
                    'discounted_price' => $totalPrice,
                    'is_eligible' => false
                ]);

                $ineligibleItems[] = [
                    'product_id' => $product->id,
                    'name' => $product->name
                ];
            }

            // Add variation info if applicable
            if ($item->product_variation_id) {
                $cartItemResult['variation_id'] = $item->product_variation_id;
            }

            $result[] = $cartItemResult;
            $totalOriginalPrice += $totalPrice;
        }

        $response = [
            'success' => true,
            'message' => empty($ineligibleItems)
                ? 'Coupon applied successfully to all items in cart'
                : 'Coupon applied partially - some items are not eligible',
            'cart_id' => $cart->id,
            'items' => $result,
            'summary' => [
                'total_original_price' => $totalOriginalPrice,
                'total_discount' => $totalDiscount,
                'total_discounted_price' => $totalOriginalPrice - $totalDiscount
            ]
        ];

        // Add ineligible items to response if any
        if (!empty($ineligibleItems)) {
            $response['ineligible_items'] = $ineligibleItems;
        }

        return $response;
    }

    /**
     * Increment coupon usage count
     *
     * @param string $couponCode
     * @return void
     */
    public function adjustCouponLimit(string $couponCode): void
    {
        $coupon = Coupon::where('code', $couponCode)->first();

        if (
            !$coupon ||
            !$coupon->is_active ||
            $coupon->start_date > now() ||
            $coupon->expiry_date < now() ||
            $coupon->uses_count >= $coupon->max_uses
        ) {
            return;
        }

        $coupon->increment('uses_count');
    }
}
