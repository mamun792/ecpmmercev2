<?php

namespace App\Http\Requests\Courier;

use Illuminate\Foundation\Http\FormRequest;

class CreateBulkOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'orders' => 'required|array|max:500',
            'orders.*.invoice' => 'required|string|max:100',
            'orders.*.recipient_name' => 'required|string|max:100',
            'orders.*.recipient_phone' => 'required|string|size:11',
            'orders.*.recipient_address' => 'required|string|max:250',
            'orders.*.cod_amount' => 'required|numeric|min:0',
            'orders.*.alternative_phone' => 'nullable|string|size:11',
            'orders.*.recipient_email' => 'nullable|email',
            'orders.*.note' => 'nullable|string',
            'orders.*.item_description' => 'nullable|string',
            'orders.*.total_lot' => 'nullable|integer|min:1',
            'orders.*.delivery_type' => 'nullable|integer|in:0,1',
        ];
    }
}
