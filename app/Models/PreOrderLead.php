<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PreOrderLead extends Model
{
    protected $fillable = [
        'name',
        'email',
        'phone',
        'product_interest',
        'message',
        'image_path',
        'status',
    ];

    protected $casts = [
        'status' => 'string',
    ];
}
