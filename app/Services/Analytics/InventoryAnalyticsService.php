<?php

namespace App\Services\Analytics;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\InventoryStock;
use App\Models\InventoryTransaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class InventoryAnalyticsService
{
    /**
     * Get comprehensive inventory analytics
     */
    public function getInventoryAnalytics($period = '30days')
    {
        $startDate = $this->getStartDateForPeriod($period);

        return [
            'overview' => $this->getInventoryOverview(),
            'sales_velocity' => $this->getSalesVelocityAnalysis($startDate),
            'stock_performance' => $this->getStockPerformanceMetrics($startDate),
            'demand_forecast' => $this->getDemandForecast(),
            'turnover_analysis' => $this->getTurnoverAnalysis($startDate),
            'seasonal_patterns' => $this->getSeasonalPatterns(),
            'reorder_recommendations' => $this->getReorderRecommendations()
        ];
    }

    /**
     * Get inventory overview metrics
     */
    private function getInventoryOverview()
    {
        $stocks = InventoryStock::with('product')->get();

        $overview = [
            'total_products' => $stocks->count(),
            'total_inventory_value' => 0,
            'out_of_stock' => 0,
            'low_stock' => 0,
            'overstocked' => 0,
            'good_stock' => 0,
            'average_stock_level' => 0
        ];

        foreach ($stocks as $stock) {
            // Safe null check for product and price
            $productPrice = 0;
            if ($stock->product && ($stock->product->cost_price || $stock->product->price)) {
                $productPrice = $stock->product->cost_price ?? ($stock->product->price * 0.7) ?? 0;
            }
            $overview['total_inventory_value'] += $stock->available_quantity * $productPrice;
            $overview['average_stock_level'] += $stock->available_quantity;

            if ($stock->available_quantity <= 0) {
                $overview['out_of_stock']++;
            } elseif ($stock->available_quantity <= $stock->minimum_threshold) {
                $overview['low_stock']++;
            } elseif ($stock->available_quantity > $stock->maximum_threshold) {
                $overview['overstocked']++;
            } else {
                $overview['good_stock']++;
            }
        }

        $overview['average_stock_level'] = $stocks->count() > 0 ? $overview['average_stock_level'] / $stocks->count() : 0;

        return $overview;
    }

    /**
     * Analyze sales velocity (how fast products sell)
     */
    private function getSalesVelocityAnalysis($startDate)
    {
        $salesData = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->where('orders.created_at', '>=', $startDate)
            ->where('orders.status', 'completed')
            ->select(
                'order_items.product_id',
                'products.name as product_name',
                'categories.name as category_name',
                DB::raw('SUM(order_items.quantity) as total_sold'),
                DB::raw('COUNT(DISTINCT orders.id) as order_frequency'),
                DB::raw('AVG(order_items.quantity) as avg_quantity_per_order'),
                DB::raw('SUM(order_items.quantity * order_items.unit_price) as total_revenue')
            )
            ->groupBy('order_items.product_id', 'products.name', 'categories.name')
            ->orderByDesc('total_sold')
            ->get();

        $days = now()->diffInDays($startDate);

        return $salesData->map(function ($item) use ($days) {
            $dailySales = $days > 0 ? $item->total_sold / $days : 0;

            return [
                'product_id' => $item->product_id,
                'product_name' => $item->product_name,
                'category' => $item->category_name ?? 'Uncategorized',
                'total_sold' => (int) $item->total_sold,
                'daily_average' => round($dailySales, 2),
                'order_frequency' => (int) $item->order_frequency,
                'avg_qty_per_order' => round($item->avg_quantity_per_order, 2),
                'total_revenue' => (float) $item->total_revenue,
                'velocity_rating' => $this->getVelocityRating($dailySales)
            ];
        });
    }

    /**
     * Get stock performance metrics
     */
    private function getStockPerformanceMetrics($startDate)
    {
        return DB::table('inventory_stocks')
            ->join('products', 'products.id', '=', 'inventory_stocks.product_id')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->select(
                'inventory_stocks.product_id',
                'products.name as product_name',
                'categories.name as category_name',
                'inventory_stocks.available_quantity',
                'inventory_stocks.minimum_threshold',
                'inventory_stocks.maximum_threshold',
                'inventory_stocks.reorder_point',
                'products.cost_price',
                'products.price'
            )
            ->whereNull('products.deleted_at')
            ->get()
            ->map(function ($item) use ($startDate) {
                $salesVelocity = $this->getProductSalesVelocity($item->product_id, $startDate);
                // Safe calculation of stock value with null checks
                $unitPrice = $item->cost_price ?? $item->price ?? 0;
                $stockValue = $item->available_quantity * ($unitPrice * 0.7);
                $daysOfStock = $salesVelocity > 0 ? $item->available_quantity / $salesVelocity : 999;

                return [
                    'product_id' => $item->product_id,
                    'product_name' => $item->product_name,
                    'category' => $item->category_name ?? 'Uncategorized',
                    'current_stock' => (int) $item->available_quantity,
                    'stock_value' => round($stockValue, 2),
                    'days_of_stock' => round($daysOfStock, 1),
                    'stock_status' => $this->getStockStatus($item),
                    'turnover_potential' => $this->getTurnoverPotential($salesVelocity, $item->available_quantity),
                    'reorder_urgency' => $this->getReorderUrgency($item, $daysOfStock)
                ];
            });
    }

    /**
     * Demand forecasting based on historical data
     */
    private function getDemandForecast()
    {
        $periods = ['7days', '30days', '90days'];
        $forecasts = [];

        foreach ($periods as $period) {
            $startDate = $this->getStartDateForPeriod($period);
            $salesData = $this->getHistoricalSales($startDate);

            $forecasts[$period] = $salesData->map(function ($sale) use ($period) {
                $days = $period === '7days' ? 7 : ($period === '30days' ? 30 : 90);
                $dailyAverage = $sale->total_sold / $days;

                return [
                    'product_id' => $sale->product_id,
                    'product_name' => $sale->product_name,
                    'historical_daily_avg' => round($dailyAverage, 2),
                    'predicted_next_7_days' => round($dailyAverage * 7, 0),
                    'predicted_next_30_days' => round($dailyAverage * 30, 0),
                    'confidence_level' => $this->calculateForecastConfidence($sale->order_frequency, $days)
                ];
            });
        }

        return $forecasts;
    }

    /**
     * Analyze inventory turnover
     */
    private function getTurnoverAnalysis($startDate)
    {
        $inventoryValue = InventoryStock::join('products', 'products.id', '=', 'inventory_stocks.product_id')
            ->sum(DB::raw('inventory_stocks.available_quantity * COALESCE(products.cost_price, products.price * 0.7)'));

        $cogs = OrderItem::join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->where('orders.created_at', '>=', $startDate)
            ->where('orders.status', 'completed')
            ->sum(DB::raw('order_items.quantity * COALESCE(products.cost_price, products.price * 0.7)'));

        $days = now()->diffInDays($startDate);
        $annualizedCogs = $days > 0 ? ($cogs / $days) * 365 : 0;
        $turnoverRatio = $inventoryValue > 0 ? $annualizedCogs / $inventoryValue : 0;

        return [
            'inventory_value' => round($inventoryValue, 2),
            'cost_of_goods_sold' => round($cogs, 2),
            'turnover_ratio' => round($turnoverRatio, 2),
            'days_sales_in_inventory' => $turnoverRatio > 0 ? round(365 / $turnoverRatio, 0) : 999,
            'performance_rating' => $this->getTurnoverRating($turnoverRatio)
        ];
    }

    /**
     * Identify seasonal patterns
     */
    private function getSeasonalPatterns()
    {
        $monthlyData = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->where('orders.created_at', '>=', Carbon::now()->subMonths(12))
            ->where('orders.status', 'completed')
            ->select(
                DB::raw('MONTH(orders.created_at) as month'),
                DB::raw('YEAR(orders.created_at) as year'),
                'categories.name as category',
                DB::raw('SUM(order_items.quantity) as total_sold'),
                DB::raw('COUNT(DISTINCT orders.id) as order_count')
            )
            ->groupBy(DB::raw('YEAR(orders.created_at), MONTH(orders.created_at), categories.name'))
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get();

        return $monthlyData->groupBy('category')->map(function ($categoryData) {
            return $categoryData->map(function ($month) {
                return [
                    'month' => $month->month,
                    'year' => $month->year,
                    'total_sold' => (int) $month->total_sold,
                    'order_count' => (int) $month->order_count,
                    'month_name' => Carbon::create()->month($month->month)->format('F')
                ];
            });
        });
    }

    /**
     * Get reorder recommendations
     */
    private function getReorderRecommendations()
    {
        return InventoryStock::with(['product.category'])
            ->whereRaw('available_quantity <= reorder_point')
            ->whereHas('product', function($query) {
                $query->whereNull('deleted_at');
            })
            ->get()
            ->map(function ($stock) {
                $salesVelocity = $this->getProductSalesVelocity($stock->product_id, Carbon::now()->subDays(30));

                // Safe null checks for product data
                $productName = $stock->product?->name ?? 'Unknown Product';
                $categoryName = $stock->product?->category?->name ?? 'Uncategorized';
                $unitCost = $stock->product?->cost_price ?? $stock->product?->price ?? 0;

                return [
                    'product_id' => $stock->product_id,
                    'product_name' => $productName,
                    'category' => $categoryName,
                    'current_stock' => $stock->available_quantity,
                    'reorder_point' => $stock->reorder_point,
                    'suggested_order_qty' => $stock->reorder_quantity,
                    'priority' => $this->getReorderPriority($stock, $salesVelocity),
                    'estimated_cost' => $stock->reorder_quantity * ($unitCost * 0.7),
                    'lead_time_coverage' => $this->getLeadTimeCoverage($stock, $salesVelocity)
                ];
            })
            ->sortByDesc('priority');
    }

    // Helper methods
    private function getStartDateForPeriod($period)
    {
        switch ($period) {
            case '7days':
                return Carbon::now()->subDays(7);
            case '30days':
                return Carbon::now()->subDays(30);
            case '90days':
                return Carbon::now()->subDays(90);
            case '1year':
                return Carbon::now()->subYear();
            default:
                return Carbon::now()->subDays(30);
        }
    }

    private function getProductSalesVelocity($productId, $startDate)
    {
        $totalSold = OrderItem::join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('order_items.product_id', $productId)
            ->where('orders.created_at', '>=', $startDate)
            ->where('orders.status', 'completed')
            ->sum('order_items.quantity');

        $days = now()->diffInDays($startDate);
        return $days > 0 ? $totalSold / $days : 0;
    }

    private function getVelocityRating($dailySales)
    {
        if ($dailySales >= 5) return 'high';
        if ($dailySales >= 1) return 'medium';
        if ($dailySales > 0) return 'low';
        return 'none';
    }

    private function getStockStatus($item)
    {
        if ($item->available_quantity <= 0) return 'out_of_stock';
        if ($item->available_quantity <= $item->minimum_threshold) return 'low_stock';
        if ($item->available_quantity > $item->maximum_threshold) return 'overstocked';
        return 'good_stock';
    }

    private function getTurnoverPotential($velocity, $stock)
    {
        if ($velocity <= 0) return 'low';
        $daysToSellAll = $stock / $velocity;
        if ($daysToSellAll <= 30) return 'high';
        if ($daysToSellAll <= 90) return 'medium';
        return 'low';
    }

    private function getReorderUrgency($item, $daysOfStock)
    {
        if ($item->available_quantity <= 0) return 'critical';
        if ($daysOfStock <= 7) return 'urgent';
        if ($daysOfStock <= 30) return 'medium';
        return 'low';
    }

    private function getTurnoverRating($ratio)
    {
        if ($ratio >= 12) return 'excellent';
        if ($ratio >= 6) return 'good';
        if ($ratio >= 3) return 'average';
        return 'poor';
    }

    private function calculateForecastConfidence($orderFrequency, $days)
    {
        $frequency = $orderFrequency / $days;
        if ($frequency >= 0.5) return 'high';
        if ($frequency >= 0.2) return 'medium';
        return 'low';
    }

    private function getHistoricalSales($startDate)
    {
        return DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->where('orders.created_at', '>=', $startDate)
            ->where('orders.status', 'completed')
            ->select(
                'order_items.product_id',
                'products.name as product_name',
                DB::raw('SUM(order_items.quantity) as total_sold'),
                DB::raw('COUNT(DISTINCT orders.id) as order_frequency')
            )
            ->groupBy('order_items.product_id', 'products.name')
            ->get();
    }

    private function getReorderPriority($stock, $salesVelocity)
    {
        if ($stock->available_quantity <= 0) return 10; // Critical

        $daysRemaining = $salesVelocity > 0 ? $stock->available_quantity / $salesVelocity : 999;

        if ($daysRemaining <= 3) return 9; // Urgent
        if ($daysRemaining <= 7) return 8; // High
        if ($daysRemaining <= 14) return 6; // Medium
        if ($daysRemaining <= 30) return 4; // Low

        return 1; // Very Low
    }

    private function getLeadTimeCoverage($stock, $salesVelocity)
    {
        $leadTimeDays = 7; // Default lead time
        $safetyDays = 3; // Safety buffer

        if ($salesVelocity <= 0) return 'infinite';

        $daysRemaining = $stock->available_quantity / $salesVelocity;
        $requiredCoverage = $leadTimeDays + $safetyDays;

        if ($daysRemaining >= $requiredCoverage) return 'adequate';
        if ($daysRemaining >= $leadTimeDays) return 'minimal';
        return 'insufficient';
    }

    /**
     * Get dashboard summary for Bengali UI with enhanced error handling
     */
    public function getDashboardSummary()
    {
        try {
            $stocks = InventoryStock::with(['product' => function($query) {
                $query->select('id', 'name', 'price', 'cost_price');
            }])->get();

            $summary = [
                'total_products' => $stocks->count(),
                'critical_count' => 0,
                'low_count' => 0,
                'good_stock' => 0,
            ];

            foreach ($stocks as $stock) {
                if ($stock->available_quantity <= 0) {
                    $summary['critical_count']++;
                } elseif ($stock->available_quantity <= ($stock->minimum_quantity ?: 10)) {
                    $summary['low_count']++;
                } else {
                    $summary['good_stock']++;
                }
            }

            return [
                'summary' => $summary,
                'sales_velocity' => $this->getSafeVelocityAnalysis(),
                'stock_analysis' => $this->getSafeInventoryOverview()
            ];

        } catch (\Exception $e) {
            \Log::error('Dashboard Summary Error: ' . $e->getMessage());

            // Return safe fallback data
            return [
                'summary' => [
                    'total_products' => 0,
                    'critical_count' => 0,
                    'low_count' => 0,
                    'good_stock' => 0,
                ],
                'sales_velocity' => [],
                'stock_analysis' => []
            ];
        }
    }

    /**
     * Safe sales velocity analysis with error handling
     */
    private function getSafeVelocityAnalysis()
    {
        try {
            return $this->getSalesVelocityAnalysis(Carbon::now()->subDays(30));
        } catch (\Exception $e) {
            \Log::error('Velocity Analysis Error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Safe inventory overview with error handling
     */
    private function getSafeInventoryOverview()
    {
        try {
            return $this->getInventoryOverview();
        } catch (\Exception $e) {
            \Log::error('Inventory Overview Error: ' . $e->getMessage());
            return [
                'total_products' => 0,
                'total_inventory_value' => 0,
                'out_of_stock' => 0,
                'low_stock' => 0,
                'overstocked' => 0,
                'good_stock' => 0,
                'average_stock_level' => 0
            ];
        }
    }

    /**
     * Get slow moving products for promotional opportunities
     */
    public function getSlowMovingProducts($days = 30)
    {
        $cutoffDate = Carbon::now()->subDays($days);

        return InventoryStock::with(['product', 'product.orderItems' => function($query) use ($cutoffDate) {
                $query->whereHas('order', function($q) use ($cutoffDate) {
                    $q->where('created_at', '>=', $cutoffDate);
                });
            }])
            ->get()
            ->filter(function($stock) use ($cutoffDate) {
                $recentSales = $stock->product->orderItems
                    ->where('created_at', '>=', $cutoffDate)
                    ->sum('quantity');

                return $recentSales == 0 && $stock->available_quantity > 20;
            })
            ->map(function($stock) {
                return [
                    'id' => $stock->product->id,
                    'name' => $stock->product->name,
                    'current_stock' => $stock->available_quantity,
                    'last_sale_date' => $stock->product->orderItems
                        ->sortByDesc('created_at')
                        ->first()?->created_at?->format('Y-m-d'),
                    'promotion_suggestion' => $this->generatePromotionSuggestion($stock)
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Get weekly revenue data
     */
    public function getWeeklyRevenue()
    {
        $startDate = Carbon::now()->subDays(7);

        return Order::whereBetween('created_at', [$startDate, Carbon::now()])
            ->selectRaw('DATE(created_at) as date, SUM(total_amount) as revenue')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('revenue', 'date')
            ->values()
            ->all();
    }

    /**
     * Get reorder optimization recommendations
     */
    public function getReorderOptimization()
    {
        return [
            'optimized_points' => 0,
            'savings_potential' => 0,
            'recommendations' => [
                'Optimize reorder points based on sales velocity',
                'Reduce safety stock for slow-moving items',
                'Increase frequency for high-velocity products'
            ]
        ];
    }

    /**
     * Generate promotion suggestion for slow-moving product
     */
    private function generatePromotionSuggestion($stock)
    {
        $stockLevel = $stock->available_quantity;

        if ($stockLevel > 100) {
            return '20-30% ছাড় দিয়ে দ্রুত বিক্রয় করুন';
        } elseif ($stockLevel > 50) {
            return '15-20% ছাড় বা বান্ডেল অফার দিন';
        } elseif ($stockLevel > 20) {
            return '10-15% ছাড় বা কম্বো প্যাকেজ তৈরি করুন';
        } else {
            return 'সোশ্যাল মিডিয়ায় প্রমোট করুন';
        }
    }
}
