<?php

namespace App\Http\Controllers\Admin\Product;

use Inertia\Inertia;
use App\Models\Product;
use Illuminate\Support\Str;
use App\Helpers\ImageHelper;
use Illuminate\Http\Request;
use App\Traits\ClearsProductCache;
use App\Models\AttributeValue;
use App\Models\ProductVariation;
use App\Models\VariationAttribute;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\DTOs\ProductStoreDTO;
use App\Contracts\ProductRepositoryInterface;
use App\Services\Inventory\InventoryService;
use App\Services\Product\ProductCreationService;
use App\Services\Product\ProductService;
use App\DTOs\ProductUpdateDTO;
use App\DTOs\ProductFilterDTO;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Models\Category;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    use ClearsProductCache;

    protected ProductRepositoryInterface $productRepository;
    protected InventoryService $inventoryService;
    protected ProductCreationService $productCreationService;
    protected ProductService $productService;

    public function __construct(
        ProductRepositoryInterface $productRepository,
        InventoryService $inventoryService,
        ProductCreationService $productCreationService,
        ProductService $productService
    ) {
        $this->productRepository = $productRepository;
        $this->inventoryService = $inventoryService;
        $this->productCreationService = $productCreationService;
        $this->productService = $productService;
    }


    /**
     * Display a listing of the resource with advanced filtering
     */
    public function index(Request $request)
    {
        try {
            $filters = ProductFilterDTO::fromRequest($request->all());
            $products = $this->productRepository->findPaginated($filters, $request->get('per_page', 50));

            // Get additional data for filters
            $categories = Category::select('id', 'name')->where('status', 'active')->get();
            $brands = \App\Models\Brand::select('id', 'brand_name as name')->where('status', 'active')->get();

            // Get optimized stats in 1-2 queries instead of 4
            $stats = $this->productRepository->getProductStats();

            return Inertia::render('Admin/Product/Index', [
                'products' => $products,
                'filters' => $filters->toArray(),
                'categories' => $categories,
                'brands' => $brands,
                'stats' => $stats,
            ]);
        } catch (\Exception $e) {
            Log::error('Product Index Error: ' . $e->getMessage());
            return Inertia::render('Admin/Product/Index', [
                'products' => [],
                'error' => 'Unable to load products.'
            ]);
        }
    }

    /**
     * Show the form for creating a new resource
     */
    public function create()
    {
        try {
            $formData = $this->productCreationService->getFormData();

            return Inertia::render('Admin/Product/Create', [
                'categories' => $formData['categories'],
                'brands' => $formData['brands'],
                'attributes' => $formData['attributes'],
                'locations' => $formData['inventory_locations'],
                'errors' => session('errors') ? session('errors')->getBag('default')->getMessages() : [],
            ]);
        } catch (\Exception $e) {
            Log::error('Product Create Page Error: ' . $e->getMessage());
            return redirect()
                ->route('admin.products.index')
                ->with('error', 'Unable to load product creation form.');
        }
    }

    /**
     * Store a newly created resource in storage.
     * Uses Big Tech Style ProductCreationService with proper transaction handling
     */
    public function store(StoreProductRequest $request)
    {
        try {
            Log::info('🚀 [ProductController] Product Store Request', [
                'name' => $request->name,
                'type' => $request->type,
                'has_feature_image' => $request->hasFile('feature_image'),
                'variations_count' => is_array($request->variations) ? count($request->variations) : 0,
            ]);

            // Use the Big Tech Style ProductCreationService
            // Handles: images, variations, attributes, inventory stock - ALL in one transaction
            $result = $this->productCreationService->createProduct($request);

            // Handle special case: Restore option for soft-deleted product (BIG TECH STYLE)
            if (isset($result['action']) && $result['action'] === 'restore_option') {
                return back()
                    ->with('restore_option', [
                        'message' => $result['error'],
                        'deleted_product' => $result['deleted_product']
                    ])
                    ->withInput();
            }

            if (!$result['success']) {
                Log::error('❌ [ProductController] Product Store Failed', ['error' => $result['error']]);
                return back()
                    ->with('error', $result['error'])
                    ->withInput();
            }

            Log::info('✅ [ProductController] Product Store Success', [
                'product_id' => $result['product']->id,
                'name' => $result['product']->name,
            ]);

            // Clear product cache
            $this->clearProductCache();

            return redirect()
                ->route('admin.products.index')
                ->with('success', 'Product created successfully with all variations, images, and inventory!');

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Validation errors are automatically handled by Laravel
            throw $e;
        } catch (\Exception $e) {
            Log::error('❌ [ProductController] Product Store Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return back()
                ->with('error', 'An unexpected error occurred: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Restore a soft-deleted product (Big Tech Style)
     * Called when user chooses to restore instead of creating duplicate
     */
    public function restore(Request $request)
    {
        try {
            $productId = $request->input('product_id');

            Log::info('🔄 [ProductController] Restoring soft-deleted product', [
                'product_id' => $productId
            ]);

            $result = $this->productCreationService->restoreSoftDeletedProduct($productId);

            if (!$result['success']) {
                return back()
                    ->with('error', $result['error'])
                    ->withInput();
            }

            // Clear product cache
            $this->clearProductCache();

            return redirect()
                ->route('admin.products.edit', $result['product'])
                ->with('success', $result['message'] . ' You can now edit it.');

        } catch (\Exception $e) {
            Log::error('❌ [ProductController] Product Restore Failed', [
                'error' => $e->getMessage()
            ]);

            return back()
                ->with('error', 'Failed to restore product: ' . $e->getMessage())
                ->withInput();
        }
    }


    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $product = Product::with([
            'category:id,name,slug',
            'brand:id,brand_name',
            'variations.inventoryStock',
            'inventoryStocks'
        ])->findOrFail($id);

        return Inertia::render('Admin/Product/Show', [
            'product' => $product
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Product $product)
    {
        try {
            $attributeValues = $this->productService->getAllAttributes();
            $categories = $this->productService->getAllCategories();
            $brands = $this->productService->getAllBrands();

            $product = $product->load([
                'category:id,name,slug',
                'brand:id,brand_name',
                'variations.inventoryStock',
                'variations.attributes.value.attribute:id,name',
                'variations.attributeValues.attribute:id,name',
                'inventoryStocks'
            ]);

            // Explicitly format variations to ensure inventoryStock is included
            $product->variations->transform(function ($variation) {
                $variation->inventoryStock;  // Access to ensure it's loaded
                return $variation;
            });

            $locations = $this->productCreationService->getFormData()['inventory_locations'];

            return Inertia::render('Admin/Product/Edit', [
                'product' => $product,
                'attributeValues' => $attributeValues,
                'categories'=> $categories,
                'brands'=> $brands,
                'locations' => $locations,
                'errors' => session('errors') ? session('errors')->getBag('default')->getMessages() : [],
            ]);
        } catch (\Exception $e) {
            Log::error('Product Edit Page Error: ' . $e->getMessage());
            return redirect()
                ->route('admin.products.index')
                ->with('error', 'Unable to load product edit form.');
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProductRequest $request, Product $product)
    {
        try {
            Log::info('Product Update Request Data:', $request->validated());

            $result = $this->productService->updateProduct($request, $product);

            Log::info('Product Update Result:', $result);

            if ($result['success']) {
                // Clear all product caches after successful update
                $this->clearAllProductCaches();

                // Clear specific product cache
                if ($product->slug) {
                    $this->clearSingleProductCache($product->slug);
                }

                return redirect()
                    ->route('admin.products.index')
                    ->with('success', 'Product updated successfully!');
            }

            // For business logic errors, return back with error flash message
            $errorMessage = 'Failed to update product: ' . $result['error'];

            // Check if there's a deleted product conflict
            if (isset($result['deleted_product'])) {
                $deletedInfo = $result['deleted_product'];
                $errorMessage = $result['error'] . "\n\nDeleted Product Details:\n" .
                    "Name: {$deletedInfo['name']}\n" .
                    "Code: {$deletedInfo['product_code']}\n" .
                    "Deleted At: {$deletedInfo['deleted_at']}";
            }

            return back()
                ->with('error', $errorMessage)
                ->withInput();

        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Product Update Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return back()
                ->with('error', 'An unexpected error occurred. Please try again.')
                ->withInput();
        }
    }



    public function quickEdit(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:Published,Unpublished',
            'is_free_delivery' => 'required|boolean',
            'remarks' => 'nullable|in:New,Popular,Trending,Hot,Special'
        ]);

        // Explicitly handle null remarks
        $remarks = $request->filled('remarks') ? $request->remarks : null;

        $result = $this->productService->quickUpdate($id, [
            'status' => $request->status,
            'is_free_delivery' => $request->is_free_delivery,
            'remarks' => $remarks,
        ]);

        if ($result['success']) {
            // Clear all product caches after quick update
            $this->clearAllProductCaches();

            return redirect()->back()->with('success', 'Product updated successfully!');
        }

        return redirect()->back()->with('error', 'An error occurred while updating the product.');
    }


    public function productDescriptionImageUpload(Request $request)
    {
        if ($request->hasFile('upload')) {
            $filePath = ImageHelper::uploadImage($request->file('upload'), 'storage/products/description');
            $url = asset($filePath);

            // Store the image path in the database
            $product = Product::find($request->product_id);
            if ($product) {
                $images = $product->description_images ?? [];
                $images[] = $filePath;
                $product->description_images = $images;
                $product->save();
            }

            return response()->json([
                'fileName' => basename($filePath),
                'uploaded' => 1,
                'url' => $url
            ]);
        }

        return response()->json(['uploaded' => 0, 'error' => ['message' => 'File not uploaded']], 400);
    }



    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $result = $this->productService->deleteProduct($id);

        if ($result['success']) {
            // Clear all product caches after deletion
            $this->clearAllProductCaches();
        }

        return redirect()->route('admin.products.index')->with(
            $result['success'] ? 'success' : 'error',
            $result['message']
        );
    }



    public function bulkDeleteProducts(Request $request)
    {
        $request->validate([
            'product_ids' => 'required|array',
            'product_ids.*' => 'exists:products,id',
            'confirmation' => 'required|string|in:confirm'
        ]);

        try {
            $result = $this->productService->bulkDeleteProducts($request->product_ids);

            if ($result['success']) {
                // Clear all product caches after bulk deletion
                $this->clearAllProductCaches();
            }

            return redirect()->back()->with($result['success'] ? 'success' : 'error', $result['message']);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'An error occurred: ' . $e->getMessage());
        }
    }

    /**
     * Bulk update product status (Published/Unpublished)
     */
    public function bulkUpdateStatus(Request $request)
    {
        $request->validate([
            'product_ids' => 'required|array',
            'product_ids.*' => 'exists:products,id',
            'status' => 'required|in:Published,Unpublished'
        ]);

        try {
            $count = Product::whereIn('id', $request->product_ids)->update([
                'status' => $request->status,
                'updated_at' => now()
            ]);

            $this->clearAllProductCaches();

            return redirect()->back()->with('success', "{$count} products updated to {$request->status}");
        } catch (\Exception $e) {
            Log::error('Bulk Status Update Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to update product status');
        }
    }

    /**
     * Bulk update product prices (percentage increase/decrease)
     */
    public function bulkUpdatePrice(Request $request)
    {
        $request->validate([
            'product_ids' => 'required|array',
            'product_ids.*' => 'exists:products,id',
            'price_type' => 'required|in:increase,decrease',
            'price_value' => 'required|numeric|min:0|max:100', // Percentage
        ]);

        try {
            $products = Product::whereIn('id', $request->product_ids)->get();
            $count = 0;

            foreach ($products as $product) {
                if ($product->price > 0) {
                    $adjustment = ($product->price * $request->price_value) / 100;
                    $newPrice = $request->price_type === 'increase'
                        ? $product->price + $adjustment
                        : max(0, $product->price - $adjustment); // Prevent negative prices

                    $product->update(['price' => round($newPrice, 2)]);
                    $count++;
                }
            }

            $this->clearAllProductCaches();

            $action = $request->price_type === 'increase' ? 'increased' : 'decreased';
            return redirect()->back()->with('success', "{$count} product prices {$action} by {$request->price_value}%");
        } catch (\Exception $e) {
            Log::error('Bulk Price Update Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to update product prices');
        }
    }

    /**
     * Bulk assign category to products
     */
    public function bulkAssignCategory(Request $request)
    {
        $request->validate([
            'product_ids' => 'required|array',
            'product_ids.*' => 'exists:products,id',
            'category_id' => 'required|exists:categories,id'
        ]);

        try {
            $count = Product::whereIn('id', $request->product_ids)->update([
                'category_id' => $request->category_id,
                'updated_at' => now()
            ]);

            $this->clearAllProductCaches();

            $category = Category::find($request->category_id);
            return redirect()->back()->with('success', "{$count} products assigned to category: {$category->name}");
        } catch (\Exception $e) {
            Log::error('Bulk Category Assignment Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to assign category');
        }
    }

    /**
     * Clone/duplicate a product
     */
    public function cloneProduct(Product $product)
    {
        try {
            DB::beginTransaction();

            // Clone main product
            $cloned = $product->replicate();
            $cloned->name = $product->name . ' (Copy)';
            $cloned->product_code = $product->product_code . '-COPY-' . Str::random(4);
            $cloned->slug = Str::slug($cloned->name) . '-' . Str::random(6);
            $cloned->status = 'Unpublished'; // Safety: cloned products start as draft
            $cloned->save();

            // Clone variations if exists
            if ($product->type === 'variable' && $product->variations->count() > 0) {
                foreach ($product->variations as $variation) {
                    $clonedVariation = $variation->replicate();
                    $clonedVariation->product_id = $cloned->id;
                    $clonedVariation->save();

                    // Clone variation attributes
                    foreach ($variation->attributes as $attribute) {
                        $clonedVariation->attributes()->create([
                            'attribute_value_id' => $attribute->attribute_value_id
                        ]);
                    }

                    // Clone inventory stock
                    if ($variation->inventoryStock) {
                        $clonedVariation->inventoryStock()->create([
                            'inventory_location_id' => $variation->inventoryStock->inventory_location_id,
                            'available_quantity' => 0, // Start with 0 stock
                            'reserved_quantity' => 0
                        ]);
                    }
                }
            }

            DB::commit();
            $this->clearAllProductCaches();

            return redirect()->route('admin.products.edit', $cloned->id)
                ->with('success', "Product cloned successfully! Edit and publish when ready.");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Product Clone Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to clone product');
        }
    }

    /**
     * Get inventory locations for dropdown
     */
    private function getInventoryLocations(): array
    {
        return [
            ['value' => 'main', 'label' => 'Main Warehouse'],
            ['value' => 'store', 'label' => 'Store'],
            ['value' => 'online', 'label' => 'Online Stock'],
        ];
    }

}
