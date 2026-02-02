# Order Status & Inventory Management - Complete Guide

## 🎯 Overview

এই ডকুমেন্টে Order Status change এবং Inventory Management এর সম্পূর্ণ flow বাংলা ও ইংরেজিতে ব্যাখ্যা করা হয়েছে।

---

## 📊 Order Status Flow Diagram

```
┌─────────────┐
│ INCOMPLETE  │ (Cart/Draft - কোন stock deduct হয়নি)
└──────┬──────┘
       │ Confirm Order
       ↓
┌─────────────┐
│   PENDING   │ ✅ Stock deducted (stock কমে গেছে)
└──────┬──────┘
       │
       ├──→ CANCELLED ✅ Stock returned (stock ফেরত আসবে)
       │
       ├──→ ON_HOLD (stock reserved থাকবে)
       │
       ↓
┌─────────────┐
│ PROCESSING  │ ✅ Stock already deducted
└──────┬──────┘
       │
       ├──→ CANCELLED ✅ Stock returned
       │
       ↓
┌─────────────┐
│   SHIPPED   │ ✅ Stock already deducted
└──────┬──────┘
       │
       ├──→ CANCELLED ✅ Stock returned
       │
       ↓
┌─────────────┐
│  DELIVERED  │ ✅ Stock already deducted
└──────┬──────┘
       │
       ├──→ RETURNED ✅ Stock returned (customer return করেছে)
       │
       └──→ COMPLETED (final - no further changes)


🔄 Reactivation Flow:
CANCELLED/RETURNED → PENDING ✅ Stock deducted again (পুনরায় stock কমবে)
```

---

## 🔢 Stock Calculation Examples (বাংলায়)

### Example 1: Simple Order Flow
```
Product: "Color T-Shirt"
Initial Stock: 100 pcs

1️⃣ Order Created (PENDING):
   - Stock: 100 → 95 ✅ (5 টি বিক্রি, stock কমলো)
   - Sold: 0 → 5
   - Log: "Stock deducted for order #123"

2️⃣ Customer Cancelled (PENDING → CANCELLED):
   - Stock: 95 → 100 ✅ (5 টি ফেরত, stock বাড়লো)
   - Sold: 5 → 0
   - Log: "Order cancelled - stock returned to inventory"

3️⃣ Admin Re-activates Order (CANCELLED → PENDING):
   - Stock: 100 → 95 ✅ (আবার 5 টি বিক্রি, stock কমলো)
   - Sold: 0 → 5
   - Log: "Order reactivated - stock allocated"
```

### Example 2: Return After Delivery
```
Product: "Smartphone"
Initial Stock: 50 pcs

1️⃣ Order Delivered:
   - Stock: 50 → 45 ✅ (5 টি delivery হয়েছে)
   - Sold: 0 → 5

2️⃣ Customer Returns Product (DELIVERED → RETURNED):
   - Stock: 45 → 50 ✅ (5 টি ফেরত এসেছে)
   - Sold: 5 → 0
   - Log: "Product returned - stock returned to inventory"

3️⃣ Restocking & Reselling (RETURNED → PENDING):
   - Stock: 50 → 45 ✅ (আবার 5 টি বিক্রি)
   - Sold: 0 → 5
   - Log: "Returned product resold - stock allocated"
```

### Example 3: Variable Product (Color & Size)
```
Product: "Packaging" 
Variation: BLUE, SIZE ML
Product Total Stock: 100
Variation Stock: 25

1️⃣ Order Created (PENDING):
   - Product Stock: 100 → 95 ✅ (main product stock কমলো)
   - Product Sold: 0 → 5
   - Variation Stock: 25 → 20 ✅ (variation stock কমলো)
   - Variation Sold: 0 → 5

2️⃣ Order Cancelled:
   - Product Stock: 95 → 100 ✅ (main product stock বাড়লো)
   - Product Sold: 5 → 0
   - Variation Stock: 20 → 25 ✅ (variation stock বাড়লো)
   - Variation Sold: 5 → 0
```

---

## 📋 Order Status Behavior Matrix

| Status Transition | Stock Action | Sold Stock | Payment | Explanation |
|-------------------|--------------|------------|---------|-------------|
| **incomplete → pending** | ✅ Reduce | ✅ Increase | - | প্রথমবার stock কাটা হয় |
| **pending → cancelled** | ✅ Return | ✅ Decrease | Refund | Customer cancel করেছে, stock ফেরত |
| **pending → processing** | ❌ No change | ❌ No change | - | আগে থেকেই stock কাটা |
| **processing → cancelled** | ✅ Return | ✅ Decrease | Refund | Processing এ cancel, stock ফেরত |
| **processing → shipped** | ❌ No change | ❌ No change | - | Stock already deducted |
| **shipped → delivered** | ❌ No change | ❌ No change | Auto-paid | Stock already gone |
| **delivered → returned** | ✅ Return | ✅ Decrease | Refund | Customer return করেছে |
| **cancelled → pending** | ✅ Reduce | ✅ Increase | - | Order পুনরায় activate |
| **returned → pending** | ✅ Reduce | ✅ Increase | - | Return product পুনরায় বিক্রি |
| **cancelled → cancelled** | ❌ No change | ❌ No change | - | Already cancelled |
| **returned → returned** | ❌ No change | ❌ No change | - | Already returned |

---

## 🔧 Implementation Details

### 1. Stock Adjustment Logic (`OrderService.php`)

#### `shouldReturnStock()` Method
```php
/**
 * কখন stock ফেরত দিতে হবে তা নির্ধারণ করে
 * 
 * Return stock when:
 * - pending → cancelled (customer cancelled)
 * - processing → cancelled (cancelled during processing)
 * - shipped → cancelled (cancelled in transit)
 * - delivered → returned (customer returned)
 */
private function shouldReturnStock(string $oldStatus, string $newStatus): bool
{
    $returningStatuses = ['cancelled', 'returned'];
    $activeStatuses = ['pending', 'processing', 'shipped', 'delivered', 'confirmed', 'on_hold'];
    
    // Active status থেকে cancelled/returned হলে stock ফেরত
    if (in_array($newStatus, $returningStatuses) && in_array($oldStatus, $activeStatuses)) {
        return true;
    }
    
    return false;
}
```

#### `shouldReduceStock()` Method
```php
/**
 * কখন stock কমাতে হবে তা নির্ধারণ করে
 * 
 * Reduce stock when:
 * - cancelled → pending (order reactivated)
 * - returned → pending (reselling returned product)
 */
private function shouldReduceStock(string $oldStatus, string $newStatus): bool
{
    $activeStatuses = ['pending', 'processing', 'shipped', 'delivered', 'confirmed', 'on_hold'];
    $returningStatuses = ['cancelled', 'returned'];
    
    // Cancelled/returned থেকে active হলে stock কমবে
    if (in_array($oldStatus, $returningStatuses) && in_array($newStatus, $activeStatuses)) {
        return true;
    }
    
    return false;
}
```

---

## 🗄️ Inventory System Integration

### Current Implementation (Hybrid Approach)

1. **Primary Stock Tracking**: `products.stock` & `product_variations.stock`
2. **Transaction Logging**: `inventory_transactions` table (via InventoryService)
3. **Future-Ready**: Full migration to `inventory_stocks` table prepared

### Inventory Transaction Logging

প্রতিটি stock movement এর জন্য transaction log তৈরি হয়:

```php
// Stock returned (cancelled/returned order)
InventoryAdjustmentDTO(
    productId: 123,
    quantity: 5,
    adjustmentType: 'increase',  // stock বাড়ছে
    reason: 'Order cancelled/returned - stock returned to inventory',
    locationCode: 'MAIN',
    userId: auth()->id()
)

// Stock deducted (new/reactivated order)
InventoryAdjustmentDTO(
    productId: 123,
    quantity: 5,
    adjustmentType: 'decrease',  // stock কমছে
    reason: 'Order reactivated - stock allocated',
    locationCode: 'MAIN',
    userId: auth()->id()
)
```

### Database Tables

#### 1. `products` & `product_variations`
```sql
-- Current stock tracking (legacy)
stock INT DEFAULT 0           -- Available stock
sold_stock INT DEFAULT 0      -- Total sold
```

#### 2. `inventory_stocks` (Future-ready)
```sql
-- Centralized inventory management (Amazon/Shopify style)
id BIGINT PRIMARY KEY
product_id BIGINT              -- Product reference
product_variation_id BIGINT    -- Variation reference (nullable)
location_code VARCHAR(50)      -- Warehouse location
available_quantity INT         -- Available for sale
reserved_quantity INT          -- Reserved for carts
total_quantity INT COMPUTED    -- available + reserved
minimum_threshold INT          -- Low stock alert
reorder_point INT              -- Auto-reorder trigger
```

#### 3. `inventory_transactions` (Audit Trail)
```sql
-- Every stock movement is logged
id BIGINT PRIMARY KEY
inventory_stock_id BIGINT      -- Reference to inventory_stocks
transaction_type ENUM          -- 'adjustment', 'sale', 'return', 'transfer'
quantity_before INT            -- Stock before change
quantity_after INT             -- Stock after change
quantity_change INT            -- Net change (+/-)
adjustment_type ENUM           -- 'increase', 'decrease', 'set'
reason VARCHAR                 -- Human-readable reason
reference_type VARCHAR         -- 'order', 'purchase', 'manual'
reference_id BIGINT            -- Order ID, Purchase ID, etc.
performed_by BIGINT            -- User who made the change
created_at TIMESTAMP           -- When it happened
```

---

## 🧪 Testing Scenarios

### Test Case 1: Normal Order Cancellation
```
✅ Create order (pending) → Stock reduced
✅ Cancel order → Stock returned
✅ Verify: stock matches original
✅ Check: inventory transaction logged
```

### Test Case 2: Order Reactivation
```
✅ Create order (pending) → Stock = 95
✅ Cancel order → Stock = 100
✅ Reactivate (cancelled → pending) → Stock = 95
✅ Verify: sold_stock = 5
✅ Check: 3 transactions logged
```

### Test Case 3: Delivery Return
```
✅ Deliver order → Stock = 95, Sold = 5
✅ Return order → Stock = 100, Sold = 0
✅ Verify: payment_status ready for refund
✅ Check: return transaction logged
```

### Test Case 4: Variable Product
```
✅ Order variation → Both product & variation stock reduced
✅ Cancel → Both stock returned
✅ Verify: product.stock + variation.stock correct
```

---

## 🚀 Usage Examples

### Example 1: Change Order Status (Controller)
```php
// OrderController.php
public function updateStatus(Request $request, $orderId)
{
    $validated = $request->validate([
        'status' => 'required|in:pending,processing,shipped,delivered,cancelled,returned'
    ]);
    
    // OrderService automatically handles stock adjustment
    $order = $this->orderService->updateOrderStatus(
        $orderId, 
        $validated['status']
    );
    
    // If delivered, auto-mark as paid
    if ($validated['status'] === 'delivered') {
        $order->update(['payment_status' => 'paid']);
    }
    
    return response()->json(['message' => 'Order status updated', 'order' => $order]);
}
```

### Example 2: Manual Stock Adjustment (Admin)
```php
// InventoryController.php
public function adjustStock(Request $request)
{
    $dto = new InventoryAdjustmentDTO(
        productId: $request->product_id,
        quantity: $request->quantity,
        adjustmentType: $request->type, // 'increase' or 'decrease'
        reason: $request->reason,
        userId: auth()->id(),
        locationCode: 'MAIN'
    );
    
    $this->inventoryService->adjustStock($dto);
}
```

---

## 📝 Migration Roadmap

### Phase 1: Current (✅ Implemented)
- ✅ Fixed order status stock logic
- ✅ Unified cancelled & returned behavior
- ✅ Inventory transaction logging
- ✅ Hybrid stock tracking (legacy + logging)

### Phase 2: Future Enhancement
- ⏳ Full migration to `inventory_stocks` table
- ⏳ Reserved quantity for active carts
- ⏳ Multi-warehouse support
- ⏳ Low stock alerts
- ⏳ Auto-reorder triggers

---

## ⚠️ Important Notes

1. **Incomplete Orders**: Stock is NOT deducted until status becomes 'pending'
2. **Cancelled = Returned**: Both behave identically for inventory
3. **Variable Products**: Stock updates happen on BOTH product AND variation
4. **Transaction Logging**: All stock movements are logged (even if InventoryService fails)
5. **Cache Invalidation**: Product cache is cleared after every stock change

---

## 🔍 Troubleshooting

### Problem: Stock not returning on cancel
**Solution**: Check log at `storage/logs/laravel.log` for:
```
Stock returned for simple product {"product_id":2, "quantity":1, ...}
```

### Problem: Stock negative
**Solution**: Using `max(0, $stock - $quantity)` prevents negative values

### Problem: Inventory transaction not logged
**Solution**: Check InventoryService is injected in OrderService constructor

---

## 📚 Related Files

- **Service**: `app/Services/Order/OrderService.php`
- **Controller**: `app/Http/Controllers/Admin/Order/OrderController.php`
- **Inventory**: `app/Services/Inventory/InventoryService.php`
- **DTO**: `app/DTOs/InventoryAdjustmentDTO.php`
- **Models**: `app/Models/Order.php`, `app/Models/Product.php`
- **Migrations**: `database/migrations/*inventory*.php`

---

## 👨‍💻 Developer Notes

### Key Methods to Remember:

1. `OrderService::updateOrderStatus()` - Main entry point
2. `OrderService::shouldReturnStock()` - Determines if stock should be returned
3. `OrderService::shouldReduceStock()` - Determines if stock should be reduced
4. `OrderService::returnProductStock()` - Returns stock + logs transaction
5. `OrderService::reduceProductStock()` - Reduces stock + logs transaction
6. `InventoryService::adjustStock()` - Central inventory management

### Logging Strategy:

- **INFO**: Stock changes, order status updates
- **WARNING**: Inventory transaction failures (with fallback)
- **ERROR**: Critical failures (order not found, etc.)

---

**Last Updated**: February 2, 2026  
**Version**: 2.0 (Big Tech Refactor Complete)  
**Author**: Senior Software Engineer
