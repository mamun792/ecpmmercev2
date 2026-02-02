<?php

namespace App\Services\Product;

use App\Models\Brand;
use App\Models\Attribute;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Support\Str;
use App\Helpers\ImageHelper;
use App\Helpers\VideoHelper;
use App\Models\AttributeValue;
use App\Models\ProductVariation;
use App\Models\VariationAttribute;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use App\Models\Campaign;

use App\Services\Inventory\InventoryService;
use App\Services\BarcodeService;

class ProductService
{
    protected InventoryService $inventoryService;
    protected BarcodeService $barcodeService;

    public function __construct(
        InventoryService $inventoryService,
        BarcodeService $barcodeService
    ) {
        $this->inventoryService = $inventoryService;
        $this->barcodeService = $barcodeService;
    }


  public function getAllProductsForAdmin($request)
  {
    $perPage = $request->input('per_page', 10);
    $search = $request->input('search', '');
    $page = $request->input('page', 1);

    // Get cache version for stock updates (incremented when stock changes)
    $cacheVersion = Cache::get('products_stock_version', 1);

    // Generate unique cache key with version for cache busting
    $cacheKey = "products.admin.v{$cacheVersion}.page.{$page}.per_page.{$perPage}.search." . md5($search);

    // Cache for 30 minutes (1800 seconds)
    return Cache::remember($cacheKey, 1800, function () use ($search, $perPage) {
      $query = Product::with([
        'category:id,name,slug,parent_id',
        'category.parentRecursive:id,name,slug,parent_id',
        'brand:id,brand_name',
        'inventoryStocks', // V2 inventory integration
        'variations.inventoryStock', // V2 variation inventory
        'campaigns'
      ])->withAvg('reviews', 'rating')
        ->withCount('reviews');

      // Apply search if provided
      if ($search) {
        $query->where(function ($q) use ($search) {
          $q->where('name', 'like', "%{$search}%")
            ->orWhere('id', 'like', "%{$search}%")
            ->orWhere('price', 'like', "%{$search}%")
            ->orWhere('status', 'like', "%{$search}%");
        });
      }

      //descending order by latest
      $query->orderBy('created_at', 'desc');

      // Get paginated results with soft-delete filtering for variations
      $products = $query->paginate($perPage);

      // Filter out deleted variations, but keep simple products and variable products with at least one valid variation
      $products->getCollection()->transform(function ($product) {
        if ($product->type === 'variable') {
          // Filter variations to only show non-deleted ones
          $activeVariations = $product->variations->filter(function ($variation) {
            // Keep variation only if it's not soft-deleted AND all its attributes are not soft-deleted
            if ($variation->trashed()) {
              return false;
            }
            return $variation->attributes->every(function ($attr) {
              return $attr->value && !$attr->value->trashed() && $attr->value->attribute && !$attr->value->attribute->trashed();
            });
          })->values();

          $product->setRelation('variations', $activeVariations);

          // V2 Inventory: Calculate stock for each variation
          $activeVariations->each(function ($variation) {
            $totalStock = $this->inventoryService->getTotalStock($variation->product_id, $variation->id);
            $variation->available_stock = $totalStock;
            $variation->stock = $totalStock; // Also set 'stock' for frontend compatibility
          });
        }

        // V2 Inventory: Calculate total available stock from inventory_stocks table
        $totalProductStock = $this->inventoryService->getTotalStock($product->id);
        $product->available_stock = $totalProductStock;
        $product->stock = $totalProductStock; // Also set 'stock' for frontend compatibility

        // Simple products are always included
        return $product;
      });

      return $products;
    });
  }


  public function getAllProducts()
  {
    return Cache::remember('products.all', 1800, function () {
      return Product::with(['category.parentRecursive', 'variations.attributes.value.attribute', 'brand', 'campaigns'])
        ->withAvg('reviews', 'rating')
        ->withCount('reviews')
        ->get();
    });
  }


  public function getSingleProduct($slug)
  {
    $product = Cache::remember("product.{$slug}", 3600, function () use ($slug) {
      return Product::where('slug', $slug)
        ->with([
          'category:id,name,slug,parent_id',
          'category.parentRecursive:id,name,slug,parent_id',
          'brand:id,brand_name',
          'variations' => function($query) {
            // Only load active variations for frontend
            $query->where('status', 'active');
          },
          'variations.attributeValues:attribute_values.id,product_variation_id,attribute_id,value',
          'variations.attributeValues.attribute:id,name',
          'variations.inventoryStock',
          'inventoryStocks',
          'campaigns'
        ])
        ->withAvg('reviews', 'rating')
        ->withCount('reviews')
        ->first();
    });

    if (!$product) {
      return null;
    }

    // V2 Inventory: Calculate total stock from inventory_stocks table
    $totalStock = $this->inventoryService->getTotalStock($product->id);
    $product->stock = $totalStock;

    // Get minimum threshold for low stock warning
    $minThreshold = $product->inventoryStocks->min('minimum_threshold') ?? 20;
    $product->minimum_threshold = $minThreshold;
    $product->is_low_stock = $totalStock > 0 && $totalStock <= $minThreshold;

    // V2 Inventory: Calculate stock for each active variation
    if ($product->type === 'variable' && $product->variations) {
      $product->variations->each(function ($variation) use ($product) {
        $variationStock = $this->inventoryService->getTotalStock($product->id, $variation->id);
        $variation->stock = $variationStock;

        // Get variation's minimum threshold from inventory_stock
        $variationMinThreshold = $variation->inventoryStock->minimum_threshold ?? 20;
        $variation->minimum_threshold = $variationMinThreshold;
        $variation->is_low_stock = $variationStock > 0 && $variationStock <= $variationMinThreshold;
      });
    }

    return $product;
  }

  public function categorywithproducts(){
    $grouped = Cache::remember('products.category_grouped', 1800, function () {
            // Get root categories that are active, ordered by 'order'
            $rootCategories = Category::whereNull('parent_id')
                ->where('status', 'active')
                ->orderBy('order', 'asc')
                ->get();

            $grouped = [];

            foreach ($rootCategories as $root) {
                // Get all descendant category IDs (active ones)
                $ids = $this->getAllDescendantCategoryIds($root->id);

                // Fetch products for these categories (V2 structure)
                $products = Product::whereIn('category_id', $ids)
                    ->where('status', 'Published')
                    ->with([
                        'category:id,name,slug',
                        'variations.attributeValues:attribute_values.id,product_variation_id,attribute_id,value',
                        'variations.attributeValues.attribute:id,name',
                        'variations.inventoryStock',
                        'inventoryStocks',
                        'campaigns',
                        'brand:id,brand_name'
                    ])
                    ->withAvg('reviews', 'rating')
                    ->withCount('reviews')
                    ->orderBy('created_at', 'desc')
                    ->take(8)
                    ->get();

                if ($products->isNotEmpty()) {
                    $grouped[] = [
                        'name' => $root->name,
                        'slug' => $root->slug,
                        'products' => $products
                    ];
                }
            }

            // Optional: Include Uncategorized products at the end
            $uncategorized = Product::whereNull('category_id')
                ->where('status', 'Published')
                ->with([
                    'category:id,name,slug',
                    'variations.attributeValues:attribute_values.id,product_variation_id,attribute_id,value',
                    'variations.attributeValues.attribute:id,name',
                    'variations.inventoryStock',
                    'inventoryStocks'
                ])
                ->withAvg('reviews', 'rating')
                ->withCount('reviews')
                ->orderBy('created_at', 'desc')
                ->take(8)
                ->get();

            if ($uncategorized->isNotEmpty()) {
                $grouped[] = [
                    'name' => 'Uncategorized',
                    'slug' => 'uncategorized',
                    'products' => $uncategorized
                ];
            }

            return $grouped;
        });

    // V2 Inventory: Calculate stock for all products and variations
    foreach ($grouped as &$category) {
        foreach ($category['products'] as $product) {
            $product->stock = $this->inventoryService->getTotalStock($product->id);
            if ($product->type === 'variable' && $product->variations) {
                $product->variations->each(function ($variation) use ($product) {
                    $variation->stock = $this->inventoryService->getTotalStock($product->id, $variation->id);
                });
            }
        }
    }

    return $grouped;
  }


      /**
     * Get active campaigns with products
     */
    public function getActiveCampaigns()
    {
        $campaigns = Campaign::active()
            ->with([
                'products',
                'products.category:id,name,slug,parent_id',
                'products.category.parentRecursive:id,name,slug,parent_id',
                'products.variations.attributeValues:attribute_values.id,product_variation_id,attribute_id,value',
                'products.variations.attributeValues.attribute:id,name',
                'products.variations.inventoryStock',
                'products.inventoryStocks',
                'products.campaigns'
            ])
            ->get();

        // Efficiently load average rating and review count for campaign products
        $campaigns->each(function($campaign) {
            $campaign->products->loadAvg('reviews', 'rating');
            $campaign->products->loadCount('reviews');

            // V2 Inventory: Calculate stock for each product
            $campaign->products->each(function($product) {
                $product->stock = $this->inventoryService->getTotalStock($product->id);
                if ($product->type === 'variable' && $product->variations) {
                    $product->variations->each(function ($variation) use ($product) {
                        $variation->stock = $this->inventoryService->getTotalStock($product->id, $variation->id);
                    });
                }
            });
        });

        // Calculate time remaining for each campaign
        $campaigns->transform(function ($campaign) {
            $endDate = \Carbon\Carbon::parse($campaign->end_date);
            $now = now();
            $timeRemaining = $endDate->diffInSeconds($now, false); // false to get negative if past

            $campaign->time_remaining = max(0, $timeRemaining); // Ensure non-negative
            return $campaign;
        });

        return $campaigns;
    }



  /**
   * Get all active attributes with active values (for product edit page)
   * Big tech style: Only show active attributes, never deleted ones
   */
  public function getAllAttributes()
  {
    return Cache::remember('active_attributes_with_values', 3600, function () {
      // Load only active attributes with active values
      $attributeValues = AttributeValue::active()
        ->with(['attribute' => function ($query) {
          $query->active()->withoutTrashed();
        }])
        ->whereHas('attribute', function ($query) {
          $query->active()->withoutTrashed();
        })
        ->get()
        ->groupBy('attribute.name')
        ->map(function ($values, $attributeName) {
          return [
            'name' => $attributeName, // Changed from 'attribute_name' to 'name' for frontend compatibility
            'values' => $values->map(function ($value) {
              return [
                'id' => $value->id,
                'value' => $value->value,
                'color' => $value->color ?? null,
              ];
            })->sortBy('value')->values(), // Sort values alphabetically
          ];
        })
        ->sortBy('name') // Sort attributes alphabetically
        ->values();

      return $attributeValues;
    });
  }

  /**
   * Get related products for a product (by category fallback). Returns a collection of Product models.
   * @param int $productId
   * @param int $limit
   * @return \Illuminate\Database\Eloquent\Collection
   */
  public function getRelatedProducts($productId, $limit = 10)
  {
    $cacheKey = "products.related.{$productId}.limit.{$limit}";

    return Cache::remember($cacheKey, 1800, function () use ($productId, $limit) {
      try {
        $product = Product::with('category')->find($productId);
        if (!$product) {
          return collect();
        }

        // Prefer same category
        if ($product->category_id) {
          $related = Product::where('category_id', $product->category_id)
            ->where('id', '!=', $productId)
            ->where('status', 'Published')
            ->with(['category', 'variations.attributes.value.attribute', 'brand', 'campaigns'])
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->take($limit)
            ->get();

          if ($related->isNotEmpty()) {
            return $related;
          }
        }

        // Fallback: get products from same brand
        if ($product->brand_id) {
          $related = Product::where('brand_id', $product->brand_id)
            ->where('id', '!=', $productId)
            ->where('status', 'Published')
            ->with(['category', 'variations.attributes.value.attribute', 'brand', 'campaigns'])
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->take($limit)
            ->get();

          if ($related->isNotEmpty()) {
            return $related;
          }
        }

        // Final fallback: latest published products excluding current
        return Product::where('id', '!=', $productId)
          ->where('status', 'Published')
          ->with(['category', 'variations.attributes.value.attribute', 'brand', 'campaigns'])
          ->withAvg('reviews', 'rating')
          ->withCount('reviews')
          ->orderBy('created_at', 'desc')
          ->take($limit)
          ->get();
      } catch (\Exception $e) {
        // On error, return empty collection but don't break the page
        return collect();
      }
    });
  }


  public function getAllCategories()
  {
    return Cache::remember('categories.all', 3600, function () {
      return Category::with('children')->whereNull('parent_id')->get();
    });
  }

  public function getAllBrands()
  {
    return Cache::remember('brands.all', 3600, function () {
      return Brand::where('status', 'active')->get();
    });
  }

  /**
   * Return paginated, filtered products based on the provided params array.
   * Expected keys: search, category, category_ids, brand_ids, min_price, max_price,
   * attributes (array), sort_by, per_page
   */
  public function getFilteredProducts(array $params = [])
  {
    $search = $params['search'] ?? '';
    $categorySlug = $params['category'] ?? '';
    $categoryIds = $params['category_ids'] ?? [];
    $brandIds = $params['brand_ids'] ?? [];
    $minPrice = $params['min_price'] ?? null;
    $maxPrice = $params['max_price'] ?? null;
    $sortBy = $params['sort_by'] ?? 'latest';
    $perPage = $params['per_page'] ?? 12;
    $attributeFilters = $params['attributes'] ?? [];

    $query = Product::where('status', 'Published')
        ->with([
            'category:id,name,slug',
            'brand:id,brand_name',
            'variations.attributeValues:attribute_values.id,product_variation_id,attribute_id,value',
            'variations.attributeValues.attribute:id,name',
            'campaigns'
        ])
        ->withAvg('reviews', 'rating')
        ->withCount('reviews');

    if ($search) {
      $query->where(function ($q) use ($search) {
        $q->where('name', 'like', "%{$search}%")
          ->orWhere('product_code', 'like', "%{$search}%")
          ->orWhere('product_tags', 'like', "%{$search}%")
          ->orWhere('short_description', 'like', "%{$search}%");
      });
    }

    // Category filters
    if ($categorySlug) {
      $category = Category::where('slug', $categorySlug)->first();
      if ($category) {
        $allCategoryIds = $this->getAllDescendantCategoryIds($category->id);
        $query->whereIn('category_id', $allCategoryIds);
      }
    } elseif (!empty($categoryIds)) {
      $allIds = [];
      foreach ((array) $categoryIds as $catId) {
        $allIds = array_merge($allIds, $this->getAllDescendantCategoryIds($catId));
      }
      $query->whereIn('category_id', array_unique($allIds));
    }

    // Brand
    if (!empty($brandIds)) {
      $query->whereIn('brand_id', (array) $brandIds);
    }

    // Price
    if ($minPrice !== null) {
      $query->where('price', '>=', $minPrice);
    }
    if ($maxPrice !== null) {
      $query->where('price', '<=', $maxPrice);
    }

    // Attribute filters
    if (!empty($attributeFilters)) {
      foreach ($attributeFilters as $attributeName => $values) {
        if (!empty($values)) {
          $query->whereHas('variations.attributes.value', function ($q) use ($attributeName, $values) {
            $q->whereHas('attribute', function ($q2) use ($attributeName) {
              $q2->where('name', $attributeName);
            });
            $q->whereIn('value', (array) $values);
          });
        }
      }
    }

    // Sorting
    switch ($sortBy) {
      case 'price_asc':
        $query->orderBy('price', 'asc');
        break;
      case 'price_desc':
        $query->orderBy('price', 'desc');
        break;
      case 'name_asc':
        $query->orderBy('name', 'asc');
        break;
      case 'name_desc':
        $query->orderBy('name', 'desc');
        break;
      case 'oldest':
        $query->orderBy('created_at', 'asc');
        break;
      case 'latest':
      default:
        $query->orderBy('created_at', 'desc');
        break;
    }

    return $query->paginate($perPage)->withQueryString();
  }

  /**
   * Return filter options: categories, brands, attributes and price range in the shape used by frontend
   */
  public function getFilterOptions()
  {
    // Categories (root with children)
    $categories = $this->getAllCategories();

    // Brands (active with published product counts)
    $brands = Brand::where('status', 'active')
      ->withCount(['products' => function ($q) {
        $q->where('status', 'Published');
      }])
      ->orderBy('brand_name')
      ->get();

    // Attributes and values (exclude soft-deleted values)
    $attributes = Attribute::with(['values' => function ($q) {
        $q->whereNull('deleted_at');
      }])
      ->whereHas('values')
      ->orderBy('name')
      ->get()
      ->map(function ($attribute) {
        return [
          'id' => $attribute->id,
          'name' => $attribute->name,
          'values' => $attribute->values->map(function ($value) {
            return [
              'id' => $value->id,
              'value' => $value->value,
              'color' => $value->color,
            ];
          })->values()
        ];
      });

    $priceRange = Product::where('status', 'Published')
      ->selectRaw('MIN(price) as min_price, MAX(price) as max_price')
      ->first();

    return [
      'categories' => $categories,
      'brands' => $brands,
      'attributes' => $attributes,
      'priceRange' => [
        'min' => (float) ($priceRange->min_price ?? 0),
        'max' => (float) ($priceRange->max_price ?? 10000),
      ],
    ];
  }

  /**
   * Recursive helper to collect category and descendant ids (active children only)
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


  public function storeProduct($request)
  {
    try {
      DB::beginTransaction();

      // Generate base slug from product name
      $baseSlug = Str::slug($request->name);
      $productCode = $request->product_code;

      Log::info('Product creation attempt:', ['name' => $request->name, 'base_slug' => $baseSlug, 'code' => $productCode]);

      // Check if an ACTIVE product exists with same name/slug
      // Names must be unique among ACTIVE products
      $activeProduct = Product::where('slug', $baseSlug)->first();

      if ($activeProduct) {
        DB::rollBack();
        Log::info('Duplicate product name blocked:', ['slug' => $baseSlug, 'existing_id' => $activeProduct->id]);
        return [
          'success' => false,
          'error' => "A product with this name already exists. Please use a different name.",
        ];
      }

      // Check if product_code conflicts with any product (active or soft-deleted)
      // Codes must be unique globally
      $existingProductCode = Product::withTrashed()
        ->where('product_code', $productCode)
        ->first();

      if ($existingProductCode) {
        DB::rollBack();
        $errorMsg = $existingProductCode->trashed()
          ? "This product code was used by a deleted product. Please use a different product code."
          : "This product code already exists. Please use a different product code.";

        Log::info('Product code conflict found:', ['code' => $productCode, 'trashed' => $existingProductCode->trashed()]);
        return [
          'success' => false,
          'error' => $errorMsg,
        ];
      }

      // Handle Image Uploads
      $featureImagePath = $request->hasFile('feature_image')
        ? ImageHelper::uploadImage($request->file('feature_image'), 'storage/products')
        : null;

      $galleryImages = $this->uploadGalleryImages($request);

      Log::info('Gallery Images:', $galleryImages);

            // Handle Video Upload
      $uploadVideoPath = null;
      if ($request->hasFile('upload_video')) {
        $uploadVideoPath = VideoHelper::uploadVideo($request->file('upload_video'), 'storage/products/videos');
      }

      // Calculate total stock
      $totalStock = $this->calculateTotalStock($request);

      // Create Product
      // The prepareProductData will call generateUniqueSlug if needed
      // Since we only check for base slug conflicts, generateUniqueSlug will just return the base slug
      $product = Product::create(
        $this->prepareProductData($request, $featureImagePath, $galleryImages, $totalStock, $uploadVideoPath)
      );

      // Auto-generate barcode if not provided
      if (empty($product->barcode)) {
        $barcode = $this->barcodeService->generateEAN13($product->id);
        $product->update(['barcode' => $barcode]);

        Log::info('📊 Auto-generated barcode for product', [
          'product_id' => $product->id,
          'barcode' => $barcode
        ]);
      }

      Log::info('Created Product ID: ' . $product);

      // Handle Variations
      if ($request->type === 'variable') {
       $test= $this->storeProductVariations($product, $request);
       Log::info('Stored Variations: ' . $test);
      }

      DB::commit();
      return ['success' => true, 'product' => $product];
    } catch (\Illuminate\Database\QueryException $e) {
      DB::rollBack();

      // Check if it's a unique constraint violation
      if ($e->getCode() == 23000) {
        // Check if constraint is for slug or product_code
        if (strpos($e->getMessage(), 'slug') !== false) {
          return [
            'success' => false,
            'error' => "A product with this name already exists. Please use a different name.",
          ];
        }

        if (strpos($e->getMessage(), 'product_code') !== false) {
          return [
            'success' => false,
            'error' => "This product code already exists. Please use a different product code.",
          ];
        }
      }

      return ['success' => false, 'error' => $e->getMessage()];
    } catch (\Exception $e) {
      DB::rollBack();
      return ['success' => false, 'error' => $e->getMessage()];
    }
  }



  private function uploadGalleryImages($request)
  {
    $galleryImages = [];
    if ($request->hasFile('gallery_images')) {
      foreach ($request->file('gallery_images') as $image) {
        $galleryImages[] = ImageHelper::uploadImage($image, 'storage/products/gallery');
      }
    }
    return $galleryImages;
  }



  private function calculateTotalStock($request)
  {
    if ($request->type === 'variable' && $request->has('variations')) {
      return array_sum(array_map(fn($v) => $v['stock'] ?? 0, $request->variations));
    }

    if ($request->has('stock_data')) {
        return array_sum(array_map(fn($item) => (int)($item['quantity'] ?? 0), $request->stock_data));
    }

    return $request->input('stock', 0);
  }



  private function storeProductVariations($product, $request)
  {
    foreach ($request->variations as $index => $variationData) {
      $variationImagePath = $request->hasFile("variations.$index.image_path")
        ? ImageHelper::uploadImage($request->file("variations.$index.image_path"), 'storage/variations')
        : null;

      $variation = ProductVariation::create([
        'product_id' => $product->id,
        'price' => $variationData['price'],
        // Accept previous_price when provided
        'previous_price' => $variationData['previous_price'] ?? null,
        'stock' => $variationData['stock'] ?? 0,
        'image_path' => $variationImagePath,
      ]);

      // Auto-generate barcode for variation
      if (empty($variation->barcode)) {
        $barcode = $this->barcodeService->generateVariationBarcode($product->id, $variation->id);
        $variation->update(['barcode' => $barcode]);
      }

      foreach ($variationData['attributes'] as $attributeData) {
        VariationAttribute::create([
          'product_variation_id' => $variation->id,
          'attribute_value_id' => $attributeData['attribute_value_id'],
        ]);
      }
    }
  }




  // Product Service update method
  public function updateProduct($request, Product $product)
  {
    try {
      DB::beginTransaction();

      \Illuminate\Support\Facades\Log::info('ProductService: Starting update for product ' . $product->id);

      // Check if name is changing
      $baseSlug = Str::slug($request->name);

      if ($baseSlug !== $product->slug) {
        // If changing to a different name, check that no other ACTIVE product has this name
        $activeProduct = Product::where('slug', $baseSlug)
          ->where('id', '!=', $product->id)
          ->first();

        if ($activeProduct) {
          DB::rollBack();
          Log::info('Duplicate product name blocked on update:', ['slug' => $baseSlug, 'existing_id' => $activeProduct->id]);
          return [
            'success' => false,
            'error' => "A product with this name already exists. Please use a different name.",
          ];
        }
      }

      // Check if product_code is changing and conflicts
      $newProductCode = $request->product_code;

      if ($newProductCode !== $product->product_code) {
        $existingCode = Product::withTrashed()
          ->where('id', '!=', $product->id)
          ->where('product_code', $newProductCode)
          ->first();

        if ($existingCode) {
          DB::rollBack();
          $errorMsg = $existingCode->trashed()
            ? "This product code was used by a deleted product. Please use a different product code."
            : "This product code already exists. Please use a different product code.";

          Log::info('Product code conflict found on update:', ['code' => $newProductCode, 'trashed' => $existingCode->trashed()]);
          return [
            'success' => false,
            'error' => $errorMsg,
          ];
        }
      }

      // Handle Image Uploads
      if ($request->hasFile('feature_image')) {
        // Delete old feature image using helper
        ImageHelper::deleteImage($product->feature_image);

        $featureImagePath = ImageHelper::uploadImage($request->file('feature_image'), 'storage/products');
      } else {
        $featureImagePath = $product->feature_image;
      }

      $galleryImages = $this->updateGalleryImages($request, $product);

      // Calculate total stock
      $totalStock = $this->calculateTotalStock($request);

      $uploadVideoPath = null;
      if ($request->hasFile('upload_video')) {
        // Delete old video using helper
        VideoHelper::deleteVideo($product->upload_video);

        $uploadVideoPath = VideoHelper::uploadVideo($request->file('upload_video'), 'storage/products/videos');
      } else {
        $uploadVideoPath = $product->upload_video;
      }

      // Update Product
      $product->update(
        $this->prepareProductData($request, $featureImagePath, $galleryImages, $totalStock, $uploadVideoPath, $product->id)
      );

      // Handle Variations
      if ($request->type === 'variable') {
        \Illuminate\Support\Facades\Log::info('ProductService: Updating variations');
        $this->updateProductVariations($product, $request);
        // Update Simple Product Inventory
        if ($request->has('stock_data')) {
             $stockData = $request->input('stock_data');
             $providedLocations = [];

             foreach ($stockData as $stockItem) {
                 if (isset($stockItem['location']) && !empty($stockItem['location'])) {
                     $location = $stockItem['location'];
                     $providedLocations[] = $location;

                     $this->inventoryService->createStock(
                        productId: $product->id,
                        quantity: (int) ($stockItem['quantity'] ?? 0),
                        locationCode: $location,
                        reason: 'Product update',
                        notes: $stockItem['notes'] ?? 'Updated via product edit',
                        minThreshold: (int) $request->input('min_quantity', 0)
                     );
                 }
             }

             // Optional: Reset other locations to 0 that were not in the request
             \App\Models\InventoryStock::where('product_id', $product->id)
                ->whereNull('product_variation_id')
                ->whereNotIn('location_code', $providedLocations)
                ->update([
                    'available_quantity' => 0,
                    'total_quantity' => DB::raw('reserved_quantity'),
                    'last_movement_at' => now(),
                    'last_updated_by' => auth()->id()
                ]);

        } elseif ($request->filled('min_quantity') || $request->filled('stock')) {
             $quantity = $request->input('stock', 0);
             $minQuantity = $request->input('min_quantity', 0);

             // Update main warehouse stock
             $this->inventoryService->createStock(
                productId: $product->id,
                quantity: (int) $quantity,
                locationCode: 'MAIN',
                reason: 'Product update',
                notes: 'Updated via product edit',
                minThreshold: (int) $minQuantity
             );
        }
      }

      DB::commit();
      return ['success' => true, 'product' => $product->fresh()];
    } catch (\Illuminate\Database\QueryException $e) {
      DB::rollBack();

      // Check if it's a unique constraint violation
      if ($e->getCode() == 23000) {
        if (strpos($e->getMessage(), 'slug') !== false) {
          return [
            'success' => false,
            'error' => "A product with this name already exists. Please use a different name.",
          ];
        }

        if (strpos($e->getMessage(), 'product_code') !== false) {
          return [
            'success' => false,
            'error' => "This product code already exists. Please use a different product code.",
          ];
        }
      }

      return ['success' => false, 'error' => $e->getMessage()];
    } catch (\Exception $e) {
      DB::rollBack();
      return ['success' => false, 'error' => $e->getMessage()];
    }
  }

  /**
   * Generate a unique slug for the product
   * If slug exists (including soft-deleted), append a number
   */
  private function generateUniqueSlug($name, $excludeId = null)
  {
    $slug = Str::slug($name);
    $originalSlug = $slug;
    $count = 1;

    // Check if slug exists (including soft-deleted products)
    while (true) {
      $query = Product::withTrashed()->where('slug', $slug);

      if ($excludeId) {
        $query->where('id', '!=', $excludeId);
      }

      if (!$query->exists()) {
        break;
      }

      $slug = $originalSlug . '-' . $count;
      $count++;
    }

    return $slug;
  }


    private function prepareProductData($request, $featureImagePath, $galleryImages, $totalStock, $uploadVideoPath = null, $productId = null)
  {
    return [
      'name' => $request->name,
      'slug' => $this->generateUniqueSlug($request->name, $productId),
      'product_code' => $request->product_code,
      'category_id' => $request->category_id,
      'brand_id' => $request->brand_id,
      'short_description' => $request->short_description,
      'description' => $request->description,
      'status' => $request->status ?? 'Published',
      'is_daily_product' => $request->is_daily_product ?? 0,
      'is_pre_order' => $request->is_pre_order ?? 0,
      'type' => $request->type ?? 'simple',
      'price' => $request->price,
      'previous_price' => $request->previous_price ?? null,
      'youtube_video' => $request->youtube_video ?? null,
      'feature_image' => $featureImagePath,
      'gallery_images' => $galleryImages,
      'product_tags' => $request->product_tags ?? [],
      'specification' => $request->specification ?? [],
      'stock' => $totalStock,
      'track_inventory' => $request->track_quantity ?? $request->track_inventory ?? 1,
      'allow_backorders' => $request->sell_without_stock ?? $request->allow_backorders ?? 0,
      'min_quantity' => $request->min_quantity,
      'remarks' => $request->remarks,
      'meta_title' => $request->meta_title,
      'meta_description' => $request->meta_description,
      'upload_video' => $uploadVideoPath,
    ];
  }

  private function updateGalleryImages($request, Product $product)
  {
    $galleryImages = $product->gallery_images ?? [];

    if ($request->hasFile('gallery_images')) {
      // Delete old gallery images using helper
      foreach ($galleryImages as $oldImage) {
        ImageHelper::deleteImage($oldImage);
      }

      $galleryImages = [];
      foreach ($request->file('gallery_images') as $image) {
        $galleryImages[] = ImageHelper::uploadImage($image, 'storage/products/gallery');
      }
    }

    return $galleryImages;
  }


  private function updateProductVariations($product, $request)
  {
    // Get existing variations as a collection indexed by ID for easy lookup
    $existingVariations = $product->variations->keyBy('id')->toArray();
    $updatedVariationIds = [];

    // Process variations from the request
    if ($request->has('variations')) {
      foreach ($request->variations as $index => $variationData) {
        $variationId = $variationData['id'] ?? null;

        if ($variationId && isset($existingVariations[$variationId])) {
          // Update existing variation
          $variation = ProductVariation::find($variationId);
          $existingImagePath = $variation->image_path;

          // Only update image_path if a new file is uploaded
          if ($request->hasFile("variations.$index.image_path")) {
            // Delete old variation image using helper
            ImageHelper::deleteImage($existingImagePath);

            $variationImagePath = ImageHelper::uploadImage($request->file("variations.$index.image_path"), 'storage/variations');
          } else {
            $variationImagePath = $existingImagePath;
          }

          $variation->update([
            'price' => $variationData['price'],
            'cost_price' => $variationData['cost_price'] ?? null,
            'previous_price' => $variationData['previous_price'] ?? null,
            'status' => $variationData['status'] ?? 'active',
            // stock is virtual, so we update inventory explicitly below
            'image_path' => $variationImagePath,
          ]);

          // Update Inventory using InventoryService
          if (isset($variationData['stock'])) {
             $this->inventoryService->createStock(
                 productId: $product->id,
                 variationId: $variation->id,
                 quantity: (int) $variationData['stock'],
                 locationCode: 'MAIN',
                 reason: 'Variation update',
                 notes: 'Stock updated via variation edit',
                 minThreshold: (int) $request->input('min_quantity', 0)
             );
          }

          // Update attributes
          if (isset($variationData['attributes'])) {
            // Delete old attributes and create new ones
            $variation->attributes()->delete();
            foreach ($variationData['attributes'] as $attributeData) {
              VariationAttribute::create([
                'product_variation_id' => $variation->id,
                'attribute_value_id' => $attributeData['attribute_value_id'],
              ]);
            }
          }

          $updatedVariationIds[] = $variationId;
        } else {
          // Create new variation
          $variationImagePath = $request->hasFile("variations.$index.image_path")
            ? ImageHelper::uploadImage($request->file("variations.$index.image_path"), 'storage/variations')
            : null;

          $variation = ProductVariation::create([
            'product_id' => $product->id,
            'price' => $variationData['price'],
            'cost_price' => $variationData['cost_price'] ?? null,
            'previous_price' => $variationData['previous_price'] ?? null,
            'status' => $variationData['status'] ?? 'active',
            // stock is handled via inventory service below
            'image_path' => $variationImagePath,
          ]);

          if (isset($variationData['stock'])) {
             $this->inventoryService->createStock(
                 productId: $product->id,
                 variationId: $variation->id,
                 quantity: (int) $variationData['stock'],
                 locationCode: 'MAIN',
                 reason: 'New variation stock',
                 notes: 'Created via variation edit',
                 minThreshold: (int) $request->input('min_quantity', 0)
             );
          }

          if (isset($variationData['attributes'])) {
            foreach ($variationData['attributes'] as $attributeData) {
              VariationAttribute::create([
                'product_variation_id' => $variation->id,
                'attribute_value_id' => $attributeData['attribute_value_id'],
              ]);
            }
          }

          $updatedVariationIds[] = $variation->id;
        }
      }
    }

    // Only delete variations that were explicitly removed by user
    // (i.e., they existed in DB but were not included in the request)
    if (!empty($updatedVariationIds)) {
      $product->variations()
        ->whereNotIn('id', $updatedVariationIds)
        ->delete();
    }
  }



public function quickUpdate($productId, array $data)
{
    try {
        $product = Product::findOrFail($productId);

        $updateData = [
            'status' => $data['status'] ?? $product->status,
            'is_free_delivery' => $data['is_free_delivery'] ?? $product->is_free_delivery,
        ];

        // ✅ Important fix: use array_key_exists to preserve null
        if (array_key_exists('remarks', $data)) {
            $updateData['remarks'] = $data['remarks']; // will update to NULL if null
        }

        $product->update($updateData);

        return [
            'success' => true,
            'message' => 'Product updated successfully',
            'product' => $product
        ];
    } catch (\Exception $e) {
        return [
            'success' => false,
            'message' => 'Failed to update product: ' . $e->getMessage()
        ];
    }
}




  public function deleteProduct($id)
  {
    try {
      DB::beginTransaction();

      // Find the product
      $product = Product::findOrFail($id);

      // Soft delete variations (keep images for order history)
      foreach ($product->variations as $variation) {
        VariationAttribute::where('product_variation_id', $variation->id)->delete();
        $variation->delete();
      }

      // Soft delete product (keeps all data for orders and inventory tracking)
      $product->delete();

      DB::commit();

      return ['success' => true, 'message' => 'Product deleted successfully! (Data preserved for orders and inventory)'];
    } catch (\Exception $e) {
      DB::rollBack();
      return ['success' => false, 'message' => 'Error deleting product: ' . $e->getMessage()];
    }
  }



  public function bulkDeleteProducts(array $productIds)
    {
        DB::beginTransaction();

        try {
            $products = Product::whereIn('id', $productIds)->get();

            foreach ($products as $product) {
                // Soft delete variations (keep images for order history)
                foreach ($product->variations as $variation) {
                    VariationAttribute::where('product_variation_id', $variation->id)->delete();
                    $variation->delete();
                }

                // Soft delete product (keeps all data for orders and inventory tracking)
                $product->delete();
            }

            DB::commit();
            return ['success' => true, 'message' => 'Selected products deleted successfully! (Data preserved for orders and inventory)'];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }






}
