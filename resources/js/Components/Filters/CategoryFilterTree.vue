<script setup>
import { ref } from 'vue';
import { ChevronRight, ChevronDown } from 'lucide-vue-next';

const props = defineProps({
    categories: {
        type: Array,
        required: true
    },
    selected: {
        type: String,
        default: ''
    },
    level: {
        type: Number,
        default: 0
    }
});

const emit = defineEmits(['select']);

const expandedCategories = ref(new Set());

const toggleExpand = (categoryId, event) => {
    event.stopPropagation();
    if (expandedCategories.value.has(categoryId)) {
        expandedCategories.value.delete(categoryId);
    } else {
        expandedCategories.value.add(categoryId);
    }
    expandedCategories.value = new Set(expandedCategories.value);
};

const isExpanded = (categoryId) => {
    return expandedCategories.value.has(categoryId);
};

const hasChildren = (category) => {
    return category.children && category.children.length > 0;
};

const selectCategory = (slug) => {
    emit('select', slug);
};
</script>

<template>
    <div class="space-y-1">
        <div
            v-for="category in categories"
            :key="category.id"
        >
            <div
                :class="[
                    'flex items-center gap-2 py-2 px-2 rounded-lg cursor-pointer transition-colors',
                    selected === category.slug ? 'bg-primary/10 text-primary' : 'hover:bg-gray-100 text-gray-700'
                ]"
                :style="{ paddingLeft: `${level * 12 + 8}px` }"
                @click="selectCategory(category.slug)"
            >
                <!-- Expand/Collapse button -->
                <button
                    v-if="hasChildren(category)"
                    @click="toggleExpand(category.id, $event)"
                    class="flex-shrink-0 w-5 h-5 flex items-center justify-center rounded hover:bg-gray-200 transition-colors"
                >
                    <ChevronDown
                        v-if="isExpanded(category.id)"
                        class="w-4 h-4"
                    />
                    <ChevronRight
                        v-else
                        class="w-4 h-4"
                    />
                </button>
                <div v-else class="w-5 flex-shrink-0"></div>

                <!-- Category name -->
                <span class="text-sm flex-1 truncate">{{ category.name }}</span>

                <!-- Product count (optional) -->
                <span v-if="category.products_count !== undefined" class="text-xs text-gray-400 flex-shrink-0">
                    ({{ category.products_count }})
                </span>
            </div>

            <!-- Children categories (recursive) -->
            <div
                v-if="hasChildren(category) && isExpanded(category.id)"
                class="mt-1"
            >
                <CategoryFilterTree
                    :categories="category.children"
                    :selected="selected"
                    :level="level + 1"
                    @select="emit('select', $event)"
                />
            </div>
        </div>

        <!-- Show "All Categories" option at root level -->
        <div
            v-if="level === 0"
            :class="[
                'flex items-center gap-2 py-2 px-2 rounded-lg cursor-pointer transition-colors mt-3 border-t pt-3',
                !selected ? 'bg-primary/10 text-primary' : 'hover:bg-gray-100 text-gray-700'
            ]"
            @click="selectCategory('')"
        >
            <div class="w-5 flex-shrink-0"></div>
            <span class="text-sm font-medium">All Categories</span>
        </div>
    </div>
</template>
