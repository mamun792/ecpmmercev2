<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { defineProps } from 'vue';

const props = defineProps({
  coupon: Object,
});

// Format dates for datetime-local input
const formatDateForInput = (dateString) => {
  if (!dateString) return '';
  const date = new Date(dateString);
  // Adjust for local timezone offset to display correct date and time
  const offset = date.getTimezoneOffset();
  const adjustedDate = new Date(date.getTime() - offset * 60 * 1000);
  return adjustedDate.toISOString().slice(0, 16);
};

const form = useForm({
  code: props.coupon.data.code || '',
  name: props.coupon.data.name || '',
  discount_value: props.coupon.data.discount_value || '',
  discount_type: props.coupon.data.discount_type || 'percentage',
  max_uses: props.coupon.data.max_uses || '',
  start_date: formatDateForInput(props.coupon.data.start_date),
  expiry_date: formatDateForInput(props.coupon.data.expiry_date),
  is_active: props.coupon.data.is_active ? '1' : '0',
});

const submit = () => {
  form.put(route('admin.coupons.update', props.coupon.data.id), {
    onSuccess: () => {
      // Optionally reset form or redirect
    },
    onError: (errors) => {
      console.error(errors);
    },
  });
};
</script>

<template>
  <Head title="Edit Coupon" />
  <AdminLayout>
    <div class="p-4 max-w-7xl mx-auto bg-white dark:bg-gray-800 min-h-screen">
      <h1 class="text-2xl font-bold mb-6 text-gray-900 dark:text-gray-100">Edit Coupon</h1>
      
      <form @submit.prevent="submit" class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- Coupon Code -->
          <div>
            <label for="code" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              Coupon Code <span class="text-red-600">*</span>
            </label>
            <input
              v-model="form.code"
              type="text"
              id="code"
              class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
              :class="{ 'border-red-500': form.errors.code }"
              required
            />
            <p v-if="form.errors.code" class="mt-1 text-sm text-red-600">{{ form.errors.code }}</p>
          </div>

          <!-- Coupon Name -->
          <div>
            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              Coupon Name <span class="text-red-600">*</span>
            </label>
            <input
              v-model="form.name"
              type="text"
              id="name"
              class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
              :class="{ 'border-red-500': form.errors.name }"
              required
            />
            <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
          </div>

          <!-- Discount Value -->
          <div>
            <label for="discount_value" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              Discount Value <span class="text-red-600">*</span>
            </label>
            <input
              v-model="form.discount_value"
              type="number"
              step="0.01"
              id="discount_value"
              class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
              :class="{ 'border-red-500': form.errors.discount_value }"
              required
            />
            <p v-if="form.errors.discount_value" class="mt-1 text-sm text-red-600">{{ form.errors.discount_value }}</p>
          </div>

          <!-- Discount Type -->
          <div>
            <label for="discount_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              Discount Type <span class="text-red-600">*</span>
            </label>
            <select
              v-model="form.discount_type"
              id="discount_type"
              class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
              :class="{ 'border-red-500': form.errors.discount_type }"
              required
            >
              <option value="percentage">Percentage</option>
              <option value="fixed">Fixed Amount</option>
            </select>
            <p v-if="form.errors.discount_type" class="mt-1 text-sm text-red-600">{{ form.errors.discount_type }}</p>
          </div>

          <!-- Max Uses -->
          <div>
            <label for="max_uses" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Maximum Uses</label>
            <input
              v-model="form.max_uses"
              type="number"
              id="max_uses"
              class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
              :class="{ 'border-red-500': form.errors.max_uses }"
            />
            <p v-if="form.errors.max_uses" class="mt-1 text-sm text-red-600">{{ form.errors.max_uses }}</p>
          </div>

          <!-- Start Date -->
          <div>
            <label for="start_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              Start Date <span class="text-red-600">*</span>
            </label>
            <input
              v-model="form.start_date"
              type="datetime-local"
              id="start_date"
              class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
              :class="{ 'border-red-500': form.errors.start_date }"
              required
            />
            <p v-if="form.errors.start_date" class="mt-1 text-sm text-red-600">{{ form.errors.start_date }}</p>
          </div>

          <!-- Expiry Date -->
          <div>
            <label for="expiry_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              Expiry Date <span class="text-red-600">*</span>
            </label>
            <input
              v-model="form.expiry_date"
              type="datetime-local"
              id="expiry_date"
              class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
              :class="{ 'border-red-500': form.errors.expiry_date }"
              required
            />
            <p v-if="form.errors.expiry_date" class="mt-1 text-sm text-red-600">{{ form.errors.expiry_date }}</p>
          </div>



          <!-- Is Active -->
          <div>
            <label for="is_active" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
            <select
              v-model="form.is_active"
              id="is_active"
              class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
              :class="{ 'border-red-500': form.errors.is_active }"
              required
            >
              <option value="1">Active</option>
              <option value="0">Deactive</option>
            </select>
            <p v-if="form.errors.is_active" class="mt-1 text-sm text-red-600">{{ form.errors.is_active }}</p>
          </div>
        </div>

        <!-- Submit Button -->
        <div>
          <button
            type="submit"
            :disabled="form.processing"
            class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-white hover:bg-blue-700 disabled:opacity-50 dark:bg-blue-500 dark:hover:bg-blue-600"
          >
            Update Coupon
          </button>
        </div>
      </form>
    </div>
  </AdminLayout>
</template>