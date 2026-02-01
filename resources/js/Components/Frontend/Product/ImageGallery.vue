<template>
    <div class="w-full mx-auto md:px-4">
        <div class="flex gap-4 md:flex-row flex-col">
            <!-- Vertical Thumbnail Gallery (Desktop Only) -->
            <div
                class="hidden md:flex flex-col w-24 flex-shrink-0 relative group"
            >
                <!-- Scroll Up Button -->
                <button
                    @click="scrollThumbnails('up')"
                    class="absolute top-2 left-1/2 -translate-x-1/2 z-20 w-8 h-8 bg-white/30 backdrop-blur-md hover:bg-white/50 rounded-full flex items-center justify-center transition-all hover:scale-110 shadow-sm border border-white/20"
                    :class="{ 'opacity-0 pointer-events-none': !canScrollUp }"
                >
                    <svg
                        class="w-4 h-4 text-gray-800"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M5 15l7-7 7 7"
                        />
                    </svg>
                </button>

                <!-- Thumbnails Container -->
                <div
                    ref="thumbnailContainer"
                    @scroll="checkScroll"
                    class="flex flex-col gap-2 overflow-y-auto scrollbar-y-hide"
                    style="max-height: 500px"
                >
                    <button
                        v-for="(media, index) in allMedia"
                        :key="index"
                        @click="handleThumbnailClick(index)"
                        class="w-full aspect-square rounded-lg overflow-hidden border-2 transition-all duration-200 hover:border-blue-400 flex-shrink-0"
                        :class="
                            index === activeIndex
                                ? 'border-blue-500 ring-2 ring-blue-300'
                                : 'border-gray-200'
                        "
                    >
                        <img
                            v-if="media.type === 'image'"
                            :src="media.src"
                            :alt="`Thumbnail ${index + 1}`"
                            class="w-full h-full object-cover"
                        />
                        <div
                            v-else
                            class="w-full h-full bg-gray-200 flex items-center justify-center relative cursor-pointer group"
                        >
                            <!-- Thumbnail video preview -->
                            <video
                                :src="media.src"
                                class="w-full h-full object-cover pointer-events-none"
                                muted
                                playsinline
                                preload="metadata"
                            />

                            <!-- Glass effect play button -->
                            <div
                                class="absolute inset-0 flex items-center justify-center"
                            >
                                <div
                                    class="bg-white/30 backdrop-blur-md rounded-full p-4 transition-transform group-hover:scale-110"
                                >
                                    <!-- Play icon -->
                                    <svg
                                        class="w-8 h-8 text-white"
                                        fill="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path d="M4 2v20l18-10L4 2z" />
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </button>
                </div>

                <!-- Scroll Down Button -->
                <button
                    @click="scrollThumbnails('down')"
                    class="absolute bottom-2 left-1/2 -translate-x-1/2 z-20 w-8 h-8 bg-white/30 backdrop-blur-md hover:bg-white/50 rounded-full flex items-center justify-center transition-all hover:scale-110 shadow-sm border border-white/20"
                    :class="{
                        'opacity-0 pointer-events-none': !canScrollDown,
                    }"
                >
                    <svg
                        class="w-4 h-4 text-gray-800"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M19 9l-7 7-7-7"
                        />
                    </svg>
                </button>
            </div>

            <!-- Main Image Display -->
            <div class="flex-1 w-full">
                <div
                    class="relative w-full border border-gray-200 bg-gray-100 md:rounded-lg overflow-hidden"
                >
                    <!-- Navigation Arrows -->
                    <button
                        @click="handlePrevious"
                        class="absolute left-2 md:left-4 top-1/2 -translate-y-1/2 z-10 w-8 h-8 bg-white/30 backdrop-blur-md hover:bg-white/50 rounded-full flex items-center justify-center transition-all hover:scale-110"
                    >
                        <svg
                            class="w-4 h-4 text-gray-800"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M15 19l-7-7 7-7"
                            />
                        </svg>
                    </button>

                    <button
                        @click="handleNext"
                        class="absolute right-2 md:right-4 top-1/2 -translate-y-1/2 z-10 w-8 h-8 bg-white/30 backdrop-blur-md hover:bg-white/50 rounded-full flex items-center justify-center transition-all hover:scale-110"
                    >
                        <svg
                            class="w-4 h-4 text-gray-800"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 5l7 7-7 7"
                            />
                        </svg>
                    </button>

                    <!-- Main Content -->
                    <div
                        v-if="activeMedia"
                        ref="imageContainer"
                        class="relative w-full h-full cursor-zoom-in"
                        @mousemove="handleMouseMove"
                        @mouseleave="handleMouseLeave"
                        @touchstart="handleTouchStart"
                        @touchmove="handleTouchMove"
                        @touchend="handleTouchEnd"
                    >
                        <!-- Image with Zoom -->
                        <img
                            v-if="activeMedia.type === 'image'"
                            :src="activeMedia.src"
                            :alt="`Product image ${activeIndex + 1}`"
                            class="w-full h-full object-cover transition-transform duration-200"
                            :style="zoomStyle"
                        />

                        <!-- Video -->
                        <video
                            v-else
                            :key="activeMedia.src"
                            :src="activeMedia.src"
                            controls
                            autoplay
                            class="w-full h-full object-cover"
                        />

                        <!-- Zoom Indicator -->
                        <div
                            v-if="isZooming"
                            class="absolute top-4 right-4 bg-black/70 text-white px-3 py-2 rounded-lg text-sm flex items-center gap-2"
                        >
                            <svg
                                class="w-4 h-4"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v6m3-3H7"
                                />
                            </svg>
                            Zoom Active
                        </div>

                        <!-- Variation Badge -->
                        <!-- <div
                            v-if="activeMedia.isVariation"
                            class="absolute top-4 left-4 bg-blue-500 text-white px-3 py-1 rounded-full text-xs font-semibold"
                        >
                            Selected Variation
                        </div> -->
                    </div>

                    <!-- Image Counter -->
                    <!-- <div
            class="absolute bottom-4 left-1/2 -translate-x-1/2 bg-black/70 text-white px-4 py-2 rounded-full text-sm"
          >
            {{ activeIndex + 1 }} / {{ allMedia.length }}
          </div> -->
                </div>

                <!-- Pagination Dots (mobile only) -->
                <div class="md:hidden flex justify-center gap-2 mt-4">
                    <button
                        v-for="(media, index) in allMedia"
                        :key="index"
                        type="button"
                        @click="handleThumbnailClick(index)"
                        :aria-label="`Go to image ${index + 1}`"
                        :aria-current="index === activeIndex ? 'true' : 'false'"
                        class="transition-transform duration-150 focus:outline-none focus:ring-2 focus:ring-primary/30"
                        :class="
                            index === activeIndex
                                ? 'w-3 h-3 bg-primary rounded-full scale-110'
                                : 'w-2 h-2 bg-gray-300 rounded-full hover:bg-gray-400'
                        "
                    ></button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, nextTick, onMounted } from "vue";

interface Media {
    type: "image" | "video";
    src: string;
    isVariation: boolean;
}

interface Props {
    images?: string[];
    selectedVariationImage?: string | null;
    video?: string | null;
}

const props = withDefaults(defineProps<Props>(), {
    images: () => [],
    selectedVariationImage: null,
    video: null,
});

const activeIndex = ref(0);
const isZooming = ref(false);
const mousePosition = ref({ x: 50, y: 50 });
const imageContainer = ref<HTMLElement | null>(null);
const thumbnailContainer = ref<HTMLElement | null>(null);
const canScrollUp = ref(false);
const canScrollDown = ref(false);

// Touch handling for mobile swipe
const touchStartX = ref(0);
const touchEndX = ref(0);
const touchStartY = ref(0);
const touchEndY = ref(0);
const minSwipeDistance = 50;

const checkScroll = () => {
    if (!thumbnailContainer.value) return;
    const { scrollTop, scrollHeight, clientHeight } = thumbnailContainer.value;
    canScrollUp.value = scrollTop > 0;
    canScrollDown.value = scrollTop + clientHeight < scrollHeight - 1;
};

// Combine all media sources
const allMedia = computed<Media[]>(() => {
    const media: Media[] = [];

    if (props.selectedVariationImage) {
        media.push({
            type: "image",
            src: props.selectedVariationImage,
            isVariation: true,
        });
    }

    props.images.forEach((img) => {
        media.push({ type: "image", src: img, isVariation: false });
    });

    if (props.video) {
        media.push({ type: "video", src: props.video, isVariation: false });
    }

    return media;
});

const activeMedia = computed(() => allMedia.value[activeIndex.value]);

const zoomStyle = computed(() => {
    if (!isZooming.value) {
        return {
            transform: "scale(1)",
        };
    }

    return {
        transform: "scale(2)",
        transformOrigin: `${mousePosition.value.x}% ${mousePosition.value.y}%`,
    };
});

// Reset to variation image when it changes
watch(
    () => props.selectedVariationImage,
    (newVal) => {
        if (newVal) {
            activeIndex.value = 0;
            nextTick(() => {
                scrollThumbnailIntoView(0);
                checkScroll();
            });
        }
    },
);

const scrollThumbnailIntoView = (index: number) => {
    if (thumbnailContainer.value) {
        const thumbnail = thumbnailContainer.value.children[
            index
        ] as HTMLElement;
        if (thumbnail) {
            thumbnail.scrollIntoView({ behavior: "smooth", block: "nearest" });
            setTimeout(checkScroll, 500);
        }
    }
};

onMounted(() => {
    checkScroll();
    window.addEventListener("resize", checkScroll);
});

watch(
    () => allMedia.value,
    () => {
        nextTick(checkScroll);
    },
);

const handlePrevious = () => {
    const newIndex =
        activeIndex.value > 0
            ? activeIndex.value - 1
            : allMedia.value.length - 1;
    activeIndex.value = newIndex;
    scrollThumbnailIntoView(newIndex);
};

const handleNext = () => {
    const newIndex =
        activeIndex.value < allMedia.value.length - 1
            ? activeIndex.value + 1
            : 0;
    activeIndex.value = newIndex;
    scrollThumbnailIntoView(newIndex);
};

const handleThumbnailClick = (index: number) => {
    activeIndex.value = index;
    scrollThumbnailIntoView(index);
};

const handleMouseMove = (event: MouseEvent) => {
    if (!imageContainer.value) return;
    const rect = imageContainer.value.getBoundingClientRect();
    const x = ((event.clientX - rect.left) / rect.width) * 100;
    const y = ((event.clientY - rect.top) / rect.height) * 100;
    mousePosition.value = { x, y };
    isZooming.value = true;
};

const handleMouseLeave = () => {
    isZooming.value = false;
};

const scrollThumbnails = (direction: "up" | "down") => {
    if (thumbnailContainer.value) {
        const scrollAmount = 100;
        thumbnailContainer.value.scrollBy({
            top: direction === "up" ? -scrollAmount : scrollAmount,
            behavior: "smooth",
        });
    }
};

// Touch event handlers for mobile swipe
const handleTouchStart = (event: TouchEvent) => {
    touchStartX.value = event.touches[0].clientX;
    touchStartY.value = event.touches[0].clientY;
};

const handleTouchMove = (event: TouchEvent) => {
    touchEndX.value = event.touches[0].clientX;
    touchEndY.value = event.touches[0].clientY;
};

const handleTouchEnd = () => {
    const deltaX = touchStartX.value - touchEndX.value;
    const deltaY = Math.abs(touchStartY.value - touchEndY.value);

    // Only trigger swipe if horizontal movement is greater than vertical (to avoid interfering with scrolling)
    if (Math.abs(deltaX) > minSwipeDistance && Math.abs(deltaX) > deltaY) {
        if (deltaX > 0) {
            // Swiped left, go to next image
            handleNext();
        } else {
            // Swiped right, go to previous image
            handlePrevious();
        }
    }

    // Reset values
    touchStartX.value = 0;
    touchEndX.value = 0;
    touchStartY.value = 0;
    touchEndY.value = 0;
};
</script>

<style scoped>
/* Custom Scrollbar */
.scrollbar-y-hide::-webkit-scrollbar {
    width: 0px; /* Chrome, Safari */
    background: transparent; /* Optional */
}

.scrollbar-y-hide {
    -ms-overflow-style: none; /* IE and Edge */
    scrollbar-width: none; /* Firefox */
}
</style>
