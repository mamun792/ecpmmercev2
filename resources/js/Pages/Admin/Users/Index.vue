<template>
  <Head title="Users" />
  <AdminLayout>
    <div class="p-6">
      <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-semibold">Users Management</h1>
        <button 
          @click="openCreateModal"
          class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700"
        >
          Create New User
        </button>
      </div>

      <!-- Users Table -->
      <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Roles</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created At</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="user in users" :key="user.id">
              <td class="px-6 py-4 whitespace-nowrap">{{ user.name }}</td>
              <td class="px-6 py-4 whitespace-nowrap">{{ user.email }}</td>
              <td class="px-6 py-4">
                <div class="flex flex-wrap gap-1">
                  <span 
                    v-for="role in user.roles" 
                    :key="role.id"
                    class="px-2 py-1 bg-blue-100 text-blue-800 rounded-full text-xs"
                  >
                    {{ role.name }}
                  </span>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">{{ formatDate(user.created_at) }}</td>
              <td class="px-6 py-4 whitespace-nowrap">
                <button 
                  @click="openEditModal(user)"
                  class="text-indigo-600 hover:text-indigo-900 mr-3"
                >
                  Edit
                </button>
                <button 
                  v-if="!user.roles.some(role => role.name === 'admin')"
                  @click="deleteUser(user)"
                  class="text-red-600 hover:text-red-900"
                >
                  Delete
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Create/Edit Modal -->
      <Modal :show="showModal" @close="closeModal">
        <div class="p-6">
          <h2 class="text-lg font-medium mb-4">{{ isEditing ? 'Edit User' : 'Create New User' }}</h2>
          <form @submit.prevent="submitForm">
            <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700">Name</label>
              <input 
                type="text" 
                v-model="form.name"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                required
              >
            </div>

            <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700">Email</label>
              <input 
                type="email" 
                v-model="form.email"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                required
              >
            </div>

            <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700">Password</label>
              <input 
                type="password" 
                v-model="form.password"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                :required="!isEditing"
                :placeholder="isEditing ? 'Leave blank to keep current password' : ''"
              >
            </div>

            <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700 mb-2">Roles</label>
              <div class="grid grid-cols-3 gap-4">
                <div v-for="role in roles" :key="role.id" class="flex items-center">
                  <input 
                    type="checkbox" 
                    :value="role.id"
                    v-model="form.roles"
                    class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                  >
                  <label class="ml-2 text-sm text-gray-700">{{ role.name }}</label>
                </div>
              </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
              <button 
                type="button"
                @click="closeModal"
                class="bg-gray-50 px-4 py-2 rounded-md text-gray-700 hover:bg-gray-100"
              >
                Cancel
              </button>
              <button 
                type="submit"
                class="bg-blue-600 px-4 py-2 rounded-md text-white hover:bg-blue-700"
                :disabled="form.processing"
              >
                {{ isEditing ? 'Update' : 'Create' }}
              </button>
            </div>
          </form>
        </div>
      </Modal>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
  users: Array,
  roles: Array
});

const showModal = ref(false);
const isEditing = ref(false);
const editingUser = ref(null);

const form = reactive({
  name: '',
  email: '',
  password: '',
  roles: [],
  processing: false
});

const openCreateModal = () => {
  isEditing.value = false;
  form.name = '';
  form.email = '';
  form.password = '';
  form.roles = [];
  showModal.value = true;
};

const openEditModal = (user) => {
  isEditing.value = true;
  editingUser.value = user;
  form.name = user.name;
  form.email = user.email;
  form.password = '';
  form.roles = user.roles.map(role => role.id);
  showModal.value = true;
};

const closeModal = () => {
  showModal.value = false;
  form.name = '';
  form.email = '';
  form.password = '';
  form.roles = [];
  editingUser.value = null;
};

const submitForm = () => {
  form.processing = true;
  
  if (isEditing.value) {
    router.put(`/admin/users/${editingUser.value.id}`, form, {
      onSuccess: () => {
        closeModal();
        form.processing = false;
        router.reload();
      },
      onError: () => {
        form.processing = false;
      }
    });
  } else {
    router.post('/admin/users', form, {
      onSuccess: () => {
        closeModal();
        form.processing = false;
        router.reload();
      },
      onError: () => {
        form.processing = false;
      }
    });
  }
};

const deleteUser = (user) => {
  if (confirm('Are you sure you want to delete this user?')) {
    router.delete(`/admin/users/${user.id}`, {
      onSuccess: () => {
        router.reload();
      }
    });
  }
};

const formatDate = (dateString) => {
  if (!dateString) return 'N/A';
  
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};
</script>