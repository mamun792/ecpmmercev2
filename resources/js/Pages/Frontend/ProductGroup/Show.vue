<script setup>
import { Head, Link } from "@inertiajs/vue3";
import FrontendLayout from "@/Layouts/FrontendLayout.vue";
import ProductCard from "@/Components/Frontend/Product/ProductCard.vue";

const props = defineProps({
    group: Object,
});
</script>

<template>
    <Head :title="group.name" />
    <FrontendLayout>
        <div class="min-h-screen bg-gray-50">
            <!-- Banner Section -->
            <div
                v-if="group.banner"
                class="container mx-auto px-4 py-8 relative overflow-hidden"
            >
                <img
                    :src="group.banner"
                    :alt="group.name"
                    class="w-full h-full object-cover"
                />
            </div>

            <div class="container mx-auto px-4 py-8">
                <!-- Page Title if no banner -->
                <div
                    v-if="!group.banner"
                    class="mb-8 border-b border-gray-200 pb-4"
                >
                    <h1 class="text-3xl font-bold text-gray-900">
                        {{ group.name }}
                    </h1>
                    <p class="text-gray-500 mt-2">
                        Discover our curated selection of
                        {{ group.name.toLowerCase() }}
                    </p>
                </div>

                <!-- Product Grid -->
                <div
                    v-if="group.products.length > 0"
                    class="grid grid-cols-2 lg:grid-cols-4 xl:grid-cols-5 gap-4 md:gap-6"
                >
                    <ProductCard
                        v-for="product in group.products"
                        :key="product.id"
                        :product="product"
                    />
                </div>

                <!-- No Results -->
                <div
                    v-else
                    class="text-center py-20 bg-white rounded-2xl shadow-sm"
                >
                    <div class="mb-4 text-gray-300 flex justify-center">
                        <svg
                            class="w-20 h-20"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="1"
                                d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"
                            />
                        </svg>
                    </div>
                    <h2 class="text-xl font-semibold text-gray-900">
                        No products in this group
                    </h2>
                    <p class="text-gray-500 mt-2">
                        Check back later for new updates!
                    </p>
                    <Link
                        href="/"
                        class="mt-6 inline-block bg-primary text-white px-8 py-3 rounded-full font-bold hover:bg-primary/90 transition-all"
                    >
                        Back to Home
                    </Link>
                </div>
            </div>
        </div>
    </FrontendLayout>
</template>
