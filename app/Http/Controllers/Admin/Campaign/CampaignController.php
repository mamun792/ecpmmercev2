<?php

namespace App\Http\Controllers\Admin\Campaign;

use Inertia\Inertia;
use App\Models\Campaign;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;

class CampaignController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $campaigns = Campaign::with('products')
            ->when($request->search, function ($query) use ($request) {
                $query->where('name', 'like', '%' . $request->search . '%');
            })
            ->when($request->status, function ($query) use ($request) {
                $query->where('status', $request->status);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return Inertia::render('Admin/Campaign/Index', [
            'campaigns' => $campaigns,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $products = Product::select('id', 'name', 'feature_image', 'price', 'stock')->get();

        return Inertia::render('Admin/Campaign/Create', [
            'products' => $products,
        ]);
    }

    /**
     * Search products for campaign.
     */
    public function searchProducts(Request $request)
    {
        $search = $request->get('q');
        $products = Product::select('id', 'name', 'feature_image', 'price', 'stock')
            ->when($search, function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('product_code', 'like', "%{$search}%");
            })
            ->limit(20)
            ->get();

        return response()->json($products);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after:start_date',
            'status' => 'required|in:active,inactive',
            'discount_type' => 'required|in:fixed,percentage',
            'discount_amount' => 'required|numeric|min:0',
            'product_ids' => 'required|array|min:1',
            'product_ids.*' => 'exists:products,id',
        ]);

        DB::transaction(function () use ($request) {
            $campaign = Campaign::create($request->only([
                'name', 'start_date', 'end_date', 'status', 'discount_type', 'discount_amount'
            ]));

            $campaign->products()->attach($request->product_ids);
        });

        Cache::flush();

        return redirect()->route('admin.campaigns.index')->with('success', 'Campaign created successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Campaign $campaign)
    {
        $campaign->load('products');

        return Inertia::render('Admin/Campaign/Show', [
            'campaign' => $campaign,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Campaign $campaign)
    {
        $campaign->load('products:id,name,feature_image,price,stock');
        $products = Product::select('id', 'name', 'feature_image', 'price', 'stock')->get();

        // Format dates for HTML date inputs
        if ($campaign->start_date instanceof \Illuminate\Support\Carbon) {
            $campaign->start_date = $campaign->start_date->format('Y-m-d');
        }
        if ($campaign->end_date instanceof \Illuminate\Support\Carbon) {
            $campaign->end_date = $campaign->end_date->format('Y-m-d');
        }

        return Inertia::render('Admin/Campaign/Edit', [
            'campaign' => $campaign,
            'products' => $products,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Campaign $campaign)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'status' => 'required|in:active,inactive',
            'discount_type' => 'required|in:fixed,percentage',
            'discount_amount' => 'required|numeric|min:0',
            'product_ids' => 'required|array|min:1',
            'product_ids.*' => 'exists:products,id',
        ]);

        DB::transaction(function () use ($request, $campaign) {
            $campaign->update($request->only([
                'name', 'start_date', 'end_date', 'status', 'discount_type', 'discount_amount'
            ]));

            $campaign->products()->sync($request->product_ids);
        });

        Cache::flush();

        return redirect()->route('admin.campaigns.index')->with('success', 'Campaign updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Campaign $campaign)
    {
        $campaign->delete();
        Cache::flush();

        return redirect()->route('admin.campaigns.index')->with('success', 'Campaign deleted successfully!');
    }
}