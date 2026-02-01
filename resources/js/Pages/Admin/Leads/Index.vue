<template>
  <AdminLayout>
    <div class="container mx-auto px-4 py-8">
      <div class="bg-white rounded-lg shadow-md">
        <div class="px-6 py-4 border-b border-gray-200">
          <div class="flex items-center justify-between">
            <div>
              <h1 class="text-2xl font-bold text-gray-900">Pre-Order Leads</h1>
              <p class="mt-1 text-sm text-gray-600">Manage pre-order lead submissions</p>
            </div>
          </div>
        </div>

        <div class="px-6 py-6">
          <!-- Status Filter -->
          <div class="mb-6">
            <div class="flex items-center space-x-4">
              <label class="text-sm font-medium text-gray-700">Filter by Status:</label>
              <select
                v-model="selectedStatus"
                @change="filterLeads"
                class="px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
              >
                <option value="">All Status</option>
                <option value="pending">Pending</option>
                <option value="contacted">Contacted</option>
                <option value="converted">Converted</option>
                <option value="rejected">Rejected</option>
              </select>
            </div>
          </div>

          <!-- Leads Table -->
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Lead Info
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Contact
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Product Interest
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Status
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Submitted
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Actions
                  </th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="lead in filteredLeads" :key="lead.id" class="hover:bg-gray-50">
                  <!-- Lead Info -->
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                      <div class="flex-shrink-0 h-10 w-10">
                        <div class="h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center">
                          <span class="text-sm font-medium text-gray-700">
                            {{ lead.name.charAt(0).toUpperCase() }}
                          </span>
                        </div>
                      </div>
                      <div class="ml-4">
                        <div class="text-sm font-medium text-gray-900">{{ lead.name }}</div>
                        <div class="text-sm text-gray-500">ID: {{ lead.id }}</div>
                      </div>
                    </div>
                  </td>

                  <!-- Contact -->
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-gray-900">
                      <a :href="`mailto:${lead.email}`" class="text-indigo-600 hover:text-indigo-500">
                        {{ lead.email }}
                      </a>
                    </div>
                    <div class="text-sm text-gray-500" v-if="lead.phone">
                      <a :href="`tel:${lead.phone}`" class="hover:text-indigo-500">
                        {{ lead.phone }}
                      </a>
                    </div>
                  </td>

                  <!-- Product Interest -->
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-gray-900">{{ lead.product_interest }}</div>
                  </td>

                  <!-- Status -->
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span
                      class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                      :class="getStatusClass(lead.status)"
                    >
                      {{ lead.status.charAt(0).toUpperCase() + lead.status.slice(1) }}
                    </span>
                  </td>

                  <!-- Submitted -->
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ formatDate(lead.created_at) }}
                  </td>

                  <!-- Actions -->
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                    <div class="flex space-x-2">
                      <Link
                        :href="route('admin.leads.show', lead.id)"
                        class="text-indigo-600 hover:text-indigo-900"
                      >
                        View
                      </Link>
                      <select
                        :value="lead.status"
                        @change="updateStatus(lead.id, $event.target.value)"
                        class="text-xs border border-gray-300 rounded px-2 py-1"
                      >
                        <option value="pending">Pending</option>
                        <option value="contacted">Contacted</option>
                        <option value="converted">Converted</option>
                        <option value="rejected">Rejected</option>
                      </select>
                      <button
                        @click="deleteLead(lead.id)"
                        class="text-red-600 hover:text-red-900 text-xs"
                      >
                        Delete
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Empty State -->
          <div v-if="filteredLeads.length === 0" class="text-center py-12">
            <div class="text-gray-500">
              <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              <h3 class="mt-2 text-sm font-medium text-gray-900">No leads found</h3>
              <p class="mt-1 text-sm text-gray-500">
                {{ selectedStatus ? `No ${selectedStatus} leads found.` : 'No pre-order leads have been submitted yet.' }}
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Link } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import { useToast } from '@/Composables/useToast'

const { success, error } = useToast()

const props = defineProps({
  leads: Array,
})

const selectedStatus = ref('')
const filteredLeads = computed(() => {
  if (!selectedStatus.value) {
    return props.leads
  }
  return props.leads.filter(lead => lead.status === selectedStatus.value)
})

const getStatusClass = (status) => {
  const classes = {
    pending: 'bg-yellow-100 text-yellow-800',
    contacted: 'bg-blue-100 text-blue-800',
    converted: 'bg-green-100 text-green-800',
    rejected: 'bg-red-100 text-red-800',
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

const filterLeads = () => {
  // Filtering is handled by the computed property
}

const updateStatus = (leadId, newStatus) => {
  fetch(`/admin/leads/${leadId}/status`, {
    method: 'PATCH',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
    },
    body: JSON.stringify({ status: newStatus }),
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      success('Lead status updated successfully!')
      // Update local data
      const lead = props.leads.find(l => l.id === leadId)
      if (lead) {
        lead.status = newStatus
      }
    } else {
      error(data.message || 'Failed to update lead status.')
    }
  })
  .catch(err => {
    console.error('Error:', err)
    error('An error occurred while updating the status.')
  })
}

const deleteLead = (leadId) => {
  if (!confirm('Are you sure you want to delete this lead? This action cannot be undone.')) {
    return
  }

  fetch(`/admin/leads/${leadId}`, {
    method: 'DELETE',
    headers: {
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
    },
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      success('Lead deleted successfully!')
      // Remove from local data
      const index = props.leads.findIndex(l => l.id === leadId)
      if (index > -1) {
        props.leads.splice(index, 1)
      }
    } else {
      error(data.message || 'Failed to delete lead.')
    }
  })
  .catch(err => {
    console.error('Error:', err)
    error('An error occurred while deleting the lead.')
  })
}
</script>