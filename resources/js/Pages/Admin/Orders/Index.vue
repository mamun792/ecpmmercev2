<script setup>
import { ref, watch, computed, onMounted, onUnmounted, nextTick } from "vue";
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
    ChevronUp,
    Timer,
    Zap,
    Target,
    Calendar,
    TrendingUp,
    Bell,
    Star,
    CheckSquare,
    Loader2,
    MousePointer,
    ShoppingCart,
    DollarSign,
    RotateCcw,
    Pause,
    PlayCircle,
    XOctagon
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

// Core state
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
const isDownloading = ref(false);
const showAdminNotesModal = ref(false);
const selectedOrderForNotes = ref(null);
const viewMode = ref("table");

// Pagination state
const currentPage = ref(1);
const lastPage = ref(1);

// Advanced features state
const showSkeletonLoader = ref(true);
const showSuccessAnimation = ref(false);
const animatingOrders = ref(new Set());
const processingOrders = ref(new Set());
const contextMenuPosition = ref({ x: 0, y: 0 });
const showContextMenu = ref(false);
const contextMenuOrder = ref(null);
const timeOfDay = ref('morning'); // morning, midday, evening
const urgentOrders = ref(new Set());
const newOrdersToday = ref(new Set());
const courierSuggestions = ref({});
const qualityChecklist = ref({});
const userPreferences = ref({
    defaultView: 'table',
    autoRefresh: true,
    soundEnabled: true,
    showTimeTracking: true,
    preferredCourier: 'steadfast'
});

// New feature states for all requested improvements
const searchQuery = ref('');
const searchSuggestions = ref([]);
const showSuggestions = ref(false);
const bulkStatusModal = ref(false);
const selectedBulkStatus = ref('');
const showExportMenu = ref(false);
const exportingOrders = ref(false);
const showKeyboardShortcuts = ref(false);
const selectedOrderIndex = ref(0);
const expandedOrders = ref([]);
const orderTimelines = ref({});
const showOrderSummary = ref(true);
const notificationQueue = ref([]);

// Time tracking
const orderTimestamps = ref({});
const progressIndicators = ref({});

// Status Change Modal
const showStatusChangeModal = ref(false);
const statusChangeOrder = ref(null);

// Performance metrics for better insights
const performanceMetrics = computed(() => {
    const totalOrders = props.statusCounts.find(s => s.status === 'total')?.count || 0;
    const deliveredOrders = props.statusCounts.find(s => s.status === 'delivered')?.count || 0;
    const cancelledOrders = props.statusCounts.find(s => s.status === 'cancelled')?.count || 0;
    const pendingOrders = props.statusCounts.find(s => s.status === 'pending')?.count || 0;

    return {
        deliveryRate: totalOrders > 0 ? Math.round((deliveredOrders / totalOrders) * 100) : 0,
        cancellationRate: totalOrders > 0 ? Math.round((cancelledOrders / totalOrders) * 100) : 0,
        pendingRate: totalOrders > 0 ? Math.round((pendingOrders / totalOrders) * 100) : 0,
        totalValue: Number(props.statusCounts.find(s => s.status === 'total')?.sales || 0),
        averageOrderValue: totalOrders > 0 ? Math.round(Number(props.statusCounts.find(s => s.status === 'total')?.sales || 0) / totalOrders) : 0
    };
});

// Time of day workflow detection
const currentWorkflowPhase = computed(() => {
    const hour = new Date().getHours();
    if (hour >= 6 && hour < 12) return 'morning';
    if (hour >= 12 && hour < 18) return 'midday';
    return 'evening';
});

// Smart order prioritization
const prioritizedOrders = computed(() => {
    if (!visibleOrders.value) return [];

    return visibleOrders.value.map(order => {
        const createdTime = new Date(order.created_at || order.date || Date.now());
        const hoursOld = isNaN(createdTime.getTime()) ? 0 : (Date.now() - createdTime.getTime()) / (1000 * 60 * 60);
        const orderValue = parseFloat(order.total_amount || order.total || 0);

        let priority = 'normal';
        let priorityScore = 0;

        // High value orders
        if (orderValue > 5000) priorityScore += 30;

        // Time-based priority
        if (hoursOld > 24) priorityScore += 40; // Older than 24 hours
        if (hoursOld > 48) priorityScore += 60; // Older than 48 hours

        // Status-based priority
        if (order.status === 'pending') priorityScore += 25;
        if (order.status === 'processing' && hoursOld > 8) priorityScore += 35;

        // New orders today
        if (hoursOld < 12) {
            newOrdersToday.value.add(order.id);
        }

        // Determine priority level
        if (priorityScore >= 80) priority = 'critical';
        else if (priorityScore >= 50) priority = 'high';
        else if (priorityScore >= 25) priority = 'medium';

        if (priority === 'critical' || priority === 'high') {
            urgentOrders.value.add(order.id);
        }

        return {
            ...order,
            priority,
            priorityScore,
            hoursOld: Math.round(hoursOld),
            isNew: hoursOld < 12,
            timeInStatus: calculateTimeInStatus(order)
        };
    });
    // Keep backend sorting (date DESC - newest first)
    // Priority info is still available on each order for display
});

// Courier suggestions based on delivery area
const getSmartCourierSuggestion = (order) => {
    if (!order.shipping_address) return 'steadfast';

    const address = order.shipping_address.toLowerCase();

    // Dhaka area - Pathao is faster
    if (address.includes('dhaka') || address.includes('gulshan') || address.includes('dhanmondi')) {
        return 'pathao';
    }

    // Outside Dhaka - Steadfast has better coverage
    return 'steadfast';
};

// Quality checklist for status changes
const getQualityChecklist = (currentStatus, newStatus) => {
    const checklists = {
        'pending_to_processing': [
            'Customer details verified',
            'Payment confirmed',
            'Product availability checked',
            'Delivery address validated'
        ],
        'processing_to_shipped': [
            'Product quality checked',
            'Packaging completed',
            'Courier booking confirmed',
            'Tracking number generated'
        ],
        'shipped_to_delivered': [
            'Delivery confirmation received',
            'Customer feedback collected',
            'Payment settled',
            'Return window started'
        ]
    };

    const key = `${currentStatus}_to_${newStatus}`;
    return checklists[key] || [];
};
// Keyboard shortcuts and lifecycle
const handleKeyboardShortcuts = (event) => {
    // Ctrl+A or Cmd+A - Select all visible orders
    if ((event.ctrlKey || event.metaKey) && event.key === 'a') {
        event.preventDefault();
        if (prioritizedOrders.value?.length > 0) {
            selectedOrders.value = prioritizedOrders.value.map(order => order.id);
            toast.success(`Selected all ${selectedOrders.value.length} orders`, {
                icon: '✅',
                duration: 2000
            });
        }
    }

    // Ctrl+F - Focus search
    if ((event.ctrlKey || event.metaKey) && event.key === 'f') {
        event.preventDefault();
        document.getElementById('order-search-input')?.focus();
    }

    // Arrow Up/Down - Navigate orders
    if (event.key === 'ArrowDown') {
        event.preventDefault();
        selectedOrderIndex.value = Math.min(selectedOrderIndex.value + 1, (prioritizedOrders.value?.length || 1) - 1);
    }
    if (event.key === 'ArrowUp') {
        event.preventDefault();
        selectedOrderIndex.value = Math.max(selectedOrderIndex.value - 1, 0);
    }

    // Enter - Open selected order details
    if (event.key === 'Enter' && prioritizedOrders.value?.length > selectedOrderIndex.value) {
        const order = prioritizedOrders.value[selectedOrderIndex.value];
        if (order) {
            toggleOrderExpansion(order.id);
        }
    }

    // Ctrl+E - Export orders
    if ((event.ctrlKey || event.metaKey) && event.key === 'e') {
        event.preventDefault();
        showExportMenu.value = !showExportMenu.value;
    }

    // Ctrl+? - Show keyboard shortcuts
    if ((event.ctrlKey || event.metaKey) && event.key === '/') {
        event.preventDefault();
        showKeyboardShortcuts.value = !showKeyboardShortcuts.value;
    }

    // Escape - Clear selection or close modals
    if (event.key === 'Escape') {
        if (showContextMenu.value) {
            showContextMenu.value = false;
        } else if (showKeyboardShortcuts.value) {
            showKeyboardShortcuts.value = false;
        } else if (showExportMenu.value) {
            showExportMenu.value = false;
        } else if (selectedOrders.value.length > 0) {
            selectedOrders.value = [];
            toast.success('Selection cleared', { icon: '🔄' });
        }
    }

    // F5 - Refresh orders
    if (event.key === 'F5') {
        event.preventDefault();
        refreshOrders();
    }

    // Ctrl+Shift+U - Mark urgent orders
    if (event.ctrlKey && event.shiftKey && event.key === 'U') {
        event.preventDefault();
        highlightUrgentOrders();
    }
};

const refreshOrders = () => {
    showSkeletonLoader.value = true;
    router.reload({ only: ['orders', 'statusCounts'] });
    toast.success('Orders refreshed', { icon: '🔄' });
    hideSkeletonLoader();
};

const highlightUrgentOrders = () => {
    const urgentCount = urgentOrders.value.size;
    if (urgentCount > 0) {
        toast.error(`${urgentCount} urgent orders need attention!`, {
            icon: '🚨',
            duration: 5000
        });
    } else {
        toast.success('No urgent orders found', { icon: '✅' });
    }
};

// Auto-refresh functionality
let autoRefreshInterval;
const startAutoRefresh = () => {
    if (userPreferences.value.autoRefresh) {
        autoRefreshInterval = setInterval(() => {
            router.reload({ only: ['orders', 'statusCounts'], preserveState: true });
        }, 30000); // Refresh every 30 seconds
    }
};

const stopAutoRefresh = () => {
    if (autoRefreshInterval) {
        clearInterval(autoRefreshInterval);
    }
};

// Lifecycle hooks
onMounted(() => {
    // Initialize pagination values
    currentPage.value = props.orders.current_page || 1;
    lastPage.value = props.orders.last_page || 1;

    loadUserPreferences();
    document.addEventListener('keydown', handleKeyboardShortcuts);
    hideSkeletonLoader();
    startAutoRefresh();

    // Detect time of day and show appropriate workflow tips
    timeOfDay.value = currentWorkflowPhase.value;

    // Show workflow reminder based on time
    setTimeout(() => {
        showWorkflowReminder();
    }, 2000);
});

onUnmounted(() => {
    document.removeEventListener('keydown', handleKeyboardShortcuts);
    stopAutoRefresh();
    saveUserPreferences();
});

const showWorkflowReminder = () => {
    const phase = currentWorkflowPhase.value;
    const reminders = {
        morning: {
            message: 'Morning routine: Review overnight orders and pending confirmations',
            icon: '🌅',
            actions: ['Check new orders', 'Confirm pending', 'Process urgent']
        },
        midday: {
            message: 'Midday check: Send shipping updates and handle inquiries',
            icon: '☀️',
            actions: ['Send to courier', 'Update customers', 'Track deliveries']
        },
        evening: {
            message: 'Evening wrap-up: Confirm deliveries and plan tomorrow',
            icon: '🌅',
            actions: ['Confirm deliveries', 'Plan shipping', 'Review metrics']
        }
    };

    if (reminders[phase]) {
        toast.success(reminders[phase].message, {
            icon: reminders[phase].icon,
            duration: 4000
        });
    }
};

// Utility functions for advanced features
const calculateTimeInStatus = (order) => {
    const statusUpdateTime = new Date(order.updated_at || order.created_at);
    const hoursInStatus = (Date.now() - statusUpdateTime.getTime()) / (1000 * 60 * 60);

    if (hoursInStatus < 1) return `${Math.round(hoursInStatus * 60)}m`;
    if (hoursInStatus < 24) return `${Math.round(hoursInStatus)}h`;
    return `${Math.round(hoursInStatus / 24)}d`;
};

const triggerSuccessAnimation = (orderId) => {
    animatingOrders.value.add(orderId);
    showSuccessAnimation.value = true;

    setTimeout(() => {
        animatingOrders.value.delete(orderId);
        if (animatingOrders.value.size === 0) {
            showSuccessAnimation.value = false;
        }
    }, 1500);

    // Play success sound if enabled
    if (userPreferences.value.soundEnabled) {
        playSuccessSound();
    }
};

const playSuccessSound = () => {
    const audio = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmwhBSuH0vLIfiqDK3/i9dRgJAYyi8v01mMSBy2A0/LNfYi2LYH19FJhJAYl');
    audio.play().catch(() => {}); // Ignore errors
};

const showContextMenuAt = (event, order) => {
    event.preventDefault();
    contextMenuPosition.value = { x: event.clientX, y: event.clientY };
    contextMenuOrder.value = order;
    showContextMenu.value = true;

    // Close menu on outside click
    const closeMenu = (e) => {
        if (!e.target.closest('.context-menu')) {
            showContextMenu.value = false;
            document.removeEventListener('click', closeMenu);
        }
    };

    setTimeout(() => {
        document.addEventListener('click', closeMenu);
    }, 100);
};

// Smart defaults and preferences
const loadUserPreferences = () => {
    const saved = localStorage.getItem('orderManagementPreferences');
    if (saved) {
        userPreferences.value = { ...userPreferences.value, ...JSON.parse(saved) };
    }
};

const saveUserPreferences = () => {
    localStorage.setItem('orderManagementPreferences', JSON.stringify(userPreferences.value));
};

// Skeleton loader management
const hideSkeletonLoader = () => {
    setTimeout(() => {
        showSkeletonLoader.value = false;
    }, 800);
};

// Progress indicators for bulk operations
const updateProgress = (operation, current, total) => {
    progressIndicators.value[operation] = {
        current,
        total,
        percentage: Math.round((current / total) * 100)
    };
};

// Pagination reactive references (lastPage is defined above)
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

// Enhanced helper functions for advanced features
const handleSelectAll = () => {
    if (prioritizedOrders.value?.length > 0) {
        if (selectAll.value) {
            selectedOrders.value = prioritizedOrders.value.map(order => order.id);
        } else {
            selectedOrders.value = [];
        }
    }
};

const sortField = ref('created_at');
const sortDirection = ref('desc');

// Function moved to avoid duplication - using enhanced version below

const formatOrderDate = (dateString) => {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric'
    });
};

const truncateText = (text, wordLimit) => {
    if (!text) return 'N/A';
    const words = text.split(' ');
    if (words.length <= wordLimit) return text;
    return words.slice(0, wordLimit).join(' ') + '...';
};

const activeActionMenu = ref(null);

const toggleActionMenu = (orderId) => {
    if (activeActionMenu.value === orderId) {
        activeActionMenu.value = null;
    } else {
        activeActionMenu.value = orderId;
    }
};

// Enhanced bulk operation functions with progress tracking
const sendBulkToSteadfast = async () => {
    if (selectedOrders.value.length === 0) {
        toast.error("No orders selected");
        return;
    }

    loading.value = true;
    updateProgress('steadfast', 0, selectedOrders.value.length);

    try {
        const response = await axios.post("/admin/send-bulk-steadfast", {
            order_ids: selectedOrders.value,
        });

        // Simulate progress updates
        for (let i = 1; i <= selectedOrders.value.length; i++) {
            setTimeout(() => {
                updateProgress('steadfast', i, selectedOrders.value.length);
            }, (i * 200));
        }

        setTimeout(() => {
            toast.success(response.data.message || "Orders sent to Steadfast successfully");
            selectedOrders.value.forEach(orderId => triggerSuccessAnimation(orderId));
            selectedOrders.value = [];
            delete progressIndicators.value.steadfast;
            router.reload();
        }, selectedOrders.value.length * 200 + 500);

    } catch (error) {
        console.error("Error sending to Steadfast:", error);
        toast.error(error.response?.data?.message || "Failed to send to Steadfast");
        delete progressIndicators.value.steadfast;
    } finally {
        loading.value = false;
    }
};

const sendBulkToPathao = async () => {
    if (selectedOrders.value.length === 0) {
        toast.error("No orders selected");
        return;
    }

    loading.value = true;
    updateProgress('pathao', 0, selectedOrders.value.length);

    try {
        const response = await axios.post("/admin/send-bulk-pathao", {
            order_ids: selectedOrders.value,
        });

        // Simulate progress updates
        for (let i = 1; i <= selectedOrders.value.length; i++) {
            setTimeout(() => {
                updateProgress('pathao', i, selectedOrders.value.length);
            }, (i * 200));
        }

        setTimeout(() => {
            toast.success(response.data.message || "Orders sent to Pathao successfully");
            selectedOrders.value.forEach(orderId => triggerSuccessAnimation(orderId));
            selectedOrders.value = [];
            delete progressIndicators.value.pathao;
            router.reload();
        }, selectedOrders.value.length * 200 + 500);

    } catch (error) {
        console.error("Error sending to Pathao:", error);
        toast.error(error.response?.data?.message || "Failed to send to Pathao");
        delete progressIndicators.value.pathao;
    } finally {
        loading.value = false;
    }
};

const printBulkInvoice = async () => {
    if (selectedOrders.value.length === 0) {
        toast.error("No orders selected");
        return;
    }

    try {
        const url = `/admin/bulk-invoice/print?order_ids=${selectedOrders.value.join(',')}`;
        window.open(url, '_blank');
        toast.success("Opening print preview...");
        selectedOrders.value.forEach(orderId => triggerSuccessAnimation(orderId));
    } catch (error) {
        console.error("Error printing invoices:", error);
        toast.error("Failed to print invoices");
    }
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

// Duplicate function removed - using the enhanced async version above

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

// Function moved above to avoid duplication

// Get sort icon
const getSortIcon = (column) => {
    if (filters.value.sort_by !== column) return "";
    return filters.value.sort_direction === "asc" ? "↑" : "↓";
};

// Format date
// Function moved above to avoid duplication

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

// Function moved above to avoid duplication

// Action Menu State
// Functions moved above to avoid duplication

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

// ============================================
// NEW FEATURES IMPLEMENTATION
// ============================================

// 1. Search with Autocomplete
const handleSearch = (event) => {
    const query = event.target.value.toLowerCase();
    searchQuery.value = query;

    if (query.length > 0) {
        // Generate suggestions from existing orders
        const suggestions = [];
        props.orders.data.forEach(order => {
            // Match order number
            if (order.order_number.toLowerCase().includes(query)) {
                suggestions.push({ type: 'order', text: order.order_number, id: order.id });
            }
            // Match customer name
            if (order.customer?.name.toLowerCase().includes(query)) {
                suggestions.push({ type: 'customer', text: order.customer.name, id: order.id });
            }
            // Match phone
            if (order.customer?.phone.includes(query)) {
                suggestions.push({ type: 'phone', text: order.customer.phone, id: order.id });
            }
        });

        searchSuggestions.value = suggestions.slice(0, 5); // Limit to 5 suggestions
        showSuggestions.value = suggestions.length > 0;
    } else {
        showSuggestions.value = false;
    }
};

const selectSuggestion = (suggestion) => {
    searchQuery.value = suggestion.text;
    filters.value.customer_search = suggestion.text;
    showSuggestions.value = false;
    applyFilters();
};

// 2. Bulk Status Update
const openBulkStatusModal = () => {
    if (selectedOrders.value.length === 0) {
        toast.error('Please select orders first');
        return;
    }
    bulkStatusModal.value = true;
};

const closeBulkStatusModal = () => {
    bulkStatusModal.value = false;
    selectedBulkStatus.value = '';
};

const applyBulkStatus = async () => {
    if (!selectedBulkStatus.value) {
        toast.error('Please select a status');
        return;
    }

    try {
        const response = await axios.post('/admin/orders/bulk-status-update', {
            order_ids: selectedOrders.value,
            status: selectedBulkStatus.value
        });

        toast.success(`${selectedOrders.value.length} orders updated to ${selectedBulkStatus.value}`);
        selectedOrders.value = [];
        bulkStatusModal.value = false;
        router.reload();
    } catch (error) {
        toast.error(error.response?.data?.message || 'Failed to update orders');
    }
};

// 3. Export Functionality
const exportOrders = async (format = 'csv') => {
    exportingOrders.value = true;
    showExportMenu.value = false;

    try {
        const response = await axios.post('/admin/orders/export', {
            format,
            filters: filters.value,
            selected_ids: selectedOrders.value.length > 0 ? selectedOrders.value : null
        }, {
            responseType: 'blob'
        });

        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', `orders_${new Date().toISOString().split('T')[0]}.${format}`);
        document.body.appendChild(link);
        link.click();
        link.remove();

        toast.success(`Orders exported as ${format.toUpperCase()}`);
    } catch (error) {
        toast.error('Failed to export orders');
    } finally {
        exportingOrders.value = false;
    }
};

// 4. Order Timeline & Expansion
const toggleOrderExpansion = (orderId) => {
    const index = expandedOrders.value.indexOf(orderId);
    if (index > -1) {
        expandedOrders.value.splice(index, 1);
    } else {
        expandedOrders.value.push(orderId);
        // Fetch timeline if not already loaded
        if (!orderTimelines.value[orderId]) {
            fetchOrderTimeline(orderId);
        }
    }
};

const fetchOrderTimeline = async (orderId) => {
    try {
        const response = await axios.get(`/admin/orders/${orderId}/timeline`);
        orderTimelines.value[orderId] = response.data.timeline || response.data || [];
    } catch (error) {
        console.error('Failed to fetch timeline:', error);
        // Show error state instead of fake data
        orderTimelines.value[orderId] = [{
            title: 'Timeline Unavailable',
            description: 'Could not load order history',
            status: 'error',
            user: 'System',
            timestamp: 'N/A'
        }];
    }
};

// 5. Real-time Notifications
const showNotification = (message, type = 'info') => {
    const notification = {
        id: Date.now(),
        message,
        type,
        timestamp: new Date()
    };

    notificationQueue.value.push(notification);

    toast[type](message, {
        duration: 4000,
        icon: type === 'success' ? '✅' : type === 'error' ? '❌' : 'ℹ️'
    });

    // Auto-remove after 5 seconds
    setTimeout(() => {
        notificationQueue.value = notificationQueue.value.filter(n => n.id !== notification.id);
    }, 5000);
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

        <div class="max-w-10xl mx-auto space-y-6">
            <!-- Enhanced Header with Workflow Indicators -->
            <div class="flex items-center justify-between">
                <div>
                    <div class="flex items-center gap-3">
                        <h1 class="text-2xl font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <div class="w-8 h-8 rounded-lg bg-gradient-to-r from-blue-500 to-indigo-600 flex items-center justify-center">
                                <Package class="w-5 h-5 text-white" />
                            </div>
                            Orders
                        </h1>

                        <!-- Workflow Phase Indicator -->
                        <div class="flex items-center gap-2 px-3 py-1.5 rounded-lg transition-all duration-300"
                             :class="{
                                 'bg-orange-100 text-orange-700': currentWorkflowPhase === 'morning',
                                 'bg-yellow-100 text-yellow-700': currentWorkflowPhase === 'midday',
                                 'bg-purple-100 text-purple-700': currentWorkflowPhase === 'evening'
                             }">
                            <div class="w-2 h-2 rounded-full animate-pulse"
                                 :class="{
                                     'bg-orange-500': currentWorkflowPhase === 'morning',
                                     'bg-yellow-500': currentWorkflowPhase === 'midday',
                                     'bg-purple-500': currentWorkflowPhase === 'evening'
                                 }"></div>
                            <span class="text-xs font-medium capitalize">{{ currentWorkflowPhase }} Routine</span>
                        </div>

                        <!-- Urgent Orders Alert -->
                        <div v-if="urgentOrders.size > 0"
                             class="flex items-center gap-2 px-3 py-1.5 bg-red-100 dark:bg-red-900/20 text-red-700 dark:text-red-400 rounded-lg animate-pulse cursor-pointer hover:bg-red-200 dark:hover:bg-red-900/30 transition-colors"
                             @click="highlightUrgentOrders">
                            <Bell class="w-4 h-4" />
                            <span class="text-xs font-semibold">{{ urgentOrders.size }} Urgent</span>
                        </div>

                        <!-- New Orders Today -->
                        <div v-if="newOrdersToday.size > 0"
                             class="flex items-center gap-2 px-3 py-1.5 bg-green-100 dark:bg-green-900/20 text-green-700 dark:text-green-400 rounded-lg">
                            <Star class="w-4 h-4" />
                            <span class="text-xs font-medium">{{ newOrdersToday.size }} New Today</span>
                        </div>

                        <div class="group relative">
                            <div class="w-5 h-5 rounded-full bg-blue-100 dark:bg-blue-900/50 flex items-center justify-center cursor-help transition-transform hover:scale-110">
                                <span class="text-xs font-medium text-blue-600 dark:text-blue-400">?</span>
                            </div>
                            <div class="absolute left-0 top-8 w-80 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-xl p-4 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300 z-50">
                                <div class="space-y-3">
                                    <h4 class="font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-lg bg-gradient-to-r from-blue-500 to-indigo-600 flex items-center justify-center">
                                            <span class="text-white text-xs">💡</span>
                                        </div>
                                        Smart Order Management
                                    </h4>
                                    <div class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                                        <p class="flex items-start gap-2">
                                            <span class="text-green-500 text-xs mt-1">✓</span>
                                            <span><strong>Priority System:</strong> Orders auto-sorted by urgency and value</span>
                                        </p>
                                        <p class="flex items-start gap-2">
                                            <span class="text-blue-500 text-xs mt-1">⚡</span>
                                            <span><strong>Smart Suggestions:</strong> Courier recommendations based on delivery area</span>
                                        </p>
                                        <p class="flex items-start gap-2">
                                            <span class="text-purple-500 text-xs mt-1">🕒</span>
                                            <span><strong>Time Tracking:</strong> See how long orders spend in each status</span>
                                        </p>
                                        <div class="pt-2 border-t border-gray-200 dark:border-gray-700">
                                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                                <kbd class="px-1.5 py-0.5 bg-gray-200 dark:bg-gray-700 rounded text-xs font-mono">Ctrl+A</kbd> Select all
                                                <kbd class="px-1.5 py-0.5 bg-gray-200 dark:bg-gray-700 rounded text-xs font-mono ml-2">F5</kbd> Refresh
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Enhanced Metrics with Live Updates -->
                    <div class="mt-2 flex items-center gap-6 flex-wrap">
                        <div class="flex items-center gap-2">
                            <span class="text-sm text-gray-600 dark:text-gray-400">
                                {{ from }}-{{ to }} of {{ total }} orders
                            </span>
                            <div v-if="showSkeletonLoader" class="w-4 h-4">
                                <Loader2 class="w-4 h-4 animate-spin text-blue-500" />
                            </div>
                        </div>

                        <div class="w-px h-4 bg-gray-300 dark:bg-gray-600"></div>

                        <div class="flex items-center gap-2">
                            <span class="font-medium text-gray-900 dark:text-white">
                                ৳{{ Number(props.statusCounts.find(s => s.status === 'total')?.sales || 0).toLocaleString() }}
                            </span>
                            <span class="text-xs text-gray-500 dark:text-gray-400">total value</span>
                            <div class="flex items-center gap-1 text-xs text-green-600 dark:text-green-400">
                                <TrendingUp class="w-3 h-3" />
                                <span>{{ performanceMetrics.averageOrderValue.toLocaleString() }} avg</span>
                            </div>
                        </div>

                        <div class="w-px h-4 bg-gray-300 dark:bg-gray-600"></div>

                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
                            <span class="text-xs px-2 py-1 bg-green-100 text-green-700 rounded-full font-medium">Live updates</span>
                        </div>

                        <div class="w-px h-4 bg-gray-300 dark:bg-gray-600"></div>

                        <div class="flex items-center gap-3">
                            <div class="text-xs text-blue-600 dark:text-blue-400 font-medium">
                                {{ performanceMetrics.deliveryRate }}% delivery rate
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">•</div>
                            <div class="text-xs text-amber-600 dark:text-amber-400 font-medium">
                                {{ performanceMetrics.pendingRate }}% pending
                            </div>
                        </div>
                    </div>
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

                    <!-- NEW: Bulk Actions Dropdown -->
                    <div class="relative">
                        <button @click="openBulkStatusModal"
                                :disabled="selectedOrders.length === 0"
                                class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium rounded-lg transition-colors"
                                :class="selectedOrders.length > 0
                                    ? 'text-blue-600 bg-blue-50 hover:bg-blue-100'
                                    : 'text-gray-400 bg-gray-50 cursor-not-allowed'">
                            <CheckSquare class="w-4 h-4" />
                            <span>Bulk Update</span>
                            <span v-if="selectedOrders.length > 0" class="bg-blue-200 text-blue-800 px-1.5 py-0.5 rounded-full text-xs font-semibold">
                                {{ selectedOrders.length }}
                            </span>
                        </button>
                    </div>

                    <!-- NEW: Export Button -->
                    <div class="relative">
                        <button @click="showExportMenu = !showExportMenu"
                                class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-green-600 bg-green-50 hover:bg-green-100 rounded-lg transition-colors">
                            <Download class="w-4 h-4" />
                            <span>Export</span>
                        </button>

                        <!-- Export Menu -->
                        <div v-if="showExportMenu"
                             class="absolute right-0 top-12 w-48 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-xl z-50">
                            <button @click="exportOrders('csv')"
                                    class="w-full px-4 py-2 text-left text-sm hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors flex items-center gap-2">
                                <span>📄</span> Export as CSV
                            </button>
                            <button @click="exportOrders('xlsx')"
                                    class="w-full px-4 py-2 text-left text-sm hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors flex items-center gap-2">
                                <span>📊</span> Export as Excel
                            </button>
                            <button @click="exportOrders('pdf')"
                                    class="w-full px-4 py-2 text-left text-sm hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors flex items-center gap-2">
                                <span>📑</span> Export as PDF
                            </button>
                        </div>
                    </div>

                    <!-- NEW: Keyboard Shortcuts Help -->
                    <button @click="showKeyboardShortcuts = !showKeyboardShortcuts"
                            class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-purple-600 bg-purple-50 hover:bg-purple-100 rounded-lg transition-colors">
                        <MousePointer class="w-4 h-4" />
                        <span>Shortcuts</span>
                    </button>

                    <button @click="resetFilters"
                            class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-lg transition-colors">
                        <ArrowUpDown class="w-4 h-4" />
                        <span>Reset</span>
                    </button>
                </div>
            </div>

            <!-- NEW: Search with Autocomplete -->
            <div class="relative bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                <div class="flex items-center gap-4">
                    <div class="flex-1 relative">
                        <input id="order-search-input"
                               type="text"
                               v-model="searchQuery"
                               @input="handleSearch"
                               @focus="showSuggestions = searchQuery.length > 0 && searchSuggestions.length > 0"
                               placeholder="Search orders, customers, phone numbers... (Ctrl+F)"
                               class="w-full px-4 py-2 pl-10 pr-4 border-2 border-gray-200 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                        <div class="absolute left-3 top-1/2 -translate-y-1/2">
                            <Filter class="w-4 h-4 text-gray-400" />
                        </div>

                        <!-- Autocomplete Suggestions -->
                        <div v-if="showSuggestions"
                             class="absolute top-full left-0 right-0 mt-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-xl z-50 max-h-60 overflow-y-auto">
                            <div v-for="(suggestion, index) in searchSuggestions"
                                 :key="index"
                                 @click="selectSuggestion(suggestion)"
                                 class="px-4 py-2 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer flex items-center gap-2 transition-colors">
                                <span v-if="suggestion.type === 'order'" class="text-blue-500">🔖</span>
                                <span v-else-if="suggestion.type === 'customer'" class="text-green-500">👤</span>
                                <span v-else class="text-purple-500">📞</span>
                                <span class="text-sm">{{ suggestion.text }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="text-xs text-gray-500">
                        Press <kbd class="px-1.5 py-0.5 bg-gray-200 dark:bg-gray-700 rounded">Ctrl+F</kbd> to search
                    </div>
                </div>
            </div>

<!-- Enhanced Status Overview with Modern Design -->
            <div class="bg-gradient-to-r from-gray-50 to-white dark:from-gray-900 dark:to-gray-800 rounded-3xl border border-gray-200 dark:border-gray-700 p-8 shadow-lg">
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2 flex items-center space-x-2">
                        <TrendingUp class="w-5 h-5 text-blue-500" />
                        <span>Order Overview</span>
                    </h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Track your order performance in real-time</p>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-6">
                    <!-- Enhanced All Orders Card -->
                    <button
                        @click="selectStatus('')"
                        class="group relative p-6 rounded-2xl transition-all duration-300 transform hover:scale-105 overflow-hidden"
                        :class="filters.status === ''
                            ? 'bg-gradient-to-br from-blue-500 to-indigo-600 text-white shadow-xl ring-4 ring-blue-500/20'
                            : 'bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 shadow-lg hover:shadow-xl border border-gray-200 dark:border-gray-700'"
                    >
                        <!-- Background Pattern -->
                        <div class="absolute inset-0 opacity-10">
                            <div class="absolute inset-0 bg-gradient-to-br from-transparent via-white/20 to-transparent transform rotate-12"></div>
                        </div>

                        <div class="relative z-10 space-y-3">
                            <!-- Icon and Title -->
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <div :class="filters.status === '' ? 'bg-white/20 text-white' : 'bg-blue-100 text-blue-600 dark:bg-blue-900/30'"
                                         class="p-2 rounded-xl transition-all duration-200">
                                        <ShoppingCart class="w-5 h-5" />
                                    </div>
                                    <div class="text-left">
                                        <div :class="filters.status === '' ? 'text-white/90' : 'text-gray-500 dark:text-gray-400'"
                                             class="text-sm font-medium">All Orders</div>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <TrendingUp :class="filters.status === '' ? 'text-white/70' : 'text-green-500'"
                                                class="w-4 h-4 animate-pulse" />
                                </div>
                            </div>

                            <!-- Main Stats -->
                            <div class="space-y-2">
                                <div :class="filters.status === '' ? 'text-white' : 'text-gray-900 dark:text-white'"
                                     class="text-3xl font-bold transition-all duration-300 group-hover:scale-110">
                                    {{ props.statusCounts.find(s => s.status === 'total')?.count || 0 }}
                                </div>
                                <div class="flex items-center justify-between">
                                    <div :class="filters.status === '' ? 'text-white/90' : 'text-green-600 dark:text-green-400'"
                                         class="text-sm font-semibold flex items-center space-x-1">
                                        <DollarSign class="w-4 h-4" />
                                        <span>৳{{ Number(props.statusCounts.find(s => s.status === 'total')?.sales || 0).toLocaleString() }}</span>
                                    </div>
                                </div>
                                <div :class="filters.status === '' ? 'text-white/80' : 'text-blue-600 dark:text-blue-400'"
                                     class="text-xs font-medium flex items-center space-x-1">
                                    <Target class="w-3 h-3" />
                                    <span>Avg: ৳{{ performanceMetrics.averageOrderValue.toLocaleString() }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Animated shine effect -->
                        <div class="absolute inset-0 -top-10 -left-10 bg-gradient-to-r from-transparent via-white/20 to-transparent w-6 h-full rotate-12 transform translate-x-full group-hover:translate-x-[-200%] transition-transform duration-1000 ease-in-out"></div>
                    </button>



                    <!-- Enhanced Individual Status Cards -->
                    <button
                        v-for="status in availableStatuses.filter(s => s !== 'incomplete')"
                        :key="status"
                        @click="selectStatus(status)"
                        class="group relative p-5 rounded-2xl transition-all duration-300 transform hover:scale-105 overflow-hidden border"
                        :class="{
                            'bg-gradient-to-br from-amber-400 to-orange-500 text-white shadow-xl ring-4 ring-amber-500/20 border-amber-300': filters.status === status && status === 'pending',
                            'bg-gradient-to-br from-blue-400 to-blue-600 text-white shadow-xl ring-4 ring-blue-500/20 border-blue-300': filters.status === status && status === 'processing',
                            'bg-gradient-to-br from-red-400 to-red-600 text-white shadow-xl ring-4 ring-red-500/20 border-red-300': filters.status === status && status === 'cancelled',
                            'bg-gradient-to-br from-purple-400 to-purple-600 text-white shadow-xl ring-4 ring-purple-500/20 border-purple-300': filters.status === status && status === 'shipped',
                            'bg-gradient-to-br from-green-400 to-green-600 text-white shadow-xl ring-4 ring-green-500/20 border-green-300': filters.status === status && status === 'delivered',
                            'bg-gradient-to-br from-pink-400 to-pink-600 text-white shadow-xl ring-4 ring-pink-500/20 border-pink-300': filters.status === status && status === 'returned',
                            'bg-gradient-to-br from-gray-400 to-gray-600 text-white shadow-xl ring-4 ring-gray-500/20 border-gray-300': filters.status === status && status === 'on_hold',
                            'bg-gradient-to-br from-indigo-400 to-indigo-600 text-white shadow-xl ring-4 ring-indigo-500/20 border-indigo-300': filters.status === status && status === 'confirmed',
                            'bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 shadow-lg hover:shadow-xl border-gray-200 dark:border-gray-700': filters.status !== status
                        }"
                    >
                        <!-- Background Pattern -->
                        <div class="absolute inset-0 opacity-10" v-if="filters.status === status">
                            <div class="absolute inset-0 bg-gradient-to-br from-transparent via-white/30 to-transparent transform rotate-12"></div>
                        </div>

                        <div class="relative z-10 space-y-3">
                            <!-- Icon and Status -->
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-2">
                                    <div :class="{
                                        'bg-white/20 text-white': filters.status === status && status === 'pending',
                                        'bg-white/20 text-white': filters.status === status && status === 'processing',
                                        'bg-white/20 text-white': filters.status === status && status === 'cancelled',
                                        'bg-white/20 text-white': filters.status === status && status === 'shipped',
                                        'bg-white/20 text-white': filters.status === status && status === 'delivered',
                                        'bg-white/20 text-white': filters.status === status && status === 'returned',
                                        'bg-white/20 text-white': filters.status === status && status === 'on_hold',
                                        'bg-white/20 text-white': filters.status === status && status === 'confirmed',
                                        'bg-amber-100 text-amber-600 dark:bg-amber-900/30': filters.status !== status && status === 'pending',
                                        'bg-blue-100 text-blue-600 dark:bg-blue-900/30': filters.status !== status && status === 'processing',
                                        'bg-red-100 text-red-600 dark:bg-red-900/30': filters.status !== status && status === 'cancelled',
                                        'bg-purple-100 text-purple-600 dark:bg-purple-900/30': filters.status !== status && status === 'shipped',
                                        'bg-green-100 text-green-600 dark:bg-green-900/30': filters.status !== status && status === 'delivered',
                                        'bg-pink-100 text-pink-600 dark:bg-pink-900/30': filters.status !== status && status === 'returned',
                                        'bg-gray-100 text-gray-600 dark:bg-gray-900/30': filters.status !== status && status === 'on_hold',
                                        'bg-indigo-100 text-indigo-600 dark:bg-indigo-900/30': filters.status !== status && status === 'confirmed'
                                    }" class="p-2 rounded-xl transition-all duration-200">
                                        <!-- Dynamic Icons for each status -->
                                        <Clock v-if="status === 'pending'" class="w-4 h-4" />
                                        <PlayCircle v-else-if="status === 'processing'" class="w-4 h-4" />
                                        <XOctagon v-else-if="status === 'cancelled'" class="w-4 h-4" />
                                        <Truck v-else-if="status === 'shipped'" class="w-4 h-4" />
                                        <CheckCircle v-else-if="status === 'delivered'" class="w-4 h-4" />
                                        <RotateCcw v-else-if="status === 'returned'" class="w-4 h-4" />
                                        <Pause v-else-if="status === 'on_hold'" class="w-4 h-4" />
                                        <CheckSquare v-else-if="status === 'confirmed'" class="w-4 h-4" />
                                        <Package v-else class="w-4 h-4" />
                                    </div>
                                    <div :class="filters.status === status ? 'text-white/90' : 'text-gray-500 dark:text-gray-400'"
                                         class="text-sm font-medium capitalize">{{ status.replace('_', ' ') }}</div>
                                </div>
                            </div>

                            <!-- Count with animation -->
                            <div :class="filters.status === status ? 'text-white' : 'text-gray-900 dark:text-white'"
                                 class="text-2xl font-bold transition-all duration-300 group-hover:scale-110">
                                {{ props.statusCounts.find(s => s.status === status)?.count || 0 }}
                            </div>

                            <!-- Revenue with icon -->
                            <div class="flex items-center space-x-1" :class="{
                                'text-white/90': filters.status === status,
                                'text-amber-600': filters.status !== status && status === 'pending',
                                'text-blue-600': filters.status !== status && status === 'processing',
                                'text-red-600': filters.status !== status && status === 'cancelled',
                                'text-purple-600': filters.status !== status && status === 'shipped',
                                'text-green-600': filters.status !== status && status === 'delivered',
                                'text-pink-600': filters.status !== status && status === 'returned',
                                'text-gray-600': filters.status !== status && status === 'on_hold',
                                'text-indigo-600': filters.status !== status && status === 'confirmed'
                            }">
                                <DollarSign class="w-3 h-3" />
                                <span class="text-sm font-semibold">
                                    ৳{{ Number(props.statusCounts.find(s => s.status === status)?.sales || 0).toLocaleString() }}
                                </span>
                            </div>
                        </div>

                        <!-- Animated shine effect for active cards -->
                        <div v-if="filters.status === status"
                             class="absolute inset-0 -top-10 -left-10 bg-gradient-to-r from-transparent via-white/20 to-transparent w-6 h-full rotate-12 transform translate-x-full group-hover:translate-x-[-200%] transition-transform duration-1000 ease-in-out"></div>

                        <!-- Pulse effect for cards with orders -->
                        <div v-if="(props.statusCounts.find(s => s.status === status)?.count || 0) > 0"
                             class="absolute top-2 right-2">
                            <div class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></div>
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
            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-xl border border-blue-200 dark:border-blue-800 p-4">
                <div class="flex items-start gap-3">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-r from-blue-500 to-indigo-600 flex items-center justify-center flex-shrink-0">
                        <span class="text-white text-sm">💡</span>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-2">Order Management Tips</h3>
                        <div class="grid md:grid-cols-2 gap-3 text-sm">
                            <p class="flex items-center gap-2 text-gray-700 dark:text-gray-300">
                                <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                                <span>Confirm orders within 2-4 hours</span>
                            </p>
                            <p class="flex items-center gap-2 text-gray-700 dark:text-gray-300">
                                <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                                <span>Group orders by area for efficiency</span>
                            </p>
                        </div>
                    </div>
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
                            <div class="flex items-center gap-2 px-3 py-1.5 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800 cursor-help">
                                <span class="text-xs font-medium text-blue-700 dark:text-blue-400">Tips</span>
                                <span class="w-4 h-4 rounded-full bg-blue-100 dark:bg-blue-900/50 flex items-center justify-center">
                                    <span class="text-xs text-blue-600 dark:text-blue-400">?</span>
                                </span>
                            </div>
                            <div class="absolute left-0 top-12 w-80 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-xl p-4 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50">
                                <div class="space-y-3">
                                    <h4 class="font-semibold text-gray-900 dark:text-white text-sm">Quick Workflow</h4>
                                    <div class="space-y-2 text-xs">
                                        <div class="flex items-center gap-2">
                                            <span class="w-4 h-4 rounded bg-amber-500 text-white text-xs flex items-center justify-center">1</span>
                                            <span class="text-gray-700 dark:text-gray-300">Review pending orders</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="w-4 h-4 rounded bg-blue-500 text-white text-xs flex items-center justify-center">2</span>
                                            <span class="text-gray-700 dark:text-gray-300">Confirm & mark as processing</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="w-4 h-4 rounded bg-purple-500 text-white text-xs flex items-center justify-center">3</span>
                                            <span class="text-gray-700 dark:text-gray-300">Send to courier</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="w-4 h-4 rounded bg-green-500 text-white text-xs flex items-center justify-center">4</span>
                                            <span class="text-gray-700 dark:text-gray-300">Update to delivered</span>
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
                                <!-- Priority Indicator -->
                                <th class="w-6 py-4"></th>

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
                                    class="w-56 px-6 py-4 text-left cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                                    <div class="flex items-center gap-2 text-sm font-semibold text-gray-900 dark:text-gray-100">
                                        <User class="w-4 h-4 text-gray-400" />
                                        <span>Customer</span>
                                        <ArrowUpDown class="w-4 h-4 text-gray-400" />
                                    </div>
                                </th>

                                <!-- Products -->
                                <th class="w-64 px-6 py-4 text-left">
                                    <div class="flex items-center gap-2 text-sm font-semibold text-gray-900 dark:text-gray-100">
                                        <Package class="w-4 h-4 text-gray-400" />
                                        <span>Products</span>
                                    </div>
                                </th>

                                <!-- Total -->
                                <th @click="toggleSort('total')"
                                    class="w-28 px-6 py-4 text-right cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                                    <div class="flex items-center justify-end gap-2 text-sm font-semibold text-gray-900 dark:text-gray-100">
                                        <span>Total</span>
                                        <ArrowUpDown class="w-4 h-4 text-gray-400" />
                                    </div>
                                </th>

                                <!-- Status -->
                                <th @click="toggleSort('status')"
                                    class="w-36 px-6 py-4 text-left cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                                    <div class="flex items-center gap-2 text-sm font-semibold text-gray-900 dark:text-gray-100">
                                        <span>Status</span>
                                        <ArrowUpDown class="w-4 h-4 text-gray-400" />
                                    </div>
                                </th>

                                <!-- Date -->
                                <th @click="toggleSort('created_at')"
                                    class="w-36 px-6 py-4 text-left cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                                    <div class="flex items-center gap-2 text-sm font-semibold text-gray-900 dark:text-gray-100">
                                        <span>Date</span>
                                        <ArrowUpDown class="w-4 h-4 text-gray-400" />
                                    </div>
                                </th>

                                <!-- Actions -->
                                <th class="w-24 px-6 py-4 text-right">
                                    <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">Actions</span>
                                </th>
                            </tr>
                        </thead>

                        <tbody v-if="visibleOrders.length === 0" class="divide-y divide-gray-100 dark:divide-gray-700">
                            <tr>
                                <td colspan="8" class="px-6 py-16">
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
                            <template
                                v-for="(order, index) in prioritizedOrders"
                                :key="order.id"
                            >
                                <tr
                                    @contextmenu="showContextMenuAt($event, order)"
                                    class="group hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-all duration-200 cursor-pointer relative"
                                    :class="{
                                        'bg-blue-50 dark:bg-blue-900/20': selectedOrders.includes(order.id),
                                        'bg-red-50 dark:bg-red-900/10 hover:bg-red-100': order.priority === 'critical',
                                        'bg-orange-50 dark:bg-orange-900/10 hover:bg-orange-100': order.priority === 'high',
                                        'bg-yellow-50 dark:bg-yellow-900/10 hover:bg-yellow-100': order.priority === 'medium',
                                        'animate-pulse': processingOrders.has(order.id),
                                        'ring-2 ring-green-400 ring-opacity-50': animatingOrders.has(order.id)
                                    }"
                                >
                                <!-- Priority Indicator -->
                                <td class="px-3 py-4 text-center">
                                    <div class="flex items-center justify-center">
                                        <div v-if="order.priority === 'critical'"
                                             class="w-2 h-8 bg-red-500 rounded-full animate-pulse"
                                             title="Critical Priority"></div>
                                        <div v-else-if="order.priority === 'high'"
                                             class="w-2 h-8 bg-orange-500 rounded-full"
                                             title="High Priority"></div>
                                        <div v-else-if="order.priority === 'medium'"
                                             class="w-2 h-8 bg-yellow-500 rounded-full"
                                             title="Medium Priority"></div>
                                        <div v-else
                                             class="w-2 h-8 bg-gray-300 rounded-full"
                                             title="Normal Priority"></div>

                                        <!-- New Order Badge -->
                                        <div v-if="order.isNew"
                                             class="absolute -top-1 -right-1 w-3 h-3 bg-green-500 rounded-full border-2 border-white animate-bounce"
                                             title="New order today"></div>
                                    </div>
                                </td>

                                <!-- Checkbox with enhanced styling -->
                                <td class="px-6 py-4">
                                    <input
                                        type="checkbox"
                                        :value="order.id"
                                        :checked="selectedOrders.includes(order.id)"
                                        @change="handleOrderSelectionChange(order.id)"
                                        class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 focus:ring-2 transition-all duration-200 hover:scale-110"
                                    />
                                </td>

                                <!-- Enhanced Order ID Cell with micro-interactions -->
                                <td class="px-6 py-4">
                                    <div class="space-y-1 group-hover:transform group-hover:scale-105 transition-transform duration-200">
                                        <div class="flex items-center gap-2">
                                            <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ order.order_number }}</span>
                                            <span v-if="order.admin_notes" class="text-xs animate-bounce" title="Has admin notes">📝</span>
                                            <div v-if="processingOrders.has(order.id)" class="w-3 h-3">
                                                <Loader2 class="w-3 h-3 animate-spin text-blue-500" />
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-1.5">
                                            <span v-if="order.isNew" class="px-1.5 py-0.5 bg-green-100 text-green-700 text-xs font-medium rounded animate-pulse">New Today</span>
                                            <span v-else-if="isToday(order.date)" class="px-1.5 py-0.5 bg-blue-100 text-blue-700 text-xs font-medium rounded">Today</span>
                                            <span class="text-xs text-gray-500">{{ formatOrderDate(order.date) }}</span>
                                        </div>

                                        <!-- Priority Score for debugging -->
                                        <div v-if="order.priorityScore > 0" class="text-xs text-gray-400">
                                            Score: {{ order.priorityScore }} | {{ order.priority }}
                                        </div>
                                    </div>
                                </td>

                                <!-- Enhanced Customer Cell with contextual actions -->
                                <td class="px-6 py-4">
                                    <div class="space-y-1.5">
                                        <!-- Customer Name & Contact with hover effects -->
                                        <div class="flex items-center gap-2 group-hover:transform group-hover:translate-x-1 transition-transform duration-200">
                                            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-blue-100 to-indigo-100 dark:from-blue-900 dark:to-indigo-900 flex items-center justify-center text-sm transition-colors group-hover:from-blue-200 group-hover:to-indigo-200">
                                                👤
                                            </div>
                                            <div class="min-w-0">
                                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">{{ truncateText(order.customer.name, 3) }}</p>
                                                <p class="text-xs text-gray-500 truncate cursor-pointer hover:text-blue-600 transition-colors">{{ order.customer?.phone }}</p>
                                            </div>
                                        </div>

                                        <!-- Enhanced Address with smart courier suggestion -->
                                        <div class="flex items-center gap-2">
                                            <p class="text-xs text-gray-500 line-clamp-1 flex-1">📍 {{ order.customer?.address }}</p>
                                            <div class="flex items-center gap-1 px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded-full">
                                                <Truck class="w-3 h-3 text-blue-500" />
                                                <span class="text-xs text-blue-600 dark:text-blue-400 font-medium">{{ getSmartCourierSuggestion(order) }}</span>
                                            </div>
                                        </div>

                                        <!-- Customer Note Badge with animation -->
                                        <div v-if="order.customer?.note && order.customer.note !== 'N/A'"
                                             class="px-2 py-1 bg-amber-50 dark:bg-amber-900/30 rounded text-xs text-amber-700 dark:text-amber-300 line-clamp-1 animate-pulse">
                                            💬 {{ order.customer?.note }}
                                        </div>

                                        <!-- Enhanced Admin Notes Button -->
                                        <button @click="openAdminNotesModal(order)"
                                                class="text-xs text-blue-600 hover:text-blue-800 font-medium transition-colors hover:scale-105 transform">
                                            {{ order.admin_notes ? '📝 Edit Notes' : '+ Add Notes' }}
                                        </button>
                                    </div>
                                </td>

                                <!-- Enhanced Products Cell -->
                                <td class="px-6 py-4">
                                    <div v-if="order.items.length === 0" class="text-xs text-gray-400">No items</div>
                                    <div v-else class="space-y-1.5">
                                        <div v-for="(item, idx) in order.items.slice(0, 2)" :key="item.id"
                                             class="flex items-center gap-2 p-1.5 bg-gray-50 dark:bg-gray-700/50 rounded hover:bg-gray-100 dark:hover:bg-gray-600/50 transition-colors">
                                            <img :src="item.product.image || ''" alt=""
                                                 class="w-8 h-8 rounded object-cover bg-gray-200 hover:scale-110 transition-transform duration-200" />
                                            <div class="min-w-0 flex-1">
                                                <p class="text-xs font-medium text-gray-900 dark:text-gray-100 truncate">
                                                    {{ truncateText(item.product.name, 4) }}
                                                </p>
                                                <div class="flex items-center gap-1.5 text-xs text-gray-500">
                                                    <span>৳{{ item.price }}</span>
                                                    <span>×{{ item.quantity }}</span>
                                                    <span v-if="item.is_pre_order" class="px-1 py-0.5 bg-amber-100 text-amber-700 rounded text-xs animate-pulse">Pre</span>
                                                </div>
                                                <div v-if="item.variation" class="flex flex-wrap gap-1 mt-0.5">
                                                    <span v-for="(value, key) in item.variation.attributes" :key="key"
                                                          class="px-1 py-0.5 bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 rounded text-xs hover:bg-purple-200 transition-colors">
                                                        {{ key }}: {{ value }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <button v-if="order.items.length > 2"
                                                class="text-xs text-blue-600 hover:text-blue-800 font-medium transition-colors hover:scale-105 transform">
                                            +{{ order.items.length - 2 }} more items
                                        </button>
                                    </div>
                                </td>

                                <!-- Total -->
                                <td class="px-6 py-4">
                                    <div class="text-right">
                                        <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                            ৳{{ Number(order.total || 0).toLocaleString('en-US', {minimumFractionDigits: 0, maximumFractionDigits: 2}) }}
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            {{ order.items.length }} {{ order.items.length === 1 ? 'item' : 'items' }}
                                        </div>
                                    </div>
                                </td>

                                <!-- Status -->
                                <td class="px-6 py-4">
                                    <div class="space-y-1.5">
                                        <StatusDropdown
                                            :model-value="order.status"
                                            :order-id="order.id"
                                            :is-loading="updatingOrders[order.id]"
                                            @status-change="(orderId, newStatus) => { updateOrderStatus(orderId, newStatus); triggerSuccessAnimation(orderId); }"
                                        />
                                        <div v-if="order.timeInStatus" class="flex items-center gap-1 text-xs text-gray-500">
                                            <Timer class="w-3 h-3" />
                                            <span>{{ order.timeInStatus }}</span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Date -->
                                <td class="px-6 py-4">
                                    <div class="space-y-1">
                                        <div class="text-sm text-gray-900 dark:text-gray-100">
                                            {{ formatOrderDate(order.created_at || order.date) }}
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs text-gray-500">
                                                {{ order.hoursOld && !isNaN(order.hoursOld) ? (order.hoursOld < 24 ? `${Math.round(order.hoursOld)}h ago` : `${Math.round(order.hoursOld / 24)}d ago`) : 'Just now' }}
                                            </span>
                                            <span v-if="order.hoursOld > 72" class="px-1.5 py-0.5 bg-red-100 text-red-700 text-xs rounded font-medium">
                                                Urgent
                                            </span>
                                            <span v-else-if="order.hoursOld > 48" class="px-1.5 py-0.5 bg-amber-100 text-amber-700 text-xs rounded">
                                                Old
                                            </span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Enhanced Actions Cell with Smart Suggestions -->
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <!-- Quick Actions -->
                                        <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <!-- NEW: Timeline Expand Button -->
                                            <button
                                                @click="toggleOrderExpansion(order.id)"
                                                class="w-8 h-8 rounded-lg flex items-center justify-center transition-all duration-200 hover:scale-110 transform"
                                                :class="expandedOrders.includes(order.id)
                                                    ? 'bg-blue-100 text-blue-600 hover:bg-blue-200'
                                                    : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                                                :title="expandedOrders.includes(order.id) ? 'Hide Timeline' : 'Show Timeline'"
                                            >
                                                <Clock class="w-4 h-4" />
                                            </button>

                                            <!-- Smart Courier Suggestion Button -->
                                            <button
                                                @click="openCourierModal(order.id)"
                                                class="w-8 h-8 rounded-lg flex items-center justify-center transition-all duration-200 hover:scale-110 transform"
                                                :class="getSmartCourierSuggestion(order) === 'pathao'
                                                    ? 'bg-purple-100 text-purple-600 hover:bg-purple-200'
                                                    : 'bg-green-100 text-green-600 hover:bg-green-200'"
                                                :title="`Recommended: ${getSmartCourierSuggestion(order)}`"
                                            >
                                                <Truck class="w-4 h-4" />
                                            </button>

                                            <!-- Fraud Check -->
                                            <button
                                                @click="checkFraud(order.customer.phone)"
                                                class="w-8 h-8 bg-blue-100 text-blue-600 rounded-lg flex items-center justify-center hover:bg-blue-200 transition-all duration-200 hover:scale-110 transform"
                                                title="Check Customer"
                                            >
                                                <Eye class="w-4 h-4" />
                                            </button>

                                            <!-- Edit Order -->
                                            <Link
                                                :href="route('admin.orders.edit', order.id)"
                                                class="w-8 h-8 bg-amber-100 text-amber-600 rounded-lg flex items-center justify-center hover:bg-amber-200 transition-all duration-200 hover:scale-110 transform"
                                                title="Edit Order"
                                            >
                                                <SquarePen class="w-4 h-4" />
                                            </Link>
                                        </div>

                                        <!-- Main Actions Dropdown -->
                                        <div class="relative">
                                            <button
                                                @click.stop="toggleActionMenu(order.id)"
                                                class="w-8 h-8 rounded-lg flex items-center justify-center transition-all duration-200 hover:scale-110 transform"
                                                :class="activeActionMenu === order.id
                                                    ? 'bg-blue-100 text-blue-600'
                                                    : 'bg-gray-100 hover:bg-gray-200 text-gray-600'"
                                            >
                                                <component :is="activeActionMenu === order.id ? X : MoreVertical" class="w-4 h-4" />
                                            </button>

                                            <!-- Enhanced Action Dropdown -->
                                            <div v-if="activeActionMenu === order.id"
                                                 class="absolute right-0 top-full mt-2 w-56 bg-white dark:bg-gray-800 rounded-xl shadow-xl border border-gray-200 dark:border-gray-700 z-50 py-2 transform animate-in slide-in-from-top-2 duration-200">

                                                <!-- Status Actions -->
                                                <div class="px-3 py-2 border-b border-gray-100 dark:border-gray-700">
                                                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-2">Quick Status Update</p>
                                                    <div class="grid grid-cols-2 gap-1">
                                                        <button
                                                            @click="updateOrderStatus(order.id, 'processing'); toggleActionMenu(order.id); triggerSuccessAnimation(order.id);"
                                                            class="px-2 py-1 text-xs bg-blue-100 text-blue-700 rounded hover:bg-blue-200 transition-colors">
                                                            ⚙️ Processing
                                                        </button>
                                                        <button
                                                            @click="updateOrderStatus(order.id, 'shipped'); toggleActionMenu(order.id); triggerSuccessAnimation(order.id);"
                                                            class="px-2 py-1 text-xs bg-purple-100 text-purple-700 rounded hover:bg-purple-200 transition-colors">
                                                            🚚 Shipped
                                                        </button>
                                                    </div>
                                                </div>

                                                <!-- Main Actions -->
                                                <div class="py-1">
                                                    <button @click="checkFraud(order?.customer?.phone); toggleActionMenu(order.id);"
                                                            class="w-full px-4 py-2 text-left text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center gap-3 transition-colors">
                                                        <User class="w-4 h-4 text-blue-500" />
                                                        <span>Check Customer</span>
                                                        <span class="ml-auto text-xs text-blue-500">Fraud Check</span>
                                                    </button>

                                                    <button @click="openCourierModal(order, order.id); toggleActionMenu(order.id);"
                                                            class="w-full px-4 py-2 text-left text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center gap-3 transition-colors">
                                                        <Truck class="w-4 h-4 text-green-500" />
                                                        <span>Send to Courier</span>
                                                        <span class="ml-auto text-xs text-green-600">{{ getSmartCourierSuggestion(order) }}</span>
                                                    </button>

                                                    <button @click="openAdminNotesModal(order); toggleActionMenu(order.id);"
                                                            class="w-full px-4 py-2 text-left text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center gap-3 transition-colors">
                                                        <SquarePen class="w-4 h-4 text-purple-500" />
                                                        <span>{{ order.admin_notes ? 'Edit Notes' : 'Add Notes' }}</span>
                                                    </button>

                                                    <Link :href="route('admin.orders.edit', order.id)"
                                                          @click="toggleActionMenu(order.id)"
                                                          class="w-full px-4 py-2 text-left text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center gap-3 transition-colors">
                                                        <SquarePen class="w-4 h-4 text-amber-500" />
                                                        <span>Edit Order</span>
                                                    </Link>

                                                    <Link :href="route('admin.orders.show', order.id)"
                                                          @click="toggleActionMenu(order.id)"
                                                          class="w-full px-4 py-2 text-left text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center gap-3 transition-colors">
                                                        <ExternalLink class="w-4 h-4 text-cyan-500" />
                                                        <span>View Details</span>
                                                    </Link>
                                                </div>

                                                <!-- Danger Zone -->
                                                <div class="border-t border-gray-100 dark:border-gray-700 pt-1">
                                                    <button @click="openDeleteModal(order.id); toggleActionMenu(order.id);"
                                                            class="w-full px-4 py-2 text-left text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 flex items-center gap-3 transition-colors">
                                                        <Trash2Icon class="w-4 h-4" />
                                                        <span>Delete Order</span>
                                                    </button>
                                                </div>
                                            </div>

                                            <!-- Click Outside to Close -->
                                            <div v-if="activeActionMenu === order.id"
                                                 class="fixed inset-0 z-40"
                                                 @click="activeActionMenu = null"></div>
                                        </div>
                                    </div>

                                    <!-- Processing Indicator -->
                                    <div v-if="processingOrders.has(order.id)"
                                         class="absolute inset-0 bg-white/80 dark:bg-gray-800/80 flex items-center justify-center rounded-lg">
                                        <div class="flex items-center gap-2 text-sm text-blue-600">
                                            <Loader2 class="w-4 h-4 animate-spin" />
                                            <span>Processing...</span>
                                        </div>
                                    </div>
                                </td>
                            </tr>

                            <!-- NEW: Expandable Order Timeline Row -->
                            <tr v-if="expandedOrders.includes(order.id)"
                                class="bg-gray-50 dark:bg-gray-800/50">
                                <td colspan="9" class="px-6 py-4">
                                    <div class="bg-white dark:bg-gray-700 rounded-lg p-4 border-2 border-blue-200 dark:border-blue-800">
                                        <!-- Timeline Header -->
                                        <div class="flex items-center justify-between mb-4">
                                            <h4 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                                                <Clock class="w-4 h-4 text-blue-500" />
                                                Order Timeline
                                            </h4>
                                            <button @click="toggleOrderExpansion(order.id)"
                                                    class="text-gray-400 hover:text-gray-600">
                                                <ChevronUp class="w-5 h-5" />
                                            </button>
                                        </div>

                                        <!-- Timeline Loading State -->
                                        <div v-if="!orderTimelines[order.id]" class="flex items-center gap-3 text-sm text-gray-500">
                                            <Loader2 class="w-4 h-4 animate-spin text-blue-500" />
                                            <span>Loading timeline...</span>
                                        </div>

                                        <!-- Timeline Content -->
                                        <div v-else class="space-y-3 max-h-64 overflow-y-auto custom-scrollbar">
                                            <div v-for="(event, idx) in orderTimelines[order.id]"
                                                 :key="idx"
                                                 class="flex gap-3 relative">
                                                <!-- Timeline Line -->
                                                <div v-if="idx < orderTimelines[order.id].length - 1"
                                                     class="absolute left-2 top-8 bottom-0 w-0.5 bg-gray-200 dark:bg-gray-600"></div>

                                                <!-- Event Icon -->
                                                <div class="w-5 h-5 rounded-full flex items-center justify-center flex-shrink-0 z-10"
                                                     :class="{
                                                         'bg-green-500': event.status === 'delivered',
                                                         'bg-blue-500': event.status === 'processing' || event.status === 'confirmed',
                                                         'bg-purple-500': event.status === 'shipped',
                                                         'bg-yellow-500': event.status === 'pending' || event.status === 'on_hold',
                                                         'bg-red-500': event.status === 'cancelled',
                                                         'bg-gray-400': !event.status
                                                     }">
                                                    <span class="text-white text-xs">✓</span>
                                                </div>

                                                <!-- Event Details -->
                                                <div class="flex-1 pb-4">
                                                    <div class="flex items-start justify-between gap-2">
                                                        <div>
                                                            <p class="text-sm font-medium text-gray-900 dark:text-white">
                                                                {{ event.title }}
                                                            </p>
                                                            <p v-if="event.description"
                                                               class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                                {{ event.description }}
                                                            </p>
                                                            <p class="text-xs text-gray-400 mt-1">
                                                                {{ event.user }} • {{ event.timestamp }}
                                                            </p>
                                                        </div>
                                                        <span class="px-2 py-1 text-xs font-medium rounded-full whitespace-nowrap"
                                                              :class="{
                                                                  'bg-green-100 text-green-700': event.status === 'delivered',
                                                                  'bg-blue-100 text-blue-700': event.status === 'processing' || event.status === 'confirmed',
                                                                  'bg-purple-100 text-purple-700': event.status === 'shipped',
                                                                  'bg-yellow-100 text-yellow-700': event.status === 'pending' || event.status === 'on_hold',
                                                                  'bg-red-100 text-red-700': event.status === 'cancelled'
                                                              }">
                                                            {{ event.status || 'Update' }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- No Timeline Data -->
                                        <div v-if="orderTimelines[order.id] && orderTimelines[order.id].length === 0"
                                             class="text-center py-6 text-sm text-gray-500">
                                            <Clock class="w-8 h-8 mx-auto mb-2 text-gray-400" />
                                            <p>No timeline data available</p>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            </template>
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

        <!-- NEW: Bulk Status Update Modal -->
        <div v-if="bulkStatusModal"
             class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50"
             @click.self="closeBulkStatusModal">
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-2xl w-full max-w-md">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Bulk Status Update</h3>
                    <button @click="closeBulkStatusModal" class="text-gray-400 hover:text-gray-600">
                        <X class="w-5 h-5" />
                    </button>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                    Update status for {{ selectedOrders.length }} selected orders
                </p>
                <select v-model="selectedBulkStatus"
                        class="w-full px-4 py-2 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 mb-4">
                    <option value="">Select Status</option>
                    <option value="pending">Pending</option>
                    <option value="processing">Processing</option>
                    <option value="confirmed">Confirmed</option>
                    <option value="shipped">Shipped</option>
                    <option value="delivered">Delivered</option>
                    <option value="cancelled">Cancelled</option>
                    <option value="on_hold">On Hold</option>
                </select>
                <div class="flex gap-3">
                    <button @click="closeBulkStatusModal"
                            class="flex-1 px-4 py-2 text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
                        Cancel
                    </button>
                    <button @click="applyBulkStatus"
                            class="flex-1 px-4 py-2 text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors">
                        Update Orders
                    </button>
                </div>
            </div>
        </div>

        <!-- NEW: Keyboard Shortcuts Panel -->
        <div v-if="showKeyboardShortcuts"
             class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50"
             @click.self="showKeyboardShortcuts = false">
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-2xl w-full max-w-2xl max-h-[80vh] overflow-y-auto">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <MousePointer class="w-5 h-5" />
                        Keyboard Shortcuts
                    </h3>
                    <button @click="showKeyboardShortcuts = false" class="text-gray-400 hover:text-gray-600">
                        <X class="w-6 h-6" />
                    </button>
                </div>

                <div class="space-y-4">
                    <div class="border-b border-gray-200 dark:border-gray-700 pb-3">
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-3">Navigation</h4>
                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600 dark:text-gray-400">Navigate up/down</span>
                                <div class="flex gap-2">
                                    <kbd class="px-2 py-1 bg-gray-200 dark:bg-gray-700 rounded text-xs">↑</kbd>
                                    <kbd class="px-2 py-1 bg-gray-200 dark:bg-gray-700 rounded text-xs">↓</kbd>
                                </div>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600 dark:text-gray-400">Open order details</span>
                                <kbd class="px-2 py-1 bg-gray-200 dark:bg-gray-700 rounded text-xs">Enter</kbd>
                            </div>
                        </div>
                    </div>

                    <div class="border-b border-gray-200 dark:border-gray-700 pb-3">
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-3">Selection</h4>
                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600 dark:text-gray-400">Select all visible</span>
                                <kbd class="px-2 py-1 bg-gray-200 dark:bg-gray-700 rounded text-xs">Ctrl+A</kbd>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600 dark:text-gray-400">Clear selection</span>
                                <kbd class="px-2 py-1 bg-gray-200 dark:bg-gray-700 rounded text-xs">Esc</kbd>
                            </div>
                        </div>
                    </div>

                    <div class="border-b border-gray-200 dark:border-gray-700 pb-3">
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-3">Actions</h4>
                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600 dark:text-gray-400">Focus search</span>
                                <kbd class="px-2 py-1 bg-gray-200 dark:bg-gray-700 rounded text-xs">Ctrl+F</kbd>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600 dark:text-gray-400">Export orders</span>
                                <kbd class="px-2 py-1 bg-gray-200 dark:bg-gray-700 rounded text-xs">Ctrl+E</kbd>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600 dark:text-gray-400">Refresh orders</span>
                                <kbd class="px-2 py-1 bg-gray-200 dark:bg-gray-700 rounded text-xs">F5</kbd>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600 dark:text-gray-400">Show this help</span>
                                <kbd class="px-2 py-1 bg-gray-200 dark:bg-gray-700 rounded text-xs">Ctrl+?</kbd>
                            </div>
                        </div>
                    </div>

                    <div class="pb-3">
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-3">Advanced</h4>
                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600 dark:text-gray-400">Highlight urgent orders</span>
                                <kbd class="px-2 py-1 bg-gray-200 dark:bg-gray-700 rounded text-xs">Ctrl+Shift+U</kbd>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                    <p class="text-sm text-blue-700 dark:text-blue-400">
                        <strong>Pro Tip:</strong> Use keyboard shortcuts to process orders 3x faster!
                    </p>
                </div>
            </div>
        </div>

        <!-- NEW: Loading Skeleton Overlay -->
        <div v-if="showSkeletonLoader"
             class="fixed inset-0 bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm flex items-center justify-center z-50">
            <div class="flex flex-col items-center gap-4">
                <Loader2 class="w-12 h-12 animate-spin text-blue-500" />
                <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Loading orders...</p>
            </div>
        </div>

        <!-- NEW: Export Progress Indicator -->
        <div v-if="exportingOrders"
             class="fixed bottom-6 right-6 bg-white dark:bg-gray-800 rounded-lg shadow-xl p-4 z-50 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-3">
                <Loader2 class="w-5 h-5 animate-spin text-blue-500" />
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Exporting orders...</span>
            </div>
        </div>

        <!-- NEW: Notification Toast Container -->
        <div class="fixed top-6 right-6 z-50 space-y-2">
            <div v-for="notification in notificationQueue"
                 :key="notification.id"
                 class="bg-white dark:bg-gray-800 rounded-lg shadow-xl p-4 border-l-4 transition-all duration-300 max-w-sm"
                 :class="{
                     'border-green-500': notification.type === 'success',
                     'border-red-500': notification.type === 'error',
                     'border-blue-500': notification.type === 'info'
                 }">
                <div class="flex items-start gap-3">
                    <span v-if="notification.type === 'success'" class="text-green-500">✅</span>
                    <span v-else-if="notification.type === 'error'" class="text-red-500">❌</span>
                    <span v-else class="text-blue-500">ℹ️</span>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ notification.message }}</p>
                        <p class="text-xs text-gray-500 mt-1">{{ new Date(notification.timestamp).toLocaleTimeString() }}</p>
                    </div>
                </div>
            </div>
        </div>
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

/* Custom Scrollbar for Timeline */
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}

.custom-scrollbar::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 10px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #555;
}

/* Dark mode scrollbar */
.dark .custom-scrollbar::-webkit-scrollbar-track {
    background: #374151;
}

.dark .custom-scrollbar::-webkit-scrollbar-thumb {
    background: #6b7280;
}

.dark .custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #9ca3af;
}
</style>
