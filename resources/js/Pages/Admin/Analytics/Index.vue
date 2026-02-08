<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { computed, ref, onMounted, onUnmounted } from 'vue';
import { TrendingUp, TrendingDown, DollarSign, ShoppingCart, Package, Users, Truck, Clock, MapPin, Calendar, Download, RefreshCw, Filter } from 'lucide-vue-next';
import VueApexCharts from 'vue3-apexcharts';

const props = defineProps({
    analytics: Object,
    revenueChart: Array,
    topProducts: Array,
    ordersByStatus: Array,
    courierStats: Object,
    districtStats: Array,
    activeUsers: Number,
    districtUserStats: Array,
});

// Auto-refresh state
const autoRefreshEnabled = ref(true);
const refreshInterval = ref(null);
const lastRefreshTime = ref(new Date());

// Date filter
const dateFrom = ref('');
const dateTo = ref('');
const showDateFilter = ref(false);

// Export loading
const exportingPDF = ref(false);
const exportingExcel = ref(false);

// Format currency
const formatCurrency = (amount) => {
    return new Intl.NumberFormat('bn-BD', {
        style: 'currency',
        currency: 'BDT',
        minimumFractionDigits: 0,
    }).format(amount);
};

// Calculate percentage change
const getPercentageChange = (current, previous) => {
    if (!previous || previous === 0) return 0;
    return (((current - previous) / previous) * 100).toFixed(1);
};

// Stats cards data
const statsCards = computed(() => [
    {
        title: 'Today Revenue',
        value: formatCurrency(props.analytics?.today?.revenue || 0),
        change: getPercentageChange(props.analytics?.today?.revenue, props.analytics?.yesterday?.revenue),
        icon: DollarSign,
        color: 'blue',
        trend: (props.analytics?.today?.revenue || 0) >= (props.analytics?.yesterday?.revenue || 0),
    },
    {
        title: 'Today Orders',
        value: props.analytics?.today?.orders || 0,
        change: getPercentageChange(props.analytics?.today?.orders, props.analytics?.yesterday?.orders),
        icon: ShoppingCart,
        color: 'green',
        trend: (props.analytics?.today?.orders || 0) >= (props.analytics?.yesterday?.orders || 0),
    },
    {
        title: 'Average Order Value',
        value: formatCurrency(props.analytics?.averageOrderValue || 0),
        icon: TrendingUp,
        color: 'purple',
    },
    {
        title: 'Total Products',
        value: props.analytics?.totalProducts || 0,
        icon: Package,
        color: 'orange',
    },
]);

const selectedPeriod = ref('7days');

// ApexCharts configuration
const revenueChartOptions = computed(() => ({
    chart: {
        type: 'area',
        height: 350,
        toolbar: { show: false },
        zoom: { enabled: false },
    },
    dataLabels: { enabled: false },
    stroke: { curve: 'smooth', width: 2 },
    colors: ['#3B82F6'],
    fill: {
        type: 'gradient',
        gradient: {
            shadeIntensity: 1,
            opacityFrom: 0.7,
            opacityTo: 0.3,
        },
    },
    xaxis: {
        categories: props.revenueChart?.map(item => item.date) || [],
    },
    yaxis: {
        labels: {
            formatter: (val) => '৳' + val.toFixed(0),
        },
    },
    tooltip: {
        y: {
            formatter: (val) => formatCurrency(val),
        },
    },
}));

const revenueChartSeries = computed(() => [{
    name: 'Revenue',
    data: props.revenueChart?.map(item => item.revenue) || [],
}]);

// Auto-refresh functionality
const refreshData = () => {
    router.reload({
        preserveScroll: true,
        preserveState: true,
        only: ['analytics', 'revenueChart', 'topProducts', 'ordersByStatus', 'courierStats', 'districtStats', 'activeUsers', 'districtUserStats'],
    });
    lastRefreshTime.value = new Date();
};

const toggleAutoRefresh = () => {
    autoRefreshEnabled.value = !autoRefreshEnabled.value;
    if (autoRefreshEnabled.value) {
        startAutoRefresh();
    } else {
        stopAutoRefresh();
    }
};

const startAutoRefresh = () => {
    if (refreshInterval.value) return;
    refreshInterval.value = setInterval(refreshData, 60000); // Refresh every 60 seconds
};

const stopAutoRefresh = () => {
    if (refreshInterval.value) {
        clearInterval(refreshInterval.value);
        refreshInterval.value = null;
    }
};

// Apply date filter
const applyDateFilter = () => {
    if (dateFrom.value && dateTo.value) {
        router.get(route('admin.analytics.index'), {
            date_from: dateFrom.value,
            date_to: dateTo.value,
        }, {
            preserveScroll: true,
            preserveState: true,
        });
    }
    showDateFilter.value = false;
};

const clearDateFilter = () => {
    dateFrom.value = '';
    dateTo.value = '';
    router.get(route('admin.analytics.index'), {}, {
        preserveScroll: true,
        preserveState: true,
    });
    showDateFilter.value = false;
};

// Export functions
const exportToPDF = async () => {
    exportingPDF.value = true;
    try {
        const response = await axios.get(route('admin.analytics.export.pdf'), {
            responseType: 'blob',
            params: {
                date_from: dateFrom.value,
                date_to: dateTo.value,
            },
        });

        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', `analytics-${new Date().toISOString().split('T')[0]}.pdf`);
        document.body.appendChild(link);
        link.click();
        link.remove();
    } catch (error) {
        console.error('Export failed:', error);
        alert('Export failed. Please try again.');
    } finally {
        exportingPDF.value = false;
    }
};

const exportToExcel = async () => {
    exportingExcel.value = true;
    try {
        const response = await axios.get(route('admin.analytics.export.excel'), {
            responseType: 'blob',
            params: {
                date_from: dateFrom.value,
                date_to: dateTo.value,
            },
        });

        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', `analytics-${new Date().toISOString().split('T')[0]}.xlsx`);
        document.body.appendChild(link);
        link.click();
        link.remove();
    } catch (error) {
        console.error('Export failed:', error);
        alert('Export failed. Please try again.');
    } finally {
        exportingExcel.value = false;
    }
};

// Lifecycle hooks
onMounted(() => {
    if (autoRefreshEnabled.value) {
        startAutoRefresh();
    }
});

onUnmounted(() => {
    stopAutoRefresh();
});
</script>

<template>
    <Head title="Analytics Dashboard" />
    <AdminLayout>
        <div class="p-6 space-y-6">
            <!-- Header with Actions -->
            <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">📊 Analytics Dashboard</h1>
                    <p class="text-gray-600 dark:text-gray-400 mt-1">
                        Last updated: {{ lastRefreshTime.toLocaleTimeString() }}
                        <span v-if="autoRefreshEnabled" class="ml-2 text-green-600">● Auto-refresh ON</span>
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <!-- Auto-refresh toggle -->
                    <button
                        @click="toggleAutoRefresh"
                        :class="[
                            'p-2 rounded-lg transition-colors',
                            autoRefreshEnabled ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'
                        ]"
                        :title="autoRefreshEnabled ? 'Auto-refresh enabled' : 'Auto-refresh disabled'"
                    >
                        <RefreshCw :class="['w-5 h-5', autoRefreshEnabled && refreshInterval && 'animate-spin']" />
                    </button>

                    <!-- Manual refresh -->
                    <button
                        @click="refreshData"
                        class="p-2 bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 transition-colors"
                        title="Refresh now"
                    >
                        <RefreshCw class="w-5 h-5" />
                    </button>

                    <!-- Date filter toggle -->
                    <button
                        @click="showDateFilter = !showDateFilter"
                        class="p-2 bg-purple-100 text-purple-700 rounded-lg hover:bg-purple-200 transition-colors"
                        title="Filter by date"
                    >
                        <Filter class="w-5 h-5" />
                    </button>

                    <!-- Advanced Analytics Link -->
                    <a
                        :href="route('admin.analytics.advanced')"
                        class="px-4 py-2 bg-gradient-to-r from-purple-600 to-indigo-600 text-white rounded-lg hover:from-purple-700 hover:to-indigo-700 transition-colors flex items-center gap-2"
                    >
                        <TrendingUp class="w-4 h-4" />
                        <span>Advanced Analytics</span>
                    </a>

                    <!-- Period selector -->
                    <select v-model="selectedPeriod" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="today">Today</option>
                        <option value="7days">Last 7 Days</option>
                        <option value="30days">Last 30 Days</option>
                        <option value="thisMonth">This Month</option>
                        <option value="thisYear">This Year</option>
                    </select>

                    <!-- Export PDF -->
                    <button
                        @click="exportToPDF"
                        :disabled="exportingPDF"
                        class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors disabled:opacity-50 flex items-center gap-2"
                    >
                        <Download class="w-4 h-4" />
                        <span v-if="!exportingPDF">PDF</span>
                        <span v-else>Exporting...</span>
                    </button>

                    <!-- Export Excel -->
                    <button
                        @click="exportToExcel"
                        :disabled="exportingExcel"
                        class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors disabled:opacity-50 flex items-center gap-2"
                    >
                        <Download class="w-4 h-4" />
                        <span v-if="!exportingExcel">Excel</span>
                        <span v-else>Exporting...</span>
                    </button>
                </div>
            </div>

            <!-- Date Filter Modal -->
            <div v-if="showDateFilter" class="p-4 bg-white dark:bg-gray-800 rounded-lg shadow-md border border-gray-200 dark:border-gray-700">
                <div class="flex flex-col sm:flex-row gap-4 items-end">
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">From Date</label>
                        <input
                            v-model="dateFrom"
                            type="date"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                        />
                    </div>
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">To Date</label>
                        <input
                            v-model="dateTo"
                            type="date"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                        />
                    </div>
                    <div class="flex gap-2">
                        <button
                            @click="applyDateFilter"
                            class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
                        >
                            Apply
                        </button>
                        <button
                            @click="clearDateFilter"
                            class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors"
                        >
                            Clear
                        </button>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6">
                <div v-for="(stat, index) in statsCards" :key="index"
                     class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-gray-700 hover:shadow-lg transition-all duration-200">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ stat.title }}</p>
                            <h3 class="text-2xl font-bold text-gray-900 dark:text-white mt-2">{{ stat.value }}</h3>
                            <div v-if="stat.change !== undefined" class="flex items-center gap-1 mt-2">
                                <component :is="stat.trend ? TrendingUp : TrendingDown"
                                          :class="stat.trend ? 'text-green-500' : 'text-red-500'"
                                          class="w-4 h-4" />
                                <span :class="stat.trend ? 'text-green-600' : 'text-red-600'" class="text-sm font-medium">
                                    {{ Math.abs(stat.change) }}%
                                </span>
                                <span class="text-xs text-gray-500">vs yesterday</span>
                            </div>
                        </div>
                        <div :class="`bg-${stat.color}-100 dark:bg-${stat.color}-900/20 p-3 rounded-lg`">
                            <component :is="stat.icon" :class="`text-${stat.color}-600 dark:text-${stat.color}-400`" class="w-6 h-6" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Revenue Trend Chart -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <TrendingUp class="w-5 h-5 text-blue-500" />
                        Revenue Trend (Last 30 Days)
                    </h3>
                    <VueApexCharts
                        type="area"
                        height="280"
                        :options="revenueChartOptions"
                        :series="revenueChartSeries"
                    />
                </div>

                <!-- Active Users Card -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <Users class="w-5 h-5 text-emerald-500" />
                        Active Users Overview
                    </h3>
                    <div class="space-y-6">
                        <div class="text-center p-6 bg-gradient-to-br from-emerald-50 to-green-50 dark:from-emerald-900/20 dark:to-green-900/20 rounded-lg">
                            <Users class="w-16 h-16 text-emerald-600 mx-auto mb-3" />
                            <h4 class="text-4xl font-bold text-gray-900 dark:text-white">{{ activeUsers || 0 }}</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">Users online in last 15 minutes</p>
                        </div>

                        <!-- Top Districts by Users -->
                        <div v-if="districtUserStats && districtUserStats.length > 0">
                            <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Top Districts by Users</h4>
                            <div class="space-y-2">
                                <div v-for="(district, index) in districtUserStats.slice(0, 5)" :key="index"
                                     class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                    <div class="flex items-center gap-2">
                                        <MapPin class="w-4 h-4 text-gray-400" />
                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ district.district }}</span>
                                    </div>
                                    <span class="text-sm font-bold text-emerald-600">{{ district.user_count }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Order Status Distribution -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <ShoppingCart class="w-5 h-5 text-green-500" />
                        Order Status Distribution
                    </h3>
                    <div class="space-y-3">
                        <div v-for="status in ordersByStatus" :key="status.status" class="flex items-center justify-between">
                            <div class="flex items-center gap-3 flex-1">
                                <div class="w-3 h-3 rounded-full" :class="{
                                    'bg-yellow-500': status.status === 'pending',
                                    'bg-blue-500': status.status === 'processing',
                                    'bg-green-500': status.status === 'delivered',
                                    'bg-red-500': status.status === 'cancelled',
                                    'bg-gray-500': !['pending', 'processing', 'delivered', 'cancelled'].includes(status.status)
                                }"></div>
                                <span class="text-sm text-gray-700 dark:text-gray-300 capitalize">{{ status.status.replace('_', ' ') }}</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ status.count }}</span>
                                <div class="w-32 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                    <div class="h-2 rounded-full transition-all duration-300"
                                         :class="{
                                             'bg-yellow-500': status.status === 'pending',
                                             'bg-blue-500': status.status === 'processing',
                                             'bg-green-500': status.status === 'delivered',
                                             'bg-red-500': status.status === 'cancelled',
                                             'bg-gray-500': !['pending', 'processing', 'delivered', 'cancelled'].includes(status.status)
                                         }"
                                         :style="{ width: `${status.percentage}%` }"></div>
                                </div>
                                <span class="text-xs text-gray-500 w-12 text-right">{{ status.percentage }}%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top Products & Courier Stats -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Top Selling Products -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <Package class="w-5 h-5 text-purple-500" />
                        Top 10 Selling Products
                    </h3>
                    <div class="space-y-3">
                        <div v-for="(product, index) in topProducts" :key="product.id"
                             class="flex items-center gap-4 p-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                            <div class="flex items-center justify-center w-8 h-8 rounded-full bg-gradient-to-br from-purple-500 to-pink-500 text-white text-sm font-bold">
                                {{ index + 1 }}
                            </div>
                            <img :src="product.image || '/placeholder.png'"
                                 :alt="product.name"
                                 class="w-12 h-12 rounded-lg object-cover bg-gray-200 dark:bg-gray-700">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ product.name }}</p>
                                <p class="text-xs text-gray-500">{{ product.sold_count }} sold</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ formatCurrency(product.revenue) }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Courier Statistics -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <Truck class="w-5 h-5 text-orange-500" />
                        Courier Performance
                    </h3>
                    <div class="space-y-4">
                        <!-- Steadfast Stats -->
                        <div class="p-4 bg-green-50 dark:bg-green-900/20 rounded-lg">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Steadfast</span>
                                <span class="text-lg font-bold text-green-600 dark:text-green-400">{{ courierStats?.steadfast?.count || 0 }}</span>
                            </div>
                            <div class="w-full bg-green-200 dark:bg-green-800 rounded-full h-2">
                                <div class="bg-green-600 dark:bg-green-500 h-2 rounded-full transition-all duration-300"
                                     :style="{ width: `${courierStats?.steadfast?.percentage || 0}%` }"></div>
                            </div>
                            <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">{{ courierStats?.steadfast?.percentage || 0 }}% of total</p>
                        </div>

                        <!-- Pathao Stats -->
                        <div class="p-4 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Pathao</span>
                                <span class="text-lg font-bold text-purple-600 dark:text-purple-400">{{ courierStats?.pathao?.count || 0 }}</span>
                            </div>
                            <div class="w-full bg-purple-200 dark:bg-purple-800 rounded-full h-2">
                                <div class="bg-purple-600 dark:bg-purple-500 h-2 rounded-full transition-all duration-300"
                                     :style="{ width: `${courierStats?.pathao?.percentage || 0}%` }"></div>
                            </div>
                            <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">{{ courierStats?.pathao?.percentage || 0 }}% of total</p>
                        </div>

                        <!-- No Courier -->
                        <div class="p-4 bg-gray-50 dark:bg-gray-700/20 rounded-lg">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">No Courier</span>
                                <span class="text-lg font-bold text-gray-600 dark:text-gray-400">{{ courierStats?.none?.count || 0 }}</span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-2">
                                <div class="bg-gray-500 dark:bg-gray-400 h-2 rounded-full transition-all duration-300"
                                     :style="{ width: `${courierStats?.none?.percentage || 0}%` }"></div>
                            </div>
                            <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">{{ courierStats?.none?.percentage || 0 }}% of total</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- District-wise Orders (Heatmap) -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <MapPin class="w-5 h-5 text-red-500" />
                    District-wise Order Distribution
                </h3>
                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3">
                    <div v-for="district in districtStats?.slice(0, 12)" :key="district.district"
                         class="p-4 rounded-lg border-2 border-gray-200 dark:border-gray-700 hover:border-blue-500 dark:hover:border-blue-400 transition-all duration-200 cursor-pointer group">
                        <p class="text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">{{ district.district }}</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white group-hover:text-blue-500">{{ district.count }}</p>
                        <p class="text-xs text-gray-500 mt-1">{{ formatCurrency(district.revenue) }}</p>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
