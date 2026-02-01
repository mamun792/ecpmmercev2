<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Link, useForm, Head } from "@inertiajs/vue3";
import { Trash2Icon, SquarePen } from "lucide-vue-next";
import DeleteModal from "@/Components/Modal/DeleteModal.vue";
import { ref } from "vue";

const props = defineProps({
  corporateClients: {
    type: Array,
    required: true,
  },
});

const showDeleteModal = ref(false);
const selectedCorporateClientId = ref(null);

const openDeleteModal = (corporateClientId) => {
  selectedCorporateClientId.value = corporateClientId;
  showDeleteModal.value = true;
};

const handleDeleteSuccess = () => {
  showDeleteModal.value = false;
  selectedCorporateClientId.value = null;
};
</script>

<template>
  <Head title="Corporate Clients List" />
  <AdminLayout>
    <div class="p-6 bg-white shadow-md rounded-md min-h-screen">
      <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-bold">Corporate Clients List</h2>
        <Link :href="route('admin.corporate-clients.create')" class="btn-primary">Add Corporate Client</Link>
      </div>

      <table class="w-full border-collapse border border-gray-200">
        <thead>
          <tr class="bg-gray-100">
            <th class="border p-2">#</th>
            <th class="border p-2">Image</th>
            <th class="border p-2">Link</th>
            <th class="border p-2">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(corporateClient, index) in props.corporateClients" :key="corporateClient.id" class="hover:bg-gray-50">
            <td class="border p-2">{{ index + 1 }}</td>
            <td class="border p-2">
              <img
                v-if="corporateClient.image"
                :src="corporateClient.image"
                alt="Corporate Client"
                class="h-12 mx-auto rounded-md object-cover"
                @error="$event.target.src = '/fallback-image.png'"
              />
              <span v-else class="text-gray-500">No Image</span>
            </td>
            <td class="border p-2">
              <a v-if="corporateClient.link" :href="corporateClient.link" target="_blank" class="text-blue-500 hover:underline">{{ corporateClient.link }}</a>
              <span v-else class="text-gray-500">No Link</span>
            </td>
            <td class="border p-2">
              <div class="flex justify-end space-x-2 relative">
                <div class="relative group">
                  <button
                    @click="openDeleteModal(corporateClient.id)"
                    class="table_delete_action hover:bg-red-500 hover:text-white p-2 rounded"
                  >
                    <Trash2Icon size="20" />
                  </button>
                  <span
                    class="absolute -top-8 left-1/2 transform -translate-x-1/2 scale-0 group-hover:scale-100 transition bg-gray-800 text-white text-xs rounded px-2 py-1"
                  >
                    Delete
                  </span>
                </div>
                <div class="relative group">
                  <Link
                    :href="route('admin.corporate-clients.edit', corporateClient.id)"
                    class="table_edit_action hover:bg-green-500 hover:text-white p-2 rounded"
                  >
                    <SquarePen size="20" />
                  </Link>
                  <span
                    class="absolute -top-8 left-1/2 transform -translate-x-1/2 scale-0 group-hover:scale-100 transition bg-gray-800 text-white text-xs rounded px-2 py-1"
                  >
                    Edit
                  </span>
                </div>
              </div>
            </td>
          </tr>
          <tr v-if="!props.corporateClients.length">
            <td colspan="4" class="border p-2 text-center text-gray-500">No corporate clients found</td>
          </tr>
        </tbody>
      </table>

      <DeleteModal
        :item-id="selectedCorporateClientId"
        item-name="corporate client"
        route-name="admin.corporate-clients.destroy"
        v-model:visible="showDeleteModal"
        @deleted="handleDeleteSuccess"
      />
    </div>
  </AdminLayout>
</template>

<style scoped>
img {
  transition: transform 0.2s ease;
}

img:hover {
  transform: scale(1.1);
}

button {
  transition: color 0.2s ease;
}
</style>