<script setup>
import { defineProps, ref, computed, onMounted } from "vue";
import { Head } from "@inertiajs/vue3";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import VueApexCharts from "vue3-apexcharts";
import OrderMap from "@/Components/Order/OrderMap.vue";

const props = defineProps({
    data: {
        type: Object,
        required: true,
    },
});

// Sales Analytics period toggle
const salesPeriod = ref('week'); // 'week' or 'month'

// Loading state and last update tracking
const isLoading = ref(false);
const lastUpdated = ref(new Date());
const autoRefresh = ref(true);

// Dark mode toggle
const darkMode = ref(document.documentElement.classList.contains('dark'));
const toggleDarkMode = () => {
    darkMode.value = !darkMode.value;
    if (darkMode.value) {
        document.documentElement.classList.add('dark');
        localStorage.setItem('theme', 'dark');
    } else {
        document.documentElement.classList.remove('dark');
        localStorage.setItem('theme', 'light');
    }
};

// Keyboard shortcuts
const showShortcutsModal = ref(false);
const handleKeyboardShortcut = (e) => {
    // Ctrl/Cmd + K for shortcuts help
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault();
        showShortcutsModal.value = !showShortcutsModal.value;
    }
    // Ctrl/Cmd + 1-6 for quick navigation
    if (e.ctrlKey || e.metaKey) {
        switch(e.key) {
            case '1': e.preventDefault(); window.location.href = '/admin/orders'; break;
            case '2': e.preventDefault(); window.location.href = '/admin/inventory'; break;
            case '3': e.preventDefault(); window.location.href = '/admin/reports'; break;
            case '4': e.preventDefault(); window.location.href = '/admin/products'; break;
            case '5': e.preventDefault(); window.location.href = '/admin/customers'; break;
            case 'r': e.preventDefault(); updateTimestamp(); break;
        }
    }
};

// Update timestamp
const updateTimestamp = () => {
    lastUpdated.value = new Date();
};

// Format time ago
const timeAgo = computed(() => {
    const seconds = Math.floor((new Date() - lastUpdated.value) / 1000);
    if (seconds < 60) return 'just now';
    const minutes = Math.floor(seconds / 60);
    if (minutes < 60) return `${minutes}m ago`;
    const hours = Math.floor(minutes / 60);
    return `${hours}h ago`;
});

// Get chart data based on selected period
const currentChartData = computed(() => {
    return salesPeriod.value === 'week'
        ? props.data.dailyOrdersData
        : (props.data.monthlyOrdersData || props.data.dailyOrdersData);
});

// Sales Performance Metrics
const salesMetrics = computed(() => {
    const data = currentChartData.value;
    const totalSales = data.reduce((sum, item) => sum + item.orders, 0);
    const totalRevenue = data.reduce((sum, item) => sum + parseFloat(item.revenue), 0);
    const avgConversion = totalSales > 0 ? (totalRevenue / totalSales).toFixed(2) : 0;

    return {
        totalSales,
        revenue: totalRevenue,
        avgConversion: ((totalSales / data.length) * 100).toFixed(2)
    };
});

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('bn-BD', {
        style: 'currency',
        currency: 'BDT',
        minimumFractionDigits: 0
    }).format(amount);
};

// Group inventory items by product (avoid duplicates, combine variations)
const groupedInventoryItems = computed(() => {
    if (!props.data.inventoryItems || props.data.inventoryItems.length === 0) {
        return [];
    }

    const productMap = new Map();

    props.data.inventoryItems.forEach(item => {
        // Extract base product name (remove variation details)
        const baseProductName = item.name.split(' - ')[0].trim();

        if (!productMap.has(baseProductName)) {
            // First occurrence of this product
            productMap.set(baseProductName, {
                id: item.id,
                name: baseProductName,
                current: 0,
                max: 0,
                status: item.status,
                percentage: 0,
                variations: []
            });
        }

        const product = productMap.get(baseProductName);

        // Extract variation name (everything after first dash)
        const variationName = item.name.includes(' - ')
            ? item.name.split(' - ').slice(1).join(' - ')
            : 'Default';

        // Add variation details
        product.variations.push({
            name: variationName,
            quantity: item.current,
            status: item.status,
            percentage: item.percentage
        });

        // Update totals
        product.current += item.current;
        product.max += item.max;

        // Update status to worst case (critical > warning > good)
        if (item.status === 'critical') {
            product.status = 'critical';
        } else if (item.status === 'warning' && product.status !== 'critical') {
            product.status = 'warning';
        }
    });

    // Calculate percentage and sort variations
    const result = Array.from(productMap.values()).map(product => {
        product.percentage = product.max > 0
            ? Math.round((product.current / product.max) * 100)
            : 0;

        // Sort variations by quantity (descending)
        product.variations.sort((a, b) => b.quantity - a.quantity);

        return product;
    });

    // Sort by status priority (critical first) and then by name
    return result.sort((a, b) => {
        const statusPriority = { critical: 0, warning: 1, good: 2 };
        const statusDiff = statusPriority[a.status] - statusPriority[b.status];
        return statusDiff !== 0 ? statusDiff : a.name.localeCompare(b.name);
    });
});

// Daily orders chart options
const dailyOrdersOptions = computed(() => ({
    chart: {
        type: "area",
        height: 350,
        toolbar: {
            show: false,
        },
        zoom: {
            enabled: false,
        },
        events: {
            dataPointSelection: handleChartClick,
            markerClick: handleChartClick,
        },
        sparkline: {
            enabled: false,
        },
        animations: {
            enabled: true,
            easing: "easeinout",
            speed: 800,
            animateGradually: {
                enabled: true,
                delay: 150,
            },
            dynamicAnimation: {
                enabled: true,
                speed: 350,
            },
        },
    },
    dataLabels: {
        enabled: false,
    },
    stroke: {
        curve: "smooth",
        width: 2,
    },
    colors: ["#7C3AED", "#06B6D4"],
    fill: {
        type: "gradient",
        gradient: {
            shadeIntensity: 1,
            opacityFrom: 0.7,
            opacityTo: 0.3,
            stops: [0, 90, 100],
        },
    },
    grid: {
        borderColor: "#f1f1f1",
        strokeDashArray: 5,
        padding: {
            top: 0,
            right: 20,
            bottom: 0,
            left: 20,
        },
    },
    xaxis: {
        categories: currentChartData.value.map((item) => item.date),
        axisBorder: {
            show: false,
        },
        axisTicks: {
            show: false,
        },
        labels: {
            style: {
                colors: "#6B7280",
                fontSize: "12px",
                fontFamily: "Inter, sans-serif",
            },
        },
    },
    yaxis: {
        labels: {
            style: {
                colors: "#6B7280",
                fontSize: "12px",
                fontFamily: "Inter, sans-serif",
            },
            formatter: function (value) {
                return value.toFixed(0);
            },
        },
    },
    tooltip: {
        enabled: true,
        style: {
            fontSize: "12px",
            fontFamily: "Inter, sans-serif",
        },
        y: {
            formatter: function (value, { seriesIndex }) {
                return seriesIndex === 0
                    ? value + " orders"
                    : "৳" + value.toFixed(2);
            },
        },
    },
    legend: {
        position: "top",
        horizontalAlign: "right",
        fontSize: "12px",
        fontFamily: "Inter, sans-serif",
        markers: {
            radius: 12,
        },
        itemMargin: {
            horizontal: 10,
            vertical: 5,
        },
    },
}));

const dailyOrdersSeries = computed(() => [
    {
        name: "Orders",
        data: currentChartData.value.map((item) => item.orders),
    },
    {
        name: "Revenue",
        data: currentChartData.value.map((item) =>
            parseFloat(item.revenue)
        ),
    },
]);

// Define the statuses and color mapping to ensure consistent ordering and colorful cards
const statusOrder = [
    'pending',
    'processing',
    'cancelled',
    'shipped',
    'delivered',
    'returned',
    'incomplete',
    'on_hold',
    'confirmed',
];

const statusColor = {
    pending: 'from-yellow-400 to-yellow-300 text-gray-900',
    processing: 'from-indigo-500 to-indigo-400 text-white',
    cancelled: 'from-red-500 to-red-400 text-white',
    shipped: 'from-sky-500 to-sky-400 text-white',
    delivered: 'from-green-500 to-emerald-400 text-white',
    returned: 'from-pink-500 to-pink-400 text-white',
    incomplete: 'from-gray-400 to-gray-300 text-gray-900',
    on_hold: 'from-amber-500 to-amber-400 text-white',
    confirmed: 'from-purple-500 to-violet-400 text-white',
};

const normalizeKey = (str) => (str || '').toLowerCase().replace(/\s+/g, '_').replace(/[^\w_]/g, '');

const deliveryStatusList = computed(() => {
    const map = (props.data.deliverySalesData || []).reduce((acc, item) => {
        acc[normalizeKey(item.name)] = item;
        return acc;
    }, {});

    return statusOrder.map((key) => {
        const item = map[key] || { name: key.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase()), orders: 0, sales: 0 };
        return { ...item, key };
    });
});

// Product performance chart options
const productPerformanceOptions = computed(() => ({
    chart: {
        type: "bar",
        height: 300,
        toolbar: {
            show: false,
        },
        animations: {
            enabled: true,
            easing: "easeinout",
            speed: 800,
        },
    },
    plotOptions: {
        bar: {
            horizontal: true,
            barHeight: "70%",
            borderRadius: 4,
            distributed: false,
        },
    },
    colors: ["#EF4444", "#F59E0B", "#6366F1"],
    dataLabels: {
        enabled: true,
        formatter: function (val) {
            return val + " sales";
        },
        style: {
            fontSize: "12px",
            fontFamily: "Inter, sans-serif",
            colors: ["#fff"],
        },
        offsetX: 0,
        dropShadow: {
            enabled: false,
        },
    },
    xaxis: {
        categories: props.data.productPerformanceData.map((item) => item.name),
        axisBorder: {
            show: false,
        },
        axisTicks: {
            show: false,
        },
        labels: {
            style: {
                colors: "#6B7280",
                fontSize: "12px",
                fontFamily: "Inter, sans-serif",
            },
        },
    },
    yaxis: {
        labels: {
            style: {
                colors: "#6B7280",
                fontSize: "12px",
                fontFamily: "Inter, sans-serif",
            },
        },
    },
    grid: {
        borderColor: "#f1f1f1",
        strokeDashArray: 5,
        padding: {
            top: 0,
            right: 20,
            bottom: 0,
            left: 20,
        },
    },
    tooltip: {
        enabled: true,
        style: {
            fontSize: "12px",
            fontFamily: "Inter, sans-serif",
        },
    },
}));

const productPerformanceSeries = computed(() => [
    {
        name: "Sales",
        data: props.data.productPerformanceData.map((item) =>
            parseInt(item.sales)
        ),
    },
]);

// Active tab for recent orders
const activeTab = ref("all");

// Chart click handler for interactive data viz
const handleChartClick = (event, chartContext, config) => {
    const dataPointIndex = config.dataPointIndex;
    if (dataPointIndex >= 0) {
        const data = currentChartData.value[dataPointIndex];
        // Navigate to orders for that date
        window.location.href = `/admin/orders?date=${data.date}`;
    }
};

// Product variation display helper
const formatProductVariations = (product) => {
    if (!product.variations || product.variations.length === 0) {
        return { name: product.name, totalQty: product.quantity || 0, variations: [] };
    }

    const totalQty = product.variations.reduce((sum, v) => sum + (v.quantity || 0), 0);
    const displayVariations = product.variations.slice(0, 3); // Show first 3
    const hasMore = product.variations.length > 3;

    return {
        name: product.name,
        totalQty,
        variations: displayVariations,
        hasMore,
        moreCount: product.variations.length - 3
    };
};

// Setup keyboard shortcuts on mount
onMounted(() => {
    document.addEventListener('keydown', handleKeyboardShortcut);

    // Check saved theme preference
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme === 'dark') {
        darkMode.value = true;
        document.documentElement.classList.add('dark');
    }
});
</script>

<template>
    <Head title="Dashboard" />
    <AdminLayout>
        <div class="mx-auto">
            <div class="flex flex-col space-y-6 md:space-y-8">
                <!-- Improved Header with Live Status -->
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                    <!-- Title Section - Mobile Optimized -->
                    <div class="flex items-center gap-3">
                        <div class="bg-gradient-to-br from-blue-600 to-purple-600 p-3 rounded-xl shadow-lg">
                            <svg class="w-6 h-6 md:w-8 md:h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-2xl md:text-3xl lg:text-4xl font-extrabold text-gray-900 dark:text-white">
                                Dashboard
                            </h1>
                            <p class="text-xs md:text-sm text-gray-600 dark:text-gray-400 mt-1">Real-time store performance</p>
                        </div>
                    </div>

                    <!-- Live Status Indicator -->
                    <div class="flex items-center gap-3">
                        <!-- Dark Mode Toggle -->
                        <button
                            @click="toggleDarkMode()"
                            class="p-2 md:p-2.5 bg-gray-50 dark:bg-gray-700 text-gray-600 dark:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors"
                            title="Toggle dark mode"
                        >
                            <svg v-if="!darkMode" class="w-4 h-4 md:w-5 md:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                            </svg>
                            <svg v-else class="w-4 h-4 md:w-5 md:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                        </button>

                        <!-- Keyboard Shortcuts Button -->
                        <button
                            @click="showShortcutsModal = true"
                            class="hidden md:flex items-center gap-2 px-3 py-2 bg-gray-50 dark:bg-gray-700 text-gray-600 dark:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors text-xs font-semibold"
                            title="Keyboard shortcuts (Ctrl+K)"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                            <span>Ctrl+K</span>
                        </button>

                        <div class="flex items-center gap-2 px-4 py-2 bg-green-50 dark:bg-green-900/20 rounded-full border border-green-200 dark:border-green-800">
                            <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
                            <span class="text-xs md:text-sm font-semibold text-green-700 dark:text-green-400">Live</span>
                            <span class="text-xs text-gray-500 dark:text-gray-400">• {{ timeAgo }}</span>
                        </div>
                        <button
                            @click="updateTimestamp()"
                            class="p-2 md:p-2.5 bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/30 transition-colors"
                            title="Refresh data"
                        >
                            <svg class="w-4 h-4 md:w-5 md:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Quick Actions Panel -->               <div class="bg-gradient-to-r from-blue-50 via-purple-50 to-pink-50 dark:from-gray-800 dark:via-gray-800 dark:to-gray-800 rounded-2xl p-4 md:p-6 shadow-lg border border-blue-100 dark:border-gray-700">
                    <div class="flex items-center gap-2 mb-4">
                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        <h3 class="text-sm md:text-base font-bold text-gray-900 dark:text-white">Quick Actions</h3>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                        <!-- New Order -->
                        <a href="/admin/orders" class="group flex flex-col items-center gap-2 p-3 md:p-4 bg-white dark:bg-gray-700 rounded-xl shadow-sm hover:shadow-md transition-all transform hover:-translate-y-1 border border-gray-100 dark:border-gray-600">
                            <div class="w-10 h-10 md:w-12 md:h-12 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
                                <svg class="w-5 h-5 md:w-6 md:h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                            </div>
                            <span class="text-xs md:text-sm font-semibold text-gray-700 dark:text-gray-300 text-center">New Order</span>
                        </a>

                        <!-- Check Inventory -->
                        <a href="/admin/inventory" class="group flex flex-col items-center gap-2 p-3 md:p-4 bg-white dark:bg-gray-700 rounded-xl shadow-sm hover:shadow-md transition-all transform hover:-translate-y-1 border border-gray-100 dark:border-gray-600">
                            <div class="w-10 h-10 md:w-12 md:h-12 bg-gradient-to-br from-amber-500 to-orange-600 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
                                <svg class="w-5 h-5 md:w-6 md:h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                </svg>
                            </div>
                            <span class="text-xs md:text-sm font-semibold text-gray-700 dark:text-gray-300 text-center">Inventory</span>
                        </a>

                        <!-- View Reports -->
                        <a href="/admin/reports" class="group flex flex-col items-center gap-2 p-3 md:p-4 bg-white dark:bg-gray-700 rounded-xl shadow-sm hover:shadow-md transition-all transform hover:-translate-y-1 border border-gray-100 dark:border-gray-600">
                            <div class="w-10 h-10 md:w-12 md:h-12 bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
                                <svg class="w-5 h-5 md:w-6 md:h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <span class="text-xs md:text-sm font-semibold text-gray-700 dark:text-gray-300 text-center">Reports</span>
                        </a>

                        <!-- Products -->
                        <a href="/admin/products" class="group flex flex-col items-center gap-2 p-3 md:p-4 bg-white dark:bg-gray-700 rounded-xl shadow-sm hover:shadow-md transition-all transform hover:-translate-y-1 border border-gray-100 dark:border-gray-600">
                            <div class="w-10 h-10 md:w-12 md:h-12 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
                                <svg class="w-5 h-5 md:w-6 md:h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                </svg>
                            </div>
                            <span class="text-xs md:text-sm font-semibold text-gray-700 dark:text-gray-300 text-center">Products</span>
                        </a>

                        <!-- Customers -->
                        <a href="/admin/customers" class="group flex flex-col items-center gap-2 p-3 md:p-4 bg-white dark:bg-gray-700 rounded-xl shadow-sm hover:shadow-md transition-all transform hover:-translate-y-1 border border-gray-100 dark:border-gray-600">
                            <div class="w-10 h-10 md:w-12 md:h-12 bg-gradient-to-br from-pink-500 to-rose-600 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
                                <svg class="w-5 h-5 md:w-6 md:h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                            </div>
                            <span class="text-xs md:text-sm font-semibold text-gray-700 dark:text-gray-300 text-center">Customers</span>
                        </a>

                        <!-- Settings -->
                        <a href="/admin/settings" class="group flex flex-col items-center gap-2 p-3 md:p-4 bg-white dark:bg-gray-700 rounded-xl shadow-sm hover:shadow-md transition-all transform hover:-translate-y-1 border border-gray-100 dark:border-gray-600">
                            <div class="w-10 h-10 md:w-12 md:h-12 bg-gradient-to-br from-gray-500 to-gray-600 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
                                <svg class="w-5 h-5 md:w-6 md:h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </div>
                            <span class="text-xs md:text-sm font-semibold text-gray-700 dark:text-gray-300 text-center">Settings</span>
                        </a>
                    </div>
                </div>

                <!-- Enhanced Summary Cards with Larger Numbers - Mobile Optimized -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6">
                    <div
                        v-for="(card, index) in data.summaryCards"
                        :key="index"
                        :class="{
                            'rounded-2xl shadow-xl hover:shadow-2xl p-8 transition-all duration-300 transform hover:-translate-y-1 overflow-hidden bg-gradient-to-br from-blue-500 via-blue-600 to-indigo-600 text-white border-2 border-blue-400': index === 0,
                            'rounded-2xl shadow-xl hover:shadow-2xl p-8 transition-all duration-300 transform hover:-translate-y-1 overflow-hidden bg-gradient-to-br from-emerald-500 via-green-600 to-teal-600 text-white border-2 border-emerald-400': index === 1,
                            'rounded-2xl shadow-xl hover:shadow-2xl p-8 transition-all duration-300 transform hover:-translate-y-1 overflow-hidden bg-gradient-to-br from-purple-500 via-purple-600 to-violet-600 text-white border-2 border-purple-400': index === 2,
                            'rounded-2xl shadow-xl hover:shadow-2xl p-8 transition-all duration-300 transform hover:-translate-y-1 overflow-hidden bg-gradient-to-br from-amber-400 via-yellow-500 to-orange-500 text-gray-900 border-2 border-amber-300': index === 3,
                        }"
                    >
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-4 rounded-2xl bg-white/20 backdrop-blur-sm shadow-lg">
                                <svg
                                    class="w-8 h-8"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                    xmlns="http://www.w3.org/2000/svg"
                                >
                                    <path
                                        v-if="index === 0"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"
                                    ></path>
                                    <path
                                        v-if="index === 1"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"
                                    ></path>
                                    <path
                                        v-if="index === 2"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                                    ></path>
                                    <path
                                        v-if="index === 3"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0"
                                    ></path>
                                </svg>
                            </div>
                            <div class="ml-4 flex-1">
                                <h3
                                    class="text-xs md:text-sm font-semibold opacity-90 uppercase tracking-wide"
                                >
                                    {{ card.title }}
                                </h3>
                                <p
                                    class="text-3xl md:text-4xl lg:text-5xl font-extrabold mt-2"
                                >
                                    {{ card.value }}
                                </p>
                            </div>
                        </div>
                        <div class="mt-4 flex items-center justify-between">
                            <div class="text-xs opacity-90 flex items-center">
                                <span :class="card.change >= 0 ? 'text-green-200' : 'text-red-200'">
                                    <svg v-if="card.change >= 0" class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path></svg>
                                    <svg v-else class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                    {{ Math.abs(card.change) }}%
                                </span>
                                <span class="ml-2 opacity-90">vs last period</span>
                            </div>
                            <div class="text-xs opacity-80">&nbsp;</div>
                        </div>
                    </div>
                </div>

                <!-- Order Status Cards - Mobile Optimized -->
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-8 gap-3 md:gap-4">
                    <div
                        v-for="(d, i) in deliveryStatusList"
                        :key="i"
                        :class="['rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 overflow-hidden border-2', `bg-gradient-to-br ${statusColor[d.key] || 'from-gray-100 to-gray-50 text-gray-900 border-gray-200'}`]"
                    >
                        <div class="flex items-center justify-between">
                            <div :class="statusColor[d.key] && statusColor[d.key].includes('text-gray-900') ? 'text-gray-900' : 'text-white'">
                                <div class="text-xs md:text-sm font-bold uppercase tracking-wide opacity-90 mb-1 md:mb-2 truncate">{{ d.name }}</div>
                                <div class="text-2xl md:text-3xl lg:text-4xl font-extrabold mb-1 md:mb-2">{{ d.orders }}</div>
                                <div class="text-xs md:text-sm font-semibold opacity-80">৳{{ Number(d.sales).toLocaleString() }}</div>
                            </div>
                            <div>
                                <span class="inline-block px-4 py-2 rounded-xl text-xs font-extrabold bg-white/30 backdrop-blur-sm shadow-lg border border-white/20" :class="statusColor[d.key] && statusColor[d.key].includes('text-gray-900') ? 'text-gray-900' : 'text-white'">
                                    {{ d.key.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase()) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Enhanced Charts Section -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Sales Performance Chart with Modern Design -->
                    <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl hover:shadow-2xl transition-all border-2 border-gray-100 dark:border-gray-700 p-8 lg:col-span-2">
                        <!-- Header -->
                        <div class="mb-8">
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center gap-3">
                                    <div class="bg-gradient-to-br from-purple-500 to-indigo-600 p-3 rounded-2xl shadow-lg">
                                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" />
                                        </svg>
                                    </div>
                                    <h3 class="text-2xl font-extrabold text-gray-900 dark:text-gray-100">
                                        📈 Sales Performance
                                    </h3>
                                </div>
                                <div class="flex space-x-2">
                                    <button
                                        @click="salesPeriod = 'week'"
                                        :class="salesPeriod === 'week'
                                            ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-lg'
                                            : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200'"
                                        class="px-5 py-2 text-sm font-bold rounded-xl transition-all transform hover:scale-105"
                                    >
                                        📅 Week
                                    </button>
                                    <button
                                        @click="salesPeriod = 'month'"
                                        :class="salesPeriod === 'month'
                                            ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-lg'
                                            : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200'"
                                        class="px-5 py-2 text-sm font-bold rounded-xl transition-all transform hover:scale-105"
                                    >
                                        🗓️ Month
                                    </button>
                                    <button
                                        class="bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 px-5 py-2 text-sm font-bold rounded-xl transition-all transform hover:scale-105"
                                    >
                                        📆 Year
                                    </button>
                                </div>
                            </div>
                            <p class="text-sm text-gray-600 dark:text-gray-400 font-medium ml-12">
                                Revenue and order trends analysis
                            </p>
                        </div>

                        <!-- Enhanced Metrics Cards -->
                        <div class="grid grid-cols-3 gap-6 mb-10">
                            <div class="bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 p-5 rounded-2xl border-2 border-blue-100 dark:border-blue-800">
                                <p class="text-xs font-extrabold text-blue-600 dark:text-blue-400 uppercase tracking-wide mb-2">
                                    💰 Total Sales
                                </p>
                                <p class="text-4xl font-extrabold bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">
                                    {{ salesMetrics.totalSales.toLocaleString() }}
                                </p>
                            </div>
                            <div class="bg-gradient-to-br from-emerald-50 to-green-50 dark:from-emerald-900/20 dark:to-green-900/20 p-5 rounded-2xl border-2 border-emerald-100 dark:border-emerald-800">
                                <p class="text-xs font-extrabold text-emerald-600 dark:text-emerald-400 uppercase tracking-wide mb-2">
                                    🎯 Revenue
                                </p>
                                <p class="text-4xl font-extrabold bg-gradient-to-r from-emerald-600 to-green-600 bg-clip-text text-transparent">
                                    {{ formatCurrency(salesMetrics.revenue) }}
                                </p>
                            </div>
                            <div class="bg-gradient-to-br from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 p-5 rounded-2xl border-2 border-purple-100 dark:border-purple-800">
                                <p class="text-xs font-extrabold text-purple-600 dark:text-purple-400 uppercase tracking-wide mb-2">
                                    📊 Avg Conversion
                                </p>
                                <p class="text-4xl font-extrabold bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-transparent">
                                    {{ salesMetrics.avgConversion }}%
                                </p>
                            </div>
                        </div>

                        <!-- Chart -->
                        <VueApexCharts
                            height="350"
                            :options="dailyOrdersOptions"
                            :series="dailyOrdersSeries"
                        />
                    </div>

                    <!-- Enhanced Top Products Card -->
                    <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl hover:shadow-2xl transition-all border-2 border-gray-100 dark:border-gray-700 p-6 sm:p-8">
                        <div class="flex items-center justify-between mb-8">
                            <div class="flex items-center gap-3">
                                <div class="bg-gradient-to-br from-emerald-500 to-teal-600 p-3 rounded-2xl shadow-lg">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                                    </svg>
                                </div>
                                <h3 class="text-xl font-extrabold text-gray-900 dark:text-gray-100">
                                    🏆 Top Products
                                </h3>
                            </div>
                            <a
                                href="/admin/products"
                                class="px-4 py-2 text-xs font-bold text-white bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 rounded-xl shadow-lg transition-all transform hover:scale-105"
                            >
                                View All →
                            </a>
                        </div>
                        <div class="space-y-4">
                            <div
                                v-for="(product, index) in data.topProducts"
                                :key="product.id"
                                class="flex items-center gap-4 p-4 rounded-2xl bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-900/50 dark:to-gray-800/50 hover:from-emerald-50 hover:to-teal-50 dark:hover:from-emerald-900/20 dark:hover:to-teal-900/20 transition-all cursor-pointer border-2 border-transparent hover:border-emerald-200 dark:hover:border-emerald-800 transform hover:scale-[1.02]"
                            >
                                <!-- Rank Badge -->
                                <div class="flex-shrink-0 w-10 h-10 bg-gradient-to-br from-blue-500 to-indigo-600 text-white rounded-xl flex items-center justify-center font-extrabold text-lg shadow-lg">
                                    {{ index + 1 }}
                                </div>

                                <!-- Product Image -->
                                <div class="flex-shrink-0 w-16 h-16 sm:w-20 sm:h-20 rounded-2xl overflow-hidden shadow-lg border-2 border-white dark:border-gray-700">
                                    <img
                                        v-if="product.thumbnail"
                                        :src="product.thumbnail"
                                        :alt="product.name"
                                        class="w-full h-full object-cover"
                                        @error="$event.target.src = '/uploads/products/default-product.png'"
                                    />
                                    <div v-else class="w-full h-full bg-gradient-to-br from-gray-200 to-gray-300 dark:from-gray-700 dark:to-gray-600 flex items-center justify-center">
                                        <svg class="w-8 h-8 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                        </svg>
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h4 class="text-sm font-extrabold text-gray-900 dark:text-gray-100 truncate">{{ product.name }}</h4>
                                    <div class="flex items-center gap-2 mt-2">
                                        <div class="flex items-center text-yellow-400">
                                            <svg v-for="star in 5" :key="star" class="w-4 h-4" :class="star <= Math.floor(product.rating) ? 'fill-current' : 'fill-gray-300'" viewBox="0 0 20 20">
                                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                            </svg>
                                            <span class="text-sm text-gray-600 dark:text-gray-400 ml-2 font-bold">{{ product.rating }}</span>
                                        </div>
                                    </div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 font-semibold">📦 {{ product.total_sold }} sales</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-bold text-gray-900 dark:text-gray-100">৳{{ product.price.toLocaleString() }}</p>
                                    <div class="flex items-center gap-1 mt-1">
                                        <svg v-if="product.growth >= 0" class="w-3 h-3 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path>
                                        </svg>
                                        <svg v-else class="w-3 h-3 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                                        </svg>
                                        <span class="text-xs font-semibold" :class="product.growth >= 0 ? 'text-green-600' : 'text-red-600'">
                                            {{ product.growth >= 0 ? '+' : '' }}{{ product.growth }}%
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div v-if="data.topProducts.length === 0" class="text-center py-8 text-gray-500 dark:text-gray-400">
                                <svg class="w-12 h-12 mx-auto mb-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                </svg>
                                <p class="text-sm font-medium">No product data available</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Orders & Inventory Row -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Recent Orders -->
                    <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                            <div class="flex items-center justify-between">
                                <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100">
                                    Recent Orders
                                </h3>
                                <a href="/admin/orders" class="text-sm font-semibold text-blue-600 hover:text-blue-700 dark:text-blue-400 flex items-center gap-1">
                                    View All Orders →
                                </a>
                            </div>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                Latest transactions from your store
                            </p>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-gray-50 dark:bg-gray-900/50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Order ID</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Customer</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Amount</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Date</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr v-for="order in data.recentOrders.slice(0, 5)" :key="order.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                            #{{ order.order_number }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                                            {{ order.customer?.name || 'Guest' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900 dark:text-gray-100">
                                            ৳{{ order.total?.toLocaleString() }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span :class="{
                                                'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400': order.status === 'completed' || order.status === 'delivered',
                                                'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400': order.status === 'processing' || order.status === 'confirmed',
                                                'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400': order.status === 'pending',
                                                'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400': order.status === 'cancelled'
                                            }" class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full capitalize">
                                                {{ order.status }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            {{ new Date(order.created_at).toLocaleDateString() }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <a :href="`/admin/orders/${order.id}`" class="text-blue-600 hover:text-blue-700 dark:text-blue-400 font-medium">
                                                View
                                            </a>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Inventory Status -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                        <div class="p-4 sm:p-6 border-b border-gray-200 dark:border-gray-700">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 bg-red-100 dark:bg-red-900/30 rounded-lg">
                                        <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="text-base sm:text-lg font-bold text-gray-900 dark:text-gray-100">Inventory Status</h3>
                                        <span v-if="data.criticalCount > 0" class="inline-flex items-center gap-1 text-xs font-semibold text-red-600 dark:text-red-400 mt-1">
                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                            </svg>
                                            {{ data.criticalCount }} Critical items
                                        </span>
                                        <span v-else class="text-xs font-semibold text-green-600 dark:text-green-400 mt-1">
                                            All stock levels healthy
                                        </span>
                                    </div>
                                </div>
                                <a href="/admin/inventory" class="hidden sm:flex items-center gap-1 text-xs font-semibold text-blue-600 hover:text-blue-700 dark:text-blue-400 whitespace-nowrap">
                                    View All →
                                </a>
                            </div>
                        </div>
                        <div class="p-4 sm:p-6 max-h-[500px] overflow-y-auto">
                            <div class="space-y-3">
                                <!-- Using grouped inventory items to avoid duplicates -->
                                <div
                                    v-for="item in groupedInventoryItems"
                                    :key="item.id"
                                    :class="[
                                        'p-4 rounded-xl border-2 transition-all hover:shadow-md cursor-pointer',
                                        item.status === 'critical' ? 'bg-red-50 dark:bg-red-900/10 border-red-300 dark:border-red-900/40 hover:border-red-400' : '',
                                        item.status === 'warning' ? 'bg-yellow-50 dark:bg-yellow-900/10 border-yellow-300 dark:border-yellow-900/40 hover:border-yellow-400' : '',
                                        item.status === 'good' ? 'bg-green-50 dark:bg-green-900/10 border-green-300 dark:border-green-900/40 hover:border-green-400' : ''
                                    ]"
                                >
                                    <div class="flex-1 min-w-0 pr-3">
                                        <!-- Product Name with Total Quantity -->
                                        <div class="flex items-center justify-between mb-1">
                                            <p class="text-sm font-semibold text-gray-900 dark:text-gray-100 truncate">
                                                {{ item.name }}
                                            </p>
                                            <span class="text-xs font-bold text-gray-700 dark:text-gray-300 ml-2 whitespace-nowrap">
                                                Total: {{ item.current }}
                                            </span>
                                        </div>

                                        <!-- Variations Display (if exists) -->
                                        <div v-if="item.variations && item.variations.length > 0" class="flex flex-wrap gap-1 mb-2">
                                            <span
                                                v-for="(variation, idx) in item.variations.slice(0, 3)"
                                                :key="idx"
                                                class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 transition-all hover:bg-blue-200 dark:hover:bg-blue-900/50"
                                            >
                                                <span class="font-semibold">{{ variation.name }}</span>
                                                <span class="ml-1 text-blue-600 dark:text-blue-400">×{{ variation.quantity }}</span>
                                            </span>
                                            <span
                                                v-if="item.variations.length > 3"
                                                class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gradient-to-r from-purple-100 to-pink-100 dark:from-purple-900/30 dark:to-pink-900/30 text-purple-700 dark:text-purple-300 cursor-help transition-all hover:from-purple-200 hover:to-pink-200 dark:hover:from-purple-900/50 dark:hover:to-pink-900/50"
                                                :title="item.variations.slice(3).map(v => `${v.name}: ${v.quantity}`).join(', ')"
                                            >
                                                +{{ item.variations.length - 3 }} more variations
                                            </span>
                                        </div>

                                        <!-- Progress Bar -->
                                        <div class="mt-1.5 w-full rounded-full h-2" :class="[
                                            item.status === 'critical' ? 'bg-red-200 dark:bg-red-900/30' : '',
                                            item.status === 'warning' ? 'bg-yellow-200 dark:bg-yellow-900/30' : '',
                                            item.status === 'good' ? 'bg-green-200 dark:bg-green-900/30' : ''
                                        ]">
                                            <div
                                                class="h-2 rounded-full transition-all"
                                                :class="[
                                                    item.status === 'critical' ? 'bg-red-600' : '',
                                                    item.status === 'warning' ? 'bg-yellow-600' : '',
                                                    item.status === 'good' ? 'bg-green-600' : ''
                                                ]"
                                                :style="{ width: Math.min(item.percentage, 100) + '%' }"
                                            ></div>
                                        </div>
                                        <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">{{ item.current }}/{{ item.max }} ({{ item.percentage }}%)</p>
                                    </div>
                                    <svg
                                        class="w-5 h-5 flex-shrink-0"
                                        :class="[
                                            item.status === 'critical' ? 'text-red-600 dark:text-red-400' : '',
                                            item.status === 'warning' ? 'text-yellow-600 dark:text-yellow-400' : '',
                                            item.status === 'good' ? 'text-green-600 dark:text-green-400' : ''
                                        ]"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path v-if="item.status === 'critical'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                        <path v-else-if="item.status === 'warning'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                        <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <div v-if="data.inventoryItems.length === 0" class="text-center py-8 text-gray-500 dark:text-gray-400">
                                    <svg class="w-12 h-12 mx-auto mb-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                    </svg>
                                    <p class="text-sm font-medium">No inventory data available</p>
                                </div>
                            </div>
                            <a href="/admin/inventory" class="sm:hidden mt-4 w-full flex items-center justify-center gap-2 text-sm font-semibold text-blue-600 hover:text-blue-700 dark:text-blue-400 py-2">
                                View All Inventory →
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Old Map (Kept as requested) -->
                <div
                    class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 md:p-6 p-0"
                >
                    <OrderMap :locations="data.locations" />
                </div>
            </div>
        </div>

        <!-- Keyboard Shortcuts Modal -->
        <div v-if="showShortcutsModal" class="fixed inset-0 z-50 overflow-y-auto" @click.self="showShortcutsModal = false">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <!-- Background overlay -->
                <div class="fixed inset-0 transition-opacity bg-gray-500 dark:bg-gray-900 bg-opacity-75 dark:bg-opacity-75" @click="showShortcutsModal = false"></div>

                <!-- Modal panel -->
                <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                    <!-- Header -->
                    <div class="bg-gradient-to-r from-blue-600 to-purple-600 px-6 py-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                    </svg>
                                </div>
                                <h3 class="text-xl font-bold text-white">Keyboard Shortcuts</h3>
                            </div>
                            <button @click="showShortcutsModal = false" class="text-white hover:text-gray-200 transition-colors">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Content -->
                    <div class="px-6 py-6 space-y-6">
                        <!-- Navigation Shortcuts -->
                        <div>
                            <h4 class="text-sm font-bold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                </svg>
                                Navigation
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                    <span class="text-sm text-gray-700 dark:text-gray-300">Orders</span>
                                    <kbd class="px-2 py-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded text-xs font-mono">Ctrl + 1</kbd>
                                </div>
                                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                    <span class="text-sm text-gray-700 dark:text-gray-300">Inventory</span>
                                    <kbd class="px-2 py-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded text-xs font-mono">Ctrl + 2</kbd>
                                </div>
                                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                    <span class="text-sm text-gray-700 dark:text-gray-300">Reports</span>
                                    <kbd class="px-2 py-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded text-xs font-mono">Ctrl + 3</kbd>
                                </div>
                                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                    <span class="text-sm text-gray-700 dark:text-gray-300">Products</span>
                                    <kbd class="px-2 py-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded text-xs font-mono">Ctrl + 4</kbd>
                                </div>
                                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                    <span class="text-sm text-gray-700 dark:text-gray-300">Customers</span>
                                    <kbd class="px-2 py-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded text-xs font-mono">Ctrl + 5</kbd>
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div>
                            <h4 class="text-sm font-bold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                                <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                                Actions
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                    <span class="text-sm text-gray-700 dark:text-gray-300">Refresh Data</span>
                                    <kbd class="px-2 py-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded text-xs font-mono">Ctrl + R</kbd>
                                </div>
                                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                    <span class="text-sm text-gray-700 dark:text-gray-300">Shortcuts Help</span>
                                    <kbd class="px-2 py-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded text-xs font-mono">Ctrl + K</kbd>
                                </div>
                            </div>
                        </div>

                        <!-- Tips -->
                        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                            <div class="flex items-start gap-3">
                                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <div>
                                    <h5 class="text-sm font-semibold text-blue-900 dark:text-blue-300 mb-1">Pro Tip</h5>
                                    <p class="text-sm text-blue-700 dark:text-blue-400">Use <kbd class="px-1.5 py-0.5 bg-white dark:bg-gray-800 border border-blue-300 dark:border-blue-700 rounded text-xs font-mono">Cmd</kbd> instead of <kbd class="px-1.5 py-0.5 bg-white dark:bg-gray-800 border border-blue-300 dark:border-blue-700 rounded text-xs font-mono">Ctrl</kbd> on Mac</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="bg-gray-50 dark:bg-gray-700 px-6 py-4">
                        <button @click="showShortcutsModal = false" class="w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition-colors">
                            Got it!
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
