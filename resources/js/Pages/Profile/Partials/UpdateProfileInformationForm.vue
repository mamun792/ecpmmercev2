<script setup>
import InputError from '@/Components/InputError.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { User, Mail, CheckCircle2, Loader2, Save } from 'lucide-vue-next';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const user = usePage().props.auth.user;

const form = useForm({
    name: user.name,
    email: user.email,
});
</script>

<template>
    <section>
        <header class="mb-10">
            <h2 class="text-2xl font-black text-gray-900 dark:text-white tracking-tight">
                Profile Information
            </h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400 font-medium">
                Update your account's public identity and contact email.
            </p>
        </header>

        <form
            @submit.prevent="form.patch(route('admin.profile.update'))"
            class="space-y-8"
        >
            <div class="grid md:grid-cols-2 gap-8">
                <!-- Name Field -->
                <div class="space-y-2">
                    <label class="px-1 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Profile Name</label>
                    <div class="relative group">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none transition-colors group-focus-within:text-blue-500 text-gray-400">
                            <User class="w-5 h-5" />
                        </div>
                        <input
                            v-model="form.name"
                            type="text"
                            class="w-full pl-12 pr-4 py-4 bg-gray-50 dark:bg-gray-800/50 border-0 rounded-[1.25rem] ring-1 ring-gray-200 dark:ring-gray-700 focus:ring-2 focus:ring-blue-500 transition-all font-bold text-gray-900 dark:text-white"
                            placeholder="Your full name"
                            required
                        />
                    </div>
                    <InputError :message="form.errors.name" />
                </div>

                <!-- Email Field -->
                <div class="space-y-2">
                    <label class="px-1 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Email Address</label>
                    <div class="relative group">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none transition-colors group-focus-within:text-blue-500 text-gray-400">
                            <Mail class="w-5 h-5" />
                        </div>
                        <input
                            v-model="form.email"
                            type="email"
                            class="w-full pl-12 pr-4 py-4 bg-gray-50 dark:bg-gray-800/50 border-0 rounded-[1.25rem] ring-1 ring-gray-200 dark:ring-gray-700 focus:ring-2 focus:ring-blue-500 transition-all font-bold text-gray-900 dark:text-white"
                            placeholder="email@example.com"
                            required
                        />
                    </div>
                    <InputError :message="form.errors.email" />
                </div>
            </div>

            <!-- Email Verification Alert -->
            <div v-if="mustVerifyEmail && user.email_verified_at === null" class="p-6 bg-amber-50 dark:bg-amber-900/10 rounded-3xl border border-amber-100 dark:border-amber-800/50">
                <div class="flex items-start gap-4">
                    <div class="p-2 bg-amber-100 dark:bg-amber-800 rounded-xl mt-0.5">
                        <Mail class="w-5 h-5 text-amber-700 dark:text-amber-400" />
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-bold text-amber-900 dark:text-amber-200">Email Unverified</p>
                        <p class="text-xs text-amber-700/80 dark:text-amber-400/80 font-medium mt-1">Please verify your email to ensure account security and access all features.</p>
                        <Link
                            :href="route('verification.send')"
                            method="post"
                            as="button"
                            class="mt-3 text-xs font-black uppercase tracking-tighter text-amber-900 dark:text-amber-200 underline hover:no-underline transition-all"
                        >
                            Resend Verification Email →
                        </Link>
                    </div>
                </div>

                <div
                    v-show="status === 'verification-link-sent'"
                    class="mt-4 p-3 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-400 text-[10px] font-bold rounded-xl border border-emerald-100 dark:border-emerald-800/50 flex items-center gap-2"
                >
                    <CheckCircle2 class="w-3.5 h-3.5" />
                    A new verification link has been sent to your email address.
                </div>
            </div>

            <!-- Form Actions -->
            <div class="pt-8 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div v-if="form.recentlySuccessful" class="flex items-center gap-2 px-3 py-1.5 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-400 rounded-lg animate-in fade-in slide-in-from-left-2">
                        <CheckCircle2 class="w-4 h-4" />
                        <span class="text-[10px] font-black uppercase tracking-widest">Saved Successfully</span>
                    </div>
                </div>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="flex items-center gap-3 px-8 py-4 bg-gray-900 dark:bg-white text-white dark:text-gray-950 rounded-2xl font-black text-xs uppercase tracking-[0.1em] shadow-xl shadow-gray-900/20 dark:shadow-none hover:bg-blue-600 dark:hover:bg-blue-300 transition-all active:scale-95 disabled:opacity-50 group"
                >
                    <Loader2 v-if="form.processing" class="w-4 h-4 animate-spin" />
                    <Save v-else class="w-4 h-4 group-hover:scale-110 transition-transform" />
                    Update Profile
                </button>
            </div>
        </form>
    </section>
</template>
