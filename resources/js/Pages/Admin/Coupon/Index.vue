<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { defineProps, ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { SquarePen, Trash2Icon, PlusIcon } from 'lucide-vue-next';
import DeleteModal from '@/Components/Modal/DeleteModal.vue';

const props = defineProps({
  coupons: Object,
});

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  });
};

const getUsesStatus = (uses_count, max_uses) => {
  if (uses_count >= max_uses) return 'finished';
  if (max_uses && uses_count / max_uses >= 0.8) return 'almost';
  return 'available';
};

// Delete Modal Logic
const showDeleteModal = ref(false);
const couponToDelete = ref(null);

const openDeleteModal = (id) => {
  couponToDelete.value = id;
  showDeleteModal.value = true;
};

const handleDeleteSuccess = () => {
  showDeleteModal.value = false;
  couponToDelete.value = null;
};
</script>

<template>
  <Head title="Coupons" />
  <AdminLayout>
    <div class="px-3 py-4  mx-auto bg-white dark:bg-gray-800">
      <div class="flex justify-between items-center mb-4">
        <h1 class="text-xl font-bold text-gray-900 dark:text-gray-100">Coupons</h1>
        <Link
          :href="route('admin.coupons.create')"
          class="inline-flex items-center px-3 py-1 bg-blue-600 border border-transparent rounded-md text-xs font-semibold text-white hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600"
        >
          Create Coupon
        </Link>
      </div>

      <!-- Coupon Table -->
      <div class="overflow-x-auto shadow-md rounded-lg">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
          <thead class="bg-gray-50 dark:bg-gray-900">
            <tr>
              <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Code</th>
              <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Name</th>
              <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Disc.</th>
              <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Type</th>
              <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Uses</th>
              <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Start</th>
              <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Expiry</th>
              <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Fest.</th>
              <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">All Prod.</th>
              <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
              <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
            <tr v-for="coupon in coupons.data" :key="coupon.id" class="hover:bg-gray-50 dark:hover:bg-gray-700">
              <td class="px-3 py-2 whitespace-nowrap text-xs text-gray-900 dark:text-gray-100">{{ coupon.code }}</td>
              <td class="px-3 py-2 whitespace-nowrap text-xs text-gray-900 dark:text-gray-100">{{ coupon.name }}</td>
              <td class="px-3 py-2 whitespace-nowrap text-xs text-gray-900 dark:text-gray-100">{{ coupon.discount_value }}</td>
              <td class="px-3 py-2 whitespace-nowrap text-xs">
                <span
                  :class="[
                    coupon.discount_type.toLowerCase() === 'percentage'
                      ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300'
                      : 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300',
                    'px-1 inline-flex text-xs leading-4 font-semibold rounded-full'
                  ]"
                >
                  {{ coupon.discount_type }}
                </span>
              </td>
              <td class="px-3 py-2 whitespace-nowrap text-xs">
                <span
                  :class="[
                    getUsesStatus(coupon.uses_count, coupon.max_uses) === 'finished'
                      ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300'
                      : getUsesStatus(coupon.uses_count, coupon.max_uses) === 'almost'
                      ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300'
                      : 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
                    'px-1 inline-flex text-xs leading-4 font-semibold rounded-full'
                  ]"
                >
                  {{ coupon.uses_count }}/{{ coupon.max_uses }}
                </span>
              </td>
              <td class="px-3 py-2 whitespace-nowrap text-xs text-gray-900 dark:text-gray-100">{{ formatDate(coupon.start_date) }}</td>
              <td class="px-3 py-2 whitespace-nowrap text-xs text-gray-900 dark:text-gray-100">{{ formatDate(coupon.expiry_date) }}</td>
              <td class="px-3 py-2 whitespace-nowrap text-xs text-gray-900 dark:text-gray-100">{{ coupon.festival_name || '-' }}</td>
              <td class="px-3 py-2 whitespace-nowrap text-xs">
                <span
                  :class="[
                    coupon.apply_to_all_products
                      ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300'
                      : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
                    'px-1 inline-flex text-xs leading-4 font-semibold rounded-full'
                  ]"
                >
                  {{ coupon.apply_to_all_products ? 'Yes' : 'No' }}
                </span>
              </td>
              <td class="px-3 py-2 whitespace-nowrap text-xs">
                <span
                  :class="[
                    coupon.is_active
                      ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300'
                      : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
                    'px-1 inline-flex text-xs leading-4 font-semibold rounded-full'
                  ]"
                >
                  {{ coupon.is_active ? 'Active' : 'Inactive' }}
                </span>
              </td>
              <td class="px-3 py-2 whitespace-nowrap text-xs flex space-x-2">
                <!-- assign product to coupon -->

                <div v-show="!coupon.apply_to_all_products" class="relative group">
                  <Link
                    :href="route('admin.coupons.addedToProducts', coupon.id)"
                    class="table_view_action hover:bg-green-500 hover:text-white p-2 rounded"
                  >
                    <PlusIcon size="16" />
                  </Link>
                  <span
                    class="absolute -top-8 left-1/2 transform -translate-x-1/2 scale-0 group-hover:scale-100 transition bg-gray-800 text-white text-xs rounded px-2 py-1"
                  >
                    Assign Product
                  </span>
                </div>

                <!-- Edit Link -->
                <div class="relative group">
                  <Link
                    :href="route('admin.coupons.edit', coupon.id)"
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
                    @click="openDeleteModal(coupon.id)"
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
            <tr v-if="!coupons.data.length">
              <td colspan="11" class="px-3 py-2 text-center text-xs text-gray-500 dark:text-gray-400">
                No coupons found.
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div class="mt-4 flex justify-between items-center">
        <div class="text-xs text-gray-700 dark:text-gray-300">
          Showing {{ coupons.from }} to {{ coupons.to }} of {{ coupons.total }}
        </div>
        <div class="flex space-x-1">
          <template v-for="(link, index) in coupons.links" :key="index">
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
        :item-id="couponToDelete"
        item-name="coupon"
        route-name="admin.coupons.destroy"
        v-model:visible="showDeleteModal"
        @deleted="handleDeleteSuccess"
      />
    </div>
  </AdminLayout>
</template>