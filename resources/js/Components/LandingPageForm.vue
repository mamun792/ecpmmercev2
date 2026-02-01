<template>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <form @submit.prevent="submitForm" class="space-y-8">
      <!-- Basic Information -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
          <h3 class="text-lg font-semibold text-gray-900">Basic Information</h3>
        </div>
        <div class="p-6 space-y-6">
          <div>
            <label for="title" class="block text-sm font-medium text-gray-700 mb-2">
              Title <span class="text-red-500">*</span>
            </label>
            <input
              v-model="form.title"
              type="text"
              id="title"
              required
              @input="generateSlug"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition"
              placeholder="Enter landing page title"
            />
            <span v-if="errors.title" class="text-sm text-red-600 mt-1 block">{{ errors.title }}</span>
          </div>

          <div>
            <label for="slug" class="block text-sm font-medium text-gray-700 mb-2">Slug</label>
            <input
              v-model="form.slug"
              type="text"
              id="slug"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition"
              placeholder="auto-generated-slug"
            />
            <span v-if="errors.slug" class="text-sm text-red-600 mt-1 block">{{ errors.slug }}</span>
          </div>

          <div>
            <label for="button_text" class="block text-sm font-medium text-gray-700 mb-2">Button Text</label>
            <input
              v-model="form.button_text"
              type="text"
              id="button_text"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition"
              placeholder="Shop Now"
            />
            <span v-if="errors.button_text" class="text-sm text-red-600 mt-1 block">{{ errors.button_text }}</span>
          </div>
        </div>
      </div>

      <!-- Hero Section -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
          <h3 class="text-lg font-semibold text-gray-900">Hero Section</h3>
        </div>
        <div class="p-6 space-y-6">
          <div>
            <label for="hero_image" class="block text-sm font-medium text-gray-700 mb-2">Hero Image</label>
            <div class="flex items-center space-x-4">
              <label class="flex-1 cursor-pointer">
                <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-indigo-500 transition">
                  <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                    <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                  </svg>
                  <p class="mt-2 text-sm text-gray-600">Click to upload hero image</p>
                </div>
                <input
                  type="file"
                  id="hero_image"
                  accept="image/*"
                  @change="handleFileUpload($event, 'hero_image')"
                  class="hidden"
                />
              </label>
            </div>
            <div v-if="previewImages.hero_image" class="mt-4 relative inline-block">
              <img :src="previewImages.hero_image" alt="Hero Preview" class="h-48 w-auto rounded-lg border border-gray-300" />
              <button
                type="button"
                @click="removeImage('hero_image')"
                class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full p-2 hover:bg-red-600 transition shadow-lg"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
            <span v-if="errors.hero_image" class="text-sm text-red-600 mt-1 block">{{ errors.hero_image }}</span>
          </div>

          <div>
            <label for="youtube_url" class="block text-sm font-medium text-gray-700 mb-2">YouTube URL</label>
            <input
              v-model="form.youtube_url"
              type="url"
              id="youtube_url"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition"
              placeholder="https://www.youtube.com/watch?v=..."
            />
            <span v-if="errors.youtube_url" class="text-sm text-red-600 mt-1 block">{{ errors.youtube_url }}</span>
          </div>

          <div>
            <label for="upload_video" class="block text-sm font-medium text-gray-700 mb-2">Upload Video</label>
            <input
              type="file"
              id="upload_video"
              accept="video/*"
              @change="handleFileUpload($event, 'upload_video')"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 file:cursor-pointer"
            />
            <span v-if="form.upload_video" class="text-sm text-gray-600 mt-2 block">
              {{ form.upload_video.name }}
            </span>
            <span v-if="errors.upload_video" class="text-sm text-red-600 mt-1 block">{{ errors.upload_video }}</span>
          </div>
        </div>
      </div>

      <!-- Product Features -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
          <h3 class="text-lg font-semibold text-gray-900">Product Features</h3>
        </div>
        <div class="p-6 space-y-6">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-3">Features List</label>
            <div class="space-y-3">
              <div
                v-for="(feature, index) in form.product_features_list"
                :key="index"
                class="flex gap-3"
              >
                <input
                  v-model="form.product_features_list[index]"
                  type="text"
                  class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition"
                  placeholder="Enter feature"
                />
                <button
                  type="button"
                  @click="removeFeature(index)"
                  class="px-4 py-2 bg-red-50 text-red-600 rounded-lg hover:bg-red-100 transition font-medium"
                >
                  Remove
                </button>
              </div>
            </div>
            <span v-if="errors.product_features_list" class="text-sm text-red-600 mt-1 block">{{ errors.product_features_list }}</span>
            <button
              type="button"
              @click="addFeature"
              class="mt-3 px-4 py-2 bg-indigo-50 text-indigo-700 rounded-lg hover:bg-indigo-100 transition font-medium"
            >
              + Add Feature
            </button>
          </div>

          <div>
            <label for="feature_image" class="block text-sm font-medium text-gray-700 mb-2">Feature Image</label>
            <div class="flex items-center space-x-4">
              <label class="flex-1 cursor-pointer">
                <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-indigo-500 transition">
                  <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                    <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                  </svg>
                  <p class="mt-2 text-sm text-gray-600">Click to upload feature image</p>
                </div>
                <input
                  type="file"
                  id="feature_image"
                  accept="image/*"
                  @change="handleFileUpload($event, 'feature_image')"
                  class="hidden"
                />
              </label>
            </div>
            <div v-if="previewImages.feature_image" class="mt-4 relative inline-block">
              <img :src="previewImages.feature_image" alt="Feature Preview" class="h-48 w-auto rounded-lg border border-gray-300" />
              <button
                type="button"
                @click="removeImage('feature_image')"
                class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full p-2 hover:bg-red-600 transition shadow-lg"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
            <span v-if="errors.feature_image" class="text-sm text-red-600 mt-1 block">{{ errors.feature_image }}</span>
          </div>

          <div>
            <label for="feature_button_text" class="block text-sm font-medium text-gray-700 mb-2">Feature Button Text</label>
            <input
              v-model="form.feature_button_text"
              type="text"
              id="feature_button_text"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition"
              placeholder="Learn More"
            />
            <span v-if="errors.feature_button_text" class="text-sm text-red-600 mt-1 block">{{ errors.feature_button_text }}</span>
          </div>
        </div>
      </div>

      <!-- CTA Section -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
          <h3 class="text-lg font-semibold text-gray-900">Call to Action</h3>
        </div>
        <div class="p-6 space-y-6">
          <div>
            <label for="cta_title" class="block text-sm font-medium text-gray-700 mb-2">CTA Title</label>
            <input
              v-model="form.cta_title"
              type="text"
              id="cta_title"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition"
              placeholder="Ready to get started?"
            />
            <span v-if="errors.cta_title" class="text-sm text-red-600 mt-1 block">{{ errors.cta_title }}</span>
          </div>

          <div>
            <label for="cta_short_description" class="block text-sm font-medium text-gray-700 mb-2">CTA Description</label>
            <textarea
              v-model="form.cta_short_description"
              id="cta_short_description"
              rows="3"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition resize-none"
              placeholder="Join thousands of satisfied customers..."
            ></textarea>
            <span v-if="errors.cta_short_description" class="text-sm text-red-600 mt-1 block">{{ errors.cta_short_description }}</span>
          </div>

          <div>
            <label for="cta_button_text" class="block text-sm font-medium text-gray-700 mb-2">CTA Button Text</label>
            <input
              v-model="form.cta_button_text"
              type="text"
              id="cta_button_text"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition"
              placeholder="Get Started Now"
            />
            <span v-if="errors.cta_button_text" class="text-sm text-red-600 mt-1 block">{{ errors.cta_button_text }}</span>
          </div>
        </div>
      </div>

      <!-- Product Gallery -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
          <h3 class="text-lg font-semibold text-gray-900">Product Gallery</h3>
        </div>
        <div class="p-6">
          <label class="block text-sm font-medium text-gray-700 mb-3">Gallery Images</label>
          <input
            type="file"
            accept="image/*"
            multiple
            @change="handleMultipleFileUpload($event, 'product_gallery')"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 file:cursor-pointer"
          />
          <div v-if="previewImages.product_gallery.length" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 mt-4">
            <div
              v-for="(image, index) in previewImages.product_gallery"
              :key="index"
              class="relative group"
            >
              <img :src="image" alt="Gallery" class="w-full h-32 object-cover rounded-lg border border-gray-300" />
              <button
                type="button"
                @click="removeGalleryImage(index)"
                class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full p-1.5 opacity-0 group-hover:opacity-100 transition shadow-lg"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
          </div>
          <span v-if="errors.product_gallery" class="text-sm text-red-600 mt-1 block">{{ errors.product_gallery }}</span>
        </div>
      </div>

      <!-- FAQs -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
          <h3 class="text-lg font-semibold text-gray-900">FAQs</h3>
        </div>
        <div class="p-6 space-y-4">
          <div
            v-for="(faq, index) in form.faqs"
            :key="index"
            class="p-4 bg-gray-50 rounded-lg border border-gray-200 space-y-3"
          >
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Question</label>
              <input
                v-model="faq.question"
                type="text"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition"
                placeholder="Enter question"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Answer</label>
              <textarea
                v-model="faq.answer"
                rows="2"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition resize-none"
                placeholder="Enter answer"
              ></textarea>
            </div>
            <button
              type="button"
              @click="removeFaq(index)"
              class="px-4 py-2 bg-red-50 text-red-600 rounded-lg hover:bg-red-100 transition font-medium text-sm"
            >
              Remove FAQ
            </button>
          </div>
          <span v-if="errors.faqs" class="text-sm text-red-600 mt-1 block">{{ errors.faqs }}</span>
          <button
            type="button"
            @click="addFaq"
            class="px-4 py-2 bg-indigo-50 text-indigo-700 rounded-lg hover:bg-indigo-100 transition font-medium"
          >
            + Add FAQ
          </button>
        </div>
      </div>

      <!-- Review Images -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
          <h3 class="text-lg font-semibold text-gray-900">Review Images</h3>
        </div>
        <div class="p-6">
          <label class="block text-sm font-medium text-gray-700 mb-3">Review Screenshots</label>
          <input
            type="file"
            accept="image/*"
            multiple
            @change="handleMultipleFileUpload($event, 'review_images')"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 file:cursor-pointer"
          />
          <div v-if="previewImages.review_images.length" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 mt-4">
            <div
              v-for="(image, index) in previewImages.review_images"
              :key="index"
              class="relative group"
            >
              <img :src="image" alt="Review" class="w-full h-32 object-cover rounded-lg border border-gray-300" />
              <button
                type="button"
                @click="removeReviewImage(index)"
                class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full p-1.5 opacity-0 group-hover:opacity-100 transition shadow-lg"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
          </div>
          <span v-if="errors.review_images" class="text-sm text-red-600 mt-1 block">{{ errors.review_images }}</span>
        </div>
      </div>

      <!-- Settings -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
          <h3 class="text-lg font-semibold text-gray-900">Settings</h3>
        </div>
        <div class="p-6 space-y-6">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label for="primary_color" class="block text-sm font-medium text-gray-700 mb-2">Primary Color</label>
              <div class="flex items-center gap-3">
                <input
                  v-model="form.primary_color"
                  type="color"
                  id="primary_color"
                  class="h-10 w-20 border border-gray-300 rounded-lg cursor-pointer"
                />
                <input
                  v-model="form.primary_color"
                  type="text"
                  class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition"
                />
              </div>
              <span v-if="errors.primary_color" class="text-sm text-red-600 mt-1 block">{{ errors.primary_color }}</span>
            </div>

            <div>
              <label for="secondary_color" class="block text-sm font-medium text-gray-700 mb-2">Secondary Color</label>
              <div class="flex items-center gap-3">
                <input
                  v-model="form.secondary_color"
                  type="color"
                  id="secondary_color"
                  class="h-10 w-20 border border-gray-300 rounded-lg cursor-pointer"
                />
                <input
                  v-model="form.secondary_color"
                  type="text"
                  class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition"
                />
              </div>
              <span v-if="errors.secondary_color" class="text-sm text-red-600 mt-1 block">{{ errors.secondary_color }}</span>
            </div>
          </div>

          <div class="space-y-3">
            <label class="flex items-center gap-3 cursor-pointer group">
              <input
                v-model="form.is_enabled_faq"
                type="checkbox"
                class="w-5 h-5 text-indigo-600 border-gray-300 rounded focus:ring-2 focus:ring-indigo-500 cursor-pointer"
              />
              <span class="text-sm font-medium text-gray-700 group-hover:text-gray-900">Enable FAQ Section</span>
            </label>

            <label class="flex items-center gap-3 cursor-pointer group">
              <input
                v-model="form.is_enabled_review"
                type="checkbox"
                class="w-5 h-5 text-indigo-600 border-gray-300 rounded focus:ring-2 focus:ring-indigo-500 cursor-pointer"
              />
              <span class="text-sm font-medium text-gray-700 group-hover:text-gray-900">Enable Review Section</span>
            </label>

            <label class="flex items-center gap-3 cursor-pointer group">
              <input
                v-model="form.is_enabled_cta"
                type="checkbox"
                class="w-5 h-5 text-indigo-600 border-gray-300 rounded focus:ring-2 focus:ring-indigo-500 cursor-pointer"
              />
              <span class="text-sm font-medium text-gray-700 group-hover:text-gray-900">Enable CTA Section</span>
            </label>
          </div>
        </div>
      </div>

      <!-- Products Selection -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
          <h3 class="text-lg font-semibold text-gray-900">Linked Products</h3>
        </div>
        <div class="p-6">
          <label class="block text-sm font-medium text-gray-700 mb-3">Select Products</label>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <label
              v-for="product in products"
              :key="product.id"
              class="relative flex items-center p-4 border-2 rounded-lg cursor-pointer transition"
              :class="form.products.includes(product.id) ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200 hover:border-gray-300'"
            >
              <input
                type="checkbox"
                :value="product.id"
                v-model="form.products"
                class="sr-only"
              />
              <div class="flex items-center gap-3 flex-1">
                <img
                  v-if="product.feature_image"
                  :src="`/storage/${product.feature_image}`"
                  :alt="product.name"
                  class="w-12 h-12 object-cover rounded-lg"
                />
                <div class="flex-1">
                  <p class="font-medium text-gray-900 text-sm">{{ product.name }}</p>
                </div>
                <div
                  v-if="form.products.includes(product.id)"
                  class="flex-shrink-0 text-indigo-600"
                >
                  <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                  </svg>
                </div>
              </div>
            </label>
          </div>
          <span v-if="errors.products" class="text-sm text-red-600 mt-1 block">{{ errors.products }}</span>
        </div>
      </div>

      <!-- Form Actions -->
      <div class="flex items-center justify-end gap-4 pt-6 border-t border-gray-200">
        <button
          type="button"
          @click="cancel"
          class="px-6 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition"
        >
          Cancel
        </button>
        <button
          type="submit"
          :disabled="processing"
          class="px-6 py-2.5 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition disabled:bg-gray-400 disabled:cursor-not-allowed"
        >
          {{ processing ? 'Saving...' : (isEdit ? 'Update Landing Page' : 'Create Landing Page') }}
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
  landingPage: {
    type: Object,
    default: null,
  },
  products: {
    type: Array,
    required: true,
  },
});

const form = ref({
  title: '',
  slug: '',
  button_text: '',
  hero_image: null,
  youtube_url: '',
  upload_video: null,
  product_features_list: [],
  feature_image: null,
  feature_button_text: '',
  cta_title: '',
  cta_short_description: '',
  cta_button_text: '',
  product_gallery: [],
  faqs: [],
  review_images: [],
  primary_color: '#4f46e5',
  secondary_color: '#10b981',
  is_enabled_faq: false,
  is_enabled_review: false,
  is_enabled_cta: false,
  products: [],
  deleted_gallery_images: [],
  deleted_review_images: [],
});

const previewImages = ref({
  hero_image: null,
  feature_image: null,
  product_gallery: [],
  review_images: [],
});

const errors = ref({});
const processing = ref(false);

const isEdit = computed(() => !!props.landingPage);

onMounted(() => {
  if (props.landingPage) {
    populateForm();
  }
});

const populateForm = () => {
  const lp = props.landingPage;
  
  form.value.title = lp.title || '';
  form.value.slug = lp.slug || '';
  form.value.button_text = lp.button_text || '';
  form.value.youtube_url = lp.youtube_url || '';
  form.value.feature_button_text = lp.feature_button_text || '';
  form.value.cta_title = lp.cta_title || '';
  form.value.cta_short_description = lp.cta_short_description || '';
  form.value.cta_button_text = lp.cta_button_text || '';

  form.value.product_features_list = lp.product_features_list 
    ? Array.isArray(lp.product_features_list) 
      ? lp.product_features_list 
      : JSON.parse(lp.product_features_list) 
    : [];
  
  form.value.faqs = lp.faqs 
    ? Array.isArray(lp.faqs) 
      ? lp.faqs 
      : JSON.parse(lp.faqs) 
    : [];

  if (lp.hero_image) {
    previewImages.value.hero_image = `/storage/${lp.hero_image}`;
  }
  if (lp.feature_image) {
    previewImages.value.feature_image = `/storage/${lp.feature_image}`;
  }
  if (lp.product_gallery) {
    previewImages.value.product_gallery = lp.product_gallery.map(img => `/storage/${img}`);
  }
  if (lp.review_images) {
    previewImages.value.review_images = lp.review_images.map(img => `/storage/${img}`);
  }

  if (lp.settings) {
    form.value.primary_color = lp.settings.primary_color || '#4f46e5';
    form.value.secondary_color = lp.settings.secondary_color || '#10b981';
    form.value.is_enabled_faq = lp.settings.is_enabled_faq || false;
    form.value.is_enabled_review = lp.settings.is_enabled_review || false;
    form.value.is_enabled_cta = lp.settings.is_enabled_cta || false;
  }

  if (lp.products) {
    form.value.products = lp.products.map((product) => product.id);
  }
};

const generateSlug = () => {
  form.value.slug = form.value.title
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/(^-|-$)/g, '');
};

const handleFileUpload = (event, field) => {
  const file = event.target.files[0];
  if (!file) return;

  form.value[field] = file;
  previewImages.value[field] = URL.createObjectURL(file);
};

const removeImage = (field) => {
  form.value[field] = null;
  previewImages.value[field] = null;
};

const addFeature = () => {
  form.value.product_features_list.push('');
};

const removeFeature = (index) => {
  form.value.product_features_list.splice(index, 1);
};

const handleMultipleFileUpload = (event, field) => {
  const files = Array.from(event.target.files);
  if (!files.length) return;

  form.value[field] = [...(form.value[field] || []), ...files];
  previewImages.value[field] = [
    ...previewImages.value[field],
    ...files.map(file => URL.createObjectURL(file)),
  ];
};

const removeGalleryImage = (index) => {
  if (isEdit.value && previewImages.value.product_gallery[index]?.startsWith('/storage/')) {
    form.value.deleted_gallery_images.push(previewImages.value.product_gallery[index].replace('/storage/', ''));
  }
  form.value.product_gallery.splice(index, 1);
  previewImages.value.product_gallery.splice(index, 1);
};

const removeReviewImage = (index) => {
  if (isEdit.value && previewImages.value.review_images[index]?.startsWith('/storage/')) {
    form.value.deleted_review_images.push(previewImages.value.review_images[index].replace('/storage/', ''));
  }
  form.value.review_images.splice(index, 1);
  previewImages.value.review_images.splice(index, 1);
};

const addFaq = () => {
  form.value.faqs.push({ question: '', answer: '' });
};

const removeFaq = (index) => {
  form.value.faqs.splice(index, 1);
};

const submitForm = () => {
  processing.value = true;
  const routeName = isEdit.value ? 'admin.landing-pages.update' : 'admin.landing-pages.store';
  const data = new FormData();

  Object.keys(form.value).forEach((key) => {
    if (key === 'product_features_list') {
      form.value.product_features_list.forEach((feature, index) => {
        data.append(`product_features_list[${index}]`, feature);
      });
    } else if (key === 'faqs') {
      form.value.faqs.forEach((faq, index) => {
        data.append(`faqs[${index}][question]`, faq.question || '');
        data.append(`faqs[${index}][answer]`, faq.answer || '');
      });
    } else if (key === 'product_gallery' || key === 'review_images') {
      form.value[key].forEach((file, index) => {
        if (file instanceof File) {
          data.append(`${key}[${index}]`, file);
        }
      });
    } else if (key === 'hero_image' || key === 'feature_image' || key === 'upload_video') {
      if (form.value[key] instanceof File) {
        data.append(key, form.value[key]);
      }
    } else if (key === 'products' || key === 'deleted_gallery_images' || key === 'deleted_review_images') {
      form.value[key].forEach((item, index) => {
        data.append(`${key}[${index}]`, item);
      });
    } else {
      data.append(key, form.value[key] || '');
    }
  });

  const requestMethod = isEdit.value ? router.put : router.post;

  requestMethod(route(routeName, isEdit.value ? props.landingPage.id : null), data, {
    forceFormData: true, // No need for manual CSRF token; Inertia handles it
    onSuccess: () => {
      processing.value = false;
      router.get(route('admin.landing-pages.index')); // Use get for navigation
    },
    onError: (err) => {
      errors.value = err;
      processing.value = false;
      alert('Failed to save the landing page. Please check the form for errors.');
    },
  });
};

const cancel = () => {
  router.get(route('admin.landing-pages.index'));
};
</script>