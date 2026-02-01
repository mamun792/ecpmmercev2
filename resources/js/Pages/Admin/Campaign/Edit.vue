<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Head, useForm } from "@inertiajs/vue3";
import { ref, computed } from "vue";

const props = defineProps({
    campaign: Object,
    products: Array,
});

const formatDate = (dateString) => {
    if (!dateString) return "";
    const date = new Date(dateString);
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, "0");
    const day = String(date.getDate()).padStart(2, "0");
    return `${year}-${month}-${day}`;
};

const form = useForm({
    name: props.campaign.name,
    start_date: formatDate(props.campaign.start_date),
    end_date: formatDate(props.campaign.end_date),
    status: props.campaign.status,
    discount_type: props.campaign.discount_type,
    discount_amount: props.campaign.discount_amount,
    product_ids: props.campaign.products.map((p) => p.id),
});

const searchQuery = ref("");
const allProducts = ref(props.products || []);

const filteredProducts = computed(() => {
    if (!searchQuery.value) return allProducts.value;
    const query = searchQuery.value.toLowerCase();
    return allProducts.value.filter(
        (product) =>
            product.name.toLowerCase().includes(query) ||
            (product.product_code &&
                product.product_code.toLowerCase().includes(query))
    );
});

const toggleProduct = (productId) => {
    const index = form.product_ids.indexOf(productId);
    if (index === -1) {
        form.product_ids.push(productId);
    } else {
        form.product_ids.splice(index, 1);
    }
};

const isSelected = (productId) => form.product_ids.includes(productId);

const submit = () => {
    form.put(route("admin.campaigns.update", props.campaign.id), {
        preserveScroll: true,
    });
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat("en-US", {
        style: "currency",
        currency: "BDT",
    })
        .format(value)
        .replace("BDT", "৳");
};
</script>

<template>
    <Head title="Edit Campaign" />
    <AdminLayout>
        <div class="max-w-6xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
            <div class="mb-8 flex items-center justify-between">
                <div>
                    <h1
                        class="text-3xl font-extrabold text-gray-900 dark:text-white tracking-tight"
                    >
                        Edit Campaign
                    </h1>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                        Modify the settings and products for
                        <span class="text-blue-600 font-bold"
                            >"{{ campaign.name }}"</span
                        >
                    </p>
                </div>
                <div class="flex items-center space-x-3">
                    <button
                        @click="window.history.back()"
                        class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-all duration-200"
                    >
                        Cancel
                    </button>
                    <button
                        @click="submit"
                        :disabled="form.processing"
                        class="inline-flex items-center px-6 py-2 border border-transparent rounded-lg shadow-sm text-sm font-bold text-white bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <svg
                            v-if="form.processing"
                            class="animate-spin -ml-1 mr-3 h-5 w-5 text-white"
                            fill="none"
                            viewBox="0 0 24 24"
                        >
                            <circle
                                class="opacity-25"
                                cx="12"
                                cy="12"
                                r="10"
                                stroke="currentColor"
                                stroke-width="4"
                            ></circle>
                            <path
                                class="opacity-75"
                                fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                            ></path>
                        </svg>
                        {{ form.processing ? "Updating..." : "Save Changes" }}
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Left Column: Settings -->
                <div class="lg:col-span-1 space-y-6">
                    <div
                        class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden"
                    >
                        <div
                            class="px-6 py-5 border-b border-gray-50 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50"
                        >
                            <h3
                                class="text-lg font-bold text-gray-900 dark:text-white flex items-center"
                            >
                                <svg
                                    class="w-5 h-5 mr-2 text-blue-500"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"
                                    />
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                    />
                                </svg>
                                General Settings
                            </h3>
                        </div>
                        <div class="px-6 py-6 space-y-5">
                            <!-- Name -->
                            <div>
                                <label
                                    class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1"
                                    >Campaign Name</label
                                >
                                <input
                                    v-model="form.name"
                                    type="text"
                                    placeholder="e.g. Summer Sale 2024"
                                    class="block w-full px-4 py-3 rounded-xl border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 focus:ring-blue-500 focus:border-blue-500 transition-all dark:text-white placeholder-gray-400"
                                    required
                                />
                                <p
                                    v-if="form.errors.name"
                                    class="mt-2 text-sm text-red-500 font-medium"
                                >
                                    {{ form.errors.name }}
                                </p>
                            </div>

                            <!-- Status -->
                            <div>
                                <label
                                    class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1"
                                    >Status</label
                                >
                                <div class="flex items-center space-x-4 mt-2">
                                    <label
                                        class="relative flex items-center cursor-pointer"
                                    >
                                        <input
                                            type="radio"
                                            value="active"
                                            v-model="form.status"
                                            class="sr-only peer"
                                        />
                                        <div
                                            class="px-4 py-2 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg text-sm font-medium peer-checked:bg-blue-50 peer-checked:border-blue-500 peer-checked:text-blue-600 transition-all"
                                        >
                                            Active
                                        </div>
                                    </label>
                                    <label
                                        class="relative flex items-center cursor-pointer"
                                    >
                                        <input
                                            type="radio"
                                            value="inactive"
                                            v-model="form.status"
                                            class="sr-only peer"
                                        />
                                        <div
                                            class="px-4 py-2 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg text-sm font-medium peer-checked:bg-red-50 peer-checked:border-red-500 peer-checked:text-red-600 transition-all"
                                        >
                                            Inactive
                                        </div>
                                    </label>
                                </div>
                                <p
                                    v-if="form.errors.status"
                                    class="mt-2 text-sm text-red-500 font-medium"
                                >
                                    {{ form.errors.status }}
                                </p>
                            </div>

                            <!-- Dates -->
                            <div class="grid grid-cols-1 gap-4">
                                <div>
                                    <label
                                        class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1"
                                        >Start Date</label
                                    >
                                    <input
                                        v-model="form.start_date"
                                        type="date"
                                        class="block w-full px-4 py-3 rounded-xl border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 focus:ring-blue-500 focus:border-blue-500 transition-all dark:text-white"
                                        required
                                    />
                                    <p
                                        v-if="form.errors.start_date"
                                        class="mt-2 text-sm text-red-500 font-medium"
                                    >
                                        {{ form.errors.start_date }}
                                    </p>
                                </div>
                                <div>
                                    <label
                                        class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1"
                                        >End Date</label
                                    >
                                    <input
                                        v-model="form.end_date"
                                        type="date"
                                        class="block w-full px-4 py-3 rounded-xl border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 focus:ring-blue-500 focus:border-blue-500 transition-all dark:text-white"
                                        required
                                    />
                                    <p
                                        v-if="form.errors.end_date"
                                        class="mt-2 text-sm text-red-500 font-medium"
                                    >
                                        {{ form.errors.end_date }}
                                    </p>
                                </div>
                            </div>

                            <!-- Discount Type & Amount -->
                            <div
                                class="pt-4 border-t border-gray-100 dark:border-gray-700"
                            >
                                <div class="mb-4">
                                    <label
                                        class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2"
                                        >Discount Type</label
                                    >
                                    <div class="grid grid-cols-2 gap-3">
                                        <button
                                            type="button"
                                            @click="
                                                form.discount_type = 'fixed'
                                            "
                                            :class="
                                                form.discount_type === 'fixed'
                                                    ? 'bg-blue-600 text-white border-blue-600 shadow-md'
                                                    : 'bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-200 border-gray-200 dark:border-gray-700 hover:border-blue-400'
                                            "
                                            class="px-4 py-3 rounded-xl border text-sm font-bold transition-all"
                                        >
                                            ৳ Fixed
                                        </button>
                                        <button
                                            type="button"
                                            @click="
                                                form.discount_type =
                                                    'percentage'
                                            "
                                            :class="
                                                form.discount_type ===
                                                'percentage'
                                                    ? 'bg-blue-600 text-white border-blue-600 shadow-md'
                                                    : 'bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-200 border-gray-200 dark:border-gray-700 hover:border-blue-400'
                                            "
                                            class="px-4 py-3 rounded-xl border text-sm font-bold transition-all"
                                        >
                                            % Percent
                                        </button>
                                    </div>
                                </div>
                                <div>
                                    <label
                                        class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1"
                                    >
                                        Discount Value
                                        <span class="text-gray-400 font-normal"
                                            >({{
                                                form.discount_type ===
                                                "percentage"
                                                    ? "Percentage"
                                                    : "Amount"
                                            }})</span
                                        >
                                    </label>
                                    <div class="relative">
                                        <div
                                            class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none"
                                        >
                                            <span
                                                class="text-gray-500 font-bold"
                                                >{{
                                                    form.discount_type ===
                                                    "percentage"
                                                        ? "%"
                                                        : "৳"
                                                }}</span
                                            >
                                        </div>
                                        <input
                                            v-model="form.discount_amount"
                                            type="number"
                                            step="0.01"
                                            placeholder="0.00"
                                            class="block w-full pl-10 pr-4 py-3 rounded-xl border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 focus:ring-blue-500 focus:border-blue-500 transition-all dark:text-white"
                                            required
                                        />
                                    </div>
                                    <p
                                        v-if="form.errors.discount_amount"
                                        class="mt-2 text-sm text-red-500 font-medium"
                                    >
                                        {{ form.errors.discount_amount }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Product Selection -->
                <div class="lg:col-span-2 space-y-6">
                    <div
                        class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 flex flex-col h-full max-h-[800px]"
                    >
                        <div
                            class="px-6 py-5 border-b border-gray-50 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50 flex flex-col sm:flex-row sm:items-center justify-between gap-4"
                        >
                            <div>
                                <h3
                                    class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2"
                                >
                                    <svg
                                        class="w-5 h-5 text-blue-500"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4L4 7m0 0v10l8 4"
                                        />
                                    </svg>
                                    Select Products
                                </h3>
                                <p
                                    class="text-xs text-gray-500 dark:text-gray-400 mt-1"
                                >
                                    {{ form.product_ids.length }} products
                                    selected
                                </p>
                            </div>

                            <!-- Search Bar -->
                            <div class="relative max-w-xs w-full">
                                <div
                                    class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"
                                >
                                    <svg
                                        class="h-4 w-4 text-gray-400"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                                        />
                                    </svg>
                                </div>
                                <input
                                    v-model="searchQuery"
                                    type="text"
                                    placeholder="Search by name or SKU..."
                                    class="block w-full pl-10 pr-4 py-2 text-sm rounded-xl border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 focus:ring-blue-500 focus:border-blue-500 transition-all dark:text-white"
                                />
                            </div>
                        </div>

                        <!-- Product List -->
                        <div
                            class="flex-1 overflow-y-auto p-4 custom-scrollbar"
                        >
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div
                                    v-for="product in filteredProducts"
                                    :key="product.id"
                                    @click="toggleProduct(product.id)"
                                    :class="
                                        isSelected(product.id)
                                            ? 'border-blue-500 bg-blue-50/30 dark:bg-blue-900/10 ring-1 ring-blue-500'
                                            : 'border-gray-100 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'
                                    "
                                    class="group relative flex items-center p-3 rounded-xl border transition-all cursor-pointer bg-white dark:bg-gray-800"
                                >
                                    <!-- Selection Indicator -->
                                    <div
                                        v-if="isSelected(product.id)"
                                        class="absolute top-2 right-2 flex items-center justify-center w-5 h-5 bg-blue-600 rounded-full shadow-sm text-white"
                                    >
                                        <svg
                                            class="w-3 h-3"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="3"
                                                d="M5 13l4 4L19 7"
                                            />
                                        </svg>
                                    </div>

                                    <!-- Product Image -->
                                    <div
                                        class="w-16 h-16 flex-shrink-0 bg-gray-50 dark:bg-gray-900 rounded-lg overflow-hidden border border-gray-100 dark:border-gray-700"
                                    >
                                        <img
                                            v-if="product.feature_image"
                                            :src="product.feature_image"
                                            class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300"
                                            alt="Product"
                                        />
                                        <div
                                            v-else
                                            class="w-full h-full flex items-center justify-center text-gray-300"
                                        >
                                            <svg
                                                class="w-8 h-8"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="1"
                                                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                                                />
                                            </svg>
                                        </div>
                                    </div>

                                    <!-- Product Info -->
                                    <div class="ml-4 flex-1 min-w-0">
                                        <h4
                                            class="text-sm font-bold text-gray-900 dark:text-white truncate group-hover:text-blue-600 transition-colors"
                                        >
                                            {{ product.name }}
                                        </h4>
                                        <div
                                            class="mt-1 flex items-center gap-3"
                                        >
                                            <span
                                                class="text-xs font-bold text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/40 px-2 py-0.5 rounded-md"
                                            >
                                                {{
                                                    formatCurrency(
                                                        product.price
                                                    )
                                                }}
                                            </span>
                                            <span
                                                :class="
                                                    product.stock > 0
                                                        ? 'text-emerald-500'
                                                        : 'text-red-500'
                                                "
                                                class="text-[10px] font-semibold flex items-center gap-1"
                                            >
                                                <span
                                                    class="w-1.5 h-1.5 rounded-full"
                                                    :class="
                                                        product.stock > 0
                                                            ? 'bg-emerald-500'
                                                            : 'bg-red-500'
                                                    "
                                                ></span>
                                                {{ product.stock }} in stock
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Empty State -->
                            <div
                                v-if="filteredProducts.length === 0"
                                class="py-20 text-center"
                            >
                                <div
                                    class="mb-4 inline-flex items-center justify-center w-16 h-16 bg-gray-50 dark:bg-gray-800 rounded-full"
                                >
                                    <svg
                                        class="w-8 h-8 text-gray-300"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                                        />
                                    </svg>
                                </div>
                                <h4
                                    class="text-sm font-medium text-gray-900 dark:text-white"
                                >
                                    No products found
                                </h4>
                                <p class="text-xs text-gray-500 mt-1">
                                    Try searching with a different keyword.
                                </p>
                            </div>
                        </div>

                        <p
                            v-if="form.errors.product_ids"
                            class="px-6 py-2 text-sm text-red-500 font-medium bg-red-50 dark:bg-red-900/10 border-t border-red-100 dark:border-red-900/20"
                        >
                            {{ form.errors.product_ids }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
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
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #cbd5e1;
}
</style>
