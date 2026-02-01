
<template>
  <Head title="General Settings | Social Links" />
  <AdminLayout>
    <div class="container mx-auto p-4">
      <h1 class="text-2xl font-bold mb-4">General Settings</h1>
      
      <form @submit.prevent="submitForm">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

          <div class="mb-4">
            <label class="block text-gray-700">Facebook URL</label>
            <input 
              v-model="form.facebook_url" 
              type="url" 
              class="w-full p-2 border rounded"
              :class="{ 'border-red-500': form.errors.facebook_url }"
            >
            <span v-if="form.errors.facebook_url" class="text-red-500 text-sm">
              {{ form.errors.facebook_url }}
            </span>
          </div>

          <div class="mb-4">
            <label class="block text-gray-700">TikTok URL</label>
            <input 
              v-model="form.tiktok_url" 
              type="url" 
              class="w-full p-2 border rounded"
              :class="{ 'border-red-500': form.errors.tiktok_url }"
            >
            <span v-if="form.errors.tiktok_url" class="text-red-500 text-sm">
              {{ form.errors.tiktok_url }}
            </span>
          </div>

          <div class="mb-4">
            <label class="block text-gray-700">YouTube URL</label>
            <input 
              v-model="form.youtube_url" 
              type="url" 
              class="w-full p-2 border rounded"
              :class="{ 'border-red-500': form.errors.youtube_url }"
            >
            <span v-if="form.errors.youtube_url" class="text-red-500 text-sm">
              {{ form.errors.youtube_url }}
            </span>
          </div>

          <div class="mb-4">
            <label class="block text-gray-700">Instagram URL</label>
            <input 
              v-model="form.instagram_url" 
              type="url" 
              class="w-full p-2 border rounded"
              :class="{ 'border-red-500': form.errors.instagram_url }"
            >
            <span v-if="form.errors.instagram_url" class="text-red-500 text-sm">
              {{ form.errors.instagram_url }}
            </span>
          </div>

          <div class="mb-4">
            <label class="block text-gray-700">X URL</label>
            <input 
              v-model="form.x_url" 
              type="url" 
              class="w-full p-2 border rounded"
              :class="{ 'border-red-500': form.errors.x_url }"
            >
            <span v-if="form.errors.x_url" class="text-red-500 text-sm">
              {{ form.errors.x_url }}
            </span>
          </div>
        </div>

        <button 
          type="submit" 
          class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600"
          :disabled="form.processing"
        >
          Save Settings
        </button>
      </form>
    </div>
  </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { useForm, Head } from '@inertiajs/vue3'
import { toast } from "@steveyuowo/vue-hot-toast"

// Define props 
const props = defineProps({
  settings: Object,
  errors: Object
})

// Initialize form with settings data
const form = useForm({
  facebook_url: props.settings?.facebook_url || '',
  tiktok_url: props.settings?.tiktok_url || '',
  youtube_url: props.settings?.youtube_url || '',
  instagram_url: props.settings?.instagram_url || '',
  x_url: props.settings?.x_url || '',
})

// Form submission function
const submitForm = () => {
  form.post(route('admin.settings.store'), {
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => {
      toast.success('Settings updated successfully')
    },
    onError: (errors) => {
      toast.error('Please fix the errors in the form')
    }
  })
}
</script>