<template>
    <Head title="Employee Salaries" />
    <AdminLayout>
        <div class="p-6">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-gray-900">Employee Salaries</h1>
                <button @click="showModal = true" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    Add Salary
                </button>
            </div>

            <!-- Search Input -->
            <div class="mb-6">
                <div class="max-w-md">
                    <label for="search" class="block text-sm font-medium text-gray-700 mb-2">
                        Search Salaries
                    </label>
                    <input
                        type="text"
                        id="search"
                        v-model="search"
                        @input="debouncedSearch"
                        placeholder="Search by employee name, email, month..."
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
                    <h2 class="text-lg font-semibold text-gray-900">All Salaries ({{ salaries.total }} total)</h2>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Month</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Paid Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created At</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="salary in salaries.data" :key="salary.id">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    {{ salary.employee.name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ salary.month }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    ৳{{ salary.amount }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ salary.paid_date ? new Date(salary.paid_date).toLocaleDateString() : 'Not Paid' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ new Date(salary.created_at).toLocaleDateString() }}
                                </td>
                            </tr>
                            <tr v-if="salaries.data.length === 0">
                                <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                                    No salaries found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Modal for adding salary -->
            <div v-if="showModal" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
<div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:p-0">
                    <!-- Background overlay -->
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showModal = false" aria-hidden="true"></div>

                    <!-- Modal panel -->
                    <span class="hidden sm:inline-block sm:align-middle" aria-hidden="true">&#8203;</span>

                    <div class="inline-block align-middle bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all h-auto sm:my-8 sm:align-middle sm:max-w-md sm:w-full relative">
                        <button @click="showModal = false" class="absolute top-0 right-0 p-2 text-gray-400 hover:text-gray-500">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="w-full">
                                <!-- <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 sm:mx-0 sm:h-10 sm:w-10">
                                    <svg class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                    </svg>
                                </div> -->
                                <div class="mt-3 sm:mt-0 sm:ml-4 sm:text-left w-full">
                                    <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                        Add Employee Salary
                                    </h3>
                                    <div class="mt-4">
                                        <form @submit.prevent="submitForm" class="space-y-4">
                                            <div>
                                                <label for="employee_id" class="block text-sm font-medium text-gray-700 mb-1">
                                                    Employee <span class="text-red-500">*</span>
                                                </label>
                                                <select
                                                    id="employee_id"
                                                    @change="fillAmount"
                                                    v-model="form.employee_id"
                                                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                                    required
                                                >
                                                    <option value="">Select Employee</option>
                                                    <option v-for="employee in employees" :key="employee.id" :value="employee.id">
                                                        {{ employee.name }}({{ employee.monthly_salary }})
                                                    </option>
                                                </select>
                                            </div>
                                            <div>
                                                <label for="month" class="block text-sm font-medium text-gray-700 mb-1">
                                                    Month <span class="text-red-500">*</span>
                                                </label>
                                                <select
                                                    id="month"
                                                    v-model="form.month"
                                                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                                    required
                                                >
                                                    <option value="">Select Month</option>
                                                    <option v-for="month in months" :key="month.value" :value="month.value">
                                                        {{ month.label }}
                                                    </option>
                                                </select>
                                            </div>
                                            <div>
                                                <label for="amount" class="block text-sm font-medium text-gray-700 mb-1">
                                                    Amount <span class="text-red-500">*</span>
                                                </label>
                                                <div class="mt-1 relative rounded-md shadow-sm">
                                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                        <span class="text-gray-500 sm:text-sm">৳</span>
                                                    </div>
                                                    <input
                                                        type="number"
                                                        id="amount"
                                                        v-model="form.amount"
                                                        step="0.01"
                                                        class="pl-7 mt-1 block w-full px-5 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                                        placeholder="0.00"
                                                        required
                                                    />
                                                </div>
                                            </div>
                                            <div>
                                                <label for="paid_date" class="block text-sm font-medium text-gray-700 mb-1">
                                                    Paid Date <span class="text-gray-400">(Optional)</span>
                                                </label>
                                                <input
                                                    type="date"
                                                    id="paid_date"
                                                    v-model="form.paid_date"
                                                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                                />
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button
                                type="submit"
                                @click="submitForm"
                                class="w-full rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm"
                            >
                                Add Salary
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { ref, reactive } from 'vue';
import { router, Head } from '@inertiajs/vue3';
import _ from 'lodash';

const props = defineProps({
    salaries: Object,
    employees: Array,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const showModal = ref(false);
const form = reactive({
    employee_id: '',
    month: '',
    amount: '',
    paid_date: '',
});

// fell amount automatically when employee is selected
const fillAmount = () => {
    const selectedEmployee = props.employees.find(emp => emp.id === form.employee_id);
    if (selectedEmployee) {
        form.amount = selectedEmployee.monthly_salary;
    } else {
        form.amount = '';
    }
};

const months = [
    { value: 'January 2025', label: 'January 2025' },
    { value: 'February 2025', label: 'February 2025' },
    { value: 'March 2025', label: 'March 2025' },
    { value: 'April 2025', label: 'April 2025' },
    { value: 'May 2025', label: 'May 2025' },
    { value: 'June 2025', label: 'June 2025' },
    { value: 'July 2025', label: 'July 2025' },
    { value: 'August 2025', label: 'August 2025' },
    { value: 'September 2025', label: 'September 2025' },
    { value: 'October 2025', label: 'October 2025' },
    { value: 'November 2025', label: 'November 2025' },
    { value: 'December 2025', label: 'December 2025' },
];

const submitForm = () => {
    router.post(route('admin.employee.salary.store'), form, {
        onSuccess: () => {
            showModal.value = false;
            form.employee_id = '';
            form.month = '';
            form.amount = '';
            form.paid_date = '';
        },
        preserveScroll: true,
    });
};

// Debounced search function
const debouncedSearch = _.debounce(() => {
    router.get(route('admin.employee.salary.index'), { search: search.value }, {
        preserveState: true,
        replace: true,
    });
}, 300);
</script>