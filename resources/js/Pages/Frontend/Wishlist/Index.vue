<script setup>
import { Link, Head } from "@inertiajs/vue3";
import FrontendLayout from "@/Layouts/FrontendLayout.vue";
import ProductCard from "@/Components/Frontend/Product/ProductCard.vue";

const props = defineProps({
    wishlistItems: {
        type: Array,
        default: () => [],
    },
});

const formatPrice = (price) => {
    return new Intl.NumberFormat("en-BD", {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    }).format(price);
};
</script>

<template>
    <FrontendLayout>
        <Head title="Wishlist" />
        <div class="py-12 bg-gray-50 min-h-[calc(100vh-400px)]">
            <div class="container mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between mb-8">
                    <h1 class="text-3xl font-bold text-gray-900">
                        My Wishlist
                    </h1>
                </div>

                <div
                    v-if="wishlistItems.length > 0"
                    class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6"
                >
                    <div
                        v-for="product in wishlistItems"
                        :key="product.id"
                        class="bg-white rounded-lg shadow-sm overflow-hidden group border border-gray-100 flex flex-col"
                    >
                        <!-- Use shared ProductCard component for consistency -->
                        <ProductCard :product="product" />
                    </div>
                </div>

                <div
                    v-else
                    class="text-center py-16 bg-white rounded-lg shadow-sm border border-gray-100"
                >
                    <div
                        class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-gray-100 text-gray-400 mb-4"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-10 w-10"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"
                            />
                        </svg>
                    </div>
                    <h2 class="text-xl font-semibold text-gray-900 mb-2">
                        Your wishlist is empty
                    </h2>
                    <p class="text-gray-500 mb-8">
                        Save items that you like in your wishlist to review them
                        later.
                    </p>
                    <Link
                        href="/products"
                        class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-primary hover:bg-primary/90 transition-colors"
                    >
                        Continue Shopping
                    </Link>
                </div>
            </div>
        </div>
    </FrontendLayout>
</template>
