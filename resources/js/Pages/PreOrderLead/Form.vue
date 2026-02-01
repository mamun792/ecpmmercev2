<template>
  <FrontendLayout>
    <Head title="Pre-Order Lead" />
    <div class="min-h-screen py-12 px-4 sm:px-6 lg:px-8">
      <div class="max-w-3xl mx-auto">
        <h1 class="text-center text-4xl font-extrabold tracking-tight text-gray-900 mb-6">
          Looking for Something Different?
        </h1>
        <p class="text-center text-sm text-gray-600 mb-8">
          Tell us what you're looking for and we'll notify you when it's available.
        </p>

        <div class="bg-white rounded-lg shadow-md p-6">
          <form @submit.prevent="submitForm" class="space-y-6">
            <!-- Product Information -->
            <div>
              <label for="product_interest" class="block text-sm font-medium text-gray-700">
                Product Information <span class="text-red-500">*</span>
              </label>
              <input
                id="product_interest"
                v-model="form.product_interest"
                type="text"
                required
                placeholder="Enter product name/URL"
                class="mt-1 block w-full px-4 py-3 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary"
                :class="{ 'border-red-500': errors.product_interest }"
              />
              <p v-if="errors.product_interest" class="mt-1 text-sm text-red-600">{{ errors.product_interest }}</p>
            </div>

            <!-- Your Name -->
            <div>
              <label for="name" class="block text-sm font-medium text-gray-700">
                Your Name <span class="text-red-500">*</span>
              </label>
              <input
                id="name"
                v-model="form.name"
                type="text"
                required
                placeholder="Enter your name"
                class="mt-1 block w-full px-4 py-3 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary"
                :class="{ 'border-red-500': errors.name }"
              />
              <p v-if="errors.name" class="mt-1 text-sm text-red-600">{{ errors.name }}</p>
            </div>

            <!-- Email & Phone -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label for="email" class="block text-sm font-medium text-gray-700">Email <span class="text-red-500">*</span></label>
                <input
                  id="email"
                  v-model="form.email"
                  type="email"
                  required
                  placeholder="Enter Email"
                  class="mt-1 block w-full px-4 py-3 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary"
                  :class="{ 'border-red-500': errors.email }"
                />
                <p v-if="errors.email" class="mt-1 text-sm text-red-600">{{ errors.email }}</p>
              </div>

              <div>
                <label for="phone" class="block text-sm font-medium text-gray-700">Phone Number <span class="text-red-500">*</span></label>
                <input
                  id="phone"
                  v-model="form.phone"
                  type="tel"
                  required
                  placeholder="Enter Phone Number"
                  class="mt-1 block w-full px-4 py-3 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary"
                  :class="{ 'border-red-500': errors.phone }"
                />
                <p v-if="errors.phone" class="mt-1 text-sm text-red-600">{{ errors.phone }}</p>
              </div>
            </div>

            <!-- Address -->
            <div>
              <label for="address" class="block text-sm font-medium text-gray-700">Address</label>
              <input
                id="address"
                v-model="form.address"
                type="text"
                placeholder="Enter address"
                class="mt-1 block w-full px-4 py-3 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary"
                :class="{ 'border-red-500': errors.address }"
              />
              <p v-if="errors.address" class="mt-1 text-sm text-red-600">{{ errors.address }}</p>
            </div>

            <!-- Message -->
            <div>
              <label for="message" class="block text-sm font-medium text-gray-700">Message</label>
              <textarea
                id="message"
                v-model="form.message"
                rows="4"
                placeholder="Enter message"
                class="mt-1 block w-full px-4 py-3 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary"
                :class="{ 'border-red-500': errors.message }"
              ></textarea>
              <p v-if="errors.message" class="mt-1 text-sm text-red-600">{{ errors.message }}</p>
            </div>

            <!-- Upload Product Image -->
            <div>
              <label class="block text-sm font-medium text-gray-700">Upload Product Image <span class="text-red-500">*</span></label>

              <div
                class="mt-2"
                :class="[isDragActive ? 'opacity-80 border-primary ring-2 ring-offset-2' : '']"
              >
                <div
                  @click="triggerFilePicker"
                  @dragover.prevent="onDragOver"
                  @dragleave.prevent="onDragLeave"
                  @drop.prevent="handleDrop"
                  class="flex items-center justify-center h-36 border-2 border-dashed border-gray-300 rounded-lg bg-gray-50 cursor-pointer"
                >
                  <div class="text-center">
                    <div class="w-10 h-10 mx-auto mb-2 rounded-full bg-white flex items-center justify-center border border-gray-200">
                      <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 9l5-5 5 5M12 4v12"></path></svg>
                    </div>
                    <p class="text-sm text-gray-600"><span class="text-orange-500 font-semibold">Click to upload</span> or drag and drop</p>
                    <p class="text-xs text-gray-400 mt-1">PNG, JPG up to 5MB</p>
                  </div>
                </div>

                <input
                  ref="imageInput"
                  type="file"
                  accept="image/*"
                  @change="handleImageUpload"
                  class="hidden"
                />

                <p v-if="errors.image" class="mt-2 text-sm text-red-600">{{ errors.image }}</p>

                <!-- Preview -->
                <div v-if="imagePreview" class="mt-4">
                  <img :src="imagePreview" alt="Preview" class="max-w-full h-48 object-cover rounded-md" />
                </div>
              </div>
            </div>

            <!-- Terms -->
            <div class="flex items-start">
              <div class="flex items-center h-5">
                <input id="terms" type="checkbox" v-model="form.terms" class="w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary" />
              </div>
              <div class="ml-3 text-sm">
                <label for="terms" class="font-medium text-gray-700">I hereby accept the terms and conditions of pre-order and read the <Link href="/page/pre-order-policy" class="text-primary underline">pre-order terms and conditions</Link> carefully.</label>
                <p v-if="errors.terms" class="mt-1 text-sm text-red-600">{{ errors.terms }}</p>
              </div>
            </div>

            <!-- Submit -->
            <div>
              <button
                type="submit"
                :disabled="loading"
                class="w-full py-3 rounded-full text-sm font-medium text-white bg-orange-300 hover:bg-orange-400 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <span v-if="loading">Submitting...</span>
                <span v-else>Submit</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </FrontendLayout>
</template>

<script setup>
import FrontendLayout from '@/Layouts/FrontendLayout.vue'
import { ref, reactive } from 'vue'
import { router, Link, Head } from '@inertiajs/vue3'
import { useToast } from '@/Composables/useToast'

const { success, error } = useToast()

const form = reactive({
  name: '',
  email: '',
  phone: '',
  product_interest: '',
  address: '',
  message: '',
  image: null,
  terms: false,
})

const errors = ref({})
const loading = ref(false)
const imagePreview = ref(null)
const imageInput = ref(null)
const isDragActive = ref(false)

const onDragOver = () => {
  isDragActive.value = true
}

const onDragLeave = () => {
  isDragActive.value = false
}

const handleDrop = (event) => {
  isDragActive.value = false
  const file = event.dataTransfer?.files?.[0]
  if (file) {
    setImage(file)
  }
}

const triggerFilePicker = () => {
  if (imageInput.value) imageInput.value.click()
}

const setImage = (file) => {
  form.image = file
  const reader = new FileReader()
  reader.onload = (e) => {
    imagePreview.value = e.target.result
  }
  reader.readAsDataURL(file)
}

const handleImageUpload = (event) => {
  const file = event.target?.files ? event.target.files[0] : null
  if (file) {
    setImage(file)
  }
}

const submitForm = () => {
  loading.value = true
  errors.value = {}

  // Basic client-side check for terms
  if (!form.terms) {
    errors.value.terms = 'You must accept the terms and conditions.'
    loading.value = false
    return
  }

  const formData = new FormData()
  formData.append('name', form.name)
  formData.append('email', form.email)
  formData.append('phone', form.phone)
  formData.append('product_interest', form.product_interest)
  formData.append('address', form.address)
  formData.append('message', form.message)
  if (form.image) {
    formData.append('image', form.image)
  }

  router.post('/pre-order-lead', formData, {
    onSuccess: (page) => {
      success(page.props.flash?.success || 'Your pre-order lead has been submitted successfully!')
      // Reset form
      form.name = ''
      form.email = ''
      form.phone = ''
      form.product_interest = ''
      form.address = ''
      form.message = ''
      form.image = null
      form.terms = false
      imagePreview.value = null
      if (imageInput.value) {
        imageInput.value.value = ''
      }
    },
    onError: (err) => {
      errors.value = err
      error('Failed to submit lead. Please check the form and try again.')
    },
    onFinish: () => {
      loading.value = false
    }
  })
}
</script>