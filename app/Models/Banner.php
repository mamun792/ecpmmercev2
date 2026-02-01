<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    protected $fillable = [
        'top_banner1',
        'top_banner1_url',
        'top_banner2',
        'top_banner2_url',
        'top_banner3',
        'top_banner3_url',
        'new_arrival_b_banner1',
        'new_arrival_b_banner1_url',
        'new_arrival_b_banner2',
        'new_arrival_b_banner2_url',
        'random_banners',
    ];
}
