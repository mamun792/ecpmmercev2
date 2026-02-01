<script setup>
import FrontendHeader from '@/Components/Frontend/FrontendHeader.vue';
import { Toaster } from '@steveyuowo/vue-hot-toast';

</script>

<template>

    <div class="guestLayout"  >
        <FrontendHeader />
     <slot />
    <Toaster />
    </div>
</template>
