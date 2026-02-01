<?php

namespace App\DTOs;

class ProductFilterDTO
{
    public function __construct(
        public readonly ?string $search = null,
        public readonly ?int $category_id = null,
        public readonly ?int $brand_id = null,
        public readonly ?string $status = null,
        public readonly ?string $type = null,
        public readonly ?string $stock_status = null,
        public readonly ?float $min_price = null,
        public readonly ?float $max_price = null,
        public readonly ?bool $is_featured = null,
        public readonly ?bool $is_daily_product = null,
        public readonly ?bool $is_pre_order = null,
        public readonly ?string $sort_by = 'created_at',
        public readonly string $sort_order = 'desc',
        public readonly int $per_page = 50,
        public readonly bool $with_trashed = false,
        public readonly bool $only_trashed = false,
        public readonly ?string $date_from = null,
        public readonly ?string $date_to = null,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            search: $data['search'] ?? null,
            category_id: isset($data['category_id']) ? (int) $data['category_id'] : null,
            brand_id: isset($data['brand_id']) ? (int) $data['brand_id'] : null,
            status: $data['status'] ?? null,
            type: $data['type'] ?? null,
            stock_status: $data['stock_status'] ?? null,
            min_price: isset($data['min_price']) ? (float) $data['min_price'] : null,
            max_price: isset($data['max_price']) ? (float) $data['max_price'] : null,
            is_featured: isset($data['is_featured']) ? (bool) $data['is_featured'] : null,
            is_daily_product: isset($data['is_daily_product']) ? (bool) $data['is_daily_product'] : null,
            is_pre_order: isset($data['is_pre_order']) ? (bool) $data['is_pre_order'] : null,
            sort_by: $data['sort_by'] ?? 'created_at',
            sort_order: $data['sort_order'] ?? 'desc',
            per_page: isset($data['per_page']) ? (int) $data['per_page'] : 50,
            with_trashed: (bool) ($data['with_trashed'] ?? false),
            only_trashed: (bool) ($data['only_trashed'] ?? false),
            date_from: $data['date_from'] ?? null,
            date_to: $data['date_to'] ?? null,
        );
    }

    public function hasFilters(): bool
    {
        return $this->search !== null ||
               $this->category_id !== null ||
               $this->brand_id !== null ||
               $this->status !== null ||
               $this->type !== null ||
               $this->stock_status !== null ||
               $this->min_price !== null ||
               $this->max_price !== null ||
               $this->is_featured !== null ||
               $this->is_daily_product !== null ||
               $this->is_pre_order !== null ||
               $this->date_from !== null ||
               $this->date_to !== null;
    }

    public function toArray(): array
    {
        return array_filter([
            'search' => $this->search,
            'category_id' => $this->category_id,
            'brand_id' => $this->brand_id,
            'status' => $this->status,
            'type' => $this->type,
            'stock_status' => $this->stock_status,
            'min_price' => $this->min_price,
            'max_price' => $this->max_price,
            'is_featured' => $this->is_featured,
            'is_daily_product' => $this->is_daily_product,
            'is_pre_order' => $this->is_pre_order,
            'sort_by' => $this->sort_by,
            'sort_order' => $this->sort_order,
            'per_page' => $this->per_page,
            'with_trashed' => $this->with_trashed,
            'only_trashed' => $this->only_trashed,
            'date_from' => $this->date_from,
            'date_to' => $this->date_to,
        ], fn($value) => $value !== null);
    }
}
