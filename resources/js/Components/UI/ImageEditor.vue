<template>
    <transition
        enter-active-class="transition ease-out duration-200"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition ease-in duration-150"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm" @click="close">
            <div @click.stop class="bg-white rounded-2xl shadow-2xl max-w-4xl w-full overflow-hidden">
                <!-- Header -->
                <div class="bg-gradient-to-r from-purple-600 to-pink-600 p-6">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <Crop class="w-6 h-6 text-white" />
                            <h3 class="text-xl font-bold text-white">Edit Image</h3>
                        </div>
                        <button @click="close" class="text-white/80 hover:text-white transition-colors">
                            <XIcon class="w-5 h-5" />
                        </button>
                    </div>
                    <p class="text-white/90 text-sm mt-1">Crop, rotate, and adjust your image</p>
                </div>

                <!-- Editor Area -->
                <div class="p-6">
                    <div class="bg-gray-100 rounded-xl overflow-hidden mb-6" style="height: 400px;">
                        <div class="relative w-full h-full flex items-center justify-center">
                            <img
                                ref="imageEl"
                                :src="imageSrc"
                                :style="{
                                    transform: `rotate(${rotation}deg) scale(${zoom})`,
                                    transition: 'transform 0.3s ease'
                                }"
                                class="max-w-full max-h-full object-contain"
                                alt="Edit preview"
                            />
                        </div>
                    </div>

                    <!-- Controls -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                        <!-- Rotation -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                <RotateCw class="w-4 h-4 inline mr-1" />
                                Rotation: {{ rotation }}°
                            </label>
                            <input
                                v-model.number="rotation"
                                type="range"
                                min="0"
                                max="360"
                                step="90"
                                class="w-full"
                            />
                            <div class="flex gap-2 mt-2">
                                <button @click="rotation = (rotation - 90) % 360" class="flex-1 px-3 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg text-sm">
                                    ↺ Left
                                </button>
                                <button @click="rotation = (rotation + 90) % 360" class="flex-1 px-3 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg text-sm">
                                    ↻ Right
                                </button>
                            </div>
                        </div>

                        <!-- Zoom -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                <ZoomIn class="w-4 h-4 inline mr-1" />
                                Zoom: {{ Math.round(zoom * 100) }}%
                            </label>
                            <input
                                v-model.number="zoom"
                                type="range"
                                min="0.5"
                                max="2"
                                step="0.1"
                                class="w-full"
                            />
                            <div class="flex gap-2 mt-2">
                                <button @click="zoom = Math.max(0.5, zoom - 0.1)" class="flex-1 px-3 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg text-sm">
                                    −
                                </button>
                                <button @click="zoom = Math.min(2, zoom + 0.1)" class="flex-1 px-3 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg text-sm">
                                    +
                                </button>
                            </div>
                        </div>

                        <!-- Flip -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                <FlipHorizontal class="w-4 h-4 inline mr-1" />
                                Flip
                            </label>
                            <div class="flex gap-2">
                                <button @click="flipHorizontal = !flipHorizontal" class="flex-1 px-3 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg text-sm" :class="{ 'bg-purple-100 text-purple-700': flipHorizontal }">
                                    ↔ Horizontal
                                </button>
                                <button @click="flipVertical = !flipVertical" class="flex-1 px-3 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg text-sm" :class="{ 'bg-purple-100 text-purple-700': flipVertical }">
                                    ↕ Vertical
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Preset Aspect Ratios -->
                    <div class="mb-6">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Quick Aspect Ratios</label>
                        <div class="flex flex-wrap gap-2">
                            <button @click="aspectRatio = '1:1'" class="px-3 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg text-sm" :class="{ 'bg-purple-100 text-purple-700': aspectRatio === '1:1' }">
                                1:1 Square
                            </button>
                            <button @click="aspectRatio = '4:3'" class="px-3 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg text-sm" :class="{ 'bg-purple-100 text-purple-700': aspectRatio === '4:3' }">
                                4:3 Standard
                            </button>
                            <button @click="aspectRatio = '16:9'" class="px-3 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg text-sm" :class="{ 'bg-purple-100 text-purple-700': aspectRatio === '16:9' }">
                                16:9 Widescreen
                            </button>
                            <button @click="aspectRatio = 'free'" class="px-3 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg text-sm" :class="{ 'bg-purple-100 text-purple-700': aspectRatio === 'free' }">
                                Free Form
                            </button>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center justify-between gap-3 pt-4 border-t border-gray-200">
                        <button @click="reset" class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl font-semibold transition-all">
                            Reset
                        </button>
                        <div class="flex gap-3">
                            <button @click="close" class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl font-semibold transition-all">
                                Cancel
                            </button>
                            <button @click="applyChanges" class="px-6 py-2.5 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white rounded-xl font-semibold shadow-lg shadow-purple-500/25 transition-all">
                                <Check class="w-4 h-4 inline mr-1" />
                                Apply Changes
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </transition>
</template>

<script setup>
import { ref, watch } from 'vue';
import { XIcon, Crop, RotateCw, ZoomIn, FlipHorizontal, Check } from 'lucide-vue-next';

const props = defineProps({
    show: {
        type: Boolean,
        default: false
    },
    imageSrc: {
        type: String,
        required: true
    }
});

const emit = defineEmits(['close', 'apply']);

const imageEl = ref(null);
const rotation = ref(0);
const zoom = ref(1);
const flipHorizontal = ref(false);
const flipVertical = ref(false);
const aspectRatio = ref('free');

const close = () => {
    emit('close');
};

const reset = () => {
    rotation.value = 0;
    zoom.value = 1;
    flipHorizontal.value = false;
    flipVertical.value = false;
    aspectRatio.value = 'free';
};

const applyChanges = () => {
    // In a production app, you would:
    // 1. Use canvas to apply transformations
    // 2. Export as blob/base64
    // 3. Send edited image back to parent
    
    const editData = {
        rotation: rotation.value,
        zoom: zoom.value,
        flipHorizontal: flipHorizontal.value,
        flipVertical: flipVertical.value,
        aspectRatio: aspectRatio.value
    };
    
    emit('apply', editData);
    close();
};

// Reset when modal closes
watch(() => props.show, (newVal) => {
    if (!newVal) {
        reset();
    }
});
</script>

<style scoped>
input[type="range"] {
    -webkit-appearance: none;
    appearance: none;
    width: 100%;
    height: 6px;
    border-radius: 3px;
    background: linear-gradient(to right, #e5e7eb 0%, #9333ea 50%, #ec4899 100%);
    outline: none;
}

input[type="range"]::-webkit-slider-thumb {
    -webkit-appearance: none;
    appearance: none;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    background: white;
    border: 2px solid #9333ea;
    cursor: pointer;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

input[type="range"]::-moz-range-thumb {
    width: 18px;
    height: 18px;
    border-radius: 50%;
    background: white;
    border: 2px solid #9333ea;
    cursor: pointer;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}
</style>
