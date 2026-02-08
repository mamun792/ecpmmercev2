<template>
    <header
        class="bg-white border-b border-gray-200 h-16 flex items-center justify-between px-6 z-20 transition-all duration-300 sticky top-0 shadow-sm"
    >
        <!-- Left Section: Toggle & Title -->
        <div class="flex items-center gap-4">
            <button
                @click="toggleSidebar"
                class="p-2 rounded-md text-gray-500 hover:text-gray-700 hover:bg-gray-100 transition-all duration-200"
            >
                <Menu class="h-5 w-5" />
            </button>

            <div class="hidden lg:block">
                <h1 class="text-lg font-semibold text-gray-900">
                    {{ currentRouteLabel }}
                </h1>
            </div>
        </div>

        <!-- Right Section: Actions -->
        <div class="flex items-center gap-3">
            <!-- Notifications -->
            <div class="relative">
                <button
                    @click="toggleNotifications"
                    class="p-2 rounded-md text-gray-500 hover:text-gray-700 hover:bg-gray-100 transition-all relative"
                >
                    <Bell class="h-5 w-5" />
                    <span v-if="unreadCount > 0"
                        class="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full"
                    ></span>
                </button>

                <transition
                    enter-active-class="transition ease-out duration-200"
                    enter-from-class="transform opacity-0 translate-y-2"
                    enter-to-class="transform opacity-100 translate-y-0"
                    leave-active-class="transition ease-in duration-150"
                    leave-from-class="transform opacity-100 translate-y-0"
                    leave-to-class="transform opacity-0 translate-y-2"
                >
                    <div
                        v-if="isNotificationsOpen"
                        class="absolute right-0 top-14 w-80 bg-white rounded-lg shadow-lg border border-gray-200 py-4 z-50"
                    >
                        <div class="px-4 pb-3 border-b border-gray-100 flex items-center justify-between">
                            <p class="text-sm font-semibold text-gray-900">Notifications</p>
                            <div class="flex items-center gap-2">
                                <span v-if="unreadCount > 0" class="text-xs font-medium text-blue-600 bg-blue-50 px-2 py-1 rounded">{{ unreadCount }} new</span>
                                <button
                                    v-if="unreadCount > 0"
                                    @click="markAllAsRead"
                                    class="text-xs font-medium text-gray-600 hover:text-blue-600 transition-colors"
                                    title="Mark all as read"
                                >
                                    ✓ All
                                </button>
                            </div>
                        </div>

                        <div class="max-h-96 overflow-y-auto">
                            <div v-if="notifications.length === 0" class="px-4 py-8 text-center">
                                <Bell class="w-8 h-8 text-gray-300 mx-auto mb-2" />
                                <p class="text-sm text-gray-500">No notifications</p>
                            </div>

                            <button
                                v-for="n in notifications.slice(0, 5)"
                                :key="n.id"
                                @click="handleNotificationClick(n)"
                                class="w-full text-left px-4 py-3 hover:bg-gray-50 transition-all border-b border-gray-50 last:border-0"
                                :class="{ 'bg-blue-50/30': !n.is_read }"
                            >
                                <div class="flex items-start gap-3">
                                    <div class="p-1.5 bg-blue-50 text-blue-600 rounded-md flex-shrink-0">
                                        <ShoppingCart v-if="n.data?.order_number" class="w-4 h-4" />
                                        <Bell v-else class="w-4 h-4" />
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="text-sm font-medium text-gray-900 truncate" :class="{ 'font-bold': !n.is_read }">
                                            {{ n.data?.order_number ?? n.data?.title ?? n.type }}
                                        </div>
                                        <p class="text-xs text-gray-500 mt-0.5 truncate">
                                            {{ n.data?.customer_name ?? 'System' }}
                                        </p>
                                        <p class="text-xs text-gray-400 mt-1">{{ formatDate(n.created_at) }}</p>
                                    </div>
                                    <div v-if="!n.is_read" class="w-2 h-2 bg-blue-600 rounded-full flex-shrink-0 mt-1"></div>
                                </div>
                            </button>
                        </div>

                        <div class="px-4 pt-3 border-t border-gray-100">
                            <Link :href="route('admin.notifications.index')" class="text-sm font-medium text-blue-600 hover:text-blue-700 transition-colors">
                                View all notifications
                            </Link>
                        </div>
                    </div>
                </transition>
            </div>

            <!-- BD Time Display -->
            <div class="hidden sm:flex flex-col items-end mr-4">
                <span class="text-sm font-medium text-gray-900">{{ bdTime }}</span>
                <span class="text-xs text-gray-400">BD Time</span>
            </div>

            <!-- Profile -->
            <div class="relative">
                <button
                    @click="toggleProfile"
                    class="flex items-center gap-3 p-1.5 rounded-lg hover:bg-gray-50 transition-all"
                >
                    <div class="relative">
                        <div class="w-8 h-8 rounded-lg bg-gray-600 text-white flex items-center justify-center font-medium text-sm">
                            {{ $page.props.auth.user.name.charAt(0) }}
                        </div>
                        <div class="absolute -bottom-0.5 -right-0.5 w-3 h-3 bg-green-500 border-2 border-white rounded-full"></div>
                    </div>
                    <div class="hidden sm:block text-left">
                        <p class="text-sm font-medium text-gray-900">
                            {{ $page.props.auth.user.name }}
                        </p>
                        <p class="text-xs text-gray-500">Administrator</p>
                    </div>
                    <ChevronDown
                        class="h-4 w-4 text-gray-400 transition-transform duration-200"
                        :class="{ 'rotate-180': isProfileOpen }"
                    />
                </button>

                <!-- Profile Dropdown -->
                <transition
                    enter-active-class="transition ease-out duration-200"
                    enter-from-class="transform opacity-0 translate-y-2"
                    enter-to-class="transform opacity-100 translate-y-0"
                    leave-active-class="transition ease-in duration-150"
                    leave-from-class="transform opacity-100 translate-y-0"
                    leave-to-class="transform opacity-0 translate-y-2"
                >
                    <div
                        v-if="isProfileOpen"
                        class="absolute right-0 mt-2 w-64 bg-white rounded-lg shadow-lg border border-gray-200 py-2 z-50"
                    >
                        <div class="px-4 py-3 border-b border-gray-100">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-gray-600 text-white flex items-center justify-center font-medium text-sm">
                                    {{ $page.props.auth.user.name.charAt(0) }}
                                </div>
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-gray-900 truncate">{{ $page.props.auth.user.name }}</p>
                                    <p class="text-xs text-gray-500 truncate">{{ $page.props.auth.user.email }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 mt-3 px-2 py-1 bg-green-50 text-green-600 rounded text-xs w-fit">
                                <div class="w-1.5 h-1.5 rounded-full bg-green-500"></div>
                                <span class="font-medium">Online</span>
                            </div>
                        </div>

                        <div class="py-1">
                            <Link class="flex items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors" :href="route('admin.profile.edit')">
                                <User class="w-4 h-4" />
                                Profile Settings
                            </Link>

                            <Link class="flex items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors" :href="route('admin.maintenance.index')">
                                <Database class="w-4 h-4" />
                                System Settings
                            </Link>
                        </div>

                        <div class="border-t border-gray-100 pt-1">
                            <Link class="flex items-center gap-3 px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors" :href="route('logout')" method="post" as="button">
                                <LogOut class="w-4 h-4" />
                                Sign Out
                            </Link>
                        </div>
                    </div>
                </transition>
            </div>
        </div>
    </header>
</template>

<script setup>
import { defineProps, ref, computed, onMounted, onUnmounted } from "vue";
import { usePage, router } from "@inertiajs/vue3";
import {
    Menu,
    Sun,
    Moon,
    Maximize2,
    Minimize2,
    ChevronDown,
    Bell,
    User,
    LogOut,
    Settings,
    ShoppingCart,
    RefreshCw,
    Database,
} from "lucide-vue-next";
import { Link } from "@inertiajs/vue3";
import { toast } from '@steveyuowo/vue-hot-toast';

defineProps({
    sidebarOpen: Boolean,
    toggleSidebar: Function,
    isDark: Boolean,
    toggleDarkMode: Function,
    isFullscreen: Boolean,
    toggleFullscreen: Function,
    isProfileOpen: Boolean,
    toggleProfile: Function,
});

// Notifications state
const isNotificationsOpen = ref(false);
const toggleNotifications = () => (isNotificationsOpen.value = !isNotificationsOpen.value);

const page = usePage();

// Notification sound (optional - will fail silently if file doesn't exist)
let notificationSound;
try {
    notificationSound = new Audio('/assets/notification.wav');
    notificationSound.volume = 0.5; // Set volume to 50%
} catch (e) {
    console.log('Notification sound not loaded:', e);
}

// Bangladesh (Dhaka) time display
const bdTime = ref('');
let bdTimer = null;
function updateBdTime() {
    try {
        // 12-hour format with AM/PM for Bangladesh (Asia/Dhaka)
        bdTime.value = new Date().toLocaleTimeString('en-US', { timeZone: 'Asia/Dhaka', hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true });
    } catch (e) {
        // fallback to 12-hour format in local locale
        bdTime.value = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true });
    }
}

onMounted(() => {
    updateBdTime();
    bdTimer = setInterval(updateBdTime, 1000);

    // Listen for real-time notifications (if Echo is available)
    if (window.Echo) {
        window.Echo.channel('notifications')
            .listen('.notification.sent', (event) => {
                console.log('New notification received:', event);

                // Play sound
                try {
                    if (notificationSound) {
                        notificationSound.play().catch(err => {
                            console.log('Could not play notification sound:', err);
                        });
                    }
                } catch (e) {
                    console.log('Notification sound error:', e);
                }

                // Show toast
                toast.success(`New notification: ${event.data?.order_number || 'New event'}`, {
                    duration: 4000,
                });

                // Reload notifications
                router.reload({ only: ['adminNotifications'] });
            });
    } else {
        // Fallback: Poll for new notifications every 60 seconds if Echo is not available
        setInterval(() => {
            router.reload({ only: ['adminNotifications'], preserveScroll: true, preserveState: true });
        }, 60000);
    }
});

onUnmounted(() => {
    if (bdTimer) clearInterval(bdTimer);

    // Leave Echo channel
    if (window.Echo) {
        window.Echo.leave('notifications');
    }
});

const currentRouteLabel = computed(() => {
    try {
        if (typeof route !== 'function') return 'Console';
        const current = route().current();
        if (!current) return 'Console';

        const parts = current.split('.');
        // resource is typically the second-to-last segment (e.g., admin.products.index)
        const resource = parts.length >= 2 ? parts[parts.length - 2] : parts[parts.length - 1];
        const action = parts[parts.length - 1];

        const humanize = (s) => s.replace(/[-_]/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
        const resourceLabel = humanize(resource);

        const actionMap = {
            index: 'List',
            create: 'Create',
            edit: 'Edit',
            show: 'Show',
            store: 'Store',
            update: 'Update',
        };

        const actionLabel = actionMap[action] || (action ? humanize(action) : '');

        if (!action || action === 'index') return resourceLabel;
        return `${resourceLabel} - ${actionLabel}`;
    } catch (e) {
        return 'Console';
    }
});

// Safely resolve adminNotifications from Inertia props
const adminNotifications = computed(() => {
    try {
        if (page && page.props && page.props.value && page.props.value.adminNotifications) {
            return page.props.value.adminNotifications;
        }
        if (page && page.props && page.props.adminNotifications) {
            return page.props.adminNotifications;
        }
    } catch (e) {
        // ignore and fall through
    }
    return { count: 0, notifications: [] };
});

const unreadCount = computed(() => adminNotifications.value?.count || 0);

// Normalize notifications into an array for safe iteration in template
const notifications = computed(() => {
    const n = adminNotifications.value?.notifications || [];
    if (Array.isArray(n)) return n;
    // Inertia pagination may provide an object with `data`
    if (n && n.data && Array.isArray(n.data)) return n.data;
    // As a last resort, convert object values to array
    if (n && typeof n === 'object') return Object.values(n);
    return [];
});

function formatDate(value) {
    if (!value) return '';
    try {
        return new Date(value).toLocaleString();
    } catch (e) {
        return value;
    }
}

// Handle notification click - navigate to related page and mark as read
async function handleNotificationClick(notification) {
    try {
        // Mark as read first
        await axios.post(route('admin.notifications.mark-as-read', notification.id));

        // Close dropdown
        isNotificationsOpen.value = false;

        // Navigate to related page
        let url = null;

        if (notification.order_id) {
            url = `/admin/orders/${notification.order_id}`;
        } else if (notification.data?.product_id) {
            url = `/admin/products/${notification.data.product_id}/edit`;
        }

        if (url) {
            router.visit(url);
            toast.success('Notification marked as read');
        } else {
            // Just mark as read and reload notifications
            router.reload({ only: ['adminNotifications'] });
            toast.success('Notification marked as read');
        }
    } catch (error) {
        console.error('Error marking notification as read:', error);
        toast.error('Failed to mark notification as read');
    }
}

// Mark all as read
async function markAllAsRead() {
    try {
        await axios.post(route('admin.notifications.mark-all-read'));
        router.reload({ only: ['adminNotifications'] });
        toast.success('All notifications marked as read');
        isNotificationsOpen.value = false;
    } catch (error) {
        console.error('Error marking all as read:', error);
        toast.error('Failed to mark all as read');
    }
}
</script>
