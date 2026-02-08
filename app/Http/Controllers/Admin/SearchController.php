<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    /**
     * Global sidebar search - Uses TNT Search for products (fuzzy), LIKE for orders/users
     */
    public function search(Request $request)
    {
        $query = trim($request->input('q', ''));

        if (strlen($query) < 2) {
            return response()->json(['results' => []]);
        }

        $results = [];

        try {
            // 1. Search Products using TNT Search (fuzzy search)
            $products = Product::search($query)
                ->take(5)
                ->get()
                ->map(function ($product) {
                    return [
                        'title' => $product->name,
                        'subtitle' => ($product->product_code ? "SKU: {$product->product_code} · " : '') . "৳{$product->price}",
                        'type' => 'Product',
                        'url' => '/admin/products/' . $product->id,
                        'icon' => 'Package',
                    ];
                })
                ->toArray();
        } catch (\Exception $e) {
            // Fallback to LIKE search if TNT fails
            $products = Product::where(function ($q) use ($query) {
                    $q->where('name', 'LIKE', "%{$query}%")
                      ->orWhere('product_code', 'LIKE', "%{$query}%")
                      ->orWhere('barcode', 'LIKE', "%{$query}%")
                      ->orWhere('search_keywords', 'LIKE', "%{$query}%");
                })
                ->limit(5)
                ->get(['id', 'name', 'product_code', 'price'])
                ->map(function ($product) {
                    return [
                        'title' => $product->name,
                        'subtitle' => ($product->product_code ? "SKU: {$product->product_code} · " : '') . "৳{$product->price}",
                        'type' => 'Product',
                        'url' => '/admin/products/' . $product->id,
                        'icon' => 'Package',
                    ];
                })
                ->toArray();
        }

        try {
            // 2. Search Orders
            $orders = Order::where(function ($q) use ($query) {
                    $q->where('order_number', 'LIKE', "%{$query}%")
                      ->orWhere('customer_name', 'LIKE', "%{$query}%")
                      ->orWhere('customer_phone', 'LIKE', "%{$query}%");
                })
                ->latest()
                ->limit(5)
                ->get(['id', 'order_number', 'customer_name', 'total', 'status'])
                ->map(function ($order) {
                    $statusIcons = [
                        'pending' => '🟡',
                        'processing' => '🔵',
                        'delivered' => '🟢',
                        'cancelled' => '🔴',
                    ];

                    return [
                        'title' => $order->order_number,
                        'subtitle' => ($statusIcons[$order->status] ?? '⚪') . " {$order->customer_name} · ৳{$order->total}",
                        'type' => 'Order',
                        'url' => '/admin/orders/' . $order->id . '/edit',
                        'icon' => 'ShoppingCart',
                    ];
                })
                ->toArray();
        } catch (\Exception $e) {
            $orders = [];
        }

        try {
            // 3. Search Users (correct column: phone_number, not phone)
            $customers = User::where(function ($q) use ($query) {
                    $q->where('name', 'LIKE', "%{$query}%")
                      ->orWhere('email', 'LIKE', "%{$query}%")
                      ->orWhere('phone_number', 'LIKE', "%{$query}%");
                })
                ->limit(3)
                ->get(['id', 'name', 'email'])
                ->map(function ($user) {
                    return [
                        'title' => $user->name,
                        'subtitle' => $user->email,
                        'type' => 'Customer',
                        'url' => '/admin/users',
                        'icon' => 'Users',
                    ];
                })
                ->toArray();
        } catch (\Exception $e) {
            $customers = [];
        }

        $results = array_merge($products, $orders, $customers);

        return response()->json([
            'results' => $results,
            'total' => count($results),
        ]);
    }
}
