<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Attribute extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'status'];

    protected $casts = [
        'status' => 'string',
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
        return $this->hasMany(AttributeValue::class)->where('status', 'active');
    }
}
