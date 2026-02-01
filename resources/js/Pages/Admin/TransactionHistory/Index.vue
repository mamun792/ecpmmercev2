<script setup>
import { ref, watch } from "vue";
import { Head, Link, router } from "@inertiajs/vue3";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import {
    Download,
    FileText,
    Search,
    Filter,
    Calendar,
    DollarSign,
    CreditCard,
} from "lucide-vue-next";
import { toast } from "@steveyuowo/vue-hot-toast";

const props = defineProps({
    transactions: Object,
    filters: Object,
    statuses: Array,
    paymentGateways: Array,
});

const showFilters = ref(false);
const loading = ref(false);
const isExporting = ref(false);

// Pagination computed values
const currentPage = ref(props.transactions.current_page || 1);
const lastPage = ref(props.transactions.last_page || 1);
const from = ref(props.transactions.from || 0);
const to = ref(props.transactions.to || 0);
const total = ref(props.transactions.total || 0);

// Initialize filters
const initFilters = () => {
    const url = new URL(window.location.href);
    return {
        status: url.searchParams.get("status") || "",
        payment_gateway: url.searchParams.get("payment_gateway") || "",
        date_from: url.searchParams.get("date_from") || "",
        date_to: url.searchParams.get("date_to") || "",
        amount_min: url.searchParams.get("amount_min") || "",
        amount_max: url.searchParams.get("amount_max") || "",
        search: url.searchParams.get("search") || "",
        per_page: parseInt(url.searchParams.get("per_page")) || 15,
    };
};

const filters = ref(initFilters());

// Per page options
const perPageOptions = [15, 25, 50, 100];

// Apply filters with debounce
let debounceTimer;
const applyFilters = () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        currentPage.value = 1; // Reset to page 1 on filter change
        router.get(
            "/admin/transaction-history",
            { ...filters.value, page: currentPage.value },
            {
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => {
                    loading.value = false;
                },
                onError: () => {
                    loading.value = false;
                },
            }
        );
    }, 500);
};

// Reset filters
const resetFilters = () => {
    filters.value = initFilters();
    currentPage.value = 1; // Reset to page 1 when filters are reset
    applyFilters();
};

// Export functions
const exportCsv = () => {
    isExporting.value = true;
    const url = new URL("/admin/transaction-history/export-csv", window.location.origin);
    Object.keys(filters.value).forEach(key => {
        if (filters.value[key]) {
            url.searchParams.set(key, filters.value[key]);
        }
    });
    window.location.href = url.toString();
    setTimeout(() => isExporting.value = false, 2000);
};

const exportPdf = () => {
    isExporting.value = true;
    const url = new URL("/admin/transaction-history/export-pdf", window.location.origin);
    Object.keys(filters.value).forEach(key => {
        if (filters.value[key]) {
            url.searchParams.set(key, filters.value[key]);
        }
    });
    window.location.href = url.toString();
    setTimeout(() => isExporting.value = false, 2000);
};

// Pagination navigation
const goToPage = (page) => {
    if (
        page < 1 ||
        page > lastPage.value ||
        page === currentPage.value ||
        loading.value
    )
        return;

    loading.value = true;
    currentPage.value = page;
    router.get(
        "/admin/transaction-history",
        { ...filters.value, page: page },
        {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                loading.value = false;
                currentPage.value = props.transactions.current_page;
                lastPage.value = props.transactions.last_page;
                from.value = props.transactions.from;
                to.value = props.transactions.to;
                total.value = props.transactions.total;
            },
            onError: () => {
                loading.value = false;
            },
        }
    );
};

// Format date
const formatDate = (dateString) => {
    if (!dateString) return "";
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

// Format currency
const formatCurrency = (amount) => {
    return '৳ ' + new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(amount);
};

// Get status badge class
const getStatusClass = (status) => {
    const classes = {
        'success': 'bg-green-100 text-green-800',
        'failed': 'bg-red-100 text-red-800',
        'pending': 'bg-yellow-100 text-yellow-800',
        'processing': 'bg-blue-100 text-blue-800',
        'cancelled': 'bg-gray-100 text-gray-800',
    };
    return classes[status] || 'bg-gray-100 text-gray-800';
};

// Watch filters and transactions
watch(filters, applyFilters, { deep: true });
watch(
    () => props.transactions,
    () => {
        // Update pagination values
        currentPage.value = props.transactions.current_page || 1;
        lastPage.value = props.transactions.last_page || 1;
        from.value = props.transactions.from || 0;
        to.value = props.transactions.to || 0;
        total.value = props.transactions.total || 0;
    },
    { deep: true }
);

const toggleFilters = () => {
    showFilters.value = !showFilters.value;
};
</script>

<template>
    <Head title="Transaction History" />
    <AdminLayout>
        <div class="p-6">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-3xl font-bold text-gray-800">Transaction History</h1>
                <div class="flex gap-2">
                    <button
                        @click="exportCsv"
                        :disabled="isExporting"
                        class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50 flex items-center gap-2"
                    >
                        <Download class="h-4 w-4" />
                        Export CSV
                    </button>
                    <button
                        @click="exportPdf"
                        :disabled="isExporting"
                        class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 disabled:opacity-50 flex items-center gap-2"
                    >
                        <FileText class="h-4 w-4" />
                        Export PDF
                    </button>
                </div>
            </div>

            <!-- Status Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white rounded-lg shadow p-4">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-blue-100">
                            <CreditCard class="h-6 w-6 text-blue-600" />
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Total Transactions</p>
                            <p class="text-2xl font-bold text-gray-900">{{ total }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-lg shadow p-4">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-green-100">
                            <DollarSign class="h-6 w-6 text-green-600" />
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Total Amount</p>
                            <p class="text-2xl font-bold text-gray-900">
                                {{ formatCurrency(transactions.data.reduce((sum, t) => sum + parseFloat(t.amount), 0)) }}
                            </p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-lg shadow p-4">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-yellow-100">
                            <Calendar class="h-6 w-6 text-yellow-600" />
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">This Month</p>
                            <p class="text-2xl font-bold text-gray-900">
                                {{ transactions.data.filter(t => new Date(t.transaction_date).getMonth() === new Date().getMonth()).length }}
                            </p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-lg shadow p-4">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-purple-100">
                            <Filter class="h-6 w-6 text-purple-600" />
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Success Rate</p>
                            <p class="text-2xl font-bold text-gray-900">
                                {{ Math.round((transactions.data.filter(t => t.status === 'success').length / transactions.data.length) * 100) || 0 }}%
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter Toggle Button -->
            <div class="mb-4">
                <button
                    @click="toggleFilters"
                    class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 transition-colors flex items-center gap-2"
                >
                    <Filter class="h-4 w-4" />
                    {{ showFilters ? 'Hide Filters' : 'Show Filters' }}
                </button>
            </div>

            <!-- Filters -->
            <div v-show="showFilters" class="mb-6 bg-gray-50 p-4 rounded-lg shadow">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select
                            v-model="filters.status"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >
                            <option value="">All Statuses</option>
                            <option v-for="status in statuses" :key="status" :value="status">
                                {{ status.charAt(0).toUpperCase() + status.slice(1) }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Payment Gateway</label>
                        <select
                            v-model="filters.payment_gateway"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >
                            <option value="">All Gateways</option>
                            <option v-for="gateway in paymentGateways" :key="gateway" :value="gateway">
                                {{ gateway.charAt(0).toUpperCase() + gateway.slice(1) }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Date From</label>
                        <input
                            v-model="filters.date_from"
                            type="date"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Date To</label>
                        <input
                            v-model="filters.date_to"
                            type="date"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Min Amount</label>
                        <input
                            v-model="filters.amount_min"
                            type="number"
                            min="0"
                            step="0.01"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            placeholder="0.00"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Max Amount</label>
                        <input
                            v-model="filters.amount_max"
                            type="number"
                            min="0"
                            step="0.01"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            placeholder="0.00"
                        />
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                        <div class="relative">
                            <Search class="h-4 w-4 absolute left-3 top-3 text-gray-400" />
                            <input
                                v-model="filters.search"
                                type="text"
                                class="w-full pl-10 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                placeholder="Search by transaction ID, invoice, order number, customer..."
                            />
                        </div>
                    </div>
                </div>
                <div class="flex justify-between items-center mt-4">
                    <button
                        @click="resetFilters"
                        class="px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600 transition-colors"
                    >
                        Reset Filters
                    </button>
                </div>
            </div>

            <!-- Per Page Filter -->
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center">
                    <label class="text-sm font-medium text-gray-700 mr-2">Show</label>
                    <select
                        v-model="filters.per_page"
                        class="rounded-md border-gray-300 shadow-sm text-sm"
                    >
                        <option v-for="option in perPageOptions" :key="option" :value="option">
                            {{ option }}
                        </option>
                    </select>
                    <span class="text-sm text-gray-700 ml-2">entries</span>
                </div>
            </div>

            <!-- Transactions Table -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Transaction Details
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Customer
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Amount
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Payment Method
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Status
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Date
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="(transaction, index) in transactions.data" :key="transaction.id" :class="index % 2 === 0 ? 'bg-white' : 'bg-gray-50'">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ transaction.transaction_id }}
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        Invoice: {{ transaction.invoice_number }}
                                    </div>
                                    <div v-if="transaction.order_number" class="text-sm text-gray-500">
                                        Order: {{ transaction.order_number }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ transaction.customer_name }}
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        {{ transaction.customer_email }}
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        {{ transaction.customer_phone }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ formatCurrency(transaction.amount) }}
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        {{ transaction.currency }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        {{ transaction.payment_gateway }}
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        {{ transaction.payment_method || 'N/A' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        :class="[
                                            'px-2 py-1 text-xs font-medium rounded-full',
                                            getStatusClass(transaction.status)
                                        ]"
                                    >
                                        {{ transaction.status.charAt(0).toUpperCase() + transaction.status.slice(1) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ formatDate(transaction.transaction_date) }}
                                </td>
                            </tr>
                            <tr v-if="transactions.data.length === 0">
                                <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                    <div class="flex flex-col items-center">
                                        <CreditCard class="h-12 w-12 text-gray-300 mb-4" />
                                        <p class="text-lg font-medium">No transactions found</p>
                                        <p class="text-sm">Try adjusting your filters or check back later.</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination -->
            <div class="flex items-center justify-between mt-6">
                <div class="text-sm text-gray-700">
                    Showing <span class="font-medium">{{ from }}</span> to
                    <span class="font-medium">{{ to }}</span> of
                    <span class="font-medium">{{ total }}</span> transactions
                </div>
                <div class="flex space-x-1">
                    <button
                        @click="goToPage(1)"
                        :disabled="currentPage === 1 || loading"
                        class="pagination-button"
                    >
                        «
                    </button>
                    <button
                        @click="goToPage(currentPage - 1)"
                        :disabled="currentPage === 1 || loading"
                        class="pagination-button"
                    >
                        ‹
                    </button>
                    <template v-if="lastPage <= 7">
                        <button
                            v-for="page in lastPage"
                            :key="page"
                            @click="goToPage(page)"
                            :disabled="loading"
                            class="pagination-button"
                            :class="{ active: currentPage === page }"
                        >
                            {{ page }}
                        </button>
                    </template>
                    <template v-else>
                        <button
                            v-if="currentPage > 3"
                            @click="goToPage(1)"
                            class="pagination-button"
                        >
                            1
                        </button>
                        <span v-if="currentPage > 4" class="pagination-ellipsis">...</span>
                        <template v-for="page in lastPage" :key="page">
                            <button
                                v-if="page >= currentPage - 1 && page <= currentPage + 1"
                                @click="goToPage(page)"
                                class="pagination-button"
                                :class="{ active: currentPage === page }"
                            >
                                {{ page }}
                            </button>
                        </template>
                        <span v-if="currentPage < lastPage - 3" class="pagination-ellipsis">...</span>
                        <button
                            v-if="currentPage < lastPage - 2"
                            @click="goToPage(lastPage)"
                            class="pagination-button"
                        >
                            {{ lastPage }}
                        </button>
                    </template>
                    <button
                        @click="goToPage(currentPage + 1)"
                        :disabled="currentPage === lastPage || loading"
                        class="pagination-button"
                    >
                        ›
                    </button>
                    <button
                        @click="goToPage(lastPage)"
                        :disabled="currentPage === lastPage || loading"
                        class="pagination-button"
                    >
                        »
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
.pagination-button {
    @apply px-3 py-1 rounded-md text-sm font-medium transition-colors;
    @apply bg-white text-gray-700 border border-gray-300;
    @apply hover:bg-blue-100 hover:text-blue-700;
}

.pagination-button:disabled {
    @apply opacity-50 cursor-not-allowed;
}

.pagination-button.active {
    @apply bg-blue-500 text-white border-blue-500;
}

.pagination-ellipsis {
    @apply px-3 py-1 text-sm text-gray-500;
}
</style>