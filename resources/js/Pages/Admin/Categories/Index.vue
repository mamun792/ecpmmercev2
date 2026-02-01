<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { defineProps, ref, watch } from "vue";
import { Link, Head } from "@inertiajs/vue3";
import CategoryTree from "@/Components/Categories/CategoryTree.vue";

const props = defineProps({
    categories: Array,
});

// Local reactive state for categories
const localCategories = ref([...props.categories]);

// Watch for changes in props.categories (refreshes after Inertia requests/deletions)
watch(
    () => props.categories,
    (newCategories) => {
        localCategories.value = [...newCategories];
    },
    { deep: true }
);

// Function to update category status recursively
const updateCategoryStatus = (categoryArray, id, status) => {
    return categoryArray.map((category) => {
        if (category.id === id) {
            return { ...category, status };
        }
        if (category.children && category.children.length) {
            return {
                ...category,
                children: updateCategoryStatus(category.children, id, status),
            };
        }
        return category;
    });
};

// Function to update category order recursively
const updateCategoryOrder = (categoryArray, id, order) => {
    return categoryArray.map((category) => {
        if (category.id === id) {
            return { ...category, order };
        }
        if (category.children && category.children.length) {
            return {
                ...category,
                children: updateCategoryOrder(category.children, id, order),
            };
        }
        return category;
    });
};

// Handle status update event from CategoryTree
const handleStatusUpdate = ({ id, status }) => {
    localCategories.value = updateCategoryStatus(
        localCategories.value,
        id,
        status
    );
};

// Handle order update event from CategoryTree
const handleOrderUpdate = ({ id, order }) => {
    localCategories.value = updateCategoryOrder(
        localCategories.value,
        id,
        order
    );
};
</script>

<template>
    <Head title="Categories" />
    <AdminLayout>
        <div class="p-6 bg-white shadow-md rounded-md">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-gray-800">Categories</h2>
                <Link
                    :href="route('admin.categories.create')"
                    class="btn-primary"
                >
                    Add Category
                </Link>
            </div>

            <div v-if="localCategories.length" class="space-y-4">
                <!-- Tree Header (Optional but helps with alignment) -->
                <div
                    class="hidden md:flex items-center px-6 py-3 bg-gray-50 rounded-lg text-sm font-semibold text-gray-600 border border-gray-100 mb-2"
                >
                    <div class="flex-1">Category Details</div>
                    <div class="w-32 text-center">Status</div>
                    <div class="w-24 text-center">Order</div>
                    <div class="w-40 text-right">Actions</div>
                </div>

                <div class="category-tree-container">
                    <CategoryTree
                        v-for="category in localCategories"
                        :key="category.id"
                        :category="category"
                        :level="0"
                        @status-updated="handleStatusUpdate"
                        @order-updated="handleOrderUpdate"
                    />
                </div>
            </div>
            <div
                v-else
                class="flex flex-col items-center justify-center py-20 bg-gray-50 rounded-xl border-2 border-dashed border-gray-200"
            >
                <div class="text-gray-400 mb-4">
                    <svg
                        class="w-16 h-16"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.5"
                            d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"
                        />
                    </svg>
                </div>
                <p class="text-gray-500 text-lg font-medium">
                    No categories found yet.
                </p>
                <Link
                    :href="route('admin.categories.create')"
                    class="mt-4 text-blue-600 hover:text-blue-700 font-semibold"
                >
                    Create your first category →
                </Link>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
.category-tree-container {
    @apply bg-white rounded-xl overflow-hidden border border-gray-100;
}
</style>
