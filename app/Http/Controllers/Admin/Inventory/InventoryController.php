<?php

namespace App\Http\Controllers\Admin\Inventory;

use App\Http\Controllers\Controller;
use App\Services\Inventory\InventoryService;
use App\Models\Product;
use App\Models\InventoryStock;
use App\Models\InventoryTransaction;
use App\DTOs\InventoryAdjustmentDTO;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

/**
 * Enterprise-grade inventory management controller
 * Handles stock adjustments, transfers, reports and analytics
 */
class InventoryController extends Controller
{
    private InventoryService $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    /**
     * Display inventory dashboard with real-time analytics
     */
    public function index(Request $request)
    {
        try {
            $query = InventoryStock::with(['product:id,name,sku', 'product.brand:id,name'])
                ->select([
                    'id',
                    'product_id',
                    'location',
                    'available_quantity',
                    'reserved_quantity',
                    'reorder_level',
                    'updated_at'
                ]);

            // Apply filters
            if ($request->filled('search')) {
                $search = $request->get('search');
                $query->whereHas('product', function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                      ->orWhere('sku', 'LIKE', "%{$search}%");
                });
            }

            if ($request->filled('location')) {
                $query->where('location', $request->get('location'));
            }

            if ($request->filled('low_stock')) {
                $query->whereRaw('available_quantity <= reorder_level');
            }

            $inventory = $query->paginate(50);

            // Get analytics data
            $analytics = $this->getInventoryAnalytics();

            return Inertia::render('Admin/Inventory/Index', [
                'inventory' => $inventory,
                'analytics' => $analytics,
                'filters' => $request->only(['search', 'location', 'low_stock']),
                'locations' => $this->getInventoryLocations(),
            ]);
        } catch (\Exception $e) {
            Log::error('Inventory Index Error: ' . $e->getMessage());
            return redirect()
                ->route('admin.dashboard')
                ->with('error', 'Unable to load inventory dashboard.');
        }
    }

    /**
     * Adjust stock levels with audit trail
     */
    public function adjustStock(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'variation_id' => 'nullable|exists:product_variations,id',
            'location' => 'nullable|string',
            'adjustment_type' => 'nullable|string|in:increase,decrease,set,add',
            'type' => 'nullable|string|in:increase,decrease,set,add',
            'quantity' => 'required|integer|min:0',
            'reason' => 'required|string|max:255',
            'notes' => 'nullable|string|max:500',
            'note' => 'nullable|string|max:500',
        ]);

        try {
            $adjustmentDto = new InventoryAdjustmentDTO(
                productId: $request->product_id,
                location: $request->location ?? 'MAIN',
                adjustmentType: $request->type ?? $request->adjustment_type,
                quantity: $request->quantity,
                reason: $request->reason,
                variationId: $request->variation_id,
                notes: $request->note ?? $request->notes,
                userId: auth()->id()
            );

            $result = $this->inventoryService->adjustStock($adjustmentDto);

            return redirect()->back()->with('success', 'Stock adjusted successfully');
        } catch (\Exception $e) {
            Log::error('Stock Adjustment Error: ' . $e->getMessage(), [
                'request' => $request->all(),
                'user_id' => auth()->id(),
            ]);

            return redirect()->back()->with('error', 'Failed to adjust stock: ' . $e->getMessage());
        }
    }

    /**
     * Transfer stock between locations
     */
    public function transferStock(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'from_location' => 'required|string',
            'to_location' => 'required|string|different:from_location',
            'quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $result = $this->inventoryService->transferStock(
                $request->product_id,
                $request->from_location,
                $request->to_location,
                $request->quantity,
                $request->notes ?? 'Stock transfer',
                auth()->id()
            );

            return response()->json([
                'success' => true,
                'message' => 'Stock transferred successfully',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            Log::error('Stock Transfer Error: ' . $e->getMessage(), [
                'request' => $request->all(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to transfer stock: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get stock transactions history
     */
    public function getTransactions(Request $request)
    {
        try {
            $query = InventoryTransaction::with([
                'product:id,name,sku',
                'user:id,name'
            ])->select([
                'id',
                'product_id',
                'location',
                'transaction_type',
                'quantity',
                'reference_type',
                'reference_id',
                'notes',
                'user_id',
                'created_at'
            ]);

            // Apply filters
            if ($request->filled('product_id')) {
                $query->where('product_id', $request->product_id);
            }

            if ($request->filled('location')) {
                $query->where('location', $request->location);
            }

            if ($request->filled('transaction_type')) {
                $query->where('transaction_type', $request->transaction_type);
            }

            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            $transactions = $query->orderBy('created_at', 'desc')
                                 ->paginate(50);

            return response()->json([
                'success' => true,
                'data' => $transactions,
            ]);
        } catch (\Exception $e) {
            Log::error('Transactions Fetch Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch transactions',
            ], 500);
        }
    }

    /**
     * Get low stock alerts
     */
    public function getLowStockAlerts()
    {
        try {
            $lowStockItems = InventoryStock::with(['product:id,name,sku'])
                ->whereRaw('available_quantity <= reorder_level')
                ->where('available_quantity', '>', 0)
                ->orderBy('available_quantity', 'asc')
                ->limit(20)
                ->get();

            $outOfStockItems = InventoryStock::with(['product:id,name,sku'])
                ->where('available_quantity', 0)
                ->orderBy('updated_at', 'desc')
                ->limit(20)
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'low_stock' => $lowStockItems,
                    'out_of_stock' => $outOfStockItems,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Low Stock Alerts Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch stock alerts',
            ], 500);
        }
    }

    /**
     * Update reorder levels for products
     */
    public function updateReorderLevel(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'location' => 'required|string',
            'reorder_level' => 'required|integer|min:0',
        ]);

        try {
            InventoryStock::where('product_id', $request->product_id)
                         ->where('location', $request->location)
                         ->update(['reorder_level' => $request->reorder_level]);

            return response()->json([
                'success' => true,
                'message' => 'Reorder level updated successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Reorder Level Update Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update reorder level',
            ], 500);
        }
    }

    /**
     * Generate inventory reports
     */
    public function generateReport(Request $request)
    {
        $request->validate([
            'report_type' => 'required|string|in:stock_valuation,movement,low_stock,location_summary',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'location' => 'nullable|string',
            'format' => 'nullable|string|in:json,csv,pdf',
        ]);

        try {
            $reportData = match ($request->report_type) {
                'stock_valuation' => $this->generateStockValuationReport($request),
                'movement' => $this->generateMovementReport($request),
                'low_stock' => $this->generateLowStockReport($request),
                'location_summary' => $this->generateLocationSummaryReport($request),
            };

            return response()->json([
                'success' => true,
                'data' => $reportData,
                'generated_at' => now()->toISOString(),
            ]);
        } catch (\Exception $e) {
            Log::error('Report Generation Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate report',
            ], 500);
        }
    }

    /**
     * Get real-time inventory analytics
     */
    private function getInventoryAnalytics(): array
    {
        try {
            $totalProducts = Product::where('status', 'active')->count();
            $totalStockValue = InventoryStock::join('products', 'inventory_stocks.product_id', '=', 'products.id')
                ->selectRaw('SUM(inventory_stocks.available_quantity * products.price) as total_value')
                ->value('total_value') ?? 0;

            $lowStockCount = InventoryStock::whereRaw('available_quantity <= reorder_level')->count();
            $outOfStockCount = InventoryStock::where('available_quantity', 0)->count();

            // Weekly movement trends
            $weeklyMovements = InventoryTransaction::selectRaw('
                DATE(created_at) as date,
                transaction_type,
                SUM(quantity) as total_quantity
            ')
            ->where('created_at', '>=', now()->subWeek())
            ->groupBy('date', 'transaction_type')
            ->orderBy('date')
            ->get();

            return [
                'total_products' => $totalProducts,
                'total_stock_value' => round($totalStockValue, 2),
                'low_stock_count' => $lowStockCount,
                'out_of_stock_count' => $outOfStockCount,
                'weekly_movements' => $weeklyMovements,
            ];
        } catch (\Exception $e) {
            Log::warning('Analytics calculation error: ' . $e->getMessage());
            return [
                'total_products' => 0,
                'total_stock_value' => 0,
                'low_stock_count' => 0,
                'out_of_stock_count' => 0,
                'weekly_movements' => [],
            ];
        }
    }

    /**
     * Get inventory locations
     */
    private function getInventoryLocations(): array
    {
        return [
            ['value' => 'main', 'label' => 'Main Warehouse'],
            ['value' => 'store', 'label' => 'Store'],
            ['value' => 'online', 'label' => 'Online Stock'],
        ];
    }

    /**
     * Generate stock valuation report
     */
    private function generateStockValuationReport(Request $request): array
    {
        $query = InventoryStock::with(['product:id,name,sku,cost_price'])
            ->select([
                'product_id',
                'location',
                'available_quantity',
                'reserved_quantity'
            ]);

        if ($request->filled('location')) {
            $query->where('location', $request->location);
        }

        $stocks = $query->get();

        $totalValue = 0;
        $reportData = [];

        foreach ($stocks as $stock) {
            $itemValue = $stock->available_quantity * ($stock->product->cost_price ?? 0);
            $totalValue += $itemValue;

            $reportData[] = [
                'product_name' => $stock->product->name,
                'sku' => $stock->product->sku,
                'location' => $stock->location,
                'quantity' => $stock->available_quantity,
                'cost_price' => $stock->product->cost_price ?? 0,
                'total_value' => round($itemValue, 2),
            ];
        }

        return [
            'summary' => ['total_value' => round($totalValue, 2)],
            'items' => $reportData,
        ];
    }

    /**
     * Generate movement report
     */
    private function generateMovementReport(Request $request): array
    {
        $query = InventoryTransaction::with(['product:id,name,sku'])
            ->select([
                'product_id',
                'location',
                'transaction_type',
                'quantity',
                'created_at',
                'notes'
            ]);

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('location')) {
            $query->where('location', $request->location);
        }

        $transactions = $query->orderBy('created_at', 'desc')->get();

        return [
            'total_transactions' => $transactions->count(),
            'transactions' => $transactions->map(function ($transaction) {
                return [
                    'product_name' => $transaction->product->name,
                    'sku' => $transaction->product->sku,
                    'location' => $transaction->location,
                    'type' => $transaction->transaction_type,
                    'quantity' => $transaction->quantity,
                    'date' => $transaction->created_at->format('Y-m-d H:i:s'),
                    'notes' => $transaction->notes,
                ];
            }),
        ];
    }

    /**
     * Generate low stock report
     */
    private function generateLowStockReport(Request $request): array
    {
        $query = InventoryStock::with(['product:id,name,sku'])
            ->whereRaw('available_quantity <= reorder_level');

        if ($request->filled('location')) {
            $query->where('location', $request->location);
        }

        $lowStockItems = $query->orderBy('available_quantity', 'asc')->get();

        return [
            'total_items' => $lowStockItems->count(),
            'items' => $lowStockItems->map(function ($stock) {
                return [
                    'product_name' => $stock->product->name,
                    'sku' => $stock->product->sku,
                    'location' => $stock->location,
                    'current_stock' => $stock->available_quantity,
                    'reorder_level' => $stock->reorder_level,
                    'shortage' => max(0, $stock->reorder_level - $stock->available_quantity),
                ];
            }),
        ];
    }

    /**
     * Generate location summary report
     */
    private function generateLocationSummaryReport(Request $request): array
    {
        $summary = InventoryStock::selectRaw('
            location,
            COUNT(DISTINCT product_id) as total_products,
            SUM(available_quantity) as total_quantity,
            SUM(reserved_quantity) as total_reserved
        ')
        ->groupBy('location')
        ->get();

        return [
            'locations' => $summary->map(function ($location) {
                return [
                    'location' => $location->location,
                    'total_products' => $location->total_products,
                    'total_quantity' => $location->total_quantity,
                    'total_reserved' => $location->total_reserved,
                    'available_quantity' => $location->total_quantity - $location->total_reserved,
                ];
            }),
        ];
    }
}
