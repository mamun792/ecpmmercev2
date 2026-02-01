<?php

namespace App\DTOs;

use Illuminate\Http\UploadedFile;

/**
 * Big Tech Style DTO for Product Variations
 * Immutable data transfer object with validation
 */
class ProductVariationDTO
{
    public function __construct(
        public readonly float $price,
        public readonly ?float $previousPrice = null,
        public readonly int $stock = 0,
        public readonly ?UploadedFile $image = null,
        public readonly ?string $existingImagePath = null,
        public readonly array $attributes = [],
    ) {
        $this->validate();
    }

    private function validate(): void
    {
        if ($this->price < 0) {
            throw new \InvalidArgumentException('Variation price cannot be negative');
        }

        if ($this->stock < 0) {
            throw new \InvalidArgumentException('Variation stock cannot be negative');
        }

        if ($this->previousPrice !== null && $this->previousPrice <= $this->price) {
            // Allow but log warning - frontend should validate this
        }

        if (empty($this->attributes)) {
            throw new \InvalidArgumentException('Variation must have at least one attribute');
        }
    }

    /**
     * Create from request array data
     */
    public static function fromArray(array $data, ?UploadedFile $image = null): self
    {
        return new self(
            price: (float) ($data['price'] ?? 0),
            previousPrice: isset($data['previous_price']) && $data['previous_price'] !== ''
                ? (float) $data['previous_price']
                : null,
            stock: (int) ($data['stock'] ?? 0),
            image: $image,
            existingImagePath: $data['existing_image_path'] ?? null,
            attributes: $data['attributes'] ?? [],
        );
    }

    /**
     * Get attributes as array of attribute_value_ids
     */
    public function getAttributeValueIds(): array
    {
        return array_map(
            fn($attr) => (int) ($attr['attribute_value_id'] ?? $attr),
            $this->attributes
        );
    }

    public function toArray(): array
    {
        return [
            'price' => $this->price,
            'previous_price' => $this->previousPrice,
            'stock' => $this->stock,
            'attributes' => $this->attributes,
        ];
    }
}
