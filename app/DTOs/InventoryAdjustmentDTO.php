<?php

namespace App\DTOs;

/**
 * Data Transfer Object for inventory adjustments
 * Encapsulates all data needed for stock adjustments with validation
 */
class InventoryAdjustmentDTO
{
    public function __construct(
        public readonly int $productId,
        public readonly string $location,
        public readonly string $adjustmentType, // 'increase', 'decrease', 'set'
        public readonly int $quantity,
        public readonly string $reason,
        public readonly ?int $variationId = null,
        public readonly ?string $notes = null,
        public readonly ?int $userId = null,
        public readonly ?string $referenceType = null,
        public readonly ?int $referenceId = null,
    ) {
        $this->validate();
    }

    /**
     * Validate the DTO data
     */
    private function validate(): void
    {
        if ($this->productId <= 0) {
            throw new \InvalidArgumentException('Product ID must be positive');
        }

        if (!in_array($this->adjustmentType, ['increase', 'decrease', 'set', 'add'])) {
            throw new \InvalidArgumentException('Invalid adjustment type');
        }

        if ($this->quantity < 0) {
            throw new \InvalidArgumentException('Quantity cannot be negative');
        }

        if (empty(trim($this->location))) {
            throw new \InvalidArgumentException('Location is required');
        }

        if (empty(trim($this->reason))) {
            throw new \InvalidArgumentException('Reason is required');
        }
    }

    /**
     * Convert to array for logging or storage
     */
    public function toArray(): array
    {
        return [
            'product_id' => $this->productId,
            'variation_id' => $this->variationId,
            'location' => $this->location,
            'adjustment_type' => $this->adjustmentType,
            'quantity' => $this->quantity,
            'reason' => $this->reason,
            'notes' => $this->notes,
            'user_id' => $this->userId,
            'reference_type' => $this->referenceType,
            'reference_id' => $this->referenceId,
        ];
    }

    /**
     * Create from request data
     */
    public static function fromRequest(array $data): self
    {
        return new self(
            productId: $data['product_id'],
            location: $data['location'],
            adjustmentType: $data['adjustment_type'],
            quantity: $data['quantity'],
            reason: $data['reason'],
            notes: $data['notes'] ?? null,
            userId: $data['user_id'] ?? null,
            referenceType: $data['reference_type'] ?? null,
            referenceId: $data['reference_id'] ?? null,
        );
    }
}
