<?php

namespace App\Services\Courier\Transformers;

use App\Contracts\OrderTransformerInterface;
use App\DTOs\CourierOrderDTO;


class SteadfastOrderTransformer  implements OrderTransformerInterface
{
    public function transform(array $data): array
    {
        $dto = CourierOrderDTO::fromArray($data);
        return array_filter($dto->toArray(), fn($value) => $value !== null);
    }

    public function transformBulk(array $orders): array
    {
        return array_map(fn($order) => $this->transform($order), $orders);
    }
}
