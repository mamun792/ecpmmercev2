<template>
  <AdminLayout>
    <div class="p-6">
      <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-semibold">Role Management</h1>
        <button 
          @click="openCreateModal"
          class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700"
        >
          Create New Role
        </button>
      </div>

      <!-- Roles Table -->
      <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role Name</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Permissions</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="role in roles" :key="role.id">
              <td class="px-6 py-4 whitespace-nowrap">{{ role.name }}</td>
              <td class="px-6 py-4">
                <div class="flex flex-wrap gap-1">
                  <span 
                    v-for="permission in role.permissions" 
                    :key="permission.id"
                    class="px-2 py-1 bg-blue-100 text-blue-800 rounded-full text-xs"
                  >
                    {{ permission.name }}
                  </span>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <button 
                  v-if="role.name !== 'admin'"
                  @click="openEditModal(role)"
                  class="text-indigo-600 hover:text-indigo-900 mr-3"
                >
                  Edit
                </button>
                <button 
                  v-if="role.name !== 'admin'"
                  @click="deleteRole(role)"
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
          <h2 class="text-lg font-medium mb-4">{{ isEditing ? 'Edit Role' : 'Create New Role' }}</h2>
          <form @submit.prevent="submitForm">
            <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700">Role Name</label>
              <input 
                type="text" 
                v-model="form.name"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                :disabled="form.name === 'admin'"
              >
            </div>

            <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700 mb-2">Permissions</label>
              <div class="grid grid-cols-3 gap-4">
                <div v-for="permission in permissions" :key="permission.id" class="flex items-center">
                  <input 
                    type="checkbox" 
                    :value="permission.name"
                    v-model="form.permissions"
                    class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                  >
                  <label class="ml-2 text-sm text-gray-700">{{ permission.name }}</label>
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
import { ref, reactive } from 'vue'
import { router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import Modal from '@/Components/Modal.vue'

const props = defineProps({
  roles: Array,
  permissions: Array
})

const showModal = ref(false)
const isEditing = ref(false)
const editingRole = ref(null)

const form = reactive({
  name: '',
  permissions: [],
  processing: false
})

const openCreateModal = () => {
  isEditing.value = false
  form.name = ''
  form.permissions = []
  showModal.value = true
}

const openEditModal = (role) => {
  isEditing.value = true
  editingRole.value = role
  form.name = role.name
  form.permissions = role.permissions.map(p => p.name)
  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
  form.name = ''
  form.permissions = []
  editingRole.value = null
}

const submitForm = () => {
  form.processing = true;
  
  if (isEditing.value) {
    router.put(`/admin/roles/${editingRole.value.id}`, form, {
      onSuccess: () => {
        closeModal();
        router.reload(); // Force page reload to refresh the table
        form.processing = false;
      },
      onError: () => {
        form.processing = false;
      }
    });
  } else {
    router.post('/admin/roles', form, {
      onSuccess: () => {
        closeModal();
        router.reload(); // Force page reload to refresh the table
        form.processing = false;
      },
      onError: () => {
        form.processing = false;
      }
    });
  }
}

const deleteRole = (role) => {
  if (confirm('Are you sure you want to delete this role?')) {
    router.delete(`/admin/roles/${role.id}`, {
      onSuccess: () => {
        router.reload(); // Force page reload to refresh the table
      }
    });
  }
}
</script>