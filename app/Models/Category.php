<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use SoftDeletes;

    protected $fillable = ["name","slug", "status", "image","icon", "order","parent_id"];


    protected static function boot()
    {
        parent::boot();
        static::creating(function ($category) {
            $category->slug = Str::slug($category->name);
        });
    }

    // Relationship: Parent Category - Load soft-deleted parents for historical data
    public function parentRecursive()
    {
        return $this->belongsTo(Category::class, 'parent_id')->withTrashed()->with('parentRecursive');
    }

    // Relationship: Child Categories (Recursive)
    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id')->with('children');
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
