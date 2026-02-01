<script setup>
import { Head, Link, useForm } from "@inertiajs/vue3";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { ref, watch } from "vue";
import { toast } from "@steveyuowo/vue-hot-toast";
import axios from "axios";
import {
    Save,
    ArrowLeft,
    Search,
    Type,
    ToggleLeft,
    X,
    Image as ImageIcon,
    Tag,
} from "lucide-vue-next";

const form = useForm({
    name: "",
    slug: "",
    status: true,
    order_number: 0,
    banner: null,
    product_ids: [],
});

const bannerPreview = ref(null);

const handleBannerChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.banner = file;
        bannerPreview.value = URL.createObjectURL(file);
    }
};

const searchQuery = ref("");
const searchResults = ref([]);
const isSearching = ref(false);
const selectedProducts = ref([]);

// Debounced search
let searchTimer = null;
watch(searchQuery, (query) => {
    clearTimeout(searchTimer);
    if (!query || query.length < 2) {
        searchResults.value = [];
        return;
    }

    isSearching.value = true;
    searchTimer = setTimeout(async () => {
        try {
            const response = await axios.get(
                route("admin.product-groups.search-products"),
                {
                    params: { query },
                },
            );
            // Filter out already selected products
            searchResults.value = response.data.filter(
                (p) => !form.product_ids.includes(p.id),
            );
        } catch (error) {
            console.error("Search failed", error);
        } finally {
            isSearching.value = false;
        }
    }, 400);
});

const addProduct = (product) => {
    if (!form.product_ids.includes(product.id)) {
        form.product_ids.push(product.id);
        selectedProducts.value.push(product);
        searchQuery.value = "";
        searchResults.value = [];
    }
};

const removeProduct = (productId) => {
    form.product_ids = form.product_ids.filter((id) => id !== productId);
    selectedProducts.value = selectedProducts.value.filter(
        (p) => p.id !== productId,
    );
};

const submit = () => {
    form.post(route("admin.product-groups.store"), {
        onSuccess: () => toast.success("Product group created successfully"),
    });
};

const generateSlug = () => {
    form.slug = form.name
        .toLowerCase()
        .replace(/ /g, "-")
        .replace(/[^\w-]+/g, "");
};
</script>

<template>
    <Head title="Create Product Group" />
    <AdminLayout>
        <div class="p-6 max-w-4xl mx-auto">
            <div class="flex items-center gap-4 mb-8">
                <Link
                    :href="route('admin.product-groups.index')"
                    class="p-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg text-gray-600 dark:text-gray-400 hover:text-blue-600 transition-colors"
                >
                    <ArrowLeft size="20" />
                </Link>
                <div>
                    <h1
                        class="text-2xl font-bold text-gray-800 dark:text-gray-100"
                    >
                        Create Product Group
                    </h1>
                    <p class="text-gray-500 dark:text-gray-400 text-sm">
                        Fill in the details to create a new group.
                    </p>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Left Column: Main Info -->
                    <div class="md:col-span-2 space-y-6">
                        <div
                            class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 space-y-4"
                        >
                            <div
                                class="flex items-center gap-2 mb-2 text-blue-600 dark:text-blue-400 font-semibold"
                            >
                                <Type size="18" />
                                <span>Basic Information</span>
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                                    >Group Name</label
                                >
                                <input
                                    v-model="form.name"
                                    @input="generateSlug"
                                    type="text"
                                    placeholder="e.g. Featured Products"
                                    class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition-all dark:text-white"
                                />
                                <span
                                    v-if="form.errors.name"
                                    class="text-red-500 text-xs mt-1"
                                    >{{ form.errors.name }}</span
                                >
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                                    >Slug (URL friendly)</label
                                >
                                <input
                                    v-model="form.slug"
                                    type="text"
                                    placeholder="featured-products"
                                    class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition-all dark:text-white"
                                />
                                <span
                                    v-if="form.errors.slug"
                                    class="text-red-500 text-xs mt-1"
                                    >{{ form.errors.slug }}</span
                                >
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                                    >Order Number</label
                                >
                                <input
                                    v-model="form.order_number"
                                    type="number"
                                    placeholder="0"
                                    class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition-all dark:text-white"
                                />
                                <span
                                    v-if="form.errors.order_number"
                                    class="text-red-500 text-xs mt-1"
                                    >{{ form.errors.order_number }}</span
                                >
                            </div>
                        </div>

                        <!-- Product Selector -->
                        <div
                            class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 space-y-4"
                        >
                            <div
                                class="flex items-center justify-between gap-2 mb-2"
                            >
                                <div
                                    class="flex items-center gap-2 text-blue-600 dark:text-blue-400 font-semibold"
                                >
                                    <Tag size="18" />
                                    <span>Select Products</span>
                                </div>
                                <span
                                    class="bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-300 px-2 py-0.5 rounded text-xs"
                                >
                                    {{ form.product_ids.length }} products
                                    selected
                                </span>
                            </div>

                            <div class="relative">
                                <div
                                    class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400"
                                >
                                    <Search size="18" />
                                </div>
                                <input
                                    v-model="searchQuery"
                                    type="text"
                                    placeholder="Search by name or product code..."
                                    class="w-full pl-10 pr-4 py-2 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition-all dark:text-white"
                                />

                                <!-- Search Results Dropdown -->
                                <div
                                    v-if="
                                        isSearching || searchResults.length > 0
                                    "
                                    class="absolute z-10 w-full mt-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-xl max-h-60 overflow-y-auto"
                                >
                                    <div
                                        v-if="isSearching"
                                        class="p-4 text-center text-gray-500 dark:text-gray-400 text-sm"
                                    >
                                        Searching...
                                    </div>
                                    <div
                                        v-else
                                        v-for="product in searchResults"
                                        :key="product.id"
                                        @click="addProduct(product)"
                                        class="flex items-center gap-3 p-3 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer transition-colors border-b last:border-0 border-gray-100 dark:border-gray-700"
                                    >
                                        <div
                                            class="w-10 h-10 rounded bg-gray-100 dark:bg-gray-900 overflow-hidden flex-shrink-0"
                                        >
                                            <img
                                                v-if="product.feature_image"
                                                :src="product.feature_image"
                                                class="w-full h-full object-cover"
                                            />
                                            <div
                                                v-else
                                                class="w-full h-full flex items-center justify-center"
                                            >
                                                <ImageIcon
                                                    size="16"
                                                    class="text-gray-400"
                                                />
                                            </div>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div
                                                class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate"
                                            >
                                                {{ product.name }}
                                            </div>
                                            <div
                                                class="text-xs text-gray-500 dark:text-gray-400"
                                            >
                                                Code:
                                                {{ product.product_code }} |
                                                Price: ৳{{ product.price }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <span
                                v-if="form.errors.product_ids"
                                class="text-red-500 text-xs mt-1 block"
                                >{{ form.errors.product_ids }}</span
                            >

                            <!-- Selected Products List -->
                            <div class="space-y-2 mt-4">
                                <div
                                    v-for="product in selectedProducts"
                                    :key="product.id"
                                    class="flex items-center justify-between gap-3 p-2 bg-gray-50 dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-lg"
                                >
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="w-8 h-8 rounded bg-white dark:bg-gray-800 overflow-hidden shadow-sm"
                                        >
                                            <img
                                                v-if="product.feature_image"
                                                :src="product.feature_image"
                                                class="w-full h-full object-cover"
                                            />
                                            <div
                                                v-else
                                                class="w-full h-full flex items-center justify-center"
                                            >
                                                <ImageIcon
                                                    size="14"
                                                    class="text-gray-400"
                                                />
                                            </div>
                                        </div>
                                        <div>
                                            <div
                                                class="text-xs font-semibold text-gray-800 dark:text-gray-200"
                                            >
                                                {{ product.name }}
                                            </div>
                                            <div
                                                class="text-[10px] text-gray-500"
                                            >
                                                {{ product.product_code }}
                                            </div>
                                        </div>
                                    </div>
                                    <button
                                        @click.prevent="
                                            removeProduct(product.id)
                                        "
                                        class="p-1.5 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-md transition-all"
                                    >
                                        <X size="14" />
                                    </button>
                                </div>
                                <div
                                    v-if="selectedProducts.length === 0"
                                    class="text-center py-8 text-gray-400 text-sm border-2 border-dashed border-gray-100 dark:border-gray-800 rounded-xl"
                                >
                                    Search and click products to add them to
                                    this group
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Settings & Submit -->
                    <div class="space-y-6">
                        <div
                            class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 space-y-4"
                        >
                            <div
                                class="flex items-center gap-2 mb-2 text-blue-600 dark:text-blue-400 font-semibold"
                            >
                                <ToggleLeft size="18" />
                                <span>Settings</span>
                            </div>

                            <div
                                class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-900 rounded-lg"
                            >
                                <span
                                    class="text-sm font-medium text-gray-700 dark:text-gray-300"
                                    >Active Status</span
                                >
                                <button
                                    @click.prevent="form.status = !form.status"
                                    class="w-12 h-6 rounded-full transition-all flex items-center px-1"
                                    :class="
                                        form.status
                                            ? 'bg-green-500'
                                            : 'bg-gray-300 dark:bg-gray-700'
                                    "
                                >
                                    <div
                                        class="w-4 h-4 bg-white rounded-full transition-all shadow-sm"
                                        :class="
                                            form.status
                                                ? 'translate-x-6'
                                                : 'translate-x-0'
                                        "
                                    ></div>
                                </button>
                            </div>
                            <span
                                v-if="form.errors.status"
                                class="text-red-500 text-xs mt-1 block"
                                >{{ form.errors.status }}</span
                            >

                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                                    >Banner Image</label
                                >
                                <div
                                    class="mt-1 flex flex-col items-center gap-4"
                                >
                                    <div
                                        v-if="bannerPreview"
                                        class="w-full h-32 rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900"
                                    >
                                        <img
                                            :src="bannerPreview"
                                            class="w-full h-full object-cover"
                                        />
                                    </div>
                                    <label
                                        class="w-full flex flex-col items-center justify-center h-24 border-2 border-dashed border-gray-300 dark:border-gray-700 rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
                                    >
                                        <div
                                            class="flex flex-col items-center justify-center pt-5 pb-6"
                                        >
                                            <ImageIcon
                                                class="w-8 h-8 text-gray-400 mb-2"
                                            />
                                            <p
                                                class="text-xs text-gray-500 dark:text-gray-400"
                                            >
                                                Click to upload banner
                                            </p>
                                        </div>
                                        <input
                                            type="file"
                                            @change="handleBannerChange"
                                            class="hidden"
                                            accept="image/*"
                                        />
                                    </label>
                                </div>
                                <span
                                    v-if="form.errors.banner"
                                    class="text-red-500 text-xs mt-1 block"
                                    >{{ form.errors.banner }}</span
                                >
                            </div>
                        </div>

                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-xl shadow-lg shadow-blue-500/30 flex items-center justify-center gap-2 transition-all disabled:opacity-50"
                        >
                            <Save size="20" />
                            <span>{{
                                form.processing ? "Saving..." : "Save Group"
                            }}</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
