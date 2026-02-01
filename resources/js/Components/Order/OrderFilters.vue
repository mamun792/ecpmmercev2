<script setup>
/**
 * Amazon-Style Advanced Filters Component
 * Collapsible filter panel with quick filters
 */
import { ref, computed } from 'vue';
import { ChevronDown, ChevronUp, Search, X, Calendar, Filter, RefreshCw } from 'lucide-vue-next';

const props = defineProps({
    modelValue: {
        type: Object,
        required: true,
    },
    paymentStatusOptions: {
        type: Array,
        default: () => ['unpaid', 'paid', 'refunded'],
    },
});

const emit = defineEmits(['update:modelValue', 'apply', 'reset']);

const isExpanded = ref(false);

const filters = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value),
});

const updateFilter = (key, value) => {
    emit('update:modelValue', { ...props.modelValue, [key]: value });
};

const hasActiveFilters = computed(() => {
    const f = props.modelValue;
    return f.payment_status || f.date_from || f.date_to || f.customer_search || f.order_number || f.min_total || f.max_total;
});

const activeFilterCount = computed(() => {
    let count = 0;
    const f = props.modelValue;
    if (f.payment_status) count++;
    if (f.date_from) count++;
    if (f.date_to) count++;
    if (f.customer_search) count++;
    if (f.order_number) count++;
    if (f.min_total) count++;
    if (f.max_total) count++;
    return count;
});

const quickFilters = [
    { id: 'today', label: "Today's Orders", icon: '📅' },
    { id: 'unpaid', label: 'Unpaid', icon: '💳' },
    { id: 'high_value', label: 'High Value (>৳5000)', icon: '💰' },
    { id: 'this_week', label: 'This Week', icon: '📊' },
];

const applyQuickFilter = (filterId) => {
    const today = new Date().toISOString().split('T')[0];
    const weekAgo = new Date(Date.now() - 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0];

    let newFilters = { ...props.modelValue };

    switch (filterId) {
        case 'today':
            newFilters.date_from = today;
            newFilters.date_to = today;
            // Clear other date-related filters
            newFilters.payment_status = '';
            newFilters.min_total = '';
            break;
        case 'unpaid':
            newFilters.payment_status = 'unpaid';
            // Clear other filters that might conflict
            newFilters.date_from = '';
            newFilters.date_to = '';
            newFilters.min_total = '';
            break;
        case 'high_value':
            newFilters.min_total = '5000';
            // Clear other filters
            newFilters.payment_status = '';
            newFilters.date_from = '';
            newFilters.date_to = '';
            break;
        case 'this_week':
            newFilters.date_from = weekAgo;
            newFilters.date_to = today;
            // Clear other filters
            newFilters.payment_status = '';
            newFilters.min_total = '';
            break;
    }

    emit('update:modelValue', newFilters);

    // Small delay to ensure the model is updated before applying
    setTimeout(() => {
        emit('apply');
    }, 10);
};

const isQuickFilterActive = (filterId) => {
    const f = props.modelValue;
    const today = new Date().toISOString().split('T')[0];
    const weekAgo = new Date(Date.now() - 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0];

    switch (filterId) {
        case 'today':
            return f.date_from === today && f.date_to === today;
        case 'unpaid':
            return f.payment_status === 'unpaid';
        case 'high_value':
            return f.min_total === '5000' || f.min_total === 5000;
        case 'this_week':
            return f.date_from === weekAgo && f.date_to === today;
        default:
            return false;
    }
};
</script>

<template>
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <!-- Header -->
        <div
            class="flex items-center justify-between px-4 py-3 bg-gradient-to-r from-gray-50 to-white cursor-pointer hover:bg-gray-50 transition-colors"
            @click="isExpanded = !isExpanded"
        >
            <div class="flex items-center gap-3">
                <div class="p-2 bg-orange-100 rounded-lg">
                    <Filter class="w-4 h-4 text-orange-600" />
                </div>
                <div>
                    <h3 class="font-semibold text-gray-900">Advanced Filters</h3>
                    <p class="text-xs text-gray-500">
                        {{ hasActiveFilters ? `${activeFilterCount} filter(s) active` : 'Refine your search' }}
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <span
                    v-if="hasActiveFilters"
                    class="px-2 py-0.5 bg-orange-600 text-white text-xs rounded-full"
                >
                    {{ activeFilterCount }}
                </span>
                <component :is="isExpanded ? ChevronUp : ChevronDown" class="w-5 h-5 text-gray-400" />
            </div>
        </div>

        <!-- Expandable Content -->
        <transition
            enter-active-class="transition-all duration-300 ease-out"
            leave-active-class="transition-all duration-200 ease-in"
            enter-from-class="opacity-0 max-h-0"
            enter-to-class="opacity-100 max-h-screen"
            leave-from-class="opacity-100 max-h-screen"
            leave-to-class="opacity-0 max-h-0"
        >
            <div v-show="isExpanded" class="overflow-hidden">
                <div class="p-4 border-t border-gray-100">
                    <!-- Quick Filters -->
                    <div class="mb-4">
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-2">Quick Filters</p>
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="qf in quickFilters"
                                :key="qf.id"
                                @click="applyQuickFilter(qf.id)"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-gray-100 hover:bg-orange-100 hover:text-orange-600 rounded-full text-sm font-medium transition-colors active:scale-95 active:bg-orange-200"
                                :class="{
                                    'bg-orange-100 text-orange-600 border border-orange-300': isQuickFilterActive(qf.id)
                                }"
                            >
                                <span>{{ qf.icon }}</span>
                                {{ qf.label }}
                            </button>
                        </div>
                    </div>

                    <!-- Filter Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <!-- Customer Search -->
                        <div class="relative">
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Customer Search</label>
                            <div class="relative">
                                <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                                <input
                                    :value="filters.customer_search"
                                    @input="updateFilter('customer_search', $event.target.value)"
                                    type="text"
                                    placeholder="Name, email, or phone..."
                                    class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-orange-200 focus:border-orange-500 transition-colors"
                                />
                            </div>
                        </div>

                        <!-- Order Number -->
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Order Number</label>
                            <input
                                :value="filters.order_number"
                                @input="updateFilter('order_number', $event.target.value)"
                                type="text"
                                placeholder="ORD-XXXXXX..."
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-orange-200 focus:border-orange-500 transition-colors font-mono"
                            />
                        </div>

                        <!-- Payment Status -->
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Payment Status</label>
                            <select
                                :value="filters.payment_status"
                                @change="updateFilter('payment_status', $event.target.value)"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-orange-200 focus:border-orange-500 transition-colors appearance-none bg-white"
                            >
                                <option value="">All Payments</option>
                                <option v-for="status in paymentStatusOptions" :key="status" :value="status">
                                    {{ status.charAt(0).toUpperCase() + status.slice(1) }}
                                </option>
                            </select>
                        </div>

                        <!-- Date Range -->
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Date Range</label>
                            <div class="flex gap-2">
                                <div class="relative flex-1">
                                    <Calendar class="absolute left-2 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400" />
                                    <input
                                        :value="filters.date_from"
                                        @input="updateFilter('date_from', $event.target.value)"
                                        type="date"
                                        class="w-full pl-7 pr-2 py-2 border border-gray-300 rounded-lg text-xs focus:ring-2 focus:ring-primary/20 focus:border-primary transition-colors"
                                    />
                                </div>
                                <span class="self-center text-gray-400">—</span>
                                <div class="relative flex-1">
                                    <Calendar class="absolute left-2 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400" />
                                    <input
                                        :value="filters.date_to"
                                        @input="updateFilter('date_to', $event.target.value)"
                                        type="date"
                                        class="w-full pl-7 pr-2 py-2 border border-gray-300 rounded-lg text-xs focus:ring-2 focus:ring-primary/20 focus:border-primary transition-colors"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Min Total -->
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Min Amount (৳)</label>
                            <input
                                :value="filters.min_total"
                                @input="updateFilter('min_total', $event.target.value)"
                                type="number"
                                min="0"
                                placeholder="0"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary transition-colors"
                            />
                        </div>

                        <!-- Max Total -->
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Max Amount (৳)</label>
                            <input
                                :value="filters.max_total"
                                @input="updateFilter('max_total', $event.target.value)"
                                type="number"
                                min="0"
                                placeholder="No limit"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary transition-colors"
                            />
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex items-center justify-between mt-4 pt-4 border-t border-gray-100">
                        <button
                            v-if="hasActiveFilters"
                            @click="emit('reset')"
                            class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-800 hover:bg-gray-100 rounded-lg transition-colors"
                        >
                            <RefreshCw class="w-4 h-4" />
                            Clear All Filters
                        </button>
                        <div v-else></div>

                        <button
                            @click="emit('apply')"
                            class="inline-flex items-center gap-2 px-6 py-2 bg-orange-600 text-white text-sm font-medium rounded-lg hover:bg-orange-700 transition-colors shadow-sm hover:shadow"
                        >
                            <Search class="w-4 h-4" />
                            Apply Filters
                        </button>
                    </div>
                </div>
            </div>
        </transition>
    </div>
</template>
