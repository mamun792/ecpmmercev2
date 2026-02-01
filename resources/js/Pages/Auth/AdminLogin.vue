<script setup>
import InputError from "@/Components/InputError.vue";
import { Head, Link, useForm } from "@inertiajs/vue3";
import { ref } from "vue";

defineProps({
    status: {
        type: String,
    },
});

const form = useForm({
    email: "",
    password: "",
    remember: false,
});

const showPassword = ref(false);

const togglePasswordVisibility = () => {
    showPassword.value = !showPassword.value;
};

const submit = () => {
    form.post(route("admin.login"), {
        onFinish: () => form.reset("password"),
    });
};
</script>

<template>
    <Head title="Admin Sign In" />

    <div class="min-h-screen grid grid-cols-1 md:grid-cols-2 bg-white">
            <!-- Left Side: Brand/Visual -->
            <div
                class="hidden md:flex flex-col justify-between bg-[#0F172A] relative overflow-hidden p-12 lg:p-16"
            >
                <!-- Decorative Gradients -->
                <div
                    class="absolute top-0 left-0 w-full h-full overflow-hidden z-0"
                >
                    <div
                        class="absolute top-[-20%] right-[-10%] w-[500px] h-[500px] rounded-full bg-red-600/20 blur-[100px]"
                    ></div>
                    <div
                        class="absolute bottom-[-20%] left-[-10%] w-[500px] h-[500px] rounded-full bg-primary/10 blur-[100px]"
                    ></div>
                </div>

                <!-- Logo Section -->
                <div class="relative z-10">
                    <div class="flex items-center gap-3 mb-8">
                        <div
                            class="w-10 h-10 bg-red-600 rounded-lg flex items-center justify-center shadow-lg shadow-red-600/30"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                width="24"
                                height="24"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2.5"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                class="text-white"
                            >
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                            </svg>
                        </div>
                        <span
                            class="text-2xl font-bold text-white tracking-tight"
                            >Admin Panel</span
                        >
                    </div>
                </div>

                <!-- Main Text -->
                <div class="relative z-10 max-w-lg">
                    <h1
                        class="text-4xl lg:text-5xl font-extrabold text-white tracking-tight leading-tight mb-6"
                    >
                        Admin Control Center
                    </h1>
                    <p class="text-lg text-slate-400 font-medium h-20">
                        Access the administrative dashboard to manage products, orders, 
                        customers, and all aspects of your e-commerce platform.
                    </p>
                </div>

                <!-- Badge -->
                <div class="relative z-10">
                    <div
                        class="flex items-center gap-4 p-4 rounded-xl bg-white/5 backdrop-blur-sm border border-white/10 w-fit"
                    >
                        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-red-400 to-primary flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-white">
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                                <polyline points="9 12 11 14 15 10"></polyline>
                            </svg>
                        </div>
                        <div>
                            <div class="text-white text-sm font-bold">
                                Secure Access
                            </div>
                            <div class="text-slate-400 text-xs">
                                Admin credentials required
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Side: Login Form -->
            <div
                class="flex flex-col justify-center items-center p-8 md:p-12 lg:p-24 bg-white relative"
            >
                <div
                    v-if="status"
                    class="absolute top-6 left-0 right-0 mx-auto w-full max-w-md bg-green-50 text-green-700 p-4 rounded-lg text-sm font-medium text-center border border-green-100"
                >
                    {{ status }}
                </div>

                <div class="w-full max-w-sm space-y-8">
                    <div class="text-center md:text-left">
                        <h2
                            class="text-3xl font-bold tracking-tight text-slate-900"
                        >
                            Admin Login
                        </h2>
                        <p class="mt-2 text-sm text-slate-500">
                            Please enter your admin credentials to access the dashboard.
                        </p>
                    </div>

                    <form @submit.prevent="submit" class="space-y-6">
                        <!-- Email Field -->
                        <div class="space-y-2">
                            <label
                                for="email"
                                class="block text-sm font-medium text-slate-700"
                                >Email</label
                            >
                            <div class="relative">
                                <div
                                    class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"
                                >
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        width="18"
                                        height="18"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        class="text-slate-400"
                                    >
                                        <rect
                                            width="20"
                                            height="16"
                                            x="2"
                                            y="4"
                                            rx="2"
                                        ></rect>
                                        <path
                                            d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"
                                        ></path>
                                    </svg>
                                </div>
                                <input
                                    id="email"
                                    type="email"
                                    v-model="form.email"
                                    required
                                    autofocus
                                    autocomplete="username"
                                    placeholder="admin@example.com"
                                    class="block w-full pl-10 pr-4 py-3 rounded-xl border border-slate-200 bg-slate-50/50 text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-all duration-200"
                                />
                            </div>
                            <InputError class="mt-2" :message="form.errors.email" />
                        </div>

                        <!-- Password Field -->
                        <div class="space-y-2">
                            <label
                                for="password"
                                class="block text-sm font-medium text-slate-700"
                                >Password</label
                            >
                            <div class="relative">
                                <div
                                    class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"
                                >
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        width="18"
                                        height="18"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        class="text-slate-400"
                                    >
                                        <rect
                                            width="18"
                                            height="11"
                                            x="3"
                                            y="11"
                                            rx="2"
                                            ry="2"
                                        ></rect>
                                        <path
                                            d="M7 11V7a5 5 0 0 1 10 0v4"
                                        ></path>
                                    </svg>
                                </div>
                                <input
                                    id="password"
                                    :type="showPassword ? 'text' : 'password'"
                                    v-model="form.password"
                                    required
                                    autocomplete="current-password"
                                    placeholder="••••••••"
                                    class="block w-full pl-10 pr-12 py-3 rounded-xl border border-slate-200 bg-slate-50/50 text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-all duration-200"
                                />
                                <button
                                    type="button"
                                    @click="togglePasswordVisibility"
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 transition-colors"
                                >
                                    <svg
                                        v-if="!showPassword"
                                        xmlns="http://www.w3.org/2000/svg"
                                        width="18"
                                        height="18"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    >
                                        <path
                                            d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"
                                        ></path>
                                        <circle cx="12" cy="12" r="3"></circle>
                                    </svg>
                                    <svg
                                        v-else
                                        xmlns="http://www.w3.org/2000/svg"
                                        width="18"
                                        height="18"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    >
                                        <path
                                            d="M9.88 9.88a3 3 0 1 0 4.24 4.24"
                                        ></path>
                                        <path
                                            d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"
                                        ></path>
                                        <path
                                            d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"
                                        ></path>
                                        <line
                                            x1="2"
                                            x2="22"
                                            y1="2"
                                            y2="22"
                                        ></line>
                                    </svg>
                                </button>
                            </div>
                            <InputError class="mt-2" :message="form.errors.password" />
                        </div>

                        <!-- Remember Me -->
                        <div class="flex items-center justify-between">
                            <label class="flex items-center gap-2 cursor-pointer group">
                                <input
                                    type="checkbox"
                                    v-model="form.remember"
                                    class="w-4 h-4 rounded border-slate-300 text-red-600 focus:ring-red-500/20 transition-colors"
                                />
                                <span class="text-sm text-slate-600 group-hover:text-slate-900 transition-colors">
                                    Remember me
                                </span>
                            </label>
                        </div>

                        <!-- Submit Button -->
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="w-full py-3.5 px-4 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-xl shadow-lg shadow-red-600/25 focus:outline-none focus:ring-2 focus:ring-red-500/50 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                        >
                            <svg
                                v-if="form.processing"
                                class="animate-spin h-5 w-5 text-white"
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                            >
                                <circle
                                    class="opacity-25"
                                    cx="12"
                                    cy="12"
                                    r="10"
                                    stroke="currentColor"
                                    stroke-width="4"
                                ></circle>
                                <path
                                    class="opacity-75"
                                    fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                ></path>
                            </svg>
                            <span>{{ form.processing ? 'Signing in...' : 'Sign In to Admin' }}</span>
                        </button>
                    </form>

                    <!-- Back to Home -->
                    <div class="text-center pt-4">
                        <Link
                            :href="route('home')"
                            class="text-sm text-slate-500 hover:text-slate-700 transition-colors inline-flex items-center gap-1"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="m12 19-7-7 7-7"></path>
                                <path d="M19 12H5"></path>
                            </svg>
                            Back to Home
                        </Link>
                    </div>

                    <!-- User Login Link -->
                    <div class="text-center border-t border-slate-100 pt-6">
                        <p class="text-sm text-slate-500">
                            Not an admin?
                            <Link
                                :href="route('login')"
                                class="text-blue-600 hover:text-blue-700 font-medium transition-colors"
                            >
                                User Login
                            </Link>
                        </p>
                    </div>
                </div>
            </div>
        </div>
</template>
