<script setup>
import { Head } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import ProductForm from '@/Components/Product/ProductForm.vue';

defineProps({
  attributeValues: Array,
});
</script>

<template>
  <Head title="Create Product" />
  <AdminLayout>
    <ProductForm :attribute-values="attributeValues" />
  </AdminLayout>
</template>