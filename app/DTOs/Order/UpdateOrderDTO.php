<?php

namespace App\DTOs\Order;

class UpdateOrderDTO
{
    public function __construct(
        public readonly int $orderId,
        public readonly ?string $customerEmail = null,
        public readonly ?string $customerName = null,
        public readonly ?string $customerPhone = null,
        public readonly ?string $shippingAddress = null,
        public readonly ?float $shippingCost = null,
        public readonly ?string $paymentMethod = null,
        public readonly ?string $status = null,
        public readonly ?string $paymentStatus = null,
        public readonly ?string $adminNotes = null,
        public readonly ?array $items = null,
        public readonly ?float $discount = null,
        public readonly ?string $discountType = null,
        public readonly ?string $area = null,
    ) {}

    public static function fromRequest(array $data, int $orderId): self
    {
        return new self(
            orderId: $orderId,
            customerEmail: $data['customer_email'] ?? null,
            customerName: $data['customer_name'] ?? null,
            customerPhone: $data['customer_phone'] ?? null,
            shippingAddress: $data['shipping_address'] ?? null,
            shippingCost: isset($data['shipping_cost']) ? (float) $data['shipping_cost'] : null,
            paymentMethod: $data['payment_method'] ?? null,
            status: $data['status'] ?? null,
            paymentStatus: $data['payment_status'] ?? null,
            adminNotes: $data['admin_notes'] ?? null,
            items: $data['items'] ?? null,
            discount: isset($data['discount']) ? (float) $data['discount'] : null,
            discountType: $data['discount_type'] ?? null,
            area: $data['area'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'customer_email' => $this->customerEmail,
            'customer_name' => $this->customerName,
            'customer_phone' => $this->customerPhone,
            'shipping_address' => $this->shippingAddress,
            'shipping_cost' => $this->shippingCost,
            'payment_method' => $this->paymentMethod,
            'status' => $this->status,
            'payment_status' => $this->paymentStatus,
            'admin_notes' => $this->adminNotes,
            'items' => $this->items,
            'pos_discount' => $this->discount,
            'discount_type' => $this->discountType,
            'area' => $this->area,
        ], fn($value) => $value !== null);
    }
}
