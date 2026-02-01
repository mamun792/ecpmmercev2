<template>
  <Head title="Landing Pages" />
  <AdminLayout>
  <div class="landing-pages-index max-w-7xl mx-auto p-6">
    <div class="page-header flex justify-between items-center mb-8">
      <h1 class="text-3xl font-semibold text-gray-900 m-0">Landing Pages</h1>
      <Link href="/admin/landing-pages/create" class="btn-primary px-4 py-2 rounded text-sm font-medium cursor-pointer transition-colors no-underline inline-block bg-indigo-600 text-white hover:bg-indigo-700">
        Create New Landing Page
      </Link>
    </div>

    <div v-if="$page.props.flash?.success" class="alert alert-success p-3 rounded-md mb-6 bg-green-100 text-green-800 border border-green-300">
      {{ $page.props.flash.success }}
    </div>

    <div class="table-container bg-white rounded-lg shadow-sm overflow-hidden">
      <table class="data-table w-full border-collapse">
        <thead class="bg-gray-50 border-b-2 border-gray-200">
          <tr>
            <th class="p-3 text-left font-semibold text-gray-700 text-xs uppercase tracking-wider">ID</th>
            <th class="p-3 text-left font-semibold text-gray-700 text-xs uppercase tracking-wider">Title</th>
            <th class="p-3 text-left font-semibold text-gray-700 text-xs uppercase tracking-wider">Slug</th>
            <th class="p-3 text-left font-semibold text-gray-700 text-xs uppercase tracking-wider">Status</th>
            <th class="p-3 text-left font-semibold text-gray-700 text-xs uppercase tracking-wider">Created At</th>
            <th class="p-3 text-left font-semibold text-gray-700 text-xs uppercase tracking-wider">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="page in landingPages.data" :key="page.id" class="hover:bg-gray-50">
            <td class="p-4 border-b border-gray-200 text-gray-600">{{ page.id }}</td>
            <td class="p-4 border-b border-gray-200 text-gray-600">{{ page.title }}</td>
            <td class="p-4 border-b border-gray-200 text-gray-600">
              <code class="bg-gray-100 px-2 py-1 rounded text-xs text-gray-500">{{ page.slug }}</code>
            </td>
            <td class="p-4 border-b border-gray-200 text-gray-600">
              <span class="badge inline-block px-3 py-1 rounded-full text-xs font-medium" :class="getStatusClass(page)">
                {{ getStatusText(page) }}
              </span>
            </td>
            <td class="p-4 border-b border-gray-200 text-gray-600">{{ formatDate(page.created_at) }}</td>
            <td class="p-4 border-b border-gray-200 text-gray-600">
              <div class="action-buttons flex gap-2">
                <!-- visit button -->
                <a :href="`${frontendUrl}/landing/${page.slug}`" target="_blank" class="btn-visit px-4 py-2 rounded text-sm font-medium cursor-pointer transition-colors no-underline inline-block bg-green-600 text-white hover:bg-green-700">
                  Visit Site
                </a>
                <Link :href="`/admin/landing-pages/${page.id}/edit`" class="btn-edit px-4 py-2 rounded text-sm font-medium cursor-pointer transition-colors no-underline inline-block bg-blue-600 text-white hover:bg-blue-700">
                  Edit
                </Link>
                <button @click="confirmDelete(page)" class="btn-delete px-4 py-2 rounded text-sm font-medium cursor-pointer transition-colors no-underline inline-block bg-red-600 text-white hover:bg-red-700" type="button">
                  Delete
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>

      <div v-if="landingPages.data.length === 0" class="empty-state p-12 text-center text-gray-500">
        <p>No landing pages found. Create your first one!</p>
      </div>
    </div>

    <div v-if="landingPages.last_page > 1" class="pagination flex gap-2 justify-center mt-6">
      <Link
        v-for="page in landingPages.links"
        :key="page.label"
        :href="page.url"
        :class="['page-link px-3 py-2 border border-gray-300 rounded text-gray-700 no-underline transition-colors', { 'bg-indigo-600 text-white border-indigo-600': page.active, 'opacity-50 cursor-not-allowed': !page.url }]"
        v-html="page.label"
      />
    </div>

    <!-- Delete Confirmation Modal -->
    <div v-if="showDeleteModal" class="modal-overlay fixed inset-0 bg-black/50 flex items-center justify-center z-[1000]" @click="closeDeleteModal">
      <div class="modal bg-white p-6 rounded-lg max-w-md w-full shadow-2xl" @click.stop>
        <h3 class="m-0 mb-4 text-lg text-gray-900">Confirm Delete</h3>
        <p class="m-0 mb-3 text-gray-600">Are you sure you want to delete "{{ pageToDelete?.title }}"?</p>
        <p class="warning text-red-600 text-sm">This action cannot be undone.</p>
        <div class="modal-actions flex gap-3 justify-end mt-6">
          <button @click="closeDeleteModal" class="btn-secondary px-4 py-2 rounded text-sm font-medium cursor-pointer transition-colors no-underline inline-block bg-gray-500 text-white hover:bg-gray-600">Cancel</button>
          <button @click="deletePage" class="btn-danger px-4 py-2 rounded text-sm font-medium cursor-pointer transition-colors no-underline inline-block bg-red-600 text-white hover:bg-red-700">Delete</button>
        </div>
      </div>
    </div>
  </div>
</AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Link, router, Head } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
  landingPages: {
    type: Object,
    required: true,
  },
  frontendUrl: {
    type: String,
    required: true,
  },
});

const showDeleteModal = ref(false);
const pageToDelete = ref(null);

const formatDate = (dateString) => {
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  });
};

const getStatusClass = (page) => {
  return (page.settings?.is_enabled_cta || page.settings?.is_enabled_faq)
    ? 'badge-active bg-green-100 text-green-800'
    : 'badge-inactive bg-red-100 text-red-800';
};

const getStatusText = (page) => {
  return (page.settings?.is_enabled_cta || page.settings?.is_enabled_faq)
    ? 'Active'
    : 'Draft';
};

const confirmDelete = (page) => {
  // console.log('confirmDelete called for page:', page);
  // pageToDelete.value = page;
  showDeleteModal.value = true;
  console.log('showDeleteModal set to:', showDeleteModal.value);
};

const closeDeleteModal = () => {
  showDeleteModal.value = false;
  pageToDelete.value = null;
};

const deletePage = () => {
  if (pageToDelete.value) {
    router.delete(`/admin/landing-pages/${pageToDelete.value.id}`, {
      onSuccess: () => {
        closeDeleteModal();
      },
    });
  }
};
</script>