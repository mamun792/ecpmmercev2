<template>
    <Head>
        <title>Create Promotional Slider</title>
    </Head>
    <AdminLayout>
        <div class="p-6">
            <h1 class="text-2xl font-bold mb-4">Create Promotional Slider</h1>
            <form @submit.prevent="submit">
            <div class="mb-4">
                <label class="block mb-1">Image</label>
                <input
                type="file"
                @change="form.image = $event.target.files[0]"
                class="border p-2 w-full"
                />
                <div v-if="form.errors.image" class="text-red-500">
                {{ form.errors.image }}
                </div>
            </div>
            <div class="mb-4">
                <label class="block mb-1">Link (optional)</label>
                <input
                v-model="form.link"
                type="url"
                class="border p-2 w-full"
                placeholder="https://example.com"
                />
                <div v-if="form.errors.link" class="text-red-500">
                {{ form.errors.link }}
                </div>
            </div>
            <button
                type="submit"
                class="bg-blue-500 text-white px-4 py-2 rounded"
                :disabled="form.processing"
            >
                Save
            </button>
            </form>
        </div>
  </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { useForm, Head } from '@inertiajs/vue3';

const form = useForm({
  image: null,
  link: '',
});

const submit = () => {
  form.post(route('admin.promotional-sliders.store'), {
    onSuccess: () => form.reset(),
  });
};
</script>