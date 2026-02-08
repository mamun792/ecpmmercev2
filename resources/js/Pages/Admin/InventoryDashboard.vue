<template>
  <AdminLayout>
    <Head :title="title" />

    <div class="p-6 bg-gray-50 min-h-screen">
      <!-- Toast Notifications -->
      <div v-if="success === false" class="alert alert-error mb-6">
        <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <span>{{ error || 'Something went wrong' }}</span>
      </div>

      <!-- Main Dashboard Component -->
      <InventoryAnalyticsDashboard
        v-if="success !== false"
        :initialData="dashboardData"
        :reorderAlerts="reorderAlerts"
      />
    </div>
  </AdminLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import InventoryAnalyticsDashboard from '@/Components/InventoryAnalyticsDashboard.vue'

// Props from Laravel controller
defineProps({
  title: {
    type: String,
    default: 'Inventory Analytics Dashboard'
  },
  dashboardData: {
    type: Object,
    default: () => ({})
  },
  reorderAlerts: {
    type: Object,
    default: () => ({})
  },
  success: {
    type: Boolean,
    default: true
  },
  error: {
    type: String,
    default: null
  }
})
</script>
