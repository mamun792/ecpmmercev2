# Header Search and Filter Implementation Plan

## ⚡ Quick Reference

### ✅ DO (Correct Approach)
- Use **WEB routes** in `routes/frontend.php`
- Controllers in `app/Http/Controllers/Frontend/`
- Return **Inertia::render()** for pages
- Return **response()->json()** for AJAX endpoints
- Search dropdown **closes on outside click**
- Search dropdown **closes on Escape key**

### ❌ DON'T (Wrong Approach)
- ~~Use API routes~~ (only for mobile apps/external)
- ~~Use `/api/` prefix in URLs~~
- ~~Use `app/Http/Controllers/Api/`~~
- ~~Require token authentication for frontend~~
- ~~Leave search dropdown open when clicking outside~~

---

## Project Overview
This document outlines the implementation plan for header search and filter functionality in the e-commerce application. The application follows a **monolithic architecture** with Laravel backend and Inertia.js + Vue.js frontend.

**Architecture Type:** Monolithic (Single Application)
- Backend: Laravel (Server-side)
- Frontend: Vue.js 3 + Inertia.js (SPA-like experience)
- Communication: **Web Routes** (not API routes) - Inertia handles communication
- Why Web Routes? In monolithic Inertia.js apps, we use standard web routes that return Inertia responses. API routes are only needed for external integrations or mobile apps.

**Current Status:**
- ✅ Cart functionality completed
- ✅ Order functionality completed
- 🔨 Header search and filter - **TO BE IMPLEMENTED**

---

## Feature Requirements

### 1. Header Search Component
Located in the main header of the application, accessible from all pages.

#### 1.1 Search Input Field
- Real-time search with autocomplete/suggestions dropdown
- Minimum 2-3 characters to trigger search suggestions
- Debounced search (300-500ms delay) to prevent excessive API calls
- Clear button to reset search input
- Search icon/button to execute search

#### 1.2 Search Suggestions Dropdown
**Display Format:**
- Product image thumbnail (small size)
- Product name
- Product price (with discount if applicable)
- Category badge/tag
- Max 5-8 suggestions displayed
- "View all results" link at bottom

**Behavior:**
- Shows as user types (after minimum characters)
- **Closes when clicking outside the search component** ✅
- **Closes when pressing Escape key** ✅
- Hides when search input is blurred (optional)
- Click on suggestion navigates to product detail page
- Press Enter key or click search button navigates to filter/results page

**Implementation:**
```javascript
// Add ref for search container
const searchContainerRef = ref(null);

// Outside click handler
const handleClickOutside = (event) => {
    if (searchContainerRef.value && !searchContainerRef.value.contains(event.target)) {
        showSuggestions.value = false;
    }
};

// Escape key handler
const handleEscapeKey = (event) => {
    if (event.key === 'Escape') {
        showSuggestions.value = false;
    }
};

// Add listeners on mount
onMounted(() => {
    document.addEventListener('click', handleClickOutside);
    document.addEventListener('keydown', handleEscapeKey);
});

// Remove listeners on unmount
onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
    document.removeEventListener('keydown', handleEscapeKey);
});
```

#### 1.3 Search Triggers
1. **Press Enter Key** → Navigate to filter page with search query
2. **Click Search Button** → Navigate to filter page with search query
3. **Click on Suggestion** → Navigate to specific product detail page

---

### 2. Home Page Category Display

#### 2.1 Category Section Layout
- Display categories in grid/carousel format
- Each category shows:
  - Category image/icon
  - Category name
  - Product count (optional)
  - "Show All" button

#### 2.2 "Show All" Button Functionality
**When clicked:**
- Navigate to filter page with category filter pre-applied
- URL format: `/products?category={category_slug}`
- Display only products from that specific category
- Include child category products if category has children

---

### 3. Filter/Search Results Page

#### 3.1 Page URL Structure
```
/products                           // All products
/products?search={query}            // Search results
/products?category={slug}           // Category filter
/products?search={query}&category={slug}&brand={id}&min_price={price}&max_price={price}  // Combined filters
```

#### 3.2 Page Layout Structure
```
┌─────────────────────────────────────────────────┐
│              Header with Search                  │
├─────────────┬───────────────────────────────────┤
│   Sidebar   │      Product Grid Area            │
│   Filters   │                                   │
│             │   ┌───────┐ ┌───────┐ ┌───────┐  │
│  Categories │   │Product│ │Product│ │Product│  │
│  Brands     │   └───────┘ └───────┘ └───────┘  │
│  Price      │                                   │
│  Attributes │   ┌───────┐ ┌───────┐ ┌───────┐  │
│  Sort By    │   │Product│ │Product│ │Product│  │
│             │   └───────┘ └───────┘ └───────┘  │
│             │                                   │
│             │      Pagination                   │
└─────────────┴───────────────────────────────────┘
```

#### 3.3 Sidebar Filters

**A. Category Filter (Hierarchical)**
- Display parent categories
- Expand/collapse to show child categories
- Multi-level category support (parent > child > sub-child)
- Show product count per category
- When parent category selected → show products from parent AND all child categories

**Example Structure:**
```
📂 Electronics (125)
   ├─ 📱 Mobile Phones (45)
   ├─ 💻 Laptops (30)
   └─ 🎧 Accessories (50)
      ├─ Headphones (20)
      └─ Chargers (30)

📂 Fashion (200)
   ├─ 👕 Men's Clothing (80)
   └─ 👗 Women's Clothing (120)
```

**B. Brand Filter**
- Checkbox list of all brands
- Search box to filter brand list (if many brands)
- Show product count per brand
- Multi-select enabled

**C. Price Range Filter**
- Min price input field
- Max price input field
- Price range slider (optional, enhanced UX)
- "Apply" button or auto-apply on change

**D. Attribute Filters (Dynamic)**
Based on available product attributes (Color, Size, Material, etc.):
- Each attribute shown as separate filter section
- Checkbox or color swatch (for color attribute)
- Multi-select enabled
- Show only attributes relevant to current product set

**E. Sort Options**
- Latest (newest first)
- Price: Low to High
- Price: High to Low
- Best Selling (optional)
- Top Rated (optional)

#### 3.4 Product Grid Display
- Responsive grid layout (4 columns desktop, 3 tablet, 2 mobile)
- Each product card shows:
  - Product image (with hover effect)
  - Product name
  - Price (with previous price if on sale)
  - Discount badge (if applicable)
  - Rating stars (if available)
  - Quick view button (optional)
  - Add to cart button
  - Wishlist icon

#### 3.5 Active Filters Display
- Show applied filters as removable chips/tags
- "Clear All Filters" button
- Each filter chip can be individually removed

#### 3.6 Results Count & Info
- Display: "Showing X-Y of Z results"
- If search query exists: "Search results for '{query}'"
- If no results: "No products found" with suggestions

---

## Technical Implementation Details

### 4. Backend Implementation

#### 4.1 Routes Required

**IMPORTANT:** Since this is a **monolithic architecture** with Inertia.js, we use **WEB ROUTES**, not API routes!

**Why Web Routes?**
- Inertia.js handles AJAX requests internally
- No need for separate API endpoints
- Session-based authentication works automatically
- CSRF protection included
- Server-side rendering support

**When to use API routes?**
- Only for external mobile apps
- Third-party integrations
- Separate frontend applications (React, Next.js, etc.)

---

**A. Search Suggestions Endpoint (WEB ROUTE - AJAX)**
```php
Route: GET /products/search-suggestions (Web Route)
Parameters:
  - q: string (search query, min 2 chars)
  - limit: integer (default: 8)

Response (JSON for AJAX):
{
  "suggestions": [
    {
      "id": 1,
      "name": "Product Name",
      "slug": "product-slug",
      "price": 1200,
      "previous_price": 1500,
      "feature_image": "/path/to/image.jpg",
      "category": {
        "id": 5,
        "name": "Category Name",
        "slug": "category-slug"
      }
    },
    ...
  ]
}
```

**B. Products Filter/Search Page (WEB ROUTE - INERTIA)**
```php
Route: GET /products (Web Route - returns Inertia page)
Parameters:
  - search: string (search query)
  - category_slug: string (category filter)
  - category_ids: array (multiple categories)
  - brand_id: integer or array (single or multiple brands)
  - min_price: decimal
  - max_price: decimal
  - attributes: object (e.g., {"Color": ["Red", "Blue"], "Size": ["M", "L"]})
  - sort_by: string (latest|price_asc|price_desc)
  - page: integer (pagination)
  - per_page: integer (default: 12)

Response:
{
  "products": {
    "data": [...],
    "current_page": 1,
    "last_page": 5,
    "total": 50,
    "per_page": 12
  },
  "filters": {
    "categories": [...],
    "brands": [...],
    "price_range": { "min": 100, "max": 5000 },
    "attributes": {
      "Color": ["Red", "Blue", "Green"],
      "Size": ["S", "M", "L", "XL"]
    }
  }
}
```

**C. Category with Product Count (WEB ROUTE - AJAX)**
```php
Route: GET /categories/with-counts (Web Route - AJAX)
Parameters:
  - search: string (optional, to filter counts based on search)
  - brand_id: integer (optional, to filter counts)

Response (JSON for AJAX):
{
  "categories": [
    {
      "id": 1,
      "name": "Electronics",
      "slug": "electronics",
      "image": "/path/to/image.jpg",
      "product_count": 125,
      "children": [
        {
          "id": 2,
          "name": "Mobile Phones",
          "slug": "mobile-phones",
          "product_count": 45,
          "children": []
        }
      ]
    }
  ]
}
```

#### 4.2 Controller Methods

**A. ProductController (Frontend - NOT API)**
Location: `app/Http/Controllers/Frontend/Product/ProductController.php`

```php
/**
 * Get search suggestions based on query (AJAX endpoint)
 * Returns JSON for AJAX calls from frontend
 */
public function searchSuggestions(Request $request)
{
    $query = $request->input('q', '');
    $limit = $request->input('limit', 8);
    
    if (strlen($query) < 2) {
        return response()->json(['suggestions' => []]);
    }
    
    $suggestions = Product::where('status', 'Published')
        ->where(function($q) use ($query) {
            $q->where('name', 'like', "%{$query}%")
              ->orWhere('product_code', 'like', "%{$query}%")
              ->orWhere('product_tags', 'like', "%{$query}%");
        })
        ->with(['category:id,name,slug'])
        ->select(['id', 'name', 'slug', 'price', 'previous_price', 'feature_image', 'category_id'])
        ->limit($limit)
        ->get();
    
    return response()->json([
        'suggestions' => $suggestions
    ]);
}

/**
 * Product filter/search page (WEB ROUTE - Returns Inertia page)
 * This is the main products listing page with filters
 */
public function index(Request $request)
{
    // This method already exists and handles most filtering
    // Enhance to include category hierarchy (parent + all children)
    // Current implementation at line 120-230 of ProductController
    
    // Add method to get all descendant category IDs
    // Already exists: getAllDescendantCategoryIds()
}
```

**B. CategoryService Enhancement**
Location: `app/Services/Categories/CategoryService.php`

```php
/**
 * Get categories with product counts
 */
public function getCategoriesWithProductCounts(array $filters = [])
{
    $query = Category::where('status', 'active')
        ->whereNull('parent_id')  // Get only parent categories
        ->with(['children' => function($q) {
            $q->where('status', 'active')
              ->with('children'); // Recursive loading
        }]);
    
    $categories = $query->get();
    
    // Attach product counts recursively
    return $this->attachProductCounts($categories, $filters);
}

/**
 * Recursively attach product counts to categories
 */
private function attachProductCounts($categories, $filters = [])
{
    return $categories->map(function($category) use ($filters) {
        $category->product_count = $this->getProductCountForCategory($category->id, $filters);
        
        if ($category->children->isNotEmpty()) {
            $category->children = $this->attachProductCounts($category->children, $filters);
        }
        
        return $category;
    });
}

/**
 * Get product count for category including all descendants
 */
private function getProductCountForCategory($categoryId, $filters = [])
{
    // Get all descendant category IDs
    $categoryIds = $this->getAllDescendantIds($categoryId);
    $categoryIds[] = $categoryId;
    
    $query = Product::where('status', 'Published')
        ->whereIn('category_id', $categoryIds);
    
    // Apply filters if provided (search, brand, price, etc.)
    if (!empty($filters['search'])) {
        $query->where('name', 'like', "%{$filters['search']}%");
    }
    
    if (!empty($filters['brand_id'])) {
        $query->where('brand_id', $filters['brand_id']);
    }
    
    return $query->count();
}

/**
 * Get all descendant category IDs (recursive)
 */
private function getAllDescendantIds($categoryId)
{
    $descendants = [];
    $children = Category::where('parent_id', $categoryId)
        ->where('status', 'active')
        ->pluck('id');
    
    foreach ($children as $childId) {
        $descendants[] = $childId;
        $descendants = array_merge(
            $descendants, 
            $this->getAllDescendantIds($childId)
        );
    }
    
    return $descendants;
}
```

#### 4.3 Database Queries Optimization

**A. Add Indexes**
```sql
-- Products table
ALTER TABLE products ADD INDEX idx_name (name);
ALTER TABLE products ADD INDEX idx_status_category (status, category_id);
ALTER TABLE products ADD INDEX idx_status_brand (status, brand_id);
ALTER TABLE products ADD INDEX idx_price (price);
ALTER TABLE products ADD INDEX idx_created_at (created_at);

-- Categories table
ALTER TABLE categories ADD INDEX idx_parent_status (parent_id, status);
ALTER TABLE categories ADD INDEX idx_slug (slug);

-- Brands table
ALTER TABLE brands ADD INDEX idx_status (status);
```

**B. Eager Loading**
Always use eager loading to prevent N+1 queries:
```php
Product::with([
    'category.parentRecursive',
    'brand',
    'variations.attributes.value.attribute'
])
```

**C. Caching Strategy**
```php
// Cache search suggestions (short TTL - 5 minutes)
Cache::remember("search_suggestions_{$query}", 300, function() use ($query) {
    // ... query logic
});

// Cache category tree with counts (15 minutes)
Cache::remember('categories_with_counts', 900, function() {
    // ... query logic
});

// Cache filter options (brands, attributes) - 30 minutes
Cache::remember('filter_options', 1800, function() {
    // ... query logic
});
```

---

### 5. Frontend Implementation

#### 5.1 Components to Create

**A. Header Search Component**
Location: `resources/js/Components/Header/SearchBar.vue`

```vue
<script setup>
import { ref, watch, onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { debounce } from 'lodash';

const searchQuery = ref('');
const suggestions = ref([]);
const showSuggestions = ref(false);
const loading = ref(false);
const searchContainerRef = ref(null);

// Debounced search function
const fetchSuggestions = debounce(async () => {
    if (searchQuery.value.length < 2) {
        suggestions.value = [];
        showSuggestions.value = false;
        return;
    }
    
    loading.value = true;
    try {
        // WEB ROUTE - not /api/ (monolithic architecture)
        const response = await axios.get('/products/search-suggestions', {
            params: { q: searchQuery.value }
        });
        suggestions.value = response.data.suggestions;
        showSuggestions.value = true;
    } catch (error) {
        console.error('Search error:', error);
    } finally {
        loading.value = false;
    }
}, 300);

watch(searchQuery, () => {
    fetchSuggestions();
});

// Handle search submit (Enter or button click)
const handleSearch = () => {
    if (searchQuery.value.trim()) {
        router.get('/products', { search: searchQuery.value });
        showSuggestions.value = false;
    }
};

// Navigate to product
const goToProduct = (slug) => {
    router.get(`/product/${slug}`);
    showSuggestions.value = false;
    searchQuery.value = '';
};

// Clear search
const clearSearch = () => {
    searchQuery.value = '';
    suggestions.value = [];
    showSuggestions.value = false;
};

// Handle click outside to close suggestions
const handleClickOutside = (event) => {
    if (searchContainerRef.value && !searchContainerRef.value.contains(event.target)) {
        showSuggestions.value = false;
    }
};

// Handle escape key to close suggestions
const handleEscapeKey = (event) => {
    if (event.key === 'Escape') {
        showSuggestions.value = false;
    }
};

// Add event listeners on mount
onMounted(() => {
    document.addEventListener('click', handleClickOutside);
    document.addEventListener('keydown', handleEscapeKey);
});

// Remove event listeners on unmount
onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
    document.removeEventListener('keydown', handleEscapeKey);
});
</script>

<template>
    <div ref="searchContainerRef" class="relative w-full max-w-2xl">
        <div class="relative">
            <input
                v-model="searchQuery"
                @keyup.enter="handleSearch"
                @focus="fetchSuggestions"
                type="text"
                placeholder="Search products..."
                class="w-full px-4 py-3 pr-24 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary"
            />
            
            <!-- Clear button -->
            <button
                v-if="searchQuery"
                @click="clearSearch"
                class="absolute right-16 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
            >
                <XIcon class="w-5 h-5" />
            </button>
            
            <!-- Search button -->
            <button
                @click="handleSearch"
                class="absolute right-2 top-1/2 -translate-y-1/2 bg-primary text-white px-4 py-2 rounded-md"
            >
                <SearchIcon class="w-5 h-5" />
            </button>
        </div>
        
        <!-- Suggestions dropdown -->
        <div
            v-if="showSuggestions && suggestions.length > 0"
            class="absolute z-50 w-full mt-2 bg-white border border-gray-200 rounded-lg shadow-lg max-h-96 overflow-y-auto"
        >
            <div
                v-for="product in suggestions"
                :key="product.id"
                @click="goToProduct(product.slug)"
                class="flex items-center gap-3 p-3 hover:bg-gray-50 cursor-pointer border-b last:border-b-0"
            >
                <img
                    :src="product.feature_image"
                    :alt="product.name"
                    class="w-12 h-12 object-cover rounded"
                />
                <div class="flex-1">
                    <h4 class="text-sm font-medium text-gray-900">{{ product.name }}</h4>
                    <p class="text-xs text-gray-500">{{ product.category?.name }}</p>
                </div>
                <div class="text-right">
                    <p class="text-sm font-bold text-primary">৳{{ product.price }}</p>
                    <p v-if="product.previous_price" class="text-xs text-gray-400 line-through">
                        ৳{{ product.previous_price }}
                    </p>
                </div>
            </div>
            
            <div
                @click="handleSearch"
                class="p-3 text-center text-primary font-medium hover:bg-gray-50 cursor-pointer"
            >
                View all results →
            </div>
        </div>
        
        <!-- Loading indicator -->
        <div v-if="loading" class="absolute right-20 top-1/2 -translate-y-1/2">
            <LoadingSpinner class="w-5 h-5" />
        </div>
    </div>
</template>
```

**B. Category Grid Component (Home Page)**
Location: `resources/js/Components/Home/CategoryGrid.vue`

```vue
<script setup>
import { router } from '@inertiajs/vue3';

const props = defineProps({
    categories: {
        type: Array,
        required: true
    }
});

const showCategoryProducts = (slug) => {
    router.get('/products', { category: slug });
};
</script>

<template>
    <section class="py-12 bg-gray-50">
        <div class="container mx-auto px-4">
            <h2 class="text-3xl font-bold text-center mb-8">Shop by Category</h2>
            
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-6">
                <div
                    v-for="category in categories"
                    :key="category.id"
                    class="bg-white rounded-lg shadow-md hover:shadow-xl transition-shadow p-6 text-center"
                >
                    <div class="w-20 h-20 mx-auto mb-4 rounded-full bg-gray-100 flex items-center justify-center">
                        <img
                            v-if="category.image"
                            :src="category.image"
                            :alt="category.name"
                            class="w-16 h-16 object-contain"
                        />
                        <span v-else-if="category.icon" v-html="category.icon" class="w-10 h-10"></span>
                    </div>
                    
                    <h3 class="font-semibold text-gray-900 mb-2">{{ category.name }}</h3>
                    <p v-if="category.product_count" class="text-sm text-gray-500 mb-3">
                        {{ category.product_count }} Products
                    </p>
                    
                    <button
                        @click="showCategoryProducts(category.slug)"
                        class="w-full px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors text-sm font-medium"
                    >
                        Show All
                    </button>
                </div>
            </div>
        </div>
    </section>
</template>
```

**C. Product Filter Page**
Location: `resources/js/Pages/Products/Index.vue`

```vue
<script setup>
import { ref, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head } from '@inertiajs/vue3';

const props = defineProps({
    products: Object,         // Paginated products
    filters: Object,          // Available filter options
    appliedFilters: Object,   // Currently applied filters
});

// Filter state
const selectedCategories = ref(props.appliedFilters?.category_ids || []);
const selectedBrands = ref(props.appliedFilters?.brand_ids || []);
const minPrice = ref(props.appliedFilters?.min_price || '');
const maxPrice = ref(props.appliedFilters?.max_price || '');
const selectedAttributes = ref(props.appliedFilters?.attributes || {});
const sortBy = ref(props.appliedFilters?.sort_by || 'latest');
const searchQuery = ref(props.appliedFilters?.search || '');

// Sidebar mobile toggle
const showFilters = ref(false);

// Apply filters
const applyFilters = () => {
    router.get('/products', {
        search: searchQuery.value,
        category_ids: selectedCategories.value,
        brand_ids: selectedBrands.value,
        min_price: minPrice.value,
        max_price: maxPrice.value,
        attributes: selectedAttributes.value,
        sort_by: sortBy.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

// Watch for filter changes (debounced)
const debouncedApplyFilters = debounce(applyFilters, 500);

watch([selectedCategories, selectedBrands, minPrice, maxPrice, selectedAttributes, sortBy], () => {
    debouncedApplyFilters();
});

// Clear all filters
const clearAllFilters = () => {
    selectedCategories.value = [];
    selectedBrands.value = [];
    minPrice.value = '';
    maxPrice.value = '';
    selectedAttributes.value = {};
    sortBy.value = 'latest';
    searchQuery.value = '';
    applyFilters();
};

// Category selection (with hierarchy support)
const toggleCategory = (categoryId) => {
    const index = selectedCategories.value.indexOf(categoryId);
    if (index === -1) {
        selectedCategories.value.push(categoryId);
    } else {
        selectedCategories.value.splice(index, 1);
    }
};

// Brand selection
const toggleBrand = (brandId) => {
    const index = selectedBrands.value.indexOf(brandId);
    if (index === -1) {
        selectedBrands.value.push(brandId);
    } else {
        selectedBrands.value.splice(index, 1);
    }
};

// Attribute selection
const toggleAttribute = (attrName, attrValue) => {
    if (!selectedAttributes.value[attrName]) {
        selectedAttributes.value[attrName] = [];
    }
    
    const index = selectedAttributes.value[attrName].indexOf(attrValue);
    if (index === -1) {
        selectedAttributes.value[attrName].push(attrValue);
    } else {
        selectedAttributes.value[attrName].splice(index, 1);
        
        // Remove empty attribute arrays
        if (selectedAttributes.value[attrName].length === 0) {
            delete selectedAttributes.value[attrName];
        }
    }
};

// Active filters count
const activeFiltersCount = computed(() => {
    let count = 0;
    count += selectedCategories.value.length;
    count += selectedBrands.value.length;
    if (minPrice.value || maxPrice.value) count++;
    count += Object.values(selectedAttributes.value).flat().length;
    return count;
});
</script>

<template>
    <Head title="Products" />
    <GuestLayout>
        <div class="container mx-auto px-4 py-8">
            <!-- Header -->
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-3xl font-bold">
                        <span v-if="searchQuery">Search results for "{{ searchQuery }}"</span>
                        <span v-else>All Products</span>
                    </h1>
                    <p class="text-gray-600 mt-2">
                        Showing {{ products.from }}-{{ products.to }} of {{ products.total }} results
                    </p>
                </div>
                
                <!-- Mobile filter toggle -->
                <button
                    @click="showFilters = !showFilters"
                    class="lg:hidden px-4 py-2 bg-primary text-white rounded-lg"
                >
                    Filters ({{ activeFiltersCount }})
                </button>
            </div>
            
            <!-- Active filters chips -->
            <div v-if="activeFiltersCount > 0" class="mb-6 flex flex-wrap gap-2">
                <button
                    @click="clearAllFilters"
                    class="px-3 py-1 bg-red-100 text-red-700 rounded-full text-sm font-medium"
                >
                    Clear All
                </button>
                <!-- Category chips -->
                <!-- Brand chips -->
                <!-- Price chip -->
                <!-- Attribute chips -->
            </div>
            
            <div class="flex gap-8">
                <!-- Sidebar Filters -->
                <aside
                    :class="[
                        'lg:w-64 lg:block',
                        showFilters ? 'block' : 'hidden'
                    ]"
                    class="bg-white p-6 rounded-lg shadow-md h-fit sticky top-4"
                >
                    <!-- Sort By -->
                    <div class="mb-6">
                        <h3 class="font-semibold text-lg mb-3">Sort By</h3>
                        <select
                            v-model="sortBy"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2"
                        >
                            <option value="latest">Latest</option>
                            <option value="price_asc">Price: Low to High</option>
                            <option value="price_desc">Price: High to Low</option>
                        </select>
                    </div>
                    
                    <!-- Categories -->
                    <div class="mb-6 border-t pt-6">
                        <h3 class="font-semibold text-lg mb-3">Categories</h3>
                        <CategoryFilterTree
                            :categories="filters.categories"
                            :selected="selectedCategories"
                            @toggle="toggleCategory"
                        />
                    </div>
                    
                    <!-- Brands -->
                    <div class="mb-6 border-t pt-6">
                        <h3 class="font-semibold text-lg mb-3">Brands</h3>
                        <div class="space-y-2 max-h-64 overflow-y-auto">
                            <label
                                v-for="brand in filters.brands"
                                :key="brand.id"
                                class="flex items-center gap-2 cursor-pointer"
                            >
                                <input
                                    type="checkbox"
                                    :checked="selectedBrands.includes(brand.id)"
                                    @change="toggleBrand(brand.id)"
                                    class="rounded text-primary"
                                />
                                <span class="text-sm">{{ brand.brand_name }}</span>
                                <span class="text-xs text-gray-500">({{ brand.product_count }})</span>
                            </label>
                        </div>
                    </div>
                    
                    <!-- Price Range -->
                    <div class="mb-6 border-t pt-6">
                        <h3 class="font-semibold text-lg mb-3">Price Range</h3>
                        <div class="flex gap-2">
                            <input
                                v-model="minPrice"
                                type="number"
                                placeholder="Min"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2"
                            />
                            <input
                                v-model="maxPrice"
                                type="number"
                                placeholder="Max"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2"
                            />
                        </div>
                    </div>
                    
                    <!-- Dynamic Attributes -->
                    <div
                        v-for="(values, attrName) in filters.attributes"
                        :key="attrName"
                        class="mb-6 border-t pt-6"
                    >
                        <h3 class="font-semibold text-lg mb-3">{{ attrName }}</h3>
                        <div class="space-y-2">
                            <label
                                v-for="value in values"
                                :key="value"
                                class="flex items-center gap-2 cursor-pointer"
                            >
                                <input
                                    type="checkbox"
                                    :checked="selectedAttributes[attrName]?.includes(value)"
                                    @change="toggleAttribute(attrName, value)"
                                    class="rounded text-primary"
                                />
                                <span class="text-sm">{{ value }}</span>
                            </label>
                        </div>
                    </div>
                </aside>
                
                <!-- Product Grid -->
                <div class="flex-1">
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                        <ProductCard
                            v-for="product in products.data"
                            :key="product.id"
                            :product="product"
                        />
                    </div>
                    
                    <!-- No results -->
                    <div v-if="products.data.length === 0" class="text-center py-12">
                        <p class="text-gray-500 text-lg">No products found</p>
                        <button
                            @click="clearAllFilters"
                            class="mt-4 px-6 py-2 bg-primary text-white rounded-lg"
                        >
                            Clear Filters
                        </button>
                    </div>
                    
                    <!-- Pagination -->
                    <Pagination
                        v-if="products.last_page > 1"
                        :current-page="products.current_page"
                        :last-page="products.last_page"
                        :total="products.total"
                        class="mt-8"
                    />
                </div>
            </div>
        </div>
    </GuestLayout>
</template>
```

**D. Category Filter Tree Component**
Location: `resources/js/Components/Filters/CategoryFilterTree.vue`

```vue
<script setup>
import { ref } from 'vue';

const props = defineProps({
    categories: Array,
    selected: Array,
    level: {
        type: Number,
        default: 0
    }
});

const emit = defineEmits(['toggle']);

const expandedCategories = ref(new Set());

const toggleExpand = (categoryId) => {
    if (expandedCategories.value.has(categoryId)) {
        expandedCategories.value.delete(categoryId);
    } else {
        expandedCategories.value.add(categoryId);
    }
    expandedCategories.value = new Set(expandedCategories.value);
};

const hasChildren = (category) => {
    return category.children && category.children.length > 0;
};
</script>

<template>
    <div class="space-y-1">
        <div
            v-for="category in categories"
            :key="category.id"
            :style="{ paddingLeft: `${level * 12}px` }"
        >
            <div class="flex items-center gap-2">
                <!-- Expand/Collapse button -->
                <button
                    v-if="hasChildren(category)"
                    @click="toggleExpand(category.id)"
                    class="w-5 h-5 flex items-center justify-center"
                >
                    <ChevronRightIcon
                        :class="[
                            'w-4 h-4 transition-transform',
                            expandedCategories.has(category.id) ? 'rotate-90' : ''
                        ]"
                    />
                </button>
                <div v-else class="w-5"></div>
                
                <!-- Checkbox and label -->
                <label class="flex items-center gap-2 cursor-pointer flex-1">
                    <input
                        type="checkbox"
                        :checked="selected.includes(category.id)"
                        @change="emit('toggle', category.id)"
                        class="rounded text-primary"
                    />
                    <span class="text-sm">{{ category.name }}</span>
                    <span class="text-xs text-gray-500">({{ category.product_count }})</span>
                </label>
            </div>
            
            <!-- Children categories -->
            <CategoryFilterTree
                v-if="hasChildren(category) && expandedCategories.has(category.id)"
                :categories="category.children"
                :selected="selected"
                :level="level + 1"
                @toggle="emit('toggle', $event)"
            />
        </div>
    </div>
</template>
```

#### 5.2 Routes to Create/Update

**ALL ROUTES IN:** `routes/frontend.php` (Web Routes - NOT API Routes)

```php
<?php
// routes/frontend.php

use App\Http\Controllers\Frontend\Product\ProductController;
use App\Http\Controllers\Frontend\Category\CategoryController;

// Product search/filter page (Inertia page)
Route::get('/products', [ProductController::class, 'index'])->name('products.index');

// Search suggestions (AJAX endpoint - returns JSON)
Route::get('/products/search-suggestions', [ProductController::class, 'searchSuggestions'])
    ->name('products.search-suggestions');

// Categories with product counts (AJAX endpoint - returns JSON)
Route::get('/categories/with-counts', [CategoryController::class, 'getCategoriesWithCounts'])
    ->name('categories.with-counts');
```

**Note:** These are **WEB routes**, not API routes because:
- ✅ Uses session-based authentication
- ✅ CSRF protection automatic
- ✅ Can return both Inertia pages AND JSON
- ✅ Consistent with monolithic architecture
- ✅ No need for token authentication

---

### 6. Category Hierarchy Logic

#### 6.1 Parent-Child Relationship

**Database Structure (Already Exists):**
```
categories table:
- id
- name
- slug
- parent_id (NULL for root categories)
- status
- image
- icon
- order
```

**Hierarchy Example:**
```
Electronics (parent_id: NULL)
├── Mobile Phones (parent_id: Electronics.id)
│   ├── Android (parent_id: Mobile_Phones.id)
│   └── iOS (parent_id: Mobile_Phones.id)
└── Laptops (parent_id: Electronics.id)
    ├── Gaming (parent_id: Laptops.id)
    └── Business (parent_id: Laptops.id)
```

#### 6.2 Product Display Logic

**When parent category selected:**
```
User selects: "Electronics"
Products shown: 
  - All products with category_id = Electronics.id
  - All products with category_id = Mobile_Phones.id
  - All products with category_id = Android.id
  - All products with category_id = iOS.id
  - All products with category_id = Laptops.id
  - All products with category_id = Gaming.id
  - All products with category_id = Business.id
```

**Implementation:**
```php
// Get all descendant category IDs (recursive)
private function getAllDescendantCategoryIds($categoryId)
{
    $categoryIds = [$categoryId];
    $children = Category::where('parent_id', $categoryId)->pluck('id');
    
    foreach ($children as $childId) {
        $categoryIds = array_merge(
            $categoryIds,
            $this->getAllDescendantCategoryIds($childId)
        );
    }
    
    return $categoryIds;
}

// Usage in product filter
$categoryIds = $this->getAllDescendantCategoryIds($selectedCategoryId);
$products = Product::whereIn('category_id', $categoryIds)->get();
```

---

### 7. Performance Considerations

#### 7.1 Query Optimization
- Use database indexes on frequently filtered columns
- Implement eager loading to prevent N+1 queries
- Use query scopes for reusable query logic
- Limit SELECT fields to only what's needed

#### 7.2 Caching Strategy
```php
// Cache keys structure
'search_suggestions_{query}'           // 5 minutes
'categories_with_counts'               // 15 minutes
'filter_options'                       // 30 minutes
'products_filter_{hash}'               // 10 minutes
```

**Cache Invalidation:**
- Clear on product create/update/delete
- Clear on category create/update/delete
- Clear on brand create/update/delete

#### 7.3 Frontend Optimization
- Debounce search input (300-500ms)
- Lazy load images
- Implement virtual scrolling for large lists
- Use pagination instead of infinite scroll for SEO

---

### 8. Mobile Responsiveness

#### 8.1 Mobile Filter Sidebar
- Off-canvas sidebar (slides in from left/bottom)
- Full-screen overlay on mobile
- Sticky "Apply Filters" button
- Filter count badge on filter button

#### 8.2 Mobile Product Grid
- 2 columns on mobile
- 3 columns on tablet
- 4 columns on desktop

#### 8.3 Mobile Search
- Full-width search bar
- Suggestions dropdown full-width
- Touch-friendly tap targets (min 44x44px)

---

### 9. SEO Considerations

#### 9.1 URL Structure (Clean URLs)
```
/products                                    // All products
/products?search=laptop                      // Search results
/products?category=electronics               // Category page
/products?category=electronics&brand=samsung // Filtered
```

#### 9.2 Meta Tags
```html
<title>Search Results for "laptop" | Your Store</title>
<meta name="description" content="Browse our selection of laptops...">
<link rel="canonical" href="https://yourstore.com/products?search=laptop">
```

#### 9.3 Structured Data (JSON-LD)
```json
{
  "@context": "https://schema.org",
  "@type": "ItemList",
  "itemListElement": [...]
}
```

---

### 10. Testing Checklist

#### 10.1 Functional Testing
- [ ] Search suggestions appear after 2 characters
- [ ] Search suggestions hide on blur/escape
- [ ] Enter key triggers search
- [ ] Search button triggers search
- [ ] Clicking suggestion navigates to product
- [ ] Category "Show All" button works
- [ ] Parent category shows all child products
- [ ] Multi-select filters work correctly
- [ ] Price range filter works
- [ ] Attribute filters work
- [ ] Sort options work
- [ ] Pagination works
- [ ] Clear filters works
- [ ] No results state shows properly

#### 10.2 Performance Testing
- [ ] Search API responds < 300ms
- [ ] Filter API responds < 500ms
- [ ] Page loads < 2 seconds
- [ ] No N+1 queries
- [ ] Caching works correctly

#### 10.3 Mobile Testing
- [ ] Filter sidebar works on mobile
- [ ] Search works on mobile
- [ ] Touch targets are adequate
- [ ] Responsive grid works
- [ ] No horizontal scroll

#### 10.4 Browser Testing
- [ ] Chrome
- [ ] Firefox
- [ ] Safari
- [ ] Edge
- [ ] Mobile Safari
- [ ] Mobile Chrome

---

### 11. Implementation Phases

#### Phase 1: Backend API Development (Week 1)
1. Create search suggestions API endpoint
2. Enhance filter products API endpoint
3. Create categories with counts API endpoint
4. Add necessary database indexes
5. Implement caching layer
6. Add unit tests for APIs

#### Phase 2: Frontend Components (Week 2)
1. Create SearchBar component
2. Create CategoryGrid component for home page
3. Create ProductFilterPage
4. Create CategoryFilterTree component
5. Create ProductCard component
6. Add responsive styles

#### Phase 3: Integration & Testing (Week 3)
1. Integrate search with header
2. Integrate categories in home page
3. Create product filter route
4. Connect all filters to API
5. Add loading states and error handling
6. Cross-browser testing
7. Mobile testing

#### Phase 4: Optimization & Polish (Week 4)
1. Optimize database queries
2. Implement comprehensive caching
3. Add lazy loading for images
4. SEO optimization
5. Performance testing
6. Bug fixes and refinements

---

### 12. Future Enhancements

#### 12.1 Advanced Features (Future)
- Autocomplete with product suggestions and category suggestions
- Recent searches history
- Popular searches
- Search by image
- Voice search
- Saved filters
- Price drop alerts
- Advanced product comparison
- Smart recommendations based on search history

#### 12.2 Analytics
- Track search queries
- Track popular filters
- Track conversion rates per category
- A/B test different filter layouts

---

## Architecture: Web Routes vs API Routes

### 🏗️ Your Project Architecture

```
┌─────────────────────────────────────────┐
│     MONOLITHIC ARCHITECTURE             │
│                                         │
│  ┌─────────────────────────────────┐   │
│  │   Laravel Backend (Server)      │   │
│  │   - Controllers                 │   │
│  │   - Models                      │   │
│  │   - Services                    │   │
│  └──────────┬──────────────────────┘   │
│             │                           │
│             │ Inertia.js Bridge         │
│             ↓                           │
│  ┌─────────────────────────────────┐   │
│  │   Vue.js Frontend (Client)      │   │
│  │   - Components                  │   │
│  │   - Pages                       │   │
│  │   - Layouts                     │   │
│  └─────────────────────────────────┘   │
│                                         │
│  Same Server, Same Application         │
└─────────────────────────────────────────┘
```

### ✅ Why Web Routes for This Project?

**Your Project Structure:**
```
Laravel (Backend) + Inertia.js + Vue.js (Frontend)
↓
Monolithic Application (Single Server)
```

**Use WEB ROUTES because:**
1. **Inertia.js handles communication** - no need for separate API
2. **Session authentication** - already works with web routes
3. **CSRF protection** - automatic with web middleware
4. **Simpler architecture** - one application, one route file
5. **Can return both** - Inertia pages AND JSON responses

**Example:**
```php
// WEB ROUTE (routes/frontend.php)
Route::get('/products', [ProductController::class, 'index']);

// Controller can return BOTH:
public function index(Request $request) {
    // For page visits - return Inertia
    if (!$request->wantsJson()) {
        return Inertia::render('Products/Index', [...]);
    }
    
    // For AJAX calls - return JSON
    return response()->json([...]);
}
```

### ❌ When NOT to use API Routes?

**API Routes are for:**
- Separate frontend apps (React, Next.js on different server)
- Mobile applications (iOS, Android)
- Third-party integrations
- Microservices architecture

**Your project is MONOLITHIC, so use WEB ROUTES!**

---

### 📊 Comparison Table

| Feature | Web Routes (✅ USE THIS) | API Routes (❌ NOT NEEDED) |
|---------|------------------------|---------------------------|
| **Route File** | `routes/frontend.php` | `routes/api.php` |
| **URL Prefix** | `/products` | `/api/products` |
| **Controller Path** | `Frontend/Product/` | `Api/Product/` |
| **Authentication** | Session (automatic) | Token (JWT/Sanctum) |
| **CSRF Protection** | ✅ Automatic | ❌ Must disable |
| **Response Type** | Inertia + JSON | JSON only |
| **Use Case** | Monolithic app | Separate frontend |
| **Complexity** | Simple | Complex |

---

## Summary

This document provides a complete implementation plan for:
1. **Header Search** - Real-time search with autocomplete suggestions
2. **Home Page Categories** - Category grid with "Show All" functionality
3. **Product Filter Page** - Comprehensive filtering with hierarchical categories, brands, price, attributes, and sorting

**Key Features:**
- Parent category selection includes all child category products
- Real-time search suggestions with **outside click to close dropdown**
- Multi-level category hierarchy
- Multiple filter types (category, brand, price, attributes)
- Mobile-responsive design
- SEO-friendly URLs
- Optimized performance with caching

**Technology Stack:**
- **Architecture:** Monolithic (Laravel + Inertia.js)
- **Routes:** Web Routes (NOT API routes)
- Backend: Laravel (Web Controllers)
- Frontend: Vue.js 3 + Inertia.js
- Database: MySQL (with proper indexing)
- Caching: Redis/Memcached
- UI: Tailwind CSS

**Important Notes:**
- ✅ Use **WEB routes** in `routes/frontend.php`
- ✅ Controllers return **Inertia pages** or **JSON** (not API responses)
- ✅ Search dropdown **closes on outside click** and **Escape key**
- ✅ CSRF protection automatic (no token needed)
- ✅ Session-based authentication (no JWT/Sanctum needed for frontend)

**Implementation Timeline:** 4 weeks
**Status:** Documentation Phase Complete ✅
**Next Step:** Begin Phase 1 - Backend Web Route Development

---

## 🎯 Quick Implementation Checklist

### Backend (routes/frontend.php)
- [ ] Add `/products` route (Inertia page)
- [ ] Add `/products/search-suggestions` route (JSON endpoint)
- [ ] Add `/categories/with-counts` route (JSON endpoint)
- [ ] Create `Frontend/Product/ProductController`
- [ ] Create search suggestions method
- [ ] Create filter products method
- [ ] Test routes with Postman/browser

### Frontend Components
- [ ] Create `SearchBar.vue` with:
  - [ ] Search input field
  - [ ] Debounced search (300ms)
  - [ ] Suggestions dropdown
  - [ ] Outside click handler ✅
  - [ ] Escape key handler ✅
  - [ ] Clear button
- [ ] Create `CategoryGrid.vue` for home page
- [ ] Create `Products/Index.vue` (filter page)
- [ ] Create `CategoryFilterTree.vue` (hierarchical)
- [ ] Add to header component

### Testing
- [ ] Search suggestions appear after 2 chars
- [ ] Dropdown closes on outside click ✅
- [ ] Dropdown closes on Escape key ✅
- [ ] Category filter includes child products
- [ ] Price filter works
- [ ] Brand filter works
- [ ] Pagination works

---

## 📞 Questions Answered

### Q1: Why use web routes instead of API routes?
**A:** Because this is a **monolithic architecture** with Inertia.js. Web routes:
- Work with Inertia.js automatically
- Support session authentication (no tokens needed)
- Have automatic CSRF protection
- Can return both Inertia pages AND JSON
- Simpler to maintain

### Q2: How to close search dropdown when clicking outside?
**A:** Use Vue's `onMounted` and `onUnmounted` hooks:
```javascript
const searchContainerRef = ref(null);

const handleClickOutside = (event) => {
    if (searchContainerRef.value && !searchContainerRef.value.contains(event.target)) {
        showSuggestions.value = false;
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});
```

And add `ref="searchContainerRef"` to the container div.

---

**END OF DOCUMENT**
