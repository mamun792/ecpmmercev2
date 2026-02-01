<script setup>
import { ref, computed } from "vue";
import { Link, usePage } from "@inertiajs/vue3";
import CategoryNav from "./CategoryNav.vue";
import CartSidebar from "./CartSidebar.vue";
import SearchBar from "../Header/SearchBar.vue";
import MobileBottomNav from "./MobileBottomNav.vue";
import {
    MapPin,
    Search,
    ShoppingCart,
    User as UserIcon,
    LogIn,
    Menu,
    X,
} from "lucide-vue-next";

const props = defineProps({
    categories: {
        type: Array,
        default: () => [],
    },
    settings: {
        type: Object,
        default: () => ({}),
    },
    cart: {
        type: Object,
        default: () => ({}),
    },
});

const emit = defineEmits(["toggle-mobile-menu"]);

const page = usePage();
const isSearchOpen = ref(false);
const isCartSidebarOpen = ref(false);

const user = computed(() => page.props.auth?.user);
const cartCount = computed(() => page.props.cartCount || 0);

const isAdmin = computed(() => {
    return user.value?.roles?.some((r) =>
        ["superadmin", "admin", "super-admin", "Admin", "Super Admin"].includes(
            r.name,
        ),
    );
});

const wishlistCount = computed(() => page.props.wishlistCount || 0);
const compareCount = computed(() => page.props.compareCount || 0);

const logoUrl = computed(() => {
    return props.settings?.media?.logo || "";
});

const siteName = computed(() => {
    return props.settings?.site_name || "E-Shop";
});

const toggleCartSidebar = () => {
    isCartSidebarOpen.value = !isCartSidebarOpen.value;
};

const closeCartSidebar = () => {
    isCartSidebarOpen.value = false;
};

const toggleSearch = () => {
    isSearchOpen.value = !isSearchOpen.value;
};
</script>

<template>
    <!-- <pre>{{ settings }}</pre> -->
    <header class="sticky top-0 z-50 bg-white shadow-sm font-sans">
        <div class="header_top py-2 bg-black text-white">
            <div class="container mx-auto px-4">
                <div
                    class="flex items-center justify-center text-[11px] sm:text-sm"
                >
                    <div v-html="settings?.generalSettings?.top_notice"></div>
                </div>
            </div>
        </div>

        <!-- Main Header -->
        <div class="bg-white relative z-30">
            <div class="container mx-auto px-4">
                <div
                    class="relative flex items-center justify-between h-20 gap-4"
                >
                    <!-- Mobile Menu Button -->
                    <button
                        @click="emit('toggle-mobile-menu')"
                        class="lg:hidden p-2 hover:bg-gray-100 rounded-lg transition-colors text-gray-700"
                    >
                        <Menu class="w-6 h-6" />
                    </button>

                    <!-- Logo (Center Mobile, Left Desktop) -->
                    <div
                        class="absolute left-1/2 top-1/2 transform -translate-x-1/2 -translate-y-1/2 lg:static lg:transform-none lg:flex-shrink-0"
                    >
                        <Link
                            v-if="logoUrl"
                            href="/"
                            class="flex items-center gap-2"
                        >
                            <img
                                :src="logoUrl"
                                :alt="siteName"
                                class="h-10 md:h-12 w-auto object-contain"
                            />
                        </Link>
                    </div>

                    <!-- Search Bar (Center - Desktop) -->
                    <div class="hidden lg:block flex-1 max-w-2xl mx-auto">
                        <SearchBar />
                    </div>

                    <!-- Right Actions -->
                    <div class="flex items-center gap-1 ml-auto lg:ml-0">
                        <!-- Track Order -->
                        <Link
                            href="/order/track"
                            class="p-2 hover:text-primary transition-colors text-black hidden lg:block"
                            title="Track Order"
                        >
                            <MapPin class="w-5 h-5 md:w-6 md:h-6" />
                        </Link>

                        <!-- User Menu -->
                        <div class="relative group hidden lg:block">
                            <Link
                                :href="route('user.dashboard')"
                                class="p-2 inline-flex items-center hover:text-primary transition-colors text-black"
                            >
                                <UserIcon class="w-5 h-5 md:w-6 md:h-6" />
                            </Link>

                            <!-- Dropdown Menu -->
                            <div
                                class="absolute right-0 top-full mt-2 w-48 bg-white rounded-lg shadow-xl border border-gray-100 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50 overflow-hidden"
                            >
                                <div class="py-1">
                                    <template v-if="user">
                                        <div
                                            class="px-4 py-3 bg-gray-50 border-b"
                                        >
                                            <p
                                                class="text-xs text-gray-500 font-medium"
                                            >
                                                Signed in as
                                            </p>
                                            <p
                                                class="text-sm font-bold text-gray-900 truncate"
                                            >
                                                {{ user.name }}
                                            </p>
                                        </div>
                                        <Link
                                            v-if="isAdmin"
                                            :href="
                                                route('admin.dashboard.index')
                                            "
                                            class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 font-medium transition-colors"
                                            >Admin Dashboard</Link
                                        >
                                        <Link
                                            v-else
                                            :href="route('user.dashboard')"
                                            class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 font-medium transition-colors"
                                            >My Dashboard</Link
                                        >
                                        <Link
                                            :href="route('user.dashboard')"
                                            class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors"
                                            >My Orders</Link
                                        >
                                        <!-- <Link
                                            href="/wishlist"
                                            class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors"
                                            >Wishlist</Link
                                        > -->
                                        <div class="border-t my-1"></div>
                                        <Link
                                            :href="route('logout')"
                                            method="post"
                                            as="button"
                                            class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 font-medium transition-colors"
                                            >Logout</Link
                                        >
                                    </template>
                                    <template v-else>
                                        <Link
                                            :href="route('login')"
                                            class="px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 font-medium transition-colors flex items-center gap-2"
                                        >
                                            <LogIn class="w-4 h-4" />
                                            Login
                                        </Link>
                                        <Link
                                            :href="route('register')"
                                            class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 font-medium transition-colors"
                                            >Register</Link
                                        >
                                    </template>
                                </div>
                            </div>
                        </div>

                        <!-- Cart -->
                        <button
                            @click="toggleCartSidebar"
                            class="relative p-2 hover:text-primary transition-colors text-black"
                        >
                            <ShoppingCart class="w-5 h-5 md:w-6 md:h-6" />
                            <span
                                v-if="cartCount > 0"
                                class="absolute top-0 right-0 w-4 h-4 bg-primary text-white text-[10px] font-bold rounded-full flex items-center justify-center transform translate-x-1 -translate-y-1"
                            >
                                {{ cartCount > 99 ? "99+" : cartCount }}
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search Overlay -->
        <transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="isSearchOpen"
                class="fixed inset-0 z-50 bg-white/90 backdrop-blur-sm"
            >
                <!-- Close Button -->
                <div class="container mx-auto px-4 pt-4 flex justify-end">
                    <button
                        @click="isSearchOpen = false"
                        class="p-2 hover:bg-gray-100 rounded-full text-gray-500 transition-colors"
                    >
                        <X class="w-8 h-8" />
                    </button>
                </div>

                <!-- Search Content -->
                <div class="container mx-auto px-4 mt-20 md:mt-32">
                    <div class="max-w-3xl mx-auto">
                        <SearchBar
                            :auto-focus="true"
                            @close="isSearchOpen = false"
                            variant="light"
                        />
                    </div>
                </div>
            </div>
        </transition>

        <!-- Category Navigation -->
        <CategoryNav :categories="categories" />

        <!-- Mobile Bottom Navigation -->
        <MobileBottomNav
            :wishlist-count="wishlistCount"
            :compare-count="compareCount"
            @open-search="isSearchOpen = true"
        />

        <!-- Cart Sidebar -->
        <CartSidebar :is-open="isCartSidebarOpen" @close="closeCartSidebar" />
    </header>
</template>
