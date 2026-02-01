<?php

namespace App\Services\Product;

use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Attribute;
use App\Models\ProductVariation;
use App\Models\VariationAttribute;
use App\DTOs\ProductCreationDTO;
use App\Helpers\ImageHelper;
use App\Helpers\VideoHelper;
use App\Services\Inventory\InventoryService;
use App\Services\BarcodeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * =====================================================
 * BIG TECH STYLE PRODUCT CREATION SERVICE
 * =====================================================
 *
 * Enterprise-grade product creation with:
 * - Complete transaction management
 * - Image/Video upload handling
 * - Variation creation with attributes
 * - Inventory stock initialization
 * - Automatic barcode generation (EAN-13)
 * - Comprehensive error handling & logging
 *
 * @author Enterprise Architecture Team
 */
class ProductCreationService
{
    public function __construct(
        private InventoryService $inventoryService,
        private BarcodeService $barcodeService
    ) {}

    // =====================================================
    // PUBLIC API - MAIN METHODS
    // =====================================================

    /**
     * Create a new product with all related data
     *
     * @param Request $request The HTTP request with product data
     * @return array{success: bool, product?: Product, error?: string}
     */
    public function createProduct(Request $request): array
    {
        try {
            Log::info('🚀 [ProductCreationService] Starting product creation', [
                'name' => $request->name,
                'type' => $request->type,
                'has_variations' => !empty($request->variations),
                'variations_count' => is_array($request->variations) ? count($request->variations) : 0,
            ]);

            // Create DTO from request
            $dto = ProductCreationDTO::fromRequest($request);

            return DB::transaction(function () use ($dto, $request) {
                // Step 1: Validate uniqueness
                $validationResult = $this->validateUniqueness($dto);
                if (!$validationResult['success']) {
                    return $validationResult;
                }

                // Step 2: Upload images and video
                $mediaResult = $this->processMediaUploads($dto);

                // Step 3: Create the product record
                $product = $this->createProductRecord($dto, $mediaResult);

                Log::info('✅ [ProductCreationService] Product created', [
                    'product_id' => $product->id,
                    'feature_image' => $product->feature_image,
                ]);

                // Step 4: Create variations if variable product
                if ($dto->hasVariations()) {
                    $this->createProductVariations($product, $dto, $request);
                    Log::info('✅ [ProductCreationService] Variations created', [
                        'product_id' => $product->id,
                        'variation_count' => count($dto->variations),
                    ]);
                }

                // Step 5: Create initial inventory stock
                $this->createInitialInventory($product, $dto);

                // Step 6: Update product stock count
                $this->updateProductStock($product);

                Log::info('🎉 [ProductCreationService] Product creation completed successfully', [
                    'product_id' => $product->id,
                    'name' => $product->name,
                    'type' => $product->type,
                    'total_stock' => $product->stock,
                ]);

                return [
                    'success' => true,
                    'product' => $product->fresh([
                        'variations.attributes.value.attribute',
                        'inventoryStocks'
                    ]),
                ];
            });

        } catch (\InvalidArgumentException $e) {
            Log::warning('⚠️ [ProductCreationService] Validation error', [
                'error' => $e->getMessage(),
            ]);
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        } catch (\Exception $e) {
            Log::error('❌ [ProductCreationService] Product creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return [
                'success' => false,
                'error' => 'Failed to create product: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get all data needed for product creation form
     */
    public function getFormData(): array
    {
        return [
            'categories' => $this->getActiveCategories(),
            'brands' => $this->getActiveBrands(),
            'attributes' => $this->getAttributesWithValues(),
            'inventory_locations' => $this->getInventoryLocations(),
            'product_types' => $this->getProductTypes(),
            'status_options' => $this->getStatusOptions(),
        ];
    }

    // =====================================================
    // PRIVATE METHODS - CORE BUSINESS LOGIC
    // =====================================================

    /**
     * Validate product uniqueness (slug, product_code)
     */
    private function validateUniqueness(ProductCreationDTO $dto): array
    {
        $baseSlug = Str::slug($dto->name);

        // Check for duplicate slug
        $existingProduct = Product::where('slug', $baseSlug)->first();
        if ($existingProduct) {
            return [
                'success' => false,
                'error' => 'A product with this name already exists. Please use a different name.',
            ];
        }

        // Check for duplicate product code (including soft-deleted)
        $existingCode = Product::withTrashed()
            ->where('product_code', $dto->productCode)
            ->first();

        if ($existingCode) {
            $errorMsg = $existingCode->trashed()
                ? 'This product code was used by a deleted product. Please use a different product code.'
                : 'This product code already exists. Please use a different product code.';

            return [
                'success' => false,
                'error' => $errorMsg,
            ];
        }

        return ['success' => true];
    }

    /**
     * Process all media uploads (feature image, gallery, video)
     * With request-level duplicate detection
     */
    private function processMediaUploads(ProductCreationDTO $dto): array
    {
        $result = [
            'feature_image' => null,
            'gallery_images' => [],
            'video' => null,
        ];

        // Track uploaded checksums in this request to prevent duplicates
        $uploadedChecksums = [];

        // Upload feature image
        if ($dto->featureImage) {
            $realPath = $dto->featureImage->getRealPath();
            $checksum = $realPath ? hash_file('sha256', $realPath) : null;

            $result['feature_image'] = ImageHelper::uploadImage(
                $dto->featureImage,
                'storage/products'
            );

            if ($checksum) {
                $uploadedChecksums[$checksum] = $result['feature_image'];
            }

            Log::debug('📷 Feature image uploaded', ['path' => $result['feature_image']]);
        }

        // Upload gallery images (skip if already uploaded as feature)
        if (!empty($dto->galleryImages)) {
            foreach ($dto->galleryImages as $galleryImage) {
                if ($galleryImage && $galleryImage->isValid()) {
                    // Check if this image was already uploaded in this request
                    $realPath = $galleryImage->getRealPath();
                    $checksum = $realPath ? hash_file('sha256', $realPath) : null;

                    if ($checksum && isset($uploadedChecksums[$checksum])) {
                        // Reuse already uploaded image
                        $result['gallery_images'][] = $uploadedChecksums[$checksum];
                        Log::debug('📷 Gallery image reused (same as feature)', ['path' => $uploadedChecksums[$checksum]]);
                    } else {
                        // Upload new image
                        $path = ImageHelper::uploadImage($galleryImage, 'storage/products/gallery');
                        if ($path) {
                            $result['gallery_images'][] = $path;
                            if ($checksum) {
                                $uploadedChecksums[$checksum] = $path;
                            }
                        }
                    }
                }
            }
            Log::debug('📷 Gallery images uploaded', ['count' => count($result['gallery_images'])]);
        }

        // Upload video
        if ($dto->uploadVideo) {
            $result['video'] = VideoHelper::uploadVideo(
                $dto->uploadVideo,
                'storage/products/videos'
            );
            Log::debug('🎬 Video uploaded', ['path' => $result['video']]);
        }

        return $result;
    }

    /**
     * Create the main product record
     */
    private function createProductRecord(ProductCreationDTO $dto, array $mediaResult): Product
    {
        $productData = $dto->toProductArray(
            featureImagePath: $mediaResult['feature_image'],
            galleryImagePaths: $mediaResult['gallery_images'],
            videoPath: $mediaResult['video']
        );

        // Auto-generate barcode if not provided
        if (empty($productData['barcode'])) {
            // Create product first to get ID, then update barcode
            $product = Product::create($productData);

            $barcode = $this->barcodeService->generateEAN13($product->id);
            $product->update(['barcode' => $barcode]);

            Log::info('📊 Auto-generated barcode', [
                'product_id' => $product->id,
                'barcode' => $barcode,
                'formatted' => $this->barcodeService->formatBarcode($barcode)
            ]);

            return $product;
        }

        return Product::create($productData);
    }

    /**
     * Create product variations with attributes
     *
     * @param Product $product The parent product
     * @param ProductCreationDTO $dto The product DTO
     * @param Request $request Original request for file access
     */
    private function createProductVariations(Product $product, ProductCreationDTO $dto, Request $request): void
    {
        Log::info('📦 [ProductCreationService] Creating variations', [
            'product_id' => $product->id,
            'variations_count' => count($dto->variations),
        ]);

        foreach ($dto->variations as $index => $variationData) {
            // Upload variation image if exists
            $variationImagePath = null;
            if ($request->hasFile("variations.{$index}.image_path")) {
                $variationImagePath = ImageHelper::uploadImage(
                    $request->file("variations.{$index}.image_path"),
                    'storage/variations'
                );
            }

            // Create variation record (stock is now managed via inventory_stocks table)
            $variation = ProductVariation::create([
                'product_id' => $product->id,
                'price' => $variationData['price'],
                'previous_price' => $variationData['previous_price'],
                'image_path' => $variationImagePath,
                // New schema fields
                'status' => 'active',
                'is_default' => $index === 0, // First variation is default
                'sort_order' => $index,
                'track_inventory' => $dto->trackQuantity,
                'stock_status' => ($variationData['stock'] ?? 0) > 0 ? 'in_stock' : 'out_of_stock',
            ]);

            // Auto-generate barcode for variation
            $variationBarcode = $this->barcodeService->generateVariationBarcode($product->id, $variation->id);
            $variation->update(['barcode' => $variationBarcode]);

            Log::debug('📦 Variation created with barcode', [
                'variation_id' => $variation->id,
                'barcode' => $variationBarcode,
                'price' => $variation->price,
                'initial_stock' => $variationData['stock'] ?? 0,
                'attributes_count' => count($variationData['attributes']),
            ]);

            // Create variation attributes
            $this->createVariationAttributes($variation, $variationData['attributes']);

            // Create inventory stock for variation (Big Tech style - centralized inventory)
            $stockQuantity = $variationData['stock'] ?? 0;
            if ($dto->trackQuantity) {
                $this->inventoryService->createStock(
                    productId: $product->id,
                    variationId: $variation->id,
                    quantity: $stockQuantity,
                    locationCode: 'MAIN',
                    reason: 'Initial variation stock',
                    notes: "Stock for variation #{$variation->id}",
                    minThreshold: $dto->minQuantity
                );
            }
        }
    }

    /**
     * Create variation attribute records
     */
    private function createVariationAttributes(ProductVariation $variation, array $attributes): void
    {
        foreach ($attributes as $attributeData) {
            $attributeValueId = is_array($attributeData)
                ? ($attributeData['attribute_value_id'] ?? null)
                : $attributeData;

            if ($attributeValueId) {
                VariationAttribute::create([
                    'product_variation_id' => $variation->id,
                    'attribute_value_id' => (int) $attributeValueId,
                ]);

                Log::debug('🏷️ Variation attribute created', [
                    'variation_id' => $variation->id,
                    'attribute_value_id' => $attributeValueId,
                ]);
            }
        }
    }

    /**
     * Create initial inventory stock for simple products
     */
    private function createInitialInventory(Product $product, ProductCreationDTO $dto): void
    {
        // Skip for variable products (handled in createProductVariations)
        if ($dto->type === 'variable') {
            Log::debug('📦 Skipping initial inventory for variable product (handled per variation)');
            return;
        }

        // Skip if not tracking quantity
        if (!$dto->trackQuantity) {
            Log::debug('📦 Skipping initial inventory - quantity tracking disabled');
            return;
        }

        // Use provided stock data or create default
        $stockData = !empty($dto->stockData) ? $dto->stockData : [
            [
                'location' => 'MAIN',
                'quantity' => $dto->stock,
                'notes' => 'Initial stock from product creation',
            ]
        ];

        foreach ($stockData as $stock) {
            if (isset($stock['quantity'])) {
                $this->inventoryService->createStock(
                    productId: $product->id,
                    quantity: (int) $stock['quantity'],
                    locationCode: $stock['location'] ?? 'MAIN',
                    reason: 'Initial stock setup',
                    notes: $stock['notes'] ?? 'Created during product creation',
                    minThreshold: $dto->minQuantity
                );

                Log::info('📦 Initial inventory created', [
                    'product_id' => $product->id,
                    'location' => $stock['location'] ?? 'MAIN',
                    'quantity' => $stock['quantity'],
                ]);
            }
        }
    }

    /**
     * Update product's stock status from inventory system
     * Stock is now managed via inventory_stocks table, not product.stock column
     */
    private function updateProductStock(Product $product): void
    {
        // Refresh product to get latest inventory data
        $product->refresh();
        $product->load('inventoryStocks');

        // Calculate total stock from inventory system
        $totalStock = $product->inventoryStocks()->sum('available_quantity');

        // Update stock_status based on inventory
        $stockStatus = $totalStock > 0 ? 'in_stock' : 'out_of_stock';

        $product->update([
            'stock_status' => $stockStatus,
        ]);

        Log::debug('📊 Product stock status updated from inventory', [
            'product_id' => $product->id,
            'total_stock' => $totalStock,
            'stock_status' => $stockStatus,
        ]);
    }

    // =====================================================
    // FORM DATA METHODS
    // =====================================================

    /**
     * Get active categories with hierarchy
     */
    private function getActiveCategories(): array
    {
        return Category::where('status', 'active')
            ->select('id', 'name', 'parent_id', 'slug')
            ->with(['parentRecursive:id,name'])
            ->orderBy('name')
            ->get()
            ->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'parent_id' => $category->parent_id,
                    'display_name' => $category->parentRecursive
                        ? "{$category->parentRecursive->name} > {$category->name}"
                        : $category->name,
                ];
            })
            ->toArray();
    }

    /**
     * Get active brands
     */
    private function getActiveBrands(): array
    {
        return Brand::where('status', 'active')
            ->select('id', 'brand_name as name', 'brand_slug as slug', 'brand_image as logo')
            ->orderBy('brand_name')
            ->get()
            ->toArray();
    }

    /**
     * Get attributes with their values
     */
    private function getAttributesWithValues(): array
    {
        return Attribute::with([
            'values' => function($query) {
                $query->select('id', 'attribute_id', 'value', 'color')
                      ->orderBy('value');
            }
        ])
        ->select('id', 'name')
        ->orderBy('name')
        ->get()
        ->toArray();
    }

    /**
     * Get available inventory locations
     */
    private function getInventoryLocations(): array
    {
        return [
            [
                'value' => 'MAIN',
                'label' => 'Main Warehouse',
                'description' => 'Primary inventory location'
            ],
            [
                'value' => 'STORE',
                'label' => 'Store Front',
                'description' => 'Physical store inventory'
            ],
            [
                'value' => 'ONLINE',
                'label' => 'Online Only',
                'description' => 'E-commerce exclusive stock'
            ],
            [
                'value' => 'SUPPLIER',
                'label' => 'Supplier Stock',
                'description' => 'Drop-shipping inventory'
            ]
        ];
    }

    /**
     * Get product type options
     */
    private function getProductTypes(): array
    {
        return [
            [
                'value' => 'simple',
                'label' => 'Simple Product',
                'description' => 'Single product without variations'
            ],
            [
                'value' => 'variable',
                'label' => 'Variable Product',
                'description' => 'Product with variations (size, color, etc.)'
            ],
        ];
    }

    /**
     * Get status options
     */
    private function getStatusOptions(): array
    {
        return [
            [
                'value' => 'Published',
                'label' => 'Published',
                'description' => 'Product is live and available'
            ],
            [
                'value' => 'Unpublished',
                'label' => 'Unpublished',
                'description' => 'Product is hidden from customers'
            ],
        ];
    }

    // =====================================================
    // VALIDATION METHODS
    // =====================================================

    /**
     * Validate product data before creation
     */
    public function validateProductData(array $data): array
    {
        $errors = [];

        // Check for duplicate SKU/Product Code
        if (!empty($data['product_code'])) {
            if (Product::where('product_code', $data['product_code'])->exists()) {
                $errors['product_code'] = ['This product code is already in use'];
            }
        }

        // Check category exists and is active
        if (!empty($data['category_id'])) {
            $category = Category::where('id', $data['category_id'])
                               ->where('status', 'active')
                               ->first();
            if (!$category) {
                $errors['category_id'] = ['Selected category is not available'];
            }
        }

        // Check brand exists and is active
        if (!empty($data['brand_id'])) {
            $brand = Brand::where('id', $data['brand_id'])
                          ->where('status', 'active')
                          ->first();
            if (!$brand) {
                $errors['brand_id'] = ['Selected brand is not available'];
            }
        }

        return $errors;
    }
}
