<template>
    <div class="container mx-auto md:mt-4 mt-0 overflow-hidden">
        <swiper
            v-if="sliders && sliders.length > 0"
            :modules="modules"
            :slides-per-view="1"
            :loop="sliders.length > 1"
            :autoplay="{
                delay: 5000,
                disableOnInteraction: false,
                pauseOnMouseEnter: true,
            }"
            :pagination="{
                clickable: true,
                dynamicBullets: true,
            }"
            :navigation="true"
            class="main-slider"
        >
            <swiper-slide v-for="slider in sliders" :key="slider.id">
                <Link
                    v-if="slider.Link"
                    :href="slider.Link"
                    class="block w-full outline-none"
                >
                    <picture>
                        <source
                            media="(max-width: 640px)"
                            :srcset="slider.mobile_image || slider.image"
                        />
                        <img
                            :src="slider.image"
                            :alt="'Slide ' + slider.id"
                            class="w-full h-auto object-cover block"
                        />
                    </picture>
                </Link>
                <div v-else class="w-full outline-none">
                    <picture>
                        <source
                            media="(max-width: 640px)"
                            :srcset="slider.mobile_image || slider.image"
                        />
                        <img
                            :src="slider.image"
                            :alt="'Slide ' + slider.id"
                            class="w-full h-auto object-cover block"
                        />
                    </picture>
                </div>
            </swiper-slide>
        </swiper>
    </div>
</template>

<script setup>
import { Swiper, SwiperSlide } from "swiper/vue";
import { Autoplay, Pagination, Navigation } from "swiper/modules";
import { Link } from "@inertiajs/vue3";

// Import Swiper styles
import "swiper/css";
import "swiper/css/pagination";
import "swiper/css/navigation";

const props = defineProps({
    sliders: {
        type: Array,
        required: true,
        default: () => [],
    },
});

const modules = [Autoplay, Pagination, Navigation];
</script>

<style scoped>
.main-slider {
    --swiper-theme-color: #f0512e;
    --swiper-navigation-size: 24px;
}

:deep(.swiper-button-next),
:deep(.swiper-button-prev) {
    background: rgba(255, 255, 255, 0.4);
    backdrop-filter: blur(8px);
    width: 48px;
    height: 48px;
    border-radius: 50%;
    color: #f0512e;
    transition: all 0.3s ease;
    border: 1px solid rgba(255, 255, 255, 0.2);
    display: none;
}

:deep(.swiper-button-next) svg,
:deep(.swiper-button-prev) svg {
    width: 24px;
    height: 24px;
}

:deep(.swiper-button-next:hover),
:deep(.swiper-button-prev:hover) {
    background: rgba(255, 255, 255, 0.8);
    transform: scale(1.1);
}

:deep(.swiper-button-next:after),
:deep(.swiper-button-prev:after) {
    font-size: 18px;
    font-weight: 800;
}

:deep(.swiper-pagination-bullet) {
    width: 6px;
    height: 6px;
    background: #fff;
    opacity: 0.6;
    transition: all 0.3s ease;
}

:deep(.swiper-pagination-bullet-active) {
    opacity: 1;
    width: 20px;
    border-radius: 4px;
    background: #f0512e;
}

@media (max-width: 768px) {
    :deep(.swiper-button-next),
    :deep(.swiper-button-prev) {
        display: none;
    }
}
</style>
