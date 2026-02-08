<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class InventoryTestController extends Controller
{
    /**
     * Test weekly report endpoint
     */
    public function testWeeklyReport()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'promotional' => [
                    [
                        'id' => 1,
                        'name' => 'Sample Product 1',
                        'current_stock' => 50,
                        'last_sale_date' => '2024-01-15',
                        'promotion_suggestion' => '15-20% discount recommended'
                    ],
                    [
                        'id' => 2,
                        'name' => 'Sample Product 2',
                        'current_stock' => 75,
                        'last_sale_date' => '2024-01-10',
                        'promotion_suggestion' => 'Bundle offer recommended'
                    ]
                ],
                'revenue' => [10000, 12000, 15000, 14000, 16000, 18000, 20000],
                'optimization' => [
                    'optimized_points' => 5,
                    'savings_potential' => 25000,
                    'recommendations' => [
                        'Optimize reorder points based on sales velocity',
                        'Reduce safety stock for slow-moving items',
                        'Increase frequency for high-velocity products'
                    ]
                ]
            ],
            'message' => 'Weekly report generated successfully'
        ]);
    }

    /**
     * Test dashboard data
     */
    public function testDashboard()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'summary' => [
                    'total_products' => 100,
                    'critical_count' => 5,
                    'low_count' => 15,
                    'good_stock' => 80
                ],
                'critical' => [
                    [
                        'id' => 1,
                        'name' => 'Critical Product 1',
                        'current_stock' => 0,
                        'minimum_stock' => 10,
                        'priority_score' => 10,
                        'days_until_stockout' => 0
                    ]
                ],
                'low' => [
                    [
                        'id' => 2,
                        'name' => 'Low Stock Product 1',
                        'current_stock' => 5,
                        'minimum_stock' => 15,
                        'priority_score' => 6,
                        'velocity' => 'medium',
                        'recommendation' => 'Reorder soon'
                    ]
                ],
                'sales_velocity' => []
            ],
            'message' => 'Dashboard data loaded successfully'
        ]);
    }
}
