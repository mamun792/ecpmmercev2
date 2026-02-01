<script setup>
/**
 * Amazon-Style Status Badge Component
 * Displays order status with consistent styling across the application
 */
const props = defineProps({
    status: {
        type: String,
        required: true,
    },
    size: {
        type: String,
        default: 'md', // 'sm', 'md', 'lg'
    },
});

const statusConfig = {
    pending: {
        label: 'Pending',
        bgClass: 'bg-amber-50',
        textClass: 'text-amber-700',
        dotClass: 'bg-amber-500',
        borderClass: 'border-amber-200',
    },
    processing: {
        label: 'Processing',
        bgClass: 'bg-blue-50',
        textClass: 'text-blue-700',
        dotClass: 'bg-blue-500',
        borderClass: 'border-blue-200',
    },
    confirmed: {
        label: 'Confirmed',
        bgClass: 'bg-violet-50',
        textClass: 'text-violet-700',
        dotClass: 'bg-violet-500',
        borderClass: 'border-violet-200',
    },
    shipped: {
        label: 'Shipped',
        bgClass: 'bg-purple-50',
        textClass: 'text-purple-700',
        dotClass: 'bg-purple-500',
        borderClass: 'border-purple-200',
    },
    delivered: {
        label: 'Delivered',
        bgClass: 'bg-emerald-50',
        textClass: 'text-emerald-700',
        dotClass: 'bg-emerald-500',
        borderClass: 'border-emerald-200',
    },
    cancelled: {
        label: 'Cancelled',
        bgClass: 'bg-red-50',
        textClass: 'text-red-700',
        dotClass: 'bg-red-500',
        borderClass: 'border-red-200',
    },
    returned: {
        label: 'Returned',
        bgClass: 'bg-orange-50',
        textClass: 'text-orange-700',
        dotClass: 'bg-orange-500',
        borderClass: 'border-orange-200',
    },
    incomplete: {
        label: 'Incomplete',
        bgClass: 'bg-slate-50',
        textClass: 'text-slate-700',
        dotClass: 'bg-slate-500',
        borderClass: 'border-slate-200',
    },
    on_hold: {
        label: 'On Hold',
        bgClass: 'bg-cyan-50',
        textClass: 'text-cyan-700',
        dotClass: 'bg-cyan-500',
        borderClass: 'border-cyan-200',
    },
};

const sizeClasses = {
    sm: 'px-2 py-0.5 text-xs',
    md: 'px-3 py-1 text-sm',
    lg: 'px-4 py-1.5 text-base',
};

const dotSizeClasses = {
    sm: 'w-1.5 h-1.5',
    md: 'w-2 h-2',
    lg: 'w-2.5 h-2.5',
};

const getConfig = (status) => {
    return statusConfig[status?.toLowerCase()] || statusConfig.pending;
};
</script>

<template>
    <span
        :class="[
            'inline-flex items-center gap-1.5 rounded-full font-medium border transition-all duration-200',
            sizeClasses[size],
            getConfig(status).bgClass,
            getConfig(status).textClass,
            getConfig(status).borderClass,
        ]"
    >
        <span
            :class="[
                'rounded-full animate-pulse',
                dotSizeClasses[size],
                getConfig(status).dotClass,
            ]"
        ></span>
        {{ getConfig(status).label }}
    </span>
</template>
