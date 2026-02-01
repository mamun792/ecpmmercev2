<?php

namespace App\DTOs;

class CourierOrderDTO
{
    public function __construct(
        public string $invoice,
        public string $recipientName,
        public string $recipientPhone,
        public string $recipientAddress,
        public float $codAmount,
        public ?string $alternativePhone = null,
        public ?string $recipientEmail = null,
        public ?string $note = null,
        public ?string $itemDescription = null,
        public ?int $totalLot = null,
        public ?int $deliveryType = 0
    ) {}

    public function toArray(): array
    {

        //// bhghigfhibkhgbghgyuhgyuy
        return [
            'invoice' => $this->invoice,
            'recipient_name' => $this->recipientName,
            'recipient_phone' => $this->recipientPhone,
            'recipient_address' => $this->recipientAddress,
            'cod_amount' => $this->codAmount,
            'alternative_phone' => $this->alternativePhone,
            'recipient_email' => $this->recipientEmail,
            'note' => $this->note,
            'item_description' => $this->itemDescription,
            'total_lot' => $this->totalLot,
            'delivery_type' => $this->deliveryType,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            invoice: $data['invoice'],
            recipientName: $data['recipient_name'],
            recipientPhone: $data['recipient_phone'],
            recipientAddress: $data['recipient_address'],
            codAmount: (float) $data['cod_amount'],
            alternativePhone: $data['alternative_phone'] ?? null,
            recipientEmail: $data['recipient_email'] ?? null,
            note: $data['note'] ?? null,
            itemDescription: $data['item_description'] ?? null,
            totalLot: $data['total_lot'] ?? null,
            deliveryType: $data['delivery_type'] ?? 0
        );
    }
}
