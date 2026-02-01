<?php

namespace App\Contracts;

use App\Models\ProductGroup;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface ProductGroupRepositoryInterface
{
    public function paginate(int $perPage = 10): LengthAwarePaginator;

    public function create(array $data): ProductGroup;

    public function update(ProductGroup $productGroup, array $data): ProductGroup;

    public function delete(ProductGroup $productGroup): bool;

    public function toggleStatus(ProductGroup $productGroup): bool;

    public function searchProducts(string $query): Collection;

    public function find(int $id): ?ProductGroup;

    public function getAllWithProducts(): Collection;
}
