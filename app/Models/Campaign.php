<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campaign extends Model
{
    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'status',
        'discount_type',
        'discount_amount'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'discount_amount' => 'decimal:2'
    ];

    public function campaignProducts(): HasMany
    {
        return $this->hasMany(CampaignProduct::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'campaign_products');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active')
                    ->where('start_date', '<=', now())
                    ->where('end_date', '>=', now());
    }

    public function isActive(): bool
    {
        return $this->status === 'active' &&
               $this->start_date <= now() &&
               $this->end_date >= now();
    }

    public function calculateDiscountedPrice(float $originalPrice): float
    {
        if ($this->discount_type === 'fixed') {
            return max(0, $originalPrice - $this->discount_amount);
        } elseif ($this->discount_type === 'percentage') {
            $discount = $originalPrice * ($this->discount_amount / 100);
            return max(0, $originalPrice - $discount);
        }

        return $originalPrice;
    }
}