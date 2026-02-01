<template>
    <div v-if="show" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" @click="close">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white" @click.stop>
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4">
                    {{ isEditing ? 'Edit Team Member' : 'Add New Team Member' }}
                </h3>
                <form @submit.prevent="submit">
                    <div class="mb-4">
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                            Name *
                        </label>
                        <input
                            type="text"
                            id="name"
                            v-model="form.name"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                            :class="{ 'border-red-500': form.errors?.name }"
                            required
                        />
                        <div v-if="form.errors?.name" class="mt-1 text-sm text-red-600">
                            {{ form.errors.name }}
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="designation" class="block text-sm font-medium text-gray-700 mb-2">
                            Designation *
                        </label>
                        <input
                            type="text"
                            id="designation"
                            v-model="form.designation"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                            :class="{ 'border-red-500': form.errors?.designation }"
                            required
                        />
                        <div v-if="form.errors?.designation" class="mt-1 text-sm text-red-600">
                            {{ form.errors.designation }}
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="image" class="block text-sm font-medium text-gray-700 mb-2">
                            Image
                        </label>
                        <input
                            type="file"
                            id="image"
                            @change="handleImageChange"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                            :class="{ 'border-red-500': form.errors?.image }"
                            accept="image/*"
                        />
                        <div v-if="form.errors?.image" class="mt-1 text-sm text-red-600">
                            {{ form.errors.image }}
                        </div>
                        <div v-if="currentImage" class="mt-2">
                            <img :src="currentImage" alt="Current Image" class="w-20 h-20 object-cover rounded">
                        </div>
                    </div>

                    <div class="flex items-center justify-end mt-6">
                        <button type="button" @click="close" class="mr-4 px-4 py-2 text-sm text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-md">
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="processing"
                            class="px-4 py-2 text-sm font-medium text-white bg-blue-500 hover:bg-blue-700 rounded-md disabled:opacity-50"
                        >
                            <span v-if="processing">{{ isEditing ? 'Updating...' : 'Creating...' }}</span>
                            <span v-else>{{ isEditing ? 'Update' : 'Create' }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    show: Boolean,
    teamMember: Object,
    isEditing: Boolean,
});

const emit = defineEmits(['close', 'saved']);

const form = useForm({
    name: '',
    designation: '',
    image: null,
});

const currentImage = ref(null);

watch(() => props.show, (newVal) => {
    if (newVal) {
        if (props.isEditing && props.teamMember) {
            form.name = props.teamMember.name;
            form.designation = props.teamMember.designation;
            currentImage.value = props.teamMember.image || null;
        } else {
            form.reset();
            currentImage.value = null;
        }
        form.clearErrors();
    }
});

const handleImageChange = (event) => {
    form.image = event.target.files[0];
};

const submit = () => {
    const routeName = props.isEditing ? 'admin.team-member.update' : 'admin.team-member.store';
    const method = props.isEditing ? 'put' : 'post';
    const url = props.isEditing ? route(routeName, props.teamMember.id) : route(routeName);

    form[method](url, {
        onSuccess: () => {
            emit('saved');
        },
    });
};

const close = () => {
    emit('close');
};
</script>