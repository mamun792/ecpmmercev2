<?php

namespace App\Providers;

use App\Repository\Order\OrderRepositoryInterface;
use App\Repository\Order\OrderRepository;
use App\Services\Order\OrderService;
use App\Services\Order\OrderInterface;
use Illuminate\Support\ServiceProvider;

class OrderServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(OrderRepositoryInterface::class, OrderRepository::class);
        $this->app->bind(OrderInterface::class, OrderService::class);
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
