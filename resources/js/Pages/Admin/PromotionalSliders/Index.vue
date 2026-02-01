<template>
    <Head>
        <title>Promotional Sliders</title>
    </Head>
    <AdminLayout>
        <div class="p-6">
            <h1 class="text-2xl font-bold mb-4">Promotional Sliders</h1>
            <Link
                :href="route('admin.promotional-sliders.create')"
                class="mb-4 inline-block bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition-colors"
            >
                Create New Slider
            </Link>
            <div class="mt-4">
                <table class="w-full border-collapse bg-white shadow-sm rounded-lg overflow-hidden">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="border p-3 text-left font-semibold text-gray-700">Image</th>
                            <th class="border p-3 text-left font-semibold text-gray-700">Link</th>
                            <th class="border p-3 text-center font-semibold text-gray-700">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="slider in sliders" :key="slider.id" class="hover:bg-gray-50">
                            <td class="border p-3">
                                <img
                                    :src="slider.image"
                                    class="w-24 h-24 object-cover rounded-lg shadow-sm"
                                    :alt="'Slider ' + slider.id"
                                />
                            </td>
                            <td class="border p-3">
                                <span class="text-gray-700">{{ slider.link || 'No link' }}</span>
                            </td>
                            <td class="border p-3">
                                <div class="flex justify-center items-center gap-2">
                                    <!-- Edit Link -->
                                    <div class="relative group">
                                        <Link 
                                            :href="route('admin.promotional-sliders.edit', slider.id)"
                                            class="table_edit_action hover:bg-green-500 hover:text-white p-2 rounded transition-colors duration-200 text-gray-600"
                                        >
                                            <SquarePen :size="20" />
                                        </Link>
                                        <span
                                            class="absolute -top-8 left-1/2 transform -translate-x-1/2 scale-0 group-hover:scale-100 transition bg-gray-800 text-white text-xs rounded px-2 py-1 whitespace-nowrap"
                                        >
                                            Edit
                                        </span>
                                    </div>

                                    <!-- Delete Button -->
                                    <div class="relative group">
                                        <button
                                            @click="destroy(slider.id)"
                                            class="table_delete_action hover:bg-red-500 hover:text-white p-2 rounded transition-colors duration-200 text-gray-600"
                                        >
                                            <Trash2 :size="20" />
                                        </button>
                                        <span
                                            class="absolute -top-8 left-1/2 transform -translate-x-1/2 scale-0 group-hover:scale-100 transition bg-gray-800 text-white text-xs rounded px-2 py-1 whitespace-nowrap"
                                        >
                                            Delete
                                        </span>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <!-- Empty State -->
                        <tr v-if="!sliders || sliders.length === 0">
                            <td colspan="3" class="border p-6 text-center text-gray-500">
                                <div class="flex flex-col items-center">
                                    <ImageIcon :size="48" class="text-gray-400 mb-2" />
                                    <span>No promotional sliders found</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Link, useForm, Head } from '@inertiajs/vue3';
import { SquarePen, Trash2, ImageIcon } from 'lucide-vue-next';

defineProps({
    sliders: Array,
});

const form = useForm({});

const destroy = (id) => {
    if (confirm('Are you sure you want to delete this slider?')) {
        form.delete(route('admin.promotional-sliders.destroy', id), {
            preserveState: true,
            preserveScroll: true,
        });
    }
};
</script>

<style scoped>
.table_edit_action {
    @apply inline-flex items-center justify-center;
}

.table_delete_action {
    @apply inline-flex items-center justify-center;
}
</style>