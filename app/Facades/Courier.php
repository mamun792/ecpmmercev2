<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class Courier extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'courier';
    }
}
