<script setup>
import { ref, computed, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'
import { 
  TrendingUp, 
  TrendingDown, 
  MapPin, 
  DollarSign, 
  Package, 
  BarChart3,
  Search,
  ArrowUpRight,
  Award,
  Target
} from 'lucide-vue-next'

const props = defineProps({
  locations: {
    type: Array,
    required: true
  },
  stats: {
    type: Object,
    required: true
  }
})

// State
const searchQuery = ref('')
const sortBy = ref('revenue') // 'revenue' | 'orders' | 'growth'
const viewMode = ref('grid') // 'grid' | 'map' | 'list'

// Computed
const filteredLocations = computed(() => {
  let filtered = props.locations

  // Search filter
  if (searchQuery.value) {
    filtered = filtered.filter(loc => 
      loc.district.toLowerCase().includes(searchQuery.value.toLowerCase())
    )
  }

  // Sort
  return [...filtered].sort((a, b) => {
    switch (sortBy.value) {
      case 'revenue':
        return b.total_revenue - a.total_revenue
      case 'orders':
        return b.total_orders - a.total_orders
      case 'growth':
        return b.growth - a.growth
      default:
        return 0
    }
  })
})

const topDistricts = computed(() => {
  return filteredLocations.value.slice(0, 5)
})

// Heatmap colors based on revenue
const getHeatmapColor = (revenue) => {
  const maxRevenue = Math.max(...props.locations.map(l => l.total_revenue))
  const intensity = (revenue / maxRevenue) * 100
  
  if (intensity >= 80) return 'from-red-500 to-red-600'
  if (intensity >= 60) return 'from-orange-500 to-orange-600'
  if (intensity >= 40) return 'from-yellow-500 to-yellow-600'
  if (intensity >= 20) return 'from-green-500 to-green-600'
  return 'from-blue-500 to-blue-600'
}

const getBadgeColor = (growth) => {
  if (growth > 20) return 'bg-green-100 text-green-800 border-green-300'
  if (growth > 0) return 'bg-blue-100 text-blue-800 border-blue-300'
  if (growth === 0) return 'bg-gray-100 text-gray-800 border-gray-300'
  return 'bg-red-100 text-red-800 border-red-300'
}

// Methods
const formatCurrency = (amount) => {
  return new Intl.NumberFormat('bn-BD', {
    style: 'currency',
    currency: 'BDT',
    minimumFractionDigits: 0
  }).format(amount)
}

const formatNumber = (num) => {
  return new Intl.NumberFormat('en-US').format(num)
}

const viewDistrictOrders = (district) => {
  router.get('/admin/orders', {
    shipping_district: district
  })
}

// Map initialization
onMounted(() => {
  if (viewMode.value === 'map') {
    initializeMap()
  }
})

const initializeMap = () => {
  const map = L.map('enhanced-order-map').setView([23.7, 90.4], 7)
  
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors',
    maxZoom: 19
  }).addTo(map)

  props.locations.forEach(loc => {
    if (loc.lat && loc.lng) {
      const maxRevenue = Math.max(...props.locations.map(l => l.total_revenue))
      const intensity = (loc.total_revenue / maxRevenue) * 100
      
      let color = '#3b82f6' // blue
      if (intensity >= 80) color = '#ef4444' // red
      else if (intensity >= 60) color = '#f97316' // orange
      else if (intensity >= 40) color = '#eab308' // yellow
      else if (intensity >= 20) color = '#22c55e' // green

      const customIcon = L.divIcon({
        className: 'custom-marker-enhanced',
        html: `
          <div style="
            background: ${color};
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 3px solid white;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            display: flex;
            align-items: center;
            justify-content: center;
          ">
            <span style="color: white; font-weight: bold; font-size: 14px;">
              ${loc.total_orders}
            </span>
          </div>
        `,
        iconSize: [40, 40],
        iconAnchor: [20, 20],
        popupAnchor: [0, -20]
      })

      const marker = L.marker([loc.lat, loc.lng], { icon: customIcon }).addTo(map)
      marker.bindPopup(`
        <div style="padding: 12px; min-width: 220px;">
          <h3 style="margin: 0 0 12px 0; font-size: 18px; font-weight: bold; color: ${color};">
            ${loc.district}
          </h3>
          <div style="display: flex; flex-direction: column; gap: 8px;">
            <div style="display: flex; justify-content: space-between;">
              <span style="color: #666;">Total Orders:</span>
              <strong style="color: #1f2937;">${formatNumber(loc.total_orders)}</strong>
            </div>
            <div style="display: flex; justify-content: space-between;">
              <span style="color: #666;">Revenue:</span>
              <strong style="color: #10b981;">${formatCurrency(loc.total_revenue)}</strong>
            </div>
            <div style="display: flex; justify-content: space-between;">
              <span style="color: #666;">Avg Order:</span>
              <strong style="color: #6366f1;">${formatCurrency(loc.avg_order_value)}</strong>
            </div>
            <div style="display: flex; justify-content: space-between;">
              <span style="color: #666;">Growth:</span>
              <strong style="color: ${loc.growth >= 0 ? '#10b981' : '#ef4444'};">
                ${loc.growth >= 0 ? '+' : ''}${loc.growth}%
              </strong>
            </div>
          </div>
          <button 
            onclick="window.location.href='/admin/orders?shipping_district=${loc.district}'"
            style="
              margin-top: 12px;
              width: 100%;
              background: ${color};
              color: white;
              padding: 8px;
              border-radius: 6px;
              border: none;
              cursor: pointer;
              font-weight: 600;
            "
          >
            View Orders →
          </button>
        </div>
      `)
    }
  })
}
</script>

<template>
  <div class="space-y-6">
    <!-- Header with Stats Cards -->
    <div class="bg-gradient-to-br from-indigo-600 via-purple-600 to-pink-600 rounded-2xl shadow-2xl p-8 text-white">
      <div class="flex items-center justify-between mb-6">
        <div>
          <h2 class="text-3xl font-bold flex items-center gap-3">
            <MapPin class="w-8 h-8" />
            District Performance Analytics
          </h2>
          <p class="text-indigo-100 mt-2">Last 30 days compared to previous period</p>
        </div>
        <div class="flex gap-2">
          <button 
            @click="viewMode = 'grid'"
            :class="[
              'px-4 py-2 rounded-lg font-semibold transition-all',
              viewMode === 'grid' 
                ? 'bg-white text-indigo-600' 
                : 'bg-white/20 hover:bg-white/30'
            ]"
          >
            Grid
          </button>
          <button 
            @click="viewMode = 'map'; setTimeout(initializeMap, 100)"
            :class="[
              'px-4 py-2 rounded-lg font-semibold transition-all',
              viewMode === 'map' 
                ? 'bg-white text-indigo-600' 
                : 'bg-white/20 hover:bg-white/30'
            ]"
          >
            Map
          </button>
          <button 
            @click="viewMode = 'list'"
            :class="[
              'px-4 py-2 rounded-lg font-semibold transition-all',
              viewMode === 'list' 
                ? 'bg-white text-indigo-600' 
                : 'bg-white/20 hover:bg-white/30'
            ]"
          >
            List
          </button>
        </div>
      </div>

      <!-- Quick Stats Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-indigo-100 text-sm">Total Districts</p>
              <p class="text-3xl font-bold mt-1">{{ stats.total_districts }}</p>
            </div>
            <Target class="w-10 h-10 text-indigo-200" />
          </div>
        </div>

        <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-indigo-100 text-sm">Total Orders</p>
              <p class="text-3xl font-bold mt-1">{{ formatNumber(stats.total_orders) }}</p>
            </div>
            <Package class="w-10 h-10 text-indigo-200" />
          </div>
        </div>

        <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-indigo-100 text-sm">Total Revenue</p>
              <p class="text-2xl font-bold mt-1">{{ formatCurrency(stats.total_revenue) }}</p>
            </div>
            <DollarSign class="w-10 h-10 text-indigo-200" />
          </div>
        </div>

        <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-indigo-100 text-sm">Top District</p>
              <p class="text-lg font-bold mt-1">{{ stats.top_district }}</p>
              <p class="text-sm text-indigo-200">{{ formatCurrency(stats.top_district_revenue) }}</p>
            </div>
            <Award class="w-10 h-10 text-yellow-300" />
          </div>
        </div>
      </div>
    </div>

    <!-- Top 5 Districts Leaderboard -->
    <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-200">
      <div class="flex items-center justify-between mb-6">
        <h3 class="text-xl font-bold text-gray-800 flex items-center gap-2">
          <Award class="w-6 h-6 text-yellow-500" />
          Top Performing Districts
        </h3>
        <div class="flex gap-2">
          <button 
            @click="sortBy = 'revenue'"
            :class="[
              'px-3 py-1.5 rounded-lg text-sm font-semibold transition-all',
              sortBy === 'revenue' 
                ? 'bg-indigo-100 text-indigo-700' 
                : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
            ]"
          >
            By Revenue
          </button>
          <button 
            @click="sortBy = 'orders'"
            :class="[
              'px-3 py-1.5 rounded-lg text-sm font-semibold transition-all',
              sortBy === 'orders' 
                ? 'bg-indigo-100 text-indigo-700' 
                : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
            ]"
          >
            By Orders
          </button>
          <button 
            @click="sortBy = 'growth'"
            :class="[
              'px-3 py-1.5 rounded-lg text-sm font-semibold transition-all',
              sortBy === 'growth' 
                ? 'bg-indigo-100 text-indigo-700' 
                : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
            ]"
          >
            By Growth
          </button>
        </div>
      </div>

      <div class="space-y-4">
        <div 
          v-for="(district, index) in topDistricts" 
          :key="district.district"
          class="group relative overflow-hidden rounded-xl border-2 border-gray-200 hover:border-indigo-400 transition-all cursor-pointer"
          @click="viewDistrictOrders(district.district)"
        >
          <!-- Rank Badge -->
          <div 
            :class="[
              'absolute top-0 left-0 w-12 h-12 flex items-center justify-center text-white font-bold text-lg',
              index === 0 ? 'bg-gradient-to-br from-yellow-400 to-yellow-600' : '',
              index === 1 ? 'bg-gradient-to-br from-gray-300 to-gray-500' : '',
              index === 2 ? 'bg-gradient-to-br from-orange-400 to-orange-600' : '',
              index > 2 ? 'bg-gradient-to-br from-blue-400 to-blue-600' : ''
            ]"
            style="clip-path: polygon(0 0, 100% 0, 100% 85%, 0 100%);"
          >
            {{ index + 1 }}
          </div>

          <!-- Heatmap Background -->
          <div 
            :class="['absolute inset-0 opacity-5 bg-gradient-to-r', getHeatmapColor(district.total_revenue)]"
          ></div>

          <!-- Content -->
          <div class="relative p-6 pl-16">
            <div class="flex items-center justify-between">
              <div class="flex-1">
                <h4 class="text-xl font-bold text-gray-800 group-hover:text-indigo-600 transition-colors">
                  {{ district.district }}
                </h4>
                <div class="flex items-center gap-4 mt-2">
                  <div class="flex items-center gap-1 text-sm text-gray-600">
                    <Package class="w-4 h-4" />
                    <span class="font-semibold">{{ formatNumber(district.total_orders) }}</span>
                    <span>orders</span>
                  </div>
                  <div class="flex items-center gap-1 text-sm text-green-600 font-semibold">
                    <DollarSign class="w-4 h-4" />
                    {{ formatCurrency(district.total_revenue) }}
                  </div>
                  <div class="text-sm text-gray-500">
                    Avg: {{ formatCurrency(district.avg_order_value) }}
                  </div>
                </div>
              </div>

              <!-- Growth Badge -->
              <div class="flex flex-col items-end gap-2">
                <div :class="['px-3 py-1.5 rounded-full text-sm font-bold border-2 flex items-center gap-1', getBadgeColor(district.growth)]">
                  <TrendingUp v-if="district.growth > 0" class="w-4 h-4" />
                  <TrendingDown v-else-if="district.growth < 0" class="w-4 h-4" />
                  <span>{{ district.growth >= 0 ? '+' : '' }}{{ district.growth }}%</span>
                </div>
                <button 
                  class="flex items-center gap-1 text-indigo-600 hover:text-indigo-700 font-semibold text-sm group-hover:translate-x-1 transition-transform"
                >
                  <span>View Orders</span>
                  <ArrowUpRight class="w-4 h-4" />
                </button>
              </div>
            </div>

            <!-- Progress Bar -->
            <div class="mt-4 h-2 bg-gray-200 rounded-full overflow-hidden">
              <div 
                :class="['h-full bg-gradient-to-r transition-all duration-500', getHeatmapColor(district.total_revenue)]"
                :style="{ width: `${(district.total_revenue / stats.total_revenue) * 100}%` }"
              ></div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Map/Grid/List View -->
    <div v-if="viewMode === 'map'" class="bg-white rounded-xl shadow-lg p-6 border border-gray-200">
      <div id="enhanced-order-map" class="h-[600px] rounded-lg"></div>
    </div>

    <!-- Grid View -->
    <div v-else-if="viewMode === 'grid'" class="space-y-4">
      <!-- Search Bar -->
      <div class="bg-white rounded-xl shadow-lg p-4 border border-gray-200">
        <div class="relative">
          <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5" />
          <input 
            v-model="searchQuery"
            type="text"
            placeholder="Search districts..."
            class="w-full pl-10 pr-4 py-2.5 border-2 border-gray-200 rounded-lg focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition-all"
          />
        </div>
      </div>

      <!-- Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div 
          v-for="district in filteredLocations"
          :key="district.district"
          class="bg-white rounded-xl shadow-md hover:shadow-xl border-2 border-gray-200 hover:border-indigo-400 transition-all cursor-pointer group"
          @click="viewDistrictOrders(district.district)"
        >
          <div :class="['h-2 rounded-t-xl bg-gradient-to-r', getHeatmapColor(district.total_revenue)]"></div>
          
          <div class="p-5">
            <div class="flex items-start justify-between mb-4">
              <h4 class="text-lg font-bold text-gray-800 group-hover:text-indigo-600 transition-colors">
                {{ district.district }}
              </h4>
              <div :class="['px-2 py-1 rounded-full text-xs font-bold border flex items-center gap-1', getBadgeColor(district.growth)]">
                <TrendingUp v-if="district.growth > 0" class="w-3 h-3" />
                <TrendingDown v-else-if="district.growth < 0" class="w-3 h-3" />
                <span>{{ district.growth >= 0 ? '+' : '' }}{{ district.growth }}%</span>
              </div>
            </div>

            <div class="space-y-3">
              <div class="flex items-center justify-between">
                <span class="text-sm text-gray-600">Orders</span>
                <span class="font-bold text-gray-800">{{ formatNumber(district.total_orders) }}</span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-sm text-gray-600">Revenue</span>
                <span class="font-bold text-green-600">{{ formatCurrency(district.total_revenue) }}</span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-sm text-gray-600">Avg Order</span>
                <span class="font-semibold text-indigo-600">{{ formatCurrency(district.avg_order_value) }}</span>
              </div>
            </div>

            <button class="mt-4 w-full bg-gradient-to-r from-indigo-600 to-purple-600 text-white py-2 rounded-lg font-semibold hover:from-indigo-700 hover:to-purple-700 transition-all flex items-center justify-center gap-2 group-hover:scale-105">
              <span>View Orders</span>
              <ArrowUpRight class="w-4 h-4" />
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- List View -->
    <div v-else class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden">
      <!-- Search Bar -->
      <div class="p-4 border-b border-gray-200 bg-gray-50">
        <div class="relative">
          <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5" />
          <input 
            v-model="searchQuery"
            type="text"
            placeholder="Search districts..."
            class="w-full pl-10 pr-4 py-2.5 border-2 border-gray-200 rounded-lg focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition-all"
          />
        </div>
      </div>

      <!-- Table -->
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white">
            <tr>
              <th class="px-6 py-4 text-left font-semibold">Rank</th>
              <th class="px-6 py-4 text-left font-semibold">District</th>
              <th class="px-6 py-4 text-right font-semibold">Orders</th>
              <th class="px-6 py-4 text-right font-semibold">Revenue</th>
              <th class="px-6 py-4 text-right font-semibold">Avg Order</th>
              <th class="px-6 py-4 text-center font-semibold">Growth</th>
              <th class="px-6 py-4 text-center font-semibold">Action</th>
            </tr>
          </thead>
          <tbody>
            <tr 
              v-for="(district, index) in filteredLocations"
              :key="district.district"
              :class="[
                'border-b border-gray-200 hover:bg-indigo-50 transition-colors cursor-pointer',
                index % 2 === 0 ? 'bg-white' : 'bg-gray-50'
              ]"
              @click="viewDistrictOrders(district.district)"
            >
              <td class="px-6 py-4">
                <div class="flex items-center gap-2">
                  <div 
                    :class="[
                      'w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm text-white',
                      index < 3 ? 'bg-gradient-to-br from-yellow-400 to-yellow-600' : 'bg-gray-400'
                    ]"
                  >
                    {{ index + 1 }}
                  </div>
                </div>
              </td>
              <td class="px-6 py-4">
                <span class="font-semibold text-gray-800">{{ district.district }}</span>
              </td>
              <td class="px-6 py-4 text-right font-semibold text-gray-800">
                {{ formatNumber(district.total_orders) }}
              </td>
              <td class="px-6 py-4 text-right font-bold text-green-600">
                {{ formatCurrency(district.total_revenue) }}
              </td>
              <td class="px-6 py-4 text-right font-semibold text-indigo-600">
                {{ formatCurrency(district.avg_order_value) }}
              </td>
              <td class="px-6 py-4">
                <div class="flex justify-center">
                  <div :class="['px-3 py-1 rounded-full text-sm font-bold border flex items-center gap-1', getBadgeColor(district.growth)]">
                    <TrendingUp v-if="district.growth > 0" class="w-3 h-3" />
                    <TrendingDown v-else-if="district.growth < 0" class="w-3 h-3" />
                    <span>{{ district.growth >= 0 ? '+' : '' }}{{ district.growth }}%</span>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 text-center">
                <button class="text-indigo-600 hover:text-indigo-700 font-semibold flex items-center gap-1 mx-auto">
                  <span>View</span>
                  <ArrowUpRight class="w-4 h-4" />
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<style scoped>
#enhanced-order-map {
  width: 100%;
  height: 600px;
}

/* Custom marker animation */
:deep(.custom-marker-enhanced) {
  animation: markerPulse 2s ease-in-out infinite;
}

@keyframes markerPulse {
  0%, 100% {
    transform: scale(1);
  }
  50% {
    transform: scale(1.1);
  }
}

/* Leaflet popup customization */
:deep(.leaflet-popup-content-wrapper) {
  border-radius: 16px;
  box-shadow: 0 12px 40px rgba(0, 0, 0, 0.2);
  padding: 0;
}

:deep(.leaflet-popup-content) {
  margin: 0;
}

:deep(.leaflet-popup-tip) {
  display: none;
}
</style>
