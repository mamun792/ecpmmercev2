<script setup>
import { Head, Link } from "@inertiajs/vue3";
import FrontendLayout from "@/Layouts/FrontendLayout.vue";
import Campaigns from "@/Components/Frontend/Product/Campaigns.vue";

defineOptions({
    layout: FrontendLayout,
});

const props = defineProps({
    activeCampaigns: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <Head title="Offers" />

    <section class="py-12 bg-gray-50 min-h-[60vh]">
        <div class="container mx-auto px-4">
            <div class="flex items-center justify-between mb-6">
                <h1 class="text-2xl font-bold text-gray-900">Offers</h1>
                <p class="text-sm text-gray-500">Latest active campaigns and deals</p>
            </div>

            <div v-if="activeCampaigns && activeCampaigns.length > 0">
                <Campaigns :campaigns="activeCampaigns" />
            </div>

            <div v-else class="bg-white rounded-lg shadow-sm border border-gray-100 p-8 text-center">
                <h2 class="text-xl font-semibold text-gray-900 mb-2">No active offers</h2>
                <p class="text-gray-500 mb-4">We don't have any active campaigns right now. Please check back later.</p>
                <Link href="/products" class="inline-block px-6 py-2 bg-primary text-white rounded-lg">Browse Products</Link>
            </div>
        </div>
    </section>
</template>
