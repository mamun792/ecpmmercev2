<script setup>
import { ref, watch, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import { 
    Search, 
    X, 
    Filter,
    ChevronDown,
    ChevronUp,
    RefreshCcw 
} from 'lucide-vue-next';

const props = defineProps({
    filters: {
        type: Object,
        default: () => ({})
    },
    categories: {
        type: Array,
        default: () => []
    },
    brands: {
        type: Array,
        default: () => []
    }
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

const toggleSection = (section) => {
    sections.value[section] = !sections.value[section];
};

const applyFilters = () => {
    router.get(route('admin.products.index'), {
        ...localFilters.value,
        page: 1 // Reset to first page
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

// Simple debounce for search
let timeout = null;
watch(() => localFilters.value.search, () => {
    clearTimeout(timeout);
    timeout = setTimeout(() => {
        applyFilters();
    }, 500);
});

// Auto-apply for other fields
const autoApplyFields = [
    'category_id', 'brand_id', 'status', 'type', 'stock_status'
];

autoApplyFields.forEach(field => {
    watch(() => localFilters.value[field], () => {
        applyFilters();
    });
});

</script>

<template>
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden flex flex-col h-full sticky top-6">
        <!-- Header -->
        <div class="p-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between bg-gray-50/50 dark:bg-gray-900/50">
            <div class="flex items-center gap-2">
                <Filter class="w-4 h-4 text-blue-600 dark:text-blue-400" />
                <h3 class="font-bold text-gray-900 dark:text-gray-100">Filters</h3>
            </div>
            <button 
                @click="clearFilters"
                class="text-xs font-semibold text-blue-600 dark:text-blue-400 hover:text-blue-700 underline flex items-center gap-1"
            >
                <RefreshCcw class="w-3 h-3" />
                Clear All
            </button>
        </div>

        <div class="flex-1 overflow-y-auto custom-scrollbar">
            <!-- Search -->
            <div class="p-4 border-b border-gray-100 dark:border-gray-700">
                <div class="relative group">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 group-focus-within:text-blue-500 transition-colors" />
                    <input 
                        v-model="localFilters.search"
                        type="text" 
                        placeholder="Search products..."
                        class="w-full pl-10 pr-4 py-2 bg-gray-50 dark:bg-gray-900 border-none rounded-xl text-sm focus:ring-2 focus:ring-blue-500/20 transition-all"
                    >
                </div>
            </div>

            <!-- Categories -->
            <div class="border-b border-gray-100 dark:border-gray-700">
                <button 
                    @click="toggleSection('categories')"
                    class="w-full p-4 flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-900/50 transition-colors"
                >
                    <span class="text-sm font-bold text-gray-900 dark:text-gray-100">Categories</span>
                    <ChevronDown v-if="!sections.categories" class="w-4 h-4 text-gray-400" />
                    <ChevronUp v-else class="w-4 h-4 text-gray-400" />
                </button>
                <div v-show="sections.categories" class="px-4 pb-4 space-y-2">
                    <select 
                        v-model="localFilters.category_id"
                        class="w-full py-2 bg-gray-50 dark:bg-gray-900 border-none rounded-xl text-sm focus:ring-2 focus:ring-blue-500/20"
                    >
                        <option value="">All Categories</option>
                        <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                            {{ cat.name }}
                        </option>
                    </select>
                </div>
            </div>

            <!-- Brands -->
            <div class="border-b border-gray-100 dark:border-gray-700">
                <button 
                    @click="toggleSection('brands')"
                    class="w-full p-4 flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-900/50 transition-colors"
                >
                    <span class="text-sm font-bold text-gray-900 dark:text-gray-100">Brands</span>
                    <ChevronDown v-if="!sections.brands" class="w-4 h-4 text-gray-400" />
                    <ChevronUp v-else class="w-4 h-4 text-gray-400" />
                </button>
                <div v-show="sections.brands" class="px-4 pb-4 space-y-2 max-h-48 overflow-y-auto custom-scrollbar">
                    <div v-for="brand in brands" :key="brand.id" class="flex items-center gap-2 group cursor-pointer">
                        <input 
                            v-model="localFilters.brand_id"
                            type="radio" 
                            :id="'brand-' + brand.id" 
                            :value="brand.id"
                            class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500 rounded-full"
                        >
                        <label :for="'brand-' + brand.id" class="text-sm text-gray-600 dark:text-gray-400 cursor-pointer group-hover:text-blue-600 transition-colors flex-1">
                            {{ brand.name }}
                        </label>
                    </div>
                </div>
            </div>

            <!-- Price Range -->
            <div class="border-b border-gray-100 dark:border-gray-700">
                <button 
                    @click="toggleSection('price')"
                    class="w-full p-4 flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-900/50 transition-colors"
                >
                    <span class="text-sm font-bold text-gray-900 dark:text-gray-100">Price Range</span>
                    <ChevronDown v-if="!sections.price" class="w-4 h-4 text-gray-400" />
                    <ChevronUp v-else class="w-4 h-4 text-gray-400" />
                </button>
                <div v-show="sections.price" class="px-4 pb-4">
                    <div class="flex items-center gap-2">
                        <div class="relative flex-1">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs">৳</span>
                            <input 
                                v-model="localFilters.min_price"
                                type="number" 
                                placeholder="Min"
                                class="w-full pl-7 pr-4 py-2 bg-gray-50 dark:bg-gray-900 border-none rounded-xl text-xs focus:ring-2 focus:ring-blue-500/20"
                            >
                        </div>
                        <span class="text-gray-400 text-xs">-</span>
                        <div class="relative flex-1">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs">৳</span>
                            <input 
                                v-model="localFilters.max_price"
                                type="number" 
                                placeholder="Max"
                                class="w-full pl-7 pr-4 py-2 bg-gray-50 dark:bg-gray-900 border-none rounded-xl text-xs focus:ring-2 focus:ring-blue-500/20"
                            >
                        </div>
                    </div>
                    <button 
                        @click="applyFilters"
                        class="w-full mt-3 py-2 bg-gray-100 dark:bg-gray-900 text-gray-700 dark:text-gray-300 rounded-xl text-xs font-bold hover:bg-blue-600 hover:text-white transition-all shadow-sm active:scale-95"
                    >
                        Apply Price
                    </button>
                </div>
            </div>

            <!-- Status -->
            <div class="border-b border-gray-100 dark:border-gray-700">
                <button 
                    @click="toggleSection('status')"
                    class="w-full p-4 flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-900/50 transition-colors"
                >
                    <span class="text-sm font-bold text-gray-900 dark:text-gray-100">Status & Inventory</span>
                    <ChevronDown v-if="!sections.status" class="w-4 h-4 text-gray-400" />
                    <ChevronUp v-else class="w-4 h-4 text-gray-400" />
                </button>
                <div v-show="sections.status" class="px-4 pb-4 space-y-4">
                    <!-- Status -->
                    <div class="space-y-2">
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Publishing</p>
                        <select 
                            v-model="localFilters.status"
                            class="w-full py-2 bg-gray-50 dark:bg-gray-900 border-none rounded-xl text-xs focus:ring-2 focus:ring-blue-500/20"
                        >
                            <option value="">All Status</option>
                            <option value="Published">Published</option>
                            <option value="Unpublished">Unpublished</option>
                        </select>
                    </div>
                    <!-- Stock Status -->
                    <div class="space-y-2">
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Availability</p>
                        <div class="flex flex-wrap gap-2">
                            <button 
                                @click="localFilters.stock_status = ''"
                                :class="[localFilters.stock_status === '' ? 'bg-blue-600 text-white shadow-md shadow-blue-500/20' : 'bg-gray-100 dark:bg-gray-900 text-gray-600 dark:text-gray-400 hover:bg-gray-200']"
                                class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all"
                            >
                                All
                            </button>
                            <button 
                                @click="localFilters.stock_status = 'in_stock'"
                                :class="[localFilters.stock_status === 'in_stock' ? 'bg-blue-600 text-white shadow-md shadow-blue-500/20' : 'bg-gray-100 dark:bg-gray-900 text-gray-600 dark:text-gray-400 hover:bg-gray-200']"
                                class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all"
                            >
                                In Stock
                            </button>
                            <button 
                                @click="localFilters.stock_status = 'out_of_stock'"
                                :class="[localFilters.stock_status === 'out_of_stock' ? 'bg-blue-600 text-white shadow-md shadow-blue-500/20' : 'bg-gray-100 dark:bg-gray-900 text-gray-600 dark:text-gray-400 hover:bg-gray-200']"
                                class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all"
                            >
                                Out of Stock
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Advanced -->
            <div class="border-b border-gray-100 dark:border-gray-700">
                <button 
                    @click="toggleSection('advanced')"
                    class="w-full p-4 flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-900/50 transition-colors"
                >
                    <span class="text-sm font-bold text-gray-900 dark:text-gray-100">Product Type</span>
                    <ChevronDown v-if="!sections.advanced" class="w-4 h-4 text-gray-400" />
                    <ChevronUp v-else class="w-4 h-4 text-gray-400" />
                </button>
                <div v-show="sections.advanced" class="px-4 pb-4 space-y-2">
                    <select 
                        v-model="localFilters.type"
                        class="w-full py-2 bg-gray-50 dark:bg-gray-900 border-none rounded-xl text-xs focus:ring-2 focus:ring-blue-500/20"
                    >
                        <option value="">All Types</option>
                        <option value="simple">Simple Product</option>
                        <option value="variable">Variable Product</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="p-4 bg-gray-50/50 dark:bg-gray-900/50 border-t border-gray-100 dark:border-gray-700">
            <p class="text-[10px] text-gray-400 text-center font-medium">Enterprise Catalog v2.0</p>
        </div>
    </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #e2e8f0;
    border-radius: 10px;
}
.dark .custom-scrollbar::-webkit-scrollbar-thumb {
    background: #334155;
}
</style>
