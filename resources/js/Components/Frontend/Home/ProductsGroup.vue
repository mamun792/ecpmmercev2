<script setup>
import { Link } from "@inertiajs/vue3";
import ProductCard from "@/Components/Frontend/Product/ProductCard.vue";

defineProps({
    productGroups: {
        type: Array,
        required: true,
    },
});
</script>

<template>
    <!-- Product Groups Section -->
    <section
        v-for="group in productGroups"
        :key="group.id"
        class="py-10 md:py-16 bg-white border-b border-gray-100 last:border-0"
    >
        <div class="container mx-auto px-4">
            <!-- Banner Section -->
            <div
                v-if="group.banner"
                class="mb-8 md:mb-12 overflow-hidden rounded-xl md:rounded-2xl shadow-sm hover:shadow-md transition-shadow duration-300"
            >
                <Link
                    :href="route('product-group.show', group.slug)"
                    class="block relative"
                >
                    <img
                        :src="group.banner"
                        :alt="group.name"
                        class="w-full h-full object-cover transition-transform duration-700 hover:scale-105"
                    />
                </Link>
            </div>

            <!-- Header Section -->
            <div class="flex items-center justify-between mb-6 md:mb-10">
                <div class="relative">
                    <h2
                        class="text-xl md:text-2xl font-bold text-gray-900 tracking-tight"
                    >
                        {{ group.name }}
                    </h2>
                    <div
                        class="absolute -bottom-2 left-0 w-12 h-1 bg-primary rounded-full"
                    ></div>
                </div>

                <Link
                    :href="route('product-group.show', group.slug)"
                    class="hidden text-sm md:flex items-center gap-2 text-primary font-semibold hover:gap-3 transition-all duration-300"
                >
                    View All
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-5 w-5"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2.5"
                            d="M13 7l5 5m0 0l-5 5m5-5H6"
                        />
                    </svg>
                </Link>
            </div>

            <!-- Product Grid -->
            <div
                class="grid grid-cols-2 md:grid-cols-2 lg:grid-cols-2 xl:grid-cols-4 gap-3 md:gap-6"
            >
                <div
                    v-for="product in group.products"
                    :key="product.id"
                    class="transform transition-all duration-300 hover:-translate-y-1"
                >
                    <ProductCard :product="product" />
                </div>
            </div>

            <!-- Mobile Show All Button -->
            <div class="mt-8 text-center md:hidden">
                <Link
                    :href="route('product-group.show', group.slug)"
                    class="inline-flex text-[12px] items-center justify-center px-8 py-3 bg-gray-50 text-gray-900 font-bold rounded-lg border border-gray-200 active:bg-gray-100 transition-colors w-full sm:w-auto"
                >
                    View All Collection
                </Link>
            </div>
        </div>
    </section>
</template>

<style scoped>
/* Any specific scoped styles if needed */
</style>
