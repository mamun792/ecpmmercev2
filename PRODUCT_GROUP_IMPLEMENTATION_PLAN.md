# Product Group Implementation Plan

This document outlines the plan for implementing the Product Group functionality, allowing admins to group multiple products together for display or promotional purposes.

## 1. Database Schema

### `product_groups` Table

- `id`: Big Incremental ID
- `name`: String (The name of the group)
- `slug`: String (Unique slug for the group, indexed)
- `status`: Boolean (active/inactive, default: true)
- `created_at`, `updated_at`

### `product_group_product` (Pivot Table)

- `id`: Big Incremental ID
- `product_group_id`: Foreign key to `product_groups` (on delete cascade)
- `product_id`: Foreign key to `products` (on delete cascade)
- `UNIQUE(product_group_id, product_id)` to prevent duplicates

## 2. Models & Relationships

### `App\Models\ProductGroup`

- `$fillable`: `name`, `slug`, `status`
- `public function products(): BelongsToMany`

### `App\Models\Product`

- `public function productGroups(): BelongsToMany`

## 3. Repository Layer

### `App\Contracts\ProductGroupRepositoryInterface`

- Interface defining methods: `all()`, `paginate()`, `create()`, `update()`, `delete()`, `toggleStatus()`, `searchProducts()`.

### `App\Repository\ProductGroupRepository`

- Implementation of the interface.
- Handles Eloquent queries.
- `searchProducts(string $query)`: Filter by `name` like `%query%` or `product_code` like `%query%`. Limit results.

## 4. Service Layer

### `App\Services\Admin\ProductGroupService`

- Injects `ProductGroupRepositoryInterface`.
- Handles business logic:
    - `store(array $data)`: Create group and attach products (use DB::transaction).
    - `update(int $id, array $data)`: Update group and sync products (use DB::transaction).
    - `delete(int $id)`: Handle deletion logic.

## 5. Validation (Form Requests)

### `App\Http\Requests\Admin\ProductGroup\StoreProductGroupRequest`

- Rules: `name` (required, unique), `status` (boolean), `product_ids` (array, exists in products).

### `App\Http\Requests\Admin\ProductGroup\UpdateProductGroupRequest`

- Rules: `name` (required, unique except current), `status` (boolean), `product_ids` (array, exists in products).

## 6. Controllers

### `App\Http\Controllers\Admin\ProductGroup\ProductGroupController`

- Thin controller. Injects `ProductGroupService`.
- `index()`: Returns Inertia view with paginated groups.
- `create()`: Returns Inertia view for creation.
- `store(StoreProductGroupRequest $request)`: Calls service, redirects with flash message.
- `edit(ProductGroup $productGroup)`: Returns Inertia view with group and loaded products.
- `update(UpdateProductGroupRequest $request, ProductGroup $productGroup)`: Calls service, redirects.
- `destroy(ProductGroup $productGroup)`: Calls service.
- `toggleStatus(ProductGroup $productGroup)`: Updates status via service/repository.
- `searchProducts(Request $request)`: API endpoint for searching products by Name or Code.

## 7. Frontend Pages (Vue + Inertia)

### `resources/js/Pages/Admin/ProductGroups/Index.vue`

- Modern UI listing groups.
- Actions: Edit, Delete, Status Toggle.

### `resources/js/Pages/Admin/ProductGroups/Create.vue` & `Edit.vue`

- Form elements:
    - Name input.
    - Status toggle.
    - **Advanced Product Search/Selector**:
        - Search input that triggers a debounced API call to `search-products`.
        - Displays results (Name + Code + Image).
        - Allows multi-select.
        - Shows list of already selected products with "Remove" option.

## 8. Routing (`routes/web.php`)

```php
Route::group(['prefix' => 'product-groups', 'as' => 'product-groups.'], function () {
    Route::get('/', [ProductGroupController::class, 'index'])->name('index');
    Route::get('/create', [ProductGroupController::class, 'create'])->name('create');
    Route::post('/', [ProductGroupController::class, 'store'])->name('store');
    Route::get('/{productGroup}/edit', [ProductGroupController::class, 'edit'])->name('edit');
    Route::put('/{productGroup}', [ProductGroupController::class, 'update'])->name('update');
    Route::delete('/{productGroup}', [ProductGroupController::class, 'destroy'])->name('destroy');
    Route::put('/{productGroup}/status', [ProductGroupController::class, 'toggleStatus'])->name('status');
    Route::get('/api/search-products', [ProductGroupController::class, 'searchProducts'])->name('search-products');
});
```

## 9. Best Practices Included

- **Clean Architecture**: Separation of concerns using Services and Repositories.
- **Optimization**: Eager loading products only when needed. Debounced search for product selection.
- **Security**: Form Requests for strict validation.
- **Data Integrity**: Database transactions during multiple writes (group + relationships).
- **SEO/UX**: Using Slugs for identification. Interactive search UI.
