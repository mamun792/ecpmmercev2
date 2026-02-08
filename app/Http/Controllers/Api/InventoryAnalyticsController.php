<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Inventory\AutoReorderService;
use App\Services\Analytics\InventoryAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class InventoryAnalyticsController extends Controller
{
    protected $autoReorderService;
    protected $analyticsService;

    public function __construct(
        AutoReorderService $autoReorderService,
        InventoryAnalyticsService $analyticsService
    ) {
        $this->autoReorderService = $autoReorderService;
        $this->analyticsService = $analyticsService;
    }

    /**
     * Get inventory analytics dashboard
     */
    public function dashboard(Request $request): JsonResponse
    {
        try {
            $period = $request->get('period', '30days');
            $analytics = $this->analyticsService->getInventoryAnalytics($period);

            return response()->json([
                'success' => true,
                'data' => $analytics,
                'period' => $period,
                'timestamp' => now()->toISOString()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch inventory analytics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get reorder alerts and recommendations
     */
    public function reorderAlerts(): JsonResponse
    {
        try {
            $alerts = $this->autoReorderService->checkReorderTriggers();

            return response()->json([
                'success' => true,
                'data' => $alerts,
                'timestamp' => now()->toISOString()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch reorder alerts',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate automatic purchase orders
     */
    public function generatePurchaseOrders(): JsonResponse
    {
        try {
            $orders = $this->autoReorderService->generateAutoPurchaseOrders();

            return response()->json([
                'success' => true,
                'data' => $orders,
                'count' => count($orders),
                'message' => count($orders) > 0 ?
                    'Generated ' . count($orders) . ' purchase order recommendations' :
                    'No purchase orders needed at this time'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate purchase orders',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Optimize reorder points based on sales data
     */
    public function optimizeReorderPoints(): JsonResponse
    {
        try {
            $optimizations = $this->autoReorderService->optimizeReorderPoints();

            return response()->json([
                'success' => true,
                'data' => $optimizations,
                'count' => count($optimizations),
                'message' => count($optimizations) > 0 ?
                    'Optimized ' . count($optimizations) . ' product reorder points' :
                    'All reorder points are already optimal'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to optimize reorder points',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get sales velocity analysis
     */
    public function salesVelocity(Request $request): JsonResponse
    {
        try {
            $period = $request->get('period', '30days');
            $analytics = $this->analyticsService->getInventoryAnalytics($period);

            return response()->json([
                'success' => true,
                'data' => $analytics['sales_velocity'],
                'period' => $period
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch sales velocity data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get demand forecasting
     */
    public function demandForecast(): JsonResponse
    {
        try {
            $analytics = $this->analyticsService->getInventoryAnalytics('90days');

            return response()->json([
                'success' => true,
                'data' => $analytics['demand_forecast'],
                'seasonal_patterns' => $analytics['seasonal_patterns']
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch demand forecast',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get turnover analysis
     */
    public function turnoverAnalysis(Request $request): JsonResponse
    {
        try {
            $period = $request->get('period', '30days');
            $analytics = $this->analyticsService->getInventoryAnalytics($period);

            return response()->json([
                'success' => true,
                'data' => $analytics['turnover_analysis'],
                'stock_performance' => $analytics['stock_performance'],
                'period' => $period
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch turnover analysis',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
