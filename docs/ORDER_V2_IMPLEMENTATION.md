# Order Management V2 Implementation

## Overview

This document details the Order Management V2 upgrade that integrates with the new V2 Inventory System, improves performance, and adds comprehensive audit trails.

## Changes Made

### 1. Database Migrations

#### `2026_02_01_064654_add_snapshot_columns_to_order_items_table.php`
- Added `deleted_at` for soft deletes
- Added `deleted_by` and `deletion_reason` for audit
- Created `order_status_histories` table for status tracking
- Created `order_edit_logs` table for field edit audit

#### `2026_02_01_064738_add_audit_columns_to_orders_table.php`
- Added `created_by`, `updated_by` for tracking
- Existing columns discovered: `deleted_by`, `deletion_reason`, `deletion_context`, `recovered_at`, `recovered_by`

### 2. New Models

#### `App\Models\OrderStatusHistory`
Tracks all status changes with:
- `previous_status`, `new_status`
- `changed_by_type` (user/system/customer)
- `changed_by_id`
- `notes`, `metadata` (JSON)
- `ip_address`

#### `App\Models\OrderEditLog`
Audits all field edits with:
- `field_name`
- `old_value`, `new_value`
- `edited_by`
- `edit_reason`
- `ip_address`

### 3. Updated Models

#### `App\Models\Order`
- Added relationships: `statusHistories()`, `editLogs()`, `createdByUser()`, `updatedByUser()`, `deletedByUser()`
- Added methods: `recordStatusChange()`, `logEdit()`, `invalidateCache()`
- Updated fillable with: `order_source`, `created_by`, `updated_by`, `deleted_by`, `deletion_reason`

#### `App\Models\OrderItem`
- Added `SoftDeletes` trait
- Updated fillable with actual DB columns:
  - `product_image_url` (not `product_image`)
  - `variation_image_url` (not `variation_image`)
  - Full snapshot columns: `product_code`, `product_short_description`, `product_status`, `product_type`, etc.
  - JSON snapshots: `product_data_snapshot`, `variation_data_snapshot`
- Added backward compatibility accessors: `getProductImageAttribute()`, `getVariationImageAttribute()`

### 4. New Trait

#### `App\Traits\OrderEagerLoading`
Centralized eager loading patterns:
- `getOrderListEagerLoads()` - For order lists
- `getOrderDetailEagerLoads()` - For order detail pages
- `getInvoiceEagerLoads()` - For invoice generation
- `getMinimalEagerLoads()` - Lightweight queries
- `getStockOperationEagerLoads()` - Stock adjustment operations

### 5. OrderService V2 Integration

#### Constructor Changes
```php
public function __construct(
    OrderRepositoryInterface $orderRepository,
    InventoryService $inventoryService  // NEW
)
```

#### Stock Validation (V2)
```php
protected function resolveAndValidateOrderItems(...)
{
    // V2 inventory check with fallback
    $totalAvailableStock = $this->inventoryService->getTotalStock(
        $product->id,
        $variation?->id
    );
    
    // Fallback to legacy if V2 returns 0
    if ($totalAvailableStock <= 0) {
        $totalAvailableStock = $product->quantity ?? 0;
    }
}
```

#### Stock Operations (V2)
```php
protected function updateProductStock($product, $quantity, $variation = null)
{
    // Use V2 InventoryService for stock adjustment
    $dto = new InventoryAdjustmentDTO(
        productId: $product->id,
        quantity: abs($quantity),
        type: $quantity < 0 ? 'sale' : 'return',
        reason: $quantity < 0 ? 'Order sale' : 'Stock adjustment',
        variationId: $variation?->id
    );
    
    // Log transaction for audit
    $this->inventoryService->adjustStock($dto);
    
    // Fallback update for legacy compatibility
    if ($variation) {
        $variation->decrement('quantity', abs($quantity));
    } else {
        $product->decrement('quantity', abs($quantity));
    }
}
```

#### New Method: `restoreProductStock()`
```php
public function restoreProductStock($product, $quantity, $variation = null, ?string $reason = null)
{
    // Restore stock via V2 inventory with transaction logging
    $dto = new InventoryAdjustmentDTO(
        productId: $product->id,
        quantity: $quantity,
        type: 'return',
        reason: $reason ?? 'Order item removed',
        variationId: $variation?->id
    );
    
    $this->inventoryService->adjustStock($dto);
}
```

#### Snapshot Data in `createOrderItem()`
Now saves comprehensive snapshots:
- Product info: `product_name`, `product_code`, `product_sku`, `product_image_url`, `product_short_description`
- Variation info: `variation_name`, `variation_sku`, `variation_image_url`, `variation_attributes`
- Price data: `base_product_price`, `variation_price_addition`, `original_price`, `cost_price`
- Tax info: `tax_rate`, `tax_amount`, `handling_fee`
- Campaign info: `campaign_name`, `campaign_code`
- Stock snapshot: `stock_at_order_time`, `inventory_location`
- Full JSON: `product_data_snapshot`, `variation_data_snapshot`

### 6. OrderController Refactoring

#### Trait Usage
```php
class OrderController extends Controller
{
    use OrderEagerLoading;
```

#### Smart Cache Invalidation
Replaced `Cache::flush()` with:
```php
Order::invalidateCache();
```

#### V2 Inventory in Item Operations
- `updateItemQuantity()` - Uses V2 stock check and adjustment
- `destroyItem()` - Uses `restoreProductStock()` for stock return with logging

## Database Columns Reference

### order_items Table (42 columns)
| Column | Type | Description |
|--------|------|-------------|
| `product_image_url` | string | Product feature image |
| `variation_image_url` | string | Variation image |
| `product_data_snapshot` | json | Full product state |
| `variation_data_snapshot` | json | Full variation state |
| `stock_at_order_time` | integer | Stock level when ordered |
| `snapshot_created_at` | timestamp | Snapshot timestamp |
| `snapshot_version` | integer | Version tracking |

### orders Table (42 columns)
| Column | Type | Description |
|--------|------|-------------|
| `created_by` | bigint | User who created |
| `updated_by` | bigint | User who last updated |
| `deleted_by` | bigint | User who deleted |
| `deletion_reason` | text | Reason for deletion |

## Benefits

1. **Performance**: Centralized eager loading eliminates N+1 queries
2. **Stock Accuracy**: V2 inventory with transaction logging
3. **Audit Trail**: Complete history of status changes and edits
4. **Data Preservation**: Comprehensive snapshots preserve order history
5. **Cache Efficiency**: Smart cache invalidation vs cache flush
6. **Backward Compatibility**: Accessors for legacy column names

## Testing

```bash
# Run Order-related tests
./vendor/bin/pest --filter="Order"

# Check service instantiation
php artisan tinker --execute="app()->make(App\\Services\\Order\\OrderService::class);"
```

## Files Modified

- `app/Services/Order/OrderService.php`
- `app/Http/Controllers/Admin/Order/OrderController.php`
- `app/Models/Order.php`
- `app/Models/OrderItem.php`

## Files Created

- `app/Models/OrderStatusHistory.php`
- `app/Models/OrderEditLog.php`
- `app/Traits/OrderEagerLoading.php`
- `database/migrations/2026_02_01_064654_add_snapshot_columns_to_order_items_table.php`
- `database/migrations/2026_02_01_064738_add_audit_columns_to_orders_table.php`
