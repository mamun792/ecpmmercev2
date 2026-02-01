<template>
    <AdminLayout>
    <Head>
        <title>Edit Employee - Admin Panel</title>
    </Head>
    <div class="p-6">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Edit Employee</h1>
            <Link :href="route('admin.employee.index')" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                Back to Employees
            </Link>
        </div>

        <div class="bg-white shadow-sm rounded-lg p-6">
            <form @submit.prevent="submit">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Employee Name -->
                    <div class="md:col-span-1">
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                            Employee Name *
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

                    <!-- Email -->
                    <div class="md:col-span-1">
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                            Email
                        </label>
                        <input
                            type="email"
                            id="email"
                            v-model="form.email"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                            :class="{ 'border-red-500': form.errors?.email }"
                        />
                        <div v-if="form.errors?.email" class="mt-1 text-sm text-red-600">
                            {{ form.errors.email }}
                        </div>
                    </div>

                    <!-- Phone -->
                    <div class="md:col-span-1">
                        <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">
                            Phone
                        </label>
                        <input
                            type="text"
                            id="phone"
                            v-model="form.phone"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                            :class="{ 'border-red-500': form.errors?.phone }"
                        />
                        <div v-if="form.errors?.phone" class="mt-1 text-sm text-red-600">
                            {{ form.errors.phone }}
                        </div>
                    </div>

                    <!-- Role -->
                    <div class="md:col-span-1">
                        <label for="role" class="block text-sm font-medium text-gray-700 mb-2">
                            Role
                        </label>
                        <input
                            type="text"
                            id="role"
                            v-model="form.role"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                            :class="{ 'border-red-500': form.errors?.role }"
                        />
                        <div v-if="form.errors?.role" class="mt-1 text-sm text-red-600">
                            {{ form.errors.role }}
                        </div>
                    </div>

                    <!-- Monthly Salary -->
                    <div class="md:col-span-1">
                        <label for="monthly_salary" class="block text-sm font-medium text-gray-700 mb-2">
                            Monthly Salary
                        </label>
                        <input
                            type="number"
                            step="0.01"
                            id="monthly_salary"
                            v-model.number="form.monthly_salary"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                            :class="{ 'border-red-500': form.errors?.monthly_salary }"
                        />
                        <div v-if="form.errors?.monthly_salary" class="mt-1 text-sm text-red-600">
                            {{ form.errors.monthly_salary }}
                        </div>
                    </div>

                    <!-- Status -->
                    <div class="md:col-span-1">
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-2">
                            Status
                        </label>
                        <select
                            id="status"
                            v-model="form.status"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                            :class="{ 'border-red-500': form.errors?.status }"
                        >
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                        <div v-if="form.errors?.status" class="mt-1 text-sm text-red-600">
                            {{ form.errors.status }}
                        </div>
                    </div>

                    <!-- Address -->
                    <div class="md:col-span-2">
                        <label for="address" class="block text-sm font-medium text-gray-700 mb-2">
                            Address
                        </label>
                        <textarea
                            id="address"
                            v-model="form.address"
                            rows="3"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                            :class="{ 'border-red-500': form.errors?.address }"
                        ></textarea>
                        <div v-if="form.errors?.address" class="mt-1 text-sm text-red-600">
                            {{ form.errors.address }}
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end mt-6">
                    <Link :href="route('admin.employee.index')" class="mr-4 px-4 py-2 text-sm text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-md">
                        Cancel
                    </Link>
                    <button
                        type="submit"
                        :disabled="processing"
                        class="px-4 py-2 text-sm font-medium text-white bg-blue-500 hover:bg-blue-700 rounded-md disabled:opacity-50"
                    >
                        <span v-if="processing">Updating...</span>
                        <span v-else>Update Employee</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { useForm, Link, Head } from '@inertiajs/vue3';

const props = defineProps({
    employee: Object,
});

const form = useForm({
    name: props.employee?.name || '',
    email: props.employee?.email || '',
    phone: props.employee?.phone || '',
    address: props.employee?.address || '',
    role: props.employee?.role || '',
    monthly_salary: props.employee?.monthly_salary || 0,
    status: props.employee?.status || 'active',
});

const submit = () => {
    form.put(route('admin.employee.update', props.employee.id));
};
</script>
