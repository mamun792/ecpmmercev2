<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { ref } from "vue";
import { useForm, Link, Head } from "@inertiajs/vue3";

const props = defineProps({
  corporateClient: {
    type: Object,
    required: true,
  },
  errors: {
    type: Object,
    required: true,
  },
});

const form = useForm({
  image: null,
  link: props.corporateClient.link || "",
  _method: 'PUT',
});

const imagePreview = ref(props.corporateClient.image ? props.corporateClient.image : null);

const handleFileChange = (event) => {
  const file = event.target.files[0];
  if (file) {
    form.image = file;
    updatePreview(file);
  }
};

const handleDrop = (event) => {
  event.preventDefault();
  const file = event.dataTransfer.files[0];
  if (file) {
    form.image = file;
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
  form.image = null;
  imagePreview.value = null;
};

const submit = () => {
  form.post(route("admin.corporate-clients.update", props.corporateClient.id), {
    forceFormData: true,
    onSuccess: () => {
      form.reset('image');
      if (!form.image) imagePreview.value = props.corporateClient.image ? props.corporateClient.image : null;
    },
  });
};
</script>

<template>
  <Head title="Edit Corporate Client" />
  <AdminLayout>
    <div class="p-6 bg-white shadow-md rounded-md min-h-screen">
      <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-bold mb-6">Edit Corporate Client</h2>
        <Link :href="route('admin.corporate-clients.index')" class="btn-primary">Back</Link>
      </div>

      <form @submit.prevent="submit" enctype="multipart/form-data">
        <!-- Image -->
        <div class="mb-6">
          <label class="block font-medium mb-1">Image</label>
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
              accept="image/*,.svg,.webp"
            />
            <label for="image-upload" class="cursor-pointer block text-gray-600 hover:text-blue-500">
              <span v-if="!form.image && !imagePreview">Drag and drop image here or click to select</span>
              <span v-else-if="form.image">{{ form.image.name }}</span>
              <span v-else>Current image (click to replace)</span>
            </label>
            <div v-if="imagePreview" class="mt-4">
              <img
                :src="imagePreview"
                alt="Corporate Client Image Preview"
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
          <div v-if="errors.image" class="text-red-500 text-sm mt-1">{{ errors.image }}</div>
        </div>

        <!-- Link -->
        <div class="mb-6">
          <label class="block font-medium mb-1">Link (Optional)</label>
          <input
            v-model="form.link"
            type="url"
            class="w-full p-2 border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            placeholder="https://example.com"
          />
          <div v-if="errors.link" class="text-red-500 text-sm mt-1">{{ errors.link }}</div>
        </div>

        <button type="submit" class="btn-primary" :disabled="form.processing">
          {{ form.processing ? "Updating..." : "Update Corporate Client" }}
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