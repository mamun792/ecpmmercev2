<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Normalize specification: accept JSON string, empty string or null and convert to array/null
        if ($this->has('specification')) {
            $spec = $this->input('specification');

            if (is_string($spec)) {
                $decoded = json_decode($spec, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $this->merge(['specification' => $decoded]);
                } elseif ($spec === '' || strtolower($spec) === 'null') {
                    $this->merge(['specification' => null]);
                } else {
                    // Non-parseable string -> null to avoid validation error
                    $this->merge(['specification' => null]);
                }
            }
        }

        // Normalize product_tags (similar handling)
        if ($this->has('product_tags')) {
            $tags = $this->input('product_tags');
            if (is_string($tags)) {
                $decoded = json_decode($tags, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $this->merge(['product_tags' => $decoded]);
                } elseif ($tags === '' || strtolower($tags) === 'null') {
                    $this->merge(['product_tags' => null]);
                } else {
                    $this->merge(['product_tags' => null]);
                }
            }
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'product_code' => 'required|string|max:50|unique:products,product_code',
            'category_id' => 'required|exists:categories,id',
            'brand_id' => 'nullable|exists:brands,id',
            'slug' => 'nullable|string|max:255|unique:products,slug',
            'short_description' => 'nullable|string|max:1000',
            'description' => 'nullable|string',
            'status' => 'nullable|in:Published,Unpublished',
            'is_daily_product' => 'nullable|boolean',
            'is_pre_order' => 'nullable|boolean',
            'type' => 'required|in:simple,variable',
            'price' => 'required_if:type,simple|numeric|min:0',
            // previous_price check (comparison with price) handled in validator to allow variable products without price
            'previous_price' => 'nullable|numeric|min:0',
            'youtube_video' => 'nullable|url|max:500',
            'feature_image' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
            'upload_video' => 'nullable|mimetypes:video/mp4,video/ogg,video/webm|max:51200',
            'gallery_images' => 'nullable|array|max:10',
            'gallery_images.*' => 'image|mimes:jpg,jpeg,png,webp|max:2048',
            'product_tags' => 'nullable|array|max:20',
            'product_tags.*' => 'string|max:50',
            'specification' => 'nullable|array',
            'stock' => 'required_if:type,simple|integer|min:0',
            'is_free_delivery' => 'nullable|boolean',
            'remarks' => 'nullable|in:New,Popular,Trending,Hot,Special',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',

            // Validation for variations if product is variable
            'variations' => 'required_if:type,variable|array|min:1',
            'variations.*.price' => 'required_if:type,variable|numeric|min:0',
            'variations.*.stock' => 'required_if:type,variable|integer|min:0',
            'variations.*.image_path' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'variations.*.attributes' => 'required_if:type,variable|array|min:1',
            'variations.*.attributes.*.attribute_value_id' => 'required_if:type,variable|integer|exists:attribute_values,id',
            'variations.*.previous_price' => 'nullable|numeric|min:0',

            // Inventory Tracking Fields
            'track_quantity' => 'nullable|boolean',
            'sell_without_stock' => 'nullable|boolean',
            'min_quantity' => 'nullable|integer|min:0',
        ];
    }

    protected function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Validate variations: previous_price must be greater than price if provided
            $variations = $this->input('variations', []);
            foreach ($variations as $i => $v) {
                if (isset($v['previous_price']) && $v['previous_price'] !== '' && isset($v['price'])) {
                    $p = floatval($v['price']);
                    $prev = floatval($v['previous_price']);
                    if (!is_nan($p) && !is_nan($prev) && $prev <= $p) {
                        $validator->errors()->add("variations.$i.previous_price", "Previous price must be greater than price for variation #" . ($i+1));
                    }
                }
            }

            // For simple products ensure main previous_price > price if provided
            if ($this->input('type') === 'simple') {
                if ($this->filled('previous_price') && $this->filled('price')) {
                    $p = floatval($this->input('price'));
                    $prev = floatval($this->input('previous_price'));
                    if (!is_nan($p) && !is_nan($prev) && $prev <= $p) {
                        $validator->errors()->add('previous_price', 'Previous price must be greater than regular price.');
                    }
                }
            }
        });
    }

    /**
     * Get custom messages for validation errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Product name is required.',
            'name.max' => 'Product name cannot exceed 255 characters.',

            'product_code.required' => 'Product code is required.',
            'product_code.unique' => 'This product code already exists. Please use a different code.',
            'product_code.max' => 'Product code cannot exceed 50 characters.',

            'category_id.required' => 'Please select a category.',
            'category_id.exists' => 'Selected category does not exist.',

            'brand_id.exists' => 'Selected brand does not exist.',

            'slug.unique' => 'This slug is already taken. Please use a different one.',
            'slug.max' => 'Slug cannot exceed 255 characters.',

            'short_description.max' => 'Short description cannot exceed 1000 characters.',

            'type.required' => 'Product type is required.',
            'type.in' => 'Product type must be either simple or variable.',

            'price.required' => 'Product price is required.',
            'price.numeric' => 'Price must be a valid number.',
            'price.min' => 'Price cannot be negative.',

            'previous_price.numeric' => 'Previous price must be a valid number.',
            'previous_price.min' => 'Previous price cannot be negative.',
            'previous_price.gt' => 'Previous price must be greater than regular price. (Example: If regular price is 100, previous price should be 150)',

            'youtube_video.url' => 'Please enter a valid YouTube URL.',
            'youtube_video.max' => 'YouTube URL cannot exceed 500 characters.',

            'feature_image.required' => 'Feature image is required.',
            'feature_image.image' => 'Feature image must be an image file.',
            'feature_image.mimes' => 'Feature image must be a file of type: jpg, jpeg, png, webp.',
            'feature_image.max' => 'Feature image size cannot exceed 2MB.',

            'upload_video.mimetypes' => 'Video must be of type: mp4, ogg, webm.',
            'upload_video.max' => 'Video size cannot exceed 50MB.',

            'gallery_images.max' => 'You can upload maximum 10 gallery images.',
            'gallery_images.*.image' => 'All gallery files must be images.',
            'gallery_images.*.mimes' => 'Gallery images must be of type: jpg, jpeg, png, webp.',
            'gallery_images.*.max' => 'Each gallery image cannot exceed 2MB.',

            'product_tags.max' => 'You can add maximum 20 tags.',
            'product_tags.*.string' => 'Each tag must be a valid text.',
            'product_tags.*.max' => 'Each tag cannot exceed 50 characters.',

            'stock.required_if' => 'Stock quantity is required for simple products.',
            'stock.integer' => 'Stock must be a valid number.',
            'stock.min' => 'Stock cannot be negative.',

            'status.in' => 'Status must be either Published or Unpublished.',

            'remarks.in' => 'Remarks must be one of: New, Popular, Trending, Hot, Special.',

            'meta_title.max' => 'Meta title cannot exceed 255 characters.',
            'meta_description.max' => 'Meta description cannot exceed 500 characters.',

            // Variation messages
            'variations.required_if' => 'At least one variation is required for variable products.',
            'variations.array' => 'Variations must be an array.',
            'variations.min' => 'Variable products must have at least one variation.',

            'variations.*.price.required_with' => 'Price is required for all variations.',
            'variations.*.price.numeric' => 'Variation price must be a valid number.',
            'variations.*.price.min' => 'Variation price cannot be negative.',

            'variations.*.stock.required_with' => 'Stock is required for all variations.',
            'variations.*.stock.integer' => 'Variation stock must be a valid number.',
            'variations.*.stock.min' => 'Variation stock cannot be negative.',

            'variations.*.image_path.image' => 'Variation image must be an image file.',
            'variations.*.image_path.mimes' => 'Variation image must be of type: jpg, jpeg, png, webp.',
            'variations.*.image_path.max' => 'Variation image cannot exceed 2MB.',

            'variations.*.attributes.required_with' => 'Attributes are required for all variations.',
            'variations.*.attributes.array' => 'Variation attributes must be an array.',
            'variations.*.attributes.min' => 'Each variation must have at least one attribute.',

            'variations.*.attributes.*.attribute_value_id.required' => 'Attribute value is required.',
            'variations.*.attributes.*.attribute_value_id.exists' => 'Selected attribute value does not exist.',
        ];
    }

    /**
     * Get custom attribute names for validation errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'product name',
            'product_code' => 'product code',
            'category_id' => 'category',
            'brand_id' => 'brand',
            'short_description' => 'short description',
            'type' => 'product type',
            'previous_price' => 'previous price',
            'youtube_video' => 'YouTube video URL',
            'feature_image' => 'feature image',
            'upload_video' => 'video',
            'gallery_images' => 'gallery images',
            'product_tags' => 'product tags',
            'is_free_delivery' => 'free delivery',
            'meta_title' => 'meta title',
            'meta_description' => 'meta description',
            'variations' => 'variations',
        ];
    }
}
