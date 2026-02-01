<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $order = $this->route('order');
        return $this->user()->can('update', $order);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'customer_email' => 'nullable|email|max:255',
            'customer_name' => 'nullable|string|max:255',
            'customer_phone' => 'nullable|string|max:20|regex:/^[0-9+\-\s()]+$/',
            'shipping_address' => 'nullable|string|max:500',
            'shipping_cost' => 'nullable|numeric|min:0|max:999999',
            'payment_method' => 'nullable|string|in:cod,credit_card,paypal,bank_transfer,bkash,nagad,rocket',
            'status' => 'nullable|string|in:pending,processing,completed,cancelled,incomplete,on_hold,confirmed,shipped,delivered,returned',
            'payment_status' => 'nullable|string|in:unpaid,paid,refunded,failed',
            'admin_notes' => 'nullable|string|max:2000',
            'discount' => 'nullable|numeric|min:0|max:999999',
            'discount_type' => 'nullable|string|in:fixed,percentage',
            'area' => 'nullable|string|in:inside_dhaka,outside_dhaka',
            'items' => 'nullable|array',
            'items.*.id' => 'required|integer|exists:order_items,id',
            'items.*.quantity' => 'required|integer|min:1|max:1000',
            'items.*.action' => 'required|string|in:update,remove',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'customer_phone.regex' => 'The phone number format is invalid.',
            'shipping_cost.max' => 'Shipping cost cannot exceed 999,999.',
            'discount.max' => 'Discount cannot exceed 999,999.',
            'items.*.id.exists' => 'One or more items do not exist.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Sanitize inputs
        if ($this->has('customer_name')) {
            $this->merge([
                'customer_name' => strip_tags($this->customer_name),
            ]);
        }

        if ($this->has('shipping_address')) {
            $this->merge([
                'shipping_address' => strip_tags($this->shipping_address),
            ]);
        }

        if ($this->has('admin_notes')) {
            $this->merge([
                'admin_notes' => strip_tags($this->admin_notes),
            ]);
        }
    }
}
