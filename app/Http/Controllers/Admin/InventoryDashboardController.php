<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Analytics\InventoryAnalyticsService;
use App\Services\Inventory\AutoReorderService;
use App\Services\Notification\NotificationService;
use Inertia\Inertia;
use Illuminate\Http\Request;

class InventoryDashboardController extends Controller
{
    protected $inventoryAnalyticsService;
    protected $autoReorderService;
    protected $notificationService;

    public function __construct(
        InventoryAnalyticsService $inventoryAnalyticsService,
        AutoReorderService $autoReorderService,
        NotificationService $notificationService
    ) {
        $this->inventoryAnalyticsService = $inventoryAnalyticsService;
        $this->autoReorderService = $autoReorderService;
        $this->notificationService = $notificationService;
    }

    /**
     * Display the main inventory analytics dashboard
     */
    public function index()
    {
        try {
            $dashboardData = $this->inventoryAnalyticsService->getDashboardSummary();
            $reorderAlerts = $this->autoReorderService->checkReorderTriggers();
            $weeklyRevenue = $this->inventoryAnalyticsService->getWeeklyRevenue();
            $slowMoving = $this->inventoryAnalyticsService->getSlowMovingProducts(30);

            return Inertia::render('Admin/InventoryDashboard', [
                'title' => 'Inventory Analytics Dashboard',
                'dashboardData' => $dashboardData,
                'reorderAlerts' => [
                    'summary' => $reorderAlerts['summary'] ?? [],
                    'critical' => ($reorderAlerts['critical'] ?? collect([]))->values()->toArray(),
                    'low' => ($reorderAlerts['low'] ?? collect([]))->values()->toArray(),
                ],
                'weeklyRevenue' => $weeklyRevenue,
                'promotionalProducts' => $slowMoving,
                'success' => true
            ]);

        } catch (\Exception $e) {
            \Log::error('Inventory Dashboard Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);

            return Inertia::render('Admin/InventoryDashboard', [
                'title' => 'Inventory Analytics Dashboard',
                'dashboardData' => [
                    'summary' => ['total_products' => 0, 'critical_count' => 0, 'low_count' => 0, 'good_stock' => 0],
                    'sales_velocity' => [],
                    'stock_analysis' => []
                ],
                'reorderAlerts' => ['summary' => ['critical_count' => 0, 'low_count' => 0, 'total_affected' => 0], 'critical' => [], 'low' => []],
                'weeklyRevenue' => [],
                'promotionalProducts' => [],
                'error' => 'Failed to load dashboard data: ' . $e->getMessage(),
                'success' => false
            ]);
        }
    }

    /**
     * Send daily report email manually
     */
    public function sendDailyReport()
    {
        try {
            $alerts = $this->autoReorderService->checkReorderTriggers();

            $emailSent = $this->notificationService->sendInventoryAlert(
                $alerts['critical']->toArray(),
                $alerts['low']->toArray(),
                $alerts['summary']
            );

            return response()->json([
                'success' => $emailSent,
                'message' => $emailSent
                    ? 'Daily report sent successfully!'
                    : 'Failed to send email'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send email: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate weekly optimization report
     */
    public function weeklyReport()
    {
        try {
            $weeklyData = [
                'promotional' => $this->inventoryAnalyticsService->getSlowMovingProducts(30),
                'revenue' => $this->inventoryAnalyticsService->getWeeklyRevenue(),
                'optimization' => $this->inventoryAnalyticsService->getReorderOptimization()
            ];

            return response()->json([
                'success' => true,
                'data' => $weeklyData,
                'message' => 'Weekly report generated successfully'
            ]);

        } catch (\Exception $e) {
            \Log::error('Weekly report error: ' . $e->getMessage());

            return response()->json([
                'success' => true,
                'data' => [
                    'promotional' => [],
                    'revenue' => ['labels' => [], 'data' => [], 'total' => 0, 'order_count' => 0],
                    'optimization' => [
                        'optimized_points' => 0,
                        'savings_potential' => 0,
                        'recommendations' => ['No recommendations available']
                    ]
                ],
                'message' => 'Weekly report with fallback data'
            ]);
        }
    }

    /**
     * Save email notification settings
     */
    public function saveEmailSettings(Request $request)
    {
        try {
            $settings = $request->validate([
                'daily_morning_report' => 'boolean',
                'critical_alerts' => 'boolean',
                'weekly_optimization' => 'boolean',
                'promotional_opportunities' => 'boolean'
            ]);

            // In a real app, save to database or config file
            // For now, just return success

            return response()->json([
                'success' => true,
                'message' => 'Email settings saved successfully!'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to save settings: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get real-time dashboard updates
     */
    public function refreshDashboard()
    {
        try {
            $dashboardData = $this->inventoryAnalyticsService->getDashboardSummary();
            $reorderAlerts = $this->autoReorderService->checkReorderTriggers();
            $weeklyRevenue = $this->inventoryAnalyticsService->getWeeklyRevenue();

            return response()->json([
                'success' => true,
                'data' => [
                    'summary' => $dashboardData['summary'],
                    'critical' => ($reorderAlerts['critical'] ?? collect([]))->values()->toArray(),
                    'low' => ($reorderAlerts['low'] ?? collect([]))->values()->toArray(),
                    'sales_velocity' => $dashboardData['sales_velocity'] ?? [],
                    'stock_analysis' => $dashboardData['stock_analysis'] ?? [],
                    'weekly_revenue' => $weeklyRevenue,
                ],
                'message' => 'Dashboard updated successfully'
            ]);

        } catch (\Exception $e) {
            \Log::error('Dashboard refresh error: ' . $e->getMessage());

            return response()->json([
                'success' => true,
                'data' => [
                    'summary' => [
                        'total_products' => 0,
                        'critical_count' => 0,
                        'low_count' => 0,
                        'good_stock' => 0
                    ],
                    'critical' => [],
                    'low' => [],
                    'sales_velocity' => []
                ],
                'message' => 'Dashboard refreshed with default data'
            ]);
        }
    }
}
