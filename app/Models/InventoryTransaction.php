<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryTransaction extends Model
{
    public $timestamps = ['created_at']; // Only created_at, no updated_at

    protected $fillable = [
        'inventory_stock_id',
        'product_id',
        'product_variation_id',
        'transaction_type',
        'quantity_change',
        'quantity_before',
        'quantity_after',
        'reference_type',
        'reference_id',
        'reference_number',
        'unit_cost',
        'total_cost',
        'location_code',
        'from_location',
        'to_location',
        'reason',
        'notes',
        'metadata',
        'created_by',
        'created_by_type',
        'source',
    ];

    protected $casts = [
        'quantity_change' => 'integer',
        'quantity_before' => 'integer',
        'quantity_after' => 'integer',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'metadata' => 'array',
    ];

    /**
     * Inventory stock relationship
     */
    public function inventoryStock(): BelongsTo
    {
        return $this->belongsTo(InventoryStock::class);
    }

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
     * User who created this transaction
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get formatted transaction type
     */
    public function getFormattedTypeAttribute(): string
    {
        return match($this->transaction_type) {
            'purchase' => 'Stock In',
            'sale' => 'Stock Out',
            'adjustment' => 'Manual Adjustment',
            'transfer' => 'Location Transfer',
            'return' => 'Customer Return',
            'damage' => 'Damaged/Expired',
            'reservation' => 'Reserved for Cart/Order',
            'release' => 'Reservation Released',
            'initial' => 'Initial Stock Setup',
            default => ucfirst(str_replace('_', ' ', $this->transaction_type))
        };
    }

    /**
     * Get quantity change with sign
     */
    public function getQuantityChangeFormattedAttribute(): string
    {
        return ($this->quantity_change >= 0 ? '+' : '') . $this->quantity_change;
    }

    /**
     * Check if this is a stock increase
     */
    public function getIsIncreaseAttribute(): bool
    {
        return $this->quantity_change > 0;
    }

    /**
     * Check if this is a stock decrease
     */
    public function getIsDecreaseAttribute(): bool
    {
        return $this->quantity_change < 0;
    }

    /**
     * Get formatted reason
     */
    public function getFormattedReasonAttribute(): string
    {
        return $this->reason ?? 'No reason provided';
    }
}
