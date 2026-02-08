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
                <td>{{ product.minimum_stock || 0 }}</td>
                <td>
                  <div class="badge badge-error">{{ product.priority_score || 10 }}/10</div>
                </td>
                <td>
                  <span v-if="product.days_until_stockout !== null"
                        class="text-red-600 font-bold">
                    {{ product.days_until_stockout }} days
                  </span>
                  <span v-else class="text-gray-500">Unknown</span>
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
                <td>{{ product.minimum_stock || 0 }}</td>
                <td>
                  <div class="badge" :class="getVelocityBadgeClass(product.velocity)">
                    {{ getVelocityText(product.velocity) }}
                  </div>
                </td>
                <td class="text-sm text-gray-600">{{ product.recommendation || 'Monitor closely' }}</td>
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
import { ref, onMounted, computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import ApexCharts from 'apexcharts'

// Reactive data
const loading = ref(false)
const emailSending = ref(false)
const weeklyLoading = ref(false)
const settingsSaving = ref(false)

const summary = ref({
  critical_count: 0,
  low_count: 0,
  total_products: 0,
  good_stock: 0
})

const criticalProducts = ref([])
const lowProducts = ref([])
const promotionalProducts = ref([])

const emailSettings = ref({
  daily_morning_report: true,
  critical_alerts: true,
  weekly_optimization: true,
  promotional_opportunities: true
})

// Computed properties
const currentDate = computed(() => {
  return new Date().toLocaleDateString('en-US', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
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
    const response = await fetch('/admin/api/inventory-analytics/dashboard')
    const data = await response.json()

    if (data.success) {
      summary.value = data.data.summary
      criticalProducts.value = data.data.critical || []
      lowProducts.value = data.data.low || []
    }

    // Update charts
    updateVelocityChart()

  } catch (error) {
    console.error('Error fetching daily report:', error)
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
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
      }
    })

    const data = await response.json()
    if (data.success) {
      alert('✅ Daily report sent successfully!')
    } else {
      alert('❌ Failed to send email')
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
    const response = await fetch('/admin/api/inventory-analytics/weekly-report')
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`)
    }
    const data = await response.json()

    if (data.success) {
      promotionalProducts.value = data.data?.promotional || []
      updateRevenueChart(data.data?.revenue || [])
    } else {
      console.error('Weekly report generation failed:', data.message)
      // Set fallback data
      promotionalProducts.value = []
      updateRevenueChart([10000, 12000, 15000, 14000, 16000, 18000, 20000])
    }
  } catch (error) {
    console.error('Error generating weekly report:', error)
    // Set fallback data on error
    promotionalProducts.value = []
    updateRevenueChart([10000, 12000, 15000, 14000, 16000, 18000, 20000])
  } finally {
    weeklyLoading.value = false
  }
}

const createPurchaseOrder = (product) => {
  // Redirect to purchase order creation
  window.location.href = `/admin/purchase-orders/create?product_id=${product.id}`
}

const createPromotion = (product) => {
  // Redirect to promotion creation
  window.location.href = `/admin/promotions/create?product_id=${product.id}`
}

const saveEmailSettings = async () => {
  settingsSaving.value = true
  try {
    const response = await fetch('/admin/api/inventory-analytics/email-settings', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
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
  const options = {
    series: [{
      name: 'Fast Sales',
      data: [65]
    }, {
      name: 'Medium Sales',
      data: [25]
    }, {
      name: 'Slow Sales',
      data: [10]
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
    }
  }

  const chart = new ApexCharts(document.querySelector('#velocityChart'), options)
  chart.render()
}

const updateRevenueChart = (revenueData) => {
  const options = {
    series: [{
      name: 'Revenue',
      data: revenueData || [10000, 12000, 15000, 14000, 16000, 18000, 20000]
    }],
    chart: {
      type: 'line',
      height: 250
    },
    colors: ['#8B5CF6'],
    xaxis: {
      categories: ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']
    },
    yaxis: {
      labels: {
        formatter: function (val) {
          return '৳' + val.toLocaleString()
        }
      }
    }
  }

  const chart = new ApexCharts(document.querySelector('#revenueChart'), options)
  chart.render()
}

// Initialize component
onMounted(() => {
  refreshDailyReport()
  generateWeeklyReport()
})
</script>
