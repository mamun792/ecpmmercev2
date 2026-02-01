<?php

namespace App\Observers;

use App\Models\Order;
use Illuminate\Support\Facades\Log;

class OrderObserver
{
    /**
     * Handle the Order "created" event.
     */
    public function created(Order $order): void
    {
        Log::info('Order created', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'total' => $order->total,
        ]);

        // Dispatch OrderCreated event
        event(new \App\Events\Orders\OrderCreated($order));

        // Auto invalidate cache
        Order::invalidateCache();
    }

    /**
     * Handle the Order "updated" event.
     */
    public function updated(Order $order): void
    {
        $changes = $order->getChanges();

        // Log significant changes
        if (isset($changes['status']) || isset($changes['payment_status'])) {
            Log::info('Order status changed', [
                'order_id' => $order->id,
                'changes' => $changes,
            ]);
        }

        // Auto invalidate cache
        Order::invalidateCache();
    }

    /**
     * Handle the Order "deleted" event.
     */
    public function deleted(Order $order): void
    {
        Log::info('Order deleted', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
        ]);

        // Auto invalidate cache
        Order::invalidateCache();
    }

    /**
     * Handle the Order "restored" event.
     */
    public function restored(Order $order): void
    {
        Log::info('Order restored', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
        ]);

        // Auto invalidate cache
        Order::invalidateCache();
    }

    /**
     * Handle the Order "force deleted" event.
     */
    public function forceDeleted(Order $order): void
    {
        Log::info('Order permanently deleted', [
            'order_id' => $order->id,
        ]);

        // Auto invalidate cache
        Order::invalidateCache();
    }
}
