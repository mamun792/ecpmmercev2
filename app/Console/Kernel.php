<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Check inventory reorder triggers every hour during business hours
        $schedule->command('inventory:check-reorder')
                 ->hourlyAt(0)
                 ->between('09:00', '18:00')
                 ->weekdays()
                 ->runInBackground();

        // Run comprehensive inventory analytics daily at 6 AM
        $schedule->command('inventory:check-reorder --notify=true')
                 ->dailyAt('06:00')
                 ->runInBackground();

        // Optimize reorder points weekly on Sunday at 2 AM
        $schedule->call(function () {
            $autoReorderService = app(\App\Services\Inventory\AutoReorderService::class);
            $optimizations = $autoReorderService->optimizeReorderPoints();

            if (count($optimizations) > 0) {
                \Log::info('Weekly reorder point optimization completed', [
                    'count' => count($optimizations),
                    'optimizations' => $optimizations
                ]);
            }
        })->weeklyOn(0, '02:00');

        // Generate promotional suggestions for slow-moving inventory weekly
        $schedule->call(function () {
            $analyticsService = app(\App\Services\Analytics\InventoryAnalyticsService::class);
            $analytics = $analyticsService->getInventoryAnalytics('30days');

            // Find slow-moving products (low velocity)
            $slowMoving = collect($analytics['sales_velocity'])
                ->where('velocity_rating', 'low')
                ->where('total_sold', '>', 0)
                ->take(10);

            if ($slowMoving->count() > 0) {
                $notificationService = app(\App\Services\Notification\NotificationService::class);
                $notificationService->sendPromotionalSuggestion($slowMoving->toArray());
            }
        })->weeklyOn(1, '09:00'); // Monday at 9 AM
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
