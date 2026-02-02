<script setup>
import { computed } from 'vue';
import { X, Calendar, DollarSign, User, Hash, MapPin, Truck } from 'lucide-vue-next';

const props = defineProps({
    filters: {
        type: Object,
        required: true
    }
});

const emit = defineEmits(['remove', 'clear-all']);

// Generate filter chips from active filters
const filterChips = computed(() => {
    const chips = [];

    if (props.filters.status) {
        chips.push({
            key: 'status',
            label: `Status: ${props.filters.status}`,
            icon: 'status',
            color: getStatusColor(props.filters.status)
        });
    }

    if (props.filters.payment_status) {
        chips.push({
            key: 'payment_status',
            label: `Payment: ${props.filters.payment_status}`,
            icon: DollarSign,
            color: 'bg-green-100 text-green-700'
        });
    }

    if (props.filters.customer_search) {
        chips.push({
            key: 'customer_search',
            label: `Customer: "${props.filters.customer_search}"`,
            icon: User,
            color: 'bg-blue-100 text-blue-700'
        });
    }

    if (props.filters.order_number) {
        chips.push({
            key: 'order_number',
            label: `Order: ${props.filters.order_number}`,
            icon: Hash,
            color: 'bg-purple-100 text-purple-700'
        });
    }

    if (props.filters.date_preset) {
        chips.push({
            key: 'date_preset',
            label: `Period: ${props.filters.date_preset.replace(/_/g, ' ')}`,
            icon: Calendar,
            color: 'bg-amber-100 text-amber-700'
        });
    }

    if (props.filters.date_from && props.filters.date_to) {
        chips.push({
            key: 'date_range',
            label: `Date: ${props.filters.date_from} to ${props.filters.date_to}`,
            icon: Calendar,
            color: 'bg-amber-100 text-amber-700'
        });
    } else if (props.filters.date_from) {
        chips.push({
            key: 'date_from',
            label: `From: ${props.filters.date_from}`,
            icon: Calendar,
            color: 'bg-amber-100 text-amber-700'
        });
    } else if (props.filters.date_to) {
        chips.push({
            key: 'date_to',
            label: `Until: ${props.filters.date_to}`,
            icon: Calendar,
            color: 'bg-amber-100 text-amber-700'
        });
    }

    if (props.filters.min_total || props.filters.max_total) {
        const min = props.filters.min_total || '0';
        const max = props.filters.max_total || '∞';
        chips.push({
            key: 'amount_range',
            label: `Amount: ৳${min} - ৳${max}`,
            icon: DollarSign,
            color: 'bg-emerald-100 text-emerald-700'
        });
    }

    if (props.filters.shipping_area) {
        chips.push({
            key: 'shipping_area',
            label: `Area: ${props.filters.shipping_area.replace(/_/g, ' ')}`,
            icon: MapPin,
            color: 'bg-indigo-100 text-indigo-700'
        });
    }

    if (props.filters.has_courier) {
        chips.push({
            key: 'has_courier',
            label: props.filters.has_courier === 'true' ? 'With Courier' : 'No Courier',
            icon: Truck,
            color: 'bg-cyan-100 text-cyan-700'
        });
    }

    return chips;
});

// Get color for status chip
function getStatusColor(status) {
    const colors = {
        pending: 'bg-blue-100 text-blue-700',
        processing: 'bg-orange-100 text-orange-700',
        shipped: 'bg-amber-100 text-amber-700',
        delivered: 'bg-green-100 text-green-700',
        cancelled: 'bg-red-100 text-red-700',
        returned: 'bg-purple-100 text-purple-700',
        on_hold: 'bg-gray-100 text-gray-700',
        confirmed: 'bg-indigo-100 text-indigo-700',
    };
    return colors[status] || 'bg-gray-100 text-gray-700';
}

// Remove specific filter
const removeFilter = (key) => {
    emit('remove', key);
};

// Clear all filters
const clearAll = () => {
    emit('clear-all');
};
</script>

<template>
    <div v-if="filterChips.length > 0" class="mb-6">
        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-2xl p-4 border border-blue-200">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold text-gray-700">Active Filters ({{ filterChips.length }})</h3>
                <button
                    @click="clearAll"
                    class="text-sm font-medium text-red-600 hover:text-red-700 hover:underline flex items-center gap-1"
                >
                    <X class="w-4 h-4" />
                    Clear All
                </button>
            </div>
            <div class="flex flex-wrap gap-2">
                <div
                    v-for="chip in filterChips"
                    :key="chip.key"
                    :class="[
                        'inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-sm font-medium shadow-sm transition-all duration-200',
                        chip.color,
                        'hover:shadow-md'
                    ]"
                >
                    <component :is="chip.icon" class="w-4 h-4" />
                    <span>{{ chip.label }}</span>
                    <button
                        @click="removeFilter(chip.key)"
                        class="ml-1 hover:bg-white/50 rounded-full p-0.5 transition-colors"
                        :aria-label="`Remove ${chip.label} filter`"
                    >
                        <X class="w-3.5 h-3.5" />
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
