<script setup>
import { computed } from "vue";

const props = defineProps({
    rating: {
        type: Number,
        required: true,
        validator: (value) => value >= 0 && value <= 5,
    },
    maxRating: {
        type: Number,
        default: 5,
    },
    showCount: {
        type: Boolean,
        default: true,
    },
    size: {
        type: String,
        default: "md",
        validator: (value) => ["sm", "md", "lg"].includes(value),
    },
});

const starSize = computed(() => {
    const sizes = {
        sm: "w-4 h-4",
        md: "w-5 h-5",
        lg: "w-6 h-6",
    };
    return sizes[props.size];
});

const fullStars = computed(() => Math.floor(props.rating));
const hasHalfStar = computed(() => props.rating % 1 >= 0.5);
const emptyStars = computed(() => {
    return props.maxRating - fullStars.value - (hasHalfStar.value ? 1 : 0);
});
</script>

<template>
    <div class="flex items-center gap-1">
        <!-- Full Stars -->
        <svg
            v-for="n in fullStars"
            :key="`full-${n}`"
            :class="starSize"
            class="text-yellow-400"
            fill="currentColor"
            viewBox="0 0 20 20"
        >
            <path
                d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
            />
        </svg>

        <!-- Half Star -->
        <svg
            v-if="hasHalfStar"
            :class="starSize"
            class="text-yellow-400"
            fill="currentColor"
            viewBox="0 0 20 20"
        >
            <defs>
                <linearGradient id="half">
                    <stop offset="50%" stop-color="currentColor" />
                    <stop offset="50%" stop-color="#d1d5db" />
                </linearGradient>
            </defs>
            <path
                fill="url(#half)"
                d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
            />
        </svg>

        <!-- Empty Stars -->
        <!-- <svg
            v-for="n in emptyStars"
            :key="`empty-${n}`"
            :class="starSize"
            class="text-gray-300"
            fill="currentColor"
            viewBox="0 0 20 20"
        >
            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
        </svg> -->

        <!-- Rating Count -->
        <span v-if="showCount" class="text-sm text-gray-600 ml-1">
            ({{ rating.toFixed(1) }})
        </span>
    </div>
</template>
