<template>
  <Modal :show="show" @update:show="$emit('update:show', $event)" max-width="lg">
    <div class="p-6">
      <div class="flex justify-between items-center mb-6">
        <h3 class="text-lg font-semibold text-gray-900">
          <i class="fas fa-edit text-blue-600 mr-2"></i>
          Adjust Stock
        </h3>
        <button @click="$emit('update:show', false)" class="text-gray-400 hover:text-gray-600">
          <i class="fas fa-times text-xl"></i>
        </button>
      </div>

      <form @submit.prevent="adjustStock" class="space-y-6">
        <!-- Product Selection -->
        <div v-if="!product">
          <label class="block text-sm font-medium text-gray-700 mb-2">Product</label>
          <select
            v-model="form.product_id"
            required
            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          >
            <option value="">Select Product</option>
            <option v-for="prod in products" :key="prod.id" :value="prod.id">
              {{ prod.name }} ({{ prod.sku }})
            </option>
          </select>
        </div>

        <!-- Product Info (if pre-selected) -->
        <div v-else class="bg-gray-50 p-4 rounded-lg">
          <div class="flex items-center space-x-3">
            <div class="bg-blue-100 p-2 rounded-lg">
              <i class="fas fa-box text-blue-600"></i>
            </div>
            <div>
              <h4 class="font-semibold text-gray-900">{{ product.product.name }}</h4>
              <p class="text-sm text-gray-600">SKU: {{ product.product.sku }}</p>
              <p class="text-sm text-gray-600">Current Stock: <span class="font-medium">{{ product.available_quantity }}</span></p>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <!-- Location -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Location</label>
            <select
              v-model="form.location"
              :disabled="product"
              required
              class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 disabled:bg-gray-100"
            >
              <option value="">Select Location</option>
              <option v-for="location in locations" :key="location.value" :value="location.value">
                {{ location.label }}
              </option>
            </select>
          </div>

          <!-- Adjustment Type -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Adjustment Type</label>
            <select
              v-model="form.adjustment_type"
              required
              class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            >
              <option value="">Select Type</option>
              <option value="increase">Increase Stock</option>
              <option value="decrease">Decrease Stock</option>
              <option value="set">Set Exact Amount</option>
            </select>
          </div>
        </div>

        <!-- Quantity -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            {{ form.adjustment_type === 'set' ? 'Set Quantity To' : 'Quantity to ' + (form.adjustment_type === 'increase' ? 'Add' : 'Remove') }}
          </label>
          <div class="relative">
            <input
              v-model.number="form.quantity"
              type="number"
              min="0"
              step="1"
              required
              class="w-full border border-gray-300 rounded-lg px-3 py-2 pr-16 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              placeholder="Enter quantity"
            >
            <div class="absolute inset-y-0 right-0 flex items-center pr-3">
              <span class="text-gray-500 text-sm">units</span>
            </div>
          </div>

          <!-- Preview Calculation -->
          <div v-if="product && form.quantity > 0 && form.adjustment_type" class="mt-2 p-2 bg-blue-50 rounded-lg">
            <p class="text-sm text-blue-700">
              <i class="fas fa-calculator mr-1"></i>
              Preview: {{ calculateNewQuantity() }} units after adjustment
            </p>
          </div>
        </div>

        <!-- Reason -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Reason *</label>
          <select
            v-model="form.reason"
            required
            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          >
            <option value="">Select Reason</option>
            <option value="Stock received">Stock received</option>
            <option value="Stock sold">Stock sold</option>
            <option value="Stock damaged">Stock damaged</option>
            <option value="Stock expired">Stock expired</option>
            <option value="Stock lost">Stock lost</option>
            <option value="Stock returned">Stock returned</option>
            <option value="Inventory audit">Inventory audit</option>
            <option value="Manual correction">Manual correction</option>
            <option value="Other">Other</option>
          </select>
        </div>

        <!-- Notes -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Notes (Optional)</label>
          <textarea
            v-model="form.notes"
            rows="3"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            placeholder="Additional notes about this adjustment..."
          ></textarea>
        </div>

        <!-- Actions -->
        <div class="flex justify-end space-x-3 pt-4 border-t">
          <button
            type="button"
            @click="$emit('update:show', false)"
            class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors"
          >
            Cancel
          </button>
          <button
            type="submit"
            :disabled="loading || !isFormValid"
            class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
          >
            <span v-if="loading" class="flex items-center">
              <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              Adjusting...
            </span>
            <span v-else>
              <i class="fas fa-save mr-2"></i>
              Adjust Stock
            </span>
          </button>
        </div>
      </form>
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
  },
  locations: {
    type: Array,
    required: true
  }
})

// Emits
const emit = defineEmits(['update:show', 'stock-adjusted'])

// Reactive state
const loading = ref(false)
const products = ref([])
const form = ref({
  product_id: null,
  location: '',
  adjustment_type: '',
  quantity: 0,
  reason: '',
  notes: ''
})

// Computed
const isFormValid = computed(() => {
  return form.value.product_id &&
         form.value.location &&
         form.value.adjustment_type &&
         form.value.quantity > 0 &&
         form.value.reason
})

// Methods
const calculateNewQuantity = () => {
  if (!props.product || !form.value.quantity || !form.value.adjustment_type) {
    return props.product?.available_quantity || 0
  }

  const current = props.product.available_quantity
  const adjustment = form.value.quantity

  switch (form.value.adjustment_type) {
    case 'increase':
      return current + adjustment
    case 'decrease':
      return Math.max(0, current - adjustment)
    case 'set':
      return adjustment
    default:
      return current
  }
}

const resetForm = () => {
  form.value = {
    product_id: props.product?.product.id || null,
    location: props.product?.location || '',
    adjustment_type: '',
    quantity: 0,
    reason: '',
    notes: ''
  }
}

const loadProducts = async () => {
  try {
    const response = await axios.get(route('admin.products.index', { format: 'json' }))
    products.value = response.data.data || []
  } catch (error) {
    console.error('Error loading products:', error)
    toast.error('Failed to load products')
  }
}

const adjustStock = async () => {
  if (!isFormValid.value) return

  loading.value = true
  try {
    const response = await axios.post(route('admin.inventory.adjust'), form.value)

    if (response.data.success) {
      toast.success('Stock adjusted successfully!')
      emit('stock-adjusted')
      resetForm()
    } else {
      toast.error(response.data.message || 'Failed to adjust stock')
    }
  } catch (error) {
    console.error('Stock adjustment error:', error)
    const message = error.response?.data?.message || 'Failed to adjust stock'
    toast.error(message)
  } finally {
    loading.value = false
  }
}

// Watchers
watch(() => props.show, (newValue) => {
  if (newValue) {
    resetForm()
    if (!props.product && products.value.length === 0) {
      loadProducts()
    }
  }
})

watch(() => props.product, (newProduct) => {
  if (newProduct) {
    form.value.product_id = newProduct.product.id
    form.value.location = newProduct.location
  }
})

// Lifecycle
onMounted(() => {
  if (props.show && !props.product) {
    loadProducts()
  }
})
</script>

<style scoped>
/* Custom styling for the adjustment preview */
.preview-calculation {
  background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
  border: 1px solid #93c5fd;
}

/* Form input focus styles */
input:focus, select:focus, textarea:focus {
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* Loading button animation */
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
</style>
