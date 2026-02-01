<?php

namespace App\Http\Controllers\Admin\ProductGroup;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductGroup\StoreProductGroupRequest;
use App\Http\Requests\Admin\ProductGroup\UpdateProductGroupRequest;
use App\Models\ProductGroup;
use App\Services\ProductGroup\ProductGroupService;
use App\Helpers\ImageHelper;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProductGroupController extends Controller
{
    public function __construct(
        protected ProductGroupService $service
    ) {}

    public function index()
    {
        return Inertia::render('Admin/ProductGroups/Index', [
            'productGroups' => $this->service->paginate()
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/ProductGroups/Create');
    }

    public function store(StoreProductGroupRequest $request)
    {
        $data = $request->validated();
        
        if ($request->hasFile('banner')) {
            $data['banner'] = ImageHelper::uploadImage($request->file('banner'), 'uploads/product_groups');
        }

        $this->service->create($data);

        return redirect()->route('admin.product-groups.index')
            ->with('success', 'Product group created successfully.');
    }

    public function edit(ProductGroup $productGroup)
    {
        return Inertia::render('Admin/ProductGroups/Edit', [
            'productGroup' => $productGroup->load('products:id,name,product_code,feature_image,price')
        ]);
    }

    public function update(UpdateProductGroupRequest $request, ProductGroup $productGroup)
    {
        $data = $request->validated();

        if ($request->hasFile('banner')) {
            // Delete old banner if exists
            if ($productGroup->banner) {
                ImageHelper::deleteImage($productGroup->banner);
            }
            $data['banner'] = ImageHelper::uploadImage($request->file('banner'), 'uploads/product_groups');
        }

        $this->service->update($productGroup, $data);

        return redirect()->route('admin.product-groups.index')
            ->with('success', 'Product group updated successfully.');
    }

    public function destroy(ProductGroup $productGroup)
    {
        if ($productGroup->banner) {
            ImageHelper::deleteImage($productGroup->banner);
        }
        
        $this->service->delete($productGroup);

        return redirect()->route('admin.product-groups.index')
            ->with('success', 'Product group deleted successfully.');
    }

    public function toggleStatus(ProductGroup $productGroup)
    {
        $this->service->toggleStatus($productGroup);

        return redirect()->back()
            ->with('success', 'Status updated successfully.');
    }

    public function searchProducts(Request $request)
    {
        $query = $request->get('query');
        if (empty($query)) {
            return response()->json([]);
        }

        return response()->json($this->service->searchProducts($query));
    }
}
