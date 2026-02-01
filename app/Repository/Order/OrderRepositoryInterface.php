<?php

namespace App\Repository\Order;

use Illuminate\Pagination\LengthAwarePaginator;



interface OrderRepositoryInterface
{



    public function getPaginatedOrders(
        int $perPage = 10,
        array $filters = [],
        string $sortBy = 'created_at',
        string $sortDirection = 'desc'
    ): LengthAwarePaginator;

    //findOrFail($orderId);
    public  function findOrderById(int $orderId);

    // public function getOrdersByUserId(int $userId): array;

    // public function getOrderItemsByOrderId(int $orderId): array;

    // public function createOrderItem(array $data): OrderItem;

    // public function updateOrderItem(int $id, array $data): bool;

    // public function deleteOrderItem(int $id): bool;
}
