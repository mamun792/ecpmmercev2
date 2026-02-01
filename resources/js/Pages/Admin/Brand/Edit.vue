<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { ref } from "vue";
import { useForm, Link, Head } from "@inertiajs/vue3";

const props = defineProps({
  brand: {
    type: Object,
    required: true,
  },
  errors: {
    type: Object,
    required: true,
  },
});

const form = useForm({
  brand_name: props.brand.brand_name,
  brand_image: null,
  _method: 'PUT',
});

const imagePreview = ref(props.brand.brand_image ? props.brand.brand_image : null);
const imageDragActive = ref(false);

const handleFileChange = (event) => {
  const file = event.target.files[0];
  if (file) {
    form.brand_image = file;
    updatePreview(file);
  }
};

const handleDrop = (event) => {
  event.preventDefault();
  const file = event.dataTransfer.files[0];
  if (file) {
    form.brand_image = file;
    updatePreview(file);
  }
  imageDragActive.value = false;
};

const handleDragOver = (event) => {
  event.preventDefault();
  imageDragActive.value = true;
};

const handleDragLeave = (event) => {
  event.preventDefault();
  imageDragActive.value = false;
};

const updatePreview = (file) => {
  const reader = new FileReader();
  reader.onload = (e) => {
    imagePreview.value = e.target.result;
  };
  reader.readAsDataURL(file);
};

const removeFile = () => {
  form.brand_image = null;
  imagePreview.value = null;
};

const submit = () => {
  form.post(route("admin.brands.update", props.brand.id), {
    forceFormData: true,
    onSuccess: () => {
      form.reset('brand_image');
      if (!form.brand_image) imagePreview.value = props.brand.brand_image ? props.brand.brand_image : null;
    },
  });
};
</script>

<template>
  <Head title="Edit Brand" />
  <AdminLayout>
    <div class="p-6 bg-white shadow-md rounded-md min-h-screen">
      <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-bold mb-6">Edit Brand</h2>
        <Link :href="route('admin.brands.index')" class="btn-primary">Back</Link>
      </div>

      <form @submit.prevent="submit" enctype="multipart/form-data">
        <!-- Brand Name -->
        <div class="mb-6">
          <label class="block font-medium mb-1">Brand Name</label>
          <input
            v-model="form.brand_name"
            type="text"
            class="w-full p-2 border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            required
          />
          <div v-if="errors.brand_name" class="text-red-500 text-sm mt-1">{{ errors.brand_name }}</div>
        </div>

        <!-- Brand Image -->
        <div class="mb-6">
          <label class="block font-medium mb-1">Brand Image</label>
          <div 
            @drop="handleDrop" 
            @dragover="handleDragOver"
            @dragleave="handleDragLeave" 
            :class="[
              'relative border-2 border-dashed rounded-md p-4 text-center',
              imageDragActive ? 'border-blue-500 bg-blue-50' : 'border-gray-300',
            ]"
          >
            <input 
              type="file" 
              @change="handleFileChange" 
              class="hidden" 
              id="image-upload"
              accept="image/*" 
            />
            <label for="image-upload" class="cursor-pointer block text-gray-600 hover:text-blue-500">
              <span v-if="!form.brand_image && !imagePreview">Drag and drop image here or click to select</span>
              <span v-else-if="form.brand_image">{{ form.brand_image.name }}</span>
              <span v-else>Current image (click to replace)</span>
            </label>
            <div v-if="imagePreview" class="mt-4">
              <img 
                :src="imagePreview" 
                alt="Brand Image Preview" 
                class="max-w-full h-auto max-h-40 mx-auto rounded-md"
                @error="$event.target.src = '/fallback-image.png'"
              />
              <button 
                type="button" 
                @click="removeFile"
                class="mt-2 text-red-500 hover:text-red-700 text-sm"
              >
                Remove Image
              </button>
            </div>
          </div>
          <div v-if="errors.brand_image" class="text-red-500 text-sm mt-1">{{ errors.brand_image }}</div>
        </div>

        <button type="submit" class="btn-primary" :disabled="form.processing">
          {{ form.processing ? "Updating..." : "Update Brand" }}
        </button>
      </form>
    </div>
  </AdminLayout>
</template>

<style scoped>
input:focus {
  outline: none;
}
</style>