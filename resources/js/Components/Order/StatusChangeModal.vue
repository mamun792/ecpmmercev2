<script setup>
import { ref, computed } from 'vue';
import { AlertTriangle, CheckCircle, Package, RotateCcw } from 'lucide-vue-next';

const props = defineProps({
    show: Boolean,
    currentStatus: String,
    orderId: Number,
    orderNumber: String,
});

const emit = defineEmits(['close', 'confirm']);

const selectedStatus = ref('');

const statusOptions = [
    { value: 'pending', label: 'Pending', color: 'blue', icon: 'Clock' },
    { value: 'processing', label: 'Processing', color: 'orange', icon: 'Package' },
    { value: 'shipped', label: 'Shipped', color: 'amber', icon: 'Truck' },
    { value: 'delivered', label: 'Delivered', color: 'green', icon: 'CheckCircle' },
    { value: 'cancelled', label: 'Cancelled', color: 'red', icon: 'XCircle', warning: true },
    { value: 'returned', label: 'Returned', color: 'purple', icon: 'RotateCcw', warning: true },
    { value: 'on_hold', label: 'On Hold', color: 'gray', icon: 'AlertCircle' },
    { value: 'confirmed', label: 'Confirmed', color: 'indigo', icon: 'CheckCircle' },
];

// Stock affecting statuses
const willReturnStock = computed(() => {
    const activeStatuses = ['pending', 'processing', 'shipped', 'delivered', 'confirmed', 'on_hold'];
    const returningStatuses = ['cancelled', 'returned'];

    return activeStatuses.includes(props.currentStatus) &&
           returningStatuses.includes(selectedStatus.value);
});

const willReduceStock = computed(() => {
    const activeStatuses = ['pending', 'processing', 'shipped', 'delivered', 'confirmed', 'on_hold'];
    const returningStatuses = ['cancelled', 'returned'];

    return returningStatuses.includes(props.currentStatus) &&
           activeStatuses.includes(selectedStatus.value);
});

const statusImpact = computed(() => {
    if (willReturnStock.value) {
        return {
            type: 'warning',
            message: '⚠️ Stock will be returned to inventory',
            detail: 'Product quantities will be added back to available stock'
        };
    }
    if (willReduceStock.value) {
        return {
            type: 'info',
            message: '📦 Stock will be deducted from inventory',
            detail: 'Product quantities will be subtracted from available stock'
        };
    }
    if (selectedStatus.value === 'delivered') {
        return {
            type: 'success',
            message: '✓ Payment status will be marked as paid',
            detail: 'Order will be automatically marked as paid upon delivery'
        };
    }
    return null;
});

const confirmStatusChange = () => {
    if (!selectedStatus.value) return;
    emit('confirm', selectedStatus.value);
    selectedStatus.value = '';
};

const closeModal = () => {
    selectedStatus.value = '';
    emit('close');
};
</script>

<template>
    <Teleport to="body">
        <Transition name="modal">
            <div
                v-if="show"
                class="fixed inset-0 z-50 overflow-y-auto"
                @click.self="closeModal"
            >
                <!-- Backdrop -->
                <div
                    class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"
                    @click="closeModal"
                ></div>

                <!-- Modal -->
                <div class="flex min-h-screen items-center justify-center p-4">
                    <div
                        class="relative bg-white rounded-2xl shadow-2xl max-w-md w-full transform transition-all"
                        @click.stop
                    >
                        <!-- Header -->
                        <div class="bg-gradient-to-r from-blue-600 to-blue-700 rounded-t-2xl px-6 py-4">
                            <h3 class="text-lg font-semibold text-white flex items-center gap-2">
                                <Package class="w-5 h-5" />
                                Change Order Status
                            </h3>
                            <p class="text-blue-100 text-sm mt-1">{{ orderNumber }}</p>
                        </div>

                        <!-- Body -->
                        <div class="px-6 py-4 space-y-4">
                            <!-- Current Status -->
                            <div class="bg-gray-50 rounded-lg p-3">
                                <p class="text-xs text-gray-600 mb-1">Current Status</p>
                                <div class="flex items-center gap-2">
                                    <div
                                        class="w-3 h-3 rounded-full"
                                        :class="{
                                            'bg-blue-500': currentStatus === 'pending',
                                            'bg-orange-500': currentStatus === 'processing',
                                            'bg-amber-500': currentStatus === 'shipped',
                                            'bg-green-500': currentStatus === 'delivered',
                                            'bg-red-500': currentStatus === 'cancelled',
                                            'bg-purple-500': currentStatus === 'returned',
                                            'bg-gray-500': currentStatus === 'on_hold',
                                            'bg-indigo-500': currentStatus === 'confirmed',
                                        }"
                                    ></div>
                                    <span class="font-semibold text-gray-800 capitalize">
                                        {{ currentStatus }}
                                    </span>
                                </div>
                            </div>

                            <!-- Status Selection -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Change to:
                                </label>
                                <div class="space-y-2 max-h-64 overflow-y-auto">
                                    <button
                                        v-for="status in statusOptions"
                                        :key="status.value"
                                        type="button"
                                        @click="selectedStatus = status.value"
                                        :disabled="status.value === currentStatus"
                                        class="w-full text-left px-4 py-3 rounded-lg border-2 transition-all flex items-center justify-between group"
                                        :class="{
                                            'border-blue-500 bg-blue-50': selectedStatus === status.value,
                                            'border-gray-200 hover:border-gray-300 hover:bg-gray-50': selectedStatus !== status.value && status.value !== currentStatus,
                                            'border-gray-100 bg-gray-50 opacity-50 cursor-not-allowed': status.value === currentStatus,
                                        }"
                                    >
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="w-4 h-4 rounded-full"
                                                :class="{
                                                    'bg-blue-500': status.value === 'pending',
                                                    'bg-orange-500': status.value === 'processing',
                                                    'bg-amber-500': status.value === 'shipped',
                                                    'bg-green-500': status.value === 'delivered',
                                                    'bg-red-500': status.value === 'cancelled',
                                                    'bg-purple-500': status.value === 'returned',
                                                    'bg-gray-500': status.value === 'on_hold',
                                                    'bg-indigo-500': status.value === 'confirmed',
                                                }"
                                            ></div>
                                            <span class="font-medium text-gray-800">
                                                {{ status.label }}
                                            </span>
                                            <AlertTriangle
                                                v-if="status.warning"
                                                class="w-4 h-4 text-amber-500"
                                            />
                                        </div>
                                        <div
                                            v-if="selectedStatus === status.value"
                                            class="w-5 h-5 rounded-full bg-blue-500 flex items-center justify-center"
                                        >
                                            <CheckCircle class="w-3 h-3 text-white" />
                                        </div>
                                    </button>
                                </div>
                            </div>

                            <!-- Status Impact Warning -->
                            <div
                                v-if="statusImpact"
                                class="rounded-lg p-3 border"
                                :class="{
                                    'bg-amber-50 border-amber-200': statusImpact.type === 'warning',
                                    'bg-blue-50 border-blue-200': statusImpact.type === 'info',
                                    'bg-green-50 border-green-200': statusImpact.type === 'success',
                                }"
                            >
                                <p
                                    class="text-sm font-semibold"
                                    :class="{
                                        'text-amber-800': statusImpact.type === 'warning',
                                        'text-blue-800': statusImpact.type === 'info',
                                        'text-green-800': statusImpact.type === 'success',
                                    }"
                                >
                                    {{ statusImpact.message }}
                                </p>
                                <p
                                    class="text-xs mt-1"
                                    :class="{
                                        'text-amber-700': statusImpact.type === 'warning',
                                        'text-blue-700': statusImpact.type === 'info',
                                        'text-green-700': statusImpact.type === 'success',
                                    }"
                                >
                                    {{ statusImpact.detail }}
                                </p>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="px-6 py-4 bg-gray-50 rounded-b-2xl flex gap-3">
                            <button
                                @click="closeModal"
                                class="flex-1 px-4 py-2.5 bg-white border border-gray-300 text-gray-700 rounded-lg font-medium hover:bg-gray-50 transition-colors"
                            >
                                Cancel
                            </button>
                            <button
                                @click="confirmStatusChange"
                                :disabled="!selectedStatus || selectedStatus === currentStatus"
                                class="flex-1 px-4 py-2.5 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-lg font-medium hover:from-blue-700 hover:to-blue-800 disabled:opacity-50 disabled:cursor-not-allowed transition-all shadow-sm"
                            >
                                Confirm Change
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}

.modal-enter-active .relative,
.modal-leave-active .relative {
    transition: transform 0.3s ease;
}

.modal-enter-from .relative {
    transform: scale(0.95) translateY(-20px);
}

.modal-leave-to .relative {
    transform: scale(0.95) translateY(20px);
}
</style>
