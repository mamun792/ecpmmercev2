<script setup>
import { defineProps } from 'vue';
import { Head } from '@inertiajs/vue3';
import FrontendLayout from '@/Layouts/FrontendLayout.vue';

const props = defineProps({
  page: {
    type: Object,
    required: true,
  },
});
</script>

<template>
  <FrontendLayout>
    <Head :title="props.page.title || props.page.name" />

    <div class="min-h-screen bg-gray-50/30 font-sans pb-12">
      <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <h1 class="text-2xl font-semibold mb-4">
          {{ props.page.title || props.page.name }}
        </h1>

        <div class="prose max-w-none" v-html="props.page.content"></div>
      </div>
    </div>
  </FrontendLayout>
</template>
