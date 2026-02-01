<script setup>
import { ref, computed } from 'vue';
import StarRating from './StarRating.vue';
import ReviewCard from './ReviewCard.vue';
import ReviewForm from './ReviewForm.vue';

const props = defineProps({
    productId: {
        type: Number,
        required: true
    },
    reviews: {
        type: Array,
        default: () => []
    },
    reviewStats: {
        type: Object,
        default: () => ({
            total_reviews: 0,
            average_rating: 0,
            rating_distribution: {}
        })
    },
    canReview: {
        type: Object,
        default: () => ({
            can_review: false,
            reason: null
        })
    }
});

const showReviewForm = ref(false);
const reviewsToShow = ref(5);

const displayedReviews = computed(() => {
    return props.reviews.slice(0, reviewsToShow.value);
});

const hasMoreReviews = computed(() => {
    return props.reviews.length > reviewsToShow.value;
});

const loadMoreReviews = () => {
    reviewsToShow.value += 5;
};
</script>

<template>
    <div class="mt-12 border-t pt-8">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-bold text-gray-900">
                Customer Reviews ({{ reviewStats.total_reviews }})
            </h2>
            
            <!-- Write Review Button -->
            <button
                v-if="canReview.can_review"
                @click="showReviewForm = !showReviewForm"
                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
            >
                {{ showReviewForm ? 'Cancel' : 'Write a Review' }}
            </button>
        </div>

        <!-- Review Stats Summary -->
        <div v-if="reviewStats.total_reviews > 0" class="mb-8 p-6 bg-gray-50 rounded-lg">
            <div class="flex items-center gap-4">
                <div class="text-center">
                    <div class="text-4xl font-bold text-gray-900">
                        {{ reviewStats.average_rating }}
                    </div>
                    <StarRating :rating="reviewStats.average_rating" :show-count="false" />
                    <div class="text-sm text-gray-600 mt-1">
                        {{ reviewStats.total_reviews }} {{ reviewStats.total_reviews === 1 ? 'review' : 'reviews' }}
                    </div>
                </div>
                
                <!-- Rating Distribution -->
                <div class="flex-1 space-y-2">
                    <div v-for="star in [5, 4, 3, 2, 1]" :key="star" class="flex items-center gap-2">
                        <span class="text-sm text-gray-600 w-12">{{ star }} star</span>
                        <div class="flex-1 bg-gray-200 rounded-full h-2">
                            <div
                                class="bg-yellow-400 h-2 rounded-full"
                                :style="{
                                    width: reviewStats.total_reviews > 0
                                        ? `${(reviewStats.rating_distribution[star] / reviewStats.total_reviews) * 100}%`
                                        : '0%'
                                }"
                            ></div>
                        </div>
                        <span class="text-sm text-gray-600 w-8">
                            {{ reviewStats.rating_distribution[star] || 0 }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cannot Review Message -->
        <div v-if="!canReview.can_review" class="mb-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
            <p class="text-sm text-yellow-800">
                {{ canReview.reason }}
            </p>
        </div>

        <!-- Review Form -->
        <ReviewForm
            v-if="showReviewForm && canReview.can_review"
            :product-id="productId"
            @submitted="showReviewForm = false"
            class="mb-8"
        />

        <!-- Reviews Grid -->
        <div v-if="reviews.length > 0">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-3 mb-8">
                <ReviewCard
                    v-for="review in displayedReviews"
                    :key="review.id"
                    :review="review"
                />
            </div>

            <!-- Load More Button -->
            <div v-if="hasMoreReviews" class="text-center">
                <button
                    @click="loadMoreReviews"
                    class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition font-medium"
                >
                    Load More Reviews
                </button>
            </div>
        </div>

        <!-- No Reviews -->
        <div v-else class="text-center py-12">
            <p class="text-gray-500 text-lg">No reviews yet. Be the first to review this product!</p>
        </div>
    </div>
</template>
