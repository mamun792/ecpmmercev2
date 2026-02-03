<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed, watch, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import { toast } from "@steveyuowo/vue-hot-toast"
import StatusDropdown from '@/Components/Order/StatusDropdown.vue';

// Debounce function to delay execution
const debounce = (func, wait) => {
  let timeout;
  return (...args) => {
    clearTimeout(timeout);
    timeout = setTimeout(() => func(...args), wait);
  };
};

const props = defineProps({
  order: Object,
  products: Object,
  paymentMethods: Array,
  orderStatuses: Array,
  paymentStatuses: Array,
});

const perPage = ref(props.products.per_page || 10);
const search = ref('');
const currentPage = ref(props.products.current_page || 1);
const loading = ref(false);

// State for selected attributes per product
const selectedAttributes = ref({});

// State for selected variations per product
const selectedVariations = ref({});

// State for error messages per product
const errorMessages = ref({});

// State for quantities per product
const quantities = ref({});

// State for pending quantity changes (item.id => { originalQuantity, newQuantity })
const pendingQuantityChanges = ref({});

// State for display order items
const displayOrderItems = ref([...props.order.items.map(item => ({
  ...item,
  isNew: false,
  stockLimit: item.product.type === 'variable' ? item.product_variation?.stock : item.product.stock,
  isVariationDeleted: item.product_variation_id && item.product_variation?.is_deleted === true,
  isProductDeleted: item.product?.is_deleted === true,
}))]);

// Check if there are pending changes
const hasPendingChanges = computed(() => {
  return Object.keys(pendingQuantityChanges.value).length > 0;
});

// Form for order updates
const form = useForm({
  customer_name: props.order.customer_name,
  customer_phone: props.order.customer_phone,
  customer_email: props.order.customer_email,
  shipping_address: props.order.shipping_address,
  admin_notes: props.order.admin_notes || '',
  payment_status: props.order.payment_status,
  payment_method: props.order.payment_method,
  discount: props.order.pos_discount || 0,
  discount_type: props.order.discount_type || 'fixed',
  shipping_cost: props.order.shipping_cost || 0,
  area: props.order.area || 'inside_dhaka',
  status: props.order.status || 'pending',
});

// Initialize quantities and search value on mount
onMounted(() => {
  const url = new URL(window.location.href);
  const searchParam = url.searchParams.get('search');
  if (searchParam) {
    search.value = searchParam;
  }
  // Initialize quantities for all products
  if (props.products && props.products.data) {
    props.products.data.forEach(product => {
      quantities.value[product.id] = 1;
    });
  }
});

// Go back to previous page
const goBack = () => {
  window.history.back();
};

// Compute filtered products based on search
const filteredProducts = computed(() => {
  if (!props.products || !props.products.data) return [];
  if (!search.value) return props.products.data;
  const searchTerm = search.value.toLowerCase();
  return props.products.data.filter(product =>
    product.name.toLowerCase().includes(searchTerm) ||
    product.id.toString().includes(searchTerm) ||
    product.price.toString().includes(searchTerm) ||
    product.status.toLowerCase().includes(searchTerm)
  );
});

// Compute order subtotal (sum of all item prices)
const orderSubtotal = computed(() => {
  return parseFloat(props.order.subtotal) || 0;
});

// Compute discount amount based on discount type
// POS discount is calculated on: subtotal + shipping
const discountAmount = computed(() => {
  const discountValue = parseFloat(form.discount) || 0;

  if (!discountValue || discountValue <= 0) return 0;

  // Base for discount calculation: subtotal + shipping (same as POS)
  const base = orderSubtotal.value + (parseFloat(form.shipping_cost) || 0);

  if (form.discount_type === 'percentage') {
    return (base * Math.min(discountValue, 100)) / 100;
  }

  return Math.min(discountValue, base);
});

// Compute order total with discount
// Formula: subtotal + shipping - discount (same as database and POS)
const orderTotalWithDiscount = computed(() => {
  const subtotal = orderSubtotal.value;
  const shipping = parseFloat(form.shipping_cost) || 0;
  const discount = discountAmount.value;
  return Math.max(0, subtotal + shipping - discount);
});

// Truncate product name to a specified word limit
const truncateName = (name, wordLimit = 2) => {
  const words = name.split(' ');
  if (words.length <= wordLimit) return name;
  return words.slice(0, wordLimit).join(' ') + '...';
};

// Get unique attributes for a product
const getUniqueAttributes = (product) => {
  const attributes = {};
  if (product.variations && product.variations.length > 0) {
    product.variations.forEach(variation => {
      // Use variationAttributes instead of attributes
      if (variation.variationAttributes && variation.variationAttributes.length > 0) {
        variation.variationAttributes.forEach(attr => {
          // Check if attr.value exists and has the nested structure
          if (attr.value && attr.value.attribute && attr.value.attribute.name) {
            const attrName = attr.value.attribute.name;
            const attrValue = attr.value.value;
            if (!attributes[attrName]) {
              attributes[attrName] = new Set();
            }
            attributes[attrName].add(attrValue);
          }
        });
      } else if (variation.attributes && variation.attributes.length > 0) {
        // Fallback for old structure
        variation.attributes.forEach(attr => {
          if (attr.value && attr.value.attribute && attr.value.attribute.name) {
            const attrName = attr.value.attribute.name;
            const attrValue = attr.value.value;
            if (!attributes[attrName]) {
              attributes[attrName] = new Set();
            }
            attributes[attrName].add(attrValue);
          }
        });
      }
    });
  }
  return attributes;
};

// Compute available variations for a product based on selected attributes
const getAvailableVariations = (product) => {
  if (!product.variations || product.variations.length === 0) return [];

  const selectedAttrs = selectedAttributes.value[product.id] || {};
  const hasAllAttributesSelected = Object.keys(getUniqueAttributes(product)).every(attrName => selectedAttrs[attrName]);

  if (product.id === 4 && hasAllAttributesSelected) {
    const selectedSize = selectedAttrs['Size'];
    const selectedColor = selectedAttrs['Color'];
    if (
      (selectedSize === 'XL' && selectedColor === 'Black') ||
      (selectedSize === 'L' && selectedColor === 'Black')
    ) {
      errorMessages.value[product.id] = `Variation not available for ${selectedSize} ${selectedColor}`;
      return [];
    }
  }

  const matchingVariations = product.variations.filter(variation => {
    // Check variationAttributes first, then fallback to attributes
    const attrs = variation.variationAttributes || variation.attributes || [];
    return attrs.every(attr => {
      if (attr.value && attr.value.attribute && attr.value.attribute.name) {
        const attrName = attr.value.attribute.name;
        const attrValue = attr.value.value;
        return selectedAttrs[attrName] === attrValue;
      }
      return false;
    });
  });

  if (hasAllAttributesSelected && matchingVariations.length === 0) {
    errorMessages.value[product.id] = 'Selected variation combination not available';
  } else {
    errorMessages.value[product.id] = null;
  }

  return matchingVariations;
};

// Get attributes for an order item
const getItemAttributes = (item) => {
  if (!item.product_variation) return [];

  // Check for variationAttributes first (new structure), then fallback to attributes
  const attrs = item.product_variation.variationAttributes || item.product_variation.attributes || [];

  return attrs.map(attr => {
    if (attr.value && attr.value.attribute && attr.value.attribute.name) {
      return {
        name: attr.value.attribute.name,
        value: attr.value.value,
      };
    }
    return null;
  }).filter(attr => attr !== null);
};

// Handle attribute selection
const selectAttribute = (product, attrName, attrValue) => {
  if (!selectedAttributes.value[product.id]) {
    selectedAttributes.value[product.id] = {};
  }
  selectedAttributes.value[product.id][attrName] = attrValue;

  const matchingVariations = getAvailableVariations(product);
  selectedVariations.value[product.id] = matchingVariations.length === 1 ? matchingVariations[0] : null;
};

// Increment quantity for a product
const incrementQuantity = (product) => {
  const stockLimit = product.type === 'variable' ? (selectedVariations.value[product.id]?.stock || 0) : product.stock;
  if (quantities.value[product.id] < stockLimit) {
    quantities.value[product.id] += 1;
  } else {
    toast.error(`Maximum stock reached (${stockLimit})`);
  }
};

// Decrement quantity for a product
const decrementQuantity = (product) => {
  if (quantities.value[product.id] > 1) {
    quantities.value[product.id] -= 1;
  }
};

// Handle per page change
const updatePerPage = () => {
  router.get(`/admin/orders/${props.order.id}/edit`, {
    per_page: perPage.value,
    search: search.value
  }, {
    preserveState: true,
    preserveScroll: true
  });
};

// Debounced search function
const debouncedSearch = debounce(() => {
  router.get(`/admin/orders/${props.order.id}/edit`, {
    per_page: perPage.value,
    search: search.value,
    page: currentPage.value
  }, {
    preserveState: true,
    preserveScroll: true
  });
}, 500);

// Watch for search input changes
watch(search, (newVal, oldVal) => {
  if (newVal !== oldVal) {
    debouncedSearch();
  }
});

// Clear search input
const clearSearch = () => {
  search.value = '';
  router.get(`/admin/orders/${props.order.id}/edit`, {
    per_page: perPage.value,
    search: '',
    page: 1
  }, {
    preserveState: true,
    preserveScroll: true
  });
};

// Handle pagination
const goToPage = (page) => {
  currentPage.value = page;
  router.get(`/admin/orders/${props.order.id}/edit`, {
    page: page,
    per_page: perPage.value,
    search: search.value
  }, {
    preserveState: true,
    preserveScroll: true
  });
};

// Handle Order New button click
const orderNew = (product) => {
  const variationId = selectedVariations.value[product.id]?.id || null;
  if (product.type === 'variable' && !variationId) {
    toast.error('Please select a valid variation');
    return;
  }

  // Get the selected quantity to add
  const quantity = quantities.value[product.id] || 1;

  // Check for existing item
  const existingItem = displayOrderItems.value.find(item =>
    item.product_id === product.id &&
    item.product_variation_id === variationId
  );

  // Get current available stock (this is what's LEFT in inventory)
  const availableStock = product.type === 'variable' ? selectedVariations.value[product.id].stock : product.stock;

  // Simple validation: Can we add the requested quantity based on available stock?
  // We only check if the AMOUNT TO ADD exceeds available stock
  if (quantity > availableStock) {
    toast.error(`Cannot add ${quantity} items. Only ${availableStock} available in stock.`);
    return;
  }

  loading.value = true;
  // Send only the quantity to ADD, not the new total (backend will handle the addition)
  router.post(`/orders/${props.order.id}/update`, {
    items: [{
      product_id: product.id,
      product_variation_id: variationId,
      quantity: quantity
    }]
  }, {
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => {
      // Reload order to sync with server state
      router.reload({
        only: ['order'],
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
          // Update displayOrderItems with the latest order items
          displayOrderItems.value = props.order.items.map(item => ({
            ...item,
            isNew: false,
            stockLimit: item.product.type === 'variable' ? item.product_variation?.stock : item.product.stock,
            isVariationDeleted: item.product_variation_id && item.product_variation?.is_deleted === true,
            isProductDeleted: item.product?.is_deleted === true,
          }));
          toast.success('Item added to order successfully');
          // Reset selection and quantity for this product
          selectedAttributes.value[product.id] = {};
          selectedVariations.value[product.id] = null;
          quantities.value[product.id] = 1;
        }
      });
    },
    onError: (errors) => {
      console.error('Error adding item to order:', errors);
      const errorMessage = errors.message || 'Failed to add item to order';
      errorMessages.value[product.id] = errorMessage;
      toast.error(errorMessage);
    },
    onFinish: () => {
      loading.value = false;
    }
  });
};

// Handle item removal
const removeItem = (itemId) => {
  loading.value = true;
  router.delete(`/orders/${props.order.id}/items/${itemId}`, {
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => {
      // Reload order to sync with server state
      router.reload({
        only: ['order'],
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
          // Update displayOrderItems with the latest order items
          displayOrderItems.value = props.order.items.map(item => ({
            ...item,
            isNew: false,
            stockLimit: item.product.type === 'variable' ? item.product_variation?.stock : item.product.stock,
            isVariationDeleted: item.product_variation_id && item.product_variation?.is_deleted === true,
            isProductDeleted: item.product?.is_deleted === true,
          }));
          toast.success('Item removed from order successfully');
        },
        onError: (errors) => {
          console.error('Error reloading order:', errors);
          toast.error('Failed to refresh order');
        }
      });
    },
    onError: (errors) => {
      console.error('Error removing item:', errors);
      toast.error(errors.message || 'Failed to remove item');
    },
    onFinish: () => {
      loading.value = false;
    }
  });
};

// Increment quantity (for order items - now tracks pending changes)
const incrementOrderItemQuantity = (item) => {
  const pending = pendingQuantityChanges.value[item.id];

  // Get the TRUE original quantity (before any pending changes)
  // If we already have pending changes, use the stored originalQuantity
  // Otherwise, the current item.quantity IS the original
  const trueOriginalQuantity = pending ? pending.originalQuantity : item.quantity;
  const currentQuantity = pending ? pending.newQuantity : item.quantity;

  // Get available stock (what's LEFT in inventory)
  const availableStock = item.stockLimit;

  // Calculate how many items we've ALREADY added (pending additions)
  const pendingAdditions = currentQuantity - trueOriginalQuantity;

  // Check if adding 1 more would exceed available stock
  // pendingAdditions + 1 > availableStock means we can't add more
  if (pendingAdditions + 1 > availableStock) {
    toast.error(`Cannot add more. Only ${availableStock} available in stock. Already added ${pendingAdditions}.`);
    return;
  }

  const newQuantity = currentQuantity + 1;

  // Track the pending change - always use the TRUE original
  if (newQuantity === trueOriginalQuantity) {
    // If back to original, remove from pending
    delete pendingQuantityChanges.value[item.id];
  } else {
    pendingQuantityChanges.value[item.id] = {
      originalQuantity: trueOriginalQuantity,
      newQuantity,
      itemId: item.id
    };
  }

  // Update display immediately
  const displayItem = displayOrderItems.value.find(i => i.id === item.id);
  if (displayItem) {
    displayItem.quantity = newQuantity;
    displayItem.final_price = (newQuantity * parseFloat(displayItem.unit_price)).toFixed(2);
  }
};

// Decrement quantity (for order items - now tracks pending changes)
const decrementOrderItemQuantity = (item) => {
  const pending = pendingQuantityChanges.value[item.id];

  // Get the TRUE original quantity (before any pending changes)
  const trueOriginalQuantity = pending ? pending.originalQuantity : item.quantity;
  const currentQuantity = pending ? pending.newQuantity : item.quantity;

  if (currentQuantity <= 1) {
    toast.error('Quantity cannot be less than 1. Use remove button to delete item.');
    return;
  }

  const newQuantity = currentQuantity - 1;

  // Track the pending change - always use the TRUE original
  if (newQuantity === trueOriginalQuantity) {
    // If back to original, remove from pending
    delete pendingQuantityChanges.value[item.id];
  } else {
    pendingQuantityChanges.value[item.id] = {
      originalQuantity: trueOriginalQuantity,
      newQuantity,
      itemId: item.id
    };
  }

  // Update display immediately
  const displayItem = displayOrderItems.value.find(i => i.id === item.id);
  if (displayItem) {
    displayItem.quantity = newQuantity;
    displayItem.final_price = (newQuantity * parseFloat(displayItem.unit_price)).toFixed(2);
  }
};

// Save all pending quantity changes
const saveQuantityChanges = () => {
  if (!hasPendingChanges.value) return;

  loading.value = true;
  const changes = Object.values(pendingQuantityChanges.value);
  let completed = 0;
  let errors = [];

  // Process each change sequentially
  const processNext = (index) => {
    if (index >= changes.length) {
      // All done
      loading.value = false;
      if (errors.length === 0) {
        toast.success('All quantity changes saved successfully');
        pendingQuantityChanges.value = {};
        // Reload to get fresh data
        router.reload({
          only: ['order'],
          preserveState: true,
          preserveScroll: true,
          onSuccess: () => {
            displayOrderItems.value = props.order.items.map(orderItem => ({
              ...orderItem,
              isNew: false,
              stockLimit: orderItem.product.type === 'variable' ? orderItem.product_variation?.stock : orderItem.product.stock,
              isVariationDeleted: orderItem.product_variation_id && orderItem.product_variation?.is_deleted === true,
              isProductDeleted: orderItem.product?.is_deleted === true,
            }));
          }
        });
      } else {
        toast.error(`${errors.length} change(s) failed. Please check and try again.`);
      }
      return;
    }

    const change = changes[index];
    router.put(`/orders/${props.order.id}/items/${change.itemId}`, {
      quantity: change.newQuantity
    }, {
      preserveState: true,
      preserveScroll: true,
      onSuccess: () => {
        completed++;
        delete pendingQuantityChanges.value[change.itemId];
        processNext(index + 1);
      },
      onError: (err) => {
        errors.push({ itemId: change.itemId, error: err });
        processNext(index + 1);
      }
    });
  };

  processNext(0);
};

// Cancel pending changes and revert display
const cancelQuantityChanges = () => {
  // Revert display items to original quantities
  displayOrderItems.value = props.order.items.map(item => ({
    ...item,
    isNew: false,
    stockLimit: item.product.type === 'variable' ? item.product_variation?.stock : item.product.stock,
    isVariationDeleted: item.product_variation_id && item.product_variation?.is_deleted === true,
    isProductDeleted: item.product?.is_deleted === true,
  }));
  pendingQuantityChanges.value = {};
  toast.info('Changes cancelled');
};

// Submit form for customer info updates
const submitForm = () => {
  form.post(`/orders/${props.order.id}/basic-info`, {
    preserveScroll: true,
    onSuccess: (page) => {
      // Check for flash message from backend
      if (page.props.flash?.success) {
        toast.success(page.props.flash.success);
      } else {
        toast.success('Order updated successfully');
      }
      // Go back to previous page
      window.history.back();
    },
    onError: (errors) => {
      console.log('Error updating order:', errors);
      toast.error('Failed to update order');
    },
  });
};
</script>

<template>
  <Head title="Edit Order" />
  <AdminLayout>
    <div class="w-full bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50 min-h-screen p-4 md:p-8">
      <!-- Header Section with Enhanced Design -->
      <div class="mb-8">
        <div class="bg-white rounded-2xl shadow-lg border border-gray-200 p-6 md:p-8">
          <div class="flex flex-col md:flex-row justify-between items-start gap-6">
            <div class="flex-1">
              <div class="flex items-center gap-4 mb-4">
                <div class="bg-gradient-to-br from-blue-500 via-blue-600 to-indigo-600 p-4 rounded-xl shadow-lg">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                  </svg>
                </div>
                <div>
                  <p class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-1">✏️ Editing Order</p>
                  <h1 class="text-3xl md:text-4xl font-bold bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">Order #{{ order.id }}</h1>
                </div>
              </div>
              <div class="flex flex-wrap gap-4">
                <div class="bg-gradient-to-br from-gray-50 to-gray-100 px-4 py-3 rounded-xl border border-gray-200">
                  <p class="text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">📊 Status</p>
                  <p class="text-sm font-semibold text-gray-700 mt-1">
                    <span :class="['inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-bold shadow-sm',
                      order.status === 'completed' ? 'bg-gradient-to-r from-green-500 to-emerald-600 text-white' :
                      order.status === 'pending' ? 'bg-gradient-to-r from-yellow-400 to-orange-500 text-white' :
                      order.status === 'cancelled' ? 'bg-gradient-to-r from-red-500 to-rose-600 text-white' :
                      'bg-gradient-to-r from-blue-500 to-indigo-600 text-white'
                    ]">
                      {{ order.status.toUpperCase() }}
                    </span>
                  </p>
                </div>
                <div class="bg-gradient-to-br from-blue-50 to-indigo-50 px-4 py-3 rounded-xl border border-blue-200">
                  <p class="text-xs font-bold text-blue-600 uppercase tracking-wide mb-1">🕐 Last Updated</p>
                  <p class="text-sm font-semibold text-gray-700 mt-1">{{ new Date(order.updated_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) }}</p>
                </div>
              </div>
            </div>
            <button @click="goBack" class="inline-flex items-center gap-2 px-5 py-3 bg-gradient-to-r from-gray-600 to-gray-700 hover:from-gray-700 hover:to-gray-800 text-white rounded-xl transition-all duration-200 hover:shadow-lg font-semibold text-sm group transform hover:-translate-y-0.5">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 group-hover:-translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
              </svg>
              Back to Orders
            </button>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 lg:gap-8">
        <!-- Left Column: Order Items & Customer Info -->
        <div class="lg:col-span-1 space-y-6 lg:space-y-8">
          <!-- Order Items Card (Top) with Enhanced Design -->
          <div class="bg-white rounded-2xl shadow-lg hover:shadow-xl transition-all border border-gray-200 overflow-hidden">
            <div class="bg-gradient-to-r from-purple-500 via-purple-600 to-indigo-600 px-6 py-5 border-b border-purple-300">
              <div class="flex justify-between items-start">
                <div>
                  <h2 class="text-xl font-bold text-white flex items-center gap-3 mb-2">
                    <div class="bg-white/20 p-2 rounded-lg backdrop-blur-sm">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M7 4a3 3 0 000 6h10a3 3 0 100-6H7zM7 10a3 3 0 000 6h10a3 3 0 100-6H7z" />
                      </svg>
                    </div>
                    🛒 Order Items
                  </h2>
                  <p class="text-sm text-purple-100 font-medium">{{ displayOrderItems.length }} item{{ displayOrderItems.length !== 1 ? 's' : '' }} in this order</p>
                </div>
                <!-- Save/Cancel buttons for pending quantity changes -->
                <div v-if="hasPendingChanges" class="flex items-center gap-2 bg-white/10 backdrop-blur-sm px-4 py-2 rounded-lg">
                  <span class="text-xs text-yellow-200 font-bold flex items-center gap-1">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                      <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                    Unsaved
                  </span>
                  <button
                    @click="cancelQuantityChanges"
                    :disabled="loading"
                    class="px-3 py-1.5 text-xs font-bold text-white bg-white/20 hover:bg-white/30 rounded-lg disabled:opacity-50 transition-all"
                  >
                    ✕ Cancel
                  </button>
                  <button
                    @click="saveQuantityChanges"
                    :disabled="loading"
                    class="px-3 py-1.5 text-xs font-bold text-purple-600 bg-white hover:bg-gray-100 rounded-lg disabled:opacity-50 transition-all flex items-center gap-1 shadow-md"
                  >
                    <svg v-if="loading" class="animate-spin h-3 w-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    ✓ Save
                  </button>
                </div>
              </div>
            </div>

            <div v-if="displayOrderItems.length > 0" class="overflow-x-auto max-h-96">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gradient-to-r from-gray-50 to-gray-100 sticky top-0">
                  <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Product</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Qty</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Total</th>
                    <th scope="col" class="px-6 py-3 text-center text-xs font-bold text-gray-700 uppercase tracking-wider">Act</th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <tr v-for="item in displayOrderItems" :key="item.id" class="hover:bg-primary/6 transition-colors text-xs">
                    <td class="px-4 py-3 whitespace-nowrap">
                      <div class="flex items-center gap-2">
                        <div class="flex-shrink-0 h-8 w-8 bg-gray-100 rounded flex items-center justify-center overflow-hidden">
                          <img :src="item.product.feature_image" alt="product image" class="h-full w-full object-cover" />
                        </div>
                        <div>
                          <div class="font-semibold text-gray-900">{{ truncateName(item.product.name, 1) }}</div>
                          <div v-if="item.product_variation && ((item.product_variation.variationAttributes && item.product_variation.variationAttributes.length > 0) || (item.product_variation.attributes && item.product_variation.attributes.length > 0))" class="flex flex-wrap gap-1 mt-1">
                            <span v-for="attr in (item.product_variation.variationAttributes || item.product_variation.attributes)" :key="attr.id" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                              <template v-if="attr.value && attr.value.attribute && attr.value.attribute.name">
                                {{ attr.value.attribute.name }}: {{ attr.value.value }}
                              </template>
                            </span>
                          </div>
                        </div>
                      </div>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap">
                      <div class="flex items-center gap-1">
                        <button @click="decrementOrderItemQuantity(item)" :disabled="loading || item.quantity <= 1 || item.isVariationDeleted || item.isProductDeleted" :title="item.isVariationDeleted ? 'Variation has been deleted' : item.isProductDeleted ? 'Product has been deleted' : ''" class="w-6 h-6 flex items-center justify-center bg-gray-200 hover:bg-gray-300 disabled:opacity-50 disabled:cursor-not-allowed rounded text-xs font-bold transition-colors">
                          -
                        </button>
                        <span
                          class="font-bold min-w-[2rem] text-center"
                          :class="[
                            item.isVariationDeleted || item.isProductDeleted ? 'text-red-600' :
                            pendingQuantityChanges[item.id] ? 'text-amber-600 bg-amber-100 px-1 rounded' :
                            'text-gray-900'
                          ]"
                        >
                          {{ item.quantity }}
                          <span v-if="pendingQuantityChanges[item.id]" class="text-xs">*</span>
                        </span>
                        <button
                          @click="incrementOrderItemQuantity(item)"
                          :disabled="loading || item.isVariationDeleted || item.isProductDeleted || ((pendingQuantityChanges[item.id]?.newQuantity || item.quantity) - (pendingQuantityChanges[item.id]?.originalQuantity || item.quantity) >= item.stockLimit)"
                          :title="item.isVariationDeleted ? 'Variation has been deleted' : item.isProductDeleted ? 'Product has been deleted' : (item.stockLimit <= 0 ? 'No stock available' : '')"
                          class="w-6 h-6 flex items-center justify-center bg-gray-200 hover:bg-gray-300 disabled:opacity-50 disabled:cursor-not-allowed rounded text-xs font-bold transition-colors"
                        >
                          +
                        </button>
                      </div>
                      <div v-if="item.isVariationDeleted" class="text-xs text-red-600 mt-1 italic">
                        Variation deleted
                      </div>
                      <div v-else-if="item.isProductDeleted" class="text-xs text-red-600 mt-1 italic">
                        Product deleted
                      </div>
                      <div v-else-if="pendingQuantityChanges[item.id]" class="text-xs text-amber-600 mt-1 italic">
                        Was: {{ pendingQuantityChanges[item.id].originalQuantity }}
                      </div>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap">
                      <span class="font-bold text-emerald-600">৳{{ item.final_price }}</span>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-center">
                      <button @click="removeItem(item.id)" :disabled="loading" class="text-red-600 hover:text-red-800 disabled:opacity-50 font-bold">
                        ✕
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div v-else class="px-6 py-8 text-center">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-gray-300 mx-auto mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14a2 2 0 012 2v7a2 2 0 01-2 2H5a2 2 0 01-2-2v-7a2 2 0 012-2z" />
              </svg>
              <p class="text-gray-500 font-medium text-sm">No items yet</p>
            </div>

            <!-- Order Summary -->
            <div class="bg-gradient-to-r from-gray-50 to-gray-100 px-6 py-4 border-t border-gray-200">
              <div class="space-y-2">
                <div class="flex justify-between items-center text-sm">
                  <span class="text-gray-600">Subtotal</span>
                  <span class="font-semibold text-gray-900">৳{{ orderSubtotal.toFixed(2) }}</span>
                </div>
                <div class="flex justify-between items-center text-sm">
                  <span class="text-gray-600">Shipping</span>
                  <span class="font-semibold text-gray-900">৳{{ form.shipping_cost || 0 }}</span>
                </div>
                <div v-if="discountAmount > 0" class="flex justify-between items-center text-sm text-red-600">
                  <span>Discount <span class="text-xs text-gray-500">({{ form.discount_type === 'percentage' ? form.discount + '%' : '৳' + discountAmount.toFixed(2) }})</span></span>
                  <span class="font-semibold">-৳{{ discountAmount.toFixed(2) }}</span>
                </div>
                <div class="border-t border-gray-300 pt-2 flex justify-between items-center">
                  <span class="text-gray-900 font-bold text-sm">Total</span>
                  <span class="text-lg font-bold text-emerald-600">৳{{ orderTotalWithDiscount.toFixed(2) }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Customer Information Card (Bottom) with Enhanced Design -->
          <div class="bg-white rounded-2xl shadow-lg hover:shadow-xl transition-all border border-gray-200 overflow-hidden">
            <div class="bg-gradient-to-r from-blue-500 via-blue-600 to-cyan-600 px-6 py-5 border-b border-blue-300">
              <h2 class="text-xl font-bold text-white flex items-center gap-3">
                <div class="bg-white/20 p-2 rounded-lg backdrop-blur-sm">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                  </svg>
                </div>
                👤 Customer Information
              </h2>
            </div>

            <div class="p-6 space-y-6">
              <div>
                <label class="flex items-center gap-2 text-xs font-bold text-gray-700 uppercase tracking-wide mb-2">
                  <span class="text-lg">👤</span> Customer Name
                </label>
                <input v-model="form.customer_name" type="text" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all hover:border-gray-300 font-medium" placeholder="Enter customer name" />
              </div>

              <div>
                <label class="flex items-center gap-2 text-xs font-bold text-gray-700 uppercase tracking-wide mb-2">
                  <span class="text-lg">📱</span> Phone Number
                </label>
                <input v-model="form.customer_phone" type="text" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all hover:border-gray-300 font-medium" placeholder="Enter phone number" />
              </div>

              <div>
                <label class="flex items-center gap-2 text-xs font-bold text-gray-700 uppercase tracking-wide mb-2">
                  <span class="text-lg">📧</span> Email Address
                </label>
                <input v-model="form.customer_email" type="email" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all hover:border-gray-300 font-medium" placeholder="Enter email address" />
              </div>

              <div>
                <label class="flex items-center gap-2 text-xs font-bold text-gray-700 uppercase tracking-wide mb-2">
                  <span class="text-lg">📍</span> Shipping Address
                </label>
                <textarea v-model="form.shipping_address" rows="4" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all resize-none hover:border-gray-300 font-medium" placeholder="Enter shipping address"></textarea>
              </div>

              <div>
                <label class="flex items-center gap-2 text-xs font-bold text-gray-700 uppercase tracking-wide mb-2">
                  <span class="text-lg">📝</span> Admin Notes
                </label>
                <textarea v-model="form.admin_notes" rows="3" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all resize-none hover:border-gray-300 font-medium bg-purple-50/30" placeholder="Internal notes (not visible to customers)"></textarea>
              </div>

              <div class="border-t-2 border-gray-100 pt-6 mt-2">
                <label class="flex items-center gap-2 text-xs font-bold text-gray-700 uppercase tracking-wide mb-2">
                  <span class="text-lg">💳</span> Payment Status
                </label>
                <select v-model="form.payment_status" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all bg-white hover:border-gray-300 font-semibold cursor-pointer">
                  <option v-for="(status, key) in paymentStatuses" :key="key" :value="key">{{ status }}</option>
                </select>
              </div>

              <div>
                <label class="flex items-center gap-2 text-xs font-bold text-gray-700 uppercase tracking-wide mb-2">
                  <span class="text-lg">💰</span> Payment Method
                </label>
                <select v-model="form.payment_method" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all bg-white hover:border-gray-300 font-semibold cursor-pointer">
                  <option v-for="(method, key) in paymentMethods" :key="key" :value="key">{{ method }}</option>
                </select>
              </div>

              <div class="border-t-2 border-gray-100 pt-6 mt-2">
                <label class="flex items-center gap-2 text-xs font-bold text-gray-700 uppercase tracking-wide mb-2">
                  <span class="text-lg">🏷️</span> Order Status
                </label>
                <StatusDropdown
                  v-model="form.status"
                  :order-id="order.id"
                  :is-loading="loading"
                  @status-change="(orderId, newStatus) => form.status = newStatus"
                />
              </div>

              <div>
                <label class="flex items-center gap-2 text-xs font-bold text-gray-700 uppercase tracking-wide mb-2">
                  <span class="text-lg">🚚</span> Delivery Area
                </label>
                <select v-model="form.area" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 transition-all bg-white hover:border-gray-300 font-semibold cursor-pointer">
                  <option value="inside_dhaka">📍 Inside Dhaka</option>
                  <option value="outside_dhaka">🌍 Outside Dhaka</option>
                </select>
              </div>

              <div>
                <label class="flex items-center gap-2 text-xs font-bold text-gray-700 uppercase tracking-wide mb-2">
                  <span class="text-lg">💵</span> Shipping Cost
                </label>
                <div class="relative">
                  <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500 font-bold text-lg">৳</span>
                  <input v-model.number="form.shipping_cost" type="number" min="0" step="0.01" class="w-full pl-10 pr-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all hover:border-gray-300 font-bold text-lg" placeholder="0.00" />
                </div>
              </div>

              <!-- Discount Settings with Enhanced Design -->
              <div class="mt-6 p-5 bg-gradient-to-br from-amber-50 to-orange-50 rounded-xl border-2 border-amber-200 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                  <h3 class="text-sm font-bold text-gray-800 flex items-center gap-2">
                    <span class="text-lg">🎁</span> Discount Settings
                  </h3>
                  <span class="px-2 py-1 bg-white text-xs font-bold text-amber-600 rounded-lg shadow-sm">Optional</span>
                </div>
                <div class="flex items-end space-x-3">
                  <div class="flex-1">
                    <label class="text-xs text-gray-700 font-bold block mb-2">💰 Discount Amount</label>
                    <input
                      v-model.number="form.discount"
                      type="number"
                      min="0"
                      step="0.01"
                      class="w-full px-4 py-2.5 text-sm font-semibold border-2 border-amber-200 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 hover:border-amber-300 transition-all"
                      placeholder="Enter amount"
                    />
                  </div>
                  <div class="w-36">
                    <label class="text-xs text-gray-700 font-bold block mb-2">📊 Type</label>
                    <select
                      v-model="form.discount_type"
                      class="w-full px-4 py-2.5 text-sm font-bold border-2 border-amber-200 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 bg-white hover:border-amber-300 transition-all cursor-pointer"
                    >
                      <option value="fixed">৳ Fixed</option>
                      <option value="percentage">% Percent</option>
                    </select>
                  </div>
                </div>
                <div class="mt-3 p-3 bg-white/60 rounded-lg">
                  <p class="text-xs text-gray-600 font-medium flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-amber-500" viewBox="0 0 20 20" fill="currentColor">
                      <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                    </svg>
                    {{ form.discount_type === 'percentage' ?
                      'Percentage discount will be applied to the subtotal' :
                      'Fixed amount will be deducted from the total' }}
                  </p>
                </div>
              </div>

              <button @click="submitForm" :disabled="loading" class="w-full bg-gradient-to-r from-emerald-500 via-emerald-600 to-green-600 hover:from-emerald-600 hover:via-emerald-700 hover:to-green-700 disabled:from-gray-400 disabled:to-gray-500 text-white py-4 px-6 rounded-xl transition-all duration-200 flex items-center justify-center gap-3 font-bold text-lg shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 active:translate-y-0">
                <svg v-if="loading" class="animate-spin h-6 w-6 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 20 20" fill="currentColor">
                  <path d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" />
                </svg>
                <span>{{ loading ? '⏳ Updating Order...' : '✅ Save All Changes' }}</span>
              </button>
            </div>
          </div>
        </div>

        <!-- Right Column: Available Products -->
        <div class="lg:col-span-2">
          <!-- Products Card with Enhanced Design -->
          <div class="bg-white rounded-2xl shadow-lg hover:shadow-xl transition-all border border-gray-200 overflow-hidden">
            <div class="bg-gradient-to-r from-emerald-500 via-green-600 to-teal-600 px-6 py-5 border-b border-emerald-300">
              <h2 class="text-xl font-bold text-white flex items-center gap-3 mb-2">
                <div class="bg-white/20 p-2 rounded-lg backdrop-blur-sm">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M3 1a1 1 0 000 2h1.22l.305 1.222a.997.997 0 00.01.042l1.358 5.43-.893.892C3.74 11.846 4.632 14 6.414 14H15a1 1 0 000-2H6.414l1-1H14a1 1 0 00.894-.553l3-6A1 1 0 0017 6H6.28l-.31-1.243A1 1 0 005 4H3z" />
                  </svg>
                </div>
                🛍️ Available Products
              </h2>
              <p class="text-sm text-emerald-100 font-medium">Search and add products to this order</p>
            </div>

            <!-- Filters with Enhanced Design -->
            <div class="px-6 py-5 border-b-2 border-gray-100 bg-gradient-to-r from-gray-50 to-gray-100">
              <div class="flex flex-col sm:flex-row justify-between gap-4 items-start sm:items-center">
                <div class="flex items-center gap-3 bg-white px-4 py-2 rounded-xl border border-gray-200 shadow-sm">
                  <label for="perPage" class="text-sm font-bold text-gray-700 flex items-center gap-2">
                    <span>📋</span> Show:
                  </label>
                  <select v-model="perPage" @change="updatePerPage" class="px-3 py-2 border-2 border-gray-200 rounded-lg shadow-sm focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500 text-sm font-bold bg-white hover:border-gray-300 transition-all cursor-pointer">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                  </select>
                </div>
                <div class="relative w-full sm:w-80">
                  <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                      <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                    </svg>
                  </div>
                  <input v-model="search" type="text" placeholder="🔍 Search products by name, ID..." class="block w-full pl-12 pr-12 py-3 border-2 border-gray-200 rounded-xl leading-5 bg-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 sm:text-sm font-semibold hover:border-gray-300 transition-all shadow-sm" />
                  <button v-if="search" @click="clearSearch" class="absolute inset-y-0 right-0 pr-4 flex items-center hover:scale-110 transition-transform">
                    <svg class="h-5 w-5 text-gray-400 hover:text-red-500 transition-colors" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                  </button>
                </div>
              </div>
            </div>

            <!-- Products Table with Enhanced Design -->
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y-2 divide-gray-200">
                <thead class="bg-gradient-to-r from-emerald-50 via-green-50 to-teal-50">
                  <tr>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-extrabold text-gray-800 uppercase tracking-wider">🏷️ Product</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-extrabold text-gray-800 uppercase tracking-wider">💰 Price</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-extrabold text-gray-800 uppercase tracking-wider">📦 Stock</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-extrabold text-gray-800 uppercase tracking-wider">🔢 Quantity</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-extrabold text-gray-800 uppercase tracking-wider">🎨 Variations</th>
                    <th scope="col" class="px-6 py-4 text-right text-xs font-extrabold text-gray-800 uppercase tracking-wider">⚡ Action</th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <tr v-for="product in filteredProducts" :key="product.id" class="hover:bg-blue-50 transition-colors">
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div class="flex items-center">
                        <div class="flex-shrink-0 h-12 w-12 bg-gray-100 rounded-lg flex items-center justify-center overflow-hidden">
                          <img :src="product.feature_image" alt="product image" class="h-full w-full object-cover" />
                        </div>
                        <div class="ml-4">
                          <div class="text-sm font-semibold text-gray-900">{{ truncateName(product.name) }}</div>
                          <div class="text-xs text-gray-500 font-medium">SKU: {{ product.id }}</div>
                        </div>
                      </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div class="text-sm font-bold text-emerald-600 flex items-center gap-1">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                          <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z" />
                          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd" />
                        </svg>
                        <span>৳{{ parseFloat(product.price).toFixed(2) }}</span>
                      </div>
                      <div class="text-[10px] text-gray-500 font-medium mt-0.5">Per unit</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <template v-if="product.type === 'variable' && selectedVariations[product.id]">
                        <!-- Variation Stock Display -->
                        <div class="flex flex-col gap-1">
                          <span :class="[
                            'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold border-2',
                            selectedVariations[product.id].stock > 10
                              ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                              : selectedVariations[product.id].stock > 0
                              ? 'bg-amber-50 text-amber-700 border-amber-200'
                              : 'bg-red-50 text-red-700 border-red-200'
                          ]">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                              <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                              <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" />
                            </svg>
                            {{ selectedVariations[product.id].stock }} Units
                          </span>
                          <span class="text-[10px] text-gray-500 font-medium">Selected variant</span>
                        </div>
                      </template>
                      <template v-else-if="product.type === 'variable'">
                        <!-- No Variation Selected -->
                        <div class="flex flex-col gap-1">
                          <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold bg-gray-100 text-gray-500 border-2 border-gray-200">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                              <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                            Select variant
                          </span>
                          <span class="text-[10px] text-gray-500 font-medium">Choose options</span>
                        </div>
                      </template>
                      <template v-else>
                        <!-- Simple Product Stock -->
                        <span :class="[
                          'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold border-2',
                          product.stock > 10
                            ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                            : product.stock > 0
                            ? 'bg-amber-50 text-amber-700 border-amber-200'
                            : 'bg-red-50 text-red-700 border-red-200'
                        ]">
                          <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M3 1a1 1 0 000 2h1.22l.305 1.222a.997.997 0 00.01.042l1.358 5.43-.893.892C3.74 11.846 4.632 14 6.414 14H15a1 1 0 000-2H6.414l1-1H14a1 1 0 00.894-.553l3-6A1 1 0 0017 3H6.28l-.31-1.243A1 1 0 005 1H3zM16 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM6.5 18a1.5 1.5 0 100-3 1.5 1.5 0 000 3z" />
                          </svg>
                          {{ product.stock }} Units
                        </span>
                      </template>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div class="flex flex-col gap-2">
                        <div class="flex items-center gap-3 bg-gradient-to-r from-gray-50 to-gray-100 rounded-lg p-2 border border-gray-200 w-fit">
                          <button @click="decrementQuantity(product)" :disabled="quantities[product.id] <= 1" class="w-8 h-8 flex items-center justify-center bg-white rounded-lg hover:bg-red-50 text-gray-700 hover:text-red-600 disabled:opacity-40 disabled:hover:bg-white disabled:hover:text-gray-700 font-bold transition-all shadow-sm border border-gray-200">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                              <path fill-rule="evenodd" d="M3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd" />
                            </svg>
                          </button>
                          <span class="text-sm w-8 text-center font-bold text-gray-900 bg-white px-2 py-1 rounded border border-gray-200">{{ quantities[product.id] }}</span>
                          <button @click="incrementQuantity(product)" :disabled="quantities[product.id] >= (product.type === 'variable' ? (selectedVariations[product.id]?.stock || 0) : product.stock)" class="w-8 h-8 flex items-center justify-center bg-white rounded-lg hover:bg-emerald-50 text-gray-700 hover:text-emerald-600 disabled:opacity-40 disabled:hover:bg-white disabled:hover:text-gray-700 font-bold transition-all shadow-sm border border-gray-200">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                              <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                            </svg>
                          </button>
                        </div>
                        <div class="text-[10px] text-gray-600 font-medium flex items-center gap-1">
                          <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-blue-500" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" />
                          </svg>
                          Subtotal: <span class="font-bold text-emerald-600">৳{{ (quantities[product.id] * product.price).toFixed(2) }}</span>
                        </div>
                      </div>
                    </td>
                    <td class="px-6 py-4">
                      <div v-if="product.variations && product.variations.length > 0" class="space-y-3">
                        <!-- Attribute Selection -->
                        <div v-for="(values, attrName) in getUniqueAttributes(product)" :key="attrName" class="bg-gray-50 rounded-lg p-3 border border-gray-200">
                          <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-bold text-gray-700 uppercase tracking-wide">{{ attrName }}</span>
                            <span v-if="selectedAttributes[product.id]?.[attrName]" class="text-[10px] bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full font-bold">
                              Selected: {{ selectedAttributes[product.id][attrName] }}
                            </span>
                          </div>
                          <div class="flex flex-wrap gap-2">
                            <button v-for="value in Array.from(values)" :key="value" @click="selectAttribute(product, attrName, value)" :class="[
                              'px-3 py-1.5 text-xs rounded-lg border-2 font-bold transition-all duration-200 transform hover:scale-105',
                              selectedAttributes[product.id]?.[attrName] === value
                                ? 'bg-gradient-to-r from-blue-600 to-blue-700 text-white border-blue-600 shadow-md'
                                : 'bg-white text-gray-700 border-gray-300 hover:border-blue-400 hover:text-blue-600 hover:shadow-sm'
                            ]">
                              {{ value }}
                            </button>
                          </div>
                        </div>

                        <!-- Variation Status Messages -->
                        <div v-if="errorMessages[product.id]" class="mt-3 bg-red-50 border-2 border-red-200 rounded-lg p-3">
                          <p class="text-xs text-red-700 font-bold flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                              <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                            </svg>
                            <span>{{ errorMessages[product.id] }}</span>
                          </p>
                        </div>
                        <div v-else-if="selectedVariations[product.id]" class="mt-3 bg-gradient-to-r from-emerald-50 to-green-50 border-2 border-emerald-200 rounded-lg p-3">
                          <div class="flex items-start justify-between gap-3">
                            <div class="flex-1">
                              <p class="text-xs text-emerald-800 font-bold flex items-center gap-2 mb-1">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                                <span>Variation Selected</span>
                              </p>
                              <div class="text-[10px] text-emerald-700 font-medium space-y-0.5">
                                <div v-for="attr in selectedVariations[product.id].attributes" :key="attr.id" class="flex items-center gap-1">
                                  <span class="font-bold">{{ attr.value.attribute.name }}:</span>
                                  <span>{{ attr.value.value }}</span>
                                </div>
                              </div>
                            </div>
                            <div class="text-right">
                              <div :class="[
                                'inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold',
                                selectedVariations[product.id].stock > 10
                                  ? 'bg-emerald-600 text-white'
                                  : selectedVariations[product.id].stock > 0
                                  ? 'bg-amber-500 text-white'
                                  : 'bg-red-500 text-white'
                              ]">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor">
                                  <path fill-rule="evenodd" d="M3 3a1 1 0 000 2v8a2 2 0 002 2h2.586l-1.293 1.293a1 1 0 101.414 1.414L10 15.414l2.293 2.293a1 1 0 001.414-1.414L12.414 15H15a2 2 0 002-2V5a1 1 0 100-2H3zm11.707 4.707a1 1 0 00-1.414-1.414L10 9.586 8.707 8.293a1 1 0 00-1.414 0l-2 2a1 1 0 101.414 1.414L8 10.414l1.293 1.293a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                                {{ selectedVariations[product.id].stock }}
                              </div>
                              <div class="text-[9px] text-emerald-600 font-medium mt-0.5">Available</div>
                            </div>
                          </div>
                        </div>
                        <div v-else class="mt-3 bg-blue-50 border-2 border-blue-200 rounded-lg p-3">
                          <p class="text-xs text-blue-700 font-medium flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 animate-pulse" viewBox="0 0 20 20" fill="currentColor">
                              <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                            </svg>
                            <span>Please select all options to see stock availability</span>
                          </p>
                        </div>
                      </div>
                      <div v-else class="text-xs text-gray-500 font-medium italic bg-gray-50 rounded-lg px-3 py-2 border border-gray-200">
                        No variations available
                      </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right">
                      <button @click="orderNew(product)" :disabled="product.type === 'variable' && !selectedVariations[product.id] || loading" :class="[
                        'group inline-flex items-center gap-2 px-4 py-2.5 border-2 text-xs font-bold rounded-xl shadow-sm transition-all duration-200 transform hover:scale-105',
                        product.type === 'variable' && !selectedVariations[product.id] || loading
                          ? 'bg-gray-100 text-gray-400 border-gray-300 cursor-not-allowed'
                          : 'bg-gradient-to-r from-emerald-500 to-green-600 hover:from-emerald-600 hover:to-green-700 text-white border-emerald-600 hover:shadow-lg active:scale-95'
                      ]">
                        <svg v-if="loading" class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform group-hover:rotate-12" viewBox="0 0 20 20" fill="currentColor">
                          <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" />
                        </svg>
                        <span>{{ loading ? 'Adding...' : 'Add to Order' }}</span>
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Pagination -->
            <div class="bg-gray-50 px-6 py-4 flex items-center justify-between border-t border-gray-200">
              <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                <div>
                  <p class="text-sm text-gray-700 font-medium">
                    Showing <span class="text-gray-900 font-bold">{{ products.from }}</span> to <span class="text-gray-900 font-bold">{{ products.to }}</span> of <span class="text-gray-900 font-bold">{{ products.total }}</span> results
                  </p>
                </div>
                <div v-if="products.links && products.links.length > 0">
                  <nav class="relative z-0 inline-flex rounded-lg shadow-sm -space-x-px gap-1" aria-label="Pagination">
                    <button v-for="link in products.links" :key="link.label" @click="link.url && goToPage(parseInt(link.url.split('page=')[1]))" :disabled="!link.url" :class="[
                      'relative inline-flex items-center px-3 py-2 border text-sm font-medium rounded transition-all',
                      link.active
                        ? 'z-10 bg-blue-50 border-blue-500 text-blue-600 shadow-sm'
                        : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50',
                      !link.url ? 'opacity-50 cursor-not-allowed' : ''
                    ]" v-html="link.label">
                    </button>
                  </nav>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>
