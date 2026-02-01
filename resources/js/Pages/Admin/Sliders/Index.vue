<template>
    <Head>
        <title>Slider Management</title>
    </Head>
    <AdminLayout>
        <div class="container mx-auto p-6">
            <h1 class="text-2xl font-bold mb-6">Slider Management</h1>

            <!-- Image Upload and Link Form -->
            <div class="bg-white p-6 rounded-lg shadow-md mb-8">
                <form @submit.prevent="submit" enctype="multipart/form-data">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700"
                            >Upload Image</label
                        >
                        <input
                            type="file"
                            accept="image/*"
                            @change="(e) => handleImageChange(e, 'image')"
                            class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                        />
                        <span
                            v-if="form.errors.image"
                            class="text-red-600 text-sm"
                            >{{ form.errors.image }}</span
                        >
                    </div>

                    <!-- Image Preview -->
                    <div v-if="previewImage" class="mb-4">
                        <img
                            :src="previewImage"
                            alt="Image Preview"
                            class="w-64 h-32 object-cover rounded-md"
                        />
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700"
                            >Upload Mobile Image</label
                        >
                        <input
                            type="file"
                            accept="image/*"
                            @change="
                                (e) => handleImageChange(e, 'mobile_image')
                            "
                            class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                        />
                        <span
                            v-if="form.errors.mobile_image"
                            class="text-red-600 text-sm"
                            >{{ form.errors.mobile_image }}</span
                        >
                    </div>

                    <!-- Mobile Image Preview -->
                    <div v-if="previewMobileImage" class="mb-4">
                        <img
                            :src="previewMobileImage"
                            alt="Mobile Image Preview"
                            class="w-32 h-48 object-cover rounded-md"
                        />
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700"
                            >Link (Optional)</label
                        >
                        <input
                            v-model="form.link"
                            type="url"
                            placeholder="https://example.com"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        />
                        <span
                            v-if="form.errors.link"
                            class="text-red-600 text-sm"
                            >{{ form.errors.link }}</span
                        >
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700"
                            >Background Color (Optional)</label
                        >
                        <input
                            v-model="form.bg_color"
                            type="color"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        />
                        <span
                            v-if="form.errors.bg_color"
                            class="text-red-600 text-sm"
                            >{{ form.errors.bg_color }}</span
                        >
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 disabled:opacity-50"
                    >
                        {{ editing ? "Update Slider" : "Add Slider" }}
                    </button>
                    <button
                        v-if="editing"
                        type="button"
                        @click="cancelEdit"
                        class="ml-2 bg-gray-300 text-gray-700 py-2 px-4 rounded-md hover:bg-gray-400"
                    >
                        Cancel
                    </button>
                </form>
            </div>

            <!-- Sliders List -->
            <div class="bg-white rounded-lg shadow-md">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                            >
                                Image
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                            >
                                Mobile Image
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                            >
                                Link
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                            >
                                BG Color
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                            >
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="slider in sliders" :key="slider.id">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <img
                                    :src="slider.image"
                                    alt="Slider Image"
                                    class="w-24 h-12 object-cover rounded"
                                />
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <img
                                    v-if="slider.mobile_image"
                                    :src="slider.mobile_image"
                                    alt="Mobile Slider Image"
                                    class="w-12 h-16 object-cover rounded"
                                />
                                <span v-else class="text-gray-400"
                                    >No Image</span
                                >
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <a
                                    :href="slider.Link"
                                    target="_blank"
                                    class="text-blue-600 hover:underline"
                                >
                                    {{ slider.Link || "No Link" }}
                                </a>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div
                                    v-if="slider.bg_color"
                                    :style="{ backgroundColor: slider.bg_color }"
                                    class="w-8 h-8 rounded border"
                                ></div>
                                <span v-else class="text-gray-400">No Color</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <button
                                    @click="editSlider(slider)"
                                    class="text-blue-600 hover:text-blue-800 mr-4"
                                >
                                    Edit
                                </button>
                                <button
                                    @click="openDeleteModal(slider.id)"
                                    class="text-red-600 hover:text-red-800"
                                >
                                    Delete
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Delete Modal -->
            <DeleteModal
                :item-id="selectedSliderId"
                item-name="slider"
                route-name="admin.sliders.destroy"
                v-model:visible="showDeleteModal"
                @deleted="handleDeleteSuccess"
            />
        </div>
    </AdminLayout>
</template>

<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import DeleteModal from "@/Components/Modal/DeleteModal.vue";
import { useForm, Head } from "@inertiajs/vue3";
import { ref } from "vue";

const props = defineProps({
    sliders: Array,
});

const form = useForm({
    image: null,
    mobile_image: null,
    link: "",
    bg_color: "",
    _method: "POST", // Default to POST for create
});

const previewImage = ref(null);
const previewMobileImage = ref(null);
const editing = ref(false);
const editingSliderId = ref(null);
const showDeleteModal = ref(false);
const selectedSliderId = ref(null);

const handleImageChange = (event, field) => {
    const file = event.target.files[0];
    if (file) {
        form[field] = file;
        if (field === "image") {
            previewImage.value = URL.createObjectURL(file);
        } else if (field === "mobile_image") {
            previewMobileImage.value = URL.createObjectURL(file);
        }
    } else {
        form[field] = null;
        if (field === "image") {
            previewImage.value = null;
        } else if (field === "mobile_image") {
            previewMobileImage.value = null;
        }
    }
};

const submit = () => {
    if (editing.value) {
        // Update slider
        form._method = "PUT"; // Spoof PUT for update
        form.post(`/admin/sliders/${editingSliderId.value}`, {
            preserveState: true,
            onSuccess: () => {
                resetForm();
            },
        });
    } else {
        // Create slider
        form._method = "POST";
        form.post("/admin/sliders", {
            preserveState: true,
            onSuccess: () => {
                resetForm();
            },
        });
    }
};

const editSlider = (slider) => {
    editing.value = true;
    editingSliderId.value = slider.id;
    form.link = slider.Link || "";
    form.bg_color = slider.bg_color || "";
    previewImage.value = slider.image;
    previewMobileImage.value = slider.mobile_image;
    form.image = null; // Reset image to allow keeping existing image
    form.mobile_image = null;
};

const cancelEdit = () => {
    resetForm();
};

const openDeleteModal = (id) => {
    selectedSliderId.value = id;
    showDeleteModal.value = true;
};

const handleDeleteSuccess = () => {
    resetForm();
    showDeleteModal.value = false;
    selectedSliderId.value = null;
};

const resetForm = () => {
    form.reset();
    form.image = null;
    form.mobile_image = null;
    form.bg_color = "";
    form._method = "POST"; // Reset to POST for next create
    previewImage.value = null;
    previewMobileImage.value = null;
    editing.value = false;
    editingSliderId.value = null;
};
</script>
