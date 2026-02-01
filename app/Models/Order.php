<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'order_number',
        'user_id',
        'session_id',
        'cart_id',
        'order_source',
        'status',
        'customer_email',
        'customer_phone',
        'customer_name',
        'shipping_address',
        'shipping_district',
        'area',
        'shipping_cost',
        'pos_discount',
        'discount_type',
        'subtotal',
        'discount_total',
        'total',
        'tax_amount',
        'payment_status',
        'payment_method',
        'transaction_id',
        'shipping_method',
        'tracking_number',
        'customer_notes',
        'admin_notes',
        'courier_name',
        'city_name',
        'zone_name',
        'area_name',
        'city_id',
        'zone_id',
        'area_id',
        'is_courier',
        'consignment_id',
        'delivery_status',
        // Audit
        'created_by',
        'updated_by',
        'deleted_by',
        'deletion_reason',
    ];

    protected $casts = [
        'shipping_cost' => 'decimal:2',
        'date' => 'datetime',
        'total' => 'decimal:2',
        'pos_discount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'is_courier' => 'boolean'
    ];

    /**
     * Get all items associated with this order
     *
     * @return HasMany
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get status history for this order
     */
    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->orderBy('created_at', 'desc');
    }

    /**
     * Get edit logs for this order
     */
    public function editLogs(): HasMany
    {
        return $this->hasMany(OrderEditLog::class)->orderBy('created_at', 'desc');
    }

    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopePaymentStatus($query, $paymentStatus)
    {
        return $query->where('payment_status', $paymentStatus);
    }

    //  const CACHE_VERSION_KEY = 'orders_cache_version';
    const CACHE_TTL = 1800; // 30 minutes
    public static function getCacheVersion()
    {
        return Cache::get('orders_cache_version', 1);
    }

    public static function incrementCacheVersion()
    {
        $newVersion = static::getCacheVersion() + 1;
        Cache::forever('orders_cache_version', $newVersion);
        return $newVersion;
    }

    public function customer()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }

    /**
     * Get user who created this order
     */
    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get user who last updated this order
     */
    public function updatedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get user who deleted this order
     */
    public function deletedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    /**
     * Record status change in history
     */
    public function recordStatusChange(string $newStatus, ?string $notes = null, ?array $metadata = null): void
    {
        $this->statusHistories()->create([
            'previous_status' => $this->getOriginal('status'),
            'new_status' => $newStatus,
            'changed_by_type' => 'user',
            'changed_by_id' => auth()->id(),
            'notes' => $notes,
            'metadata' => $metadata,
            'ip_address' => request()->ip(),
        ]);
    }

    /**
     * Log field edit
     */
    public function logEdit(string $fieldName, $oldValue, $newValue, ?string $reason = null): void
    {
        $this->editLogs()->create([
            'field_name' => $fieldName,
            'old_value' => is_array($oldValue) ? json_encode($oldValue) : $oldValue,
            'new_value' => is_array($newValue) ? json_encode($newValue) : $newValue,
            'edited_by' => auth()->id(),
            'edit_reason' => $reason,
            'ip_address' => request()->ip(),
        ]);
    }

    /**
     * Smart cache invalidation - invalidate only order-related cache
     */
    public static function invalidateCache(): void
    {
        // Increment cache version instead of flushing all cache
        static::incrementCacheVersion();

        // Clear specific order-related cache keys
        Cache::forget('order_stats');
        Cache::forget('order_status_counts');
    }
}
