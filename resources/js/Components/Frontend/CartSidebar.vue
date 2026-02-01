<script setup>
import { watch } from 'vue';
import { Link } from '@inertiajs/vue3';
import { useCart } from '@/Composables/useCart';

const props = defineProps({
    isOpen: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['close']);

// Use centralized cart composable
const { 
    cart, 
    cartItems, 
    cartTotal, 
    cartCount, 
    isLoading, 
    incrementQuantity, 
    decrementQuantity, 
    removeItem 
} = useCart();

// Handle body scroll when sidebar opens/closes
watch(() => props.isOpen, (newVal) => {
    if (newVal) {
        document.body.style.overflow = 'hidden';
    } else {
        document.body.style.overflow = '';
    }
});

const handleOverlayClick = () => {
    emit('close');
};
</script>

<template>
    <!-- Overlay -->
    <Transition name="fade">
        <div 
            v-if="isOpen" 
            class="fixed inset-0 bg-black/50 z-50"
            @click="handleOverlayClick"
        ></div>
    </Transition>

    <!-- Sidebar -->
    <Transition name="slide-left">
        <div 
            v-if="isOpen" 
            class="fixed top-0 right-0 h-full w-96 max-w-[90vw] bg-white z-50 shadow-xl flex flex-col"
        >
            <!-- Header -->
            <div class="flex items-center justify-between p-4 border-b bg-gray-50">
                <h2 class="text-lg font-bold text-gray-900">
                    Shopping Cart 
                    <span v-if="cartCount > 0" class="text-sm font-normal text-gray-500">({{ cartCount }} items)</span>
                </h2>
                <button 
                    @click="emit('close')"
                    class="p-2 hover:bg-gray-200 rounded-full transition-colors"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Loading State -->
            <div v-if="isLoading" class="flex-1 flex items-center justify-center">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary"></div>
            </div>

            <!-- Empty Cart -->
            <div v-else-if="!cart || cartItems.length === 0" class="flex-1 flex flex-col items-center justify-center p-8 text-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-24 w-24 text-gray-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">Your cart is empty</h3>
                <p class="text-gray-500 mb-6">Add items to get started</p>
                <Link 
                    href="/" 
                    @click="emit('close')"
                    class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors"
                >
                    Continue Shopping
                </Link>
            </div>

            <!-- Cart Items -->
            <div v-else class="flex-1 overflow-y-auto p-4 space-y-4">
                <div 
                    v-for="item in cartItems" 
                    :key="item.id"
                    class="flex gap-4 p-3 border rounded-lg hover:shadow-md transition-shadow"
                >
                    <!-- Product Image -->
                    <img 
                        :src="item.product_image" 
                        :alt="item.product_name"
                        class="w-20 h-20 object-cover rounded-lg"
                    />
                    
                    <!-- Product Details -->
                    <div class="flex-1 min-w-0">
                        <h3 class="font-medium text-gray-900 truncate mb-1">{{ item.product_name }}</h3>
                        
                        <!-- Variation Attributes -->
                        <div v-if="item.variation_attributes" class="text-xs text-gray-500 mb-2 space-y-1">
                            <span 
                                v-for="(attr, idx) in item.variation_attributes" 
                                :key="idx"
                                class="inline-block mr-2"
                            >
                                {{ attr.name }}: <span class="font-medium">{{ attr.value }}</span>
                            </span>
                        </div>

                        <!-- Price -->
                        <p class="text-primary font-semibold mb-2">৳{{ item.price }}</p>

                        <!-- Quantity Controls -->
                        <div class="flex items-center gap-2">
                            <button 
                                @click="decrementQuantity(item.id, item.quantity)"
                                class="w-7 h-7 flex items-center justify-center border rounded hover:bg-gray-100 transition-colors"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
                                </svg>
                            </button>
                            <span class="w-8 text-center font-medium">{{ item.quantity }}</span>
                            <button 
                                @click="incrementQuantity(item.id)"
                                class="w-7 h-7 flex items-center justify-center border rounded hover:bg-gray-100 transition-colors"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                            </button>
                            <button 
                                @click="removeItem(item.id)"
                                class="ml-auto text-red-500 hover:text-red-700 p-1"
                                title="Remove item"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>

                        <!-- Subtotal -->
                        <p class="text-sm text-gray-600 mt-2">
                            Subtotal: <span class="font-semibold">৳{{ item.subtotal }}</span>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div v-if="cart && cartItems.length > 0" class="border-t bg-gray-50 p-4 space-y-4">
                <!-- Total -->
                <div class="flex items-center justify-between text-lg font-bold">
                    <span>Total:</span>
                    <span class="text-primary">৳{{ cartTotal }}</span>
                </div>

                <!-- Action Buttons -->
                <div class="space-y-2">
                    <Link 
                        :href="route('order.create')" 
                        @click="emit('close')"
                        class="block w-full py-3 bg-primary text-white text-center rounded-lg font-semibold hover:bg-primary/90 transition-colors"
                    >
                        Proceed to Checkout
                    </Link>
                    <Link 
                        href="/cart" 
                        @click="emit('close')"
                        class="block w-full py-3 border border-primary text-primary text-center rounded-lg font-semibold hover:bg-primary/10 transition-colors"
                    >
                        View Full Cart
                    </Link>
                </div>
            </div>
        </div>
    </Transition>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

.slide-left-enter-active,
.slide-left-leave-active {
    transition: transform 0.3s ease;
}

.slide-left-enter-from,
.slide-left-leave-to {
    transform: translateX(100%);
}
</style>
