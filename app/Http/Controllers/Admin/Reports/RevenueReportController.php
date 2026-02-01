<?php

namespace App\Http\Controllers\Admin\Reports;

use App\Http\Controllers\Controller;
use App\Services\Reports\RevenueReportService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class RevenueReportController extends Controller
{
    protected $revenueService;

    public function __construct(RevenueReportService $revenueService)
    {
        $this->revenueService = $revenueService;
    }

    /**
     * Display the revenue dashboard
     */
    public function dashboard(Request $request)
    {
        // Parse dates - use "All Time" style range by default for better initial view
        $startDate = $request->get('start_date', '2020-01-01');
        $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));

        // Clear ALL revenue-related caches for fresh data (disable in production for performance)
        \Illuminate\Support\Facades\Cache::forget("revenue_summary_{$startDate}_{$endDate}");
        \Illuminate\Support\Facades\Cache::forget('monthly_trends_12');
        \Illuminate\Support\Facades\Cache::forget('daily_revenue_30');

        $data = [
            'summary' => $this->revenueService->getRevenueSummary($startDate, $endDate),
            'monthly_trends' => $this->revenueService->getMonthlyTrends(12),
            'daily_revenue' => $this->revenueService->getDailyRevenue(30),
            'top_products' => $this->revenueService->getProductPerformance(10, $startDate, $endDate),
            'top_customers' => $this->revenueService->getCustomerAnalytics(20, $startDate, $endDate),
            'payment_breakdown' => $this->revenueService->getPaymentMethodBreakdown($startDate, $endDate),
            'status_breakdown' => $this->revenueService->getOrderStatusBreakdown($startDate, $endDate),
            'location_breakdown' => $this->revenueService->getLocationBreakdown($startDate, $endDate),
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
        ];

        return Inertia::render('Admin/Reports/Revenue', $data);
    }

    /**
     * Get monthly revenue data (AJAX)
     */
    public function getMonthlyRevenue(Request $request)
    {
        $period = $request->get('period', 12);
        return response()->json([
            'monthly_trends' => $this->revenueService->getMonthlyTrends($period)
        ]);
    }

    /**
     * Get top products data (AJAX)
     */
    public function getTopProducts(Request $request)
    {
        $limit = $request->get('limit', 10);
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        return response()->json([
            'top_products' => $this->revenueService->getProductPerformance($limit, $startDate, $endDate)
        ]);
    }

    /**
     * Get top customers data (AJAX)
     */
    public function getTopCustomers(Request $request)
    {
        $limit = $request->get('limit', 20);
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        return response()->json([
            'top_customers' => $this->revenueService->getCustomerAnalytics($limit, $startDate, $endDate)
        ]);
    }

    /**
     * Export revenue report (PDF)
     */
    public function export(Request $request)
    {
        $startDate = $request->get('start_date', '2020-01-01');
        $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));

        $data = [
            'summary' => $this->revenueService->getRevenueSummary($startDate, $endDate),
            'top_products' => $this->revenueService->getProductPerformance(50, $startDate, $endDate),
            'top_customers' => $this->revenueService->getCustomerAnalytics(50, $startDate, $endDate),
            'payment_breakdown' => $this->revenueService->getPaymentMethodBreakdown($startDate, $endDate),
            'status_breakdown' => $this->revenueService->getOrderStatusBreakdown($startDate, $endDate),
            'startDate' => $startDate,
            'endDate' => $endDate,
        ];

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.revenue-pdf', $data);
        $pdf->setPaper('a4', 'portrait');

        $filename = 'revenue-report-' . $startDate . '-to-' . $endDate . '.pdf';

        return $pdf->download($filename);
    }
}
