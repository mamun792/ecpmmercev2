<template>
    <div class="relative inline-block" @mouseenter="showTooltip" @mouseleave="hideTooltip">
        <slot></slot>
        
        <transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0 translate-y-1"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 translate-y-1"
        >
            <div
                v-if="visible"
                :class="[
                    'absolute z-50 px-3 py-2 text-sm font-medium text-white bg-gray-900 rounded-lg shadow-lg',
                    'max-w-xs whitespace-normal',
                    positionClasses
                ]"
                role="tooltip"
            >
                {{ text }}
                <div :class="['absolute w-2 h-2 bg-gray-900 transform rotate-45', arrowClasses]"></div>
            </div>
        </transition>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    text: {
        type: String,
        required: true
    },
    position: {
        type: String,
        default: 'top',
        validator: (value) => ['top', 'bottom', 'left', 'right'].includes(value)
    },
    delay: {
        type: Number,
        default: 200
    }
});

const visible = ref(false);
let timeoutId = null;

const positionClasses = computed(() => {
    switch (props.position) {
        case 'top':
            return 'bottom-full left-1/2 -translate-x-1/2 mb-2';
        case 'bottom':
            return 'top-full left-1/2 -translate-x-1/2 mt-2';
        case 'left':
            return 'right-full top-1/2 -translate-y-1/2 mr-2';
        case 'right':
            return 'left-full top-1/2 -translate-y-1/2 ml-2';
        default:
            return 'bottom-full left-1/2 -translate-x-1/2 mb-2';
    }
});

const arrowClasses = computed(() => {
    switch (props.position) {
        case 'top':
            return 'top-full left-1/2 -translate-x-1/2 -mt-1';
        case 'bottom':
            return 'bottom-full left-1/2 -translate-x-1/2 -mb-1';
        case 'left':
            return 'left-full top-1/2 -translate-y-1/2 -ml-1';
        case 'right':
            return 'right-full top-1/2 -translate-y-1/2 -mr-1';
        default:
            return 'top-full left-1/2 -translate-x-1/2 -mt-1';
    }
});

const showTooltip = () => {
    timeoutId = setTimeout(() => {
        visible.value = true;
    }, props.delay);
};

const hideTooltip = () => {
    if (timeoutId) {
        clearTimeout(timeoutId);
        timeoutId = null;
    }
    visible.value = false;
};
</script>
