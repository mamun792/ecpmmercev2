<?php

namespace App\Http\Requests\Cupon;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCouponRequest extends FormRequest
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
            'code' => ['sometimes', 'string', 'max:50', Rule::unique('coupons')->ignore($this->coupon)],
            'name' => ['sometimes', 'string', 'max:255'],
            'discount_value' => ['sometimes', 'numeric', 'min:0'],
            'discount_type' => ['sometimes', Rule::in(['percentage', 'fixed'])],
            'max_uses' => ['nullable', 'integer', 'min:1'],
            'start_date' => ['sometimes', 'date'],
            'expiry_date' => ['sometimes', 'date', 'after:start_date'],
            'is_active' => ['sometimes', 'boolean'],

        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'is_active' => $this->is_active ? true : false,
        ]);
    }
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        throw new \Illuminate\Http\Exceptions\HttpResponseException(
            response()->json([
                'status' => 'error',
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422)
        );
    }
}
