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

// Toggle select all checkboxes
const toggleSelectAll = () => {
    selectAll.value = !selectAll.value;
    selectedOrders.value = selectAll.value
        ? visibleOrders.value.map((order) => order.id)
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
        selectedOrders.value.length === visibleOrders.value.length &&
        visibleOrders.value.length > 0;
};

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

        <div class="order_management p-1 rounded-lg">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-4">
                    <h1 class="text-2xl font-bold text-gray-800">
                        Order Management
                    </h1>
                    <Link :href="route('admin.orders.incomplete')" class="inline-flex items-center gap-2 bg-amber-100 text-amber-800 px-3 py-1 rounded-full text-sm font-semibold shadow hover:bg-amber-200">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3" />
                        </svg>
                        Incomplete: {{ props.statusCounts.find(s => s.status === 'incomplete')?.count || 0 }}
                    </Link>
                </div>
                <button @click="resetFilters" class="btn bg-white border px-3 py-1 rounded text-sm">Reset Filters</button>
            </div>

            <!-- Status Cards -->
            <div
                class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-3 mb-6"
            >


                <!-- Total Orders Card -->
                <div
                    @click="selectStatus('')"
                    class="relative overflow-hidden rounded-xl shadow-sm hover:shadow-md cursor-pointer transition-all duration-300 group"
                    :class="{
                        'bg-gradient-to-br from-blue-600 to-blue-800 text-white ring-2 ring-blue-400 ring-offset-2':
                            filters.status === '',
                        'bg-white hover:bg-gray-50 border border-gray-200':
                            filters.status !== '',
                    }"
                >
                    <div class="p-5 relative z-10">
                        <div class="flex justify-between items-start">
                            <div class="flex-1">
                                <h3
                                    class="text-sm font-medium mb-1"
                                    :class="
                                        filters.status === ''
                                            ? 'text-blue-100'
                                            : 'text-gray-500'
                                    "
                                >
                                    Total Orders
                                </h3>
                                <p
                                    class="text-3xl font-bold"
                                    :class="
                                        filters.status === ''
                                            ? 'text-white'
                                            : 'text-gray-800'
                                    "
                                >
                                    {{
                                        props.statusCounts.find(
                                            (s) => s.status === "total"
                                        )?.count || 0
                                    }}
                                </p>
                                <p
                                    class="text-sm mt-1"
                                    :class="
                                        filters.status === ''
                                            ? 'text-blue-100'
                                            : 'text-gray-600'
                                    "
                                >
                                    ৳{{ Number(props.statusCounts.find(
                                        (s) => s.status === "total"
                                    )?.sales || 0).toFixed(2) }}
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

            <!-- Status Tabs - Big Tech Style -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden mb-6">
                <div class="border-b border-gray-200">
                    <div class="flex items-center overflow-x-auto scrollbar-hide">
                        <!-- All Orders Tab -->
                        <button
                            @click="selectStatus('')"
                            :class="[
                                'flex-shrink-0 px-6 py-4 text-sm font-semibold border-b-2 transition-all duration-200 hover:bg-gray-50',
                                filters.status === ''
                                    ? 'border-orange-600 text-orange-600 bg-orange-50'
                                    : 'border-transparent text-gray-600 hover:text-gray-900'
                            ]"
                        >
                            <div class="flex items-center gap-2">
                                <span>All Orders</span>
                                <span class="px-2 py-0.5 rounded-full text-xs font-bold"
                                      :class="filters.status === '' ? 'bg-orange-600 text-white' : 'bg-gray-200 text-gray-700'">
                                    {{ props.statusCounts.find((s) => s.status === 'total')?.count || 0 }}
                                </span>
                            </div>
                        </button>

                        <!-- Pending Tab -->
                        <button
                            @click="selectStatus('pending')"
                            :class="[
                                'flex-shrink-0 px-6 py-4 text-sm font-semibold border-b-2 transition-all duration-200 hover:bg-gray-50',
                                filters.status === 'pending'
                                    ? 'border-amber-600 text-amber-600 bg-amber-50'
                                    : 'border-transparent text-gray-600 hover:text-gray-900'
                            ]"
                        >
                            <div class="flex items-center gap-2">
                                <Clock class="w-4 h-4" />
                                <span>Pending</span>
                                <span class="px-2 py-0.5 rounded-full text-xs font-bold"
                                      :class="filters.status === 'pending' ? 'bg-amber-600 text-white' : 'bg-gray-200 text-gray-700'">
                                    {{ props.statusCounts.find((s) => s.status === 'pending')?.count || 0 }}
                                </span>
                            </div>
                        </button>

                        <!-- Processing Tab -->
                        <button
                            @click="selectStatus('processing')"
                            :class="[
                                'flex-shrink-0 px-6 py-4 text-sm font-semibold border-b-2 transition-all duration-200 hover:bg-gray-50',
                                filters.status === 'processing'
                                    ? 'border-blue-600 text-blue-600 bg-blue-50'
                                    : 'border-transparent text-gray-600 hover:text-gray-900'
                            ]"
                        >
                            <div class="flex items-center gap-2">
                                <Package class="w-4 h-4" />
                                <span>Processing</span>
                                <span class="px-2 py-0.5 rounded-full text-xs font-bold"
                                      :class="filters.status === 'processing' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700'">
                                    {{ props.statusCounts.find((s) => s.status === 'processing')?.count || 0 }}
                                </span>
                            </div>
                        </button>

                        <!-- Shipped Tab -->
                        <button
                            @click="selectStatus('shipped')"
                            :class="[
                                'flex-shrink-0 px-6 py-4 text-sm font-semibold border-b-2 transition-all duration-200 hover:bg-gray-50',
                                filters.status === 'shipped'
                                    ? 'border-indigo-600 text-indigo-600 bg-indigo-50'
                                    : 'border-transparent text-gray-600 hover:text-gray-900'
                            ]"
                        >
                            <div class="flex items-center gap-2">
                                <Truck class="w-4 h-4" />
                                <span>Shipped</span>
                                <span class="px-2 py-0.5 rounded-full text-xs font-bold"
                                      :class="filters.status === 'shipped' ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-700'">
                                    {{ props.statusCounts.find((s) => s.status === 'shipped')?.count || 0 }}
                                </span>
                            </div>
                        </button>

                        <!-- Delivered Tab -->
                        <button
                            @click="selectStatus('delivered')"
                            :class="[
                                'flex-shrink-0 px-6 py-4 text-sm font-semibold border-b-2 transition-all duration-200 hover:bg-gray-50',
                                filters.status === 'delivered'
                                    ? 'border-green-600 text-green-600 bg-green-50'
                                    : 'border-transparent text-gray-600 hover:text-gray-900'
                            ]"
                        >
                            <div class="flex items-center gap-2">
                                <CheckCircle class="w-4 h-4" />
                                <span>Delivered</span>
                                <span class="px-2 py-0.5 rounded-full text-xs font-bold"
                                      :class="filters.status === 'delivered' ? 'bg-green-600 text-white' : 'bg-gray-200 text-gray-700'">
                                    {{ props.statusCounts.find((s) => s.status === 'delivered')?.count || 0 }}
                                </span>
                            </div>
                        </button>

                        <!-- Completed Tab -->
                        <button
                            @click="selectStatus('completed')"
                            :class="[
                                'flex-shrink-0 px-6 py-4 text-sm font-semibold border-b-2 transition-all duration-200 hover:bg-gray-50',
                                filters.status === 'completed'
                                    ? 'border-emerald-600 text-emerald-600 bg-emerald-50'
                                    : 'border-transparent text-gray-600 hover:text-gray-900'
                            ]"
                        >
                            <div class="flex items-center gap-2">
                                <CheckCircle class="w-4 h-4" />
                                <span>Completed</span>
                                <span class="px-2 py-0.5 rounded-full text-xs font-bold"
                                      :class="filters.status === 'completed' ? 'bg-emerald-600 text-white' : 'bg-gray-200 text-gray-700'">
                                    {{ props.statusCounts.find((s) => s.status === 'completed')?.count || 0 }}
                                </span>
                            </div>
                        </button>

                        <!-- Cancelled Tab -->
                        <button
                            @click="selectStatus('cancelled')"
                            :class="[
                                'flex-shrink-0 px-6 py-4 text-sm font-semibold border-b-2 transition-all duration-200 hover:bg-gray-50',
                                filters.status === 'cancelled'
                                    ? 'border-red-600 text-red-600 bg-red-50'
                                    : 'border-transparent text-gray-600 hover:text-gray-900'
                            ]"
                        >
                            <div class="flex items-center gap-2">
                                <XCircle class="w-4 h-4" />
                                <span>Cancelled</span>
                                <span class="px-2 py-0.5 rounded-full text-xs font-bold"
                                      :class="filters.status === 'cancelled' ? 'bg-red-600 text-white' : 'bg-gray-200 text-gray-700'">
                                    {{ props.statusCounts.find((s) => s.status === 'cancelled')?.count || 0 }}
                                </span>
                            </div>
                        </button>

                        <!-- Returned Tab -->
                        <button
                            @click="selectStatus('returned')"
                            :class="[
                                'flex-shrink-0 px-6 py-4 text-sm font-semibold border-b-2 transition-all duration-200 hover:bg-gray-50',
                                filters.status === 'returned'
                                    ? 'border-purple-600 text-purple-600 bg-purple-50'
                                    : 'border-transparent text-gray-600 hover:text-gray-900'
                            ]"
                        >
                            <div class="flex items-center gap-2">
                                <ArrowUpDown class="w-4 h-4" />
                                <span>Returned</span>
                                <span class="px-2 py-0.5 rounded-full text-xs font-bold"
                                      :class="filters.status === 'returned' ? 'bg-purple-600 text-white' : 'bg-gray-200 text-gray-700'">
                                    {{ props.statusCounts.find((s) => s.status === 'returned')?.count || 0 }}
                                </span>
                            </div>
                        </button>
                    </div>
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

            <div class="mb-6 flex justify-end items-center space-x-2">
                <button
                    v-if="selectedOrders.length > 0"
                    @click="sendBulkToSteadfast"
                    class="px-2 py-2 bg-blue-500 text-white rounded-md text-sm font-medium hover:bg-blue-600"
                    :disabled="isDownloading"
                >
                    Send {{ selectedOrders.length }} Orders to Steadfast
                </button>
                <button
                    v-if="selectedOrders.length > 0"
                    @click="sendBulkToPathao"
                    class="px-2 py-2 bg-blue-500 text-white rounded-md text-sm font-medium hover:bg-blue-600"
                    :disabled="isDownloading"
                >
                    Send {{ selectedOrders.length }} Orders to Pathao
                </button>
                <!-- <button
                    v-if="selectedOrders.length > 0"
                    @click="downloadBulkInvoice"
                    class="px-2 py-2 bg-green-500 text-white rounded-md text-sm font-medium hover:bg-green-600 disabled:opacity-50 disabled:cursor-not-allowed"
                    :disabled="isDownloading"
                >
                    <span v-if="isDownloading">Downloading...</span>
                    <span v-else
                        >Download {{ selectedOrders.length }} Invoices</span
                    >
                </button> -->

                <button
                    v-if="selectedOrders.length > 0"
                    @click="printBulkInvoice"
                    class="px-4 py-2 bg-purple-500 text-white rounded-md text-sm font-medium hover:bg-purple-600"
                >
                    Print {{ selectedOrders.length }} Invoices
                </button>
            </div>

            <!-- Per Page Filter -->
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center">
                    <label class="text-sm font-medium text-gray-700 mr-2"
                        >Show</label
                    >
                    <select
                        v-model="filters.per_page"
                        class="rounded-md border-gray-300 shadow-sm text-sm"
                    >
                        <option
                            v-for="option in perPageOptions"
                            :key="option"
                            :value="option"
                        >
                            {{ option }}
                        </option>
                    </select>
                    <span class="text-sm text-gray-700 ml-2">entries</span>
                </div>
            </div>

            <!-- <pre>{{ orders }}</pre> -->

            <!-- Orders Table -->
            <div
                class="overflow-x-auto rounded-xl shadow-lg border border-gray-100"
            >
                <table class="min-w-full divide-y table-auto">
                    <thead class="bg-gray-900 text-white">
                        <tr>
                            <th
                                class="px-3 py-4 text-left text-xs font-semibold uppercase tracking-wider w-12 rounded-tl-xl transition-colors duration-200"
                            >
                                <input
                                    type="checkbox"
                                    :checked="selectAll"
                                    @change="toggleSelectAll"
                                    class="rounded text-blue-500 focus:ring-blue-500 bg-gray-800 border-gray-600"
                                />
                            </th>
                            <th
                                @click="toggleSort('order_number')"
                                class="px-3 py-4 text-left text-xs min-w-[150px] font-semibold uppercase tracking-wider cursor-pointer !hover:bg-gray-800 transition-colors duration-200"
                            >
                                <div class="flex items-center gap-1">
                                    Order {{ getSortIcon("order_number") }}
                                </div>
                            </th>
                            <th
                                @click="toggleSort('customer_name')"
                                class="px-3 py-4 text-left text-xs min-w-[150px] font-semibold uppercase tracking-wider cursor-pointer !hover:bg-gray-800 transition-colors duration-200"
                            >
                                <div class="flex items-center gap-1">
                                    Customer {{ getSortIcon("customer_name") }}
                                </div>
                            </th>
                            <th
                                class="px-3 py-4 text-left text-xs min-w-[200px] font-semibold uppercase tracking-wider transition-colors duration-200"
                            >
                                Products
                            </th>
                            <th
                                @click="toggleSort('total')"
                                class="px-3 py-4 text-left text-xs min-w-[100px] font-semibold uppercase tracking-wider cursor-pointer !hover:bg-gray-800 transition-colors duration-200"
                            >
                                <div class="flex items-center gap-1">
                                    Total {{ getSortIcon("total") }}
                                </div>
                            </th>
                            <th
                                @click="toggleSort('status')"
                                class="px-3 py-4 text-left text-xs min-w-[150px] font-semibold uppercase tracking-wider cursor-pointer !hover:bg-gray-800 transition-colors duration-200"
                            >
                                <div class="flex items-center gap-1">
                                    Status {{ getSortIcon("status") }}
                                </div>
                            </th>
                            <th
                                @click="toggleSort('payment_status')"
                                class="px-3 py-4 text-left text-xs font-semibold min-w-[100px] uppercase tracking-wider cursor-pointer !hover:bg-gray-800 transition-colors duration-200"
                            >
                                <div class="flex items-center gap-1">
                                    Payment {{ getSortIcon("payment_status") }}
                                </div>
                            </th>
                            <th
                                class="px-3 py-4 text-left text-xs min-w-[150px] font-semibold uppercase tracking-wider transition-colors duration-200"
                            >
                                Courier Info
                            </th>
                            <th
                                class="px-3 py-4 text-left text-xs min-w-[50px] font-semibold uppercase tracking-wider rounded-tr-xl transition-colors duration-200"
                            >
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr
                            v-for="(order, index) in visibleOrders"
                            :key="order.id"
                            class="hover:bg-gray-50 transition-colors duration-150"
                            :class="{
                                'bg-blue-50/50': selectedOrders.includes(
                                    order.id
                                ),
                            }"
                        >

                            <td class="px-1 py-3 text-sm">
                                <input
                                    type="checkbox"
                                    :value="order.id"
                                    :checked="selectedOrders.includes(order.id)"
                                    @change="toggleOrderSelection(order.id)"
                                    class="rounded text-blue-500"
                                />
                            </td>
                            <td class="px-1 py-3 text-sm">
                                <p
                                    class="font-semibold text-sm bg-green-500 text-white px-2 py-1 rounded inline-flex items-center gap-1"
                                >
                                    {{ order.order_number }}
                                    <span v-if="order.admin_notes" class="inline-flex items-center justify-center w-4 h-4 bg-blue-500 rounded-full text-white text-[10px] font-bold" title="Has admin notes">
                                        N
                                    </span>
                                </p>
                                <p class="mt-1 text-black flex items-center gap-1">
                                    <span v-if="isToday(order.date)" class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-green-100 text-green-800 border border-green-300">
                                        TODAY
                                    </span>
                                    Date: {{ formatOrderDate(order.date) }}
                                </p>
                                <p class="text-xs text-gray-500">
                                    Updated:
                                    {{ formatOrderDate(order.updated_at) }}
                                </p>
                            </td>
                            <td class="px-1 py-3 text-sm">
                                <div
                                    class="border border-gray-200 rounded-lg p-3 bg-gradient-to-br from-white to-gray-50 hover:shadow-md transition-shadow"
                                >
                                    <p
                                        class="truncate flex items-center flex-wrap font-medium text-gray-800"
                                    >
                                        <User class="w-4 h-4 mr-1 text-blue-600" />{{
                                            truncateText(order.customer.name, 2)
                                        }}
                                    </p>
                                    <p class="text-gray-600 flex items-center text-xs mt-1">
                                        <Mail class="w-3 h-3 mr-1 text-gray-500" />{{
                                            order.customer?.email
                                        }}
                                    </p>
                                    <p class="text-gray-600 flex items-center text-xs mt-1">
                                        <Phone class="w-3 h-3 mr-1 text-gray-500" />{{
                                            order.customer?.phone
                                        }}
                                    </p>
                                    <p class="text-gray-600 flex items-center text-xs mt-1">
                                        <MapPin class="w-3 h-3 mr-1 text-gray-500" />{{
                                            order.customer?.address
                                        }}
                                    </p>
                                    <p v-if="order.customer?.note && order.customer.note !== 'N/A'" class="flex text-green-800 bg-green-200 py-1 rounded text-sm mt-1 items-center">
                                        <Hash class="w-4 h-4 mr-1" />Note:
                                        {{ order.customer?.note || "N/A" }}
                                    </p>
                                    <div v-if="order.admin_notes" class="mt-2">
                                        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-lg p-2">
                                            <div class="flex items-start gap-2">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-blue-600 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                                <div class="flex-1 min-w-0">
                                                    <p class="text-xs font-semibold text-blue-800 mb-0.5">Admin Notes:</p>
                                                    <p class="text-xs text-blue-700 line-clamp-2">{{ order.admin_notes }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <button
                                        @click="openAdminNotesModal(order)"
                                        class="mt-2 w-full flex items-center justify-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-md transition-all"
                                        :class="order.admin_notes ? 'bg-blue-100 text-blue-700 hover:bg-blue-200' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                        {{ order.admin_notes ? 'Edit Notes' : 'Add Notes' }}
                                    </button>
                                </div>
                            </td>
                            <td class="px-1 py-3 text-sm">
                                <div class="flex flex-wrap gap-1">
                                    <div
                                        v-if="order.items.length === 0"
                                        class="text-black italic"
                                    >
                                        No items
                                    </div>
                                    <div
                                        v-for="(item, index) in order.items"
                                        :key="item.id"
                                        v-show="
                                            index <
                                            (showAllItems
                                                ? order.items.length
                                                : 2)
                                        "
                                        class="flex items-center mb-2 flex-wrap border border-gray-200 rounded-lg p-2"
                                    >
                                        <img
                                            :src="item.product.image || ''"
                                            alt="Product Image"
                                            class="w-16 h-16 object-cover mr-3 rounded"
                                        />
                                        <div class="flex-1 min-w-0">
                                            <div
                                                class="flex items-center gap-2"
                                            >
                                                <p class="font-medium truncate">
                                                    {{
                                                        truncateText(
                                                            item.product.name,
                                                            2
                                                        )
                                                    }}
                                                </p>
                                                <span
                                                    v-if="item.is_pre_order"
                                                    class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded"
                                                >
                                                    Pre-Order
                                                </span>
                                            </div>
                                            <p class="text-xs text-gray-600">
                                                Qty: {{ item.quantity }} |
                                                Price: ৳{{ item.price }}
                                            </p>
                                            <p
                                                v-if="item.variation"
                                                class="text-xs text-gray-600 truncate"
                                            >
                                                <span
                                                    v-for="(value, key) in item
                                                        .variation.attributes"
                                                    :key="key"
                                                >
                                                    {{ key }}: {{ value }}
                                                    <span
                                                        v-if="
                                                            Object.keys(
                                                                item.variation
                                                                    .attributes
                                                            ).indexOf(key) <
                                                            Object.keys(
                                                                item.variation
                                                                    .attributes
                                                            ).length -
                                                                1
                                                        "
                                                        >,
                                                    </span>
                                                </span>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <div v-if="order.items.length > 2" class="mt-2">
                                    <button
                                        @click="showAllItems = !showAllItems"
                                        class="text-blue-600 hover:text-blue-800 text-sm font-medium"
                                    >
                                        {{
                                            showAllItems
                                                ? "Show Less"
                                                : "See More"
                                        }}
                                    </button>
                                </div>
                            </td>
                            <td class="px-1 py-3 text-sm">
                                <div class="flex items-center">
                                    <span class="text-lg font-bold text-gray-900">৳{{ order.total }}</span>
                                </div>
                            </td>
                            <td class="px-1 py-3 text-sm">
                                <!-- Click to open status change modal -->
                                <button
                                    @click="openStatusChangeModal(order)"
                                    type="button"
                                    class="w-full appearance-none rounded-md border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all hover:shadow-md"
                                    :class="{
                                        'bg-blue-500 text-white border-blue-600': order.status === 'pending',
                                        'bg-orange-500 text-white border-orange-600': order.status === 'processing',
                                        'bg-amber-500 text-black border-amber-600': order.status === 'shipped',
                                        'bg-green-500 text-white border-green-600': order.status === 'delivered',
                                        'bg-red-500 text-white border-red-600': order.status === 'cancelled',
                                        'bg-purple-500 text-white border-purple-600': order.status === 'returned',
                                        'bg-gray-500 text-white border-gray-600': order.status === 'on_hold',
                                        'bg-indigo-500 text-white border-indigo-600': order.status === 'confirmed',
                                        'opacity-50 cursor-not-allowed': updatingOrders[order.id]
                                    }"
                                    :disabled="updatingOrders[order.id]"
                                >
                                    <div class="flex items-center justify-between">
                                        <span class="capitalize font-medium">{{ order.status }}</span>
                                        <svg v-if="!updatingOrders[order.id]" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                        </svg>
                                        <svg v-else class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                    </div>
                                </button>
                            </td>
                            <td class="px-1 py-3 text-sm">
                                <span
                                    :class="{
                                        'px-2 py-1 rounded-full text-xs font-medium': true,
                                        'bg-red-100 text-red-800':
                                            order.payment_status === 'unpaid',
                                        'bg-green-100 text-green-800':
                                            order.payment_status === 'paid',
                                        'bg-gray-100 text-black':
                                            order.payment_status === 'refunded',
                                    }"
                                >
                                    {{ order.payment_status }}
                                </span>
                            </td>
                            <td class="px-2 py-2 text-sm">
                                <div
                                    v-if="
                                        order.tracking_number ||
                                        order.is_courier
                                    "
                                    class="border border-gray-200 rounded-lg p-1 bg-gray-50"
                                >
                                    <div class="flex flex-col space-y-1">
                                        <div
                                            class="flex items-center space-x-1"
                                        >
                                            <Truck
                                                class="w-4 h-4 text-gray-600"
                                            />
                                            <span
                                                class="px-1 py-0.5 rounded-full text-xs font-medium"
                                                :class="{
                                                    'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200':
                                                        order.courier_name,
                                                    'bg-gray-100 text-gray-800 dark:bg-gray-600 dark:text-gray-200':
                                                        order.courier_name ==
                                                        'N/A',
                                                }"
                                            >
                                                {{
                                                    order.courier_name || "N/A"
                                                }}
                                            </span>
                                        </div>
                                        <div
                                            class="flex flex-col justify-center items-center gap-1 p-2 bg-white rounded-md shadow-sm border"
                                        >
                                            <!-- Consignment ID -->
                                            <span
                                                class="text-xs font-semibold text-gray-800"
                                            >
                                                {{ order.consignment_id }}
                                            </span>

                                            <!-- Tracking Link -->
                                            <a
                                                v-if="
                                                    order?.tracking_number !==
                                                    'N/A'
                                                "
                                                :href="`https://steadfast.com.bd/t/${order.tracking_number}`"
                                                target="_blank"
                                                class="text-[11px] font-medium text-blue-600 hover:text-blue-800 underline"
                                            >
                                                Track Shipment
                                            </a>
                                        </div>

                                        <div
                                            v-show="order?.area_id"
                                            class="flex items-center space-x-1"
                                        >
                                            <MapPin
                                                class="w-4 h-4 text-green-600"
                                            />
                                            <span
                                                class="text-xs bg-green-100 text-green-800 px-1 py-0.5 rounded"
                                            >
                                                Address Saved
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-3 py-3 text-sm">
                                <div class="relative flex justify-center">
                                    <button
                                        @click.stop="toggleActionMenu(order.id)"
                                        class="p-2 rounded-full hover:bg-gray-100 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        :class="{
                                            'bg-blue-50 text-blue-600':
                                                activeActionMenu === order.id,
                                        }"
                                    >
                                        <component
                                            :is="
                                                activeActionMenu === order.id
                                                    ? X
                                                    : MoreVertical
                                            "
                                            class="w-5 h-5"
                                        />
                                    </button>

                                    <!-- Action Menu Dropdown -->
                                    <div
                                        v-if="activeActionMenu === order.id"
                                        class="absolute right-0 top-full mt-2 w-48 bg-white rounded-lg shadow-xl border border-gray-100 z-50 overflow-hidden animate-in fade-in zoom-in-95 duration-200"
                                    >
                                        <div class="py-1">
                                            <button
                                                @click="
                                                    checkFraud(
                                                        order?.customer?.phone
                                                    );
                                                    toggleActionMenu(order.id);
                                                "
                                                class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 hover:text-blue-600 flex items-center gap-2 transition-colors"
                                            >
                                                <User class="w-4 h-4" />
                                                Check Fraud
                                            </button>

                                            <!-- <Link
                                                :href="
                                                    route(
                                                        'admin.orders.show',
                                                        order.id
                                                    )
                                                "
                                                class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 hover:text-blue-600 flex items-center gap-2 transition-colors"
                                            >
                                                <Eye class="w-4 h-4" />
                                                View Details
                                            </Link> -->

                                            <button
                                                @click="
                                                    openCourierModal(
                                                        order,
                                                        order.id
                                                    );
                                                    toggleActionMenu(order.id);
                                                "
                                                class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 hover:text-green-600 flex items-center gap-2 transition-colors"
                                            >
                                                <MapPin class="w-4 h-4" />
                                                Couriers
                                            </button>

                                            <Link
                                                :href="
                                                    route(
                                                        'admin.orders.edit',
                                                        order.id
                                                    )
                                                "
                                                class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 hover:text-amber-600 flex items-center gap-2 transition-colors"
                                            >
                                                <SquarePen class="w-4 h-4" />
                                                Edit Order
                                            </Link>

                                            <div
                                                class="border-t border-gray-100 my-1"
                                            ></div>

                                            <button
                                                @click="
                                                    openDeleteModal(order.id);
                                                    toggleActionMenu(order.id);
                                                "
                                                class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 flex items-center gap-2 transition-colors"
                                            >
                                                <Trash2Icon class="w-4 h-4" />
                                                Delete
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Backdrop to close menu -->
                                    <div
                                        v-if="activeActionMenu === order.id"
                                        class="fixed inset-0 z-40 cursor-default"
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

            <!-- Pagination -->
            <div class="flex items-center justify-between mt-4">
                <div class="text-sm text-gray-700 dark:text-gray-300">
                    Showing <span class="font-medium">{{ from }}</span> to
                    <span class="font-medium">{{ to }}</span> of
                    <span class="font-medium">{{ total }}</span> orders
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
                        <span v-if="currentPage > 4" class="pagination-ellipsis"
                            >...</span
                        >
                        <template v-for="page in lastPage" :key="page">
                            <button
                                v-if="
                                    page >= currentPage - 1 &&
                                    page <= currentPage + 1
                                "
                                @click="goToPage(page)"
                                class="pagination-button"
                                :class="{ active: currentPage === page }"
                            >
                                {{ page }}
                            </button>
                        </template>
                        <span
                            v-if="currentPage < lastPage - 3"
                            class="pagination-ellipsis"
                            >...</span
                        >
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
</style>
