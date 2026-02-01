<?php

namespace App\DTOs;

class ProductStoreDTO
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $shortDescription = null,
        public readonly ?string $description = null,
        public readonly int $categoryId,
        public readonly ?int $brandId = null,
        public readonly string $slug,
        public readonly string $sku,
        public readonly float $price = 0.00,
        public readonly ?float $comparePrice = null,
        public readonly ?float $costPrice = null,
        public readonly bool $trackQuantity = true,
        public readonly bool $sellWithoutStock = false,
        public readonly int $minQuantity = 0,
        public readonly string $status = 'active',
        public readonly ?string $metaTitle = null,
        public readonly ?string $metaDescription = null,
        public readonly array $images = [],
        public readonly array $variations = [],
        public readonly array $stockData = [],
    ) {
        $this->validate();
    }

    private function validate(): void
    {
        if (empty(trim($this->name))) {
            throw new \InvalidArgumentException('Product name is required');
        }

        if (empty(trim($this->slug))) {
            throw new \InvalidArgumentException('Product slug is required');
        }

        if (empty(trim($this->sku))) {
            throw new \InvalidArgumentException('Product SKU is required');
        }

        if ($this->categoryId <= 0) {
            throw new \InvalidArgumentException('Valid category ID is required');
        }

        if ($this->price < 0) {
            throw new \InvalidArgumentException('Price cannot be negative');
        }
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'product_code' => $this->sku,  // Map sku to product_code for database
            'short_description' => $this->shortDescription,
            'description' => $this->description,
            'category_id' => $this->categoryId,
            'brand_id' => $this->brandId,
            'slug' => $this->slug,
            'price' => $this->price,
            'compare_price' => $this->comparePrice,
            'cost_price' => $this->costPrice,
            'track_quantity' => $this->trackQuantity,
            'sell_without_stock' => $this->sellWithoutStock,
            'min_quantity' => $this->minQuantity,
            'status' => $this->status,
            'meta_title' => $this->metaTitle,
            'meta_description' => $this->metaDescription,
        ];
    }

    public function toLegacyArray(): array
    {
        return [
            'name' => $this->name,
            'product_code' => $this->sku,
            'category_id' => $this->categoryId,
            'brand_id' => $this->brandId,
            'short_description' => $this->shortDescription,
            'description' => $this->description,
            'status' => $this->status,
            'slug' => $this->slug,
            'type' => count($this->variations) > 0 ? 'variable' : 'simple',
            'price' => $this->price,
            'previous_price' => $this->comparePrice,
            'stock' => $this->trackQuantity ? array_sum(array_column($this->stockData, 'quantity')) : 0,
            'track_quantity' => $this->trackQuantity,
            'sell_without_stock' => $this->sellWithoutStock,
            'min_quantity' => $this->minQuantity,
            'remarks' => null,
            'meta_title' => $this->metaTitle,
            'meta_description' => $this->metaDescription,
            'is_daily_product' => false,
            'is_pre_order' => false,
            'is_free_delivery' => false,
            'view_count' => 0,
            'sold_stock' => 0,
            'variations' => $this->variations,
            'stock_data' => $this->stockData,
            'images' => $this->images,
        ];
    }

    public static function fromRequest(array $data): self
    {
        return new self(
            name: $data['name'],
            shortDescription: $data['short_description'] ?? null,
            description: $data['description'] ?? null,
            categoryId: (int) $data['category_id'],
            brandId: isset($data['brand_id']) ? (int) $data['brand_id'] : null,
            slug: $data['slug'] ?? \Illuminate\Support\Str::slug($data['name']),
            sku: $data['product_code'] ?? self::generateSku($data['name']),
            price: (float) ($data['price'] ?? 0),
            comparePrice: isset($data['previous_price']) ? (float) $data['previous_price'] : null,
            costPrice: isset($data['cost_price']) ? (float) $data['cost_price'] : null,
            trackQuantity: (bool) ($data['track_quantity'] ?? true),
            sellWithoutStock: (bool) ($data['sell_without_stock'] ?? false),
            minQuantity: (int) ($data['min_quantity'] ?? 0),
            status: $data['status'] ?? 'Published',
            metaTitle: $data['meta_title'] ?? null,
            metaDescription: $data['meta_description'] ?? null,
            images: self::processImages($data),
            variations: $data['variations'] ?? [],
            stockData: $data['stock_data'] ?? [],
        );
    }

    private static function generateSku(string $name): string
    {
        return 'PRD-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    }

    private static function processImages(array $data): array
    {
        $images = [];

        if (isset($data['feature_image'])) {
            $images['feature_image'] = $data['feature_image'];
        }

        if (isset($data['gallery_images'])) {
            $images['gallery_images'] = $data['gallery_images'];
        }

        if (isset($data['upload_video'])) {
            $images['upload_video'] = $data['upload_video'];
        }

        return $images;
    }
}
