<?php

namespace App\Contracts;

interface CourierServiceInterface
{
    public function createOrder(array $orderData): array;
    public function createBulkOrder(array $orders): array;
    public function getStatusByConsignmentId(string $consignmentId): array;
    // public function getStatusByInvoice(string $invoice): array;
    // public function getStatusByTrackingCode(string $trackingCode): array;
}
