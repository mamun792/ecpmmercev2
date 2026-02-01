<template>
    <Head title="Reports Dashboard" />
    <AdminLayout>
        <div class="reports-dashboard p-6 bg-gray-50 min-h-screen">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900 mb-2">
                    Reports Dashboard
                </h1>
                <p class="text-gray-600">
                    Track your business performance and inventory insights
                </p>
            </div>

            <!-- Filters Section -->
            <div class="bg-white rounded-lg shadow-sm p-6 mb-8">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">
                    Filters
                </h2>

                <form @submit.prevent="applyFilters" class="space-y-4">
                    <!-- Time Period Filter -->
                    <div
                        class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4"
                    >
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 mb-2"
                                >Time Period</label
                            >
                            <select
                                v-model="filters.period"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            >
                                <option value="all">All Time</option>
                                <option value="today">Today</option>
                                <option value="weekly">This Week</option>
                                <option value="monthly">This Month</option>
                                <option value="yearly">This Year</option>
                                <option value="custom">Custom Range</option>
                            </select>
                        </div>
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 mb-2"
                                >Order Status</label
                            >
                            <select
                                v-model="filters.status"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            >
                                <option value="">All Status</option>
                                <option value="pending">Pending</option>
                                <option value="processing">Processing</option>
                                <option value="shipped">Shipped</option>
                                <option value="delivered">Delivered</option>
                                <option value="cancelled">Cancelled</option>
                                <option value="on_hold">On Hold</option>
                                <option value="confirmed">Confirmed</option>
                            </select>
                        </div>
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 mb-2"
                                >Payment Status</label
                            >
                            <select
                                v-model="filters.payment_status"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            >
                                <option value="">All Payments</option>
                                <option value="paid">Paid</option>
                                <option value="unpaid">Unpaid</option>
                                <option value="partial">Partial</option>
                            </select>
                        </div>
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 mb-2"
                                >Per Page</label
                            >
                            <select
                                v-model="filters.per_page"
                                @change="applyFilters"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            >
                                <option value="10">10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                        </div>
                    </div>

                    <!-- Custom Date Range -->
                    <div
                        v-if="filters.period === 'custom'"
                        class="grid grid-cols-1 md:grid-cols-2 gap-4"
                    >
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 mb-2"
                                >From Date</label
                            >
                            <input
                                type="date"
                                v-model="filters.date_from"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            />
                        </div>
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 mb-2"
                                >To Date</label
                            >
                            <input
                                type="date"
                                v-model="filters.date_to"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            />
                        </div>
                    </div>

                    <!-- Additional Filters -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 mb-2"
                                >Customer Search</label
                            >
                            <input
                                type="text"
                                v-model="filters.customer_search"
                                placeholder="Search by name, email, or phone"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            />
                        </div>
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 mb-2"
                                >Min Total</label
                            >
                            <input
                                type="number"
                                v-model="filters.min_total"
                                placeholder="0"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            />
                        </div>
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 mb-2"
                                >Max Total</label
                            >
                            <input
                                type="number"
                                v-model="filters.max_total"
                                placeholder="1000"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            />
                        </div>
                    </div>

                    <!-- Filter Actions -->
                    <div class="flex gap-3">
                        <button
                            type="submit"
                            class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors"
                        >
                            Apply Filters
                        </button>
                        <button
                            type="button"
                            @click="resetFilters"
                            class="px-6 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors"
                        >
                            Reset
                        </button>
                    </div>
                </form>
            </div>

            <!-- Stats Cards -->
            <div
                class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8"
            >
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">
                                Total Products
                            </p>
                            <p class="text-3xl font-bold text-gray-900">
                                {{ totalProducts }}
                            </p>
                        </div>
                        <div class="bg-blue-100 p-3 rounded-full">
                            <svg
                                class="w-6 h-6 text-blue-600"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10"
                                ></path>
                            </svg>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">
                                Total Stock
                            </p>
                            <p class="text-3xl font-bold text-gray-900">
                                {{ totalStock }}
                            </p>
                        </div>
                        <div class="bg-green-100 p-3 rounded-full">
                            <svg
                                class="w-6 h-6 text-green-600"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
                                ></path>
                            </svg>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">
                                Total Sold
                            </p>
                            <p class="text-3xl font-bold text-gray-900">
                                {{ totalSold }}
                            </p>
                        </div>
                        <div class="bg-primary/10 p-3 rounded-full">
                            <svg
                                class="w-6 h-6 text-primary"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"
                                ></path>
                            </svg>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">
                                Total Value
                            </p>
                            <p class="text-3xl font-bold text-gray-900">
                                ৳{{ totalValue.toLocaleString() }}
                            </p>
                        </div>
                        <div class="bg-purple-100 p-3 rounded-full">
                            <svg
                                class="w-6 h-6 text-purple-600"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"
                                ></path>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Performance Metrics -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">
                        Sell-Through Rate
                    </h3>
                    <div class="flex items-center justify-between">
                        <div class="text-4xl font-bold text-gray-900">
                            {{ overallSellThroughRate.toFixed(1) }}%
                        </div>
                        <div class="w-20 h-20">
                            <svg class="w-full h-full" viewBox="0 0 100 100">
                                <circle
                                    cx="50"
                                    cy="50"
                                    r="45"
                                    fill="none"
                                    stroke="#e5e7eb"
                                    stroke-width="10"
                                />
                                <circle
                                    cx="50"
                                    cy="50"
                                    r="45"
                                    fill="none"
                                    stroke="#3b82f6"
                                    stroke-width="10"
                                    stroke-dasharray="283"
                                    :stroke-dashoffset="
                                        283 -
                                        (283 * overallSellThroughRate) / 100
                                    "
                                    transform="rotate(-90 50 50)"
                                />
                            </svg>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">
                        Category Performance
                    </h3>
                    <div class="space-y-4">
                        <div
                            v-for="(category, name) in productsByCategory"
                            :key="name"
                            class="flex items-center justify-between"
                        >
                            <div>
                                <p class="font-medium text-gray-900">
                                    {{ name }}
                                </p>
                                <p class="text-sm text-gray-600">
                                    {{ category.total_products }} products
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="font-semibold text-gray-900">
                                    {{ category.sell_through_rate.toFixed(1) }}%
                                </p>
                                <p class="text-sm text-gray-600">
                                    ৳{{
                                        category.inventory_value.toLocaleString()
                                    }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top Performers -->
            <div class="bg-white rounded-lg shadow-sm p-6 mb-8">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">
                    Top Performing Products
                </h3>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b">
                                <th
                                    class="text-left py-3 px-4 font-medium text-gray-700"
                                >
                                    Product
                                </th>
                                <th
                                    class="text-left py-3 px-4 font-medium text-gray-700"
                                >
                                    Category
                                </th>
                                <th
                                    class="text-left py-3 px-4 font-medium text-gray-700"
                                >
                                    Stock
                                </th>
                                <th
                                    class="text-left py-3 px-4 font-medium text-gray-700"
                                >
                                    Sold
                                </th>
                                <th
                                    class="text-left py-3 px-4 font-medium text-gray-700"
                                >
                                    Sell-Through Rate
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="product in topPerformers"
                                :key="product.id"
                                class="border-b hover:bg-gray-50"
                            >
                                <td class="py-3 px-4 font-medium text-gray-900">
                                    {{ product.name }}
                                </td>
                                <td class="py-3 px-4 text-gray-600">
                                    {{ product.category }}
                                </td>
                                <td class="py-3 px-4 text-gray-600">
                                    {{ product.stock }}
                                </td>
                                <td class="py-3 px-4 text-gray-600">
                                    {{ product.sold }}
                                </td>
                                <td class="py-3 px-4">
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                        :class="
                                            product.sell_through_rate > 10
                                                ? 'bg-green-100 text-green-800'
                                                : product.sell_through_rate > 5
                                                ? 'bg-yellow-100 text-yellow-800'
                                                : 'bg-red-100 text-red-800'
                                        "
                                    >
                                        {{
                                            product.sell_through_rate.toFixed(
                                                1
                                            )
                                        }}%
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Orders Table -->
            <div class="bg-white rounded-lg shadow-sm p-6 mb-8">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Orders</h3>
                    <div class="flex gap-2">
                        <button
                            @click="exportToCSV"
                            class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-colors text-sm"
                        >
                            {{
                                selectedOrders.length > 0
                                    ? `Export Selected (${selectedOrders.length}) to CSV`
                                    : "Export All to CSV"
                            }}
                        </button>
                        <button
                            @click="exportToPDF"
                            class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-colors text-sm"
                        >
                            Export to PDF
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b">
                                <th
                                    class="text-left py-3 px-4 font-medium text-gray-700"
                                >
                                    <input
                                        type="checkbox"
                                        v-model="selectAll"
                                        @change="toggleSelectAll"
                                        class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                    />
                                </th>
                                <th
                                    class="text-left py-3 px-4 font-medium text-gray-700"
                                >
                                    Order #
                                </th>
                                <th
                                    class="text-left py-3 px-4 font-medium text-gray-700"
                                >
                                    Customer
                                </th>
                                <th
                                    class="text-left py-3 px-4 font-medium text-gray-700"
                                >
                                    Total
                                </th>
                                <th
                                    class="text-left py-3 px-4 font-medium text-gray-700"
                                >
                                    Status
                                </th>
                                <th
                                    class="text-left py-3 px-4 font-medium text-gray-700"
                                >
                                    Payment
                                </th>
                                <th
                                    class="text-left py-3 px-4 font-medium text-gray-700"
                                >
                                    Date
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="order in orders.data"
                                :key="order.id"
                                class="border-b hover:bg-gray-50"
                            >
                                <td class="py-3 px-4">
                                    <input
                                        type="checkbox"
                                        :value="order.id"
                                        v-model="selectedOrders"
                                        class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                    />
                                </td>
                                <td class="py-3 px-4 font-medium text-gray-900">
                                    {{ order.order_number }}
                                </td>
                                <td class="py-3 px-4">
                                    <div>
                                        <p class="font-medium text-gray-900">
                                            {{ order.customer.name }}
                                        </p>
                                        <p class="text-sm text-gray-600">
                                            {{ order.customer.email }}
                                        </p>
                                    </div>
                                </td>
                                <td class="py-3 px-4 font-medium text-gray-900">
                                    ৳{{ order.total }}
                                </td>
                                <td class="py-3 px-4">
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                        :class="getStatusColor(order.status)"
                                    >
                                        {{ order.status }}
                                    </span>
                                </td>
                                <td class="py-3 px-4">
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                        :class="
                                            getPaymentStatusColor(
                                                order.payment_status
                                            )
                                        "
                                    >
                                        {{ order.payment_status }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-gray-600">
                                    {{ formatDate(order.date) }}
                                </td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="border-t-2 border-gray-300 bg-gray-50">
                                <td
                                    colspan="3"
                                    class="py-3 px-4 font-bold text-gray-900"
                                >
                                    Total Amount:
                                </td>
                                <td
                                    class="py-3 px-4 font-bold text-gray-900 text-lg"
                                >
                                    ৳{{
                                        calculateTotalAmount().toLocaleString()
                                    }}
                                </td>
                                <td colspan="4" class="py-3 px-4"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="mt-6 flex items-center justify-between">
                    <div class="text-sm text-gray-700">
                        Showing {{ orders.from }} to {{ orders.to }} of
                        {{ orders.total }} results
                    </div>
                    <div class="flex gap-2">
                        <button
                            v-for="link in orders.links"
                            :key="link.label"
                            @click="changePage(link.url)"
                            :disabled="!link.url"
                            class="px-3 py-1 text-sm border rounded-md"
                            :class="
                                link.active
                                    ? 'bg-blue-600 text-white border-blue-600'
                                    : !link.url
                                    ? 'bg-gray-100 text-gray-400 border-gray-200 cursor-not-allowed'
                                    : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'
                            "
                            v-html="link.label"
                        ></button>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { Head } from "@inertiajs/vue3";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { ref, reactive, onMounted } from "vue";
import { router } from "@inertiajs/vue3";
import { jsPDF } from "jspdf";

const props = defineProps({
    orders: Object,
    totalProducts: Number,
    totalVariations: Number,
    totalStock: Number,
    totalSold: Number,
    totalValue: Number,
    overallSellThroughRate: Number,
    lowStockProducts: Array,
    productsByCategory: Object,
    topPerformers: Array,
});

const filters = reactive({
    period: "all",
    status: "",
    payment_status: "",
    date_from: "",
    date_to: "",
    customer_search: "",
    min_total: "",
    max_total: "",
    per_page: 10,
});

const selectedOrders = ref([]);
const selectAll = ref(false);

// Initialize filters with URL parameters on mount
onMounted(() => {
    const urlParams = new URLSearchParams(window.location.search);
    Object.keys(filters).forEach((key) => {
        const value = urlParams.get(key);
        if (value !== null) {
            filters[key] = value;
        }
    });
});

// Toggle select all orders
const toggleSelectAll = () => {
    if (selectAll.value) {
        selectedOrders.value = props.orders.data.map((order) => order.id);
    } else {
        selectedOrders.value = [];
    }
};

// Calculate total amount of selected orders
const calculateTotalAmount = () => {
    const ordersToSum =
        selectedOrders.value.length > 0
            ? props.orders.data.filter((order) =>
                  selectedOrders.value.includes(order.id)
              )
            : props.orders.data;
    return ordersToSum.reduce(
        (total, order) => total + parseFloat(order.total),
        0
    );
};

// Export to CSV function
const exportToCSV = () => {
    // Select data based on whether orders are selected
    const selectedData =
        selectedOrders.value.length > 0
            ? props.orders.data.filter((order) =>
                  selectedOrders.value.includes(order.id)
              )
            : props.orders.data;

    if (selectedData.length === 0) {
        alert("No orders to export");
        return;
    }

    // CSV Headers
    const headers = [
        "Order #",
        "Customer Name",
        "Customer Email",
        "Total",
        "Status",
        "Payment Status",
        "Date",
    ];

    // Convert data to CSV format
    const csvContent = [
        // Add headers
        headers.join(","),
        // Add data rows
        ...selectedData.map((order) =>
            [
                `"${order.order_number}"`,
                `"${order.customer.name}"`,
                `"${order.customer.email}"`,
                `"$${order.total}"`,
                `"${order.status}"`,
                `"${order.payment_status}"`,
                `"${formatDate(order.date)}"`,
            ].join(",")
        ),
    ].join("\n");

    // Create and download CSV file
    const blob = new Blob([csvContent], { type: "text/csv;charset=utf-8;" });
    const link = document.createElement("a");

    if (link.download !== undefined) {
        const url = URL.createObjectURL(blob);
        link.setAttribute("href", url);

        // Generate filename based on selection
        const filename =
            selectedOrders.value.length > 0
                ? `selected_orders_${selectedOrders.value.length}_${
                      new Date().toISOString().split("T")[0]
                  }.csv`
                : `all_orders_${selectedData.length}_${
                      new Date().toISOString().split("T")[0]
                  }.csv`;

        link.setAttribute("download", filename);
        link.style.visibility = "hidden";
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
};

// Export to PDF function
const exportToPDF = () => {
    const doc = new jsPDF();
    const pageWidth = doc.internal.pageSize.getWidth();
    const pageHeight = doc.internal.pageSize.getHeight();
    const margin = 15;
    let y = 20;

    // Select data based on whether orders are selected
    const selectedData =
        selectedOrders.value.length > 0
            ? props.orders.data.filter((order) =>
                  selectedOrders.value.includes(order.id)
              )
            : props.orders.data;

    // Header
    doc.setFont("helvetica", "bold");
    doc.setFontSize(15);
    doc.setTextColor(0, 0, 0); // Black text
    doc.text("Orders Report", pageWidth / 2, y, { align: "center" });
    y += 10;

    doc.setFontSize(10);
    doc.setFont("helvetica", "normal");
    doc.text(
        `Generated on: ${new Date().toLocaleDateString("en-US", {
            year: "numeric",
            month: "long",
            day: "numeric",
        })}`,
        pageWidth / 2,
        y,
        { align: "center" }
    );
    y += 20;

    // Table setup
    const headers = [
        "Order #",
        "Customer",
        "Total",
        "Status",
        "Payment",
        "Date",
    ];
    const columnWidths = [25, 55, 20, 25, 25, 35];
    const startX = margin;

    const drawTableRow = (rowData, isHeader = false, rowHeight = 10) => {
        let currentX = startX;

        // Draw each cell
        rowData.forEach((cellData, index) => {
            const cellWidth = columnWidths[index];

            // IMPORTANT: Set colors BEFORE drawing each cell
            doc.setFillColor(255, 255, 255); // White background
            doc.setDrawColor(0, 0, 0); // Black border
            doc.setTextColor(0, 0, 0); // Black text

            if (isHeader) {
                doc.setFont("helvetica", "bold");
            } else {
                doc.setFont("helvetica", "normal");
            }

            // Fill cell with white background and draw border
            doc.rect(currentX, y, cellWidth, rowHeight, "FD");

            // Add text with proper padding
            const textX = currentX + 2;
            const textY = y + 7;

            if (typeof cellData === "string" && cellData.includes("\n")) {
                // Handle multi-line text
                const lines = cellData.split("\n");
                lines.forEach((line, lineIndex) => {
                    doc.text(line, textX, textY + lineIndex * 4, {
                        maxWidth: cellWidth - 4,
                    });
                });
            } else {
                doc.text(String(cellData), textX, textY, {
                    maxWidth: cellWidth - 4,
                });
            }

            currentX += cellWidth;
        });

        y += rowHeight;
    };

    const checkPageBreak = (requiredSpace = 20) => {
        if (y > pageHeight - requiredSpace) {
            doc.addPage();
            y = 20;
            // Redraw headers on new page
            drawTableRow(headers, true);
            return true;
        }
        return false;
    };

    // Draw table headers
    drawTableRow(headers, true);

    // Draw table rows
    selectedData.forEach((order, index) => {
        checkPageBreak(15);

        const row = [
            order.order_number,
            `${order.customer.name}\n${order.customer.email}`,
            `$${order.total}`,
            order.status,
            order.payment_status,
            formatDate(order.date),
        ];

        drawTableRow(row, false, 12);
    });

    // Total Amount section
    y += 10;
    checkPageBreak(25);

    doc.setFont("helvetica", "bold");
    doc.setTextColor(0, 0, 0); // Black text
    doc.setFillColor(240, 240, 240); // Light gray background for total
    doc.setDrawColor(0, 0, 0); // Black border
    const tableWidth = columnWidths.reduce((a, b) => a + b, 0);
    doc.rect(startX, y, tableWidth, 12, "FD");
    doc.text(
        `Total Amount: $${calculateTotalAmount().toLocaleString()}`,
        startX + 5,
        y + 8
    );

    // Footer
    doc.setFont("helvetica", "italic");
    doc.setFontSize(8);
    doc.setTextColor(100, 100, 100); // Gray text for footer
    doc.text(
        "Generated by Inventory Management System",
        pageWidth / 2,
        pageHeight - 10,
        { align: "center" }
    );

    // Save PDF
    doc.save(`orders_report_${new Date().toISOString().split("T")[0]}.pdf`);
};

const applyFilters = () => {
    const params = { ...filters };

    if (filters.period === "today") {
        const today = new Date().toISOString().split("T")[0];
        params.date_from = today;
        params.date_to = today;
    } else if (filters.period === "weekly") {
        const today = new Date();
        const weekStart = new Date(
            today.setDate(today.getDate() - today.getDay())
        );
        const weekEnd = new Date(
            today.setDate(today.getDate() - today.getDay() + 6)
        );
        params.date_from = weekStart.toISOString().split("T")[0];
        params.date_to = weekEnd.toISOString().split("T")[0];
    } else if (filters.period === "monthly") {
        const today = new Date();
        const monthStart = new Date(today.getFullYear(), today.getMonth(), 1);
        const monthEnd = new Date(today.getFullYear(), today.getMonth() + 1, 0);
        params.date_from = monthStart.toISOString().split("T")[0];
        params.date_to = monthEnd.toISOString().split("T")[0];
    } else if (filters.period === "yearly") {
        const today = new Date();
        const yearStart = new Date(today.getFullYear(), 0, 1);
        const yearEnd = new Date(today.getFullYear(), 11, 31);
        params.date_from = yearStart.toISOString().split("T")[0];
        params.date_to = yearEnd.toISOString().split("T")[0];
    }

    Object.keys(params).forEach((key) => {
        if (
            params[key] === "" ||
            params[key] === null ||
            params[key] === undefined
        ) {
            delete params[key];
        }
    });

    router.get(route("admin.reports.generateReport"), params, {
        preserveState: false,
        preserveScroll: false,
    });
};

const resetFilters = () => {
    Object.keys(filters).forEach((key) => {
        if (key === "per_page") {
            filters[key] = 10;
        } else if (key === "period") {
            filters[key] = "all";
        } else {
            filters[key] = "";
        }
    });
    selectedOrders.value = [];
    selectAll.value = false;

    router.get(
        route("admin.reports.generateReport"),
        {},
        {
            preserveState: false,
            preserveScroll: false,
        }
    );
};

const changePage = (url) => {
    if (url) {
        selectedOrders.value = [];
        selectAll.value = false;
        router.get(
            url,
            {},
            {
                preserveState: false,
                preserveScroll: false,
            }
        );
    }
};

const getStatusColor = (status) => {
    const colors = {
        pending: "bg-yellow-100 text-yellow-800",
        processing: "bg-blue-100 text-blue-800",
        shipped: "bg-purple-100 text-purple-800",
        delivered: "bg-green-100 text-green-800",
        cancelled: "bg-red-100 text-red-800",
        on_hold: "bg-teal-100 text-teal-800",
        confirmed: "bg-indigo-100 text-indigo-800",
    };
    return colors[status] || "bg-gray-100 text-gray-800";
};

const getPaymentStatusColor = (status) => {
    const colors = {
        paid: "bg-green-100 text-green-800",
        unpaid: "bg-red-100 text-red-800",
        partial: "bg-yellow-100 text-yellow-800",
    };
    return colors[status] || "bg-gray-100 text-gray-800";
};

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString("en-US", {
        year: "numeric",
        month: "short",
        day: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    });
};
</script>
