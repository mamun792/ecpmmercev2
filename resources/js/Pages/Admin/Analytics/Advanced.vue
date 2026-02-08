<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import VueApexCharts from 'vue3-apexcharts';
import { TrendingUp, TrendingDown, Clock, Users, Package, DollarSign, AlertTriangle, Award, Target, Zap } from 'lucide-vue-next';

const props = defineProps({
    hourlySales: Array,
    dayOfWeekAnalysis: Array,
    monthOverMonth: Array,
    salesVelocity: Array,
    customerRetention: Object,
    customerLifetimeValue: Object,
    customerSegmentation: Array,
    revenueByCategory: Array,
    lowStockAlerts: Array,
    profitMarginAnalysis: Array,
    paymentMethodDistribution: Array,
    refundRate: Object,
    fulfillmentTime: Object,
    courierSuccessRate: Array,
    pendingOrderAge: Array,
    couponUsage: Array,
    liveOrders: Array,
    todayGoalProgress: Object,
    todayLeaderboard: Array,
});

// Format currency
const formatCurrency = (amount) => {
    return new Intl.NumberFormat('bn-BD', {
        style: 'currency',
        currency: 'BDT',
        minimumFractionDigits: 0,
    }).format(amount);
};

// Hourly Sales Chart
const hourlySalesChart = computed(() => ({
    series: [{
        name: 'Orders',
        data: props.hourlySales?.map(h => h.orders) || []
    }, {
        name: 'Revenue',
        data: props.hourlySales?.map(h => h.revenue) || []
    }],
    options: {
        chart: { type: 'bar', height: 350 },
        xaxis: { categories: props.hourlySales?.map(h => h.hour) || [] },
        colors: ['#3B82F6', '#10B981'],
        dataLabels: { enabled: false },
    }
}));

// Day of Week Chart
const dayOfWeekChart = computed(() => ({
    series: [{
        name: 'Revenue',
        data: props.dayOfWeekAnalysis?.map(d => d.revenue) || []
    }],
    options: {
        chart: { type: 'bar', height: 350 },
        xaxis: { categories: props.dayOfWeekAnalysis?.map(d => d.day) || [] },
        colors: ['#8B5CF6'],
        plotOptions: { bar: { borderRadius: 4, horizontal: false } },
    }
}));

// Month over Month Chart
const monthOverMonthChart = computed(() => ({
    series: [{
        name: 'Revenue',
        data: props.monthOverMonth?.map(m => m.revenue) || []
    }],
    options: {
        chart: { type: 'line', height: 350 },
        xaxis: { categories: props.monthOverMonth?.map(m => m.month) || [] },
        colors: ['#F59E0B'],
        stroke: { curve: 'smooth', width: 3 },
    }
}));

// Revenue by Category Chart
const revenueByCategoryChart = computed(() => ({
    series: props.revenueByCategory?.map(c => c.revenue) || [],
    options: {
        chart: { type: 'donut', height: 350 },
        labels: props.revenueByCategory?.map(c => c.category) || [],
        colors: ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#EC4899'],
    }
}));

// Payment Method Chart
const paymentMethodChart = computed(() => ({
    series: props.paymentMethodDistribution?.map(p => p.count) || [],
    options: {
        chart: { type: 'pie', height: 300 },
        labels: props.paymentMethodDistribution?.map(p => p.method) || [],
        colors: ['#10B981', '#3B82F6', '#F59E0B'],
    }
}));

// Progress bar color
const getProgressColor = (percentage) => {
    if (percentage >= 100) return 'bg-green-500';
    if (percentage >= 75) return 'bg-blue-500';
    if (percentage >= 50) return 'bg-yellow-500';
    return 'bg-red-500';
};

// Alert badge color
const getAlertColor = (level) => {
    const colors = {
        critical: 'bg-red-100 text-red-700',
        warning: 'bg-yellow-100 text-yellow-700',
        low: 'bg-orange-100 text-orange-700',
        normal: 'bg-gray-100 text-gray-700',
    };
    return colors[level] || colors.normal;
};
</script>

<template>
    <Head title="Advanced Analytics" />

    <AdminLayout>
        <div class="p-6 space-y-8">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">🔬 Advanced Analytics</h1>
                    <p class="text-gray-600 dark:text-gray-400 mt-1">Deep insights and performance metrics</p>
                </div>
            </div>

            <!-- Real-time Dashboard Section -->
            <section class="bg-gradient-to-r from-blue-50 to-purple-50 dark:from-blue-900/20 dark:to-purple-900/20 rounded-xl p-6">
                <h2 class="text-2xl font-bold mb-6 flex items-center gap-2">
                    <Zap class="w-6 h-6 text-yellow-500" />
                    Real-Time Dashboard
                </h2>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Today's Goal Progress -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow">
                        <h3 class="text-lg font-semibold mb-4 flex items-center gap-2">
                            <Target class="w-5 h-5 text-green-500" />
                            Today's Goal
                        </h3>
                        <div class="text-center">
                            <div class="text-4xl font-bold text-gray-900 dark:text-white mb-2">
                                {{ Math.round(todayGoalProgress?.daily_progress || 0) }}%
                            </div>
                            <p class="text-sm text-gray-600 mb-4">
                                {{ formatCurrency(todayGoalProgress?.daily_revenue || 0) }} / {{ formatCurrency(todayGoalProgress?.daily_goal || 0) }}
                            </p>
                            <div class="w-full bg-gray-200 rounded-full h-3">
                                <div
                                    :class="getProgressColor(todayGoalProgress?.daily_progress || 0)"
                                    class="h-3 rounded-full transition-all duration-500"
                                    :style="{width: Math.min(todayGoalProgress?.daily_progress || 0, 100) + '%'}"
                                ></div>
                            </div>
                        </div>
                    </div>

                    <!-- Today's Leaderboard -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow">
                        <h3 class="text-lg font-semibold mb-4 flex items-center gap-2">
                            <Award class="w-5 h-5 text-yellow-500" />
                            Top Sellers Today
                        </h3>
                        <div class="space-y-2">
                            <div v-for="(product, index) in todayLeaderboard?.slice(0, 3)" :key="index" class="flex items-center gap-3">
                                <div class="text-2xl font-bold" :class="index === 0 ? 'text-yellow-500' : index === 1 ? 'text-gray-400' : 'text-orange-600'">
                                    {{ index + 1 }}
                                </div>
                                <div class="flex-1">
                                    <p class="font-medium text-sm">{{ product.name }}</p>
                                    <p class="text-xs text-gray-500">{{ product.sold_today }} units - {{ formatCurrency(product.revenue_today) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Live Orders Feed -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow">
                        <h3 class="text-lg font-semibold mb-4 flex items-center gap-2">
                            <Clock class="w-5 h-5 text-blue-500" />
                            Live Orders
                        </h3>
                        <div class="space-y-2 max-h-48 overflow-y-auto">
                            <div v-for="order in liveOrders?.slice(0, 5)" :key="order.order_number" class="text-sm border-l-2 border-blue-500 pl-3 py-1">
                                <p class="font-medium">{{ order.order_number }}</p>
                                <p class="text-xs text-gray-500">{{ formatCurrency(order.total) }} • {{ order.time }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Sales Performance Analytics -->
            <section>
                <h2 class="text-2xl font-bold mb-6 flex items-center gap-2">
                    <TrendingUp class="w-6 h-6 text-green-500" />
                    Sales Performance Analytics
                </h2>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Hourly Sales Pattern -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow">
                        <h3 class="text-lg font-semibold mb-4">Hourly Sales Pattern</h3>
                        <VueApexCharts v-if="hourlySales?.length" type="bar" height="300" :options="hourlySalesChart.options" :series="hourlySalesChart.series" />
                        <p v-else class="text-gray-500 text-center py-8">No data available</p>
                    </div>

                    <!-- Day of Week Analysis -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow">
                        <h3 class="text-lg font-semibold mb-4">Day of Week Performance</h3>
                        <VueApexCharts v-if="dayOfWeekAnalysis?.length" type="bar" height="300" :options="dayOfWeekChart.options" :series="dayOfWeekChart.series" />
                        <p v-else class="text-gray-500 text-center py-8">No data available</p>
                    </div>

                    <!-- Month over Month Growth -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow lg:col-span-2">
                        <h3 class="text-lg font-semibold mb-4">Month-over-Month Growth</h3>
                        <VueApexCharts v-if="monthOverMonth?.length" type="line" height="300" :options="monthOverMonthChart.options" :series="monthOverMonthChart.series" />
                        <p v-else class="text-gray-500 text-center py-8">No data available</p>
                    </div>

                    <!-- Sales Velocity -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow lg:col-span-2">
                        <h3 class="text-lg font-semibold mb-4">Sales Velocity (Units/Day)</h3>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="border-b">
                                        <th class="text-left py-2">Product</th>
                                        <th class="text-right py-2">Total Sold</th>
                                        <th class="text-right py-2">Velocity</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="product in salesVelocity" :key="product.name" class="border-b">
                                        <td class="py-2">{{ product.name }}</td>
                                        <td class="text-right">{{ product.total_sold }}</td>
                                        <td class="text-right font-semibold text-green-600">{{ product.velocity }} units/day</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Customer Insights -->
            <section>
                <h2 class="text-2xl font-bold mb-6 flex items-center gap-2">
                    <Users class="w-6 h-6 text-blue-500" />
                    Customer Insights
                </h2>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                    <!-- Retention Stats -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow">
                        <h3 class="text-lg font-semibold mb-4">Customer Retention</h3>
                        <div class="space-y-3">
                            <div>
                                <p class="text-sm text-gray-600">Retention Rate</p>
                                <p class="text-3xl font-bold text-green-600">{{ customerRetention?.retention_rate }}%</p>
                            </div>
                            <div class="flex justify-between text-sm">
                                <div>
                                    <p class="text-gray-600">New</p>
                                    <p class="font-semibold">{{ customerRetention?.new }}</p>
                                </div>
                                <div>
                                    <p class="text-gray-600">Returning</p>
                                    <p class="font-semibold">{{ customerRetention?.returning }}</p>
                                </div>
                                <div>
                                    <p class="text-gray-600">Total</p>
                                    <p class="font-semibold">{{ customerRetention?.total }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- CLV Stats -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow">
                        <h3 class="text-lg font-semibold mb-4">Customer Lifetime Value</h3>
                        <div class="space-y-3">
                            <div>
                                <p class="text-sm text-gray-600">Average CLV</p>
                                <p class="text-3xl font-bold text-purple-600">{{ formatCurrency(customerLifetimeValue?.clv) }}</p>
                            </div>
                            <div class="text-sm">
                                <p class="text-gray-600">Avg Order Value: {{ formatCurrency(customerLifetimeValue?.avg_order_value) }}</p>
                                <p class="text-gray-600">Avg Orders/Customer: {{ customerLifetimeValue?.avg_orders_per_customer }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Refund Rate -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow">
                        <h3 class="text-lg font-semibold mb-4">Success & Refund Rate</h3>
                        <div class="space-y-3">
                            <div>
                                <p class="text-sm text-gray-600">Success Rate</p>
                                <p class="text-3xl font-bold text-green-600">{{ refundRate?.success_rate }}%</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Refund Rate</p>
                                <p class="text-2xl font-bold text-red-600">{{ refundRate?.refund_rate }}%</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Top Customers -->
                <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow">
                    <h3 class="text-lg font-semibold mb-4">Top 20% Customers (Pareto Principle)</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b">
                                    <th class="text-left py-2">Customer</th>
                                    <th class="text-right py-2">Orders</th>
                                    <th class="text-right py-2">Total Spent</th>
                                    <th class="text-right py-2">Revenue %</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="customer in customerSegmentation?.slice(0, 10)" :key="customer.phone" class="border-b">
                                    <td class="py-2">{{ customer.name }}</td>
                                    <td class="text-right">{{ customer.orders }}</td>
                                    <td class="text-right">{{ formatCurrency(customer.total_spent) }}</td>
                                    <td class="text-right font-semibold text-green-600">{{ customer.revenue_percentage }}%</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- Product Analytics -->
            <section>
                <h2 class="text-2xl font-bold mb-6 flex items-center gap-2">
                    <Package class="w-6 h-6 text-purple-500" />
                    Product Analytics
                </h2>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Revenue by Category -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow">
                        <h3 class="text-lg font-semibold mb-4">Revenue by Category</h3>
                        <VueApexCharts v-if="revenueByCategory?.length" type="donut" height="300" :options="revenueByCategoryChart.options" :series="revenueByCategoryChart.series" />
                        <p v-else class="text-gray-500 text-center py-8">No data available</p>
                    </div>

                    <!-- Low Stock Alerts -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow">
                        <h3 class="text-lg font-semibold mb-4 flex items-center gap-2">
                            <AlertTriangle class="w-5 h-5 text-red-500" />
                            Low Stock Alerts
                        </h3>
                        <div class="space-y-2 max-h-72 overflow-y-auto">
                            <div v-for="product in lowStockAlerts" :key="product.name" class="flex items-center justify-between p-2 rounded" :class="getAlertColor(product.alert_level)">
                                <div>
                                    <p class="font-medium text-sm">{{ product.name }}</p>
                                    <p class="text-xs">Stock: {{ product.stock }} • Velocity: {{ product.daily_velocity }}/day</p>
                                </div>
                                <span class="text-xs font-bold">{{ product.days_remaining }} days</span>
                            </div>
                        </div>
                    </div>

                    <!-- Profit Margin Analysis -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow lg:col-span-2">
                        <h3 class="text-lg font-semibold mb-4">Profit Margin Analysis</h3>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="border-b">
                                        <th class="text-left py-2">Product</th>
                                        <th class="text-right py-2">Units Sold</th>
                                        <th class="text-right py-2">Revenue</th>
                                        <th class="text-right py-2">Cost</th>
                                        <th class="text-right py-2">Profit</th>
                                        <th class="text-right py-2">Margin %</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="product in profitMarginAnalysis" :key="product.name" class="border-b">
                                        <td class="py-2">{{ product.name }}</td>
                                        <td class="text-right">{{ product.units_sold }}</td>
                                        <td class="text-right">{{ formatCurrency(product.revenue) }}</td>
                                        <td class="text-right">{{ formatCurrency(product.cost) }}</td>
                                        <td class="text-right font-semibold text-green-600">{{ formatCurrency(product.profit) }}</td>
                                        <td class="text-right font-bold" :class="product.margin_percentage > 30 ? 'text-green-600' : 'text-yellow-600'">{{ product.margin_percentage }}%</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Operational & Financial Metrics -->
            <section>
                <h2 class="text-2xl font-bold mb-6 flex items-center gap-2">
                    <DollarSign class="w-6 h-6 text-green-500" />
                    Operational & Financial Metrics
                </h2>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Fulfillment Time -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow">
                        <h3 class="text-lg font-semibold mb-4">Order Fulfillment Time</h3>
                        <div class="grid grid-cols-3 gap-4 text-center">
                            <div>
                                <p class="text-sm text-gray-600">Average</p>
                                <p class="text-2xl font-bold text-blue-600">{{ fulfillmentTime?.avg_days }}d</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Fastest</p>
                                <p class="text-2xl font-bold text-green-600">{{ fulfillmentTime?.min_hours }}h</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Slowest</p>
                                <p class="text-2xl font-bold text-red-600">{{ fulfillmentTime?.max_hours }}h</p>
                            </div>
                        </div>
                    </div>

                    <!-- Courier Success Rate -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow">
                        <h3 class="text-lg font-semibold mb-4">Courier Success Rate</h3>
                        <div class="space-y-3">
                            <div v-for="courier in courierSuccessRate" :key="courier.courier" class="flex items-center justify-between">
                                <span class="font-medium">{{ courier.courier }}</span>
                                <div class="flex items-center gap-2">
                                    <span class="text-sm text-gray-600">{{ courier.delivered }}/{{ courier.total }}</span>
                                    <span class="font-bold" :class="courier.success_rate > 90 ? 'text-green-600' : 'text-yellow-600'">{{ courier.success_rate }}%</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Method Distribution -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow">
                        <h3 class="text-lg font-semibold mb-4">Payment Method Distribution</h3>
                        <VueApexCharts v-if="paymentMethodDistribution?.length" type="pie" height="250" :options="paymentMethodChart.options" :series="paymentMethodChart.series" />
                        <p v-else class="text-gray-500 text-center py-8">No data available</p>
                    </div>

                    <!-- Pending Orders Age -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow">
                        <h3 class="text-lg font-semibold mb-4">Pending Orders Age</h3>
                        <div class="space-y-2 max-h-60 overflow-y-auto">
                            <div v-for="order in pendingOrderAge" :key="order.order_number" class="flex items-center justify-between p-2 rounded" :class="getAlertColor(order.alert)">
                                <div>
                                    <p class="font-medium text-sm">{{ order.order_number }}</p>
                                    <p class="text-xs">{{ order.customer }} • {{ formatCurrency(order.total) }}</p>
                                </div>
                                <span class="text-xs font-bold">{{ order.age_days }}d</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Marketing Analytics -->
            <section v-if="couponUsage?.length">
                <h2 class="text-2xl font-bold mb-6">💰 Marketing Analytics</h2>

                <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow">
                    <h3 class="text-lg font-semibold mb-4">Coupon Usage Statistics</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b">
                                    <th class="text-left py-2">Coupon Code</th>
                                    <th class="text-right py-2">Usage</th>
                                    <th class="text-right py-2">Total Discount</th>
                                    <th class="text-right py-2">Revenue Generated</th>
                                    <th class="text-right py-2">Avg Order</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="coupon in couponUsage" :key="coupon.coupon" class="border-b">
                                    <td class="py-2 font-mono font-bold">{{ coupon.coupon }}</td>
                                    <td class="text-right">{{ coupon.usage_count }}</td>
                                    <td class="text-right text-red-600">-{{ formatCurrency(coupon.total_discount) }}</td>
                                    <td class="text-right text-green-600">{{ formatCurrency(coupon.revenue_generated) }}</td>
                                    <td class="text-right">{{ formatCurrency(coupon.avg_order_value) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </div>
    </AdminLayout>
</template>
