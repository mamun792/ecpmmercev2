<script setup>
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
    CheckCircle,
    XCircle,
    AlertCircle,
    ExternalLink,
} from "lucide-vue-next";
import CourierSelectionModal from "@/Components/Couriers/CourierSelectionModal.vue";
import DeleteModal from "@/Components/Modal/DeleteModal.vue";
import { toast } from "@steveyuowo/vue-hot-toast";
import axios from "axios";
import StatusDropdown from "@/Components/Order/StatusDropdown.vue";
import StatusChangeModal from "@/Components/Order/StatusChangeModal.vue";
import FraudCheckerModal from "@/Components/Couriers/FraudCheckerModal.vue";
import AdminNotesModal from "@/Components/Order/AdminNotesModal.vue";
import AdvancedFilters from "@/Components/Order/AdvancedFilters.vue";
import FilterChips from "@/Components/Order/FilterChips.vue";

const props = defineProps({
    orders: Object,
    statusCounts: Object,
    filterMeta: Object,
    cities: Array,
});

const showDeleteModal = ref(false);
const orderToDelete = ref(null);
const selectedOrders = ref([]);
const selectAll = ref(false);
const showCourierModal = ref(false);
const selectedOrderId = ref(null);
const selectedOrder = ref(null);
const savedOrderData = ref({});
const showAllItems = ref(false);
const loading = ref(false);
const isDownloading = ref(false); // New loading state for invoice download
const showAdminNotesModal = ref(false);
const selectedOrderForNotes = ref(null);
const viewMode = ref("table"); // View mode toggle - default to table

// Status Change Modal
const showStatusChangeModal = ref(false);
const statusChangeOrder = ref(null);

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
        status: url.searchParams.get("status") || "",
        payment_status: url.searchParams.get("payment_status") || "",
        date_from: url.searchParams.get("date_from") || "",
        date_to: url.searchParams.get("date_to") || "",
        customer_search: url.searchParams.get("customer_search") || "",
        order_number: url.searchParams.get("order_number") || "",
        min_total: url.searchParams.get("min_total") || "",
        max_total: url.searchParams.get("max_total") || "",
        date_preset: url.searchParams.get("date_preset") || "", // NEW
        shipping_area: url.searchParams.get("shipping_area") || "", // NEW
        has_courier: url.searchParams.get("has_courier") || "", // NEW
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

// 🔄 Enhanced Select All Logic
const isAllSelected = computed(() => {
    if (!visibleOrders.value || visibleOrders.value.length === 0) return false;
    return visibleOrders.value.every(order => selectedOrders.value.includes(order.id));
});

const isPartiallySelected = computed(() => {
    if (!visibleOrders.value || visibleOrders.value.length === 0) return false;
    return selectedOrders.value.length > 0 && selectedOrders.value.length < visibleOrders.value.length;
});

// Handle select all checkbox change
const handleSelectAllChange = () => {
    console.log("Select all changed. Current isAllSelected:", isAllSelected.value);
    console.log("Visible orders:", visibleOrders.value?.length);
    console.log("Selected orders before:", selectedOrders.value);

    if (isAllSelected.value) {
        // Deselect all visible orders
        selectedOrders.value = selectedOrders.value.filter(
            orderId => !visibleOrders.value.some(order => order.id === orderId)
        );
    } else {
        // Select all visible orders
        const visibleOrderIds = visibleOrders.value.map(order => order.id);
        const newSelections = visibleOrderIds.filter(id => !selectedOrders.value.includes(id));
        selectedOrders.value = [...selectedOrders.value, ...newSelections];
    }

    console.log("Selected orders after:", selectedOrders.value);
    selectAll.value = isAllSelected.value;
};

// Handle individual order selection change
const handleOrderSelectionChange = (orderId) => {
    console.log("Order selection changed for ID:", orderId);
    console.log("Selected orders before:", selectedOrders.value);

    const index = selectedOrders.value.indexOf(orderId);
    if (index === -1) {
        selectedOrders.value.push(orderId);
    } else {
        selectedOrders.value.splice(index, 1);
    }

    console.log("Selected orders after:", selectedOrders.value);
    console.log("Is all selected now:", isAllSelected.value);
    selectAll.value = isAllSelected.value;
};

// Legacy functions for backward compatibility
const toggleSelectAll = () => handleSelectAllChange();
const toggleOrderSelection = (orderId) => handleOrderSelectionChange(orderId);

// Open courier modal
const openCourierModal = (order, orderId) => {
    if (!orderId) {
        console.error("Order ID is undefined");
        toast.error("Invalid order ID");
        return;
    }
    selectedOrderId.value = orderId;
    selectedOrder.value = order;
    showCourierModal.value = true;
};

// Handle courier submission for individual order
const handleCourierSubmit = (data) => {
    if (!data || !data.orderId) {
        console.error("Invalid data received in handleCourierSubmit:", data);
        toast.error("Invalid courier data");
        return;
    }
    savedOrderData.value[data.orderId] = {
        recipient_city: data.city_name,
        recipient_zone: data.zone_name,
        recipient_area: data.area_name,
        specialInstruction:
            data.specialInstruction || "Need to Delivery before 5 PM",
    };
    showCourierModal.value = false;
};

// Send selected orders to Pathao
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
        onError: (errors) => {
            toast.error(
                "Failed to create shipment: " +
                    (errors.error || "Unknown error")
            );
        },
    });
};

const sendBulkToSteadfast = async () => {
    if (selectedOrders.value.length === 0) {
        toast.error("No orders selected");
        return;
    }

    const payload = {
        orderIds: selectedOrders.value,
    };

    try {
        await axios.post("/api/courier/orders/bulk", payload);

        selectedOrders.value = [];
        selectAll.value = false;
        savedOrderData.value = {};

        toast.success("Shipments created successfully");
    } catch (error) {
        const message =
            error.response?.data?.error || error.message || "Unknown error";

        toast.error("Failed to create shipment: " + message);
    }
};

// Download bulk invoices
const downloadBulkInvoice = async () => {
    if (selectedOrders.value.length === 0) {
        toast.error("No orders selected");
        return;
    }

    isDownloading.value = true; // Set loading state to true
    try {
        const response = await axios.post(
            "/admin/bulk-invoice/download",
            { order_ids: selectedOrders.value },
            { responseType: "blob" }
        );

        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement("a");
        link.href = url;
        link.setAttribute(
            "download",
            `bulk-invoices-${new Date().toISOString()}.pdf`
        );
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        window.URL.revokeObjectURL(url);

        toast.success("Invoices downloaded successfully");
    } catch (error) {
        console.error("Error downloading invoices:", error);
        toast.error(
            "Failed to download invoices: " +
                (error.response?.data?.message || "Unknown error")
        );
    } finally {
        isDownloading.value = false; // Reset loading state
    }
};

// Status and payment status options
const statusOptions = [
    "pending",
    "processing",
    "completed",
    "cancelled",
    "incomplete",
    "on_hold",
    "confirmed",
];
const paymentStatusOptions = ["unpaid", "paid", "refunded"];
const availableStatuses = [
    "pending",
    "processing",
    "cancelled",
    "shipped",
    "delivered",
    "returned",
    "incomplete",
    "on_hold",
    "confirmed",
];

// Helper to determine if a date string is today (date portion only)
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

// Expose all orders by default
const visibleOrders = computed(() => {
    return props.orders?.data ?? [];
});

// Apply filters with debounce
let debounceTimer;
const applyFilters = () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        currentPage.value = 1; // Reset to page 1 on filter change
        router.get(
            "/admin/orders",
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

// Handle status card click
const selectStatus = (status) => {
    filters.value.status = status === filters.value.status ? "" : status;
    currentPage.value = 1; // Reset to page 1 when status changes
    applyFilters();
};

// Handle sorting
const toggleSort = (column) => {
    const validColumns = [
        "id",
        "order_number",
        "status",
        "customer_email",
        "customer_name",
        "total",
        "created_at",
        "payment_status",
    ];
    if (!validColumns.includes(column)) return;
    if (filters.value.sort_by === column) {
        filters.value.sort_direction =
            filters.value.sort_direction === "asc" ? "desc" : "asc";
    } else {
        filters.value.sort_by = column;
        filters.value.sort_direction = "asc";
    }
    currentPage.value = 1; // Reset to page 1 when sorting changes
    applyFilters();
};

const truncateText = (text, limit) => {
    const words = text.split(" ");
    if (words.length > limit) {
        return words.slice(0, limit).join(" ") + "...";
    }
    return text;
};

// Get sort icon
const getSortIcon = (column) => {
    if (filters.value.sort_by !== column) return "";
    return filters.value.sort_direction === "asc" ? "↑" : "↓";
};

// Format date
const formatOrderDate = (dateString) => {
    if (!dateString) return "";
    return new Date(dateString).toLocaleString();
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
            onError: () => {
                loading.value = false;
            },
        }
    );
};

// Watch filters and orders
watch(filters, applyFilters, { deep: true });
watch(
    () => props.orders,
    () => {
        selectedOrders.value = [];
        selectAll.value = false;
        const currentOrderIds = props.orders.data.map((order) => order.id);
        savedOrderData.value = Object.fromEntries(
            Object.entries(savedOrderData.value).filter(([id]) =>
                currentOrderIds.includes(Number(id))
            )
        );
        // Update pagination values
        currentPage.value = props.orders.current_page || 1;
        lastPage.value = props.orders.last_page || 1;
        from.value = props.orders.from || 0;
        to.value = props.orders.to || 0;
        total.value = props.orders.total || 0;
    },
    { deep: true }
);

// Create a loading state map to track which orders are being updated
const updatingOrders = ref({});

// Open status change modal
const openStatusChangeModal = (order) => {
    statusChangeOrder.value = order;
    showStatusChangeModal.value = true;
};

// Confirm status change from modal
const confirmStatusChange = async (newStatus) => {
    if (!statusChangeOrder.value) return;

    const orderId = statusChangeOrder.value.id;
    updatingOrders.value[orderId] = true;
    showStatusChangeModal.value = false;

    try {
        const response = await axios.put(`/orders/${orderId}/status`, {
            status: newStatus,
        });
        toast.success(
            response.data.message || "Order status updated successfully"
        );
        router.reload();
    } catch (error) {
        console.error("Error updating order status:", error);
        toast.error(
            error.response?.data?.message || "Failed to update order status"
        );
    } finally {
        updatingOrders.value[orderId] = false;
        statusChangeOrder.value = null;
    }
};

// Legacy status update function (keeping for backward compatibility)
const updateOrderStatus = async (orderId, newStatus) => {
    updatingOrders.value[orderId] = true;

    try {
        const response = await axios.put(`/orders/${orderId}/status`, {
            status: newStatus,
        });
        toast.success(
            response.data.message || "Order status updated successfully"
        );
        router.reload();
    } catch (error) {
        console.error("Error updating order status:", error);
        toast.error(
            error.response?.data?.message || "Failed to update order status"
        );
    } finally {
        updatingOrders.value[orderId] = false;
    }
};

const showModal = ref(false);
const fraudData = ref({});

const checkFraudLoading = ref(false);
const showFilters = ref(false);

const checkFraud = async (phone) => {
    try {
        checkFraudLoading.value = true;
        const response = await axios.post("/admin/fraud-check", { phone });
        fraudData.value = response.data;
        showModal.value = true;
        toast.success(response.data.message || "Fraud check successful");
    } catch (error) {
        console.error("Fraud check error:", error);
        toast.error(error.response?.data?.message || "Failed to check fraud");
    } finally {
        checkFraudLoading.value = false;
    }
};

const toggleFilters = () => {
    showFilters.value = !showFilters.value;
};

// Print bulk invoices
const printBulkInvoice = () => {
    if (selectedOrders.value.length === 0) {
        toast.error("No orders selected");
        return;
    }

    // Open new window with bulk print URL
    const printWindow = window.open(
        `/admin/bulk-invoice/print?order_ids=${selectedOrders.value.join(",")}`,
        "_blank",
        "width=" +
            screen.width +
            ",height=" +
            screen.height +
            ",scrollbars=yes,resizable=yes,fullscreen=yes"
    );

    if (printWindow) {
        printWindow.focus();
    } else {
        toast.error(
            "Failed to open print window. Please allow popups for this site."
        );
    }
};

// Action Menu State
const activeActionMenu = ref(null);

const toggleActionMenu = (orderId) => {
    if (activeActionMenu.value === orderId) {
        activeActionMenu.value = null;
    } else {
        activeActionMenu.value = orderId;
    }
};

// Admin Notes Modal Functions
const openAdminNotesModal = (order) => {
    selectedOrderForNotes.value = order;
    showAdminNotesModal.value = true;
};

const closeAdminNotesModal = () => {
    showAdminNotesModal.value = false;
    selectedOrderForNotes.value = null;
};

const handleNotesSaved = (newNotes) => {
    // The page will be refreshed by Inertia after successful save
    toast.success('Admin notes saved successfully');
};

// Filter chip removal functions
const removeFilter = (filterKey) => {
    if (filterKey === 'date_range') {
        filters.value.date_from = '';
        filters.value.date_to = '';
    } else if (filterKey === 'amount_range') {
        filters.value.min_total = '';
        filters.value.max_total = '';
    } else {
        filters.value[filterKey] = '';
    }
    applyFilters();
};

const clearAllFilters = () => {
    // Reset all filter values
    filters.value = {
        status: '',
        payment_status: '',
        customer_search: '',
        order_number: '',
        date_from: '',
        date_to: '',
        date_preset: '',
        min_total: '',
        max_total: '',
        shipping_area: '',
        has_courier: '',
        per_page: filters.value.per_page || 10,
        sort_by: filters.value.sort_by || 'created_at',
        sort_direction: filters.value.sort_direction || 'desc',
    };
    // Apply the cleared filters immediately
    applyFilters();
};

// Close menu when clicking outside (optional but good for UX)
// For simplicity in this step, we'll just rely on the toggle or clicking another one.
</script>

<template>
    <Head title="Order Management" />
    <AdminLayout>
        <div
            v-if="checkFraudLoading"
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
        >
            <div
                class="w-12 h-12 border-4 border-white border-t-transparent rounded-full animate-spin"
            ></div>
        </div>
        <!-- <pre>{{ orders }}</pre> -->

        <div class="order_management p-4 rounded-xl">
            <!-- Clean Header -->
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-4 gap-3">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-blue-600 flex items-center justify-center">
                        <span class="text-white text-xl">📦</span>
                    </div>
                    <div>
                        <h1 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-gray-100">Orders Management</h1>
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ from }}-{{ to }} of {{ total }} orders • Manage efficiently</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <Link :href="route('admin.orders.incomplete')" class="inline-flex items-center gap-2 bg-amber-100 hover:bg-amber-200 text-amber-800 px-3 py-2 rounded-lg text-sm font-semibold">
                        ⏰ Incomplete: {{ props.statusCounts.find(s => s.status === 'incomplete')?.count || 0 }}
                    </Link>
                    <button @click="resetFilters" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-2 rounded-lg text-sm font-semibold flex items-center gap-1">
                        🔄 <span class="hidden sm:inline">Reset</span>
                    </button>
                </div>
            </div>

            <!-- Status Cards -->
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3 mb-4">
                <!-- Total Orders Card - Enhanced -->
                <div
                    @click="selectStatus('')"
                    class="relative overflow-hidden rounded-xl shadow-sm hover:shadow-lg cursor-pointer transition-all duration-300 group touch-manipulation"
                    :class="{
                        'bg-gradient-to-br from-blue-600 to-blue-800 text-white ring-4 ring-blue-400 ring-offset-2 shadow-xl':
                            filters.status === '',
                        'bg-white hover:bg-blue-50 border border-gray-200 hover:border-blue-300':
                            filters.status !== '',
                    }"
                    title="Click to view all orders regardless of status"
                >
                    <div class="p-4 sm:p-5 relative z-10">
                        <div class="flex justify-between items-start">
                            <div class="flex-1">
                                <h3
                                    class="text-sm font-bold mb-1 flex items-center gap-2"
                                    :class="
                                        filters.status === ''
                                            ? 'text-blue-100'
                                            : 'text-gray-600'
                                    "
                                >
                                    <span>📦</span>
                                    <span class="hidden sm:inline">Total Orders</span>
                                    <span class="sm:hidden">Total</span>
                                </h3>
                                <p
                                    class="text-2xl sm:text-3xl font-black"
                                    :class="
                                        filters.status === ''
                                            ? 'text-white'
                                            : 'text-gray-900'
                                    "
                                >
                                    {{
                                        props.statusCounts.find(
                                            (s) => s.status === "total"
                                        )?.count || 0
                                    }}
                                </p>
                                <p
                                    class="text-xs sm:text-sm mt-1 font-semibold flex items-center gap-1"
                                    :class="
                                        filters.status === ''
                                            ? 'text-blue-100'
                                            : 'text-gray-600'
                                    "
                                >
                                    <span>💰</span>
                                    <span>৳{{ Number(props.statusCounts.find(
                                        (s) => s.status === "total"
                                    )?.sales || 0).toFixed(2) }}</span>
                                </p>
                            </div>
                            <div
                                class="p-2 rounded-lg"
                                :class="
                                    filters.status === ''
                                        ? 'bg-blue-500/30 text-white'
                                        : 'bg-blue-100 text-blue-600'
                                "
                            >
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="h-6 w-6"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                                    />
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>



                <div
                    v-for="status in availableStatuses.filter(s => s !== 'incomplete')"
                    :key="status"
                    @click="selectStatus(status)"
                    class="relative overflow-hidden rounded-xl shadow-sm hover:shadow-md cursor-pointer transition-all duration-300"
                    :class="{
                        'bg-gradient-to-br from-blue-600 to-blue-800 text-white ring-2 ring-blue-400 ring-offset-2':
                            filters.status === status && status === 'pending',
                        'bg-gradient-to-br from-emerald-500 to-emerald-700 text-white ring-2 ring-emerald-400 ring-offset-2':
                            filters.status === status &&
                            status === 'processing',
                        'bg-gradient-to-br from-rose-500 to-rose-700 text-white ring-2 ring-rose-400 ring-offset-2':
                            filters.status === status && status === 'cancelled',
                        'bg-gradient-to-br from-amber-500 to-amber-700 text-white ring-2 ring-amber-400 ring-offset-2':
                            filters.status === status && status === 'shipped',
                        'bg-gradient-to-br from-indigo-500 to-indigo-700 text-white ring-2 ring-indigo-400 ring-offset-2':
                            filters.status === status && status === 'delivered',
                        'bg-gradient-to-br from-primary to-primary text-white ring-2 ring-primary ring-offset-2':
                            filters.status === status && status === 'returned',
                        'bg-gradient-to-br from-slate-600 to-slate-800 text-white ring-2 ring-slate-400 ring-offset-2':
                            filters.status === status &&
                            status === 'incomplete',
                        'bg-gradient-to-br from-cyan-500 to-cyan-700 text-white ring-2 ring-cyan-400 ring-offset-2':
                            filters.status === status && status === 'on_hold',
                        'bg-gradient-to-br from-violet-500 to-violet-700 text-white ring-2 ring-violet-400 ring-offset-2':
                            filters.status === status && status === 'confirmed',

                        'bg-white hover:bg-gray-50 border border-gray-200':
                            filters.status !== status,
                    }"
                >
                    <div class="p-5 relative z-10">
                        <div class="flex justify-between items-start">
                            <div class="flex-1">
                                <h3
                                    class="text-sm font-medium mb-1"
                                    :class="
                                        filters.status === status
                                            ? 'text-white/90'
                                            : 'text-gray-500'
                                    "
                                >
                                    {{
                                        status.charAt(0).toUpperCase() +
                                        status.slice(1)
                                    }}
                                </h3>
                                <p
                                    class="text-3xl font-bold"
                                    :class="
                                        filters.status === status
                                            ? 'text-white'
                                            : 'text-gray-800'
                                    "
                                >
                                    {{
                                        props.statusCounts.find(
                                            (s) => s.status === status
                                        )?.count || 0
                                    }}
                                </p>
                                <p
                                    class="text-sm mt-1"
                                    :class="
                                        filters.status === status
                                            ? 'text-white/80'
                                            : 'text-gray-600'
                                    "
                                >
                                    ৳{{ Number(props.statusCounts.find(
                                        (s) => s.status === status
                                    )?.sales || 0).toFixed(2) }}
                                </p>
                            </div>
                            <!-- Icon based on status -->
                            <div
                                class="p-2 rounded-lg"
                                :class="{
                                    'bg-white/20 text-white':
                                        filters.status === status,
                                    'bg-blue-100 text-blue-600':
                                        filters.status !== status &&
                                        status === 'pending',
                                    'bg-emerald-100 text-emerald-600':
                                        filters.status !== status &&
                                        status === 'processing',
                                    'bg-rose-100 text-rose-600':
                                        filters.status !== status &&
                                        status === 'cancelled',
                                    'bg-amber-100 text-amber-600':
                                        filters.status !== status &&
                                        status === 'shipped',
                                    'bg-indigo-100 text-indigo-600':
                                        filters.status !== status &&
                                        status === 'delivered',
                                    'bg-primary/10 text-primary':
                                        filters.status !== status &&
                                        status === 'returned',
                                    'bg-slate-100 text-slate-600':
                                        filters.status !== status &&
                                        status === 'incomplete',
                                    'bg-cyan-100 text-cyan-600':
                                        filters.status !== status &&
                                        status === 'on_hold',
                                    'bg-violet-100 text-violet-600':
                                        filters.status !== status &&
                                        status === 'confirmed',
                                }"
                            >
                                <span class="text-xs font-bold">
                                    {{ status.substring(0, 2).toUpperCase() }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Status Filters -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-3 mb-4">
                <div class="flex flex-wrap items-center gap-2">
                    <button
                        @click="selectStatus('')"
                        :class="[
                            'px-3 py-2 text-sm font-semibold rounded-lg transition-all',
                            filters.status === ''
                                ? 'bg-blue-600 text-white'
                                : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                        ]"
                    >
                        📦 All ({{ props.statusCounts.find((s) => s.status === 'total')?.count || 0 }})
                    </button>

                    <button
                        @click="selectStatus('pending')"
                        :class="[
                            'px-3 py-2 text-sm font-semibold rounded-lg transition-all',
                            filters.status === 'pending'
                                ? 'bg-amber-600 text-white'
                                : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                        ]"
                    >
                        ⏰ Pending ({{ props.statusCounts.find((s) => s.status === 'pending')?.count || 0 }})
                    </button>

                    <button
                        @click="selectStatus('processing')"
                        :class="[
                            'px-3 py-2 text-sm font-semibold rounded-lg transition-all',
                            filters.status === 'processing'
                                ? 'bg-green-600 text-white'
                                : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                        ]"
                    >
                        ⚙️ Processing ({{ props.statusCounts.find((s) => s.status === 'processing')?.count || 0 }})
                    </button>
                    <button
                        @click="selectStatus('shipped')"
                        :class="[
                            'px-3 py-2 text-sm font-semibold rounded-lg transition-all',
                            filters.status === 'shipped'
                                ? 'bg-purple-600 text-white'
                                : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                        ]"
                    >
                        🚚 Shipped ({{ props.statusCounts.find((s) => s.status === 'shipped')?.count || 0 }})
                    </button>
                    <button
                        @click="selectStatus('delivered')"
                        :class="[
                            'px-3 py-2 text-sm font-semibold rounded-lg transition-all',
                            filters.status === 'delivered'
                                ? 'bg-green-600 text-white'
                                : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                        ]"
                    >
                        ✅ Delivered ({{ props.statusCounts.find((s) => s.status === 'delivered')?.count || 0 }})
                    </button>
                    <button
                        @click="selectStatus('cancelled')"
                        :class="[
                            'px-3 py-2 text-sm font-semibold rounded-lg transition-all',
                            filters.status === 'cancelled'
                                ? 'bg-red-600 text-white'
                                : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                        ]"
                    >
                        ❌ Cancelled ({{ props.statusCounts.find((s) => s.status === 'cancelled')?.count || 0 }})
                    </button>
                </div>
            </div>

            <!-- Filter Chips - Show Active Filters -->
            <FilterChips
                :filters="filters"
                @remove="removeFilter"
                @clear-all="clearAllFilters"
            />

            <!-- Advanced Filters Component -->
            <AdvancedFilters
                v-model="filters"
                :status-counts="statusCounts"
                @apply="applyFilters"
                @reset="resetFilters"
                class="mb-6"
            />

            <!-- Bulk Actions -->
            <div class="mb-4" v-if="selectedOrders.length > 0">
                <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-700 p-3">
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                        <div class="flex items-center gap-2">
                            <span class="text-lg">⚡</span>
                            <span class="font-semibold text-gray-900 dark:text-gray-100">{{ selectedOrders.length }} orders selected</span>
                        </div>

                        <div class="flex flex-wrap items-center gap-3 w-full sm:w-auto">
                            <!-- Steadfast Courier Button -->
                            <button
                                @click="sendBulkToSteadfast"
                                class="inline-flex items-center gap-2 bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white px-4 py-2.5 rounded-xl text-sm font-bold shadow-sm transition-all duration-300 active:scale-95 touch-manipulation flex-shrink-0"
                                :disabled="isDownloading"
                                title="Send selected orders to Steadfast courier service"
                            >
                                <span class="text-lg">🚚</span>
                                <span class="hidden sm:inline">Send to Steadfast</span>
                                <span class="sm:hidden">Steadfast</span>
                                <span class="px-2 py-0.5 bg-blue-700 text-white rounded-full text-xs font-black">{{ selectedOrders.length }}</span>
                            </button>

                            <!-- Pathao Courier Button -->
                            <button
                                @click="sendBulkToPathao"
                                class="inline-flex items-center gap-2 bg-gradient-to-r from-emerald-500 to-emerald-600 hover:from-emerald-600 hover:to-emerald-700 text-white px-4 py-2.5 rounded-xl text-sm font-bold shadow-sm transition-all duration-300 active:scale-95 touch-manipulation flex-shrink-0"
                                :disabled="isDownloading"
                                title="Send selected orders to Pathao courier service"
                            >
                                <span class="text-lg">🛵</span>
                                <span class="hidden sm:inline">Send to Pathao</span>
                                <span class="sm:hidden">Pathao</span>
                                <span class="px-2 py-0.5 bg-emerald-700 text-white rounded-full text-xs font-black">{{ selectedOrders.length }}</span>
                            </button>

                            <!-- Print Invoices Button -->
                            <button
                                @click="printBulkInvoice"
                                class="inline-flex items-center gap-2 bg-gradient-to-r from-purple-500 to-purple-600 hover:from-purple-600 hover:to-purple-700 text-white px-4 py-2.5 rounded-xl text-sm font-bold shadow-sm transition-all duration-300 active:scale-95 touch-manipulation flex-shrink-0"
                                title="Print invoices for all selected orders"
                            >
                                <span class="text-lg">🖨️</span>
                                <span class="hidden sm:inline">Print Invoices</span>
                                <span class="sm:hidden">Print</span>
                                <span class="px-2 py-0.5 bg-purple-700 text-white rounded-full text-xs font-black">{{ selectedOrders.length }}</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

<!-- Per Page Settings -->
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                    <label class="text-sm text-gray-600 dark:text-gray-400">Show:</label>
                    <select
                        v-model="filters.per_page"
                        @change="updateFilters"
                        class="px-3 py-1 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm"
                    >
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                    <span class="text-sm text-gray-600 dark:text-gray-400">per page</span>
                </div>
            </div>

                <!-- 🎯 Enhanced Beautiful Orders Table -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                <!-- 🎨 Compact Table Header -->
                <div class="bg-gradient-to-r from-blue-50 via-indigo-50 to-purple-50 dark:from-gray-900 dark:via-gray-900 dark:to-gray-900 border-b border-gray-200 dark:border-gray-700 p-3 md:p-4">
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
                        <div class="flex items-center gap-2 md:gap-3">
                            <div class="w-8 h-8 md:w-10 md:h-10 rounded-xl bg-gradient-to-br from-blue-500 via-purple-500 to-pink-500 flex items-center justify-center shadow-lg">
                                <span class="text-white text-sm md:text-lg">📋</span>
                            </div>
                            <div>
                                <h3 class="text-sm md:text-lg font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
                                    Orders Management
                                </h3>
                                <p class="text-xs text-gray-600 dark:text-gray-400 mt-0.5">
                                    📊 {{ from }}-{{ to }} of {{ total }} orders
                                </p>
                            </div>
                        </div>
                        <div class="bg-white dark:bg-gray-800 px-2 py-1 md:px-3 md:py-1.5 rounded-lg shadow-sm border border-gray-200 dark:border-gray-600">
                            <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">📈 Live</span>
                        </div>
                    </div>
                </div>

                <!-- 📱 Responsive Table Container -->
                <div class="overflow-x-auto scrollbar-thin scrollbar-track-gray-100 scrollbar-thumb-gray-400 hover:scrollbar-thumb-gray-500">
                    <table class="min-w-full table-auto" style="min-width: 1200px;">
                        <thead>
                            <tr class="bg-gradient-to-r from-gray-800 via-blue-800 to-gray-800">
                                <!-- ✅ Compact Select All Checkbox -->
                                <th class="px-2 md:px-4 py-3 text-left min-w-[70px]">
                                    <div class="flex items-center gap-1 md:gap-2">
                                        <div class="relative group">
                                            <input
                                                type="checkbox"
                                                :checked="isAllSelected"
                                                @change="handleSelectAllChange"
                                                class="w-4 h-4 rounded-lg text-blue-600 bg-white border-2 border-gray-300 focus:ring-2 focus:ring-blue-200 transition-all duration-300 cursor-pointer hover:border-blue-400"
                                            />
                                        </div>
                                        <span class="text-xs font-bold text-white/90 uppercase tracking-wide">
                                            <span class="hidden sm:inline">Select</span>
                                            <span class="sm:hidden">All</span>
                                        </span>
                                    </div>
                                </th>

                                <!-- 🏷️ Compact Order ID Header -->
                                <th
                                    @click="toggleSort('order_number')"
                                    class="px-2 md:px-4 py-3 text-left cursor-pointer hover:bg-white/10 transition-all duration-200 rounded-lg group min-w-[140px]"
                                >
                                    <div class="flex items-center gap-1 md:gap-2">
                                        <div class="w-5 h-5 rounded-lg bg-gradient-to-br from-green-400 to-emerald-500 flex items-center justify-center shadow-sm">
                                            <span class="text-white text-xs">🏷️</span>
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="text-xs font-bold text-white">Order ID</span>
                                            <span class="text-xs text-white/60 hidden md:block">Click sort</span>
                                        </div>
                                        <div class="text-white/60 group-hover:text-white transition-colors text-xs">
                                            {{ getSortIcon("order_number") }}
                                        </div>
                                    </div>
                                </th>

                                <!-- 👤 Compact Customer Header -->
                                <th
                                    @click="toggleSort('customer_name')"
                                    class="px-2 md:px-4 py-3 text-left cursor-pointer hover:bg-white/10 transition-all duration-200 rounded-lg group min-w-[160px]"
                                >
                                    <div class="flex items-center gap-1 md:gap-2">
                                        <div class="w-5 h-5 rounded-lg bg-gradient-to-br from-blue-400 to-cyan-500 flex items-center justify-center shadow-sm">
                                            <span class="text-white text-xs">👤</span>
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="text-xs font-bold text-white">Customer</span>
                                            <span class="text-xs text-white/60 hidden md:block">Contact info</span>
                                        </div>
                                        <div class="text-white/60 group-hover:text-white transition-colors text-xs">
                                            {{ getSortIcon("customer_name") }}
                                        </div>
                                    </div>
                                </th>

                                <!-- 🛍️ Compact Products Header -->
                                <th class="px-2 md:px-4 py-3 text-left min-w-[180px]">
                                    <div class="flex items-center gap-1 md:gap-2">
                                        <div class="w-5 h-5 rounded-lg bg-gradient-to-br from-purple-400 to-pink-500 flex items-center justify-center shadow-sm">
                                            <span class="text-white text-xs">🛍️</span>
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="text-xs font-bold text-white">Products</span>
                                            <span class="text-xs text-white/60 hidden md:block">Items</span>
                                        </div>
                                    </div>
                                </th>

                                <!-- 💰 Compact Total Header -->
                                <th
                                    @click="toggleSort('total')"
                                    class="px-2 md:px-4 py-3 text-left cursor-pointer hover:bg-white/10 transition-all duration-200 rounded-lg group min-w-[100px]"
                                >
                                    <div class="flex items-center gap-1 md:gap-2">
                                        <div class="w-5 h-5 rounded-lg bg-gradient-to-br from-yellow-400 to-orange-500 flex items-center justify-center shadow-sm">
                                            <span class="text-white text-xs">💰</span>
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="text-xs font-bold text-white">Total</span>
                                            <span class="text-xs text-white/60 hidden md:block">Amount</span>
                                        </div>
                                        <div class="text-white/60 group-hover:text-white transition-colors text-xs">
                                            {{ getSortIcon("total") }}
                                        </div>
                                    </div>
                                </th>

                                <!-- 📊 Compact Status Header -->
                                <th
                                    @click="toggleSort('status')"
                                    class="px-2 md:px-4 py-3 text-left cursor-pointer hover:bg-white/10 transition-all duration-200 rounded-lg group min-w-[120px]"
                                >
                                    <div class="flex items-center gap-1 md:gap-2">
                                        <div class="w-5 h-5 rounded-lg bg-gradient-to-br from-indigo-400 to-purple-500 flex items-center justify-center shadow-sm">
                                            <span class="text-white text-xs">📊</span>
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="text-xs font-bold text-white">Status</span>
                                            <span class="text-xs text-white/60 hidden md:block">Progress</span>
                                        </div>
                                        <div class="text-white/60 group-hover:text-white transition-colors text-xs">
                                            {{ getSortIcon("status") }}
                                        </div>
                                    </div>
                                </th>

                                <!-- 💳 Compact Payment Header -->
                                <th class="px-2 md:px-4 py-3 text-left min-w-[100px]">
                                    <div class="flex items-center gap-1 md:gap-2">
                                        <div class="w-5 h-5 rounded-lg bg-gradient-to-br from-teal-400 to-cyan-500 flex items-center justify-center shadow-sm">
                                            <span class="text-white text-xs">💳</span>
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="text-xs font-bold text-white">Payment</span>
                                            <span class="text-xs text-white/60 hidden md:block">Status</span>
                                        </div>
                                    </div>
                                </th>

                                <!-- 🚚 Compact Courier Header -->
                                <th class="px-2 md:px-4 py-3 text-left min-w-[140px]">
                                    <div class="flex items-center gap-1 md:gap-2">
                                        <div class="w-5 h-5 rounded-lg bg-gradient-to-br from-emerald-400 to-green-500 flex items-center justify-center shadow-sm">
                                            <span class="text-white text-xs">🚚</span>
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="text-xs font-bold text-white">Courier</span>
                                            <span class="text-xs text-white/60 hidden md:block">Shipping</span>
                                        </div>
                                    </div>
                                </th>

                                <!-- ⚡ Compact Actions Header -->
                                <th class="px-2 md:px-3 py-3 text-left min-w-[80px]">
                                    <div class="flex items-center gap-1">
                                        <div class="w-5 h-5 rounded-lg bg-gradient-to-br from-red-400 to-pink-500 flex items-center justify-center shadow-sm">
                                            <span class="text-white text-xs">⚡</span>
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="text-xs font-bold text-white">Actions</span>
                                        </div>
                                    </div>
                                </th>
                            </tr>
                        </thead>
<tbody class="bg-white dark:bg-gray-800">
                            <tr
                                v-for="(order, index) in visibleOrders"
                                :key="order.id"
                                class="border-b border-gray-100 dark:border-gray-700 hover:bg-gradient-to-r hover:from-blue-50 hover:to-indigo-50 dark:hover:from-blue-900/10 dark:hover:to-indigo-900/10 hover:shadow-lg hover:border-blue-200 dark:hover:border-blue-600 transition-all duration-300 transform hover:scale-[1.002] group"
                                :class="{
                                    'bg-gradient-to-r from-blue-50 via-indigo-50 to-purple-50 dark:from-blue-900/20 dark:via-indigo-900/20 dark:to-purple-900/20 ring-2 ring-blue-300 dark:ring-blue-600 shadow-xl scale-[1.005]': selectedOrders.includes(order.id),
                                }"
                            >
                                <!-- ✨ Compact Checkbox Cell -->
                                <td class="px-2 md:px-4 py-3 min-w-[60px]">
                                    <div class="flex items-center justify-center">
                                        <div class="relative group">
                                            <input
                                                type="checkbox"
                                                :value="order.id"
                                                :checked="selectedOrders.includes(order.id)"
                                                @change="handleOrderSelectionChange(order.id)"
                                                class="w-4 h-4 rounded-lg text-blue-600 bg-white border-2 border-gray-300 focus:ring-2 focus:ring-blue-200 transition-all duration-300 cursor-pointer hover:border-blue-400"
                                            />
                                            <div v-if="selectedOrders.includes(order.id)" class="absolute -top-0.5 -right-0.5 w-2 h-2 bg-gradient-to-br from-green-400 to-emerald-500 rounded-full animate-pulse"></div>
                                        </div>
                                    </div>
                                </td>

                                <!-- 📋 Compact Order ID Cell -->
                                <td class="px-2 md:px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-emerald-500 to-green-600 flex items-center justify-center shadow-lg group-hover:scale-105 transition-all duration-300">
                                            <span class="text-white font-bold text-xs">📋</span>
                                        </div>
                                        <div class="flex flex-col">
                                            <div class="flex items-center gap-2">
                                                <span class="text-sm font-bold bg-gradient-to-r from-gray-800 to-gray-600 dark:from-gray-100 dark:to-gray-300 bg-clip-text text-transparent group-hover:from-blue-600 group-hover:to-indigo-600 transition-all duration-300">{{ order.order_number }}</span>
                                                <span v-if="order.admin_notes"
                                                      class="w-5 h-5 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 text-white text-xs font-bold flex items-center justify-center shadow-lg hover:scale-110 transition-all duration-200"
                                                      title="📝 Has admin notes">
                                                    📝
                                                </span>
                                            </div>
                                            <div class="flex items-center gap-2 mt-1">
                                                <div v-if="isToday(order.date)" class="px-2 py-1 bg-gradient-to-r from-green-400 via-emerald-500 to-green-600 text-white rounded-full text-xs font-bold shadow-lg animate-pulse">
                                                    ✨ TODAY
                                                </div>
                                                <span class="text-xs text-gray-600 dark:text-gray-400 flex items-center gap-1 bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded-full">
                                                    📅 {{ formatOrderDate(order.date) }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                            <!-- 👤 Compact Customer Cell -->
                            <td class="px-2 md:px-4 py-3">
                                <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-2 bg-gradient-to-br from-white via-gray-50 to-blue-50 dark:from-gray-800 dark:via-gray-750 dark:to-blue-900/20 hover:shadow-lg transition-all duration-300">
                                    <div class="space-y-2">
                                        <!-- Customer Header -->
                                        <div class="flex items-center gap-2 pb-1 border-b border-gray-200 dark:border-gray-600">
                                            <div class="w-7 h-7 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center shadow-lg">
                                                <span class="text-white text-xs">👤</span>
                                            </div>
                                            <span class="font-bold text-sm text-gray-900 dark:text-gray-100 truncate">
                                                {{ truncateText(order.customer.name, 2) }}
                                            </span>
                                        </div>

                                        <!-- Contact Info Grid -->
                                        <div class="space-y-1">
                                            <!-- Email -->
                                            <div class="flex items-center gap-1 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/30 dark:to-indigo-900/30 rounded-lg px-2 py-1">
                                                <span class="text-blue-600 text-xs">📧</span>
                                                <span class="text-xs text-gray-700 dark:text-gray-300 truncate font-medium">{{ order.customer?.email }}</span>
                                            </div>

                                            <!-- Phone -->
                                            <div class="flex items-center gap-1 bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/30 dark:to-emerald-900/30 rounded-lg px-2 py-1">
                                                <span class="text-green-600 text-xs">📞</span>
                                                <span class="text-xs text-gray-700 dark:text-gray-300 font-medium">{{ order.customer?.phone }}</span>
                                            </div>

                                            <!-- Address -->
                                            <div class="flex items-start gap-1 bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-900/30 dark:to-pink-900/30 rounded-lg px-2 py-1">
                                                <span class="text-purple-600 text-xs">📍</span>
                                                <span class="text-xs text-gray-700 dark:text-gray-300 line-clamp-2 font-medium">{{ order.customer?.address }}</span>
                                            </div>
                                        </div>

                                        <!-- Customer Note -->
                                        <div v-if="order.customer?.note && order.customer.note !== 'N/A'"
                                             class="bg-gradient-to-r from-yellow-50 to-orange-50 dark:from-yellow-900/30 dark:to-orange-900/30 border border-yellow-200 dark:border-yellow-700 rounded-lg p-2">
                                            <div class="flex items-start gap-1">
                                                <span class="text-yellow-600 text-sm">💬</span>
                                                <div class="flex-1">
                                                    <span class="text-xs font-bold text-yellow-800 dark:text-yellow-300">Note:</span>
                                                    <p class="text-xs text-yellow-700 dark:text-yellow-400 mt-0.5">{{ order.customer?.note }}</p>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Admin Notes Section -->
                                        <div v-if="order.admin_notes" class="bg-gradient-to-r from-indigo-50 to-blue-50 dark:from-indigo-900/40 dark:to-blue-900/40 border border-indigo-200 dark:border-indigo-600 rounded-lg p-2">
                                            <div class="flex items-start gap-1">
                                                <span class="text-indigo-600 text-sm">🔒</span>
                                                <div class="flex-1">
                                                    <p class="text-xs font-bold text-indigo-800 dark:text-indigo-300 mb-0.5">Admin Notes:</p>
                                                    <p class="text-xs text-indigo-700 dark:text-indigo-400 line-clamp-2">{{ order.admin_notes }}</p>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Action Button -->
                                        <button
                                            @click="openAdminNotesModal(order)"
                                            class="w-full bg-gradient-to-r from-blue-500 to-indigo-600 hover:from-blue-600 hover:to-indigo-700 text-white px-3 py-2 rounded-lg font-bold transition-all duration-200 shadow-lg hover:shadow-xl active:scale-95 transform hover:scale-105">
                                            <div class="flex items-center justify-center gap-1">
                                                <span class="text-sm">📝</span>
                                                <span class="text-xs">{{ order.admin_notes ? 'Edit Notes' : 'Add Notes' }}</span>
                                            </div>
                                        </button>
                                    </div>
                                </div>
                            </td>
                            <!-- 🛍️ Compact Items Cell -->
                            <td class="px-2 md:px-4 py-3">
                                <div class="space-y-2">
                                    <!-- No Items State -->
                                    <div v-if="order.items.length === 0" class="text-center py-4 bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-800 rounded-lg border border-dashed border-gray-300 dark:border-gray-600">
                                        <div class="text-2xl mb-1">📦</div>
                                        <div class="text-gray-600 dark:text-gray-400 font-medium text-xs">No items</div>
                                    </div>

                                    <!-- Items List -->
                                    <div v-else class="space-y-2">
                                        <div
                                            v-for="(item, index) in order.items"
                                            :key="item.id"
                                            v-show="index < (showAllItems ? order.items.length : 2)"
                                            class="bg-gradient-to-br from-white via-blue-50 to-indigo-50 dark:from-gray-800 dark:via-blue-900/20 dark:to-indigo-900/20 border border-blue-200 dark:border-blue-700 rounded-lg p-2 hover:shadow-lg transition-all duration-300 group"
                                        >
                                            <div class="flex items-start gap-2">
                                                <!-- Product Image -->
                                                <div class="relative shrink-0">
                                                    <img
                                                        :src="item.product.image || ''"
                                                        alt="Product Image"
                                                        class="w-12 h-12 object-cover rounded-lg shadow-lg ring-1 ring-blue-200 dark:ring-blue-700"
                                                    />
                                                    <div class="absolute -top-1 -right-1 w-5 h-5 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-full flex items-center justify-center shadow-lg">
                                                        <span class="text-white text-xs font-bold">{{ item.quantity }}</span>
                                                    </div>
                                                </div>

                                                <!-- Product Details -->
                                                <div class="flex-1 min-w-0 space-y-1">
                                                    <!-- Product Name & Badge -->
                                                    <div class="flex items-start gap-1">
                                                        <h4 class="font-bold text-gray-900 dark:text-gray-100 text-xs truncate leading-tight">
                                                            {{ truncateText(item.product.name, 2) }}
                                                        </h4>
                                                        <span v-if="item.is_pre_order" class="bg-gradient-to-r from-amber-400 to-orange-500 text-white text-xs px-1 py-0.5 rounded-full font-bold shadow-sm whitespace-nowrap">
                                                            ⏰ Pre
                                                        </span>
                                                    </div>

                                                    <!-- Price Info -->
                                                    <div class="bg-gradient-to-r from-green-100 to-emerald-100 dark:from-green-900/30 dark:to-emerald-900/30 rounded-lg px-2 py-1">
                                                        <div class="flex items-center gap-1 text-xs">
                                                            <span class="text-green-600">💰</span>
                                                            <span class="font-bold text-green-800 dark:text-green-300">৳{{ item.price }}</span>
                                                            <span class="text-green-600">×{{ item.quantity }}</span>
                                                        </div>
                                                    </div>

                                                    <!-- Variation Info -->
                                                    <div v-if="item.variation" class="bg-gradient-to-r from-purple-100 to-pink-100 dark:from-purple-900/30 dark:to-pink-900/30 rounded-lg px-2 py-1">
                                                        <div class="flex flex-wrap gap-1">
                                                            <span v-for="(value, key) in item.variation.attributes" :key="key" class="bg-gradient-to-r from-purple-500 to-pink-500 text-white text-xs px-1 py-0.5 rounded-full font-bold shadow-sm">
                                                                {{ key }}: {{ value }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Show More/Less Button -->
                                    <div v-if="order.items.length > 2" class="text-center">
                                        <button
                                            @click="showAllItems = !showAllItems"
                                            class="bg-gradient-to-r from-blue-500 to-indigo-600 hover:from-blue-600 hover:to-indigo-700 text-white px-3 py-1 rounded-lg font-bold transition-all duration-200 shadow-lg hover:shadow-xl active:scale-95 transform hover:scale-105 text-xs"
                                        >
                                            <div class="flex items-center gap-1">
                                                <span>{{ showAllItems ? "👆 Less" : "👇 More" }}</span>
                                                <span class="bg-white/20 px-1 py-0.5 rounded-full text-xs">+{{ order.items.length - 2 }}</span>
                                            </div>
                                        </button>
                                    </div>
                                </div>
                            </td>
                            <!-- 💰 Compact Total Cell -->
                            <td class="px-2 md:px-4 py-3">
                                <div class="text-center">
                                    <div class="bg-gradient-to-br from-green-500 via-emerald-500 to-teal-600 text-white rounded-lg p-3 shadow-lg ring-2 ring-green-200 dark:ring-green-900/30 hover:scale-105 transition-all duration-300 group">
                                        <div class="flex flex-col items-center gap-1">
                                            <div class="text-lg">💰</div>
                                            <div class="text-sm font-black drop-shadow-lg">৳{{ parseFloat(order.total).toLocaleString() }}</div>
                                            <div class="text-xs opacity-90 font-medium bg-white/20 px-1 py-0.5 rounded-full">Total</div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <!-- 📊 Compact Status Cell -->
                            <td class="px-2 md:px-4 py-3">
                                <button
                                    @click="openStatusChangeModal(order)"
                                    type="button"
                                    class="w-full bg-gradient-to-br shadow-lg hover:shadow-xl rounded-lg border-2 px-3 py-2 font-bold transition-all duration-300 hover:scale-105 active:scale-95 transform ring-2 ring-opacity-30 text-xs"
                                    :class="{
                                        'from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white border-blue-400 ring-blue-200': order.status === 'pending',
                                        'from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 text-white border-orange-400 ring-orange-200': order.status === 'processing',
                                        'from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white border-amber-400 ring-amber-200': order.status === 'shipped',
                                        'from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white border-green-400 ring-green-200': order.status === 'delivered',
                                        'from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white border-red-400 ring-red-200': order.status === 'cancelled',
                                        'from-purple-500 to-purple-600 hover:from-purple-600 hover:to-purple-700 text-white border-purple-400 ring-purple-200': order.status === 'returned',
                                        'from-gray-500 to-gray-600 hover:from-gray-600 hover:to-gray-700 text-white border-gray-400 ring-gray-200': order.status === 'on_hold',
                                        'from-indigo-500 to-indigo-600 hover:from-indigo-600 hover:to-indigo-700 text-white border-indigo-400 ring-indigo-200': order.status === 'confirmed',
                                        'opacity-60 cursor-not-allowed': updatingOrders[order.id]
                                    }"
                                    :disabled="updatingOrders[order.id]"
                                >
                                    <div class="flex items-center justify-between gap-1">
                                        <div class="flex items-center gap-1">
                                            <div class="w-5 h-5 bg-white/20 rounded-full flex items-center justify-center">
                                                <span class="text-xs">
                                                    {{ order.status === 'pending' ? '⏳' :
                                                       order.status === 'processing' ? '🔄' :
                                                       order.status === 'shipped' ? '🚚' :
                                                       order.status === 'delivered' ? '✅' :
                                                       order.status === 'cancelled' ? '❌' :
                                                       order.status === 'returned' ? '↩️' :
                                                       order.status === 'on_hold' ? '⏸️' :
                                                       order.status === 'confirmed' ? '✔️' : '📊' }}
                                                </span>
                                            </div>
                                            <span class="capitalize text-xs drop-shadow-sm">{{ order.status }}</span>
                                        </div>
                                        <svg v-if="!updatingOrders[order.id]" class="h-3 w-3 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"></path>
                                        </svg>
                                        <svg v-else class="h-3 w-3 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                    </div>
                                </button>
                            </td>
                            <!-- 💳 Enhanced Payment Status Cell -->
                            <td class="px-4 py-6">
                                <div class="text-center">
                                    <span class="px-4 py-3 rounded-2xl font-black text-sm shadow-lg ring-4 ring-opacity-30 transition-all duration-300 hover:scale-105"
                                        :class="{
                                            'bg-gradient-to-br from-red-500 to-red-600 text-white ring-red-200': order.payment_status === 'unpaid',
                                            'bg-gradient-to-br from-green-500 to-green-600 text-white ring-green-200': order.payment_status === 'paid',
                                            'bg-gradient-to-br from-gray-500 to-gray-600 text-white ring-gray-200': order.payment_status === 'refunded',
                                        }">
                                        <div class="flex items-center gap-2">
                                            <span class="text-lg">
                                                {{ order.payment_status === 'unpaid' ? '❌' :
                                                   order.payment_status === 'paid' ? '✅' :
                                                   order.payment_status === 'refunded' ? '↩️' : '💳' }}
                                            </span>
                                            <span class="capitalize drop-shadow-sm">{{ order.payment_status }}</span>
                                        </div>
                                    </span>
                                </div>
                            </td>
                            <!-- 🚚 Enhanced Courier Info Cell -->
                            <td class="px-4 py-5">
                                <div v-if="order.tracking_number || order.is_courier" class="bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50 dark:from-blue-900/30 dark:via-indigo-900/30 dark:to-purple-900/30 border-2 border-blue-200 dark:border-blue-600 rounded-2xl p-4 hover:shadow-xl transition-all duration-300 hover:scale-105">
                                    <div class="space-y-4">
                                        <!-- Courier Name -->
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-full flex items-center justify-center shadow-lg">
                                                <Truck class="w-5 h-5 text-white" />
                                            </div>
                                            <span class="px-3 py-2 rounded-xl font-bold shadow-sm text-sm"
                                                :class="{
                                                    'bg-gradient-to-r from-green-400 to-emerald-500 text-white': order.courier_name && order.courier_name !== 'N/A',
                                                    'bg-gradient-to-r from-gray-400 to-gray-500 text-white': order.courier_name === 'N/A' || !order.courier_name,
                                                }">
                                                {{ order.courier_name || "N/A" }}
                                            </span>
                                        </div>

                                        <!-- Tracking Info -->
                                        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border-2 border-gray-200 dark:border-gray-600 shadow-inner">
                                            <div class="space-y-3">
                                                <!-- Consignment ID -->
                                                <div class="text-center">
                                                    <div class="text-xs text-gray-500 dark:text-gray-400 font-medium mb-1">📦 Consignment ID</div>
                                                    <div class="text-sm font-bold text-gray-800 dark:text-gray-200 bg-gradient-to-r from-blue-100 to-indigo-100 dark:from-blue-900/30 dark:to-indigo-900/30 px-3 py-2 rounded-lg">
                                                        {{ order.consignment_id }}
                                                    </div>
                                                </div>

                                                <!-- Tracking Link -->
                                                <div v-if="order?.tracking_number !== 'N/A'" class="text-center">
                                                    <a :href="`https://steadfast.com.bd/t/${order.tracking_number}`" target="_blank" class="inline-flex items-center gap-2 bg-gradient-to-r from-blue-500 to-indigo-600 hover:from-blue-600 hover:to-indigo-700 text-white px-4 py-2 rounded-xl font-bold transition-all duration-200 shadow-lg hover:shadow-xl active:scale-95 transform hover:scale-105">
                                                        <span>🔍</span>
                                                        <span>Track Shipment</span>
                                                        <span>↗️</span>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Area Status -->
                                        <div v-if="order?.area_id" class="flex items-center justify-center gap-2 bg-gradient-to-r from-green-100 to-emerald-100 dark:from-green-900/30 dark:to-emerald-900/30 border border-green-300 dark:border-green-600 rounded-xl px-4 py-2">
                                            <MapPin class="w-5 h-5 text-green-600" />
                                            <span class="text-sm font-bold text-green-800 dark:text-green-300">📍 Address Saved</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- No Courier Info -->
                                <div v-else class="text-center py-6 bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-800 rounded-2xl border-2 border-dashed border-gray-300 dark:border-gray-600">
                                    <div class="text-3xl mb-2">📭</div>
                                    <div class="text-gray-600 dark:text-gray-400 font-medium">No courier info</div>
                                </div>
                            </td>
                            <!-- ⚙️ Enhanced Actions Cell -->
                            <td class="px-4 py-6">
                                <div class="relative flex justify-center">
                                    <button
                                        @click.stop="toggleActionMenu(order.id)"
                                        class="group w-12 h-12 bg-gradient-to-br from-gray-100 to-gray-200 hover:from-blue-500 hover:to-indigo-600 rounded-2xl flex items-center justify-center transition-all duration-300 focus:outline-none focus:ring-4 focus:ring-blue-200 hover:shadow-lg hover:scale-110 active:scale-95"
                                        :class="{
                                            'bg-gradient-to-br from-blue-500 to-indigo-600 text-white shadow-xl scale-110': activeActionMenu === order.id,
                                            'text-gray-600 hover:text-white': activeActionMenu !== order.id,
                                        }"
                                    >
                                        <component
                                            :is="activeActionMenu === order.id ? X : MoreVertical"
                                            class="w-6 h-6 transition-transform duration-200"
                                            :class="{ 'rotate-90': activeActionMenu === order.id }"
                                        />
                                    </button>

                                    <!-- 🎯 Enhanced Action Menu Dropdown -->
                                    <div v-if="activeActionMenu === order.id" class="absolute right-0 top-full mt-3 w-64 bg-gradient-to-br from-white via-blue-50 to-indigo-50 rounded-2xl shadow-2xl border-2 border-blue-200 z-50 overflow-hidden animate-in fade-in zoom-in-95 slide-in-from-top-2 duration-300">
                                        <div class="p-2">
                                            <!-- Header -->
                                            <div class="bg-gradient-to-r from-blue-500 to-indigo-600 text-white p-3 rounded-xl mb-2">
                                                <div class="flex items-center gap-2">
                                                    <span class="text-xl">⚙️</span>
                                                    <span class="font-bold">Quick Actions</span>
                                                </div>
                                            </div>

                                            <!-- Action Buttons -->
                                            <div class="space-y-1">
                                                <!-- Check Fraud -->
                                                <button
                                                    @click="checkFraud(order?.customer?.phone); toggleActionMenu(order.id);"
                                                    class="w-full text-left px-4 py-3 rounded-xl text-sm font-medium text-gray-700 hover:bg-gradient-to-r hover:from-red-50 hover:to-pink-50 hover:text-red-600 flex items-center gap-3 transition-all duration-200 hover:shadow-md hover:scale-105 active:scale-95 group"
                                                >
                                                    <div class="w-8 h-8 bg-gradient-to-br from-red-100 to-red-200 group-hover:from-red-500 group-hover:to-red-600 rounded-full flex items-center justify-center transition-all duration-200">
                                                        <User class="w-4 h-4 group-hover:text-white" />
                                                    </div>
                                                    <div>
                                                        <div class="font-bold">🔍 Check Fraud</div>
                                                        <div class="text-xs text-gray-500 group-hover:text-red-500">Verify customer details</div>
                                                    </div>
                                                </button>

                                                <!-- Courier Management -->
                                                <button
                                                    @click="openCourierModal(order, order.id); toggleActionMenu(order.id);"
                                                    class="w-full text-left px-3 py-2 rounded-lg text-xs font-medium text-gray-700 hover:bg-gradient-to-r hover:from-green-50 hover:to-emerald-50 hover:text-green-600 flex items-center gap-2 transition-all duration-200 hover:shadow-md active:scale-95 group"
                                                >
                                                    <div class="w-6 h-6 bg-gradient-to-br from-green-100 to-green-200 group-hover:from-green-500 group-hover:to-green-600 rounded-full flex items-center justify-center transition-all duration-200">
                                                        <MapPin class="w-3 h-3 group-hover:text-white" />
                                                    </div>
                                                    <div>
                                                        <div class="font-bold text-xs">🚚 Couriers</div>
                                                    </div>
                                                </button>

                                                <!-- Edit Order -->
                                                <Link
                                                    :href="route('admin.orders.edit', order.id)"
                                                    @click="toggleActionMenu(order.id)"
                                                    class="w-full text-left px-3 py-2 rounded-lg text-xs font-medium text-gray-700 hover:bg-gradient-to-r hover:from-amber-50 hover:to-yellow-50 hover:text-amber-600 flex items-center gap-2 transition-all duration-200 hover:shadow-md active:scale-95 group"
                                                >
                                                    <div class="w-6 h-6 bg-gradient-to-br from-amber-100 to-amber-200 group-hover:from-amber-500 group-hover:to-amber-600 rounded-full flex items-center justify-center transition-all duration-200">
                                                        <SquarePen class="w-3 h-3 group-hover:text-white" />
                                                    </div>
                                                    <div>
                                                        <div class="font-bold text-xs">✏️ Edit Order</div>
                                                    </div>
                                                </Link>

                                                <!-- Divider -->
                                                <div class="border-t border-gray-200 my-1"></div>

                                                <!-- Delete Order -->
                                                <button
                                                    @click="openDeleteModal(order.id); toggleActionMenu(order.id);"
                                                    class="w-full text-left px-3 py-2 rounded-lg text-xs font-medium text-gray-700 hover:bg-gradient-to-r hover:from-red-50 hover:to-pink-50 hover:text-red-600 flex items-center gap-2 transition-all duration-200 hover:shadow-md active:scale-95 group"
                                                >
                                                    <div class="w-6 h-6 bg-gradient-to-br from-red-100 to-red-200 group-hover:from-red-500 group-hover:to-red-600 rounded-full flex items-center justify-center transition-all duration-200">
                                                        <Trash2Icon class="w-3 h-3 group-hover:text-white" />
                                                    </div>
                                                    <div>
                                                        <div class="font-bold text-xs">🗑️ Delete</div>
                                                    </div>
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- 🎭 Compact Backdrop -->
                                    <div
                                        v-if="activeActionMenu === order.id"
                                        class="fixed inset-0 z-40 bg-black/20 backdrop-blur-sm"
                                        @click="activeActionMenu = null"
                                    ></div>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="visibleOrders.length === 0">
                            <td
                                colspan="10"
                                class="px-1 py-6 text-center text-black"
                            >
                                No orders found matching your filters.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Enhanced Pagination - User Friendly -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-4 mt-6">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                    <!-- Results Summary -->
                    <div class="flex items-center gap-2 text-sm">
                        <span class="text-2xl">📊</span>
                        <span class="text-gray-600 dark:text-gray-400 font-medium">
                            <strong class="text-gray-900 dark:text-gray-100">{{ from }}-{{ to }}</strong>
                            of
                            <strong class="text-gray-900 dark:text-gray-100">{{ total }}</strong>
                            orders
                        </span>
                    </div>

                    <!-- Navigation Buttons -->
                    <div class="flex items-center gap-2">
                        <!-- First Page -->
                        <button
                            @click="goToPage(1)"
                            :disabled="currentPage === 1 || loading"
                            class="inline-flex items-center justify-center w-10 h-10 text-sm font-bold rounded-xl bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 active:scale-95 touch-manipulation"
                            title="Go to first page"
                        >
                            ⏮️
                        </button>

                        <!-- Previous Page -->
                        <button
                            @click="goToPage(currentPage - 1)"
                            :disabled="currentPage === 1 || loading"
                            class="inline-flex items-center justify-center w-10 h-10 text-sm font-bold rounded-xl bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 active:scale-95 touch-manipulation"
                            title="Go to previous page"
                        >
                            ⬅️
                        </button>

                        <!-- Page Numbers -->
                        <div class="hidden sm:flex items-center gap-1">
                            <template v-if="lastPage <= 7">
                                <button
                                    v-for="page in lastPage"
                                    :key="page"
                                    @click="goToPage(page)"
                                    :disabled="loading"
                                    class="inline-flex items-center justify-center w-10 h-10 text-sm font-bold rounded-xl border transition-all duration-200 active:scale-95 touch-manipulation"
                                    :class="currentPage === page
                                        ? 'bg-gradient-to-r from-blue-500 to-blue-600 text-white border-blue-500 shadow-md'
                                        : 'bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600'"
                                    :title="`Go to page ${page}`"
                                >
                                    {{ page }}
                                </button>
                            </template>
                            <template v-else>
                                <button
                                    v-if="currentPage > 3"
                                    @click="goToPage(1)"
                                    class="inline-flex items-center justify-center w-10 h-10 text-sm font-bold rounded-xl bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 transition-all duration-200 active:scale-95 touch-manipulation"
                                    title="Go to page 1"
                                >
                                    1
                                </button>
                                <span v-if="currentPage > 4" class="flex items-center justify-center w-10 h-10 text-gray-400 text-lg font-bold">⋯</span>

                                <template v-for="page in lastPage" :key="page">
                                    <button
                                        v-if="page >= currentPage - 1 && page <= currentPage + 1"
                                        @click="goToPage(page)"
                                        class="inline-flex items-center justify-center w-10 h-10 text-sm font-bold rounded-xl border transition-all duration-200 active:scale-95 touch-manipulation"
                                        :class="currentPage === page
                                            ? 'bg-gradient-to-r from-blue-500 to-blue-600 text-white border-blue-500 shadow-md'
                                            : 'bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600'"
                                        :title="`Go to page ${page}`"
                                    >
                                        {{ page }}
                                    </button>
                                </template>

                                <span v-if="currentPage < lastPage - 3" class="flex items-center justify-center w-10 h-10 text-gray-400 text-lg font-bold">⋯</span>
                                <button
                                    v-if="currentPage < lastPage - 2"
                                    @click="goToPage(lastPage)"
                                    class="inline-flex items-center justify-center w-10 h-10 text-sm font-bold rounded-xl bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 transition-all duration-200 active:scale-95 touch-manipulation"
                                    :title="`Go to page ${lastPage}`"
                                >
                                    {{ lastPage }}
                                </button>
                            </template>
                        </div>

                        <!-- Mobile Page Indicator -->
                        <div class="sm:hidden flex items-center gap-2">
                            <span class="text-sm font-bold text-gray-700 dark:text-gray-300 px-3 py-2 bg-gray-100 dark:bg-gray-700 rounded-xl">
                                Page {{ currentPage }} of {{ lastPage }}
                            </span>
                        </div>

                        <!-- Next Page -->
                        <button
                            @click="goToPage(currentPage + 1)"
                            :disabled="currentPage === lastPage || loading"
                            class="inline-flex items-center justify-center w-10 h-10 text-sm font-bold rounded-xl bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 active:scale-95 touch-manipulation"
                            title="Go to next page"
                        >
                            ➡️
                        </button>

                        <!-- Last Page -->
                        <button
                            @click="goToPage(lastPage)"
                            :disabled="currentPage === lastPage || loading"
                            class="inline-flex items-center justify-center w-10 h-10 text-sm font-bold rounded-xl bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 active:scale-95 touch-manipulation"
                            title="Go to last page"
                        >
                            ⏭️
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
            <DeleteModal
                :item-id="orderToDelete"
                item-name="order"
                route-name="admin.orders.destroy"
                v-model:visible="showDeleteModal"
                @deleted="handleDeleteSuccess"
            />

            <!-- Courier Selection Modal -->
            <CourierSelectionModal
                :show="showCourierModal"
                :cities="cities?.data?.data || []"
                :orderId="selectedOrderId"
                :order="selectedOrder"
                @close="showCourierModal = false"
                @submit="handleCourierSubmit"
            />
        </div>

        <!-- Fraud Checker Modal -->
        <FraudCheckerModal
            :visible="showModal"
            :fraudData="fraudData"
            @close="showModal = false"
        />

        <!-- Admin Notes Modal -->
        <AdminNotesModal
            v-if="selectedOrderForNotes"
            :visible="showAdminNotesModal"
            :order-id="selectedOrderForNotes.id"
            :order-number="selectedOrderForNotes.order_number"
            :initial-notes="selectedOrderForNotes.admin_notes || ''"
            :customer-note="selectedOrderForNotes.customer?.note || ''"
            @close="closeAdminNotesModal"
            @saved="handleNotesSaved"
        />

        <!-- Status Change Confirmation Modal -->
        <StatusChangeModal
            :show="showStatusChangeModal"
            :current-status="statusChangeOrder?.status"
            :order-id="statusChangeOrder?.id"
            :order-number="statusChangeOrder?.order_number"
            @close="showStatusChangeModal = false; statusChangeOrder = null"
            @confirm="confirmStatusChange"
        />
    </AdminLayout>
</template>

<style scoped>
.pagination-button {
    @apply px-3 py-1 rounded-md text-sm font-medium transition-colors;
    @apply bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300;
    @apply border border-gray-300 dark:border-gray-600;
    @apply hover:bg-blue-100 dark:hover:bg-blue-900 hover:text-blue-700 dark:hover:text-blue-200;
}

.pagination-button:disabled {
    @apply opacity-50 cursor-not-allowed;
}

.pagination-button.active {
    @apply bg-blue-500 text-white border-blue-500;
}

.pagination-ellipsis {
    @apply px-3 py-1 text-sm text-gray-500 dark:text-gray-400;
}

/* Hide scrollbar for status tabs */
.scrollbar-hide::-webkit-scrollbar {
    display: none;
}

.scrollbar-hide {
    -ms-overflow-style: none;
    scrollbar-width: none;
}

/* Custom scrollbar for table overflow */
.scrollbar-thin {
    scrollbar-width: thin;
}

.scrollbar-track-gray-100 {
    scrollbar-color: #e5e7eb #f3f4f6;
}

.scrollbar-thumb-gray-400 {
    scrollbar-color: #9ca3af #f3f4f6;
}

.hover\:scrollbar-thumb-gray-500:hover {
    scrollbar-color: #6b7280 #f3f4f6;
}

/* Webkit scrollbar styles */
::-webkit-scrollbar {
    height: 8px;
    width: 8px;
}

::-webkit-scrollbar-track {
    background: #f3f4f6;
}

::-webkit-scrollbar-thumb {
    background: #9ca3af;
    border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
    background: #6b7280;
}

/* Enhanced table responsiveness - More Compact */
@media (max-width: 768px) {
    .table-cell {
        min-width: unset !important;
        width: auto !important;
    }

    /* More compact table cells on mobile */
    .min-w-\[180px\] {
        min-width: 140px !important;
    }

    .min-w-\[160px\] {
        min-width: 120px !important;
    }

    .min-w-\[140px\] {
        min-width: 100px !important;
    }

    .min-w-\[120px\] {
        min-width: 90px !important;
    }

    .min-w-\[100px\] {
        min-width: 80px !important;
    }

    .min-w-\[80px\] {
        min-width: 70px !important;
    }

    .min-w-\[70px\] {
        min-width: 60px !important;
    }

    .min-w-\[60px\] {
        min-width: 50px !important;
    }

    /* Reduce font sizes further on mobile */
    table {
        font-size: 0.75rem;
    }

    th, td {
        padding: 0.5rem 0.25rem !important;
    }

    /* Make action dropdown more compact on mobile */
    .w-56 {
        width: 12rem !important;
    }
}
</style>
