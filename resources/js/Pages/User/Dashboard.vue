<script setup>
import { Head, Link, useForm } from "@inertiajs/vue3";

defineProps({
    user: {
        type: Object,
        required: true,
    },
    recentOrders: {
        type: Array,
        default: () => [],
    },
    stats: {
        type: Object,
        default: () => ({
            totalOrders: 0,
            pendingOrders: 0,
            completedOrders: 0,
            canceledOrders: 0,
        }),
    },
});

const logoutForm = useForm({});

const logout = () => {
    logoutForm.post(route("logout"));
};
</script>

<template>
    <Head title="My Dashboard" />

    <div class="min-h-screen bg-slate-50">
        <!-- Navigation -->
        <nav class="bg-white shadow-sm border-b border-slate-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <Link
                            :href="route('home')"
                            class="flex items-center gap-3"
                        >
                            <div
                                class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="18"
                                    height="18"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2.5"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    class="text-white"
                                >
                                    <path
                                        d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"
                                    ></path>
                                </svg>
                            </div>
                            <span class="text-lg font-bold text-slate-900"
                                >E-Commerce</span
                            >
                        </Link>
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="flex items-center gap-3">
                            <div
                                class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center"
                            >
                                <span
                                    class="text-sm font-semibold text-blue-600"
                                >
                                    {{ user.name?.charAt(0).toUpperCase() }}
                                </span>
                            </div>
                            <span class="text-sm font-medium text-slate-700">{{
                                user.name
                            }}</span>
                        </div>
                        <button
                            @click="logout"
                            class="px-4 py-2 text-sm font-medium text-slate-600 hover:text-slate-900 hover:bg-slate-100 rounded-lg transition-colors"
                        >
                            Logout
                        </button>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
            <!-- Welcome Section -->
            <div class="mb-8">
                <h1 class="text-2xl font-bold text-slate-900">
                    Welcome back, {{ user.name }}!
                </h1>
                <p class="mt-1 text-sm text-slate-500">
                    Here's what's happening with your account.
                </p>
            </div>

            <!-- Stats Cards -->
            <div
                class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8"
            >
                <!-- Total Orders -->
                <div
                    class="bg-white rounded-xl shadow-sm border border-slate-200 p-6"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-slate-500">
                                Total Orders
                            </p>
                            <p class="text-3xl font-bold text-slate-900 mt-1">
                                {{ stats.totalOrders }}
                            </p>
                        </div>
                        <div
                            class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                width="24"
                                height="24"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                class="text-blue-600"
                            >
                                <path
                                    d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"
                                ></path>
                                <polyline
                                    points="3.27 6.96 12 12.01 20.73 6.96"
                                ></polyline>
                                <line x1="12" y1="22.08" x2="12" y2="12"></line>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Pending Orders -->
                <div
                    class="bg-white rounded-xl shadow-sm border border-slate-200 p-6"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-slate-500">
                                Pending Orders
                            </p>
                            <p class="text-3xl font-bold text-slate-900 mt-1">
                                {{ stats.pendingOrders }}
                            </p>
                        </div>
                        <div
                            class="w-12 h-12 bg-yellow-100 rounded-xl flex items-center justify-center"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                width="24"
                                height="24"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                class="text-yellow-600"
                            >
                                <circle cx="12" cy="12" r="10"></circle>
                                <polyline points="12 6 12 12 16 14"></polyline>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Completed Orders -->
                <div
                    class="bg-white rounded-xl shadow-sm border border-slate-200 p-6"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-slate-500">
                                Completed Orders
                            </p>
                            <p class="text-3xl font-bold text-slate-900 mt-1">
                                {{ stats.completedOrders }}
                            </p>
                        </div>
                        <div
                            class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                width="24"
                                height="24"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                class="text-green-600"
                            >
                                <path
                                    d="M22 11.08V12a10 10 0 1 1-5.93-9.14"
                                ></path>
                                <polyline
                                    points="22 4 12 14.01 9 11.01"
                                ></polyline>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Canceled Orders -->
                <div
                    class="bg-white rounded-xl shadow-sm border border-slate-200 p-6"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-slate-500">
                                Canceled Orders
                            </p>
                            <p class="text-3xl font-bold text-slate-900 mt-1">
                                {{ stats.canceledOrders }}
                            </p>
                        </div>
                        <div
                            class="w-12 h-12 bg-red-100 rounded-xl flex items-center justify-center"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                width="24"
                                height="24"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                class="text-red-600"
                            >
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="15" y1="9" x2="9" y2="15"></line>
                                <line x1="9" y1="9" x2="15" y2="15"></line>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions & Recent Orders -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Quick Actions -->
                <div
                    class="bg-white rounded-xl shadow-sm border border-slate-200 p-6"
                >
                    <h2 class="text-lg font-semibold text-slate-900 mb-4">
                        Quick Actions
                    </h2>
                    <div class="space-y-3">
                        <Link
                            :href="route('home')"
                            class="flex items-center gap-3 p-3 rounded-lg bg-slate-50 hover:bg-slate-100 transition-colors"
                        >
                            <div
                                class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="20"
                                    height="20"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    class="text-blue-600"
                                >
                                    <circle cx="9" cy="21" r="1"></circle>
                                    <circle cx="20" cy="21" r="1"></circle>
                                    <path
                                        d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"
                                    ></path>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-slate-900">
                                    Shop Now
                                </p>
                                <p class="text-xs text-slate-500">
                                    Browse products
                                </p>
                            </div>
                        </Link>

                        <a
                            href="#"
                            class="flex items-center gap-3 p-3 rounded-lg bg-slate-50 hover:bg-slate-100 transition-colors"
                        >
                            <div
                                class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="20"
                                    height="20"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    class="text-purple-600"
                                >
                                    <path
                                        d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"
                                    ></path>
                                    <circle cx="12" cy="7" r="4"></circle>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-slate-900">
                                    Edit Profile
                                </p>
                                <p class="text-xs text-slate-500">
                                    Update your info
                                </p>
                            </div>
                        </a>

                        <a
                            href="#"
                            class="flex items-center gap-3 p-3 rounded-lg bg-slate-50 hover:bg-slate-100 transition-colors"
                        >
                            <div
                                class="w-10 h-10 bg-pink-100 rounded-lg flex items-center justify-center"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="20"
                                    height="20"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    class="text-pink-600"
                                >
                                    <path
                                        d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"
                                    ></path>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-slate-900">
                                    Wishlist
                                </p>
                                <p class="text-xs text-slate-500">
                                    View saved items
                                </p>
                            </div>
                        </a>
                    </div>
                </div>

                <!-- Recent Orders -->
                <div
                    class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-slate-200 p-6"
                >
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-slate-900">
                            Recent Orders
                        </h2>
                        <a
                            href="#"
                            class="text-sm text-blue-600 hover:text-blue-700 font-medium"
                            >View All</a
                        >
                    </div>

                    <div v-if="recentOrders.length > 0" class="space-y-4">
                        <div
                            v-for="order in recentOrders"
                            :key="order.id"
                            class="flex items-center justify-between p-4 rounded-lg bg-slate-50"
                        >
                            <div class="flex items-center gap-4">
                                <div
                                    class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center"
                                >
                                    <span
                                        class="text-sm font-semibold text-blue-600"
                                        >#{{ order.id }}</span
                                    >
                                </div>
                                <div>
                                    <p
                                        class="text-sm font-medium text-slate-900"
                                    >
                                        Order #{{ order.id }}
                                    </p>
                                    <p class="text-xs text-slate-500">
                                        {{ order.created_at }}
                                    </p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-medium text-slate-900">
                                    ৳{{ order.total || "0.00" }}
                                </p>
                                <span
                                    :class="{
                                        'bg-yellow-100 text-yellow-700':
                                            order.status === 'pending',
                                        'bg-blue-100 text-blue-700':
                                            order.status === 'processing',
                                        'bg-green-100 text-green-700':
                                            order.status === 'delivered',
                                        'bg-red-100 text-red-700':
                                            order.status === 'cancelled',
                                    }"
                                    class="text-xs font-medium px-2 py-1 rounded-full"
                                >
                                    {{ order.status }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div v-else class="text-center py-12">
                        <div
                            class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-4"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                width="32"
                                height="32"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.5"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                class="text-slate-400"
                            >
                                <path
                                    d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"
                                ></path>
                            </svg>
                        </div>
                        <h3 class="text-sm font-medium text-slate-900 mb-1">
                            No orders yet
                        </h3>
                        <p class="text-sm text-slate-500">
                            Start shopping to see your orders here.
                        </p>
                        <Link
                            :href="route('home')"
                            class="inline-flex items-center gap-2 mt-4 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                width="16"
                                height="16"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >
                                <circle cx="9" cy="21" r="1"></circle>
                                <circle cx="20" cy="21" r="1"></circle>
                                <path
                                    d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"
                                ></path>
                            </svg>
                            Start Shopping
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Account Info -->
            <div
                class="mt-6 bg-white rounded-xl shadow-sm border border-slate-200 p-6"
            >
                <h2 class="text-lg font-semibold text-slate-900 mb-4">
                    Account Information
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <p class="text-sm text-slate-500">Name</p>
                        <p class="text-sm font-medium text-slate-900">
                            {{ user.name }}
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-slate-500">Email</p>
                        <p class="text-sm font-medium text-slate-900">
                            {{ user.email }}
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-slate-500">Phone</p>
                        <p class="text-sm font-medium text-slate-900">
                            {{ user.phone_number || "Not provided" }}
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-slate-500">Address</p>
                        <p class="text-sm font-medium text-slate-900">
                            {{ user.address || "Not provided" }}
                        </p>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>
