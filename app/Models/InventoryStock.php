<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryStock extends Model
{
    protected $fillable = [
        'product_id',
        'product_variation_id',
        'location_code',
        'location_name',
        'available_quantity',
        'reserved_quantity',
        'minimum_threshold',
        'maximum_threshold',
        'reorder_point',
        'reorder_quantity',
        'average_cost_price',
        'last_cost_price',
        'status',
        'track_inventory',
        'adjustment_count',
        'last_movement_at',
        'last_updated_by',
    ];

    protected $casts = [
        'available_quantity' => 'integer',
        'reserved_quantity' => 'integer',
        'minimum_threshold' => 'integer',
        'maximum_threshold' => 'integer',
        'reorder_point' => 'integer',
        'reorder_quantity' => 'integer',
        'average_cost_price' => 'decimal:2',
        'last_cost_price' => 'decimal:2',
        'track_inventory' => 'boolean',
        'adjustment_count' => 'integer',
        'last_movement_at' => 'datetime',
    ];

    /**
     * Product relationship
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Product variation relationship
     */
    public function productVariation(): BelongsTo
    {
        return $this->belongsTo(ProductVariation::class);
    }

    /**
     * User who last updated
     */
    public function lastUpdatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_updated_by');
    }

    /**
     * Inventory transactions
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(InventoryTransaction::class)->orderBy('created_at', 'desc');
    }

    /**
     * Stock reservations
     */
    public function reservations(): HasMany
    {
        return $this->hasMany(StockReservation::class);
    }

    /**
     * Active reservations
     */
    public function activeReservations(): HasMany
    {
        return $this->reservations()->where('status', 'active');
    }

    /**
     * Get total quantity (available + reserved)
     */
    public function getTotalQuantityAttribute(): int
    {
        return $this->available_quantity + $this->reserved_quantity;
    }

    /**
     * Check if stock is low
     */
    public function getIsLowStockAttribute(): bool
    {
        return $this->available_quantity <= $this->minimum_threshold && $this->available_quantity > 0;
    }

    /**
     * Check if out of stock
     */
    public function getIsOutOfStockAttribute(): bool
    {
        return $this->available_quantity <= 0;
    }

    /**
     * Get stock status
     */
    public function getStockStatusAttribute(): string
    {
        if ($this->is_out_of_stock) {
            return 'out_of_stock';
        }

        if ($this->is_low_stock) {
            return 'low_stock';
        }

        return 'in_stock';
    }
}
