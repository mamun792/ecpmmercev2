<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderEditLog extends Model
{
    use HasFactory;

    protected $table = 'order_edit_logs';

    protected $fillable = [
        'order_id',
        'field_name',
        'old_value',
        'new_value',
        'edited_by',
        'edit_reason',
        'ip_address',
    ];

    /**
     * Get the order this log belongs to
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the user who made the edit
     */
    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'edited_by');
    }

    /**
     * Get human-readable field name
     */
    public function getFieldLabelAttribute(): string
    {
        $labels = [
            'customer_name' => 'Customer Name',
            'customer_phone' => 'Customer Phone',
            'customer_email' => 'Customer Email',
            'shipping_address' => 'Shipping Address',
            'payment_status' => 'Payment Status',
            'payment_method' => 'Payment Method',
            'shipping_cost' => 'Shipping Cost',
            'pos_discount' => 'POS Discount',
            'admin_notes' => 'Admin Notes',
            'status' => 'Order Status',
        ];

        return $labels[$this->field_name] ?? ucwords(str_replace('_', ' ', $this->field_name));
    }
}
