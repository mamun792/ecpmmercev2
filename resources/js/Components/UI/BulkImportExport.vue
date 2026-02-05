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
            <div @click.stop class="bg-white rounded-2xl shadow-2xl max-w-3xl w-full overflow-hidden max-h-[90vh] flex flex-col">
                <!-- Header -->
                <div class="bg-gradient-to-r from-emerald-600 to-teal-600 p-6">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <component :is="mode === 'import' ? Upload : Download" class="w-6 h-6 text-white" />
                            <h3 class="text-xl font-bold text-white">
                                {{ mode === 'import' ? 'Import Products' : 'Export Products' }}
                            </h3>
                        </div>
                        <button @click="close" class="text-white/80 hover:text-white transition-colors">
                            <XIcon class="w-5 h-5" />
                        </button>
                    </div>
                    <p class="text-white/90 text-sm mt-1">
                        {{ mode === 'import' ? 'Upload a CSV file to import products in bulk' : 'Download products as CSV file' }}
                    </p>
                </div>

                <!-- Content -->
                <div class="flex-1 overflow-y-auto p-6">
                    <!-- Import Mode -->
                    <div v-if="mode === 'import'" class="space-y-6">
                        <!-- Instructions -->
                        <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
                            <div class="flex gap-3">
                                <Info class="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" />
                                <div>
                                    <h4 class="text-sm font-semibold text-blue-900 mb-2">CSV Format Requirements</h4>
                                    <ul class="text-sm text-blue-700 space-y-1">
                                        <li>• <strong>Required columns:</strong> name, category_id, price, stock</li>
                                        <li>• <strong>Optional columns:</strong> product_code, brand_id, description, cost_price</li>
                                        <li>• First row must be column headers</li>
                                        <li>• Use UTF-8 encoding</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Download Template -->
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl border border-gray-200">
                            <div class="flex items-center gap-3">
                                <FileDown class="w-5 h-5 text-gray-600" />
                                <div>
                                    <p class="text-sm font-semibold text-gray-900">Download CSV Template</p>
                                    <p class="text-xs text-gray-500">Get a sample file with correct format</p>
                                </div>
                            </div>
                            <button @click="downloadTemplate" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-sm font-semibold transition-all">
                                Download
                            </button>
                        </div>

                        <!-- File Upload -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Upload CSV File</label>
                            <div
                                @drop.prevent="handleDrop"
                                @dragover.prevent="isDragging = true"
                                @dragleave="isDragging = false"
                                :class="[
                                    'border-2 border-dashed rounded-xl p-8 text-center transition-all cursor-pointer',
                                    isDragging ? 'border-emerald-500 bg-emerald-50' : 'border-gray-300 hover:border-emerald-400 hover:bg-emerald-50/50'
                                ]"
                                @click="$refs.fileInput.click()"
                            >
                                <Upload class="w-12 h-12 text-gray-400 mx-auto mb-3" />
                                <p class="text-sm font-semibold text-gray-700 mb-1">
                                    {{ uploadedFile ? uploadedFile.name : 'Click to upload or drag and drop' }}
                                </p>
                                <p class="text-xs text-gray-500">CSV files only (max 10MB)</p>
                            </div>
                            <input
                                ref="fileInput"
                                type="file"
                                accept=".csv"
                                @change="handleFileSelect"
                                class="hidden"
                            />
                        </div>

                        <!-- Preview -->
                        <div v-if="parsedData.length > 0" class="border border-gray-200 rounded-xl overflow-hidden">
                            <div class="bg-gray-50 px-4 py-3 border-b border-gray-200">
                                <h4 class="text-sm font-semibold text-gray-900">
                                    Preview ({{ parsedData.length }} products)
                                </h4>
                            </div>
                            <div class="overflow-x-auto max-h-60">
                                <table class="w-full text-sm">
                                    <thead class="bg-gray-50 sticky top-0">
                                        <tr>
                                            <th v-for="header in headers" :key="header" class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">
                                                {{ header }}
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(row, index) in parsedData.slice(0, 5)" :key="index" class="border-t border-gray-200">
                                            <td v-for="header in headers" :key="header" class="px-4 py-2 text-gray-700">
                                                {{ row[header] || '-' }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div v-if="parsedData.length > 5" class="bg-gray-50 px-4 py-2 border-t border-gray-200 text-xs text-gray-500 text-center">
                                Showing 5 of {{ parsedData.length }} rows
                            </div>
                        </div>

                        <!-- Progress -->
                        <div v-if="importing" class="space-y-3">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-700">Importing products...</span>
                                <span class="font-semibold text-gray-900">{{ importProgress }}%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2 overflow-hidden">
                                <div class="bg-gradient-to-r from-emerald-500 to-teal-500 h-full transition-all duration-300" :style="{ width: importProgress + '%' }"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Export Mode -->
                    <div v-else class="space-y-6">
                        <!-- Options -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-3">Export Options</label>
                            <div class="space-y-2">
                                <label class="flex items-center gap-2 p-3 bg-gray-50 rounded-lg cursor-pointer hover:bg-gray-100 transition-all">
                                    <input v-model="exportOptions.includeVariations" type="checkbox" class="w-4 h-4 text-emerald-600 rounded" />
                                    <span class="text-sm text-gray-700">Include product variations</span>
                                </label>
                                <label class="flex items-center gap-2 p-3 bg-gray-50 rounded-lg cursor-pointer hover:bg-gray-100 transition-all">
                                    <input v-model="exportOptions.includeImages" type="checkbox" class="w-4 h-4 text-emerald-600 rounded" />
                                    <span class="text-sm text-gray-700">Include image URLs</span>
                                </label>
                                <label class="flex items-center gap-2 p-3 bg-gray-50 rounded-lg cursor-pointer hover:bg-gray-100 transition-all">
                                    <input v-model="exportOptions.publishedOnly" type="checkbox" class="w-4 h-4 text-emerald-600 rounded" />
                                    <span class="text-sm text-gray-700">Published products only</span>
                                </label>
                            </div>
                        </div>

                        <!-- Info -->
                        <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-4">
                            <div class="flex gap-3">
                                <Check class="w-5 h-5 text-emerald-600 flex-shrink-0 mt-0.5" />
                                <div>
                                    <p class="text-sm text-emerald-700">
                                        Your CSV file will include all product data according to the selected options.
                                        You can edit the file and re-import it later.
                                    </p>
                                </div>
                            </div>
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
                            v-if="mode === 'import'"
                            @click="startImport"
                            :disabled="!uploadedFile || importing"
                            class="px-6 py-2.5 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white rounded-xl font-semibold shadow-lg shadow-emerald-500/25 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <Upload class="w-4 h-4 inline mr-1" />
                            {{ importing ? 'Importing...' : 'Import Products' }}
                        </button>
                        <button
                            v-else
                            @click="startExport"
                            :disabled="exporting"
                            class="px-6 py-2.5 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white rounded-xl font-semibold shadow-lg shadow-emerald-500/25 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <Download class="w-4 h-4 inline mr-1" />
                            {{ exporting ? 'Exporting...' : 'Export Products' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </transition>
</template>

<script setup>
import { ref } from 'vue';
import { XIcon, Upload, Download, FileDown, Info, Check } from 'lucide-vue-next';

const props = defineProps({
    show: {
        type: Boolean,
        default: false
    },
    mode: {
        type: String,
        default: 'import', // 'import' or 'export'
        validator: (value) => ['import', 'export'].includes(value)
    }
});

const emit = defineEmits(['close', 'import', 'export']);

const fileInput = ref(null);
const uploadedFile = ref(null);
const isDragging = ref(false);
const parsedData = ref([]);
const headers = ref([]);
const importing = ref(false);
const exporting = ref(false);
const importProgress = ref(0);

const exportOptions = ref({
    includeVariations: true,
    includeImages: true,
    publishedOnly: false
});

const close = () => {
    emit('close');
    resetState();
};

const resetState = () => {
    uploadedFile.value = null;
    parsedData.value = [];
    headers.value = [];
    importing.value = false;
    exporting.value = false;
    importProgress.value = 0;
};

const handleFileSelect = (event) => {
    const file = event.target.files[0];
    if (file) {
        uploadedFile.value = file;
        parseCSV(file);
    }
};

const handleDrop = (event) => {
    isDragging.value = false;
    const file = event.dataTransfer.files[0];
    if (file && file.name.endsWith('.csv')) {
        uploadedFile.value = file;
        parseCSV(file);
    }
};

const parseCSV = (file) => {
    const reader = new FileReader();
    reader.onload = (e) => {
        const text = e.target.result;
        const lines = text.split('\n').filter(line => line.trim());

        if (lines.length > 0) {
            headers.value = lines[0].split(',').map(h => h.trim());
            parsedData.value = lines.slice(1).map(line => {
                const values = line.split(',');
                const row = {};
                headers.value.forEach((header, index) => {
                    row[header] = values[index]?.trim() || '';
                });
                return row;
            });
        }
    };
    reader.readAsText(file);
};

const downloadTemplate = () => {
    const template = 'name,product_code,category_id,brand_id,price,cost_price,stock,description,status\n' +
                     'Sample Product,SKU-001,1,1,999.99,499.99,100,Product description here,Published\n';

    const blob = new Blob([template], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'product_import_template.csv';
    a.click();
    window.URL.revokeObjectURL(url);
};

const startImport = () => {
    importing.value = true;
    importProgress.value = 0;

    // Simulate import progress
    const interval = setInterval(() => {
        importProgress.value += 10;
        if (importProgress.value >= 100) {
            clearInterval(interval);
            importing.value = false;
            emit('import', parsedData.value);
            close();
        }
    }, 300);
};

const startExport = () => {
    exporting.value = true;

    // In production, this would call the backend API
    setTimeout(() => {
        emit('export', exportOptions.value);
        exporting.value = false;
        close();
    }, 1000);
};
</script>
