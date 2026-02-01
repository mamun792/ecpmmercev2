<?php

namespace App\Listeners;

use App\Events\OrderCreated;
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
            DB::table('notifications')->insert([
                'type' => 'order_created',
                'order_id' => $order->id,
                'data' => json_encode([
                    'order_number' => $order->order_number,
                    'total' => (string) $order->total,
                    'customer_name' => $order->customer_name,
                ]),
                'is_read' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            Log::info('Order notification created for order ID: ' . $order->id);
        } catch (\Exception $e) {
            Log::error('Failed to create order notification: ' . $e->getMessage());
        }
    } else {
        Log::info('Order notification skipped for incomplete order ID: ' . $order->id);
    }
}

}
