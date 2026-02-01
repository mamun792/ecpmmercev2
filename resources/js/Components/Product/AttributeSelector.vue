<template>
    <div class="space-y-4">
        <div
            v-for="(attribute, index) in attributes"
            :key="attribute.id || attribute.name"
            class="bg-white/70 backdrop-blur-sm rounded-2xl border border-gray-200/50 shadow-sm overflow-hidden"
        >
            <div class="p-5 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white font-bold">
                        {{ attribute.name.charAt(0).toUpperCase() }}
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-gray-900">{{ attribute.name }}</h3>
                        <p class="text-xs text-gray-500">
                            {{ getSelectedCount(attribute.name) }} of {{ attribute.values?.length || 0 }} selected
                        </p>
                    </div>
                </div>
            </div>
            <div class="p-5">
                <div v-if="attribute.values && attribute.values.length > 0" class="flex flex-wrap gap-2">
                    <div v-for="value in attribute.values" :key="value.id">
                        <input
                            :id="'attr-' + value.id"
                            type="checkbox"
                            :value="value.id"
                            v-model="selectedAttributesMap[attribute.name]"
                            class="peer sr-only"
                        />
                        <label
                            :for="'attr-' + value.id"
                            class="cursor-pointer inline-flex items-center px-4 py-2.5 rounded-xl border-2 bg-white text-gray-700 hover:bg-gray-50 border-gray-200 peer-checked:border-indigo-500 peer-checked:bg-indigo-50 peer-checked:text-indigo-700 transition-all select-none text-sm font-medium shadow-sm hover:shadow"
                        >
                            <Check class="w-4 h-4 mr-1.5 opacity-0 peer-checked:opacity-100 transition-opacity" />
                            {{ value.value }}
                            <span
                                v-if="value.color"
                                class="w-4 h-4 rounded-full ml-2 border border-gray-300"
                                :style="{ backgroundColor: value.color }"
                            ></span>
                        </label>
                    </div>
                </div>
                <div v-else class="text-center py-6 text-gray-500">
                    <span class="text-sm">No values available for this attribute</span>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div v-if="attributes.length === 0" class="text-center py-12">
            <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-gray-100 flex items-center justify-center">
                <Layers class="w-8 h-8 text-gray-400" />
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">No Attributes Available</h3>
            <p class="text-gray-600 mb-4">Create some attributes first to add product variations.</p>
            <button
                type="button"
                class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 transition-colors"
            >
                <PlusIcon class="w-4 h-4 mr-2" />
                Create Attribute
            </button>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import { Check, Layers, PlusIcon } from 'lucide-vue-next'

const props = defineProps({
    attributes: {
        type: Array,
        default: () => []
    },
    selectedAttributesMap: {
        type: Object,
        default: () => ({})
    }
})

const emit = defineEmits(['update:selectedAttributesMap'])

// Computed to get selected count for an attribute
const getSelectedCount = (attributeName) => {
    return props.selectedAttributesMap[attributeName]?.length || 0
}

// Watch for changes and emit to parent
const selectedAttributesMap = computed({
    get() {
        return props.selectedAttributesMap
    },
    set(value) {
        emit('update:selectedAttributesMap', value)
    }
})
</script>
