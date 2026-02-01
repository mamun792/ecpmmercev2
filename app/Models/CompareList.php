<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompareList extends Model
{
    protected $fillable = ['user_id', 'session_id', 'product_ids'];

    protected $casts = [
        'product_ids' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function products()
    {
        return Product::with(['category.parentRecursive', 'variations.attributes.value.attribute'])
            ->whereIn('id', $this->product_ids ?? [])
            ->get();
    }

    public function addProduct($productId)
    {
        $ids = $this->product_ids ?? [];
        if (!in_array($productId, $ids) && count($ids) < 4) {
            $ids[] = $productId;
            $this->product_ids = array_values(array_unique($ids));
            $this->save();
        }
    }

    public function removeProduct($productId)
    {
        $ids = $this->product_ids ?? [];
        $this->product_ids = array_values(array_diff($ids, [$productId]));
        $this->save();
    }
}
