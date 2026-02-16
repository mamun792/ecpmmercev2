<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductVariation extends Model
{
    use SoftDeletes;

    /**
     * Mass assignable attributes
     * Note: stock/sold_stock removed - now managed via inventory_stocks table
     */
    protected $fillable = [
        'product_id',
        'price',
        'previous_price',
        'image_path',
        // Dynamic Pricing (Big Tech Style)
        'price_type',
        'price_value',
        // Status & Control
        'status',
        'is_default',
        'sort_order',
        // Identification
        'variation_code',
        'barcode',
        'sku',
        // Inventory Control
        'track_inventory',
        'stock_status',
        'allow_backorders',
        // Physical Properties
        'weight',
        'dimensions',
        'shipping_weight',
        // Cost Management
        'cost_price',
        'compare_at_price',
        // Availability
        'available_from',
        'available_until',
        'requires_shipping',
        // SEO
        'meta_title',
        'meta_description',
    ];

    /**
     * Attributes to append to JSON serialization
     */
    protected $appends = ['stock'];

    /**
     * Attribute casting
     */
    protected $casts = [
        'price' => 'decimal:2',
        'previous_price' => 'decimal:2',
        'price_value' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'compare_at_price' => 'decimal:2',
        'weight' => 'decimal:2',
        'shipping_weight' => 'decimal:2',
        'dimensions' => 'array',
        'is_default' => 'boolean',
        'track_inventory' => 'boolean',
        'allow_backorders' => 'boolean',
        'requires_shipping' => 'boolean',
        'sort_order' => 'integer',
        'available_from' => 'datetime',
        'available_until' => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function attributes()
    {
        return $this->hasMany(VariationAttribute::class);
    }

    public function attributeValues()
    {
        return $this->hasManyThrough(AttributeValue::class, VariationAttribute::class, 'product_variation_id', 'id', 'id', 'attribute_value_id')
            ->withTrashed(); // Load soft-deleted attribute values for historical orders
    }
    public function variationAttributes()
    {
        return $this->hasMany(VariationAttribute::class, 'product_variation_id');
    }

    /**
     * Get inventory stock for this variation
     */
    public function inventoryStock(): HasOne
    {
        return $this->hasOne(InventoryStock::class, 'product_variation_id');
    }

    /**
     * Get current available stock from inventory system
     */
    public function getStockAttribute(): int
    {
        return $this->inventoryStock?->available_quantity ?? 0;
    }

    /**
     * Get the variation image with full URL
     */
    public function getImageUrlAttribute()
    {
        return $this->image_path ? asset($this->image_path) : null;
    }

    /**
     * Big Tech Style: Calculate final price based on dynamic pricing
     *
     * @return float
     */
    public function getFinalPriceAttribute(): float
    {
        $basePrice = $this->product->price ?? 0;

        return match($this->price_type ?? 'adjustment') {
            'adjustment' => $basePrice + ($this->price_value ?? 0),
            'percentage' => $basePrice * (1 + (($this->price_value ?? 0) / 100)),
            'override' => $this->price_value ?? $this->price,
            default => $this->price
        };
    }

    /**
     * Get price calculation formula for display
     *
     * @return string
     */
    public function getPriceFormulaAttribute(): string
    {
        $basePrice = $this->product->price ?? 0;

        return match($this->price_type ?? 'adjustment') {
            'adjustment' => "৳" . number_format($basePrice, 2) . " + ৳" . number_format($this->price_value ?? 0, 2),
            'percentage' => "৳" . number_format($basePrice, 2) . " × " . (1 + (($this->price_value ?? 0) / 100)),
            'override' => "Fixed Price",
            default => "Base Price"
        };
    }
}
