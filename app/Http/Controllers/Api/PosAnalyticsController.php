<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Analytics\PosAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PosAnalyticsController extends Controller
{
    protected $analyticsService;

    public function __construct(PosAnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    /**
     * Get real-time POS analytics dashboard data
     */
    public function dashboard(): JsonResponse
    {
        try {
            $analytics = $this->analyticsService->getRealTimeAnalytics();

            return response()->json([
                'success' => true,
                'data' => $analytics,
                'timestamp' => now()->toISOString(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch analytics data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get inventory alerts
     */
    public function inventoryAlerts(): JsonResponse
    {
        try {
            $alerts = $this->analyticsService->getInventoryAlerts();

            return response()->json([
                'success' => true,
                'data' => $alerts,
                'count' => $alerts->count(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch inventory alerts',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get customer insights
     */
    public function customerInsights(): JsonResponse
    {
        try {
            $insights = $this->analyticsService->getCustomerInsights();

            return response()->json([
                'success' => true,
                'data' => $insights,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch customer insights',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Refresh analytics cache
     */
    public function refreshCache(): JsonResponse
    {
        try {
            $this->analyticsService->clearCache();

            return response()->json([
                'success' => true,
                'message' => 'Analytics cache refreshed successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to refresh cache',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
