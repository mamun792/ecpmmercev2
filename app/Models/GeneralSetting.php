<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GeneralSetting extends Model
{
    protected $fillable = [
        'app_name',
        'home_page_title',
        'product_page_title',
        'phone_number',
        'whatsapp_number',
        'address',
        'store_phone_number',
        'store_email',
        'facebook_url',
        'tiktok_url',
        'youtube_url',
        'instagram_url',
        'x_url',
        'shipping_charge_inside_dhaka',
        'shipping_charge_outside_dhaka',
        'attention_notice',
        'pre_order_notice',
        'top_notice',
        'facebook_iframe',
        'facebook_page_id',
        'footer_text',
        'primary_color',
    ];
}
