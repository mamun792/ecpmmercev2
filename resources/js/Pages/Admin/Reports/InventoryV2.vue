<script setup>
import { ref, computed } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import {
  Package,
  TrendingDown,
  TrendingUp,
  DollarSign,
  ShoppingCart,
  Download,
  FileText,
  Filter,
  Search,
  RefreshCw,
  BarChart3,
  Archive,
  AlertTriangle,
  CheckCircle,
  XCircle,
  Info,
  Zap,
  Target,
  Award,
  Clock,
  Eye
} from 'lucide-vue-next';
import { toast } from "@steveyuowo/vue-hot-toast";

const props = defineProps({
  products: Array,
  totalProducts: Number,
  totalStock: Number,
  totalSold: Number,
  totalValue: Number,
  filters: Object,
});

// Loading state
const isLoading = ref(false);

// Filter state
const searchQuery = ref(props.filters?.search || '');
const selectedCategory = ref(props.filters?.category || '');
const stockStatus = ref(props.filters?.stock_status || 'all');
const sortBy = ref(props.filters?.sort_by || 'name');

// Computed stats
const lowStockProducts = computed(() => {
  return props.products?.filter(p => {
    const stock = p.computed_stock || 0;
    const reorderLevel = p.reorder_level || 10;
    return stock > 0 && stock <= reorderLevel;
  }).length || 0;
});

const outOfStockProducts = computed(() => {
  return props.products?.filter(p => (p.computed_stock || 0) === 0).length || 0;
});

const inStockProducts = computed(() => {
  return props.products?.filter(p => {
    const stock = p.computed_stock || 0;
    const reorderLevel = p.reorder_level || 10;
    return stock > reorderLevel;
  }).length || 0;
});

// Turnover rate (sold / stock ratio)
const turnoverRate = computed(() => {
  if (props.totalStock === 0) return 0;
  return ((props.totalSold / (props.totalStock + props.totalSold)) * 100).toFixed(1);
});

// Format currency
const formatCurrency = (amount) => {
  return '৳' + (Number(amount) || 0).toLocaleString('en-BD', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
};

// Format number
const formatNumber = (num) => {
  return (Number(num) || 0).toLocaleString('en-US');
};

// Get product stock - USE COMPUTED VALUES FROM BACKEND
const getProductStock = (product) => {
  return product.computed_stock || 0;
};

// Get product sold - USE COMPUTED VALUES FROM BACKEND
const getProductSold = (product) => {
  return product.computed_sold || 0;
};

// Get stock status
const getStockStatus = (product) => {
  const stock = getProductStock(product);
  const reorderLevel = product.reorder_level || 10;

  if (stock === 0) return { label: 'Out of Stock', color: 'red', bgColor: 'bg-red-50', textColor: 'text-red-700', borderColor: 'border-red-200' };
  if (stock <= reorderLevel) return { label: 'Low Stock', color: 'amber', bgColor: 'bg-amber-50', textColor: 'text-amber-700', borderColor: 'border-amber-200' };
  return { label: 'In Stock', color: 'green', bgColor: 'bg-green-50', textColor: 'text-green-700', borderColor: 'border-green-200' };
};

// Apply filters
const applyFilters = () => {
  isLoading.value = true;
  router.visit(route('admin.reports.inventory.v2'), {
    method: 'get',
    data: {
      search: searchQuery.value,
      category: selectedCategory.value,
      stock_status: stockStatus.value,
      sort_by: sortBy.value,
    },
    preserveState: false,
    preserveScroll: true,
    onFinish: () => {
      isLoading.value = false;
      toast.success('Filters applied!');
    }
  });
};

// Reset filters
const resetFilters = () => {
  searchQuery.value = '';
  selectedCategory.value = '';
  stockStatus.value = 'all';
  sortBy.value = 'name';
  applyFilters();
};

// Export PDF
const exportPDF = () => {
  isLoading.value = true;
  toast.loading('Generating PDF...');
  window.open(route('admin.reports.inventory.export-pdf', {
    search: searchQuery.value,
    category: selectedCategory.value,
    stock_status: stockStatus.value,
    sort_by: sortBy.value,
  }), '_blank');
  setTimeout(() => {
    isLoading.value = false;
    toast.success('PDF generated!');
  }, 1000);
};

// Export CSV
const exportCSV = () => {
  isLoading.value = true;
  toast.loading('Generating CSV...');
  window.location.href = route('admin.reports.inventory.export-csv', {
    search: searchQuery.value,
    category: selectedCategory.value,
    stock_status: stockStatus.value,
    sort_by: sortBy.value,
  });
  setTimeout(() => {
    isLoading.value = false;
    toast.success('CSV downloaded!');
  }, 1000);
};

// Get unique categories
const categories = computed(() => {
  const cats = new Set();
  props.products?.forEach(p => {
    if (p.category?.name) cats.add(p.category.name);
  });
  return Array.from(cats).sort();
});
</script>

<template>
  <Head title="Inventory Analytics" />
  <AdminLayout>
    <div class="min-h-screen bg-gray-50 p-4 md:p-6 lg:p-8">

      <!-- Modern Header - Shopify Style -->
      <div class="bg-white border-b border-gray-200 -mx-4 -mt-4 md:-mx-6 md:-mt-6 lg:-mx-8 lg:-mt-8 px-4 md:px-6 lg:px-8 py-6 mb-8">
        <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4">
          <div>
            <h1 class="text-3xl font-semibold text-gray-900 mb-1">Inventory Analytics</h1>
            <p class="text-sm text-gray-600">Monitor stock levels, track performance, and optimize inventory</p>
          </div>

          <div class="flex gap-2">
            <button
              @click="exportCSV"
              :disabled="isLoading"
              class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-all disabled:opacity-50"
            >
              <FileText class="w-4 h-4" />
              CSV
            </button>
            <button
              @click="exportPDF"
              :disabled="isLoading"
              class="inline-flex items-center gap-2 px-4 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-lg transition-all disabled:opacity-50 shadow-sm"
            >
              <Download class="w-4 h-4" />
              PDF
            </button>
          </div>
        </div>
      </div>

      <!-- Clean Stats Grid - Stripe Style -->
      <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">

        <div class="bg-white border border-gray-200 rounded-xl p-6 hover:shadow-md transition-shadow">
          <div class="flex items-center justify-between mb-4">
            <div class="p-2 bg-blue-50 rounded-lg">
              <Package class="w-5 h-5 text-blue-600" />
            </div>
            <Info class="w-4 h-4 text-gray-400 cursor-help" title="Total unique products" />
          </div>
          <div class="text-3xl font-semibold text-gray-900 mb-1">{{ formatNumber(totalProducts) }}</div>
          <div class="text-sm font-medium text-gray-600">Products</div>
          <div class="mt-3 text-xs text-gray-500">{{ inStockProducts }} in stock, {{ lowStockProducts }} low, {{ outOfStockProducts }} out</div>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl p-6 hover:shadow-md transition-shadow">
          <div class="flex items-center justify-between mb-4">
            <div class="p-2 bg-emerald-50 rounded-lg">
              <Archive class="w-5 h-5 text-emerald-600" />
            </div>
            <Info class="w-4 h-4 text-gray-400 cursor-help" title="Total units in warehouse" />
          </div>
          <div class="text-3xl font-semibold text-gray-900 mb-1">{{ formatNumber(totalStock) }}</div>
          <div class="text-sm font-medium text-gray-600">Units Available</div>
          <div class="mt-3 flex items-center gap-1">
            <div class="text-xs text-gray-500">Turnover: {{ turnoverRate }}%</div>
            <div class="flex-1 h-1.5 bg-gray-100 rounded-full overflow-hidden">
              <div class="h-full bg-emerald-500 rounded-full" :style="{ width: turnoverRate + '%' }"></div>
            </div>
          </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl p-6 hover:shadow-md transition-shadow">
          <div class="flex items-center justify-between mb-4">
            <div class="p-2 bg-purple-50 rounded-lg">
              <TrendingUp class="w-5 h-5 text-purple-600" />
            </div>
            <Info class="w-4 h-4 text-gray-400 cursor-help" title="Total units sold (all-time)" />
          </div>
          <div class="text-3xl font-semibold text-gray-900 mb-1">{{ formatNumber(totalSold) }}</div>
          <div class="text-sm font-medium text-gray-600">Units Sold</div>
          <div class="mt-3 text-xs text-gray-500">Historical sales performance</div>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl p-6 hover:shadow-md transition-shadow">
          <div class="flex items-center justify-between mb-4">
            <div class="p-2 bg-orange-50 rounded-lg">
              <DollarSign class="w-5 h-5 text-orange-600" />
            </div>
            <Info class="w-4 h-4 text-gray-400 cursor-help" title="Current inventory value" />
          </div>
          <div class="text-3xl font-semibold text-gray-900 mb-1">{{ formatCurrency(totalValue) }}</div>
          <div class="text-sm font-medium text-gray-600">Stock Value</div>
          <div class="mt-3 text-xs text-gray-500">{{ formatCurrency(totalValue / (totalProducts || 1)) }} avg per product</div>
        </div>
      </div>

      <!-- Action Insights - Unique Feature -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-gradient-to-br from-green-50 to-emerald-50 border border-green-200 rounded-xl p-5">
          <div class="flex items-start gap-3">
            <div class="p-2 bg-white rounded-lg shadow-sm">
              <CheckCircle class="w-5 h-5 text-green-600" />
            </div>
            <div class="flex-1">
              <div class="text-lg font-semibold text-gray-900 mb-1">{{ inStockProducts }} Products</div>
              <div class="text-sm text-gray-600 mb-2">Healthy stock levels</div>
              <div class="text-xs text-green-700 font-medium">✓ No action required</div>
            </div>
          </div>
        </div>

        <div class="bg-gradient-to-br from-amber-50 to-yellow-50 border border-amber-200 rounded-xl p-5">
          <div class="flex items-start gap-3">
            <div class="p-2 bg-white rounded-lg shadow-sm">
              <AlertTriangle class="w-5 h-5 text-amber-600" />
            </div>
            <div class="flex-1">
              <div class="text-lg font-semibold text-gray-900 mb-1">{{ lowStockProducts }} Products</div>
              <div class="text-sm text-gray-600 mb-2">Running low on stock</div>
              <div class="text-xs text-amber-700 font-medium">⚠ Reorder recommended</div>
            </div>
          </div>
        </div>

        <div class="bg-gradient-to-br from-red-50 to-pink-50 border border-red-200 rounded-xl p-5">
          <div class="flex items-start gap-3">
            <div class="p-2 bg-white rounded-lg shadow-sm">
              <XCircle class="w-5 h-5 text-red-600" />
            </div>
            <div class="flex-1">
              <div class="text-lg font-semibold text-gray-900 mb-1">{{ outOfStockProducts }} Products</div>
              <div class="text-sm text-gray-600 mb-2">Completely out of stock</div>
              <div class="text-xs text-red-700 font-medium">⚠ Urgent restocking needed</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Filters Section - Modern Design -->
      <div class="bg-white border border-gray-200 rounded-xl p-6 mb-6">
        <div class="flex items-center gap-2 mb-5">
          <Filter class="w-5 h-5 text-gray-600" />
          <h3 class="text-base font-semibold text-gray-900">Filters</h3>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
          <!-- Search -->
          <div>
            <label class="block text-xs font-medium text-gray-700 mb-1.5">Search</label>
            <div class="relative">
              <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
              <input
                v-model="searchQuery"
                type="text"
                placeholder="Product name or SKU..."
                class="w-full pl-9 pr-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-transparent"
                @keyup.enter="applyFilters"
              >
            </div>
          </div>

          <!-- Category -->
          <div>
            <label class="block text-xs font-medium text-gray-700 mb-1.5">Category</label>
            <select
              v-model="selectedCategory"
              class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-transparent"
            >
              <option value="">All Categories</option>
              <option v-for="cat in categories" :key="cat" :value="cat">{{ cat }}</option>
            </select>
          </div>

          <!-- Stock Status -->
          <div>
            <label class="block text-xs font-medium text-gray-700 mb-1.5">Stock Status</label>
            <select
              v-model="stockStatus"
              class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-transparent"
            >
              <option value="all">All Status</option>
              <option value="in_stock">In Stock</option>
              <option value="low_stock">Low Stock</option>
              <option value="out_of_stock">Out of Stock</option>
            </select>
          </div>

          <!-- Sort -->
          <div>
            <label class="block text-xs font-medium text-gray-700 mb-1.5">Sort By</label>
            <select
              v-model="sortBy"
              class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-transparent"
            >
              <option value="name">Name (A-Z)</option>
              <option value="stock">Stock Level</option>
              <option value="sold">Units Sold</option>
              <option value="price">Price</option>
              <option value="category">Category</option>
            </select>
          </div>
        </div>

        <div class="flex gap-2">
          <button
            @click="applyFilters"
            :disabled="isLoading"
            class="px-4 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-lg transition-all disabled:opacity-50 inline-flex items-center gap-2"
          >
            <RefreshCw class="w-4 h-4" :class="{ 'animate-spin': isLoading }" />
            Apply
          </button>
          <button
            @click="resetFilters"
            class="px-4 py-2 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 text-sm font-medium rounded-lg transition-all"
          >
            Reset
          </button>
        </div>
      </div>

      <!-- Products Table - Clean Minimal Design -->
      <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
        <div class="border-b border-gray-200 px-6 py-4">
          <h3 class="text-sm font-semibold text-gray-900">Products ({{ products?.length || 0 }})</h3>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full">
            <thead>
              <tr class="bg-gray-50 border-b border-gray-200">
                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Product</th>
                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Category</th>
                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Type</th>
                <th class="px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Stock</th>
                <th class="px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Sold</th>
                <th class="px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Price</th>
                <th class="px-6 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-for="product in products" :key="product.id" class="hover:bg-gray-50 transition-colors">
                <td class="px-6 py-4">
                  <div class="flex items-center gap-3">
                    <div v-if="product.feature_image" class="w-10 h-10 rounded-lg overflow-hidden flex-shrink-0 border border-gray-200">
                      <img :src="product.feature_image" :alt="product.name" class="w-full h-full object-cover" />
                    </div>
                    <div v-else class="w-10 h-10 rounded-lg bg-gray-100 flex items-center justify-center flex-shrink-0 border border-gray-200">
                      <Package class="w-5 h-5 text-gray-400" />
                    </div>
                    <div class="min-w-0">
                      <div class="text-sm font-medium text-gray-900 truncate">{{ product.name }}</div>
                      <div class="text-xs text-gray-500">{{ product.sku || 'No SKU' }}</div>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4 text-sm text-gray-600">{{ product.category?.name || 'Uncategorized' }}</td>
                <td class="px-6 py-4">
                  <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium" :class="product.type === 'simple' ? 'bg-blue-50 text-blue-700' : 'bg-purple-50 text-purple-700'">
                    {{ product.type === 'simple' ? 'Simple' : 'Variable' }}
                  </span>
                </td>
                <td class="px-6 py-4 text-right">
                  <span class="text-sm font-semibold text-gray-900">{{ formatNumber(getProductStock(product)) }}</span>
                </td>
                <td class="px-6 py-4 text-right">
                  <span class="text-sm text-gray-600">{{ formatNumber(getProductSold(product)) }}</span>
                </td>
                <td class="px-6 py-4 text-right">
                  <span class="text-sm font-semibold text-gray-900">{{ formatCurrency(product.price) }}</span>
                </td>
                <td class="px-6 py-4">
                  <div class="flex justify-center">
                    <span
                      class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium border"
                      :class="[
                        getStockStatus(product).bgColor,
                        getStockStatus(product).textColor,
                        getStockStatus(product).borderColor
                      ]"
                    >
                      {{ getStockStatus(product).label }}
                    </span>
                  </div>
                </td>
              </tr>

              <tr v-if="!products || !products.length">
                <td colspan="7" class="px-6 py-16 text-center">
                  <Package class="w-12 h-12 mx-auto mb-3 text-gray-300" />
                  <p class="text-sm font-medium text-gray-900 mb-1">No products found</p>
                  <p class="text-sm text-gray-500">Try adjusting your filters</p>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Inventory Management Tips - User Benefits -->
      <div class="mt-8 bg-gradient-to-br from-slate-900 to-slate-800 rounded-xl p-8 text-white">
        <div class="flex items-center gap-2 mb-6">
          <Zap class="w-5 h-5 text-yellow-400" />
          <h3 class="text-lg font-semibold">Inventory Management Benefits</h3>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
          <div class="flex items-start gap-3">
            <div class="p-2 bg-white/10 rounded-lg backdrop-blur-sm flex-shrink-0">
              <Target class="w-5 h-5 text-blue-400" />
            </div>
            <div>
              <div class="font-semibold mb-1">Stock Optimization</div>
              <div class="text-sm text-gray-300">Maintain optimal stock levels to reduce holding costs</div>
            </div>
          </div>

          <div class="flex items-start gap-3">
            <div class="p-2 bg-white/10 rounded-lg backdrop-blur-sm flex-shrink-0">
              <Award class="w-5 h-5 text-green-400" />
            </div>
            <div>
              <div class="font-semibold mb-1">Performance Tracking</div>
              <div class="text-sm text-gray-300">Monitor product turnover rates and sales velocity</div>
            </div>
          </div>

          <div class="flex items-start gap-3">
            <div class="p-2 bg-white/10 rounded-lg backdrop-blur-sm flex-shrink-0">
              <Clock class="w-5 h-5 text-purple-400" />
            </div>
            <div>
              <div class="font-semibold mb-1">Timely Reordering</div>
              <div class="text-sm text-gray-300">Get alerts for low stock before running out</div>
            </div>
          </div>

          <div class="flex items-start gap-3">
            <div class="p-2 bg-white/10 rounded-lg backdrop-blur-sm flex-shrink-0">
              <Eye class="w-5 h-5 text-orange-400" />
            </div>
            <div>
              <div class="font-semibold mb-1">Real-time Visibility</div>
              <div class="text-sm text-gray-300">Always know your exact inventory position</div>
            </div>
          </div>
        </div>
      </div>

    </div>
  </AdminLayout>
</template>

<style scoped>
/* Minimal custom styles - using mostly Tailwind */
::-webkit-scrollbar {
  height: 6px;
  width: 6px;
}
::-webkit-scrollbar-track {
  background: #f1f5f9;
}
::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 3px;
}
::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}
</style>
