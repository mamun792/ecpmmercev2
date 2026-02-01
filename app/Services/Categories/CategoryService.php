<?php

namespace App\Services\Categories;
use App\Models\Category;

use App\Helpers\ImageHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;


class CategoryService
{

    public function getAllCategories(){
        $categories = Category::with('children')->whereNull('parent_id')->get();
        return $categories;
    }

    public function getAllCategoriesForApi()
    {
        $categories = Category::where('status', 'active')
            ->with(['children' => function ($query) {
                $query->where('status', 'active')
                    ->orderBy('order', 'asc')
                    ->with(['children' => function ($query) {
                        $query->where('status', 'active')
                                ->orderBy('order', 'asc');
                    }]);
            }])
            ->whereNull('parent_id')
            ->orderBy('order', 'asc')
            ->get();

        return $categories;
    }

    public function createCategory(array $data)
    {
        try {
            // Handle image upload
            $imagePath = null;
            if (isset($data['image']) && $data['image']->isValid()) {
                $imagePath = ImageHelper::uploadImage($data['image'], 'storage/categories');
            }

            // Handle icon upload
            $iconPath = null;
            if (isset($data['icon']) && $data['icon']->isValid()) {
                $iconPath = ImageHelper::uploadImage($data['icon'], 'storage/categories/icons');
            }

            // Check if soft-deleted category with same name exists
            $existingCategory = Category::withTrashed()
                ->where('name', $data['name'])
                ->where('parent_id', $data['parent_id'] ?? null)
                ->first();

            if ($existingCategory && $existingCategory->trashed()) {
                // Restore soft-deleted category
                $existingCategory->restore();
                $existingCategory->update([
                    'image' => $imagePath ?? $existingCategory->image,
                    'icon' => $iconPath ?? $existingCategory->icon,
                    'order' => $data['order'] ?? 0,
                ]);
                return $existingCategory;
            }

            // Create new category
            return Category::create([
                'name' => $data['name'],

                'parent_id' => $data['parent_id'] ?? null,
                'image' => $imagePath,
                'icon' => $iconPath,
                'order' => $data['order'] ?? 0,
            ]);

        } catch (\Exception $e) {

            throw new \Exception('Failed to create category: ' . $e->getMessage());

        }
    }


    public function updateCategory(Category $category, array $data)
    {
        try {
            // Handle image upload if new image is provided
            $imagePath = $category->image;
            if (isset($data['image']) && $data['image']->isValid()) {
                // Delete old image using helper
                if ($imagePath) {
                    ImageHelper::deleteImage($imagePath);
                }
                $imagePath = ImageHelper::uploadImage($data['image'], 'storage/categories');
            }

            // Handle icon upload if new icon is provided
            $iconPath = $category->icon;
            if (isset($data['icon']) && $data['icon']->isValid()) {
                // Delete old icon using helper
                if ($iconPath) {
                    ImageHelper::deleteImage($iconPath);
                }
                $iconPath = ImageHelper::uploadImage($data['icon'], 'storage/categories/icons');
            }

            // Update category
            $category->update([
                'name' => $data['name'],
                'slug' => str::slug($data['name']),
                'parent_id' => $data['parent_id'] ?? null,
                'image' => $imagePath,
                'icon' => $iconPath,
                'order' => $data['order'] ?? 0,
            ]);

            Cache::flush();

            return $category;
        } catch (\Exception $e) {
            throw new \Exception('Failed to update category: ' . $e->getMessage());
        }
    }


    public function updateStatus($id, $status)
    {
        $category = Category::findOrFail($id);
        $category->status = $status;
        $category->save();

        Cache::flush();

        return $category;
    }


    public function deleteCategory(Category $category)
    {
        try {
            // Check if category is used in products or orders
            $productsCount = \DB::table('products')->where('category_id', $category->id)->count();
            $ordersCount = \DB::table('order_items')
                ->join('products', 'order_items.product_id', '=', 'products.id')
                ->where('products.category_id', $category->id)
                ->count();

            // Note: Images are NOT deleted for soft-deleted categories
            // They are preserved for historical data (orders, reports)
            // Only delete images if you implement a hard delete later

            // Soft delete the category (sets deleted_at timestamp)
            $category->delete();

            Cache::flush();

            // Return info about usage
            return [
                'success' => true,
                'has_products' => $productsCount > 0,
                'has_orders' => $ordersCount > 0,
                'products_count' => $productsCount,
                'orders_count' => $ordersCount
            ];
        } catch (\Exception $e) {
            throw new \Exception('Failed to delete category: ' . $e->getMessage());
        }
    }


}
