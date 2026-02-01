<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LandingPage extends Model
{
    protected $guarded = [];



        /**
     * Get the settings for the landing page.
     */
    public function settings()
    {
        return $this->hasOne(LandingPageSetting::class);
    }

    /**
     * Get the products associated with the landing page.
     */
    public function products()
    {
        return $this->hasMany(LandingPageProduct::class);
    }

    /**
     * Get the actual product models through the pivot.
     */
    public function linkedProducts()
    {
        return $this->belongsToMany(
            Product::class,
            'landing_page_products',
            'landing_page_id',
            'product_id'
        );
    }

    /**
     * Accessor for product features list (decode JSON)
     */
    public function getProductFeaturesListAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * Accessor for product gallery (decode JSON)
     */
    public function getProductGalleryAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * Accessor for FAQs (decode JSON)
     */
    public function getFaqsAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * Accessor for review images (decode JSON)
     */
    public function getReviewImagesAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }


}
