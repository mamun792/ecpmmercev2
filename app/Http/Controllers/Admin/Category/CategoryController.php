<?php

namespace App\Http\Controllers\Admin\Category;

use Inertia\Inertia;
use App\Models\Category;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\Cache\Store;
use Illuminate\Support\Facades\Cache;
use App\Services\Categories\CategoryService;
use App\Http\Requests\Categories\StoreCategoryRequest;
use App\Http\Requests\Categories\UpdateCategoryRequest;

class CategoryController extends Controller
{


    protected $categoryService;
    public function __construct( CategoryService $categoryService)
    {
        $this->categoryService = $categoryService;

    }


    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $categories = $this->categoryService->getAllCategories();
        //return $categories;
        return Inertia::render('Admin/Categories/Index', ['categories' => $categories]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $categories = $this->categoryService->getAllCategories();
        //return $categories;
        return Inertia::render('Admin/Categories/Create', ['categories' => $categories]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCategoryRequest $request)
    {
        try {

            $category = $this->categoryService->createCategory($request->validated());

            // Clear categories cache
            Cache::forget('categories.all');

            return redirect()
                ->route('admin.categories.index')
                ->with('success', 'Category created successfully.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Failed to create category: ' . $e->getMessage());
        }
    }


    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Category $category)
    {
        $categories = $this->categoryService->getAllCategories();
        return Inertia::render('Admin/Categories/Edit', ['category'=> $category,'categories'=> $categories]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCategoryRequest $request, Category $category)
    {
        try {
            $categoryService = new CategoryService();
            $categoryService->updateCategory($category, $request->validated());

            // Clear categories cache
            Cache::forget('categories.all');

            return redirect()
                ->route('admin.categories.index')
                ->with('success', 'Category updated successfully.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Failed to update category: ' . $e->getMessage());
        }
    }


    public function updateStatus(Request $request, $id)
    {
        try {
            $request->validate([
                'status' => 'required|in:active,deactive'
            ]);

            $category = $this->categoryService->updateStatus($id, $request->status);

            // Clear categories cache
            Cache::flush();

            return response()->json([
                'success' => true,
                'message' => 'Status updated successfully',
                'category' => $category
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update status'
            ], 500);
        }
    }

    public function updateOrder(Request $request, $id)
    {
        $request->validate([
            'order' => 'required|integer|min:0',
        ]);

        $category = Category::findOrFail($id);
        $category->update([
            'order' => $request->order,
        ]);

        // Clear categories cache
        Cache::flush();

        return response()->json(['success' => true, 'message' => 'Order updated successfully']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Category $category)
    {
        try {
            $categoryService = new CategoryService();
            $result = $categoryService->deleteCategory($category);

            // Clear categories cache
            Cache::forget('categories.all');

            // Build success message with usage info
            $message = 'Category deleted successfully.';
            if ($result['has_products'] || $result['has_orders']) {
                $message .= ' (Note: This category was used in ';
                $parts = [];
                if ($result['has_products']) $parts[] = $result['products_count'] . ' product(s)';
                if ($result['has_orders']) $parts[] = $result['orders_count'] . ' order(s)';
                $message .= implode(' and ', $parts) . '. Historical data will still show this category.)';
            }

            return redirect()
                ->route('admin.categories.index')
                ->with('success', $message);
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Failed to delete category: ' . $e->getMessage());
        }
    }
}
