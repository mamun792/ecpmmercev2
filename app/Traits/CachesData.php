<?php

namespace App\Traits;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

trait CachesData
{
    /**
     * Cache TTL in minutes
     */
    protected int $cacheTTL = 30;

    /**
     * Get cached data or execute callback and cache result
     *
     * @param string $key
     * @param callable $callback
     * @param int|null $ttl Time to live in minutes
     * @return mixed
     */
    protected function getCached(string $key, callable $callback, ?int $ttl = null)
    {
        try {
            $ttl = $ttl ?? $this->cacheTTL;

            return Cache::remember($key, now()->addMinutes($ttl), function () use ($callback) {
                return $callback();
            });
        } catch (\Exception $e) {
            Log::error("Cache error for key {$key}: " . $e->getMessage());
            return $callback();
        }
    }

    /**
     * Clear cache by key
     *
     * @param string $key
     * @return bool
     */
    protected function clearCache(string $key): bool
    {
        try {
            return Cache::forget($key);
        } catch (\Exception $e) {
            Log::error("Cache clear error for key {$key}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Clear multiple cache keys by pattern
     *
     * @param string $pattern
     * @return void
     */
    protected function clearCachePattern(string $pattern): void
    {
        try {
            $keys = $this->getCacheKeysByPattern($pattern);

            foreach ($keys as $key) {
                Cache::forget($key);
            }

            Log::info("Cleared cache pattern: {$pattern}");
        } catch (\Exception $e) {
            Log::error("Cache pattern clear error for {$pattern}: " . $e->getMessage());
        }
    }

    /**
     * Clear all caches for a specific entity type
     *
     * @param string $entity (e.g., 'products', 'orders', 'dashboard')
     * @return void
     */
    protected function clearEntityCache(string $entity): void
    {
        $patterns = [
            "{$entity}:*",
            "dashboard:*",
            "admin:{$entity}:*"
        ];

        foreach ($patterns as $pattern) {
            $this->clearCachePattern($pattern);
        }
    }

    /**
     * Get cache keys matching pattern
     *
     * @param string $pattern
     * @return array
     */
    private function getCacheKeysByPattern(string $pattern): array
    {
        // For database cache driver, we need to query the cache table
        try {
            $cacheTable = config('cache.stores.database.table', 'cache');

            $keys = \DB::table($cacheTable)
                ->where('key', 'like', str_replace('*', '%', $pattern))
                ->pluck('key')
                ->toArray();

            return $keys;
        } catch (\Exception $e) {
            Log::error("Error getting cache keys: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Clear all application cache
     *
     * @return bool
     */
    protected function clearAllCache(): bool
    {
        try {
            Cache::flush();
            Log::info('All cache cleared');
            return true;
        } catch (\Exception $e) {
            Log::error("Cache flush error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Generate cache key for entity
     *
     * @param string $entity
     * @param string $type
     * @param array $params
     * @return string
     */
    protected function cacheKey(string $entity, string $type, array $params = []): string
    {
        $key = "{$entity}:{$type}";

        if (!empty($params)) {
            $key .= ':' . md5(json_encode($params));
        }

        return $key;
    }
}
