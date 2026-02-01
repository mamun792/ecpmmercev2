<script setup>
import { computed } from "vue";
import { Link } from "@inertiajs/vue3";
import { Swiper, SwiperSlide } from "swiper/vue";
import { Autoplay, Navigation } from "swiper/modules";
import "swiper/css";
import "swiper/css/navigation";

const props = defineProps({
    categories: {
        type: Array,
        required: true,
    },
});

const parentCategories = computed(() => {
    // copy the array first to avoid mutating the original prop, then filter and sort by `order`
    return [...props.categories]
        .filter((c) => !c.parent_id)
        .sort((a, b) => (a.order ?? 0) - (b.order ?? 0));
});

const swiperModules = [Autoplay, Navigation];

const swiperBreakpoints = {
    320: {
        slidesPerView: 2,
        spaceBetween: 10,
    },
    640: {
        slidesPerView: 3,
        spaceBetween: 15,
    },
    768: {
        slidesPerView: 4,
        spaceBetween: 20,
    },
    1024: {
        slidesPerView: 5,
        spaceBetween: 25,
    },
    1280: {
        slidesPerView: 6, // Adjusted for typical constraints
        spaceBetween: 30,
    },
};
</script>

<template>
    <section class="py-12 bg-white">
        <div class="container mx-auto px-4">
            <!-- Section Header -->
            <div class="text-center mb-10">
                <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-2">
                    Explore Popular Categories
                </h2>
                <p class="text-gray-600 text-sm md:text-base">
                    Find your preferred item in the highlighted product
                    selection.
                </p>
            </div>

            <!-- Categories Slider -->
            <div class="relative px-4 md:px-8">
                <!-- Tip: set :loop="false" while verifying to prevent cloned slides from changing which slide appears first -->
                <swiper
                    :modules="swiperModules"
                    :slides-per-view="3"
                    :space-between="15"
                    :breakpoints="swiperBreakpoints"
                    :autoplay="{
                        delay: 3000,
                        disableOnInteraction: false,
                        pauseOnMouseEnter: true,
                    }"
                    :loop="true"
                    class="py-4"
                >
                    <swiper-slide
                        v-for="category in parentCategories"
                        :key="category.id"
                        class="flex justify-center"
                    >
                        <Link
                            :href="route('category.show', category.slug)"
                            class="group flex flex-col items-center gap-3 w-full"
                        >
                            <!-- Image Container -->
                            <div
                                class="relative aspect-square rounded-full bg-gray-100 flex items-center justify-center transition-all duration-300 overflow-hidden"
                            >
                                <img
                                    v-if="category.image"
                                    :src="category.image"
                                    :alt="category.name"
                                    class="w-full h-full object-cover mix-blend-multiply transition-transform duration-500 group-hover:scale-110"
                                />
                                <div
                                    v-else
                                    class="w-full h-full flex items-center justify-center text-gray-400"
                                >
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        class="h-8 w-8"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                                        />
                                    </svg>
                                </div>
                            </div>

                            <!-- Category Name -->
                            <span
                                class="text-xs md:text-sm font-semibold text-gray-800 text-center leading-tight group-hover:text-primary transition-colors line-clamp-2"
                            >
                                {{ category.name }}
                            </span>
                        </Link>
                    </swiper-slide>
                </swiper>
            </div>
        </div>
    </section>
</template>

<style scoped>
:deep(.swiper-wrapper) {
    align-items: center;
}
</style>
