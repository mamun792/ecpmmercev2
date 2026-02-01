<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class OrderItem extends Model
{
    use HasFactory;
    protected $fillable = [
        'order_id',
        'product_id',
        'product_variation_id',
        'quantity',
        'unit_price',
        'subtotal',
        'discount_type',
        'discount_amount',
        'discount_total',
        'final_price',
        'coupon_code',
        'options',
        'is_pre_order',
    ];


    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'options' => 'array',
        'is_pre_order' => 'boolean',
    ];

    /**
     * Get the order that owns the item
     *
     * @return BelongsTo
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the product associated with this item
     *
     * @return BelongsTo
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    /**
     * Get the product variation if any
     * Include soft-deleted variations to preserve order history
     *
     * @return BelongsTo|null
     */
    public function productVariation(): BelongsTo
    {
        return $this->belongsTo(ProductVariation::class)->withTrashed();
    }
}
