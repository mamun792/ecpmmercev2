<?php

namespace App\Http\Controllers\Frontend\Product;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Attribute;
use App\Services\Product\ProductService;
use App\Services\Categories\CategoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use App\Services\ProductGroup\ProductGroupService;

class ProductController extends Controller
{
    protected $productService;
    protected $categoryService;
    protected $productGroupService;

    public function __construct(ProductService $productService, CategoryService $categoryService, ProductGroupService $productGroupService)
    {
        $this->productService = $productService;
        $this->categoryService = $categoryService;
        $this->productGroupService = $productGroupService;
    }

    /**
     * Product listing/filter page (Inertia page)
     */
    public function index(Request $request)
    {
        // Get filter parameters
        $search = $request->input('search', '');
        $categorySlug = $request->input('category', '');
        $categoryIds = $request->input('category_ids', []);
        $brandIds = $request->input('brand_ids', []);
        $minPrice = $request->input('min_price', null);
        $maxPrice = $request->input('max_price', null);
        $sortBy = $request->input('sort_by', 'latest');
        $perPage = $request->input('per_page', 12);
        $attributeFilters = $request->input('attributes', []); // e.g., ['Size' => ['XL', 'L'], 'Color' => ['Red']]

            // Build filters payload and delegate to ProductService
        $filterPayload = $request->only([
            'search', 'category', 'category_ids', 'brand_ids', 'min_price', 'max_price', 'sort_by', 'per_page', 'attributes'
        ]);

        $products = $this->productService->getFilteredProducts($filterPayload);

        $filterOptions = $this->productService->getFilterOptions();

        return Inertia::render('Products/Index', [
            'products' => $products,
            'filters' => $filterOptions,
            'appliedFilters' => [
                'search' => $filterPayload['search'] ?? '',
                'category' => $filterPayload['category'] ?? '',
                'category_ids' => $filterPayload['category_ids'] ?? [],
                'brand_ids' => $filterPayload['brand_ids'] ?? [],
                'min_price' => $filterPayload['min_price'] ?? null,
                'max_price' => $filterPayload['max_price'] ?? null,
                'sort_by' => $filterPayload['sort_by'] ?? $sortBy,
                'attributes' => $filterPayload['attributes'] ?? [],
            ],
        ]);
    }

    /**
     * Search suggestions endpoint (AJAX - returns JSON)
     */
    public function searchSuggestions(Request $request)
    {
        $query = $request->input('q', '');
        $limit = $request->input('limit', 8);

        if (strlen($query) < 2) {
            return response()->json(['suggestions' => []]);
        }

        $cacheKey = 'search_suggestions_' . md5($query) . '_' . $limit;

        $suggestions = Cache::remember($cacheKey, 300, function () use ($query, $limit) {
            return Product::where('status', 'Published')
                ->where(function ($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%")
                        ->orWhere('product_code', 'like', "%{$query}%")
                        ->orWhere('product_tags', 'like', "%{$query}%");
                })
                ->with(['category:id,name,slug'])
                ->select(['id', 'name', 'slug', 'price', 'previous_price', 'feature_image', 'category_id'])
                ->limit($limit)
                ->get();
        });

        return response()->json([
            'suggestions' => $suggestions
        ]);
    }

    /**
     * Get all descendant category IDs including the parent
     */
    private function getAllDescendantCategoryIds($categoryId)
    {
        $categoryIds = [$categoryId];
        $children = Category::where('parent_id', $categoryId)
            ->where('status', 'active')
            ->pluck('id');

        foreach ($children as $childId) {
            $categoryIds = array_merge(
                $categoryIds,
                $this->getAllDescendantCategoryIds($childId)
            );
        }

        return $categoryIds;
    }

    /**
     * Render the product details page using Inertia
     */
    public function detailsPage($slug)
    {
        $product = $this->productService->getSingleProduct($slug);

        if (!$product) {
            abort(404);
        }

        // Get reviews
        $reviewService = app(\App\Services\Review\ReviewService::class);
        $reviews = $reviewService->getFormattedProductReviews($product['id']);
        $reviewStats = $reviewService->getReviewStats($product['id']);
        
        // Check if current user/guest can review
        $userId = \Illuminate\Support\Facades\Auth::id();
        // Use cart_session_id cookie for guests (same as cart and order system)
        $sessionId = \Illuminate\Support\Facades\Auth::check() ? null : (request()->cookie('cart_session_id') ?? request()->session()->get('cart_session_id'));
        $canReview = $reviewService->canReview($product['id'], $userId, $sessionId);

        // Related products
        $relatedProducts = $this->productService->getRelatedProducts($product['id']);

        return Inertia::render('Frontend/Product/Details', [
            'product' => $product,
            'reviews' => $reviews,
            'reviewStats' => $reviewStats,
            'canReview' => $canReview,
            'relatedProducts' => $relatedProducts,
        ]);
    }
    /**
     * Render the product group page using Inertia
     */
    public function productGroup($slug)
    {
        $group = \App\Models\ProductGroup::with('products')
            ->where('slug', $slug)
            ->where('status', true)
            ->firstOrFail();

        return Inertia::render('Frontend/ProductGroup/Show', [
            'group' => $group
        ]);
    }
}
