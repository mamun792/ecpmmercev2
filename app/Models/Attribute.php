<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Attribute extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'status', 'is_global', 'display_order', 'settings'];

    protected $casts = [
        'status' => 'string',
        'is_global' => 'boolean',
        'display_order' => 'integer',
        'settings' => 'array',
    ];

    /**
     * Scope: Only active attributes
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function values()
    {
        return $this->hasMany(AttributeValue::class);
    }

    /**
     * Get only active values
     */
    public function activeValues()
    {
        return $this->hasMany(AttributeValue::class)
            ->where('status', 'active')
            ->orderBy('display_order');
    }

    /**
     * Scope: Only global library attributes
     */
    public function scopeGlobal($query)
    {
        return $query->where('is_global', true)->orderBy('display_order');
    }

    /**
     * Scope: Non-global (product-specific) attributes
     */
    public function scopeProductSpecific($query)
    {
        return $query->where('is_global', false);
    }
}
