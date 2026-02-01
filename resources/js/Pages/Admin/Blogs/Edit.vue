<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Link, useForm, Head } from "@inertiajs/vue3";
import { ref, onMounted } from "vue";
import { toast } from "@steveyuowo/vue-hot-toast";
import { Ckeditor } from "@ckeditor/ckeditor5-vue";
import "ckeditor5/ckeditor5.css";
import { ClassicEditor, editorConfig } from "@/Helpers/ckeditor";

const props = defineProps({
    blog: Object,
});

const isLayoutReady = ref(false);
const editor = ClassicEditor;
const config = editorConfig;

onMounted(() => {
    isLayoutReady.value = true;
});

const form = useForm({
    _method: "PUT",
    title: props.blog.title || "",
    slug: props.blog.slug || "",
    excerpt: props.blog.excerpt || "",
    content: props.blog.content || "",
    featured_image: null,
    author_name: props.blog.author_name || "",
    status: props.blog.status || "draft",
    published_at: props.blog.published_at
        ? props.blog.published_at.slice(0, 16)
        : "", // Format for datetime-local
    meta_title: props.blog.meta_title || "",
    meta_title: props.blog.meta_title || "",
    meta_description: props.blog.meta_description || "",
    tags: Array.isArray(props.blog.tags)
        ? props.blog.tags
        : props.blog.tags
        ? props.blog.tags.split(",").map((t) => t.trim())
        : [],
});

const imagePreview = ref(props.blog.featured_image || null);
const newTag = ref("");

const handleImageChange = (event) => {
    const file = event.target.files[0];
    if (file) {
        form.featured_image = file;
        imagePreview.value = URL.createObjectURL(file);
    }
};

const removeImage = () => {
    form.featured_image = null;
    imagePreview.value = null;
};

const addTag = () => {
    const tag = newTag.value.trim();
    if (tag && !form.tags.includes(tag)) {
        form.tags.push(tag);
    }
    newTag.value = "";
};

const removeTag = (index) => {
    form.tags.splice(index, 1);
};

const handleBackspace = () => {
    if (newTag.value === "" && form.tags.length > 0) {
        form.tags.pop();
    }
};

const generateSlug = () => {
    form.slug = form.title
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, "-")
        .replace(/(^-|-$)/g, "");
};

const submit = () => {
    // Ensure any pending tag in input is added before submit
    if (newTag.value.trim()) {
        addTag();
    }

    form.post(route("admin.blogs.update", props.blog.id), {
        onSuccess: () => {
            toast.success("Blog updated successfully");
        },
        onError: () => {
            toast.error("Failed to update blog");
        },
    });
};
</script>

<template>
    <Head title="Edit Blog" />
    <AdminLayout>
        <div
            class="max-w-4xl mx-auto p-6 bg-white dark:bg-gray-800 shadow-md rounded-md"
        >
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-bold text-gray-800 dark:text-white">
                    Edit Blog
                </h2>
                <Link
                    :href="route('admin.blogs.index')"
                    class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600 transition"
                >
                    Back to List
                </Link>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Title -->
                <div class="grid grid-cols-1 gap-6">
                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                            >Title <span class="text-red-500">*</span></label
                        >
                        <input
                            v-model="form.title"
                            type="text"
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-purple-500 focus:ring-purple-500"
                            required
                        />
                        <div
                            v-if="form.errors.title"
                            class="text-red-500 text-sm mt-1"
                        >
                            {{ form.errors.title }}
                        </div>
                    </div>
                </div>

                <!-- Featured Image -->
                <div>
                    <label
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                        >Featured Image</label
                    >
                    <div class="mt-1 flex items-center space-x-4">
                        <div v-if="imagePreview" class="relative">
                            <img
                                :src="imagePreview"
                                alt="Preview"
                                class="h-32 w-auto object-cover rounded-md border dark:border-gray-600"
                            />
                            <button
                                type="button"
                                @click="removeImage"
                                class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full p-1 hover:bg-red-600"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="h-4 w-4"
                                    viewBox="0 0 20 20"
                                    fill="currentColor"
                                >
                                    <path
                                        fill-rule="evenodd"
                                        d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                            </button>
                        </div>
                        <div
                            v-else
                            class="flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 dark:border-gray-600 border-dashed rounded-md w-full"
                        >
                            <div class="space-y-1 text-center">
                                <svg
                                    class="mx-auto h-12 w-12 text-gray-400"
                                    stroke="currentColor"
                                    fill="none"
                                    viewBox="0 0 48 48"
                                    aria-hidden="true"
                                >
                                    <path
                                        d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02"
                                        stroke-width="2"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    />
                                </svg>
                                <div
                                    class="flex text-sm text-gray-600 dark:text-gray-400"
                                >
                                    <label
                                        for="file-upload"
                                        class="relative cursor-pointer bg-white dark:bg-gray-700 rounded-md font-medium text-purple-600 hover:text-purple-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-purple-500"
                                    >
                                        <span>Upload a file</span>
                                        <input
                                            id="file-upload"
                                            name="file-upload"
                                            type="file"
                                            class="sr-only"
                                            @change="handleImageChange"
                                            accept="image/*"
                                        />
                                    </label>
                                    <p class="pl-1">or drag and drop</p>
                                </div>
                                <p
                                    class="text-xs text-gray-500 dark:text-gray-400"
                                >
                                    PNG, JPG, GIF up to 2MB
                                </p>
                            </div>
                        </div>
                    </div>
                    <div
                        v-if="form.errors.featured_image"
                        class="text-red-500 text-sm mt-1"
                    >
                        {{ form.errors.featured_image }}
                    </div>
                </div>

                <!-- Excerpt -->
                <div>
                    <label
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                        >Excerpt</label
                    >
                    <textarea
                        v-model="form.excerpt"
                        rows="3"
                        class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-purple-500 focus:ring-purple-500"
                    ></textarea>
                    <div
                        v-if="form.errors.excerpt"
                        class="text-red-500 text-sm mt-1"
                    >
                        {{ form.errors.excerpt }}
                    </div>
                </div>

                <!-- Content -->
                <div>
                    <label
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                        >Content <span class="text-red-500">*</span></label
                    >
                    <div class="ckeditor-container">
                        <ckeditor
                            v-if="isLayoutReady"
                            v-model="form.content"
                            :editor="editor"
                            :config="config"
                        />
                    </div>
                    <div
                        v-if="form.errors.content"
                        class="text-red-500 text-sm mt-1"
                    >
                        {{ form.errors.content }}
                    </div>
                </div>

                <!-- Author & Status -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                            >Author Name</label
                        >
                        <input
                            v-model="form.author_name"
                            type="text"
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-purple-500 focus:ring-purple-500"
                        />
                        <div
                            v-if="form.errors.author_name"
                            class="text-red-500 text-sm mt-1"
                        >
                            {{ form.errors.author_name }}
                        </div>
                    </div>
                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                            >Status</label
                        >
                        <select
                            v-model="form.status"
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-purple-500 focus:ring-purple-500"
                        >
                            <option value="draft">Draft</option>
                            <option value="published">Published</option>
                            <option value="archived">Archived</option>
                        </select>
                        <div
                            v-if="form.errors.status"
                            class="text-red-500 text-sm mt-1"
                        >
                            {{ form.errors.status }}
                        </div>
                    </div>
                </div>

                <!-- SEO Section -->
                <div class="border-t dark:border-gray-700 pt-6">
                    <h3
                        class="text-lg font-medium text-gray-900 dark:text-white mb-4"
                    >
                        SEO Settings
                    </h3>
                    <div class="grid grid-cols-1 gap-6">
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                                >Meta Title</label
                            >
                            <input
                                v-model="form.meta_title"
                                type="text"
                                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-purple-500 focus:ring-purple-500"
                            />
                            <div
                                v-if="form.errors.meta_title"
                                class="text-red-500 text-sm mt-1"
                            >
                                {{ form.errors.meta_title }}
                            </div>
                        </div>
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                                >Meta Description</label
                            >
                            <textarea
                                v-model="form.meta_description"
                                rows="3"
                                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-purple-500 focus:ring-purple-500"
                            ></textarea>
                            <div
                                v-if="form.errors.meta_description"
                                class="text-red-500 text-sm mt-1"
                            >
                                {{ form.errors.meta_description }}
                            </div>
                        </div>
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                                >Tags</label
                            >
                            <div
                                class="w-full rounded-md border border-gray-300 dark:border-gray-600 dark:bg-gray-700 p-2 flex flex-wrap gap-2 focus-within:ring-2 focus-within:ring-purple-500 focus-within:border-purple-500"
                            >
                                <span
                                    v-for="(tag, index) in form.tags"
                                    :key="index"
                                    class="bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200 px-2 py-1 rounded-md text-sm flex items-center"
                                >
                                    {{ tag }}
                                    <button
                                        type="button"
                                        @click="removeTag(index)"
                                        class="ml-1 text-purple-600 dark:text-purple-300 hover:text-purple-800 dark:hover:text-white focus:outline-none"
                                    >
                                        &times;
                                    </button>
                                </span>
                                <input
                                    v-model="newTag"
                                    @keydown.enter.prevent="addTag"
                                    @keydown.backspace="handleBackspace"
                                    type="text"
                                    placeholder="Add a tag..."
                                    class="flex-grow bg-transparent border-none focus:ring-0 text-gray-700 dark:text-white placeholder-gray-400 min-w-[100px]"
                                />
                            </div>
                            <p class="text-xs text-gray-500 mt-1">
                                Press Enter to add a tag. Backspace to remove
                                the last tag.
                            </p>
                            <div
                                v-if="form.errors.tags"
                                class="text-red-500 text-sm mt-1"
                            >
                                {{ form.errors.tags }}
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    class="flex justify-end pt-6 border-t dark:border-gray-700"
                >
                    <button
                        type="submit"
                        class="px-6 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 transition disabled:opacity-50"
                        :disabled="form.processing"
                    >
                        {{ form.processing ? "Updating..." : "Update Blog" }}
                    </button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
