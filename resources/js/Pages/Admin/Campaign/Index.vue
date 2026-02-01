<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { defineProps, ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { SquarePen, Trash2Icon, PlusIcon } from 'lucide-vue-next';
import DeleteModal from '@/Components/Modal/DeleteModal.vue';

const props = defineProps({
  campaigns: Object,
});

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  });
};

// Delete Modal Logic
const showDeleteModal = ref(false);
const campaignToDelete = ref(null);

const openDeleteModal = (id) => {
  campaignToDelete.value = id;
  showDeleteModal.value = true;
};

const handleDeleteSuccess = () => {
  showDeleteModal.value = false;
  campaignToDelete.value = null;
};
</script>

<template>
  <Head title="Campaigns" />
  <AdminLayout>
    <div class="px-3 py-4 mx-auto bg-white dark:bg-gray-800">
      <div class="flex justify-between items-center mb-4">
        <h1 class="text-xl font-bold text-gray-900 dark:text-gray-100">Campaigns</h1>
        <Link
          :href="route('admin.campaigns.create')"
          class="inline-flex items-center px-3 py-1 bg-blue-600 border border-transparent rounded-md text-xs font-semibold text-white hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600"
        >
          Create Campaign
        </Link>
      </div>

      <!-- Campaign Table -->
      <div class="overflow-x-auto shadow-md rounded-lg">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
          <thead class="bg-gray-50 dark:bg-gray-900">
            <tr>
              <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Name</th>
              <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Discount</th>
              <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Type</th>
              <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Start Date</th>
              <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">End Date</th>
              <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Products</th>
              <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
              <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
            <tr v-for="campaign in campaigns.data" :key="campaign.id" class="hover:bg-gray-50 dark:hover:bg-gray-700">
              <td class="px-3 py-2 whitespace-nowrap text-xs text-gray-900 dark:text-gray-100">{{ campaign.name }}</td>
              <td class="px-3 py-2 whitespace-nowrap text-xs text-gray-900 dark:text-gray-100">{{ campaign.discount_amount }}</td>
              <td class="px-3 py-2 whitespace-nowrap text-xs">
                <span
                  :class="[
                    campaign.discount_type === 'percentage'
                      ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300'
                      : 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300',
                    'px-1 inline-flex text-xs leading-4 font-semibold rounded-full'
                  ]"
                >
                  {{ campaign.discount_type }}
                </span>
              </td>
              <td class="px-3 py-2 whitespace-nowrap text-xs text-gray-900 dark:text-gray-100">{{ formatDate(campaign.start_date) }}</td>
              <td class="px-3 py-2 whitespace-nowrap text-xs text-gray-900 dark:text-gray-100">{{ formatDate(campaign.end_date) }}</td>
              <td class="px-3 py-2 whitespace-nowrap text-xs text-gray-900 dark:text-gray-100">{{ campaign.products?.length || 0 }}</td>
              <td class="px-3 py-2 whitespace-nowrap text-xs">
                <span
                  :class="[
                    campaign.status === 'active'
                      ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300'
                      : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
                    'px-1 inline-flex text-xs leading-4 font-semibold rounded-full'
                  ]"
                >
                  {{ campaign.status }}
                </span>
              </td>
              <td class="px-3 py-2 whitespace-nowrap text-xs flex space-x-2">
                <!-- Edit Link -->
                <div class="relative group">
                  <Link
                    :href="route('admin.campaigns.edit', campaign.id)"
                    class="table_edit_action hover:bg-green-500 hover:text-white p-2 rounded"
                  >
                    <SquarePen size="16" />
                  </Link>
                  <span
                    class="absolute -top-8 left-1/2 transform -translate-x-1/2 scale-0 group-hover:scale-100 transition bg-gray-800 text-white text-xs rounded px-2 py-1"
                  >
                    Edit
                  </span>
                </div>
                <!-- Delete Button -->
                <div class="relative group">
                  <button
                    @click="openDeleteModal(campaign.id)"
                    class="table_delete_action hover:bg-red-500 hover:text-white p-2 rounded"
                  >
                    <Trash2Icon size="16" />
                  </button>
                  <span
                    class="absolute -top-8 left-1/2 transform -translate-x-1/2 scale-0 group-hover:scale-100 transition bg-gray-800 text-white text-xs rounded px-2 py-1"
                  >
                    Delete
                  </span>
                </div>
              </td>
            </tr>
            <tr v-if="!campaigns.data.length">
              <td colspan="8" class="px-3 py-2 text-center text-xs text-gray-500 dark:text-gray-400">
                No campaigns found.
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div class="mt-4 flex justify-between items-center">
        <div class="text-xs text-gray-700 dark:text-gray-300">
          Showing {{ campaigns.from }} to {{ campaigns.to }} of {{ campaigns.total }}
        </div>
        <div class="flex space-x-1">
          <template v-for="(link, index) in campaigns.links" :key="index">
            <Link
              v-if="link.url"
              :href="link.url"
              class="px-2 py-1 text-xs font-medium rounded-md"
              :class="[
                link.active
                  ? 'bg-blue-600 text-white dark:bg-blue-500'
                  : 'bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600'
              ]"
              v-html="link.label"
            />
            <span
              v-else
              class="px-2 py-1 text-xs font-medium rounded-md bg-gray-200 text-gray-400 dark:bg-gray-700 dark:text-gray-500 opacity-50 cursor-not-allowed"
              v-html="link.label"
            />
          </template>
        </div>
      </div>

      <!-- Delete Confirmation Modal using Reusable Component -->
      <DeleteModal
        :item-id="campaignToDelete"
        item-name="campaign"
        route-name="admin.campaigns.destroy"
        v-model:visible="showDeleteModal"
        @deleted="handleDeleteSuccess"
      />
    </div>
  </AdminLayout>
</template>