<script setup>
/**
 * Amazon-Style Payment Status Badge Component
 */
const props = defineProps({
    status: {
        type: String,
        required: true,
    },
    size: {
        type: String,
        default: 'md',
    },
});

const statusConfig = {
    paid: {
        label: 'Paid',
        bgClass: 'bg-emerald-50',
        textClass: 'text-emerald-700',
        icon: '✓',
        borderClass: 'border-emerald-200',
    },
    unpaid: {
        label: 'Unpaid',
        bgClass: 'bg-red-50',
        textClass: 'text-red-700',
        icon: '○',
        borderClass: 'border-red-200',
    },
    refunded: {
        label: 'Refunded',
        bgClass: 'bg-slate-50',
        textClass: 'text-slate-700',
        icon: '↩',
        borderClass: 'border-slate-200',
    },
    failed: {
        label: 'Failed',
        bgClass: 'bg-orange-50',
        textClass: 'text-orange-700',
        icon: '✕',
        borderClass: 'border-orange-200',
    },
};

const sizeClasses = {
    sm: 'px-2 py-0.5 text-xs',
    md: 'px-3 py-1 text-sm',
    lg: 'px-4 py-1.5 text-base',
};

const getConfig = (status) => {
    return statusConfig[status?.toLowerCase()] || statusConfig.unpaid;
};
</script>

<template>
    <span
        :class="[
            'inline-flex items-center gap-1 rounded-full font-medium border',
            sizeClasses[size],
            getConfig(status).bgClass,
            getConfig(status).textClass,
            getConfig(status).borderClass,
        ]"
    >
        <span class="text-xs">{{ getConfig(status).icon }}</span>
        {{ getConfig(status).label }}
    </span>
</template>
