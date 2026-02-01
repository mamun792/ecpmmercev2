<script setup>
import { Head, useForm, router } from "@inertiajs/vue3";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { toast } from "@steveyuowo/vue-hot-toast";
import { ref, watch, computed } from "vue";
import { 
  Plus, 
  Trash2, 
  Settings2, 
  Layers, 
  Palette, 
  CheckCircle2, 
  XCircle, 
  ChevronRight,
  LayoutGrid,
  Search,
  Filter
} from "lucide-vue-next";

const props = defineProps({
    attrList: Array,
    editingAttribute: Object,
});

// Search and Filter State
const searchSearch = ref("");
const filteredAttrs = computed(() => {
    if (!searchSearch.value) return props.attrList;
    return props.attrList.filter(attr => 
        attr.name.toLowerCase().includes(searchSearch.value.toLowerCase())
    );
});

// Toggle status function (Big Tech style)
const toggleStatus = (attributeId, currentStatus) => {
    const action = currentStatus === 'active' ? 'deactivate' : 'activate';
    
    router.put(
        route('admin.attributes.toggle-status', attributeId),
        {},
        {
            onSuccess: () => {
                toast.success(`Attribute ${action}d successfully`);
            },
            onError: () => {
                toast.error('Operation failed');
            },
        }
    );
};

const isEditing = ref(false);

const form = useForm({
    name: "",
    values: [],
});

watch(
    () => props.editingAttribute,
    (newValue) => {
        if (newValue) {
            isEditing.value = true;
            form.name = newValue.name;
            const nameLower = (newValue.name || "").toString().trim().toLowerCase();
            const isColor = nameLower === "color" || nameLower === "colorus";
            form.values = newValue.values.map((v) => ({
                id: v.id,
                value: v.value,
                ...(isColor ? { color: v.color || "#000000" } : {})
            }));
        } else {
            isEditing.value = false;
            form.reset();
        }
    },
    { immediate: true }
);

const isColorAttribute = computed(() => {
    const name = (form.name || "").toString().trim().toLowerCase();
    return ["color", "colorus", "colour", "colors", "colours"].includes(name);
});

watch(isColorAttribute, (newVal) => {
    if (newVal) {
        form.values = form.values.map((v) => ({
            ...v,
            color: v.color || "#3B82F6",
        }));
    } else {
        form.values = form.values.map(({ color, ...rest }) => rest);
    }
});

const addValue = () => {
    if (isColorAttribute.value) {
        form.values.push({ value: "", color: "#3B82F6" });
    } else {
        form.values.push({ value: "" });
    }
};

const removeValue = (index) => {
    form.values.splice(index, 1);
};

const submitForm = () => {
    if (isEditing.value) {
        form.put(route("admin.attributes.update", props.editingAttribute.id), {
            onSuccess: () => {
                toast.success("Attribute updated successfully");
                form.reset();
            },
            onError: (errors) => {
                toast.error("Please fix the highlighted errors");
                console.error("Update failed:", errors);
            }
        });
    } else {
        form.post(route("admin.attributes.store"), {
            onSuccess: () => {
                toast.success("New attribute created");
                form.reset();
            },
            onError: (errors) => {
                toast.error("Failed to create attribute. Check form data.");
                console.error("Creation failed:", errors);
            }
        });
    }
};

const editAttr = (id) => {
    router.get(route("admin.attributes.edit", id));
};

const resetForm = () => {
    router.get(route("admin.attributes.index"), {}, {
        onSuccess: () => {
            isEditing.value = false;
            form.reset();
        }
    });
};
</script>

<template>
    <Head title="Manage Attributes" />
    <AdminLayout>
        <div class="min-h-screen bg-[#F8FAFC] dark:bg-gray-950 p-6 lg:p-10">
            <!-- Header Section -->
            <div class="max-w-[1400px] mx-auto mb-10">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h1 class="text-3xl font-extrabold text-gray-900 dark:text-white flex items-center gap-3">
                            <span class="p-2 bg-blue-600 rounded-xl shadow-lg shadow-blue-500/20">
                                <LayoutGrid class="w-7 h-7 text-white" />
                            </span>
                            Product Attributes
                        </h1>
                        <p class="text-gray-500 mt-2 dark:text-gray-400 font-medium">Define and manage product specifications like size, color, and more.</p>
                    </div>
                </div>
            </div>

            <div class="max-w-[1400px] mx-auto grid lg:grid-cols-12 gap-8">
                <!-- Left: Control Panel (Form) -->
                <div class="lg:col-span-4">
                    <div class="bg-white dark:bg-gray-900 rounded-3xl shadow-xl shadow-gray-200/50 dark:shadow-none border border-gray-100 dark:border-gray-800 sticky top-10">
                        <div class="p-8">
                            <div class="flex items-center justify-between mb-8">
                                <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                    {{ isEditing ? 'Update Attribute' : 'Create New' }}
                                </h2>
                                <button v-if="isEditing" @click="resetForm" class="text-sm text-blue-600 font-bold hover:underline">
                                    Clear Selection
                                </button>
                            </div>

                            <form @submit.prevent="submitForm" class="space-y-6">
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2 uppercase tracking-wider">Attribute Name</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                            <Settings2 class="h-5 w-5 text-gray-400" />
                                        </div>
                                        <input
                                            v-model="form.name"
                                            type="text"
                                            class="w-full pl-11 pr-4 py-3 bg-gray-50 dark:bg-gray-800 border-0 rounded-2xl ring-1 ring-gray-200 dark:ring-gray-700 focus:ring-2 focus:ring-blue-500 transition-all font-medium"
                                            placeholder="e.g., Color, Size"
                                            required
                                        />
                                    </div>
                                    <p v-if="form.errors.name" class="mt-2 text-sm text-red-500 font-bold">{{ form.errors.name }}</p>
                                </div>

                                <div class="space-y-4">
                                    <div class="flex items-center justify-between mb-4">
                                        <label class="text-sm font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Attribute Values</label>
                                        <button 
                                            type="button" 
                                            @click="addValue"
                                            class="text-xs font-extra-bold text-blue-600 flex items-center gap-1 hover:text-blue-700 bg-blue-50 dark:bg-blue-900/20 px-3 py-1.5 rounded-full transition-all"
                                        >
                                            <Plus class="w-3.5 h-3.5" />
                                            Add Value
                                        </button>
                                    </div>

                                    <div v-if="form.values.length === 0" class="text-center py-10 bg-gray-50 dark:bg-gray-800/50 rounded-2xl border-2 border-dashed border-gray-200 dark:border-gray-700">
                                        <div class="inline-flex p-3 bg-gray-100 dark:bg-gray-700 rounded-full mb-3">
                                            <Layers class="w-6 h-6 text-gray-400" />
                                        </div>
                                        <p class="text-sm text-gray-500 dark:text-gray-400 font-medium italic">No values added yet.</p>
                                    </div>

                                    <div class="space-y-3 max-h-[400px] overflow-y-auto pr-2 custom-scrollbar">
                                        <div 
                                            v-for="(value, index) in form.values" 
                                            :key="index"
                                            class="p-4 bg-gray-50 dark:bg-gray-800 rounded-2xl group transition-all relative border border-transparent hover:border-blue-100 dark:hover:border-blue-900 shadow-sm"
                                        >
                                            <div class="flex items-center gap-4">
                                                <!-- Value Input -->
                                                <div class="flex-grow">
                                                    <div class="relative">
                                                        <input
                                                            v-model="form.values[index].value"
                                                            type="text"
                                                            class="w-full bg-white dark:bg-gray-700/50 border-0 px-4 py-2.5 rounded-xl ring-1 ring-gray-200 dark:ring-gray-700 focus:ring-2 focus:ring-blue-500 font-bold text-gray-800 dark:text-gray-200 placeholder-gray-400 shadow-sm"
                                                            placeholder="Color name (e.g., Rose Red)"
                                                            required
                                                        />
                                                    </div>
                                                </div>

                                                <!-- Professional Color Interface -->
                                                <div v-if="isColorAttribute" class="flex items-center gap-3 bg-white dark:bg-gray-700/50 px-3 py-1.5 rounded-xl ring-1 ring-gray-200 dark:ring-gray-700 shadow-sm">
                                                    <div class="flex flex-col items-center">
                                                        <span class="text-[9px] font-black text-gray-400 uppercase tracking-tighter mb-0.5">Hex Code</span>
                                                        <input 
                                                            v-model="form.values[index].color"
                                                            type="text"
                                                            class="w-20 text-[11px] font-mono font-bold bg-transparent border-0 p-0 focus:ring-0 text-gray-700 dark:text-gray-300 uppercase"
                                                            maxlength="7"
                                                        />
                                                    </div>
                                                    <div class="relative group/picker">
                                                        <div 
                                                            :style="{ backgroundColor: form.values[index].color }" 
                                                            class="w-9 h-9 rounded-full shadow-lg border-2 border-white dark:border-gray-600 transition-all hover:scale-110 active:scale-95 cursor-pointer ring-2 ring-transparent group-hover/picker:ring-blue-100"
                                                            title="Custom Picker"
                                                        ></div>
                                                        <input
                                                            v-model="form.values[index].color"
                                                            type="color"
                                                            class="absolute inset-0 opacity-0 w-full h-full cursor-pointer"
                                                        />
                                                    </div>
                                                </div>

                                                <!-- Delete Button -->
                                                <button 
                                                    type="button"
                                                    @click="removeValue(index)"
                                                    class="p-2.5 text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-xl transition-all border border-transparent hover:border-red-100/50"
                                                    title="Remove Value"
                                                >
                                                    <Trash2 class="w-5 h-5" />
                                                </button>
                                            </div>

                                            <!-- Individual Error Display -->
                                            <p v-if="form.errors[`values.${index}.value`]" class="mt-2 text-[10px] text-red-500 font-extrabold uppercase tracking-widest px-1">
                                                {{ form.errors[`values.${index}.value`] }}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="w-full py-4 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white font-extrabold rounded-2xl shadow-xl shadow-blue-500/20 transition-all flex items-center justify-center gap-2 transform active:scale-95"
                                >
                                    <CheckCircle2 class="w-5 h-5" />
                                    {{ isEditing ? "Update Attribute" : "Create Attribute" }}
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Right: Attributes Explorer (Table) -->
                <div class="lg:col-span-8">
                    <div class="bg-white dark:bg-gray-900 rounded-3xl shadow-xl shadow-gray-200/50 dark:shadow-none border border-gray-100 dark:border-gray-800 overflow-hidden">
                        <!-- Table Toolbar -->
                        <div class="p-6 border-b border-gray-50 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-900/50 flex flex-col md:flex-row md:items-center justify-between gap-4">
                            <div class="relative flex-grow max-w-md">
                                <Search class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
                                <input 
                                    v-model="searchSearch"
                                    type="text" 
                                    placeholder="Search attributes..."
                                    class="w-full pl-12 pr-4 py-2.5 bg-white dark:bg-gray-800 border-0 rounded-xl ring-1 ring-gray-200 dark:ring-gray-700 focus:ring-2 focus:ring-blue-500 font-medium"
                                />
                            </div>
                            <div class="flex items-center gap-2">
                                <button class="p-2.5 bg-white dark:bg-gray-800 border-0 rounded-xl ring-1 ring-gray-200 dark:ring-gray-700 text-gray-500 hover:text-blue-600 transition-all">
                                    <Filter class="w-5 h-5" />
                                </button>
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left">
                                <thead>
                                    <tr class="bg-gray-50 dark:bg-gray-800/50">
                                        <th class="px-8 py-5 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Name</th>
                                        <th class="px-8 py-5 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Status</th>
                                        <th class="px-8 py-5 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Available Values</th>
                                        <th class="px-8 py-5 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                                    <tr v-for="attr in filteredAttrs" :key="attr.id" class="hover:bg-gray-50/80 dark:hover:bg-gray-800/40 transition-colors group">
                                        <td class="px-8 py-6">
                                            <div class="flex items-center gap-3">
                                                <div class="w-10 h-10 bg-blue-50 dark:bg-blue-900/20 rounded-xl flex items-center justify-center text-blue-600 dark:text-blue-400 font-bold border border-blue-100 dark:border-blue-800 shadow-sm">
                                                    {{ attr.name.charAt(0) }}
                                                </div>
                                                <span class="text-sm font-bold text-gray-900 dark:text-white">{{ attr.name }}</span>
                                            </div>
                                        </td>
                                        <td class="px-8 py-6">
                                            <div class="flex items-center gap-3">
                                                <button 
                                                    @click="toggleStatus(attr.id, attr.status)"
                                                    class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none ring-offset-2 focus:ring-2 focus:ring-blue-500"
                                                    :class="attr.status === 'active' ? 'bg-blue-600' : 'bg-gray-200 dark:bg-gray-700'"
                                                    :title="attr.status === 'active' ? 'Click to Deactivate' : 'Click to Activate'"
                                                >
                                                    <span 
                                                        aria-hidden="true" 
                                                        class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                                                        :class="attr.status === 'active' ? 'translate-x-5' : 'translate-x-0'"
                                                    ></span>
                                                </button>
                                                <span 
                                                    :class="[
                                                        'text-[10px] font-bold uppercase tracking-widest',
                                                        attr.status === 'active' ? 'text-blue-600 dark:text-blue-400' : 'text-gray-400'
                                                    ]"
                                                >
                                                    {{ attr.status }}
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-8 py-6">
                                            <div class="flex flex-wrap gap-2 max-w-[300px]">
                                                <div
                                                    v-for="value in attr.values"
                                                    :key="value.id"
                                                    class="group/pill inline-flex items-center gap-1.5 px-2.5 py-1 text-[11px] font-bold rounded-lg bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 border border-gray-100 dark:border-gray-700 shadow-sm transition-all hover:border-blue-200 dark:hover:border-blue-800"
                                                >
                                                    <span
                                                        v-if="value.color"
                                                        class="w-2.5 h-2.5 rounded-full shadow-inner border border-white/50"
                                                        :style="{ backgroundColor: value.color }"
                                                    ></span>
                                                    {{ value.value }}
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-8 py-6">
                                            <div class="flex items-center justify-end gap-2">
                                                <button 
                                                    @click="editAttr(attr.id)"
                                                    class="p-2.5 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-xl transition-all border border-transparent hover:border-blue-100 dark:hover:border-blue-800"
                                                >
                                                    <ChevronRight class="w-5 h-5" />
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-if="filteredAttrs.length === 0">
                                        <td colspan="4" class="px-8 py-20 text-center">
                                            <div class="max-w-[180px] mx-auto opacity-40 grayscale mb-4">
                                                <img src="https://static.thenounproject.com/png/5048256-200.png" alt="No data" />
                                            </div>
                                            <p class="text-gray-500 font-bold">No attributes found matching your search.</p>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #E2E8F0;
    border-radius: 10px;
}
.dark .custom-scrollbar::-webkit-scrollbar-thumb {
    background: #1E293B;
}
</style>
