<?php

namespace App\Http\Requests\Cupon;

use Illuminate\Foundation\Http\FormRequest;

use Illuminate\Validation\Rule;
use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\Validation\Validator;

use Illuminate\Validation\ValidationException;

class StoreProductCouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_ids' => [
                'required',
                'array',
                'min:1',
            ],
            'product_ids.*' => [
                'integer',
                'exists:products,id',
                'distinct',    // Ensure no duplicate product IDs
            ]



        ];
    }


    protected function failedValidation(Validator $validator): JsonResponse
    {
        $errors = (new ValidationException($validator))->errors();

        return response()->json([
            'status' => 'error',
            'message' => 'Validation failed',
            'errors' => $errors,
        ], 422);
    }
}
