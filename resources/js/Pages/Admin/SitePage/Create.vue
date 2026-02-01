<template>
    <Head>
        <title>Create New Page</title>
    </Head>
    <AdminLayout>
        <div class="p-6">
            <h1 class="text-2xl font-bold mb-4">Create Page</h1>

            <div
                v-if="$page.props.flash && $page.props.flash.success"
                class="mb-4 p-4 bg-green-100 text-green-700"
            >
                {{ $page.props.flash.success }}
            </div>

            <form @submit.prevent="submit" class="w-full">
                <div class="mb-4">
                    <label
                        for="name"
                        class="block text-sm font-medium text-gray-700"
                        >Name</label
                    >
                    <input
                        v-model="form.name"
                        type="text"
                        id="name"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                        :class="{ 'border-red-500': form.errors.name }"
                    />
                    <div
                        v-if="form.errors.name"
                        class="text-red-500 text-sm mt-1"
                    >
                        {{ form.errors.name }}
                    </div>
                </div>

                <div class="mb-4">
                    <label
                        for="title"
                        class="block text-sm font-medium text-gray-700"
                        >Title</label
                    >
                    <input
                        v-model="form.title"
                        type="text"
                        id="title"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                        :class="{ 'border-red-500': form.errors.title }"
                    />
                    <div
                        v-if="form.errors.title"
                        class="text-red-500 text-sm mt-1"
                    >
                        {{ form.errors.title }}
                    </div>
                </div>

                <div class="mb-4">
                    <label
                        for="content"
                        class="block text-sm font-medium text-gray-700"
                        >Content</label
                    >

                    <div ref="editorElement">
                        <ckeditor
                            v-if="isLayoutReady"
                            id="description"
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

                <div class="flex space-x-4">
                    <button
                        type="submit"
                        class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
                    >
                        Create
                    </button>
                    <Link
                        :href="route('admin.site-pages.index')"
                        class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded"
                    >
                        Cancel
                    </Link>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>

<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { ref, computed, onMounted } from "vue";
import { useForm, Link, Head } from "@inertiajs/vue3";
import { Ckeditor } from "@ckeditor/ckeditor5-vue";
import "ckeditor5/ckeditor5.css";
import { ClassicEditor, editorConfig } from "@/Helpers/ckeditor";
import { toast } from "@steveyuowo/vue-hot-toast";

const isLayoutReady = ref(false);
const editor = ClassicEditor;
const config = editorConfig;


onMounted(() => {
    isLayoutReady.value = true;
});

const form = useForm({
    name: "",
    title: "",
    content: "",
});

const submit = () => {
    form.post(route("admin.site-pages.store"), {
        onSuccess: () => {
            toast.success("Page created successfully!");
            form.reset();
        },
        onError: () => {
            toast.error("Failed to create page. Please check the errors.");
        },
    });
};
</script>
