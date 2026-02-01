<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class OrderItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'order_id',
        'product_id',
        'product_variation_id',
        // Product Snapshot (using actual column names from DB)
        'product_name',
        'product_code',
        'product_sku',
        'product_image_url',
        'product_short_description',
        // Variation Snapshot
        'variation_name',
        'variation_sku',
        'variation_image_url',
        'variation_attributes',
        // Price Snapshots
        'base_product_price',
        'variation_price_addition',
        'original_price',
        'cost_price',
        // Tax & Fees
        'tax_rate',
        'tax_amount',
        'handling_fee',
        // Campaign Info
        'campaign_name',
        'campaign_code',
        // Product State
        'product_status',
        'product_type',
        'was_pre_order',
        'stock_at_order_time',
        'inventory_location',
        // Full JSON Snapshots
        'product_data_snapshot',
        'variation_data_snapshot',
        'snapshot_created_at',
        'snapshot_version',
        // Order Item Values
        'quantity',
        'unit_price',
        'subtotal',
        'coupon_code',
        'discount_type',
        'discount_total',
        'discount_amount',
        'final_price',
        'options',
        'is_pre_order',
        // Audit
        'deleted_by',
        'deletion_reason',
    ];


    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'options' => 'array',
        'variation_attributes' => 'array',
        'product_data_snapshot' => 'array',
        'variation_data_snapshot' => 'array',
        'is_pre_order' => 'boolean',
        'was_pre_order' => 'boolean',
        'unit_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'original_price' => 'decimal:2',
        'base_product_price' => 'decimal:2',
        'variation_price_addition' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'final_price' => 'decimal:2',
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
     * Uses withTrashed to preserve history even if product is deleted
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

    /**
     * Get user who deleted this item
     */
    public function deletedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    /**
     * Get the display name (snapshot or from relation)
     */
    public function getDisplayNameAttribute(): string
    {
        // Use snapshot if available, otherwise fallback to relation
        if ($this->product_name) {
            return $this->product_name;
        }

        return $this->product?->name ?? 'Product Deleted';
    }

    /**
     * Get the display SKU
     */
    public function getDisplaySkuAttribute(): ?string
    {
        if ($this->variation_sku) {
            return $this->variation_sku;
        }

        if ($this->product_sku) {
            return $this->product_sku;
        }

        return $this->productVariation?->sku ?? $this->product?->sku;
    }

    /**
     * Get the display image (use actual column names)
     */
    public function getDisplayImageAttribute(): ?string
    {
        // Priority: variation_image_url > product_image_url > relation images
        if ($this->variation_image_url) {
            return $this->variation_image_url;
        }

        if ($this->product_image_url) {
            return $this->product_image_url;
        }

        return $this->productVariation?->image_path ?? $this->product?->feature_image;
    }

    /**
     * Backward compatibility accessor for frontend Vue components
     * Maps to product_image_url column
     */
    public function getProductImageAttribute(): ?string
    {
        return $this->product_image_url
            ?? $this->product?->feature_image;
    }

    /**
     * Backward compatibility accessor for variation_image
     */
    public function getVariationImageAttribute(): ?string
    {
        return $this->variation_image_url
            ?? $this->productVariation?->image_path;
    }
}
