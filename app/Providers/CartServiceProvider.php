<?php

namespace App\Providers;

use App\Services\Cart\CartService;
use Illuminate\Support\ServiceProvider;

use App\Repository\Cart\CartRepository;
use App\Repository\Product\ProductRepository;
use App\Services\Inventory\InventoryService;

class CartServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(CartService::class, function ($app) {
            return new CartService(
                $app->make(CartRepository::class),
                $app->make(ProductRepository::class),
                $app->make(InventoryService::class)
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
