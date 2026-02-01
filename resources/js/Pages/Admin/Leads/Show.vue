<template>
  <AdminLayout>
    <div class="container mx-auto px-4 py-8">
      <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-lg shadow-md">
          <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
              <div>
                <h1 class="text-2xl font-bold text-gray-900">Lead Details #{{ lead.id }}</h1>
                <p class="mt-1 text-sm text-gray-600">Submitted on {{ formatDate(lead.created_at) }}</p>
              </div>
              <Link
                href="/admin/leads"
                class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded"
              >
                Back to Leads
              </Link>
            </div>
          </div>

          <div class="px-6 py-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- Personal Information -->
              <div class="space-y-4">
                <h3 class="text-lg font-medium text-gray-900">Personal Information</h3>

                <div>
                  <label class="block text-sm font-medium text-gray-700">Full Name</label>
                  <p class="mt-1 text-sm text-gray-900">{{ lead.name }}</p>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700">Email Address</label>
                  <p class="mt-1 text-sm text-gray-900">
                    <a :href="`mailto:${lead.email}`" class="text-indigo-600 hover:text-indigo-500">
                      {{ lead.email }}
                    </a>
                  </p>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700">Phone Number</label>
                  <p class="mt-1 text-sm text-gray-900">
                    <a v-if="lead.phone" :href="`tel:${lead.phone}`" class="text-indigo-600 hover:text-indigo-500">
                      {{ lead.phone }}
                    </a>
                    <span v-else class="text-gray-500">Not provided</span>
                  </p>
                </div>
              </div>

              <!-- Lead Information -->
              <div class="space-y-4">
                <h3 class="text-lg font-medium text-gray-900">Lead Information</h3>

                <div>
                  <label class="block text-sm font-medium text-gray-700">Product Interest</label>
                  <p class="mt-1 text-sm text-gray-900">{{ lead.product_interest }}</p>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700">Status</label>
                  <div class="mt-1">
                    <select
                      :value="lead.status"
                      @change="updateStatus($event.target.value)"
                      class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                    >
                      <option value="pending">Pending</option>
                      <option value="contacted">Contacted</option>
                      <option value="converted">Converted</option>
                      <option value="rejected">Rejected</option>
                    </select>
                  </div>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700">Submitted At</label>
                  <p class="mt-1 text-sm text-gray-900">{{ formatDate(lead.created_at) }}</p>
                </div>
              </div>
            </div>

            <!-- Message -->
            <div class="mt-6">
              <label class="block text-sm font-medium text-gray-700">Message</label>
              <div class="mt-1 p-4 bg-gray-50 rounded-md">
                <p v-if="lead.message" class="text-sm text-gray-900 whitespace-pre-wrap">
                  {{ lead.message }}
                </p>
                <p v-else class="text-sm text-gray-500 italic">No message provided</p>
              </div>
            </div>

            <!-- Image -->
            <div v-if="lead.image_path" class="mt-6">
              <label class="block text-sm font-medium text-gray-700">Uploaded Image</label>
              <div class="mt-2">
                <img
                  :src="lead.image_path"
                  alt="Lead uploaded image"
                  class="max-w-full h-64 object-cover rounded-md shadow-md"
                />
              </div>
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
import { ref } from 'vue'
import { useToast } from '@/Composables/useToast'

const { success, error } = useToast()

const props = defineProps({
  lead: Object,
})

const lead = ref({ ...props.lead })

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

const updateStatus = (newStatus) => {
  fetch(`/admin/leads/${lead.value.id}/status`, {
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
      // Update local state
      lead.value.status = newStatus
    } else {
      error(data.message || 'Failed to update lead status.')
    }
  })
  .catch(err => {
    console.error('Error:', err)
    error('An error occurred while updating the status.')
  })
}
</script>
