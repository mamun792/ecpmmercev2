<script setup>
import { defineProps, reactive, ref, onMounted, nextTick } from "vue";
import { Swiper, SwiperSlide } from "swiper/vue";
import { Navigation, Pagination, EffectCoverflow } from "swiper/modules";
import { ChevronLeft, ChevronRight, Play, Maximize2 } from "lucide-vue-next";

import "swiper/css/effect-coverflow";

const props = defineProps({
    productswithVideo: {
        type: Array,
        required: true,
    },
});

const modules = [Navigation, Pagination, EffectCoverflow];
const activeSlide = ref(0);
const videoRefs = reactive({});
const playingStates = ref({});
const swiperInstance = ref(null);

// Initialize playing states
const initializePlayingStates = () => {
    props.productswithVideo.forEach((product) => {
        playingStates.value[product.id] = false;
    });
};

// Pause all videos
const pauseAllVideos = () => {
    Object.values(videoRefs).forEach((video) => {
        if (video) {
            video.pause();
            const productId = video.dataset.productId;
            if (productId) {
                playingStates.value[productId] = false;
            }
        }
    });
};

// Play specific video
const playVideo = (productId) => {
    const video = videoRefs[productId];
    //console.log('Video ref for product', productId, video); // Debug log
    if (video) {
        video
            .play()
            .then(() => {
                playingStates.value[productId] = true;
            })
            .catch((err) => {
                console.warn(
                    `Failed to autoplay video for product ${productId}:`,
                    err,
                );
            });
    } else {
        setTimeout(() => {
            const retryVideo = videoRefs[productId];
            //console.log('Retry video ref for product', productId, retryVideo); // Debug log
            if (retryVideo) {
                retryVideo
                    .play()
                    .then(() => {
                        playingStates.value[productId] = true;
                    })
                    .catch(() => {});
            }
        }, 200);
    }
};

// Handle slide change end (after animation)
const onSlideChange = (swiper) => {
    pauseAllVideos();
    activeSlide.value = swiper.realIndex; // Use realIndex instead of activeIndex

    nextTick(() => {
        const activeProduct = props.productswithVideo[swiper.realIndex];
        if (activeProduct) {
            playVideo(activeProduct.id);
            //console.log('Active product:', activeProduct);
        }
    });
};

// Handle swiper init
const onSwiper = (swiper) => {
    swiperInstance.value = swiper;
    //console.log('Swiper initialized with slides:', props.productswithVideo.length); // Debug log
    nextTick(() => {
        const firstProduct = props.productswithVideo[0];
        if (firstProduct) {
            setTimeout(() => {
                playVideo(firstProduct.id);
            }, 300);
        }
    });
};

// Manual toggle play/pause
const toggleVideo = (productId) => {
    const video = videoRefs[productId];
    if (video) {
        if (video.paused) {
            pauseAllVideos();
            playVideo(productId);
        } else {
            video.pause();
            playingStates.value[productId] = false;
        }
    }
};

onMounted(() => {
    initializePlayingStates();
});
</script>

<template>
    <div
        v-if="props.productswithVideo.length > 0"
        class="container mx-auto py-8"
    >
        <h3 class="text-2xl font-semibold text-center">
            Trending Looks To Watch
        </h3>
        <!-- <pre>{{ productswithVideo }}</pre> -->
        <div
            class="relative w-full flex items-center justify-center overflow-hidden"
        >
            <div class="w-full mx-auto px-4">
                <swiper
                    :slides-per-view="3"
                    :space-between="0"
                    :centered-slides="true"
                    :loop="props.productswithVideo.length >= 3"
                    :effect="'coverflow'"
                    :grab-cursor="true"
                    :coverflow-effect="{
                        rotate: 0,
                        stretch: 0,
                        depth: 100,
                        modifier: 2.5,
                        slideShadows: true,
                    }"
                    :navigation="{
                        nextEl: '.swiper-button-next-custom',
                        prevEl: '.swiper-button-prev-custom',
                    }"
                    :pagination="{
                        clickable: true,
                        dynamicBullets: true,
                    }"
                    :modules="modules"
                    @swiper="onSwiper"
                    @slide-change-transition-end="onSlideChange"
                    class="product-swiper"
                    :breakpoints="{
                        0: { slidesPerView: 1, spaceBetween: 0 },
                        450: { slidesPerView: 1, spaceBetween: 0 },
                        1024: { slidesPerView: 3, spaceBetween: 0 },
                    }"
                >
                    <swiper-slide
                        v-for="(product, index) in props.productswithVideo"
                        :key="product.id"
                        class="swiper-slide-custom"
                    >
                        <div class="relative group">
                            <!-- Video Container -->
                            <div
                                class="relative aspect-[2/3] rounded-2xl overflow-hidden bg-black"
                            >
                                <!-- Video -->
                                <video
                                    :ref="
                                        (el) => {
                                            if (el) videoRefs[product.id] = el;
                                        }
                                    "
                                    :data-product-id="product.id"
                                    :src="product?.upload_video"
                                    :poster="product?.feature_image"
                                    muted
                                    loop
                                    preload="metadata"
                                    @loadedmetadata="
                                        () => {
                                            if (activeSlide === index)
                                                playVideo(product.id);
                                        }
                                    "
                                    class="w-full h-full object-cover"
                                />

                                <!-- Product Title -->
                                <div
                                    class="absolute top-0 left-0 right-0 p-6 z-10"
                                >
                                    <div
                                        class="bg-black/50 backdrop-blur-sm rounded-lg px-4 py-2 inline-block"
                                    >
                                        <h3
                                            class="text-white font-semibold text-sm leading-tight"
                                        >
                                            {{ product.name }}
                                        </h3>
                                    </div>
                                </div>

                                <!-- Play Button Overlay -->
                                <div
                                    class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300 cursor-pointer z-10"
                                    @click="toggleVideo(product.id)"
                                >
                                    <div
                                        class="w-16 h-16 bg-white/20 backdrop-blur-sm rounded-full flex items-center justify-center hover:bg-white/30 transition-all duration-300"
                                    >
                                        <Play
                                            v-if="!playingStates[product.id]"
                                            class="w-8 h-8 text-white ml-1"
                                        />
                                        <div
                                            v-else
                                            class="w-8 h-8 flex items-center justify-center"
                                        >
                                            <div
                                                class="w-2 h-6 bg-white rounded-sm mr-1"
                                            ></div>
                                            <div
                                                class="w-2 h-6 bg-white rounded-sm"
                                            ></div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Bottom Controls -->
                                <div
                                    class="absolute bottom-0 left-0 right-0 p-6 z-10"
                                >
                                    <div class="w-full">
                                        <div
                                            class="flex gap-3 justify-center items-center"
                                        >
                                            <NuxtLink
                                                :to="`/product/${product.slug}`"
                                                class="bg-white/20 backdrop-blur-sm hover:bg-white/30 text-white text-center w-full px-4 py-2 rounded-lg transition-all duration-300"
                                            >
                                                View
                                            </NuxtLink>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </swiper-slide>
                </swiper>
            </div>
        </div>
    </div>
</template>

<style scoped>
.product-swiper {
    width: 100%;
    padding: 30px 0;
}

.swiper-slide-custom:not(.swiper-slide-active) {
    transform: scale(0.85);
}

.swiper-slide-active {
    opacity: 1;
    transform: scale(1);
    z-index: 10;
}

:deep(.swiper-pagination-bullet) {
    width: 12px;
    height: 12px;
    background: var(--color-primary);
    opacity: 1;
    transition: all 0.3s ease;
}

:deep(.swiper-pagination-bullet-active) {
    background: var(--color-primary);
    transform: scale(1.2);
}

:deep(.swiper-button-next),
:deep(.swiper-button-prev) {
    display: none;
}
</style>
