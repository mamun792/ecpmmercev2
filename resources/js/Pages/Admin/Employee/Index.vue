<template>
    <Head title="Employee Management" />
    <AdminLayout>
        <div class="p-6">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-gray-900">Employee Management</h1>
                <Link :href="route('admin.employee.create')" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    Add New Employee
                </Link>
            </div>

            <!-- Search Input -->
            <div class="mb-6">
                <div class="max-w-md">
                    <label for="search" class="block text-sm font-medium text-gray-700 mb-2">
                        Search Employees
                    </label>
                    <input
                        type="text"
                        id="search"
                        v-model="search"
                        @input="debouncedSearch"
                        placeholder="Search by name, email, phone, designation..."
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                    />
                </div>
            </div>

            <!-- Flash Message -->
            <div v-if="$page.props.flash?.success" class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                {{ $page.props.flash.success }}
            </div>
            <div v-if="$page.props.flash?.error" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                {{ $page.props.flash.error }}
            </div>

            <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">All Employees ({{ employees.total }} total)</h2>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phone</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Monthly Salary</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="employee in employees.data" :key="employee.id">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    {{ employee.name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ employee.email || 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ employee.phone || 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ employee.role || 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    ${{ employee.monthly_salary || '0.00' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="['inline-flex px-2 py-1 text-xs font-semibold rounded-full', employee.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800']">
                                        {{ employee.status === 'active' ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex items-center gap-5">
                                        <Link :href="route('admin.employee.edit', employee.id)" class="text-indigo-600 hover:text-indigo-900">
                                        Edit
                                    </Link>
                                    <form @submit.prevent="deleteEmployee(employee.id)" class="inline">
                                        <button type="submit" class="text-red-600 hover:text-red-900" @click="confirmDelete = employee.id">
                                            Delete
                                        </button>
                                    </form>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="employees.data.length === 0">
                                <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                    No employees found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { ref, watch } from 'vue';
import { Link, router, Head } from '@inertiajs/vue3';
import _ from 'lodash';

const props = defineProps({
    employees: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const updateEmployeeStatus = (id, status) => {
    if (confirm(`Are you sure you want to ${status ? 'activate' : 'deactivate'} this employee?`)) {
        router.put(route('admin.employee.update', id), {
            status: status
        }, {
            preserveScroll: true,
        });
    }
};

const deleteEmployee = (id) => {
    if (confirm('Are you sure you want to delete this employee?')) {
        router.delete(route('admin.employee.destroy', id));
    }
};

// Debounced search function
const debouncedSearch = _.debounce(() => {
    router.get(route('admin.employee.index'), { search: search.value }, {
        preserveState: true,
        replace: true,
    });
}, 300);

// Watch for search changes (fallback if debounced search fails)
watch(search, (newValue, oldValue) => {
    if (newValue !== oldValue) {
        // This will trigger the debounced function
    }
});
</script>
