<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\AnalyticsExport;

class AnalyticsController extends Controller
{
    public function index(Request $request)
    {
        // Get date filters if provided
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        // Basic analytics
        $analytics = [
            'today' => $this->getTodayStats(),
            'yesterday' => $this->getYesterdayStats(),
            'thisWeek' => $this->getWeekStats(),
            'thisMonth' => $this->getMonthStats(),
            'averageOrderValue' => $this->getAverageOrderValue($dateFrom, $dateTo),
            'totalProducts' => Product::count(),
            'totalCustomers' => Order::distinct('customer_phone')->count('customer_phone'),
        ];

        // Revenue chart data (last 30 days or custom range)
        $revenueChart = $this->getRevenueChartData($dateFrom, $dateTo);

        // Top 10 selling products
        $topProducts = $this->getTopProducts($dateFrom, $dateTo);

        // Orders by status
        $ordersByStatus = $this->getOrdersByStatus($dateFrom, $dateTo);

        // Courier statistics
        $courierStats = $this->getCourierStats($dateFrom, $dateTo);

        // District-wise stats
        $districtStats = $this->getDistrictStats($dateFrom, $dateTo);

        // Active users (last 15 minutes)
        $activeUsers = $this->getActiveUsers();

        // District-wise user count
        $districtUserStats = $this->getDistrictUserStats();

        // Advanced Analytics
        $hourlySales = $this->getHourlySalesPattern($dateFrom, $dateTo);
        $dayOfWeekAnalysis = $this->getDayOfWeekAnalysis($dateFrom, $dateTo);
        $monthOverMonth = $this->getMonthOverMonthGrowth();
        $salesVelocity = $this->getSalesVelocity();

        // Customer Insights
        $customerRetention = $this->getCustomerRetention();
        $customerLifetimeValue = $this->getCustomerLifetimeValue();
        $customerSegmentation = $this->getCustomerSegmentation();

        // Product Analytics
        $revenueByCategory = $this->getRevenueByCategory($dateFrom, $dateTo);
        $lowStockAlerts = $this->getLowStockAlerts();
        $profitMarginAnalysis = $this->getProfitMarginAnalysis();

        // Financial Metrics
        $paymentMethodDistribution = $this->getPaymentMethodDistribution($dateFrom, $dateTo);
        $refundRate = $this->getRefundRate($dateFrom, $dateTo);

        // Operational Metrics
        $fulfillmentTime = $this->getOrderFulfillmentTime($dateFrom, $dateTo);
        $courierSuccessRate = $this->getCourierSuccessRate($dateFrom, $dateTo);
        $pendingOrderAge = $this->getPendingOrderAge();

        // Marketing Analytics
        $couponUsage = $this->getCouponUsageStatistics($dateFrom, $dateTo);

        // Real-time Dashboard
        $liveOrders = $this->getLiveOrderFeed();
        $todayGoalProgress = $this->getTodayGoalProgress();
        $todayLeaderboard = $this->getTodayLeaderboard();

        return Inertia::render('Admin/Analytics/Index', [
            'analytics' => $analytics,
            'revenueChart' => $revenueChart,
            'topProducts' => $topProducts,
            'ordersByStatus' => $ordersByStatus,
            'courierStats' => $courierStats,
            'districtStats' => $districtStats,
            'activeUsers' => $activeUsers,
            'districtUserStats' => $districtUserStats,
        ]);
    }

    public function advanced(Request $request)
    {
        // Get date filters if provided
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        // Advanced Analytics
        $hourlySales = $this->getHourlySalesPattern($dateFrom, $dateTo);
        $dayOfWeekAnalysis = $this->getDayOfWeekAnalysis($dateFrom, $dateTo);
        $monthOverMonth = $this->getMonthOverMonthGrowth();
        $salesVelocity = $this->getSalesVelocity();

        // Customer Insights
        $customerRetention = $this->getCustomerRetention();
        $customerLifetimeValue = $this->getCustomerLifetimeValue();
        $customerSegmentation = $this->getCustomerSegmentation();

        // Product Analytics
        $revenueByCategory = $this->getRevenueByCategory($dateFrom, $dateTo);
        $lowStockAlerts = $this->getLowStockAlerts();
        $profitMarginAnalysis = $this->getProfitMarginAnalysis();

        // Financial Metrics
        $paymentMethodDistribution = $this->getPaymentMethodDistribution($dateFrom, $dateTo);
        $refundRate = $this->getRefundRate($dateFrom, $dateTo);

        // Operational Metrics
        $fulfillmentTime = $this->getOrderFulfillmentTime($dateFrom, $dateTo);
        $courierSuccessRate = $this->getCourierSuccessRate($dateFrom, $dateTo);
        $pendingOrderAge = $this->getPendingOrderAge();

        // Marketing Analytics
        $couponUsage = $this->getCouponUsageStatistics($dateFrom, $dateTo);

        // Real-time Dashboard
        $liveOrders = $this->getLiveOrderFeed();
        $todayGoalProgress = $this->getTodayGoalProgress();
        $todayLeaderboard = $this->getTodayLeaderboard();

        return Inertia::render('Admin/Analytics/Advanced', [
            // Advanced Analytics
            'hourlySales' => $hourlySales,
            'dayOfWeekAnalysis' => $dayOfWeekAnalysis,
            'monthOverMonth' => $monthOverMonth,
            'salesVelocity' => $salesVelocity,

            // Customer Insights
            'customerRetention' => $customerRetention,
            'customerLifetimeValue' => $customerLifetimeValue,
            'customerSegmentation' => $customerSegmentation,

            // Product Analytics
            'revenueByCategory' => $revenueByCategory,
            'lowStockAlerts' => $lowStockAlerts,
            'profitMarginAnalysis' => $profitMarginAnalysis,

            // Financial Metrics
            'paymentMethodDistribution' => $paymentMethodDistribution,
            'refundRate' => $refundRate,

            // Operational Metrics
            'fulfillmentTime' => $fulfillmentTime,
            'courierSuccessRate' => $courierSuccessRate,
            'pendingOrderAge' => $pendingOrderAge,

            // Marketing Analytics
            'couponUsage' => $couponUsage,

            // Real-time Dashboard
            'liveOrders' => $liveOrders,
            'todayGoalProgress' => $todayGoalProgress,
            'todayLeaderboard' => $todayLeaderboard,
        ]);
    }

    private function getTodayStats()
    {
        return [
            'revenue' => Order::whereDate('created_at', today())
                ->where('status', '!=', 'cancelled')
                ->sum('total'),
            'orders' => Order::whereDate('created_at', today())->count(),
        ];
    }

    private function getYesterdayStats()
    {
        return [
            'revenue' => Order::whereDate('created_at', today()->subDay())
                ->where('status', '!=', 'cancelled')
                ->sum('total'),
            'orders' => Order::whereDate('created_at', today()->subDay())->count(),
        ];
    }

    private function getWeekStats()
    {
        return [
            'revenue' => Order::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
                ->where('status', '!=', 'cancelled')
                ->sum('total'),
            'orders' => Order::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
        ];
    }

    private function getMonthStats()
    {
        return [
            'revenue' => Order::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->where('status', '!=', 'cancelled')
                ->sum('total'),
            'orders' => Order::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
        ];
    }

    private function getDistrictStats($dateFrom = null, $dateTo = null)
    {
        $query = Order::select(
                'shipping_district as district',
                DB::raw('count(*) as count'),
                DB::raw('SUM(total) as revenue')
            )
            ->where('status', '!=', 'cancelled')
            ->where('status', '!=', 'incomplete')
            ->whereNotNull('shipping_district');

        if ($dateFrom && $dateTo) {
            $query->whereBetween('created_at', [$dateFrom, $dateTo]);
        }

        return $query->groupBy('shipping_district')
            ->orderBy('count', 'desc')
            ->limit(20)
            ->get()
            ->toArray();
    }

    private function getActiveUsers()
    {
        // Count users active in last 15 minutes
        return User::where('last_seen_at', '>=', now()->subMinutes(15))->count();
    }

    private function getDistrictUserStats()
    {
        // Get user count by extracting district from address field
        // Since users table doesn't have separate city/district columns,
        // we'll group by the full address or return empty if not needed

        // For now, return an empty array or aggregate from orders table
        // Alternative: Parse addresses or add migration for city/district columns

        return Order::select(
                'shipping_district as district',
                DB::raw('COUNT(DISTINCT customer_phone) as user_count')
            )
            ->whereNotNull('shipping_district')
            ->where('shipping_district', '!=', '')
            ->groupBy('shipping_district')
            ->orderBy('user_count', 'desc')
            ->limit(20)
            ->get()
            ->map(function($item) {
                return [
                    'district' => $item->district,
                    'user_count' => $item->user_count
                ];
            })
            ->toArray();
    }

    public function exportPDF(Request $request)
    {
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $data = [
            'analytics' => [
                'today' => $this->getTodayStats(),
                'yesterday' => $this->getYesterdayStats(),
                'averageOrderValue' => $this->getAverageOrderValue($dateFrom, $dateTo),
                'totalProducts' => Product::count(),
            ],
            'topProducts' => $this->getTopProducts($dateFrom, $dateTo),
            'ordersByStatus' => $this->getOrdersByStatus($dateFrom, $dateTo),
            'courierStats' => $this->getCourierStats($dateFrom, $dateTo),
            'districtStats' => $this->getDistrictStats($dateFrom, $dateTo),
            'dateRange' => $dateFrom && $dateTo ? "$dateFrom to $dateTo" : 'All Time',
        ];

        $pdf = Pdf::loadView('admin.analytics.pdf', $data);

        return $pdf->download('analytics-' . date('Y-m-d') . '.pdf');
    }

    public function exportExcel(Request $request)
    {
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        return Excel::download(
            new AnalyticsExport($dateFrom, $dateTo),
            'analytics-' . date('Y-m-d') . '.xlsx'
        );
    }

    private function getAverageOrderValue($dateFrom = null, $dateTo = null)
    {
        $query = Order::where('status', '!=', 'cancelled')
            ->where('status', '!=', 'incomplete');

        if ($dateFrom && $dateTo) {
            $query->whereBetween('created_at', [$dateFrom, $dateTo]);
        }

        return $query->avg('total') ?? 0;
    }

    private function getRevenueChartData($dateFrom = null, $dateTo = null)
    {
        $startDate = $dateFrom ? Carbon::parse($dateFrom) : now()->subDays(29);
        $endDate = $dateTo ? Carbon::parse($dateTo) : now();

        return Order::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(total) as revenue')
            )
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('status', '!=', 'cancelled')
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get()
            ->map(function ($item) {
                return [
                    'date' => Carbon::parse($item->date)->format('M d'),
                    'revenue' => (float) $item->revenue,
                ];
            })
            ->toArray();
    }

    private function getTopProducts($dateFrom = null, $dateTo = null)
    {
        $query = OrderItem::select(
                'products.id',
                'products.name',
                'products.feature_image as image',
                DB::raw('SUM(order_items.quantity) as sold_count'),
                DB::raw('SUM(order_items.final_price * order_items.quantity) as revenue')
            )
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.status', '!=', 'cancelled')
            ->where('orders.status', '!=', 'incomplete');

        if ($dateFrom && $dateTo) {
            $query->whereBetween('orders.created_at', [$dateFrom, $dateTo]);
        }

        return $query->groupBy('products.id', 'products.name', 'products.feature_image')
            ->orderBy('sold_count', 'desc')
            ->limit(10)
            ->get()
            ->toArray();
    }

    private function getOrdersByStatus($dateFrom = null, $dateTo = null)
    {
        $query = Order::select('status', DB::raw('count(*) as count'))
            ->where('status', '!=', 'incomplete');

        if ($dateFrom && $dateTo) {
            $query->whereBetween('created_at', [$dateFrom, $dateTo]);
        }

        $orders = $query->groupBy('status')->get();
        $total = $orders->sum('count');

        return $orders->map(function ($order) use ($total) {
            return [
                'status' => $order->status,
                'count' => $order->count,
                'percentage' => $total > 0 ? round(($order->count / $total) * 100, 1) : 0,
            ];
        })->toArray();
    }

    private function getCourierStats($dateFrom = null, $dateTo = null)
    {
        $query = Order::where('status', '!=', 'incomplete');

        if ($dateFrom && $dateTo) {
            $query->whereBetween('created_at', [$dateFrom, $dateTo]);
        }

        $total = $query->count();

        $steadfast = Order::where('courier_name', 'steadfast')
            ->where('status', '!=', 'incomplete');

        if ($dateFrom && $dateTo) {
            $steadfast->whereBetween('created_at', [$dateFrom, $dateTo]);
        }
        $steadfast = $steadfast->count();

        $pathao = Order::where('courier_name', 'pathao')
            ->where('status', '!=', 'incomplete');

        if ($dateFrom && $dateTo) {
            $pathao->whereBetween('created_at', [$dateFrom, $dateTo]);
        }
        $pathao = $pathao->count();

        $none = Order::where(function($q) {
                $q->whereNull('courier_name')->orWhere('courier_name', '');
            })
            ->where('status', '!=', 'incomplete');

        if ($dateFrom && $dateTo) {
            $none->whereBetween('created_at', [$dateFrom, $dateTo]);
        }
        $none = $none->count();

        return [
            'steadfast' => [
                'count' => $steadfast,
                'percentage' => $total > 0 ? round(($steadfast / $total) * 100, 1) : 0,
            ],
            'pathao' => [
                'count' => $pathao,
                'percentage' => $total > 0 ? round(($pathao / $total) * 100, 1) : 0,
            ],
            'none' => [
                'count' => $none,
                'percentage' => $total > 0 ? round(($none / $total) * 100, 1) : 0,
            ],
        ];
    }

    // ==================== SALES PERFORMANCE ANALYTICS ====================

    private function getHourlySalesPattern($dateFrom = null, $dateTo = null)
    {
        $query = Order::select(
                DB::raw('HOUR(created_at) as hour'),
                DB::raw('COUNT(*) as orders'),
                DB::raw('SUM(total) as revenue')
            )
            ->where('status', '!=', 'cancelled');

        if ($dateFrom && $dateTo) {
            $query->whereBetween('created_at', [$dateFrom, $dateTo]);
        } else {
            $query->where('created_at', '>=', now()->subDays(30));
        }

        return $query->groupBy('hour')
            ->orderBy('hour')
            ->get()
            ->map(fn($item) => [
                'hour' => $item->hour . ':00',
                'orders' => $item->orders,
                'revenue' => (float) $item->revenue,
            ])
            ->toArray();
    }

    private function getDayOfWeekAnalysis($dateFrom = null, $dateTo = null)
    {
        $query = Order::select(
                DB::raw('DAYNAME(created_at) as day'),
                DB::raw('DAYOFWEEK(created_at) as day_num'),
                DB::raw('COUNT(*) as orders'),
                DB::raw('SUM(total) as revenue')
            )
            ->where('status', '!=', 'cancelled');

        if ($dateFrom && $dateTo) {
            $query->whereBetween('created_at', [$dateFrom, $dateTo]);
        } else {
            $query->where('created_at', '>=', now()->subDays(30));
        }

        return $query->groupBy('day', 'day_num')
            ->orderBy('day_num')
            ->get()
            ->map(fn($item) => [
                'day' => $item->day,
                'orders' => $item->orders,
                'revenue' => (float) $item->revenue,
            ])
            ->toArray();
    }

    private function getMonthOverMonthGrowth()
    {
        $months = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $revenue = Order::whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->where('status', '!=', 'cancelled')
                ->sum('total');

            $orders = Order::whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->count();

            $months[] = [
                'month' => $month->format('M Y'),
                'revenue' => (float) $revenue,
                'orders' => $orders,
            ];
        }
        return $months;
    }

    private function getSalesVelocity()
    {
        return OrderItem::select(
                'products.id',
                'products.name',
                DB::raw('SUM(order_items.quantity) as total_sold'),
                DB::raw('SUM(order_items.quantity) / DATEDIFF(MAX(orders.created_at), MIN(orders.created_at)) as velocity')
            )
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.created_at', '>=', now()->subDays(30))
            ->where('orders.status', '!=', 'cancelled')
            ->groupBy('products.id', 'products.name')
            ->orderBy('velocity', 'desc')
            ->limit(10)
            ->get()
            ->map(fn($item) => [
                'name' => $item->name,
                'total_sold' => $item->total_sold,
                'velocity' => round($item->velocity, 2),
            ])
            ->toArray();
    }

    // ==================== CUSTOMER INSIGHTS ====================

    private function getCustomerRetention()
    {
        $totalCustomers = Order::distinct('customer_phone')->count('customer_phone');
        $returningCustomers = Order::select('customer_phone')
            ->groupBy('customer_phone')
            ->havingRaw('COUNT(*) > 1')
            ->count();

        $newCustomers = $totalCustomers - $returningCustomers;
        $retentionRate = $totalCustomers > 0 ? round(($returningCustomers / $totalCustomers) * 100, 1) : 0;

        return [
            'total' => $totalCustomers,
            'new' => $newCustomers,
            'returning' => $returningCustomers,
            'retention_rate' => $retentionRate,
        ];
    }

    private function getCustomerLifetimeValue()
    {
        $avgOrderValue = Order::where('status', '!=', 'cancelled')->avg('total') ?? 0;
        $avgOrdersPerCustomer = Order::select('customer_phone', DB::raw('COUNT(*) as order_count'))
            ->where('status', '!=', 'cancelled')
            ->groupBy('customer_phone')
            ->get()
            ->avg('order_count') ?? 1;

        $clv = $avgOrderValue * $avgOrdersPerCustomer;

        return [
            'clv' => round($clv, 2),
            'avg_order_value' => round($avgOrderValue, 2),
            'avg_orders_per_customer' => round($avgOrdersPerCustomer, 2),
        ];
    }

    private function getCustomerSegmentation()
    {
        $customers = Order::select(
                'customer_phone',
                'customer_name',
                DB::raw('COUNT(*) as order_count'),
                DB::raw('SUM(total) as total_spent')
            )
            ->where('status', '!=', 'cancelled')
            ->groupBy('customer_phone', 'customer_name')
            ->orderBy('total_spent', 'desc')
            ->limit(20)
            ->get()
            ->toArray();

        $totalRevenue = array_sum(array_column($customers, 'total_spent'));

        return array_map(function($customer) use ($totalRevenue) {
            return [
                'name' => $customer['customer_name'] ?? 'Unknown',
                'phone' => $customer['customer_phone'],
                'orders' => $customer['order_count'],
                'total_spent' => (float) $customer['total_spent'],
                'revenue_percentage' => $totalRevenue > 0 ? round(($customer['total_spent'] / $totalRevenue) * 100, 1) : 0,
            ];
        }, $customers);
    }

    // ==================== PRODUCT ANALYTICS ====================

    private function getRevenueByCategory($dateFrom = null, $dateTo = null)
    {
        $query = OrderItem::select(
                'categories.name as category',
                DB::raw('SUM(order_items.final_price * order_items.quantity) as revenue'),
                DB::raw('SUM(order_items.quantity) as units_sold')
            )
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.status', '!=', 'cancelled');

        if ($dateFrom && $dateTo) {
            $query->whereBetween('orders.created_at', [$dateFrom, $dateTo]);
        }

        return $query->groupBy('categories.name')
            ->orderBy('revenue', 'desc')
            ->get()
            ->map(fn($item) => [
                'category' => $item->category,
                'revenue' => (float) $item->revenue,
                'units_sold' => $item->units_sold,
            ])
            ->toArray();
    }

    private function getLowStockAlerts()
    {
        $thirtyDaysAgo = now()->subDays(30);

        return Product::select(
                'products.id',
                'products.name',
                'inventory_stocks.available_quantity as stock',
                DB::raw('COALESCE(SUM(order_items.quantity), 0) as sold_last_30_days'),
                DB::raw('COALESCE(SUM(order_items.quantity) / 30, 0) as daily_velocity'),
                DB::raw('FLOOR(inventory_stocks.available_quantity / (COALESCE(SUM(order_items.quantity) / 30, 0) + 0.01)) as days_remaining')
            )
            ->leftJoin('inventory_stocks', 'products.id', '=', 'inventory_stocks.product_id')
            ->leftJoin('order_items', 'products.id', '=', 'order_items.product_id')
            ->leftJoin('orders', function($join) use ($thirtyDaysAgo) {
                $join->on('order_items.order_id', '=', 'orders.id')
                     ->where('orders.created_at', '>=', $thirtyDaysAgo)
                     ->where('orders.status', '!=', 'cancelled');
            })
            ->where('products.status', 'active')
            ->whereNotNull('inventory_stocks.available_quantity')
            ->groupBy('products.id', 'products.name', 'inventory_stocks.available_quantity')
            ->havingRaw('days_remaining < 7 OR inventory_stocks.available_quantity < 10')
            ->orderBy('days_remaining')
            ->limit(10)
            ->get()
            ->map(fn($item) => [
                'name' => $item->name,
                'stock' => $item->stock ?? 0,
                'daily_velocity' => round($item->daily_velocity, 2),
                'days_remaining' => (int) $item->days_remaining,
                'alert_level' => $item->days_remaining < 3 ? 'critical' : ($item->days_remaining < 7 ? 'warning' : 'low'),
            ])
            ->toArray();
    }

    private function getProfitMarginAnalysis()
    {
        return OrderItem::select(
                'products.id',
                'products.name',
                DB::raw('SUM(order_items.quantity) as units_sold'),
                DB::raw('SUM(order_items.final_price * order_items.quantity) as revenue'),
                DB::raw('SUM(order_items.cost_price * order_items.quantity) as cost'),
                DB::raw('(SUM(order_items.final_price * order_items.quantity) - SUM(order_items.cost_price * order_items.quantity)) as profit'),
                DB::raw('((SUM(order_items.final_price * order_items.quantity) - SUM(order_items.cost_price * order_items.quantity)) / NULLIF(SUM(order_items.final_price * order_items.quantity), 0) * 100) as margin_percentage')
            )
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.status', '!=', 'cancelled')
            ->whereNotNull('order_items.cost_price')
            ->groupBy('products.id', 'products.name')
            ->orderBy('profit', 'desc')
            ->limit(10)
            ->get()
            ->map(fn($item) => [
                'name' => $item->name,
                'units_sold' => $item->units_sold,
                'revenue' => (float) $item->revenue,
                'cost' => (float) $item->cost,
                'profit' => (float) $item->profit,
                'margin_percentage' => round($item->margin_percentage, 1),
            ])
            ->toArray();
    }

    // ==================== FINANCIAL METRICS ====================

    private function getPaymentMethodDistribution($dateFrom = null, $dateTo = null)
    {
        $query = Order::select(
                'payment_method',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(total) as revenue')
            )
            ->where('status', '!=', 'cancelled');

        if ($dateFrom && $dateTo) {
            $query->whereBetween('created_at', [$dateFrom, $dateTo]);
        }

        $results = $query->groupBy('payment_method')->get();
        $total = $results->sum('count');

        return $results->map(fn($item) => [
            'method' => $item->payment_method ?? 'Cash',
            'count' => $item->count,
            'revenue' => (float) $item->revenue,
            'percentage' => $total > 0 ? round(($item->count / $total) * 100, 1) : 0,
        ])->toArray();
    }

    private function getRefundRate($dateFrom = null, $dateTo = null)
    {
        $query = Order::query();

        if ($dateFrom && $dateTo) {
            $query->whereBetween('created_at', [$dateFrom, $dateTo]);
        }

        $total = (clone $query)->count();
        $cancelled = (clone $query)->where('status', 'cancelled')->count();
        $delivered = (clone $query)->where('status', 'delivered')->count();

        $refundRate = $total > 0 ? round(($cancelled / $total) * 100, 1) : 0;
        $successRate = $total > 0 ? round(($delivered / $total) * 100, 1) : 0;

        return [
            'total_orders' => $total,
            'cancelled' => $cancelled,
            'delivered' => $delivered,
            'refund_rate' => $refundRate,
            'success_rate' => $successRate,
        ];
    }

    // ==================== OPERATIONAL METRICS ====================

    private function getOrderFulfillmentTime($dateFrom = null, $dateTo = null)
    {
        $query = Order::select(
                DB::raw('AVG(TIMESTAMPDIFF(HOUR, created_at, updated_at)) as avg_hours'),
                DB::raw('MIN(TIMESTAMPDIFF(HOUR, created_at, updated_at)) as min_hours'),
                DB::raw('MAX(TIMESTAMPDIFF(HOUR, created_at, updated_at)) as max_hours')
            )
            ->where('status', 'delivered');

        if ($dateFrom && $dateTo) {
            $query->whereBetween('created_at', [$dateFrom, $dateTo]);
        }

        $result = $query->first();

        return [
            'avg_hours' => round($result->avg_hours ?? 0, 1),
            'min_hours' => round($result->min_hours ?? 0, 1),
            'max_hours' => round($result->max_hours ?? 0, 1),
            'avg_days' => round(($result->avg_hours ?? 0) / 24, 1),
        ];
    }

    private function getCourierSuccessRate($dateFrom = null, $dateTo = null)
    {
        $query = Order::whereNotNull('courier_name')
            ->where('courier_name', '!=', '');

        if ($dateFrom && $dateTo) {
            $query->whereBetween('created_at', [$dateFrom, $dateTo]);
        }

        $courierData = [];
        $couriers = ['steadfast', 'pathao'];

        foreach ($couriers as $courier) {
            $total = (clone $query)->where('courier_name', $courier)->count();
            $delivered = (clone $query)->where('courier_name', $courier)
                ->where('status', 'delivered')->count();

            $courierData[] = [
                'courier' => ucfirst($courier),
                'total' => $total,
                'delivered' => $delivered,
                'success_rate' => $total > 0 ? round(($delivered / $total) * 100, 1) : 0,
            ];
        }

        return $courierData;
    }

    private function getPendingOrderAge()
    {
        return Order::select(
                'id',
                'order_number',
                'customer_name',
                'total',
                'status',
                'created_at',
                DB::raw('TIMESTAMPDIFF(HOUR, created_at, NOW()) as age_hours')
            )
            ->whereIn('status', ['pending', 'processing'])
            ->orderBy('created_at', 'asc')
            ->limit(10)
            ->get()
            ->map(fn($item) => [
                'order_number' => $item->order_number,
                'customer' => $item->customer_name,
                'total' => (float) $item->total,
                'status' => $item->status,
                'age_hours' => $item->age_hours,
                'age_days' => round($item->age_hours / 24, 1),
                'alert' => $item->age_hours > 72 ? 'critical' : ($item->age_hours > 48 ? 'warning' : 'normal'),
            ])
            ->toArray();
    }

    // ==================== MARKETING ANALYTICS ====================

    private function getCouponUsageStatistics($dateFrom = null, $dateTo = null)
    {
        // Since orders table doesn't have coupon_code column,
        // we'll use the coupons table usage tracking instead
        $query = \App\Models\Coupon::select(
                'code as coupon_code',
                'uses_count as usage_count',
                'discount_value',
                'discount_type'
            )
            ->where('uses_count', '>', 0)
            ->where('is_active', true);

        // Note: We can't filter by date range since coupons table doesn't track usage timestamps
        // This would require a coupon_usages tracking table for full functionality

        return $query->orderBy('uses_count', 'desc')
            ->limit(10)
            ->get()
            ->map(function($item) {
                // Calculate estimated discount based on average order value
                $avgOrderValue = Order::where('status', '!=', 'cancelled')->avg('total') ?? 0;
                $estimatedDiscount = $item->discount_type === 'percentage'
                    ? ($avgOrderValue * $item->discount_value / 100) * $item->usage_count
                    : $item->discount_value * $item->usage_count;

                return [
                    'coupon' => $item->coupon_code,
                    'usage_count' => $item->usage_count,
                    'total_discount' => round($estimatedDiscount, 2),
                    'revenue_generated' => round($avgOrderValue * $item->usage_count, 2),
                    'avg_order_value' => round($avgOrderValue, 2),
                ];
            })
            ->toArray();
    }

    // ==================== REAL-TIME DASHBOARD ====================

    private function getLiveOrderFeed()
    {
        return Order::select('id', 'order_number', 'customer_name', 'total', 'status', 'created_at')
            ->whereDate('created_at', today())
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(fn($item) => [
                'order_number' => $item->order_number,
                'customer' => $item->customer_name,
                'total' => (float) $item->total,
                'status' => $item->status,
                'time' => $item->created_at->diffForHumans(),
            ])
            ->toArray();
    }

    private function getTodayGoalProgress()
    {
        $monthlyGoal = 100000; // Set this as configurable
        $today = Order::whereDate('created_at', today())
            ->where('status', '!=', 'cancelled')
            ->sum('total');

        $thisMonth = Order::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->where('status', '!=', 'cancelled')
            ->sum('total');

        return [
            'daily_revenue' => (float) $today,
            'daily_goal' => 5000,
            'daily_progress' => round(($today / 5000) * 100, 1),
            'monthly_revenue' => (float) $thisMonth,
            'monthly_goal' => $monthlyGoal,
            'monthly_progress' => round(($thisMonth / $monthlyGoal) * 100, 1),
        ];
    }

    private function getTodayLeaderboard()
    {
        return OrderItem::select(
                'products.id',
                'products.name',
                'products.feature_image as image',
                DB::raw('SUM(order_items.quantity) as sold_today'),
                DB::raw('SUM(order_items.final_price * order_items.quantity) as revenue_today')
            )
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereDate('orders.created_at', today())
            ->where('orders.status', '!=', 'cancelled')
            ->groupBy('products.id', 'products.name', 'products.feature_image')
            ->orderBy('sold_today', 'desc')
            ->limit(5)
            ->get()
            ->map(fn($item) => [
                'name' => $item->name,
                'image' => $item->image,
                'sold_today' => $item->sold_today,
                'revenue_today' => (float) $item->revenue_today,
            ])
            ->toArray();
    }
}
