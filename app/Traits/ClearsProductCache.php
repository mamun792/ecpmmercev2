<?php

namespace App\Traits;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

trait ClearsProductCache
{
    /**
     * Clear all product-related caches
     */
    protected function clearProductCache(): void
    {
        // Just flush all cache - simplest and most reliable
        Cache::flush();
    }

    /**
     * Clear cache for a specific product
     */
    protected function clearSingleProductCache(string $slug): void
    {
        Cache::forget("product.{$slug}");
    }

    /**
     * Clear all caches (use carefully)
     */
    protected function clearAllProductCaches(): void
    {
        // Flush entire cache to ensure all product-related data is cleared
        // This includes products, brands, categories, and other cached data
        Cache::flush();

        // Also clear specific cache patterns if needed
        $this->clearBrandCache();
    }

    /**
     * Clear brand-related caches
     */
    protected function clearBrandCache(): void
    {
        // Clear all brand cache keys
        Cache::forget('brands.all');
        Cache::forget('brands.active');

        // Clear any paginated brand caches
        // Since we don't know all possible page combinations, we flush all cache
        // The Cache::flush() above already handles this
    }
}
