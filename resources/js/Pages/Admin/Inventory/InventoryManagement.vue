<script setup>
import { defineProps, ref, computed, watch, onMounted, onUnmounted } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import {
  Search,
  Package,
  ChevronDown,
  ChevronRight,
  Plus,
  ArrowUpRight,
  AlertTriangle,
  TrendingUp,
  Box,
  Download,
  Printer,
  Edit,
  Trash2,
  ArrowUp,
  ArrowDown,
  Minus
} from 'lucide-vue-next';
import { toast } from "@steveyuowo/vue-hot-toast";

const props = defineProps({
  allProductsStock: Array,
  locations: Array,
});

// State
const searchQuery = ref('');
const expandedProducts = ref({});
const showRestockModal = ref(false);
const selectedVariation = ref(null);
const selectedProduct = ref(null);
const selectedLocation = ref('MAIN');
const restockQuantity = ref(0);
const startRestock = ref(false);
const restockNote = ref('');
const sortBy = ref('name');

// Filter States
const stockStatusFilter = ref('all'); // all, in_stock, low_stock, out_of_stock
const locationFilter = ref('all'); // all, MAIN, STORE, etc.
const isSearching = ref(false);

// New state for enhancements
const selectedProducts = ref([]);
const showBulkUpdateModal = ref(false);
const bulkUpdateQuantity = ref(0);
const showShortcutsModal = ref(false);

// Keyboard shortcuts handler
const handleKeyboardShortcut = (e) => {
  // Don't trigger shortcuts when typing in input fields
  if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') {
    return;
  }

  // Ctrl/Cmd + K - Toggle shortcuts modal
  if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
    e.preventDefault();
    showShortcutsModal.value = !showShortcutsModal.value;
    return;
  }

  // Ctrl/Cmd + E - Export CSV
  if ((e.ctrlKey || e.metaKey) && e.key === 'e') {
    e.preventDefault();
    exportToCSV();
    return;
  }

  // Ctrl/Cmd + P - Print
  if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
    e.preventDefault();
    printInventory();
    return;
  }

  // Ctrl/Cmd + F - Focus search
  if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
    e.preventDefault();
    document.querySelector('input[type="text"]')?.focus();
    return;
  }

  // Number keys 1-4 for filter tabs
  if (!e.ctrlKey && !e.metaKey && !e.shiftKey && !e.altKey) {
    if (e.key === '1') stockStatusFilter.value = 'all';
    else if (e.key === '2') stockStatusFilter.value = 'in_stock';
    else if (e.key === '3') stockStatusFilter.value = 'low_stock';
    else if (e.key === '4') stockStatusFilter.value = 'out_of_stock';
  }
};

// Lifecycle hooks for keyboard shortcuts
onMounted(() => {
  window.addEventListener('keydown', handleKeyboardShortcut);
});

onUnmounted(() => {
  window.removeEventListener('keydown', handleKeyboardShortcut);
});

// Export to CSV function
const exportToCSV = () => {
  const headers = ['Product ID', 'Product Name', 'Stock Value', 'Available', 'Sold', 'Status'];
  const rows = filteredProducts.value.map(p => [
    p.product_id,
    p.product_name,
    formatCurrency(p.total_stock * p.product_price),
    p.total_stock,
    p.total_sold,
    p.stock_status
  ]);

  const csvContent = [headers, ...rows].map(row => row.join(',')).join('\n');
  const blob = new Blob([csvContent], { type: 'text/csv' });
  const url = window.URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = `inventory_${new Date().toISOString().split('T')[0]}.csv`;
  a.click();
  toast.success('Inventory exported successfully!');
};

// Print inventory
const printInventory = () => {
  window.print();
  toast.success('Print dialog opened!');
};

// Quick stock adjustment
const quickAdjustStock = (product, variation, amount) => {
  router.post(route('admin.inventory.adjust'), {
    product_id: product.product_id,
    variation_id: variation?.variation_id || null,
    quantity: amount,
    location: 'MAIN',
    type: amount > 0 ? 'add' : 'remove',
    reason: amount > 0 ? 'quick_add' : 'quick_remove',
    note: `Quick adjustment: ${amount > 0 ? '+' : ''}${amount}`
  }, {
    preserveScroll: true,
    onSuccess: () => {
      toast.success(`Stock ${amount > 0 ? 'added' : 'removed'} successfully!`);
    },
    onError: () => {
      toast.error('Failed to adjust stock');
    }
  });
};

// Get product image helper
const getProductImage = (product) => {
  if (!product || !product.product_image) {
    return null;
  }
  if (product.product_image.startsWith('http') || product.product_image.startsWith('/storage/')) {
    return product.product_image;
  }
  return `/storage/${product.product_image}`;
};

// Computed Stats
const stats = computed(() => {
  const totalValue = props.allProductsStock.reduce((acc, product) => {
    if (product.variations && product.variations.length > 0) {
      return acc + product.variations.reduce((vAcc, v) => vAcc + (v.current_stock * v.price), 0);
    }
    return acc + (product.total_stock * product.product_price);
  }, 0);

  const totalStock = props.allProductsStock.reduce((acc, product) => acc + product.total_stock, 0);
  const totalSold = props.allProductsStock.reduce((acc, product) => acc + product.total_sold, 0);

  // Count low stock items
  const lowStockCount = props.allProductsStock.filter(p => p.stock_status === 'low_stock').length;
  const lowStockVariations = props.allProductsStock.reduce((acc, p) => {
    return acc + (p.variations?.filter(v => v.stock_status === 'low_stock').length || 0);
  }, 0);

  return {
    totalValue: totalValue,
    totalStock: totalStock,
    totalSold: totalSold,
    productsCount: props.allProductsStock.length,
    lowStockCount: lowStockCount + lowStockVariations
  };
});

// Helper to get current stock for selected item
const currentStockOfSelected = computed(() => {
  const loc = selectedLocation.value;
  if (selectedVariation.value) {
    return selectedVariation.value.location_stock?.[loc] || 0;
  }
  if (selectedProduct.value) {
    return selectedProduct.value.location_stock?.[loc] || 0;
  }
  return 0;
});

// Computed new stock
const newStockProjection = computed(() => {
  const current = Number(currentStockOfSelected.value) || 0;
  const add = Number(restockQuantity.value) || 0;
  return current + add;
});

// Filter & Sort with enhanced logic
const filteredProducts = computed(() => {
  let products = [...props.allProductsStock];

  // Search filter
  if (searchQuery.value.trim()) {
    const query = searchQuery.value.toLowerCase().trim();
    products = products.filter(product =>
      product.product_name.toLowerCase().includes(query) ||
      product.product_id.toString().includes(query)
    );
  }

  // Stock status filter
  if (stockStatusFilter.value !== 'all') {
    products = products.filter(product => product.stock_status === stockStatusFilter.value);
  }

  // Location filter
  if (locationFilter.value !== 'all') {
    products = products.filter(product => {
      return product.location_stock && product.location_stock[locationFilter.value] > 0;
    });
  }

  // Sort logic
  if (sortBy.value === 'name') {
    products.sort((a, b) => a.product_name.localeCompare(b.product_name));
  } else if (sortBy.value === 'stock_low') {
    products.sort((a, b) => a.total_stock - b.total_stock);
  } else if (sortBy.value === 'stock_high') {
    products.sort((a, b) => b.total_stock - a.total_stock);
  } else if (sortBy.value === 'value_high') {
    const getValue = (p) => p.variations?.length > 0
      ? p.variations.reduce((acc, v) => acc + (v.current_stock * v.price), 0)
      : p.total_stock * p.product_price;
    products.sort((a, b) => getValue(b) - getValue(a));
  }

  return products;
});

// Stock filter counts
const filterCounts = computed(() => {
  return {
    all: props.allProductsStock.length,
    in_stock: props.allProductsStock.filter(p => p.stock_status === 'in_stock').length,
    low_stock: props.allProductsStock.filter(p => p.stock_status === 'low_stock').length,
    out_of_stock: props.allProductsStock.filter(p => p.stock_status === 'out_of_stock').length
  };
});

// Pagination
const currentPage = ref(1);
const itemsPerPage = ref(10);
const totalPages = computed(() => Math.ceil(filteredProducts.value.length / itemsPerPage.value));

const paginatedProducts = computed(() => {
  const startIndex = (currentPage.value - 1) * itemsPerPage.value;
  return filteredProducts.value.slice(startIndex, startIndex + itemsPerPage.value);
});

// Methods
const toggleExpand = (productId) => {
  expandedProducts.value[productId] = !expandedProducts.value[productId];
};

const openRestockModal = (variation, product) => {
  if (variation) {
    selectedVariation.value = { ...variation, productName: product.product_name };
    selectedProduct.value = null;
  } else {
    selectedVariation.value = null;
    selectedProduct.value = product;
  }
  selectedLocation.value = 'MAIN';
  restockQuantity.value = 0;
  restockNote.value = 'Restock via Inventory Dashboard';
  showRestockModal.value = true;
};

const closeRestockModal = () => {
  showRestockModal.value = false;
  selectedVariation.value = null;
  restockQuantity.value = 0;
  startRestock.value = false;
};

const toggleVariationStatus = (variation) => {
  const newStatus = variation.status === 'active' ? 'inactive' : 'active';

  router.post(route('admin.inventory.variation.toggle-status'), {
    variation_id: variation.variation_id,
    status: newStatus
  }, {
    preserveScroll: true,
    onSuccess: () => {
      toast.success(`Variation ${newStatus === 'active' ? 'activated' : 'deactivated'} successfully`);
      variation.status = newStatus; // Update local state
    },
    onError: () => {
      toast.error("Failed to update variation status");
    }
  });
};

const submitRestock = () => {
  if (restockQuantity.value <= 0) {
    toast.error("Quantity must be greater than 0");
    return;
  }

  startRestock.value = true;

  router.post(route('admin.inventory.adjust'), {
    product_id: selectedVariation.value ? selectedVariation.value.product_id : selectedProduct.value.product_id,
    variation_id: selectedVariation.value ? selectedVariation.value.variation_id : null,
    quantity: restockQuantity.value,
    location: selectedLocation.value, // Added location
    type: 'add',
    reason: 'restock',
    note: restockNote.value
  }, {
    preserveScroll: true,
    onSuccess: () => {
      toast.success("Stock updated successfully");
      closeRestockModal();
    },
    onError: () => {
      toast.error("Failed to update stock");
      startRestock.value = false;
    }
  });
};

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-BD', { style: 'currency', currency: 'BDT' }).format(amount);
};

// Watchers
watch(searchQuery, () => {
  currentPage.value = 1;
  isSearching.value = true;
  setTimeout(() => isSearching.value = false, 300);
});

watch(stockStatusFilter, () => currentPage.value = 1);
watch(locationFilter, () => currentPage.value = 1);

</script>

<template>
  <Head title="Inventory Management" />
  <AdminLayout>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900 p-6">

      <!-- Header -->
      <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 flex items-center gap-2">
            <Box class="w-8 h-8 text-blue-600" />
            📦 Inventory Management - Made Simple!
          </h1>
          <p class="text-gray-500 dark:text-gray-400 mt-1">💡 <strong>Quick Guide:</strong> {{ stats.productsCount }} products • Green=Good • Orange=Low • Red=Empty</p>
          <div class="flex items-center gap-4 mt-2 text-xs">
            <span class="flex items-center gap-1 text-green-600">
              <span class="w-2 h-2 bg-green-500 rounded-full"></span>
              ✅ Good Stock
            </span>
            <span class="flex items-center gap-1 text-amber-600">
              <span class="w-2 h-2 bg-amber-500 rounded-full animate-pulse"></span>
              ⚠️ Need Soon
            </span>
            <span class="flex items-center gap-1 text-red-600">
              <span class="w-2 h-2 bg-red-500 rounded-full"></span>
              🚨 Restock Now
            </span>
          </div>
        </div>
        <button
          @click="router.visit(route('admin.products.create'))"
          class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors shadow-sm"
          title="Click to add a new product to your store"
        >
          <Plus class="w-4 h-4 mr-2" />
          ➕ Add New Product
        </button>
      </div>

      <!-- Quick Actions Panel -->
      <div class="bg-gradient-to-r from-purple-50 via-pink-50 to-blue-50 dark:from-gray-800 dark:via-gray-800 dark:to-gray-800 rounded-2xl p-4 mb-6 shadow-lg border border-purple-100 dark:border-gray-700">
        <div class="flex items-center justify-between mb-3">
          <div class="flex items-center gap-2">
            <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
            <h3 class="text-sm font-bold text-gray-900 dark:text-white">⚡ Quick Actions</h3>
          </div>
          <button
            @click="showShortcutsModal = true"
            class="flex items-center gap-1 px-3 py-1.5 bg-gradient-to-r from-blue-500 to-purple-500 text-white text-xs font-semibold rounded-lg hover:from-blue-600 hover:to-purple-600 transition-all shadow-sm"
          >
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
            <span class="hidden sm:inline">Shortcuts</span>
            <kbd class="hidden md:inline px-1 bg-white/20 rounded text-xs">Ctrl+K</kbd>
          </button>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-2 sm:gap-3">
          <button
            @click="exportToCSV"
            class="group flex flex-col items-center gap-2 p-3 bg-white dark:bg-gray-700 rounded-xl shadow-sm hover:shadow-md transition-all transform hover:-translate-y-1 border border-gray-100 dark:border-gray-600"
          >
            <div class="w-10 h-10 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
              <Download class="w-5 h-5 text-white" />
            </div>
            <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">Export CSV</span>
          </button>

          <button
            @click="printInventory"
            class="group flex flex-col items-center gap-2 p-3 bg-white dark:bg-gray-700 rounded-xl shadow-sm hover:shadow-md transition-all transform hover:-translate-y-1 border border-gray-100 dark:border-gray-600"
          >
            <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
              <Printer class="w-5 h-5 text-white" />
            </div>
            <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">Print</span>
          </button>

          <button
            @click="router.visit(route('admin.products.index'))"
            class="group flex flex-col items-center gap-2 p-3 bg-white dark:bg-gray-700 rounded-xl shadow-sm hover:shadow-md transition-all transform hover:-translate-y-1 border border-gray-100 dark:border-gray-600"
          >
            <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-pink-600 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
              <Edit class="w-5 h-5 text-white" />
            </div>
            <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">Edit Products</span>
          </button>

          <button
            @click="router.visit(route('admin.dashboard'))"
            class="group flex flex-col items-center gap-2 p-3 bg-white dark:bg-gray-700 rounded-xl shadow-sm hover:shadow-md transition-all transform hover:-translate-y-1 border border-gray-100 dark:border-gray-600"
          >
            <div class="w-10 h-10 bg-gradient-to-br from-amber-500 to-orange-600 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
              <Package class="w-5 h-5 text-white" />
            </div>
            <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">Dashboard</span>
          </button>
        </div>
      </div>

      <!-- Stats Cards with enhanced gradients -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <!-- Total Value Card -->
        <div class="relative overflow-hidden bg-gradient-to-br from-blue-50 to-indigo-100 dark:from-blue-900/20 dark:to-indigo-900/20 p-6 rounded-2xl border-2 border-blue-200 dark:border-blue-800 shadow-lg hover:shadow-xl transition-shadow">
          <div class="absolute top-0 right-0 w-32 h-32 bg-blue-500/10 rounded-full -mr-16 -mt-16"></div>
          <div class="relative">
            <div class="text-xs font-bold text-blue-600 dark:text-blue-400 uppercase tracking-wider mb-2">💰 Stock Value</div>
            <div class="text-3xl font-extrabold text-gray-900 dark:text-gray-100">{{ formatCurrency(stats.totalValue) }}</div>
            <div class="text-xs text-blue-600 dark:text-blue-400 mt-2">💡 What your inventory is worth</div>
          </div>
        </div>

        <!-- Units in Stock Card -->
        <div class="relative overflow-hidden bg-gradient-to-br from-green-50 to-emerald-100 dark:from-green-900/20 dark:to-emerald-900/20 p-6 rounded-2xl border-2 border-green-200 dark:border-green-800 shadow-lg hover:shadow-xl transition-shadow">
          <div class="absolute top-0 right-0 w-32 h-32 bg-green-500/10 rounded-full -mr-16 -mt-16"></div>
          <div class="relative">
            <div class="text-xs font-bold text-green-600 dark:text-green-400 uppercase tracking-wider mb-2">📦 Available Now</div>
            <div class="text-3xl font-extrabold text-gray-900 dark:text-gray-100 flex items-center gap-2">
              {{ stats.totalStock }}
              <TrendingUp class="w-5 h-5 text-green-500" />
            </div>
            <div class="text-xs text-green-600 dark:text-green-400 mt-2">💡 Ready to sell to customers</div>
          </div>
        </div>

        <!-- Units Sold Card -->
        <div class="relative overflow-hidden bg-gradient-to-br from-purple-50 to-pink-100 dark:from-purple-900/20 dark:to-pink-900/20 p-6 rounded-2xl border-2 border-purple-200 dark:border-purple-800 shadow-lg hover:shadow-xl transition-shadow">
          <div class="absolute top-0 right-0 w-32 h-32 bg-purple-500/10 rounded-full -mr-16 -mt-16"></div>
          <div class="relative">
            <div class="text-xs font-bold text-purple-600 dark:text-purple-400 uppercase tracking-wider mb-2">🛒 Already Sold</div>
            <div class="text-3xl font-extrabold text-gray-900 dark:text-gray-100">{{ stats.totalSold }}</div>
            <div class="text-xs text-purple-600 dark:text-purple-400 mt-2">💡 Units sold to customers</div>
          </div>
        </div>

        <!-- Low Stock Alerts Card -->
        <div class="relative overflow-hidden bg-gradient-to-br from-amber-50 to-orange-100 dark:from-amber-900/20 dark:to-orange-900/20 p-6 rounded-2xl border-2 dark:border-amber-800 shadow-lg hover:shadow-xl transition-shadow" :class="stats.lowStockCount > 0 ? 'border-amber-400' : 'border-amber-200'">
          <div class="absolute top-0 right-0 w-32 h-32 bg-amber-500/10 rounded-full -mr-16 -mt-16"></div>
          <div class="relative">
            <div class="text-xs font-bold text-amber-600 dark:text-amber-400 uppercase tracking-wider mb-2">⚠️ Need Restocking</div>
            <div class="text-3xl font-extrabold flex items-center gap-2" :class="stats.lowStockCount > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-gray-400 dark:text-gray-500'">
              {{ stats.lowStockCount }}
              <AlertTriangle class="w-5 h-5" :class="stats.lowStockCount > 0 ? 'animate-pulse' : ''" />
            </div>
            <p class="text-xs mt-2" :class="stats.lowStockCount > 0 ? 'text-amber-600 dark:text-amber-400 font-semibold' : 'text-gray-500 dark:text-gray-400'">
              {{ stats.lowStockCount > 0 ? '🚨 Click "Low Stock" tab below!' : '✅ All products have enough stock!' }}
            </p>
          </div>
        </div>
      </div>

      <!-- Quick Help Panel -->
      <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-xl border border-blue-200 dark:border-blue-800 p-4 mb-6">
        <div class="flex items-start gap-3">
          <div class="p-2 bg-blue-100 dark:bg-blue-900/50 rounded-lg">
            <span class="text-xl">💡</span>
          </div>
          <div class="flex-1">
            <h3 class="text-sm font-bold text-blue-900 dark:text-blue-100 mb-2">How to Use This Page:</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs text-blue-800 dark:text-blue-200">
              <div class="flex items-center gap-2">
                <span class="text-green-500">✅</span>
                <span><strong>Green products</strong> have enough stock</span>
              </div>
              <div class="flex items-center gap-2">
                <span class="text-amber-500 animate-pulse">⚠️</span>
                <span><strong>Orange products</strong> need restocking soon</span>
              </div>
              <div class="flex items-center gap-2">
                <span class="text-red-500">🚨</span>
                <span><strong>Red products</strong> are completely out - restock now!</span>
              </div>
              <div class="flex items-center gap-2">
                <span class="text-blue-500">➕</span>
                <span>Click <strong>"Add Stock"</strong> button to restock items</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Main Content -->
      <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden">

        <!-- Stock Status Filter Tabs -->
        <div class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 px-6 pt-4">
          <div class="flex gap-2 overflow-x-auto pb-4 scrollbar-hide">
            <button
              @click="stockStatusFilter = 'all'"
              :class="[
                'px-6 py-2.5 rounded-lg font-semibold text-sm transition-all whitespace-nowrap',
                stockStatusFilter === 'all'
                  ? 'bg-blue-600 text-white shadow-lg shadow-blue-500/30'
                  : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-600'
              ]"
            >
              📋 All Products
              <span class="ml-2 px-2 py-0.5 rounded-full text-xs font-bold" :class="stockStatusFilter === 'all' ? 'bg-blue-500' : 'bg-gray-200 dark:bg-gray-700'">
                {{ filterCounts.all }}
              </span>
            </button>

            <button
              @click="stockStatusFilter = 'in_stock'"
              :class="[
                'px-6 py-2.5 rounded-lg font-semibold text-sm transition-all whitespace-nowrap',
                stockStatusFilter === 'in_stock'
                  ? 'bg-green-600 text-white shadow-lg shadow-green-500/30'
                  : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-600'
              ]"
            >
              ✅ Good Stock
              <span class="ml-2 px-2 py-0.5 rounded-full text-xs font-bold" :class="stockStatusFilter === 'in_stock' ? 'bg-green-500' : 'bg-gray-200 dark:bg-gray-700'">
                {{ filterCounts.in_stock }}
              </span>
            </button>

            <button
              @click="stockStatusFilter = 'low_stock'"
              :class="[
                'px-6 py-2.5 rounded-lg font-semibold text-sm transition-all whitespace-nowrap',
                stockStatusFilter === 'low_stock'
                  ? 'bg-amber-600 text-white shadow-lg shadow-amber-500/30 animate-pulse'
                  : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-600'
              ]"
            >
              <AlertTriangle class="w-4 h-4 inline mr-1" />
              ⚠️ Need Soon
              <span class="ml-2 px-2 py-0.5 rounded-full text-xs font-bold" :class="stockStatusFilter === 'low_stock' ? 'bg-amber-500' : 'bg-gray-200 dark:bg-gray-700'">
                {{ filterCounts.low_stock }}
              </span>
            </button>

            <button
              @click="stockStatusFilter = 'out_of_stock'"
              :class="[
                'px-6 py-2.5 rounded-lg font-semibold text-sm transition-all whitespace-nowrap',
                stockStatusFilter === 'out_of_stock'
                  ? 'bg-red-600 text-white shadow-lg shadow-red-500/30'
                  : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-600'
              ]"
            >
              <AlertTriangle class="w-4 h-4 inline mr-1" />
              🚨 Restock Now
              <span class="ml-2 px-2 py-0.5 rounded-full text-xs font-bold" :class="stockStatusFilter === 'out_of_stock' ? 'bg-red-500' : 'bg-gray-200 dark:bg-gray-700'">
                {{ filterCounts.out_of_stock }}
              </span>
            </button>
          </div>
        </div>

        <!-- Advanced Toolbar with Search and Filters -->
        <div class="p-6 border-b border-gray-200 dark:border-gray-700 bg-gradient-to-r from-gray-50 to-white dark:from-gray-900 dark:to-gray-800">
          <div class="flex flex-col lg:flex-row gap-4 items-start lg:items-center justify-between">
            <!-- Search Bar -->
            <div class="relative w-full lg:w-96">
              <Search class="absolute left-4 top-3.5 w-5 h-5 text-gray-400" />
              <input
                v-model="searchQuery"
                type="text"
                class="w-full pl-12 pr-4 py-3 rounded-xl border-2 border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition shadow-sm"
                placeholder="🔍 Type product name or ID (e.g., 'Test' or '7')..."
                title="💡 Tip: You can search by product name or product ID number"
              >
              <div v-if="isSearching" class="absolute right-4 top-3.5">
                <svg class="animate-spin h-5 w-5 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
              </div>
            </div>

            <!-- Filters Row -->
            <div class="flex flex-wrap gap-3 items-center w-full lg:w-auto">
              <!-- Location Filter -->
              <select
                v-model="locationFilter"
                class="px-4 py-2.5 rounded-xl border-2 border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none font-medium text-sm shadow-sm"
              >
                <option value="all">All Locations</option>
                <option v-for="loc in locations" :key="loc.value" :value="loc.value">
                  📍 {{ loc.label }}
                </option>
              </select>

              <!-- Sort Dropdown -->
              <select
                v-model="sortBy"
                class="px-4 py-2.5 rounded-xl border-2 border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none font-medium text-sm shadow-sm"
              >
                <option value="name">Sort by Name</option>
                <option value="stock_low">Stock: Low to High</option>
                <option value="stock_high">Stock: High to Low</option>
                <option value="value_high">Value: High to Low</option>
              </select>
            </div>
          </div>

          <!-- Active Filters Display -->
          <div v-if="stockStatusFilter !== 'all' || locationFilter !== 'all' || searchQuery" class="mt-4 flex flex-wrap gap-2 items-center">
            <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">Active Filters:</span>
            <span v-if="stockStatusFilter !== 'all'" class="inline-flex items-center gap-1 px-3 py-1 rounded-lg text-xs font-semibold bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 border border-blue-200 dark:border-blue-800">
              {{ stockStatusFilter.replace('_', ' ').toUpperCase() }}
              <button @click="stockStatusFilter = 'all'" class="ml-1 hover:text-blue-600">&times;</button>
            </span>
            <span v-if="locationFilter !== 'all'" class="inline-flex items-center gap-1 px-3 py-1 rounded-lg text-xs font-semibold bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-300 border border-purple-200 dark:border-purple-800">
              Location: {{ locations.find(l => l.value === locationFilter)?.label }}
              <button @click="locationFilter = 'all'" class="ml-1 hover:text-purple-600">&times;</button>
            </span>
            <span v-if="searchQuery" class="inline-flex items-center gap-1 px-3 py-1 rounded-lg text-xs font-semibold bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300 border border-green-200 dark:border-green-800">
              Search: "{{ searchQuery }}"
              <button @click="searchQuery = ''" class="ml-1 hover:text-green-600">&times;</button>
            </span>
            <button @click="stockStatusFilter = 'all'; locationFilter = 'all'; searchQuery = ''" class="text-xs font-semibold text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300">
              Clear All
            </button>
          </div>
        </div>

        <!-- Product Table with Professional Styling -->
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gradient-to-r from-gray-100 to-gray-50 dark:from-gray-900 dark:to-gray-800 sticky top-0 z-10">
              <tr class="border-b-2 border-gray-300 dark:border-gray-600">
                <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">📦 Product Name</th>
                <th class="px-6 py-4 text-right text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">💰 Stock Value</th>
                <th class="px-6 py-4 text-center text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">📊 Available</th>
                <th class="px-6 py-4 text-center text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">🛒 Sold</th>
                <th class="px-6 py-4 text-center text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">🚦 Status</th>
                <th class="px-6 py-4 text-right text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">⚡ Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700 bg-white dark:bg-gray-800">
              <template v-for="product in paginatedProducts" :key="product.product_id">

                <!-- Main Product Row with Enhanced Styling -->
                <tr class="hover:bg-gradient-to-r hover:from-blue-50 hover:to-indigo-50 dark:hover:from-gray-700/50 dark:hover:to-gray-600/50 transition-all duration-200 group border-l-4" :class="product.stock_status === 'low_stock' ? 'border-l-amber-400' : product.stock_status === 'out_of_stock' ? 'border-l-red-400' : 'border-l-transparent'">
                  <td class="px-6 py-5 whitespace-nowrap">
                    <div class="flex items-center gap-4">
                      <!-- Product Image -->
                      <div class="h-14 w-14 flex-shrink-0 relative rounded-xl overflow-hidden bg-gray-100 dark:bg-gray-700">
                        <img
                          v-if="getProductImage(product)"
                          :src="getProductImage(product)"
                          :alt="product.product_name"
                          class="h-full w-full object-cover group-hover:scale-110 transition-transform duration-200"
                          @error="(e) => { e.target.style.display = 'none'; e.target.nextElementSibling.style.display = 'flex'; }"
                        />
                        <div class="absolute inset-0 flex items-center justify-center" :class="{ 'hidden': getProductImage(product) }">
                          <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                          </svg>
                        </div>
                        <!-- Stock Badge Overlay -->
                        <span v-if="product.stock_status === 'low_stock'" class="absolute -top-1 -right-1 w-4 h-4 bg-amber-500 border-2 border-white dark:border-gray-800 rounded-full animate-pulse" title="Low Stock"></span>
                        <span v-else-if="product.stock_status === 'out_of_stock'" class="absolute -top-1 -right-1 w-4 h-4 bg-red-500 border-2 border-white dark:border-gray-800 rounded-full" title="Out of Stock"></span>
                        <span v-else class="absolute -top-1 -right-1 w-4 h-4 bg-green-500 border-2 border-white dark:border-gray-800 rounded-full" title="Good Stock"></span>
                      </div>
                      <div class="flex-1">
                        <div class="text-sm font-bold text-gray-900 dark:text-gray-100 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">{{ product.product_name }}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-2 mt-1">
                          <span class="px-2 py-0.5 bg-gray-100 dark:bg-gray-700 rounded-md font-medium">
                            ID: {{ product.product_id }}
                          </span>
                          <span v-if="product.variations.length > 0" class="inline-flex items-center gap-1 px-2 py-0.5 bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 rounded-md font-medium border border-blue-200 dark:border-blue-800">
                            🔄 {{ product.variations.length }} {{ product.variations.length === 1 ? 'variation' : 'variations' }}
                          </span>
                        </div>
                      </div>
                    </div>
                  </td>
                  <td class="px-6 py-5 whitespace-nowrap text-right">
                    <div class="text-base font-bold text-gray-900 dark:text-gray-100">
                      {{ formatCurrency(product.variations.length > 0
                          ? product.variations.reduce((acc, v) => acc + (v.current_stock * v.price), 0)
                          : (product.total_stock * product.product_price)
                      ) }}
                    </div>
                    <div v-if="product.variations.length === 0" class="text-xs text-gray-500 dark:text-gray-400">
                      {{ formatCurrency(product.product_price) }} / unit
                    </div>
                  </td>
                  <td class="px-6 py-4">
                    <div class="space-y-2">
                      <!-- Stock Number with Quick Actions -->
                      <div class="flex items-center justify-center gap-2">
                        <button
                          v-if="product.variations.length === 0 && product.total_stock > 0"
                          @click="quickAdjustStock(product, null, -1)"
                          class="p-1 rounded-md bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 hover:bg-red-200 dark:hover:bg-red-900/50 transition-colors"
                          title="Remove 1 unit"
                        >
                          <Minus class="w-3 h-3" />
                        </button>
                        <span class="text-lg font-bold px-3" :class="{
                          'text-green-600 dark:text-green-400': product.stock_status === 'in_stock',
                          'text-amber-600 dark:text-amber-400': product.stock_status === 'low_stock',
                          'text-red-600 dark:text-red-400': product.stock_status === 'out_of_stock'
                        }">
                          {{ product.total_stock }}
                        </span>
                        <button
                          v-if="product.variations.length === 0"
                          @click="quickAdjustStock(product, null, 1)"
                          class="p-1 rounded-md bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400 hover:bg-green-200 dark:hover:bg-green-900/50 transition-colors"
                          title="Add 1 unit"
                        >
                          <Plus class="w-3 h-3" />
                        </button>
                      </div>

                      <!-- Visual Progress Bar -->
                      <div class="w-32 mx-auto">
                        <div class="h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                          <div
                            class="h-full rounded-full transition-all duration-300"
                            :class="{
                              'bg-gradient-to-r from-green-400 to-green-600': product.stock_status === 'in_stock',
                              'bg-gradient-to-r from-amber-400 to-orange-600': product.stock_status === 'low_stock',
                              'bg-gradient-to-r from-red-400 to-red-600': product.stock_status === 'out_of_stock'
                            }"
                            :style="{ width: Math.min((product.total_stock / Math.max(product.total_stock + product.total_sold, 100)) * 100, 100) + '%' }"
                          ></div>
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1 text-center">
                          {{ Math.round((product.total_stock / Math.max(product.total_stock + product.total_sold, 100)) * 100) }}% available
                        </div>
                      </div>
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-center">
                    <div class="flex flex-col items-center gap-1">
                      <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ product.total_sold }}</span>
                      <span v-if="product.total_sold > product.total_stock" class="inline-flex items-center gap-1 text-xs font-medium text-green-600 dark:text-green-400">
                        <ArrowUp class="w-3 h-3" />
                        Popular
                      </span>
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-center">
                    <div class="flex items-center justify-center gap-2">
                      <!-- Warning Badge for Low Stock -->
                      <span
                        v-if="product.stock_status === 'low_stock'"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold bg-gradient-to-r from-amber-100 to-orange-100 dark:from-amber-900/30 dark:to-orange-900/30 text-amber-900 dark:text-amber-200 border-2 border-amber-400 dark:border-amber-600 shadow-sm animate-pulse"
                        :title="`⚠️ Only ${product.total_stock} left! You should restock when it goes below ${product.minimum_threshold}`"
                      >
                        <AlertTriangle class="w-4 h-4" />
                        <span>⚠️ RESTOCK SOON</span>
                      </span>

                      <!-- Out of Stock Badge -->
                      <span
                        v-else-if="product.stock_status === 'out_of_stock'"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold bg-gradient-to-r from-red-100 to-rose-100 dark:from-red-900/30 dark:to-rose-900/30 text-red-900 dark:text-red-200 border-2 border-red-400 dark:border-red-600 shadow-sm"
                        title="🚨 This product is completely out of stock! Customers cannot buy it."
                      >
                        <AlertTriangle class="w-4 h-4" />
                        <span>🚨 RESTOCK NOW!</span>
                      </span>

                      <!-- In Stock Badge -->
                      <span
                        v-else
                        class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300 border border-green-300 dark:border-green-700"
                        :title="`✅ Good! You have ${product.total_stock} units available for sale`"
                      >
                        <span>✅ GOOD STOCK</span>
                      </span>
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-right flex justify-end gap-2">
                    <button
                      v-if="product.variations.length === 0"
                      @click="openRestockModal(null, product)"
                      class="inline-flex items-center px-3 py-1 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium rounded transition shadow-sm"
                      title="Click to add more stock for this product"
                    >
                      <Plus class="w-3 h-3 mr-1" />
                      ➕ Add Stock
                    </button>
                    <!-- User-friendly Variations Toggle Button -->
                    <button
                      v-if="product.variations.length > 0"
                      @click="toggleExpand(product.product_id)"
                      class="inline-flex items-center px-3 py-2 bg-gradient-to-r from-purple-50 to-indigo-50 dark:from-purple-900/30 dark:to-indigo-900/30 border border-purple-200 dark:border-purple-700 rounded-lg text-xs font-semibold text-purple-700 dark:text-purple-300 hover:from-purple-100 hover:to-indigo-100 dark:hover:from-purple-800/50 dark:hover:to-indigo-800/50 transition-all shadow-sm"
                      :title="expandedProducts[product.product_id] ? 'Click to hide variations' : `Click to see all ${product.variations.length} variations`"
                    >
                      <span v-if="!expandedProducts[product.product_id]" class="flex items-center gap-2">
                        <ChevronRight class="w-4 h-4" />
                        👁️ Show {{ product.variations.length }} Options
                      </span>
                      <span v-else class="flex items-center gap-2">
                        <ChevronDown class="w-4 h-4" />
                        🙈 Hide Options
                      </span>
                    </button>
                  </td>
                </tr>

                <!-- Expanded Variations Row -->
                <tr v-if="expandedProducts[product.product_id]" class="bg-gray-50 dark:bg-gray-900/50">
                  <td colspan="6" class="px-6 py-4">
                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden bg-white dark:bg-gray-800">
                      <div class="px-4 py-3 bg-gradient-to-r from-blue-50 to-purple-50 dark:from-blue-900/20 dark:to-purple-900/20 border-b border-blue-200 dark:border-blue-700">
                        <div class="flex justify-between items-center">
                          <div>
                            <span class="text-sm font-bold text-blue-800 dark:text-blue-200 flex items-center gap-2">
                              🔄 Product Options & Variations
                              <span class="px-2 py-0.5 bg-blue-100 dark:bg-blue-800 text-blue-700 dark:text-blue-200 rounded-full text-xs font-semibold">
                                {{ product.variations.length }} total
                              </span>
                            </span>
                            <p class="text-xs text-blue-600 dark:text-blue-300 mt-1">
                              💡 Each variation can have different colors, sizes, and prices
                            </p>
                          </div>
                          <button
                            @click="toggleExpand(product.product_id)"
                            class="text-blue-500 hover:text-blue-600 dark:text-blue-400 dark:hover:text-blue-300 transition-colors"
                            title="Click to collapse this section"
                          >
                            <ChevronDown class="w-5 h-5" />
                          </button>
                        </div>
                      </div>
                      <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                          <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase" title="Variation ID number">🔢 Variant #</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase" title="Color, size, and other options">🎨 Options</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase" title="Selling price for this variation">💰 Price</th>
                            <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase" title="Stock available right now">📊 In Stock</th>
                            <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase" title="Units already sold">🛒 Sold</th>
                            <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase" title="Enable or disable this variation">🔘 On/Off</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase" title="Add more stock">⚡ Action</th>
                          </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                          <tr v-for="(variation, idx) in product.variations" :key="variation.variation_id">
                            <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                              #{{ variation.variation_id }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                              <div class="flex flex-wrap gap-2">
                                <span
                                  v-for="(val, key) in variation.attributes"
                                  :key="key"
                                  class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300 border border-blue-100 dark:border-blue-800"
                                >
                                  {{ key }}: <span class="ml-1 text-gray-900 dark:text-white">{{ val }}</span>
                                </span>
                              </div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-right text-sm font-semibold text-gray-900 dark:text-gray-100">
                              {{ formatCurrency(variation.price) }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-center">
                              <div class="flex items-center justify-center gap-2">
                                <!-- Stock Number -->
                                <span class="text-sm font-bold" :class="{
                                  'text-green-600 dark:text-green-400': variation.stock_status === 'in_stock',
                                  'text-amber-600 dark:text-amber-400': variation.stock_status === 'low_stock',
                                  'text-red-600 dark:text-red-400': variation.stock_status === 'out_of_stock'
                                }">
                                  {{ variation.current_stock }}
                                </span>

                                <!-- Warning Badge -->
                                <span
                                  v-if="variation.stock_status === 'low_stock'"
                                  class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-xs font-semibold bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-300 border border-amber-200 dark:border-amber-800 animate-pulse"
                                  :title="`Low Stock Warning: Only ${variation.current_stock} left (Threshold: ${variation.minimum_threshold})`"
                                >
                                  <AlertTriangle class="w-3 h-3" />
                                  <span>Low</span>
                                </span>

                                <!-- Out of Stock Badge -->
                                <span
                                  v-else-if="variation.stock_status === 'out_of_stock'"
                                  class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-xs font-semibold bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300 border border-red-200 dark:border-red-800"
                                >
                                  <AlertTriangle class="w-3 h-3" />
                                  <span>Out</span>
                                </span>

                                <!-- In Stock Badge -->
                                <span
                                  v-else
                                  class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300 border border-green-200 dark:border-green-800"
                                >
                                  <span>OK</span>
                                </span>
                              </div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-center text-sm text-gray-500">
                              {{ variation.sold_stock }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-center">
                              <!-- Modern Toggle Switch -->
                              <div class="flex items-center justify-center">
                                <button
                                  @click="toggleVariationStatus(variation)"
                                  class="relative inline-flex items-center h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                                  :class="{
                                    'bg-green-500': variation.status === 'active',
                                    'bg-gray-300 dark:bg-gray-600': variation.status === 'inactive'
                                  }"
                                  :title="variation.status === 'active' ? '✅ Click to disable this variation' : '❌ Click to enable this variation'"
                                >
                                  <!-- Toggle Circle -->
                                  <span
                                    class="pointer-events-none inline-block h-4 w-4 rounded-full bg-white shadow-lg ring-0 transition duration-200 ease-in-out"
                                    :class="{
                                      'translate-x-5': variation.status === 'active',
                                      'translate-x-0': variation.status === 'inactive'
                                    }"
                                  ></span>
                                </button>
                                <!-- Status Label -->
                                <span class="ml-2 text-xs font-medium" :class="{
                                  'text-green-600 dark:text-green-400': variation.status === 'active',
                                  'text-gray-500 dark:text-gray-400': variation.status === 'inactive'
                                }">
                                  {{ variation.status === 'active' ? '✅ On' : '❌ Off' }}
                                </span>
                              </div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-right">
                              <button
                                @click="openRestockModal(variation, product)"
                                class="inline-flex items-center px-3 py-1 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium rounded transition shadow-sm"
                                title="Click to add more stock for this variation"
                              >
                                <Plus class="w-3 h-3 mr-1" />
                                ➕ Add Stock
                              </button>
                            </td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                  </td>
                </tr>
              </template>
            </tbody>
          </table>
        </div>

        <!-- Empty State -->
        <div v-if="filteredProducts.length === 0" class="p-12 text-center">
          <Package class="w-12 h-12 text-gray-300 mx-auto mb-4" />
          <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">No products found</h3>
          <p class="text-gray-500 dark:text-gray-400 mt-2">Try adjusting your search or filters</p>
        </div>

        <!-- Pagination -->
        <div v-if="totalPages > 1" class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex justify-between items-center">
          <div class="text-sm text-gray-500">
            Page {{ currentPage }} of {{ totalPages }}
          </div>
          <div class="flex gap-2">
            <button
              @click="currentPage--" :disabled="currentPage === 1"
              class="px-3 py-1 border border-gray-300 dark:border-gray-600 rounded-md text-sm font-medium disabled:opacity-50 hover:bg-gray-50 dark:hover:bg-gray-700 transition"
            >
              Previous
            </button>
            <button
              @click="currentPage++" :disabled="currentPage === totalPages"
              class="px-3 py-1 border border-gray-300 dark:border-gray-600 rounded-md text-sm font-medium disabled:opacity-50 hover:bg-gray-50 dark:hover:bg-gray-700 transition"
            >
              Next
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Restock Modal -->
    <div v-if="showRestockModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm transition-opacity">
      <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-md w-full overflow-hidden transform transition-all border border-gray-100 dark:border-gray-700">
        <!-- Header -->
        <div class="px-6 py-5 bg-gray-50 dark:bg-gray-900 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center">
          <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 flex items-center gap-2">
            <span class="p-1.5 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
              <Package class="w-5 h-5 text-blue-600 dark:text-blue-400" />
            </span>
            Restock Inventory
          </h3>
          <button @click="closeRestockModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors p-1 rounded-full hover:bg-gray-100 dark:hover:bg-gray-800">
            <span class="text-2xl leading-none">&times;</span>
          </button>
        </div>

        <div class="p-6 space-y-6">
          <!-- Product Info -->
          <div class="flex items-start gap-4">
            <div class="h-16 w-16 flex-shrink-0 bg-gray-100 dark:bg-gray-700 rounded-lg overflow-hidden border border-gray-200 dark:border-gray-600">
               <img
                 v-if="selectedProduct?.product_image || (selectedVariation && selectedProduct?.product_image)"
                 :src="selectedProduct?.product_image"
                 class="w-full h-full object-cover"
                 alt=""
               >
               <div v-else class="w-full h-full flex items-center justify-center text-gray-400">
                 <Package class="w-8 h-8" />
               </div>
            </div>
            <div>
              <div class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-1">
                {{ selectedVariation ? selectedVariation.productName : selectedProduct?.product_name }}
              </div>
              <div class="text-sm text-gray-500 dark:text-gray-400 flex flex-col gap-0.5">
                <span v-if="selectedVariation">
                  <span class="font-medium text-gray-700 dark:text-gray-300">Variation #{{ selectedVariation.variation_id }}</span>
                  <span class="text-xs mt-1 inline-flex flex-wrap gap-1">
                    <span v-for="(val, key) in selectedVariation.attributes" :key="key" class="px-1.5 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-gray-600 dark:text-gray-300 border border-gray-200 dark:border-gray-600">
                      {{ key }}: {{ val }}
                    </span>
                  </span>
                </span>
                <span v-else>
                  Product ID: {{ selectedProduct?.product_id }}
                </span>
              </div>
            </div>
          </div>

          <!-- Stock Calculation -->
          <div class="grid grid-cols-2 gap-4">
            <div class="bg-gray-50 dark:bg-gray-700/30 p-4 rounded-xl border border-gray-100 dark:border-gray-700">
              <div class="text-xs text-gray-500 dark:text-gray-400 uppercase font-bold mb-1 tracking-wider">Current Stock</div>
              <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ currentStockOfSelected }}</div>
            </div>
            <div class="bg-blue-50 dark:bg-blue-900/10 p-4 rounded-xl border border-blue-100 dark:border-blue-900/30 relative overflow-hidden">
              <div class="absolute right-0 top-0 p-2 opacity-10">
                <TrendingUp class="w-12 h-12 text-blue-600" />
              </div>
              <div class="text-xs text-blue-600 dark:text-blue-400 uppercase font-bold mb-1 tracking-wider">New Total</div>
              <div class="text-2xl font-bold text-blue-700 dark:text-blue-300">
                {{ newStockProjection }}
              </div>
            </div>
          </div>

          <!-- Quantity Input -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Target Location</label>
              <select
                v-model="selectedLocation"
                class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm transition-shadow shadow-sm"
              >
                <option v-for="loc in locations" :key="loc.value" :value="loc.value">
                  {{ loc.label }}
                </option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Quantity to Add</label>
              <div class="relative">
                <button
                  @click="restockQuantity > 0 ? restockQuantity-- : null"
                  class="absolute left-2 top-2 p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors"
                  type="button"
                >
                  <span class="text-lg font-bold">-</span>
                </button>
                <input
                  v-model.number="restockQuantity"
                  type="number"
                  min="1"
                  class="w-full px-12 py-3 text-center rounded-xl border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-xl font-bold transition-shadow shadow-sm"
                  placeholder="0"
                >
                <button
                  @click="restockQuantity++"
                  class="absolute right-2 top-2 p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors"
                  type="button"
                >
                  <span class="text-lg font-bold">+</span>
                </button>
              </div>
            </div>
          </div>

          <!-- Note -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Note (Optional)</label>
            <textarea
              v-model="restockNote"
              rows="3"
              class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm transition-shadow resize-none"
              placeholder="Reason for restock..."
            ></textarea>
          </div>
        </div>

        <!-- Footer -->
        <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900 border-t border-gray-100 dark:border-gray-700 flex justify-end gap-3">
          <button
            @click="closeRestockModal"
            class="px-5 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-800 rounded-xl transition-colors"
          >
            Cancel
          </button>
          <button
            @click="submitRestock"
            :disabled="startRestock || restockQuantity <= 0"
            class="px-5 py-2.5 text-sm font-bold text-white bg-blue-600 hover:bg-blue-700 active:bg-blue-800 rounded-xl shadow-lg shadow-blue-500/30 transition-all transform active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none flex items-center gap-2"
          >
            <span v-if="startRestock" class="flex items-center gap-2">
              <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              Updating...
            </span>
            <span v-else>Confirm Restock</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Keyboard Shortcuts Modal -->
    <div
      v-if="showShortcutsModal"
      @click="showShortcutsModal = false"
      class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4 animate-fadeIn"
    >
      <div
        @click.stop
        class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto animate-slideUp"
      >
        <!-- Modal Header -->
        <div class="sticky top-0 bg-gradient-to-r from-blue-600 to-purple-600 px-6 py-4 rounded-t-2xl">
          <div class="flex items-center justify-between">
            <h3 class="text-xl font-bold text-white flex items-center gap-2">
              ⚡ Keyboard Shortcuts
            </h3>
            <button
              @click="showShortcutsModal = false"
              class="p-2 hover:bg-white/20 rounded-lg transition-colors"
            >
              <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
              </svg>
            </button>
          </div>
        </div>

        <!-- Shortcuts List -->
        <div class="p-6 space-y-6">
          <!-- General Actions -->
          <div>
            <h4 class="text-sm font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">General Actions</h4>
            <div class="space-y-2">
              <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                <span class="text-sm text-gray-700 dark:text-gray-300">Export to CSV</span>
                <div class="flex items-center gap-1">
                  <kbd class="px-2 py-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded text-xs font-semibold">Ctrl</kbd>
                  <span class="text-gray-400">+</span>
                  <kbd class="px-2 py-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded text-xs font-semibold">E</kbd>
                </div>
              </div>
              <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                <span class="text-sm text-gray-700 dark:text-gray-300">Print Inventory</span>
                <div class="flex items-center gap-1">
                  <kbd class="px-2 py-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded text-xs font-semibold">Ctrl</kbd>
                  <span class="text-gray-400">+</span>
                  <kbd class="px-2 py-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded text-xs font-semibold">P</kbd>
                </div>
              </div>
              <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                <span class="text-sm text-gray-700 dark:text-gray-300">Focus Search</span>
                <div class="flex items-center gap-1">
                  <kbd class="px-2 py-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded text-xs font-semibold">Ctrl</kbd>
                  <span class="text-gray-400">+</span>
                  <kbd class="px-2 py-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded text-xs font-semibold">F</kbd>
                </div>
              </div>
              <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                <span class="text-sm text-gray-700 dark:text-gray-300">Toggle This Menu</span>
                <div class="flex items-center gap-1">
                  <kbd class="px-2 py-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded text-xs font-semibold">Ctrl</kbd>
                  <span class="text-gray-400">+</span>
                  <kbd class="px-2 py-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded text-xs font-semibold">K</kbd>
                </div>
              </div>
            </div>
          </div>

          <!-- Filter Shortcuts -->
          <div>
            <h4 class="text-sm font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">Quick Filters</h4>
            <div class="space-y-2">
              <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                <span class="text-sm text-gray-700 dark:text-gray-300">Show All Products</span>
                <kbd class="px-2 py-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded text-xs font-semibold">1</kbd>
              </div>
              <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                <span class="text-sm text-gray-700 dark:text-gray-300">Good Stock Only</span>
                <kbd class="px-2 py-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded text-xs font-semibold">2</kbd>
              </div>
              <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                <span class="text-sm text-gray-700 dark:text-gray-300">Low Stock Only</span>
                <kbd class="px-2 py-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded text-xs font-semibold">3</kbd>
              </div>
              <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                <span class="text-sm text-gray-700 dark:text-gray-300">Out of Stock Only</span>
                <kbd class="px-2 py-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded text-xs font-semibold">4</kbd>
              </div>
            </div>
          </div>

          <!-- Pro Tip -->
          <div class="bg-gradient-to-r from-blue-50 to-purple-50 dark:from-blue-900/20 dark:to-purple-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
            <div class="flex items-start gap-3">
              <span class="text-2xl">💡</span>
              <div>
                <h5 class="font-semibold text-blue-900 dark:text-blue-100 mb-1">Pro Tip</h5>
                <p class="text-sm text-blue-800 dark:text-blue-200">
                  On Mac, use <kbd class="px-1 py-0.5 bg-white dark:bg-gray-800 border border-blue-300 dark:border-blue-700 rounded text-xs">⌘ Cmd</kbd> instead of <kbd class="px-1 py-0.5 bg-white dark:bg-gray-800 border border-blue-300 dark:border-blue-700 rounded text-xs">Ctrl</kbd>
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

  </AdminLayout>
</template>

<style scoped>
@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.animate-fadeIn {
  animation: fadeIn 0.2s ease-out;
}

.animate-slideUp {
  animation: slideUp 0.3s ease-out;
}

/* Print styles */
@media print {
  .no-print {
    display: none !important;
  }

  .print-full-width {
    width: 100% !important;
    max-width: none !important;
  }
}
</style>
