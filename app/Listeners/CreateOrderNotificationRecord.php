<?php

namespace App\Listeners;

use App\Events\OrderCreated;
use App\Events\NotificationSent;
use App\Models\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CreateOrderNotificationRecord
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        // No-op
    }

/**
 * Handle the event.
 */
public function handle(OrderCreated $event): void
{
    $order = $event->order;

    // Add condition: only notify if order is not 'incomplete'
    if ($order->status !== 'incomplete') {
        try {
            $notification = Notification::create([
                'type' => 'order_created',
                'order_id' => $order->id,
                'data' => [
                    'order_number' => $order->order_number,
                    'total' => (string) $order->total,
                    'customer_name' => $order->customer_name,
                ],
                'is_read' => false,
            ]);

            // Dispatch real-time broadcast event
            event(new NotificationSent($notification));

            Log::info('Order notification created and broadcasted for order ID: ' . $order->id);
        } catch (\Exception $e) {
            Log::error('Failed to create order notification: ' . $e->getMessage());
        }
    } else {
        Log::info('Order notification skipped for incomplete order ID: ' . $order->id);
    }
}

}
