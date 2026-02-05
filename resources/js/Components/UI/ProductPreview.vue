<template>
    <transition
        enter-active-class="transition ease-out duration-300"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition ease-in duration-200"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto bg-black/70 backdrop-blur-sm" @click="close">
            <div class="min-h-screen px-4 py-8 flex items-center justify-center">
                <div @click.stop class="bg-white rounded-2xl shadow-2xl max-w-6xl w-full overflow-hidden">
                    <!-- Header -->
                    <div class="bg-gradient-to-r from-blue-600 to-indigo-600 p-6">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <Eye class="w-6 h-6 text-white" />
                                <div>
                                    <h3 class="text-xl font-bold text-white">Product Preview</h3>
                                    <p class="text-white/90 text-sm mt-0.5">See how your product will look to customers</p>
                                </div>
                            </div>
                            <button @click="close" class="text-white/80 hover:text-white transition-colors">
                                <XIcon class="w-6 h-6" />
                            </button>
                        </div>
                    </div>

                    <!-- View Toggle -->
                    <div class="border-b border-gray-200 bg-gray-50 px-6 py-4">
                        <div class="flex items-center gap-4">
                            <button
                                v-for="view in views"
                                :key="view.id"
                                @click="currentView = view.id"
                                :class="[
                                    'flex items-center gap-2 px-4 py-2 rounded-lg font-medium text-sm transition-all',
                                    currentView === view.id
                                        ? 'bg-white shadow text-blue-600 border-2 border-blue-500'
                                        : 'text-gray-600 hover:bg-white/50'
                                ]"
                            >
                                <component :is="view.icon" class="w-4 h-4" />
                                {{ view.label }}
                            </button>
                        </div>
                    </div>

                    <!-- Preview Content -->
                    <div class="p-6 bg-gray-50 max-h-[70vh] overflow-y-auto">
                        <!-- Info Banner -->
                        <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-6 flex items-start gap-3">
                            <Eye class="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" />
                            <div class="text-sm text-blue-900">
                                <p class="font-semibold mb-1">Preview Mode</p>
                                <p class="text-blue-700">This is how your product will appear to customers. Switch views to see different layouts.</p>
                            </div>
                        </div>
                        <!-- Mobile View -->
                        <div v-if="currentView === 'mobile'" class="max-w-sm mx-auto">
                            <div class="bg-white rounded-2xl shadow-xl overflow-hidden border-8 border-gray-800">
                                <!-- Mobile Product Card -->
                                <div class="p-4">
                                    <div class="relative bg-gray-100 rounded-xl overflow-hidden aspect-square mb-4">
                                        <img
                                            v-if="productData.feature_image_preview"
                                            :src="productData.feature_image_preview"
                                            :alt="productData.name"
                                            class="w-full h-full object-cover"
                                        />
                                        <div v-else class="w-full h-full flex items-center justify-center text-gray-400">
                                            <ImageIcon class="w-16 h-16" />
                                        </div>

                                        <!-- Badges -->
                                        <div class="absolute top-2 left-2 flex flex-col gap-2">
                                            <span v-if="productData.previous_price && parseFloat(productData.previous_price) > parseFloat(productData.price)"
                                                  class="bg-red-500 text-white text-xs font-bold px-2 py-1 rounded-full">
                                                -{{ Math.round((1 - productData.price / productData.previous_price) * 100) }}%
                                            </span>
                                            <span v-if="productData.is_daily_product" class="bg-green-500 text-white text-xs font-bold px-2 py-1 rounded-full">
                                                Daily Deal
                                            </span>
                                        </div>
                                    </div>

                                    <h3 class="font-bold text-lg text-gray-900 mb-2 line-clamp-2">
                                        {{ productData.name || 'Product Name' }}
                                    </h3>

                                    <div class="flex items-center gap-2 mb-3">
                                        <span class="text-2xl font-bold text-blue-600">৳{{ productData.price || '0.00' }}</span>
                                        <span v-if="productData.previous_price && parseFloat(productData.previous_price) > parseFloat(productData.price)"
                                              class="text-sm line-through text-gray-400">
                                            ৳{{ productData.previous_price }}
                                        </span>
                                    </div>

                                    <p v-if="productData.short_description" class="text-sm text-gray-600 mb-4 line-clamp-3">
                                        {{ productData.short_description }}
                                    </p>

                                    <button class="w-full bg-gradient-to-r from-blue-600 to-indigo-600 text-white py-3 rounded-xl font-semibold shadow-lg">
                                        Add to Cart
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Desktop View -->
                        <div v-else-if="currentView === 'desktop'" class="bg-white rounded-2xl shadow-xl p-8">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                <!-- Images -->
                                <div>
                                    <div class="bg-gray-100 rounded-2xl overflow-hidden aspect-square mb-4">
                                        <img
                                            v-if="productData.feature_image_preview"
                                            :src="productData.feature_image_preview"
                                            :alt="productData.name"
                                            class="w-full h-full object-cover"
                                        />
                                        <div v-else class="w-full h-full flex items-center justify-center text-gray-400">
                                            <ImageIcon class="w-24 h-24" />
                                        </div>
                                    </div>

                                    <!-- Gallery Thumbnails -->
                                    <div v-if="productData.gallery_previews && productData.gallery_previews.length > 0"
                                         class="grid grid-cols-4 gap-2">
                                        <div v-for="(img, index) in productData.gallery_previews.slice(0, 4)"
                                             :key="index"
                                             class="bg-gray-100 rounded-lg overflow-hidden aspect-square">
                                            <img :src="img" :alt="`Gallery ${index + 1}`" class="w-full h-full object-cover" />
                                        </div>
                                    </div>
                                </div>

                                <!-- Details -->
                                <div>
                                    <!-- Badges -->
                                    <div class="flex gap-2 mb-4">
                                        <span v-if="productData.is_daily_product" class="bg-green-100 text-green-700 text-xs font-semibold px-3 py-1 rounded-full">
                                            Daily Deal
                                        </span>
                                        <span v-if="productData.is_pre_order" class="bg-purple-100 text-purple-700 text-xs font-semibold px-3 py-1 rounded-full">
                                            Pre-Order
                                        </span>
                                    </div>

                                    <h1 class="text-3xl font-bold text-gray-900 mb-4">
                                        {{ productData.name || 'Product Name' }}
                                    </h1>

                                    <div class="flex items-center gap-3 mb-6">
                                        <span class="text-4xl font-bold text-blue-600">৳{{ productData.price || '0.00' }}</span>
                                        <span v-if="productData.previous_price && parseFloat(productData.previous_price) > parseFloat(productData.price)"
                                              class="text-xl line-through text-gray-400">
                                            ৳{{ productData.previous_price }}
                                        </span>
                                        <span v-if="productData.previous_price && parseFloat(productData.previous_price) > parseFloat(productData.price)"
                                              class="bg-red-500 text-white text-sm font-bold px-3 py-1 rounded-full">
                                            Save {{ Math.round((1 - productData.price / productData.previous_price) * 100) }}%
                                        </span>
                                    </div>

                                    <p v-if="productData.short_description" class="text-gray-600 mb-6 leading-relaxed">
                                        {{ productData.short_description }}
                                    </p>

                                    <div class="space-y-3 mb-6 p-4 bg-gray-50 rounded-xl">
                                        <div v-if="productData.product_code" class="flex items-center gap-2 text-sm">
                                            <span class="text-gray-500 font-medium">SKU:</span>
                                            <span class="font-mono font-semibold text-gray-900">{{ productData.product_code }}</span>
                                        </div>
                                        <div v-if="productData.brand_name" class="flex items-center gap-2 text-sm">
                                            <span class="text-gray-500 font-medium">Brand:</span>
                                            <span class="font-semibold text-gray-900">{{ productData.brand_name }}</span>
                                        </div>
                                        <div v-if="productData.category_name" class="flex items-center gap-2 text-sm">
                                            <span class="text-gray-500 font-medium">Category:</span>
                                            <span class="font-semibold text-gray-900">{{ productData.category_name }}</span>
                                        </div>
                                        <div class="flex items-center gap-2 text-sm">
                                            <span class="text-gray-500 font-medium">Availability:</span>
                                            <span v-if="productData.stock > 0" class="flex items-center gap-1.5">
                                                <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                                                <span class="text-green-600 font-semibold">{{ productData.stock }} in stock</span>
                                            </span>
                                            <span v-else class="flex items-center gap-1.5">
                                                <span class="w-2 h-2 bg-red-500 rounded-full"></span>
                                                <span class="text-red-600 font-semibold">Out of Stock</span>
                                            </span>
                                        </div>
                                    </div>

                                    <div class="flex gap-3">
                                        <button class="flex-1 bg-gradient-to-r from-blue-600 to-indigo-600 text-white py-4 rounded-xl font-bold text-lg shadow-lg hover:shadow-xl transition-all">
                                            Add to Cart
                                        </button>
                                        <button class="px-6 py-4 border-2 border-gray-300 rounded-xl hover:bg-gray-50 transition-all">
                                            <Heart class="w-6 h-6 text-gray-600" />
                                        </button>
                                    </div>

                                    <!-- Product Tags -->
                                    <div v-if="productData.product_tags && productData.product_tags.length > 0" class="mt-6">
                                        <p class="text-sm text-gray-500 mb-2">Tags:</p>
                                        <div class="flex flex-wrap gap-2">
                                            <span v-for="tag in productData.product_tags" :key="tag"
                                                  class="bg-gray-100 text-gray-700 text-xs px-3 py-1 rounded-full">
                                                {{ tag }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Full Description -->
                            <div v-if="productData.description" class="mt-8 pt-8 border-t border-gray-200">
                                <h2 class="text-2xl font-bold text-gray-900 mb-4">Product Description</h2>
                                <div class="prose max-w-none" v-html="productData.description"></div>
                            </div>
                        </div>

                        <!-- Card View -->
                        <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                            <div v-for="i in 3" :key="i" class="bg-white rounded-2xl shadow-lg overflow-hidden hover:shadow-xl transition-all">
                                <div class="relative bg-gray-100 aspect-square">
                                    <img
                                        v-if="productData.feature_image_preview"
                                        :src="productData.feature_image_preview"
                                        :alt="productData.name"
                                        class="w-full h-full object-cover"
                                    />
                                    <div v-else class="w-full h-full flex items-center justify-center text-gray-400">
                                        <ImageIcon class="w-16 h-16" />
                                    </div>
                                </div>
                                <div class="p-4">
                                    <h3 class="font-bold text-gray-900 mb-2 line-clamp-2">
                                        {{ productData.name || 'Product Name' }}
                                    </h3>
                                    <div class="flex items-center gap-2">
                                        <span class="text-xl font-bold text-blue-600">৳{{ productData.price || '0.00' }}</span>
                                        <span v-if="productData.previous_price" class="text-sm line-through text-gray-400">
                                            ৳{{ productData.previous_price }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </transition>
</template>

<script setup>
import { ref } from 'vue';
import { Eye, XIcon, Smartphone, Monitor, Grid, ImageIcon, Heart } from 'lucide-vue-next';

const props = defineProps({
    show: {
        type: Boolean,
        default: false
    },
    productData: {
        type: Object,
        default: () => ({})
    }
});

const emit = defineEmits(['close']);

const currentView = ref('desktop');

const views = [
    { id: 'desktop', label: 'Desktop', icon: Monitor },
    { id: 'mobile', label: 'Mobile', icon: Smartphone },
    { id: 'card', label: 'Card View', icon: Grid }
];

const close = () => {
    emit('close');
};
</script>

<style scoped>
/* Prose styling for product description */
.prose {
    color: #374151;
    line-height: 1.75;
}

.prose h1, .prose h2, .prose h3 {
    color: #111827;
    font-weight: 700;
    margin-top: 1.5em;
    margin-bottom: 0.5em;
}

.prose p {
    margin-bottom: 1em;
}

.prose ul, .prose ol {
    margin-left: 1.5em;
    margin-bottom: 1em;
}

.prose li {
    margin-bottom: 0.5em;
}
</style>
