<script setup>
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import {
    Search,
    X,
    Filter,
    ChevronDown,
    ChevronUp,
    RefreshCcw,
    SlidersHorizontal
} from 'lucide-vue-next';

const props = defineProps({
    filters: { type: Object, default: () => ({}) },
    categories: { type: Array, default: () => [] },
    brands: { type: Array, default: () => [] }
});

const localFilters = ref({
    search: props.filters.search || '',
    category_id: props.filters.category_id || '',
    brand_id: props.filters.brand_id || '',
    status: props.filters.status || '',
    type: props.filters.type || '',
    stock_status: props.filters.stock_status || '',
    min_price: props.filters.min_price || '',
    max_price: props.filters.max_price || '',
});

const sections = ref({
    categories: true,
    brands: true,
    price: true,
    status: true,
    advanced: false
});

// Count active filters for badge
const activeFilterCount = ref(() => {
    return Object.entries(localFilters.value)
        .filter(([k, v]) => k !== 'search' && v !== '').length;
});

const toggleSection = (section) => {
    sections.value[section] = !sections.value[section];
};

const applyFilters = () => {
    router.get(route('admin.products.index'), {
        ...localFilters.value,
        page: 1
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true
    });
};

const clearFilters = () => {
    localFilters.value = {
        search: '',
        category_id: '',
        brand_id: '',
        status: '',
        type: '',
        stock_status: '',
        min_price: '',
        max_price: '',
    };
    applyFilters();
};

// Debounce search
let timeout = null;
watch(() => localFilters.value.search, () => {
    clearTimeout(timeout);
    timeout = setTimeout(() => applyFilters(), 450);
});

// Auto-apply for select/radio fields
['category_id', 'brand_id', 'status', 'type', 'stock_status'].forEach(field => {
    watch(() => localFilters.value[field], () => applyFilters());
});
</script>

<template>
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden flex flex-col sticky top-4 max-h-[calc(100vh-5rem)]">

        <!-- Header -->
        <div class="px-4 py-3.5 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between bg-gray-50/60 dark:bg-gray-900/40 flex-shrink-0">
            <div class="flex items-center gap-2">
                <div class="w-7 h-7 rounded-lg bg-blue-600 flex items-center justify-center">
                    <SlidersHorizontal class="w-3.5 h-3.5 text-white" />
                </div>
                <span class="font-black text-gray-900 dark:text-gray-100 text-sm">Filters</span>
            </div>
            <button
                @click="clearFilters"
                class="inline-flex items-center gap-1 text-xs font-semibold text-red-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 px-2.5 py-1.5 rounded-xl transition-all active:scale-95"
            >
                <RefreshCcw class="w-3 h-3" />
                Clear All
            </button>
        </div>

        <div class="flex-1 overflow-y-auto custom-scrollbar">

            <!-- Search -->
            <div class="p-4 border-b border-gray-100 dark:border-gray-700">
                <div class="relative">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400" />
                    <input
                        v-model="localFilters.search"
                        type="text"
                        placeholder="Search products..."
                        class="w-full pl-9 pr-9 py-2 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-sm text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 outline-none transition-all"
                    />
                    <button v-if="localFilters.search" @click="localFilters.search = ''"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                        <X class="w-3.5 h-3.5" />
                    </button>
                </div>
            </div>

            <!-- Categories -->
            <div class="border-b border-gray-100 dark:border-gray-700">
                <button @click="toggleSection('categories')"
                    class="w-full px-4 py-3 flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-900/40 transition-colors">
                    <span class="text-sm font-bold text-gray-800 dark:text-gray-200">Categories</span>
                    <ChevronDown class="w-4 h-4 text-gray-400 transition-transform duration-200" :class="{ 'rotate-180': sections.categories }" />
                </button>
                <div v-show="sections.categories" class="px-4 pb-3">
                    <select v-model="localFilters.category_id"
                        class="w-full py-2 px-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-sm text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 outline-none cursor-pointer">
                        <option value="">All Categories</option>
                        <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                    </select>
                </div>
            </div>

            <!-- Brands -->
            <div class="border-b border-gray-100 dark:border-gray-700">
                <button @click="toggleSection('brands')"
                    class="w-full px-4 py-3 flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-900/40 transition-colors">
                    <span class="text-sm font-bold text-gray-800 dark:text-gray-200">Brands</span>
                    <ChevronDown class="w-4 h-4 text-gray-400 transition-transform duration-200" :class="{ 'rotate-180': sections.brands }" />
                </button>
                <div v-show="sections.brands" class="px-4 pb-3 space-y-1 max-h-44 overflow-y-auto custom-scrollbar">
                    <!-- All brands option -->
                    <label class="flex items-center gap-2.5 group cursor-pointer py-1.5 px-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-900/50 transition-colors">
                        <input v-model="localFilters.brand_id" type="radio" value=""
                               class="w-3.5 h-3.5 text-blue-600 border-gray-300 focus:ring-blue-500 cursor-pointer" />
                        <span class="text-sm text-gray-600 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-gray-100 transition-colors">All Brands</span>
                    </label>
                    <label v-for="brand in brands" :key="brand.id"
                           class="flex items-center gap-2.5 group cursor-pointer py-1.5 px-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-900/50 transition-colors"
                           :class="{ 'bg-blue-50 dark:bg-blue-900/20': localFilters.brand_id == brand.id }">
                        <input v-model="localFilters.brand_id" type="radio" :value="brand.id"
                               class="w-3.5 h-3.5 text-blue-600 border-gray-300 focus:ring-blue-500 cursor-pointer" />
                        <span class="text-sm transition-colors"
                              :class="localFilters.brand_id == brand.id
                                ? 'text-blue-700 dark:text-blue-400 font-semibold'
                                : 'text-gray-600 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-gray-100'">
                            {{ brand.name }}
                        </span>
                    </label>
                </div>
            </div>

            <!-- Price Range -->
            <div class="border-b border-gray-100 dark:border-gray-700">
                <button @click="toggleSection('price')"
                    class="w-full px-4 py-3 flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-900/40 transition-colors">
                    <span class="text-sm font-bold text-gray-800 dark:text-gray-200">Price Range</span>
                    <ChevronDown class="w-4 h-4 text-gray-400 transition-transform duration-200" :class="{ 'rotate-180': sections.price }" />
                </button>
                <div v-show="sections.price" class="px-4 pb-4 space-y-3">
                    <div class="flex items-center gap-2">
                        <div class="relative flex-1">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs font-bold">৳</span>
                            <input v-model="localFilters.min_price" type="number" placeholder="Min"
                                class="w-full pl-7 pr-2 py-2 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 outline-none" />
                        </div>
                        <div class="w-3 h-px bg-gray-300 flex-shrink-0"></div>
                        <div class="relative flex-1">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs font-bold">৳</span>
                            <input v-model="localFilters.max_price" type="number" placeholder="Max"
                                class="w-full pl-7 pr-2 py-2 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 outline-none" />
                        </div>
                    </div>
                    <button @click="applyFilters"
                        class="w-full py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition-all shadow-sm shadow-blue-500/20 active:scale-95">
                        Apply Price Filter
                    </button>
                </div>
            </div>

            <!-- Status & Inventory -->
            <div class="border-b border-gray-100 dark:border-gray-700">
                <button @click="toggleSection('status')"
                    class="w-full px-4 py-3 flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-900/40 transition-colors">
                    <span class="text-sm font-bold text-gray-800 dark:text-gray-200">Status & Availability</span>
                    <ChevronDown class="w-4 h-4 text-gray-400 transition-transform duration-200" :class="{ 'rotate-180': sections.status }" />
                </button>
                <div v-show="sections.status" class="px-4 pb-4 space-y-4">
                    <!-- Publishing Status -->
                    <div class="space-y-2">
                        <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">Publishing</p>
                        <select v-model="localFilters.status"
                            class="w-full py-2 px-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 outline-none cursor-pointer">
                            <option value="">All Status</option>
                            <option value="Published">✅ Published</option>
                            <option value="Unpublished">⏸️ Draft / Unpublished</option>
                        </select>
                    </div>
                    <!-- Stock Availability -->
                    <div class="space-y-2">
                        <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">Availability</p>
                        <div class="flex gap-2">
                            <button @click="localFilters.stock_status = ''"
                                :class="[localFilters.stock_status === '' ? 'bg-blue-600 text-white shadow-sm shadow-blue-500/20' : 'bg-gray-100 dark:bg-gray-900 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-800']"
                                class="flex-1 py-2 rounded-xl text-xs font-semibold transition-all active:scale-95">
                                All
                            </button>
                            <button @click="localFilters.stock_status = 'in_stock'"
                                :class="[localFilters.stock_status === 'in_stock' ? 'bg-green-600 text-white shadow-sm shadow-green-500/20' : 'bg-gray-100 dark:bg-gray-900 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-800']"
                                class="flex-1 py-2 rounded-xl text-xs font-semibold transition-all active:scale-95">
                                In Stock
                            </button>
                            <button @click="localFilters.stock_status = 'out_of_stock'"
                                :class="[localFilters.stock_status === 'out_of_stock' ? 'bg-red-500 text-white shadow-sm shadow-red-500/20' : 'bg-gray-100 dark:bg-gray-900 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-800']"
                                class="flex-1 py-2 rounded-xl text-xs font-semibold transition-all active:scale-95">
                                Out
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Product Type -->
            <div class="border-b border-gray-100 dark:border-gray-700">
                <button @click="toggleSection('advanced')"
                    class="w-full px-4 py-3 flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-900/40 transition-colors">
                    <span class="text-sm font-bold text-gray-800 dark:text-gray-200">Product Type</span>
                    <ChevronDown class="w-4 h-4 text-gray-400 transition-transform duration-200" :class="{ 'rotate-180': sections.advanced }" />
                </button>
                <div v-show="sections.advanced" class="px-4 pb-3">
                    <select v-model="localFilters.type"
                        class="w-full py-2 px-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 outline-none cursor-pointer">
                        <option value="">All Types</option>
                        <option value="simple">Simple Product</option>
                        <option value="variable">Variable Product</option>
                    </select>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 3px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
.dark .custom-scrollbar::-webkit-scrollbar-thumb { background: #334155; }
</style>
