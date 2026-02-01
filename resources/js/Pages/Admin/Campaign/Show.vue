<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
  campaign: Object,
});

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  });
};
</script>

<template>
  <Head title="Campaign Details" />
  <AdminLayout>
    <div class="px-3 py-4 mx-auto bg-white dark:bg-gray-800">
      <div class="flex justify-between items-center mb-4">
        <h1 class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ campaign.name }}</h1>
        <Link
          :href="route('admin.campaigns.edit', campaign.id)"
          class="px-4 py-2 bg-blue-600 border border-transparent rounded-md text-xs font-semibold text-white hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600"
        >
          Edit Campaign
        </Link>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Campaign Details</h3>
          <div class="space-y-2">
            <p><strong>Name:</strong> {{ campaign.name }}</p>
            <p><strong>Start Date:</strong> {{ formatDate(campaign.start_date) }}</p>
            <p><strong>End Date:</strong> {{ formatDate(campaign.end_date) }}</p>
            <p><strong>Status:</strong>
              <span :class="campaign.status === 'active' ? 'text-green-600' : 'text-red-600'">
                {{ campaign.status }}
              </span>
            </p>
            <p><strong>Discount Type:</strong> {{ campaign.discount_type }}</p>
            <p><strong>Discount Amount:</strong> {{ campaign.discount_amount }}</p>
          </div>
        </div>

        <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Products ({{ campaign.products.length }})</h3>
          <div class="space-y-2 max-h-60 overflow-y-auto">
            <div v-for="product in campaign.products" :key="product.id" class="flex justify-between items-center p-2 bg-white dark:bg-gray-600 rounded">
              <span>{{ product.name }}</span>
              <span class="text-sm text-gray-500">ID: {{ product.id }}</span>
            </div>
            <p v-if="!campaign.products.length" class="text-gray-500">No products assigned</p>
          </div>
        </div>
      </div>

      <div class="flex justify-start">
        <Link
          href="/admin/campaigns"
          class="px-4 py-2 bg-gray-600 border border-transparent rounded-md text-xs font-semibold text-white hover:bg-gray-700"
        >
          Back to Campaigns
        </Link>
      </div>
    </div>
  </AdminLayout>
</template>