<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, router, Link } from '@inertiajs/vue3';
import VueApexCharts from 'vue3-apexcharts';
import {
  TrendingUp,
  TrendingDown,
  DollarSign,
  ShoppingCart,
  Users,
  Package,
  Calendar,
  Download,
  Filter,
  BarChart3,
  PieChart,
  ArrowUpRight,
  ArrowDownRight,
  Eye,
  RefreshCw,
  Wallet,
  Target,
  Award,
  Zap,
  FileText,
  Printer
} from 'lucide-vue-next';
import { toast } from "@steveyuowo/vue-hot-toast";

const props = defineProps({
  summary: Object,
  monthly_trends: Array,
  daily_revenue: Array,
  top_products: Array,
  top_customers: Array,
  payment_breakdown: Array,
  status_breakdown: Array,
  location_breakdown: Array,
  filters: Object,
});

// Loading state
const isLoading = ref(false);

// Filter state
const startDate = ref(props.filters?.start_date || '2020-01-01');
const endDate = ref(props.filters?.end_date || new Date().toISOString().split('T')[0]);
const activeTab = ref('products');
const activeQuickFilter = ref('all_time');

// Safe access to summary with defaults
const summary = computed(() => ({
  total_revenue: props.summary?.total_revenue || 0,
  net_profit: props.summary?.net_profit || 0,
  profit_margin: props.summary?.profit_margin || 0,
  total_orders: props.summary?.total_orders || 0,
  avg_order_value: props.summary?.avg_order_value || 0,
  growth_rate: props.summary?.growth_rate || 0,
  product_cost: props.summary?.product_cost || 0,
  shipping_cost: props.summary?.shipping_cost || 0,
  discount_cost: props.summary?.discount_cost || 0,
}));

// Quick date filters
const quickFilters = [
  { label: 'Today', value: 'today', icon: Zap },
  { label: 'Yesterday', value: 'yesterday', icon: Calendar },
  { label: 'Last 7 Days', value: 'last_7_days', icon: Calendar },
  { label: 'Last 30 Days', value: 'last_30_days', icon: Calendar },
  { label: 'This Month', value: 'this_month', icon: Calendar },
  { label: 'Last Month', value: 'last_month', icon: Calendar },
  { label: 'All Time', value: 'all_time', icon: Target },
];

// Apply quick filter
const applyQuickFilter = (filter) => {
  const today = new Date();
  let start, end;
  activeQuickFilter.value = filter;

  switch (filter) {
    case 'today':
      start = end = today.toISOString().split('T')[0];
      break;
    case 'yesterday':
      const yesterday = new Date(today);
      yesterday.setDate(yesterday.getDate() - 1);
      start = end = yesterday.toISOString().split('T')[0];
      break;
    case 'last_7_days':
      const week = new Date(today);
      week.setDate(week.getDate() - 7);
      start = week.toISOString().split('T')[0];
      end = today.toISOString().split('T')[0];
      break;
    case 'last_30_days':
      const month = new Date(today);
      month.setDate(month.getDate() - 30);
      start = month.toISOString().split('T')[0];
      end = today.toISOString().split('T')[0];
      break;
    case 'this_month':
      start = new Date(today.getFullYear(), today.getMonth(), 1).toISOString().split('T')[0];
      end = today.toISOString().split('T')[0];
      break;
    case 'last_month':
      const lastMonth = new Date(today.getFullYear(), today.getMonth() - 1, 1);
      start = lastMonth.toISOString().split('T')[0];
      end = new Date(today.getFullYear(), today.getMonth(), 0).toISOString().split('T')[0];
      break;
    case 'all_time':
      start = '2020-01-01';
      end = today.toISOString().split('T')[0];
      break;
  }

  startDate.value = start;
  endDate.value = end;
  applyFilter();
};

// Apply custom date filter
const applyFilter = () => {
  isLoading.value = true;
  router.visit(route('admin.reports.revenue.dashboard'), {
    method: 'get',
    data: {
      start_date: startDate.value,
      end_date: endDate.value,
    },
    preserveState: false,
    preserveScroll: true,
    onFinish: () => {
      isLoading.value = false;
      toast.success('Data refreshed!');
    }
  });
};

// Format currency - BDT
const formatCurrency = (amount) => {
  const num = Number(amount) || 0;
  return '৳' + num.toLocaleString('en-BD', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
};

// Format number with commas
const formatNumber = (num) => {
  return (Number(num) || 0).toLocaleString('en-US');
};

// Chart configurations
const monthlyChartOptions = computed(() => ({
  chart: {
    type: 'area',
    height: 380,
    fontFamily: 'Inter, sans-serif',
    toolbar: { show: true, tools: { download: true, selection: false, zoom: false, zoomin: false, zoomout: false, pan: false, reset: false } },
    animations: { enabled: true, speed: 800, animateGradually: { enabled: true, delay: 150 } },
    dropShadow: { enabled: true, opacity: 0.1, blur: 3 }
  },
  colors: ['#3B82F6', '#10B981', '#F59E0B'],
  dataLabels: { enabled: false },
  stroke: { curve: 'smooth', width: 3 },
  fill: {
    type: 'gradient',
    gradient: { shadeIntensity: 1, opacityFrom: 0.45, opacityTo: 0.05, stops: [0, 100] }
  },
  xaxis: {
    categories: props.monthly_trends?.map(t => t.month) || [],
    labels: { style: { colors: '#64748b', fontSize: '12px' } },
    axisBorder: { show: false },
    axisTicks: { show: false }
  },
  yaxis: {
    labels: {
      style: { colors: '#64748b', fontSize: '12px' },
      formatter: (val) => formatCurrency(val)
    }
  },
  legend: { position: 'top', horizontalAlign: 'left', fontWeight: 600 },
  grid: { borderColor: '#e2e8f0', strokeDashArray: 5, padding: { left: 20 } },
  tooltip: {
    shared: true,
    intersect: false,
    y: { formatter: (val) => formatCurrency(val) },
    theme: 'light'
  }
}));

const monthlyChartSeries = computed(() => [
  { name: 'Revenue', data: props.monthly_trends?.map(t => t.revenue) || [] },
  { name: 'Profit', data: props.monthly_trends?.map(t => t.profit) || [] },
  { name: 'Cost', data: props.monthly_trends?.map(t => t.cost) || [] }
]);

// Payment Donut Chart
const paymentChartOptions = computed(() => ({
  chart: { type: 'donut', height: 320, fontFamily: 'Inter, sans-serif' },
  labels: props.payment_breakdown?.map(p => (p.method || 'Unknown').toUpperCase()) || [],
  colors: ['#3B82F6', '#10B981', '#F59E0B', '#8B5CF6', '#EC4899'],
  legend: { position: 'bottom', fontWeight: 500 },
  dataLabels: { enabled: true, formatter: (val) => val.toFixed(1) + '%', style: { fontSize: '12px' } },
  plotOptions: {
    pie: {
      donut: {
        size: '70%',
        labels: {
          show: true,
          name: { show: true, fontSize: '14px', fontWeight: 600 },
          value: { show: true, fontSize: '24px', fontWeight: 700, formatter: (val) => formatCurrency(val) },
          total: { show: true, label: 'Total', fontSize: '12px', color: '#64748b', formatter: () => formatCurrency(summary.value.total_revenue) }
        }
      }
    }
  },
  tooltip: { y: { formatter: (val) => formatCurrency(val) } }
}));

const paymentChartSeries = computed(() => props.payment_breakdown?.map(p => p.revenue) || []);

// Status Bar Chart
const statusChartOptions = computed(() => ({
  chart: { type: 'bar', height: 320, fontFamily: 'Inter, sans-serif', toolbar: { show: false } },
  colors: ['#10B981', '#3B82F6', '#F59E0B', '#EF4444', '#8B5CF6', '#6B7280'],
  plotOptions: { bar: { horizontal: false, columnWidth: '55%', borderRadius: 8, distributed: true } },
  dataLabels: { enabled: true, formatter: (val) => val, style: { fontSize: '12px', fontWeight: 600 } },
  xaxis: {
    categories: props.status_breakdown?.map(s => (s.status || '').charAt(0).toUpperCase() + (s.status || '').slice(1)) || [],
    labels: { style: { colors: '#64748b', fontSize: '11px' } }
  },
  yaxis: { labels: { style: { colors: '#64748b' } } },
  legend: { show: false },
  grid: { borderColor: '#e2e8f0', strokeDashArray: 5 },
  tooltip: { y: { formatter: (val) => val + ' orders' } }
}));

const statusChartSeries = computed(() => [{ name: 'Orders', data: props.status_breakdown?.map(s => s.orders) || [] }]);

// Export functionality
const exportReport = () => {
  toast.success('Exporting report...');
  window.open(route('admin.reports.revenue.export', { start_date: startDate.value, end_date: endDate.value }), '_blank');
};
</script>

<template>
  <Head title="Revenue Dashboard" />
  <AdminLayout>
    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50/30 to-slate-100 p-4 md:p-6 lg:p-8">

      <!-- Header Section - Amazon/Apple Style -->
      <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center mb-8 gap-4">
        <div>
          <div class="flex items-center gap-3 mb-2">
            <div class="p-3 bg-gradient-to-br from-blue-600 to-indigo-700 rounded-2xl shadow-lg shadow-blue-500/30">
              <BarChart3 class="w-7 h-7 text-white" />
            </div>
            <div>
              <h1 class="text-2xl md:text-3xl font-bold text-gray-900">Revenue Analytics</h1>
              <p class="text-sm text-gray-500">Real-time business intelligence & profit tracking</p>
            </div>
          </div>
        </div>

        <div class="flex gap-3">
          <Link
            :href="route('admin.reports.inventory.v2')"
            class="inline-flex items-center gap-2 px-5 py-2.5 bg-white border border-gray-200 text-gray-700 font-semibold rounded-xl hover:bg-gray-50 transition-all shadow-sm"
          >
            <FileText class="w-4 h-4" />
            Inventory Report
          </Link>
          <button
            @click="exportReport"
            class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-semibold rounded-xl shadow-lg shadow-blue-500/25 transition-all transform hover:scale-[1.02]"
          >
            <Download class="w-4 h-4" />
            Export
          </button>
        </div>
      </div>

      <!-- Quick Filters Bar - Modern Pill Style -->
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 mb-6">
        <div class="flex flex-wrap items-center gap-3">
          <div class="flex items-center gap-2 text-sm font-semibold text-gray-700">
            <Calendar class="w-4 h-4 text-blue-600" />
            <span>Quick Filters:</span>
          </div>

          <div class="flex flex-wrap gap-2">
            <button
              v-for="filter in quickFilters"
              :key="filter.value"
              @click="applyQuickFilter(filter.value)"
              class="px-4 py-2 rounded-full text-sm font-medium transition-all duration-200"
              :class="activeQuickFilter === filter.value
                ? 'bg-blue-600 text-white shadow-md shadow-blue-500/30'
                : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
            >
              {{ filter.label }}
            </button>
          </div>

          <div class="ml-auto flex items-center gap-2">
            <input
              v-model="startDate"
              type="date"
              class="px-3 py-2 text-sm rounded-lg border border-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
            <span class="text-gray-400">→</span>
            <input
              v-model="endDate"
              type="date"
              class="px-3 py-2 text-sm rounded-lg border border-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
            <button
              @click="applyFilter"
              :disabled="isLoading"
              class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg transition-all flex items-center gap-2 disabled:opacity-50"
            >
              <RefreshCw class="w-4 h-4" :class="{ 'animate-spin': isLoading }" />
              Apply
            </button>
          </div>
        </div>
      </div>

      <!-- KPI Cards Grid - Big Tech Style -->
      <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-5 mb-8">

        <!-- Total Revenue Card -->
        <div class="group relative overflow-hidden bg-gradient-to-br from-blue-500 via-blue-600 to-indigo-700 p-6 rounded-2xl shadow-xl hover:shadow-2xl transition-all duration-300 hover:-translate-y-1">
          <div class="absolute top-0 right-0 w-40 h-40 bg-white/10 rounded-full -mr-20 -mt-20 group-hover:scale-110 transition-transform"></div>
          <div class="absolute bottom-0 left-0 w-24 h-24 bg-white/5 rounded-full -ml-12 -mb-12"></div>
          <div class="relative">
            <div class="flex items-center justify-between mb-4">
              <div class="p-2.5 bg-white/20 rounded-xl backdrop-blur-sm">
                <DollarSign class="w-6 h-6 text-white" />
              </div>
              <span v-if="summary.growth_rate !== 0" class="flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold" :class="summary.growth_rate > 0 ? 'bg-green-400/30 text-green-100' : 'bg-red-400/30 text-red-100'">
                <ArrowUpRight v-if="summary.growth_rate > 0" class="w-3 h-3" />
                <ArrowDownRight v-else class="w-3 h-3" />
                {{ Math.abs(summary.growth_rate).toFixed(1) }}%
              </span>
            </div>
            <div class="text-white/80 text-sm font-medium uppercase tracking-wider mb-1">Total Revenue</div>
            <div class="text-3xl md:text-4xl font-black text-white mb-1">{{ formatCurrency(summary.total_revenue) }}</div>
            <div class="text-white/60 text-xs">{{ startDate }} to {{ endDate }}</div>
          </div>
        </div>

        <!-- Net Profit Card -->
        <div class="group relative overflow-hidden bg-gradient-to-br from-emerald-500 via-green-600 to-teal-700 p-6 rounded-2xl shadow-xl hover:shadow-2xl transition-all duration-300 hover:-translate-y-1">
          <div class="absolute top-0 right-0 w-40 h-40 bg-white/10 rounded-full -mr-20 -mt-20 group-hover:scale-110 transition-transform"></div>
          <div class="relative">
            <div class="flex items-center justify-between mb-4">
              <div class="p-2.5 bg-white/20 rounded-xl backdrop-blur-sm">
                <TrendingUp class="w-6 h-6 text-white" />
              </div>
              <span class="px-2.5 py-1 bg-white/20 rounded-full text-xs font-bold text-white backdrop-blur-sm">
                {{ summary.profit_margin.toFixed(1) }}% margin
              </span>
            </div>
            <div class="text-white/80 text-sm font-medium uppercase tracking-wider mb-1">Net Profit</div>
            <div class="text-3xl md:text-4xl font-black text-white mb-1">{{ formatCurrency(summary.net_profit) }}</div>
            <div class="text-white/60 text-xs">After costs & shipping</div>
          </div>
        </div>

        <!-- Total Orders Card -->
        <div class="group relative overflow-hidden bg-gradient-to-br from-violet-500 via-purple-600 to-fuchsia-700 p-6 rounded-2xl shadow-xl hover:shadow-2xl transition-all duration-300 hover:-translate-y-1">
          <div class="absolute top-0 right-0 w-40 h-40 bg-white/10 rounded-full -mr-20 -mt-20 group-hover:scale-110 transition-transform"></div>
          <div class="relative">
            <div class="flex items-center justify-between mb-4">
              <div class="p-2.5 bg-white/20 rounded-xl backdrop-blur-sm">
                <ShoppingCart class="w-6 h-6 text-white" />
              </div>
            </div>
            <div class="text-white/80 text-sm font-medium uppercase tracking-wider mb-1">Total Orders</div>
            <div class="text-3xl md:text-4xl font-black text-white mb-1">{{ formatNumber(summary.total_orders) }}</div>
            <div class="text-white/60 text-xs">Orders processed</div>
          </div>
        </div>

        <!-- Avg Order Value Card -->
        <div class="group relative overflow-hidden bg-gradient-to-br from-amber-500 via-orange-500 to-red-600 p-6 rounded-2xl shadow-xl hover:shadow-2xl transition-all duration-300 hover:-translate-y-1">
          <div class="absolute top-0 right-0 w-40 h-40 bg-white/10 rounded-full -mr-20 -mt-20 group-hover:scale-110 transition-transform"></div>
          <div class="relative">
            <div class="flex items-center justify-between mb-4">
              <div class="p-2.5 bg-white/20 rounded-xl backdrop-blur-sm">
                <Target class="w-6 h-6 text-white" />
              </div>
            </div>
            <div class="text-white/80 text-sm font-medium uppercase tracking-wider mb-1">Avg Order Value</div>
            <div class="text-3xl md:text-4xl font-black text-white mb-1">{{ formatCurrency(summary.avg_order_value) }}</div>
            <div class="text-white/60 text-xs">Per order</div>
          </div>
        </div>
      </div>

      <!-- Cost Breakdown Section -->
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-8">
        <h3 class="text-lg font-bold text-gray-900 mb-6 flex items-center gap-2">
          <Wallet class="w-5 h-5 text-blue-600" />
          Profit & Cost Analysis
        </h3>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-6">
          <div class="text-center p-4 rounded-xl bg-blue-50 border border-blue-100">
            <div class="text-2xl md:text-3xl font-bold text-blue-600">{{ formatCurrency(summary.total_revenue) }}</div>
            <div class="text-sm text-gray-600 mt-1">Revenue</div>
          </div>
          <div class="text-center p-4 rounded-xl bg-red-50 border border-red-100">
            <div class="text-2xl md:text-3xl font-bold text-red-600">-{{ formatCurrency(summary.product_cost) }}</div>
            <div class="text-sm text-gray-600 mt-1">Product Cost</div>
          </div>
          <div class="text-center p-4 rounded-xl bg-amber-50 border border-amber-100">
            <div class="text-2xl md:text-3xl font-bold text-amber-600">-{{ formatCurrency(summary.shipping_cost) }}</div>
            <div class="text-sm text-gray-600 mt-1">Shipping</div>
          </div>
          <div class="text-center p-4 rounded-xl bg-green-50 border border-green-100">
            <div class="text-2xl md:text-3xl font-bold text-green-600">={{ formatCurrency(summary.net_profit) }}</div>
            <div class="text-sm text-gray-600 mt-1">Net Profit</div>
          </div>
        </div>

        <!-- Visual Progress Bar -->
        <div class="relative h-6 bg-gray-100 rounded-full overflow-hidden flex">
          <div
            v-if="summary.total_revenue > 0"
            class="bg-gradient-to-r from-red-400 to-red-500 h-full transition-all duration-500 flex items-center justify-center"
            :style="{ width: `${Math.min((summary.product_cost / summary.total_revenue * 100), 100)}%` }"
          >
            <span v-if="summary.product_cost / summary.total_revenue > 0.15" class="text-[10px] font-bold text-white">Cost {{ (summary.product_cost / summary.total_revenue * 100).toFixed(0) }}%</span>
          </div>
          <div
            v-if="summary.total_revenue > 0"
            class="bg-gradient-to-r from-amber-400 to-amber-500 h-full transition-all duration-500 flex items-center justify-center"
            :style="{ width: `${Math.min((summary.shipping_cost / summary.total_revenue * 100), 100)}%` }"
          >
            <span v-if="summary.shipping_cost / summary.total_revenue > 0.10" class="text-[10px] font-bold text-white">Ship {{ (summary.shipping_cost / summary.total_revenue * 100).toFixed(0) }}%</span>
          </div>
          <div
            v-if="summary.total_revenue > 0"
            class="bg-gradient-to-r from-green-400 to-emerald-500 h-full transition-all duration-500 flex items-center justify-center flex-1"
          >
            <span class="text-[10px] font-bold text-white">Profit {{ summary.profit_margin.toFixed(0) }}%</span>
          </div>
          <div v-if="summary.total_revenue === 0" class="flex-1 flex items-center justify-center text-gray-400 text-sm">
            No revenue data to display
          </div>
        </div>
      </div>

      <!-- Charts Section -->
      <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-8">
        <!-- Monthly Trends - Takes 2 columns -->
        <div class="xl:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
          <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
            <BarChart3 class="w-5 h-5 text-blue-600" />
            Monthly Revenue Trends
          </h3>
          <VueApexCharts
            v-if="monthly_trends && monthly_trends.length"
            type="area"
            height="350"
            :options="monthlyChartOptions"
            :series="monthlyChartSeries"
          />
          <div v-else class="flex items-center justify-center h-64 text-gray-400">
            <div class="text-center">
              <BarChart3 class="w-12 h-12 mx-auto mb-2 opacity-50" />
              <p>No monthly data available</p>
            </div>
          </div>
        </div>

        <!-- Payment Methods Donut -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
          <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
            <PieChart class="w-5 h-5 text-purple-600" />
            Payment Methods
          </h3>
          <VueApexCharts
            v-if="payment_breakdown && payment_breakdown.length"
            type="donut"
            height="320"
            :options="paymentChartOptions"
            :series="paymentChartSeries"
          />
          <div v-else class="flex items-center justify-center h-64 text-gray-400">
            <div class="text-center">
              <PieChart class="w-12 h-12 mx-auto mb-2 opacity-50" />
              <p>No payment data</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Order Status & Tabs Section -->
      <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-8">
        <!-- Order Status Chart -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
          <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
            <ShoppingCart class="w-5 h-5 text-green-600" />
            Order Status
          </h3>
          <VueApexCharts
            v-if="status_breakdown && status_breakdown.length"
            type="bar"
            height="300"
            :options="statusChartOptions"
            :series="statusChartSeries"
          />
          <div v-else class="flex items-center justify-center h-64 text-gray-400">
            <div class="text-center">
              <ShoppingCart class="w-12 h-12 mx-auto mb-2 opacity-50" />
              <p>No status data</p>
            </div>
          </div>
        </div>

        <!-- Top Products / Customers Tabs -->
        <div class="xl:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
          <!-- Tab Navigation -->
          <div class="flex border-b border-gray-100">
            <button
              @click="activeTab = 'products'"
              class="flex-1 px-6 py-4 text-sm font-semibold transition-all"
              :class="activeTab === 'products' ? 'text-blue-600 border-b-2 border-blue-600 bg-blue-50/50' : 'text-gray-500 hover:text-gray-700'"
            >
              <Award class="w-4 h-4 inline-block mr-2" />
              Top Products
            </button>
            <button
              @click="activeTab = 'customers'"
              class="flex-1 px-6 py-4 text-sm font-semibold transition-all"
              :class="activeTab === 'customers' ? 'text-blue-600 border-b-2 border-blue-600 bg-blue-50/50' : 'text-gray-500 hover:text-gray-700'"
            >
              <Users class="w-4 h-4 inline-block mr-2" />
              Top Customers
            </button>
          </div>

          <!-- Top Products Table -->
          <div v-show="activeTab === 'products'" class="overflow-x-auto">
            <table class="w-full">
              <thead class="bg-gray-50/80">
                <tr>
                  <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">#</th>
                  <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Product</th>
                  <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase">Sold</th>
                  <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase">Revenue</th>
                  <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase">Profit</th>
                  <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase">Margin</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <tr v-for="product in top_products" :key="product.id" class="hover:bg-gray-50/50 transition-colors">
                  <td class="px-4 py-3 text-sm font-bold text-gray-400">{{ product.rank }}</td>
                  <td class="px-4 py-3">
                    <div class="flex items-center gap-3">
                      <img v-if="product.image" :src="product.image" class="w-10 h-10 rounded-lg object-cover shadow-sm" />
                      <div class="w-10 h-10 rounded-lg bg-gray-200 flex items-center justify-center" v-else>
                        <Package class="w-5 h-5 text-gray-400" />
                      </div>
                      <span class="text-sm font-semibold text-gray-900 truncate max-w-[150px]">{{ product.name }}</span>
                    </div>
                  </td>
                  <td class="px-4 py-3 text-right text-sm font-bold text-gray-900">{{ formatNumber(product.units_sold) }}</td>
                  <td class="px-4 py-3 text-right text-sm font-bold text-blue-600">{{ formatCurrency(product.revenue) }}</td>
                  <td class="px-4 py-3 text-right text-sm font-bold text-green-600">{{ formatCurrency(product.profit) }}</td>
                  <td class="px-4 py-3 text-right">
                    <span class="px-2.5 py-1 rounded-full text-xs font-bold" :class="product.margin_percentage >= 30 ? 'bg-green-100 text-green-700' : product.margin_percentage >= 15 ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700'">
                      {{ product.margin_percentage?.toFixed(1) || 0 }}%
                    </span>
                  </td>
                </tr>
                <tr v-if="!top_products || !top_products.length">
                  <td colspan="6" class="px-4 py-12 text-center text-gray-400">
                    <Package class="w-10 h-10 mx-auto mb-2 opacity-50" />
                    <p>No products data available</p>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Top Customers Table -->
          <div v-show="activeTab === 'customers'" class="overflow-x-auto">
            <table class="w-full">
              <thead class="bg-gray-50/80">
                <tr>
                  <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">#</th>
                  <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Customer</th>
                  <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase">Orders</th>
                  <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase">Revenue</th>
                  <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase">Avg Order</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <tr v-for="customer in top_customers" :key="customer.rank" class="hover:bg-gray-50/50 transition-colors">
                  <td class="px-4 py-3 text-sm font-bold text-gray-400">{{ customer.rank }}</td>
                  <td class="px-4 py-3">
                    <div class="text-sm font-semibold text-gray-900">{{ customer.name }}</div>
                    <div class="text-xs text-gray-500">{{ customer.phone }}</div>
                  </td>
                  <td class="px-4 py-3 text-right text-sm font-bold text-gray-900">{{ customer.orders }}</td>
                  <td class="px-4 py-3 text-right text-sm font-bold text-blue-600">{{ formatCurrency(customer.revenue) }}</td>
                  <td class="px-4 py-3 text-right text-sm font-medium text-gray-600">{{ formatCurrency(customer.avg_order) }}</td>
                </tr>
                <tr v-if="!top_customers || !top_customers.length">
                  <td colspan="5" class="px-4 py-12 text-center text-gray-400">
                    <Users class="w-10 h-10 mx-auto mb-2 opacity-50" />
                    <p>No customer data available</p>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Admin Benefits Section -->
      <div class="bg-gradient-to-r from-slate-800 via-slate-900 to-slate-800 rounded-2xl p-6 text-white">
        <h3 class="text-lg font-bold mb-4 flex items-center gap-2">
          <Zap class="w-5 h-5 text-yellow-400" />
          Business Intelligence Benefits
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div class="bg-white/10 rounded-xl p-4 backdrop-blur-sm">
            <DollarSign class="w-8 h-8 text-green-400 mb-2" />
            <div class="font-semibold">Revenue Tracking</div>
            <div class="text-sm text-gray-300">Real-time sales & profit monitoring</div>
          </div>
          <div class="bg-white/10 rounded-xl p-4 backdrop-blur-sm">
            <TrendingUp class="w-8 h-8 text-blue-400 mb-2" />
            <div class="font-semibold">Growth Analysis</div>
            <div class="text-sm text-gray-300">Period comparison & growth rates</div>
          </div>
          <div class="bg-white/10 rounded-xl p-4 backdrop-blur-sm">
            <Target class="w-8 h-8 text-purple-400 mb-2" />
            <div class="font-semibold">Cost Optimization</div>
            <div class="text-sm text-gray-300">Identify profit margins & costs</div>
          </div>
          <div class="bg-white/10 rounded-xl p-4 backdrop-blur-sm">
            <Users class="w-8 h-8 text-orange-400 mb-2" />
            <div class="font-semibold">Customer Insights</div>
            <div class="text-sm text-gray-300">Top customers & buying patterns</div>
          </div>
        </div>
      </div>

    </div>
  </AdminLayout>
</template>

<style scoped>
/* Custom scrollbar for tables */
::-webkit-scrollbar {
  height: 6px;
}
::-webkit-scrollbar-track {
  background: #f1f5f9;
  border-radius: 10px;
}
::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 10px;
}
::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}
</style>
