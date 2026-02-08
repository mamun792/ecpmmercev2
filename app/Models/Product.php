<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Laravel\Scout\Searchable;

class Product extends Model
{
    use HasFactory, SoftDeletes, Searchable;

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

    protected $appends = [
        'feature_image_url',
        'avg_rating',
        'reviews_count',
        'stock',
        'variations_count',
        'sales_count_7_days',
        'sales_count_30_days',
        'total_revenue',
        'profit_margin',
        'performance_score'
    ];

    /**
     * Get the indexable data array for the model.
     * Defines what fields will be searchable with TNTSearch
     */
    public function toSearchableArray()
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'product_code' => $this->product_code,
            'barcode' => $this->barcode,
            'short_description' => $this->short_description,
            'search_keywords' => $this->search_keywords,
            'category_name' => $this->category?->name,
            'brand_name' => $this->brand?->brand_name,
        ];
    }

    /**
     * Get the name of the index associated with the model.
     */
    public function searchableAs()
    {
        return 'products_index';
    }

    /**
     * Determine if the model should be searchable.
     * Only index Published products
     */
    public function shouldBeSearchable()
    {
        return $this->status === 'Published';
    }

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

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
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
     * Get total sales count for last 7 days
     */
    public function getSalesCount7DaysAttribute(): int
    {
        return $this->orderItems()
            ->whereHas('order', function ($q) {
                $q->where('created_at', '>=', now()->subDays(7))
                  ->whereNotIn('status', ['Cancelled', 'Refunded']);
            })
            ->sum('quantity') ?? 0;
    }

    /**
     * Get total sales count for last 30 days
     */
    public function getSalesCount30DaysAttribute(): int
    {
        return $this->orderItems()
            ->whereHas('order', function ($q) {
                $q->where('created_at', '>=', now()->subDays(30))
                  ->whereNotIn('status', ['Cancelled', 'Refunded']);
            })
            ->sum('quantity') ?? 0;
    }

    /**
     * Get total revenue generated from this product (all time)
     */
    public function getTotalRevenueAttribute(): float
    {
        return $this->orderItems()
            ->whereHas('order', function ($q) {
                $q->whereIn('status', ['Delivered', 'Completed', 'Processing']);
            })
            ->sum(\DB::raw('unit_price * quantity')) ?? 0;
    }

    /**
     * Get profit margin percentage (based on cost_price)
     */
    public function getProfitMarginAttribute(): float
    {
        if (!$this->cost_price || $this->cost_price <= 0 || !$this->price || $this->price <= 0) {
            return 0;
        }

        $profit = $this->price - $this->cost_price;
        return round(($profit / $this->price) * 100, 1);
    }

    /**
     * Get performance score (fast/slow moving indicator)
     */
    public function getPerformanceScoreAttribute(): string
    {
        $sales7Days = $this->sales_count_7_days;
        $sales30Days = $this->sales_count_30_days;

        // Fast moving: 10+ sales in last 7 days OR 30+ in last 30 days
        if ($sales7Days >= 10 || $sales30Days >= 30) {
            return 'fast';
        }

        // Moderate: 5+ sales in last 7 days OR 15+ in last 30 days
        if ($sales7Days >= 5 || $sales30Days >= 15) {
            return 'moderate';
        }

        // Slow moving: < 5 sales in 7 days AND < 15 in 30 days
        return 'slow';
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
