<?php

namespace App\Services\Reports;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class RevenueReportService
{
    /**
     * Get revenue summary for date range
     */
    public function getRevenueSummary($startDate = null, $endDate = null)
    {
        $startDate = $startDate ? Carbon::parse($startDate)->startOfDay() : Carbon::now()->startOfMonth();
        $endDate = $endDate ? Carbon::parse($endDate)->endOfDay() : Carbon::now()->endOfDay();

        $cacheKey = "revenue_summary_{$startDate->format('Y-m-d')}_{$endDate->format('Y-m-d')}";

        return Cache::remember($cacheKey, 300, function () use ($startDate, $endDate) {
            // Get orders within date range
            $orders = Order::whereBetween('created_at', [$startDate, $endDate])
                ->where('status', '!=', 'cancelled')
                ->get();

            $totalRevenue = $orders->sum('total');
            $totalOrders = $orders->count();
            $avgOrderValue = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;

            // Calculate profit using cost_price
            $profit = $this->calculateProfit($startDate, $endDate);
            $profitMargin = $totalRevenue > 0 ? ($profit['net_profit'] / $totalRevenue) * 100 : 0;

            // Calculate growth rate (compare with previous period)
            $previousPeriod = $this->getPreviousPeriodRevenue($startDate, $endDate);
            $growthRate = $previousPeriod > 0
                ? (($totalRevenue - $previousPeriod) / $previousPeriod) * 100
                : 0;

            return [
                'total_revenue' => round($totalRevenue, 2),
                'net_profit' => round($profit['net_profit'], 2),
                'profit_margin' => round($profitMargin, 2),
                'total_orders' => $totalOrders,
                'avg_order_value' => round($avgOrderValue, 2),
                'growth_rate' => round($growthRate, 2),
                'product_cost' => round($profit['product_cost'], 2),
                'shipping_cost' => round($profit['shipping_cost'], 2),
                'discount_cost' => round($profit['discount_cost'], 2),
            ];
        });
    }

    /**
     * Calculate profit with cost breakdown
     */
    public function calculateProfit($startDate, $endDate)
    {
        $query = DB::table('orders as o')
            ->join('order_items as oi', 'o.id', '=', 'oi.order_id')
            ->join('products as p', 'oi.product_id', '=', 'p.id')
            ->leftJoin('product_variations as pv', 'oi.product_variation_id', '=', 'pv.id')
            ->whereBetween('o.created_at', [$startDate, $endDate])
            ->where('o.status', '!=', 'cancelled')
            ->select([
                DB::raw('SUM(o.total) as total_revenue'),
                DB::raw('SUM(oi.quantity * COALESCE(pv.cost_price, p.cost_price, 0)) as product_cost'),
                DB::raw('SUM(o.shipping_cost) as shipping_cost'),
                DB::raw('SUM(o.discount_total) as discount_cost'),
            ])
            ->first();

        $productCost = $query->product_cost ?? 0;
        $shippingCost = $query->shipping_cost ?? 0;
        $discountCost = $query->discount_cost ?? 0;
        $totalRevenue = $query->total_revenue ?? 0;

        $netProfit = $totalRevenue - $productCost - $shippingCost;

        return [
            'total_revenue' => $totalRevenue,
            'product_cost' => $productCost,
            'shipping_cost' => $shippingCost,
            'discount_cost' => $discountCost,
            'net_profit' => $netProfit,
        ];
    }

    /**
     * Get monthly revenue trends
     */
    public function getMonthlyTrends($period = 12)
    {
        $cacheKey = "monthly_trends_{$period}";

        return Cache::remember($cacheKey, 600, function () use ($period) {
            $startDate = Carbon::now()->subMonths($period)->startOfMonth();

            $trends = DB::table('orders as o')
                ->leftJoin('order_items as oi', 'o.id', '=', 'oi.order_id')
                ->leftJoin('products as p', 'oi.product_id', '=', 'p.id')
                ->leftJoin('product_variations as pv', 'oi.product_variation_id', '=', 'pv.id')
                ->where('o.created_at', '>=', $startDate)
                ->where('o.status', '!=', 'cancelled')
                ->select([
                    DB::raw('DATE_FORMAT(o.created_at, "%Y-%m") as month'),
                    DB::raw('SUM(o.total) as revenue'),
                    DB::raw('SUM(o.subtotal) as subtotal'),
                    DB::raw('SUM(o.shipping_cost) as shipping'),
                    DB::raw('SUM(o.discount_total) as discounts'),
                    DB::raw('COUNT(DISTINCT o.id) as order_count'),
                    DB::raw('SUM(oi.quantity * COALESCE(pv.cost_price, p.cost_price, 0)) as product_cost'),
                ])
                ->groupBy('month')
                ->orderBy('month', 'DESC')
                ->get();

            return $trends->map(function ($trend) {
                $profit = $trend->revenue - ($trend->product_cost ?? 0) - $trend->shipping;
                return [
                    'month' => $trend->month,
                    'revenue' => round($trend->revenue, 2),
                    'profit' => round($profit, 2),
                    'cost' => round(($trend->product_cost ?? 0), 2),
                    'shipping' => round($trend->shipping, 2),
                    'discounts' => round($trend->discounts, 2),
                    'orders' => $trend->order_count,
                ];
            });
        });
    }

    /**
     * Get top products by revenue with profit margins
     */
    public function getProductPerformance($limit = 10, $startDate = null, $endDate = null)
    {
        $startDate = $startDate ? Carbon::parse($startDate)->startOfDay() : Carbon::now()->startOfMonth();
        $endDate = $endDate ? Carbon::parse($endDate)->endOfDay() : Carbon::now()->endOfDay();

        $products = DB::table('order_items as oi')
            ->join('products as p', 'oi.product_id', '=', 'p.id')
            ->join('orders as o', 'oi.order_id', '=', 'o.id')
            ->leftJoin('product_variations as pv', 'oi.product_variation_id', '=', 'pv.id')
            ->whereBetween('o.created_at', [$startDate, $endDate])
            ->where('o.status', '!=', 'cancelled')
            ->select([
                'p.id',
                'p.name',
                'p.feature_image',
                DB::raw('SUM(oi.quantity) as units_sold'),
                DB::raw('SUM(oi.final_price) as revenue'),
                DB::raw('SUM(oi.quantity * COALESCE(pv.cost_price, p.cost_price, 0)) as total_cost'),
                DB::raw('SUM(oi.quantity * (oi.unit_price - COALESCE(pv.cost_price, p.cost_price, 0))) as profit'),
            ])
            ->groupBy('p.id', 'p.name', 'p.feature_image')
            ->orderByDesc('revenue')
            ->limit($limit)
            ->get();

        return $products->map(function ($product, $index) {
            $marginPercentage = $product->revenue > 0
                ? ($product->profit / $product->revenue) * 100
                : 0;

            return [
                'rank' => $index + 1,
                'id' => $product->id,
                'name' => $product->name,
                'image' => $product->feature_image,
                'units_sold' => $product->units_sold,
                'revenue' => round($product->revenue, 2),
                'cost' => round($product->total_cost, 2),
                'profit' => round($product->profit, 2),
                'margin_percentage' => round($marginPercentage, 2),
            ];
        });
    }

    /**
     * Get top customers by revenue
     */
    public function getCustomerAnalytics($limit = 20, $startDate = null, $endDate = null)
    {
        $startDate = $startDate ? Carbon::parse($startDate)->startOfDay() : Carbon::now()->startOfMonth();
        $endDate = $endDate ? Carbon::parse($endDate)->endOfDay() : Carbon::now()->endOfDay();

        $customers = Order::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', '!=', 'cancelled')
            ->select([
                DB::raw('COALESCE(customer_name, "Guest") as customer'),
                'customer_phone',
                DB::raw('COUNT(*) as order_count'),
                DB::raw('SUM(total) as total_revenue'),
                DB::raw('ROUND(AVG(total), 2) as avg_order_value'),
            ])
            ->groupBy('customer_name', 'customer_phone')
            ->orderByDesc('total_revenue')
            ->limit($limit)
            ->get();

        return $customers->map(function ($customer, $index) {
            return [
                'rank' => $index + 1,
                'name' => $customer->customer,
                'phone' => $customer->customer_phone,
                'orders' => $customer->order_count,
                'revenue' => round($customer->total_revenue, 2),
                'avg_order' => round($customer->avg_order_value, 2),
            ];
        });
    }

    /**
     * Get payment method breakdown
     */
    public function getPaymentMethodBreakdown($startDate = null, $endDate = null)
    {
        $startDate = $startDate ? Carbon::parse($startDate)->startOfDay() : Carbon::now()->startOfMonth();
        $endDate = $endDate ? Carbon::parse($endDate)->endOfDay() : Carbon::now()->endOfDay();

        $totalRevenue = Order::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', '!=', 'cancelled')
            ->sum('total');

        $breakdown = Order::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', '!=', 'cancelled')
            ->select([
                'payment_method',
                DB::raw('SUM(total) as revenue'),
                DB::raw('COUNT(*) as orders'),
            ])
            ->groupBy('payment_method')
            ->orderByDesc('revenue')
            ->get();

        return $breakdown->map(function ($item) use ($totalRevenue) {
            $percentage = $totalRevenue > 0 ? ($item->revenue / $totalRevenue) * 100 : 0;
            return [
                'method' => $item->payment_method ?? 'Unknown',
                'revenue' => round($item->revenue, 2),
                'orders' => $item->orders,
                'percentage' => round($percentage, 2),
            ];
        });
    }

    /**
     * Get order status breakdown
     */
    public function getOrderStatusBreakdown($startDate = null, $endDate = null)
    {
        $startDate = $startDate ? Carbon::parse($startDate)->startOfDay() : Carbon::now()->startOfMonth();
        $endDate = $endDate ? Carbon::parse($endDate)->endOfDay() : Carbon::now()->endOfDay();

        $breakdown = Order::whereBetween('created_at', [$startDate, $endDate])
            ->select([
                'status',
                DB::raw('COUNT(*) as order_count'),
                DB::raw('SUM(total) as revenue'),
            ])
            ->groupBy('status')
            ->orderByRaw("
                CASE status
                    WHEN 'completed' THEN 1
                    WHEN 'processing' THEN 2
                    WHEN 'pending' THEN 3
                    WHEN 'cancelled' THEN 4
                    ELSE 5
                END
            ")
            ->get();

        return $breakdown->map(function ($item) {
            return [
                'status' => $item->status,
                'orders' => $item->order_count,
                'revenue' => round($item->revenue, 2),
            ];
        });
    }

    /**
     * Get revenue by location/area
     */
    public function getLocationBreakdown($startDate = null, $endDate = null)
    {
        $startDate = $startDate ? Carbon::parse($startDate)->startOfDay() : Carbon::now()->startOfMonth();
        $endDate = $endDate ? Carbon::parse($endDate)->endOfDay() : Carbon::now()->endOfDay();

        $breakdown = Order::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', '!=', 'cancelled')
            ->select([
                'area',
                DB::raw('COUNT(*) as order_count'),
                DB::raw('SUM(total) as revenue'),
            ])
            ->groupBy('area')
            ->orderByDesc('revenue')
            ->get();

        return $breakdown->map(function ($item) {
            return [
                'area' => $item->area ?? 'Unknown',
                'orders' => $item->order_count,
                'revenue' => round($item->revenue, 2),
            ];
        });
    }

    /**
     * Get daily revenue for last N days
     */
    public function getDailyRevenue($days = 30)
    {
        $startDate = Carbon::now()->subDays($days)->startOfDay();

        $daily = Order::where('created_at', '>=', $startDate)
            ->where('status', '!=', 'cancelled')
            ->select([
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(total) as revenue'),
                DB::raw('COUNT(*) as orders'),
            ])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return $daily->map(function ($day) {
            return [
                'date' => $day->date,
                'revenue' => round($day->revenue, 2),
                'orders' => $day->orders,
            ];
        });
    }

    /**
     * Get previous period revenue for comparison
     */
    private function getPreviousPeriodRevenue($startDate, $endDate)
    {
        $days = $startDate->diffInDays($endDate);
        $previousStart = $startDate->copy()->subDays($days + 1);
        $previousEnd = $startDate->copy()->subDay();

        return Order::whereBetween('created_at', [$previousStart, $previousEnd])
            ->where('status', '!=', 'cancelled')
            ->sum('total');
    }
}
