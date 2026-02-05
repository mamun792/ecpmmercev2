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
    Check,
    Download,
    Printer,
    Bike,
    Filter,
    ChevronDown,
    ChevronLeft,
    ChevronRight,
    ChevronsLeft,
    ChevronsRight,
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
        <!-- Loading Overlay -->
        <div
            v-if="checkFraudLoading"
            class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50"
        >
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-2xl flex flex-col items-center gap-3">
                <div class="w-12 h-12 border-4 border-blue-500 border-t-transparent rounded-full animate-spin"></div>
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Checking fraud...</span>
            </div>
        </div>

<div class="max-w-7xl mx-auto space-y-8">
            <!-- Minimal Header -->
            <div class="flex items-center justify-between">
                <div>
                    <div class="flex items-center gap-3">
                        <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">Orders</h1>
                        <div class="group relative">
                            <div class="w-5 h-5 rounded-full bg-blue-100 dark:bg-blue-900/50 flex items-center justify-center cursor-help">
                                <span class="text-xs font-medium text-blue-600 dark:text-blue-400">?</span>
                            </div>
                            <div class="absolute left-0 top-8 w-80 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-xl p-4 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50">
                                <div class="space-y-3">
                                    <h4 class="font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-lg bg-gradient-to-r from-blue-500 to-indigo-600 flex items-center justify-center">
                                            <span class="text-white text-xs">💡</span>
                                        </div>
                                        Order Management Tips
                                    </h4>
                                    <div class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                                        <p class="flex items-start gap-2">
                                            <span class="text-green-500 text-xs mt-1">✓</span>
                                            <span>Process orders within 24 hours for best customer experience</span>
                                        </p>
                                        <p class="flex items-start gap-2">
                                            <span class="text-blue-500 text-xs mt-1">💡</span>
                                            <span>Use bulk actions to send multiple orders to courier at once</span>
                                        </p>
                                        <p class="flex items-start gap-2">
                                            <span class="text-purple-500 text-xs mt-1">⚡</span>
                                            <span>Click on status cards to filter orders quickly</span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        {{ from }}-{{ to }} of {{ total }} orders
                        <span class="mx-2">•</span>
                        <span class="font-medium text-gray-900 dark:text-white">৳{{ Number(props.statusCounts.find(s => s.status === 'total')?.sales || 0).toLocaleString() }}</span> total value
                        <span class="mx-2">•</span>
                        <span class="text-xs px-2 py-1 bg-green-100 text-green-700 rounded-full">Live updates</span>
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <Link :href="route('admin.orders.incomplete')"
                          class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-amber-600 bg-amber-50 hover:bg-amber-100 rounded-lg transition-colors">
                        <Clock class="w-4 h-4" />
                        <span>Incomplete</span>
                        <span class="bg-amber-200 text-amber-800 px-1.5 py-0.5 rounded-full text-xs font-semibold">
                            {{ props.statusCounts.find(s => s.status === 'incomplete')?.count || 0 }}
                        </span>
                    </Link>
                    <button @click="resetFilters"
                            class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-lg transition-colors">
                        <ArrowUpDown class="w-4 h-4" />
                        <span>Reset</span>
                    </button>
                </div>
            </div>

<!-- Minimal Status Overview -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6">
                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-4">
                    <!-- Total Orders -->
                    <button
                        @click="selectStatus('')"
                        class="group p-4 rounded-xl transition-all duration-200"
                        :class="filters.status === ''
                            ? 'bg-blue-50 ring-2 ring-blue-500 ring-opacity-20'
                            : 'hover:bg-gray-50 dark:hover:bg-gray-700/50'"
                    >
                        <div class="text-center space-y-1">
                            <div class="text-2xl font-bold text-gray-900 dark:text-white">
                                {{ props.statusCounts.find(s => s.status === 'total')?.count || 0 }}
                            </div>
                            <div class="text-xs font-medium text-gray-500 dark:text-gray-400">All Orders</div>
                            <div class="text-xs font-semibold text-green-600">
                                ৳{{ Number(props.statusCounts.find(s => s.status === 'total')?.sales || 0).toLocaleString() }}
                            </div>
                        </div>
                    </button>



                    <!-- Individual Status Cards -->
                    <button
                        v-for="status in availableStatuses.filter(s => s !== 'incomplete')"
                        :key="status"
                        @click="selectStatus(status)"
                        class="group p-4 rounded-xl transition-all duration-200"
                        :class="{
                            'bg-amber-50 ring-2 ring-amber-500 ring-opacity-20': filters.status === status && status === 'pending',
                            'bg-blue-50 ring-2 ring-blue-500 ring-opacity-20': filters.status === status && status === 'processing',
                            'bg-red-50 ring-2 ring-red-500 ring-opacity-20': filters.status === status && status === 'cancelled',
                            'bg-purple-50 ring-2 ring-purple-500 ring-opacity-20': filters.status === status && status === 'shipped',
                            'bg-green-50 ring-2 ring-green-500 ring-opacity-20': filters.status === status && status === 'delivered',
                            'bg-pink-50 ring-2 ring-pink-500 ring-opacity-20': filters.status === status && status === 'returned',
                            'bg-gray-50 ring-2 ring-gray-500 ring-opacity-20': filters.status === status && status === 'on_hold',
                            'bg-indigo-50 ring-2 ring-indigo-500 ring-opacity-20': filters.status === status && status === 'confirmed',
                            'hover:bg-gray-50 dark:hover:bg-gray-700/50': filters.status !== status
                        }"
                    >
                        <div class="text-center space-y-1">
                            <div class="text-2xl font-bold text-gray-900 dark:text-white">
                                {{ props.statusCounts.find(s => s.status === status)?.count || 0 }}
                            </div>
                            <div class="text-xs font-medium text-gray-500 dark:text-gray-400 capitalize">{{ status.replace('_', ' ') }}</div>
                            <div class="text-xs font-semibold"
                                 :class="{
                                     'text-amber-600': status === 'pending',
                                     'text-blue-600': status === 'processing',
                                     'text-red-600': status === 'cancelled',
                                     'text-purple-600': status === 'shipped',
                                     'text-green-600': status === 'delivered',
                                     'text-pink-600': status === 'returned',
                                     'text-gray-600': status === 'on_hold',
                                     'text-indigo-600': status === 'confirmed'
                                 }">
                                ৳{{ Number(props.statusCounts.find(s => s.status === status)?.sales || 0).toLocaleString() }}
                            </div>
                        </div>
                    </button>
                </div>
            </div>



            <!-- Filter Chips - Show Active Filters -->
            <FilterChips
                :filters="filters"
                @remove="removeFilter"
                @clear-all="clearAllFilters"
            />

            <!-- Best Practices Banner -->
            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-2xl border border-blue-200 dark:border-blue-800 p-6 mb-6">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-r from-blue-500 to-indigo-600 flex items-center justify-center flex-shrink-0">
                        <span class="text-white text-xl">💡</span>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Order Management Best Practices</h3>
                        <div class="grid md:grid-cols-2 gap-4 text-sm">
                            <div class="space-y-2">
                                <p class="flex items-center gap-2 text-gray-700 dark:text-gray-300">
                                    <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                                    <span><strong>Quick Processing:</strong> Confirm orders within 2-4 hours</span>
                                </p>
                                <p class="flex items-center gap-2 text-gray-700 dark:text-gray-300">
                                    <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                                    <span><strong>Batch Shipping:</strong> Group orders by area for efficiency</span>
                                </p>
                            </div>
                            <div class="space-y-2">
                                <p class="flex items-center gap-2 text-gray-700 dark:text-gray-300">
                                    <span class="w-1.5 h-1.5 rounded-full bg-purple-500"></span>
                                    <span><strong>Customer Communication:</strong> Update status regularly</span>
                                </p>
                                <p class="flex items-center gap-2 text-gray-700 dark:text-gray-300">
                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                    <span><strong>Quality Check:</strong> Verify customer details before shipping</span>
                                </p>
                            </div>
                        </div>
                    </div>
                    <button class="text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-400 transition-colors p-1">
                        <X class="w-5 h-5" />
                    </button>
                </div>
            </div>

            <!-- Advanced Filters Component -->
            <AdvancedFilters
                v-model="filters"
                :status-counts="statusCounts"
                @apply="applyFilters"
                @reset="resetFilters"
                class="mb-4"
            />

            <!-- Bulk Actions Bar with Enhanced UX -->
            <div v-if="selectedOrders.length > 0" class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-4 mb-6">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-blue-100 dark:bg-blue-900/50 flex items-center justify-center">
                            <Check class="w-5 h-5 text-blue-600" />
                        </div>
                        <div>
                            <p class="font-semibold text-gray-900 dark:text-white">
                                {{ selectedOrders.length }} order{{ selectedOrders.length > 1 ? 's' : '' }} selected
                            </p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                Choose an action to apply to selected orders
                            </p>
                        </div>
                        <!-- Bulk Actions Help -->
                        <div class="group relative">
                            <div class="w-5 h-5 rounded-full bg-green-100 dark:bg-green-900/50 flex items-center justify-center cursor-help">
                                <span class="text-xs font-medium text-green-600 dark:text-green-400">?</span>
                            </div>
                            <div class="absolute left-0 top-8 w-80 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-xl p-4 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50">
                                <div class="space-y-3">
                                    <h4 class="font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-lg bg-gradient-to-r from-green-500 to-emerald-600 flex items-center justify-center">
                                            <span class="text-white text-xs">⚡</span>
                                        </div>
                                        Bulk Actions Guide
                                    </h4>
                                    <div class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                                        <p class="flex items-start gap-2">
                                            <span class="text-blue-500 text-xs mt-1">🚛</span>
                                            <span><strong>Courier:</strong> Send multiple orders to Steadfast or Pathao at once</span>
                                        </p>
                                        <p class="flex items-start gap-2">
                                            <span class="text-purple-500 text-xs mt-1">📄</span>
                                            <span><strong>Invoice:</strong> Download or print invoices for all selected orders</span>
                                        </p>
                                        <p class="flex items-start gap-2">
                                            <span class="text-green-500 text-xs mt-1">💡</span>
                                            <span><strong>Tip:</strong> Select orders with same status for better workflow</span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <!-- Courier Actions -->
                        <div class="flex items-center gap-2">
                            <button
                                @click="sendBulkToSteadfast"
                                :disabled="isDownloading"
                                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors disabled:opacity-50"
                            >
                                <Truck class="w-4 h-4 text-white" />
                                <span>Send to Steadfast</span>
                                <span class="bg-blue-700 px-2 py-0.5 rounded-full text-xs">{{ selectedOrders.length }}</span>
                            </button>

                            <button
                                @click="sendBulkToPathao"
                                :disabled="isDownloading"
                                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg transition-colors disabled:opacity-50"
                            >
                                <Bike class="w-4 h-4 text-white" />
                                <span>Send to Pathao</span>
                                <span class="bg-emerald-700 px-2 py-0.5 rounded-full text-xs">{{ selectedOrders.length }}</span>
                            </button>
                        </div>

                        <!-- Divider -->
                        <div class="w-px h-8 bg-gray-200 dark:bg-gray-600"></div>

                        <!-- Document Actions -->
                        <div class="flex items-center gap-2">
                            <button
                                @click="downloadBulkInvoice"
                                :disabled="isDownloading"
                                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition-colors disabled:opacity-50"
                            >
                                <span v-if="isDownloading" class="w-4 h-4 border-2 border-gray-600 border-t-transparent rounded-full animate-spin"></span>
                                <Download v-else class="w-4 h-4 text-blue-600" />
                                <span>Download Invoice</span>
                            </button>

                            <button
                                @click="printBulkInvoice"
                                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition-colors"
                            >
                                <Printer class="w-4 h-4 text-purple-600" />
                                <span>Print Invoice</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Orders Table Card -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <!-- Table Header with Per Page selector and Workflow Tips -->
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Orders</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                Showing {{ from }}-{{ to }} of {{ total }} orders
                            </p>
                        </div>
                        <!-- Quick Tips -->
                        <div class="group relative hidden lg:block">
                            <div class="flex items-center gap-2 px-3 py-1.5 bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-lg border border-purple-200 dark:border-purple-800 cursor-help">
                                <span class="text-xs font-medium text-purple-700 dark:text-purple-400">Quick Tips</span>
                                <span class="w-4 h-4 rounded-full bg-purple-100 dark:bg-purple-900/50 flex items-center justify-center">
                                    <span class="text-xs text-purple-600 dark:text-purple-400">?</span>
                                </span>
                            </div>
                            <div class="absolute left-0 top-12 w-96 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-xl p-6 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50">
                                <div class="space-y-4">
                                    <h4 class="font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-lg bg-gradient-to-r from-purple-500 to-pink-600 flex items-center justify-center">
                                            <span class="text-white text-xs">⚡</span>
                                        </div>
                                        Order Processing Workflow
                                    </h4>
                                    <div class="space-y-3 text-sm">
                                        <div class="flex items-center gap-3 p-2 bg-amber-50 dark:bg-amber-900/20 rounded-lg">
                                            <span class="w-6 h-6 rounded-full bg-amber-500 text-white text-xs font-bold flex items-center justify-center">1</span>
                                            <span class="text-gray-700 dark:text-gray-300">Review pending orders and verify customer details</span>
                                        </div>
                                        <div class="flex items-center gap-3 p-2 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                                            <span class="w-6 h-6 rounded-full bg-blue-500 text-white text-xs font-bold flex items-center justify-center">2</span>
                                            <span class="text-gray-700 dark:text-gray-300">Confirm orders and mark as 'Processing'</span>
                                        </div>
                                        <div class="flex items-center gap-3 p-2 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
                                            <span class="w-6 h-6 rounded-full bg-purple-500 text-white text-xs font-bold flex items-center justify-center">3</span>
                                            <span class="text-gray-700 dark:text-gray-300">Send to courier when ready to ship</span>
                                        </div>
                                        <div class="flex items-center gap-3 p-2 bg-green-50 dark:bg-green-900/20 rounded-lg">
                                            <span class="w-6 h-6 rounded-full bg-green-500 text-white text-xs font-bold flex items-center justify-center">4</span>
                                            <span class="text-gray-700 dark:text-gray-300">Update to 'Delivered' once confirmed</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <div class="flex items-center gap-2">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Show:</span>
                            <select v-model="filters.per_page" @change="updateFilters"
                                    class="px-3 py-1.5 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="10">10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                            <span class="text-sm text-gray-600 dark:text-gray-400">per page</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
                            <span class="text-xs text-gray-500 dark:text-gray-400">Live</span>
                        </div>
                    </div>
                </div>

                <!-- Table Container -->
                <div class="overflow-x-auto">
                    <table class="w-full table-fixed">
                        <thead class="bg-gray-50 dark:bg-gray-700/50">
                            <tr>
                                <!-- Select All -->
                                <th class="w-12 px-6 py-4 text-left">
                                    <input type="checkbox"
                                           :checked="isAllSelected"
                                           @change="handleSelectAllChange"
                                           class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 focus:ring-2"
                                    />
                                </th>

                                <!-- Order ID -->
                                <th @click="toggleSort('order_number')"
                                    class="w-40 px-6 py-4 text-left cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                                    <div class="flex items-center gap-2 text-sm font-semibold text-gray-900 dark:text-gray-100">
                                        <span>Order</span>
                                        <ArrowUpDown class="w-4 h-4 text-blue-500" />
                                    </div>
                                </th>

                                <!-- Customer -->
                                <th @click="toggleSort('customer_name')"
                                    class="w-48 px-6 py-4 text-left cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                                    <div class="flex items-center gap-2 text-sm font-semibold text-gray-900 dark:text-gray-100">
                                        <span>Customer</span>
                                        <ArrowUpDown class="w-4 h-4 text-green-500" />
                                    </div>
                                </th>

                                <!-- Products -->
                                <th class="px-6 py-4 text-left">
                                    <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">Products</span>
                                </th>

                                <!-- Total -->
                                <th @click="toggleSort('total')"
                                    class="w-32 px-6 py-4 text-left cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                                    <div class="flex items-center gap-2 text-sm font-semibold text-gray-900 dark:text-gray-100">
                                        <span>Total</span>
                                        <ArrowUpDown class="w-4 h-4 text-emerald-500" />
                                    </div>
                                </th>

                                <!-- Status -->
                                <th @click="toggleSort('status')"
                                    class="w-32 px-6 py-4 text-left cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                                    <div class="flex items-center gap-2 text-sm font-semibold text-gray-900 dark:text-gray-100">
                                        <span>Status</span>
                                        <ArrowUpDown class="w-4 h-4 text-purple-500" />
                                    </div>
                                </th>

                                <!-- Date -->
                                <th @click="toggleSort('created_at')"
                                    class="w-40 px-6 py-4 text-left cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                                    <div class="flex items-center gap-2 text-sm font-semibold text-gray-900 dark:text-gray-100">
                                        <span>Date</span>
                                        <ArrowUpDown class="w-4 h-4 text-amber-500" />
                                    </div>
                                </th>

                                <!-- Actions -->
                                <th class="w-32 px-6 py-4 text-center">
                                    <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">Actions</span>
                                </th>
                            </tr>
                        </thead>

                        <tbody v-if="visibleOrders.length === 0" class="divide-y divide-gray-100 dark:divide-gray-700">
                            <tr>
                                <td colspan="7" class="px-6 py-16">
                                    <div class="text-center">
                                        <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-gradient-to-r from-blue-100 to-indigo-100 dark:from-blue-900/30 dark:to-indigo-900/30 flex items-center justify-center">
                                            <Package class="w-8 h-8 text-blue-600 dark:text-blue-400" />
                                        </div>
                                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">No orders found</h3>
                                        <p class="text-gray-500 dark:text-gray-400 mb-4 max-w-sm mx-auto">
                                            {{ filters.status ? `No orders with '${filters.status}' status found.` : 'No orders match your current filters.' }}
                                        </p>
                                        <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
                                            <button @click="resetFilters"
                                                    class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors">
                                                <ArrowUpDown class="w-4 h-4" />
                                                Clear All Filters
                                            </button>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                                or try adjusting your search criteria
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </tbody>

                        <tbody v-else class="divide-y divide-gray-100 dark:divide-gray-700">
                            <tr
                                v-for="(order, index) in visibleOrders"
                                :key="order.id"
                                class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors"
                                :class="{ 'bg-blue-50 dark:bg-blue-900/20': selectedOrders.includes(order.id) }"
                            >
                                <!-- Checkbox -->
                                <td class="px-6 py-4">
                                    <input
                                        type="checkbox"
                                        :value="order.id"
                                        :checked="selectedOrders.includes(order.id)"
                                        @change="handleOrderSelectionChange(order.id)"
                                        class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 focus:ring-2"
                                    />
                                </td>

                                <!-- Order ID Cell -->
                                <td class="px-3 py-3">
                                    <div class="space-y-1">
                                        <div class="flex items-center gap-2">
                                            <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ order.order_number }}</span>
                                            <span v-if="order.admin_notes" class="text-xs" title="Has admin notes">📝</span>
                                        </div>
                                        <div class="flex items-center gap-1.5">
                                            <span v-if="isToday(order.date)" class="px-1.5 py-0.5 bg-green-100 text-green-700 text-xs font-medium rounded">Today</span>
                                            <span class="text-xs text-gray-500">{{ formatOrderDate(order.date) }}</span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Customer Cell -->
                                <td class="px-3 py-3">
                                    <div class="space-y-1.5">
                                        <!-- Customer Name & Contact -->
                                        <div class="flex items-center gap-2">
                                            <div class="w-8 h-8 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-sm">
                                                👤
                                            </div>
                                            <div class="min-w-0">
                                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">{{ truncateText(order.customer.name, 3) }}</p>
                                                <p class="text-xs text-gray-500 truncate">{{ order.customer?.phone }}</p>
                                            </div>
                                        </div>

                                        <!-- Address -->
                                        <p class="text-xs text-gray-500 line-clamp-1">📍 {{ order.customer?.address }}</p>

                                        <!-- Customer Note Badge -->
                                        <div v-if="order.customer?.note && order.customer.note !== 'N/A'"
                                             class="px-2 py-1 bg-amber-50 dark:bg-amber-900/30 rounded text-xs text-amber-700 dark:text-amber-300 line-clamp-1">
                                            💬 {{ order.customer?.note }}
                                        </div>

                                        <!-- Admin Notes Button -->
                                        <button @click="openAdminNotesModal(order)"
                                                class="text-xs text-blue-600 hover:text-blue-800 font-medium">
                                            {{ order.admin_notes ? '📝 Edit Notes' : '+ Add Notes' }}
                                        </button>
                                    </div>
                                </td>

                                <!-- Products Cell -->
                                <td class="px-3 py-3">
                                    <div v-if="order.items.length === 0" class="text-xs text-gray-400">No items</div>
                                    <div v-else class="space-y-1.5">
                                        <div v-for="(item, idx) in order.items.slice(0, 2)" :key="item.id"
                                             class="flex items-center gap-2 p-1.5 bg-gray-50 dark:bg-gray-700/50 rounded">
                                            <img :src="item.product.image || ''" alt=""
                                                 class="w-8 h-8 rounded object-cover bg-gray-200" />
                                            <div class="min-w-0 flex-1">
                                                <p class="text-xs font-medium text-gray-900 dark:text-gray-100 truncate">
                                                    {{ truncateText(item.product.name, 4) }}
                                                </p>
                                                <div class="flex items-center gap-1.5 text-xs text-gray-500">
                                                    <span>৳{{ item.price }}</span>
                                                    <span>×{{ item.quantity }}</span>
                                                    <span v-if="item.is_pre_order" class="px-1 py-0.5 bg-amber-100 text-amber-700 rounded text-xs">Pre</span>
                                                </div>
                                                <div v-if="item.variation" class="flex flex-wrap gap-1 mt-0.5">
                                                    <span v-for="(value, key) in item.variation.attributes" :key="key"
                                                          class="px-1 py-0.5 bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 rounded text-xs">
                                                        {{ key }}: {{ value }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <button v-if="order.items.length > 2"
                                                class="text-xs text-blue-600 hover:text-blue-800 font-medium">
                                            +{{ order.items.length - 2 }} more items
                                        </button>
                                    </div>
                                </td>

                                <!-- Total Cell -->
                                <td class="px-3 py-3">
                                    <div class="text-center">
                                        <span class="inline-block px-3 py-1.5 bg-green-50 dark:bg-green-900/30 text-green-700 dark:text-green-300 rounded-lg font-bold text-sm">
                                            ৳{{ parseFloat(order.total).toLocaleString() }}
                                        </span>
                                    </div>
                                </td>

                                <!-- Status Cell -->
                                <td class="px-3 py-3">
                                    <button @click="openStatusChangeModal(order)"
                                            :disabled="updatingOrders[order.id]"
                                            class="px-2.5 py-1.5 rounded-lg text-xs font-semibold transition-colors flex items-center gap-1.5"
                                            :class="{
                                                'bg-amber-100 text-amber-700 hover:bg-amber-200': order.status === 'pending',
                                                'bg-blue-100 text-blue-700 hover:bg-blue-200': order.status === 'processing',
                                                'bg-purple-100 text-purple-700 hover:bg-purple-200': order.status === 'shipped',
                                                'bg-green-100 text-green-700 hover:bg-green-200': order.status === 'delivered',
                                                'bg-red-100 text-red-700 hover:bg-red-200': order.status === 'cancelled',
                                                'bg-pink-100 text-pink-700 hover:bg-pink-200': order.status === 'returned',
                                                'bg-cyan-100 text-cyan-700 hover:bg-cyan-200': order.status === 'on_hold',
                                                'bg-indigo-100 text-indigo-700 hover:bg-indigo-200': order.status === 'confirmed',
                                                'opacity-60': updatingOrders[order.id]
                                            }">
                                        <span>{{ order.status === 'pending' ? '⏳' : order.status === 'processing' ? '⚙️' : order.status === 'shipped' ? '🚚' : order.status === 'delivered' ? '✅' : order.status === 'cancelled' ? '❌' : order.status === 'returned' ? '↩️' : order.status === 'on_hold' ? '⏸️' : '✔️' }}</span>
                                        <span class="capitalize">{{ order.status }}</span>
                                        <svg v-if="!updatingOrders[order.id]" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                        </svg>
                                        <span v-else class="w-3 h-3 border-2 border-current border-t-transparent rounded-full animate-spin"></span>
                                    </button>
                                </td>

                                <!-- Payment Cell -->
                                <td class="px-3 py-3">
                                    <span class="px-2 py-1 rounded-lg text-xs font-medium"
                                          :class="{
                                              'bg-red-100 text-red-700': order.payment_status === 'unpaid',
                                              'bg-green-100 text-green-700': order.payment_status === 'paid',
                                              'bg-gray-100 text-gray-700': order.payment_status === 'refunded'
                                          }">
                                        {{ order.payment_status === 'unpaid' ? '❌' : order.payment_status === 'paid' ? '✅' : '↩️' }}
                                        {{ order.payment_status }}
                                    </span>
                                </td>

                                <!-- Courier Cell -->
                                <td class="px-3 py-3">
                                    <div v-if="order.tracking_number || order.is_courier" class="space-y-1.5">
                                        <div class="flex items-center gap-1.5">
                                            <span class="text-sm">🚚</span>
                                            <span class="px-1.5 py-0.5 rounded text-xs font-medium"
                                                  :class="order.courier_name ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'">
                                                {{ order.courier_name || 'N/A' }}
                                            </span>
                                        </div>
                                        <div v-if="order.consignment_id" class="text-xs text-gray-500">
                                            ID: {{ order.consignment_id }}
                                        </div>
                                        <a v-if="order.tracking_number && order.tracking_number !== 'N/A'"
                                           :href="`https://steadfast.com.bd/t/${order.tracking_number}`"
                                           target="_blank"
                                           class="text-xs text-blue-600 hover:text-blue-800 font-medium">
                                            🔍 Track
                                        </a>
                                    </div>
                                    <div v-else class="text-xs text-gray-400">No courier</div>
                                </td>

                                <!-- Actions Cell -->
                                <td class="px-3 py-3">
                                    <div class="relative flex justify-center">
                                        <button @click.stop="toggleActionMenu(order.id)"
                                                class="w-8 h-8 rounded-lg flex items-center justify-center transition-colors"
                                                :class="activeActionMenu === order.id ? 'bg-blue-100 text-blue-600' : 'bg-gray-100 hover:bg-gray-200 text-gray-600'">
                                            <component :is="activeActionMenu === order.id ? X : MoreVertical" class="w-4 h-4" />
                                        </button>

                                        <!-- Action Dropdown -->
                                        <div v-if="activeActionMenu === order.id"
                                             class="absolute right-0 top-full mt-1 w-44 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 z-50 py-1">
                                            <button @click="checkFraud(order?.customer?.phone); toggleActionMenu(order.id);"
                                                    class="w-full px-3 py-2 text-left text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center gap-2">
                                                <User class="w-4 h-4" /> Check Fraud
                                            </button>
                                            <button @click="openCourierModal(order, order.id); toggleActionMenu(order.id);"
                                                    class="w-full px-3 py-2 text-left text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center gap-2">
                                                <MapPin class="w-4 h-4" /> Courier
                                            </button>
                                            <Link :href="route('admin.orders.edit', order.id)"
                                                  @click="toggleActionMenu(order.id)"
                                                  class="w-full px-3 py-2 text-left text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center gap-2">
                                                <SquarePen class="w-4 h-4" /> Edit
                                            </Link>
                                            <hr class="my-1 border-gray-200 dark:border-gray-700" />
                                            <button @click="openDeleteModal(order.id); toggleActionMenu(order.id);"
                                                    class="w-full px-3 py-2 text-left text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 flex items-center gap-2">
                                                <Trash2Icon class="w-4 h-4" /> Delete
                                            </button>
                                        </div>

                                        <!-- Backdrop -->
                                        <div v-if="activeActionMenu === order.id"
                                             class="fixed inset-0 z-40"
                                             @click="activeActionMenu = null"></div>
                                    </div>
                                </td>
                            </tr>

                            <!-- Empty State -->
                            <tr v-if="visibleOrders.length === 0">
                                <td colspan="9" class="px-4 py-8 text-center text-gray-500">
                                    No orders found matching your filters.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-700/25">
                    <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            Showing <span class="font-medium text-gray-900 dark:text-white">{{ from }}-{{ to }}</span>
                            of <span class="font-medium text-gray-900 dark:text-white">{{ total }}</span> orders
                        </div>

                        <div class="flex items-center gap-2">
                            <button @click="goToPage(1)" :disabled="currentPage === 1 || loading"
                                    class="inline-flex items-center justify-center w-8 h-8 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 hover:text-gray-700 disabled:opacity-50 disabled:cursor-not-allowed dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700">
                                <ChevronsLeft class="w-4 h-4 text-blue-600" />
                            </button>

                            <button @click="goToPage(currentPage - 1)" :disabled="currentPage === 1 || loading"
                                    class="inline-flex items-center justify-center w-8 h-8 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 hover:text-gray-700 disabled:opacity-50 disabled:cursor-not-allowed dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700">
                                <ChevronLeft class="w-4 h-4 text-green-600" />
                            </button>

                            <div class="flex items-center gap-2 px-3 py-2 text-sm bg-blue-50 text-blue-700 rounded-lg dark:bg-blue-900/25 dark:text-blue-300">
                                <span class="font-medium">{{ currentPage }}</span>
                                <span class="text-blue-500 dark:text-blue-400">of</span>
                                <span class="font-medium">{{ lastPage }}</span>
                            </div>

                            <button @click="goToPage(currentPage + 1)" :disabled="currentPage === lastPage || loading"
                                    class="inline-flex items-center justify-center w-8 h-8 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 hover:text-gray-700 disabled:opacity-50 disabled:cursor-not-allowed dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700">
                                <ChevronRight class="w-4 h-4 text-green-600" />
                            </button>

                            <button @click="goToPage(lastPage)" :disabled="currentPage === lastPage || loading"
                                    class="inline-flex items-center justify-center w-8 h-8 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 hover:text-gray-700 disabled:opacity-50 disabled:cursor-not-allowed dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700">
                                <ChevronsRight class="w-4 h-4 text-blue-600" />
                            </button>
                        </div>
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
