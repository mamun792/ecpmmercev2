<?php

namespace App\Http\Requests\GeneralSetting;

use Illuminate\Foundation\Http\FormRequest;

class GeneralSettingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'app_name' => 'nullable|string|max:255',
            'home_page_title' => 'nullable|string|max:255',
            'product_page_title' => 'nullable|string|max:255',
            'phone_number' => 'nullable|numeric',
            'whatsapp_number' => 'nullable|numeric',
            'address' => 'nullable|string|max:500',
            'store_phone_number' => 'nullable|numeric',
            'store_email' => 'nullable|email|max:255',
            'facebook_url' => 'nullable|url|max:255',
            'tiktok_url' => 'nullable|url|max:255',
            'youtube_url' => 'nullable|url|max:255',
            'instagram_url' => 'nullable|url|max:255',
            'x_url' => 'nullable|url|max:255',
            'shipping_charge_inside_dhaka' => 'nullable|numeric',
            'shipping_charge_outside_dhaka' => 'nullable|numeric',
            'attention_notice' => 'nullable|string',
            'top_notice' => 'nullable|string',
            'pre_order_notice' => 'nullable|string',
            'facebook_iframe' => 'nullable|string',
            'facebook_page_id' => 'nullable|string',
            'footer_text' => 'nullable|string',
            'primary_color' => 'nullable|string|max:7',
        ];
    }
}
