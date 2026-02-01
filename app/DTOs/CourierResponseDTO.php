<?php

namespace App\DTOs;


class CourierResponseDTO
{
    public function __construct(
        public int $status,
        public string $message,
        public ?array $consignment = null,
        public ?array $data = null,
        public ?string $deliveryStatus = null,
        public ?array $errors = null
    ) {}

    public static function fromArray(array $response): self
    {
        return new self(
            status: $response['status'] ?? 500,
            message: $response['message'] ?? 'Unknown error',
            consignment: $response['consignment'] ?? null,
            data: $response['data'] ?? null,
            deliveryStatus: $response['delivery_status'] ?? $response['consignment']['status'] ?? null,
            errors: $response['errors'] ?? $response['details'] ?? null
        );
    }

    public function isSuccess(): bool
    {
        return $this->status === 200;
    }

    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'message' => $this->message,
            'consignment' => $this->consignment,
            'data' => $this->data,
            'delivery_status' => $this->deliveryStatus,
            'errors' => $this->errors,
        ];
    }
}
