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
                    <div ref="cropContainerRef" class="bg-gray-100 rounded-xl overflow-hidden mb-6 relative" style="height: 400px;">
                        <div class="relative w-full h-full flex items-center justify-center">
                            <img
                                v-if="imageSrc"
                                ref="imageEl"
                                :src="imageSrc"
                                :style="{
                                    transform: `rotate(${rotation}deg) scale(${zoom * (flipHorizontal ? -1 : 1)}, ${zoom * (flipVertical ? -1 : 1)})`,
                                    transition: 'transform 0.3s ease'
                                }"
                                class="max-w-full max-h-full object-contain"
                                alt="Edit preview"
                            />
                            <div v-else class="text-gray-400 text-center">
                                <Crop class="w-12 h-12 mx-auto mb-2 opacity-50" />
                                <p>No image selected</p>
                            </div>

                            <!-- Custom Crop Box (Free Mode) -->
                            <div v-if="imageSrc && aspectRatio === 'free'"
                                 class="absolute inset-0 pointer-events-none">
                                <!-- Dark overlay -->
                                <div class="absolute inset-0 bg-black/40"></div>

                                <!-- Draggable crop box -->
                                <div
                                    :style="{
                                        left: customCrop.x + 'px',
                                        top: customCrop.y + 'px',
                                        width: customCrop.width + 'px',
                                        height: customCrop.height + 'px'
                                    }"
                                    class="absolute border-2 border-white shadow-lg pointer-events-auto cursor-move"
                                    @mousedown="startDrag"
                                >
                                    <!-- Grid overlay (rule of thirds) -->
                                    <div class="absolute inset-0 grid grid-cols-3 grid-rows-3">
                                        <div v-for="i in 9" :key="i" class="border border-white/30"></div>
                                    </div>

                                    <!-- Corner resize handles -->
                                    <div @mousedown="startResize('tl', $event)"
                                         class="absolute -left-1.5 -top-1.5 w-4 h-4 bg-white border-2 border-purple-600 rounded-full cursor-nwse-resize hover:scale-125 transition-transform"></div>
                                    <div @mousedown="startResize('tr', $event)"
                                         class="absolute -right-1.5 -top-1.5 w-4 h-4 bg-white border-2 border-purple-600 rounded-full cursor-nesw-resize hover:scale-125 transition-transform"></div>
                                    <div @mousedown="startResize('bl', $event)"
                                         class="absolute -left-1.5 -bottom-1.5 w-4 h-4 bg-white border-2 border-purple-600 rounded-full cursor-nesw-resize hover:scale-125 transition-transform"></div>
                                    <div @mousedown="startResize('br', $event)"
                                         class="absolute -right-1.5 -bottom-1.5 w-4 h-4 bg-white border-2 border-purple-600 rounded-full cursor-nwse-resize hover:scale-125 transition-transform"></div>

                                    <!-- Edge resize handles -->
                                    <div @mousedown="startResize('top', $event)"
                                         class="absolute left-1/2 -translate-x-1/2 -top-1.5 w-8 h-3 bg-white border-2 border-purple-600 rounded-full cursor-ns-resize hover:scale-125 transition-transform"></div>
                                    <div @mousedown="startResize('right', $event)"
                                         class="absolute top-1/2 -translate-y-1/2 -right-1.5 w-3 h-8 bg-white border-2 border-purple-600 rounded-full cursor-ew-resize hover:scale-125 transition-transform"></div>
                                    <div @mousedown="startResize('bottom', $event)"
                                         class="absolute left-1/2 -translate-x-1/2 -bottom-1.5 w-8 h-3 bg-white border-2 border-purple-600 rounded-full cursor-ns-resize hover:scale-125 transition-transform"></div>
                                    <div @mousedown="startResize('left', $event)"
                                         class="absolute top-1/2 -translate-y-1/2 -left-1.5 w-3 h-8 bg-white border-2 border-purple-600 rounded-full cursor-ew-resize hover:scale-125 transition-transform"></div>

                                    <!-- Crop dimensions display -->
                                    <div class="absolute -top-8 left-0 bg-purple-600 text-white px-2 py-1 rounded text-xs font-semibold whitespace-nowrap">
                                        {{ Math.round(customCrop.width) }} × {{ Math.round(customCrop.height) }}px
                                    </div>
                                </div>
                            </div>

                            <!-- Aspect Ratio Crop Overlay (Preset Ratios) -->
                            <div v-if="imageSrc && aspectRatio !== 'free'"
                                 class="absolute inset-0 pointer-events-none">
                                <div class="absolute inset-0 bg-black/40"></div>
                                <div :style="cropOverlayStyle"
                                     class="absolute border-2 border-white shadow-lg">
                                    <div class="absolute inset-0 border-2 border-dashed border-white/50"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Mode Label -->
                        <div class="absolute top-4 right-4 bg-purple-600 text-white px-3 py-1.5 rounded-lg text-sm font-semibold shadow-lg">
                            <template v-if="aspectRatio === 'free'">
                                <Move class="w-3.5 h-3.5 inline mr-1" />
                                Custom Crop - Drag to adjust
                            </template>
                            <template v-else>
                                {{ aspectRatio }} Crop Active
                            </template>
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
import { ref, watch, computed, onMounted, onUnmounted } from 'vue';
import { XIcon, Crop, RotateCw, ZoomIn, FlipHorizontal, Check, Move } from 'lucide-vue-next';

const props = defineProps({
    show: {
        type: Boolean,
        default: false
    },
    imageSrc: {
        type: String,
        default: null
    }
});

const emit = defineEmits(['close', 'apply']);

const imageEl = ref(null);
const rotation = ref(0);
const zoom = ref(1);
const flipHorizontal = ref(false);
const flipVertical = ref(false);
const aspectRatio = ref('free');

// ========== CUSTOM CROP: Drag-to-crop functionality ==========
const customCrop = ref({
    x: 50,        // Left position in pixels
    y: 50,        // Top position in pixels
    width: 300,   // Crop box width
    height: 300   // Crop box height
});

const isDragging = ref(false);
const isResizing = ref(false);
const resizeHandle = ref(null); // 'tl', 'tr', 'bl', 'br', 'top', 'right', 'bottom', 'left'
const dragStart = ref({ x: 0, y: 0, cropX: 0, cropY: 0, cropWidth: 0, cropHeight: 0 });

const cropContainerRef = ref(null);

// Calculate crop overlay position and size based on aspect ratio
// Accounts for rotation - crop box stays fixed/centered even when image rotates
const cropOverlayStyle = computed(() => {
    if (aspectRatio.value === 'free') return {};

    const ratios = {
        '1:1': 1,
        '4:3': 4 / 3,
        '16:9': 16 / 9
    };

    const targetRatio = ratios[aspectRatio.value];
    const containerWidth = 400; // Match preview container
    const containerHeight = 400;

    // Rotation affects effective container dimensions
    // At 90° and 270°, width and height swap
    const rotationRad = (rotation.value * Math.PI) / 180;
    const isRotated90or270 = rotation.value === 90 || rotation.value === 270;

    // Effective dimensions after rotation
    let effectiveWidth = containerWidth;
    let effectiveHeight = containerHeight;

    if (isRotated90or270) {
        // Swap dimensions for 90° and 270° rotations
        effectiveWidth = containerHeight;
        effectiveHeight = containerWidth;
    }

    const containerRatio = effectiveWidth / effectiveHeight;

    let width, height, top, left;

    if (containerRatio > targetRatio) {
        // Container is wider, limit by height
        height = effectiveHeight * 0.8;
        width = height * targetRatio;
    } else {
        // Container is taller, limit by width
        width = effectiveWidth * 0.8;
        height = width / targetRatio;
    }

    // Always center the crop box in the container
    // This keeps it fixed regardless of rotation
    top = (containerHeight - height) / 2;
    left = (containerWidth - width) / 2;

    return {
        width: `${width}px`,
        height: `${height}px`,
        top: `${top}px`,
        left: `${left}px`
    };
});

const close = () => {
    emit('close');
};

const reset = () => {
    rotation.value = 0;
    zoom.value = 1;
    flipHorizontal.value = false;
    flipVertical.value = false;
    aspectRatio.value = 'free';

    // Reset custom crop to center
    customCrop.value = {
        x: 50,
        y: 50,
        width: 300,
        height: 300
    };
};

// ========== CUSTOM CROP: Mouse event handlers ==========
const startDrag = (e) => {
    if (aspectRatio.value !== 'free') return; // Only allow in free mode

    isDragging.value = true;
    dragStart.value = {
        x: e.clientX,
        y: e.clientY,
        cropX: customCrop.value.x,
        cropY: customCrop.value.y,
        cropWidth: customCrop.value.width,
        cropHeight: customCrop.value.height
    };
};

const startResize = (handle, e) => {
    if (aspectRatio.value !== 'free') return; // Only allow in free mode

    e.stopPropagation();
    isResizing.value = true;
    resizeHandle.value = handle;
    dragStart.value = {
        x: e.clientX,
        y: e.clientY,
        cropX: customCrop.value.x,
        cropY: customCrop.value.y,
        cropWidth: customCrop.value.width,
        cropHeight: customCrop.value.height
    };
};

const onMouseMove = (e) => {
    if (!isDragging.value && !isResizing.value) return;

    const deltaX = e.clientX - dragStart.value.x;
    const deltaY = e.clientY - dragStart.value.y;

    const containerRect = cropContainerRef.value?.getBoundingClientRect();
    if (!containerRect) return;

    if (isDragging.value) {
        // Move the crop box
        let newX = dragStart.value.cropX + deltaX;
        let newY = dragStart.value.cropY + deltaY;

        // Constrain to container bounds
        newX = Math.max(0, Math.min(newX, containerRect.width - customCrop.value.width));
        newY = Math.max(0, Math.min(newY, containerRect.height - customCrop.value.height));

        customCrop.value.x = newX;
        customCrop.value.y = newY;
    } else if (isResizing.value) {
        // Resize the crop box based on handle
        const minSize = 50; // Minimum crop size

        let newX = customCrop.value.x;
        let newY = customCrop.value.y;
        let newWidth = customCrop.value.width;
        let newHeight = customCrop.value.height;

        switch (resizeHandle.value) {
            case 'tl': // Top-left
                newX = Math.min(dragStart.value.cropX + deltaX, dragStart.value.cropX + dragStart.value.cropWidth - minSize);
                newY = Math.min(dragStart.value.cropY + deltaY, dragStart.value.cropY + dragStart.value.cropHeight - minSize);
                newWidth = dragStart.value.cropWidth - (newX - dragStart.value.cropX);
                newHeight = dragStart.value.cropHeight - (newY - dragStart.value.cropY);
                break;
            case 'tr': // Top-right
                newY = Math.min(dragStart.value.cropY + deltaY, dragStart.value.cropY + dragStart.value.cropHeight - minSize);
                newWidth = Math.max(minSize, dragStart.value.cropWidth + deltaX);
                newHeight = dragStart.value.cropHeight - (newY - dragStart.value.cropY);
                break;
            case 'bl': // Bottom-left
                newX = Math.min(dragStart.value.cropX + deltaX, dragStart.value.cropX + dragStart.value.cropWidth - minSize);
                newWidth = dragStart.value.cropWidth - (newX - dragStart.value.cropX);
                newHeight = Math.max(minSize, dragStart.value.cropHeight + deltaY);
                break;
            case 'br': // Bottom-right
                newWidth = Math.max(minSize, dragStart.value.cropWidth + deltaX);
                newHeight = Math.max(minSize, dragStart.value.cropHeight + deltaY);
                break;
            case 'top':
                newY = Math.min(dragStart.value.cropY + deltaY, dragStart.value.cropY + dragStart.value.cropHeight - minSize);
                newHeight = dragStart.value.cropHeight - (newY - dragStart.value.cropY);
                break;
            case 'right':
                newWidth = Math.max(minSize, dragStart.value.cropWidth + deltaX);
                break;
            case 'bottom':
                newHeight = Math.max(minSize, dragStart.value.cropHeight + deltaY);
                break;
            case 'left':
                newX = Math.min(dragStart.value.cropX + deltaX, dragStart.value.cropX + dragStart.value.cropWidth - minSize);
                newWidth = dragStart.value.cropWidth - (newX - dragStart.value.cropX);
                break;
        }

        // Constrain to container bounds
        newX = Math.max(0, newX);
        newY = Math.max(0, newY);
        newWidth = Math.min(newWidth, containerRect.width - newX);
        newHeight = Math.min(newHeight, containerRect.height - newY);

        customCrop.value = { x: newX, y: newY, width: newWidth, height: newHeight };
    }
};

const stopDrag = () => {
    isDragging.value = false;
    isResizing.value = false;
    resizeHandle.value = null;
};

// Add/remove global mouse listeners
const addMouseListeners = () => {
    window.addEventListener('mousemove', onMouseMove);
    window.addEventListener('mouseup', stopDrag);
};

const removeMouseListeners = () => {
    window.removeEventListener('mousemove', onMouseMove);
    window.removeEventListener('mouseup', stopDrag);
};

// Setup listeners when component mounts
onMounted(() => {
    addMouseListeners();
});

onUnmounted(() => {
    removeMouseListeners();
});

const applyChanges = () => {
    // Prepare edit data with all transformations including custom crop
    const editData = {
        rotation: rotation.value,
        zoom: zoom.value,
        flipHorizontal: flipHorizontal.value,
        flipVertical: flipVertical.value,
        aspectRatio: aspectRatio.value,
        // Include custom crop coordinates for free mode
        customCrop: aspectRatio.value === 'free' ? {
            x: customCrop.value.x,
            y: customCrop.value.y,
            width: customCrop.value.width,
            height: customCrop.value.height
        } : null
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
