<script setup>
import { Head, Link, router } from "@inertiajs/vue3";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { ref } from "vue";
import { toast } from "@steveyuowo/vue-hot-toast";
import DeleteModal from "@/Components/Modal/DeleteModal.vue";
import {
    Plus,
    Search,
    Edit,
    Trash2,
    CheckCircle,
    XCircle,
    Package,
} from "lucide-vue-next";

const props = defineProps({
    productGroups: Object,
});

const showDeleteModal = ref(false);
const groupToDelete = ref(null);

const toggleStatus = (id) => {
    router.put(
        route("admin.product-groups.status", id),
        {},
        {
            preserveScroll: true,
            onSuccess: () => toast.success("Status updated successfully"),
        },
    );
};

const openDeleteModal = (id) => {
    groupToDelete.value = id;
    showDeleteModal.value = true;
};

const handleDeleteSuccess = () => {
    showDeleteModal.value = false;
    groupToDelete.value = null;
};
</script>

<template>
    <Head title="Product Groups" />
    <AdminLayout>
        <div class="p-6">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1
                        class="text-2xl font-bold text-gray-800 dark:text-gray-100"
                    >
                        Product Groups
                    </h1>
                    <p class="text-gray-500 dark:text-gray-400 text-sm">
                        Manage groups of products for promotions or display.
                    </p>
                </div>
                <Link
                    :href="route('admin.product-groups.create')"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center gap-2 transition-colors"
                >
                    <Plus size="20" />
                    <span>Create Group</span>
                </Link>
            </div>

            <div
                class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden"
            >
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead
                            class="bg-gray-50 dark:bg-gray-900/50 border-b border-gray-100 dark:border-gray-700"
                        >
                            <tr>
                                <th
                                    class="px-6 py-4 text-sm font-semibold text-gray-600 dark:text-gray-300"
                                >
                                    Banner
                                </th>
                                <th
                                    class="px-6 py-4 text-sm font-semibold text-gray-600 dark:text-gray-300"
                                >
                                    Name
                                </th>
                                <th
                                    class="px-6 py-4 text-sm font-semibold text-gray-600 dark:text-gray-300 text-center"
                                >
                                    Order
                                </th>
                                <th
                                    class="px-6 py-4 text-sm font-semibold text-gray-600 dark:text-gray-300 text-center"
                                >
                                    Products
                                </th>
                                <th
                                    class="px-6 py-4 text-sm font-semibold text-gray-600 dark:text-gray-300 text-center"
                                >
                                    Status
                                </th>
                                <th
                                    class="px-6 py-4 text-sm font-semibold text-gray-600 dark:text-gray-300 text-right"
                                >
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody
                            class="divide-y divide-gray-100 dark:divide-gray-700"
                        >
                            <tr
                                v-for="group in productGroups.data"
                                :key="group.id"
                                class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
                            >
                                <td class="px-6 py-4">
                                    <div
                                        class="w-12 h-8 rounded border border-gray-100 dark:border-gray-800 overflow-hidden bg-gray-50 dark:bg-gray-900"
                                    >
                                        <img
                                            v-if="group.banner"
                                            :src="group.banner"
                                            class="w-full h-full object-cover"
                                        />
                                        <div
                                            v-else
                                            class="w-full h-full flex items-center justify-center"
                                        >
                                            <ImageIcon
                                                size="14"
                                                class="text-gray-400"
                                            />
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-col">
                                        <span
                                            class="font-medium text-gray-900 dark:text-gray-100"
                                            >{{ group.name }}</span
                                        >
                                        <span
                                            class="text-gray-500 dark:text-gray-400 text-xs"
                                            >{{ group.slug }}</span
                                        >
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span
                                        class="text-gray-600 dark:text-gray-400 font-medium"
                                        >{{ group.order_number }}</span
                                    >
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <div
                                        class="flex items-center justify-center gap-1.5"
                                    >
                                        <Package
                                            size="16"
                                            class="text-gray-400"
                                        />
                                        <span
                                            class="font-semibold text-blue-600 dark:text-blue-400"
                                            >{{ group.products_count }}</span
                                        >
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex justify-center">
                                        <button
                                            @click="toggleStatus(group.id)"
                                            :title="
                                                group.status
                                                    ? 'Disable'
                                                    : 'Enable'
                                            "
                                            class="p-1 rounded-full transition-colors"
                                            :class="
                                                group.status
                                                    ? 'text-green-500 hover:bg-green-50 dark:hover:bg-green-900/20'
                                                    : 'text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700'
                                            "
                                        >
                                            <CheckCircle
                                                v-if="group.status"
                                                size="24"
                                            />
                                            <XCircle v-else size="24" />
                                        </button>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div
                                        class="flex justify-end items-center gap-2"
                                    >
                                        <Link
                                            :href="
                                                route(
                                                    'admin.product-groups.edit',
                                                    group.id,
                                                )
                                            "
                                            class="p-2 text-blue-600 hover:bg-blue-50 dark:text-blue-400 dark:hover:bg-blue-900/20 rounded-lg transition-colors"
                                            title="Edit"
                                        >
                                            <Edit size="18" />
                                        </Link>
                                        <button
                                            @click="openDeleteModal(group.id)"
                                            class="p-2 text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20 rounded-lg transition-colors"
                                            title="Delete"
                                        >
                                            <Trash2 size="18" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="productGroups.data.length === 0">
                                <td
                                    colspan="5"
                                    class="px-6 py-12 text-center text-gray-500 dark:text-gray-400"
                                >
                                    <div
                                        class="flex flex-col items-center gap-2"
                                    >
                                        <Package
                                            size="40"
                                            class="text-gray-300"
                                        />
                                        <p>
                                            No product groups found. Create your
                                            first group!
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div
                    v-if="productGroups.links.length > 3"
                    class="px-6 py-4 bg-gray-50 dark:bg-gray-900/50 border-t border-gray-100 dark:border-gray-700 flex justify-end"
                >
                    <nav class="flex gap-1">
                        <Link
                            v-for="(link, k) in productGroups.links"
                            :key="k"
                            :href="link.url || '#'"
                            class="px-3 py-1 text-sm rounded-md transition-colors"
                            :class="[
                                link.active
                                    ? 'bg-blue-600 text-white font-bold'
                                    : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700',
                                !link.url
                                    ? 'opacity-50 cursor-not-allowed'
                                    : '',
                            ]"
                            v-html="link.label"
                        />
                    </nav>
                </div>
            </div>
        </div>

        <DeleteModal
            :item-id="groupToDelete"
            item-name="product group"
            route-name="admin.product-groups.destroy"
            v-model:visible="showDeleteModal"
            @deleted="handleDeleteSuccess"
        />
    </AdminLayout>
</template>
