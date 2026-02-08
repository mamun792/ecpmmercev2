<template>
    <div
        class="sidebar bg-white border-r border-gray-200 transition-all duration-300 ease-out h-full shadow-sm flex flex-col relative z-30"
        :class="{
            'w-16 md:w-20': !sidebarOpen,
            'w-64': sidebarOpen,
            'min-w-16 md:min-w-20': !sidebarOpen,
            'min-w-64': sidebarOpen
        }"
    >
        <!-- Logo Section - Enhanced Amazon Style -->
        <div
            class="flex items-center h-12 md:h-16 bg-gradient-to-r from-blue-600 to-indigo-700 relative overflow-hidden flex-shrink-0"
            :class="{ 'justify-center px-1': !sidebarOpen, 'justify-start px-3 md:px-6': sidebarOpen }"
        >
            <!-- Background Decoration -->
            <div class="absolute inset-0 bg-white/10 backdrop-blur-sm"></div>
            <div class="absolute top-0 right-0 w-20 h-20 bg-white/5 rounded-full -mr-10 -mt-10"></div>

            <div class="flex items-center w-full relative z-10">
                <img
                    v-if="sidebarOpen"
                    src="/assets/img/logo/logo.png"
                    alt="Logo"
                    class="h-6 md:h-8 object-contain transition-all duration-300 filter brightness-0 invert"
                />
                <img
                    v-else
                    src="/assets/img/logo/favicon.png"
                    alt="Logo Icon"
                    class="h-6 w-6 md:h-8 md:w-8 object-contain transition-all duration-300 filter brightness-0 invert"
                />

                <!-- Quick Stats Indicator (Collapsed) -->
                <div v-if="!sidebarOpen" class="absolute -top-1 -right-1 w-3 h-3 bg-green-400 rounded-full animate-pulse border-2 border-white shadow-sm"></div>
            </div>
        </div>

        <!-- Quick Stats Bar -->
        <div v-if="sidebarOpen" class="bg-gradient-to-r from-green-50 to-blue-50 border-b border-gray-100 p-3 md:p-4 flex-shrink-0">
            <div v-if="quickStats.isLoading" class="flex items-center justify-center py-2">
                <Loader2 class="w-4 h-4 animate-spin text-blue-500 mr-2" />
                <span class="text-xs text-gray-500">Loading stats...</span>
            </div>
            <div v-else class="grid grid-cols-3 gap-2 md:gap-3">
                <div class="group relative bg-white/60 backdrop-blur-sm rounded-xl p-2 md:p-3 hover:bg-white/80 transition-all duration-200 cursor-pointer border border-white/50">
                    <div class="flex items-center">
                        <DollarSign class="w-3 h-3 md:w-4 md:h-4 text-green-600 mr-1 md:mr-2" />
                        <div>
                            <p class="text-xs md:text-sm font-bold text-gray-800">৳{{ quickStats.todayRevenue.toLocaleString() }}</p>
                            <p class="text-xs text-gray-500">Today</p>
                        </div>
                    </div>
                    <div class="absolute -top-1 -right-1 w-2 h-2 bg-green-400 rounded-full animate-pulse"></div>
                </div>

                <div class="group relative bg-white/60 backdrop-blur-sm rounded-xl p-2 md:p-3 hover:bg-white/80 transition-all duration-200 cursor-pointer border border-white/50">
                    <div class="flex items-center">
                        <ShoppingCart class="w-3 h-3 md:w-4 md:h-4 text-blue-600 mr-1 md:mr-2" />
                        <div>
                            <p class="text-xs md:text-sm font-bold text-gray-800">{{ quickStats.todayOrders }}</p>
                            <p class="text-xs text-gray-500">Orders</p>
                        </div>
                    </div>
                    <div v-if="notifications.newOrders > 0" class="absolute -top-1 -right-1 w-4 h-4 bg-orange-500 rounded-full flex items-center justify-center">
                        <span class="text-xs font-bold text-white">{{ notifications.newOrders > 9 ? '9+' : notifications.newOrders }}</span>
                    </div>
                </div>

                <div class="group relative bg-white/60 backdrop-blur-sm rounded-xl p-2 md:p-3 hover:bg-white/80 transition-all duration-200 cursor-pointer border border-white/50"
                     :class="{ 'ring-2 ring-red-400': quickStats.lowStockCount > 10 }">
                    <div class="flex items-center">
                        <AlertTriangle class="w-3 h-3 md:w-4 md:h-4 text-amber-600 mr-1 md:mr-2" :class="{ 'text-red-600': quickStats.lowStockCount > 10 }" />
                        <div>
                            <p class="text-xs md:text-sm font-bold text-gray-800">{{ quickStats.lowStockCount }}</p>
                            <p class="text-xs text-gray-500">Low Stock</p>
                        </div>
                    </div>
                    <div v-if="quickStats.lowStockCount > 5" class="absolute -top-1 -right-1 w-2 h-2 bg-red-500 rounded-full animate-pulse"></div>
                </div>
            </div>

            <!-- Live Status Indicator -->
            <div class="flex items-center justify-center mt-2 md:mt-3 pt-2 border-t border-white/50">
                <Activity class="w-3 h-3 text-green-500 animate-pulse mr-1" />
                <span class="text-xs text-gray-600 font-medium">Live Dashboard</span>
                <div class="ml-2 flex space-x-1">
                    <div class="w-1 h-1 bg-green-500 rounded-full animate-pulse"></div>
                    <div class="w-1 h-1 bg-blue-500 rounded-full animate-pulse" style="animation-delay: 0.2s;"></div>
                    <div class="w-1 h-1 bg-purple-500 rounded-full animate-pulse" style="animation-delay: 0.4s;"></div>
                </div>
            </div>
        </div>

        <!-- Global Search Bar (Enhanced) -->
        <div v-if="sidebarOpen" class="p-2 md:p-3 border-b border-gray-100 bg-white">
            <div class="relative">
                <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400" />
                <input
                    v-model="searchQuery"
                    type="text"
                    placeholder="Search orders, products, customers..."
                    class="w-full pl-9 pr-4 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 bg-gray-50 focus:bg-white"
                    @focus="showSearch = true"
                    @blur="showSearch = false"
                />
                <div v-if="searchQuery" class="absolute right-3 top-1/2 transform -translate-y-1/2">
                    <Loader2 class="w-4 h-4 animate-spin text-blue-500" />
                </div>
            </div>
        </div>

        <!-- Quick Actions (Mobile-Optimized) -->
        <div v-if="sidebarOpen" class="p-2 md:p-3 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-gray-100">
            <div class="grid grid-cols-3 gap-1 md:gap-2">
                <Link v-for="(action, index) in quickActions" :key="index"
                      :href="route(action.route)"
                      class="group flex flex-col items-center justify-center p-2 md:p-3 rounded-lg transition-all duration-200 hover:scale-105 hover:shadow-md"
                      :class="action.color">
                    <component :is="action.icon" class="w-4 h-4 md:w-5 md:h-5 text-white mb-1 group-hover:scale-110 transition-transform" />
                    <span class="text-xs font-medium text-white text-center leading-tight">{{ action.label }}</span>
                </Link>
            </div>
        </div>

        <!-- Navigation Section - Enhanced Amazon Style with Loading States -->
        <div class="flex-1 overflow-y-auto py-2 md:py-4 scrollbar-hide">
            <nav class="px-1 md:px-3">
                <!-- Loading Skeleton -->
                <div v-if="isLoading" class="space-y-2 animate-pulse">
                    <div v-for="n in 6" :key="n" class="flex items-center px-3 py-2">
                        <div class="w-5 h-5 bg-gray-200 rounded mr-3"></div>
                        <div v-if="sidebarOpen" class="h-4 bg-gray-200 rounded flex-1"></div>
                    </div>
                </div>

                <ul v-else class="space-y-1">
                    <template v-for="(item, index) in filteredNavigationItems" :key="index">
                        <!-- Enhanced Section Headers -->
                        <li v-if="sidebarOpen && item.isHeader" class="px-2 md:px-3 pt-4 md:pt-6 pb-1 md:pb-2 first:pt-1 md:first:pt-2">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide hidden md:block">
                                    {{ item.label }}
                                </span>
                                <div v-if="item.hasNotifications" class="w-2 h-2 bg-orange-400 rounded-full animate-pulse"></div>
                            </div>
                        </li>

                        <li v-else-if="shouldShowItem(item)" class="relative group">
                            <!-- Enhanced Single Navigation Link -->
                            <template v-if="!item.children">
                                <Link
                                    :href="item.route ? route(item.route) : '#'"
                                    class="flex items-center px-2 md:px-3 py-2.5 md:py-3 text-sm rounded-xl transition-all duration-300 relative group hover:scale-[1.02] hover:shadow-sm"
                                    :class="[
                                        isActive(item.route)
                                            ? 'bg-gradient-to-r from-orange-500 to-pink-500 text-white shadow-lg shadow-orange-500/25 border-l-4 border-orange-600'
                                            : 'text-gray-700 hover:bg-gradient-to-r hover:from-gray-50 hover:to-blue-50 hover:text-gray-900',
                                        !sidebarOpen ? 'justify-center' : '',
                                        !item.route ? 'cursor-not-allowed opacity-60' : ''
                                    ]"
                                    :aria-disabled="!item.route"
                                    @click="trackVisit(item)"
                                >
                                    <!-- Icon with enhanced animations -->
                                    <div class="relative">
                                        <component
                                            :is="item.icon"
                                            class="flex-shrink-0 transition-all duration-300 group-hover:scale-110"
                                            :class="[
                                                isActive(item.route) ? 'text-white' : 'text-gray-500 group-hover:text-blue-600',
                                                sidebarOpen ? 'w-4 h-4 md:w-5 md:h-5 mr-2 md:mr-3' : 'w-5 h-5 md:w-6 md:h-6'
                                            ]"
                                        />
                                        <!-- Notification Dot -->
                                        <div v-if="item.hasNotification" class="absolute -top-1 -right-1 w-2 h-2 bg-red-500 rounded-full animate-pulse"></div>
                                    </div>

                                    <span
                                        v-if="sidebarOpen"
                                        class="flex-1 text-left font-medium truncate text-xs md:text-sm transition-all duration-300"
                                    >
                                        {{ item.label }}
                                    </span>

                                    <!-- Enhanced Badge with Animations -->
                                    <div v-if="sidebarOpen && (item.badge || item.count)" class="ml-auto flex items-center space-x-1">
                                        <span v-if="item.badge" class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-orange-100 text-orange-800 animate-pulse">
                                            {{ item.badge }}
                                        </span>
                                        <span v-if="item.count" class="inline-flex items-center justify-center w-5 h-5 rounded-full text-xs font-bold bg-red-500 text-white">
                                            {{ item.count > 99 ? '99+' : item.count }}
                                        </span>
                                        <ChevronRight v-if="isActive(item.route)" class="w-3 h-3 text-white" />
                                    </div>

                                    <!-- Tooltip for collapsed state -->
                                    <div v-if="!sidebarOpen" class="absolute left-full ml-2 px-2 py-1 bg-gray-900 text-white text-xs rounded-md opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap z-50">
                                        {{ item.label }}
                                        <div class="absolute right-full top-1/2 -translate-y-1/2 border-4 border-transparent border-r-gray-900"></div>
                                    </div>
                                </Link>
                            </template>

                            <!-- Enhanced Dropdown Menu -->
                            <template v-else>
                                <button
                                    @click="toggleSubmenu(item.id)"
                                    class="w-full flex items-center px-2 md:px-3 py-2.5 md:py-3 text-sm rounded-xl transition-all duration-300 group hover:scale-[1.02] hover:shadow-sm relative"
                                    :class="[
                                        isSubmenuActive(item)
                                            ? 'bg-gradient-to-r from-blue-50 to-indigo-50 text-gray-900 border-l-4 border-blue-500'
                                            : 'text-gray-700 hover:bg-gradient-to-r hover:from-gray-50 hover:to-blue-50 hover:text-gray-900',
                                        !sidebarOpen ? 'justify-center' : ''
                                    ]"
                                >
                                    <div class="relative">
                                        <component
                                            :is="item.icon"
                                            class="flex-shrink-0 transition-all duration-300 group-hover:scale-110"
                                            :class="[
                                                isSubmenuActive(item) ? 'text-blue-600' : 'text-gray-500 group-hover:text-blue-600',
                                                sidebarOpen ? 'w-4 h-4 md:w-5 md:h-5 mr-2 md:mr-3' : 'w-5 h-5 md:w-6 md:h-6'
                                            ]"
                                        />
                                        <div v-if="getSubmenuNotificationCount(item) > 0" class="absolute -top-1 -right-1 w-3 h-3 bg-red-500 rounded-full flex items-center justify-center">
                                            <span class="text-xs font-bold text-white">{{ getSubmenuNotificationCount(item) > 9 ? '9+' : getSubmenuNotificationCount(item) }}</span>
                                        </div>
                                    </div>

                                    <span
                                        v-if="sidebarOpen"
                                        class="flex-1 text-left font-medium truncate text-xs md:text-sm"
                                    >
                                        {{ item.label }}
                                    </span>

                                    <div v-if="sidebarOpen" class="flex items-center space-x-2">
                                        <span v-if="getVisibleChildrenCount(item) > 0" class="text-xs text-gray-400 font-medium">
                                            {{ getVisibleChildrenCount(item) }}
                                        </span>
                                        <ChevronDown
                                            class="w-4 h-4 transition-all duration-300 text-gray-400"
                                            :class="{ 'rotate-180 text-blue-600': openSubmenus[item.id] }"
                                        />
                                    </div>

                                    <!-- Tooltip for collapsed state -->
                                    <div v-if="!sidebarOpen" class="absolute left-full ml-2 px-2 py-1 bg-gray-900 text-white text-xs rounded-md opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap z-50">
                                        {{ item.label }} ({{ getVisibleChildrenCount(item) }})
                                        <div class="absolute right-full top-1/2 -translate-y-1/2 border-4 border-transparent border-r-gray-900"></div>
                                    </div>
                                </button>

                                <!-- Enhanced Submenu with Animations -->
                                <div
                                    v-if="sidebarOpen"
                                    class="overflow-hidden transition-all duration-300 ease-in-out transform"
                                    :style="{
                                        maxHeight: openSubmenus[item.id] ? (getVisibleChildren(item).length * 40 + 20) + 'px' : '0px',
                                        opacity: openSubmenus[item.id] ? '1' : '0'
                                    }"
                                >
                                    <ul class="mt-1 space-y-1 ml-4 md:ml-6 pl-4 border-l-2 border-gray-100">
                                        <li
                                            v-for="(child, childIndex) in getVisibleChildren(item)"
                                            :key="childIndex"
                                            class="transform transition-all duration-300"
                                            :style="{ transitionDelay: `${childIndex * 50}ms` }"
                                        >
                                            <Link
                                                :href="child.route ? route(child.route) : '#'"
                                                class="flex items-center px-3 py-2 text-sm rounded-lg transition-all duration-300 group hover:scale-[1.02] relative"
                                                :class="[
                                                    isActive(child.route)
                                                        ? 'bg-gradient-to-r from-orange-400 to-pink-400 text-white shadow-md font-semibold'
                                                        : 'text-gray-600 hover:text-gray-900 hover:bg-gradient-to-r hover:from-gray-50 hover:to-blue-50',
                                                    !child.route ? 'cursor-not-allowed opacity-60' : ''
                                                ]"
                                                :aria-disabled="!child.route"
                                                @click="trackVisit(child)"
                                            >
                                                <div class="w-1.5 h-1.5 rounded-full mr-3 flex-shrink-0 transition-all duration-200"
                                                     :class="isActive(child.route) ? 'bg-white' : 'bg-gray-400 group-hover:bg-blue-400'"></div>
                                                <span class="flex-1 truncate">{{ child.label }}</span>
                                                <div v-if="child.count" class="ml-2 inline-flex items-center justify-center w-4 h-4 rounded-full text-xs font-bold bg-red-500 text-white">
                                                    {{ child.count }}
                                                </div>
                                                <Star v-if="isActive(child.route)" class="w-3 h-3 ml-2 text-white" />
                                            </Link>
                                        </li>
                                    </ul>
                                </div>
                            </template>
                        </li>
                    </template>
                </ul>
            </nav>
        </div>

        <!-- Enhanced User Profile Section with Role-Based Display -->
        <div class="border-t border-gray-200 bg-gradient-to-r from-gray-50 to-blue-50 p-3 md:p-4">
            <div
                class="flex items-center transition-all duration-300 hover:bg-white/60 rounded-xl p-2 cursor-pointer group"
                :class="{ 'justify-center': !sidebarOpen }"
            >
                <div class="relative flex-shrink-0">
                    <div class="w-10 h-10 md:w-12 md:h-12 rounded-xl bg-gradient-to-br from-blue-600 to-purple-600 text-white flex items-center justify-center font-bold text-sm md:text-base shadow-lg group-hover:scale-105 transition-transform">
                        {{ $page.props.auth.user.name.charAt(0) }}
                    </div>
                    <div class="absolute -bottom-1 -right-1 w-3 h-3 md:w-4 md:h-4 bg-green-500 border-2 border-white rounded-full shadow-sm">
                        <div class="absolute inset-0 bg-green-500 rounded-full animate-ping opacity-75"></div>
                    </div>
                    <div v-if="notifications.urgent > 0" class="absolute -top-1 -right-1 w-4 h-4 bg-red-500 rounded-full flex items-center justify-center">
                        <span class="text-xs font-bold text-white">{{ notifications.urgent }}</span>
                    </div>
                </div>

                <div v-if="sidebarOpen" class="ml-3 overflow-hidden flex-1">
                    <p class="text-sm font-bold text-gray-900 truncate group-hover:text-blue-600 transition-colors">
                        {{ $page.props.auth.user.name }}
                    </p>
                    <div class="flex items-center justify-between mt-1">
                        <div class="flex items-center">
                            <div class="w-2 h-2 bg-green-500 rounded-full mr-2 animate-pulse"></div>
                            <p class="text-xs text-gray-600 uppercase tracking-wide font-medium" :class="getRoleColor()">
                                {{ getUserRoleDisplay() }}
                            </p>
                        </div>
                        <div v-if="notifications.urgent > 0" class="flex items-center">
                            <Bell class="w-3 h-3 text-orange-500 animate-pulse" />
                        </div>
                    </div>

                    <!-- Role-Based Quick Stats -->
                    <div class="mt-2 flex items-center space-x-2 text-xs text-gray-500">
                        <div class="flex items-center">
                            <Clock class="w-3 h-3 mr-1" />
                            <span>Online</span>
                        </div>
                        <div v-if="userIsAdmin" class="flex items-center">
                            <Target class="w-3 h-3 mr-1 text-green-500" />
                            <span class="text-green-600 font-medium">Full Access</span>
                        </div>
                    </div>
                </div>

                <!-- Tooltip for collapsed state -->
                <div v-if="!sidebarOpen" class="absolute left-full ml-2 px-3 py-2 bg-gray-900 text-white text-sm rounded-lg opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap z-50">
                    <div class="font-medium">{{ $page.props.auth.user.name }}</div>
                    <div class="text-xs text-gray-300">{{ getUserRoleDisplay() }}</div>
                    <div class="absolute right-full top-1/2 -translate-y-1/2 border-4 border-transparent border-r-gray-900"></div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { defineProps, computed, watch, ref, onMounted, onUnmounted, reactive } from "vue";
import {
    ChevronDown,
    Home,
    Users,
    ShoppingCart,
    Settings,
    UserRoundPlus,
    BadgeDollarSign,
    TicketPercent,
    Newspaper,
    FilePenLine,
    FileText,
    Megaphone,
    Truck,
    LayoutDashboard,
    Wallet,
    ShieldCheck,
    Globe,
    BarChart3,
    Search,
    TrendingUp,
    AlertTriangle,
    Target,
    Clock,
    Zap,
    Star,
    Bell,
    Activity,
    DollarSign,
    Package,
    ChevronRight,
    Loader2
} from "lucide-vue-next";
import { Link, usePage, router } from "@inertiajs/vue3";
import { toast } from "@steveyuowo/vue-hot-toast";

const page = usePage();

const props = defineProps({
    sidebarOpen: {
        type: Boolean,
        default: true,
    },
    openSubmenus: {
        type: Object,
        default: () => ({}),
    },
    toggleSubmenu: {
        type: Function,
        required: true,
    },
});

// Enhanced State Management
const isLoading = ref(false);
const searchQuery = ref('');
const showSearch = ref(false);
const quickActions = ref([
    { label: 'New Order', route: 'admin.orders.create', icon: ShoppingCart, color: 'bg-blue-500' },
    { label: 'Add Product', route: 'admin.products.create', icon: Package, color: 'bg-green-500' },
    { label: 'View Reports', route: 'admin.reports.revenue.dashboard', icon: BarChart3, color: 'bg-purple-500' }
]);
const recentPages = ref([]);

// Dynamic stats from backend
const sidebarStats = computed(() => page.props.sidebarStats || null);
const quickStats = computed(() => ({
    todayRevenue: sidebarStats.value?.todayRevenue || 0,
    todayOrders: sidebarStats.value?.todayOrders || 0,
    lowStockCount: sidebarStats.value?.lowStockCount || 0,
    pendingCount: sidebarStats.value?.pendingOrders || 0,
    incompleteOrders: sidebarStats.value?.incompleteOrders || 0,
    isLoading: !sidebarStats.value
}));
const notifications = computed(() => ({
    urgent: (sidebarStats.value?.pendingOrders || 0) + (sidebarStats.value?.incompleteOrders || 0),
    lowStock: sidebarStats.value?.lowStockCount || 0,
    newOrders: sidebarStats.value?.pendingOrders || 0
}));

// User Permissions Logic
const userPermissions = computed(
    () => page.props.auth?.user?.permissions || [],
);
const userRoles = computed(() => page.props.auth?.user?.roles || []);
const userIsAdmin = computed(
    () =>
        !!page.props.auth?.user?.is_admin ||
        userRoles.value.includes("admin") ||
        userRoles.value.includes("super-admin"),
);

const hasPermission = (permission) => {
    if (userIsAdmin.value) return true;
    return Array.isArray(userPermissions.value)
        ? userPermissions.value.includes(permission)
        : false;
};

const hasAnyPermission = (permissions) => {
    if (userIsAdmin.value) return true;
    if (!Array.isArray(permissions)) return false;
    return permissions.some((p) => hasPermission(p));
};

// Enhanced navigation with notifications
const baseNavigationItems = [
    { label: "Core Operations", isHeader: true },
    {
        label: "Dashboard",
        route: "admin.dashboard.index",
        icon: LayoutDashboard,
        permission: "admin.dashboard.index",
        badge: userIsAdmin.value ? "Admin" : null,
    },
    {
        label: "Orders",
        route: "admin.orders.index",
        icon: ShoppingCart,
        permission: "admin.orders.index",
    },
    {
        label: "Incomplete Orders",
        route: "admin.orders.incomplete",
        icon: ShoppingCart,
        permission: "admin.orders.index",
    },
    {
        label: "Pre-Order Leads",
        route: "admin.leads.index",
        icon: UserRoundPlus,
        permission: "admin.leads.index",
    },
    {
        label: "POS",
        route: "admin.pos.index",
        icon: Newspaper,
        permission: "admin.pos.index",
    },

    { label: "Inventory & Catalog", isHeader: true },
    {
        label: "Products",
        icon: Globe,
        id: 1,
        children: [
            {
                label: "All Products",
                route: "admin.products.index",
                permission: "admin.products.index",
            },
            {
                label: "Add Product",
                route: "admin.products.create",
                permission: "admin.products.create",
            },
            {
                label: "Attributes",
                route: "admin.attributes.index",
                permission: "admin.attributes.index",
            },
            {
                label: "Categories",
                route: "admin.categories.index",
                permission: "admin.categories.index",
            },
            {
                label: "Brands",
                route: "admin.brands.index",
                permission: "admin.brands.index",
            },
            {
                label: "Product Groups",
                route: "admin.product-groups.index",
                permission: "admin.product-groups.index",
            },
        ],
    },
    {
        label: "Inventory",
        icon: Truck,
        id: 2,
        children: [
            {
                label: "Stocks",
                route: "admin.inventory.getAllProductsStock",
                permission: "admin.inventory.getAllProductsStock",
            },
            {
                label: "Report",
                route: "admin.reports.inventory.v2",
                permission: "admin.reports.generateReport",
            },
        ],
    },

    { label: "Management", isHeader: true },
    {
        label: "Employees",
        icon: Users,
        id: 5,
        children: [
            {
                label: "All Employees",
                route: "admin.employee.index",
                permission: "admin.employee.index",
            },
            {
                label: "Employee Salaries",
                route: "admin.employee.salary.index",
                permission: "admin.employee.salary.index",
            },
            {
                label: "Team",
                route: "admin.team-member.index",
                permission: "admin.team-member.index",
            },
        ],
    },
    {
        label: "Accounting",
        icon: BadgeDollarSign,
        id: 6,
        children: [
            {
                label: "Business Dashboard",
                route: "admin.business-dashboard.index",
                permission: "admin.business-dashboard.index",
            },
            {
                label: "Expenses",
                route: "admin.expenses.index",
                permission: "admin.expenses.index",
            },
            {
                label: "Purchase Costs",
                route: "admin.product-purchase-costs.index",
                permission: "admin.product-purchase-costs.index",
            },
        ],
    },
    {
        label: "Revenue Dashboard",
        route: "admin.reports.revenue.dashboard",
        icon: BarChart3,
        permission: "admin.reports.revenue.dashboard",
    },
    {
        label: "Users & Access",
        icon: ShieldCheck,
        id: 7,
        children: [
            {
                label: "Users",
                route: "admin.users.index",
                permission: "admin.users.index",
            },
            {
                label: "Roles & Permissions",
                route: "admin.roles.index",
                permission: "admin.roles.index",
            },
        ],
    },

    { label: "Marketing & CMS", isHeader: true },
    {
        label: "Coupons",
        route: "admin.coupons.index",
        icon: TicketPercent,
        permission: "admin.coupons.index",
    },
    {
        label: "Campaigns",
        route: "admin.campaigns.index",
        icon: Megaphone,
        permission: "admin.campaigns.index",
    },
    {
        label: "Landing Pages",
        route: "admin.landing-pages.index",
        icon: FilePenLine,
        permission: "admin.landing-pages.index",
    },
    {
        label: "Site Pages",
        route: "admin.site-pages.index",
        icon: FilePenLine,
        permission: "admin.site-pages.index",
    },
    {
        label: "Reviews",
        route: "admin.reviews.index",
        icon: FilePenLine,
        permission: "admin.reviews.index",
    },

    { label: "Infrastructure", isHeader: true },
    {
        label: "API Settings",
        icon: Settings,
        id: 3,
        children: [
            {
                label: "Courier API",
                route: "admin.courier.settings.index",
                permission: "admin.courier.settings.index",
            },
        ],
    },
    {
        label: "Settings",
        icon: Settings,
        id: 4,
        children: [
            {
                label: "Basic Information",
                route: "admin.settings.basicInformation",
                permission: "admin.settings.basicInformation",
            },
            {
                label: "Media Manager",
                route: "admin.media.index",
                permission: "admin.media.index",
            },
            {
                label: "Slider Config",
                route: "admin.sliders.index",
                permission: "admin.sliders.index",
            },
            {
                label: "Banners",
                route: "admin.banners.index",
                permission: "admin.banners.index",
            },
            {
                label: "Social Links",
                route: "admin.settings.socialLinks",
                permission: "admin.settings.socialLinks",
            },
            {
                label: "Marketing Tools",
                route: "admin.marketing-tools.index",
                permission: "admin.marketing-tools.index",
            },
            {
                label: "Maintenance",
                route: "admin.maintenance.index",
                permission: "admin.maintenance.index",
            },
        ],
    },
];

// Enhanced Helper Functions
const shouldShowItem = (item) => {
    if (userIsAdmin.value) return true;
    if (item.children) {
        return item.children.some((child) => hasPermission(child.permission));
    }
    return hasPermission(item.permission);
};

const shouldShowChild = (child) => {
    if (userIsAdmin.value) return true;
    return hasPermission(child.permission);
};

const getVisibleChildren = (item) => {
    if (userIsAdmin.value) return item.children || [];
    return (item.children || []).filter((child) => hasPermission(child.permission));
};

const getVisibleChildrenCount = (item) => {
    return getVisibleChildren(item).length;
};

const getSubmenuNotificationCount = (item) => {
    return getVisibleChildren(item).reduce((count, child) => {
        return count + (child.count || 0);
    }, 0);
};

const isActive = (routeName) => {
    return routeName ? route().current(routeName) : false;
};

const isSubmenuActive = (item) => {
    if (!item.children) return false;
    return item.children.some((child) => isActive(child.route));
};

const getUserRoleDisplay = () => {
    if (userIsAdmin.value) return 'Administrator';
    if (userRoles.value.includes('manager')) return 'Manager';
    if (userRoles.value.includes('staff')) return 'Staff';
    if (userRoles.value.includes('accountant')) return 'Accountant';
    return 'User';
};

const getRoleColor = () => {
    const role = getUserRoleDisplay().toLowerCase();
    const colors = {
        'administrator': 'text-red-600',
        'manager': 'text-blue-600',
        'staff': 'text-green-600',
        'accountant': 'text-purple-600'
    };
    return colors[role] || 'text-gray-600';
};

const trackVisit = (item) => {
    if (item.route) {
        const recent = recentPages.value;
        const existingIndex = recent.findIndex(p => p.route === item.route);

        if (existingIndex > -1) {
            recent.splice(existingIndex, 1);
        }

        recent.unshift({ ...item, visitedAt: Date.now() });
        recentPages.value = recent.slice(0, 5); // Keep only 5 recent items
    }
};

// Role-based navigation filtering
const filteredNavigationItems = computed(() => {
    const role = getUserRoleDisplay().toLowerCase();
    const items = navigationItemsWithNotifications.value;

    // Role-based filtering logic
    if (role === 'staff') {
        return items.filter(item => {
            if (item.isHeader) return ['Core Operations', 'Inventory & Catalog'].includes(item.label);
            return ['Dashboard', 'Orders', 'Incomplete Orders', 'POS', 'Products', 'Inventory'].includes(item.label);
        });
    }

    if (role === 'accountant') {
        return items.filter(item => {
            if (item.isHeader) return ['Core Operations', 'Management'].includes(item.label);
            return ['Dashboard', 'Revenue Dashboard', 'Accounting', 'Orders'].includes(item.label);
        });
    }

    if (role === 'manager') {
        return items.filter(item => {
            if (item.isHeader) return !['Infrastructure'].includes(item.label);
            return !['API Settings', 'Settings'].includes(item.label);
        });
    }

    return items; // Admin sees everything
});

// Enhanced navigation with notifications
const navigationItemsWithNotifications = computed(() => {
    return baseNavigationItems.map(item => {
        const enhanced = { ...item };

        // Add notification counts based on item type (using computed values)
        if (item.label === 'Orders') {
            enhanced.count = notifications.value.newOrders;
            enhanced.hasNotification = notifications.value.newOrders > 0;
        }

        if (item.label === 'Incomplete Orders') {
            enhanced.count = quickStats.value.incompleteOrders;
            enhanced.hasNotification = quickStats.value.incompleteOrders > 5;
        }

        if (item.label === 'Inventory & Catalog') {
            enhanced.hasNotifications = notifications.value.lowStock > 10;
        }

        return enhanced;
    });
});

// Use enhanced navigation
const navigationItems = navigationItemsWithNotifications;

// Lifecycle Management
onMounted(() => {
    // Keyboard shortcuts
    const handleKeyPress = (e) => {
        if (e.altKey) {
            const num = parseInt(e.key);
            if (num >= 1 && num <= 9) {
                e.preventDefault();
                const visibleItems = filteredNavigationItems.value.filter(item => !item.isHeader && item.route);
                if (visibleItems[num - 1]) {
                    router.visit(route(visibleItems[num - 1].route));
                }
            }
        }
    };

    document.addEventListener('keydown', handleKeyPress);

    onUnmounted(() => {
        document.removeEventListener('keydown', handleKeyPress);
    });
});

// Watchers
watch(
    () => route().current(),
    (currentRoute) => {
        filteredNavigationItems.value.forEach((item) => {
            if (item.children) {
                const hasActiveChild = item.children.some((child) =>
                    child.route ? route().current(child.route) : false,
                );
                if (hasActiveChild) {
                    props.toggleSubmenu(item.id, true);
                }
            }
        });
    },
    { immediate: true },
);
</script>

<style scoped>
/* Enhanced Scrollbar Styling */
.scrollbar-hide {
    scrollbar-width: thin;
    scrollbar-color: rgba(156, 163, 175, 0.5) transparent;
}

.scrollbar-hide::-webkit-scrollbar {
    width: 4px;
}

.scrollbar-hide::-webkit-scrollbar-track {
    background: transparent;
}

.scrollbar-hide::-webkit-scrollbar-thumb {
    background: rgba(156, 163, 175, 0.5);
    border-radius: 2px;
}

.scrollbar-hide::-webkit-scrollbar-thumb:hover {
    background: rgba(156, 163, 175, 0.8);
}

/* Enhanced Active Indicator */
.router-link-active {
    position: relative;
}

.router-link-active::before {
    content: '';
    position: absolute;
    left: -2px;
    top: 0;
    bottom: 0;
    width: 4px;
    background: linear-gradient(to bottom, #f97316, #ec4899);
    border-radius: 0 2px 2px 0;
    animation: slideIn 0.3s ease-out;
}

/* Smooth Animations */
@keyframes slideIn {
    from {
        transform: translateX(-100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

@keyframes fadeInUp {
    from {
        transform: translateY(10px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

/* Loading Animation */
@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

.animate-pulse {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

/* Enhanced Hover Effects */
.group:hover .group-hover\:scale-110 {
    transform: scale(1.1);
}

.group:hover .group-hover\:scale-105 {
    transform: scale(1.05);
}

/* Notification Animation */
@keyframes bounce {
    0%, 20%, 53%, 80%, 100% {
        transform: translate3d(0,0,0);
    }
    40%, 43% {
        transform: translate3d(0, -8px, 0);
    }
    70% {
        transform: translate3d(0, -4px, 0);
    }
    90% {
        transform: translate3d(0, -2px, 0);
    }
}

.animate-bounce {
    animation: bounce 1s infinite;
}

/* Mobile Responsive Enhancements */
@media (max-width: 768px) {
    .group:hover .group-hover\:scale-110 {
        transform: none; /* Disable scale on mobile */
    }

    .hover\:scale-\[1\.02\] {
        transform: none; /* Disable hover scale on mobile */
    }
}

/* Gradient Background Animation */
@keyframes gradientShift {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
}

.animate-gradient {
    background-size: 200% 200%;
    animation: gradientShift 3s ease infinite;
}
</style>
