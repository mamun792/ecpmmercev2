<script setup>
import { ref, computed } from "vue";
import { Head, Link, useForm, usePage } from "@inertiajs/vue3";
import FrontendLayout from "@/Layouts/FrontendLayout.vue";
import { useToast } from "@/Composables/useToast";

const { success, error: showError } = useToast();

const props = defineProps({
    user: {
        type: Object,
        required: true,
    },
    orders: {
        type: Object,
        default: () => ({ data: [] }),
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

const showCurrentPassword = ref(false);
const showNewPassword = ref(false);
const showConfirmPassword = ref(false);

const page = usePage();
const activeTab = ref("orders");

const logoutForm = useForm({});

// Profile form
const profileForm = useForm({
    name: props.user.name,
    email: props.user.email,
    phone_number: props.user.phone_number,
    address: props.user.address,
});

// Password form
const passwordForm = useForm({
    current_password: "",
    password: "",
    password_confirmation: "",
});

const logout = () => {
    logoutForm.post(route("logout"));
};

// Update profile
const updateProfile = () => {
    profileForm.put(route("user.profile.update"), {
        preserveScroll: true,
        onSuccess: () => {
            success("Profile updated successfully.");
            //profileForm.reset();
        },
    });
};

// Update password
const updatePassword = () => {
    passwordForm.put(route("user.password.update"), {
        preserveScroll: true,
        onSuccess: () => {
            success("Password updated successfully.");
            passwordForm.reset();
        },
        onError: () => {
            passwordForm.reset(
                "current_password",
                "password",
                "password_confirmation"
            );
        },
    });
};

// Format date
const formatDate = (dateString) => {
    if (!dateString) return "";
    const date = new Date(dateString);
    return date.toLocaleDateString("en-US", {
        year: "numeric",
        month: "short",
        day: "numeric",
    });
};

// Get status badge class
const getStatusClass = (status) => {
    const classes = {
        pending: "bg-yellow-100 text-yellow-800",
        processing: "bg-blue-100 text-blue-800",
        shipped: "bg-purple-100 text-purple-800",
        delivered: "bg-green-100 text-green-800",
        cancelled: "bg-red-100 text-red-800",
    };
    return classes[status] || "bg-gray-100 text-gray-800";
};

// Get status icon
const getStatusIcon = (status) => {
    const icons = {
        pending: "M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z",
        processing:
            "M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15",
        shipped:
            "M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0",
        delivered: "M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z",
        cancelled:
            "M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z",
    };
    return icons[status] || icons["pending"];
};
</script>

<template>
    <Head title="My Dashboard" />
    <FrontendLayout>
        <!-- <pre>{{ orders }}</pre> -->
        <div class="min-h-screen bg-gray-50 py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
                    <!-- Sidebar -->
                    <div class="lg:col-span-1">
                        <div
                            class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden sticky top-4"
                        >
                            <!-- User Profile Card -->
                            <div
                                class="p-6 bg-gradient-to-br from-primary to-primary/90 text-white"
                            >
                                <div class="flex items-center space-x-4">
                                    <div
                                        class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center"
                                    >
                                        <span class="text-2xl font-bold">
                                            {{
                                                user.name
                                                    ?.charAt(0)
                                                    .toUpperCase()
                                            }}
                                        </span>
                                    </div>
                                    <div>
                                        <h2 class="text-lg font-semibold">
                                            {{ user.name }}
                                        </h2>
                                        <p class="text-white/70 text-sm">
                                            {{ user.email }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Navigation Menu -->
                            <nav class="p-4 space-y-1">
                                <button
                                    @click="activeTab = 'orders'"
                                    :class="[
                                        'w-full flex items-center px-4 py-3 text-sm font-medium rounded-xl transition-colors',
                                        activeTab === 'orders'
                                            ? 'bg-primary/10 text-primary'
                                            : 'text-gray-600 hover:bg-gray-50',
                                    ]"
                                >
                                    <svg
                                        class="w-5 h-5 mr-3"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"
                                        />
                                    </svg>
                                    My Orders
                                </button>

                                <button
                                    @click="activeTab = 'profile'"
                                    :class="[
                                        'w-full flex items-center px-4 py-3 text-sm font-medium rounded-xl transition-colors',
                                        activeTab === 'profile'
                                            ? 'bg-primary/10 text-primary'
                                            : 'text-gray-600 hover:bg-gray-50',
                                    ]"
                                >
                                    <svg
                                        class="w-5 h-5 mr-3"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                                        />
                                    </svg>
                                    Edit Profile
                                </button>

                                <button
                                    @click="activeTab = 'security'"
                                    :class="[
                                        'w-full flex items-center px-4 py-3 text-sm font-medium rounded-xl transition-colors',
                                        activeTab === 'security'
                                            ? 'bg-primary/10 text-primary'
                                            : 'text-gray-600 hover:bg-gray-50',
                                    ]"
                                >
                                    <svg
                                        class="w-5 h-5 mr-3"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
                                        />
                                    </svg>
                                    Change Password
                                </button>

                                <hr class="my-2" />

                                <button
                                    @click="logout"
                                    class="w-full flex items-center px-4 py-3 text-sm font-medium text-red-600 hover:bg-red-50 rounded-xl transition-colors"
                                >
                                    <svg
                                        class="w-5 h-5 mr-3"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"
                                        />
                                    </svg>
                                    Logout
                                </button>
                            </nav>
                        </div>
                    </div>

                    <!-- Main Content -->
                    <div class="lg:col-span-3">
                        <!-- Stats Cards -->
                        <div
                            class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8"
                        >
                            <div
                                class="bg-white rounded-xl shadow-sm border border-gray-100 p-5"
                            >
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm text-gray-500">
                                            Total Orders
                                        </p>
                                        <p
                                            class="text-2xl font-bold text-gray-900"
                                        >
                                            {{ stats.totalOrders }}
                                        </p>
                                    </div>
                                    <div
                                        class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center"
                                    >
                                        <svg
                                            class="w-6 h-6 text-blue-600"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"
                                            />
                                        </svg>
                                    </div>
                                </div>
                            </div>

                            <div
                                class="bg-white rounded-xl shadow-sm border border-gray-100 p-5"
                            >
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm text-gray-500">
                                            Pending
                                        </p>
                                        <p
                                            class="text-2xl font-bold text-yellow-600"
                                        >
                                            {{ stats.pendingOrders }}
                                        </p>
                                    </div>
                                    <div
                                        class="w-12 h-12 bg-yellow-100 rounded-xl flex items-center justify-center"
                                    >
                                        <svg
                                            class="w-6 h-6 text-yellow-600"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                                            />
                                        </svg>
                                    </div>
                                </div>
                            </div>

                            <div
                                class="bg-white rounded-xl shadow-sm border border-gray-100 p-5"
                            >
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm text-gray-500">
                                            Delivered
                                        </p>
                                        <p
                                            class="text-2xl font-bold text-green-600"
                                        >
                                            {{ stats.completedOrders }}
                                        </p>
                                    </div>
                                    <div
                                        class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center"
                                    >
                                        <svg
                                            class="w-6 h-6 text-green-600"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                            />
                                        </svg>
                                    </div>
                                </div>
                            </div>

                            <div
                                class="bg-white rounded-xl shadow-sm border border-gray-100 p-5"
                            >
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm text-gray-500">
                                            Canceled
                                        </p>
                                        <p
                                            class="text-2xl font-bold text-red-600"
                                        >
                                            {{ stats.canceledOrders }}
                                        </p>
                                    </div>
                                    <div
                                        class="w-12 h-12 bg-red-100 rounded-xl flex items-center justify-center"
                                    >
                                        <svg
                                            class="w-6 h-6 text-red-600"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"
                                            />
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Orders Tab -->
                        <div v-if="activeTab === 'orders'">
                            <div
                                class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden"
                            >
                                <div class="px-6 py-4 border-b border-gray-100">
                                    <h2
                                        class="text-lg font-semibold text-gray-900"
                                    >
                                        Order History
                                    </h2>
                                    <p class="text-sm text-gray-500">
                                        Track and manage your orders
                                    </p>
                                </div>

                                <!-- Orders List -->
                                <div v-if="orders.data?.length > 0">
                                    <div
                                        v-for="order in orders.data"
                                        :key="order.id"
                                        class="p-6 border-b border-gray-100 last:border-0 hover:bg-gray-50 transition-colors"
                                    >
                                        <div
                                            class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4"
                                        >
                                            <!-- Order Info -->
                                            <div class="flex-1">
                                                <div
                                                    class="flex items-center gap-3 mb-2"
                                                >
                                                    <span
                                                        class="font-semibold text-gray-900"
                                                        >#{{
                                                            order.order_number
                                                        }}</span
                                                    >
                                                    <span
                                                        :class="[
                                                            'px-2.5 py-1 text-xs font-medium rounded-full',
                                                            getStatusClass(
                                                                order.status
                                                            ),
                                                        ]"
                                                    >
                                                        {{
                                                            order.status
                                                                ?.charAt(0)
                                                                .toUpperCase() +
                                                            order.status?.slice(
                                                                1
                                                            )
                                                        }}
                                                    </span>
                                                </div>
                                                <p
                                                    class="text-sm text-gray-500"
                                                >
                                                    {{
                                                        formatDate(
                                                            order.created_at
                                                        )
                                                    }}
                                                </p>

                                                <!-- Order Items Preview -->
                                                <div class="mt-3 space-y-2">
                                                    <div
                                                        v-for="item in order.items?.slice(
                                                            0,
                                                            3
                                                        )"
                                                        :key="item.id"
                                                        class="flex items-center gap-3 bg-gray-50 rounded-lg px-3 py-2"
                                                    >
                                                        <img
                                                            v-if="
                                                                item.product
                                                                    ?.feature_image
                                                            "
                                                            :src="
                                                                item.product
                                                                    .feature_image
                                                            "
                                                            :alt="
                                                                item.product
                                                                    ?.name
                                                            "
                                                            class="w-10 h-10 rounded object-cover flex-shrink-0"
                                                        />
                                                        <div
                                                            class="flex-1 min-w-0"
                                                        >
                                                            <div
                                                                class="flex items-start justify-between gap-3"
                                                            >
                                                                <div
                                                                    class="flex-1"
                                                                >
                                                                    <div
                                                                        class="flex items-center gap-2"
                                                                    >
                                                                        <h4
                                                                            class="text-sm font-medium text-gray-900 truncate"
                                                                        >
                                                                            {{
                                                                                item
                                                                                    .product
                                                                                    ?.name
                                                                            }}
                                                                        </h4>
                                                                        <span
                                                                            class="text-xs text-gray-500 ml-1"
                                                                            >×{{
                                                                                item.quantity
                                                                            }}</span
                                                                        >
                                                                    </div>
                                                                    <p
                                                                        v-if="
                                                                            item
                                                                                .product_variation
                                                                                ?.attributes
                                                                                ?.length
                                                                        "
                                                                        class="text-xs text-gray-500 mt-1 truncate"
                                                                    >
                                                                        {{
                                                                            item.product_variation.attributes
                                                                                .map(
                                                                                    (
                                                                                        a
                                                                                    ) =>
                                                                                        `${a.value?.attribute?.name}: ${a.value?.value}`
                                                                                )
                                                                                .join(
                                                                                    " | "
                                                                                )
                                                                        }}
                                                                    </p>
                                                                </div>
                                                                <div
                                                                    class="text-right"
                                                                >
                                                                    <p
                                                                        class="text-sm font-semibold text-gray-900"
                                                                    >
                                                                        ৳{{
                                                                            parseFloat(
                                                                                item.unit_price ||
                                                                                    item.subtotal ||
                                                                                    0
                                                                            ).toFixed(
                                                                                2
                                                                            )
                                                                        }}
                                                                    </p>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div
                                                        v-if="
                                                            order.items
                                                                ?.length > 3
                                                        "
                                                        class="text-xs text-gray-500"
                                                    >
                                                        +{{
                                                            order.items.length -
                                                            3
                                                        }}
                                                        more
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Order Total -->
                                            <div class="text-right">
                                                <p
                                                    class="text-lg font-bold text-gray-900"
                                                >
                                                    ৳{{
                                                        parseFloat(
                                                            order.total || 0
                                                        ).toFixed(2)
                                                    }}
                                                </p>
                                                <p
                                                    class="text-xs text-gray-500"
                                                >
                                                    {{
                                                        order.items?.length
                                                    }}
                                                    item(s)
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Empty State -->
                                <div v-else class="p-12 text-center">
                                    <div
                                        class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4"
                                    >
                                        <svg
                                            class="w-8 h-8 text-gray-400"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"
                                            />
                                        </svg>
                                    </div>
                                    <h3
                                        class="text-lg font-medium text-gray-900 mb-1"
                                    >
                                        No orders yet
                                    </h3>
                                    <p class="text-sm text-gray-500 mb-4">
                                        Start shopping to see your orders here.
                                    </p>
                                    <Link
                                        href="/"
                                        class="inline-flex items-center px-4 py-2 bg-primary text-white text-sm font-medium rounded-lg hover:bg-primary/90 transition-colors"
                                    >
                                        Start Shopping
                                    </Link>
                                </div>

                                <!-- Pagination -->
                                <div
                                    v-if="orders.links?.length > 3"
                                    class="px-6 py-4 border-t border-gray-100 flex justify-center gap-2"
                                >
                                    <Link
                                        v-for="link in orders.links"
                                        :key="link.label"
                                        :href="link.url || '#'"
                                        :class="[
                                            'px-3 py-1 rounded text-sm',
                                            link.active
                                                ? 'bg-primary text-white'
                                                : 'text-gray-600 hover:bg-gray-100',
                                            !link.url &&
                                                'opacity-50 cursor-not-allowed',
                                        ]"
                                        v-html="link.label"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Profile Tab -->
                        <div
                            v-if="activeTab === 'profile'"
                            class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6"
                        >
                            <h2
                                class="text-lg font-semibold text-gray-900 mb-6"
                            >
                                Edit Profile
                            </h2>
                            <form
                                @submit.prevent="updateProfile"
                                class="space-y-4 max-w-md"
                            >
                                <div>
                                    <label
                                        class="block text-sm font-medium text-gray-700 mb-1"
                                        >Full Name</label
                                    >
                                    <input
                                        type="text"
                                        v-model="profileForm.name"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                                    />
                                    <div
                                        v-if="profileForm.errors.name"
                                        class="text-sm text-red-600 mt-1"
                                    >
                                        {{ profileForm.errors.name }}
                                    </div>
                                </div>
                                <div>
                                    <label
                                        class="block text-sm font-medium text-gray-700 mb-1"
                                        >Email</label
                                    >
                                    <input
                                        type="email"
                                        v-model="profileForm.email"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                                    />
                                    <div
                                        v-if="profileForm.errors.email"
                                        class="text-sm text-red-600 mt-1"
                                    >
                                        {{ profileForm.errors.email }}
                                    </div>
                                </div>
                                <div>
                                    <label
                                        class="block text-sm font-medium text-gray-700 mb-1"
                                        >Phone</label
                                    >
                                    <input
                                        type="tel"
                                        v-model="profileForm.phone_number"
                                        placeholder="01XXXXXXXXX"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                                    />
                                    <div
                                        v-if="profileForm.errors.phone_number"
                                        class="text-sm text-red-600 mt-1"
                                    >
                                        {{ profileForm.errors.phone_number }}
                                    </div>
                                </div>
                                <div>
                                    <label
                                        class="block text-sm font-medium text-gray-700 mb-1"
                                        >Address</label
                                    >
                                    <textarea
                                        rows="3"
                                        v-model="profileForm.address"
                                        placeholder="Your full address"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary resize-none"
                                    ></textarea>
                                    <div
                                        v-if="profileForm.errors.address"
                                        class="text-sm text-red-600 mt-1"
                                    >
                                        {{ profileForm.errors.address }}
                                    </div>
                                </div>
                                <button
                                    type="submit"
                                    :disabled="profileForm.processing"
                                    class="w-full py-3 bg-primary hover:bg-primary/90 text-white font-medium rounded-lg transition-colors disabled:opacity-50"
                                >
                                    {{
                                        profileForm.processing
                                            ? "Saving..."
                                            : "Save Changes"
                                    }}
                                </button>
                            </form>
                        </div>

                        <!-- Security Tab -->
                        <div
                            v-if="activeTab === 'security'"
                            class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6"
                        >
                            <h2
                                class="text-lg font-semibold text-gray-900 mb-6"
                            >
                                Change Password
                            </h2>
                            <form
                                @submit.prevent="updatePassword"
                                class="space-y-4 max-w-md"
                            >
                                <div>
                                    <label
                                        class="block text-sm font-medium text-gray-700 mb-1"
                                        >Current Password</label
                                    >
                                    <div class="relative">
                                        <input
                                            :type="
                                                showCurrentPassword
                                                    ? 'text'
                                                    : 'password'
                                            "
                                            v-model="
                                                passwordForm.current_password
                                            "
                                            placeholder="Enter current password"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary pr-12"
                                        />
                                        <button
                                            type="button"
                                            @click="
                                                showCurrentPassword =
                                                    !showCurrentPassword
                                            "
                                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                                        >
                                            <svg
                                                v-if="showCurrentPassword"
                                                class="w-5 h-5"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                                />
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                                                />
                                            </svg>
                                            <svg
                                                v-else
                                                class="w-5 h-5"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"
                                                />
                                            </svg>
                                        </button>
                                    </div>
                                    <div
                                        v-if="
                                            passwordForm.errors.current_password
                                        "
                                        class="text-sm text-red-600 mt-1"
                                    >
                                        {{
                                            passwordForm.errors.current_password
                                        }}
                                    </div>
                                </div>
                                <div>
                                    <label
                                        class="block text-sm font-medium text-gray-700 mb-1"
                                        >New Password</label
                                    >
                                    <div class="relative">
                                        <input
                                            :type="
                                                showNewPassword
                                                    ? 'text'
                                                    : 'password'
                                            "
                                            v-model="passwordForm.password"
                                            placeholder="Enter new password"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary pr-12"
                                        />
                                        <button
                                            type="button"
                                            @click="
                                                showNewPassword =
                                                    !showNewPassword
                                            "
                                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                                        >
                                            <svg
                                                v-if="showNewPassword"
                                                class="w-5 h-5"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                                />
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                                                />
                                            </svg>
                                            <svg
                                                v-else
                                                class="w-5 h-5"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"
                                                />
                                            </svg>
                                        </button>
                                    </div>
                                    <div
                                        v-if="passwordForm.errors.password"
                                        class="text-sm text-red-600 mt-1"
                                    >
                                        {{ passwordForm.errors.password }}
                                    </div>
                                </div>
                                <div>
                                    <label
                                        class="block text-sm font-medium text-gray-700 mb-1"
                                        >Confirm New Password</label
                                    >
                                    <div class="relative">
                                        <input
                                            :type="
                                                showConfirmPassword
                                                    ? 'text'
                                                    : 'password'
                                            "
                                            v-model="
                                                passwordForm.password_confirmation
                                            "
                                            placeholder="Confirm new password"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary pr-12"
                                        />
                                        <button
                                            type="button"
                                            @click="
                                                showConfirmPassword =
                                                    !showConfirmPassword
                                            "
                                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                                        >
                                            <svg
                                                v-if="showConfirmPassword"
                                                class="w-5 h-5"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                                />
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                                                />
                                            </svg>
                                            <svg
                                                v-else
                                                class="w-5 h-5"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"
                                                />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                                <button
                                    type="submit"
                                    :disabled="passwordForm.processing"
                                    class="w-full py-3 bg-primary hover:bg-primary/90 text-white font-medium rounded-lg transition-colors disabled:opacity-50"
                                >
                                    {{
                                        passwordForm.processing
                                            ? "Updating..."
                                            : "Update Password"
                                    }}
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </FrontendLayout>
</template>
