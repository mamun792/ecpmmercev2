<?php

namespace App\Services\Courier;

use App\Contracts\CourierServiceInterface;

class CourierManager
{
    public function driver(string $driver = 'steadfast'): CourierServiceInterface
    {
        return match ($driver) {
            'steadfast' => app(SteadfastCourierService::class),
            default => throw new \InvalidArgumentException("Courier driver [{$driver}] not found."),
        };
    }

    public function __call(string $method, array $arguments)
    {
        return $this->driver()->$method(...$arguments);
    }
}
