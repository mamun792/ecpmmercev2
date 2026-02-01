<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { ref, onMounted } from 'vue'
import { Head } from '@inertiajs/vue3'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'

const props = defineProps({
  locations: Array
})

onMounted(() => {
  const map = L.map('order-map').setView([23.7, 90.4], 7) // Bangladesh center

  // Light, colorful map theme
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors',
    maxZoom: 19
    }).addTo(map);

// L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Street_Map/MapServer/tile/{z}/{y}/{x}', {
//   attribution: 'Tiles © Esri — Source: Esri, DeLorme, NAVTEQ, USGS, and others',
//   maxZoom: 19
// }).addTo(map);


  props.locations.forEach(loc => {
    if (loc.lat && loc.lng) {
      // Custom colorful marker icon
      const customIcon = L.divIcon({
        className: 'custom-marker',
        html: `
          <div style="
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            width: 36px;
            height: 36px;
            border-radius: 50% 50% 50% 0;
            transform: rotate(-45deg);
            border: 3px solid white;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            display: flex;
            align-items: center;
            justify-content: center;
          ">
            <span style="
              transform: rotate(45deg);
              color: white;
              font-weight: bold;
              font-size: 14px;
            ">📦</span>
          </div>
        `,
        iconSize: [36, 36],
        iconAnchor: [18, 36],
        popupAnchor: [0, -36]
      })

      const marker = L.marker([loc.lat, loc.lng], { icon: customIcon }).addTo(map)
      marker.bindPopup(`
        <div style="padding: 8px; min-width: 150px;">
          <h3 style="margin: 0 0 8px 0; font-size: 16px; font-weight: bold; color: #667eea;">
            ${loc.district}
          </h3>
          <p style="margin: 0; color: #666; font-size: 14px;">
            <strong>Total Orders:</strong> 
            <span style="color: #764ba2; font-weight: bold;">${loc.total_orders}</span>
          </p>
        </div>
      `)
    }
  })
})
</script>

<template>
    <Head>
        <title>District Wise Orders</title>
    </Head>
    <AdminLayout>
        <div class="p-6 space-y-6">
            <div class="flex flex-col lg:flex-row gap-6">
                <div class="map_area lg:w-3/4 w-full">
                    <h1 class="text-2xl font-bold mb-4 text-gray-800">📦 District Wise Orders</h1>
                    <!-- Map -->
                    <div id="order-map" class="h-[600px] rounded-lg shadow-lg border-2 border-gray-100"></div>
                </div>

                <!-- List -->
                <div class="bg-gradient-to-br from-white to-gray-50 w-full lg:w-1/4 shadow-lg rounded-lg p-6 border border-gray-100">
                    <h2 class="text-lg font-semibold mb-4 text-gray-800 flex items-center gap-2">
                        <span class="text-2xl">🧾</span> Order Count by District
                    </h2>
                    <div class="overflow-x-auto">
                        <table class="min-w-full border-collapse">
                            <thead>
                            <tr class="bg-gradient-to-r from-purple-500 to-indigo-600 text-white">
                                <th class="text-left px-4 py-3 rounded-tl-lg font-semibold">District</th>
                                <th class="text-right px-4 py-3 rounded-tr-lg font-semibold">Total Orders</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr 
                                v-for="(loc, index) in props.locations" 
                                :key="loc.district" 
                                :class="index % 2 === 0 ? 'bg-purple-50' : 'bg-white'"
                                class="border-b border-gray-200 hover:bg-purple-100 transition-colors duration-150"
                            >
                                <td class="px-4 py-3 font-medium text-gray-700">{{ loc.district }}</td>
                                <td class="px-4 py-3 text-right">
                                    <span class="inline-block bg-gradient-to-r from-purple-500 to-indigo-600 text-white px-3 py-1 rounded-full text-sm font-bold">
                                        {{ loc.total_orders }}
                                    </span>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
#order-map {
  width: 100%;
  height: 600px;
}

/* Custom marker animation */
:deep(.custom-marker) {
  animation: markerBounce 0.5s ease-out;
}

@keyframes markerBounce {
  0% {
    transform: translateY(-20px);
    opacity: 0;
  }
  50% {
    transform: translateY(5px);
  }
  100% {
    transform: translateY(0);
    opacity: 1;
  }
}

/* Leaflet popup customization */
:deep(.leaflet-popup-content-wrapper) {
  border-radius: 12px;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
}

:deep(.leaflet-popup-tip) {
  background: white;
}
</style>