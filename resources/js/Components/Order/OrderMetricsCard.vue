<script setup>
/**
 * Amazon-Style Metrics Card Component
 * Used for displaying order statistics with animation
 */
import { computed } from 'vue';

const props = defineProps({
    title: {
        type: String,
        required: true,
    },
    count: {
        type: [Number, String],
        default: 0,
    },
    sales: {
        type: [Number, String],
        default: 0,
    },
    icon: {
        type: String,
        default: 'orders',
    },
    status: {
        type: String,
        default: '',
    },
    isActive: {
        type: Boolean,
        default: false,
    },
    trend: {
        type: Number,
        default: 0,
    },
    currency: {
        type: String,
        default: '৳',
    },
});

const emit = defineEmits(['click']);

// Color mapping for different statuses
const colorConfig = computed(() => {
    const configs = {
        '': { // Total/Default
            gradient: 'from-slate-600 to-slate-800',
            bgActive: 'bg-gradient-to-br from-slate-600 to-slate-800',
            ring: 'ring-slate-400',
            iconBg: 'bg-slate-100',
            iconText: 'text-slate-600',
        },
        pending: {
            gradient: 'from-amber-500 to-amber-700',
            bgActive: 'bg-gradient-to-br from-amber-500 to-amber-700',
            ring: 'ring-amber-400',
            iconBg: 'bg-amber-100',
            iconText: 'text-amber-600',
        },
        processing: {
            gradient: 'from-blue-500 to-blue-700',
            bgActive: 'bg-gradient-to-br from-blue-500 to-blue-700',
            ring: 'ring-blue-400',
            iconBg: 'bg-blue-100',
            iconText: 'text-blue-600',
        },
        confirmed: {
            gradient: 'from-violet-500 to-violet-700',
            bgActive: 'bg-gradient-to-br from-violet-500 to-violet-700',
            ring: 'ring-violet-400',
            iconBg: 'bg-violet-100',
            iconText: 'text-violet-600',
        },
        shipped: {
            gradient: 'from-purple-500 to-purple-700',
            bgActive: 'bg-gradient-to-br from-purple-500 to-purple-700',
            ring: 'ring-purple-400',
            iconBg: 'bg-purple-100',
            iconText: 'text-purple-600',
        },
        delivered: {
            gradient: 'from-emerald-500 to-emerald-700',
            bgActive: 'bg-gradient-to-br from-emerald-500 to-emerald-700',
            ring: 'ring-emerald-400',
            iconBg: 'bg-emerald-100',
            iconText: 'text-emerald-600',
        },
        cancelled: {
            gradient: 'from-red-500 to-red-700',
            bgActive: 'bg-gradient-to-br from-red-500 to-red-700',
            ring: 'ring-red-400',
            iconBg: 'bg-red-100',
            iconText: 'text-red-600',
        },
        returned: {
            gradient: 'from-orange-500 to-orange-700',
            bgActive: 'bg-gradient-to-br from-orange-500 to-orange-700',
            ring: 'ring-orange-400',
            iconBg: 'bg-orange-100',
            iconText: 'text-orange-600',
        },
        on_hold: {
            gradient: 'from-cyan-500 to-cyan-700',
            bgActive: 'bg-gradient-to-br from-cyan-500 to-cyan-700',
            ring: 'ring-cyan-400',
            iconBg: 'bg-cyan-100',
            iconText: 'text-cyan-600',
        },
    };
    return configs[props.status] || configs[''];
});

const formattedCount = computed(() => {
    const num = Number(props.count);
    if (num >= 1000000) return `${(num / 1000000).toFixed(1)}M`;
    if (num >= 1000) return `${(num / 1000).toFixed(1)}K`;
    return num.toLocaleString();
});

const formattedSales = computed(() => {
    const num = Number(props.sales);
    if (num >= 1000000) return `${props.currency}${(num / 1000000).toFixed(1)}M`;
    if (num >= 1000) return `${props.currency}${(num / 1000).toFixed(1)}K`;
    return `${props.currency}${num.toLocaleString()}`;
});

const statusAbbrev = computed(() => {
    return props.title.substring(0, 2).toUpperCase();
});
</script>

<template>
    <div
        @click="emit('click')"
        class="relative overflow-hidden rounded-xl shadow-sm hover:shadow-lg cursor-pointer transition-all duration-300 transform hover:-translate-y-0.5 group"
        :class="[
            isActive
                ? [colorConfig.bgActive, 'text-white', 'ring-2', colorConfig.ring, 'ring-offset-2']
                : 'bg-white hover:bg-gray-50 border border-gray-200'
        ]"
    >
        <!-- Background Pattern -->
        <div
            v-if="isActive"
            class="absolute inset-0 opacity-10"
            style="background-image: url('data:image/svg+xml,%3Csvg width=\'20\' height=\'20\' viewBox=\'0 0 20 20\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'%23ffffff\' fill-opacity=\'0.4\'%3E%3Ccircle cx=\'3\' cy=\'3\' r=\'2\'/%3E%3C/g%3E%3C/svg%3E');"
        ></div>

        <div class="p-4 relative z-10">
            <div class="flex justify-between items-start">
                <div class="flex-1">
                    <!-- Title -->
                    <h3
                        class="text-xs font-medium mb-1 uppercase tracking-wide"
                        :class="isActive ? 'text-white/90' : 'text-gray-500'"
                    >
                        {{ title }}
                    </h3>

                    <!-- Count -->
                    <p
                        class="text-2xl font-bold tracking-tight"
                        :class="isActive ? 'text-white' : 'text-gray-900'"
                    >
                        {{ formattedCount }}
                    </p>

                    <!-- Sales -->
                    <p
                        class="text-sm mt-1 font-medium"
                        :class="isActive ? 'text-white/80' : 'text-gray-600'"
                    >
                        {{ formattedSales }}
                    </p>

                    <!-- Trend indicator -->
                    <div v-if="trend !== 0" class="flex items-center mt-2 text-xs">
                        <span
                            :class="[
                                'flex items-center gap-0.5 px-1.5 py-0.5 rounded-full',
                                trend > 0
                                    ? (isActive ? 'bg-white/20 text-white' : 'bg-green-100 text-green-700')
                                    : (isActive ? 'bg-white/20 text-white' : 'bg-red-100 text-red-700')
                            ]"
                        >
                            <svg v-if="trend > 0" class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                            </svg>
                            <svg v-else class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 12.586V5a1 1 0 012 0v7.586l2.293-2.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            {{ Math.abs(trend) }}%
                        </span>
                    </div>
                </div>

                <!-- Icon -->
                <div
                    class="p-2.5 rounded-xl transition-transform duration-300 group-hover:scale-110"
                    :class="isActive ? 'bg-white/20 text-white' : [colorConfig.iconBg, colorConfig.iconText]"
                >
                    <span class="text-sm font-bold">{{ statusAbbrev }}</span>
                </div>
            </div>
        </div>

        <!-- Hover indicator -->
        <div
            class="absolute bottom-0 left-0 right-0 h-1 transform scale-x-0 group-hover:scale-x-100 transition-transform duration-300"
            :class="isActive ? 'bg-white/30' : `bg-gradient-to-r ${colorConfig.gradient}`"
        ></div>
    </div>
</template>
