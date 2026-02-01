<?php

namespace App\Services\Order;

use Illuminate\Pagination\LengthAwarePaginator;
use App\Models\Order;

interface OrderInterface
{
    public function createOrder(array $data): Order;




    public function getOrders(array $params = []): LengthAwarePaginator;

    // updateOrderStatus
    public function updateOrderStatus(int $orderId, string $status): Order;






    public function updateOrder(int $orderId, array $data): Order;

    public function updateCourierDetails(int $orderId, array $data): Order;

    public function deleteOrder(int $id);

    // updateNewOrder($orderId, $request->all());
    public function updateNewOrder(int $orderId, array $data): Order;

    /**
     * Return admin-facing notifications (count + recent items)
     *
     * @return array
     */
    public function adminNotifications(): array;

    /**
     * Update product stock (V2 inventory integration)
     */
    public function updateProductStock(
        \App\Models\Product $product,
        ?\App\Models\ProductVariation $variation,
        int $quantity,
        ?int $orderId = null
    ): void;

    /**
     * Restore product stock (V2 inventory integration)
     */
    public function restoreProductStock(
        \App\Models\Product $product,
        ?\App\Models\ProductVariation $variation,
        int $quantity,
        ?int $orderId = null,
        string $reason = 'Order cancellation'
    ): void;

    /**
     * Calculate order totals
     */
    public function calculateOrderTotals(Order $order): void;
}
