<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'product_code',
        'category_id',
        'brand_id',
        'short_description',
        'description',
        'status',
        'is_daily_product',
        'type',
        'price',
        'cost_price',
        'previous_price',
        'youtube_video',
        'feature_image',
        'upload_video',
        'gallery_images',
        'description_images',
        'product_tags',
        'specification',
        'view_count',
        'is_free_delivery',
        'is_pre_order',
        'remarks',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'search_keywords',
        'track_inventory',
        'stock_status',
        'allow_backorders',
        'sort_order',
        'barcode',
        'isbn',
        'weight',
        'dimensions',
        'published_at',
        'featured_at',
        'deleted_by',
        'deletion_reason',
        'deletion_context',
        'recovered_at',
        'recovered_by'
    ];

    protected $casts = [
        'gallery_images' => 'array',
        'description_images' => 'array',
        'product_tags' => 'array',
        'specification' => 'array',
        'dimensions' => 'array',
        'deletion_context' => 'array',
        'is_free_delivery' => 'boolean',
        'is_pre_order' => 'boolean',
        'is_daily_product' => 'boolean',
        'track_inventory' => 'boolean',
        'allow_backorders' => 'boolean',
        'published_at' => 'datetime',
        'featured_at' => 'datetime',
        'recovered_at' => 'datetime',
        'weight' => 'decimal:2',
        'price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'previous_price' => 'decimal:2',
    ];

    protected $appends = ['feature_image_url', 'avg_rating', 'reviews_count', 'stock', 'variations_count'];

    // Load category including soft-deleted for historical records (orders, invoices)
    public function category()
    {
        return $this->belongsTo(Category::class)->withTrashed()->with('parentRecursive');
    }

    public function variations()
    {
        return $this->hasMany(ProductVariation::class);
    }

    public function coupons(): BelongsToMany
    {
        return $this->belongsToMany(Coupon::class)
            ->withTimestamps();
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function campaigns()
    {
        return $this->belongsToMany(Campaign::class, 'campaign_products')
                    ->withTimestamps();
    }

    public function inventoryStocks()
    {
        return $this->hasMany(InventoryStock::class);
    }

    public function inventoryTransactions()
    {
        return $this->hasMany(InventoryTransaction::class);
    }

    public function stockReservations()
    {
        return $this->hasMany(StockReservation::class);
    }

    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function recoveredBy()
    {
        return $this->belongsTo(User::class, 'recovered_by');
    }

    /**
     * Get total available stock across all locations
     */
    public function getTotalStockAttribute()
    {
        return $this->inventoryStocks()->sum('available_quantity');
    }

    /**
     * Alias for total_stock - Used by frontend UI
     */
    public function getStockAttribute(): int
    {
        return (int) $this->total_stock;
    }

    /**
     * Get total reserved stock across all locations
     */
    public function getTotalReservedAttribute()
    {
        return $this->inventoryStocks()->sum('reserved_quantity');
    }

    /**
     * Check if product is in stock
     */
    public function getIsInStockAttribute()
    {
        return $this->total_stock > 0;
    }

    /**
     * Check if product is low stock
     */
    public function getIsLowStockAttribute()
    {
        $minThreshold = $this->inventoryStocks()->min('minimum_threshold') ?? 0;
        return $this->total_stock <= $minThreshold && $this->total_stock > 0;
    }

    public function productGroups(): BelongsToMany
    {
        return $this->belongsToMany(ProductGroup::class, 'product_group_product')
                    ->withTimestamps();
    }

    public function reviews()
    {
        return $this->hasMany(Review::class)->where('is_approved', true);
    }

    /**
     * Get the average rating for the product
     */
    public function getAvgRatingAttribute()
    {
        // Check if avg_rating was eager loaded via withAvg('reviews', 'rating') which defaults to reviews_avg_rating
        if (array_key_exists('reviews_avg_rating', $this->attributes)) {
            return (float) $this->attributes['reviews_avg_rating'];
        }

        // Check if it was alias-loaded as 'avg_rating'
        if (array_key_exists('avg_rating', $this->attributes)) {
            return (float) $this->attributes['avg_rating'];
        }

        return (float) $this->reviews()->avg('rating') ?: 0;
    }

    public function getReviewsCountAttribute()
    {
        // Check if reviews_count was eager loaded via withCount('reviews')
        if (array_key_exists('reviews_count', $this->attributes)) {
            return (int) $this->attributes['reviews_count'];
        }

        return $this->reviews()->count();
    }
    /**
     * Get variations count for this product
     */
    public function getVariationsCountAttribute(): int
    {
        // Check if variations_count was eager loaded via withCount('variations')
        if (array_key_exists('variations_count', $this->attributes)) {
            return (int) $this->attributes['variations_count'];
        }

        return (int) $this->variations()->count();
    }
    /**
     * Get the gallery images with full URLs
     */
    public function getGalleryImagesUrlsAttribute()
    {
        if (!$this->gallery_images || !is_array($this->gallery_images)) {
            return [];
        }

        return array_map(function ($image) {
            return asset($image);
        }, $this->gallery_images);
    }

    /**
     * Get the feature image with full URL
     */
    public function getFeatureImageUrlAttribute()
    {
        return $this->feature_image ? asset($this->feature_image) : null;
    }
}
