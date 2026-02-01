<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LandingPageSetting extends Model
{
    protected $guarded = [];



    /**
     * Get the landing page that owns the settings.
     */
    public function landingPage()
    {
        return $this->belongsTo(LandingPage::class);
    }
    



}
