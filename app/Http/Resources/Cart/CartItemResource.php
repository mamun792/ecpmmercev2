<?php

namespace App\Http\Resources\Cart;

use Illuminate\Http\Resources\Json\JsonResource;

class CartItemResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'product_name' => $this->product->name ?? 'Product Not Available',
            'product_image' => $this->product->feature_image ?? null,
            'is_pre_order' => $this->product->is_pre_order ?? false,
            'is_free_delivery' => $this->product->is_free_delivery ?? false,
            'variation_id' => $this->product_variation_id,
            'variation_attributes' => $this->when($this->variation && $this->variation->attributes, function () {
                return $this->variation->attributes->map(function ($attr) {
                    if (!$attr->value || !$attr->value->attribute) {
                        return null;
                    }
                    return [
                        'name' => $attr->value->attribute->name,
                        'value' => $attr->value->value
                    ];
                })->filter();
            }),
            'price' => $this->price,
            'quantity' => $this->quantity,
            'subtotal' => $this->price * $this->quantity,
            'options' => $this->options,
        ];
    }
}
