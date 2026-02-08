<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Carbon\Carbon;

class AnalyticsController extends Controller
{
    public function index()
    {
        // Basic analytics
        $analytics = [
            'today' => $this->getTodayStats(),
            'yesterday' => $this->getYesterdayStats(),
            'thisWeek' => $this->getWeekStats(),
            'thisMonth' => $this->getMonthStats(),
            'averageOrderValue' => $this->getAverageOrderValue(),
            'totalProducts' => Product::count(),
            'totalCustomers' => Order::distinct('customer_phone')->count('customer_phone'),
        ];

        // Revenue chart data (last 30 days)
        $revenueChart = $this->getRevenueChartData();

        // Top 10 selling products
        $topProducts = $this->getTopProducts();

        // Orders by status
        $ordersByStatus = $this->getOrdersByStatus();

        // Courier statistics
        $courierStats = $this->getCourierStats();

        // District-wise stats
        $districtStats = $this->getDistrictStats();

        return Inertia::render('Admin/Analytics/Index', [
            'analytics' => $analytics,
            'revenueChart' => $revenueChart,
            'topProducts' => $topProducts,
            'ordersByStatus' => $ordersByStatus,
            'courierStats' => $courierStats,
            'districtStats' => $districtStats,
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

    private function getAverageOrderValue()
    {
        return Order::where('status', '!=', 'cancelled')
            ->where('status', '!=', 'incomplete')
            ->avg('total') ?? 0;
    }

    private function getRevenueChartData()
    {
        return Order::selectRaw('DATE(created_at) as date, SUM(total) as revenue')
            ->where('created_at', '>=', now()->subDays(30))
            ->where('status', '!=', 'cancelled')
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get()
            ->map(fn($item) => [
                'date' => Carbon::parse($item->date)->format('M d'),
                'revenue' => (float) $item->revenue,
            ])
            ->toArray();
    }

    private function getTopProducts()
    {
        return OrderItem::select(
                'products.id',
                'products.name',
                'products.feature_image as image',
                DB::raw('SUM(order_items.quantity) as sold_count'),
                DB::raw('SUM(order_items.final_price * order_items.quantity) as revenue')
            )
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.status', '!=', 'cancelled')
            ->where('orders.status', '!=', 'incomplete')
            ->groupBy('products.id', 'products.name', 'products.feature_image')
            ->orderBy('sold_count', 'desc')
            ->limit(10)
            ->get()
            ->toArray();
    }

    private function getOrdersByStatus()
    {
        $total = Order::where('status', '!=', 'incomplete')->count();
        
        return Order::select('status', DB::raw('count(*) as count'))
            ->where('status', '!=', 'incomplete')
            ->groupBy('status')
            ->get()
            ->map(function($item) use ($total) {
                return [
                    'status' => $item->status,
                    'count' => $item->count,
                    'percentage' => $total > 0 ? round(($item->count / $total) * 100, 1) : 0,
                ];
            })
            ->toArray();
    }

    private function getCourierStats()
    {
        $total = Order::where('status', '!=', 'incomplete')->count();

        $steadfast = Order::where('courier_name', 'steadfast')
            ->where('status', '!=', 'incomplete')
            ->count();

        $pathao = Order::where('courier_name', 'pathao')
            ->where('status', '!=', 'incomplete')
            ->count();

        $none = Order::whereNull('courier_name')
            ->orWhere('courier_name', '')
            ->where('status', '!=', 'incomplete')
            ->count();

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

    private function getDistrictStats()
    {
        return Order::select(
                'shipping_district as district',
                DB::raw('count(*) as count'),
                DB::raw('SUM(total) as revenue')
            )
            ->where('status', '!=', 'cancelled')
            ->where('status', '!=', 'incomplete')
            ->whereNotNull('shipping_district')
            ->groupBy('shipping_district')
            ->orderBy('count', 'desc')
            ->limit(20)
            ->get()
            ->toArray();
    }
}
