<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionHistory extends Model
{
    protected $fillable = [
        'transaction_id',
        'invoice_number',
        'order_number',
        'order_id',
        'user_id',
        'customer_name',
        'customer_email',
        'customer_phone',
        'amount',
        'currency',
        'payment_method',
        'payment_gateway',
        'status',
        'gateway_response',
        'notes',
        'transaction_date',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'gateway_response' => 'array',
        'transaction_date' => 'datetime',
    ];

    /**
     * Get the order associated with this transaction
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the user associated with this transaction
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for filtering by status
     */
    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for filtering by payment gateway
     */
    public function scopePaymentGateway($query, $gateway)
    {
        return $query->where('payment_gateway', $gateway);
    }

    /**
     * Scope for filtering by date range
     */
    public function scopeDateRange($query, $from, $to)
    {
        return $query->whereBetween('transaction_date', [$from, $to]);
    }

    /**
     * Scope for filtering by amount range
     */
    public function scopeAmountRange($query, $min, $max)
    {
        return $query->whereBetween('amount', [$min, $max]);
    }
}
