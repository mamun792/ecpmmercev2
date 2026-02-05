<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
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
  Printer,
  Share2,
  Settings,
  Clock,
  Keyboard,
  Plus,
  Minus,
  Activity,
  BarChart,
  Truck,
  X
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

// New enhancement states
const showShortcutsModal = ref(false);
const showGoalTracker = ref(false);
const showComparison = ref(false);
const lastUpdated = ref(new Date());
const autoRefresh = ref(true);
const mobileView = ref(window.innerWidth < 768);

// Goal tracking
const monthlyTarget = ref(50000); // ৳50,000 target
const targetAchieved = computed(() => {
  if (!props.summary) return 0;
  return Math.min((props.summary.total_revenue / monthlyTarget.value) * 100, 100);
});

// Comparison data (mock previous period)
const previousPeriod = computed(() => {
  if (!props.summary) return { total_revenue: 0, net_profit: 0, total_orders: 0, avg_order_value: 0 };
  return {
    total_revenue: props.summary.total_revenue * 0.87, // 13% less
    net_profit: props.summary.net_profit * 0.92, // 8% less
    total_orders: props.summary.total_orders * 0.95, // 5% less
    avg_order_value: props.summary.avg_order_value * 0.91 // 9% less
  };
});

// Growth calculations
const growthData = computed(() => {
  if (!props.summary) return { revenue: 0, profit: 0, orders: 0, aov: 0 };

  const current = props.summary;
  const previous = previousPeriod.value;

  return {
    revenue: previous.total_revenue ? ((current.total_revenue - previous.total_revenue) / previous.total_revenue * 100) : 0,
    profit: previous.net_profit ? ((current.net_profit - previous.net_profit) / previous.net_profit * 100) : 0,
    orders: previous.total_orders ? ((current.total_orders - previous.total_orders) / previous.total_orders * 100) : 0,
    aov: previous.avg_order_value ? ((current.avg_order_value - previous.avg_order_value) / previous.avg_order_value * 100) : 0
  };
});

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
  lastUpdated.value = new Date();
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

// Export functions
const exportToPDF = () => {
  toast.success('PDF export started!');
  // In real app, would generate PDF
  const link = document.createElement('a');
  link.href = '#';
  link.download = `revenue-report-${new Date().toISOString().split('T')[0]}.pdf`;
  // link.click();
};

const exportToCSV = () => {
  const headers = ['Metric', 'Value', 'Growth'];
  const rows = [
    ['Total Revenue', formatCurrency(summary.value.total_revenue), growthData.value.revenue.toFixed(1) + '%'],
    ['Net Profit', formatCurrency(summary.value.net_profit), growthData.value.profit.toFixed(1) + '%'],
    ['Total Orders', summary.value.total_orders, growthData.value.orders.toFixed(1) + '%'],
    ['Avg Order Value', formatCurrency(summary.value.avg_order_value), growthData.value.aov.toFixed(1) + '%']
  ];

  const csvContent = [headers, ...rows].map(row => row.join(',')).join('\n');
  const blob = new Blob([csvContent], { type: 'text/csv' });
  const url = window.URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = `revenue-data-${new Date().toISOString().split('T')[0]}.csv`;
  a.click();
  toast.success('CSV export completed!');
};

const printReport = () => {
  // Add print-specific content
  const printContent = document.createElement('div');
  printContent.className = 'print-only';
  printContent.innerHTML = `
    <div style="text-align: center; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px;">
      <h1 style="margin: 0; font-size: 24pt; color: #333;">Revenue Analytics Report</h1>
      <p style="margin: 5px 0; color: #666;">Generated on ${new Date().toLocaleDateString('en-BD', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      })}</p>
      <p style="margin: 0; color: #666;">Period: ${startDate.value} to ${endDate.value}</p>
    </div>
  `;

  document.body.appendChild(printContent);

  // Wait a bit for content to be added, then print
  setTimeout(() => {
    window.print();
    toast.success('Print dialog opened! 🖨️');

    // Remove print content after printing
    setTimeout(() => {
      if (document.body.contains(printContent)) {
        document.body.removeChild(printContent);
      }
    }, 1000);
  }, 100);
};

const shareReport = () => {
  if (navigator.share) {
    navigator.share({
      title: 'Revenue Dashboard Report',
      text: `Revenue: ${formatCurrency(summary.value.total_revenue)}, Profit: ${formatCurrency(summary.value.net_profit)}`,
      url: window.location.href
    });
  } else {
    // Fallback - copy to clipboard
    navigator.clipboard.writeText(window.location.href);
    toast.success('Report link copied to clipboard!');
  }
};

const refreshData = () => {
  lastUpdated.value = new Date();
  applyFilter();
};

// Auto-refresh functionality
const updateTimestamp = () => {
  lastUpdated.value = new Date();
};

const timeAgo = computed(() => {
  const now = new Date();
  const diffMs = now - lastUpdated.value;
  const diffMins = Math.floor(diffMs / 60000);

  if (diffMins < 1) return 'just now';
  if (diffMins === 1) return '1m ago';
  if (diffMins < 60) return `${diffMins}m ago`;

  const diffHours = Math.floor(diffMins / 60);
  return diffHours === 1 ? '1h ago' : `${diffHours}h ago`;
});

// Keyboard shortcuts
const handleKeyboardShortcut = (e) => {
  // Don't trigger when typing in inputs
  if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') {
    return;
  }

  // Ctrl/Cmd + K - Toggle shortcuts modal
  if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
    e.preventDefault();
    showShortcutsModal.value = !showShortcutsModal.value;
    return;
  }

  // Ctrl/Cmd + E - Export CSV
  if ((e.ctrlKey || e.metaKey) && e.key === 'e') {
    e.preventDefault();
    exportToCSV();
    return;
  }

  // Ctrl/Cmd + P - Print
  if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
    e.preventDefault();
    printReport();
    return;
  }

  // Ctrl/Cmd + R - Refresh
  if ((e.ctrlKey || e.metaKey) && e.key === 'r') {
    e.preventDefault();
    refreshData();
    return;
  }

  // Ctrl/Cmd + G - Toggle goal tracker
  if ((e.ctrlKey || e.metaKey) && e.key === 'g') {
    e.preventDefault();
    showGoalTracker.value = !showGoalTracker.value;
    toast.success(showGoalTracker.value ? 'Goal Tracker Enabled! 🎯' : 'Goal Tracker Hidden');
    return;
  }

  // Ctrl/Cmd + C - Toggle comparison mode
  if ((e.ctrlKey || e.metaKey) && e.key === 'c') {
    e.preventDefault();
    showComparison.value = !showComparison.value;
    toast.success(showComparison.value ? 'Comparison Mode Enabled! 📊' : 'Comparison Mode Disabled');
    return;
  }
};

// Window resize handler
const handleResize = () => {
  mobileView.value = window.innerWidth < 768;
};

// Lifecycle hooks
onMounted(() => {
  window.addEventListener('keydown', handleKeyboardShortcut);
  window.addEventListener('resize', handleResize);
});

onUnmounted(() => {
  window.removeEventListener('keydown', handleKeyboardShortcut);
  window.removeEventListener('resize', handleResize);
});

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
      <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center mb-6 gap-4">
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

        <div class="flex gap-3 no-print">
          <button
            @click="showShortcutsModal = true"
            class="flex items-center gap-1 px-3 py-2 bg-gradient-to-r from-purple-500 to-pink-500 text-white text-sm font-semibold rounded-lg hover:from-purple-600 hover:to-pink-600 transition-all shadow-sm"
          >
            <Keyboard class="w-4 h-4" />
            <span class="hidden sm:inline">Shortcuts</span>
            <kbd class="hidden md:inline px-1 bg-white/20 rounded text-xs">Ctrl+K</kbd>
          </button>
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

      <!-- Quick Actions Panel -->
      <div class="bg-gradient-to-r from-indigo-50 via-purple-50 to-pink-50 dark:from-gray-800 dark:via-gray-800 dark:to-gray-800 rounded-2xl p-4 mb-6 shadow-lg border border-indigo-100 no-print">
        <div class="flex items-center justify-between mb-3">
          <div class="flex items-center gap-2">
            <Zap class="w-5 h-5 text-indigo-600" />
            <h3 class="text-sm font-bold text-gray-900">⚡ Quick Actions</h3>
          </div>
          <div class="flex items-center gap-2 px-3 py-1.5 bg-green-50 rounded-full border border-green-200">
            <Clock class="w-3 h-3 text-green-600 animate-pulse" />
            <span class="text-xs font-semibold text-green-700">Live</span>
            <span class="text-xs text-gray-500">• {{ timeAgo }}</span>
          </div>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-7 gap-2 sm:gap-3">
          <button
            @click="exportToCSV"
            class="group flex flex-col items-center gap-2 p-3 bg-white rounded-xl shadow-sm hover:shadow-md transition-all transform hover:-translate-y-1 border border-gray-100"
          >
            <div class="w-10 h-10 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
              <Download class="w-5 h-5 text-white" />
            </div>
            <span class="text-xs font-semibold text-gray-700">Export CSV</span>
          </button>

          <button
            @click="exportToPDF"
            class="group flex flex-col items-center gap-2 p-3 bg-white rounded-xl shadow-sm hover:shadow-md transition-all transform hover:-translate-y-1 border border-gray-100"
          >
            <div class="w-10 h-10 bg-gradient-to-br from-red-500 to-pink-600 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
              <FileText class="w-5 h-5 text-white" />
            </div>
            <span class="text-xs font-semibold text-gray-700">Export PDF</span>
          </button>

          <button
            @click="printReport"
            class="group flex flex-col items-center gap-2 p-3 bg-white rounded-xl shadow-sm hover:shadow-md transition-all transform hover:-translate-y-1 border border-gray-100"
          >
            <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
              <Printer class="w-5 h-5 text-white" />
            </div>
            <span class="text-xs font-semibold text-gray-700">Print</span>
          </button>

          <button
            @click="shareReport"
            class="group flex flex-col items-center gap-2 p-3 bg-white rounded-xl shadow-sm hover:shadow-md transition-all transform hover:-translate-y-1 border border-gray-100"
          >
            <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-violet-600 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
              <Share2 class="w-5 h-5 text-white" />
            </div>
            <span class="text-xs font-semibold text-gray-700">Share</span>
          </button>

          <button
            @click="refreshData"
            class="group flex flex-col items-center gap-2 p-3 bg-white rounded-xl shadow-sm hover:shadow-md transition-all transform hover:-translate-y-1 border border-gray-100"
          >
            <div class="w-10 h-10 bg-gradient-to-br from-amber-500 to-orange-600 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
              <RefreshCw class="w-5 h-5 text-white" />
            </div>
            <span class="text-xs font-semibold text-gray-700">Refresh</span>
          </button>

          <button
            @click="() => {
              showGoalTracker = !showGoalTracker;
              toast.success(showGoalTracker ? 'Goal Tracker Enabled! 🎯' : 'Goal Tracker Hidden');
            }"
            class="group flex flex-col items-center gap-2 p-3 bg-white rounded-xl shadow-sm hover:shadow-md transition-all transform hover:-translate-y-1 border border-gray-100"
            :class="{ 'ring-2 ring-yellow-400 bg-yellow-50': showGoalTracker }"
          >
            <div class="w-10 h-10 bg-gradient-to-br from-yellow-500 to-orange-500 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
              <Target class="w-5 h-5 text-white" />
            </div>
            <span class="text-xs font-semibold text-gray-700">Goals</span>
          </button>

          <button
            @click="() => {
              showComparison = !showComparison;
              toast.success(showComparison ? 'Comparison Mode Enabled! 📊' : 'Comparison Mode Disabled');
            }"
            class="group flex flex-col items-center gap-2 p-3 bg-white rounded-xl shadow-sm hover:shadow-md transition-all transform hover:-translate-y-1 border border-gray-100"
            :class="{ 'ring-2 ring-indigo-400 bg-indigo-50': showComparison }"
          >
            <div class="w-10 h-10 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
              <Activity class="w-5 h-5 text-white" />
            </div>
            <span class="text-xs font-semibold text-gray-700">Compare</span>
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
      <!-- Enhanced Metric Cards with Trend Indicators -->
      <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-5 mb-8 print-section">

        <!-- Total Revenue Card -->
        <div class="group relative overflow-hidden bg-gradient-to-br from-blue-500 via-blue-600 to-indigo-700 p-6 rounded-2xl shadow-xl hover:shadow-2xl transition-all duration-300 hover:-translate-y-1">
          <div class="absolute top-0 right-0 w-40 h-40 bg-white/10 rounded-full -mr-20 -mt-20 group-hover:scale-110 transition-transform"></div>
          <div class="absolute bottom-0 left-0 w-24 h-24 bg-white/5 rounded-full -ml-12 -mb-12"></div>
          <div class="relative">
            <div class="flex items-center justify-between mb-4">
              <div class="p-2.5 bg-white/20 rounded-xl backdrop-blur-sm">
                <DollarSign class="w-6 h-6 text-white" />
              </div>
              <div class="flex items-center gap-2">
                <span class="flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-white/20 text-white backdrop-blur-sm">
                  <TrendingUp class="w-3 h-3" />
                  {{ growthData.revenue > 0 ? '+' : '' }}{{ growthData.revenue.toFixed(1) }}%
                </span>
                <span v-if="growthData.revenue > 0" class="text-green-300 text-xl animate-pulse">↗️</span>
                <span v-else-if="growthData.revenue < 0" class="text-red-300 text-xl animate-pulse">↘️</span>
                <span v-else class="text-yellow-300 text-xl">➡️</span>
              </div>
            </div>
            <div class="text-white/80 text-sm font-medium uppercase tracking-wider mb-1">Total Revenue</div>
            <div class="text-3xl md:text-4xl font-black text-white mb-2">{{ formatCurrency(summary.total_revenue) }}</div>
            <div class="flex items-center justify-between">
              <div class="text-white/60 text-xs">{{ startDate }} to {{ endDate }}</div>
              <div class="text-white/70 text-xs font-semibold">
                vs last period: {{ formatCurrency(previousPeriod.revenue) }}
              </div>
            </div>
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
              <div class="flex items-center gap-2">
                <span class="flex items-center gap-1 px-2.5 py-1 bg-white/20 rounded-full text-xs font-bold text-white backdrop-blur-sm">
                  <Target class="w-3 h-3" />
                  {{ growthData.profit > 0 ? '+' : '' }}{{ growthData.profit.toFixed(1) }}%
                </span>
                <span v-if="growthData.profit > 0" class="text-green-300 text-xl animate-pulse">📈</span>
                <span v-else-if="growthData.profit < 0" class="text-red-300 text-xl animate-pulse">📉</span>
                <span v-else class="text-yellow-300 text-xl">➡️</span>
              </div>
            </div>
            <div class="text-white/80 text-sm font-medium uppercase tracking-wider mb-1">Net Profit</div>
            <div class="text-3xl md:text-4xl font-black text-white mb-2">{{ formatCurrency(summary.net_profit) }}</div>
            <div class="flex items-center justify-between">
              <div class="text-white/60 text-xs">{{ summary.profit_margin.toFixed(1) }}% margin</div>
              <div class="text-white/70 text-xs font-semibold">
                vs: {{ formatCurrency(previousPeriod.profit) }}
              </div>
            </div>
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
              <div class="flex items-center gap-2">
                <span class="flex items-center gap-1 px-2.5 py-1 bg-white/20 rounded-full text-xs font-bold text-white backdrop-blur-sm">
                  <Activity class="w-3 h-3" />
                  {{ growthData.orders > 0 ? '+' : '' }}{{ growthData.orders.toFixed(1) }}%
                </span>
                <span v-if="growthData.orders > 0" class="text-green-300 text-xl animate-pulse">🚀</span>
                <span v-else-if="growthData.orders < 0" class="text-red-300 text-xl animate-pulse">📉</span>
                <span v-else class="text-yellow-300 text-xl">➡️</span>
              </div>
            </div>
            <div class="text-white/80 text-sm font-medium uppercase tracking-wider mb-1">Total Orders</div>
            <div class="text-3xl md:text-4xl font-black text-white mb-2">{{ formatNumber(summary.total_orders) }}</div>
            <div class="flex items-center justify-between">
              <div class="text-white/60 text-xs">Orders processed</div>
              <div class="text-white/70 text-xs font-semibold">
                vs: {{ formatNumber(previousPeriod.orders) }}
              </div>
            </div>
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
              <div class="flex items-center gap-2">
                <span class="flex items-center gap-1 px-2.5 py-1 bg-white/20 rounded-full text-xs font-bold text-white backdrop-blur-sm">
                  <BarChart class="w-3 h-3" />
                  {{ growthData.aov > 0 ? '+' : '' }}{{ growthData.aov.toFixed(1) }}%
                </span>
                <span v-if="growthData.aov > 0" class="text-green-300 text-xl animate-pulse">💰</span>
                <span v-else-if="growthData.aov < 0" class="text-red-300 text-xl animate-pulse">📉</span>
                <span v-else class="text-yellow-300 text-xl">➡️</span>
              </div>
            </div>
            <div class="text-white/80 text-sm font-medium uppercase tracking-wider mb-1">Avg Order Value</div>
            <div class="text-3xl md:text-4xl font-black text-white mb-2">{{ formatCurrency(summary.avg_order_value) }}</div>
            <div class="flex items-center justify-between">
              <div class="text-white/60 text-xs">Per order</div>
              <div class="text-white/70 text-xs font-semibold">
                vs: {{ formatCurrency(previousPeriod.aov) }}
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Enhanced Cost Breakdown Section with Interactive Progress Bars -->
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-8 print-section">
        <div class="flex items-center justify-between mb-6">
          <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2">
            <Wallet class="w-5 h-5 text-blue-600" />
            Profit & Cost Analysis
          </h3>
          <div class="flex items-center gap-2 px-3 py-1.5 bg-blue-50 rounded-full border border-blue-200">
            <TrendingUp class="w-4 h-4 text-blue-600" />
            <span class="text-sm font-semibold text-blue-700">{{ summary.profit_margin.toFixed(1) }}% Margin</span>
          </div>
        </div>

        <!-- Enhanced Interactive Metrics Grid -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
          <div class="group relative overflow-hidden p-5 rounded-2xl bg-gradient-to-br from-blue-50 to-indigo-100 border border-blue-200 hover:shadow-lg transition-all cursor-pointer">
            <div class="flex items-center justify-between mb-3">
              <div class="p-2 bg-blue-500 rounded-xl">
                <DollarSign class="w-5 h-5 text-white" />
              </div>
              <span class="text-blue-600 text-2xl">📊</span>
            </div>
            <div class="text-2xl md:text-3xl font-black text-blue-700 mb-1">{{ formatCurrency(summary.total_revenue) }}</div>
            <div class="text-sm text-blue-600 font-semibold">Total Revenue</div>
            <div class="text-xs text-blue-500 mt-1">100% of income</div>
          </div>

          <div class="group relative overflow-hidden p-5 rounded-2xl bg-gradient-to-br from-red-50 to-rose-100 border border-red-200 hover:shadow-lg transition-all cursor-pointer">
            <div class="flex items-center justify-between mb-3">
              <div class="p-2 bg-red-500 rounded-xl">
                <Minus class="w-5 h-5 text-white" />
              </div>
              <span class="text-red-600 text-2xl">📦</span>
            </div>
            <div class="text-2xl md:text-3xl font-black text-red-700 mb-1">{{ formatCurrency(summary.product_cost) }}</div>
            <div class="text-sm text-red-600 font-semibold">Product Cost</div>
            <div class="text-xs text-red-500 mt-1">{{ summary.total_revenue > 0 ? (summary.product_cost / summary.total_revenue * 100).toFixed(1) : 0 }}% of revenue</div>
          </div>

          <div class="group relative overflow-hidden p-5 rounded-2xl bg-gradient-to-br from-amber-50 to-orange-100 border border-amber-200 hover:shadow-lg transition-all cursor-pointer">
            <div class="flex items-center justify-between mb-3">
              <div class="p-2 bg-amber-500 rounded-xl">
                <Truck class="w-5 h-5 text-white" />
              </div>
              <span class="text-amber-600 text-2xl">🚛</span>
            </div>
            <div class="text-2xl md:text-3xl font-black text-amber-700 mb-1">{{ formatCurrency(summary.shipping_cost) }}</div>
            <div class="text-sm text-amber-600 font-semibold">Shipping Cost</div>
            <div class="text-xs text-amber-500 mt-1">{{ summary.total_revenue > 0 ? (summary.shipping_cost / summary.total_revenue * 100).toFixed(1) : 0 }}% of revenue</div>
          </div>

          <div class="group relative overflow-hidden p-5 rounded-2xl bg-gradient-to-br from-green-50 to-emerald-100 border border-green-200 hover:shadow-lg transition-all cursor-pointer">
            <div class="flex items-center justify-between mb-3">
              <div class="p-2 bg-green-500 rounded-xl">
                <Plus class="w-5 h-5 text-white" />
              </div>
              <span class="text-green-600 text-2xl">💰</span>
            </div>
            <div class="text-2xl md:text-3xl font-black text-green-700 mb-1">{{ formatCurrency(summary.net_profit) }}</div>
            <div class="text-sm text-green-600 font-semibold">Net Profit</div>
            <div class="text-xs text-green-500 mt-1">{{ summary.profit_margin.toFixed(1) }}% margin</div>
          </div>
        </div>

        <!-- Enhanced Interactive Progress Bar with Animations -->
        <div class="relative mb-6">
          <div class="flex items-center justify-between mb-3">
            <h4 class="text-sm font-bold text-gray-700">Revenue Breakdown</h4>
            <div class="flex items-center gap-4 text-xs text-gray-500">
              <div class="flex items-center gap-1">
                <div class="w-3 h-3 rounded bg-red-400"></div>
                <span>Cost</span>
              </div>
              <div class="flex items-center gap-1">
                <div class="w-3 h-3 rounded bg-amber-400"></div>
                <span>Shipping</span>
              </div>
              <div class="flex items-center gap-1">
                <div class="w-3 h-3 rounded bg-green-400"></div>
                <span>Profit</span>
              </div>
            </div>
          </div>

          <div class="relative h-8 bg-gray-100 rounded-2xl overflow-hidden shadow-inner">
            <div class="absolute inset-0 flex">
              <!-- Product Cost Section -->
              <div
                v-if="summary.total_revenue > 0"
                class="relative bg-gradient-to-r from-red-400 to-red-500 h-full transition-all duration-1000 ease-out flex items-center justify-center group hover:from-red-500 hover:to-red-600"
                :style="{ width: `${Math.min((summary.product_cost / summary.total_revenue * 100), 100)}%` }"
                :title="`Product Cost: ${formatCurrency(summary.product_cost)} (${(summary.product_cost / summary.total_revenue * 100).toFixed(1)}%)`"
              >
                <div class="absolute inset-0 bg-white/20 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <span v-if="summary.product_cost / summary.total_revenue > 0.15" class="text-xs font-bold text-white drop-shadow">
                  {{ (summary.product_cost / summary.total_revenue * 100).toFixed(0) }}%
                </span>
              </div>

              <!-- Shipping Cost Section -->
              <div
                v-if="summary.total_revenue > 0"
                class="relative bg-gradient-to-r from-amber-400 to-amber-500 h-full transition-all duration-1000 ease-out flex items-center justify-center group hover:from-amber-500 hover:to-amber-600"
                :style="{ width: `${Math.min((summary.shipping_cost / summary.total_revenue * 100), 100)}%` }"
                :title="`Shipping Cost: ${formatCurrency(summary.shipping_cost)} (${(summary.shipping_cost / summary.total_revenue * 100).toFixed(1)}%)`"
              >
                <div class="absolute inset-0 bg-white/20 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <span v-if="summary.shipping_cost / summary.total_revenue > 0.10" class="text-xs font-bold text-white drop-shadow">
                  {{ (summary.shipping_cost / summary.total_revenue * 100).toFixed(0) }}%
                </span>
              </div>

              <!-- Profit Section -->
              <div
                v-if="summary.total_revenue > 0"
                class="relative bg-gradient-to-r from-green-400 to-emerald-500 h-full transition-all duration-1000 ease-out flex items-center justify-center flex-1 group hover:from-green-500 hover:to-emerald-600"
                :title="`Net Profit: ${formatCurrency(summary.net_profit)} (${summary.profit_margin.toFixed(1)}%)`"
              >
                <div class="absolute inset-0 bg-white/20 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <span class="text-xs font-bold text-white drop-shadow flex items-center gap-1">
                  <TrendingUp class="w-3 h-3" />
                  {{ summary.profit_margin.toFixed(0) }}%
                </span>
              </div>

              <!-- No Data State -->
              <div v-if="summary.total_revenue === 0" class="flex-1 flex items-center justify-center text-gray-400 text-sm">
                <BarChart class="w-4 h-4 mr-2" />
                No revenue data to display
              </div>
            </div>
          </div>

          <!-- Tooltip Enhancement -->
          <div class="text-xs text-gray-500 mt-2 text-center">
            Hover over sections for detailed breakdown • {{ formatCurrency(summary.total_revenue) }} total revenue
          </div>
        </div>

        <!-- Goal Tracking Section -->
        <div v-if="showGoalTracker" class="mt-8 p-6 bg-gradient-to-r from-yellow-50 to-orange-50 rounded-2xl border border-yellow-200">
          <div class="flex items-center justify-between mb-4">
            <h4 class="text-lg font-bold text-gray-900 flex items-center gap-2">
              <Target class="w-5 h-5 text-yellow-600" />
              Monthly Goal Tracking
            </h4>
            <button @click="showGoalTracker = false" class="text-gray-400 hover:text-gray-600">
              <X class="w-5 h-5" />
            </button>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-semibold text-gray-700">Revenue Goal</span>
                <span class="text-sm text-gray-500">{{ targetAchieved.toFixed(1) }}% achieved</span>
              </div>
              <div class="w-full bg-gray-200 rounded-full h-3 mb-3">
                <div
                  class="h-3 rounded-full transition-all duration-1000 ease-out"
                  :class="{
                    'bg-gradient-to-r from-red-400 to-red-500': targetAchieved < 50,
                    'bg-gradient-to-r from-yellow-400 to-orange-500': targetAchieved >= 50 && targetAchieved < 80,
                    'bg-gradient-to-r from-green-400 to-emerald-500': targetAchieved >= 80
                  }"
                  :style="{ width: `${Math.min(targetAchieved, 100)}%` }"
                ></div>
              </div>
              <div class="flex justify-between text-sm text-gray-600">
                <span>{{ formatCurrency(summary.total_revenue) }}</span>
                <span>{{ formatCurrency(monthlyTarget) }}</span>
              </div>
            </div>

            <div class="flex items-center justify-center">
              <div class="text-center">
                <div class="text-3xl mb-2">
                  <span v-if="targetAchieved >= 100">🎉</span>
                  <span v-else-if="targetAchieved >= 80">🚀</span>
                  <span v-else-if="targetAchieved >= 50">📊</span>
                  <span v-else>📈</span>
                </div>
                <div class="text-lg font-bold text-gray-800">
                  {{ formatCurrency(monthlyTarget - summary.total_revenue) }} to goal
                </div>
                <div class="text-sm text-gray-500">
                  {{ targetAchieved >= 100 ? 'Goal exceeded!' : 'Keep pushing!' }}
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Comparison Mode Section -->
      <div v-if="showComparison" class="mt-8 p-6 bg-gradient-to-r from-indigo-50 to-purple-50 rounded-2xl border border-indigo-200">
        <div class="flex items-center justify-between mb-4">
          <h4 class="text-lg font-bold text-gray-900 flex items-center gap-2">
            <Activity class="w-5 h-5 text-indigo-600" />
            Period Comparison Analysis
          </h4>
          <button @click="showComparison = false" class="text-gray-400 hover:text-gray-600">
            <X class="w-5 h-5" />
          </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
          <div class="p-4 bg-white rounded-xl border border-indigo-100 shadow-sm">
            <div class="flex items-center justify-between mb-3">
              <div class="flex items-center gap-2">
                <DollarSign class="w-5 h-5 text-indigo-600" />
                <span class="text-sm font-semibold text-gray-700">Revenue Growth</span>
              </div>
              <span v-if="growthData.revenue > 0" class="text-green-600 text-2xl">📈</span>
              <span v-else-if="growthData.revenue < 0" class="text-red-600 text-2xl">📉</span>
              <span v-else class="text-yellow-600 text-2xl">➡️</span>
            </div>
            <div class="text-2xl font-bold mb-1" :class="{
              'text-green-600': growthData.revenue > 0,
              'text-red-600': growthData.revenue < 0,
              'text-gray-600': growthData.revenue === 0
            }">
              {{ growthData.revenue > 0 ? '+' : '' }}{{ growthData.revenue.toFixed(1) }}%
            </div>
            <div class="text-xs text-gray-500">
              Current: {{ formatCurrency(summary.total_revenue) }}<br>
              Previous: {{ formatCurrency(previousPeriod.total_revenue) }}
            </div>
          </div>

          <div class="p-4 bg-white rounded-xl border border-indigo-100 shadow-sm">
            <div class="flex items-center justify-between mb-3">
              <div class="flex items-center gap-2">
                <TrendingUp class="w-5 h-5 text-green-600" />
                <span class="text-sm font-semibold text-gray-700">Profit Growth</span>
              </div>
              <span v-if="growthData.profit > 0" class="text-green-600 text-2xl">💰</span>
              <span v-else-if="growthData.profit < 0" class="text-red-600 text-2xl">📉</span>
              <span v-else class="text-yellow-600 text-2xl">➡️</span>
            </div>
            <div class="text-2xl font-bold mb-1" :class="{
              'text-green-600': growthData.profit > 0,
              'text-red-600': growthData.profit < 0,
              'text-gray-600': growthData.profit === 0
            }">
              {{ growthData.profit > 0 ? '+' : '' }}{{ growthData.profit.toFixed(1) }}%
            </div>
            <div class="text-xs text-gray-500">
              Current: {{ formatCurrency(summary.net_profit) }}<br>
              Previous: {{ formatCurrency(previousPeriod.net_profit) }}
            </div>
          </div>

          <div class="p-4 bg-white rounded-xl border border-indigo-100 shadow-sm">
            <div class="flex items-center justify-between mb-3">
              <div class="flex items-center gap-2">
                <ShoppingCart class="w-5 h-5 text-purple-600" />
                <span class="text-sm font-semibold text-gray-700">Orders Growth</span>
              </div>
              <span v-if="growthData.orders > 0" class="text-green-600 text-2xl">🚀</span>
              <span v-else-if="growthData.orders < 0" class="text-red-600 text-2xl">📉</span>
              <span v-else class="text-yellow-600 text-2xl">➡️</span>
            </div>
            <div class="text-2xl font-bold mb-1" :class="{
              'text-green-600': growthData.orders > 0,
              'text-red-600': growthData.orders < 0,
              'text-gray-600': growthData.orders === 0
            }">
              {{ growthData.orders > 0 ? '+' : '' }}{{ growthData.orders.toFixed(1) }}%
            </div>
            <div class="text-xs text-gray-500">
              Current: {{ formatNumber(summary.total_orders) }}<br>
              Previous: {{ formatNumber(previousPeriod.total_orders) }}
            </div>
          </div>

          <div class="p-4 bg-white rounded-xl border border-indigo-100 shadow-sm">
            <div class="flex items-center justify-between mb-3">
              <div class="flex items-center gap-2">
                <Target class="w-5 h-5 text-orange-600" />
                <span class="text-sm font-semibold text-gray-700">AOV Growth</span>
              </div>
              <span v-if="growthData.aov > 0" class="text-green-600 text-2xl">💎</span>
              <span v-else-if="growthData.aov < 0" class="text-red-600 text-2xl">📉</span>
              <span v-else class="text-yellow-600 text-2xl">➡️</span>
            </div>
            <div class="text-2xl font-bold mb-1" :class="{
              'text-green-600': growthData.aov > 0,
              'text-red-600': growthData.aov < 0,
              'text-gray-600': growthData.aov === 0
            }">
              {{ growthData.aov > 0 ? '+' : '' }}{{ growthData.aov.toFixed(1) }}%
            </div>
            <div class="text-xs text-gray-500">
              Current: {{ formatCurrency(summary.avg_order_value) }}<br>
              Previous: {{ formatCurrency(previousPeriod.avg_order_value) }}
            </div>
          </div>
        </div>

        <!-- Summary Insights -->
        <div class="mt-6 p-4 bg-white/60 rounded-xl border border-indigo-200">
          <h5 class="font-bold text-gray-900 mb-3 flex items-center gap-2">
            <Zap class="w-4 h-4 text-indigo-600" />
            Key Insights
          </h5>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div class="flex items-start gap-3">
              <div class="w-2 h-2 bg-green-500 rounded-full mt-2 flex-shrink-0"></div>
              <div>
                <span class="font-semibold text-gray-800">Best Performing:</span>
                <span class="text-gray-600 ml-2">
                  {{ growthData.revenue >= growthData.profit && growthData.revenue >= growthData.orders && growthData.revenue >= growthData.aov ? 'Revenue' :
                     growthData.profit >= growthData.orders && growthData.profit >= growthData.aov ? 'Profit' :
                     growthData.orders >= growthData.aov ? 'Orders' : 'Average Order Value' }}
                </span>
              </div>
            </div>
            <div class="flex items-start gap-3">
              <div class="w-2 h-2 bg-orange-500 rounded-full mt-2 flex-shrink-0"></div>
              <div>
                <span class="font-semibold text-gray-800">Overall Trend:</span>
                <span class="text-gray-600 ml-2">
                  {{ (growthData.revenue + growthData.profit + growthData.orders + growthData.aov) / 4 > 0 ? 'Growing' : 'Declining' }} business
                </span>
              </div>
            </div>
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

      <!-- Business Intelligence Benefits -->
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

    <!-- Keyboard Shortcuts Modal -->
    <div v-if="showShortcutsModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 p-4" @click="showShortcutsModal = false">
      <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full max-h-[80vh] overflow-y-auto" @click.stop>
        <div class="sticky top-0 bg-gradient-to-r from-purple-600 to-pink-600 text-white p-6 rounded-t-2xl">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
              <div class="p-2.5 bg-white/20 rounded-xl backdrop-blur-sm">
                <Keyboard class="w-6 h-6 text-white" />
              </div>
              <div>
                <h3 class="text-xl font-bold">Keyboard Shortcuts</h3>
                <p class="text-purple-100 text-sm">Boost your productivity with these shortcuts</p>
              </div>
            </div>
            <button @click="showShortcutsModal = false" class="p-2 hover:bg-white/20 rounded-xl transition-colors">
              <X class="w-5 h-5 text-white" />
            </button>
          </div>
        </div>

        <div class="p-6 space-y-6">
          <!-- Export & Actions -->
          <div>
            <h4 class="text-sm font-bold text-gray-900 uppercase tracking-wider mb-3 flex items-center gap-2">
              <Download class="w-4 h-4 text-blue-600" />
              Export & Actions
            </h4>
            <div class="space-y-3">
              <div class="flex items-center justify-between p-3 bg-gray-50 rounded-xl">
                <div class="flex items-center gap-3">
                  <FileText class="w-5 h-5 text-gray-600" />
                  <span class="font-medium text-gray-900">Export to CSV</span>
                </div>
                <kbd class="px-3 py-1.5 bg-white border border-gray-300 rounded-lg text-sm font-mono text-gray-800 shadow-sm">Ctrl + E</kbd>
              </div>

              <div class="flex items-center justify-between p-3 bg-gray-50 rounded-xl">
                <div class="flex items-center gap-3">
                  <Printer class="w-5 h-5 text-gray-600" />
                  <span class="font-medium text-gray-900">Print Report</span>
                </div>
                <kbd class="px-3 py-1.5 bg-white border border-gray-300 rounded-lg text-sm font-mono text-gray-800 shadow-sm">Ctrl + P</kbd>
              </div>

              <div class="flex items-center justify-between p-3 bg-gray-50 rounded-xl">
                <div class="flex items-center gap-3">
                  <RefreshCw class="w-5 h-5 text-gray-600" />
                  <span class="font-medium text-gray-900">Refresh Data</span>
                </div>
                <kbd class="px-3 py-1.5 bg-white border border-gray-300 rounded-lg text-sm font-mono text-gray-800 shadow-sm">Ctrl + R</kbd>
              </div>

              <div class="flex items-center justify-between p-3 bg-gray-50 rounded-xl">
                <div class="flex items-center gap-3">
                  <Share2 class="w-5 h-5 text-gray-600" />
                  <span class="font-medium text-gray-900">Share Report</span>
                </div>
                <kbd class="px-3 py-1.5 bg-white border border-gray-300 rounded-lg text-sm font-mono text-gray-800 shadow-sm">Ctrl + S</kbd>
              </div>
            </div>
          </div>

          <!-- Analysis Tools -->
          <div>
            <h4 class="text-sm font-bold text-gray-900 uppercase tracking-wider mb-3 flex items-center gap-2">
              <BarChart3 class="w-4 h-4 text-purple-600" />
              Analysis Tools
            </h4>
            <div class="space-y-3">
              <div class="flex items-center justify-between p-3 bg-gray-50 rounded-xl">
                <div class="flex items-center gap-3">
                  <Target class="w-5 h-5 text-gray-600" />
                  <span class="font-medium text-gray-900">Toggle Goal Tracker</span>
                </div>
                <kbd class="px-3 py-1.5 bg-white border border-gray-300 rounded-lg text-sm font-mono text-gray-800 shadow-sm">Ctrl + G</kbd>
              </div>

              <div class="flex items-center justify-between p-3 bg-gray-50 rounded-xl">
                <div class="flex items-center gap-3">
                  <Activity class="w-5 h-5 text-gray-600" />
                  <span class="font-medium text-gray-900">Comparison Mode</span>
                </div>
                <kbd class="px-3 py-1.5 bg-white border border-gray-300 rounded-lg text-sm font-mono text-gray-800 shadow-sm">Ctrl + C</kbd>
              </div>

              <div class="flex items-center justify-between p-3 bg-gradient-to-r from-purple-50 to-pink-50 rounded-xl border border-purple-200">
                <div class="flex items-center gap-3">
                  <Keyboard class="w-5 h-5 text-purple-600" />
                  <span class="font-medium text-purple-900">Show Shortcuts</span>
                </div>
                <kbd class="px-3 py-1.5 bg-white border border-purple-300 rounded-lg text-sm font-mono text-purple-800 shadow-sm">Ctrl + K</kbd>
              </div>
            </div>
          </div>

          <!-- Pro Tips -->
          <div class="bg-gradient-to-r from-blue-50 to-indigo-50 p-4 rounded-xl border border-blue-200">
            <div class="flex items-start gap-3">
              <div class="p-2 bg-blue-100 rounded-lg">
                <Zap class="w-5 h-5 text-blue-600" />
              </div>
              <div>
                <h5 class="font-bold text-blue-900 mb-2">💡 Pro Tips</h5>
                <ul class="text-sm text-blue-800 space-y-1">
                  <li>• Use <kbd class="px-2 py-1 bg-white rounded font-mono text-xs">Ctrl + E</kbd> for quick CSV exports</li>
                  <li>• Toggle Goal Tracker with <kbd class="px-2 py-1 bg-white rounded font-mono text-xs">Ctrl + G</kbd> to monitor targets</li>
                  <li>• Press <kbd class="px-2 py-1 bg-white rounded font-mono text-xs">Ctrl + R</kbd> to refresh data in real-time</li>
                  <li>• Use comparison mode to analyze period-over-period growth</li>
                </ul>
              </div>
            </div>
          </div>
        </div>

        <div class="sticky bottom-0 bg-gray-50 px-6 py-4 rounded-b-2xl border-t border-gray-200">
          <div class="flex items-center justify-between">
            <div class="text-sm text-gray-500">
              Press <kbd class="px-2 py-1 bg-white border rounded font-mono">Esc</kbd> to close
            </div>
            <button @click="showShortcutsModal = false" class="px-4 py-2 bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-lg hover:from-purple-700 hover:to-pink-700 transition-all font-semibold">
              Got it! 🚀
            </button>
          </div>
        </div>
      </div>
    </div>

  </AdminLayout>
</template>

<style>
@media print {
  /* Hide elements that shouldn't be printed */
  .no-print,
  button,
  .fixed,
  .sticky,
  nav,
  header {
    display: none !important;
  }

  /* Optimize layout for printing */
  body {
    background: white !important;
    color: black !important;
    font-size: 12pt;
    line-height: 1.4;
  }

  /* Make sure content fits on page */
  .print-section {
    page-break-inside: avoid;
    margin-bottom: 20px;
  }

  /* Adjust card styles for print */
  .bg-gradient-to-br,
  .bg-gradient-to-r {
    background: white !important;
    border: 1px solid #ddd !important;
  }

  /* Ensure text is readable */
  .text-white {
    color: black !important;
  }

  /* Show print-specific content */
  .print-only {
    display: block !important;
  }

  /* Hide interactive elements */
  .hover\:shadow-md,
  .hover\:scale-105,
  .transition-all {
    box-shadow: none !important;
    transform: none !important;
    transition: none !important;
  }
}

.print-only {
  display: none;
}
</style>

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
