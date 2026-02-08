<template>
  <div class="inventory-analytics-dashboard">
    <!-- Header Section -->
    <div class="bg-gradient-to-r from-blue-600 to-purple-600 text-white p-6 rounded-lg mb-6">
      <h1 class="text-2xl font-bold mb-2">📊 Inventory Analytics Dashboard</h1>
      <p class="opacity-90">{{ currentDate }} - Real-time Inventory Monitoring</p>
    </div>

    <!-- Quick Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
      <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-lg">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <div class="w-8 h-8 bg-red-500 rounded-full flex items-center justify-center">
              <span class="text-white text-sm">🚨</span>
            </div>
          </div>
          <div class="ml-3">
            <p class="text-sm font-medium text-red-800">Critical Stock</p>
            <p class="text-2xl font-bold text-red-900">{{ summary?.critical_count || 0 }}</p>
          </div>
        </div>
      </div>

      <div class="bg-yellow-50 border-l-4 border-yellow-500 p-4 rounded-lg">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <div class="w-8 h-8 bg-yellow-500 rounded-full flex items-center justify-center">
              <span class="text-white text-sm">⚠️</span>
            </div>
          </div>
          <div class="ml-3">
            <p class="text-sm font-medium text-yellow-800">Low Stock</p>
            <p class="text-2xl font-bold text-yellow-900">{{ summary?.low_count || 0 }}</p>
          </div>
        </div>
      </div>

      <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded-lg">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center">
              <span class="text-white text-sm">📈</span>
            </div>
          </div>
          <div class="ml-3">
            <p class="text-sm font-medium text-blue-800">Total Products</p>
            <p class="text-2xl font-bold text-blue-900">{{ summary?.total_products || 0 }}</p>
          </div>
        </div>
      </div>

      <div class="bg-green-50 border-l-4 border-green-500 p-4 rounded-lg">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center">
              <span class="text-white text-sm">✅</span>
            </div>
          </div>
          <div class="ml-3">
            <p class="text-sm font-medium text-green-800">Good Stock</p>
            <p class="text-2xl font-bold text-green-900">{{ summary?.good_stock || 0 }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Daily Report Section -->
    <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-xl font-bold text-gray-800">📋 Today's Summary Report</h2>
        <div class="flex gap-2">
          <button
            @click="refreshDailyReport"
            class="btn btn-sm btn-primary"
            :disabled="loading"
          >
            <span v-if="loading" class="loading loading-spinner loading-xs"></span>
            {{ loading ? 'Updating...' : '🔄 Refresh' }}
          </button>
          <button
            @click="sendDailyEmail"
            class="btn btn-sm btn-accent"
            :disabled="emailSending"
          >
            <span v-if="emailSending" class="loading loading-spinner loading-xs"></span>
            {{ emailSending ? 'Sending...' : '📧 Send Email' }}
          </button>
        </div>
      </div>

      <!-- Sales Velocity Chart -->
      <div class="mb-6">
        <h3 class="text-lg font-semibold mb-3">📊 Sales Velocity Analysis</h3>
        <div id="velocityChart" class="w-full h-64"></div>
      </div>

      <!-- Critical Stock Alert Table -->
      <div v-if="criticalProducts && criticalProducts.length > 0" class="mb-6">
        <h3 class="text-lg font-semibold text-red-600 mb-3">🚨 Critical Stock Alerts</h3>
        <div class="overflow-x-auto">
          <table class="table table-zebra w-full">
            <thead>
              <tr class="bg-red-100">
                <th>Product Name</th>
                <th>Current Stock</th>
                <th>Minimum Required</th>
                <th>Priority</th>
                <th>Days to Stockout</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="product in criticalProducts" :key="product.id" class="hover">
                <td class="font-medium">{{ product.name }}</td>
                <td>
                  <span class="badge badge-error">{{ product.current_stock || 0 }}</span>
                </td>
                <td>{{ product.minimum_threshold || 0 }}</td>
                <td>
                  <div class="badge badge-error">{{ product.urgency || 'critical' }}</div>
                </td>
                <td>
                  <span class="text-red-600 font-bold">
                    {{ product.days_out_of_stock || 0 }} days
                  </span>
                </td>
                <td>
                  <button
                    @click="createPurchaseOrder(product)"
                    class="btn btn-xs btn-primary"
                  >
                    📝 Order Now
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Low Stock Warning Table -->
      <div v-if="lowProducts && lowProducts.length > 0" class="mb-6">
        <h3 class="text-lg font-semibold text-yellow-600 mb-3">⚠️ Low Stock Warnings</h3>
        <div class="overflow-x-auto">
          <table class="table table-zebra w-full">
            <thead>
              <tr class="bg-yellow-100">
                <th>Product Name</th>
                <th>Current Stock</th>
                <th>Minimum Required</th>
                <th>Sales Velocity</th>
                <th>Recommendation</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="product in lowProducts" :key="product.id" class="hover">
                <td class="font-medium">{{ product.name }}</td>
                <td>
                  <span class="badge badge-warning">{{ product.current_stock || 0 }}</span>
                </td>
                <td>{{ product.minimum_threshold || 0 }}</td>
                <td>
                  <div class="badge" :class="getVelocityBadgeClass(product.urgency === 'low' ? 'low' : 'medium')">
                    {{ product.urgency || 'low' }}
                  </div>
                </td>
                <td class="text-sm text-gray-600">Order {{ product.suggested_order_qty || 0 }} units</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Weekly Optimization Section -->
    <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-xl font-bold text-gray-800">📈 Weekly Optimization</h2>
        <button
          @click="generateWeeklyReport"
          class="btn btn-sm btn-secondary"
          :disabled="weeklyLoading"
        >
          <span v-if="weeklyLoading" class="loading loading-spinner loading-xs"></span>
          {{ weeklyLoading ? 'Generating...' : '📊 Weekly Report' }}
        </button>
      </div>

      <!-- Promotional Suggestions -->
      <div v-if="promotionalProducts && promotionalProducts.length > 0" class="mb-6">
        <h3 class="text-lg font-semibold text-purple-600 mb-3">🎯 Promotional Suggestions</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <div
            v-for="product in promotionalProducts"
            :key="product.id"
            class="card bg-gradient-to-br from-purple-50 to-pink-50 shadow-md"
          >
            <div class="card-body p-4">
              <h4 class="card-title text-sm">{{ product.name }}</h4>
              <div class="space-y-2 text-xs">
                <p><strong>Stock:</strong> {{ product.current_stock }} units</p>
                <p><strong>Last Sale:</strong> {{ formatDate(product.last_sale_date) }}</p>
                <p class="text-purple-600 font-medium">
                  <strong>Suggestion:</strong> {{ product.promotion_suggestion }}
                </p>
              </div>
              <div class="card-actions justify-end mt-3">
                <button
                  @click="createPromotion(product)"
                  class="btn btn-xs btn-secondary"
                >
                  🏷️ Create Promo
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Revenue Analysis Chart -->
      <div class="mb-6">
        <h3 class="text-lg font-semibold mb-3">💰 Weekly Revenue Analysis</h3>
        <div id="revenueChart" class="w-full h-64"></div>
      </div>
    </div>

    <!-- Email Notification Settings -->
    <div class="bg-white rounded-lg shadow-lg p-6">
      <h2 class="text-xl font-bold text-gray-800 mb-4">📧 Email Notification Settings</h2>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="space-y-4">
          <h3 class="font-semibold text-gray-700">Daily Reports</h3>
          <div class="form-control">
            <label class="label cursor-pointer">
              <span class="label-text">Morning Summary (6:00 AM)</span>
              <input
                type="checkbox"
                v-model="emailSettings.daily_morning_report"
                class="toggle toggle-primary"
              />
            </label>
          </div>
          <div class="form-control">
            <label class="label cursor-pointer">
              <span class="label-text">Critical Stock Alerts</span>
              <input
                type="checkbox"
                v-model="emailSettings.critical_alerts"
                class="toggle toggle-error"
              />
            </label>
          </div>
        </div>

        <div class="space-y-4">
          <h3 class="font-semibold text-gray-700">Weekly Reports</h3>
          <div class="form-control">
            <label class="label cursor-pointer">
              <span class="label-text">Optimization Suggestions</span>
              <input
                type="checkbox"
                v-model="emailSettings.weekly_optimization"
                class="toggle toggle-secondary"
              />
            </label>
          </div>
          <div class="form-control">
            <label class="label cursor-pointer">
              <span class="label-text">Promotional Opportunities</span>
              <input
                type="checkbox"
                v-model="emailSettings.promotional_opportunities"
                class="toggle toggle-accent"
              />
            </label>
          </div>
        </div>
      </div>

      <div class="mt-6 flex justify-end">
        <button
          @click="saveEmailSettings"
          class="btn btn-primary"
          :disabled="settingsSaving"
        >
          <span v-if="settingsSaving" class="loading loading-spinner loading-sm"></span>
          {{ settingsSaving ? 'Saving...' : '💾 Save Settings' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, watch, nextTick } from 'vue'
import { usePage } from '@inertiajs/vue3'
import ApexCharts from 'apexcharts'

// Accept props from Inertia page
const props = defineProps({
  initialData: {
    type: Object,
    default: () => ({})
  },
  reorderAlerts: {
    type: Object,
    default: () => ({})
  },
  weeklyRevenue: {
    type: [Object, Array],
    default: () => ({})
  },
  initialPromotionalProducts: {
    type: Array,
    default: () => ([])
  }
})

// Reactive data
const loading = ref(false)
const emailSending = ref(false)
const weeklyLoading = ref(false)
const settingsSaving = ref(false)

// Initialize from props (real DB data from Inertia)
const summary = ref({
  critical_count: props.initialData?.summary?.critical_count || 0,
  low_count: props.initialData?.summary?.low_count || 0,
  total_products: props.initialData?.summary?.total_products || 0,
  good_stock: props.initialData?.summary?.good_stock || 0
})

const criticalProducts = ref(props.reorderAlerts?.critical || [])
const lowProducts = ref(props.reorderAlerts?.low || [])
const promotionalProducts = ref(props.initialPromotionalProducts || [])
const salesVelocity = ref(props.initialData?.sales_velocity || [])

const emailSettings = ref({
  daily_morning_report: true,
  critical_alerts: true,
  weekly_optimization: true,
  promotional_opportunities: true
})

// Chart instances for cleanup
let velocityChartInstance = null
let revenueChartInstance = null

// Computed properties
const currentDate = computed(() => {
  return new Date().toLocaleDateString('en-US', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
})

// Computed: Calculate velocity distribution from real data
const velocityDistribution = computed(() => {
  const velocity = salesVelocity.value || []
  if (!velocity.length) {
    // Calculate from stock data
    const total = summary.value.total_products || 1
    const critical = summary.value.critical_count || 0
    const low = summary.value.low_count || 0
    const good = summary.value.good_stock || 0
    return {
      fast: Math.round((good / total) * 100) || 0,
      medium: Math.round((low / total) * 100) || 0,
      slow: Math.round((critical / total) * 100) || 0
    }
  }
  const total = velocity.length || 1
  const fast = velocity.filter(v => v.velocity_rating === 'high').length
  const medium = velocity.filter(v => v.velocity_rating === 'medium').length
  const slow = velocity.filter(v => ['low', 'none'].includes(v.velocity_rating)).length
  return {
    fast: Math.round((fast / total) * 100),
    medium: Math.round((medium / total) * 100),
    slow: Math.round((slow / total) * 100)
  }
})

// Methods
const formatNumber = (number) => {
  return number?.toFixed ? number.toFixed(0) : '0'
}

const formatDate = (date) => {
  if (!date) return 'No data'
  return new Date(date).toLocaleDateString('en-US')
}

const getVelocityText = (velocity) => {
  switch(velocity) {
    case 'high': return 'Fast'
    case 'medium': return 'Medium'
    case 'low': return 'Slow'
    default: return 'Unknown'
  }
}

const getVelocityBadgeClass = (velocity) => {
  switch(velocity) {
    case 'high': return 'badge-success'
    case 'medium': return 'badge-warning'
    case 'low': return 'badge-error'
    default: return 'badge-neutral'
  }
}

const refreshDailyReport = async () => {
  loading.value = true
  try {
    const response = await fetch('/admin/api/inventory-analytics/refresh-dashboard', {
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    })

    if (!response.ok) throw new Error(`HTTP ${response.status}`)

    const data = await response.json()

    if (data.success && data.data) {
      summary.value = {
        critical_count: data.data.summary?.critical_count || 0,
        low_count: data.data.summary?.low_count || 0,
        total_products: data.data.summary?.total_products || 0,
        good_stock: data.data.summary?.good_stock || 0
      }
      criticalProducts.value = data.data.critical || []
      lowProducts.value = data.data.low || []
      salesVelocity.value = data.data.sales_velocity || []
    }

    await nextTick()
    updateVelocityChart()

  } catch (error) {
    console.error('Error refreshing dashboard:', error)
    alert('❌ Failed to refresh. Please reload the page.')
  } finally {
    loading.value = false
  }
}

const sendDailyEmail = async () => {
  emailSending.value = true
  try {
    const response = await fetch('/admin/api/inventory-analytics/send-daily-report', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
        'X-Requested-With': 'XMLHttpRequest'
      }
    })

    const data = await response.json()
    if (data.success) {
      alert('✅ Daily report sent successfully!')
    } else {
      alert('❌ Failed to send email: ' + (data.message || 'Unknown error'))
    }
  } catch (error) {
    console.error('Error sending daily email:', error)
    alert('❌ Failed to send email')
  } finally {
    emailSending.value = false
  }
}

const generateWeeklyReport = async () => {
  weeklyLoading.value = true
  try {
    const response = await fetch('/admin/api/inventory-analytics/weekly-report', {
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    })

    if (!response.ok) throw new Error(`HTTP ${response.status}`)

    const data = await response.json()

    if (data.success) {
      promotionalProducts.value = data.data?.promotional || []

      const revenueObj = data.data?.revenue || {}
      await nextTick()
      updateRevenueChart(revenueObj.data || [], revenueObj.labels || [])
    }
  } catch (error) {
    console.error('Error generating weekly report:', error)
  } finally {
    weeklyLoading.value = false
  }
}

const createPurchaseOrder = (product) => {
  window.location.href = `/admin/purchase-orders/create?product_id=${product.id}`
}

const createPromotion = (product) => {
  window.location.href = `/admin/promotions/create?product_id=${product.id}`
}

const saveEmailSettings = async () => {
  settingsSaving.value = true
  try {
    const response = await fetch('/admin/api/inventory-analytics/email-settings', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: JSON.stringify(emailSettings.value)
    })

    if (response.ok) {
      alert('✅ Settings saved successfully!')
    } else {
      alert('❌ Failed to save settings')
    }
  } catch (error) {
    console.error('Error saving email settings:', error)
    alert('❌ Failed to save settings')
  } finally {
    settingsSaving.value = false
  }
}

const updateVelocityChart = () => {
  const el = document.querySelector('#velocityChart')
  if (!el) return

  // Destroy previous chart instance
  if (velocityChartInstance) {
    velocityChartInstance.destroy()
    velocityChartInstance = null
  }

  const dist = velocityDistribution.value

  const options = {
    series: [{
      name: 'Fast Sales',
      data: [dist.fast]
    }, {
      name: 'Medium Sales',
      data: [dist.medium]
    }, {
      name: 'Slow Sales',
      data: [dist.slow]
    }],
    chart: {
      type: 'bar',
      height: 250,
      stacked: true,
      stackType: '100%'
    },
    colors: ['#10B981', '#F59E0B', '#EF4444'],
    xaxis: {
      categories: ['Sales Velocity'],
    },
    legend: {
      position: 'bottom',
    },
    plotOptions: {
      bar: {
        horizontal: true,
      },
    },
    dataLabels: {
      formatter: function(val) {
        return val.toFixed(0) + '%'
      }
    }
  }

  velocityChartInstance = new ApexCharts(el, options)
  velocityChartInstance.render()
}

const updateRevenueChart = (revenueData, labels) => {
  const el = document.querySelector('#revenueChart')
  if (!el) return

  // Destroy previous chart instance
  if (revenueChartInstance) {
    revenueChartInstance.destroy()
    revenueChartInstance = null
  }

  const chartLabels = labels?.length ? labels : ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']
  const chartData = revenueData?.length ? revenueData : [0, 0, 0, 0, 0, 0, 0]

  const options = {
    series: [{
      name: 'Revenue',
      data: chartData
    }],
    chart: {
      type: 'area',
      height: 250,
      toolbar: { show: false }
    },
    colors: ['#8B5CF6'],
    fill: {
      type: 'gradient',
      gradient: {
        shadeIntensity: 1,
        opacityFrom: 0.4,
        opacityTo: 0.1,
      }
    },
    xaxis: {
      categories: chartLabels
    },
    yaxis: {
      labels: {
        formatter: function (val) {
          return '৳' + (val || 0).toLocaleString()
        }
      }
    },
    dataLabels: {
      enabled: true,
      formatter: function(val) {
        return val > 0 ? '৳' + val.toLocaleString() : ''
      }
    }
  }

  revenueChartInstance = new ApexCharts(el, options)
  revenueChartInstance.render()
}

// Initialize component using props data (no API call needed on mount)
onMounted(async () => {
  console.log('Dashboard mounted with props:', {
    summary: summary.value,
    critical: criticalProducts.value.length,
    low: lowProducts.value.length,
    velocity: salesVelocity.value.length,
    promotional: promotionalProducts.value.length
  })

  await nextTick()

  // Render charts with props data
  updateVelocityChart()

  // Render revenue chart from props
  const rev = props.weeklyRevenue || {}
  updateRevenueChart(rev.data || [], rev.labels || [])
})
</script>
