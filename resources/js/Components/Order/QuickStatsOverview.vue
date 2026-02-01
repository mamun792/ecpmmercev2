<script setup>
/**
 * Quick Stats Overview for Admin Dashboard
 * Shows key metrics at a glance with visual indicators
 */
import { computed } from 'vue';
import {
    TrendingUp,
    TrendingDown,
    AlertTriangle,
    Clock,
    CheckCircle,
    DollarSign,
    Users,
    Package,
    Calendar,
    Zap
} from 'lucide-vue-next';

const props = defineProps({
    orders: {
        type: Array,
        default: () => []
    }
});

const formatCurrency = (amount) => {
    return new Intl.NumberFormat("en-BD", {
        style: "currency",
        currency: "BDT",
        minimumFractionDigits: 0,
    }).format(amount).replace("BDT", "৳");
};

// Calculate stats
const stats = computed(() => {
    const today = new Date();
    const yesterday = new Date(today);
    yesterday.setDate(yesterday.getDate() - 1);
    const thisWeek = new Date(today);
    thisWeek.setDate(today.getDate() - 7);

    // Today's orders
    const todayOrders = props.orders.filter(order => {
        const orderDate = new Date(order.date);
        return orderDate.toDateString() === today.toDateString();
    });

    // Yesterday's orders
    const yesterdayOrders = props.orders.filter(order => {
        const orderDate = new Date(order.date);
        return orderDate.toDateString() === yesterday.toDateString();
    });

    // This week's orders
    const weekOrders = props.orders.filter(order => {
        const orderDate = new Date(order.date);
        return orderDate >= thisWeek;
    });

    // Pending orders
    const pendingOrders = props.orders.filter(order =>
        order.status === 'pending' || order.status === 'processing'
    );

    // High value orders
    const highValueOrders = props.orders.filter(order =>
        parseFloat(order.total) > 5000
    );

    // Unpaid orders
    const unpaidOrders = props.orders.filter(order =>
        order.payment_status === 'unpaid'
    );

    // Risk orders (COD + High Value + New Customer)
    const riskOrders = props.orders.filter(order => {
        const isHighValue = parseFloat(order.total) > 10000;
        const isCOD = order.payment_method === 'cod' || !order.payment_method;
        const isNewCustomer = order.customer?.order_count === 1;
        return (isHighValue && isCOD) || isNewCustomer;
    });

    // Revenue calculation
    const todayRevenue = todayOrders.reduce((sum, order) => sum + parseFloat(order.total || 0), 0);
    const weekRevenue = weekOrders.reduce((sum, order) => sum + parseFloat(order.total || 0), 0);

    // Growth calculation
    const todayGrowth = yesterdayOrders.length > 0
        ? ((todayOrders.length - yesterdayOrders.length) / yesterdayOrders.length) * 100
        : todayOrders.length > 0 ? 100 : 0;

    return {
        todayOrders: todayOrders.length,
        weekOrders: weekOrders.length,
        pendingOrders: pendingOrders.length,
        highValueOrders: highValueOrders.length,
        unpaidOrders: unpaidOrders.length,
        riskOrders: riskOrders.length,
        todayRevenue,
        weekRevenue,
        todayGrowth,
        averageOrderValue: props.orders.length > 0
            ? props.orders.reduce((sum, order) => sum + parseFloat(order.total || 0), 0) / props.orders.length
            : 0
    };
});

const statCards = computed(() => [
    {
        title: "Today's Orders",
        value: stats.value.todayOrders,
        subtitle: stats.value.todayGrowth >= 0
            ? `+${stats.value.todayGrowth.toFixed(1)}% from yesterday`
            : `${stats.value.todayGrowth.toFixed(1)}% from yesterday`,
        icon: Calendar,
        color: 'blue',
        trend: stats.value.todayGrowth >= 0 ? 'up' : 'down',
        pulse: true
    },
    {
        title: "Today's Revenue",
        value: formatCurrency(stats.value.todayRevenue),
        subtitle: `Avg: ${formatCurrency(stats.value.averageOrderValue)}`,
        icon: DollarSign,
        color: 'green',
        trend: stats.value.todayRevenue > 0 ? 'up' : null,
        pulse: false
    },
    {
        title: "Pending Orders",
        value: stats.value.pendingOrders,
        subtitle: "Needs immediate attention",
        icon: Clock,
        color: stats.value.pendingOrders > 10 ? 'amber' : 'blue',
        trend: null,
        urgent: stats.value.pendingOrders > 10
    },
    {
        title: "High Value Orders",
        value: stats.value.highValueOrders,
        subtitle: "Orders > ৳5,000",
        icon: TrendingUp,
        color: 'purple',
        trend: 'up',
        pulse: false
    },
    {
        title: "Unpaid Orders",
        value: stats.value.unpaidOrders,
        subtitle: "Requires follow-up",
        icon: AlertTriangle,
        color: stats.value.unpaidOrders > 0 ? 'red' : 'green',
        trend: null,
        urgent: stats.value.unpaidOrders > 5
    },
    {
        title: "Risk Orders",
        value: stats.value.riskOrders,
        subtitle: "COD + High Value/New Customer",
        icon: Zap,
        color: stats.value.riskOrders > 0 ? 'orange' : 'green',
        trend: null,
        urgent: stats.value.riskOrders > 3
    }
]);

const getColorClasses = (color, urgent = false) => {
    const baseClasses = {
        blue: urgent ? 'bg-blue-600 text-white' : 'bg-blue-50 text-blue-700 border-blue-200',
        green: urgent ? 'bg-green-600 text-white' : 'bg-green-50 text-green-700 border-green-200',
        amber: urgent ? 'bg-amber-600 text-white' : 'bg-amber-50 text-amber-700 border-amber-200',
        red: urgent ? 'bg-red-600 text-white' : 'bg-red-50 text-red-700 border-red-200',
        purple: urgent ? 'bg-purple-600 text-white' : 'bg-purple-50 text-purple-700 border-purple-200',
        orange: urgent ? 'bg-orange-600 text-white' : 'bg-orange-50 text-orange-700 border-orange-200'
    };

    return baseClasses[color] || baseClasses.blue;
};

const getIconColorClasses = (color, urgent = false) => {
    const iconClasses = {
        blue: urgent ? 'text-white' : 'text-blue-600',
        green: urgent ? 'text-white' : 'text-green-600',
        amber: urgent ? 'text-white' : 'text-amber-600',
        red: urgent ? 'text-white' : 'text-red-600',
        purple: urgent ? 'text-white' : 'text-purple-600',
        orange: urgent ? 'text-white' : 'text-orange-600'
    };

    return iconClasses[color] || iconClasses.blue;
};

const getTrendIcon = (trend) => {
    return trend === 'up' ? TrendingUp : TrendingDown;
};
</script>

<template>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4 mb-6">
        <div
            v-for="(stat, index) in statCards"
            :key="stat.title"
            class="relative overflow-hidden rounded-2xl border-2 transition-all duration-300 hover:shadow-lg hover:-translate-y-1 cursor-pointer group"
            :class="[
                getColorClasses(stat.color, stat.urgent),
                stat.pulse ? 'animate-pulse' : '',
                stat.urgent ? 'ring-2 ring-red-400 ring-opacity-75 animate-pulse' : ''
            ]"
        >
            <!-- Background Pattern -->
            <div class="absolute inset-0 opacity-10">
                <div class="absolute -right-4 -top-4 w-16 h-16 rounded-full"
                     :class="stat.urgent ? 'bg-white' : `bg-${stat.color}-200`"></div>
                <div class="absolute -right-2 -bottom-2 w-12 h-12 rounded-full"
                     :class="stat.urgent ? 'bg-white' : `bg-${stat.color}-200`"></div>
            </div>

            <div class="relative p-4">
                <!-- Header -->
                <div class="flex items-center justify-between mb-3">
                    <component
                        :is="stat.icon"
                        class="w-8 h-8 transition-transform group-hover:scale-110"
                        :class="getIconColorClasses(stat.color, stat.urgent)"
                    />

                    <!-- Trend Indicator -->
                    <div v-if="stat.trend" class="flex items-center gap-1">
                        <component
                            :is="getTrendIcon(stat.trend)"
                            class="w-4 h-4"
                            :class="[
                                stat.trend === 'up' ? 'text-green-500' : 'text-red-500',
                                stat.urgent ? 'text-white' : ''
                            ]"
                        />
                    </div>
                </div>

                <!-- Value -->
                <div class="mb-1">
                    <div
                        class="text-2xl font-bold leading-tight"
                        :class="stat.urgent ? 'text-white' : `text-${stat.color}-900`"
                    >
                        {{ stat.value }}
                    </div>
                </div>

                <!-- Title -->
                <div
                    class="text-sm font-semibold mb-1"
                    :class="stat.urgent ? 'text-white opacity-90' : `text-${stat.color}-700`"
                >
                    {{ stat.title }}
                </div>

                <!-- Subtitle -->
                <div
                    class="text-xs leading-tight"
                    :class="stat.urgent ? 'text-white opacity-75' : `text-${stat.color}-600`"
                >
                    {{ stat.subtitle }}
                </div>

                <!-- Urgent Indicator -->
                <div
                    v-if="stat.urgent"
                    class="absolute top-2 right-2 w-3 h-3 bg-red-400 rounded-full animate-ping"
                ></div>
            </div>
        </div>
    </div>

    <!-- Quick Actions Bar -->
    <div class="bg-gradient-to-r from-orange-500 to-orange-600 rounded-2xl p-4 mb-6 shadow-lg">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="text-white">
                <h3 class="font-bold text-lg">Quick Admin Actions</h3>
                <p class="text-orange-100 text-sm">Manage your orders efficiently</p>
            </div>

            <div class="flex flex-wrap gap-2">
                <button
                    v-if="stats.pendingOrders > 0"
                    class="flex items-center gap-2 px-4 py-2 bg-white bg-opacity-20 text-white rounded-lg hover:bg-opacity-30 transition-all duration-200 backdrop-blur-sm"
                >
                    <Clock class="w-4 h-4" />
                    Process {{ stats.pendingOrders }} Pending
                </button>

                <button
                    v-if="stats.unpaidOrders > 0"
                    class="flex items-center gap-2 px-4 py-2 bg-white bg-opacity-20 text-white rounded-lg hover:bg-opacity-30 transition-all duration-200 backdrop-blur-sm"
                >
                    <AlertTriangle class="w-4 h-4" />
                    Follow-up {{ stats.unpaidOrders }} Unpaid
                </button>

                <button class="flex items-center gap-2 px-4 py-2 bg-white bg-opacity-20 text-white rounded-lg hover:bg-opacity-30 transition-all duration-200 backdrop-blur-sm">
                    <Package class="w-4 h-4" />
                    Bulk Ship Orders
                </button>

                <button class="flex items-center gap-2 px-4 py-2 bg-white bg-opacity-20 text-white rounded-lg hover:bg-opacity-30 transition-all duration-200 backdrop-blur-sm">
                    <Users class="w-4 h-4" />
                    Customer Reports
                </button>
            </div>
        </div>
    </div>
</template>
