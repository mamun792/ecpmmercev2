<?php

namespace App\Console\Commands;

use App\Contracts\CourierServiceInterface;
use Illuminate\Console\Command;

use App\Models\Order;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Throwable;

class CheckOrderDeliveryStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-order-delivery-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch and update delivery status for all orders with a consignment_id';

    protected CourierServiceInterface $courierService;

    public function __construct(CourierServiceInterface $courierService)
    {
        parent::__construct();
        $this->courierService = $courierService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {

        $orders = Order::whereNotNull('consignment_id')->get();
        Log::info("Found {$orders->count()} orders with consignment_id.");
        foreach ($orders as $order) {
            try {
                // 1) Fetch
                $response = $this->courierService->getStatusByConsignmentId($order->consignment_id);



                // 3) Make sure we got back an array
                if (! is_array($response)) {
                    // skip anything that's not a valid response
                    Log::warning("Order #{$order->id} returned non-array response: " . json_encode($response));
                    continue;
                }

                // 4) Extract the actual delivery_status
                $newDeliveryStatus = $response['delivery_status'] ?? null;
                Log::debug("Order #{$order->id} new  delivery_status: {$newDeliveryStatus}");

                // 5) If it’s changed, persist & log
                if ($newDeliveryStatus !== null && $newDeliveryStatus !== $order->delivery_status) {
                    $order->update(['delivery_status' => $newDeliveryStatus]);
                    Log::info("✅ Order #{$order->id} updated to '{$newDeliveryStatus}'");
                }
            } catch (Throwable $e) {
                // 6) Catch & log any transport/parsing errors
                Log::error("Failed to fetch status for Order #{$order->id}: {$e->getMessage()}");
            }
        }
        // 2. Loop through each one
        $this->info("✅ Checked statuses for {$orders->count()} orders.");
        return self::SUCCESS;
    }
}
