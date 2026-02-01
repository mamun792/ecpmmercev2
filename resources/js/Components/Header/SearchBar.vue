<script setup>
import { ref, watch, onMounted, onUnmounted } from "vue";
import { router } from "@inertiajs/vue3";
import axios from "axios";
import { Search, X, Loader2 } from "lucide-vue-next";
import debounce from "lodash/debounce";

const props = defineProps({
    autoFocus: {
        type: Boolean,
        default: false,
    },
    variant: {
        type: String,
        default: "light", // 'light' or 'dark'
    },
});

const emit = defineEmits(["close"]);

const searchQuery = ref("");
const suggestions = ref([]);
const showSuggestions = ref(false);
const loading = ref(false);
const searchContainerRef = ref(null);
const inputRef = ref(null);

// Debounced search function
const fetchSuggestions = debounce(async () => {
    if (searchQuery.value.length < 2) {
        suggestions.value = [];
        showSuggestions.value = false;
        return;
    }

    loading.value = true;
    try {
        const response = await axios.get("/products/search-suggestions", {
            params: { q: searchQuery.value },
        });
        suggestions.value = response.data.suggestions;
        showSuggestions.value = true;
    } catch (error) {
        console.error("Search error:", error);
        suggestions.value = [];
    } finally {
        loading.value = false;
    }
}, 300);

watch(searchQuery, () => {
    fetchSuggestions();
});

// Handle search submit (Enter or button click)
const handleSearch = () => {
    if (searchQuery.value.trim()) {
        router.get("/products", { search: searchQuery.value.trim() });
        showSuggestions.value = false;
        emit("close");
    }
};

// Navigate to product
const goToProduct = (slug) => {
    router.get(`/product/${slug}`);
    showSuggestions.value = false;
    searchQuery.value = "";
    emit("close");
};

// Clear search
const clearSearch = () => {
    searchQuery.value = "";
    suggestions.value = [];
    showSuggestions.value = false;
};

// Handle click outside to close suggestions
const handleClickOutside = (event) => {
    if (
        searchContainerRef.value &&
        !searchContainerRef.value.contains(event.target)
    ) {
        showSuggestions.value = false;
    }
};

// Handle escape key to close suggestions
const handleEscapeKey = (event) => {
    if (event.key === "Escape") {
        showSuggestions.value = false;
    }
};

// Format price
const formatPrice = (price) => {
    return new Intl.NumberFormat("en-BD", {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(price);
};

// Add event listeners on mount
onMounted(() => {
    document.addEventListener("click", handleClickOutside);
    document.addEventListener("keydown", handleEscapeKey);

    if (props.autoFocus && inputRef.value) {
        inputRef.value.focus();
    }
});

// Remove event listeners on unmount
onUnmounted(() => {
    document.removeEventListener("click", handleClickOutside);
    document.removeEventListener("keydown", handleEscapeKey);
});
</script>

<template>
    <div ref="searchContainerRef" class="relative flex-1 w-full">
        <div class="relative group">
            <div
                class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none"
            >
                <Search
                    class="w-5 h-5 transition-colors"
                    :class="
                        variant === 'dark' ? 'text-gray-400' : 'text-gray-400'
                    "
                />
            </div>
            <input
                ref="inputRef"
                v-model="searchQuery"
                @keyup.enter="handleSearch"
                @focus="searchQuery.length >= 2 && (showSuggestions = true)"
                type="text"
                placeholder="Search..."
                class="w-full pl-11 pr-12 py-2.5 transition-all outline-none"
                :class="[
                    variant === 'dark'
                        ? 'bg-[#2a2a2a] text-white placeholder-gray-500 rounded-full focus:bg-[#333]'
                        : 'bg-white text-gray-900 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent',
                ]"
            />

            <!-- Loading indicator -->
            <div
                v-if="loading"
                class="absolute right-4 top-1/2 -translate-y-1/2"
            >
                <Loader2 class="w-5 h-5 text-gray-400 animate-spin" />
            </div>

            <!-- Clear button -->
            <button
                v-if="searchQuery && !loading"
                @click="clearSearch"
                class="absolute right-14 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-200 transition-colors"
            >
                <X class="w-5 h-5" />
            </button>

            <!-- Search Button -->
            <button
                @click="handleSearch"
                class="absolute right-1.5 top-1/2 -translate-y-1/2 bg-primary hover:bg-primary/90 text-white p-1.5 rounded-full transition-colors flex items-center justify-center"
                aria-label="Search"
            >
                <Search class="w-4 h-4" />
            </button>
        </div>

        <!-- Suggestions dropdown -->
        <div
            v-if="showSuggestions && suggestions.length > 0"
            class="absolute z-50 w-full mt-2 bg-white border border-gray-200 rounded-lg shadow-xl max-h-[400px] overflow-y-auto"
        >
            <div
                v-for="product in suggestions"
                :key="product.id"
                @click="goToProduct(product.slug)"
                class="flex items-center gap-3 p-3 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-b-0 transition-colors"
            >
                <div
                    class="w-14 h-14 flex-shrink-0 bg-gray-100 rounded-lg overflow-hidden"
                >
                    <img
                        :src="product.feature_image"
                        :alt="product.name"
                        class="w-full h-full object-contain p-1"
                    />
                </div>
                <div class="flex-1 min-w-0">
                    <h4 class="text-sm font-medium text-gray-900 truncate">
                        {{ product.name }}
                    </h4>
                    <p
                        v-if="product.category"
                        class="text-xs text-gray-500 mt-0.5"
                    >
                        {{ product.category.name }}
                    </p>
                </div>
                <div class="text-right flex-shrink-0">
                    <p class="text-sm font-bold text-primary">
                        ৳{{ formatPrice(product.price) }}
                    </p>
                    <p
                        v-if="
                            product.previous_price &&
                            product.previous_price > product.price
                        "
                        class="text-xs text-gray-400 line-through"
                    >
                        ৳{{ formatPrice(product.previous_price) }}
                    </p>
                </div>
            </div>

            <!-- View all results link -->
            <div
                @click="handleSearch"
                class="p-3 text-center text-primary font-medium hover:bg-primary/5 cursor-pointer transition-colors border-t border-gray-100"
            >
                View all results for "{{ searchQuery }}" →
            </div>
        </div>

        <!-- No results message -->
        <div
            v-if="
                showSuggestions &&
                suggestions.length === 0 &&
                searchQuery.length >= 2 &&
                !loading
            "
            class="absolute z-50 w-full mt-2 bg-white border border-gray-200 rounded-lg shadow-xl p-8 text-center"
        >
            <div class="flex flex-col items-center gap-3">
                <div class="p-3 bg-gray-50 rounded-full">
                    <Search class="w-8 h-8 text-gray-300" />
                </div>
                <div>
                    <p class="text-gray-900 font-medium">No products found</p>
                    <p class="text-gray-500 text-sm mt-1">
                        We couldn't find anything matching "{{ searchQuery }}"
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
