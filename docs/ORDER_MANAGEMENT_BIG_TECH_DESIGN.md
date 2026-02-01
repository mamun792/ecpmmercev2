# Order Management - Big Tech UI/UX Design Plan
## Amazon-Style Order Management System

**Status:** Design Specification  
**Inspired By:** Amazon, Shopify, BigCommerce  
**Target:** Enterprise-grade Order Management Dashboard

---

## 1. Database Schema - Optimized for Scale

### Core Tables (Already Implemented)

#### `orders` Table
```sql
- id (PK, indexed)
- order_number (unique, indexed) - "ORD-YYYYMMDD-XXXX"
- user_id (FK users, indexed)
- status (enum, indexed) - pending, processing, shipped, delivered, cancelled
- payment_status (enum, indexed) - pending, paid, failed, refunded
- subtotal (decimal)
- discount_total (decimal)
- shipping_cost (decimal)
- tax_amount (decimal)
- total (decimal)
- customer_name (indexed for search)
- customer_email (indexed)
- customer_phone (indexed)
- shipping_address (text)
- shipping_city (varchar, indexed)
- shipping_district (varchar, indexed)
- shipping_postal_code (varchar)
- shipping_country (varchar, default: 'Bangladesh')
- billing_address (text)
- payment_method (varchar)
- courier_name (varchar)
- tracking_number (varchar, indexed)
- notes (text)
- admin_notes (text)
- created_at (indexed for date range filters)
- updated_at
- deleted_at (soft delete)

Indexes:
- idx_order_search (customer_name, customer_email, order_number)
- idx_order_status (status, created_at)
- idx_order_location (shipping_district, shipping_city)
- idx_order_tracking (tracking_number)
```

#### `order_items` Table
```sql
- id (PK)
- order_id (FK orders, indexed)
- product_id (FK products)
- product_variation_id (FK product_variations, nullable)
- product_name (cached)
- product_sku (cached, indexed)
- variation_name (cached)
- variation_sku (cached)
- quantity (int)
- unit_price (decimal)
- subtotal (decimal)
- discount_total (decimal)
- final_price (decimal)
- is_pre_order (boolean)
- created_at
- updated_at
- deleted_at
- deleted_by (FK users, nullable)
- deletion_reason (text)

Indexes:
- idx_order_items_product (product_id, product_variation_id)
- idx_order_items_sku (product_sku, variation_sku)
```

#### `order_status_histories` Table
```sql
- id (PK)
- order_id (FK orders, indexed)
- old_status (varchar)
- new_status (varchar)
- changed_by (FK users, indexed)
- changed_at (timestamp, indexed)
- notes (text)
- ip_address (varchar)
- user_agent (text)

Indexes:
- idx_status_history_order (order_id, changed_at DESC)
- idx_status_history_user (changed_by, changed_at DESC)
```

#### `order_edit_logs` Table
```sql
- id (PK)
- order_id (FK orders, indexed)
- field_changed (varchar, indexed)
- old_value (text)
- new_value (text)
- changed_by (FK users, indexed)
- change_reason (text)
- changed_at (timestamp, indexed)

Indexes:
- idx_edit_logs_order (order_id, changed_at DESC)
- idx_edit_logs_field (field_changed, changed_at DESC)
```

---

## 2. UI/UX Design System - Amazon-Inspired

### Color Palette
```css
/* Primary Colors */
--primary-brand: #FF9900;      /* Amazon Orange */
--primary-dark: #146EB4;       /* Amazon Blue */
--primary-light: #FFF9F0;      /* Light Orange Background */

/* Status Colors */
--status-pending: #FFC107;     /* Amber */
--status-processing: #2196F3;  /* Blue */
--status-shipped: #9C27B0;     /* Purple */
--status-delivered: #4CAF50;   /* Green */
--status-cancelled: #F44336;   /* Red */

/* Neutral Colors */
--neutral-50: #FAFAFA;
--neutral-100: #F5F5F5;
--neutral-200: #EEEEEE;
--neutral-300: #E0E0E0;
--neutral-700: #616161;
--neutral-900: #212121;

/* Semantic Colors */
--success: #4CAF50;
--warning: #FF9800;
--error: #F44336;
--info: #2196F3;
```

### Typography
```css
/* Font Family */
--font-primary: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
--font-mono: 'JetBrains Mono', 'Courier New', monospace;

/* Font Sizes */
--text-xs: 0.75rem;    /* 12px */
--text-sm: 0.875rem;   /* 14px */
--text-base: 1rem;     /* 16px */
--text-lg: 1.125rem;   /* 18px */
--text-xl: 1.25rem;    /* 20px */
--text-2xl: 1.5rem;    /* 24px */
--text-3xl: 1.875rem;  /* 30px */

/* Font Weights */
--font-normal: 400;
--font-medium: 500;
--font-semibold: 600;
--font-bold: 700;
```

### Spacing System
```css
--space-1: 0.25rem;  /* 4px */
--space-2: 0.5rem;   /* 8px */
--space-3: 0.75rem;  /* 12px */
--space-4: 1rem;     /* 16px */
--space-6: 1.5rem;   /* 24px */
--space-8: 2rem;     /* 32px */
--space-12: 3rem;    /* 48px */
```

---

## 3. Page Layout Structure

### Main Order Dashboard Layout

```
┌─────────────────────────────────────────────────────────────┐
│  HEADER (Sticky)                                            │
│  ┌──────────┬────────────────────────────┬────────────────┐ │
│  │ 📊 Orders│  Search: [______________]  │  [+ New Order] │ │
│  └──────────┴────────────────────────────┴────────────────┘ │
├─────────────────────────────────────────────────────────────┤
│  METRICS ROW (4 Cards)                                      │
│  ┌──────────┬──────────┬──────────┬──────────┐             │
│  │ Total    │ Pending  │ Shipped  │ Revenue  │             │
│  │ 1,234    │ 56       │ 789      │ $45,678  │             │
│  └──────────┴──────────┴──────────┴──────────┘             │
├─────────────────────────────────────────────────────────────┤
│  FILTERS BAR (Collapsible)                                  │
│  ┌─────────────────────────────────────────────────────────┐│
│  │ Status: [All ▼]  Date: [Last 30 days ▼]  Location: [▼] ││
│  │ [Apply Filters]  [Reset]                    [Export CSV]││
│  └─────────────────────────────────────────────────────────┘│
├─────────────────────────────────────────────────────────────┤
│  DATA TABLE (Sortable, Paginated)                          │
│  ┌─────────────────────────────────────────────────────────┐│
│  │ ☐ | Order #    | Customer    | Total  | Status | Date  ││
│  │───┼────────────┼─────────────┼────────┼────────┼───────││
│  │ ☐ | #ORD-001   | John Doe    | $250   | 🟢 Del | Jan 5 ││
│  │ ☐ | #ORD-002   | Jane Smith  | $180   | 🟡 Pen | Jan 4 ││
│  │ ☐ | #ORD-003   | Bob Johnson | $420   | 🔵 Shi | Jan 4 ││
│  └─────────────────────────────────────────────────────────┘│
│  PAGINATION (Bottom)                                        │
│  ┌─────────────────────────────────────────────────────────┐│
│  │  Showing 1-20 of 1,234   [< Prev]  [1] 2 3 4  [Next >] ││
│  └─────────────────────────────────────────────────────────┘│
└─────────────────────────────────────────────────────────────┘
```

---

## 4. Component Specifications

### 4.1 Order Status Badge
```vue
<!-- StatusBadge.vue -->
<template>
  <span :class="['status-badge', `status-${status}`]">
    <span class="status-dot"></span>
    {{ label }}
  </span>
</template>

<style scoped>
.status-badge {
  display: inline-flex;
  align-items: center;
  padding: 4px 12px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 500;
}

.status-pending {
  background: #FFF3CD;
  color: #856404;
}

.status-processing {
  background: #D1ECF1;
  color: #0C5460;
}

.status-shipped {
  background: #E2E3F1;
  color: #4A148C;
}

.status-delivered {
  background: #D4EDDA;
  color: #155724;
}

.status-cancelled {
  background: #F8D7DA;
  color: #721C24;
}

.status-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  margin-right: 6px;
  background: currentColor;
}
</style>
```

### 4.2 Order Table Component
```vue
<!-- OrderTable.vue -->
<template>
  <div class="order-table-container">
    <!-- Bulk Actions Bar -->
    <div v-if="selectedOrders.length > 0" class="bulk-actions-bar">
      <span class="selected-count">{{ selectedOrders.length }} selected</span>
      <div class="bulk-actions">
        <button @click="bulkPrintInvoices" class="btn-secondary">
          <PrinterIcon /> Print Invoices
        </button>
        <button @click="bulkExport" class="btn-secondary">
          <DownloadIcon /> Export
        </button>
        <button @click="bulkUpdateStatus" class="btn-primary">
          <EditIcon /> Update Status
        </button>
      </div>
    </div>

    <!-- Table -->
    <div class="table-wrapper">
      <table class="order-table">
        <thead>
          <tr>
            <th class="th-checkbox">
              <input 
                type="checkbox" 
                @change="toggleSelectAll"
                :checked="allSelected"
              />
            </th>
            <th @click="sort('order_number')" class="sortable">
              Order # <SortIcon :direction="sortDirection('order_number')" />
            </th>
            <th @click="sort('customer_name')" class="sortable">
              Customer <SortIcon :direction="sortDirection('customer_name')" />
            </th>
            <th>Items</th>
            <th @click="sort('total')" class="sortable text-right">
              Total <SortIcon :direction="sortDirection('total')" />
            </th>
            <th>Status</th>
            <th>Payment</th>
            <th @click="sort('created_at')" class="sortable">
              Date <SortIcon :direction="sortDirection('created_at')" />
            </th>
            <th class="th-actions">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr 
            v-for="order in orders" 
            :key="order.id"
            :class="{ 'row-selected': isSelected(order.id) }"
            @click="navigateToOrder(order.id)"
            class="order-row"
          >
            <td @click.stop>
              <input 
                type="checkbox" 
                :checked="isSelected(order.id)"
                @change="toggleSelect(order.id)"
              />
            </td>
            <td class="td-order-number">
              <span class="order-number">{{ order.order_number }}</span>
              <span v-if="order.tracking_number" class="tracking-number">
                📦 {{ order.tracking_number }}
              </span>
            </td>
            <td>
              <div class="customer-info">
                <span class="customer-name">{{ order.customer_name }}</span>
                <span class="customer-email">{{ order.customer_email }}</span>
              </div>
            </td>
            <td>
              <span class="item-count">{{ order.items_count }} items</span>
            </td>
            <td class="text-right">
              <span class="order-total">৳{{ formatNumber(order.total) }}</span>
            </td>
            <td>
              <StatusBadge :status="order.status" />
            </td>
            <td>
              <PaymentBadge :status="order.payment_status" />
            </td>
            <td>
              <div class="date-info">
                <span class="date">{{ formatDate(order.created_at) }}</span>
                <span class="time">{{ formatTime(order.created_at) }}</span>
              </div>
            </td>
            <td @click.stop class="td-actions">
              <div class="action-buttons">
                <button 
                  @click="viewOrder(order.id)" 
                  class="btn-icon"
                  title="View Details"
                >
                  <EyeIcon />
                </button>
                <button 
                  @click="editOrder(order.id)" 
                  class="btn-icon"
                  title="Edit Order"
                >
                  <EditIcon />
                </button>
                <button 
                  @click="printInvoice(order.id)" 
                  class="btn-icon"
                  title="Print Invoice"
                >
                  <PrinterIcon />
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Empty State -->
    <div v-if="orders.length === 0" class="empty-state">
      <EmptyBoxIcon class="empty-icon" />
      <h3>No orders found</h3>
      <p>Try adjusting your filters or create a new order</p>
      <button @click="createOrder" class="btn-primary">
        <PlusIcon /> Create New Order
      </button>
    </div>
  </div>
</template>

<style scoped>
.order-table-container {
  background: white;
  border-radius: 8px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
  overflow: hidden;
}

.bulk-actions-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 16px;
  background: #F0F7FF;
  border-bottom: 1px solid #E3F2FD;
}

.table-wrapper {
  overflow-x: auto;
}

.order-table {
  width: 100%;
  border-collapse: collapse;
}

.order-table thead {
  background: #FAFAFA;
  border-bottom: 2px solid #E0E0E0;
}

.order-table th {
  padding: 12px 16px;
  text-align: left;
  font-size: 12px;
  font-weight: 600;
  color: #616161;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.sortable {
  cursor: pointer;
  user-select: none;
  transition: background 0.2s;
}

.sortable:hover {
  background: #F5F5F5;
}

.order-row {
  border-bottom: 1px solid #E0E0E0;
  cursor: pointer;
  transition: background 0.2s;
}

.order-row:hover {
  background: #FAFAFA;
}

.row-selected {
  background: #E3F2FD !important;
}

.order-table td {
  padding: 16px;
  font-size: 14px;
}

.order-number {
  font-family: var(--font-mono);
  font-weight: 600;
  color: #146EB4;
}

.tracking-number {
  display: block;
  font-size: 12px;
  color: #757575;
  margin-top: 4px;
}

.customer-info {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.customer-name {
  font-weight: 500;
  color: #212121;
}

.customer-email {
  font-size: 12px;
  color: #757575;
}

.order-total {
  font-weight: 600;
  color: #212121;
  font-size: 15px;
}

.date-info {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.date {
  font-weight: 500;
  color: #212121;
}

.time {
  font-size: 12px;
  color: #757575;
}

.action-buttons {
  display: flex;
  gap: 8px;
}

.btn-icon {
  padding: 6px;
  border: 1px solid #E0E0E0;
  background: white;
  border-radius: 4px;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-icon:hover {
  background: #F5F5F5;
  border-color: #BDBDBD;
}

.empty-state {
  padding: 60px 20px;
  text-align: center;
}

.empty-icon {
  width: 80px;
  height: 80px;
  color: #BDBDBD;
  margin-bottom: 16px;
}
</style>
```

### 4.3 Advanced Filters Component
```vue
<!-- OrderFilters.vue -->
<template>
  <div class="filters-container">
    <div class="filters-header">
      <h3>Filters</h3>
      <button @click="toggleFilters" class="btn-text">
        {{ filtersExpanded ? 'Collapse' : 'Expand' }}
      </button>
    </div>

    <div v-if="filtersExpanded" class="filters-content">
      <div class="filter-grid">
        <!-- Status Filter -->
        <div class="filter-group">
          <label>Order Status</label>
          <select v-model="filters.status" multiple class="filter-select">
            <option value="">All Statuses</option>
            <option value="pending">Pending</option>
            <option value="processing">Processing</option>
            <option value="shipped">Shipped</option>
            <option value="delivered">Delivered</option>
            <option value="cancelled">Cancelled</option>
          </select>
        </div>

        <!-- Payment Status Filter -->
        <div class="filter-group">
          <label>Payment Status</label>
          <select v-model="filters.payment_status" class="filter-select">
            <option value="">All</option>
            <option value="pending">Pending</option>
            <option value="paid">Paid</option>
            <option value="failed">Failed</option>
            <option value="refunded">Refunded</option>
          </select>
        </div>

        <!-- Date Range Filter -->
        <div class="filter-group">
          <label>Date Range</label>
          <div class="date-range-inputs">
            <input 
              type="date" 
              v-model="filters.date_from"
              class="filter-input"
              placeholder="From"
            />
            <span class="date-separator">to</span>
            <input 
              type="date" 
              v-model="filters.date_to"
              class="filter-input"
              placeholder="To"
            />
          </div>
        </div>

        <!-- Location Filter -->
        <div class="filter-group">
          <label>Location</label>
          <select v-model="filters.district" class="filter-select">
            <option value="">All Districts</option>
            <option value="dhaka">Dhaka</option>
            <option value="chittagong">Chittagong</option>
            <option value="sylhet">Sylhet</option>
            <!-- Add more districts -->
          </select>
        </div>

        <!-- Amount Range Filter -->
        <div class="filter-group">
          <label>Order Amount</label>
          <div class="amount-range-inputs">
            <input 
              type="number" 
              v-model="filters.min_amount"
              class="filter-input"
              placeholder="Min"
            />
            <span class="amount-separator">-</span>
            <input 
              type="number" 
              v-model="filters.max_amount"
              class="filter-input"
              placeholder="Max"
            />
          </div>
        </div>

        <!-- Courier Filter -->
        <div class="filter-group">
          <label>Courier</label>
          <select v-model="filters.courier" class="filter-select">
            <option value="">All Couriers</option>
            <option value="steadfast">Steadfast</option>
            <option value="pathao">Pathao</option>
            <option value="redx">RedX</option>
          </select>
        </div>
      </div>

      <div class="filter-actions">
        <button @click="applyFilters" class="btn-primary">
          Apply Filters
        </button>
        <button @click="resetFilters" class="btn-secondary">
          Reset
        </button>
        <button @click="saveFilterPreset" class="btn-text">
          Save as Preset
        </button>
      </div>

      <!-- Quick Filters (Tags) -->
      <div class="quick-filters">
        <span class="quick-filter-label">Quick Filters:</span>
        <button 
          @click="applyQuickFilter('today')" 
          class="quick-filter-tag"
        >
          Today's Orders
        </button>
        <button 
          @click="applyQuickFilter('pending')" 
          class="quick-filter-tag"
        >
          Pending Orders
        </button>
        <button 
          @click="applyQuickFilter('high_value')" 
          class="quick-filter-tag"
        >
          High Value (>৳5000)
        </button>
      </div>
    </div>
  </div>
</template>
```

---

## 5. Key Features Implementation

### 5.1 Real-time Search
```javascript
// Use debounced search with backend API
const searchOrders = debounce(async (query) => {
  const results = await axios.get('/api/orders/search', {
    params: {
      q: query,
      fields: ['order_number', 'customer_name', 'customer_email', 'tracking_number']
    }
  });
  return results.data;
}, 300);
```

### 5.2 Bulk Operations
```javascript
async function bulkUpdateStatus(orderIds, newStatus) {
  const response = await axios.post('/api/orders/bulk-update-status', {
    order_ids: orderIds,
    status: newStatus,
    notify_customers: true
  });
  
  // Show success notification
  toast.success(`${orderIds.length} orders updated successfully`);
  
  // Refresh table
  await fetchOrders();
}
```

### 5.3 Export to CSV/Excel
```javascript
async function exportOrders(filters, format = 'csv') {
  const response = await axios.post('/api/orders/export', {
    filters: filters,
    format: format,
    fields: [
      'order_number',
      'customer_name',
      'customer_email',
      'total',
      'status',
      'created_at'
    ]
  }, {
    responseType: 'blob'
  });
  
  // Download file
  const url = window.URL.createObjectURL(new Blob([response.data]));
  const link = document.createElement('a');
  link.href = url;
  link.setAttribute('download', `orders_${Date.now()}.${format}`);
  document.body.appendChild(link);
  link.click();
  link.remove();
}
```

---

## 6. Performance Optimizations

### 6.1 Database Indexes (Already Implemented)
```sql
-- Already have these indexes
CREATE INDEX idx_order_search ON orders(customer_name, customer_email, order_number);
CREATE INDEX idx_order_status ON orders(status, created_at);
CREATE INDEX idx_order_location ON orders(shipping_district, shipping_city);
```

### 6.2 Pagination Strategy
```javascript
// Server-side pagination with cursor-based approach
const fetchOrders = async (page = 1, perPage = 20) => {
  const response = await axios.get('/api/orders', {
    params: {
      page: page,
      per_page: perPage,
      sort: 'created_at',
      direction: 'desc'
    }
  });
  
  return {
    data: response.data.data,
    meta: response.data.meta,
    links: response.data.links
  };
};
```

### 6.3 Caching Strategy
```javascript
// Cache frequently accessed data
const cachedMetrics = computed(() => {
  return cache.get('order_metrics', async () => {
    const response = await axios.get('/api/orders/metrics');
    return response.data;
  }, { ttl: 300 }); // 5 minutes cache
});
```

---

## 7. Mobile Responsive Design

### Breakpoints
```css
/* Responsive breakpoints */
@media (max-width: 640px) {
  /* Mobile: Stack cards vertically */
  .filter-grid { grid-template-columns: 1fr; }
  .table-wrapper { overflow-x: scroll; }
}

@media (min-width: 641px) and (max-width: 1024px) {
  /* Tablet: 2 column grid */
  .filter-grid { grid-template-columns: repeat(2, 1fr); }
}

@media (min-width: 1025px) {
  /* Desktop: 3 column grid */
  .filter-grid { grid-template-columns: repeat(3, 1fr); }
}
```

---

## 8. Accessibility (WCAG 2.1 AA)

### Requirements
- ✅ Keyboard navigation support
- ✅ ARIA labels for screen readers
- ✅ High contrast mode support
- ✅ Focus indicators
- ✅ Alt text for all icons

```html
<!-- Example: Accessible button -->
<button 
  type="button"
  aria-label="View order details"
  role="button"
  tabindex="0"
  @click="viewOrder(order.id)"
  @keypress.enter="viewOrder(order.id)"
>
  <EyeIcon aria-hidden="true" />
</button>
```

---

## 9. Next Steps

### Phase 1: Foundation (Week 1)
- ✅ Database schema optimized
- ⚠️ Create Vue components (StatusBadge, OrderTable, OrderFilters)
- ⚠️ Implement API endpoints for filtering/sorting
- ⚠️ Set up Tailwind CSS + DaisyUI theming

### Phase 2: Features (Week 2)
- ⚠️ Real-time search functionality
- ⚠️ Bulk operations (update status, export)
- ⚠️ Advanced filters with presets
- ⚠️ Order metrics dashboard

### Phase 3: Polish (Week 3)
- ⚠️ Mobile responsive design
- ⚠️ Loading states and skeletons
- ⚠️ Error handling and validation
- ⚠️ Accessibility testing

### Phase 4: Optimization (Week 4)
- ⚠️ Performance optimization
- ⚠️ Caching layer
- ⚠️ Analytics integration
- ⚠️ User testing and feedback

---

## 10. Technical Stack

### Frontend
- **Framework:** Vue 3 + Composition API
- **State Management:** Pinia
- **UI Components:** Tailwind CSS + DaisyUI
- **Icons:** Heroicons
- **Charts:** ApexCharts
- **Date Picker:** Vue Datepicker
- **Table:** TanStack Table (Vue)

### Backend
- **Framework:** Laravel 11
- **Database:** MySQL 8.0
- **Cache:** Redis
- **Queue:** Laravel Queue
- **Events:** Laravel Events

---

## Conclusion

This design specification provides a comprehensive blueprint for building an Amazon-style order management system with:
- **Scalable database architecture**
- **Modern, intuitive UI/UX**
- **Performance optimization**
- **Enterprise-grade features**

The implementation follows Big Tech best practices while maintaining Laravel/Vue ecosystem conventions.
