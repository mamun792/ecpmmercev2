<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LandingPageProduct extends Model
{
    protected $guarded = [];

        /**
     * Get the landing page that owns the product.
     */
    public function landingPage()
    {
        return $this->belongsTo(LandingPage::class);
    }

    /**
     * Get the product.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
