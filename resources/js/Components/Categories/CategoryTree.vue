<script setup>
import { defineProps, ref, defineEmits } from "vue";
import { Link } from "@inertiajs/vue3";
import {
    Trash2,
    Edit3,
    ChevronRight,
    ChevronDown,
    Plus,
    Image as ImageIcon,
    MoreVertical,
    LayoutGrid,
} from "lucide-vue-next";
import DeleteModal from "@/Components/Modal/DeleteModal.vue";
import axios from "axios";
import { toast } from "@steveyuowo/vue-hot-toast";

const props = defineProps({
    category: Object,
    level: {
        type: Number,
        default: 0,
    },
});

const emit = defineEmits(["status-updated", "order-updated"]);

const isExpanded = ref(true);
const showDeleteModal = ref(false);
const orderValue = ref(props.category.order);
const isHovered = ref(false);

const toggleExpand = () => {
    isExpanded.value = !isExpanded.value;
};

const openDeleteModal = () => {
    showDeleteModal.value = true;
};

const handleDeleteSuccess = () => {
    showDeleteModal.value = false;
};

const updateStatus = async (categoryId, newStatus) => {
    try {
        const response = await axios.put(
            route("admin.categories.updateStatus", categoryId),
            {
                status: newStatus,
            }
        );

        if (response.data.success) {
            emit("status-updated", { id: categoryId, status: newStatus });
            toast.success(response.data.message);
        }
    } catch (error) {
        console.error("Failed to update status:", error);
        toast.error(error.response?.data?.message || "Failed to update status");
    }
};

const updateOrder = async (categoryId) => {
    try {
        const response = await axios.put(
            route("admin.categories.updateOrder", categoryId),
            {
                order: orderValue.value,
            }
        );

        if (response.data.success) {
            emit("order-updated", { id: categoryId, order: orderValue.value });
            toast.success(response.data.message);
        }
    } catch (error) {
        console.error("Failed to update order:", error);
        toast.error(error.response?.data?.message || "Failed to update order");
        orderValue.value = props.category.order;
    }
};
</script>

<template>
    <div class="category-node" :class="{ 'is-root': level === 0 }">
        <!-- Category Row Item -->
        <div
            class="category-row group"
            :class="{ 'bg-blue-50/30': isHovered }"
            @mouseenter="isHovered = true"
            @mouseleave="isHovered = false"
        >
            <!-- Hierarchy Lines and Content -->
            <div
                class="flex items-center flex-1 min-w-0"
                :style="{ paddingLeft: `${level * 24}px` }"
            >
                <!-- Vertical guide lines for nested items -->
                <div
                    v-if="level > 0"
                    class="hierarchy-line"
                    v-for="i in level"
                    :key="i"
                    :style="{ left: `${(i - 1) * 24 + 12}px` }"
                ></div>

                <!-- Expand/Collapse Button -->
                <button
                    @click="toggleExpand"
                    class="w-6 h-6 flex items-center justify-center rounded-md transition-all duration-200 hover:bg-gray-200/50 mr-2 z-10"
                    :class="category.children?.length ? 'visible' : 'invisible'"
                >
                    <component
                        :is="isExpanded ? ChevronDown : ChevronRight"
                        class="w-4 h-4 text-gray-500"
                    />
                </button>

                <!-- Category Visuals (Icon/Image) -->
                <div class="relative flex-shrink-0 mr-3">
                    <div
                        class="w-10 h-10 rounded-lg overflow-hidden border border-gray-100 bg-gray-50 flex items-center justify-center shadow-sm group-hover:shadow transition-shadow duration-200"
                    >
                        <img
                            v-if="category.image"
                            :src="category.image"
                            class="w-full h-full object-cover transform transition-transform duration-300 group-hover:scale-110"
                            @error="(e) => (e.target.src = '')"
                        />
                        <ImageIcon v-else class="w-5 h-5 text-gray-300" />
                    </div>
                    <div
                        v-if="category.icon"
                        class="absolute -bottom-1 -right-1 w-5 h-5 rounded-full border-2 border-white bg-white shadow-sm overflow-hidden"
                    >
                        <img
                            :src="category.icon"
                            class="w-full h-full object-cover"
                        />
                    </div>
                </div>

                <!-- Name and Sub-info -->
                <div class="flex flex-col min-w-0">
                    <span
                        class="text-sm font-semibold text-gray-800 truncate group-hover:text-blue-600 transition-colors duration-200"
                    >
                        {{ category.name }}
                    </span>
                    <span
                        class="text-[10px] uppercase tracking-wider text-gray-400 font-bold"
                    >
                        ID: #{{ category.id }} •
                        {{ category.children?.length || 0 }} Subcategories
                    </span>
                </div>
            </div>

            <!-- Status Column -->
            <div
                class="hidden md:flex items-center justify-center w-32 shrink-0"
            >
                <button
                    @click="
                        updateStatus(
                            category.id,
                            category.status === 'active' ? 'deactive' : 'active'
                        )
                    "
                    class="status-pill transition-all duration-200"
                    :class="
                        category.status === 'active'
                            ? 'is-active'
                            : 'is-inactive'
                    "
                >
                    <span
                        class="w-1.5 h-1.5 rounded-full mr-2"
                        :class="
                            category.status === 'active'
                                ? 'bg-green-500'
                                : 'bg-gray-400'
                        "
                    ></span>
                    {{ category.status === "active" ? "Active" : "Inactive" }}
                </button>
            </div>

            <!-- Order Column -->
            <div
                class="hidden md:flex items-center justify-center w-24 shrink-0"
            >
                <div class="relative group/order">
                    <input
                        v-model.number="orderValue"
                        type="number"
                        min="0"
                        class="order-input"
                        @change="updateOrder(category.id)"
                    />
                    <LayoutGrid
                        class="absolute left-2 top-1/2 -translate-y-1/2 w-3 h-3 text-gray-300 group-focus-within/order:text-blue-400 transition-colors"
                    />
                </div>
            </div>

            <!-- Actions Column -->
            <div
                class="flex items-center justify-end w-40 shrink-0 space-x-1 px-4"
            >
                <Link
                    :href="
                        route('admin.categories.create', {
                            parent_id: category.id,
                        })
                    "
                    class="action-btn text-gray-400 hover:text-blue-600 hover:bg-blue-50"
                    title="Add Subcategory"
                >
                    <Plus class="w-4 h-4" />
                </Link>
                <Link
                    :href="route('admin.categories.edit', category.id)"
                    class="action-btn text-gray-400 hover:text-green-600 hover:bg-green-50"
                    title="Edit Category"
                >
                    <Edit3 class="w-4 h-4" />
                </Link>
                <button
                    @click="openDeleteModal"
                    class="action-btn text-gray-400 hover:text-red-600 hover:bg-red-50"
                    title="Delete Category"
                >
                    <Trash2 class="w-4 h-4" />
                </button>
            </div>
        </div>

        <!-- Recursive Children with Transition -->
        <transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0 -translate-y-2"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 -translate-y-2"
        >
            <div
                v-if="
                    isExpanded && category.children && category.children.length
                "
                class="category-children"
            >
                <CategoryTree
                    v-for="child in category.children"
                    :key="child.id"
                    :category="child"
                    :level="level + 1"
                    @status-updated="$emit('status-updated', $event)"
                    @order-updated="$emit('order-updated', $event)"
                />
            </div>
        </transition>

        <!-- Delete Modal -->
        <DeleteModal
            :item-id="category.id"
            item-name="category"
            route-name="admin.categories.destroy"
            v-model:visible="showDeleteModal"
            @deleted="handleDeleteSuccess"
        />
    </div>
</template>

<style scoped>
.category-node {
    @apply relative;
}

.category-row {
    @apply flex items-center px-4 py-3 transition-all duration-200 border-b border-gray-50 bg-white;
}

.category-row:last-child {
    @apply border-b-0;
}

.is-root > .category-row {
    @apply font-semibold;
}

.hierarchy-line {
    @apply absolute top-0 bottom-0 w-[1.5px] bg-gray-200 pointer-events-none transition-colors duration-300;
}

/* Horizontal connector for the current level */
.hierarchy-line:last-of-type::after {
    content: "";
    @apply absolute top-1/2 left-0 w-3 h-[1.5px] bg-gray-200 -translate-y-1/2 rounded-r-full;
}

.category-row:hover .hierarchy-line {
    @apply bg-blue-300;
}

.category-row:hover .hierarchy-line:last-of-type::after {
    @apply bg-blue-400;
}

.status-pill {
    @apply inline-flex items-center px-3 py-1 rounded-full text-xs font-medium border transition-all;
}

.status-pill.is-active {
    @apply bg-green-50 text-green-700 border-green-100 hover:bg-green-100;
}

.status-pill.is-inactive {
    @apply bg-gray-50 text-gray-600 border-gray-100 hover:bg-gray-100;
}

.order-input {
    @apply w-16 pl-7 pr-2 py-1 text-xs border border-gray-100 rounded-md focus:outline-none focus:border-blue-300 focus:ring-4 focus:ring-blue-50 transition-all text-center font-medium text-gray-600;
}

.action-btn {
    @apply p-2 rounded-lg transition-all duration-200;
}

.category-children {
    @apply bg-gray-50/10;
}

/* Custom shadow for row items */
.category-row:hover {
    z-index: 5;
    box-shadow: 0 4px 12px -2px rgba(0, 0, 0, 0.05);
}
</style>
