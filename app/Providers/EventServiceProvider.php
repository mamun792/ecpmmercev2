<?php

namespace App\Providers;

use App\Events\OrderCreated;
use App\Events\OrderUpdated;
use App\Listeners\ClearOrderCache;
use App\Listeners\CreateOrderNotificationRecord;
use App\Listeners\ClearOrderUpdateCache;
use Illuminate\Support\ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */

    /**
     * The event-to-listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        OrderCreated::class => [
            ClearOrderCache::class,
            CreateOrderNotificationRecord::class,
        ],

        // Add other events and listeners here
        OrderUpdated::class => [
            ClearOrderUpdateCache::class,
        ],
    ];
    public function register(): void
    {
        // Register any application services.
        // You can also register bindings, singletons, etc. here.
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
