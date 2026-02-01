<?php

namespace App\Http\Requests\Courier;

use Illuminate\Foundation\Http\FormRequest;

class CreateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'invoice' => 'required|string|max:100',
            'recipient_name' => 'required|string|max:100',
            'recipient_phone' => 'required|string|size:11',
            'recipient_address' => 'required|string|max:250',
            'cod_amount' => 'required|numeric|min:0',
            'alternative_phone' => 'nullable|string|size:11',
            'recipient_email' => 'nullable|email',
            'note' => 'nullable|string',
            'item_description' => 'nullable|string',
            'total_lot' => 'nullable|integer|min:1',
            'delivery_type' => 'nullable|integer|in:0,1',
        ];
    }
}
