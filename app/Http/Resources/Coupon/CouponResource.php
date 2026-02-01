<?php

namespace App\Http\Resources\Coupon;

use App\Http\Resources\Product\ProductResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CouponResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'discount_value' => $this->discount_value,
            'discount_type' => $this->discount_type,
            'max_uses' => $this->max_uses,
            'uses_count' => $this->uses_count,
            'start_date' => $this->start_date ? $this->start_date->toIso8601String() : null,
            'expiry_date' => $this->expiry_date ? $this->expiry_date->toIso8601String() : null,
            'is_active' => $this->is_active,
            'is_valid' => $this->isValid(),
            'products' => ProductResource::collection($this->whenLoaded('products')),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
