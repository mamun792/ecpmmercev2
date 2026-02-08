<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Inventory\AutoReorderService;

class CheckInventoryReorder extends Command
{
    protected $signature = 'inventory:check-reorder {--notify=true}';
    protected $description = 'Check inventory levels and trigger reorder alerts';

    protected $autoReorderService;

    public function __construct(AutoReorderService $autoReorderService)
    {
        parent::__construct();
        $this->autoReorderService = $autoReorderService;
    }

    public function handle()
    {
        $this->info('🔍 Checking inventory reorder triggers...');

        try {
            $alerts = $this->autoReorderService->checkReorderTriggers();

            $this->info('📊 Inventory Check Results:');
            $this->table(
                ['Metric', 'Count'],
                [
                    ['Critical Stock Items', $alerts['summary']['critical_count']],
                    ['Low Stock Items', $alerts['summary']['low_count']],
                    ['Total Affected', $alerts['summary']['total_affected']]
                ]
            );

            if ($alerts['summary']['critical_count'] > 0) {
                $this->error('🚨 CRITICAL: ' . $alerts['summary']['critical_count'] . ' products are out of stock!');

                foreach ($alerts['critical']->take(5) as $product) {
                    $this->warn("  • {$product['name']} - Stock: {$product['current_stock']} (Min: {$product['minimum_threshold']})");
                }
            }

            if ($alerts['summary']['low_count'] > 0) {
                $this->warn('⚠️  LOW STOCK: ' . $alerts['summary']['low_count'] . ' products need restocking soon');
            }

            if ($alerts['summary']['total_affected'] == 0) {
                $this->info('✅ All products have adequate stock levels');
            }

            // Send email notifications if enabled and there are issues
            if ($this->option('notify') === 'true' && $alerts['summary']['total_affected'] > 0) {
                $this->info('📧 Sending email notifications...');

                $notificationService = app(\App\Services\Notification\NotificationService::class);
                $emailSent = $notificationService->sendInventoryAlert(
                    $alerts['critical']->toArray(),
                    $alerts['low']->toArray(),
                    $alerts['summary']
                );

                if ($emailSent) {
                    $this->info('✅ Email notifications sent successfully!');
                    $this->info('📧 Check your Mailtrap inbox at https://mailtrap.io/inboxes');
                } else {
                    $this->warn('⚠️  Failed to send email notifications (check logs)');
                }
            }

            // Auto-generate purchase orders for critical items
            if ($this->option('notify') === 'true') {
                $this->info('📋 Generating automatic purchase order recommendations...');
                $orders = $this->autoReorderService->generateAutoPurchaseOrders();

                if (count($orders) > 0) {
                    $this->info('📄 Generated ' . count($orders) . ' purchase order recommendations');
                    $this->table(
                        ['Product', 'Current Stock', 'Order Qty', 'Est. Cost'],
                        collect($orders)->map(function ($order) {
                            return [
                                $order['product_name'],
                                $order['current_stock'],
                                $order['order_quantity'],
                                '৳' . number_format($order['estimated_cost'], 2)
                            ];
                        })->toArray()
                    );
                } else {
                    $this->info('✅ No purchase orders needed at this time');
                }
            }

        } catch (\Exception $e) {
            $this->error('❌ Error checking inventory: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
