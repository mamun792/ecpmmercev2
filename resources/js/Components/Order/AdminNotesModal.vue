<template>
    <div v-if="visible" class="fixed inset-0 z-50 overflow-y-auto">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-black/60 backdrop-blur-sm transition-opacity" @click="$emit('close')"></div>

        <!-- Modal -->
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative w-full max-w-4xl transform overflow-hidden rounded-2xl bg-white shadow-2xl transition-all">
                <!-- Header -->
                <div class="bg-gradient-to-r from-blue-600 via-blue-700 to-indigo-700 px-6 py-5">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <div class="rounded-xl bg-white/20 p-3 backdrop-blur-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-white">Admin Notes</h3>
                                <div class="flex items-center gap-2 mt-1">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-semibold bg-white/20 text-white backdrop-blur-sm">
                                        Order {{ orderNumber }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <button
                            @click="$emit('close')"
                            class="rounded-xl p-2.5 text-white/80 transition-all hover:bg-white/20 hover:text-white hover:rotate-90 duration-300"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Body -->
                <div class="px-6 py-6 bg-gradient-to-br from-gray-50 to-white">
                    <!-- Current Customer Note (if exists) -->
                    <div v-if="customerNote && customerNote !== 'N/A'" class="mb-6 rounded-xl bg-gradient-to-r from-green-50 to-emerald-50 border-2 border-green-200 p-4 shadow-sm">
                        <div class="flex items-start gap-3">
                            <div class="flex-shrink-0 w-10 h-10 bg-green-500 rounded-lg flex items-center justify-center shadow-md">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-bold text-green-900 mb-1">Customer Note</p>
                                <p class="text-sm text-green-800 leading-relaxed">{{ customerNote }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Main Content Grid -->
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <!-- Left Side - Notes Editor -->
                        <div class="lg:col-span-2 space-y-4">
                            <div>
                                <label class="mb-3 flex items-center gap-2 text-sm font-bold text-gray-800">
                                    <div class="w-1 h-5 bg-gradient-to-b from-blue-600 to-blue-400 rounded-full"></div>
                                    Admin Notes
                                    <span class="text-red-500">*</span>
                                </label>
                                <textarea
                                    v-model="localNotes"
                                    rows="8"
                                    class="w-full rounded-xl border-2 border-gray-200 shadow-sm focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all resize-none"
                                    placeholder="Enter internal notes about this order...

Examples:
• Special delivery instructions
• Customer requests or concerns
• Payment issues or confirmations
• Follow-up actions needed"
                                    @keydown.meta.enter="handleSave"
                                    @keydown.ctrl.enter="handleSave"
                                ></textarea>
                                <div class="flex items-center justify-between mt-2">
                                    <p class="text-xs text-gray-500 flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                        </svg>
                                        <span class="font-medium">Tip:</span> Press Ctrl+Enter (Cmd+Enter) to save
                                    </p>
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-semibold" :class="characterCount > 500 ? 'text-amber-600' : 'text-gray-500'">{{ characterCount }} / 2000</span>
                                        <div class="w-20 h-1.5 bg-gray-200 rounded-full overflow-hidden">
                                            <div
                                                class="h-full transition-all rounded-full"
                                                :class="characterCount > 1500 ? 'bg-red-500' : characterCount > 1000 ? 'bg-amber-500' : 'bg-blue-500'"
                                                :style="{ width: Math.min((characterCount / 2000) * 100, 100) + '%' }"
                                            ></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Side - Quick Templates -->
                        <div class="lg:col-span-1">
                            <div class="sticky top-6">
                                <div class="rounded-xl border-2 border-gray-200 bg-white shadow-sm">
                                    <div class="bg-gradient-to-r from-gray-50 to-gray-100 px-4 py-3 border-b-2 border-gray-200 rounded-t-xl">
                                        <p class="text-sm font-bold text-gray-800 flex items-center gap-2">
                                            <svg class="w-4 h-4 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z" />
                                                <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd" />
                                            </svg>
                                            Quick Templates
                                        </p>
                                    </div>
                                    <div class="p-4 space-y-2 max-h-96 overflow-y-auto">
                                        <button
                                            v-for="template in quickTemplates"
                                            :key="template.label"
                                            @click="addTemplate(template.text)"
                                            class="w-full text-left rounded-lg bg-gradient-to-r from-white to-gray-50 px-3 py-2.5 text-xs font-medium text-gray-700 shadow-sm hover:shadow-md border-2 border-gray-200 hover:border-blue-400 hover:from-blue-50 hover:to-blue-100 hover:text-blue-700 transition-all group"
                                        >
                                            <div class="flex items-center gap-2">
                                                <span class="text-base group-hover:scale-125 transition-transform">{{ template.label.split(' ')[0] }}</span>
                                                <span class="flex-1">{{ template.label.substring(template.label.indexOf(' ') + 1) }}</span>
                                                <svg class="w-3.5 h-3.5 text-gray-400 group-hover:text-blue-600 opacity-0 group-hover:opacity-100 transition-all" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                                                </svg>
                                            </div>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="flex items-center justify-between gap-3 bg-gradient-to-r from-gray-100 to-gray-50 px-6 py-4 border-t-2 border-gray-200">
                    <div class="flex items-center gap-2 text-xs text-gray-600">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                        </svg>
                        <span>Last updated: <strong>{{ new Date().toLocaleString() }}</strong></span>
                    </div>
                    <div class="flex items-center gap-3">
                        <button
                            @click="$emit('close')"
                            class="rounded-xl px-5 py-2.5 text-sm font-semibold text-gray-700 bg-white border-2 border-gray-300 transition-all hover:bg-gray-100 hover:border-gray-400 shadow-sm hover:shadow"
                            :disabled="saving"
                        >
                            Cancel
                        </button>
                        <button
                            @click="handleSave"
                            :disabled="saving || !hasChanges"
                            class="rounded-xl bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-2.5 text-sm font-bold text-white shadow-lg transition-all hover:from-blue-700 hover:to-blue-800 hover:shadow-xl disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2 border-2 border-blue-800"
                        >
                            <svg v-if="saving" class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            {{ saving ? 'Saving...' : 'Save Notes' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { toast } from '@steveyuowo/vue-hot-toast';

const props = defineProps({
    visible: {
        type: Boolean,
        default: false,
    },
    orderId: {
        type: Number,
        required: true,
    },
    orderNumber: {
        type: String,
        required: true,
    },
    initialNotes: {
        type: String,
        default: '',
    },
    customerNote: {
        type: String,
        default: '',
    },
});

const emit = defineEmits(['close', 'saved']);

const localNotes = ref('');
const saving = ref(false);

// Quick templates for common admin notes
const quickTemplates = [
    { label: '📞 Called Customer', text: 'Called customer to confirm order details.' },
    { label: '🚚 Urgent Delivery', text: 'Customer requested urgent delivery.' },
    { label: '💰 Payment Pending', text: 'Waiting for payment confirmation.' },
    { label: '📦 Partial Stock', text: 'Some items out of stock, contacted customer.' },
    { label: '🔄 Address Changed', text: 'Customer updated delivery address.' },
    { label: '⚠️ Special Instructions', text: 'Special handling required: ' },
];

// Watch for prop changes
watch(() => props.visible, (newVal) => {
    if (newVal) {
        localNotes.value = props.initialNotes || '';
    }
});

watch(() => props.initialNotes, (newVal) => {
    if (props.visible) {
        localNotes.value = newVal || '';
    }
});

const characterCount = computed(() => localNotes.value.length);

const hasChanges = computed(() => {
    return localNotes.value !== (props.initialNotes || '');
});

const addTemplate = (templateText) => {
    if (localNotes.value) {
        localNotes.value += '\n' + templateText;
    } else {
        localNotes.value = templateText;
    }
};

const handleSave = async () => {
    if (saving.value || !hasChanges.value) return;

    saving.value = true;

    try {
        await router.put(
            `/admin/orders/${props.orderId}/admin-notes`,
            { admin_notes: localNotes.value },
            {
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => {
                    toast.success('Admin notes updated successfully');
                    emit('saved', localNotes.value);
                    emit('close');
                },
                onError: (errors) => {
                    console.error('Error updating admin notes:', errors);
                    toast.error('Failed to update admin notes');
                },
                onFinish: () => {
                    saving.value = false;
                },
            }
        );
    } catch (error) {
        console.error('Error:', error);
        toast.error('An error occurred while saving');
        saving.value = false;
    }
};
</script>
