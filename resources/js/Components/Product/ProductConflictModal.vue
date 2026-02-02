<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import { RefreshCw, Package, AlertTriangle, CheckCircle } from 'lucide-vue-next';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    conflictData: {
        type: Object,
        default: null,
    },
    formData: {
        type: Object,
        required: true,
    }
});

const emit = defineEmits(['close', 'restore', 'create-new']);

const isLoading = ref(false);

const handleRestore = async () => {
    if (!props.conflictData?.slug) return;

    isLoading.value = true;

    try {
        await router.post(route('admin.products.restore', props.conflictData.slug), {
            preserveScroll: true,
            onSuccess: () => {
                emit('restore');
                emit('close');
            },
            onError: (errors) => {
                console.error('Restore failed:', errors);
            },
            onFinish: () => {
                isLoading.value = false;
            }
        });
    } catch (error) {
        console.error('Restore error:', error);
        isLoading.value = false;
    }
};

const handleCreateNew = () => {
    emit('create-new');
    emit('close');
};

const formatDate = (dateString) => {
    if (!dateString) return 'Unknown';
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};
</script>

<template>
    <Modal :show="show" @close="$emit('close')" max-width="2xl" :closeable="!isLoading">
        <div class="relative bg-white rounded-2xl shadow-2xl overflow-hidden">
            <!-- Header -->
            <div class="bg-gradient-to-r from-amber-500 to-orange-500 px-6 py-4">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-white/20 rounded-lg">
                        <AlertTriangle class="h-6 w-6 text-white" />
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-white">Product Name Conflict</h2>
                        <p class="text-amber-100 text-sm">A product with similar name already exists</p>
                    </div>
                </div>
            </div>

            <!-- Content -->
            <div class="p-6 space-y-6">
                <!-- Current Product Info -->
                <div class="bg-blue-50 rounded-xl p-4 border border-blue-200">
                    <h3 class="font-semibold text-blue-900 mb-2 flex items-center gap-2">
                        <Package class="h-4 w-4" />
                        Product You're Creating
                    </h3>
                    <div class="text-sm text-blue-800">
                        <p><strong>Name:</strong> {{ formData.name }}</p>
                        <p v-if="formData.sku"><strong>SKU:</strong> {{ formData.sku }}</p>
                        <p><strong>Category:</strong> {{ formData.category_name || 'Not specified' }}</p>
                    </div>
                </div>

                <!-- Existing Product Info -->
                <div v-if="conflictData" class="bg-red-50 rounded-xl p-4 border border-red-200">
                    <h3 class="font-semibold text-red-900 mb-2 flex items-center gap-2">
                        <AlertTriangle class="h-4 w-4" />
                        Existing Product (Soft Deleted)
                    </h3>
                    <div class="text-sm text-red-800 space-y-1">
                        <p><strong>Name:</strong> {{ conflictData.name }}</p>
                        <p v-if="conflictData.sku"><strong>SKU:</strong> {{ conflictData.sku }}</p>
                        <p><strong>Category:</strong> {{ conflictData.category?.name || 'Not specified' }}</p>
                        <p><strong>Deleted:</strong> {{ formatDate(conflictData.deleted_at) }}</p>
                        <p v-if="conflictData.variations_count" class="text-xs text-red-600">
                            {{ conflictData.variations_count }} variations available for restore
                        </p>
                    </div>
                </div>

                <!-- Resolution Options -->
                <div class="border-t pt-6">
                    <h3 class="font-semibold text-gray-900 mb-4">Choose Resolution:</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Restore Option -->
                        <button
                            @click="handleRestore"
                            :disabled="isLoading"
                            class="group relative bg-gradient-to-r from-green-500 to-emerald-500 hover:from-green-600 hover:to-emerald-600
                                   text-white p-6 rounded-xl transition-all duration-200 transform hover:scale-105
                                   disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none"
                        >
                            <div class="flex items-center gap-3 mb-3">
                                <div class="p-2 bg-white/20 rounded-lg">
                                    <RefreshCw v-if="!isLoading" class="h-5 w-5" />
                                    <RefreshCw v-else class="h-5 w-5 animate-spin" />
                                </div>
                                <span class="font-semibold">Restore Existing Product</span>
                            </div>
                            <p class="text-sm text-green-100">
                                Restore the deleted product with all its data, variations, and history intact.
                            </p>
                            <div class="absolute inset-0 bg-white/10 opacity-0 group-hover:opacity-100 transition-opacity rounded-xl"></div>
                        </button>

                        <!-- Create New Option -->
                        <button
                            @click="handleCreateNew"
                            :disabled="isLoading"
                            class="group relative bg-gradient-to-r from-blue-500 to-indigo-500 hover:from-blue-600 hover:to-indigo-600
                                   text-white p-6 rounded-xl transition-all duration-200 transform hover:scale-105
                                   disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none"
                        >
                            <div class="flex items-center gap-3 mb-3">
                                <div class="p-2 bg-white/20 rounded-lg">
                                    <CheckCircle class="h-5 w-5" />
                                </div>
                                <span class="font-semibold">Create New Product</span>
                            </div>
                            <p class="text-sm text-blue-100">
                                Create a new product with a unique name (system will auto-generate unique slug).
                            </p>
                            <div class="absolute inset-0 bg-white/10 opacity-0 group-hover:opacity-100 transition-opacity rounded-xl"></div>
                        </button>
                    </div>
                </div>

                <!-- Big Tech Style Info -->
                <div class="bg-gray-50 rounded-lg p-4 text-center">
                    <p class="text-xs text-gray-600">
                        🚀 <strong>Big Tech Architecture:</strong> This intelligent conflict resolution ensures data integrity
                        while providing Amazon-style user experience for product management.
                    </p>
                </div>
            </div>

            <!-- Footer -->
            <div class="bg-gray-50 px-6 py-4 flex justify-between items-center">
                <p class="text-xs text-gray-500">
                    Smart duplicate handling powered by enterprise-grade conflict resolution
                </p>
                <button
                    v-if="!isLoading"
                    @click="$emit('close')"
                    class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800 transition-colors"
                >
                    Cancel
                </button>
            </div>
        </div>
    </Modal>
</template>
