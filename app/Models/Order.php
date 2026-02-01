<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'order_number',
        'user_id',
        'session_id',
        'cart_id',
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
        'delivery_status'
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
}
