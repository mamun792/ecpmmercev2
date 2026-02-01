<script setup>
/**
 * Amazon-Style Order Management Dashboard
 * Modern UI/UX with advanced filtering, bulk operations, and real-time updates
 */
import { ref, watch, computed } from "vue";
import { Head, Link, router } from "@inertiajs/vue3";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import {
    SquarePen,
    MapPin,
    Trash2Icon,
    User,
    Mail,
    Phone,
    Eye,
    MoreVertical,
    X,
    Hash,
    Truck,
    Package,
    Clock,
    ArrowUpDown,
    ChevronLeft,
    ChevronRight,
    ChevronsLeft,
    ChevronsRight,
    AlertCircle,
    ExternalLink,
} from "lucide-vue-next";

// Custom Components
import StatusBadge from "@/Components/Order/StatusBadge.vue";
import PaymentBadge from "@/Components/Order/PaymentBadge.vue";
import OrderMetricsCard from "@/Components/Order/OrderMetricsCard.vue";
import OrderFilters from "@/Components/Order/OrderFilters.vue";
import BulkActionsBar from "@/Components/Order/BulkActionsBar.vue";

// External Components
import CourierSelectionModal from "@/Components/Couriers/CourierSelectionModal.vue";
import DeleteModal from "@/Components/Modal/DeleteModal.vue";
import StatusDropdown from "@/Components/Order/StatusDropdown.vue";
import FraudCheckerModal from "@/Components/Couriers/FraudCheckerModal.vue";
import AdminNotesModal from "@/Components/Order/AdminNotesModal.vue";

import { toast } from "@steveyuowo/vue-hot-toast";
import axios from "axios";

const props = defineProps({
    orders: Object,
    statusCounts: Object,
    cities: Array,
});

// State Management
const showDeleteModal = ref(false);
const orderToDelete = ref(null);
const selectedOrders = ref([]);
const selectAll = ref(false);
const showCourierModal = ref(false);
const selectedOrderId = ref(null);
const selectedOrder = ref(null);
const savedOrderData = ref({});
const showAllItems = ref({});
const loading = ref(false);
const isDownloading = ref(false);
const showAdminNotesModal = ref(false);
const selectedOrderForNotes = ref(null);
const showModal = ref(false);
const fraudData = ref({});
const checkFraudLoading = ref(false);
const activeActionMenu = ref(null);
const updatingOrders = ref({});

// Pagination
const currentPage = ref(props.orders.current_page || 1);
const lastPage = ref(props.orders.last_page || 1);
const from = ref(props.orders.from || 0);
const to = ref(props.orders.to || 0);
const total = ref(props.orders.total || 0);

// Filters
const initFilters = () => {
    const url = new URL(window.location.href);
    return {
        status: url.searchParams.get("status") || "",
        payment_status: url.searchParams.get("payment_status") || "",
        date_from: url.searchParams.get("date_from") || "",
        date_to: url.searchParams.get("date_to") || "",
        customer_search: url.searchParams.get("customer_search") || "",
        order_number: url.searchParams.get("order_number") || "",
        min_total: url.searchParams.get("min_total") || "",
        max_total: url.searchParams.get("max_total") || "",
        per_page: parseInt(url.searchParams.get("per_page")) || 10,
        sort_by: url.searchParams.get("sort_by") || "created_at",
        sort_direction: url.searchParams.get("sort_direction") || "desc",
    };
};

const filters = ref(initFilters());
const perPageOptions = [10, 25, 50, 100];
const paymentStatusOptions = ["unpaid", "paid", "refunded"];

// Available statuses (excluding incomplete as it has its own page)
const availableStatuses = [
    "pending",
    "processing",
    "confirmed",
    "shipped",
    "delivered",
    "cancelled",
    "returned",
    "on_hold",
];

// Computed
const visibleOrders = computed(() => props.orders?.data ?? []);

const incompleteCount = computed(() => {
    return props.statusCounts.find((s) => s.status === "incomplete")?.count || 0;
});

const totalOrdersCount = computed(() => {
    return props.statusCounts.find((s) => s.status === "total")?.count || 0;
});

const totalSales = computed(() => {
    return props.statusCounts.find((s) => s.status === "total")?.sales || 0;
});

// Helper functions
const isToday = (dateString) => {
    if (!dateString) return false;
    const d = new Date(dateString);
    const today = new Date();
    return (
        d.getFullYear() === today.getFullYear() &&
        d.getMonth() === today.getMonth() &&
        d.getDate() === today.getDate()
    );
};

const truncateText = (text, limit) => {
    if (!text) return "";
    const words = text.split(" ");
    if (words.length > limit) {
        return words.slice(0, limit).join(" ") + "...";
    }
    return text;
};

const formatOrderDate = (dateString) => {
    if (!dateString) return "";
    const date = new Date(dateString);
    return date.toLocaleDateString("en-GB", {
        day: "2-digit",
        month: "short",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    });
};

const formatCurrency = (amount) => {
    return new Intl.NumberFormat("en-BD", {
        style: "currency",
        currency: "BDT",
        minimumFractionDigits: 0,
    }).format(amount).replace("BDT", "৳");
};

// Filter Actions
let debounceTimer;
const applyFilters = () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        currentPage.value = 1;
        loading.value = true;
        router.get(
            "/admin/orders",
            { ...filters.value, page: currentPage.value },
            {
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => (loading.value = false),
                onError: () => (loading.value = false),
            }
        );
    }, 500);
};

const resetFilters = () => {
    filters.value = {
        ...initFilters(),
        status: "",
        payment_status: "",
        date_from: "",
        date_to: "",
        customer_search: "",
        order_number: "",
        min_total: "",
        max_total: "",
    };
    currentPage.value = 1;
    applyFilters();
};

const selectStatus = (status) => {
    filters.value.status = status === filters.value.status ? "" : status;
    currentPage.value = 1;
    applyFilters();
};

// Sorting
const toggleSort = (column) => {
    const validColumns = ["order_number", "customer_name", "total", "created_at", "status", "payment_status"];
    if (!validColumns.includes(column)) return;

    if (filters.value.sort_by === column) {
        filters.value.sort_direction = filters.value.sort_direction === "asc" ? "desc" : "asc";
    } else {
        filters.value.sort_by = column;
        filters.value.sort_direction = "asc";
    }
    currentPage.value = 1;
    applyFilters();
};

const getSortIcon = (column) => {
    if (filters.value.sort_by !== column) return "";
    return filters.value.sort_direction === "asc" ? "↑" : "↓";
};

// Selection
const toggleSelectAll = () => {
    selectAll.value = !selectAll.value;
    selectedOrders.value = selectAll.value ? visibleOrders.value.map((order) => order.id) : [];
};

const toggleOrderSelection = (orderId) => {
    const index = selectedOrders.value.indexOf(orderId);
    if (index === -1) {
        selectedOrders.value.push(orderId);
    } else {
        selectedOrders.value.splice(index, 1);
    }
    selectAll.value = selectedOrders.value.length === visibleOrders.value.length && visibleOrders.value.length > 0;
};

const clearSelection = () => {
    selectedOrders.value = [];
    selectAll.value = false;
};

// Pagination
const goToPage = (page) => {
    if (page < 1 || page > lastPage.value || page === currentPage.value || loading.value) return;

    loading.value = true;
    currentPage.value = page;
    router.get(
        "/admin/orders",
        { ...filters.value, page: page },
        {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                loading.value = false;
                currentPage.value = props.orders.current_page;
                lastPage.value = props.orders.last_page;
                from.value = props.orders.from;
                to.value = props.orders.to;
                total.value = props.orders.total;
            },
            onError: () => (loading.value = false),
        }
    );
};

// Order Status Update
const updateOrderStatus = async (orderId, newStatus) => {
    updatingOrders.value[orderId] = true;
    try {
        const response = await axios.put(`/orders/${orderId}/status`, { status: newStatus });
        toast.success(response.data.message || "Order status updated successfully");
        router.reload();
    } catch (error) {
        toast.error(error.response?.data?.message || "Failed to update order status");
    } finally {
        updatingOrders.value[orderId] = false;
    }
};

// Courier Functions
const openCourierModal = (order, orderId) => {
    if (!orderId) {
        toast.error("Invalid order ID");
        return;
    }
    selectedOrderId.value = orderId;
    selectedOrder.value = order;
    showCourierModal.value = true;
};

const handleCourierSubmit = (data) => {
    if (!data?.orderId) {
        toast.error("Invalid courier data");
        return;
    }
    savedOrderData.value[data.orderId] = {
        recipient_city: data.city_name,
        recipient_zone: data.zone_name,
        recipient_area: data.area_name,
        specialInstruction: data.specialInstruction || "Need to Delivery before 5 PM",
    };
    showCourierModal.value = false;
};

const sendBulkToSteadfast = async () => {
    if (selectedOrders.value.length === 0) {
        toast.error("No orders selected");
        return;
    }
    try {
        await axios.post("/api/courier/orders/bulk", { orderIds: selectedOrders.value });
        selectedOrders.value = [];
        selectAll.value = false;
        toast.success("Shipments created successfully");
    } catch (error) {
        toast.error("Failed to create shipment: " + (error.response?.data?.error || error.message));
    }
};

const sendBulkToPathao = () => {
    if (selectedOrders.value.length === 0) {
        toast.error("No orders selected");
        return;
    }
    const payload = {
        orders: selectedOrders.value.map((orderId) => ({
            orderId,
            ...savedOrderData.value[orderId],
        })),
    };
    router.post("/admin/couriers/pathao/shipments/bulk", payload, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            selectedOrders.value = [];
            selectAll.value = false;
            savedOrderData.value = {};
            toast.success("Shipments created successfully");
        },
        onError: (errors) => toast.error("Failed to create shipment: " + (errors.error || "Unknown error")),
    });
};

// Invoice Functions
const downloadBulkInvoice = async () => {
    if (selectedOrders.value.length === 0) {
        toast.error("No orders selected");
        return;
    }
    isDownloading.value = true;
    try {
        const response = await axios.post(
            "/admin/bulk-invoice/download",
            { order_ids: selectedOrders.value },
            { responseType: "blob" }
        );
        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement("a");
        link.href = url;
        link.setAttribute("download", `bulk-invoices-${new Date().toISOString()}.pdf`);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        window.URL.revokeObjectURL(url);
        toast.success("Invoices downloaded successfully");
    } catch (error) {
        toast.error("Failed to download invoices");
    } finally {
        isDownloading.value = false;
    }
};

const printBulkInvoice = () => {
    if (selectedOrders.value.length === 0) {
        toast.error("No orders selected");
        return;
    }
    const printWindow = window.open(
        `/admin/bulk-invoice/print?order_ids=${selectedOrders.value.join(",")}`,
        "_blank",
        `width=${screen.width},height=${screen.height},scrollbars=yes,resizable=yes,fullscreen=yes`
    );
    if (printWindow) {
        printWindow.focus();
    } else {
        toast.error("Failed to open print window. Please allow popups.");
    }
};

// Fraud Check
const checkFraud = async (phone) => {
    try {
        checkFraudLoading.value = true;
        const response = await axios.post("/admin/fraud-check", { phone });
        fraudData.value = response.data;
        showModal.value = true;
        toast.success(response.data.message || "Fraud check successful");
    } catch (error) {
        toast.error(error.response?.data?.message || "Failed to check fraud");
    } finally {
        checkFraudLoading.value = false;
    }
};

// Delete Modal
const openDeleteModal = (id) => {
    orderToDelete.value = id;
    showDeleteModal.value = true;
};

const handleDeleteSuccess = () => {
    showDeleteModal.value = false;
    orderToDelete.value = null;
};

// Admin Notes Modal
const openAdminNotesModal = (order) => {
    selectedOrderForNotes.value = order;
    showAdminNotesModal.value = true;
};

const closeAdminNotesModal = () => {
    showAdminNotesModal.value = false;
    selectedOrderForNotes.value = null;
};

const handleNotesSaved = () => toast.success("Admin notes saved successfully");

// Action Menu
const toggleActionMenu = (orderId) => {
    activeActionMenu.value = activeActionMenu.value === orderId ? null : orderId;
};

const toggleShowItems = (orderId) => {
    showAllItems.value[orderId] = !showAllItems.value[orderId];
};

// Watchers
watch(
    () => props.orders,
    () => {
        selectedOrders.value = [];
        selectAll.value = false;
        const currentOrderIds = props.orders.data.map((order) => order.id);
        savedOrderData.value = Object.fromEntries(
            Object.entries(savedOrderData.value).filter(([id]) => currentOrderIds.includes(Number(id)))
        );
        currentPage.value = props.orders.current_page || 1;
        lastPage.value = props.orders.last_page || 1;
        from.value = props.orders.from || 0;
        to.value = props.orders.to || 0;
        total.value = props.orders.total || 0;
    },
    { deep: true }
);
</script>

<template>
    <Head title="Order Management" />
    <AdminLayout>
        <!-- Loading Overlay -->
        <div
            v-if="checkFraudLoading || loading"
            class="fixed inset-0 bg-black/30 backdrop-blur-sm flex items-center justify-center z-50"
        >
            <div class="bg-white rounded-2xl p-6 shadow-2xl flex flex-col items-center gap-3">
                <div class="w-12 h-12 border-4 border-primary border-t-transparent rounded-full animate-spin"></div>
                <p class="text-sm font-medium text-gray-600">Loading...</p>
            </div>
        </div>

        <div class="min-h-screen bg-gray-50/50">
            <!-- Header Section -->
            <div class="bg-white border-b border-gray-200 sticky top-0 z-30">
                <div class="px-4 sm:px-6 lg:px-8 py-4">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div class="flex items-center gap-4">
                            <div class="p-2 bg-gradient-to-br from-primary to-primary/80 rounded-xl shadow-lg shadow-primary/25">
                                <Package class="w-6 h-6 text-white" />
                            </div>
                            <div>
                                <h1 class="text-2xl font-bold text-gray-900">Order Management</h1>
                                <p class="text-sm text-gray-500">Manage and track all orders</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <!-- Incomplete Orders Badge -->
                            <Link
                                :href="route('admin.orders.incomplete')"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-amber-50 to-orange-50 border border-amber-200 rounded-xl text-amber-800 hover:shadow-md transition-all duration-200 group"
                            >
                                <AlertCircle class="w-4 h-4 group-hover:animate-pulse" />
                                <span class="font-semibold">{{ incompleteCount }}</span>
                                <span class="text-sm">Incomplete</span>
                            </Link>

                            <!-- Reset Button -->
                            <button
                                @click="resetFilters"
                                class="px-4 py-2 bg-white border border-gray-200 rounded-xl text-sm font-medium text-gray-600 hover:bg-gray-50 hover:border-gray-300 transition-all"
                            >
                                Reset All
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="px-4 sm:px-6 lg:px-8 py-6 space-y-6">
                <!-- Metrics Cards -->
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-5 gap-4">
                    <!-- Total Orders Card -->
                    <OrderMetricsCard
                        title="Total Orders"
                        :count="totalOrdersCount"
                        :sales="totalSales"
                        status=""
                        :is-active="filters.status === ''"
                        @click="selectStatus('')"
                    />

                    <!-- Status Cards -->
                    <OrderMetricsCard
                        v-for="status in availableStatuses"
                        :key="status"
                        :title="status.charAt(0).toUpperCase() + status.slice(1).replace('_', ' ')"
                        :count="props.statusCounts.find((s) => s.status === status)?.count || 0"
                        :sales="props.statusCounts.find((s) => s.status === status)?.sales || 0"
                        :status="status"
                        :is-active="filters.status === status"
                        @click="selectStatus(status)"
                    />
                </div>

                <!-- Advanced Filters -->
                <OrderFilters
                    v-model="filters"
                    :payment-status-options="paymentStatusOptions"
                    @apply="applyFilters"
                    @reset="resetFilters"
                />

                <!-- Bulk Actions Bar -->
                <BulkActionsBar
                    :selected-count="selectedOrders.length"
                    :is-downloading="isDownloading"
                    @clear-selection="clearSelection"
                    @bulk-print="printBulkInvoice"
                    @bulk-download="downloadBulkInvoice"
                    @send-to-steadfast="sendBulkToSteadfast"
                    @send-to-pathao="sendBulkToPathao"
                />

                <!-- Table Controls -->
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <label class="text-sm text-gray-600">Show</label>
                        <select
                            v-model="filters.per_page"
                            @change="applyFilters"
                            class="px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary"
                        >
                            <option v-for="option in perPageOptions" :key="option" :value="option">{{ option }}</option>
                        </select>
                        <span class="text-sm text-gray-600">entries</span>
                    </div>

                    <div class="text-sm text-gray-500">
                        Showing <span class="font-semibold text-gray-900">{{ from }}</span> to
                        <span class="font-semibold text-gray-900">{{ to }}</span> of
                        <span class="font-semibold text-gray-900">{{ total }}</span> orders
                    </div>
                </div>

                <!-- Orders Table -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr class="bg-gradient-to-r from-gray-900 to-gray-800">
                                    <th class="px-4 py-4 text-left w-12">
                                        <input
                                            type="checkbox"
                                            :checked="selectAll"
                                            @change="toggleSelectAll"
                                            class="rounded border-gray-600 bg-gray-700 text-primary focus:ring-primary"
                                        />
                                    </th>
                                    <th
                                        @click="toggleSort('order_number')"
                                        class="px-4 py-4 text-left text-xs font-semibold text-white uppercase tracking-wider cursor-pointer hover:bg-white/5 transition-colors"
                                    >
                                        <div class="flex items-center gap-2">
                                            Order <span class="text-primary">{{ getSortIcon('order_number') }}</span>
                                        </div>
                                    </th>
                                    <th
                                        @click="toggleSort('customer_name')"
                                        class="px-4 py-4 text-left text-xs font-semibold text-white uppercase tracking-wider cursor-pointer hover:bg-white/5 transition-colors"
                                    >
                                        <div class="flex items-center gap-2">
                                            Customer <span class="text-primary">{{ getSortIcon('customer_name') }}</span>
                                        </div>
                                    </th>
                                    <th class="px-4 py-4 text-left text-xs font-semibold text-white uppercase tracking-wider">
                                        Products
                                    </th>
                                    <th
                                        @click="toggleSort('total')"
                                        class="px-4 py-4 text-left text-xs font-semibold text-white uppercase tracking-wider cursor-pointer hover:bg-white/5 transition-colors"
                                    >
                                        <div class="flex items-center gap-2">
                                            Total <span class="text-primary">{{ getSortIcon('total') }}</span>
                                        </div>
                                    </th>
                                    <th
                                        @click="toggleSort('status')"
                                        class="px-4 py-4 text-left text-xs font-semibold text-white uppercase tracking-wider cursor-pointer hover:bg-white/5 transition-colors"
                                    >
                                        <div class="flex items-center gap-2">
                                            Status <span class="text-primary">{{ getSortIcon('status') }}</span>
                                        </div>
                                    </th>
                                    <th
                                        @click="toggleSort('payment_status')"
                                        class="px-4 py-4 text-left text-xs font-semibold text-white uppercase tracking-wider cursor-pointer hover:bg-white/5 transition-colors"
                                    >
                                        <div class="flex items-center gap-2">
                                            Payment <span class="text-primary">{{ getSortIcon('payment_status') }}</span>
                                        </div>
                                    </th>
                                    <th class="px-4 py-4 text-left text-xs font-semibold text-white uppercase tracking-wider">
                                        Courier
                                    </th>
                                    <th class="px-4 py-4 text-center text-xs font-semibold text-white uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr
                                    v-for="order in visibleOrders"
                                    :key="order.id"
                                    class="hover:bg-gray-50/80 transition-colors duration-150"
                                    :class="{ 'bg-primary/5': selectedOrders.includes(order.id) }"
                                >
                                    <!-- Checkbox -->
                                    <td class="px-4 py-4">
                                        <input
                                            type="checkbox"
                                            :checked="selectedOrders.includes(order.id)"
                                            @change="toggleOrderSelection(order.id)"
                                            class="rounded border-gray-300 text-primary focus:ring-primary"
                                        />
                                    </td>

                                    <!-- Order Info -->
                                    <td class="px-4 py-4">
                                        <div class="space-y-1">
                                            <div class="flex items-center gap-2">
                                                <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-gradient-to-r from-emerald-500 to-emerald-600 text-white text-sm font-semibold rounded-lg shadow-sm">
                                                    {{ order.order_number }}
                                                </span>
                                                <span
                                                    v-if="order.admin_notes"
                                                    class="w-5 h-5 flex items-center justify-center bg-blue-500 rounded-full text-white text-xs font-bold"
                                                    title="Has admin notes"
                                                >
                                                    N
                                                </span>
                                            </div>
                                            <div class="flex items-center gap-2 text-xs text-gray-500">
                                                <Clock class="w-3 h-3" />
                                                <span :class="{ 'text-emerald-600 font-medium': isToday(order.date) }">
                                                    <span v-if="isToday(order.date)" class="px-1.5 py-0.5 bg-emerald-100 rounded text-emerald-700 mr-1">TODAY</span>
                                                    {{ formatOrderDate(order.date) }}
                                                </span>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Customer Info -->
                                    <td class="px-4 py-4">
                                        <div class="bg-gradient-to-br from-gray-50 to-white border border-gray-100 rounded-xl p-3 space-y-2 max-w-xs">
                                            <div class="flex items-center gap-2">
                                                <User class="w-4 h-4 text-primary" />
                                                <span class="font-medium text-gray-900 truncate">{{ truncateText(order.customer.name, 3) }}</span>
                                            </div>
                                            <div class="flex items-center gap-2 text-xs text-gray-600">
                                                <Phone class="w-3 h-3" />
                                                <span>{{ order.customer?.phone }}</span>
                                            </div>
                                            <div class="flex items-center gap-2 text-xs text-gray-600">
                                                <MapPin class="w-3 h-3" />
                                                <span class="truncate">{{ truncateText(order.customer?.address, 5) }}</span>
                                            </div>

                                            <!-- Customer Note -->
                                            <div v-if="order.customer?.note && order.customer.note !== 'N/A'" class="p-2 bg-emerald-50 border border-emerald-200 rounded-lg">
                                                <p class="text-xs text-emerald-700"><strong>Note:</strong> {{ order.customer?.note }}</p>
                                            </div>

                                            <!-- Admin Notes -->
                                            <div v-if="order.admin_notes" class="p-2 bg-blue-50 border border-blue-200 rounded-lg">
                                                <p class="text-xs text-blue-700 line-clamp-2"><strong>Admin:</strong> {{ order.admin_notes }}</p>
                                            </div>

                                            <!-- Add/Edit Notes Button -->
                                            <button
                                                @click="openAdminNotesModal(order)"
                                                class="w-full flex items-center justify-center gap-1.5 px-2 py-1.5 text-xs font-medium rounded-lg transition-colors"
                                                :class="order.admin_notes ? 'bg-blue-100 text-blue-700 hover:bg-blue-200' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                                            >
                                                <SquarePen class="w-3 h-3" />
                                                {{ order.admin_notes ? 'Edit Notes' : 'Add Notes' }}
                                            </button>
                                        </div>
                                    </td>

                                    <!-- Products -->
                                    <td class="px-4 py-4">
                                        <div class="space-y-2 max-w-sm">
                                            <template v-if="order.items.length === 0">
                                                <p class="text-gray-400 italic text-sm">No items</p>
                                            </template>
                                            <template v-else>
                                                <div
                                                    v-for="(item, idx) in order.items"
                                                    :key="item.id"
                                                    v-show="idx < (showAllItems[order.id] ? order.items.length : 2)"
                                                    class="flex items-center gap-3 p-2 bg-gray-50 border border-gray-100 rounded-lg"
                                                >
                                                    <img
                                                        :src="item.product.image || '/placeholder.png'"
                                                        :alt="item.product.name"
                                                        class="w-12 h-12 object-cover rounded-lg border border-gray-200"
                                                    />
                                                    <div class="flex-1 min-w-0">
                                                        <p class="font-medium text-sm text-gray-900 truncate">{{ truncateText(item.product.name, 3) }}</p>
                                                        <div class="flex items-center gap-2 text-xs text-gray-500">
                                                            <span>Qty: {{ item.quantity }}</span>
                                                            <span>•</span>
                                                            <span>{{ formatCurrency(item.price) }}</span>
                                                        </div>
                                                        <p v-if="item.variation" class="text-xs text-gray-400 truncate mt-0.5">
                                                            <span v-for="(value, key) in item.variation.attributes" :key="key">
                                                                {{ key }}: {{ value }}
                                                            </span>
                                                        </p>
                                                    </div>
                                                    <span
                                                        v-if="item.is_pre_order"
                                                        class="px-2 py-0.5 bg-blue-100 text-blue-700 text-xs rounded-full"
                                                    >
                                                        Pre-Order
                                                    </span>
                                                </div>
                                                <button
                                                    v-if="order.items.length > 2"
                                                    @click="toggleShowItems(order.id)"
                                                    class="text-primary hover:text-primary/80 text-xs font-medium"
                                                >
                                                    {{ showAllItems[order.id] ? 'Show Less' : `+${order.items.length - 2} more` }}
                                                </button>
                                            </template>
                                        </div>
                                    </td>

                                    <!-- Total -->
                                    <td class="px-4 py-4">
                                        <span class="text-lg font-bold text-gray-900">{{ formatCurrency(order.total) }}</span>
                                    </td>

                                    <!-- Status -->
                                    <td class="px-4 py-4">
                                        <StatusDropdown
                                            v-model="order.status"
                                            :order-id="order.id"
                                            :is-loading="updatingOrders[order.id]"
                                            @status-change="updateOrderStatus"
                                        />
                                    </td>

                                    <!-- Payment Status -->
                                    <td class="px-4 py-4">
                                        <PaymentBadge :status="order.payment_status" />
                                    </td>

                                    <!-- Courier Info -->
                                    <td class="px-4 py-4">
                                        <div v-if="order.tracking_number || order.is_courier" class="p-2 bg-gray-50 border border-gray-100 rounded-lg space-y-2">
                                            <div class="flex items-center gap-2">
                                                <Truck class="w-4 h-4 text-gray-500" />
                                                <span class="text-xs font-medium px-2 py-0.5 bg-emerald-100 text-emerald-700 rounded-full">
                                                    {{ order.courier_name || 'N/A' }}
                                                </span>
                                            </div>
                                            <div v-if="order.consignment_id" class="text-xs text-gray-600 font-mono">
                                                {{ order.consignment_id }}
                                            </div>
                                            <a
                                                v-if="order.tracking_number && order.tracking_number !== 'N/A'"
                                                :href="`https://steadfast.com.bd/t/${order.tracking_number}`"
                                                target="_blank"
                                                class="inline-flex items-center gap-1 text-xs text-primary hover:underline"
                                            >
                                                Track <ExternalLink class="w-3 h-3" />
                                            </a>
                                            <div v-if="order.area_id" class="flex items-center gap-1">
                                                <MapPin class="w-3 h-3 text-emerald-500" />
                                                <span class="text-xs text-emerald-600">Address Saved</span>
                                            </div>
                                        </div>
                                        <span v-else class="text-xs text-gray-400">No courier</span>
                                    </td>

                                    <!-- Actions -->
                                    <td class="px-4 py-4">
                                        <div class="relative flex justify-center">
                                            <button
                                                @click.stop="toggleActionMenu(order.id)"
                                                class="p-2 rounded-lg hover:bg-gray-100 transition-colors focus:outline-none focus:ring-2 focus:ring-primary/20"
                                                :class="{ 'bg-primary/10 text-primary': activeActionMenu === order.id }"
                                            >
                                                <component :is="activeActionMenu === order.id ? X : MoreVertical" class="w-5 h-5" />
                                            </button>

                                            <!-- Dropdown Menu -->
                                            <transition
                                                enter-active-class="transition ease-out duration-100"
                                                enter-from-class="transform opacity-0 scale-95"
                                                enter-to-class="transform opacity-100 scale-100"
                                                leave-active-class="transition ease-in duration-75"
                                                leave-from-class="transform opacity-100 scale-100"
                                                leave-to-class="transform opacity-0 scale-95"
                                            >
                                                <div
                                                    v-if="activeActionMenu === order.id"
                                                    class="absolute right-0 top-full mt-2 w-48 bg-white rounded-xl shadow-xl border border-gray-100 z-50 overflow-hidden"
                                                >
                                                    <div class="py-1">
                                                        <button
                                                            @click="checkFraud(order?.customer?.phone); toggleActionMenu(order.id);"
                                                            class="w-full text-left px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 hover:text-primary flex items-center gap-3 transition-colors"
                                                        >
                                                            <User class="w-4 h-4" />
                                                            Check Fraud
                                                        </button>
                                                        <button
                                                            @click="openCourierModal(order, order.id); toggleActionMenu(order.id);"
                                                            class="w-full text-left px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 hover:text-emerald-600 flex items-center gap-3 transition-colors"
                                                        >
                                                            <MapPin class="w-4 h-4" />
                                                            Couriers
                                                        </button>
                                                        <Link
                                                            :href="route('admin.orders.edit', order.id)"
                                                            class="w-full text-left px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 hover:text-amber-600 flex items-center gap-3 transition-colors"
                                                        >
                                                            <SquarePen class="w-4 h-4" />
                                                            Edit Order
                                                        </Link>
                                                        <div class="border-t border-gray-100 my-1"></div>
                                                        <button
                                                            @click="openDeleteModal(order.id); toggleActionMenu(order.id);"
                                                            class="w-full text-left px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 flex items-center gap-3 transition-colors"
                                                        >
                                                            <Trash2Icon class="w-4 h-4" />
                                                            Delete
                                                        </button>
                                                    </div>
                                                </div>
                                            </transition>

                                            <!-- Backdrop -->
                                            <div
                                                v-if="activeActionMenu === order.id"
                                                class="fixed inset-0 z-40"
                                                @click="activeActionMenu = null"
                                            ></div>
                                        </div>
                                    </td>
                                </tr>

                                <!-- Empty State -->
                                <tr v-if="visibleOrders.length === 0">
                                    <td colspan="9" class="px-4 py-16 text-center">
                                        <div class="flex flex-col items-center">
                                            <Package class="w-16 h-16 text-gray-300 mb-4" />
                                            <h3 class="text-lg font-semibold text-gray-900">No orders found</h3>
                                            <p class="text-gray-500 mt-1">Try adjusting your filters or search criteria</p>
                                            <button
                                                @click="resetFilters"
                                                class="mt-4 px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors"
                                            >
                                                Reset Filters
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="flex items-center justify-between px-4 py-4 border-t border-gray-200 bg-gray-50/50">
                        <div class="text-sm text-gray-600">
                            Showing <span class="font-semibold">{{ from }}</span> to
                            <span class="font-semibold">{{ to }}</span> of
                            <span class="font-semibold">{{ total }}</span> orders
                        </div>

                        <div class="flex items-center gap-1">
                            <button
                                @click="goToPage(1)"
                                :disabled="currentPage === 1 || loading"
                                class="p-2 rounded-lg hover:bg-gray-200 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                            >
                                <ChevronsLeft class="w-4 h-4" />
                            </button>
                            <button
                                @click="goToPage(currentPage - 1)"
                                :disabled="currentPage === 1 || loading"
                                class="p-2 rounded-lg hover:bg-gray-200 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                            >
                                <ChevronLeft class="w-4 h-4" />
                            </button>

                            <template v-if="lastPage <= 7">
                                <button
                                    v-for="page in lastPage"
                                    :key="page"
                                    @click="goToPage(page)"
                                    :disabled="loading"
                                    class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors"
                                    :class="currentPage === page ? 'bg-primary text-white' : 'hover:bg-gray-200'"
                                >
                                    {{ page }}
                                </button>
                            </template>
                            <template v-else>
                                <button v-if="currentPage > 3" @click="goToPage(1)" class="px-3 py-1.5 rounded-lg text-sm hover:bg-gray-200">1</button>
                                <span v-if="currentPage > 4" class="px-2 text-gray-400">...</span>
                                <template v-for="page in lastPage" :key="page">
                                    <button
                                        v-if="page >= currentPage - 1 && page <= currentPage + 1"
                                        @click="goToPage(page)"
                                        class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors"
                                        :class="currentPage === page ? 'bg-primary text-white' : 'hover:bg-gray-200'"
                                    >
                                        {{ page }}
                                    </button>
                                </template>
                                <span v-if="currentPage < lastPage - 3" class="px-2 text-gray-400">...</span>
                                <button v-if="currentPage < lastPage - 2" @click="goToPage(lastPage)" class="px-3 py-1.5 rounded-lg text-sm hover:bg-gray-200">{{ lastPage }}</button>
                            </template>

                            <button
                                @click="goToPage(currentPage + 1)"
                                :disabled="currentPage === lastPage || loading"
                                class="p-2 rounded-lg hover:bg-gray-200 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                            >
                                <ChevronRight class="w-4 h-4" />
                            </button>
                            <button
                                @click="goToPage(lastPage)"
                                :disabled="currentPage === lastPage || loading"
                                class="p-2 rounded-lg hover:bg-gray-200 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                            >
                                <ChevronsRight class="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modals -->
        <DeleteModal
            :item-id="orderToDelete"
            item-name="order"
            route-name="admin.orders.destroy"
            v-model:visible="showDeleteModal"
            @deleted="handleDeleteSuccess"
        />

        <CourierSelectionModal
            :show="showCourierModal"
            :cities="cities?.data?.data || []"
            :orderId="selectedOrderId"
            :order="selectedOrder"
            @close="showCourierModal = false"
            @submit="handleCourierSubmit"
        />

        <FraudCheckerModal
            :visible="showModal"
            :fraudData="fraudData"
            @close="showModal = false"
        />

        <AdminNotesModal
            :visible="showAdminNotesModal"
            :order-id="selectedOrderForNotes?.id"
            :order-number="selectedOrderForNotes?.order_number"
            :initial-notes="selectedOrderForNotes?.admin_notes"
            :customer-note="selectedOrderForNotes?.customer?.note"
            @close="closeAdminNotesModal"
            @saved="handleNotesSaved"
        />
    </AdminLayout>
</template>
