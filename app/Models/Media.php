<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Media extends Model
{
    protected $fillable = ['logo', 'footer_logo', 'favicon', 'loder_logo', 'footer_payment_logo'];
}
