<?php

namespace App\DTOs;

class ProductUpdateDTO
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $slug = null,
        public readonly ?string $product_code = null,
        public readonly ?int $category_id = null,
        public readonly ?int $brand_id = null,
        public readonly ?string $short_description = null,
        public readonly ?string $description = null,
        public readonly ?string $status = null,
        public readonly ?string $type = null,
        public readonly ?float $price = null,
        public readonly ?float $previous_price = null,
        public readonly ?string $feature_image = null,
        public readonly ?string $youtube_video = null,
        public readonly ?string $upload_video = null,
        public readonly ?array $gallery_images = null,
        public readonly ?array $description_images = null,
        public readonly ?array $product_tags = null,
        public readonly ?array $specification = null,
        public readonly ?bool $is_daily_product = null,
        public readonly ?bool $is_free_delivery = null,
        public readonly ?bool $is_pre_order = null,
        public readonly ?string $remarks = null,
        public readonly ?string $meta_title = null,
        public readonly ?string $meta_description = null,
        public readonly ?bool $track_inventory = null,
        public readonly ?bool $allow_backorders = null,
        public readonly ?string $barcode = null,
        public readonly ?string $isbn = null,
        public readonly ?float $weight = null,
        public readonly ?array $dimensions = null,
        public readonly ?string $meta_keywords = null,
        public readonly ?string $search_keywords = null,
        public readonly ?int $sort_order = null,
        public readonly ?array $variations = null,
        public readonly ?array $options = null,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            name: $data['name'] ?? null,
            slug: $data['slug'] ?? null,
            product_code: $data['product_code'] ?? null,
            category_id: isset($data['category_id']) ? (int) $data['category_id'] : null,
            brand_id: isset($data['brand_id']) ? (int) $data['brand_id'] : null,
            short_description: $data['short_description'] ?? null,
            description: $data['description'] ?? null,
            status: $data['status'] ?? null,
            type: $data['type'] ?? null,
            price: isset($data['price']) ? (float) $data['price'] : null,
            previous_price: isset($data['previous_price']) ? (float) $data['previous_price'] : null,
            feature_image: $data['feature_image'] ?? null,
            youtube_video: $data['youtube_video'] ?? null,
            upload_video: $data['upload_video'] ?? null,
            gallery_images: $data['gallery_images'] ?? null,
            description_images: $data['description_images'] ?? null,
            product_tags: $data['product_tags'] ?? null,
            specification: $data['specification'] ?? null,
            is_daily_product: isset($data['is_daily_product']) ? (bool) $data['is_daily_product'] : null,
            is_free_delivery: isset($data['is_free_delivery']) ? (bool) $data['is_free_delivery'] : null,
            is_pre_order: isset($data['is_pre_order']) ? (bool) $data['is_pre_order'] : null,
            remarks: $data['remarks'] ?? null,
            meta_title: $data['meta_title'] ?? null,
            meta_description: $data['meta_description'] ?? null,
            track_inventory: isset($data['track_inventory']) ? (bool) $data['track_inventory'] : null,
            allow_backorders: isset($data['allow_backorders']) ? (bool) $data['allow_backorders'] : null,
            barcode: $data['barcode'] ?? null,
            isbn: $data['isbn'] ?? null,
            weight: isset($data['weight']) ? (float) $data['weight'] : null,
            dimensions: $data['dimensions'] ?? null,
            meta_keywords: $data['meta_keywords'] ?? null,
            search_keywords: $data['search_keywords'] ?? null,
            sort_order: isset($data['sort_order']) ? (int) $data['sort_order'] : null,
            variations: $data['variations'] ?? null,
            options: $data['options'] ?? null,
        );
    }

    public function toArray(): array
    {
        $data = [];

        if ($this->name !== null) $data['name'] = $this->name;
        if ($this->slug !== null) $data['slug'] = $this->slug;
        if ($this->product_code !== null) $data['product_code'] = $this->product_code;
        if ($this->category_id !== null) $data['category_id'] = $this->category_id;
        if ($this->brand_id !== null) $data['brand_id'] = $this->brand_id;
        if ($this->short_description !== null) $data['short_description'] = $this->short_description;
        if ($this->description !== null) $data['description'] = $this->description;
        if ($this->status !== null) {
            $data['status'] = $this->status;
            $data['published_at'] = $this->status === 'Published' ? now() : null;
        }
        if ($this->type !== null) $data['type'] = $this->type;
        if ($this->price !== null) $data['price'] = $this->price;
        if ($this->previous_price !== null) $data['previous_price'] = $this->previous_price;
        if ($this->feature_image !== null) $data['feature_image'] = $this->feature_image;
        if ($this->youtube_video !== null) $data['youtube_video'] = $this->youtube_video;
        if ($this->upload_video !== null) $data['upload_video'] = $this->upload_video;
        if ($this->gallery_images !== null) $data['gallery_images'] = $this->gallery_images;
        if ($this->description_images !== null) $data['description_images'] = $this->description_images;
        if ($this->product_tags !== null) $data['product_tags'] = $this->product_tags;
        if ($this->specification !== null) $data['specification'] = $this->specification;
        if ($this->is_daily_product !== null) $data['is_daily_product'] = $this->is_daily_product;
        if ($this->is_free_delivery !== null) $data['is_free_delivery'] = $this->is_free_delivery;
        if ($this->is_pre_order !== null) $data['is_pre_order'] = $this->is_pre_order;
        if ($this->remarks !== null) $data['remarks'] = $this->remarks;
        if ($this->meta_title !== null) $data['meta_title'] = $this->meta_title;
        if ($this->meta_description !== null) $data['meta_description'] = $this->meta_description;
        if ($this->track_inventory !== null) $data['track_inventory'] = $this->track_inventory;
        if ($this->allow_backorders !== null) $data['allow_backorders'] = $this->allow_backorders;
        if ($this->barcode !== null) $data['barcode'] = $this->barcode;
        if ($this->isbn !== null) $data['isbn'] = $this->isbn;
        if ($this->weight !== null) $data['weight'] = $this->weight;
        if ($this->dimensions !== null) $data['dimensions'] = $this->dimensions;
        if ($this->meta_keywords !== null) $data['meta_keywords'] = $this->meta_keywords;
        if ($this->search_keywords !== null) $data['search_keywords'] = $this->search_keywords;
        if ($this->sort_order !== null) $data['sort_order'] = $this->sort_order;

        return $data;
    }
}
