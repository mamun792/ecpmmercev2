<script setup>
import { ref, watch, computed } from "vue";
import { Head, Link, router } from "@inertiajs/vue3";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import {
    SquarePen,
    Trash2Icon,
    User,
    Mail,
    Phone,
    Eye,
    ShoppingCart,
    Clock,
} from "lucide-vue-next";
import DeleteModal from "@/Components/Modal/DeleteModal.vue";
import { toast } from "@steveyuowo/vue-hot-toast";
import StatusDropdown from "@/Components/Order/StatusDropdown.vue";

const props = defineProps({
    orders: Object,
});

const showDeleteModal = ref(false);
const orderToDelete = ref(null);
const selectedOrders = ref([]);
const selectAll = ref(false);

// Pagination computed values
const currentPage = ref(props.orders.current_page || 1);
const lastPage = ref(props.orders.last_page || 1);
const from = ref(props.orders.from || 0);
const to = ref(props.orders.to || 0);
const total = ref(props.orders.total || 0);

// Initialize filters
const initFilters = () => {
    const url = new URL(window.location.href);
    return {
        payment_status: url.searchParams.get("payment_status") || "",
        date_from: url.searchParams.get("date_from") || "",
        date_to: url.searchParams.get("date_to") || "",
        customer_search: url.searchParams.get("customer_search") || "",
        order_number: url.searchParams.get("order_number") || "",
        per_page: parseInt(url.searchParams.get("per_page")) || 10,
        sort_by: url.searchParams.get("sort_by") || "created_at",
        sort_direction: url.searchParams.get("sort_direction") || "desc",
    };
};

const filters = ref(initFilters());

// Per page options
const perPageOptions = [10, 25, 50, 100];

// Open delete modal
const openDeleteModal = (id) => {
    orderToDelete.value = id;
    showDeleteModal.value = true;
};

// Handle delete success
const handleDeleteSuccess = () => {
    showDeleteModal.value = false;
    orderToDelete.value = null;
};

// Toggle select all checkboxes
const toggleSelectAll = () => {
    selectAll.value = !selectAll.value;
    selectedOrders.value = selectAll.value
        ? props.orders.data.map((order) => order.id)
        : [];
};

// Toggle individual order selection
const toggleOrderSelection = (orderId) => {
    const index = selectedOrders.value.indexOf(orderId);
    if (index === -1) {
        selectedOrders.value.push(orderId);
    } else {
        selectedOrders.value.splice(index, 1);
    }
    selectAll.value =
        selectedOrders.value.length === props.orders.data.length &&
        props.orders.data.length > 0;
};

// Apply filters with debounce
let filterTimeout;
const applyFilters = () => {
    clearTimeout(filterTimeout);
    filterTimeout = setTimeout(() => {
        router.get(
            route("admin.orders.incomplete"),
            {
                ...filters.value,
                page: 1, // Reset to first page when filtering
            },
            {
                preserveState: true,
                preserveScroll: true,
            }
        );
    }, 500);
};

// Watch for filter changes
watch(
    () => filters.value,
    () => {
        applyFilters();
    },
    { deep: true }
);

// Clear all filters
const clearFilters = () => {
    filters.value = {
        payment_status: "",
        date_from: "",
        date_to: "",
        customer_search: "",
        order_number: "",
        per_page: 10,
        sort_by: "created_at",
        sort_direction: "desc",
    };
};

// Status update handler
const handleStatusUpdate = (orderId, newStatus) => {
    toast.success(`Order status updated to ${newStatus}`);
};

// Format date
const formatDate = (date) => {
    if (!date) return 'N/A';
    try {
        const dateObj = new Date(date);
        if (isNaN(dateObj.getTime())) return 'Invalid Date';
        return dateObj.toLocaleDateString("en-US", {
            year: "numeric",
            month: "short",
            day: "numeric",
            hour: "2-digit",
            minute: "2-digit",
        });
    } catch (e) {
        return 'Invalid Date';
    }
};

// Get status badge color
const getStatusColor = (status) => {
    const colors = {
        pending: "bg-yellow-200 text-yellow-900",
        processing: "bg-sky-200 text-sky-900",
        completed: "bg-emerald-200 text-emerald-900",
        cancelled: "bg-rose-200 text-rose-900",
        shipped: "bg-violet-200 text-violet-900",
        delivered: "bg-teal-200 text-teal-900",
        returned: "bg-primary/20 text-primary",
        incomplete: "bg-amber-200 text-amber-900",
        on_hold: "bg-pink-200 text-pink-900",
        confirmed: "bg-indigo-200 text-indigo-900",
    };
    return colors[status] || "bg-gray-200 text-gray-900";
};

// Total sales (computed) 
const getPaymentStatusColor = (status) => {
    const colors = {
        paid: "bg-green-100 text-green-800",
        unpaid: "bg-red-100 text-red-800",
        refunded: "bg-primary/10 text-primary",
    };
    return colors[status] || "bg-gray-100 text-gray-800";
};

// Total sales (computed)
const totalSales = computed(() => {
    return props.orders?.data ? props.orders.data.reduce((sum, o) => sum + Number(o.total || 0), 0) : 0;
});

// Average order value for current page
const avgOrder = computed(() => {
    const count = props.orders?.data?.length || 1;
    return totalSales.value / count;
});

// Return border class based on status for colorful accent
const orderBorderClass = (status) => {
    const map = {
        pending: 'border-l-4 border-yellow-400',
        processing: 'border-l-4 border-sky-400',
        completed: 'border-l-4 border-emerald-400',
        cancelled: 'border-l-4 border-rose-400',
        shipped: 'border-l-4 border-violet-400',
        delivered: 'border-l-4 border-teal-400',
        returned: 'border-l-4 border-primary',
        incomplete: 'border-l-4 border-amber-400',
        on_hold: 'border-l-4 border-pink-400',
        confirmed: 'border-l-4 border-indigo-400',
    };
    return map[status] || 'border-l-4 border-gray-300';
};

// Pagination
const goToPage = (page) => {
    if (page >= 1 && page <= lastPage.value) {
        router.get(
            route("admin.orders.incomplete"),
            {
                ...filters.value,
                page: page,
            },
            {
                preserveState: true,
                preserveScroll: true,
            }
        );
    }
};
</script>

<template>
    <Head title="Incomplete Orders" />
    <AdminLayout>
        <!-- Header -->
        <div class="mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <Clock class="w-7 h-7 text-gray-600" />
                        Incomplete Orders
                    </h1>
                    <p class="text-gray-600 dark:text-gray-400 mt-1">
                        Manage orders that are not yet completed
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <Link :href="route('admin.orders.index')" class="btn bg-white border px-3 py-1 rounded text-sm shadow-sm">View All Orders</Link>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mt-4">
                <div class="p-4 rounded-lg bg-gradient-to-r from-amber-400 to-amber-600 text-white shadow-lg flex flex-col">
                    <div class="text-sm">Incomplete Orders</div>
                    <div class="text-2xl font-bold">{{ props.orders?.total || 0 }}</div>
                    <div class="text-xs mt-1 opacity-90">Open issues & missing info</div>
                </div>

                <div class="p-4 rounded-lg bg-gradient-to-r from-pink-500 to-rose-500 text-white shadow-lg flex flex-col">
                    <div class="text-sm">Total Sales</div>
                    <div class="text-2xl font-bold">৳{{ totalSales.toFixed(2) }}</div>
                    <div class="text-xs mt-1 opacity-90">Across the current results</div>
                </div>



                <div class="p-4 rounded-lg bg-gradient-to-r from-emerald-400 to-emerald-600 text-white shadow-lg flex flex-col">
                    <div class="text-sm">Avg Order</div>
                    <div class="text-2xl font-bold">৳{{ avgOrder.toFixed(2) }}</div>
                    <div class="text-xs mt-1 opacity-90">Average order value</div>
                </div>
            </div> 
        </div>

        <!-- Filters Section -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Order Number Search -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Order Number
                    </label>
                    <input
                        v-model="filters.order_number"
                        type="text"
                        placeholder="Search by order number..."
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                    />
                </div>

                <!-- Customer Search -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Customer
                    </label>
                    <input
                        v-model="filters.customer_search"
                        type="text"
                        placeholder="Search by name, email, phone..."
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                    />
                </div>

                <!-- Date From -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        From Date
                    </label>
                    <input
                        v-model="filters.date_from"
                        type="date"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                    />
                </div>

                <!-- Date To -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        To Date
                    </label>
                    <input
                        v-model="filters.date_to"
                        type="date"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                    />
                </div>
            </div>

            <!-- Clear Filters Button -->
            <div class="mt-4">
                <button
                    @click="clearFilters"
                    class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors"
                >
                    Clear Filters
                </button>
            </div>
        </div>

        <!-- Orders Table -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
            <!-- Table Header -->
            <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                <div class="text-sm text-gray-600 dark:text-gray-400">
                    Showing {{ from }} to {{ to }} of {{ total }} incomplete orders
                </div>
                <div class="flex items-center gap-2">
                    <label class="text-sm text-gray-600 dark:text-gray-400">Per Page:</label>
                    <select
                        v-model="filters.per_page"
                        class="px-3 py-1 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white"
                    >
                        <option v-for="option in perPageOptions" :key="option" :value="option">
                            {{ option }}
                        </option>
                    </select>
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gradient-to-r from-pink-600 to-indigo-700 text-white">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                <input
                                    type="checkbox"
                                    :checked="selectAll"
                                    @change="toggleSelectAll"
                                    class="rounded border-gray-300"
                                />
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-100 dark:text-gray-400 uppercase tracking-wider">
                                Order
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-100 dark:text-gray-400 uppercase tracking-wider">
                                Customer
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-100 dark:text-gray-400 uppercase tracking-wider">
                                Notes
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-100 dark:text-gray-400 uppercase tracking-wider">
                                Date
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-100 dark:text-gray-400 uppercase tracking-wider">
                                Total
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-100 dark:text-gray-400 uppercase tracking-wider">
                                Status
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-100 dark:text-gray-400 uppercase tracking-wider">
                                Payment
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-100 dark:text-gray-400 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <tr
                            v-for="order in orders.data"
                            :key="order.id"
                            :class="[orderBorderClass(order.status), 'hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors bg-white dark:bg-gray-800']"
                        >
                            <td class="px-6 py-4 whitespace-nowrap">
                                <input
                                    type="checkbox"
                                    :checked="selectedOrders.includes(order.id)"
                                    @change="toggleOrderSelection(order.id)"
                                    class="rounded border-gray-300"
                                />
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ order.order_number }}
                                </div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                    Items: {{ order.items?.length || 0 }}
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-start gap-2">
                                    <User class="w-4 h-4 text-gray-400 mt-0.5" />
                                    <div>
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ order.customer?.name || 'N/A' }}
                                        </div>
                                        <div v-if="order.customer?.phone && order.customer.phone !== 'N/A'" class="text-sm text-gray-500 dark:text-gray-400 flex items-center gap-1">
                                            <Phone class="w-3 h-3" />
                                            {{ order.customer.phone }}
                                        </div>
                                        <div v-if="order.customer?.email && order.customer.email !== 'N/A'" class="text-sm text-gray-500 dark:text-gray-400 flex items-center gap-1">
                                            <Mail class="w-3 h-3" />
                                            {{ order.customer.email }}
                                        </div>

                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ order.admin_notes || 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ formatDate(order.date || order.created_at) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">
                                    ৳{{ parseFloat(order.total || 0).toFixed(2) }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span
                                    class="px-2 py-1 text-xs font-semibold rounded-full"
                                    :class="getStatusColor(order.status || 'incomplete')"
                                >
                                    {{ order.status || 'incomplete' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span
                                    class="px-2 py-1 text-xs font-semibold rounded-full"
                                    :class="getPaymentStatusColor(order.payment_status)"
                                >
                                    {{ order.payment_status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <div class="flex items-center gap-2">
                                    <!-- <Link
                                        :href="route('admin.orders.show', order.id)"
                                        class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300"
                                        title="View Details"
                                    >
                                        <Eye class="w-5 h-5" />
                                    </Link> -->
                                    <Link
                                        :href="route('admin.orders.edit', order.id)"
                                        class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300"
                                        title="Edit"
                                    >
                                        <SquarePen class="w-5 h-5" />
                                    </Link>
                                    <button
                                        @click="openDeleteModal(order.id)"
                                        class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                                        title="Delete"
                                    >
                                        <Trash2Icon class="w-5 h-5" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="orders.data.length === 0">
                            <td colspan="8" class="px-6 py-8 text-center">
                                <div class="p-8 rounded-lg bg-gradient-to-r from-white to-slate-50 dark:from-gray-800 dark:to-gray-900">
                                    <ShoppingCart class="w-12 h-12 mx-auto mb-3 text-amber-400" />
                                    <p class="text-lg font-semibold text-gray-700 dark:text-gray-200">No incomplete orders found</p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">Try adjusting filters or check back later.</p>
                                </div>
                            </td>
                        </tr> 
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div
                v-if="orders.data.length > 0"
                class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between"
            >
                <div class="text-sm text-gray-600 dark:text-gray-400">
                    Page {{ currentPage }} of {{ lastPage }}
                </div>
                <div class="flex gap-2">
                    <button
                        @click="goToPage(currentPage - 1)"
                        :disabled="currentPage === 1"
                        class="px-3 py-1 border border-gray-300 dark:border-gray-600 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-100 dark:hover:bg-gray-700 dark:text-white"
                    >
                        Previous
                    </button>
                    <button
                        v-for="page in Math.min(5, lastPage)"
                        :key="page"
                        @click="goToPage(page)"
                        :class="{
                            'bg-gradient-to-r from-pink-500 to-rose-500 text-white': page === currentPage,
                            'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300': page !== currentPage,
                        }"
                        class="px-3 py-1 border border-gray-300 dark:border-gray-600 rounded-lg hover:shadow-md transition-all"
                    >
                        {{ page }}
                    </button>
                    <button
                        @click="goToPage(currentPage + 1)"
                        :disabled="currentPage === lastPage"
                        class="px-3 py-1 border border-gray-300 dark:border-gray-600 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-100 dark:hover:bg-gray-700 dark:text-white"
                    >
                        Next
                    </button>
                </div>
            </div>
        </div>

        <!-- Delete Modal -->
        <DeleteModal
            :item-id="orderToDelete"
            item-name="order"
            route-name="admin.orders.destroy"
            v-model:visible="showDeleteModal"
            @deleted="handleDeleteSuccess"
        />
    </AdminLayout>
</template>
