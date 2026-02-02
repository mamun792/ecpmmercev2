<?php

namespace App\Services\Search;

use App\Models\Product;
use Illuminate\Support\Facades\Cache;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Unified Search Service
 * Provides fuzzy search with TNTSearch for both admin and frontend
 * Supports typo tolerance, auto-complete, and cached results
 */
class ProductSearchService
{
    /**
     * Search products with fuzzy matching (typo tolerant)
     * 
     * @param string $query Search term
     * @param array $filters Additional filters (category, brand, price range)
     * @param int $perPage Items per page
     * @param bool $useCache Whether to cache results
     * @return LengthAwarePaginator
     */
    public function search(
        string $query,
        array $filters = [],
        int $perPage = 15,
        bool $useCache = true
    ): LengthAwarePaginator {
        $cacheKey = $this->getCacheKey($query, $filters, $perPage);

        if ($useCache) {
            return Cache::remember($cacheKey, 300, function () use ($query, $filters, $perPage) {
                return $this->performSearch($query, $filters, $perPage);
            });
        }

        return $this->performSearch($query, $filters, $perPage);
    }

    /**
     * Perform the actual search using Scout + TNTSearch
     */
    protected function performSearch(string $query, array $filters, int $perPage): LengthAwarePaginator
    {
        // Use Scout search with fuzzy matching enabled
        $searchQuery = Product::search($query);

        // Apply additional filters
        if (!empty($filters['category_id'])) {
            $searchQuery->where('category_id', $filters['category_id']);
        }

        if (!empty($filters['brand_id'])) {
            $searchQuery->where('brand_id', $filters['brand_id']);
        }

        if (!empty($filters['min_price'])) {
            $searchQuery->where('price', '>=', $filters['min_price']);
        }

        if (!empty($filters['max_price'])) {
            $searchQuery->where('price', '<=', $filters['max_price']);
        }

        // Only show published products on frontend
        if (!empty($filters['frontend'])) {
            $searchQuery->where('status', 'Published');
        }

        // Load necessary relationships
        return $searchQuery
            ->query(fn($builder) => $builder->with([
                'category:id,name,slug',
                'brand:id,brand_name',
                'inventoryStocks' => fn($q) => $q->select('product_id')->selectRaw('SUM(available_quantity) as total_stock')->groupBy('product_id')
            ]))
            ->paginate($perPage);
    }

    /**
     * Get search suggestions (autocomplete)
     * 
     * @param string $query Partial search term
     * @param int $limit Number of suggestions
     * @return array
     */
    public function getSuggestions(string $query, int $limit = 5): array
    {
        if (strlen($query) < 2) {
            return [];
        }

        $cacheKey = "search_suggestions:" . md5($query) . ":{$limit}";

        return Cache::remember($cacheKey, 600, function () use ($query, $limit) {
            return Product::search($query)
                ->where('status', 'Published')
                ->take($limit)
                ->get()
                ->map(function ($product) {
                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'slug' => $product->slug,
                        'image' => $product->feature_image_url,
                        'price' => $product->price,
                        'category' => $product->category?->name,
                    ];
                })
                ->toArray();
        });
    }

    /**
     * Search by barcode or product code (exact match)
     */
    public function searchByCode(string $code): ?Product
    {
        return Product::where('barcode', $code)
            ->orWhere('product_code', $code)
            ->first();
    }

    /**
     * Get popular search terms
     */
    public function getPopularSearches(int $limit = 10): array
    {
        // This would normally track searches in database
        // For now, return cached popular terms
        return Cache::remember('popular_searches', 3600, function () use ($limit) {
            return [
                'laptop',
                'phone',
                'headphone',
                'watch',
                'camera',
                'gaming',
                'keyboard',
                'mouse',
                'monitor',
                'speaker'
            ];
        });
    }

    /**
     * Clear search cache
     */
    public function clearCache(?string $query = null): void
    {
        if ($query) {
            $pattern = "search:" . md5($query) . "*";
            Cache::forget($pattern);
        } else {
            // Clear all search caches
            Cache::flush();
        }
    }

    /**
     * Generate cache key for search
     */
    protected function getCacheKey(string $query, array $filters, int $perPage): string
    {
        $filterHash = md5(json_encode($filters));
        return "search:" . md5($query) . ":{$filterHash}:{$perPage}";
    }

    /**
     * Rebuild search index for all products
     * Call this after bulk updates
     */
    public function rebuildIndex(): void
    {
        Product::makeAllSearchable();
        $this->clearCache();
    }

    /**
     * Get search statistics
     */
    public function getSearchStats(): array
    {
        return [
            'total_indexed' => Product::where('status', 'Published')->count(),
            'last_indexed' => Cache::get('search_last_indexed', 'Never'),
            'index_size' => $this->getIndexSize(),
        ];
    }

    /**
     * Get index size (in MB)
     */
    protected function getIndexSize(): float
    {
        $indexPath = storage_path('tntsearch/products_index.index');
        
        if (file_exists($indexPath)) {
            return round(filesize($indexPath) / 1024 / 1024, 2);
        }

        return 0;
    }
}
