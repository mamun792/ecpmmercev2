<template>
  <Modal :show="show" @update:show="$emit('update:show', $event)" max-width="xl">
    <div class="p-6">
      <div class="flex justify-between items-center mb-6">
        <h3 class="text-lg font-semibold text-gray-900">
          <i class="fas fa-history text-indigo-600 mr-2"></i>
          Stock History
        </h3>
        <button @click="$emit('update:show', false)" class="text-gray-400 hover:text-gray-600">
          <i class="fas fa-times text-xl"></i>
        </button>
      </div>

      <!-- Product Info -->
      <div v-if="product" class="bg-gray-50 p-4 rounded-lg mb-6">
        <div class="flex items-center space-x-3">
          <div class="bg-indigo-100 p-2 rounded-lg">
            <i class="fas fa-box text-indigo-600"></i>
          </div>
          <div>
            <h4 class="font-semibold text-gray-900">{{ product.product.name }}</h4>
            <p class="text-sm text-gray-600">SKU: {{ product.product.sku || product.product.product_code }}</p>
            <p class="text-sm text-gray-600">
              Current Stock: <span class="font-medium">{{ product.available_quantity }}</span>
              | Reserved: <span class="font-medium">{{ product.reserved_quantity }}</span>
            </p>
          </div>
        </div>
      </div>

      <!-- Filters -->
      <div class="bg-white p-4 rounded-lg border mb-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Transaction Type</label>
            <select
              v-model="filters.transaction_type"
              @change="loadTransactions"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
            >
              <option value="">All Types</option>
              <option value="adjustment">Adjustments</option>
              <option value="purchase">Purchases</option>
              <option value="sale">Sales</option>
              <option value="transfer">Transfers</option>
              <option value="return">Returns</option>
              <option value="damage">Damage</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Date From</label>
            <input
              v-model="filters.date_from"
              @change="loadTransactions"
              type="date"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
            >
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Date To</label>
            <input
              v-model="filters.date_to"
              @change="loadTransactions"
              type="date"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
            >
          </div>
        </div>
      </div>

      <!-- Transactions Table -->
      <div class="bg-white border rounded-lg overflow-hidden">
        <div v-if="loading" class="p-8 text-center">
          <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
          <p class="mt-2 text-gray-600">Loading transaction history...</p>
        </div>

        <div v-else-if="transactions.length === 0" class="p-8 text-center text-gray-500">
          <i class="fas fa-file-alt text-4xl mb-4 opacity-50"></i>
          <p>No transactions found for this product</p>
        </div>

        <div v-else class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Change</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Before/After</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reason</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="transaction in transactions" :key="transaction.id" class="hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  {{ formatDate(transaction.created_at) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span :class="getTransactionTypeClass(transaction.transaction_type)">
                    {{ formatTransactionType(transaction.transaction_type) }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                  <span :class="getQuantityChangeClass(transaction.quantity_change)">
                    {{ transaction.quantity_change > 0 ? '+' : '' }}{{ transaction.quantity_change }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  {{ transaction.quantity_before }} → {{ transaction.quantity_after }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                    {{ transaction.location_code || transaction.location }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  <div>
                    <p class="font-medium">{{ transaction.reason }}</p>
                    <p v-if="transaction.notes" class="text-gray-500 text-xs mt-1">{{ transaction.notes }}</p>
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  {{ transaction.user?.name || 'System' }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="pagination.last_page > 1" class="mt-4 flex justify-between items-center">
        <div class="text-sm text-gray-700">
          Showing {{ pagination.from }} to {{ pagination.to }} of {{ pagination.total }} transactions
        </div>
        <div class="flex space-x-1">
          <button
            v-for="page in visiblePages"
            :key="page"
            @click="goToPage(page)"
            :class="page === pagination.current_page
              ? 'bg-indigo-600 text-white'
              : 'bg-white text-gray-700 hover:bg-gray-50'"
            class="px-3 py-1 border border-gray-300 rounded text-sm font-medium"
          >
            {{ page }}
          </button>
        </div>
      </div>

      <!-- Export Options -->
      <div class="mt-6 pt-4 border-t flex justify-between items-center">
        <div class="text-sm text-gray-600">
          <i class="fas fa-info-circle mr-1"></i>
          Total of {{ transactions.length }} transactions shown
        </div>
        <div class="flex space-x-2">
          <button
            @click="exportTransactions('csv')"
            class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors text-sm"
          >
            <i class="fas fa-download mr-2"></i>Export CSV
          </button>
          <button
            @click="$emit('update:show', false)"
            class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors text-sm"
          >
            Close
          </button>
        </div>
      </div>
    </div>
  </Modal>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import Modal from '@/Components/Modal.vue'
import { toast } from '@steveyuowo/vue-hot-toast'
import axios from 'axios'

// Props
const props = defineProps({
  show: {
    type: Boolean,
    default: false
  },
  product: {
    type: Object,
    default: null
  }
})

// Emits
const emit = defineEmits(['update:show'])

// Reactive state
const loading = ref(false)
const transactions = ref([])
const pagination = ref({
  current_page: 1,
  last_page: 1,
  per_page: 50,
  total: 0,
  from: 0,
  to: 0
})

const filters = ref({
  transaction_type: '',
  date_from: '',
  date_to: ''
})

// Computed
const visiblePages = computed(() => {
  const current = pagination.value.current_page
  const last = pagination.value.last_page
  const pages = []

  if (last <= 7) {
    for (let i = 1; i <= last; i++) {
      pages.push(i)
    }
  } else {
    if (current <= 4) {
      for (let i = 1; i <= 5; i++) {
        pages.push(i)
      }
      pages.push('...', last)
    } else if (current >= last - 3) {
      pages.push(1, '...')
      for (let i = last - 4; i <= last; i++) {
        pages.push(i)
      }
    } else {
      pages.push(1, '...')
      for (let i = current - 1; i <= current + 1; i++) {
        pages.push(i)
      }
      pages.push('...', last)
    }
  }

  return pages
})

// Methods
const formatDate = (dateString) => {
  const date = new Date(dateString)
  return date.toLocaleString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const formatTransactionType = (type) => {
  const types = {
    adjustment: 'Adjustment',
    purchase: 'Purchase',
    sale: 'Sale',
    transfer: 'Transfer',
    return: 'Return',
    damage: 'Damage',
    reservation: 'Reservation',
    release: 'Release',
    initial: 'Initial'
  }
  return types[type] || type
}

const getTransactionTypeClass = (type) => {
  const baseClasses = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium'
  const typeClasses = {
    adjustment: 'bg-blue-100 text-blue-800',
    purchase: 'bg-green-100 text-green-800',
    sale: 'bg-orange-100 text-orange-800',
    transfer: 'bg-purple-100 text-purple-800',
    return: 'bg-yellow-100 text-yellow-800',
    damage: 'bg-red-100 text-red-800',
    reservation: 'bg-indigo-100 text-indigo-800',
    release: 'bg-gray-100 text-gray-800',
    initial: 'bg-cyan-100 text-cyan-800'
  }
  return `${baseClasses} ${typeClasses[type] || 'bg-gray-100 text-gray-800'}`
}

const getQuantityChangeClass = (change) => {
  if (change > 0) return 'text-green-600'
  if (change < 0) return 'text-red-600'
  return 'text-gray-600'
}

const loadTransactions = async (page = 1) => {
  if (!props.product) return

  loading.value = true
  try {
    const params = {
      product_id: props.product.product_id || props.product.id,
      page,
      ...filters.value
    }

    const response = await axios.get(route('admin.inventory.transactions'), { params })

    if (response.data.success) {
      transactions.value = response.data.data.data || []
      pagination.value = {
        current_page: response.data.data.current_page || 1,
        last_page: response.data.data.last_page || 1,
        per_page: response.data.data.per_page || 50,
        total: response.data.data.total || 0,
        from: response.data.data.from || 0,
        to: response.data.data.to || 0
      }
    } else {
      transactions.value = []
      toast.error('Failed to load transaction history')
    }
  } catch (error) {
    console.error('Error loading transactions:', error)
    transactions.value = []
    toast.error('Failed to load transaction history')
  } finally {
    loading.value = false
  }
}

const goToPage = (page) => {
  if (page !== '...' && page !== pagination.value.current_page) {
    loadTransactions(page)
  }
}

const exportTransactions = async (format = 'csv') => {
  try {
    const params = {
      product_id: props.product.product_id || props.product.id,
      format,
      ...filters.value
    }

    const response = await axios.get(route('admin.inventory.transactions'), {
      params,
      responseType: 'blob'
    })

    // Create download
    const blob = new Blob([response.data], {
      type: format === 'csv' ? 'text/csv' : 'application/json'
    })
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = `stock-history-${props.product.product.sku || props.product.product.product_code}-${new Date().toISOString().split('T')[0]}.${format}`
    link.click()
    window.URL.revokeObjectURL(url)

    toast.success('Transaction history exported successfully!')
  } catch (error) {
    console.error('Export error:', error)
    toast.error('Failed to export transaction history')
  }
}

// Watchers
watch(() => props.show, (newValue) => {
  if (newValue && props.product) {
    // Reset filters
    filters.value = {
      transaction_type: '',
      date_from: '',
      date_to: ''
    }
    loadTransactions()
  }
})

// Lifecycle
onMounted(() => {
  if (props.show && props.product) {
    loadTransactions()
  }
})
</script>

<style scoped>
/* Loading spinner */
.animate-spin {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

/* Table hover effects */
tbody tr:hover {
  background-color: #f9fafb;
}

/* Pagination hover effects */
.pagination-btn:hover {
  background-color: #f3f4f6;
}

/* Custom scrollbar for table */
.overflow-x-auto::-webkit-scrollbar {
  height: 8px;
}

.overflow-x-auto::-webkit-scrollbar-track {
  background: #f1f5f9;
}

.overflow-x-auto::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 4px;
}

.overflow-x-auto::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}
</style>
