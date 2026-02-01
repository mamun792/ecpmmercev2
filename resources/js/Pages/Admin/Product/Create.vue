<script setup>
import { Head } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import ProductForm from '@/Components/Product/ProductForm.vue';

// Professional props definition with proper data structure
defineProps({
  categories: {
    type: Array,
    default: () => []
  },
  brands: {
    type: Array,
    default: () => []
  },
  attributes: {
    type: Array,
    default: () => []
  },
  locations: {
    type: Array,
    default: () => []
  },
  errors: {
    type: Object,
    default: () => ({})
  }
});
</script>

<template>
  <Head title="Create Product | Professional E-commerce Management" />
  <AdminLayout>
    <div class="container mx-auto px-4 py-6">
      <!-- Professional Header -->
      <div class="mb-6">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">Create New Product</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">Add a new product to your catalog with professional inventory management</p>
          </div>
          <div class="text-sm text-gray-500 dark:text-gray-400 bg-blue-50 dark:bg-blue-900/20 px-3 py-1 rounded-full">
            Big Tech Architecture ⚡
          </div>
        </div>
      </div>

      <!-- Professional Product Form -->
      <ProductForm
        :categories="categories"
        :brands="brands"
        :attributes="attributes"
        :locations="locations"
        :errors="errors"
      />
    </div>
  </AdminLayout>
</template>
