<template>
  <Head title="Reviews Management" />
  <AdminLayout>
    <div class="w-full mx-auto py-10">
      <div class="flex justify-between mb-6">
        <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-100">Reviews Management</h1>
        <button @click="openCreateModal" class="btn btn-primary">
          Create Admin Review
        </button>
      </div>

      <!-- Tabs -->
      <div class="mb-6">
        <nav class="flex space-x-4" aria-label="Tabs">
          <button
            @click="activeTab = 'all'"
            :class="[
              'px-3 py-2 font-medium text-sm rounded-md',
              activeTab === 'all'
                ? 'bg-primary-100 text-primary-700 dark:bg-primary-900 dark:text-primary-300'
                : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'
            ]"
          >
            All Reviews ({{ stats.total || 0 }})
          </button>
          <button
            @click="activeTab = 'pending'"
            :class="[
              'px-3 py-2 font-medium text-sm rounded-md',
              activeTab === 'pending'
                ? 'bg-primary-100 text-primary-700 dark:bg-primary-900 dark:text-primary-300'
                : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'
            ]"
          >
            Pending Approval ({{ stats.pending || 0 }})
          </button>
        </nav>
      </div>

      <!-- Table Controls -->
      <div class="flex flex-col md:flex-row justify-between items-center mb-4 gap-4">
        <div class="flex items-center">
          <label for="perPage" class="mr-2 text-sm font-medium text-gray-600 dark:text-gray-300">Show:</label>
          <select id="perPage" v-model="perPage"
            class="rounded-md border border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50 text-sm px-2 w-[60px] py-1 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
            @change="handlePerPageChange">
            <option value="10">10</option>
            <option value="25">25</option>
            <option value="50">50</option>
          </select>
        </div>

        <div class="relative w-full md:w-64">
          <input type="text" v-model="searchQuery" placeholder="Search reviews..."
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
              <th>Product</th>
              <th>Reviewer</th>
              <th>Review</th>
              <th>Rating</th>
              <th>Status</th>
              <th>Images</th>
              <th>Created</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            <!-- Skeleton Loader -->
            <template v-if="loading">
              <tr v-for="i in perPage" :key="`skeleton-${i}`" class="animate-pulse">
                <td class="px-3 py-4 whitespace-nowrap">
                  <div class="h-4 w-8 bg-gray-200 dark:bg-gray-600 rounded"></div>
                </td>
                <td class="px-3 py-4 whitespace-nowrap">
                  <div class="h-4 w-32 bg-gray-200 dark:bg-gray-600 rounded"></div>
                </td>
                <td class="px-3 py-4 whitespace-nowrap">
                  <div class="h-4 w-24 bg-gray-200 dark:bg-gray-600 rounded"></div>
                </td>
                <td class="px-3 py-4">
                  <div class="h-4 w-48 bg-gray-200 dark:bg-gray-600 rounded"></div>
                </td>
                <td class="px-3 py-4 whitespace-nowrap">
                  <div class="h-4 w-16 bg-gray-200 dark:bg-gray-600 rounded"></div>
                </td>
                <td class="px-3 py-4 whitespace-nowrap">
                  <div class="h-4 w-20 bg-gray-200 dark:bg-gray-600 rounded"></div>
                </td>
                <td class="px-3 py-4 whitespace-nowrap">
                  <div class="h-4 w-12 bg-gray-200 dark:bg-gray-600 rounded"></div>
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
              <template v-for="(review, index) in reviews.data" :key="review.id">
                <tr>
                  <td>{{ index + 1 }}</td>
                  <td>
                    <div class="font-medium text-gray-900 dark:text-gray-100">{{ review.product?.name }}</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">ID: {{ review.product_id }}</div>
                  </td>
                  <td>
                    <div class="font-medium text-gray-900 dark:text-gray-100">{{ review.name }}</div>
                    <div v-if="review.user" class="text-sm text-gray-500 dark:text-gray-400">
                      User ID: {{ review.user.id }}
                    </div>
                    <div v-else-if="review.session_id" class="text-sm text-gray-500 dark:text-gray-400">
                      Guest Session
                    </div>
                    <div v-else class="text-sm text-primary-600 dark:text-primary-400 font-medium">
                      Admin Review
                    </div>
                  </td>
                  <td>
                    <div class="max-w-xs truncate text-gray-900 dark:text-gray-100">{{ review.review_message }}</div>
                  </td>
                  <td>
                    <div v-if="review.rating" class="flex items-center">
                      <div class="flex text-yellow-400">
                        <Star v-for="i in 5" :key="i" :class="{ 'fill-current': i <= review.rating }" size="16" />
                      </div>
                      <span class="ml-1 text-sm text-gray-600 dark:text-gray-400">{{ review.rating }}/5</span>
                    </div>
                    <span v-else class="text-gray-400 dark:text-gray-500">No rating</span>
                  </td>
                  <td>
                    <span
                      class="px-2 py-1 text-xs font-medium rounded-full"
                      :class="{
                        'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300': review.is_approved,
                        'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300': !review.is_approved
                      }"
                    >
                      {{ review.is_approved ? 'Approved' : 'Pending' }}
                    </span>
                  </td>
                  <td>
                    <div v-if="review.images && review.images.length > 0" class="flex items-center space-x-2">
                      <img
                        :src="review.images[0]"
                        class="w-8 h-8 rounded object-cover border border-gray-200 dark:border-gray-600"
                        alt="Review image"
                      />
                      <span class="text-xs text-gray-600 dark:text-gray-400">
                        {{ review.images.length }} image{{ review.images.length > 1 ? 's' : '' }}
                      </span>
                    </div>
                    <span v-else class="text-gray-400 dark:text-gray-500 text-sm">No images</span>
                  </td>
                  <td>{{ formatDate(review.created_at) }}</td>
                  <td class="actions">
                    <div class="flex justify-end space-x-2">
                      <!-- Approve Button -->
                      <button
                        v-if="!review.is_approved"
                        @click="approveReview(review.id)"
                        class="text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-300 p-2 rounded"
                        :disabled="approvingReview === review.id"
                      >
                        <CheckCircle v-if="approvingReview !== review.id" size="20" />
                        <Loader v-else size="20" class="animate-spin" />
                      </button>

                      <!-- View Details Button -->
                      <button
                        @click="viewReviewDetails(review)"
                        class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 p-2 rounded"
                      >
                        <Eye size="20" />
                      </button>

                      <!-- Delete Button -->
                      <button
                        @click="deleteReview(review.id)"
                        class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 p-2 rounded"
                        :disabled="deletingReview === review.id"
                      >
                        <Trash2 v-if="deletingReview !== review.id" size="20" />
                        <Loader v-else size="20" class="animate-spin" />
                      </button>
                    </div>
                  </td>
                </tr>
              </template>

              <!-- Empty State -->
              <tr v-if="reviews.data.length === 0 && !loading">
                <td colspan="9" class="px-6 py-10 text-center text-gray-500 dark:text-gray-400">
                  <div class="flex flex-col items-center">
                    <MessageSquare size="48" class="text-gray-300 dark:text-gray-600 mb-3" />
                    <p>No reviews found</p>
                  </div>
                </td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="reviews.data.length > 0" class="flex items-center justify-end mt-4">
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
              <button v-if="page >= currentPage - 1 && page <= currentPage + 1" @click="goToPage(page)" :disabled="loading" class="pagination-button" :class="{ 'active': currentPage === page }">
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

      <!-- Create Admin Review Modal -->
      <div v-if="showCreateModal"
        class="fixed inset-0 bg-gray-600 bg-opacity-50 dark:bg-gray-900 dark:bg-opacity-75 flex items-center justify-center z-50">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 w-full max-w-2xl max-h-[90vh] overflow-y-auto">
          <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Create Admin Review</h3>

          <form @submit.prevent="submitCreateReview" class="space-y-4">
            <!-- Product Selection with Search -->
            <div class="relative">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Product *</label>
              <div class="relative">
                <input 
                  v-model="productSearchQuery" 
                  @focus="showProductDropdown = true"
                  @input="showProductDropdown = true"
                  type="text" 
                  required
                  class="w-full rounded-md border border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 pr-10"
                  placeholder="Search for a product..." 
                />
                <div class="absolute right-3 top-2.5 text-gray-400 dark:text-gray-500">
                  <Search size="18" />
                </div>
              </div>
              
              <!-- Dropdown List -->
              <div 
                v-if="showProductDropdown && filteredProducts.length > 0" 
                class="absolute z-10 w-full mt-1 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md shadow-lg max-h-60 overflow-y-auto"
              >
                <div 
                  v-for="product in filteredProducts" 
                  :key="product.id"
                  @click="selectProduct(product)"
                  class="px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 cursor-pointer text-gray-900 dark:text-gray-100 flex items-center gap-3"
                  :class="{'bg-primary-50 dark:bg-primary-900': createForm.product_id === product.id}"
                >
                  <img 
                    v-if="product.feature_image" 
                    :src="product.feature_image" 
                    :alt="product.name"
                    class="w-10 h-10 object-cover rounded border border-gray-200 dark:border-gray-600"
                  />
                  <div v-else class="w-10 h-10 bg-gray-200 dark:bg-gray-600 rounded flex items-center justify-center text-gray-400 dark:text-gray-500 text-xs">
                    No img
                  </div>
                  <div class="flex-1">
                    <div class="font-medium">{{ product.name }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">ID: {{ product.id }}</div>
                  </div>
                </div>
              </div>
              
              <!-- No results -->
              <div 
                v-if="showProductDropdown && filteredProducts.length === 0" 
                class="absolute z-10 w-full mt-1 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md shadow-lg"
              >
                <div class="px-4 py-3 text-gray-500 dark:text-gray-400 text-sm">
                  No products found
                </div>
              </div>
              
              <!-- Selected Product Display -->
              <div v-if="createForm.product_id && !showProductDropdown" class="mt-2">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary-100 text-primary-800 dark:bg-primary-900 dark:text-primary-300">
                  Selected: {{ getSelectedProductName }} (ID: {{ createForm.product_id }})
                </span>
              </div>
            </div>

            <!-- Reviewer Name -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Reviewer Name *</label>
              <input v-model="createForm.name" type="text" required
                class="w-full rounded-md border border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                placeholder="Enter reviewer name" />
            </div>

            <!-- Rating -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Rating</label>
              <select v-model="createForm.rating"
                class="w-full rounded-md border border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                <option value="">No rating</option>
                <option value="1">1 Star</option>
                <option value="2">2 Stars</option>
                <option value="3">3 Stars</option>
                <option value="4">4 Stars</option>
                <option value="5">5 Stars</option>
              </select>
            </div>

            <!-- Review Message -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Review Message *</label>
              <textarea v-model="createForm.review_message" required rows="4"
                class="w-full rounded-md border border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                placeholder="Enter review message"></textarea>
            </div>

            <!-- Images -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Images</label>
              <input type="file" multiple accept="image/*" @change="handleImageUpload"
                class="w-full rounded-md border border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" />
              <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">You can select multiple images (optional)</p>
            </div>

            <!-- Buttons -->
            <div class="flex justify-end space-x-4 pt-4">
              <button type="button" @click="closeCreateModal"
                class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-300 dark:hover:bg-gray-600">
                Cancel
              </button>
              <button type="submit" :disabled="creatingReview"
                class="px-4 py-2 btn-primary text-white rounded-md hover:bg-primary-700 dark:bg-primary-700 dark:hover:bg-primary-800 disabled:opacity-50">
                {{ creatingReview ? 'Creating...' : 'Create Review' }}
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Review Details Modal -->
      <div v-if="showDetailsModal"
        class="fixed inset-0 bg-gray-600 bg-opacity-50 dark:bg-gray-900 dark:bg-opacity-75 flex items-center justify-center z-50">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 w-full max-w-2xl max-h-[90vh] overflow-y-auto">
          <div class="flex justify-between items-start mb-4">
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Review Details</h3>
            <button @click="closeDetailsModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
              <X size="24" />
            </button>
          </div>

          <div v-if="selectedReview" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Product</label>
                <p class="text-gray-900 dark:text-gray-100">{{ selectedReview.product?.name }}</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Reviewer</label>
                <p class="text-gray-900 dark:text-gray-100">{{ selectedReview.name }}</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Rating</label>
                <div v-if="selectedReview.rating" class="flex items-center">
                  <div class="flex text-yellow-400">
                    <Star v-for="i in 5" :key="i" :class="{ 'fill-current': i <= selectedReview.rating }" size="16" />
                  </div>
                  <span class="ml-2 text-gray-900 dark:text-gray-100">{{ selectedReview.rating }}/5</span>
                </div>
                <p v-else class="text-gray-500 dark:text-gray-400">No rating</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                <span
                  class="px-2 py-1 text-xs font-medium rounded-full"
                  :class="{
                    'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300': selectedReview.is_approved,
                    'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300': !selectedReview.is_approved
                  }"
                >
                  {{ selectedReview.is_approved ? 'Approved' : 'Pending' }}
                </span>
              </div>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Review Message</label>
              <p class="text-gray-900 dark:text-gray-100 bg-gray-50 dark:bg-gray-700 p-3 rounded-md">{{ selectedReview.review_message }}</p>
            </div>

            <div v-if="selectedReview.images && selectedReview.images.length > 0">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Images</label>
              <div class="grid grid-cols-3 gap-2">
                <img
                  v-for="(image, index) in selectedReview.images"
                  :key="index"
                  :src="image"
                  class="w-full h-24 object-cover rounded-md border border-gray-200 dark:border-gray-600"
                  alt="Review image"
                />
              </div>
            </div>

            <div class="grid grid-cols-2 gap-4 text-sm text-gray-500 dark:text-gray-400">
              <div>
                <span class="font-medium">Created:</span> {{ formatDate(selectedReview.created_at) }}
              </div>
              <div>
                <span class="font-medium">Type:</span>
                <span v-if="selectedReview.user">User Review</span>
                <span v-else-if="selectedReview.session_id">Guest Review</span>
                <span v-else class="text-primary-600 dark:text-primary-400 font-medium">Admin Review</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { ref, computed, watch } from 'vue';
import { toast } from "@steveyuowo/vue-hot-toast";
import axios from 'axios';
import {
  Search,
  MessageSquare,
  CheckCircle,
  Eye,
  Star,
  X,
  Loader,
  Trash2
} from 'lucide-vue-next';

const props = defineProps({
  reviews: Object,
  products: Array,
  stats: Object
});

// State
const activeTab = ref('all');
const searchQuery = ref('');
const perPage = ref(10);
const loading = ref(false);
const showCreateModal = ref(false);
const showDetailsModal = ref(false);
const selectedReview = ref(null);
const approvingReview = ref(null);
const deletingReview = ref(null);
const creatingReview = ref(false);
const productSearchQuery = ref('');
const showProductDropdown = ref(false);

// Create form
const createForm = ref({
  product_id: '',
  name: '',
  review_message: '',
  rating: '',
  images: []
});

// Computed properties for pagination
const paginationData = computed(() => props.reviews);
const currentPage = computed(() => props.reviews.current_page);
const lastPage = computed(() => props.reviews.last_page);

// Filtered products based on search
const filteredProducts = computed(() => {
  if (!productSearchQuery.value) {
    return props.products;
  }
  
  const query = productSearchQuery.value.toLowerCase();
  return props.products.filter(product => 
    product.name.toLowerCase().includes(query) ||
    product.id.toString().includes(query)
  );
});

// Watch for tab changes
watch(activeTab, (newTab) => {
  loadReviews();
});

// Methods
const loadReviews = () => {
  loading.value = true;
  router.get(
    route('admin.reviews.index'),
    {
      tab: activeTab.value,
      search: searchQuery.value,
      page: 1,
      per_page: perPage.value
    },
    {
      preserveState: true,
      onSuccess: () => {
        loading.value = false;
      }
    }
  );
};

const handleSearch = () => {
  loading.value = true;
  router.get(
    route('admin.reviews.index'),
    {
      tab: activeTab.value,
      search: searchQuery.value,
      page: 1,
      per_page: perPage.value
    },
    {
      preserveState: true,
      onSuccess: () => {
        loading.value = false;
      }
    }
  );
};

const handlePerPageChange = () => {
  loadReviews();
};

const goToPage = (page) => {
  if (page < 1 || page > lastPage.value || page === currentPage.value) return;

  loading.value = true;
  router.get(
    route('admin.reviews.index'),
    {
      tab: activeTab.value,
      search: searchQuery.value,
      page: page,
      per_page: perPage.value
    },
    {
      preserveState: true,
      onSuccess: () => {
        loading.value = false;
      }
    }
  );
};

const approveReview = async (reviewId) => {
  approvingReview.value = reviewId;

  try {
    const response = await axios.put(route('admin.reviews.approve', reviewId));

    if (response.data.success) {
      toast.success('Review approved successfully!');
      loadReviews(); // Refresh the list
    } else {
      toast.error(response.data.message || 'Failed to approve review');
    }
  } catch (error) {
    toast.error('Failed to approve review');
    console.error('Approve review error:', error);
  } finally {
    approvingReview.value = null;
  }
};

const openCreateModal = () => {
  createForm.value = {
    product_id: '',
    name: '',
    review_message: '',
    rating: '',
    images: []
  };
  productSearchQuery.value = '';
  showProductDropdown.value = false;
  showCreateModal.value = true;
};

const closeCreateModal = () => {
  showCreateModal.value = false;
  productSearchQuery.value = '';
  showProductDropdown.value = false;
};

const selectProduct = (product) => {
  createForm.value.product_id = product.id;
  productSearchQuery.value = product.name;
  showProductDropdown.value = false;
};

const getSelectedProductName = computed(() => {
  if (!createForm.value.product_id) return '';
  const product = props.products.find(p => p.id === createForm.value.product_id);
  return product ? product.name : '';
});

const handleImageUpload = (event) => {
  createForm.value.images = Array.from(event.target.files);
};

const submitCreateReview = () => {
  creatingReview.value = true;

  const formData = new FormData();
  formData.append('product_id', createForm.value.product_id);
  formData.append('name', createForm.value.name);
  formData.append('review_message', createForm.value.review_message);
  if (createForm.value.rating) {
    formData.append('rating', createForm.value.rating);
  }
  createForm.value.images.forEach((image, index) => {
    formData.append(`images[${index}]`, image);
  });

  router.post(
    route('admin.reviews.store'),
    formData,
    {
      preserveState: true,
      onSuccess: () => {
        toast.success('Admin review created successfully!');
        creatingReview.value = false;
        closeCreateModal();
        loadReviews(); // Refresh the list
      },
      onError: (errors) => {
        toast.error('Failed to create review');
        creatingReview.value = false;
        Object.values(errors).forEach(error => toast.error(error));
      }
    }
  );
};

const viewReviewDetails = (review) => {
  selectedReview.value = review;
  showDetailsModal.value = true;
};

const deleteReview = async (reviewId) => {
  if (!confirm('Are you sure you want to delete this review?')) {
    return;
  }

  deletingReview.value = reviewId;

  try {
    const response = await axios.delete(route('admin.reviews.destroy', reviewId));

    if (response.data.success) {
      toast.success('Review deleted successfully!');
      loadReviews(); // Refresh the list
    } else {
      toast.error(response.data.message || 'Failed to delete review');
    }
  } catch (error) {
    toast.error('Failed to delete review');
    console.error('Delete review error:', error);
  } finally {
    deletingReview.value = null;
  }
};

const closeDetailsModal = () => {
  showDetailsModal.value = false;
  selectedReview.value = null;
};

function formatDate(isoDate) {
  const date = new Date(isoDate);
  const now = new Date();
  const diffInMs = now - date;

  const seconds = Math.floor(diffInMs / 1000);
  const minutes = Math.floor(seconds / 60);
  const hours = Math.floor(minutes / 60);
  const days = Math.floor(hours / 24);

  if (days > 0) {
    return `${days} day${days === 1 ? '' : 's'} ago`;
  } else if (hours > 0) {
    return `${hours} hour${hours === 1 ? '' : 's'} ago`;
  } else if (minutes > 0) {
    return `${minutes} minute${minutes === 1 ? '' : 's'} ago`;
  } else {
    return `${seconds} second${seconds === 1 ? '' : 's'} ago`;
  }
}

// Debounce search input
let searchTimeout = null;
const debouncedSearch = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    handleSearch();
  }, 500);
};
</script>

<style>
@keyframes pulse {
  0%, 100% { opacity: 0.5; }
  50% { opacity: 0.8; }
}

.animate-pulse {
  animation: pulse 1.5s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

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