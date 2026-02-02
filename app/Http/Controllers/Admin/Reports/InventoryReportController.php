<?php

namespace App\Http\Controllers\Admin\Reports;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf;

class InventoryReportController extends Controller
{
    /**
     * Display inventory report dashboard V2
     */
    public function index(Request $request)
    {
        // Fetch products with relationships
        $query = Product::with([
            'category',
            'variations.attributes.value' => function($query) {
                $query->withTrashed();
            },
            'variations.attributes.value.attribute' => function($query) {
                $query->withTrashed();
            }
        ]);

        // Apply search filter
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        // Apply category filter
        if ($request->filled('category')) {
            $query->whereHas('category', function($q) use ($request) {
                $q->where('name', $request->input('category'));
            });
        }

        // Apply stock status filter
        if ($request->filled('stock_status') && $request->input('stock_status') !== 'all') {
            $status = $request->input('stock_status');

            if ($status === 'out_of_stock') {
                $query->where(function($q) {
                    $q->where('type', 'simple')->where('stock', 0)
                      ->orWhere(function($q2) {
                          $q2->where('type', 'variable')
                             ->whereDoesntHave('variations', function($q3) {
                                 $q3->where('stock', '>', 0);
                             });
                      });
                });
            } elseif ($status === 'low_stock') {
                $query->where(function($q) {
                    $q->where(function($q1) {
                        $q1->where('type', 'simple')
                           ->whereRaw('stock > 0 AND stock <= COALESCE(reorder_level, 10)');
                    })->orWhere(function($q2) {
                        $q2->where('type', 'variable')
                           ->whereHas('variations', function($q3) {
                               $q3->whereRaw('stock > 0 AND stock <= 10');
                           });
                    });
                });
            } elseif ($status === 'in_stock') {
                $query->where(function($q) {
                    $q->where(function($q1) {
                        $q1->where('type', 'simple')
                           ->whereRaw('stock > COALESCE(reorder_level, 10)');
                    })->orWhere(function($q2) {
                        $q2->where('type', 'variable')
                           ->whereHas('variations', function($q3) {
                               $q3->where('stock', '>', 10);
                           });
                    });
                });
            }
        }

        // Apply sorting (Note: stock/sold will be sorted after computation in collection)
        $sortBy = $request->input('sort_by', 'name');
        $sortDirection = $request->input('sort_direction', 'asc');

        switch ($sortBy) {
            case 'price':
                $query->orderBy('price', $sortDirection);
                break;
            case 'category':
                $query->join('categories', 'products.category_id', '=', 'categories.id')
                      ->orderBy('categories.name', $sortDirection)
                      ->select('products.*');
                break;
            default:
                $query->orderBy('name', $sortDirection);
        }

        $products = $query->get();

        // Filter out variations with soft-deleted attributes AND calculate stock/sold for each product
        $products->each(function ($product) {
            if ($product->type === 'variable') {
                $filteredVariations = $product->variations->filter(function ($variation) {
                    return $variation->attributes->every(function ($attr) {
                        return $attr->value && !$attr->value->trashed();
                    });
                })->values();

                $product->setRelation('variations', $filteredVariations);

                // Calculate stock from inventory_stocks table
                $product->computed_stock = \DB::table('inventory_stocks')
                    ->whereIn('product_variation_id', $filteredVariations->pluck('id'))
                    ->sum('available_quantity');

                // Calculate sold from order_items (completed orders only)
                $product->computed_sold = \DB::table('order_items')
                    ->join('orders', 'order_items.order_id', '=', 'orders.id')
                    ->whereIn('order_items.product_variation_id', $filteredVariations->pluck('id'))
                    ->whereIn('orders.status', ['pending', 'processing', 'shipped', 'delivered', 'completed'])
                    ->sum('order_items.quantity');

                // Add variation count and info
                $product->variation_count = $filteredVariations->count();

                // Use first variation price if product price is 0 or null
                if (!$product->price || $product->price == 0) {
                    $firstVariation = $filteredVariations->first();
                    $product->display_price = $firstVariation ? $firstVariation->price : 0;
                } else {
                    $product->display_price = $product->price;
                }
            } else {
                // For simple products, get stock from inventory_stocks table
                $product->computed_stock = \DB::table('inventory_stocks')
                    ->where('product_id', $product->id)
                    ->whereNull('product_variation_id')
                    ->sum('available_quantity');

                // Calculate sold from order_items (completed orders only)
                $product->computed_sold = \DB::table('order_items')
                    ->join('orders', 'order_items.order_id', '=', 'orders.id')
                    ->where('order_items.product_id', $product->id)
                    ->whereNull('order_items.product_variation_id')
                    ->whereIn('orders.status', ['pending', 'processing', 'shipped', 'delivered', 'completed'])
                    ->sum('order_items.quantity');

                $product->variation_count = 0;
                $product->display_price = $product->price ?? 0;
            }
        });

        // Sort by stock/sold after computation
        if ($sortBy === 'stock') {
            $products = $sortDirection === 'asc'
                ? $products->sortBy('computed_stock')
                : $products->sortByDesc('computed_stock');
        } elseif ($sortBy === 'sold') {
            $products = $sortDirection === 'asc'
                ? $products->sortBy('computed_sold')
                : $products->sortByDesc('computed_sold');
        }

        // Calculate statistics
        $totalProducts = $products->count();

        $totalStock = $products->sum('computed_stock');
        $totalSold = $products->sum('computed_sold');

        $totalValue = $products->sum(function ($product) {
            return $product->computed_stock * $product->display_price;
        });

        return Inertia::render('Admin/Reports/InventoryV2', [
            'products' => $products,
            'totalProducts' => $totalProducts,
            'totalStock' => $totalStock,
            'totalSold' => $totalSold,
            'totalValue' => round($totalValue, 2),
            'filters' => [
                'search' => $request->input('search'),
                'category' => $request->input('category'),
                'stock_status' => $request->input('stock_status', 'all'),
                'sort_by' => $sortBy,
                'sort_direction' => $sortDirection,
            ],
        ]);
    }

    /**
     * Export inventory report as PDF
     */
    public function exportPDF(Request $request)
    {
        // Get products with same filters as index
        $query = Product::with(['category', 'variations']);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category')) {
            $query->whereHas('category', function($q) use ($request) {
                $q->where('name', $request->input('category'));
            });
        }

        $sortBy = $request->input('sort_by', 'name');
        $sortDirection = $request->input('sort_direction', 'asc');

        switch ($sortBy) {
            case 'stock':
                $query->orderBy('stock', $sortDirection);
                break;
            case 'sold':
                $query->orderBy('sold_stock', $sortDirection);
                break;
            case 'price':
                $query->orderBy('price', $sortDirection);
                break;
            default:
                $query->orderBy('name', $sortDirection);
        }

        $products = $query->get();

        // Calculate stats
        $totalProducts = $products->count();
        $totalStock = $products->sum(function ($product) {
            return $product->type === 'simple' ? $product->stock : $product->variations->sum('stock');
        });
        $totalSold = $products->sum(function ($product) {
            return $product->type === 'simple' ? $product->sold_stock : $product->variations->sum('sold_stock');
        });
        $totalValue = $products->sum(function ($product) {
            if ($product->type === 'simple') {
                return $product->stock * $product->price;
            }
            return $product->variations->sum(fn($v) => $v->stock * $v->price);
        });

        $data = [
            'products' => $products,
            'totalProducts' => $totalProducts,
            'totalStock' => $totalStock,
            'totalSold' => $totalSold,
            'totalValue' => $totalValue,
            'generatedAt' => now()->format('d M Y, h:i A'),
        ];

        $pdf = Pdf::loadView('reports.inventory-pdf', $data);
        $pdf->setPaper('a4', 'portrait');

        return $pdf->download('inventory-report-' . now()->format('Y-m-d') . '.pdf');
    }

    /**
     * Export inventory report as CSV
     */
    public function exportCSV(Request $request)
    {
        // Get products with same filters
        $query = Product::with(['category', 'variations']);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category')) {
            $query->whereHas('category', function($q) use ($request) {
                $q->where('name', $request->input('category'));
            });
        }

        $products = $query->orderBy('name')->get();

        // Create CSV content
        $filename = 'inventory-report-' . now()->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($products) {
            $file = fopen('php://output', 'w');

            // Add BOM for UTF-8
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // Header row
            fputcsv($file, [
                'Product Name',
                'SKU',
                'Category',
                'Type',
                'Stock Quantity',
                'Sold Quantity',
                'Price (৳)',
                'Stock Value (৳)',
                'Status'
            ]);

            // Data rows
            foreach ($products as $product) {
                $stock = $product->type === 'simple'
                    ? $product->stock
                    : $product->variations->sum('stock');

                $sold = $product->type === 'simple'
                    ? $product->sold_stock
                    : $product->variations->sum('sold_stock');

                $stockValue = $product->type === 'simple'
                    ? $product->stock * $product->price
                    : $product->variations->sum(fn($v) => $v->stock * $v->price);

                $status = $stock === 0 ? 'Out of Stock'
                    : ($stock <= ($product->reorder_level ?? 10) ? 'Low Stock' : 'In Stock');

                fputcsv($file, [
                    $product->name,
                    $product->sku ?? 'N/A',
                    $product->category->name ?? 'Uncategorized',
                    $product->type === 'simple' ? 'Simple' : 'Variable',
                    $stock,
                    $sold,
                    number_format($product->price, 2),
                    number_format($stockValue, 2),
                    $status
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
