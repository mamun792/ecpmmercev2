<script setup>
import { Head, Link } from "@inertiajs/vue3";
import FrontendLayout from "@/Layouts/FrontendLayout.vue";
import ProductCard from "@/Components/Frontend/Product/ProductCard.vue";
import Campaigns from "@/Components/Frontend/Product/Campaigns.vue";
import Sliders from "@/Components/Frontend/Slider/Sliders.vue";
import Features from "@/Components/Frontend/Features/Features.vue";
import ProductVideo from "@/Components/Frontend/Product/ProductVideo.vue";

import FeaturedCategories from "@/Components/Frontend/Home/FeaturedCategories.vue";
import ProductsGroup from "@/Components/Frontend/Home/ProductsGroup.vue";
import { computed, toRef } from "vue";

defineOptions({
    layout: FrontendLayout,
});

const props = defineProps({
    categories: {
        type: Array,
        default: () => [],
    },
    groupedProducts: {
        type: Object,
        default: () => ({}),
    },
    activeCampaigns: {
        type: Object,
        default: () => ({}),
    },
    productGroups: {
        type: Array,
        default: () => [],
    },
    sliders: {
        type: Array,
        default: () => [],
    },
    settings: {
        type: Object,
        default: () => ({}),
    },
    productswithVideo: {
        type: Array,
        default: () => [],
    },
});

// Expose settings as a ref so it's accessible in the template and reactive in script
const settings = toRef(props, "settings");

// Expose other props as refs so template bindings remain available
const categories = toRef(props, "categories");
const groupedProducts = toRef(props, "groupedProducts");
const activeCampaigns = toRef(props, "activeCampaigns");
const productGroups = toRef(props, "productGroups");
const sliders = toRef(props, "sliders");

const pageTitle = computed(
    () => settings.value?.generalSettings?.home_page_title || "Home",
);
</script>

<template>
    <Head :title="pageTitle" />

    <!-- <pre>{{ settings }}</pre> -->

    <Sliders :sliders="sliders" />



    <!-- Featured Categories -->
    <FeaturedCategories :categories="categories" />

    <ProductVideo :productswithVideo="productswithVideo" />

    <!-- Offers preview (compact) -->
    <!-- <section class="py-8 bg-white">
        <div class="container mx-auto px-4">
            <div v-if="activeCampaigns && activeCampaigns.length > 0" class="flex items-center justify-between gap-4">
                <div class="flex-1">
                    <h2 class="text-xl font-bold text-gray-900">Latest Offers</h2>
                    <p class="text-sm text-gray-600 mt-1">Active campaigns and limited time deals. Click View All to see every offer.</p>
                    <div class="mt-4 flex items-center gap-3 overflow-auto">
                        <div v-for="c in activeCampaigns.slice(0,3)" :key="c.id" class="px-3 py-2 bg-gray-50 border rounded-lg text-sm font-medium text-gray-800 whitespace-nowrap">
                            {{ c.name }}
                        </div>
                    </div>
                </div>

                <div class="shrink-0">
                    <Link :href="route('offers.index')" class="px-4 py-2 bg-primary text-white rounded-lg">View All Offers</Link>
                </div>
            </div>

            <div v-else class="text-center text-gray-500 py-8">
                No offers currently. Check back soon.
            </div>
        </div>
    </section> -->

    <!-- Product Groups Section -->
    <ProductsGroup :productGroups="productGroups" />

    <!-- Products by Category Section -->
    <section
        v-for="group in groupedProducts"
        :key="group.name"
        class="py-8 bg-white"
    >
        <div
            v-if="group.products && group.products.length > 0"
            class="container mx-auto px-4"
        >
            <!-- Section Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h2
                        class="text-xl md:text-2xl font-bold text-gray-900 mb-2"
                    >
                        {{ group.name }}
                    </h2>
                </div>
                <Link
                    :href="`/category/${group.slug}`"
                    class="flex text-sm items-center gap-2 text-primary font-semibold hover:text-primary/90 transition-colors"
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
                            stroke-width="2"
                            d="M17 8l4 4m0 0l-4 4m4-4H3"
                        />
                    </svg>
                </Link>
            </div>
            <hr class="border-gray-200 my-4" />

            <!-- Product Grid -->
            <div
                class="pt-5 grid grid-cols-2 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 md:gap-6"
            >
                <ProductCard
                    v-for="product in group.products.slice(0, 6)"
                    :key="product.id"
                    :product="product"
                />
            </div>

            <!-- Show More Button -->
            <div v-if="group.products.length > 5" class="text-center mt-8">
                <Link
                    :href="`/category/${group.slug}`"
                    class="inline-block px-8 py-3 bg-primary text-white font-semibold rounded-lg hover:bg-primary/90 transition-colors"
                >
                    View All {{ group.products.length }} Products
                </Link>
            </div>
        </div>
    </section>

    <!-- <pre>{{ categories }}</pre> -->

    <!-- CTA Section -->
    <!-- <section class="py-16 bg-gray-900 text-white">
        <div class="container mx-auto px-4 text-center">
            <h2 class="text-3xl md:text-4xl font-bold mb-4">
                Ready to Start Shopping?
            </h2>
            <p class="text-gray-400 max-w-2xl mx-auto mb-8">
                Join thousands of happy customers and discover amazing deals
                every day.
            </p>
            <div class="flex flex-wrap justify-center gap-4">
                <Link
                    href="/products"
                    class="px-8 py-3 bg-primary text-white font-semibold rounded-lg hover:bg-primary/90 transition-colors"
                >
                    Browse Products
                </Link>
                <Link
                    v-if="!$page.props.auth?.user"
                    :href="route('register')"
                    class="px-8 py-3 bg-transparent border-2 border-white text-white font-semibold rounded-lg hover:bg-white/10 transition-colors"
                >
                    Create Account
                </Link>
            </div>
        </div>
    </section> -->
    <!-- Features Section -->
    <!-- <Features /> -->
</template>
