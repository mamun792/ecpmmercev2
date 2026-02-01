<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VariationAttribute extends Model
{
    protected $fillable = ['product_variation_id', 'attribute_value_id'];

    public function productVariation()
    {
        return $this->belongsTo(ProductVariation::class);
    }

    // Load attribute value including soft-deleted for historical records
    public function value()
    {
        return $this->belongsTo(AttributeValue::class, 'attribute_value_id')->withTrashed();
    }

    public function attribute()
    {
        return $this->belongsTo(Attribute::class, 'attribute_id', 'id');
    }
}
