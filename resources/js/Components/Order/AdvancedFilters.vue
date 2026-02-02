<script setup>
import { ref, computed, watch } from 'vue';
import { Search, X, Calendar, DollarSign, MapPin, Truck, Filter, ChevronDown, Hash } from 'lucide-vue-next';

const props = defineProps({
    modelValue: {
        type: Object,
        required: true
    },
    statusCounts: {
        type: Object,
        default: () => ({})
    }
});

const emit = defineEmits(['update:modelValue', 'apply', 'reset']);

const localFilters = ref({ ...props.modelValue });
const showAdvanced = ref(false);

// Quick date presets
const datePresets = [
    { value: 'today', label: 'Today' },
    { value: 'yesterday', label: 'Yesterday' },
    { value: 'this_week', label: 'This Week' },
    { value: 'last_week', label: 'Last Week' },
    { value: 'this_month', label: 'This Month' },
    { value: 'last_month', label: 'Last Month' },
    { value: 'last_7_days', label: 'Last 7 Days' },
    { value: 'last_30_days', label: 'Last 30 Days' },
    { value: 'this_year', label: 'This Year' },
];

// Payment status options
const paymentStatusOptions = [
    { value: 'unpaid', label: 'Unpaid', color: 'text-red-600 bg-red-50' },
    { value: 'paid', label: 'Paid', color: 'text-green-600 bg-green-50' },
    { value: 'refunded', label: 'Refunded', color: 'text-purple-600 bg-purple-50' },
    { value: 'failed', label: 'Failed', color: 'text-gray-600 bg-gray-50' },
];

// Shipping area options
const shippingAreas = [
    { value: 'inside_dhaka', label: 'Inside Dhaka' },
    { value: 'outside_dhaka', label: 'Outside Dhaka' },
];

// Watch for external changes only (don't emit back)
watch(() => props.modelValue, (newValue) => {
    localFilters.value = { ...newValue };
}, { deep: true });

// Apply date preset
const applyDatePreset = (preset) => {
    localFilters.value.date_preset = preset;
    // Clear manual date range when preset is selected
    localFilters.value.date_from = '';
    localFilters.value.date_to = '';
    // Emit updated filters
    emit('update:modelValue', localFilters.value);
};

// Clear date preset when manual date is selected
watch([() => localFilters.value.date_from, () => localFilters.value.date_to], () => {
    if (localFilters.value.date_from || localFilters.value.date_to) {
        localFilters.value.date_preset = '';
        emit('update:modelValue', localFilters.value);
    }
});

// Active filter count
const activeFilterCount = computed(() => {
    let count = 0;
    const filterKeys = ['status', 'payment_status', 'customer_search', 'order_number',
                        'date_from', 'date_to', 'date_preset', 'min_total', 'max_total',
                        'shipping_area', 'has_courier'];

    filterKeys.forEach(key => {
        if (localFilters.value[key] && localFilters.value[key] !== '') {
            count++;
        }
    });

    return count;
});

// Reset all filters
const resetFilters = () => {
    const clearedFilters = {
        status: '',
        payment_status: '',
        customer_search: '',
        order_number: '',
        date_from: '',
        date_to: '',
        date_preset: '',
        min_total: '',
        max_total: '',
        shipping_area: '',
        has_courier: '',
    };
    localFilters.value = { ...clearedFilters };
    emit('update:modelValue', clearedFilters);
    emit('reset');
};

// Toggle advanced filters
const toggleAdvanced = () => {
    showAdvanced.value = !showAdvanced.value;
};
</script>

<template>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <!-- Header -->
        <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-indigo-50">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-blue-600 rounded-lg">
                        <Filter class="w-5 h-5 text-white" />
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Advanced Filters</h3>
                        <p class="text-sm text-gray-500">
                            {{ activeFilterCount }} filter{{ activeFilterCount !== 1 ? 's' : '' }} active
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button
                        v-if="activeFilterCount > 0"
                        @click="resetFilters"
                        class="px-4 py-2 text-sm font-medium text-red-600 bg-red-50 rounded-lg hover:bg-red-100 transition-colors"
                    >
                        <X class="w-4 h-4 inline mr-1" />
                        Clear All
                    </button>
                    <button
                        @click="toggleAdvanced"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white rounded-lg border border-gray-300 hover:bg-gray-50 transition-colors flex items-center gap-2"
                    >
                        {{ showAdvanced ? 'Hide' : 'Show' }} Advanced
                        <ChevronDown
                            :class="['w-4 h-4 transition-transform', showAdvanced ? 'rotate-180' : '']"
                        />
                    </button>
                </div>
            </div>
        </div>

        <!-- Basic Filters (Always Visible) -->
        <div class="p-6 space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <!-- Search by Customer/Order -->
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700">
                        <Search class="w-4 h-4 inline mr-1" />
                        Search Customer
                    </label>
                    <input
                        v-model="localFilters.customer_search"
                        @input="emit('update:modelValue', localFilters)"
                        type="text"
                        placeholder="Name, email, or phone..."
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    />
                </div>

                <!-- Order Number -->
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700">
                        <Hash class="w-4 h-4 inline mr-1" />
                        Order Number
                    </label>
                    <input
                        v-model="localFilters.order_number"
                        @input="emit('update:modelValue', localFilters)"
                        type="text"
                        placeholder="ORD-123456..."
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    />
                </div>

                <!-- Payment Status -->
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700">
                        <DollarSign class="w-4 h-4 inline mr-1" />
                        Payment Status
                    </label>
                    <select
                        v-model="localFilters.payment_status"
                        @change="emit('update:modelValue', localFilters)"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    >
                        <option value="">All Payments</option>
                        <option
                            v-for="status in paymentStatusOptions"
                            :key="status.value"
                            :value="status.value"
                        >
                            {{ status.label }}
                        </option>
                    </select>
                </div>
            </div>

            <!-- Quick Date Filters -->
            <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                    <Calendar class="w-4 h-4 inline mr-1" />
                    Quick Date Filter
                </label>
                <div class="flex flex-wrap gap-2">
                    <button
                        v-for="preset in datePresets"
                        :key="preset.value"
                        @click="applyDatePreset(preset.value)"
                        :class="[
                            'px-4 py-2 text-sm font-medium rounded-lg transition-all',
                            localFilters.date_preset === preset.value
                                ? 'bg-blue-600 text-white shadow-md'
                                : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                        ]"
                    >
                        {{ preset.label }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Advanced Filters (Collapsible) -->
        <transition
            enter-active-class="transition-all duration-300 ease-out"
            enter-from-class="max-h-0 opacity-0"
            enter-to-class="max-h-96 opacity-100"
            leave-active-class="transition-all duration-200 ease-in"
            leave-from-class="max-h-96 opacity-100"
            leave-to-class="max-h-0 opacity-0"
        >
            <div v-show="showAdvanced" class="px-6 pb-6 border-t border-gray-200 bg-gray-50">
                <div class="pt-6 space-y-4">
                    <h4 class="text-sm font-semibold text-gray-700 mb-4">Advanced Options</h4>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <!-- Date From -->
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">Date From</label>
                            <input
                                v-model="localFilters.date_from"
                                @change="emit('update:modelValue', localFilters)"
                                type="date"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            />
                        </div>

                        <!-- Date To -->
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">Date To</label>
                            <input
                                v-model="localFilters.date_to"
                                @change="emit('update:modelValue', localFilters)"
                                type="date"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            />
                        </div>

                        <!-- Min Total -->
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">Min Amount (৳)</label>
                            <input
                                v-model.number="localFilters.min_total"
                                @input="emit('update:modelValue', localFilters)"
                                type="number"
                                placeholder="0"
                                min="0"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            />
                        </div>

                        <!-- Max Total -->
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">Max Amount (৳)</label>
                            <input
                                v-model.number="localFilters.max_total"
                                @input="emit('update:modelValue', localFilters)"
                                type="number"
                                placeholder="100000"
                                min="0"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            />
                        </div>

                        <!-- Shipping Area -->
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">
                                <MapPin class="w-4 h-4 inline mr-1" />
                                Shipping Area
                            </label>
                            <select
                                v-model="localFilters.shipping_area"
                                @change="emit('update:modelValue', localFilters)"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            >
                                <option value="">All Areas</option>
                                <option
                                    v-for="area in shippingAreas"
                                    :key="area.value"
                                    :value="area.value"
                                >
                                    {{ area.label }}
                                </option>
                            </select>
                        </div>

                        <!-- Courier Status -->
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">
                                <Truck class="w-4 h-4 inline mr-1" />
                                Courier Status
                            </label>
                            <select
                                v-model="localFilters.has_courier"
                                @change="emit('update:modelValue', localFilters)"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            >
                                <option value="">All Orders</option>
                                <option value="true">With Courier</option>
                                <option value="false">No Courier</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </transition>
    </div>
</template>

<style scoped>
.scrollbar-hide::-webkit-scrollbar {
    display: none;
}
.scrollbar-hide {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>
