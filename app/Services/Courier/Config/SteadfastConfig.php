<?php

namespace App\Services\Courier\Config;

use App\Contracts\CourierConfigInterface;

class SteadfastConfig implements CourierConfigInterface
{
    public function getApiKey(): string
    {
        return config('courier.steadfast.api_key');
    }

    public function getSecretKey(): string
    {
        return config('courier.steadfast.secret_key');
    }

    public function getBaseUrl(): string
    {
        return config('courier.steadfast.base_url', 'https://portal.packzy.com/api/v1');
    }

    public function getHeaders(): array
    {
        return [
            'Api-Key' => $this->getApiKey(),
            'Secret-Key' => $this->getSecretKey(),
            'Content-Type' => 'application/json',
        ];
    }
}
