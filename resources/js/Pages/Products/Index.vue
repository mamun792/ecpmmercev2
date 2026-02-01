<script setup>
import { ref, computed, watch, onMounted } from "vue";
import { router, Head, Link } from "@inertiajs/vue3";
import FrontendLayout from "@/Layouts/FrontendLayout.vue";
import ProductCard from "@/Components/Frontend/Product/ProductCard.vue";
import CategoryFilterTree from "@/Components/Filters/CategoryFilterTree.vue";
import {
    ChevronDown,
    ChevronUp,
    X,
    Filter,
    SlidersHorizontal,
} from "lucide-vue-next";
import debounce from "lodash/debounce";

const props = defineProps({
    products: Object,
    filters: Object,
    appliedFilters: Object,
});

// Filter state
const selectedCategory = ref(props.appliedFilters?.category || "");
const selectedBrands = ref(props.appliedFilters?.brand_ids || []);
const selectedAttributes = ref(props.appliedFilters?.attributes || {}); // e.g., {Size: ['XL', 'L'], Color: ['Red']}
const minPrice = ref(props.appliedFilters?.min_price || "");
const maxPrice = ref(props.appliedFilters?.max_price || "");
const sortBy = ref(props.appliedFilters?.sort_by || "latest");
const searchQuery = ref(props.appliedFilters?.search || "");

// UI state
const showFilters = ref(false);
const showBrands = ref(true);
const showPrice = ref(true);
const showCategories = ref(true);
const showAttributes = ref({}); // Track which attribute sections are expanded

// Initialize showAttributes for all available attributes
const initializeShowAttributes = () => {
    if (props.filters?.attributes) {
        props.filters.attributes.forEach((attr) => {
            if (showAttributes.value[attr.name] === undefined) {
                showAttributes.value[attr.name] = true;
            }
        });
    }
};

// Initialize on mount
onMounted(() => {
    initializeShowAttributes();
});

// Also watch for filters changes (in case data loads after mount)
watch(
    () => props.filters?.attributes,
    () => {
        initializeShowAttributes();
    },
    { immediate: true },
);

// Check if attribute is a color type (case-insensitive)
const isColorAttribute = (attributeName) => {
    return attributeName.toLowerCase() === "color";
};

// Sort options
const sortOptions = [
    { value: "latest", label: "Latest" },
    { value: "oldest", label: "Oldest" },
    { value: "price_asc", label: "Price: Low to High" },
    { value: "price_desc", label: "Price: High to Low" },
    { value: "name_asc", label: "Name: A to Z" },
    { value: "name_desc", label: "Name: Z to A" },
];

// Apply filters
const applyFilters = () => {
    const params = {};

    if (searchQuery.value) params.search = searchQuery.value;
    if (selectedCategory.value) params.category = selectedCategory.value;
    if (selectedBrands.value.length > 0)
        params.brand_ids = selectedBrands.value;
    if (minPrice.value) params.min_price = minPrice.value;
    if (maxPrice.value) params.max_price = maxPrice.value;
    if (sortBy.value !== "latest") params.sort_by = sortBy.value;

    // Add attribute filters
    if (Object.keys(selectedAttributes.value).length > 0) {
        params.attributes = selectedAttributes.value;
    }

    router.get("/products", params, {
        preserveState: true,
        preserveScroll: true,
    });
};

// Debounced apply filters
const debouncedApplyFilters = debounce(applyFilters, 500);

// Watch for filter changes
watch(
    [selectedBrands, selectedAttributes, minPrice, maxPrice],
    () => {
        debouncedApplyFilters();
    },
    { deep: true },
);

// Immediate apply for sort and category
watch([sortBy, selectedCategory], () => {
    applyFilters();
});

// Clear all filters
const clearAllFilters = () => {
    selectedCategory.value = "";
    selectedBrands.value = [];
    selectedAttributes.value = {};
    minPrice.value = "";
    maxPrice.value = "";
    sortBy.value = "latest";
    searchQuery.value = "";
    router.get("/products");
};

// Toggle brand selection
const toggleBrand = (brandId) => {
    const index = selectedBrands.value.indexOf(brandId);
    if (index === -1) {
        selectedBrands.value = [...selectedBrands.value, brandId];
    } else {
        selectedBrands.value = selectedBrands.value.filter(
            (id) => id !== brandId,
        );
    }
};

// Toggle attribute value selection
const toggleAttributeValue = (attributeName, value) => {
    // Create a new object to ensure reactivity
    const newAttributes = { ...selectedAttributes.value };

    if (!newAttributes[attributeName]) {
        newAttributes[attributeName] = [];
    }

    const index = newAttributes[attributeName].indexOf(value);
    if (index === -1) {
        newAttributes[attributeName] = [...newAttributes[attributeName], value];
    } else {
        newAttributes[attributeName] = newAttributes[attributeName].filter(
            (v) => v !== value,
        );
        // Remove attribute key if empty
        if (newAttributes[attributeName].length === 0) {
            delete newAttributes[attributeName];
        }
    }

    // Replace the entire object to trigger reactivity
    selectedAttributes.value = newAttributes;
};

// Select category
const selectCategory = (slug) => {
    selectedCategory.value = slug;
};

// Remove single filter
const removeFilter = (type, value = null, attributeName = null) => {
    switch (type) {
        case "search":
            searchQuery.value = "";
            break;
        case "category":
            selectedCategory.value = "";
            break;
        case "brand":
            selectedBrands.value = selectedBrands.value.filter(
                (id) => id !== value,
            );
            break;
        case "attribute":
            if (attributeName && selectedAttributes.value[attributeName]) {
                const newAttributes = { ...selectedAttributes.value };
                newAttributes[attributeName] = newAttributes[
                    attributeName
                ].filter((v) => v !== value);
                if (newAttributes[attributeName].length === 0) {
                    delete newAttributes[attributeName];
                }
                selectedAttributes.value = newAttributes;
            }
            break;
        case "price":
            minPrice.value = "";
            maxPrice.value = "";
            break;
    }
    applyFilters();
};

// Active filters count
const activeFiltersCount = computed(() => {
    let count = 0;
    if (searchQuery.value) count++;
    if (selectedCategory.value) count++;
    count += selectedBrands.value.length;
    // Count all selected attribute values
    Object.values(selectedAttributes.value).forEach((values) => {
        count += values.length;
    });
    if (minPrice.value || maxPrice.value) count++;
    return count;
});

// Get brand name by ID
const getBrandName = (brandId) => {
    const brand = props.filters.brands.find((b) => b.id === brandId);
    return brand ? brand.brand_name : "";
};

// Get category name by slug
const getCategoryName = (slug) => {
    const findCategory = (categories, targetSlug) => {
        for (const cat of categories) {
            if (cat.slug === targetSlug) return cat.name;
            if (cat.children?.length) {
                const found = findCategory(cat.children, targetSlug);
                if (found) return found;
            }
        }
        return null;
    };
    return findCategory(props.filters.categories, slug) || slug;
};

// Pagination
const goToPage = (page) => {
    const params = { ...props.appliedFilters, page };
    router.get("/products", params, {
        preserveState: true,
        preserveScroll: true,
    });
};

// Format price
const formatPrice = (price) => {
    return new Intl.NumberFormat("en-BD").format(price);
};
</script>

<template>
    <Head title="Products" />
    <FrontendLayout>
        <div class="min-h-screen bg-gray-50">
            <div class="container mx-auto px-4 py-6">
                <!-- Page Header -->
                <div class="mb-6">
                    <h1 class="text-2xl md:text-3xl font-bold text-gray-900">
                        <span v-if="searchQuery"
                            >Search results for "{{ searchQuery }}"</span
                        >
                        <span v-else-if="selectedCategory">{{
                            getCategoryName(selectedCategory)
                        }}</span>
                        <span v-else>All Products</span>
                    </h1>
                </div>

                <!-- Top Bar: Results count, Sort, Mobile Filter -->
                <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
                    <div
                        class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4"
                    >
                        <!-- Results count -->
                        <div class="text-gray-600">
                            <span class="font-semibold text-gray-900"
                                >Showing:</span
                            >
                            ({{ products.total }} Items)
                        </div>

                        <div class="flex items-center gap-4">
                            <!-- Sort dropdown -->
                            <div class="flex items-center gap-2">
                                <label
                                    class="text-sm hidden sm:block text-gray-600 whitespace-nowrap"
                                    >Sort By:</label
                                >
                                <select
                                    v-model="sortBy"
                                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:border-transparent"
                                >
                                    <option
                                        v-for="option in sortOptions"
                                        :key="option.value"
                                        :value="option.value"
                                    >
                                        {{ option.label }}
                                    </option>
                                </select>
                            </div>

                            <!-- Mobile filter toggle -->
                            <button
                                @click="showFilters = !showFilters"
                                class="lg:hidden flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors"
                            >
                                <Filter class="w-4 h-4" />
                                Filters
                                <span
                                    v-if="activeFiltersCount > 0"
                                    class="bg-white text-primary text-xs font-bold px-1.5 py-0.5 rounded-full"
                                >
                                    {{ activeFiltersCount }}
                                </span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Active Filters Chips -->
                <div
                    v-if="activeFiltersCount > 0"
                    class="mb-6 flex flex-wrap items-center gap-2"
                >
                    <span class="text-sm text-gray-600">Active Filters:</span>

                    <!-- Search chip -->
                    <button
                        v-if="searchQuery"
                        @click="removeFilter('search')"
                        class="inline-flex items-center gap-1 px-3 py-1.5 bg-primary/10 text-primary rounded-full text-sm hover:bg-primary/20 transition-colors"
                    >
                        Search: {{ searchQuery }}
                        <X class="w-4 h-4" />
                    </button>

                    <!-- Category chip -->
                    <button
                        v-if="selectedCategory"
                        @click="removeFilter('category')"
                        class="inline-flex items-center gap-1 px-3 py-1.5 bg-blue-100 text-blue-700 rounded-full text-sm hover:bg-blue-200 transition-colors"
                    >
                        {{ getCategoryName(selectedCategory) }}
                        <X class="w-4 h-4" />
                    </button>

                    <!-- Brand chips -->
                    <button
                        v-for="brandId in selectedBrands"
                        :key="brandId"
                        @click="removeFilter('brand', brandId)"
                        class="inline-flex items-center gap-1 px-3 py-1.5 bg-green-100 text-green-700 rounded-full text-sm hover:bg-green-200 transition-colors"
                    >
                        {{ getBrandName(brandId) }}
                        <X class="w-4 h-4" />
                    </button>

                    <!-- Attribute chips -->
                    <template
                        v-for="(values, attributeName) in selectedAttributes"
                        :key="attributeName"
                    >
                        <button
                            v-for="value in values"
                            :key="`${attributeName}-${value}`"
                            @click="
                                removeFilter('attribute', value, attributeName)
                            "
                            class="inline-flex items-center gap-1 px-3 py-1.5 bg-indigo-100 text-indigo-700 rounded-full text-sm hover:bg-indigo-200 transition-colors"
                        >
                            {{ attributeName }}: {{ value }}
                            <X class="w-4 h-4" />
                        </button>
                    </template>

                    <!-- Price chip -->
                    <button
                        v-if="minPrice || maxPrice"
                        @click="removeFilter('price')"
                        class="inline-flex items-center gap-1 px-3 py-1.5 bg-purple-100 text-purple-700 rounded-full text-sm hover:bg-purple-200 transition-colors"
                    >
                        ৳{{ minPrice || 0 }} - ৳{{ maxPrice || "Max" }}
                        <X class="w-4 h-4" />
                    </button>

                    <!-- Clear all -->
                    <button
                        @click="clearAllFilters"
                        class="inline-flex items-center gap-1 px-3 py-1.5 bg-red-100 text-red-700 rounded-full text-sm hover:bg-red-200 transition-colors font-medium"
                    >
                        Clear All
                        <X class="w-4 h-4" />
                    </button>
                </div>

                <div class="flex gap-6">
                    <!-- Sidebar Filters -->
                    <aside
                        :class="[
                            'fixed lg:relative inset-0 z-50 lg:z-auto lg:w-72 lg:flex-shrink-0',
                            showFilters ? 'block' : 'hidden lg:block',
                        ]"
                    >
                        <!-- Mobile overlay -->
                        <div
                            v-if="showFilters"
                            class="fixed inset-0 bg-black/50 lg:hidden"
                            @click="showFilters = false"
                        ></div>

                        <!-- Filter panel -->
                        <div
                            class="fixed lg:relative top-0 left-0 h-full lg:h-auto w-80 lg:w-full bg-white lg:bg-transparent p-6 lg:p-0 overflow-y-auto lg:overflow-visible"
                        >
                            <!-- Mobile header -->
                            <div
                                class="flex items-center justify-between mb-6 lg:hidden"
                            >
                                <h2 class="text-lg font-semibold">Filters</h2>
                                <button
                                    @click="showFilters = false"
                                    class="p-2 hover:bg-gray-100 rounded-lg"
                                >
                                    <X class="w-5 h-5" />
                                </button>
                            </div>

                            <div
                                class="bg-white rounded-lg shadow-sm lg:sticky lg:top-4"
                            >
                                <!-- Categories -->
                                <div class="border-b border-gray-200">
                                    <button
                                        @click="
                                            showCategories = !showCategories
                                        "
                                        class="w-full flex items-center justify-between p-4 hover:bg-gray-50 transition-colors"
                                    >
                                        <span
                                            class="font-semibold text-gray-900"
                                            >Categories</span
                                        >
                                        <ChevronUp
                                            v-if="showCategories"
                                            class="w-5 h-5 text-gray-500"
                                        />
                                        <ChevronDown
                                            v-else
                                            class="w-5 h-5 text-gray-500"
                                        />
                                    </button>
                                    <div
                                        v-show="showCategories"
                                        class="px-4 pb-4"
                                    >
                                        <CategoryFilterTree
                                            :categories="filters.categories"
                                            :selected="selectedCategory"
                                            @select="selectCategory"
                                        />
                                    </div>
                                </div>

                                <!-- Brands -->
                                <div class="border-b border-gray-200">
                                    <button
                                        @click="showBrands = !showBrands"
                                        class="w-full flex items-center justify-between p-4 hover:bg-gray-50 transition-colors"
                                    >
                                        <span
                                            class="font-semibold text-gray-900"
                                            >Brands</span
                                        >
                                        <ChevronUp
                                            v-if="showBrands"
                                            class="w-5 h-5 text-gray-500"
                                        />
                                        <ChevronDown
                                            v-else
                                            class="w-5 h-5 text-gray-500"
                                        />
                                    </button>
                                    <div
                                        v-show="showBrands"
                                        class="px-4 pb-4 max-h-64 overflow-y-auto"
                                    >
                                        <div class="space-y-2">
                                            <label
                                                v-for="brand in filters.brands"
                                                :key="brand.id"
                                                class="flex items-center gap-3 cursor-pointer hover:bg-gray-50 p-1.5 rounded transition-colors"
                                            >
                                                <input
                                                    type="checkbox"
                                                    :checked="
                                                        selectedBrands.includes(
                                                            brand.id,
                                                        )
                                                    "
                                                    @change="
                                                        toggleBrand(brand.id)
                                                    "
                                                    class="w-4 h-4 rounded border-gray-300 text-primary focus:ring-primary"
                                                />
                                                <span
                                                    class="text-sm text-gray-700 flex-1"
                                                    >{{
                                                        brand.brand_name
                                                    }}</span
                                                >
                                                <span
                                                    class="text-xs text-gray-400"
                                                    >({{
                                                        brand.products_count
                                                    }})</span
                                                >
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <!-- Attributes -->
                                <div
                                    v-for="attribute in filters.attributes"
                                    :key="attribute.id"
                                    class="border-b border-gray-200"
                                >
                                    <button
                                        @click="
                                            showAttributes[attribute.name] =
                                                !showAttributes[attribute.name]
                                        "
                                        class="w-full flex items-center justify-between p-4 hover:bg-gray-50 transition-colors"
                                    >
                                        <span
                                            class="font-semibold text-gray-900"
                                            >{{ attribute.name }}</span
                                        >
                                        <ChevronUp
                                            v-if="
                                                showAttributes[attribute.name]
                                            "
                                            class="w-5 h-5 text-gray-500"
                                        />
                                        <ChevronDown
                                            v-else
                                            class="w-5 h-5 text-gray-500"
                                        />
                                    </button>
                                    <div
                                        v-show="showAttributes[attribute.name]"
                                        class="px-4 pb-4 max-h-64 overflow-y-auto"
                                    >
                                        <div class="space-y-2">
                                            <label
                                                v-for="attrValue in attribute.values"
                                                :key="attrValue.id"
                                                class="flex items-center gap-3 cursor-pointer hover:bg-gray-50 p-1.5 rounded transition-colors"
                                            >
                                                <input
                                                    type="checkbox"
                                                    :checked="
                                                        selectedAttributes[
                                                            attribute.name
                                                        ]?.includes(
                                                            attrValue.value,
                                                        )
                                                    "
                                                    @change="
                                                        toggleAttributeValue(
                                                            attribute.name,
                                                            attrValue.value,
                                                        )
                                                    "
                                                    class="w-4 h-4 rounded border-gray-300 text-primary focus:ring-primary"
                                                />
                                                <div
                                                    class="flex items-center gap-2 flex-1"
                                                >
                                                    <span
                                                        v-if="
                                                            isColorAttribute(
                                                                attribute.name,
                                                            ) && attrValue.color
                                                        "
                                                        class="w-4 h-4 rounded-full border border-gray-300"
                                                        :style="{
                                                            backgroundColor:
                                                                attrValue.color,
                                                        }"
                                                    ></span>
                                                    <span
                                                        class="text-sm text-gray-700"
                                                        >{{
                                                            attrValue.value
                                                        }}</span
                                                    >
                                                </div>
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <!-- Price Range -->
                                <div>
                                    <button
                                        @click="showPrice = !showPrice"
                                        class="w-full flex items-center justify-between p-4 hover:bg-gray-50 transition-colors"
                                    >
                                        <span
                                            class="font-semibold text-gray-900"
                                            >Price Range</span
                                        >
                                        <ChevronUp
                                            v-if="showPrice"
                                            class="w-5 h-5 text-gray-500"
                                        />
                                        <ChevronDown
                                            v-else
                                            class="w-5 h-5 text-gray-500"
                                        />
                                    </button>
                                    <div v-show="showPrice" class="px-4 pb-4">
                                        <div class="text-xs text-gray-500 mb-3">
                                            Range: ৳{{
                                                formatPrice(
                                                    filters.priceRange.min,
                                                )
                                            }}
                                            - ৳{{
                                                formatPrice(
                                                    filters.priceRange.max,
                                                )
                                            }}
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <div class="flex-1">
                                                <input
                                                    v-model="minPrice"
                                                    type="number"
                                                    placeholder="Min"
                                                    :min="
                                                        filters.priceRange.min
                                                    "
                                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-orange-400 focus:border-transparent"
                                                />
                                            </div>
                                            <span class="text-gray-400">-</span>
                                            <div class="flex-1">
                                                <input
                                                    v-model="maxPrice"
                                                    type="number"
                                                    placeholder="Max"
                                                    :max="
                                                        filters.priceRange.max
                                                    "
                                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-orange-400 focus:border-transparent"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Mobile Apply button -->
                            <div class="mt-6 lg:hidden">
                                <button
                                    @click="showFilters = false"
                                    class="w-full py-3 bg-primary text-white rounded-lg font-medium hover:bg-primary/90 transition-colors"
                                >
                                    Apply Filters
                                </button>
                            </div>
                        </div>
                    </aside>

                    <!-- Product Grid -->
                    <div class="flex-1">
                        <!-- Products -->
                        <div
                            v-if="products.data.length > 0"
                            class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-4 md:gap-6"
                        >
                            <ProductCard
                                v-for="product in products.data"
                                :key="product.id"
                                :product="product"
                            />
                        </div>

                        <!-- No results -->
                        <div
                            v-else
                            class="bg-white rounded-lg shadow-sm p-12 text-center"
                        >
                            <div
                                class="w-24 h-24 mx-auto mb-6 bg-gray-100 rounded-full flex items-center justify-center"
                            >
                                <SlidersHorizontal
                                    class="w-12 h-12 text-gray-400"
                                />
                            </div>
                            <h3
                                class="text-xl font-semibold text-gray-900 mb-2"
                            >
                                No products found
                            </h3>
                            <p class="text-gray-500 mb-6">
                                Try adjusting your filters or search terms
                            </p>
                            <button
                                @click="clearAllFilters"
                                class="px-6 py-2.5 bg-primary text-white rounded-lg font-medium hover:bg-primary/90 transition-colors"
                            >
                                Clear All Filters
                            </button>
                        </div>

                        <!-- Pagination -->
                        <div
                            v-if="products.last_page > 1"
                            class="mt-8 flex justify-center"
                        >
                            <nav class="flex items-center gap-1">
                                <!-- Previous -->
                                <button
                                    @click="goToPage(products.current_page - 1)"
                                    :disabled="products.current_page === 1"
                                    class="px-3 py-2 border border-gray-300 rounded-lg text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50 transition-colors"
                                >
                                    Previous
                                </button>

                                <!-- Page numbers -->
                                <template
                                    v-for="page in products.last_page"
                                    :key="page"
                                >
                                    <button
                                        v-if="
                                            page === 1 ||
                                            page === products.last_page ||
                                            (page >=
                                                products.current_page - 2 &&
                                                page <=
                                                    products.current_page + 2)
                                        "
                                        @click="goToPage(page)"
                                        :class="[
                                            'w-10 h-10 rounded-lg text-sm font-medium transition-colors',
                                            page === products.current_page
                                                ? 'bg-primary text-white'
                                                : 'border border-gray-300 hover:bg-gray-50',
                                        ]"
                                    >
                                        {{ page }}
                                    </button>
                                    <span
                                        v-else-if="
                                            page ===
                                                products.current_page - 3 ||
                                            page === products.current_page + 3
                                        "
                                        class="px-2 text-gray-400"
                                    >
                                        ...
                                    </span>
                                </template>

                                <!-- Next -->
                                <button
                                    @click="goToPage(products.current_page + 1)"
                                    :disabled="
                                        products.current_page ===
                                        products.last_page
                                    "
                                    class="px-3 py-2 border border-gray-300 rounded-lg text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50 transition-colors"
                                >
                                    Next
                                </button>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </FrontendLayout>
</template>
