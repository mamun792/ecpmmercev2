<template>
  <nav v-if="links && links.length" aria-label="Pagination">
    <ul class="inline-flex items-center space-x-1">
      <li v-for="(link, idx) in links" :key="idx">
        <Link
          :href="link.url ?? '#'
          "
          preserve-scroll
          class="px-3 py-1 border text-sm rounded"
          :class="[
            link.active ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 hover:bg-gray-100 border-gray-200',
            !link.url ? 'cursor-default opacity-60 pointer-events-none' : ''
          ]"
          v-html="link.label"
          :aria-current="link.active ? 'page' : null"
        />
      </li>
    </ul>
  </nav>
</template>

<script setup>
import { defineProps } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
  meta: { type: Object, default: () => ({}) },
  links: { type: Array, default: () => [] },
});

// expose props to template
const links = props.links;
</script>
