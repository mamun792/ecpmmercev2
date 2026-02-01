<?php

namespace App\Http\Controllers\Admin\BusinessDashboard;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Expense;
use App\Models\ProductPurchaseCost;
use Illuminate\Http\Request;
use Carbon\Carbon;

class BusinessDashboardController extends Controller
{
    public function index(Request $request)
    {
        $filter = $request->get('filter', 'monthly');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        // Base date range query builder
        $dateRange = function($query) use ($filter, $startDate, $endDate) {
            switch ($filter) {
                case 'weekly':
                    $query->whereBetween('created_at', [
                        Carbon::now()->startOfWeek(),
                        Carbon::now()->endOfWeek()
                    ]);
                    break;
                case 'monthly':
                    $query->whereBetween('created_at', [
                        Carbon::now()->startOfMonth(),
                        Carbon::now()->endOfMonth()
                    ]);
                    break;
                case 'yearly':
                    $query->whereBetween('created_at', [
                        Carbon::now()->startOfYear(),
                        Carbon::now()->endOfYear()
                    ]);
                    break;
                case 'custom':
                    if ($startDate && $endDate) {
                        $query->whereBetween('created_at', [
                            Carbon::parse($startDate)->startOfDay(),
                            Carbon::parse($endDate)->endOfDay()
                        ]);
                    }
                    break;
            }
        };

        // Revenue calculation (paid orders)
        $revenue = Order::where('payment_status', 'paid')
            ->where(function($query) use ($dateRange) {
                $dateRange($query);
            })
            ->sum('total');

        // Expenses calculation (from expenses table)
        $expenses = Expense::where(function($query) use ($dateRange) {
            $dateRange($query);
        })->sum('amount');

        // Product purchase costs (additional expenses)
        $purchaseCosts = ProductPurchaseCost::where(function($query) use ($dateRange) {
            $dateRange($query);
        })->sum('total_price');

        // Total expenses = expenses + purchase costs
        $totalExpenses = $expenses + $purchaseCosts;

        // Net profit = revenue - total expenses
        $profit = $revenue - $totalExpenses;

        // Total orders count
        $totalOrders = Order::where('payment_status', 'paid')
            ->where(function($query) use ($dateRange) {
                $dateRange($query);
            })
            ->count();

        // Get chart data for the selected period
        $chartData = $this->getChartData($filter, $startDate, $endDate);

        // Get additional chart data
        $ordersChartData = $this->getOrdersChartData($filter, $startDate, $endDate);
        $monthlyOverviewData = $this->getMonthlyOverviewData($filter, $startDate, $endDate);

        return inertia('Admin/BusinessDashboard/Index', [
            'revenue' => $revenue,
            'expenses' => $totalExpenses,
            'profit' => $profit,
            'totalOrders' => $totalOrders,
            'filter' => $filter,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'chartData' => $chartData,
            'ordersChartData' => $ordersChartData,
            'monthlyOverviewData' => $monthlyOverviewData,
        ]);
    }

    private function getChartData($filter, $startDate, $endDate)
    {
        $data = [];

        switch ($filter) {
            case 'weekly':
                $data = $this->getWeeklyChartData();
                break;
            case 'monthly':
                $data = $this->getMonthlyChartData();
                break;
            case 'yearly':
                $data = $this->getYearlyChartData();
                break;
            case 'custom':
                $data = $this->getCustomChartData($startDate, $endDate);
                break;
        }

        return $data;
    }

    private function getWeeklyChartData()
    {
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();

        $revenueData = [];
        $expenseData = [];
        $categories = [];

        for ($date = $startOfWeek->copy(); $date->lte($endOfWeek); $date->addDay()) {
            $categories[] = $date->format('D');

            // Revenue for this day
            $revenue = Order::where('payment_status', 'paid')
                ->whereDate('created_at', $date->format('Y-m-d'))
                ->sum('total');
            $revenueData[] = (float) $revenue;

            // Expenses for this day
            $expenses = Expense::whereDate('created_at', $date->format('Y-m-d'))->sum('amount') +
                       ProductPurchaseCost::whereDate('created_at', $date->format('Y-m-d'))->sum('total_price');
            $expenseData[] = (float) $expenses;
        }

        return [
            'categories' => $categories,
            'revenue' => $revenueData,
            'expenses' => $expenseData,
        ];
    }

    private function getMonthlyChartData()
    {
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $revenueData = [];
        $expenseData = [];
        $categories = [];

        for ($date = $startOfMonth->copy(); $date->lte($endOfMonth); $date->addDay()) {
            $categories[] = $date->format('d');

            // Revenue for this day
            $revenue = Order::where('payment_status', 'paid')
                ->whereDate('created_at', $date->format('Y-m-d'))
                ->sum('total');
            $revenueData[] = (float) $revenue;

            // Expenses for this day
            $expenses = Expense::whereDate('created_at', $date->format('Y-m-d'))->sum('amount') +
                       ProductPurchaseCost::whereDate('created_at', $date->format('Y-m-d'))->sum('total_price');
            $expenseData[] = (float) $expenses;
        }

        return [
            'categories' => $categories,
            'revenue' => $revenueData,
            'expenses' => $expenseData,
        ];
    }

    private function getYearlyChartData()
    {
        $currentYear = Carbon::now()->year;

        $revenueData = [];
        $expenseData = [];
        $categories = [];

        for ($month = 1; $month <= 12; $month++) {
            $categories[] = Carbon::create($currentYear, $month, 1)->format('M');

            // Revenue for this month
            $revenue = Order::where('payment_status', 'paid')
                ->whereYear('created_at', $currentYear)
                ->whereMonth('created_at', $month)
                ->sum('total');
            $revenueData[] = (float) $revenue;

            // Expenses for this month
            $expenses = Expense::whereYear('created_at', $currentYear)
                ->whereMonth('created_at', $month)
                ->sum('amount') +
                ProductPurchaseCost::whereYear('created_at', $currentYear)
                ->whereMonth('created_at', $month)
                ->sum('total_price');
            $expenseData[] = (float) $expenses;
        }

        return [
            'categories' => $categories,
            'revenue' => $revenueData,
            'expenses' => $expenseData,
        ];
    }

    private function getCustomChartData($startDate, $endDate)
    {
        if (!$startDate || !$endDate) {
            return ['categories' => [], 'revenue' => [], 'expenses' => []];
        }

        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        $revenueData = [];
        $expenseData = [];
        $categories = [];

        // If date range is less than 30 days, show daily data
        if ($start->diffInDays($end) <= 30) {
            for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
                $categories[] = $date->format('M d');

                $revenue = Order::where('payment_status', 'paid')
                    ->whereDate('created_at', $date->format('Y-m-d'))
                    ->sum('total');
                $revenueData[] = (float) $revenue;

                $expenses = Expense::whereDate('created_at', $date->format('Y-m-d'))->sum('amount') +
                           ProductPurchaseCost::whereDate('created_at', $date->format('Y-m-d'))->sum('total_price');
                $expenseData[] = (float) $expenses;
            }
        } else {
            // If date range is more than 30 days, show monthly data
            for ($date = $start->copy(); $date->lte($end); $date->addMonth()) {
                $categories[] = $date->format('M Y');

                $revenue = Order::where('payment_status', 'paid')
                    ->whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->sum('total');
                $revenueData[] = (float) $revenue;

                $expenses = Expense::whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->sum('amount') +
                    ProductPurchaseCost::whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->sum('total_price');
                $expenseData[] = (float) $expenses;
            }
        }

        return [
            'categories' => $categories,
            'revenue' => $revenueData,
            'expenses' => $expenseData,
        ];
    }

    private function getOrdersChartData($filter, $startDate, $endDate)
    {
        $data = [];

        switch ($filter) {
            case 'weekly':
                $data = $this->getWeeklyOrdersData();
                break;
            case 'monthly':
                $data = $this->getMonthlyOrdersData();
                break;
            case 'yearly':
                $data = $this->getYearlyOrdersData();
                break;
            case 'custom':
                $data = $this->getCustomOrdersData($startDate, $endDate);
                break;
        }

        return $data;
    }

    private function getWeeklyOrdersData()
    {
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();

        $ordersData = [];
        $categories = [];

        for ($date = $startOfWeek->copy(); $date->lte($endOfWeek); $date->addDay()) {
            $categories[] = $date->format('D');

            $orders = Order::where('payment_status', 'paid')
                ->whereDate('created_at', $date->format('Y-m-d'))
                ->count();
            $ordersData[] = (int) $orders;
        }

        return [
            'categories' => $categories,
            'orders' => $ordersData,
        ];
    }

    private function getMonthlyOrdersData()
    {
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $ordersData = [];
        $categories = [];

        for ($date = $startOfMonth->copy(); $date->lte($endOfMonth); $date->addDay()) {
            $categories[] = $date->format('d');

            $orders = Order::where('payment_status', 'paid')
                ->whereDate('created_at', $date->format('Y-m-d'))
                ->count();
            $ordersData[] = (int) $orders;
        }

        return [
            'categories' => $categories,
            'orders' => $ordersData,
        ];
    }

    private function getYearlyOrdersData()
    {
        $currentYear = Carbon::now()->year;

        $ordersData = [];
        $categories = [];

        for ($month = 1; $month <= 12; $month++) {
            $categories[] = Carbon::create($currentYear, $month, 1)->format('M');

            $orders = Order::where('payment_status', 'paid')
                ->whereYear('created_at', $currentYear)
                ->whereMonth('created_at', $month)
                ->count();
            $ordersData[] = (int) $orders;
        }

        return [
            'categories' => $categories,
            'orders' => $ordersData,
        ];
    }

    private function getCustomOrdersData($startDate, $endDate)
    {
        if (!$startDate || !$endDate) {
            return ['categories' => [], 'orders' => []];
        }

        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        $ordersData = [];
        $categories = [];

        if ($start->diffInDays($end) <= 30) {
            for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
                $categories[] = $date->format('M d');

                $orders = Order::where('payment_status', 'paid')
                    ->whereDate('created_at', $date->format('Y-m-d'))
                    ->count();
                $ordersData[] = (int) $orders;
            }
        } else {
            for ($date = $start->copy(); $date->lte($end); $date->addMonth()) {
                $categories[] = $date->format('M Y');

                $orders = Order::where('payment_status', 'paid')
                    ->whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->count();
                $ordersData[] = (int) $orders;
            }
        }

        return [
            'categories' => $categories,
            'orders' => $ordersData,
        ];
    }

    private function getProfitChartData($filter, $startDate, $endDate)
    {
        $data = [];

        switch ($filter) {
            case 'weekly':
                $data = $this->getWeeklyProfitData();
                break;
            case 'monthly':
                $data = $this->getMonthlyProfitData();
                break;
            case 'yearly':
                $data = $this->getYearlyProfitData();
                break;
            case 'custom':
                $data = $this->getCustomProfitData($startDate, $endDate);
                break;
        }

        return $data;
    }

    private function getWeeklyProfitData()
    {
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();

        $profitData = [];
        $categories = [];

        for ($date = $startOfWeek->copy(); $date->lte($endOfWeek); $date->addDay()) {
            $categories[] = $date->format('D');

            $revenue = Order::where('payment_status', 'paid')
                ->whereDate('created_at', $date->format('Y-m-d'))
                ->sum('total');

            $expenses = Expense::whereDate('created_at', $date->format('Y-m-d'))->sum('amount') +
                       ProductPurchaseCost::whereDate('created_at', $date->format('Y-m-d'))->sum('total_price');

            $profit = $revenue - $expenses;
            $profitData[] = (float) $profit;
        }

        return [
            'categories' => $categories,
            'profit' => $profitData,
        ];
    }

    private function getMonthlyProfitData()
    {
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $profitData = [];
        $categories = [];

        for ($date = $startOfMonth->copy(); $date->lte($endOfMonth); $date->addDay()) {
            $categories[] = $date->format('d');

            $revenue = Order::where('payment_status', 'paid')
                ->whereDate('created_at', $date->format('Y-m-d'))
                ->sum('total');

            $expenses = Expense::whereDate('created_at', $date->format('Y-m-d'))->sum('amount') +
                       ProductPurchaseCost::whereDate('created_at', $date->format('Y-m-d'))->sum('total_price');

            $profit = $revenue - $expenses;
            $profitData[] = (float) $profit;
        }

        return [
            'categories' => $categories,
            'profit' => $profitData,
        ];
    }

    private function getYearlyProfitData()
    {
        $currentYear = Carbon::now()->year;

        $profitData = [];
        $categories = [];

        for ($month = 1; $month <= 12; $month++) {
            $categories[] = Carbon::create($currentYear, $month, 1)->format('M');

            $revenue = Order::where('payment_status', 'paid')
                ->whereYear('created_at', $currentYear)
                ->whereMonth('created_at', $month)
                ->sum('total');

            $expenses = Expense::whereYear('created_at', $currentYear)
                ->whereMonth('created_at', $month)
                ->sum('amount') +
                ProductPurchaseCost::whereYear('created_at', $currentYear)
                ->whereMonth('created_at', $month)
                ->sum('total_price');

            $profit = $revenue - $expenses;
            $profitData[] = (float) $profit;
        }

        return [
            'categories' => $categories,
            'profit' => $profitData,
        ];
    }

    private function getCustomProfitData($startDate, $endDate)
    {
        if (!$startDate || !$endDate) {
            return ['categories' => [], 'profit' => []];
        }

        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        $profitData = [];
        $categories = [];

        if ($start->diffInDays($end) <= 30) {
            for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
                $categories[] = $date->format('M d');

                $revenue = Order::where('payment_status', 'paid')
                    ->whereDate('created_at', $date->format('Y-m-d'))
                    ->sum('total');

                $expenses = Expense::whereDate('created_at', $date->format('Y-m-d'))->sum('amount') +
                           ProductPurchaseCost::whereDate('created_at', $date->format('Y-m-d'))->sum('total_price');

                $profit = $revenue - $expenses;
                $profitData[] = (float) $profit;
            }
        } else {
            for ($date = $start->copy(); $date->lte($end); $date->addMonth()) {
                $categories[] = $date->format('M Y');

                $revenue = Order::where('payment_status', 'paid')
                    ->whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->sum('total');

                $expenses = Expense::whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->sum('amount') +
                    ProductPurchaseCost::whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->sum('total_price');

                $profit = $revenue - $expenses;
                $profitData[] = (float) $profit;
            }
        }

        return [
            'categories' => $categories,
            'profit' => $profitData,
        ];
    }

    private function getMonthlyOverviewData($filter, $startDate, $endDate)
    {
        $data = [];

        switch ($filter) {
            case 'weekly':
                $data = $this->getWeeklyMonthlyOverviewData();
                break;
            case 'monthly':
                $data = $this->getMonthlyMonthlyOverviewData();
                break;
            case 'yearly':
                $data = $this->getYearlyMonthlyOverviewData();
                break;
            case 'custom':
                $data = $this->getCustomMonthlyOverviewData($startDate, $endDate);
                break;
        }

        return $data;
    }

    private function getWeeklyMonthlyOverviewData()
    {
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();

        $salaryData = [];
        $expenseData = [];
        $purchaseData = [];
        $revenueData = [];
        $categories = [];

        for ($date = $startOfWeek->copy(); $date->lte($endOfWeek); $date->addDay()) {
            $categories[] = $date->format('D');

            // Salary data (from employee salaries)
            $salary = \App\Models\EmployeeSalary::whereDate('paid_date', $date->format('Y-m-d'))->sum('amount');
            $salaryData[] = (float) $salary;

            // Expenses (excluding salaries)
            $expenses = Expense::whereDate('created_at', $date->format('Y-m-d'))->sum('amount');
            $expenseData[] = (float) $expenses;

            // Product purchases
            $purchases = ProductPurchaseCost::whereDate('created_at', $date->format('Y-m-d'))->sum('total_price');
            $purchaseData[] = (float) $purchases;

            // Revenue
            $revenue = Order::where('payment_status', 'paid')
                ->whereDate('created_at', $date->format('Y-m-d'))
                ->sum('total');
            $revenueData[] = (float) $revenue;
        }

        return [
            'categories' => $categories,
            'salary' => $salaryData,
            'expenses' => $expenseData,
            'purchases' => $purchaseData,
            'revenue' => $revenueData,
        ];
    }

    private function getMonthlyMonthlyOverviewData()
    {
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $salaryData = [];
        $expenseData = [];
        $purchaseData = [];
        $revenueData = [];
        $categories = [];

        for ($date = $startOfMonth->copy(); $date->lte($endOfMonth); $date->addDay()) {
            $categories[] = $date->format('d');

            // Salary data
            $salary = \App\Models\EmployeeSalary::whereDate('paid_date', $date->format('Y-m-d'))->sum('amount');
            $salaryData[] = (float) $salary;

            // Expenses (excluding salaries)
            $expenses = Expense::whereDate('created_at', $date->format('Y-m-d'))->sum('amount');
            $expenseData[] = (float) $expenses;

            // Product purchases
            $purchases = ProductPurchaseCost::whereDate('created_at', $date->format('Y-m-d'))->sum('total_price');
            $purchaseData[] = (float) $purchases;

            // Revenue
            $revenue = Order::where('payment_status', 'paid')
                ->whereDate('created_at', $date->format('Y-m-d'))
                ->sum('total');
            $revenueData[] = (float) $revenue;
        }

        return [
            'categories' => $categories,
            'salary' => $salaryData,
            'expenses' => $expenseData,
            'purchases' => $purchaseData,
            'revenue' => $revenueData,
        ];
    }

    private function getYearlyMonthlyOverviewData()
    {
        $currentYear = Carbon::now()->year;

        $salaryData = [];
        $expenseData = [];
        $purchaseData = [];
        $revenueData = [];
        $categories = [];

        for ($month = 1; $month <= 12; $month++) {
            $categories[] = Carbon::create($currentYear, $month, 1)->format('M');

            // Salary data
            $salary = \App\Models\EmployeeSalary::whereYear('paid_date', $currentYear)
                ->whereMonth('paid_date', $month)
                ->sum('amount');
            $salaryData[] = (float) $salary;

            // Expenses (excluding salaries)
            $expenses = Expense::whereYear('created_at', $currentYear)
                ->whereMonth('created_at', $month)
                ->sum('amount');
            $expenseData[] = (float) $expenses;

            // Product purchases
            $purchases = ProductPurchaseCost::whereYear('created_at', $currentYear)
                ->whereMonth('created_at', $month)
                ->sum('total_price');
            $purchaseData[] = (float) $purchases;

            // Revenue
            $revenue = Order::where('payment_status', 'paid')
                ->whereYear('created_at', $currentYear)
                ->whereMonth('created_at', $month)
                ->sum('total');
            $revenueData[] = (float) $revenue;
        }

        return [
            'categories' => $categories,
            'salary' => $salaryData,
            'expenses' => $expenseData,
            'purchases' => $purchaseData,
            'revenue' => $revenueData,
        ];
    }

    private function getCustomMonthlyOverviewData($startDate, $endDate)
    {
        if (!$startDate || !$endDate) {
            return [
                'categories' => [],
                'salary' => [],
                'expenses' => [],
                'purchases' => [],
                'revenue' => []
            ];
        }

        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        $salaryData = [];
        $expenseData = [];
        $purchaseData = [];
        $revenueData = [];
        $categories = [];

        if ($start->diffInDays($end) <= 30) {
            for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
                $categories[] = $date->format('M d');

                $salary = \App\Models\EmployeeSalary::whereDate('paid_date', $date->format('Y-m-d'))->sum('amount');
                $salaryData[] = (float) $salary;

                $expenses = Expense::whereDate('created_at', $date->format('Y-m-d'))->sum('amount');
                $expenseData[] = (float) $expenses;

                $purchases = ProductPurchaseCost::whereDate('created_at', $date->format('Y-m-d'))->sum('total_price');
                $purchaseData[] = (float) $purchases;

                $revenue = Order::where('payment_status', 'paid')
                    ->whereDate('created_at', $date->format('Y-m-d'))
                    ->sum('total');
                $revenueData[] = (float) $revenue;
            }
        } else {
            for ($date = $start->copy(); $date->lte($end); $date->addMonth()) {
                $categories[] = $date->format('M Y');

                $salary = \App\Models\EmployeeSalary::whereYear('paid_date', $date->year)
                    ->whereMonth('paid_date', $date->month)
                    ->sum('amount');
                $salaryData[] = (float) $salary;

                $expenses = Expense::whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->sum('amount');
                $expenseData[] = (float) $expenses;

                $purchases = ProductPurchaseCost::whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->sum('total_price');
                $purchaseData[] = (float) $purchases;

                $revenue = Order::where('payment_status', 'paid')
                    ->whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->sum('total');
                $revenueData[] = (float) $revenue;
            }
        }

        return [
            'categories' => $categories,
            'salary' => $salaryData,
            'expenses' => $expenseData,
            'purchases' => $purchaseData,
            'revenue' => $revenueData,
        ];
    }
}
