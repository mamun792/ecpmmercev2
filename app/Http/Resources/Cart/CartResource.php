<?php

namespace App\Http\Resources\Cart;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Services\Coupon\CouponService;

class CartResource extends JsonResource
{
    public function toArray($request)
    {
        $couponCode = session('applied_coupon');
        $discount = 0;
        $couponType = null;
        $couponValue = 0;
        $appliedItems = collect([]);

        if ($couponCode) {
            try {
                $couponService = app(CouponService::class);
                $coupon = $couponService->verifyCoupon($couponCode);
                $couponType = $coupon->discount_type;
                $couponValue = $coupon->discount_value;

                $couponData = $couponService->applyToCart($couponCode, $this->id);
                $discount = $couponData['summary']['total_discount'] ?? 0;
                $appliedItems = collect($couponData['items'] ?? [])->keyBy('cart_item_id');
            } catch (\Exception $e) {
                \Log::warning("Coupon $couponCode invalid, removing from session: " . $e->getMessage());
                session()->forget('applied_coupon');
            }
        }

        return [
            'id' => $this->id,
            'total' => $this->total,
            'items_count' => $this->items->count(),
            'items' => $this->items->filter(function ($item) {
                // Filter out items with deleted products
                return $item->product !== null;
            })->map(function ($item) use ($appliedItems) {
                $resource = new CartItemResource($item);
                $data = $resource->toArray(request());

                $appliedInfo = $appliedItems->get($item->id);
                if ($appliedInfo && $appliedInfo['is_eligible']) {
                    $data['coupon_code'] = $appliedInfo['coupon_code'];
                    $data['discount_type'] = $appliedInfo['discount_type'];
                    $data['discount_amount'] = $appliedInfo['discount_amount']; // This is the value (e.g. 10)
                    $data['item_discount'] = $appliedInfo['discount']; // This is the calculated amount (e.g. 200)
                } else {
                    $data['coupon_code'] = null;
                    $data['discount_type'] = null;
                    $data['discount_amount'] = 0;
                    $data['item_discount'] = 0;
                }

                return $data;
            }),
            'coupon_code' => $couponCode,
            'coupon_type' => $couponType,
            'coupon_value' => $couponValue,
            'discount' => $discount,
            'is_daily_product' => $this->items->contains(function ($item) {
                return $item->product && $item->product->is_daily_product;
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}
