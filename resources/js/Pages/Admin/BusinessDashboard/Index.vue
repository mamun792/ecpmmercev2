<template>
  <AdminLayout>
    <div class="p-6 bg-gray-50 min-h-screen">
      <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="mb-8">
          <h1 class="text-3xl font-bold text-gray-900">Business Dashboard</h1>
          <p class="text-gray-600 mt-2">Monitor your business performance and revenue</p>
        </div>

        <!-- Filter Section -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
          <div class="flex flex-wrap items-end gap-4">
            <!-- Filter Type -->
            <div class="min-w-[200px]">
              <label class="block text-sm font-medium text-gray-700 mb-2">Time Period</label>
              <select
                v-model="filter"
                @change="applyFilter"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              >
                <option value="weekly">Weekly</option>
                <option value="monthly">Monthly</option>
                <option value="yearly">Yearly</option>
                <option value="custom">Custom Range</option>
              </select>
            </div>

            <!-- Start Date (for custom range) -->
            <div v-if="filter === 'custom'" class="min-w-[150px]">
              <label class="block text-sm font-medium text-gray-700 mb-2">Start Date</label>
              <input
                type="date"
                v-model="startDate"
                @change="applyFilter"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              />
            </div>

            <!-- End Date (for custom range) -->
            <div v-if="filter === 'custom'" class="min-w-[150px]">
              <label class="block text-sm font-medium text-gray-700 mb-2">End Date</label>
              <input
                type="date"
                v-model="endDate"
                @change="applyFilter"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              />
            </div>

            <!-- Apply Button -->
            <div>
              <button
                @click="applyFilter"
                class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 transition duration-200"
              >
                Apply Filter
              </button>
            </div>
          </div>
        </div>

        <!-- Metrics Cards Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
          <!-- Total Revenue Card -->
          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow duration-200">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-gray-600 text-sm font-medium">Total Revenue</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">{{ formatCurrency(revenue) }}</p>
                <p class="text-green-600 text-sm mt-1 flex items-center">
                  <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                  </svg>
                  +12.5% from last {{ getFilterLabel().toLowerCase() }}
                </p>
              </div>
              <div class="bg-green-100 p-3 rounded-full">
                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                </svg>
              </div>
            </div>
          </div>

          <!-- Total Expenses Card -->
          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow duration-200">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-gray-600 text-sm font-medium">Total Expenses</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">{{ formatCurrency(expenses) }}</p>
                <p class="text-red-600 text-sm mt-1 flex items-center">
                  <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM7 9a1 1 0 000 2h6a1 1 0 100-2H7z" clip-rule="evenodd"/>
                  </svg>
                  +8.2% from last {{ getFilterLabel().toLowerCase() }}
                </p>
              </div>
              <div class="bg-red-100 p-3 rounded-full">
                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2z"/>
                </svg>
              </div>
            </div>
          </div>

          <!-- Net Profit Card -->
          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow duration-200">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-gray-600 text-sm font-medium">Net Profit</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">{{ formatCurrency(profit) }}</p>
                <p class="text-green-600 text-sm mt-1 flex items-center">
                  <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                  </svg>
                  +15.3% from last {{ getFilterLabel().toLowerCase() }}
                </p>
              </div>
              <div class="bg-blue-100 p-3 rounded-full">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
              </div>
            </div>
          </div>

          <!-- Total Orders Card -->
          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow duration-200">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-gray-600 text-sm font-medium">Total Orders</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">{{ totalOrders }}</p>
                <p class="text-green-600 text-sm mt-1 flex items-center">
                  <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                  </svg>
                  +5.7% from last {{ getFilterLabel().toLowerCase() }}
                </p>
              </div>
              <div class="bg-purple-100 p-3 rounded-full">
                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                </svg>
              </div>
            </div>
          </div>
        </div>

        <!-- Charts Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
          <!-- Revenue vs Expenses Chart -->
          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Revenue vs Expenses Trend</h3>
            <apexchart
              type="area"
              height="300"
              :options="chartOptions"
              :series="chartSeries"
            ></apexchart>
          </div>

          <!-- Revenue Breakdown Pie Chart -->
          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Revenue Breakdown</h3>
            <apexchart
              type="pie"
              height="300"
              :options="pieChartOptions"
              :series="pieChartSeries"
            ></apexchart>
          </div>
        </div>

        <!-- Additional Charts Row -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <!-- Orders Trend Chart -->
          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Orders Trend</h3>
            <apexchart
              type="line"
              height="300"
              :options="ordersChartOptions"
              :series="ordersChartSeries"
            ></apexchart>
          </div>

          <!-- Monthly Overview Chart -->
          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Monthly Overview</h3>
            <apexchart
              type="bar"
              height="300"
              :options="monthlyOverviewChartOptions"
              :series="monthlyOverviewChartSeries"
            ></apexchart>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { ref, onMounted, watch, computed } from 'vue'
import { router } from '@inertiajs/vue3'

// Props
const props = defineProps({
  revenue: {
    type: Number,
    default: 0
  },
  expenses: {
    type: Number,
    default: 0
  },
  profit: {
    type: Number,
    default: 0
  },
  totalOrders: {
    type: Number,
    default: 0
  },
  filter: {
    type: String,
    default: 'monthly'
  },
  startDate: {
    type: String,
    default: null
  },
  endDate: {
    type: String,
    default: null
  },
  chartData: {
    type: Object,
    default: () => ({
      categories: [],
      revenue: [],
      expenses: []
    })
  },
  ordersChartData: {
    type: Object,
    default: () => ({
      categories: [],
      orders: []
    })
  },
  monthlyOverviewData: {
    type: Object,
    default: () => ({
      categories: [],
      salary: [],
      expenses: [],
      purchases: [],
      revenue: []
    })
  }
})

// Reactive data - sync with props
const filter = ref(props.filter)
const startDate = ref(props.startDate)
const endDate = ref(props.endDate)

// Watch for prop changes and update refs
watch(() => props.filter, (newFilter) => {
  filter.value = newFilter
})
watch(() => props.startDate, (newStartDate) => {
  startDate.value = newStartDate
})
watch(() => props.endDate, (newEndDate) => {
  endDate.value = newEndDate
})

// Methods
const applyFilter = () => {
  const params = {
    filter: filter.value
  }

  if (filter.value === 'custom') {
    params.start_date = startDate.value
    params.end_date = endDate.value
  }

  router.get(route('admin.business-dashboard.index'), params, {
    preserveState: true,
    replace: true
  })
}

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD'
  }).format(amount)
}

const getFilterLabel = () => {
  switch (filter.value) {
    case 'weekly':
      return 'This Week'
    case 'monthly':
      return 'This Month'
    case 'yearly':
      return 'This Year'
    case 'custom':
      return startDate.value && endDate.value
        ? `${startDate.value} to ${endDate.value}`
        : 'Custom Range'
    default:
      return ''
  }
}

// Computed properties for charts
const chartOptions = computed(() => ({
  chart: {
    type: 'area',
    height: 300,
    toolbar: {
      show: false
    }
  },
  colors: ['#10B981', '#EF4444'],
  dataLabels: {
    enabled: false
  },
  stroke: {
    curve: 'smooth',
    width: 2
  },
  fill: {
    type: 'gradient',
    gradient: {
      shade: 'light',
      type: 'vertical',
      opacityFrom: 0.4,
      opacityTo: 0.1,
    }
  },
  xaxis: {
    categories: props.chartData.categories || [],
    labels: {
      style: {
        colors: '#6B7280'
      }
    }
  },
  yaxis: {
    labels: {
      style: {
        colors: '#6B7280'
      },
      formatter: function(value) {
        return '$' + value.toLocaleString();
      }
    }
  },
  tooltip: {
    y: {
      formatter: function(value) {
        return '$' + value.toLocaleString();
      }
    }
  },
  legend: {
    position: 'top',
    horizontalAlign: 'right'
  }
}));

const chartSeries = computed(() => [
  {
    name: 'Revenue',
    data: props.chartData.revenue || []
  },
  {
    name: 'Expenses',
    data: props.chartData.expenses || []
  }
]);

const pieChartOptions = computed(() => ({
  chart: {
    type: 'pie',
    height: 300
  },
  colors: ['#10B981', '#EF4444', '#3B82F6', '#F59E0B'],
  labels: ['Revenue', 'Expenses', 'Profit', 'Other'],
  legend: {
    position: 'bottom'
  },
  tooltip: {
    y: {
      formatter: function(value) {
        return '$' + Number(value).toLocaleString();
      }
    }
  },
  responsive: [{
    breakpoint: 480,
    options: {
      chart: {
        width: 200
      },
      legend: {
        position: 'bottom'
      }
    }
  }]
}));

const pieChartSeries = computed(() => {
  const revenue = Number(props.revenue) || 0;
  const expenses = Number(props.expenses) || 0;
  const profit = Number(props.profit) || 0;

  // Calculate other income (if any) - for now, just ensure we have valid numbers
  const other = Math.max(0, revenue - expenses - profit);

  return [revenue, expenses, profit, other];
});

const ordersChartOptions = computed(() => ({
  chart: {
    type: 'line',
    height: 300,
    toolbar: {
      show: false
    }
  },
  colors: ['#8B5CF6'],
  stroke: {
    curve: 'smooth',
    width: 3
  },
  markers: {
    size: 5,
    colors: ['#8B5CF6'],
    strokeColors: '#fff',
    strokeWidth: 2
  },
  xaxis: {
    categories: props.ordersChartData.categories || [],
    labels: {
      style: {
        colors: '#6B7280'
      }
    }
  },
  yaxis: {
    labels: {
      style: {
        colors: '#6B7280'
      }
    }
  },
  tooltip: {
    y: {
      formatter: function(value) {
        return value + ' orders';
      }
    }
  }
}));

const ordersChartSeries = computed(() => [
  {
    name: 'Orders',
    data: props.ordersChartData.orders || []
  }
]);

const monthlyOverviewChartOptions = computed(() => ({
  chart: {
    type: 'bar',
    height: 300,
    toolbar: {
      show: false
    },
    stacked: true
  },
  colors: ['#3B82F6', '#EF4444', '#F59E0B', '#10B981'],
  plotOptions: {
    bar: {
      borderRadius: 4,
      columnWidth: '70%',
      distributed: false
    }
  },
  xaxis: {
    categories: props.monthlyOverviewData.categories || [],
    labels: {
      style: {
        colors: '#6B7280'
      }
    }
  },
  yaxis: {
    labels: {
      style: {
        colors: '#6B7280'
      },
      formatter: function(value) {
        return '$' + value.toLocaleString();
      }
    }
  },
  tooltip: {
    y: {
      formatter: function(value) {
        return '$' + Number(value).toLocaleString();
      }
    }
  },
  dataLabels: {
    enabled: false
  },
  legend: {
    position: 'top',
    horizontalAlign: 'right'
  }
}));

const monthlyOverviewChartSeries = computed(() => [
  {
    name: 'Salary',
    data: props.monthlyOverviewData.salary || []
  },
  {
    name: 'Expenses',
    data: props.monthlyOverviewData.expenses || []
  },
  {
    name: 'Purchases',
    data: props.monthlyOverviewData.purchases || []
  },
  {
    name: 'Revenue',
    data: props.monthlyOverviewData.revenue || []
  }
]);

// Initialize
onMounted(() => {
  // Any initialization logic here
})
</script>

<style scoped>
/* Additional styles if needed */
</style>