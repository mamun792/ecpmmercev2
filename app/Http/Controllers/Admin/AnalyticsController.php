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
}
