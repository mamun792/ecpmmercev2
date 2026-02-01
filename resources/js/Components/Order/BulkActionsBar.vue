<script setup>
/**
 * Amazon-Style Bulk Actions Bar Component
 * Appears when orders are selected for batch operations
 */
import { Printer, Download, Truck, X, CheckSquare } from 'lucide-vue-next';

const props = defineProps({
    selectedCount: {
        type: Number,
        required: true,
    },
    isDownloading: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits([
    'clear-selection',
    'bulk-print',
    'bulk-download',
    'send-to-steadfast',
    'send-to-pathao',
]);
</script>

<template>
    <transition
        enter-active-class="transition-all duration-300 ease-out"
        leave-active-class="transition-all duration-200 ease-in"
        enter-from-class="opacity-0 -translate-y-2"
        enter-to-class="opacity-100 translate-y-0"
        leave-from-class="opacity-100 translate-y-0"
        leave-to-class="opacity-0 -translate-y-2"
    >
        <div
            v-if="selectedCount > 0"
            class="sticky top-0 z-40 bg-gradient-to-r from-primary/10 via-blue-50 to-primary/10 border-y border-primary/20 shadow-sm"
        >
            <div class="flex items-center justify-between px-4 py-3">
                <!-- Selection Info -->
                <div class="flex items-center gap-3">
                    <div class="flex items-center gap-2 bg-primary text-white px-3 py-1.5 rounded-lg">
                        <CheckSquare class="w-4 h-4" />
                        <span class="font-semibold">{{ selectedCount }}</span>
                        <span class="text-sm opacity-90">selected</span>
                    </div>
                    <button
                        @click="emit('clear-selection')"
                        class="flex items-center gap-1 text-sm text-gray-600 hover:text-gray-800 transition-colors"
                    >
                        <X class="w-4 h-4" />
                        Clear
                    </button>
                </div>

                <!-- Actions -->
                <div class="flex items-center gap-2">
                    <!-- Print Invoices -->
                    <button
                        @click="emit('bulk-print')"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-purple-600 text-white text-sm font-medium rounded-lg hover:bg-purple-700 transition-colors shadow-sm"
                    >
                        <Printer class="w-4 h-4" />
                        <span class="hidden sm:inline">Print Invoices</span>
                    </button>

                    <!-- Download Invoices -->
                    <button
                        @click="emit('bulk-download')"
                        :disabled="isDownloading"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors shadow-sm disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <Download class="w-4 h-4" :class="{ 'animate-bounce': isDownloading }" />
                        <span class="hidden sm:inline">{{ isDownloading ? 'Downloading...' : 'Download' }}</span>
                    </button>

                    <!-- Send to Steadfast -->
                    <button
                        @click="emit('send-to-steadfast')"
                        :disabled="isDownloading"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors shadow-sm disabled:opacity-50"
                    >
                        <Truck class="w-4 h-4" />
                        <span class="hidden md:inline">Steadfast</span>
                    </button>

                    <!-- Send to Pathao -->
                    <button
                        @click="emit('send-to-pathao')"
                        :disabled="isDownloading"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition-colors shadow-sm disabled:opacity-50"
                    >
                        <Truck class="w-4 h-4" />
                        <span class="hidden md:inline">Pathao</span>
                    </button>
                </div>
            </div>
        </div>
    </transition>
</template>
