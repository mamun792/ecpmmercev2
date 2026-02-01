<?php

namespace App\Services\ProductGroup;

use App\Contracts\ProductGroupRepositoryInterface;
use App\Models\ProductGroup;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProductGroupService
{
    public function __construct(
        protected ProductGroupRepositoryInterface $repository
    ) {}

    public function paginate(int $perPage = 10): LengthAwarePaginator
    {
        return $this->repository->paginate($perPage);
    }

    public function create(array $data): ProductGroup
    {
        return DB::transaction(function () use ($data) {
            return $this->repository->create($data);
        });
    }

    public function update(ProductGroup $productGroup, array $data): ProductGroup
    {
        return DB::transaction(function () use ($productGroup, $data) {
            return $this->repository->update($productGroup, $data);
        });
    }

    public function delete(ProductGroup $productGroup): bool
    {
        return $this->repository->delete($productGroup);
    }

    public function toggleStatus(ProductGroup $productGroup): bool
    {
        return $this->repository->toggleStatus($productGroup);
    }

    public function searchProducts(string $query): Collection
    {
        return $this->repository->searchProducts($query);
    }

    public function find(int $id): ?ProductGroup
    {
        return $this->repository->find($id);
    }

    public function getAllWithProducts(): Collection
    {
        return $this->repository->getAllWithProducts();
    }
}
