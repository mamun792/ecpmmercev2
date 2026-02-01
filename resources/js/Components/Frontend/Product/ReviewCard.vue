<script setup>
import { computed, ref } from 'vue';
import StarRating from './StarRating.vue';

const props = defineProps({
    review: {
        type: Object,
        required: true
    }
});

const selectedImage = ref(null);

const avatarColor = computed(() => {
    const colors = [
        'bg-blue-500',
        'bg-green-500',
        'bg-purple-500',
        'bg-pink-500',
        'bg-indigo-500',
        'bg-red-500'
    ];
    const index = props.review.id % colors.length;
    return colors[index];
});

const openImageModal = (image) => {
    selectedImage.value = image;
};

const closeImageModal = () => {
    selectedImage.value = null;
};
</script>

<template>
    <div class="h-full border border-gray-200 rounded-xl p-5 hover:shadow-lg transition-all duration-300 bg-white hover:border-blue-300">
        <div class="flex flex-col h-full">
            <!-- Header -->
            <div class="flex items-start gap-3 mb-4">
                <!-- User Avatar -->
                <div :class="[avatarColor, 'w-12 h-12 rounded-full flex items-center justify-center text-white font-semibold flex-shrink-0']">
                    {{ review.user.initials }}
                </div>

                <!-- User Info -->
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <h4 class="font-semibold text-gray-900 truncate">{{ review.user.name }}</h4>
                        <span class="inline-flex items-center gap-1 text-xs text-green-700 bg-green-50 px-2 py-0.5 rounded-full flex-shrink-0">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            Verified
                        </span>
                    </div>
                    <div class="flex items-center justify-between mt-1">
                        <StarRating :rating="review.rating" size="sm" :show-count="false" />
                        <span class="text-xs text-gray-500">{{ review.time_ago }}</span>
                    </div>
                </div>
            </div>

            <!-- Rating Stars -->
            <!-- <div class="mb-3">
                <div class="flex items-center gap-1">
                    <span v-for="i in 5" :key="i" :class="i <= review.rating ? 'text-yellow-400' : 'text-gray-300'">
                        ★
                    </span>
                </div>
            </div> -->

            <!-- Review Comment -->
            <p class="text-gray-700 text-sm leading-relaxed mb-4 line-clamp-4 flex-grow">
                {{ review.comment }}
            </p>

            <!-- Review Images (if any) -->
            <div v-if="review.images && review.images.length > 0" class="mt-auto pt-3 border-t border-gray-100">
                <div class="grid grid-cols-3 gap-2">
                    <div 
                        v-for="(image, index) in review.images" 
                        :key="index"
                        class="relative group cursor-pointer overflow-hidden rounded-lg bg-gray-100 aspect-square"
                        @click="openImageModal(image)"
                    >
                        <img 
                            :src="image" 
                            :alt="`Review image ${index + 1}`"
                            class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300"
                        />
                        <!-- Eye Icon on Hover -->
                        <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Image Modal Popup -->
    <div 
        v-if="selectedImage"
        class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4"
        @click="closeImageModal"
    >
        <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full max-h-96 overflow-hidden" @click.stop>
            <div class="flex items-center justify-between p-4 border-b border-gray-200">
                <button 
                    @click="closeImageModal"
                    class="text-gray-500 hover:text-gray-700 transition"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="flex items-center justify-center p-6">
                <img 
                    :src="selectedImage" 
                    :alt="'Review image'"
                    class="max-w-full max-h-80 object-contain rounded-lg"
                />
            </div>
        </div>
    </div>
</template>
