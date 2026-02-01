<script setup>
import { computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import FrontendLayout from '@/Layouts/FrontendLayout.vue';

const props = defineProps({
    order: {
        type: Object,
        required: true
    }
});

const page = usePage();
const isAuthenticated = computed(() => !!page.props.auth?.user);

// Format date
const formatDate = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

// Calculate estimated delivery (3-5 business days)
const estimatedDelivery = computed(() => {
    const today = new Date();
    const minDays = 3;
    const maxDays = 5;
    
    const minDate = new Date(today);
    minDate.setDate(today.getDate() + minDays);
    
    const maxDate = new Date(today);
    maxDate.setDate(today.getDate() + maxDays);
    
    const options = { month: 'short', day: 'numeric' };
    return `${minDate.toLocaleDateString('en-US', options)} - ${maxDate.toLocaleDateString('en-US', options)}`;
});
</script>

<template>
    <Head title="Order Confirmed" />
    <!-- <pre>{{ order }}</pre> -->
    <FrontendLayout>
        <div class="min-h-screen bg-gradient-to-b from-green-50 to-white py-12 px-4 sm:px-6 lg:px-8">
            <div class="max-w-3xl mx-auto">
                <!-- Success Animation Container -->
                <div class="text-center mb-8">
                    <!-- Animated Success Icon -->
                    <div class="relative inline-flex">
                        <div class="w-24 h-24 bg-green-100 rounded-full flex items-center justify-center animate-pulse">
                            <svg class="w-12 h-12 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path 
                                    stroke-linecap="round" 
                                    stroke-linejoin="round" 
                                    stroke-width="2.5" 
                                    d="M5 13l4 4L19 7"
                                />
                            </svg>
                        </div>
                        <!-- Decorative rings -->
                        <div class="absolute inset-0 w-24 h-24 bg-green-200 rounded-full animate-ping opacity-20"></div>
                    </div>
                    
                    <h1 class="mt-6 text-3xl sm:text-4xl font-extrabold text-gray-900">
                        Order Confirmed!
                    </h1>
                    <p class="mt-3 text-lg text-gray-600">
                        Thank you for your order. We've received your order and will begin processing it soon.
                    </p>
                </div>

                <!-- Order Details Card -->
                <div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
                    <!-- Order Header -->
                    <div class="bg-gradient-to-r from-primary to-primary/90 px-6 py-5">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="text-white/70 text-sm font-medium">Order Number</p>
                                <p class="text-white text-xl font-bold tracking-wide">{{ order.order_number }}</p>
                            </div>
                            <div class="mt-3 sm:mt-0 text-left sm:text-right">
                                <p class="text-white/70 text-sm font-medium">Order Date</p>
                                <p class="text-white font-medium">{{ formatDate(order.created_at) }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Order Status Timeline -->
                    <div class="px-6 py-5 border-b border-gray-100 bg-gray-50">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-green-500 rounded-full flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-semibold text-gray-900">Order Placed</p>
                                    <p class="text-xs text-gray-500">We have received your order</p>
                                </div>
                            </div>
                            <div class="hidden sm:block text-right">
                                <p class="text-sm font-medium text-gray-700">Estimated Delivery</p>
                                <p class="text-sm text-primary font-semibold">{{ estimatedDelivery }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Customer & Shipping Info -->
                    <div class="px-6 py-5 grid grid-cols-1 md:grid-cols-2 gap-6 border-b border-gray-100">
                        <!-- Customer Info -->
                        <div>
                            <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">Customer Details</h3>
                            <div class="space-y-2">
                                <p class="text-gray-900 font-medium">{{ order.customer_name }}</p>
                                <p class="text-gray-600 flex items-center">
                                    <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                    </svg>
                                    {{ order.customer_phone }}
                                </p>
                            </div>
                        </div>
                        
                        <!-- Shipping Address -->
                        <div>
                            <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">Shipping Address</h3>
                            <p class="text-gray-600 flex items-start">
                                <svg class="w-4 h-4 mr-2 mt-1 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                {{ order.shipping_address }}
                            </p>
                        </div>
                    </div>

                    <!-- Order Items -->
                    <div class="px-6 py-5">
                        <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4">Order Items</h3>
                        <div class="space-y-4">
                            <div 
                                v-for="item in order.items" 
                                :key="item.id"
                                class="flex items-center space-x-4 p-4 bg-gray-50 rounded-xl"
                            >
                                <!-- Product Image -->
                                <div class="w-20 h-20 bg-white rounded-lg overflow-hidden shadow-sm flex-shrink-0">
                                    <img 
                                        v-if="item.product?.feature_image"
                                        :src="item.product.feature_image"
                                        :alt="item.product?.name"
                                        class="w-full h-full object-cover"
                                    />
                                    <div v-else class="w-full h-full flex items-center justify-center text-gray-300">
                                        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                    </div>
                                </div>
                                
                                <!-- Product Details -->
                                <div class="flex-1 min-w-0">
                                    <h4 class="text-base font-semibold text-gray-900 truncate">{{ item.product?.name }}</h4>
                                    <!-- Variation Attributes -->
                                    <p v-if="item.product_variation?.attributes?.length" class="text-sm text-gray-500 mt-1">
                                        <span v-for="(attr, index) in item.product_variation.attributes" :key="attr.id">
                                            {{ attr.value?.attribute?.name }}: {{ attr.value?.value }}
                                            <span v-if="index < item.product_variation.attributes.length - 1"> | </span>
                                        </span>
                                    </p>
                                    <p class="text-sm text-gray-500 mt-1">Qty: {{ item.quantity }}</p>
                                </div>
                                
                                <!-- Item Price -->
                                <div class="text-right">
                                    <p class="text-lg font-semibold text-gray-900">৳{{ parseFloat(item.subtotal || 0).toFixed(2) }}</p>
                                    <p class="text-sm text-gray-500">৳{{ parseFloat(item.unit_price || 0).toFixed(2) }} each</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Order Summary -->
                    <div class="px-6 py-5 bg-gray-50 border-t border-gray-100">
                        <div class="max-w-xs ml-auto space-y-2">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Subtotal</span>
                                <span class="font-medium text-gray-900">৳{{ parseFloat(order.subtotal || 0).toFixed(2) }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Shipping</span>
                                <span class="font-medium text-gray-900">৳{{ parseFloat(order.shipping_cost || 0).toFixed(2) }}</span>
                            </div>
                            <div v-if="parseFloat(order.discount_total || order.discount || 0) > 0" class="flex justify-between text-sm">
                                <span class="text-gray-600">Discount</span>
                                <span class="font-medium text-green-600">-৳{{ parseFloat(order.discount_total || order.discount || 0).toFixed(2) }}</span>
                            </div>
                            <div class="flex justify-between text-lg font-bold border-t border-gray-200 pt-3">
                                <span class="text-gray-900">Total</span>
                                <span class="text-primary">৳{{ parseFloat(order.total || 0).toFixed(2) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Info -->
                    <div class="px-6 py-4 bg-amber-50 border-t border-amber-100">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-amber-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            <div>
                                <p class="text-sm font-semibold text-amber-800">
                                    Payment Method: {{ order.payment_method === 'cod' ? 'Cash on Delivery' : order.payment_method }}
                                </p>
                                <p class="text-xs text-amber-600">
                                    Status: {{ order.payment_status === 'unpaid' ? 'Pay on delivery' : order.payment_status }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="mt-8 flex flex-col sm:flex-row gap-4 justify-center">
                    <Link 
                        href="/"
                        class="inline-flex items-center justify-center px-8 py-4 bg-primary hover:bg-primary/90 text-white font-semibold rounded-xl shadow-lg transition-all hover:shadow-xl"
                    >
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        Continue Shopping
                    </Link>
                    <Link 
                        v-if="isAuthenticated"
                        :href="route('user.dashboard')"
                        class="inline-flex items-center justify-center px-8 py-4 bg-white hover:bg-gray-50 text-gray-700 font-semibold rounded-xl shadow-lg border border-gray-200 transition-all hover:shadow-xl"
                    >
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                        View My Orders
                    </Link>
                </div>

                <!-- Additional Info -->
                <div class="mt-8 text-center">
                    <p class="text-sm text-gray-500">
                        Need help? Contact us at 
                        <a href="tel:+8801XXXXXXXXX" class="text-primary hover:underline font-medium">+880 1XX-XXXXXXX</a>
                    </p>
                </div>
            </div>
        </div>
    </FrontendLayout>
</template>

<style scoped>
@keyframes checkmark {
    0% {
        stroke-dashoffset: 100;
    }
    100% {
        stroke-dashoffset: 0;
    }
}
</style>
