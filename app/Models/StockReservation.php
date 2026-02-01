<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockReservation extends Model
{
    protected $fillable = [
        'inventory_stock_id',
        'product_id',
        'product_variation_id',
        'reserved_for_type',
        'reserved_for_id',
        'quantity',
        'reserved_at',
        'expires_at',
        'fulfilled_at',
        'cancelled_at',
        'status',
        'fulfilled_quantity',
        'reason',
        'context',
        'created_by',
        'source',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'fulfilled_quantity' => 'integer',
        'reserved_at' => 'datetime',
        'expires_at' => 'datetime',
        'fulfilled_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'context' => 'array',
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
     * User who created this reservation
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get remaining quantity to be fulfilled
     */
    public function getRemainingQuantityAttribute(): int
    {
        return $this->quantity - $this->fulfilled_quantity;
    }

    /**
     * Check if reservation is active
     */
    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'active' &&
               (!$this->expires_at || $this->expires_at->isFuture());
    }

    /**
     * Check if reservation is expired
     */
    public function getIsExpiredAttribute(): bool
    {
        return $this->status === 'active' &&
               $this->expires_at &&
               $this->expires_at->isPast();
    }

    /**
     * Check if reservation is fully fulfilled
     */
    public function getIsFullyFulfilledAttribute(): bool
    {
        return $this->status === 'fulfilled' ||
               $this->fulfilled_quantity >= $this->quantity;
    }

    /**
     * Get formatted status
     */
    public function getFormattedStatusAttribute(): string
    {
        return match($this->status) {
            'active' => 'Active',
            'fulfilled' => 'Fulfilled',
            'expired' => 'Expired',
            'cancelled' => 'Cancelled',
            'partial' => 'Partially Fulfilled',
            default => ucfirst($this->status)
        };
    }

    /**
     * Get time until expiration
     */
    public function getExpiresInAttribute(): ?string
    {
        if (!$this->expires_at || $this->expires_at->isPast()) {
            return null;
        }

        return $this->expires_at->diffForHumans();
    }

    /**
     * Scope for active reservations
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
                    ->where(function ($q) {
                        $q->whereNull('expires_at')
                          ->orWhere('expires_at', '>', now());
                    });
    }

    /**
     * Scope for expired reservations
     */
    public function scopeExpired($query)
    {
        return $query->where('status', 'active')
                    ->where('expires_at', '<=', now());
    }

    /**
     * Scope for specific reserved entity
     */
    public function scopeForEntity($query, string $type, int $id)
    {
        return $query->where('reserved_for_type', $type)
                    ->where('reserved_for_id', $id);
    }
}
