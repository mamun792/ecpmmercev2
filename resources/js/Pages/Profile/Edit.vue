<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head } from '@inertiajs/vue3';
import { User, ShieldCheck, Bell, CreditCard, Layout } from 'lucide-vue-next';
import { ref } from 'vue';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const activeTab = ref('profile');

const tabs = [
    { id: 'profile', label: 'Basic Info', icon: User },
    { id: 'security', label: 'Security', icon: ShieldCheck },
    // { id: 'notifications', label: 'Notifications', icon: Bell },
];
</script>

<template>
    <Head title="Profile Settings" />

    <AdminLayout>
        <div class="max-w-[1200px] mx-auto py-8 px-4 sm:px-6 lg:px-8">
            <!-- Professional Header -->
            <div class="mb-12">
                <h1 class="text-4xl font-black text-gray-900 dark:text-white tracking-tight mb-2">Account Settings</h1>
                <p class="text-gray-500 dark:text-gray-400 font-medium font-serif">Manage your personal information, security, and preferences.</p>
            </div>

            <div class="flex flex-col lg:flex-row gap-8">
                <!-- Vertical Tab Navigation -->
                <aside class="lg:w-64 flex-shrink-0">
                    <nav class="space-y-1">
                        <button
                            v-for="tab in tabs"
                            :key="tab.id"
                            @click="activeTab = tab.id"
                            :class="[
                                activeTab === tab.id
                                    ? 'bg-blue-600 text-white shadow-lg shadow-blue-500/20'
                                    : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 hover:text-gray-900 dark:hover:text-white',
                                'w-full flex items-center gap-3 px-4 py-3.5 text-sm font-bold rounded-2xl transition-all duration-200'
                            ]"
                        >
                            <component :is="tab.icon" class="w-5 h-5" />
                            {{ tab.label }}
                        </button>
                    </nav>

                    <div class="mt-8 p-6 bg-gradient-to-br from-gray-900 to-gray-800 dark:from-gray-800 dark:to-gray-900 rounded-[2rem] text-white shadow-xl">
                        <Layout class="w-8 h-8 opacity-50 mb-4" />
                        <h4 class="font-black text-sm mb-2 uppercase tracking-widest">Support</h4>
                        <p class="text-[11px] text-gray-400 leading-relaxed font-medium">Need help with your account? Our support team is available 24/7.</p>
                        <button class="mt-4 text-[10px] font-black uppercase tracking-tighter text-blue-400 hover:text-blue-300 transition-colors">Contact Expert →</button>
                    </div>
                </aside>

                <!-- Content Area -->
                <main class="flex-1">
                    <transition
                        enter-active-class="transition ease-out duration-300"
                        enter-from-class="opacity-0 translate-y-4"
                        enter-to-class="opacity-100 translate-y-0"
                        mode="out-in"
                    >
                        <div :key="activeTab">
                            <!-- Basic Information Section -->
                            <div v-if="activeTab === 'profile'" class="bg-white dark:bg-gray-900 p-8 sm:p-10 rounded-[2.5rem] shadow-xl shadow-gray-200/50 dark:shadow-none border border-gray-100 dark:border-gray-800">
                                <UpdateProfileInformationForm
                                    :must-verify-email="mustVerifyEmail"
                                    :status="status"
                                />
                            </div>

                            <!-- Security Section -->
                            <div v-if="activeTab === 'security'" class="bg-white dark:bg-gray-900 p-8 sm:p-10 rounded-[2.5rem] shadow-xl shadow-gray-200/50 dark:shadow-none border border-gray-100 dark:border-gray-800">
                                <UpdatePasswordForm />
                            </div>
                        </div>
                    </transition>
                </main>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
.font-serif {
    font-family: 'Inter', system-ui, -apple-system, sans-serif;
}
</style>
