<?php

namespace App\DTOs;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;

/**
 * Big Tech Style DTO for Product Creation
 * Complete data transfer object handling all product creation data
 */
class ProductCreationDTO
{
    public function __construct(
        // Basic Information
        public readonly string $name,
        public readonly string $productCode,
        public readonly int $categoryId,
        public readonly ?int $brandId = null,
        public readonly ?string $shortDescription = null,
        public readonly ?string $description = null,
        public readonly string $status = 'Published',
        public readonly string $type = 'simple',

        // Pricing
        public readonly float $price = 0.00,
        public readonly ?float $costPrice = null,
        public readonly ?float $previousPrice = null,
        public readonly ?float $purchasePrice = null,
        public readonly int $stock = 0,

        // Images & Media
        public readonly ?UploadedFile $featureImage = null,
        public readonly array $galleryImages = [],
        public readonly ?UploadedFile $uploadVideo = null,
        public readonly ?string $youtubeVideo = null,

        // Flags
        public readonly bool $isDailyProduct = false,
        public readonly bool $isPreOrder = false,
        public readonly bool $isFreeDelivery = false,
        public readonly bool $trackQuantity = true,
        public readonly bool $sellWithoutStock = false,
        public readonly int $minQuantity = 0,

        // Additional Data
        public readonly ?string $remarks = null,
        public readonly ?string $metaTitle = null,
        public readonly ?string $metaDescription = null,
        public readonly array $productTags = [],
        public readonly array $specification = [],

        // Variations (for variable products)
        public readonly array $variations = [],

        // Inventory Stock Data
        public readonly array $stockData = [],
    ) {
        $this->validate();
    }

    private function validate(): void
    {
        if (empty(trim($this->name))) {
            throw new \InvalidArgumentException('Product name is required');
        }

        if (empty(trim($this->productCode))) {
            throw new \InvalidArgumentException('Product code is required');
        }

        if ($this->categoryId <= 0) {
            throw new \InvalidArgumentException('Valid category ID is required');
        }

        if ($this->type === 'simple' && $this->price < 0) {
            throw new \InvalidArgumentException('Price cannot be negative');
        }

        if ($this->type === 'variable' && empty($this->variations)) {
            throw new \InvalidArgumentException('Variable product must have at least one variation');
        }
    }

    /**
     * Create DTO from HTTP Request - Big Tech Factory Pattern
     */
    public static function fromRequest(Request $request): self
    {
        // Parse variations with images
        $variations = self::parseVariations($request);

        // Parse stock data
        $stockData = self::parseStockData($request);

        // Calculate total stock for variable products
        $stock = $request->type === 'variable'
            ? array_sum(array_column($variations, 'stock'))
            : (int) ($request->stock ?? 0);

        return new self(
            name: $request->name,
            productCode: $request->product_code,
            categoryId: (int) $request->category_id,
            brandId: $request->brand_id ? (int) $request->brand_id : null,
            shortDescription: $request->short_description,
            description: $request->description,
            status: $request->status ?? 'Published',
            type: $request->type ?? 'simple',

            price: (float) ($request->price ?? 0),
            costPrice: $request->cost_price ? (float) $request->cost_price : null,
            previousPrice: $request->previous_price ? (float) $request->previous_price : null,
            purchasePrice: $request->purchase_price ? (float) $request->purchase_price : null,
            stock: $stock,

            featureImage: $request->hasFile('feature_image') ? $request->file('feature_image') : null,
            galleryImages: $request->hasFile('gallery_images') ? $request->file('gallery_images') : [],
            uploadVideo: $request->hasFile('upload_video') ? $request->file('upload_video') : null,
            youtubeVideo: $request->youtube_video,

            isDailyProduct: (bool) $request->is_daily_product,
            isPreOrder: (bool) $request->is_pre_order,
            isFreeDelivery: (bool) $request->is_free_delivery,
            trackQuantity: (bool) ($request->track_quantity ?? true),
            sellWithoutStock: (bool) ($request->sell_without_stock ?? false),
            minQuantity: (int) ($request->min_quantity ?? 0),

            remarks: $request->remarks,
            metaTitle: $request->meta_title,
            metaDescription: $request->meta_description,
            productTags: is_array($request->product_tags) ? $request->product_tags : [],
            specification: is_array($request->specification) ? $request->specification : [],

            variations: $variations,
            stockData: $stockData,
        );
    }

    /**
     * Parse variations from request with images
     */
    private static function parseVariations(Request $request): array
    {
        if ($request->type !== 'variable' || empty($request->variations)) {
            return [];
        }

        $variations = [];
        foreach ($request->variations as $index => $variationData) {
            // Skip invalid variations
            if (empty($variationData['attributes'])) {
                continue;
            }

            // Check for variation image file
            $variationImage = $request->hasFile("variations.{$index}.image_path")
                ? $request->file("variations.{$index}.image_path")
                : null;

            $variations[] = [
                'price' => (float) ($variationData['price'] ?? 0),
                'cost_price' => isset($variationData['cost_price']) && $variationData['cost_price'] !== ''
                    ? (float) $variationData['cost_price']
                    : null,
                'previous_price' => isset($variationData['previous_price']) && $variationData['previous_price'] !== ''
                    ? (float) $variationData['previous_price']
                    : null,
                'stock' => (int) ($variationData['stock'] ?? 0),
                'image' => $variationImage,
                'attributes' => $variationData['attributes'],
            ];
        }

        return $variations;
    }

    /**
     * Parse stock data from request
     */
    private static function parseStockData(Request $request): array
    {
        if (empty($request->stock_data)) {
            // Default stock data if none provided
            $stock = $request->type === 'simple' ? (int) ($request->stock ?? 0) : 0;

            if ($stock > 0) {
                return [
                    [
                        'location' => 'MAIN',
                        'quantity' => $stock,
                        'notes' => 'Initial stock from product creation',
                    ]
                ];
            }
            return [];
        }

        return array_map(function ($item) {
            return [
                'location' => $item['location'] ?? 'MAIN',
                'quantity' => (int) ($item['quantity'] ?? 0),
                'notes' => $item['notes'] ?? null,
            ];
        }, $request->stock_data);
    }

    /**
     * Generate unique slug
     */
    public function generateSlug(): string
    {
        return Str::slug($this->name);
    }

    /**
     * Convert to database-ready array for Product model
     */
    public function toProductArray(?string $featureImagePath = null, ?array $galleryImagePaths = null, ?string $videoPath = null): array
    {
        $data = [
            'name' => $this->name,
            'product_code' => $this->productCode,
            'category_id' => $this->categoryId,
            'brand_id' => $this->brandId,
            'slug' => $this->generateSlug(),
            'short_description' => $this->shortDescription,
            'description' => $this->description,
            'status' => $this->status,
            'type' => $this->type,
            'price' => $this->price,
            'previous_price' => $this->previousPrice,
            'feature_image' => $featureImagePath,
            'gallery_images' => $galleryImagePaths ? json_encode($galleryImagePaths) : null,
            'upload_video' => $videoPath,
            'youtube_video' => $this->youtubeVideo,
            'is_daily_product' => $this->isDailyProduct,
            'is_pre_order' => $this->isPreOrder,
            'is_free_delivery' => $this->isFreeDelivery,
            'remarks' => $this->remarks,
            'meta_title' => $this->metaTitle,
            'meta_description' => $this->metaDescription,
            'product_tags' => !empty($this->productTags) ? json_encode($this->productTags) : null,
            'specification' => !empty($this->specification) ? json_encode($this->specification) : null,
            'view_count' => 0,
            'sold_stock' => 0,
        ];

        // Add cost_price if it exists in the table
        if ($this->costPrice !== null) {
            $data['cost_price'] = $this->costPrice;
        }

        // Add optional fields if they exist in database schema
        if (Schema::hasColumn('products', 'stock')) {
            $data['stock'] = $this->stock;
        }

        if (Schema::hasColumn('products', 'purchase_price') && $this->purchasePrice !== null) {
            $data['purchase_price'] = $this->purchasePrice;
        }

        if (Schema::hasColumn('products', 'track_inventory')) {
            $data['track_inventory'] = $this->trackQuantity;
        }

        if (Schema::hasColumn('products', 'allow_backorders')) {
            $data['allow_backorders'] = $this->sellWithoutStock;
        }

        if (Schema::hasColumn('products', 'min_quantity') && $this->minQuantity !== null) {
            $data['min_quantity'] = $this->minQuantity;
        }

        return $data;
    }

    /**
     * Check if product has variations
     */
    public function hasVariations(): bool
    {
        return $this->type === 'variable' && !empty($this->variations);
    }

    /**
     * Get total stock from all variations
     */
    public function getTotalVariationStock(): int
    {
        if (!$this->hasVariations()) {
            return $this->stock;
        }

        return array_sum(array_column($this->variations, 'stock'));
    }
}
