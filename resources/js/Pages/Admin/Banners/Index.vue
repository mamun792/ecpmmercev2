
<template>
  <Head title="Banner Management" />
<AdminLayout>
  <div class="w-full mx-auto px-4 py-8">
    <div class="bg-white rounded-lg shadow-lg p-6 border border-gray-200">
      <h1 class="text-2xl font-bold mb-6">Banner Management</h1>
      
      <form @submit.prevent="submitForm" enctype="multipart/form-data">
        <!-- Top Banners Section -->
        <div class="mb-8">
          <h2 class="text-xl font-semibold mb-4">Top Banners</h2>
          
          <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div v-for="n in 3" :key="`top_banner${n}`" class="space-y-4 border border-gray-200 p-4 rounded-lg">
              <h3 class="text-lg font-medium">Top Banner {{ n }}</h3>
              
              <!-- Image Upload -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                  Banner Image
                </label>
                
                <!-- Current Image Preview -->
                <div v-if="getBannerImage(`top_banner${n}`)" class="relative">
                  <img 
                    :src="getBannerImage(`top_banner${n}`)" 
                    :alt="`Top Banner ${n}`"
                    class="w-full h-32 object-cover rounded-lg border border-gray-300"
                  >
                  <button 
                    type="button"
                    @click="deleteBanner(`top_banner${n}`)"
                    class="absolute top-2 right-2 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center hover:bg-red-600"
                  >
                    ×
                  </button>
                </div>
                
                <!-- Live Preview on Upload -->
                <div v-if="previews[`top_banner${n}`]" class="relative">
                  <img 
                    :src="previews[`top_banner${n}`]" 
                    :alt="`Top Banner ${n} Preview`"
                    class="w-full h-32 object-cover rounded-lg border border-gray-300"
                  >
                  <button 
                    type="button"
                    @click="clearPreview(`top_banner${n}`)"
                    class="absolute top-2 right-2 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center hover:bg-red-600"
                  >
                    ×
                  </button>
                </div>
                
                <!-- File Input -->
                <input 
                  type="file"
                  :ref="`top_banner${n}`"
                  @change="handleFileChange(`top_banner${n}`, $event)"
                  accept="image/*"
                  class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                >
                <p v-if="form.errors[`top_banner${n}`]" class="text-sm text-red-600 mt-1">{{ form.errors[`top_banner${n}`] }}</p>
              </div>
              
              <!-- URL Input -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                  Banner URL
                </label>
                <input 
                  type="url"
                  v-model="form[`top_banner${n}_url`]"
                  placeholder="https://example.com"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                <p v-if="form.errors[`top_banner${n}_url`]" class="text-sm text-red-600 mt-1">{{ form.errors[`top_banner${n}_url`] }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- New Arrival Banners Section -->
        <div class="mb-8">
          <h2 class="text-xl font-semibold mb-4">New Arrival Banners</h2>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div v-for="n in 2" :key="`new_arrival_b_banner${n}`" class="space-y-4 border border-gray-200 p-4 rounded-lg">
              <h3 class="text-lg font-medium">New Arrival Banner {{ n }}</h3>
              
              <!-- Image Upload -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                  Banner Image
                </label>
                
                <!-- Current Image Preview -->
                <div v-if="getBannerImage(`new_arrival_b_banner${n}`)" class="relative">
                  <img 
                    :src="getBannerImage(`new_arrival_b_banner${n}`)" 
                    :alt="`New Arrival Banner ${n}`"
                    class="w-full h-32 object-cover rounded-lg border border-gray-300"
                  >
                  <button 
                    type="button"
                    @click="deleteBanner(`new_arrival_b_banner${n}`)"
                    class="absolute top-2 right-2 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center hover:bg-red-600"
                  >
                    ×
                  </button>
                </div>
                
                <!-- Live Preview on Upload -->
                <div v-if="previews[`new_arrival_b_banner${n}`]" class="relative">
                  <img 
                    :src="previews[`new_arrival_b_banner${n}`]" 
                    :alt="`New Arrival Banner ${n} Preview`"
                    class="w-full h-32 object-cover rounded-lg border border-gray-300"
                  >
                  <button 
                    type="button"
                    @click="clearPreview(`new_arrival_b_banner${n}`)"
                    class="absolute top-2 right-2 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center hover:bg-red-600"
                  >
                    ×
                  </button>
                </div>
                
                <!-- File Input -->
                <input 
                  type="file"
                  :ref="`new_arrival_b_banner${n}`"
                  @change="handleFileChange(`new_arrival_b_banner${n}`, $event)"
                  accept="image/*"
                  class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                >
                <p v-if="form.errors[`new_arrival_b_banner${n}`]" class="text-sm text-red-600 mt-1">{{ form.errors[`new_arrival_b_banner${n}`] }}</p>
              </div>
              
              <!-- URL Input -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                  Banner URL
                </label>
                <input 
                  type="url"
                  v-model="form[`new_arrival_b_banner${n}_url`]"
                  placeholder="https://example.com"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                <p v-if="form.errors[`new_arrival_b_banner${n}_url`]" class="text-sm text-red-600 mt-1">{{ form.errors[`new_arrival_b_banner${n}_url`] }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Random Banners Section -->
        <div class="mb-8">
          <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-semibold">Random Banners</h2>
            <button 
              type="button"
              @click="addRandomBanner"
              class="bg-green-500 text-white px-4 py-2 rounded-md hover:bg-green-600 transition-colors"
            >
              Add Random Banner
            </button>
          </div>
          
          <div class="space-y-4">
            <div 
              v-for="(randomBanner, index) in form.random_banners" 
              :key="`random_banner_${index}`"
              class="bg-gray-50 p-4 rounded-lg border border-gray-200"
            >
              <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium">Random Banner {{ index + 1 }}</h3>
                <button 
                  type="button"
                  @click="removeRandomBanner(index)"
                  class="bg-red-500 text-white px-3 py-1 rounded-md hover:bg-red-600 transition-colors"
                >
                  Remove
                </button>
              </div>
              
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Image Upload -->
                <div class="space-y-2">
                  <label class="block text-sm font-medium text-gray-700">
                    Banner Image
                  </label>
                  
                  <!-- Current Image Preview -->
                  <div v-if="getRandomBannerImage(index)" class="relative">
                    <img 
                      :src="getRandomBannerImage(index)" 
                      :alt="`Random Banner ${index + 1}`"
                      class="w-full h-32 object-cover rounded-lg border border-gray-300"
                    >
                    <button 
                      type="button"
                      @click="deleteRandomBannerImage(index)"
                      class="absolute top-2 right-2 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center hover:bg-red-600"
                    >
                      ×
                    </button>
                  </div>
                  
                  <!-- Live Preview on Upload -->
                  <div v-if="previews[`random_banner${index}`]" class="relative">
                    <img 
                      :src="previews[`random_banner${index}`]" 
                      :alt="`Random Banner ${index + 1} Preview`"
                      class="w-full h-32 object-cover rounded-lg border border-gray-300"
                    >
                    <button 
                      type="button"
                      @click="clearPreview(`random_banner${index}`)"
                      class="absolute top-2 right-2 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center hover:bg-red-600"
                    >
                      ×
                    </button>
                  </div>
                  
                  <!-- File Input -->
                  <input 
                    type="file"
                    :ref="`random_banner${index}`"
                    @change="handleRandomBannerFileChange(index, $event)"
                    accept="image/*"
                    class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                  >
                  <p v-if="form.errors[`random_banners.${index}.image`]" class="text-sm text-red-600 mt-1">{{ form.errors[`random_banners.${index}.image`] }}</p>
                </div>
                
                <!-- URL Input -->
                <div class="space-y-2">
                  <label class="block text-sm font-medium text-gray-700">
                    Banner URL
                  </label>
                  <input 
                    type="url"
                    v-model="randomBanner.url"
                    placeholder="https://example.com"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                  >
                  <p v-if="form.errors[`random_banners.${index}.url`]" class="text-sm text-red-600 mt-1">{{ form.errors[`random_banners.${index}.url`] }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Submit Button -->
        <div class="flex justify-end">
          <button 
            type="submit"
            :disabled="processing"
            class="bg-blue-500 text-white px-6 py-2 rounded-md hover:bg-blue-600 transition-colors disabled:opacity-50"
          >
            {{ processing ? 'Saving...' : 'Save Banners' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { ref } from 'vue'
import { useForm, Head } from '@inertiajs/vue3'
import axios from 'axios'

// Props
const props = defineProps({
  banner: {
    type: Object,
    default: () => ({})
  }
})

// State
const processing = ref(false)
const previews = ref({})

// Initialize form data
const form = useForm({
  top_banner1: null,
  top_banner1_url: props.banner?.top_banner1_url || '',
  top_banner2: null,
  top_banner2_url: props.banner?.top_banner2_url || '',
  top_banner3: null,
  top_banner3_url: props.banner?.top_banner3_url || '',
  new_arrival_b_banner1: null,
  new_arrival_b_banner1_url: props.banner?.new_arrival_b_banner1_url || '',
  new_arrival_b_banner2: null,
  new_arrival_b_banner2_url: props.banner?.new_arrival_b_banner2_url || '',
  random_banners: props.banner?.random_banners ? JSON.parse(props.banner.random_banners) : []
})

// Get banner image URL
const getBannerImage = (field) => {
  return props.banner?.[field] || null
}

// Get random banner image URL
const getRandomBannerImage = (index) => {
  const randomBanners = props.banner?.random_banners ? JSON.parse(props.banner.random_banners) : []
  return randomBanners[index]?.image || null
}

// Handle file change for regular banners
const handleFileChange = (field, event) => {
  const file = event.target.files[0]
  if (file) {
    form[field] = file
    previews.value[field] = URL.createObjectURL(file)
  }
}

// Handle file change for random banners
const handleRandomBannerFileChange = (index, event) => {
  const file = event.target.files[0]
  if (file) {
    form.random_banners[index].image = file
    previews.value[`random_banner${index}`] = URL.createObjectURL(file)
  }
}

// Clear preview
const clearPreview = (field) => {
  previews.value[field] = null
  if (field.startsWith('random_banner')) {
    const index = parseInt(field.replace('random_banner', ''))
    form.random_banners[index].image = null
    // Reset file input
    if (refs[`random_banner${index}`]) {
      refs[`random_banner${index}`].value = null
    }
  } else {
    form[field] = null
    if (refs[field]) {
      refs[field].value = null
    }
  }
}

// Add new random banner
const addRandomBanner = () => {
  form.random_banners.push({
    image: null,
    url: ''
  })
}

// Remove random banner
const removeRandomBanner = async (index) => {
  if (confirm('Are you sure you want to remove this random banner?')) {
    try {
      const response = await axios.delete('/admin/banners/random-banner', {
        data: { index }
      })
      form.random_banners.splice(index, 1)
      delete previews.value[`random_banner${index}`]
      // Update form with fresh random_banners from response
      if (response.data.random_banners) {
        form.random_banners = response.data.random_banners
      }
    } catch (error) {
      console.error('Error removing random banner:', error)
    }
  }
}

// Delete banner image
const deleteBanner = async (field) => {
  if (confirm('Are you sure you want to delete this banner?')) {
    try {
      await axios.delete('/admin/banners/delete-banner', {
        data: { field }
      })
      form[field] = null
      form[field + '_url'] = ''
      delete previews.value[field]
      if (refs[field]) {
        refs[field].value = null
      }
      window.location.reload()
    } catch (error) {
      console.error('Error deleting banner:', error)
    }
  }
}

// Delete random banner image
const deleteRandomBannerImage = async (index) => {
  if (confirm('Are you sure you want to delete this random banner image?')) {
    try {
      await axios.delete('/admin/banners/random-banner-image', {
        data: { index }
      })
      form.random_banners[index].image = null
      delete previews.value[`random_banner${index}`]
      if (refs[`random_banner${index}`]) {
        refs[`random_banner${index}`].value = null
      }
      window.location.reload()
    } catch (error) {
      console.error('Error deleting random banner image:', error)
    }
  }
}

// Submit form
const submitForm = () => {
  processing.value = true
  
  const formData = new FormData()
  
  // Add regular banner fields
  const bannerFields = [
    'top_banner1', 'top_banner2', 'top_banner3',
    'new_arrival_b_banner1', 'new_arrival_b_banner2'
  ]
  
  bannerFields.forEach(field => {
    if (form[field]) {
      formData.append(field, form[field])
    }
    formData.append(field + '_url', form[field + '_url'] || '')
  })
  
  // Add random banners
  form.random_banners.forEach((randomBanner, index) => {
    if (randomBanner.image instanceof File) {
      formData.append(`random_banners[${index}][image]`, randomBanner.image)
    }
    formData.append(`random_banners[${index}][url]`, randomBanner.url || '')
  })

  form.post('/admin/banners', {
    data: formData,
    forceFormData: true,
    onSuccess: () => {
      // Clear previews and reset file inputs
      Object.keys(previews.value).forEach(key => {
        delete previews.value[key]
        if (refs[key]) {
          refs[key].value = null
        }
      })
      // Reset random banner file inputs
      form.random_banners.forEach((_, index) => {
        if (refs[`random_banner${index}`]) {
          refs[`random_banner${index}`].value = null
        }
      })
    },
    onFinish: () => {
      processing.value = false
    }
  })
}

// References to file inputs
const refs = ref({})
</script>

<style scoped>
.container {
  max-width: 1200px;
}
</style>
