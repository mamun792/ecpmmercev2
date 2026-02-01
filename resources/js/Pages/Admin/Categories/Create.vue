<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { ref, computed } from "vue";
import { useForm, Link } from "@inertiajs/vue3";

const props = defineProps({
    categories: {
        type: Array,
        required: true,
    },
    errors: {
        type: Object,
        required: true,
    },
});

// Get parent_id from URL if present
const urlParams = new URLSearchParams(window.location.search);
const parentIdFromUrl = urlParams.get("parent_id");

const form = useForm({
    name: "",
    parent_id: parentIdFromUrl ? parseInt(parentIdFromUrl) : null,
    image: null,
    icon: null,
    order: 0,
});

const imageProgress = ref(0);
const iconProgress = ref(0);
const imageDragActive = ref(false);
const iconDragActive = ref(false);
const imagePreview = ref(null); // For image preview URL
const iconPreview = ref(null); // For icon preview URL

// Function to process categories recursively
const formatCategories = (categories, level = 0) => {
    let formatted = [];

    categories.forEach((category) => {
        formatted.push({
            id: category.id,
            name: `${"-".repeat(level)} ${category.name}`,
        });

        if (category.children && category.children.length > 0) {
            formatted = formatted.concat(
                formatCategories(category.children, level + 1)
            );
        }
    });

    return formatted;
};

const formattedCategories = computed(() => formatCategories(props.categories));

const handleFileChange = (event, field) => {
    const file = event.target.files[0];
    if (file) {
        form[field] = file;
        updatePreview(file, field);
        simulateUploadProgress(field);
    }
};

const handleDrop = (event, field) => {
    event.preventDefault();
    const file = event.dataTransfer.files[0];
    if (file) {
        form[field] = file;
        updatePreview(file, field);
        simulateUploadProgress(field);
    }
    field === "image"
        ? (imageDragActive.value = false)
        : (iconDragActive.value = false);
};

const handleDragOver = (event, field) => {
    event.preventDefault();
    field === "image"
        ? (imageDragActive.value = true)
        : (iconDragActive.value = true);
};

const handleDragLeave = (event, field) => {
    event.preventDefault();
    field === "image"
        ? (imageDragActive.value = false)
        : (iconDragActive.value = false);
};

const updatePreview = (file, field) => {
    const reader = new FileReader();
    reader.onload = (e) => {
        if (field === "image") {
            imagePreview.value = e.target.result;
        } else {
            iconPreview.value = e.target.result;
        }
    };
    reader.readAsDataURL(file);
};

const simulateUploadProgress = (field) => {
    const progress = field === "image" ? imageProgress : iconProgress;
    progress.value = 0;
    const interval = setInterval(() => {
        if (progress.value < 100) {
            progress.value += 10;
        } else {
            clearInterval(interval);
        }
    }, 100);
};

const submit = () => {
    form.post(route("admin.categories.store"), {
        forceFormData: true,
        onSuccess: () => {
            form.reset();
            imageProgress.value = 0;
            iconProgress.value = 0;
            imagePreview.value = null; // Reset preview
            iconPreview.value = null; // Reset preview
        },
    });
};
</script>

<template>
    <AdminLayout>
        <div class="p-6 bg-white shadow-md rounded-md min-h-screen">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-bold mb-6">Add Category</h2>
                <Link
                    :href="route('admin.categories.index')"
                    class="btn-primary"
                    >Back</Link
                >
            </div>

            <form @submit.prevent="submit" enctype="multipart/form-data">
                <!-- Name -->
                <div class="mb-6">
                    <label class="block font-medium mb-1">Category Name</label>
                    <input
                        v-model="form.name"
                        type="text"
                        class="w-full p-2 border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        required
                    />
                    <div v-if="errors.name" class="text-red-500 text-sm mt-1">
                        {{ errors.name }}
                    </div>
                </div>
                <!-- order -->
                <div class="mb-6">
                    <label class="block font-medium mb-1">Category Order</label>
                    <input
                        v-model="form.order"
                        type="number"
                        class="w-full p-2 border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        required
                    />
                    <div v-if="errors.order" class="text-red-500 text-sm mt-1">
                        {{ errors.order }}
                    </div>
                </div>

                <!-- Parent Category -->
                <div class="mb-6">
                    <label class="block font-medium mb-1"
                        >Parent Category</label
                    >
                    <select
                        v-model="form.parent_id"
                        class="w-full p-2 border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    >
                        <option :value="null">None</option>
                        <option
                            v-for="category in formattedCategories"
                            :key="category.id"
                            :value="category.id"
                        >
                            {{ category.name }}
                        </option>
                    </select>
                </div>

                <!-- Image Upload -->
                <div class="mb-6">
                    <label class="block font-medium mb-1">Category Image</label>
                    <div
                        @drop="handleDrop($event, 'image')"
                        @dragover="handleDragOver($event, 'image')"
                        @dragleave="handleDragLeave($event, 'image')"
                        :class="[
                            'relative border-2 border-dashed rounded-md p-4 text-center',
                            imageDragActive
                                ? 'border-blue-500 bg-blue-50'
                                : 'border-gray-300',
                        ]"
                    >
                        <input
                            type="file"
                            @change="handleFileChange($event, 'image')"
                            class="hidden"
                            id="image-upload"
                            accept="image/*"
                        />
                        <label
                            for="image-upload"
                            class="cursor-pointer block text-gray-600 hover:text-blue-500"
                        >
                            <span v-if="!form.image"
                                >Drag and drop image here or click to
                                select</span
                            >
                            <span v-else>{{ form.image.name }}</span>
                        </label>
                        <!-- Image Preview -->
                        <div v-if="imagePreview" class="mt-4">
                            <img
                                :src="imagePreview"
                                alt="Image Preview"
                                class="max-w-full h-auto max-h-40 mx-auto rounded-md"
                            />
                        </div>
                        <div
                            v-if="imageProgress > 0 && imageProgress < 100"
                            class="mt-2"
                        >
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div
                                    class="bg-blue-500 h-2 rounded-full transition-all duration-300"
                                    :style="{ width: `${imageProgress}%` }"
                                ></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Icon Upload -->
                <div class="mb-6">
                    <label class="block font-medium mb-1">Category Icon</label>
                    <div
                        @drop="handleDrop($event, 'icon')"
                        @dragover="handleDragOver($event, 'icon')"
                        @dragleave="handleDragLeave($event, 'icon')"
                        :class="[
                            'relative border-2 border-dashed rounded-md p-4 text-center',
                            iconDragActive
                                ? 'border-blue-500 bg-blue-50'
                                : 'border-gray-300',
                        ]"
                    >
                        <input
                            type="file"
                            @change="handleFileChange($event, 'icon')"
                            class="hidden"
                            id="icon-upload"
                            accept="image/*"
                        />
                        <label
                            for="icon-upload"
                            class="cursor-pointer block text-gray-600 hover:text-blue-500"
                        >
                            <span v-if="!form.icon"
                                >Drag and drop icon here or click to
                                select</span
                            >
                            <span v-else>{{ form.icon.name }}</span>
                        </label>
                        <!-- Icon Preview -->
                        <div v-if="iconPreview" class="mt-4">
                            <img
                                :src="iconPreview"
                                alt="Icon Preview"
                                class="max-w-full h-auto max-h-20 mx-auto rounded-md"
                            />
                        </div>
                        <div
                            v-if="iconProgress > 0 && iconProgress < 100"
                            class="mt-2"
                        >
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div
                                    class="bg-blue-500 h-2 rounded-full transition-all duration-300"
                                    :style="{ width: `${iconProgress}%` }"
                                ></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Submit -->
                <button
                    type="submit"
                    class="btn-primary"
                    :disabled="form.processing"
                >
                    {{ form.processing ? "Creating..." : "Create Category" }}
                </button>
            </form>
        </div>
    </AdminLayout>
</template>

<style scoped>
input:focus,
select:focus {
    outline: none;
}
</style>
