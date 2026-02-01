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

// Get chart data based on selected period
const currentChartData = computed(() => {
    return salesPeriod.value === 'week' 
        ? props.data.dailyOrdersData 
        : (props.data.monthlyOrdersData || props.data.dailyOrdersData);
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

// Order status chart options
const orderStatusOptions = computed(() => ({
    chart: {
        type: "donut",
        height: 300,
        animations: {
            enabled: true,
            easing: "easeinout",
            speed: 800,
        },
    },
    colors: [
        "#F59E0B",
        "#6366F1",
        "#10B981",
        "#EF4444",
        "#8B5CF6",
        "#EC4899",
        "#14B8A6",
        "#F97316",
        "#6B7280",
    ],
    labels: props.data.orderStatusData.map((item) => item.name),
    legend: {
        position: "bottom",
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
    plotOptions: {
        pie: {
            donut: {
                size: "65%",
                labels: {
                    show: true,
                    name: {
                        show: true,
                        fontSize: "14px",
                        fontFamily: "Inter, sans-serif",
                        color: "#6B7280",
                    },
                    value: {
                        show: true,
                        fontSize: "20px",
                        fontFamily: "Inter, sans-serif",
                        fontWeight: 600,
                        color: "#111827",
                        formatter: function (val) {
                            return val;
                        },
                    },
                    total: {
                        show: true,
                        showAlways: true,
                        label: "Total Orders",
                        fontSize: "14px",
                        fontFamily: "Inter, sans-serif",
                        fontWeight: 400,
                        color: "#6B7280",
                        formatter: function () {
                            return props.data.orderStatusData.reduce(
                                (sum, item) => sum + item.value,
                                0
                            );
                        },
                    },
                },
            },
        },
    },
    dataLabels: {
        enabled: false,
    },
    tooltip: {
        enabled: true,
        style: {
            fontSize: "12px",
            fontFamily: "Inter, sans-serif",
        },
        y: {
            formatter: function (val) {
                return val + " orders";
            },
        },
    },
}));

const orderStatusSeries = computed(() =>
    props.data.orderStatusData.map((item) => item.value)
);

// Payment status chart options
const paymentStatusOptions = computed(() => ({
    chart: {
        type: "donut",
        height: 300,
        animations: {
            enabled: true,
            easing: "easeinout",
            speed: 800,
        },
    },
    colors: ["#EF4444", "#10B981", "#6366F1"],
    labels: props.data.paymentStatusData.map((item) => item.name),
    legend: {
        position: "bottom",
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
    plotOptions: {
        pie: {
            donut: {
                size: "65%",
                labels: {
                    show: true,
                    name: {
                        show: true,
                        fontSize: "14px",
                        fontFamily: "Inter, sans-serif",
                        color: "#6B7280",
                    },
                    value: {
                        show: true,
                        fontSize: "20px",
                        fontFamily: "Inter, sans-serif",
                        fontWeight: 600,
                        color: "#111827",
                        formatter: function (val) {
                            return val;
                        },
                    },
                    total: {
                        show: true,
                        showAlways: true,
                        label: "Total",
                        fontSize: "14px",
                        fontFamily: "Inter, sans-serif",
                        fontWeight: 400,
                        color: "#6B7280",
                        formatter: function () {
                            return props.data.paymentStatusData.reduce(
                                (sum, item) => sum + item.value,
                                0
                            );
                        },
                    },
                },
            },
        },
    },
    dataLabels: {
        enabled: false,
    },
    tooltip: {
        enabled: true,
        style: {
            fontSize: "12px",
            fontFamily: "Inter, sans-serif",
        },
        y: {
            formatter: function (val) {
                return val;
            },
        },
    },
}));

const paymentStatusSeries = computed(() =>
    props.data.paymentStatusData.map((item) => item.value)
);

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
</script>

<template>
    <Head title="Dashboard" />
    <AdminLayout>
        <div class="mx-auto">
            <div class="flex flex-col space-y-6">
                <!-- Header -->
                <div
                    class="flex flex-col md:flex-row md:items-center md:justify-between"
                >
                    <div>
                        <h1
                            class="text-2xl font-bold text-gray-900 dark:text-gray-100"
                        >
                            Dashboard Overview
                        </h1>
                        <div class="w-32 h-1 rounded-full bg-gradient-to-r from-indigo-500 via-pink-500 to-yellow-400 mt-3 mb-2"></div>
                        <p
                            class="mt-1 text-sm text-gray-500 dark:text-gray-400"
                        >
                            Monitor your store performance and key metrics
                        </p>
                    </div>
                </div>

                <!-- Summary Cards -->
                <div
                    class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6"
                >
                    <div
                        v-for="(card, index) in data.summaryCards"
                        :key="index"
                        :class="{
                            'rounded-xl shadow-sm p-6 transition-all duration-300 hover:shadow-md overflow-hidden bg-gradient-to-r from-indigo-500 to-indigo-400 text-white': index === 0,
                            'rounded-xl shadow-sm p-6 transition-all duration-300 hover:shadow-md overflow-hidden bg-gradient-to-r from-green-500 to-emerald-400 text-white': index === 1,
                            'rounded-xl shadow-sm p-6 transition-all duration-300 hover:shadow-md overflow-hidden bg-gradient-to-r from-purple-600 to-violet-500 text-white': index === 2,
                            'rounded-xl shadow-sm p-6 transition-all duration-300 hover:shadow-md overflow-hidden bg-gradient-to-r from-amber-400 to-amber-300 text-gray-900': index === 3,
                        }"
                    >
                        <div class="flex items-center">
                            <div class="p-3 rounded-lg bg-white/10">
                                <svg
                                    class="w-6 h-6"
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
                                        d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 a0 11-18 0 9 9 0 0118 0z"
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
                            <div class="ml-4">
                                <h3
                                    class="text-sm font-medium opacity-90"
                                >
                                    {{ card.title }}
                                </h3>
                                <p
                                    class="text-2xl font-semibold mt-1"
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

                <!-- Delivery Status Row -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div
                        v-for="(d, i) in deliveryStatusList"
                        :key="i"
                        :class="['rounded-xl p-4 shadow-sm overflow-hidden', `bg-gradient-to-r ${statusColor[d.key] || 'from-gray-100 to-gray-50 text-gray-900'}`]"
                    >
                        <div class="flex items-center justify-between">
                            <div :class="statusColor[d.key] && statusColor[d.key].includes('text-gray-900') ? 'text-gray-900' : 'text-white'">
                                <div class="text-xs opacity-90 mb-1">{{ d.name }}</div>
                                <div class="text-lg font-semibold">{{ d.orders }} orders</div>
                                <div class="text-sm opacity-80">৳{{ Number(d.sales).toFixed(2) }} sales</div>
                            </div>
                            <div>
                                <span class="inline-block px-3 py-1 rounded-full text-xs font-semibold bg-white/20 backdrop-blur-sm" :class="statusColor[d.key] && statusColor[d.key].includes('text-gray-900') ? 'text-gray-900' : 'text-white'">
                                    {{ d.key.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase()) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Row 1 -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Daily Orders & Revenue Chart -->
                    <div
                        class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 lg:col-span-2"
                    >
                        <div class="flex items-center justify-between mb-6">
                            <h3
                                class="text-lg font-semibold text-gray-900 dark:text-gray-100"
                            >
                                Sales Analytics
                            </h3>
                            <div class="flex space-x-2">
                                <button
                                    @click="salesPeriod = 'week'"
                                    :class="salesPeriod === 'week' 
                                        ? 'bg-indigo-100 dark:bg-indigo-900/50 text-indigo-800 dark:text-indigo-200' 
                                        : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200'"
                                    class="px-3 py-1 text-xs font-medium rounded-full transition-colors"
                                >
                                    Week
                                </button>
                                <button
                                    @click="salesPeriod = 'month'"
                                    :class="salesPeriod === 'month' 
                                        ? 'bg-indigo-100 dark:bg-indigo-900/50 text-indigo-800 dark:text-indigo-200' 
                                        : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200'"
                                    class="px-3 py-1 text-xs font-medium rounded-full transition-colors"
                                >
                                    Month
                                </button>
                            </div>
                        </div>
                        <VueApexCharts
                            height="350"
                            :options="dailyOrdersOptions"
                            :series="dailyOrdersSeries"
                        />
                    </div>

                    <!-- Product Performance Chart -->
                    <div
                        class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6"
                    >
                        <div class="flex items-center justify-between mb-6">
                            <h3
                                class="text-lg font-semibold text-gray-900 dark:text-gray-100"
                            >
                                Top Products
                            </h3>
                            <button
                                class="text-xs font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300"
                            >
                                View All
                            </button>
                        </div>
                        <VueApexCharts
                            height="300"
                            :options="productPerformanceOptions"
                            :series="productPerformanceSeries"
                        />
                    </div>
                </div>

                <!-- Charts Row 2 -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div
                        class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6"
                    >
                        <h3
                            class="text-lg font-semibold mb-6 text-gray-900 dark:text-gray-100"
                        >
                            Order Status
                        </h3>
                        <VueApexCharts
                            height="300"
                            :options="orderStatusOptions"
                            :series="orderStatusSeries"
                        />
                    </div>

                    <div
                        class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6"
                    >
                        <h3
                            class="text-lg font-semibold mb-6 text-gray-900 dark:text-gray-100"
                        >
                            Payment Status
                        </h3>
                        <VueApexCharts
                            height="300"
                            :options="paymentStatusOptions"
                            :series="paymentStatusSeries"
                        />
                    </div>
                </div>

                <div
                    class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 md:p-6 p-0"
                >
                    <OrderMap :locations="data.locations" />
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
