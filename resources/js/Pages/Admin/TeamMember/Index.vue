<template>
    <Head title="Team Member Management" />
    <AdminLayout>
        <div class="p-6">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-gray-900">Team Member Management</h1>
                <button @click="openCreateModal" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    Add New Team Member
                </button>
            </div>

            <!-- Search Input -->
            <div class="mb-6">
                <div class="max-w-md">
                    <label for="search" class="block text-sm font-medium text-gray-700 mb-2">
                        Search Team Members
                    </label>
                    <input
                        type="text"
                        id="search"
                        v-model="search"
                        @input="debouncedSearch"
                        placeholder="Search by name, designation..."
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
                    <h2 class="text-lg font-semibold text-gray-900">All Team Members ({{ teamMembers.total }} total)</h2>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Image</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Designation</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="teamMember in teamMembers.data" :key="teamMember.id">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <img v-if="teamMember.image" :src="teamMember.image" alt="Team Member Image" class="w-12 h-12 rounded-full object-cover">
                                    <div v-else class="w-12 h-12 rounded-full bg-gray-300 flex items-center justify-center">
                                        <span class="text-gray-600 text-sm">{{ teamMember.name.charAt(0) }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    {{ teamMember.name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ teamMember.designation }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex items-center gap-5">
                                        <button @click="openEditModal(teamMember)" class="text-indigo-600 hover:text-indigo-900">
                                            Edit
                                        </button>
                                        <form @submit.prevent="deleteTeamMember(teamMember.id)" class="inline">
                                            <button type="submit" class="text-red-600 hover:text-red-900">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="teamMembers.data.length === 0">
                                <td colspan="4" class="px-6 py-4 text-center text-gray-500">
                                    No team members found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Modal -->
            <TeamMemberModal
                :show="showModal"
                :teamMember="selectedTeamMember"
                :isEditing="isEditing"
                @close="closeModal"
                @saved="handleSaved"
            />
        </div>
    </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { ref, watch } from 'vue';
import { Link, router, Head } from '@inertiajs/vue3';
import _ from 'lodash';
import TeamMemberModal from './TeamMemberModal.vue';

const props = defineProps({
    teamMembers: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const showModal = ref(false);
const selectedTeamMember = ref(null);
const isEditing = ref(false);

const openCreateModal = () => {
    selectedTeamMember.value = null;
    isEditing.value = false;
    showModal.value = true;
};

const openEditModal = (teamMember) => {
    selectedTeamMember.value = teamMember;
    isEditing.value = true;
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    selectedTeamMember.value = null;
    isEditing.value = false;
};

const handleSaved = () => {
    closeModal();
    router.reload();
};

const deleteTeamMember = (id) => {
    if (confirm('Are you sure you want to delete this team member?')) {
        router.delete(route('admin.team-member.destroy', id));
    }
};

// Debounced search function
const debouncedSearch = _.debounce(() => {
    router.get(route('admin.team-member.index'), { search: search.value }, {
        preserveState: true,
        replace: true,
    });
}, 300);
</script>