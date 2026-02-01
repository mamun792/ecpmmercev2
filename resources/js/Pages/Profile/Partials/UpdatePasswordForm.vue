<script setup>
import InputError from "@/Components/InputError.vue";
import { useForm } from "@inertiajs/vue3";
import { ref, computed } from "vue";
import { Lock, Eye, EyeOff, Loader2, ShieldCheck, CheckCircle2, KeyRound, AlertTriangle } from "lucide-vue-next";

const passwordInput = ref(null);
const currentPasswordInput = ref(null);

const showCurrentPassword = ref(false);
const showNewPassword = ref(false);
const showConfirmPassword = ref(false);

const form = useForm({
    current_password: "",
    password: "",
    password_confirmation: "",
});

const passwordRequirements = computed(() => {
    return {
        length: form.password.length >= 8,
        match: form.password && form.password === form.password_confirmation,
        hasNumber: /[0-9]/.test(form.password),
        hasSpecial: /[!@#$%^&*(),.?":{}|<>]/.test(form.password),
    };
});

const updatePassword = () => {
    if (!passwordRequirements.value.match) return;
    
    form.put(route("password.update"), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onError: () => {
            if (form.errors.password) {
                form.reset("password", "password_confirmation");
                passwordInput.value.focus();
            }
            if (form.errors.current_password) {
                form.reset("current_password");
                currentPasswordInput.value.focus();
            }
        },
    });
};
</script>

<template>
    <section>
        <header class="mb-10">
            <h2 class="text-2xl font-black text-gray-900 dark:text-white tracking-tight">
                Security Settings
            </h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400 font-medium">
                Keep your account secure by using a strong, unique password.
            </p>
        </header>

        <form @submit.prevent="updatePassword" class="space-y-8">
            <!-- Current Password -->
            <div class="space-y-2">
                <label class="px-1 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Verify Current Password</label>
                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none transition-colors group-focus-within:text-blue-500 text-gray-400">
                        <Lock class="w-5 h-5" />
                    </div>
                    <input
                        ref="currentPasswordInput"
                        v-model="form.current_password"
                        :type="showCurrentPassword ? 'text' : 'password'"
                        class="w-full pl-12 pr-12 py-4 bg-gray-50 dark:bg-gray-800/50 border-0 rounded-[1.25rem] ring-1 ring-gray-200 dark:ring-gray-700 focus:ring-2 focus:ring-blue-500 transition-all font-bold text-gray-900 dark:text-white"
                        placeholder="Current password"
                        autocomplete="current-password"
                        required
                    />
                    <button
                        type="button"
                        @click="showCurrentPassword = !showCurrentPassword"
                        class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors"
                    >
                        <Eye v-if="showCurrentPassword" class="w-5 h-5" />
                        <EyeOff v-else class="w-5 h-5" />
                    </button>
                </div>
                <InputError :message="form.errors.current_password" />
            </div>

            <div class="grid md:grid-cols-2 gap-8">
                <!-- New Password -->
                <div class="space-y-2">
                    <label class="px-1 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">New Password</label>
                    <div class="relative group">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none transition-colors group-focus-within:text-blue-500 text-gray-400">
                            <KeyRound class="w-5 h-5" />
                        </div>
                        <input
                            ref="passwordInput"
                            v-model="form.password"
                            :type="showNewPassword ? 'text' : 'password'"
                            class="w-full pl-12 pr-12 py-4 bg-gray-50 dark:bg-gray-800/50 border-0 rounded-[1.25rem] ring-1 ring-gray-200 dark:ring-gray-700 focus:ring-2 focus:ring-blue-500 transition-all font-bold text-gray-900 dark:text-white"
                            placeholder="New secure password"
                            autocomplete="new-password"
                            required
                        />
                        <button
                            type="button"
                            @click="showNewPassword = !showNewPassword"
                            class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors"
                        >
                            <Eye v-if="showNewPassword" class="w-5 h-5" />
                            <EyeOff v-else class="w-5 h-5" />
                        </button>
                    </div>
                    
                    <!-- Real-time Validation Hints -->
                    <div class="flex flex-wrap gap-2 px-1 mt-2">
                        <div :class="['flex items-center gap-1.5 px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-tight transition-all', passwordRequirements.length ? 'bg-emerald-50 text-emerald-600' : 'bg-gray-100 text-gray-400']">
                            <CheckCircle2 class="w-3 h-3" /> 8+ Chars
                        </div>
                        <div :class="['flex items-center gap-1.5 px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-tight transition-all', passwordRequirements.hasNumber ? 'bg-emerald-50 text-emerald-600' : 'bg-gray-100 text-gray-400']">
                            <CheckCircle2 class="w-3 h-3" /> 1+ Number
                        </div>
                    </div>
                    <InputError :message="form.errors.password" />
                </div>

                <!-- Confirm Password -->
                <div class="space-y-2">
                    <label class="px-1 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Confirm New Password</label>
                    <div class="relative group">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none transition-colors group-focus-within:text-blue-500 text-gray-400">
                            <CheckCircle2 class="w-5 h-5" />
                        </div>
                        <input
                            v-model="form.password_confirmation"
                            :type="showConfirmPassword ? 'text' : 'password'"
                            class="w-full pl-12 pr-12 py-4 bg-gray-50 dark:bg-gray-800/50 border-0 rounded-[1.25rem] ring-1 ring-gray-200 dark:ring-gray-700 focus:ring-2 focus:ring-blue-500 transition-all font-bold text-gray-900 dark:text-white"
                            placeholder="Confirm new password"
                            autocomplete="new-password"
                            required
                        />
                        <button
                            type="button"
                            @click="showConfirmPassword = !showConfirmPassword"
                            class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors"
                        >
                            <Eye v-if="showConfirmPassword" class="w-5 h-5" />
                            <EyeOff v-else class="w-5 h-5" />
                        </button>
                    </div>

                    <!-- Real-time Matching Hint -->
                    <div class="px-1 mt-2">
                        <div v-if="form.password_confirmation" :class="['flex items-center gap-1.5 px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-tight transition-all w-fit', passwordRequirements.match ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-500']">
                            <template v-if="passwordRequirements.match">
                                <CheckCircle2 class="w-3 h-3" /> Passwords Match
                            </template>
                            <template v-else>
                                <AlertTriangle class="w-3 h-3" /> Not Matching
                            </template>
                        </div>
                    </div>
                    <InputError :message="form.errors.password_confirmation" />
                </div>
            </div>

            <!-- Security Notice (Legacy) -->
             <div v-if="!passwordRequirements.length || !passwordRequirements.hasNumber" class="p-6 bg-blue-50 dark:bg-blue-900/10 rounded-3xl border border-blue-100 dark:border-blue-800/50">
                <div class="flex items-center gap-4">
                    <ShieldCheck class="w-6 h-6 text-blue-600" />
                    <p class="text-[11px] font-bold text-blue-900 dark:text-blue-400 uppercase tracking-widest leading-relaxed">
                        Security Tip: Combine uppercase letters, numbers, and symbols for maximum protection.
                    </p>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="pt-8 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div v-if="form.recentlySuccessful" class="flex items-center gap-2 px-3 py-1.5 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-400 rounded-lg animate-in fade-in slide-in-from-left-2">
                        <CheckCircle2 class="w-4 h-4" />
                        <span class="text-[10px] font-black uppercase tracking-widest">Password Updated</span>
                    </div>
                </div>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="flex items-center gap-3 px-8 py-4 bg-gray-900 dark:bg-white text-white dark:text-gray-950 rounded-2xl font-black text-xs uppercase tracking-[0.1em] shadow-xl shadow-gray-900/20 dark:shadow-none hover:bg-blue-600 dark:hover:bg-blue-300 transition-all active:scale-95 disabled:opacity-50 group"
                >
                    <Loader2 v-if="form.processing" class="w-4 h-4 animate-spin" />
                    <ShieldCheck v-else class="w-4 h-4 group-hover:scale-110 transition-transform" />
                    Update Security
                </button>
            </div>
        </form>
    </section>
</template>
