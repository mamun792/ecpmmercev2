<?php

namespace App\Listeners;

use App\Events\OrderCreated;
use App\Models\Order;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Queue\InteractsWithQueue;

class ClearOrderCache
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

    public function handle(OrderCreated $event)
    {
        // Increment the cache version instead of clearing specific keys
        Order::incrementCacheVersion();
        Log::info('Order cache version incremented for order ID: ' . $event->order->id);
    }
}
