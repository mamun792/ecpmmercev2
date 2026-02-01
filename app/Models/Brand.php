<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;

class Brand extends Model
{
    protected $fillable = ['brand_name', 'brand_slug', "status", 'brand_image'];

    /**
     * Get all products for this brand
     */
    public function products()
    {
        return $this->hasMany(Product::class, 'brand_id');
    }
}
