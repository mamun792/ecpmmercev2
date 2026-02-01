<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;


class Coupon extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'discount_value',
        'discount_type',
        'max_uses',
        'uses_count',
        'start_date',
        'expiry_date',
        'is_active',
        'apply_to_all_products',
        'festival_name',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'max_uses' => 'integer',
        'uses_count' => 'integer',
        'start_date' => 'datetime',
        'expiry_date' => 'datetime',
        'is_active' => 'boolean',
        'apply_to_all_products' => 'boolean',
        'festival_name' => 'string',
    ];

    /**
     * The products that belong to the coupon.
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class)
            ->withTimestamps();
    }

    /**
     * Calculate discount amount based on coupon type and value
     *
     * @param float $price
     * @return float
     */
    public function calculateDiscount(float $price): float
    {
        // Return 0 if price is 0 or less
        if ($price <= 0) {
            return 0;
        }

        // Check if order meets minimum order value requirement
        if ($this->min_order_value && $price < $this->min_order_value) {
            return 0;
        }

        $discount = 0;

        // Calculate discount based on type
        switch ($this->discount_type) {
            case 'percentage':
                $discount = ($price * $this->discount_value) / 100;
                break;
            case 'fixed':
                $discount = min($this->discount_value, $price);
                break;
            default:
                $discount = 0;
        }

        // Apply maximum discount limit if set
        if ($this->max_discount_amount && $discount > $this->max_discount_amount) {
            $discount = $this->max_discount_amount;
        }

        return $discount;
    }


    public function isValid(): bool
    {
        $now = now();

        // Check if coupon is active
        if (!$this->is_active) {
            return false;
        }

        // Check if coupon has reached max uses
        if ($this->max_uses !== null && $this->uses_count >= $this->max_uses) {
            return false;
        }

        // Check if coupon has started
        if ($this->start_date && $now->lt($this->start_date)) {
            return false;
        }

        // Check if coupon has expired
        if ($this->expiry_date && $now->gt($this->expiry_date)) {
            return false;
        }

        return true;
    }
}
