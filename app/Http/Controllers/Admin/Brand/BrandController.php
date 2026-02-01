<?php

namespace App\Http\Controllers\Admin\Brand;

use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\Brand\BrandService;
use App\Http\Requests\Brand\StoreBrandRequest;
use App\Models\Brand;
use Illuminate\Support\Facades\Cache;

class BrandController extends Controller
{
    protected $brandService;

    public function __construct(BrandService $brandService)
    {
        $this->brandService = $brandService;
    }

    public function index(){
        $brands = $this->brandService->getAllBrands();
        return Inertia::render("Admin/Brand/Index", compact("brands"));
    }

    public function create(){
        return Inertia::render("Admin/Brand/Create");
    }

    public function store(StoreBrandRequest $request)
    {
        try {

            // clear product  cache
             Cache::forget('products.all');
            $this->brandService->createBrand($request->validated());



            return redirect()
                ->route('admin.brands.index')
                ->with('success', 'Brand created successfully.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Failed to create brand: ' . $e->getMessage());
        }
    }


        /**
     * Show the form for editing the specified brand.
     */
    public function edit(Brand $brand)
    {
        return Inertia::render('Admin/Brand/Edit', compact('brand'));
    }

    /**
     * Update the specified brand in storage.
     */
    public function update(StoreBrandRequest $request, Brand $brand)
    {
        try {
            
            $this->brandService->updateBrand($brand, $request->validated());
            Cache::flush();

            return redirect()
                ->route('admin.brands.index')
                ->with('success', 'Brand updated successfully.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Failed to update brand: ' . $e->getMessage());
        }
    }




    public function updateStatus(Request $request, $id)
{
    try {
        $request->validate([
            'status' => 'required|in:active,deactive'
        ]);

        $brand = $this->brandService->updateStatus($id, $request->status);

        return response()->json([
            'success' => true,
            'message' => 'Status updated successfully',
            'brand' => $brand
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to update status'
        ], 500);
    }
}

    /**
     * Remove the specified brand from storage.
     */
    public function destroy(Brand $brand)
    {
        try {
            $this->brandService->deleteBrand($brand);

            return redirect()
                ->route('admin.brands.index')
                ->with('success', 'Brand deleted successfully.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Failed to delete brand: ' . $e->getMessage());
        }
    }



}
