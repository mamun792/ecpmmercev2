<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Link, Head } from "@inertiajs/vue3";

const props = defineProps({
    blog: Object,
});

const formatDate = (dateString) => {
    if (!dateString) return "N/A";
    return new Date(dateString).toLocaleDateString("en-US", {
        year: "numeric",
        month: "long",
        day: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    });
};
</script>

<template>
    <Head :title="blog.title" />
    <AdminLayout>
        <div
            class="max-w-4xl mx-auto p-6 bg-white dark:bg-gray-800 shadow-md rounded-md"
        >
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-bold text-gray-800 dark:text-white">
                    Blog Details
                </h2>
                <div class="space-x-2">
                    <Link
                        :href="route('admin.blogs.edit', blog.id)"
                        class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition"
                    >
                        Edit
                    </Link>
                    <Link
                        :href="route('admin.blogs.index')"
                        class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600 transition"
                    >
                        Back to List
                    </Link>
                </div>
            </div>

            <div class="space-y-6">
                <!-- Header Info -->
                <div class="border-b dark:border-gray-700 pb-6">
                    <h1
                        class="text-3xl font-bold text-gray-900 dark:text-white mb-2"
                    >
                        {{ blog.title }}
                    </h1>
                    <div
                        class="flex items-center text-sm text-gray-500 dark:text-gray-400 space-x-4"
                    >
                        <span
                            >By
                            {{
                                blog.author_name || blog.user?.name || "Unknown"
                            }}</span
                        >
                        <span>&bull;</span>
                        <span>{{ formatDate(blog.published_at) }}</span>
                        <span>&bull;</span>
                        <span
                            class="px-2 py-0.5 rounded-full text-xs font-semibold"
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
                                blog.status.charAt(0).toUpperCase() +
                                blog.status.slice(1)
                            }}
                        </span>
                    </div>
                </div>

                <!-- Featured Image -->
                <div v-if="blog.featured_image" class="w-full">
                    <img
                        :src="blog.featured_image"
                        :alt="blog.title"
                        class="w-full h-auto max-h-96 object-cover rounded-lg shadow-sm"
                    />
                </div>

                <!-- Content -->
                <div class="prose dark:prose-invert max-w-none">
                    <div class="whitespace-pre-wrap">{{ blog.content }}</div>
                </div>

                <!-- Meta Info -->
                <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg mt-8">
                    <h3
                        class="text-lg font-semibold text-gray-900 dark:text-white mb-3"
                    >
                        SEO & Metadata
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="block text-gray-500 dark:text-gray-400"
                                >Slug</span
                            >
                            <span class="text-gray-900 dark:text-gray-200">{{
                                blog.slug
                            }}</span>
                        </div>
                        <div>
                            <span class="block text-gray-500 dark:text-gray-400"
                                >Views</span
                            >
                            <span class="text-gray-900 dark:text-gray-200">{{
                                blog.views_count
                            }}</span>
                        </div>
                        <div class="col-span-1 md:col-span-2">
                            <span class="block text-gray-500 dark:text-gray-400"
                                >Meta Title</span
                            >
                            <span class="text-gray-900 dark:text-gray-200">{{
                                blog.meta_title || "N/A"
                            }}</span>
                        </div>
                        <div class="col-span-1 md:col-span-2">
                            <span class="block text-gray-500 dark:text-gray-400"
                                >Meta Description</span
                            >
                            <span class="text-gray-900 dark:text-gray-200">{{
                                blog.meta_description || "N/A"
                            }}</span>
                        </div>
                        <div class="col-span-1 md:col-span-2">
                            <span class="block text-gray-500 dark:text-gray-400"
                                >Tags</span
                            >
                            <div class="flex flex-wrap gap-2 mt-1">
                                <span
                                    v-for="tag in blog.tags"
                                    :key="tag"
                                    class="px-2 py-1 bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200 rounded text-xs"
                                >
                                    {{ tag }}
                                </span>
                                <span
                                    v-if="!blog.tags || !blog.tags.length"
                                    class="text-gray-500 dark:text-gray-400 italic"
                                    >No tags</span
                                >
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
