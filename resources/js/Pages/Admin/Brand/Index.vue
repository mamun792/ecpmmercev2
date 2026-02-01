<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Link, useForm, Head } from "@inertiajs/vue3";
import { Trash2Icon, SquarePen } from "lucide-vue-next";
import DeleteModal from "@/Components/Modal/DeleteModal.vue";
import { ref } from "vue";
import axios from 'axios';
import { toast } from "@steveyuowo/vue-hot-toast"

const props = defineProps({
  brands: {
    type: Array,
    required: true,
  },
});

const showDeleteModal = ref(false);
const selectedBrandId = ref(null);

const openDeleteModal = (brandId) => {
  selectedBrandId.value = brandId;
  showDeleteModal.value = true;
};

const handleDeleteSuccess = () => {
  showDeleteModal.value = false;
  selectedBrandId.value = null;
};

const updateStatus = async (brandId, status) => {
  try {
    const response = await axios.put(route('admin.brands.updateStatus', brandId), {
      status: status
    });
    
    if (response.data.success) {
      // Update the local brand status
      const brand = props.brands.find(b => b.id === brandId);
      if (brand) {
        brand.status = status;
      }
      toast.success(response.data.message);
    }
  } catch (error) {
    console.error('Failed to update status:', error);
    toast.error(response.data.message || 'Failed to update status');
  }
};
</script>

<template>
  <Head title="Brand List" />
  <AdminLayout>
    <div class="p-6 bg-white shadow-md rounded-md min-h-screen">
      <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-bold">Brand List</h2>
        <Link :href="route('admin.brands.create')" class="btn-primary">Add Brand</Link>
      </div>

      <table class="w-full border-collapse border border-gray-200">
        <thead>
          <tr class="bg-gray-100">
            <th class="border p-2">#</th>
            <th class="border p-2">Brand Name</th>
            <th class="border p-2">Brand Image</th>
            <th class="border p-2">Status</th>
            <th class="border p-2">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(brand, index) in props.brands" :key="brand.id" class="hover:bg-gray-50">
            <td class="border p-2">{{ index + 1 }}</td>
            <td class="border p-2">{{ brand.brand_name }}</td>
            <td class="border p-2">
              <img 
                v-if="brand.brand_image" 
                :src="brand.brand_image" 
                alt="Brand" 
                class="h-12 mx-auto rounded-md object-cover"
                @error="$event.target.src = '/fallback-image.png'"
              />
              <span v-else class="text-gray-500">No Image</span>
            </td>
            <td class="border p-2">
              <select 
                :value="brand.status"
                @change="updateStatus(brand.id, $event.target.value)"
                :class="{
                  'bg-green-100 text-green-800': brand.status === 'active',
                  'bg-red-100 text-red-800': brand.status === 'deactive'
                }"
                class="border rounded px-2 py-1 focus:outline-none"
              >
                <option value="active">Active</option>
                <option value="deactive">Deactive</option>
              </select>
            </td>
            <td class="border p-2">
              <div class="flex justify-end space-x-2 relative">
                <div class="relative group">
                  <button 
                    @click="openDeleteModal(brand.id)"
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
                    :href="route('admin.brands.edit', brand.id)"
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
          <tr v-if="!props.brands.length">
            <td colspan="5" class="border p-2 text-center text-gray-500">No brands found</td>
          </tr>
        </tbody>
      </table>

      <DeleteModal
        :item-id="selectedBrandId"
        item-name="brand"
        route-name="admin.brands.destroy"
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

select {
  transition: all 0.2s ease;
}
</style>