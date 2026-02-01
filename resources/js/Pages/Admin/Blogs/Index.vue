<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Link, Head, router } from "@inertiajs/vue3";
import {
    Trash2Icon,
    SquarePen,
    Search,
    ChevronDown,
    ChevronRight,
} from "lucide-vue-next";
import DeleteModal from "@/Components/Modal/DeleteModal.vue";
import { ref, computed } from "vue";
import { toast } from "@steveyuowo/vue-hot-toast";

const props = defineProps({
    blogs: Object,
    filters: Object,
});

const showDeleteModal = ref(false);
const selectedBlogId = ref(null);
const searchQuery = ref(props.filters.search || "");
const loading = ref(false);

const openDeleteModal = (blogId) => {
    selectedBlogId.value = blogId;
    showDeleteModal.value = true;
};

const handleDeleteSuccess = () => {
    showDeleteModal.value = false;
    selectedBlogId.value = null;
};

// Pagination Logic
const currentPage = computed(() => props.blogs.current_page);
const lastPage = computed(() => props.blogs.last_page);

const goToPage = (page) => {
    if (page < 1 || page > lastPage.value || page === currentPage.value) return;

    loading.value = true;
    router.get(
        route("admin.blogs.index"),
        { search: searchQuery.value, page: page },
        {
            preserveState: true,
            onSuccess: () => {
                loading.value = false;
            },
        },
    );
};

// Search Logic
let searchTimeout = null;
const handleSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        loading.value = true;
        router.get(
            route("admin.blogs.index"),
            { search: searchQuery.value, page: 1 },
            {
                preserveState: true,
                onSuccess: () => {
                    loading.value = false;
                },
            },
        );
    }, 500);
};

const formatDate = (dateString) => {
    if (!dateString) return "N/A";
    return new Date(dateString).toLocaleDateString("en-US", {
        year: "numeric",
        month: "short",
        day: "numeric",
    });
};
</script>

<template>
    <Head title="Blog List" />
    <AdminLayout>
        <div
            class="p-6 bg-white dark:bg-gray-800 shadow-md rounded-md min-h-screen"
        >
            <div
                class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4"
            >
                <h2 class="text-xl font-bold text-gray-800 dark:text-white">
                    Blog List
                </h2>

                <div class="flex gap-4 w-full md:w-auto">
                    <div class="relative w-full md:w-64">
                        <input
                            type="text"
                            v-model="searchQuery"
                            placeholder="Search blogs..."
                            class="w-full pl-10 pr-4 py-2 rounded-md border border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-purple-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                            @input="handleSearch"
                        />
                        <div
                            class="absolute left-3 top-2.5 text-gray-400 dark:text-gray-500"
                        >
                            <Search size="18" />
                        </div>
                    </div>

                    <Link
                        :href="route('admin.blogs.create')"
                        class="btn-primary whitespace-nowrap"
                    >
                        Add Blog
                    </Link>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table
                    class="w-full border-collapse border border-gray-200 dark:border-gray-700"
                >
                    <thead>
                        <tr class="bg-gray-100 dark:bg-gray-900 text-left">
                            <th
                                class="border dark:border-gray-700 p-3 text-gray-700 dark:text-gray-300"
                            >
                                #
                            </th>
                            <th
                                class="border dark:border-gray-700 p-3 text-gray-700 dark:text-gray-300"
                            >
                                Image
                            </th>
                            <th
                                class="border dark:border-gray-700 p-3 text-gray-700 dark:text-gray-300"
                            >
                                Title
                            </th>
                            <th
                                class="border dark:border-gray-700 p-3 text-gray-700 dark:text-gray-300"
                            >
                                Author
                            </th>
                            <th
                                class="border dark:border-gray-700 p-3 text-gray-700 dark:text-gray-300"
                            >
                                Status
                            </th>
                            <th
                                class="border dark:border-gray-700 p-3 text-gray-700 dark:text-gray-300"
                            >
                                Published
                            </th>
                            <th
                                class="border dark:border-gray-700 p-3 text-gray-700 dark:text-gray-300"
                            >
                                Views
                            </th>
                            <th
                                class="border dark:border-gray-700 p-3 text-gray-700 dark:text-gray-300"
                            >
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody
                        class="divide-y divide-gray-200 dark:divide-gray-700"
                    >
                        <tr v-if="loading" class="animate-pulse">
                            <td
                                colspan="8"
                                class="p-4 text-center text-gray-500"
                            >
                                Loading...
                            </td>
                        </tr>
                        <template v-else>
                            <tr
                                v-for="(blog, index) in props.blogs.data"
                                :key="blog.id"
                                class="hover:bg-gray-50 dark:hover:bg-gray-700"
                            >
                                <td
                                    class="border dark:border-gray-700 p-3 text-gray-900 dark:text-gray-100"
                                >
                                    {{
                                        (currentPage - 1) *
                                            props.blogs.per_page +
                                        index +
                                        1
                                    }}
                                </td>
                                <td class="border dark:border-gray-700 p-3">
                                    <img
                                        v-if="blog.featured_image"
                                        :src="blog.featured_image"
                                        alt="Blog Image"
                                        class="h-12 w-20 object-cover rounded-md bg-gray-100 dark:bg-gray-600"
                                    />
                                    <span v-else class="text-gray-500 text-sm"
                                        >No Image</span
                                    >
                                </td>
                                <td
                                    class="border dark:border-gray-700 p-3 text-gray-900 dark:text-gray-100 font-medium"
                                >
                                    {{ blog.title }}
                                </td>
                                <td
                                    class="border dark:border-gray-700 p-3 text-gray-900 dark:text-gray-100"
                                >
                                    {{
                                        blog.author_name ||
                                        blog.user?.name ||
                                        "Unknown"
                                    }}
                                </td>
                                <td class="border dark:border-gray-700 p-3">
                                    <span
                                        class="px-2 py-1 rounded-full text-xs font-semibold"
                                        :class="{
                                            'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200':
                                                blog.status === 'published',
                                            'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200':
                                                blog.status === 'draft',
                                            'bg-gray-100 text-gray-800 dark:bg-gray-600 dark:text-gray-200':
                                                blog.status === 'archived',
                                        }"
                                    >
                                        {{
                                            blog.status
                                                .charAt(0)
                                                .toUpperCase() +
                                            blog.status.slice(1)
                                        }}
                                    </span>
                                </td>
                                <td
                                    class="border dark:border-gray-700 p-3 text-gray-900 dark:text-gray-100 text-sm"
                                >
                                    {{ formatDate(blog.published_at) }}
                                </td>
                                <td
                                    class="border dark:border-gray-700 p-3 text-gray-900 dark:text-gray-100"
                                >
                                    {{ blog.views_count }}
                                </td>
                                <td class="border dark:border-gray-700 p-3">
                                    <div class="flex items-center space-x-2">
                                        <Link
                                            :href="
                                                route(
                                                    'admin.blogs.edit',
                                                    blog.id,
                                                )
                                            "
                                            class="p-2 bg-blue-100 text-blue-600 rounded hover:bg-blue-200 dark:bg-blue-900 dark:text-blue-300 dark:hover:bg-blue-800 transition"
                                            title="Edit"
                                        >
                                            <SquarePen size="18" />
                                        </Link>
                                        <button
                                            @click="openDeleteModal(blog.id)"
                                            class="p-2 bg-red-100 text-red-600 rounded hover:bg-red-200 dark:bg-red-900 dark:text-red-300 dark:hover:bg-red-800 transition"
                                            title="Delete"
                                        >
                                            <Trash2Icon size="18" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!props.blogs.data.length">
                                <td
                                    colspan="8"
                                    class="border dark:border-gray-700 p-8 text-center text-gray-500 dark:text-gray-400"
                                >
                                    No blogs found.
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div
                v-if="props.blogs.total > props.blogs.per_page"
                class="flex items-center justify-end mt-6"
            >
                <div class="flex space-x-1">
                    <button
                        @click="goToPage(1)"
                        :disabled="currentPage === 1 || loading"
                        class="pagination-button"
                    >
                        «
                    </button>
                    <button
                        @click="goToPage(currentPage - 1)"
                        :disabled="currentPage === 1 || loading"
                        class="pagination-button"
                    >
                        ‹
                    </button>

                    <template v-if="lastPage <= 7">
                        <button
                            v-for="page in lastPage"
                            :key="page"
                            @click="goToPage(page)"
                            :disabled="loading"
                            class="pagination-button"
                            :class="{ active: currentPage === page }"
                        >
                            {{ page }}
                        </button>
                    </template>
                    <template v-else>
                        <button
                            v-if="currentPage > 3"
                            @click="goToPage(1)"
                            class="pagination-button"
                        >
                            1
                        </button>
                        <span v-if="currentPage > 4" class="pagination-ellipsis"
                            >...</span
                        >

                        <template v-for="page in lastPage" :key="page">
                            <button
                                v-if="
                                    page >= currentPage - 1 &&
                                    page <= currentPage + 1
                                "
                                @click="goToPage(page)"
                                class="pagination-button"
                                :class="{ active: currentPage === page }"
                            >
                                {{ page }}
                            </button>
                        </template>

                        <span
                            v-if="currentPage < lastPage - 3"
                            class="pagination-ellipsis"
                            >...</span
                        >
                        <button
                            v-if="currentPage < lastPage - 2"
                            @click="goToPage(lastPage)"
                            class="pagination-button"
                        >
                            {{ lastPage }}
                        </button>
                    </template>

                    <button
                        @click="goToPage(currentPage + 1)"
                        :disabled="currentPage === lastPage || loading"
                        class="pagination-button"
                    >
                        ›
                    </button>
                    <button
                        @click="goToPage(lastPage)"
                        :disabled="currentPage === lastPage || loading"
                        class="pagination-button"
                    >
                        »
                    </button>
                </div>
            </div>

            <DeleteModal
                :item-id="selectedBlogId"
                item-name="blog"
                route-name="admin.blogs.destroy"
                v-model:visible="showDeleteModal"
                @deleted="handleDeleteSuccess"
            />
        </div>
    </AdminLayout>
</template>

<style scoped>
.btn-primary {
    @apply px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700 transition duration-200 flex items-center justify-center;
}

.pagination-button {
    @apply px-3 py-1 border border-gray-300 dark:border-gray-600 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition disabled:opacity-50 disabled:cursor-not-allowed;
}

.active {
    @apply bg-purple-600 text-white border-purple-600 hover:bg-purple-700 dark:bg-purple-600 dark:border-purple-600 dark:hover:bg-purple-700;
}

.pagination-ellipsis {
    @apply px-2 py-1 text-gray-500 dark:text-gray-400;
}
</style>
