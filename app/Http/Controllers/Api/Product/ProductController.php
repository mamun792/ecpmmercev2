<?php

namespace App\Http\Controllers\Api\Product;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Http\Controllers\Controller;
use App\Services\Product\ProductService;


class ProductController extends Controller
{
    protected $productService;
    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    public function getAllProducts()
    {
        $products = $this->productService->getAllProducts();
        return response()->json($products);
    }

    public function singleProduct($slug)
    {
        $product = $this->productService->getSingleProduct($slug);
        return response()->json($product);
    }

        public function productsByRemark($remark)
    {
        $cacheKey = "products.remark.{$remark}";

        $products = Cache::remember($cacheKey, 1800, function () use ($remark) {
            return Product::where('remarks', $remark)
                ->with(['category', 'variations.attributes.value.attribute'])
                ->paginate(10);
        });

        return response()->json($products);
    }


    public function categorywithproducts()
    {
        return Cache::remember('products.category_grouped', 1800, function () {
            // Load products with necessary relations (not reviews)
            $products = Product::with('category', 'variations.attributes.value.attribute')
                ->where('status', 'Published')
                ->get();

            // Group products by category name and limit to 8 per category
            $groupedByCategory = $products->groupBy(function ($product) {
                return $product->category ? $product->category->name : 'Uncategorized';
            })->map(function ($products) {
                return $products->take(8);
            });

            // Sort by category 'order' field
            $sortedByCategory = $groupedByCategory->sortBy(function ($products, $categoryName) {
                $category = $products->first()->category;
                return $category ? $category->order : PHP_INT_MAX;
            })->toArray();

            return response()->json($sortedByCategory);
        });
    }


    public function productswithVideo()
    {
        return Cache::remember('products.with_video', 1800, function () {
            $products = Product::select('id', 'name', 'product_code', 'slug', 'price', 'previous_price', 'feature_image', 'upload_video')
                        ->where('status', 'published')
                        ->whereNotNull('upload_video')
                        ->take(30)
                        ->orderBy('created_at','desc')
                        ->get();

            return response()->json($products);
        });
    }


    public function getRelatedProducts($productId)
    {
        $cacheKey = "products.related.{$productId}";

        return Cache::remember($cacheKey, 1800, function () use ($productId) {
            try {
                // Find the main product
                $product = Product::with('category', 'variations.attributes.value.attribute', 'campaigns', 'brand')->findOrFail($productId);

                if (!$product) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Product not found',
                    ], 404);
                }

                // Get related products based on category
                $relatedProducts = Product::where('category_id', $product->category_id)
                    ->where('id', '!=', $productId) // Exclude current product
                    ->where('status', 'Published')
                    ->with(['category', 'variations.attributes.value.attribute'])
                    ->take(10)
                    ->get();

                return response()->json([
                    'status' => 'success',
                    'message' => 'Related products retrieved successfully',
                    'data' => $relatedProducts
                ], 200);
            } catch (\Exception $e) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'An error occurred while fetching related products',
                    'error' => $e->getMessage()
                ], 500);
            }
        });
    }



    public function filter(Request $request)
    {
        $query = Product::with(['category.parentRecursive',  'variations.attributes.value.attribute', 'brand', 'campaigns'])->where('status', 'published');

        // Filter by category slug OR by category and all its descendants
        if ($request->has('category_slug')) {
            $categorySlug = $request->category_slug;

            // Find the category by slug
            $category = Category::where('slug', $categorySlug)->first();

            if ($category) {
                // Get all descendant categories including the category itself
                $categoryIds = $this->getAllDescendantCategoryIds($category->id);


                // Filter products by these category IDs
                $query->whereIn('category_id', $categoryIds);
            } else {
                // If category not found, fallback to direct slug matching
                $query->whereHas('category', function ($q) use ($categorySlug) {
                    $q->where('slug', $categorySlug);
                });
            }
        }

        // Alternative: Allow explicit filter by multiple category IDs
        if ($request->has('category_ids')) {
            $categoryIds = $request->category_ids;
            $query->whereIn('category_id', $categoryIds);
        }

        // Filter by brand id
        if ($request->has('brand_id')) {
            $query->where('brand_id', $request->brand_id);
        }


        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Filter by price range
        if ($request->has('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }
        if ($request->has('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }

        // Search by name or product code
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('product_code', 'like', "%{$search}%");
            });
        }

        // Filter by attributes
        if ($request->has('attributes')) {
            $attributeFilters = $request->input('attributes');

            // For each attribute type (Color, Size, etc.)
            foreach ($attributeFilters as $attributeName => $values) {
                if (!empty($values)) {
                    $query->whereHas('variations', function ($q) use ($attributeName, $values) {
                        $q->whereHas('attributes.value', function ($q2) use ($attributeName, $values) {
                            $q2->whereHas('attribute', function ($q3) use ($attributeName) {
                                $q3->where('name', $attributeName);
                            });
                            $q2->whereIn('value', (array)$values);
                        });
                    });
                }
            }
        }

        // Sorting
        if ($request->has('sort_by')) {
            switch ($request->sort_by) {
                case 'latest':
                    $query->orderBy('created_at', 'desc');
                    break;
                case 'price_asc':
                    $query->orderBy('price', 'asc');
                    break;
                case 'price_desc':
                    $query->orderBy('price', 'desc');
                    break;
            }
        }

        $products = $query->paginate(10);

        return response()->json([
            'status' => 'success',
            'data' => $products,
        ], 200);
    }

    /**
     * Get all descendant category IDs for a given category
     *
     * @param int $categoryId
     * @return array
     */
    private function getAllDescendantCategoryIds($categoryId)
    {
        // Start with the current category
        $allIds = [$categoryId];

        // Get direct children
        $children = Category::where('parent_id', $categoryId)->get();


        foreach ($children as $child) {
            // Recursively get children of each child
            $descendantIds = $this->getAllDescendantCategoryIds($child->id);
            $allIds = array_merge($allIds, $descendantIds);
        }

        return $allIds;
    }



    //letest products
    public function latestProducts()
    {
        $products = Product::with(['category', 'variations.attributes.value.attribute', 'campaigns'])->where('status', 'published')->latest()->paginate(40);
        return response()->json([
            'status' => 'success',
            'data' => $products,
        ], 200);
    }


    public function flashSalesProducts()
    {
        $products = Product::with(['category', 'variations.attributes.value.attribute', 'campaigns'])
            ->where('status', 'published')
            ->where('remarks', 'Hot') // Filter for Hot products
            ->latest()
            ->paginate(20);

        return response()->json([
            'status' => 'success',
            'name' => 'Flash Sales', // Custom name for response
            'data' => $products,
        ], 200);
    }


    public function ProductViewCount($id)
    {
        $product = Product::find($id);
        if ($product) {
            $product->increment('view_count');
            return response()->json([
                'success' => true,
                'message' => 'Product view count incremented.',
                'view_count' => $product->view_count,
            ], 200);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Product not found.',
            ], 404);
        }
    }


    // only previous_price products
    public function previousPriceProducts()
    {
        $products = Product::with(['category', 'variations.attributes.value.attribute', 'campaigns'])
            ->where('status', 'published')
            ->where('previous_price', '>', 0)
            ->latest()
            ->paginate(20);

        return response()->json([
            'success' => true,
            'message' => 'Previous Price Products',
            'data' => $products,
        ], 200);
    }



}
