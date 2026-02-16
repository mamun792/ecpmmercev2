<?php

namespace App\Repository;

use App\Contracts\ProductRepositoryInterface;
use App\DTOs\ProductFilterDTO;
use App\DTOs\ProductStoreDTO;
use App\DTOs\ProductUpdateDTO;
use App\Models\Product;
use App\Models\InventoryStock;
use App\Services\Inventory\InventoryService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ProductRepository implements ProductRepositoryInterface
{
    protected InventoryService $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    /**
     * Find all products with optional filters
     */
    public function findAll(ProductFilterDTO $filters = null): Collection
    {
        $query = $this->buildQuery($filters);
        return $query->get();
    }

    /**
     * Find products with pagination
     */
    public function findPaginated(ProductFilterDTO $filters = null, int $perPage = 50): LengthAwarePaginator
    {
        $query = $this->buildQuery($filters);
        return $query->paginate($perPage);
    }

    /**
     * Find product by ID
     */
    public function findById(int $id): ?Product
    {
        return Product::with([
            'category',
            'brand',
            'variations.variationAttributes.attribute',
            'variations.variationAttributes.value',
            'inventoryStocks',
            'reviews' => fn($q) => $q->where('is_approved', true)->latest()
        ])->find($id);
    }

    /**
     * Find product by slug
     */
    public function findBySlug(string $slug): ?Product
    {
        return Product::with([
            'category',
            'brand',
            'variations',
            'inventoryStocks'
        ])->where('slug', $slug)->first();
    }

    /**
     * Find product by SKU/Code
     */
    public function findByCode(string $code): ?Product
    {
        return Product::where('product_code', $code)->first();
    }

    /**
     * Create new product
     */
    public function create(ProductStoreDTO $data): Product
    {
        return DB::transaction(function () use ($data) {
            // Create the product using legacy array mapping
            $product = Product::create($data->toLegacyArray());

            Log::info('Product created', ['product_id' => $product->id, 'name' => $product->name]);

            // Create initial inventory stock from stockData if specified
            if (!empty($data->stockData)) {
                foreach ($data->stockData as $stockItem) {
                    $this->inventoryService->createStock(
                        productId: $product->id,
                        quantity: $stockItem['quantity'] ?? 0,
                        locationCode: $stockItem['location_code'] ?? 'default',
                        costPrice: $data->costPrice ?? 0,
                        reason: 'Initial stock setup',
                        notes: 'Stock created during product creation'
                    );
                }
            }

            // Handle variations for variable products
            if (!empty($data->variations)) {
                $this->createProductVariations($product, $data->variations);
            }

            // Update stock status
            $this->updateStockStatus($product->id);

            return $product;
        });
    }

    /**
     * Update existing product
     */
    public function update(int $id, ProductUpdateDTO $data): Product
    {
        return DB::transaction(function () use ($id, $data) {
            $product = Product::findOrFail($id);

            // Update product data
            $product->update($data->toArray());

            Log::info('Product updated', ['product_id' => $product->id, 'name' => $product->name]);

            // Handle variations update
            if ($data->variations) {
                $this->updateProductVariations($product, $data->variations);
            }

            // Handle options update
            if ($data->options) {
                $this->updateProductOptions($product, $data->options);
            }

            // Update stock status
            $this->updateStockStatus($product->id);

            return $product->refresh();
        });
    }

    /**
     * Delete product (soft delete)
     */
    public function delete(int $id, ?string $reason = null): bool
    {
        return DB::transaction(function () use ($id, $reason) {
            $product = Product::findOrFail($id);

            // Update the product with deletion info
            $product->update([
                'deletion_reason' => $reason,
                'deleted_by' => auth()->id(),
                'deletion_context' => [
                    'deleted_at' => now(),
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent()
                ]
            ]);

            // Soft delete the product
            $deleted = $product->delete();

            if ($deleted) {
                Log::info('Product deleted', [
                    'product_id' => $id,
                    'reason' => $reason,
                    'deleted_by' => auth()->id()
                ]);
            }

            return $deleted;
        });
    }

    /**
     * Restore soft deleted product
     */
    public function restore(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $product = Product::onlyTrashed()->findOrFail($id);

            // Update restore info
            $product->update([
                'recovered_at' => now(),
                'recovered_by' => auth()->id()
            ]);

            $restored = $product->restore();

            if ($restored) {
                Log::info('Product restored', [
                    'product_id' => $id,
                    'restored_by' => auth()->id()
                ]);
            }

            return $restored;
        });
    }

    /**
     * Get products by category
     */
    public function findByCategory(int $categoryId, int $limit = null): Collection
    {
        $query = Product::where('category_id', $categoryId)
                       ->where('status', 'Published')
                       ->orderBy('sort_order')
                       ->orderBy('created_at', 'desc');

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * Get featured products
     */
    public function getFeatured(int $limit = 10): Collection
    {
        return Cache::remember("featured_products_{$limit}", 1800, function () use ($limit) {
            return Product::where('status', 'Published')
                         ->whereNotNull('featured_at')
                         ->with(['category', 'brand', 'inventoryStocks'])
                         ->orderBy('featured_at', 'desc')
                         ->limit($limit)
                         ->get();
        });
    }

    /**
     * Search products
     */
    public function search(string $query, ProductFilterDTO $filters = null, int $perPage = 15): LengthAwarePaginator
    {
        $searchQuery = $this->buildSearchQuery($query, $filters);
        return $searchQuery->paginate($perPage);
    }

    /**
     * Get products with low stock
     */
    public function getLowStock(int $threshold = 10): Collection
    {
        return Product::whereHas('inventoryStocks', function ($query) use ($threshold) {
            $query->where('available_quantity', '<=', $threshold)
                  ->where('available_quantity', '>', 0);
        })->with(['inventoryStocks', 'category'])->get();
    }

    /**
     * Get count of products with low stock (optimized for stats)
     */
    public function getLowStockCount(int $threshold = 10): int
    {
        return Product::whereHas('inventoryStocks', function ($query) use ($threshold) {
            $query->where('available_quantity', '<=', $threshold)
                  ->where('available_quantity', '>', 0);
        })->count();
    }

    /**
     * Get out of stock products
     * Products with no inventory or zero stock
     */
    public function getOutOfStock(): Collection
    {
        return Product::where(function ($query) {
            // Products with inventory records showing 0 stock
            $query->whereHas('inventoryStocks', function ($q) {
                $q->where('available_quantity', '<=', 0);
            })
            // OR products with no inventory records at all
            ->orWhereDoesntHave('inventoryStocks');
        })->with(['inventoryStocks', 'category'])->get();
    }

    /**
     * Get count of out of stock products (optimized for stats)
     */
    public function getOutOfStockCount(): int
    {
        return Product::where(function ($query) {
            $query->whereHas('inventoryStocks', function ($q) {
                $q->where('available_quantity', '<=', 0);
            })->orWhereDoesntHave('inventoryStocks');
        })->count();
    }

    /**
     * Get all product stats in one optimized query
     */
    public function getProductStats(): array
    {
        // Main stats in single query
        $stats = Product::selectRaw('
            COUNT(*) as total_products,
            SUM(CASE WHEN status = "Published" THEN 1 ELSE 0 END) as published_products
        ')->first();

        return [
            'total_products' => (int) $stats->total_products,
            'published_products' => (int) $stats->published_products,
            'low_stock_products' => $this->getLowStockCount(),
            'out_of_stock_products' => $this->getOutOfStockCount(),
        ];
    }

    /**
     * Update product stock status based on inventory
     */
    public function updateStockStatus(int $productId): void
    {
        $product = Product::findOrFail($productId);

        $totalStock = InventoryStock::where('product_id', $productId)
                                   ->sum('available_quantity');

        $stockStatus = $totalStock > 0 ? 'in_stock' : 'out_of_stock';

        $product->update(['stock_status' => $stockStatus]);
    }

    /**
     * Build query with filters
     */
    protected function buildQuery(?ProductFilterDTO $filters): Builder
    {
        // Load variations with attributes for the stock display in products index
        $query = Product::with([
            'category:id,name,slug',  // Select only needed columns
            'brand:id,brand_name',
            'inventoryStocks' => function($q) {
                $q->select('product_id', DB::raw('SUM(available_quantity) as total_stock'))
                  ->groupBy('product_id');
            },
            // Load variations with attributes for the variation breakdown display
            'variations' => function($q) {
                $q->select('id', 'product_id', 'sku', 'variation_code', 'price', 'is_default')
                  ->with([
                      'attributes.value.attribute:id,name', // Load attribute names and values
                      'inventoryStock:product_variation_id,available_quantity' // Load variation stock
                  ])
                  ->limit(10); // Limit to prevent performance issues
            }
        ])->withCount('variations'); // Count variations for UI

        if (!$filters) {
            return $query->orderBy('created_at', 'desc');
        }

        // Handle soft deletes
        if ($filters->only_trashed) {
            $query->onlyTrashed();
        } elseif ($filters->with_trashed) {
            $query->withTrashed();
        }

        // Apply filters
        if ($filters->search) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters->search}%")
                  ->orWhere('product_code', 'like', "%{$filters->search}%")
                  ->orWhere('barcode', 'like', "%{$filters->search}%") // Added barcode search
                  ->orWhere('short_description', 'like', "%{$filters->search}%")
                  ->orWhereHas('variations', function($vq) use ($filters) {
                      $vq->where('sku', 'like', "%{$filters->search}%")
                         ->orWhere('barcode', 'like', "%{$filters->search}%");
                  });
            });
        }

        if ($filters->category_id) {
            $query->where('category_id', $filters->category_id);
        }

        if ($filters->brand_id) {
            $query->where('brand_id', $filters->brand_id);
        }

        if ($filters->status) {
            $query->where('status', $filters->status);
        }

        if ($filters->type) {
            $query->where('type', $filters->type);
        }

        if ($filters->stock_status) {
            $query->where('stock_status', $filters->stock_status);
        }

        if ($filters->min_price) {
            $query->where('price', '>=', $filters->min_price);
        }

        if ($filters->max_price) {
            $query->where('price', '<=', $filters->max_price);
        }

        if ($filters->is_daily_product !== null) {
            $query->where('is_daily_product', $filters->is_daily_product);
        }

        if ($filters->is_pre_order !== null) {
            $query->where('is_pre_order', $filters->is_pre_order);
        }

        if ($filters->date_from) {
            $query->where('created_at', '>=', $filters->date_from);
        }

        if ($filters->date_to) {
            $query->where('created_at', '<=', $filters->date_to);
        }

        // Apply sorting
        $query->orderBy($filters->sort_by, $filters->sort_order);

        return $query;
    }

    /**
     * Build search query
     */
    protected function buildSearchQuery(string $searchTerm, ?ProductFilterDTO $filters): Builder
    {
        $query = Product::where('status', 'Published');

        // Full-text search
        $query->where(function ($q) use ($searchTerm) {
            $q->where('name', 'like', "%{$searchTerm}%")
              ->orWhere('short_description', 'like', "%{$searchTerm}%")
              ->orWhere('description', 'like', "%{$searchTerm}%")
              ->orWhere('product_code', 'like', "%{$searchTerm}%")
              ->orWhere('barcode', 'like', "%{$searchTerm}%") // Added barcode search
              ->orWhere('search_keywords', 'like', "%{$searchTerm}%")
              ->orWhereHas('variations', function($vq) use ($searchTerm) {
                  $vq->where('sku', 'like', "%{$searchTerm}%")
                     ->orWhere('barcode', 'like', "%{$searchTerm}%");
              });
        });

        // Apply additional filters if provided
        if ($filters) {
            if ($filters->category_id) {
                $query->where('category_id', $filters->category_id);
            }
            // Add other filter logic...
        }

        return $query->with(['category', 'brand', 'inventoryStocks']);
    }

    // Additional helper methods would go here...

    public function bulkUpdateStatus(array $productIds, string $status): int
    {
        return Product::whereIn('id', $productIds)->update([
            'status' => $status,
            'published_at' => $status === 'Published' ? now() : null
        ]);
    }

    public function findForEdit(int $id): ?Product
    {
        return Product::with([
            'category',
            'brand',
            'variations.variationAttributes.attribute',
            'variations.variationAttributes.value',
            'inventoryStocks',
            'productGroups',
            'campaigns'
        ])->find($id);
    }

    public function getAnalytics(int $productId): array
    {
        // Implementation for product analytics
        return [];
    }

    /**
     * Create product variations with attributes
     * Big Tech Style - Full implementation with centralized inventory
     */
    protected function createProductVariations(Product $product, array $variations): void
    {
        foreach ($variations as $index => $variationData) {
            // Create variation record (stock is now managed via inventory_stocks table)
            $variation = \App\Models\ProductVariation::create([
                'product_id' => $product->id,
                'cost_price' => $variationData['cost_price'] ?? 0,
                'price' => $variationData['price'] ?? 0,
                'previous_price' => $variationData['previous_price'] ?? null,
                'image_path' => $variationData['image_path'] ?? null,
                // New schema fields
                'status' => 'active',
                'is_default' => $index === 0,
                'sort_order' => $index,
                'track_inventory' => true,
                'stock_status' => ($variationData['stock'] ?? 0) > 0 ? 'in_stock' : 'out_of_stock',
            ]);

            Log::info('Variation created via Repository', [
                'variation_id' => $variation->id,
                'product_id' => $product->id,
            ]);

            // Create variation attributes
            if (!empty($variationData['attributes'])) {
                foreach ($variationData['attributes'] as $attributeData) {
                    $attributeValueId = is_array($attributeData)
                        ? ($attributeData['attribute_value_id'] ?? null)
                        : $attributeData;

                    if ($attributeValueId) {
                        \App\Models\VariationAttribute::create([
                            'product_variation_id' => $variation->id,
                            'attribute_value_id' => (int) $attributeValueId,
                        ]);
                    }
                }
            }

            // Create inventory stock for variation
            if (($variationData['stock'] ?? 0) > 0) {
                $this->inventoryService->createStock(
                    productId: $product->id,
                    variationId: $variation->id,
                    quantity: $variationData['stock'],
                    locationCode: 'MAIN',
                    reason: 'Initial variation stock',
                    notes: "Stock for variation #{$variation->id}"
                );
            }
        }

        // Update product total stock
        $totalStock = $product->variations()->sum('stock');
        $product->update(['stock' => $totalStock]);
    }

    protected function createProductOptions(Product $product, array $options): void
    {
        // Implementation for creating product options
    }

    /**
     * Update product variations
     * Big Tech Style - Full implementation with soft delete handling
     */
    protected function updateProductVariations(Product $product, array $variations): void
    {
        // Get existing variation IDs
        $existingVariationIds = $product->variations()->pluck('id')->toArray();
        $updatedVariationIds = [];

        foreach ($variations as $variationData) {
            if (isset($variationData['id']) && in_array($variationData['id'], $existingVariationIds)) {
                // Update existing variation
                $variation = \App\Models\ProductVariation::find($variationData['id']);
                if ($variation) {
                    $variation->update([
                        'price' => $variationData['price'] ?? $variation->price,
                        'previous_price' => $variationData['previous_price'] ?? $variation->previous_price,
                        'stock' => $variationData['stock'] ?? $variation->stock,
                        'image_path' => $variationData['image_path'] ?? $variation->image_path,
                    ]);

                    // Update attributes if provided
                    if (!empty($variationData['attributes'])) {
                        // Remove old attributes
                        $variation->attributes()->delete();

                        // Create new attributes
                        foreach ($variationData['attributes'] as $attributeData) {
                            $attributeValueId = is_array($attributeData)
                                ? ($attributeData['attribute_value_id'] ?? null)
                                : $attributeData;

                            if ($attributeValueId) {
                                \App\Models\VariationAttribute::create([
                                    'product_variation_id' => $variation->id,
                                    'attribute_value_id' => (int) $attributeValueId,
                                ]);
                            }
                        }
                    }

                    $updatedVariationIds[] = $variation->id;
                }
            } else {
                // Create new variation (stock managed via inventory_stocks)
                $variation = \App\Models\ProductVariation::create([
                    'product_id' => $product->id,
                    'cost_price' => $variationData['cost_price'] ?? 0,
                    'price' => $variationData['price'] ?? 0,
                    'previous_price' => $variationData['previous_price'] ?? null,
                    'image_path' => $variationData['image_path'] ?? null,
                    'status' => 'active',
                    'track_inventory' => true,
                    'stock_status' => ($variationData['stock'] ?? 0) > 0 ? 'in_stock' : 'out_of_stock',
                ]);

                // Create inventory stock for new variation
                if (($variationData['stock'] ?? 0) > 0) {
                    $this->inventoryService->createStock(
                        productId: $product->id,
                        variationId: $variation->id,
                        quantity: $variationData['stock'],
                        locationCode: 'MAIN',
                        reason: 'Initial variation stock',
                        notes: "Stock for new variation #{$variation->id}"
                    );
                }

                if (!empty($variationData['attributes'])) {
                    foreach ($variationData['attributes'] as $attributeData) {
                        $attributeValueId = is_array($attributeData)
                            ? ($attributeData['attribute_value_id'] ?? null)
                            : $attributeData;

                        if ($attributeValueId) {
                            \App\Models\VariationAttribute::create([
                                'product_variation_id' => $variation->id,
                                'attribute_value_id' => (int) $attributeValueId,
                            ]);
                        }
                    }
                }

                $updatedVariationIds[] = $variation->id;
            }
        }

        // Soft delete variations that weren't updated
        $variationsToDelete = array_diff($existingVariationIds, $updatedVariationIds);
        if (!empty($variationsToDelete)) {
            \App\Models\ProductVariation::whereIn('id', $variationsToDelete)->delete();
        }

        // Update product total stock
        $totalStock = $product->variations()->sum('stock');
        $product->update(['stock' => $totalStock]);
    }

    protected function updateProductOptions(Product $product, array $options): void
    {
        // Implementation for updating product options
    }
}
