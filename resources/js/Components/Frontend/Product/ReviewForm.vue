<script setup>
import { ref } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';

const props = defineProps({
    productId: {
        type: Number,
        required: true
    }
});

const emit = defineEmits(['submitted']);

const page = usePage();

const form = useForm({
    product_id: props.productId,
    rating: 5,
    review_message: '',
    name: '', // For guest users
    images: [] // For review images
});

const previewImages = ref([]);

const hoveredRating = ref(0);

const submitReview = () => {
    form.post(route('reviews.store'), {
        preserveScroll: true,
        forceFormData: true, // Force multipart/form-data for file upload
        onSuccess: () => {
            form.reset('review_message', 'name', 'images');
            previewImages.value.forEach(url => URL.revokeObjectURL(url));
            previewImages.value = [];
            emit('submitted');
        }
    });
};

const setRating = (rating) => {
    form.rating = rating;
};

const handleImageUpload = (event) => {
    const files = Array.from(event.target.files);
    
    // Limit to 5 images
    if (files.length > 5) {
        alert('You can upload maximum 5 images');
        return;
    }
    
    form.images = files;
    
    // Create preview URLs
    previewImages.value = files.map(file => URL.createObjectURL(file));
};

const removeImage = (index) => {
    const newImages = Array.from(form.images);
    newImages.splice(index, 1);
    form.images = newImages;
    
    const newPreviews = [...previewImages.value];
    URL.revokeObjectURL(newPreviews[index]);
    newPreviews.splice(index, 1);
    previewImages.value = newPreviews;
};
</script>

<template>
    <div class="border border-gray-200 rounded-lg p-6 bg-gray-50">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Write Your Review</h3>
        
        <form @submit.prevent="submitReview" class="space-y-4">
            <!-- Rating -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Your Rating <span class="text-red-500">*</span>
                </label>
                <div class="flex items-center gap-1">
                    <button
                        v-for="star in 5"
                        :key="star"
                        type="button"
                        @click="setRating(star)"
                        @mouseenter="hoveredRating = star"
                        @mouseleave="hoveredRating = 0"
                        class="focus:outline-none"
                    >
                        <svg
                            class="w-8 h-8 transition"
                            :class="star <= (hoveredRating || form.rating) ? 'text-yellow-400' : 'text-gray-300'"
                            fill="currentColor"
                            viewBox="0 0 20 20"
                        >
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                    </button>
                </div>
                <div v-if="form.errors.rating" class="text-red-500 text-sm mt-1">
                    {{ form.errors.rating }}
                </div>
            </div>

            <!-- Name (for guests only) -->
            <div v-if="!page.props.auth.user">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Your Name <span class="text-red-500">*</span>
                </label>
                <input
                    v-model="form.name"
                    type="text"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="Enter your name"
                    :required="!page.props.auth.user"
                />
                <div v-if="form.errors.name" class="text-red-500 text-sm mt-1">
                    {{ form.errors.name }}
                </div>
            </div>

            <!-- Review Message -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Your Review <span class="text-red-500">*</span>
                </label>
                <textarea
                    v-model="form.review_message"
                    rows="4"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="Share your experience with this product..."
                    required
                ></textarea>
                <div class="text-sm text-gray-500 mt-1">
                    Minimum 10 characters
                </div>
                <div v-if="form.errors.review_message" class="text-red-500 text-sm mt-1">
                    {{ form.errors.review_message }}
                </div>
            </div>

            <!-- Image Upload -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Add Photos (Optional)
                </label>
                <div class="flex items-center gap-4">
                    <label class="cursor-pointer">
                        <input
                            type="file"
                            @change="handleImageUpload"
                            accept="image/*"
                            multiple
                            class="hidden"
                        />
                        <div class="px-4 py-2 border-2 border-dashed border-gray-300 rounded-lg hover:border-blue-500 transition flex items-center gap-2">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <span class="text-sm text-gray-600">Upload Images</span>
                        </div>
                    </label>
                    <span class="text-xs text-gray-500">Max 5 images, 2MB each</span>
                </div>
                
                <!-- Image Previews -->
                <div v-if="previewImages.length > 0" class="mt-4 grid grid-cols-5 gap-2">
                    <div v-for="(preview, index) in previewImages" :key="index" class="relative group">
                        <img :src="preview" alt="Preview" class="w-full h-20 object-cover rounded-lg" />
                        <button
                            type="button"
                            @click="removeImage(index)"
                            class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full p-1 opacity-0 group-hover:opacity-100 transition"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
                
                <div v-if="form.errors.images" class="text-red-500 text-sm mt-1">
                    {{ form.errors.images }}
                </div>
            </div>

            <!-- Submit Button -->
            <div class="flex items-center gap-4">
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition"
                >
                    {{ form.processing ? 'Submitting...' : 'Submit Review' }}
                </button>
            </div>
        </form>
    </div>
</template>
