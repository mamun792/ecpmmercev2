<?php

namespace App\Http\Controllers\Admin\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Order;
// carbon
use Carbon\Carbon;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        // Summary Metrics
        $totalSales = Order::sum('total');
        $deliveredSales = Order::where('status', 'delivered')->sum('total');
        $totalOrders = Order::count();
        $totalCustomers = User::count();
        $pendingOrders = Order::where('status', 'pending')->count();

        // Compute simple period-over-period changes (use 7-day windows for sales/orders, 30-day for customers)
        $now = Carbon::now();

        // Sales change (last 7 days vs previous 7 days)
        $salesCurrentStart = $now->copy()->subDays(6)->startOfDay();
        $salesCurrentEnd = $now->copy()->endOfDay();
        $salesPrevStart = $now->copy()->subDays(13)->startOfDay();
        $salesPrevEnd = $now->copy()->subDays(7)->endOfDay();

        $salesCurrent = Order::whereBetween('created_at', [$salesCurrentStart, $salesCurrentEnd])->sum('total');
        $salesPrevious = Order::whereBetween('created_at', [$salesPrevStart, $salesPrevEnd])->sum('total');
        $salesChange = $this->percentChange($salesCurrent, $salesPrevious);

        // Delivered sales change (last 7 days vs previous 7 days)
        $deliveredCurrent = Order::where('status', 'delivered')->whereBetween('created_at', [$salesCurrentStart, $salesCurrentEnd])->sum('total');
        $deliveredPrevious = Order::where('status', 'delivered')->whereBetween('created_at', [$salesPrevStart, $salesPrevEnd])->sum('total');
        $deliveredChange = $this->percentChange($deliveredCurrent, $deliveredPrevious);

        // Orders change (last 7 days vs previous 7 days)
        $ordersCurrent = Order::whereBetween('created_at', [$salesCurrentStart, $salesCurrentEnd])->count();
        $ordersPrevious = Order::whereBetween('created_at', [$salesPrevStart, $salesPrevEnd])->count();
        $ordersChange = $this->percentChange($ordersCurrent, $ordersPrevious);

        // Customers change (last 30 days vs previous 30 days)
        $custNow = Carbon::now();
        $custCurrentStart = $custNow->copy()->subDays(29)->startOfDay();
        $custCurrentEnd = $custNow->copy()->endOfDay();
        $custPrevStart = $custNow->copy()->subDays(59)->startOfDay();
        $custPrevEnd = $custNow->copy()->subDays(30)->endOfDay();

        $customersCurrent = User::whereBetween('created_at', [$custCurrentStart, $custCurrentEnd])->count();
        $customersPrevious = User::whereBetween('created_at', [$custPrevStart, $custPrevEnd])->count();
        $customersChange = $this->percentChange($customersCurrent, $customersPrevious);

        // Pending orders change (last 7 days pending vs previous 7 days pending)
        $pendingCurrent = Order::whereBetween('created_at', [$salesCurrentStart, $salesCurrentEnd])->where('status', 'pending')->count();
        $pendingPrevious = Order::whereBetween('created_at', [$salesPrevStart, $salesPrevEnd])->where('status', 'pending')->count();
        $pendingChange = $this->percentChange($pendingCurrent, $pendingPrevious);

        // Chart + Performance Data
        $dailyOrdersData = $this->getDailyOrdersData();
        $productPerformanceData = $this->getProductPerformanceData();
        $orderStatusData = $this->getOrderStatusData();
        $paymentStatusData = $this->getPaymentStatusData();

        // Recent Orders
        $recentOrders = Order::with([
            'customer',
            'items.product',
            'items.productVariation.variationAttributes.attribute.values'
        ])
            ->latest()
            ->take(10)
            ->get();

        $locations = $this->districtWiseOrders();

        $data = [
            'summaryCards' => [
                // ['title' => 'Total Sales', 'value' => '৳' . number_format($totalSales, 2), 'change' => $salesChange],
                ['title' => 'Delivered Sales', 'value' => '৳' . number_format($deliveredSales, 2), 'change' => $deliveredChange],
                ['title' => 'Total Orders', 'value' => $totalOrders, 'change' => $ordersChange],
                ['title' => 'Total Customers', 'value' => $totalCustomers, 'change' => $customersChange],
                ['title' => 'Pending Orders', 'value' => $pendingOrders, 'change' => $pendingChange],
            ],
            'dailyOrdersData' => $dailyOrdersData,
            'monthlyOrdersData' => $this->getMonthlyOrdersData(),
            'productPerformanceData' => $productPerformanceData,
            'orderStatusData' => $orderStatusData,
            'paymentStatusData' => $paymentStatusData,
            'deliverySalesData' => $this->getDeliverySalesData(),
            'recentOrders' => $recentOrders,
            'locations' => $locations,
        ];
        return Inertia::render('Admin/Dashboard/Index', [
            'data' => $data,
        ]);


    }

    private function getDailyOrdersData()
    {
        $startDate = Carbon::now()->subDays(6)->startOfDay();
        $endDate = Carbon::now()->endOfDay();

        $orders = Order::selectRaw('DATE(created_at) as date, COUNT(*) as order_count, SUM(total) as revenue')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get()
            ->keyBy(fn($row) => Carbon::parse($row->date)->format('m/d'));

        $result = [];

        $currentDate = $startDate->copy();
        while ($currentDate <= $endDate) {
            $formattedDate = $currentDate->format('m/d');
            $day = $orders->get($formattedDate);

            $result[] = [
                'date' => $formattedDate,
                'orders' => $day->order_count ?? 0,
                'revenue' => $day->revenue ?? 0,
            ];

            $currentDate->addDay();
        }

        return $result;
    }

    private function getMonthlyOrdersData()
    {
        $startDate = Carbon::now()->subDays(29)->startOfDay();
        $endDate = Carbon::now()->endOfDay();

        $orders = Order::selectRaw('DATE(created_at) as date, COUNT(*) as order_count, SUM(total) as revenue')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get()
            ->keyBy(fn($row) => Carbon::parse($row->date)->format('m/d'));

        $result = [];

        $currentDate = $startDate->copy();
        while ($currentDate <= $endDate) {
            $formattedDate = $currentDate->format('m/d');
            $day = $orders->get($formattedDate);

            $result[] = [
                'date' => $formattedDate,
                'orders' => $day->order_count ?? 0,
                'revenue' => $day->revenue ?? 0,
            ];

            $currentDate->addDay();
        }

        return $result;
    }

    private function getProductPerformanceData()
    {
        $topProducts = DB::table('order_items')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->select('products.id', 'products.name', DB::raw('SUM(order_items.quantity) as sales'))
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('sales')
            ->take(5)
            ->get();

        $totalSales = $topProducts->sum('sales');

        return $topProducts->map(function ($item) use ($totalSales) {
            return [
                'name' => $item->name,
                'sales' => $item->sales,
                'percentage' => $totalSales > 0 ? round(($item->sales / $totalSales) * 100, 1) : 0,
            ];
        });
    }

    private function getOrderStatusData()
    {
        return Order::select('status', DB::raw('COUNT(*) as value'))
            ->groupBy('status')
            ->get()
            ->map(function ($item) {
                return [
                    'name' => ucfirst($item->status),
                    'value' => $item->value,
                ];
            });
    }

    private function getPaymentStatusData()
    {
        return Order::select('payment_status', DB::raw('COUNT(*) as value'))
            ->groupBy('payment_status')
            ->get()
            ->map(function ($item) {
                return [
                    'name' => ucfirst($item->payment_status ?? 'Unpaid'),
                    'value' => $item->value,
                ];
            });
    }

    private function percentChange($current, $previous)
    {
        // Avoid NaN: if previous is 0, return 0 when both zero, or 100 when previous is 0 and current > 0
        if ((float) $previous === 0.0) {
            return ((float) $current === 0.0) ? 0 : 100;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    private function getDeliverySalesData()
    {
        // Return counts and total sales grouped by delivery status
        $rows = Order::select('status', DB::raw('COUNT(*) as orders'), DB::raw('SUM(total) as sales'))
            ->groupBy('status')
            ->get();

        return $rows->map(function ($item) {
            return [
                'name' => ucfirst($item->status),
                'orders' => (int) $item->orders,
                'sales' => (float) $item->sales,
            ];
        });
    }



        private function districtWiseOrders()
    {
        $districtCounts = DB::table('orders')
            ->select('shipping_district', DB::raw('COUNT(*) as total_orders'))
            ->whereNotNull('shipping_district')
            ->groupBy('shipping_district')
            ->get();

        // Load district coordinates
        $geoData = collect(json_decode(file_get_contents(public_path('jsondata/bd_locations.js')), true));

        // Merge coordinates with counts
        $locations = $districtCounts->map(function ($item) use ($geoData) {
            $match = $geoData->firstWhere('district', $item->shipping_district);

            return [
                'district' => $item->shipping_district,
                'total_orders' => $item->total_orders,
                'lat' => $match['lat'] ?? null,
                'lng' => $match['lng'] ?? null,
            ];
        });

        return $locations;
    }



}
