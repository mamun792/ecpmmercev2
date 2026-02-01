<template>
  <Head title="Product Purchase Costs" />
  <AdminLayout>
    <div class="w-full mx-auto py-10">
      <div class="flex justify-between mb-6">
        <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-100">Product Purchase Costs</h1>
        <div class="flex gap-2">
          <button @click="openCreateModal" class="btn btn-primary">
            Add Purchase Cost
          </button>
        </div>
      </div>

      <!-- Table Controls -->
      <div class="flex flex-col md:flex-row justify-between items-center mb-4 gap-4">
        <div class="flex items-center">
          <label for="perPage" class="mr-2 text-sm font-medium text-gray-600 dark:text-gray-300">Show:</label>
          <select id="perPage" v-model="perPage"
            class="rounded-md border border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50 text-sm px-2 w-[60px] py-1 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
            @change="handlePerPageChange">
            <option value="5">5</option>
            <option value="10">10</option>
            <option value="25">25</option>
            <option value="50">50</option>
          </select>
        </div>

        <div class="relative w-full md:w-64">
          <input type="text" v-model="searchQuery" placeholder="Search purchase costs..."
            class="w-full pl-10 pr-4 py-2 rounded-md border border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-primary-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
            @input="debouncedSearch" />
          <div class="absolute left-3 top-2.5 text-gray-400 dark:text-gray-500">
            <Search size="18" />
          </div>
        </div>
      </div>

      <!-- Main Table -->
      <div class="main-table">
        <table>
          <thead>
            <tr>
              <th>#</th>
              <th>Cost Name</th>
              <th>Quantity</th>
              <th>Unit Price</th>
              <th>Total Price</th>
              <th>Supplier</th>
              <th>Month</th>
              <th>Purchase Date</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            <!-- Skeleton Loader -->
            <template v-if="loading">
              <tr v-for="i in perPage" :key="`skeleton-${i}`" class="animate-pulse">
                <td class="px-3 py-4 whitespace-nowrap">
                  <div class="h-4 w-16 bg-gray-200 dark:bg-gray-600 rounded"></div>
                </td>
                <td class="px-3 py-4 whitespace-nowrap">
                  <div class="h-4 w-32 bg-gray-200 dark:bg-gray-600 rounded"></div>
                </td>
                <td class="px-3 py-4 whitespace-nowrap">
                  <div class="h-4 w-20 bg-gray-200 dark:bg-gray-600 rounded"></div>
                </td>
                <td class="px-3 py-4 whitespace-nowrap">
                  <div class="h-4 w-24 bg-gray-200 dark:bg-gray-600 rounded"></div>
                </td>
                <td class="px-3 py-4 whitespace-nowrap">
                  <div class="h-4 w-20 bg-gray-200 dark:bg-gray-600 rounded"></div>
                </td>
                <td class="px-3 py-4 whitespace-nowrap">
                  <div class="h-4 w-28 bg-gray-200 dark:bg-gray-600 rounded"></div>
                </td>
                <td class="px-3 py-4 whitespace-nowrap">
                  <div class="h-4 w-16 bg-gray-200 dark:bg-gray-600 rounded"></div>
                </td>
                <td class="px-3 py-4 whitespace-nowrap">
                  <div class="h-4 w-24 bg-gray-200 dark:bg-gray-600 rounded"></div>
                </td>
                <td class="px-3 py-4 whitespace-nowrap">
                  <div class="h-4 w-16 bg-gray-200 dark:bg-gray-600 rounded"></div>
                </td>
              </tr>
            </template>

            <!-- Actual Data -->
            <template v-else>
              <tr v-for="(item, index) in productPurchaseCosts.data" :key="item.id">
                <td>{{ index + 1 }}</td>
                <td>{{ item.cost_name }}</td>
                <td>{{ item.quantity || '-' }}</td>
                <td>{{ item.unit_price ? '৳' + item.unit_price : '-' }}</td>
                <td>৳{{ item.total_price }}</td>
                <td>{{ item.supplier_name || '-' }}</td>
                <td>{{ item.month }}</td>
                <td>{{ formatDate(item.purchase_date) }}</td>
                <td class="actions">
                  <div class="flex justify-end space-x-2 relative">
                    <!-- Edit Button -->
                    <div class="relative group">
                      <button @click="openEditModal(item)"
                        class="table_edit_action hover:bg-green-500 hover:text-white p-2 rounded">
                        <SquarePen size="20" />
                      </button>
                      <span
                        class="absolute -top-8 left-1/2 transform -translate-x-1/2 scale-0 group-hover:scale-100 transition bg-gray-800 text-white text-xs rounded px-2 py-1">
                        Edit
                      </span>
                    </div>

                    <!-- Delete Button -->
                    <div class="relative group">
                      <button @click="openDeleteModal(item.id)"
                        class="table_delete_action hover:bg-red-500 hover:text-white p-2 rounded">
                        <Trash2Icon size="20" />
                      </button>
                      <span
                        class="absolute -top-8 left-1/2 transform -translate-x-1/2 scale-0 group-hover:scale-100 transition bg-gray-800 text-white text-xs rounded px-2 py-1">
                        Delete
                      </span>
                    </div>
                  </div>
                </td>
              </tr>

              <!-- Empty State -->
              <tr v-if="productPurchaseCosts.data.length === 0 && !loading">
                <td colspan="9" class="px-6 py-10 text-center text-gray-500 dark:text-gray-400">
                  <div class="flex flex-col items-center">
                    <Package size="48" class="text-gray-300 dark:text-gray-600 mb-3" />
                    <p>No purchase costs found</p>
                  </div>
                </td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>

      <!-- Laravel Pagination -->
      <div class="flex items-center justify-end mt-4">
        <div class="flex space-x-1">
          <button @click="goToPage(1)" :disabled="currentPage === 1 || loading" class="pagination-button">«</button>
          <button @click="goToPage(currentPage - 1)" :disabled="currentPage === 1 || loading" class="pagination-button">‹</button>
          <template v-if="lastPage <= 7">
            <button v-for="page in lastPage" :key="page" @click="goToPage(page)" :disabled="loading" class="pagination-button" :class="{ 'active': currentPage === page }">
              {{ page }}
            </button>
          </template>
          <template v-else>
            <button v-if="currentPage > 3" @click="goToPage(1)" class="pagination-button">1</button>
            <span v-if="currentPage > 4" class="pagination-ellipsis">...</span>
            <template v-for="page in lastPage" :key="page">
              <button v-if="page >= currentPage - 1 && page <= currentPage + 1" @click="goToPage(page)" class="pagination-button" :class="{ 'active': currentPage === page }">
                {{ page }}
              </button>
            </template>
            <span v-if="currentPage < lastPage - 3" class="pagination-ellipsis">...</span>
            <button v-if="currentPage < lastPage - 2" @click="goToPage(lastPage)" class="pagination-button">{{ lastPage }}</button>
          </template>
          <button @click="goToPage(currentPage + 1)" :disabled="currentPage === lastPage || loading" class="pagination-button">›</button>
          <button @click="goToPage(lastPage)" :disabled="currentPage === lastPage || loading" class="pagination-button">»</button>
        </div>
      </div>

      <!-- Delete Confirmation Modal -->
      <DeleteModal
        :item-id="itemToDelete"
        item-name="purchase cost"
        route-name="admin.product-purchase-costs.destroy"
        v-model:visible="showDeleteModal"
        @deleted="handleDeleteSuccess"
      />

      <!-- Create/Edit Modal -->
      <div v-if="showModal"
        class="fixed inset-0 bg-gray-600 bg-opacity-50 dark:bg-gray-900 dark:bg-opacity-75 flex items-center justify-center z-50">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 w-full max-w-md">
          <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
            {{ isEditing ? 'Edit Purchase Cost' : 'Create Purchase Cost' }}
          </h3>

          <form @submit.prevent="submitForm" class="space-y-4">
            <!-- Cost Name -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Cost Name</label>
              <input type="text" v-model="form.cost_name" required
                class="w-full rounded-md border border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-3 py-2" />
            </div>

            <!-- Quantity (Optional) -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Quantity (Optional)</label>
              <input type="number" min="1" v-model="form.quantity"
                class="w-full rounded-md border border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-3 py-2" />
            </div>

            <!-- Unit Price (Optional) -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Unit Price (Optional)</label>
              <input type="number" step="0.01" min="0" v-model="form.unit_price" @input="calculateTotal"
                class="w-full rounded-md border border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-3 py-2" />
            </div>

            <!-- Total Price -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Total Price</label>
              <input type="number" step="0.01" min="0" v-model="form.total_price" required
                class="w-full rounded-md border border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-3 py-2" />
            </div>

            <!-- Supplier Name -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Supplier Name</label>
              <input type="text" v-model="form.supplier_name"
                class="w-full rounded-md border border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-3 py-2" />
            </div>

            <!-- Month -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Month</label>
              <select v-model="form.month" required
                class="w-full rounded-md border border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-3 py-2">
                <option value="">Select Month</option>
                <option value="January">January</option>
                <option value="February">February</option>
                <option value="March">March</option>
                <option value="April">April</option>
                <option value="May">May</option>
                <option value="June">June</option>
                <option value="July">July</option>
                <option value="August">August</option>
                <option value="September">September</option>
                <option value="October">October</option>
                <option value="November">November</option>
                <option value="December">December</option>
              </select>
            </div>

            <!-- Purchase Date -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Purchase Date</label>
              <input type="date" v-model="form.purchase_date" required
                class="w-full rounded-md border border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-3 py-2" />
            </div>

            <!-- Note -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Note</label>
              <textarea v-model="form.note" rows="3"
                class="w-full rounded-md border border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-3 py-2"></textarea>
            </div>

            <!-- Buttons -->
            <div class="flex justify-end space-x-4">
              <button type="button" @click="closeModal"
                class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-300 dark:hover:bg-gray-600">
                Cancel
              </button>
              <button type="submit" :disabled="isSubmitting"
                class="px-4 py-2 btn-primary text-white rounded-md hover:bg-primary-700 dark:bg-primary-700 dark:hover:bg-primary-800 disabled:opacity-50">
                {{ isSubmitting ? 'Saving...' : (isEditing ? 'Update' : 'Create') }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { ref, computed, watch } from 'vue';
import { toast } from "@steveyuowo/vue-hot-toast";
import DeleteModal from '@/Components/Modal/DeleteModal.vue';
import {
  Search,
  Package,
  Trash2Icon,
  SquarePen
} from 'lucide-vue-next';

const props = defineProps({
  productPurchaseCosts: Object
});

// State
const searchQuery = ref('');
const perPage = ref(10);
const loading = ref(false);
const showDeleteModal = ref(false);
const itemToDelete = ref(null);
const showModal = ref(false);
const isEditing = ref(false);
const currentItem = ref(null);
const isSubmitting = ref(false);

const form = ref({
  cost_name: '',
  quantity: '',
  unit_price: '',
  total_price: '',
  supplier_name: '',
  month: '',
  purchase_date: '',
  note: ''
});

// Computed properties for pagination
const paginationData = computed(() => props.productPurchaseCosts);
const currentPage = computed(() => props.productPurchaseCosts.current_page);
const lastPage = computed(() => props.productPurchaseCosts.last_page);

// Methods
const openDeleteModal = (id) => {
  itemToDelete.value = id;
  showDeleteModal.value = true;
};

const handleDeleteSuccess = () => {
  showDeleteModal.value = false;
  itemToDelete.value = null;
};

const openCreateModal = () => {
  isEditing.value = false;
  currentItem.value = null;
  form.value = {
    cost_name: '',
    quantity: '',
    unit_price: '',
    total_price: '',
    supplier_name: '',
    month: '',
    purchase_date: '',
    note: ''
  };
  showModal.value = true;
};

const openEditModal = (item) => {
  isEditing.value = true;
  currentItem.value = item;
  form.value = {
    cost_name: item.cost_name,
    quantity: item.quantity,
    unit_price: item.unit_price,
    total_price: item.total_price,
    supplier_name: item.supplier_name || '',
    month: item.month,
    purchase_date: item.purchase_date,
    note: item.note || ''
  };
  showModal.value = true;
};

const closeModal = () => {
  showModal.value = false;
  isEditing.value = false;
  currentItem.value = null;
  form.value = {
    cost_name: '',
    quantity: '',
    unit_price: '',
    total_price: '',
    supplier_name: '',
    month: '',
    purchase_date: '',
    note: ''
  };
};

const calculateTotal = () => {
  const quantity = parseFloat(form.value.quantity) || 0;
  const unitPrice = parseFloat(form.value.unit_price) || 0;
  form.value.total_price = (quantity * unitPrice).toFixed(2);
};

const submitForm = () => {
  isSubmitting.value = true;

  const routeName = isEditing.value ? 'admin.product-purchase-costs.update' : 'admin.product-purchase-costs.store';
  const method = isEditing.value ? 'put' : 'post';
  let url;
  if (isEditing.value) {
    if (!currentItem.value || !currentItem.value.id) {
      toast.error('No purchase cost selected for editing');
      isSubmitting.value = false;
      return;
    }
    url = route(routeName, currentItem.value.id);
  } else {
    url = route(routeName);
  }

  router[method](url, form.value, {
    preserveState: true,
    onSuccess: () => {
      toast.success(`Purchase cost ${isEditing.value ? 'updated' : 'created'} successfully!`);
      closeModal();
      isSubmitting.value = false;
    },
    onError: (errors) => {
      toast.error(`Failed to ${isEditing.value ? 'update' : 'create'} purchase cost`);
      Object.values(errors).forEach(error => toast.error(error));
      isSubmitting.value = false;
    }
  });
};

const handleSearch = () => {
  loading.value = true;
  router.get(
    route('admin.product-purchase-costs.index'),
    { search: searchQuery.value, page: 1, per_page: perPage.value },
    {
      preserveState: true,
      onSuccess: () => {
        loading.value = false;
      }
    }
  );
};

const handlePerPageChange = (e) => {
  perPage.value = parseInt(e.target.value);
  loading.value = true;
  router.get(
    route('admin.product-purchase-costs.index'),
    { search: searchQuery.value, page: 1, per_page: perPage.value },
    {
      preserveState: true,
      onSuccess: () => {
        loading.value = false;
      }
    }
  );
};

const goToPage = (page) => {
  if (page < 1 || page > lastPage.value || page === currentPage.value) return;

  loading.value = true;
  router.get(
    route('admin.product-purchase-costs.index'),
    { search: searchQuery.value, page: page, per_page: perPage.value },
    {
      preserveState: true,
      onSuccess: () => {
        loading.value = false;
      }
    }
  );
};

function formatDate(dateString) {
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  });
}

// Debounce search input
let searchTimeout = null;
const debouncedSearch = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    handleSearch();
  }, 500);
};

// Watch for quantity and unit_price changes to auto-calculate total
watch(() => [form.value.quantity, form.value.unit_price], calculateTotal);
</script>

<style>
.pagination-button {
  @apply px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition;
}

.active {
  @apply bg-green-500 text-white border-green-500 hover:bg-green-600 dark:bg-green-600 dark:border-green-600 dark:hover:bg-green-700;
}

.pagination-ellipsis {
  @apply px-5 py-2 text-sm text-gray-500 dark:text-gray-400;
}
</style>
