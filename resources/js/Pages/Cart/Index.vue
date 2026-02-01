<template>
  <Head title="Cart" />
  <FrontendLayout>
    <div class="min-h-screen bg-gray-50 py-8">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
          <h1 class="text-3xl font-bold text-gray-900">Shopping Cart</h1>
          <p class="mt-2 text-sm text-gray-600">Review your items before checkout</p>
        </div>

        <!-- Empty Cart State -->
        <div v-if="!cartItems || cartItems.length === 0" class="flex flex-col items-center justify-center py-16">
          <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-12 text-center max-w-md">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-24 w-24 text-gray-300 mb-4 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
            <h2 class="text-2xl font-bold text-gray-900 mb-2">Your cart is empty</h2>
            <p class="text-gray-600 mb-6">Add some products to your cart to get started.</p>
            <Link 
              href="/" 
              class="inline-flex items-center px-6 py-3 bg-primary text-white font-medium rounded-lg hover:bg-primary/90 transition-colors"
            >
              <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
              </svg>
              Continue Shopping
            </Link>
          </div>
        </div>

        <!-- Cart Items -->
        <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-8">
          <!-- Cart Items List -->
          <div class="lg:col-span-2 space-y-4">
            <div 
              v-for="item in cartItems" 
              :key="item.id"
              class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-shadow"
            >
              <div class="flex gap-4">
                <!-- Product Image -->
                <div class="w-24 h-24 flex-shrink-0 bg-gray-100 rounded-lg overflow-hidden">
                  <img 
                    v-if="item.product?.feature_image" 
                    :src="item.product.feature_image" 
                    :alt="item.product?.name"
                    class="w-full h-full object-cover"
                  />
                  <div v-else class="w-full h-full flex items-center justify-center text-gray-400">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                  </div>
                </div>

                <!-- Product Details -->
                <div class="flex-1">
                  <h3 class="font-semibold text-gray-900 mb-1 break-words">{{ item.product?.name }}</h3>
                  
                  <!-- Variation Attributes -->
                  <div v-if="item.variation?.attributes" class="text-sm text-gray-600 mb-2">
                    <span v-for="(attr, idx) in item.variation.attributes" :key="attr.id">
                      <span class="font-medium">{{ attr.value.attribute.name }}:</span> {{ attr.value.value }}
                      <span v-if="idx < item.variation.attributes.length - 1">, </span>
                    </span>
                  </div>

                  <!-- Type Badge -->
                  <span v-if="item.is_pre_order" class="inline-block px-2 py-1 bg-yellow-100 text-yellow-800 text-xs font-medium rounded">
                    Pre-Order
                  </span>

                  <!-- Price and Quantity -->
                  <div class="mt-3 flex items-center justify-between">
                    <div>
                      <p class="text-sm text-gray-600">Price: <span class="font-semibold text-gray-900">৳ {{ parseFloat(item.price).toFixed(2) }}</span></p>
                      <p class="text-sm text-gray-600">Quantity: <span class="font-semibold">{{ item.quantity }}</span></p>
                    </div>
                    <div class="text-right">
                      <p class="text-lg font-bold text-gray-900">৳ {{ calculateTotalPrice(item.price, item.quantity) }}</p>
                    </div>
                  </div>
                </div>

                <!-- Delete Button -->
                <button 
                  @click="deleteItem(item.id)"
                  class="flex-shrink-0 text-red-500 hover:text-red-700 p-2 h-fit"
                  title="Remove item"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                </button>
              </div>
            </div>
          </div>

          <!-- Order Summary -->
          <div class="lg:col-span-1">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 sticky top-4">
              <h2 class="text-lg font-bold text-gray-900 mb-4">Order Summary</h2>
              
              <div class="space-y-3 mb-6">
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">Subtotal</span>
                  <span class="font-semibold text-gray-900">৳ {{ subtotal }}</span>
                </div>
              </div>

              <div class="border-t border-gray-200 pt-4 mb-6">
                <div class="flex justify-between">
                  <span class="text-base font-bold text-gray-900">TOTAL</span>
                  <span class="text-xl font-bold text-primary">৳ {{ subtotal }}</span>
                </div>
              </div>

              <Link 
                :href="route('order.create')"
                class="block w-full py-3 bg-primary text-white text-center font-semibold rounded-lg hover:bg-primary/90 transition-colors"
              >
                Checkout
              </Link>

              <Link 
                href="/" 
                class="block w-full mt-3 py-3 border border-gray-300 text-gray-700 text-center font-medium rounded-lg hover:bg-gray-50 transition-colors"
              >
                Continue Shopping
              </Link>
            </div>
          </div>
        </div>
      </div>
    </div>
  </FrontendLayout>
</template>

<script setup>
import FrontendLayout from '@/Layouts/FrontendLayout.vue';
import { computed } from 'vue';
import { router, Head, Link } from '@inertiajs/vue3';
import { useToast } from '@/Composables/useToast';

const { success, error: showError } = useToast();

const props = defineProps({
  cartItems: Array,
});

const calculateTotalPrice = (price, quantity) => {
  return (parseFloat(price) * quantity).toFixed(2);
};

const subtotal = computed(() => {
  if (!props.cartItems || props.cartItems.length === 0) return '0.00';
  return props.cartItems.reduce((sum, item) => {
    return sum + (parseFloat(item.price) * item.quantity);
  }, 0).toFixed(2);
});

const deleteItem = (itemId) => {
  router.post(route('cart.remove'), { item_id: itemId }, {
    preserveScroll: true,
    onSuccess: () => {
      success('Item removed from cart');
    },
    onError: () => {
      showError('Failed to remove item');
    },
  });
};
</script>

