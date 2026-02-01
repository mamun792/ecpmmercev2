<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { ref } from "vue";
import { useForm, Link, Head } from "@inertiajs/vue3";

const form = useForm({
  brand_name: "",
  brand_image: null,
});

const imagePreview = ref(null);

const handleFileChange = (event) => {
  const file = event.target.files[0];
  if (file) {
    form.brand_image = file;
    const reader = new FileReader();
    reader.onload = (e) => {
      imagePreview.value = e.target.result;
    };
    reader.readAsDataURL(file);
  }
};

const submit = () => {
  form.post(route("admin.brands.store"), {
    forceFormData: true,
    onSuccess: () => {
      form.reset();
      imagePreview.value = null;
    },
  });
};
</script>

<template>
  <Head title="Create Brand" />
  <AdminLayout>
    <div class="p-6 bg-white shadow-md rounded-md min-h-screen">
      <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-bold">Add Brand</h2>
        <Link :href="route('admin.brands.index')" class="btn-primary">Back</Link>
      </div>

      <form @submit.prevent="submit" enctype="multipart/form-data">
        <div class="mb-6">
          <label class="block font-medium mb-1">Brand Name</label>
          <input v-model="form.brand_name" type="text" class="w-full p-2 border rounded-md" required />
        </div>

        <div class="mb-6">
          <label class="block font-medium mb-1">Brand Image</label>
          <input type="file" @change="handleFileChange" class="w-full p-2 border rounded-md" accept="image/*" />
          <div v-if="imagePreview" class="mt-4">
            <img :src="imagePreview" alt="Preview" class="max-w-full h-auto max-h-40 mx-auto rounded-md" />
          </div>
        </div>

        <button type="submit" class="btn-primary" :disabled="form.processing">
          {{ form.processing ? 'Creating...' : 'Create Brand' }}
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
