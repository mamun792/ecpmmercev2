<?php

namespace App\Http\Requests\Admin\ProductGroup;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('product_group')->id;
        return [
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:product_groups,slug,' . $id,
            'status' => 'required|boolean',
            'order_number' => 'nullable|integer',
            'banner' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'product_ids' => 'required|array|min:1',
            'product_ids.*' => 'required|exists:products,id',
        ];
    }
}
