<?php

namespace App\Providers;

use App\Support\SystemCheck;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register any application services here
        $this->app->bind(
            \App\Contracts\PreOrderLeadInterface::class,
            \App\Services\PreOrderLeadService::class
        );

        $this->app->bind(
            \App\Contracts\ProductGroupRepositoryInterface::class,
            \App\Repository\ProductGroup\ProductGroupRepository::class
        );

        // Big Tech Style Repository Pattern Bindings
        $this->app->bind(
            \App\Contracts\ProductRepositoryInterface::class,
            \App\Repository\ProductRepository::class
        );

        // Service Layer Bindings
        $this->app->singleton(\App\Services\Inventory\InventoryService::class);
        $this->app->singleton(\App\Services\Product\ProductCreationService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        // Register Model Observers
        \App\Models\Order::observe(\App\Observers\OrderObserver::class);
    }
}
