<template>
  <Head title="Inventory Management | Big Tech Style Dashboard" />

  <AdminLayout>
    <!-- Header Section -->
    <div class="bg-gradient-to-r from-blue-600 to-indigo-700 text-white p-6 rounded-lg mb-6">
      <div class="flex justify-between items-center">
        <div>
          <h1 class="text-3xl font-bold mb-2">Inventory Management</h1>
          <p class="text-blue-100">Real-time stock control and analytics</p>
        </div>
        <div class="flex space-x-3">
          <button
            @click="showAdjustModal = true"
            class="bg-white text-blue-600 px-4 py-2 rounded-lg font-semibold hover:bg-blue-50 transition-colors"
          >
            <i class="fas fa-edit mr-2"></i>Adjust Stock
          </button>
          <button
            @click="showTransferModal = true"
            class="bg-green-500 text-white px-4 py-2 rounded-lg font-semibold hover:bg-green-600 transition-colors"
          >
            <i class="fas fa-exchange-alt mr-2"></i>Transfer Stock
          </button>
        </div>
      </div>
    </div>

    <!-- Analytics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
      <div class="bg-white p-6 rounded-lg shadow-sm border">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-gray-600 text-sm">Total Products</p>
            <p class="text-2xl font-bold text-gray-900">{{ analytics.total_products }}</p>
          </div>
          <div class="bg-blue-100 p-3 rounded-full">
            <i class="fas fa-box text-blue-600 text-xl"></i>
          </div>
        </div>
      </div>

      <div class="bg-white p-6 rounded-lg shadow-sm border">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-gray-600 text-sm">Stock Value</p>
            <p class="text-2xl font-bold text-gray-900">${{ formatNumber(analytics.total_stock_value) }}</p>
          </div>
          <div class="bg-green-100 p-3 rounded-full">
            <i class="fas fa-dollar-sign text-green-600 text-xl"></i>
          </div>
        </div>
      </div>

      <div class="bg-white p-6 rounded-lg shadow-sm border">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-gray-600 text-sm">Low Stock Items</p>
            <p class="text-2xl font-bold text-orange-600">{{ analytics.low_stock_count }}</p>
          </div>
          <div class="bg-orange-100 p-3 rounded-full">
            <i class="fas fa-exclamation-triangle text-orange-600 text-xl"></i>
          </div>
        </div>
      </div>

      <div class="bg-white p-6 rounded-lg shadow-sm border">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-gray-600 text-sm">Out of Stock</p>
            <p class="text-2xl font-bold text-red-600">{{ analytics.out_of_stock_count }}</p>
          </div>
          <div class="bg-red-100 p-3 rounded-full">
            <i class="fas fa-times-circle text-red-600 text-xl"></i>
          </div>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white p-4 rounded-lg shadow-sm border mb-6">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
          <input
            v-model="filters.search"
            @input="debouncedSearch"
            type="text"
            placeholder="Search products..."
            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          >
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Location</label>
          <select
            v-model="filters.location"
            @change="fetchInventory"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          >
            <option value="">All Locations</option>
            <option v-for="location in locations" :key="location.value" :value="location.value">
              {{ location.label }}
            </option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Stock Status</label>
          <select
            v-model="filters.low_stock"
            @change="fetchInventory"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          >
            <option value="">All Stock</option>
            <option value="true">Low Stock Only</option>
          </select>
        </div>

        <div class="flex items-end">
          <button
            @click="exportInventory"
            class="w-full bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors"
          >
            <i class="fas fa-download mr-2"></i>Export
          </button>
        </div>
      </div>
    </div>

    <!-- Inventory Table -->
    <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">SKU</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Available</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reserved</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reorder Level</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="item in inventory.data" :key="item.id" class="hover:bg-gray-50">
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">{{ item.product.name }}</div>
                <div class="text-sm text-gray-500">{{ item.product.brand?.name }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ item.product.sku }}</td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                  {{ item.location }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ item.available_quantity }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ item.reserved_quantity }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ item.reorder_level }}</td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span :class="getStockStatusClass(item)">
                  {{ getStockStatus(item) }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                <button
                  @click="adjustStockFor(item)"
                  class="text-blue-600 hover:text-blue-900 transition-colors"
                  title="Adjust Stock"
                >
                  <i class="fas fa-edit"></i>
                </button>
                <button
                  @click="viewHistory(item)"
                  class="text-green-600 hover:text-green-900 transition-colors"
                  title="View History"
                >
                  <i class="fas fa-history"></i>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
        <Pagination :pagination="inventory" />
      </div>
    </div>

    <!-- Stock Adjustment Modal -->
    <StockAdjustmentModal
      v-model:show="showAdjustModal"
      :product="selectedProduct"
      :locations="locations"
      @stock-adjusted="handleStockAdjusted"
    />

    <!-- Stock Transfer Modal -->
    <StockTransferModal
      v-model:show="showTransferModal"
      :locations="locations"
      @stock-transferred="handleStockTransferred"
    />

    <!-- History Modal -->
    <StockHistoryModal
      v-model:show="showHistoryModal"
      :product="selectedProduct"
    />
  </AdminLayout>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { Head } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import Pagination from '@/Components/Pagination.vue'
import StockAdjustmentModal from '@/Components/Admin/Inventory/StockAdjustmentModal.vue'
import StockTransferModal from '@/Components/Admin/Inventory/StockTransferModal.vue'
import StockHistoryModal from '@/Components/Admin/Inventory/StockHistoryModal.vue'
import { debounce } from 'lodash'
import { toast } from '@steveyuowo/vue-hot-toast'

// Props from backend
const props = defineProps({
  inventory: {
    type: Object,
    required: true
  },
  analytics: {
    type: Object,
    required: true
  },
  filters: {
    type: Object,
    default: () => ({})
  },
  locations: {
    type: Array,
    required: true
  }
})

// Reactive state
const loading = ref(false)
const showAdjustModal = ref(false)
const showTransferModal = ref(false)
const showHistoryModal = ref(false)
const selectedProduct = ref(null)
const filters = ref({
  search: props.filters.search || '',
  location: props.filters.location || '',
  low_stock: props.filters.low_stock || ''
})

// Methods
const formatNumber = (number) => {
  return new Intl.NumberFormat().format(number)
}

const getStockStatus = (item) => {
  if (item.available_quantity === 0) return 'Out of Stock'
  if (item.available_quantity <= item.reorder_level) return 'Low Stock'
  return 'In Stock'
}

const getStockStatusClass = (item) => {
  const status = getStockStatus(item)
  const baseClasses = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium'

  if (status === 'Out of Stock') {
    return `${baseClasses} bg-red-100 text-red-800`
  } else if (status === 'Low Stock') {
    return `${baseClasses} bg-yellow-100 text-yellow-800`
  } else {
    return `${baseClasses} bg-green-100 text-green-800`
  }
}

const fetchInventory = async () => {
  loading.value = true
  try {
    window.location.href = route('admin.inventory.index', filters.value)
  } catch (error) {
    console.error('Error fetching inventory:', error)
    toast.error('Failed to fetch inventory data')
  } finally {
    loading.value = false
  }
}

const debouncedSearch = debounce(() => {
  fetchInventory()
}, 500)

const adjustStockFor = (item) => {
  selectedProduct.value = item
  showAdjustModal.value = true
}

const viewHistory = (item) => {
  selectedProduct.value = item
  showHistoryModal.value = true
}

const handleStockAdjusted = () => {
  showAdjustModal.value = false
  selectedProduct.value = null
  fetchInventory()
  toast.success('Stock adjusted successfully!')
}

const handleStockTransferred = () => {
  showTransferModal.value = false
  fetchInventory()
  toast.success('Stock transferred successfully!')
}

const exportInventory = async () => {
  try {
    const response = await axios.post(route('admin.inventory.reports'), {
      report_type: 'stock_valuation',
      location: filters.value.location,
      format: 'csv'
    })

    // Handle CSV download
    const blob = new Blob([response.data], { type: 'text/csv' })
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = `inventory-export-${new Date().toISOString().split('T')[0]}.csv`
    link.click()
    window.URL.revokeObjectURL(url)

    toast.success('Inventory exported successfully!')
  } catch (error) {
    console.error('Export error:', error)
    toast.error('Failed to export inventory')
  }
}

onMounted(() => {
  // Auto-refresh every 30 seconds for real-time updates
  setInterval(() => {
    if (!showAdjustModal.value && !showTransferModal.value && !showHistoryModal.value) {
      fetchInventory()
    }
  }, 30000)
})
</script>

<style scoped>
/* Custom styles for animations and transitions */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

/* Loading spinner */
.loading-spinner {
  border: 2px solid #f3f3f3;
  border-top: 2px solid #3b82f6;
  border-radius: 50%;
  width: 20px;
  height: 20px;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

/* Table row hover effect */
tbody tr:hover {
  background-color: #f8fafc;
}

/* Status badge animations */
.status-badge {
  transition: all 0.2s ease;
}

.status-badge:hover {
  transform: translateY(-1px);
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}
</style>
