<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class ProductGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'status',
        'order_number',
        'banner',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($productGroup) {
            if (empty($productGroup->slug)) {
                $productGroup->slug = Str::slug($productGroup->name);
            }
        });

        static::updating(function ($productGroup) {
            if ($productGroup->isDirty('name') && !$productGroup->isDirty('slug')) {
                $productGroup->slug = Str::slug($productGroup->name);
            }
        });
    }

    /**
     * Get the products for the product group.
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_group_product')
            ->withTimestamps();
    }
}
