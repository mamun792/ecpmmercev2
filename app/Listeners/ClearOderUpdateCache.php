<?php

namespace App\Listeners;

use App\Events\OrderUpdated;
use App\Models\Order;


class ClearOderUpdateCache
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(OrderUpdated $event)
    {
        // Increment the cache version instead of clearing specific keys
        Order::incrementCacheVersion();
        // Log::info('Order cache version incremented for order ID: ' . $event->order->id);
    }
}
