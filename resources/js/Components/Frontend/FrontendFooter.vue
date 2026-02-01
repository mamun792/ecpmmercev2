<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    settings: {
        type: Object,
        default: () => ({})
    },
    pages: {
        type: Array,
        default: () => []
    }
});

const currentYear = computed(() => new Date().getFullYear());

// Support settings returned as { generalSettings, media, ... }
const general = computed(() => props.settings?.generalSettings || {});
const media = computed(() => props.settings?.media || {});

const siteName = computed(() => general.value?.app_name || general.value?.site_name || props.settings?.siteName || 'E-Shop');
const siteDescription = computed(() => general.value?.home_page_title || general.value?.site_description || 'Your trusted online shopping destination');

const footerLogo = computed(() => media.value?.footer_logo || media.value?.footerLogo || media.value?.logo || null);

// Social links, only show if present
const facebookUrl = computed(() => general.value?.facebook_url || null);
const instagramUrl = computed(() => general.value?.instagram_url || null);
const youtubeUrl = computed(() => general.value?.youtube_url || null);
const tiktokUrl = computed(() => general.value?.tiktok_url || null);
const xUrl = computed(() => general.value?.x_url || null);

// Contact fields
const storeEmail = computed(() => general.value?.store_email || general.value?.store_email || props.settings?.media?.store_email || '');
const storePhone = computed(() => general.value?.store_phone_number || general.value?.phone_number || '');
const storeAddress = computed(() => general.value?.address || '');

const facebookIframe = computed(() => general.value?.facebook_iframe || null);

const paymentMethodsImage = computed(() => media.value?.footer_payment_logo || media.value?.footer_payment_logo || null);

const pagesList = computed(() => props.pages || []);
const privacyPage = computed(() => pagesList.value.find(p => p.slug === 'privacy-policy' || p.slug === 'privacy'));
const termsPage = computed(() => pagesList.value.find(p => p.slug === 'terms' || p.slug === 'terms-of-service'));
</script>

<template>
    <footer class="bg-gray-900 text-gray-300">
        <!-- Main Footer -->
         <!-- <pre>{{ settings }}</pre> -->
        <div class="container mx-auto px-4 py-12">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                <!-- Company Info (Logo + Social Icons via API) -->
                <div>
                    <div class="mb-4">
                        <img v-if="footerLogo" :src="footerLogo" alt="Footer Logo" class="h-10 object-contain mb-3" />
                    </div>

                    <!-- Social Links (render only when URL is present) -->
                    <div class="flex items-center gap-3">
                        <a v-if="facebookUrl" :href="facebookUrl" target="_blank" rel="noopener noreferrer" class="w-10 h-10 rounded-full bg-gray-800 flex items-center justify-center hover:bg-primary transition-colors">
                            <!-- Facebook SVG -->
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.879v-6.99H7.898v-2.889h2.54V9.845c0-2.507 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.462h-1.26c-1.243 0-1.63.771-1.63 1.562v1.875h2.773l-.443 2.889h-2.33v6.99C18.343 21.128 22 16.991 22 12z"/>
                            </svg>
                        </a>

                        <a v-if="instagramUrl" :href="instagramUrl" target="_blank" rel="noopener noreferrer" class="w-10 h-10 rounded-full bg-gray-800 flex items-center justify-center hover:bg-primary transition-colors">
                            <!-- Instagram SVG -->
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" viewBox="0 0 24 24" fill="currentColor"><path d="M7 2C4.243 2 2 4.243 2 7v10c0 2.757 2.243 5 5 5h10c2.757 0 5-2.243 5-5V7c0-2.757-2.243-5-5-5H7zm5 6a4 4 0 110 8 4 4 0 010-8zm6.5-.5a1 1 0 110 2 1 1 0 010-2z"/></svg>
                        </a>

                        <a v-if="youtubeUrl" :href="youtubeUrl" target="_blank" rel="noopener noreferrer" class="w-10 h-10 rounded-full bg-gray-800 flex items-center justify-center hover:bg-primary transition-colors">
                            <!-- YouTube SVG -->
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" viewBox="0 0 24 24" fill="currentColor"><path d="M23.498 6.186a2.994 2.994 0 00-2.11-2.117C19.675 3.5 12 3.5 12 3.5s-7.675 0-9.388.569A2.994 2.994 0 00.5 6.186 31.25 31.25 0 000 12a31.25 31.25 0 00.5 5.814 2.994 2.994 0 002.112 2.117C4.325 20.5 12 20.5 12 20.5s7.675 0 9.388-.569a2.994 2.994 0 002.11-2.117A31.25 31.25 0 0024 12a31.25 31.25 0 00-.502-5.814zM9.75 15.02V8.98L15.5 12l-5.75 3.02z"/></svg>
                        </a>

                        <a v-if="tiktokUrl" :href="tiktokUrl" target="_blank" rel="noopener noreferrer" class="w-10 h-10 rounded-full bg-gray-800 flex items-center justify-center hover:bg-primary transition-colors">
                            <!-- TikTok SVG -->
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2v10a4 4 0 104 4V6h4V2h-8z"/></svg>
                        </a>

                        <a v-if="xUrl" :href="xUrl" target="_blank" rel="noopener noreferrer" class="w-10 h-10 rounded-full bg-gray-800 flex items-center justify-center hover:bg-primary transition-colors">
                            <!-- X (Twitter) SVG -->
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" viewBox="0 0 24 24" fill="currentColor"><path d="M23 3a10.9 10.9 0 01-3.14 1.53A4.48 4.48 0 0012 8v1A10.66 10.66 0 013 4s-4 9 5 13a11.64 11.64 0 01-7 2c9 5 20 0 20-11.5a4.5 4.5 0 00-.08-.83A7.72 7.72 0 0023 3z"/></svg>
                        </a>
                    </div>
                </div>

                <!-- Quick Links -->
                <div>
                    <h4 class="text-lg font-semibold text-white mb-4">Quick Links</h4>
                    <ul class="space-y-2">
                        <!-- Dynamic Site Pages -->
                        <li v-for="p in pagesList" :key="p.id">
                            <Link :href="`/page/${p.slug}`" class="hover:text-primary transition-colors">{{ p.name }}</Link>
                        </li>
                    </ul>
                </div>

                <!-- Contact Info -->
                <div>
                    <h4 class="text-lg font-semibold text-white mb-4">Contact</h4>
                    <ul class="space-y-3">
                        <li class="flex items-start gap-3 text-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mt-0.5 text-white shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <span>{{ storeAddress || 'Dhaka, Bangladesh' }}</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                            </svg>
                            <a v-if="storePhone" :href="`tel:${storePhone}`" class="hover:text-primary">{{ storePhone }}</a>
                        </li>
                        <li class="flex items-center gap-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            <a v-if="storeEmail" :href="`mailto:${storeEmail}`" class="hover:text-primary">{{ storeEmail }}</a>
                        </li>
                    </ul>
                </div>

                <!-- Facebook Iframe (only shows when provided) -->
                <div v-if="facebookIframe">
                    <h4 class="text-lg font-semibold text-white mb-4">Facebook</h4>
                    <div class="rounded overflow-hidden" v-html="facebookIframe"></div>
                </div>
            </div>
        </div>

        <!-- Payment Methods & Bottom Bar -->
        <div class="border-t border-gray-800">
            <div class="container mx-auto px-4 py-6">
                <div class="flex flex-col md:flex-row items-center justify-between gap-4">
                    <!-- Copyright -->
                    <p class="text-sm text-gray-500">
                        © {{ currentYear }} {{ siteName }}. All rights reserved. Developed by <a href="https://auxtechbd.com" target="_blank" class="underline" rel="noopener noreferrer">Auxtech</a>.
                    </p>

                    <!-- Payment Methods -->
                    <div v-if="paymentMethodsImage" class="flex flex-wrap items-center gap-4">
                        <span class="text-sm text-gray-500">We Accept:</span>
                        <div class="flex items-center gap-2">
                            <img :src="paymentMethodsImage" alt="Payment Methods" class="h-20 object-contain" v-if="paymentMethodsImage" />
                        </div>
                    </div>

                    <!-- Policy Links -->
                    <!-- <div class="flex items-center gap-4 text-sm">
                        <Link :href="privacyPage ? `/page/${privacyPage.slug}` : '/privacy-policy'" class="text-gray-500 hover:text-primary transition-colors">Privacy Policy</Link>
                        <Link :href="termsPage ? `/page/${termsPage.slug}` : '/terms'" class="text-gray-500 hover:text-primary transition-colors">Terms of Service</Link>
                    </div> -->
                </div>
            </div>
        </div>
    </footer>
</template>
