<?php

namespace App\Models;

use Dotenv\Parser\Value;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AttributeValue extends Model
{
    use SoftDeletes;

    protected $fillable = ['attribute_id', 'value', 'color', 'status', 'display_order'];

    protected $casts = [
        'status' => 'string',
        'display_order' => 'integer',
    ];

    /**
     * Scope: Only active attribute values
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    // Load parent attribute including soft-deleted for historical records
    public function attribute()
    {
        return $this->belongsTo(Attribute::class)->withTrashed();
    }
}
