<?php

namespace App\Contracts;

use App\DTOs\ProductFilterDTO;
use App\DTOs\ProductStoreDTO;
use App\DTOs\ProductUpdateDTO;
use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface ProductRepositoryInterface
{
    /**
     * Find all products with optional filters
     */
    public function findAll(ProductFilterDTO $filters = null): Collection;

    /**
     * Find products with pagination
     */
    public function findPaginated(ProductFilterDTO $filters = null, int $perPage = 15): LengthAwarePaginator;

    /**
     * Find product by ID
     */
    public function findById(int $id): ?Product;

    /**
     * Find product by slug
     */
    public function findBySlug(string $slug): ?Product;

    /**
     * Find product by SKU/Code
     */
    public function findByCode(string $code): ?Product;

    /**
     * Create new product
     */
    public function create(ProductStoreDTO $data): Product;

    /**
     * Update existing product
     */
    public function update(int $id, ProductUpdateDTO $data): Product;

    /**
     * Delete product (soft delete)
     */
    public function delete(int $id, ?string $reason = null): bool;

    /**
     * Restore soft deleted product
     */
    public function restore(int $id): bool;

    /**
     * Get products by category
     */
    public function findByCategory(int $categoryId, int $limit = null): Collection;

    /**
     * Get featured products
     */
    public function getFeatured(int $limit = 10): Collection;

    /**
     * Search products
     */
    public function search(string $query, ProductFilterDTO $filters = null, int $perPage = 15): LengthAwarePaginator;

    /**
     * Get products with low stock
     */
    public function getLowStock(int $threshold = 10): Collection;

    /**
     * Get out of stock products
     */
    public function getOutOfStock(): Collection;

    /**
     * Update product stock status based on inventory
     */
    public function updateStockStatus(int $productId): void;

    /**
     * Bulk update product status
     */
    public function bulkUpdateStatus(array $productIds, string $status): int;

    /**
     * Get product with all relationships for editing
     */
    public function findForEdit(int $id): ?Product;

    /**
     * Get product analytics data
     */
    public function getAnalytics(int $productId): array;
}
