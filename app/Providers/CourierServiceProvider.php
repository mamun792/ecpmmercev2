<?php

namespace App\Providers;

use App\Contracts\CourierConfigInterface;
use App\Contracts\OrderTransformerInterface;
use App\Services\Courier\Config\SteadfastConfig;
use App\Contracts\CourierServiceInterface;
use App\Services\Courier\Transformers\SteadfastOrderTransformer;
use App\Services\Courier\CourierManager;
use App\Services\Courier\Http\CourierHttpClient;
use App\Services\Courier\SteadfastCourierService;
use Illuminate\Support\ServiceProvider;


class CourierServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register configuration
        $this->app->bind(CourierConfigInterface::class, SteadfastConfig::class);

        // Register transformer
        $this->app->bind(OrderTransformerInterface::class, SteadfastOrderTransformer::class);

        // Register HTTP client
        $this->app->singleton(CourierHttpClient::class, function ($app) {
            return new CourierHttpClient($app->make(CourierConfigInterface::class));
        });

        // Register main service
        $this->app->bind(CourierServiceInterface::class, SteadfastCourierService::class);

        // Register manager
        $this->app->singleton(CourierManager::class);

        // Register alias
        $this->app->alias(CourierManager::class, 'courier');
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/courier.php' => config_path('courier.php'),
        ], 'courier-config');
    }
}
