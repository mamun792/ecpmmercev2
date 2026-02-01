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
        <!-- Logo Section - Amazon Style -->
        <div
            class="flex items-center h-12 md:h-16 bg-white border-b border-gray-100 z-10 flex-shrink-0 relative"
            :class="{ 'justify-center px-1': !sidebarOpen, 'justify-start px-3 md:px-6': sidebarOpen }"
        >
            <div class="flex items-center w-full relative">
                <img
                    v-if="sidebarOpen"
                    src="/assets/img/logo/logo.png"
                    alt="Logo"
                    class="h-6 md:h-8 object-contain transition-all duration-300"
                />
                <img
                    v-else
                    src="/assets/img/logo/favicon.png"
                    alt="Logo Icon"
                    class="h-6 w-6 md:h-8 md:w-8 object-contain transition-all duration-300"
                />
            </div>
        </div>

        <!-- Navigation Section - Amazon Style -->
        <div class="flex-1 overflow-y-auto py-2 md:py-4">
            <nav class="px-1 md:px-3">
                <ul class="space-y-1">
                    <template v-for="(item, index) in navigationItems" :key="index">
                        <!-- Section Headers -->
                        <li v-if="sidebarOpen && item.isHeader" class="px-2 md:px-3 pt-4 md:pt-6 pb-1 md:pb-2 first:pt-1 md:first:pt-2">
                            <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide hidden md:block">
                                {{ item.label }}
                            </span>
                        </li>

                        <li v-else-if="shouldShowItem(item)" class="relative">
                            <!-- Single Navigation Link -->
                            <template v-if="!item.children">
                                <Link
                                    :href="item.route ? route(item.route) : '#'"
                                    class="flex items-center px-2 md:px-3 py-2 text-sm rounded-md transition-all duration-200 relative group"
                                    :class="[
                                        isActive(item.route)
                                            ? 'bg-orange-50 text-orange-700 border-l-4 border-orange-500'
                                            : 'text-gray-700 hover:bg-gray-50 hover:text-gray-900',
                                        !sidebarOpen ? 'justify-center' : '',
                                        !item.route ? 'cursor-not-allowed opacity-60' : ''
                                    ]"
                                    :aria-disabled="!item.route"
                                >
                                    <component
                                        :is="item.icon"
                                        class="flex-shrink-0 transition-all duration-200"
                                        :class="[
                                            isActive(item.route) ? 'text-orange-600' : 'text-gray-500 group-hover:text-gray-700',
                                            sidebarOpen ? 'w-4 h-4 md:w-5 md:h-5 mr-2 md:mr-3' : 'w-5 h-5 md:w-6 md:h-6'
                                        ]"
                                    />

                                    <span
                                        v-if="sidebarOpen"
                                        class="flex-1 text-left font-medium truncate text-xs md:text-sm"
                                    >
                                        {{ item.label }}
                                    </span>

                                    <span
                                        v-if="sidebarOpen && item.badge"
                                        class="ml-auto inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800"
                                    >
                                        {{ item.badge }}
                                    </span>
                                </Link>
                            </template>

                            <!-- Dropdown Menu -->
                            <template v-else>
                                <button
                                    @click="toggleSubmenu(item.id)"
                                    class="w-full flex items-center px-3 py-2 text-sm rounded-md transition-all duration-200 group"
                                    :class="[
                                        isSubmenuActive(item)
                                            ? 'bg-gray-100 text-gray-900'
                                            : 'text-gray-700 hover:bg-gray-50 hover:text-gray-900',
                                        !sidebarOpen ? 'justify-center' : ''
                                    ]"
                                >
                                    <component
                                        :is="item.icon"
                                        class="flex-shrink-0 transition-all duration-200"
                                        :class="[
                                            isSubmenuActive(item) ? 'text-gray-700' : 'text-gray-500 group-hover:text-gray-700',
                                            sidebarOpen ? 'w-5 h-5 mr-3' : 'w-6 h-6'
                                        ]"
                                    />

                                    <span
                                        v-if="sidebarOpen"
                                        class="flex-1 text-left font-medium truncate"
                                    >
                                        {{ item.label }}
                                    </span>

                                    <ChevronDown
                                        v-if="sidebarOpen"
                                        class="w-4 h-4 transition-transform duration-200 text-gray-400"
                                        :class="{ 'rotate-180': openSubmenus[item.id] }"
                                    />
                                </button>

                                <!-- Submenu -->
                                <div
                                    v-if="sidebarOpen"
                                    class="overflow-hidden transition-all duration-200 ease-in-out"
                                    :style="{ maxHeight: openSubmenus[item.id] ? '500px' : '0px' }"
                                >
                                    <ul class="mt-1 space-y-1 ml-6">
                                        <li
                                            v-for="(child, childIndex) in getVisibleChildren(item)"
                                            :key="childIndex"
                                        >
                                            <Link
                                                :href="child.route ? route(child.route) : '#'
                                                "
                                                class="flex items-center px-3 py-2 text-sm rounded-md transition-all duration-200 group"
                                                :class="[
                                                    isActive(child.route)
                                                        ? 'bg-orange-50 text-orange-700 font-medium'
                                                        : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50',
                                                    !child.route ? 'cursor-not-allowed opacity-60' : ''
                                                ]"
                                                :aria-disabled="!child.route"
                                            >
                                                <span class="w-1.5 h-1.5 bg-gray-300 rounded-full mr-3 flex-shrink-0"></span>
                                                {{ child.label }}
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

        <!-- User Profile Section - Amazon Style -->
        <div class="border-t border-gray-200 p-4">
            <div
                class="flex items-center transition-all duration-300"
                :class="{ 'justify-center': !sidebarOpen }"
            >
                <div class="relative flex-shrink-0">
                    <div class="w-10 h-10 rounded-lg bg-gray-600 text-white flex items-center justify-center font-medium text-sm">
                        {{ $page.props.auth.user.name.charAt(0) }}
                    </div>
                    <div class="absolute -bottom-1 -right-1 w-3 h-3 bg-green-500 border-2 border-white rounded-full"></div>
                </div>

                <div v-if="sidebarOpen" class="ml-3 overflow-hidden">
                    <p class="text-sm font-medium text-gray-900 truncate">
                        {{ $page.props.auth.user.name }}
                    </p>
                    <div class="flex items-center">
                        <span class="w-2 h-2 bg-green-500 rounded-full mr-2"></span>
                        <p class="text-xs text-gray-500 uppercase tracking-wide">
                            {{ userRoles.includes('admin') ? 'Administrator' : 'Manager' }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { defineProps, computed, watch } from "vue";
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
} from "lucide-vue-next";
import { Link, usePage } from "@inertiajs/vue3";

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

// Navigation Data
const navigationItems = [
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
                route: "admin.reports.generateReport",
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

// Helper Functions
const shouldShowItem = (item) => {
    if (userIsAdmin.value) return true;
    if (item.children) {
        // Show parent menu if user has permission for at least one child
        return item.children.some((child) => hasPermission(child.permission));
    }
    return hasPermission(item.permission);
};

// Check if a child menu item should be shown
const shouldShowChild = (child) => {
    if (userIsAdmin.value) return true;
    return hasPermission(child.permission);
};

// Get visible children for a menu item
const getVisibleChildren = (item) => {
    if (userIsAdmin.value) return item.children;
    return item.children.filter((child) => hasPermission(child.permission));
};

const isActive = (routeName) => {
    return routeName ? route().current(routeName) : false;
};

const isSubmenuActive = (item) => {
    if (!item.children) return false;
    return item.children.some((child) => isActive(child.route));
};

// Watchers
watch(
    () => route().current(),
    (currentRoute) => {
        navigationItems.forEach((item) => {
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
.scrollbar-hide::-webkit-scrollbar {
    display: none;
}
.scrollbar-hide {
    -ms-overflow-style: none;
    scrollbar-width: none;
}

/* Custom indicator for active menu items */
.router-link-active {
    position: relative;
}
</style>
