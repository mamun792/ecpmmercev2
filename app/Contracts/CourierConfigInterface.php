<?php

namespace App\Contracts;

interface CourierConfigInterface
{
    public function getApiKey(): string;
    public function getSecretKey(): string;
    public function getBaseUrl(): string;
    public function getHeaders(): array;
}
