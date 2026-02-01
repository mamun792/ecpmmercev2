<script setup>
import { Link, Head } from "@inertiajs/vue3";
import FrontendLayout from "@/Layouts/FrontendLayout.vue";
import { useCompare } from "@/Composables/useCompare";

const { removeFromCompare, clearCompare } = useCompare();

const props = defineProps({
    compareItems: {
        type: Array,
        default: () => [],
    },
});

const handleRemove = (productId) => {
    removeFromCompare(productId);
};

const handleClear = () => {
    clearCompare();
};

const formatPrice = (price) => {
    return new Intl.NumberFormat("en-BD", {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    }).format(price);
};

const handleAddToCart = (product) => {
    // Logic to add product to cart
};

const formatRating = (product) => {
    if (!product || product.avg_rating === null || product.avg_rating === undefined) return "--";
    return `${parseFloat(product.avg_rating).toFixed(1)} (${product.reviews_count || 0})`;
};

const formatShortDescription = (product) => {
    if (!product || !product.short_description) return "--";
    // Strip HTML tags and limit length
    const text = product.short_description.replace(/<[^>]*>/g, "").trim();
    return text.length > 120 ? text.slice(0, 120) + "..." : text;
};

const formatAttributes = (product) => {
    if (!product || !product.variations || product.variations.length === 0) return "--";
    const map = new Map();

    product.variations.forEach((variation) => {
        variation.attributes?.forEach((a) => {
            const key = (a.value.attribute && a.value.attribute.name) || "Attribute";
            const val = a.value.value;
            if (!map.has(key)) map.set(key, new Set());
            map.get(key).add(val);
        });
    });

    if (map.size === 0) return "--";
    return Array.from(map.entries())
        .map(([k, set]) => `${k}: ${Array.from(set).join(", ")}`)
        .join(" • ");
};

const formatSpecification = (product) => {
    if (!product || !product.specification || product.specification.length === 0) return "--";
    // Map spec array {title,value} to a compact string
    return product.specification.map(s => `${s.title}: ${s.value}`).join(" • ");
};
</script>

<template>
    <FrontendLayout>
        <Head title="Compare" />
        <div class="py-12 bg-gray-50 min-h-[calc(100vh-400px)]">
            <div class="container mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between mb-8">
                    <h1 class="text-3xl font-bold text-gray-900">
                        Compare Products
                    </h1>
                    <button
                        v-if="compareItems.length > 0"
                        @click="handleClear"
                        class="text-sm font-medium text-red-600 hover:text-red-700 transition-colors"
                    >
                        Clear All
                    </button>
                </div>

                <div
                    v-if="compareItems.length > 0"
                    class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-x-auto"
                >
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50">
                                <th
                                    class="p-4 border-b w-48 font-semibold text-gray-700"
                                >
                                    Product
                                </th>
                                <th
                                    v-for="product in compareItems"
                                    :key="product.id"
                                    class="p-4 border-b border-l min-w-[200px]"
                                >
                                    <div class="relative group">
                                        <button
                                            @click="handleRemove(product.id)"
                                            class="absolute -top-2 -right-2 p-1 bg-white rounded-full shadow-sm text-gray-400 hover:text-red-500 transition-colors z-10 border"
                                        >
                                            <svg
                                                xmlns="http://www.w3.org/2000/svg"
                                                class="h-4 w-4"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                stroke="currentColor"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M6 18L18 6M6 6l12 12"
                                                />
                                            </svg>
                                        </button>
                                        <Link
                                            :href="
                                                route(
                                                    'product.details',
                                                    product.slug
                                                )
                                            "
                                            class="block aspect-square bg-gray-50 rounded-lg mb-4"
                                        >
                                            <img
                                                :src="product.feature_image"
                                                :alt="product.name"
                                                class="w-full h-full object-contain p-2"
                                            />
                                        </Link>
                                        <Link
                                            :href="
                                                route(
                                                    'product.details',
                                                    product.slug
                                                )
                                            "
                                            class="block text-sm font-bold text-gray-900 line-clamp-2 hover:text-primary transition-colors h-10"
                                        >
                                            {{ product.name }}
                                        </Link>
                                    </div>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td
                                    class="p-4 border-b bg-gray-50 font-medium text-gray-700"
                                >
                                    Price
                                </td>
                                <td
                                    v-for="product in compareItems"
                                    :key="product.id"
                                    class="p-4 border-b border-l"
                                >
                                    <span class="text-lg font-bold text-primary"
                                        >৳
                                        {{ formatPrice(product.price) }}</span
                                    >
                                </td>
                            </tr>
                            <tr>
                                <td
                                    class="p-4 border-b bg-gray-50 font-medium text-gray-700"
                                >
                                    Category
                                </td>
                                <td
                                    v-for="product in compareItems"
                                    :key="product.id"
                                    class="p-4 border-b border-l text-sm text-gray-600"
                                >
                                    {{ product.category?.name || "N/A" }}
                                </td>
                            </tr>
                            <tr>
                                <td
                                    class="p-4 border-b bg-gray-50 font-medium text-gray-700"
                                >
                                    Availability
                                </td>
                                <td
                                    v-for="product in compareItems"
                                    :key="product.id"
                                    class="p-4 border-b border-l"
                                >
                                    <span
                                        :class="[
                                            'text-xs font-semibold px-2 py-1 rounded-full',
                                            product.stock > 0
                                                ? 'bg-green-100 text-green-700'
                                                : 'bg-red-100 text-red-700',
                                        ]"
                                    >
                                        {{
                                            product.stock > 0
                                                ? "In Stock"
                                                : "Out of Stock"
                                        }}
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td
                                    class="p-4 border-b bg-gray-50 font-medium text-gray-700"
                                >
                                    Product Code
                                </td>
                                <td
                                    v-for="product in compareItems"
                                    :key="product.id"
                                    class="p-4 border-b border-l text-sm text-gray-600"
                                >
                                    {{ product.product_code || "N/A" }}
                                </td>
                            </tr>
                            <tr>
                                <td
                                    class="p-4 border-b bg-gray-50 font-medium text-gray-700"
                                >
                                    Brand
                                </td>
                                <td
                                    v-for="product in compareItems"
                                    :key="product.id"
                                    class="p-4 border-b border-l text-sm text-gray-600"
                                >
                                    {{ product.brand?.name || "--" }}
                                </td>
                            </tr>

                            <!-- Average Rating -->
                            <tr>
                                <td
                                    class="p-4 border-b bg-gray-50 font-medium text-gray-700"
                                >
                                    Avg Rating
                                </td>
                                <td
                                    v-for="product in compareItems"
                                    :key="product.id"
                                    class="p-4 border-b border-l text-sm text-gray-600"
                                >
                                    <span v-if="product.avg_rating && product.reviews_count > 0">{{ formatRating(product) }}</span>
                                    <span v-else>--</span>
                                </td>
                            </tr>

                            <!-- Short Description -->
                            <tr>
                                <td
                                    class="p-4 border-b bg-gray-50 font-medium text-gray-700"
                                >
                                    Short Description
                                </td>
                                <td
                                    v-for="product in compareItems"
                                    :key="product.id"
                                    class="p-4 border-b border-l text-sm text-gray-600"
                                >
                                    {{ formatShortDescription(product) }}
                                </td>
                            </tr>

                            <!-- Attributes -->
                            <tr>
                                <td
                                    class="p-4 border-b bg-gray-50 font-medium text-gray-700"
                                >
                                    Attributes
                                </td>
                                <td
                                    v-for="product in compareItems"
                                    :key="product.id"
                                    class="p-4 border-b border-l text-sm text-gray-600"
                                >
                                    {{ formatAttributes(product) }}
                                </td>
                            </tr>

                            <!-- Specifications -->
                            <tr>
                                <td
                                    class="p-4 border-b bg-gray-50 font-medium text-gray-700"
                                >
                                    Specifications
                                </td>
                                <td
                                    v-for="product in compareItems"
                                    :key="product.id"
                                    class="p-4 border-b border-l text-sm text-gray-600"
                                >
                                    {{ formatSpecification(product) }}
                                </td>
                            </tr>

                            <tr>
                                <td class="p-4 bg-gray-50"></td>
                                <td
                                    v-for="product in compareItems"
                                    :key="product.id"
                                    class="p-4 border-l"
                                >
                                    <Link
                                        :href="route('product.details', product.slug)"
                                        class="inline-flex items-center justify-center gap-2 w-full sm:px-4 px-2 py-2.5 bg-primary text-white text-[12px] sm:text-sm font-semibold rounded-lg hover:bg-primary/90 transition-all duration-200 shadow-sm hover:shadow-md active:scale-95"
                                        :class="{ 'opacity-50 pointer-events-none': product.stock <= 0 }"
                                    >
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            class="h-4 w-4"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                            />
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                                            />
                                        </svg>
                                        <span>View Details</span>
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
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
                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
                            />
                        </svg>
                    </div>
                    <h2 class="text-xl font-semibold text-gray-900 mb-2">
                        Comparison list is empty
                    </h2>
                    <p class="text-gray-500 mb-8">
                        Add products to compare their features and prices.
                    </p>
                    <Link
                        href="/products"
                        class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-primary hover:bg-primary/90 transition-colors"
                    >
                        Browse Products
                    </Link>
                </div>
            </div>
        </div>
    </FrontendLayout>
</template>
