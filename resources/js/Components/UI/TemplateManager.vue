<template>
    <transition
        enter-active-class="transition ease-out duration-200"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition ease-in duration-150"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" @click="close">
            <div @click.stop class="bg-white rounded-2xl shadow-2xl max-w-4xl w-full overflow-hidden max-h-[90vh] flex flex-col">
                <!-- Header -->
                <div class="bg-gradient-to-r from-indigo-600 to-purple-600 p-6">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <component :is="mode === 'save' ? Save : FileText" class="w-6 h-6 text-white" />
                            <h3 class="text-xl font-bold text-white">
                                {{ mode === 'save' ? 'Save as Template' : 'Load Template' }}
                            </h3>
                        </div>
                        <button @click="close" class="text-white/80 hover:text-white transition-colors">
                            <XIcon class="w-5 h-5" />
                        </button>
                    </div>
                    <p class="text-white/90 text-sm mt-1">
                        {{ mode === 'save' ? 'Save current product data as reusable template' : 'Choose a template to populate the form' }}
                    </p>
                </div>

                <!-- Content -->
                <div class="flex-1 overflow-y-auto p-6">
                    <!-- Save Mode -->
                    <div v-if="mode === 'save'" class="space-y-6">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Template Name <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="templateName"
                                type="text"
                                placeholder="e.g., T-Shirt Template, Electronics Product"
                                class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl text-gray-900 focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Description
                            </label>
                            <textarea
                                v-model="templateDescription"
                                rows="3"
                                placeholder="Describe when to use this template..."
                                class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl text-gray-900 focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all resize-none"
                            ></textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-3">Fields to Include</label>
                            <div class="grid grid-cols-2 gap-3">
                                <label v-for="field in availableFields" :key="field.key" class="flex items-center gap-2 p-3 bg-gray-50 rounded-lg cursor-pointer hover:bg-gray-100 transition-all">
                                    <input v-model="selectedFields" :value="field.key" type="checkbox" class="w-4 h-4 text-indigo-600 rounded" />
                                    <span class="text-sm text-gray-700">{{ field.label }}</span>
                                </label>
                            </div>
                        </div>

                        <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
                            <div class="flex gap-3">
                                <Info class="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" />
                                <div>
                                    <p class="text-sm text-blue-700">
                                        <strong>Tip:</strong> Templates save field values but not images. You'll need to upload images separately when using a template.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Load Mode -->
                    <div v-else class="space-y-4">
                        <!-- Search -->
                        <div class="relative">
                            <Search class="w-5 h-5 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" />
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Search templates..."
                                class="w-full pl-10 pr-4 py-3 border-2 border-gray-200 rounded-xl text-gray-900 focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all"
                            />
                        </div>

                        <!-- Templates Grid -->
                        <div v-if="filteredTemplates.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div
                                v-for="template in filteredTemplates"
                                :key="template.id"
                                @click="selectTemplate(template)"
                                :class="[
                                    'p-4 rounded-xl border-2 cursor-pointer transition-all',
                                    selectedTemplate?.id === template.id
                                        ? 'border-indigo-500 bg-indigo-50'
                                        : 'border-gray-200 hover:border-indigo-300 hover:bg-indigo-50/50'
                                ]"
                            >
                                <div class="flex items-start justify-between mb-2">
                                    <div class="flex items-center gap-2">
                                        <FileText class="w-5 h-5 text-indigo-600" />
                                        <h4 class="font-semibold text-gray-900">{{ template.name }}</h4>
                                    </div>
                                    <button
                                        @click.stop="deleteTemplate(template.id)"
                                        class="text-gray-400 hover:text-red-600 transition-colors"
                                    >
                                        <Trash2 class="w-4 h-4" />
                                    </button>
                                </div>
                                <p class="text-sm text-gray-600 mb-3">{{ template.description }}</p>
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-gray-500">{{ template.fields_count }} fields</span>
                                    <span class="text-gray-400">{{ template.created_at }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Empty State -->
                        <div v-else class="text-center py-12">
                            <FileText class="w-16 h-16 text-gray-300 mx-auto mb-4" />
                            <p class="text-gray-500 font-medium mb-1">No templates found</p>
                            <p class="text-sm text-gray-400">
                                {{ searchQuery ? 'Try a different search term' : 'Create your first template to get started' }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Footer Actions -->
                <div class="border-t border-gray-200 p-6 bg-gray-50">
                    <div class="flex items-center justify-between gap-3">
                        <button @click="close" class="px-4 py-2.5 bg-white hover:bg-gray-100 text-gray-700 border border-gray-300 rounded-xl font-semibold transition-all">
                            Cancel
                        </button>
                        <button
                            v-if="mode === 'save'"
                            @click="saveTemplate"
                            :disabled="!templateName || selectedFields.length === 0"
                            class="px-6 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white rounded-xl font-semibold shadow-lg shadow-indigo-500/25 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <Save class="w-4 h-4 inline mr-1" />
                            Save Template
                        </button>
                        <button
                            v-else
                            @click="loadTemplate"
                            :disabled="!selectedTemplate"
                            class="px-6 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white rounded-xl font-semibold shadow-lg shadow-indigo-500/25 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <Check class="w-4 h-4 inline mr-1" />
                            Load Template
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </transition>
</template>

<script setup>
import { ref, computed } from 'vue';
import { XIcon, Save, FileText, Info, Search, Trash2, Check } from 'lucide-vue-next';

const props = defineProps({
    show: {
        type: Boolean,
        default: false
    },
    mode: {
        type: String,
        default: 'load', // 'save' or 'load'
        validator: (value) => ['save', 'load'].includes(value)
    },
    currentData: {
        type: Object,
        default: () => ({})
    }
});

const emit = defineEmits(['close', 'save', 'load']);

// Save mode state
const templateName = ref('');
const templateDescription = ref('');
const selectedFields = ref(['category_id', 'brand_id', 'type', 'status']);

const availableFields = [
    { key: 'category_id', label: 'Category' },
    { key: 'brand_id', label: 'Brand' },
    { key: 'type', label: 'Product Type' },
    { key: 'status', label: 'Status' },
    { key: 'price', label: 'Price' },
    { key: 'cost_price', label: 'Cost Price' },
    { key: 'short_description', label: 'Short Description' },
    { key: 'description', label: 'Full Description' },
];

// Load mode state
const searchQuery = ref('');
const selectedTemplate = ref(null);

// Mock templates - in production, fetch from API
const templates = ref([
    {
        id: 1,
        name: 'T-Shirt Template',
        description: 'Standard template for clothing items with common attributes',
        fields_count: 6,
        created_at: '2 days ago',
        data: {
            category_id: 1,
            type: 'variable',
            status: 'Published'
        }
    },
    {
        id: 2,
        name: 'Electronics Product',
        description: 'Template for electronic devices with pricing structure',
        fields_count: 8,
        created_at: '1 week ago',
        data: {
            category_id: 5,
            type: 'simple',
            status: 'Published'
        }
    },
    {
        id: 3,
        name: 'Food & Beverage',
        description: 'Perishable products with expiry tracking',
        fields_count: 5,
        created_at: '2 weeks ago',
        data: {
            category_id: 3,
            type: 'simple',
            status: 'Published'
        }
    }
]);

const filteredTemplates = computed(() => {
    if (!searchQuery.value) return templates.value;
    
    const query = searchQuery.value.toLowerCase();
    return templates.value.filter(t =>
        t.name.toLowerCase().includes(query) ||
        t.description.toLowerCase().includes(query)
    );
});

const close = () => {
    emit('close');
    resetState();
};

const resetState = () => {
    templateName.value = '';
    templateDescription.value = '';
    selectedFields.value = ['category_id', 'brand_id', 'type', 'status'];
    searchQuery.value = '';
    selectedTemplate.value = null;
};

const saveTemplate = () => {
    const templateData = {
        name: templateName.value,
        description: templateDescription.value,
        fields: selectedFields.value,
        data: {}
    };
    
    // Extract selected field values from current form data
    selectedFields.value.forEach(field => {
        if (props.currentData[field]) {
            templateData.data[field] = props.currentData[field];
        }
    });
    
    emit('save', templateData);
    close();
};

const selectTemplate = (template) => {
    selectedTemplate.value = template;
};

const loadTemplate = () => {
    if (selectedTemplate.value) {
        emit('load', selectedTemplate.value.data);
        close();
    }
};

const deleteTemplate = (templateId) => {
    if (confirm('Are you sure you want to delete this template?')) {
        const index = templates.value.findIndex(t => t.id === templateId);
        if (index > -1) {
            templates.value.splice(index, 1);
        }
    }
};
</script>
