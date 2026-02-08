<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    protected $table = 'notifications';

    protected $fillable = [
        'user_id',
        'type',
        'data',
        'order_id',
        'is_read',
    ];

    protected $casts = [
        'data' => 'array',
        'is_read' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user that owns the notification
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the related order if exists
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the URL for this notification
     */
    public function getUrlAttribute(): ?string
    {
        if ($this->order_id) {
            return route('admin.orders.show', $this->order_id);
        }

        // Add more types as needed
        if (isset($this->data['product_id'])) {
            return route('admin.products.edit', $this->data['product_id']);
        }

        return null;
    }

    /**
     * Scope for unread notifications
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope for read notifications
     */
    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }
}
