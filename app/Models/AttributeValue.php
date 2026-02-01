<?php

namespace App\Models;

use Dotenv\Parser\Value;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AttributeValue extends Model
{
    use SoftDeletes;

    protected $fillable = ['attribute_id', 'value', 'color', 'status'];

    protected $casts = [
        'status' => 'string',
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
