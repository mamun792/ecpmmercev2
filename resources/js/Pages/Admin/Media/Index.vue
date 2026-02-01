<template>
  <Head title="Media Management" />
  <AdminLayout>
    <div class="container mx-auto p-6">
      <h1 class="text-2xl font-bold mb-6">Media Management</h1>

      <!-- Media Upload Form -->
      <div class="bg-white p-6 rounded-lg shadow-md">
        <form @submit.prevent="submit" enctype="multipart/form-data">
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Logo -->
            <div class="border rounded-lg p-4">
              <h3 class="text-sm font-medium text-gray-700 mb-2">Logo (150px)</h3>
              <div class="flex justify-center mb-4 h-32 bg-gray-50 rounded-md">
                <img v-if="previews.logo" :src="previews.logo" alt="Logo Preview" class="max-h-full object-contain" />
                <span v-else class="text-gray-400 self-center">No Logo</span>
              </div>
              <input
                type="file"
                accept="image/*"
                @change="handleImageChange($event, 'logo')"
                class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
              />
              <span v-if="form.errors.logo" class="text-red-600 text-sm">{{ form.errors.logo }}</span>
            </div>

            <!-- Favicon -->
            <div class="border rounded-lg p-4">
              <h3 class="text-sm font-medium text-gray-700 mb-2">Favicon (25px*25px)</h3>
              <div class="flex justify-center mb-4 h-32 bg-gray-50 rounded-md">
                <img v-if="previews.favicon" :src="previews.favicon" alt="Favicon Preview" class="max-h-full object-contain" />
                <span v-else class="text-gray-400 self-center">No Favicon</span>
              </div>
              <input
                type="file"
                accept="image/*"
                @change="handleImageChange($event, 'favicon')"
                class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
              />
              <span v-if="form.errors.favicon" class="text-red-600 text-sm">{{ form.errors.favicon }}</span>
            </div>

            <!-- Loader Logo -->
            <div class="border rounded-lg p-4">
              <h3 class="text-sm font-medium text-gray-700 mb-2">Loader (150px*150px)</h3>
              <div class="flex justify-center mb-4 h-32 bg-gray-50 rounded-md">
                <img v-if="previews.loder_logo" :src="previews.loder_logo" alt="Loader Preview" class="max-h-full object-contain" />
                <span v-else class="text-gray-400 self-center">No Loader</span>
              </div>
              <input
                type="file"
                accept="image/*"
                @change="handleImageChange($event, 'loder_logo')"
                class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
              />
              <span v-if="form.errors.loder_logo" class="text-red-600 text-sm">{{ form.errors.loder_logo }}</span>
            </div>

            <!-- Footer Logo -->
            <div class="border rounded-lg p-4">
              <h3 class="text-sm font-medium text-gray-700 mb-2">Footer (150px)</h3>
              <div class="flex justify-center mb-4 h-32 bg-gray-50 rounded-md">
                <img v-if="previews.footer_logo" :src="previews.footer_logo" alt="Footer Preview" class="max-h-full object-contain" />
                <span v-else class="text-gray-400 self-center">No Footer Logo</span>
              </div>
              <input
                type="file"
                accept="image/*"
                @change="handleImageChange($event, 'footer_logo')"
                class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
              />
              <span v-if="form.errors.footer_logo" class="text-red-600 text-sm">{{ form.errors.footer_logo }}</span>
            </div>

            <!-- Footer Payment Logo -->
            <div class="border rounded-lg p-4">
              <h3 class="text-sm font-medium text-gray-700 mb-2">Footer Payment Logo (150px)</h3>
              <div class="flex justify-center mb-4 h-32 bg-gray-50 rounded-md">
                <img v-if="previews.footer_payment_logo" :src="previews.footer_payment_logo" alt="Footer Payment Preview" class="max-h-full object-contain" />
                <span v-else class="text-gray-400 self-center">No Footer Payment Logo</span>
              </div>
              <input
                type="file"
                accept="image/*"
                @change="handleImageChange($event, 'footer_payment_logo')"
                class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
              />
              <span v-if="form.errors.footer_payment_logo" class="text-red-600 text-sm">{{ form.errors.footer_payment_logo }}</span>
            </div>
          </div>

          <div class="mt-6 flex justify-end space-x-4">
            <button
              type="submit"
              :disabled="form.processing"
              class="bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 disabled:opacity-50"
            >
              Save Changes
            </button>
          </div>
        </form>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { toast } from "@steveyuowo/vue-hot-toast"
import { useForm, router, Head } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
  media: Object
});

const form = useForm({
  logo: null,
  footer_logo: null,
  favicon: null,
  loder_logo: null,
  footer_payment_logo: null,
  _method: 'POST' // Default to POST for create
});

const previews = ref({
  logo: props.media?.logo || null,
  footer_logo: props.media?.footer_logo || null,
  favicon: props.media?.favicon || null,
  loder_logo: props.media?.loder_logo || null,
  footer_payment_logo: props.media?.footer_payment_logo || null
});

const handleImageChange = (event, field) => {
  const file = event.target.files[0];
  if (file) {
    form[field] = file;
    previews.value[field] = URL.createObjectURL(file);
  } else {
    form[field] = null;
    previews.value[field] = props.media?.[field] || null;
  }
};

const submit = () => {
  if (props.media) {
    // Update existing media
    form._method = 'PUT';
    form.post('/admin/media', {
      preserveState: true,
      onSuccess: () => {
        form.reset();
        toast.success("Media updated successfully!");
      }
    });
  } else {
    // Create new media
    form._method = 'POST';
    form.post('/admin/media', {
      preserveState: true,
      onSuccess: () => {
        form.reset();
        toast.success("Media created successfully!");
      }
    });
  }
};

const resetForm = () => {
  form.reset();
  form._method = 'POST'; // Reset to POST for next create
  previews.value = {
    logo: null,
    footer_logo: null,
    favicon: null,
    loder_logo: null,
    footer_payment_logo: null
  };
};

// Update previews when media prop changes
watch(() => props.media, (newMedia) => {
  previews.value = {
    logo: newMedia?.logo || null,
    footer_logo: newMedia?.footer_logo || null,
    favicon: newMedia?.favicon || null,
    loder_logo: newMedia?.loder_logo || null,
    footer_payment_logo: newMedia?.footer_payment_logo || null
  };
});
</script>