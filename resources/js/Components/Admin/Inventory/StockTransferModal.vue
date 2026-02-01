<template>
  <Modal :show="show" @update:show="$emit('update:show', $event)" max-width="lg">
    <div class="p-6">
      <div class="flex justify-between items-center mb-6">
        <h3 class="text-lg font-semibold text-gray-900">
          <i class="fas fa-exchange-alt text-green-600 mr-2"></i>
          Transfer Stock
        </h3>
        <button @click="$emit('update:show', false)" class="text-gray-400 hover:text-gray-600">
          <i class="fas fa-times text-xl"></i>
        </button>
      </div>

      <form @submit.prevent="transferStock" class="space-y-6">
        <!-- Product Selection -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Product</label>
          <select
            v-model="form.product_id"
            required
            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-green-500 focus:border-green-500"
          >
            <option value="">Select Product</option>
            <option v-for="product in products" :key="product.id" :value="product.id">
              {{ product.name }} ({{ product.product_code || product.sku }})
            </option>
          </select>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <!-- From Location -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">From Location</label>
            <select
              v-model="form.from_location"
              required
              @change="checkSourceStock"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-green-500 focus:border-green-500"
            >
              <option value="">Select Source</option>
              <option v-for="location in locations" :key="location.value" :value="location.value">
                {{ location.label }}
              </option>
            </select>
          </div>

          <!-- To Location -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">To Location</label>
            <select
              v-model="form.to_location"
              required
              class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-green-500 focus:border-green-500"
            >
              <option value="">Select Destination</option>
              <option
                v-for="location in locations"
                :key="location.value"
                :value="location.value"
                :disabled="location.value === form.from_location"
              >
                {{ location.label }}
              </option>
            </select>
          </div>
        </div>

        <!-- Available Stock Info -->
        <div v-if="availableStock !== null" class="bg-blue-50 p-4 rounded-lg border border-blue-200">
          <div class="flex items-center space-x-2">
            <i class="fas fa-info-circle text-blue-600"></i>
            <span class="text-sm text-blue-700">
              Available in <strong>{{ form.from_location }}</strong>: {{ availableStock }} units
            </span>
          </div>
        </div>

        <!-- Quantity -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Quantity to Transfer</label>
          <div class="relative">
            <input
              v-model.number="form.quantity"
              type="number"
              min="1"
              :max="availableStock || undefined"
              step="1"
              required
              class="w-full border border-gray-300 rounded-lg px-3 py-2 pr-16 focus:ring-2 focus:ring-green-500 focus:border-green-500"
              placeholder="Enter quantity"
            >
            <div class="absolute inset-y-0 right-0 flex items-center pr-3">
              <span class="text-gray-500 text-sm">units</span>
            </div>
          </div>

          <!-- Validation Warning -->
          <div v-if="availableStock !== null && form.quantity > availableStock" class="mt-2 p-2 bg-red-50 rounded-lg border border-red-200">
            <p class="text-sm text-red-700">
              <i class="fas fa-exclamation-triangle mr-1"></i>
              Insufficient stock! Available: {{ availableStock }} units
            </p>
          </div>
        </div>

        <!-- Transfer Notes -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Transfer Notes (Optional)</label>
          <textarea
            v-model="form.notes"
            rows="3"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-green-500 focus:border-green-500"
            placeholder="Add notes about this transfer..."
          ></textarea>
        </div>

        <!-- Transfer Summary -->
        <div v-if="isFormValid" class="bg-green-50 p-4 rounded-lg border border-green-200">
          <h4 class="font-medium text-green-800 mb-2">
            <i class="fas fa-arrow-right mr-1"></i>
            Transfer Summary
          </h4>
          <div class="grid grid-cols-2 gap-4 text-sm text-green-700">
            <div>
              <strong>From:</strong> {{ getLocationLabel(form.from_location) }}
            </div>
            <div>
              <strong>To:</strong> {{ getLocationLabel(form.to_location) }}
            </div>
            <div>
              <strong>Product:</strong> {{ getSelectedProductName() }}
            </div>
            <div>
              <strong>Quantity:</strong> {{ form.quantity }} units
            </div>
          </div>
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
            :disabled="loading || !isFormValid || (availableStock !== null && form.quantity > availableStock)"
            class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
          >
            <span v-if="loading" class="flex items-center">
              <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              Transferring...
            </span>
            <span v-else>
              <i class="fas fa-arrow-right mr-2"></i>
              Transfer Stock
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
  locations: {
    type: Array,
    required: true
  }
})

// Emits
const emit = defineEmits(['update:show', 'stock-transferred'])

// Reactive state
const loading = ref(false)
const products = ref([])
const availableStock = ref(null)
const form = ref({
  product_id: null,
  from_location: '',
  to_location: '',
  quantity: 0,
  notes: ''
})

// Computed
const isFormValid = computed(() => {
  return form.value.product_id &&
         form.value.from_location &&
         form.value.to_location &&
         form.value.quantity > 0 &&
         form.value.from_location !== form.value.to_location
})

// Methods
const getLocationLabel = (value) => {
  const location = props.locations.find(loc => loc.value === value)
  return location ? location.label : value
}

const getSelectedProductName = () => {
  const product = products.value.find(p => p.id === form.value.product_id)
  return product ? product.name : ''
}

const resetForm = () => {
  form.value = {
    product_id: null,
    from_location: '',
    to_location: '',
    quantity: 0,
    notes: ''
  }
  availableStock.value = null
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

const checkSourceStock = async () => {
  if (!form.value.product_id || !form.value.from_location) {
    availableStock.value = null
    return
  }

  try {
    // This would be an API call to get current stock for this product/location
    // For now, we'll simulate it
    availableStock.value = 100 // Placeholder
  } catch (error) {
    console.error('Error checking stock:', error)
    availableStock.value = null
  }
}

const transferStock = async () => {
  if (!isFormValid.value) return

  loading.value = true
  try {
    const response = await axios.post(route('admin.inventory.transfer'), form.value)

    if (response.data.success) {
      toast.success('Stock transferred successfully!')
      emit('stock-transferred')
      resetForm()
    } else {
      toast.error(response.data.message || 'Failed to transfer stock')
    }
  } catch (error) {
    console.error('Stock transfer error:', error)
    const message = error.response?.data?.message || 'Failed to transfer stock'
    toast.error(message)
  } finally {
    loading.value = false
  }
}

// Watchers
watch(() => props.show, (newValue) => {
  if (newValue) {
    resetForm()
    if (products.value.length === 0) {
      loadProducts()
    }
  }
})

watch([() => form.value.product_id, () => form.value.from_location], () => {
  checkSourceStock()
})

// Lifecycle
onMounted(() => {
  if (props.show) {
    loadProducts()
  }
})
</script>

<style scoped>
/* Custom styling for transfer preview */
.transfer-summary {
  background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
  border: 1px solid #6ee7b7;
}

/* Form validation styles */
.form-error {
  background: linear-gradient(135deg, #fecaca 0%, #fca5a5 100%);
  border: 1px solid #f87171;
}

/* Loading animation */
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
