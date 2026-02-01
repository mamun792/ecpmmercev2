<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductPurchaseCost extends Model
{
    protected $fillable = [
        'cost_name',
        'quantity',
        'unit_price',
        'total_price',
        'purchase_date',
        'supplier_name',
        'month',
        'note',
    ];
}
