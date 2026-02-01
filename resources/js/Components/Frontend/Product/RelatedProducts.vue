<script setup>
import { defineProps } from "vue";
import { Link } from "@inertiajs/vue3";
import { Swiper, SwiperSlide } from "swiper/vue";
import { Navigation, Autoplay } from "swiper/modules";
import ProductCard from "@/Components/Frontend/Product/ProductCard.vue";

// Import Swiper styles
import "swiper/css";
import "swiper/css/navigation";

const props = defineProps({
    products: {
        type: Array,
        required: true,
    },
});

const modules = [Navigation, Autoplay];
</script>

<template>
    <div
        v-if="products && products.length"
        class="mt-16 border-t border-gray-200 pt-16"
    >
        <div class="flex items-center justify-between mb-8">
            <h3 class="text-2xl font-bold text-gray-900">Similar Products</h3>

            <!-- Custom Navigation Buttons -->
            <div class="flex gap-2">
                <button
                    class="related-prev flex items-center justify-center w-10 h-10 rounded-full border border-gray-200 bg-white text-gray-600 hover:bg-primary hover:text-white hover:border-primary transition-all shadow-sm"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        width="20"
                        height="20"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <path d="m15 18-6-6 6-6" />
                    </svg>
                </button>
                <button
                    class="related-next flex items-center justify-center w-10 h-10 rounded-full border border-gray-200 bg-white text-gray-600 hover:bg-primary hover:text-white hover:border-primary transition-all shadow-sm"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        width="20"
                        height="20"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <path d="m9 18 6-6-6-6" />
                    </svg>
                </button>
            </div>
        </div>

        <Swiper
            :modules="modules"
            :slides-per-view="1"
            :space-between="20"
            :navigation="{
                prevEl: '.related-prev',
                nextEl: '.related-next',
            }"
            :autoplay="{
                delay: 5000,
                disableOnInteraction: false,
            }"
            :breakpoints="{
                640: {
                    slidesPerView: 2,
                },
                768: {
                    slidesPerView: 3,
                },
                1024: {
                    slidesPerView: 4,
                },
            }"
            class="related-products-swiper !pb-10"
        >
            <SwiperSlide
                v-for="product in products"
                :key="product.id"
                class="h-auto"
            >
                <ProductCard :product="product" class="h-full" />
            </SwiperSlide>
        </Swiper>
    </div>
</template>

<style scoped>
.related-products-swiper {
    padding-left: 1px; /* Prevent shadow clipping on left */
    padding-right: 1px; /* Prevent shadow clipping on right */
}
</style>
