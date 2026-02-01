<?php

namespace App\Contracts;

interface OrderTransformerInterface
{
    public function transform(array $data): array;
    public function transformBulk(array $orders): array;
}
