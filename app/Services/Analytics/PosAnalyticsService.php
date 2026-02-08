<?php

namespace App\Services\Analytics;

use App\Models\Order;
use App\Models\Product;
use App\Models\OrderItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class PosAnalyticsService
{
    /**
     * Get real-time POS analytics
     */
    public function getRealTimeAnalytics()
    {
        $cacheKey = 'pos_analytics_' . Carbon::now()->format('Y-m-d-H');

        return Cache::remember($cacheKey, 300, function () { // 5 minutes cache
            $today = Carbon::today();
            $yesterday = Carbon::yesterday();
            $thisWeek = Carbon::now()->startOfWeek();
            $lastWeek = Carbon::now()->subWeek()->startOfWeek();

            return [
                'today' => $this->getTodayStats(),
                'this_week' => $this->getWeekStats($thisWeek),
                'comparison' => $this->getComparisonStats(),
                'popular_products' => $this->getPopularProducts(),
                'sales_timeline' => $this->getSalesTimeline(),
                'payment_methods' => $this->getPaymentMethodStats(),
                'performance_metrics' => $this->getPerformanceMetrics(),
            ];
        });
    }

    /**
     * Get today's statistics
     */
    private function getTodayStats()
    {
        $today = Carbon::today();

        $stats = Order::whereDate('created_at', $today)
            ->selectRaw('
                COUNT(*) as total_orders,
                SUM(total) as revenue,
                AVG(total) as avg_order_value,
                COUNT(DISTINCT user_id) as unique_customers,
                SUM(CASE WHEN payment_status = "paid" THEN 1 ELSE 0 END) as paid_orders
            ')
            ->first();

        $hourlyData = Order::whereDate('created_at', $today)
            ->selectRaw('HOUR(created_at) as hour, COUNT(*) as orders, SUM(total) as revenue')
            ->groupBy('hour')
            ->orderBy('hour')
            ->get()
            ->keyBy('hour');

        // Fill missing hours with 0
        $hourlyStats = collect(range(0, 23))->map(function ($hour) use ($hourlyData) {
            return [
                'hour' => $hour,
                'orders' => $hourlyData->get($hour)?->orders ?? 0,
                'revenue' => $hourlyData->get($hour)?->revenue ?? 0,
            ];
        });

        return [
            'total_orders' => (int) ($stats->total_orders ?? 0),
            'revenue' => (float) ($stats->revenue ?? 0),
            'avg_order_value' => (float) ($stats->avg_order_value ?? 0),
            'unique_customers' => (int) ($stats->unique_customers ?? 0),
            'paid_orders' => (int) ($stats->paid_orders ?? 0),
            'payment_rate' => $stats->total_orders > 0 ? round(($stats->paid_orders / $stats->total_orders) * 100, 1) : 0,
            'hourly_stats' => $hourlyStats,
        ];
    }

    /**
     * Get week statistics
     */
    private function getWeekStats($startDate)
    {
        return Order::whereBetween('created_at', [$startDate, $startDate->copy()->endOfWeek()])
            ->selectRaw('
                COUNT(*) as total_orders,
                SUM(total) as revenue,
                AVG(total) as avg_order_value
            ')
            ->first();
    }

    /**
     * Get comparison statistics
     */
    private function getComparisonStats()
    {
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();

        $todayStats = $this->getTodayStats();
        $yesterdayStats = Order::whereDate('created_at', $yesterday)
            ->selectRaw('COUNT(*) as orders, SUM(total) as revenue')
            ->first();

        $orderGrowth = $yesterdayStats->orders > 0
            ? round((($todayStats['total_orders'] - $yesterdayStats->orders) / $yesterdayStats->orders) * 100, 1)
            : 0;

        $revenueGrowth = $yesterdayStats->revenue > 0
            ? round((($todayStats['revenue'] - $yesterdayStats->revenue) / $yesterdayStats->revenue) * 100, 1)
            : 0;

        return [
            'order_growth' => $orderGrowth,
            'revenue_growth' => $revenueGrowth,
            'yesterday_orders' => (int) $yesterdayStats->orders,
            'yesterday_revenue' => (float) $yesterdayStats->revenue,
        ];
    }

    /**
     * Get popular products
     */
    private function getPopularProducts($limit = 10)
    {
        return OrderItem::with(['product:id,name,price,feature_image'])
            ->whereHas('order', function ($query) {
                $query->whereDate('created_at', Carbon::today());
            })
            ->selectRaw('
                product_id,
                SUM(quantity) as total_sold,
                SUM(quantity * unit_price) as revenue,
                COUNT(*) as order_count
            ')
            ->groupBy('product_id')
            ->orderByDesc('total_sold')
            ->limit($limit)
            ->get()
            ->map(function ($item) {
                return [
                    'product' => $item->product,
                    'total_sold' => (int) $item->total_sold,
                    'revenue' => (float) $item->revenue,
                    'order_count' => (int) $item->order_count,
                ];
            });
    }

    /**
     * Get sales timeline for today
     */
    private function getSalesTimeline()
    {
        return Order::whereDate('created_at', Carbon::today())
            ->selectRaw('
                HOUR(created_at) as hour,
                COUNT(*) as orders,
                SUM(total) as revenue
            ')
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();
    }

    /**
     * Get payment method statistics
     */
    private function getPaymentMethodStats()
    {
        return Order::whereDate('created_at', Carbon::today())
            ->selectRaw('
                payment_method,
                COUNT(*) as count,
                SUM(total) as revenue,
                AVG(total) as avg_value
            ')
            ->groupBy('payment_method')
            ->get()
            ->map(function ($item) {
                return [
                    'method' => $item->payment_method,
                    'count' => (int) $item->count,
                    'revenue' => (float) $item->revenue,
                    'avg_value' => (float) $item->avg_value,
                ];
            });
    }

    /**
     * Get performance metrics
     */
    private function getPerformanceMetrics()
    {
        $todayStart = Carbon::today();
        $now = Carbon::now();

        // Calculate average time between orders
        $orders = Order::whereDate('created_at', $todayStart)
            ->orderBy('created_at')
            ->pluck('created_at');

        $avgTimeBetweenOrders = 0;
        if ($orders->count() > 1) {
            $intervals = [];
            for ($i = 1; $i < $orders->count(); $i++) {
                $intervals[] = $orders[$i]->diffInMinutes($orders[$i-1]);
            }
            $avgTimeBetweenOrders = collect($intervals)->avg();
        }

        // Peak hour
        $peakHour = Order::whereDate('created_at', $todayStart)
            ->selectRaw('HOUR(created_at) as hour, COUNT(*) as orders')
            ->groupBy('hour')
            ->orderByDesc('orders')
            ->first();

        return [
            'avg_time_between_orders' => round($avgTimeBetweenOrders, 1),
            'peak_hour' => $peakHour ? [
                'hour' => $peakHour->hour,
                'orders' => $peakHour->orders
            ] : null,
            'conversion_rate' => $this->calculateConversionRate(),
            'items_per_order' => $this->getAverageItemsPerOrder(),
        ];
    }

    /**
     * Calculate conversion rate (orders vs cart abandonments)
     */
    private function calculateConversionRate()
    {
        // This would require cart tracking - simplified for now
        return 85.2; // Placeholder
    }

    /**
     * Get average items per order
     */
    private function getAverageItemsPerOrder()
    {
        return OrderItem::whereHas('order', function ($query) {
                $query->whereDate('created_at', Carbon::today());
            })
            ->selectRaw('AVG(quantity) as avg_items')
            ->first()
            ->avg_items ?? 0;
    }

    /**
     * Get inventory alerts
     */
    public function getInventoryAlerts()
    {
        return DB::table('inventory_stocks')
            ->join('products', 'products.id', '=', 'inventory_stocks.product_id')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->whereColumn('inventory_stocks.available_quantity', '<=', 'inventory_stocks.minimum_threshold')
            ->where('inventory_stocks.available_quantity', '>', 0)
            ->whereNull('products.deleted_at')
            ->select(
                'products.id',
                'products.name',
                'products.feature_image',
                'categories.name as category_name',
                'inventory_stocks.available_quantity',
                'inventory_stocks.minimum_threshold'
            )
            ->limit(10)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'current_stock' => (int) $item->available_quantity,
                    'threshold' => (int) $item->minimum_threshold,
                    'category' => $item->category_name ?? 'Uncategorized',
                    'image' => $item->feature_image,
                    'urgency' => $item->available_quantity <= ($item->minimum_threshold * 0.5) ? 'critical' : 'warning'
                ];
            });
    }

    /**
     * Get customer insights
     */
    public function getCustomerInsights()
    {
        $todayCustomers = Order::whereDate('created_at', Carbon::today())
            ->with('customer:id,name,email')
            ->selectRaw('
                user_id,
                COUNT(*) as order_count,
                SUM(total) as total_spent,
                MAX(created_at) as last_order
            ')
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->orderByDesc('total_spent')
            ->limit(10)
            ->get();

        return $todayCustomers->map(function ($order) {
            return [
                'user' => $order->customer,
                'order_count' => (int) $order->order_count,
                'total_spent' => (float) $order->total_spent,
                'last_order' => $order->last_order,
                'customer_type' => $order->order_count > 1 ? 'returning' : 'new'
            ];
        });
    }

    /**
     * Clear analytics cache
     */
    public function clearCache()
    {
        Cache::forget('pos_analytics_' . Carbon::now()->format('Y-m-d-H'));
    }
}
