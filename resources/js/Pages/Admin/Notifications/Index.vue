<template>
    <Head title="Notifications" />
    <AdminLayout>
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-2xl font-semibold text-gray-900">Notifications</h2>

                <div class="flex items-center gap-2">
                    <button
                        v-if="selectedIds.length > 0"
                        @click="markSelectedAsRead"
                        class="px-3 py-1.5 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-md transition-colors"
                    >
                        Mark {{ selectedIds.length }} as read
                    </button>
                    <button
                        @click="markAllAsRead"
                        class="px-3 py-1.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 rounded-md transition-colors"
                    >
                        Mark all as read
                    </button>
                    <button
                        @click="clearReadNotifications"
                        class="px-3 py-1.5 text-sm font-medium text-red-700 bg-white border border-red-300 hover:bg-red-50 rounded-md transition-colors"
                    >
                        Clear read
                    </button>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                    <!-- Search -->
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Search</label>
                        <input
                            v-model="searchForm.search"
                            type="text"
                            placeholder="Search notifications..."
                            class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            @input="debouncedSearch"
                        />
                    </div>

                    <!-- Type Filter -->
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Type</label>
                        <select
                            v-model="searchForm.type"
                            class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            @change="applyFilters"
                        >
                            <option value="">All Types</option>
                            <option v-for="type in types" :key="type" :value="type">{{ type }}</option>
                        </select>
                    </div>

                    <!-- Status Filter -->
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Status</label>
                        <select
                            v-model="searchForm.status"
                            class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            @change="applyFilters"
                        >
                            <option value="">All</option>
                            <option value="unread">Unread</option>
                            <option value="read">Read</option>
                        </select>
                    </div>

                    <!-- Date From -->
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">From Date</label>
                        <input
                            v-model="searchForm.date_from"
                            type="date"
                            class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            @change="applyFilters"
                        />
                    </div>

                    <!-- Date To -->
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">To Date</label>
                        <input
                            v-model="searchForm.date_to"
                            type="date"
                            class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            @change="applyFilters"
                        />
                    </div>
                </div>

                <!-- Clear Filters -->
                <div class="mt-3 flex justify-end">
                    <button
                        @click="clearFilters"
                        class="px-3 py-1.5 text-xs font-medium text-gray-600 hover:text-gray-800 transition-colors"
                    >
                        Clear filters
                    </button>
                </div>
            </div>

            <!-- Notifications List -->
            <div class="space-y-2">
                <div v-if="notifications.data.length === 0" class="p-8 bg-white rounded-lg border border-gray-200 text-center">
                    <Bell class="w-12 h-12 text-gray-300 mx-auto mb-3" />
                    <p class="text-sm text-gray-500">No notifications found</p>
                </div>

                <div
                    v-for="n in notifications.data"
                    :key="n.id"
                    class="p-4 bg-white dark:bg-gray-900 rounded-lg border border-gray-100 dark:border-gray-800 hover:shadow-md transition-all cursor-pointer group"
                    :class="{ 'bg-blue-50/30 border-blue-200': !n.is_read }"
                    @click="handleNotificationClick(n)"
                >
                    <div class="flex items-start gap-4">
                        <!-- Checkbox -->
                        <input
                            type="checkbox"
                            :checked="selectedIds.includes(n.id)"
                            @click.stop="toggleSelection(n.id)"
                            class="mt-1 h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                        />

                        <!-- Icon -->
                        <div class="p-2 bg-blue-50 text-blue-600 rounded-md">
                            <ShoppingCart v-if="n.data?.order_number" class="w-5 h-5" />
                            <Bell v-else class="w-5 h-5" />
                        </div>

                        <!-- Content -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex-1">
                                    <div class="font-medium text-sm text-gray-800 dark:text-white truncate" :class="{ 'font-bold': !n.is_read }">
                                        {{ n.data?.order_number ?? n.data?.title ?? n.type }}
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                        {{ n.data?.customer_name ? `${n.data.customer_name} • ${n.data.total ?? ''}` : n.data?.total ?? 'System notification' }}
                                    </div>
                                </div>
                                <div class="flex items-center gap-3">
                                    <div class="text-xs text-gray-400">{{ formatDate(n.created_at) }}</div>
                                    <div v-if="!n.is_read" class="w-2 h-2 bg-blue-600 rounded-full"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="notifications.data.length > 0" class="mt-4">
                <Pagination :meta="notifications.meta" :links="notifications.links" />
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { usePage, Head, router } from "@inertiajs/vue3";
import { computed, ref } from 'vue';
import AdminLayout from "@/Layouts/AdminLayout.vue";
import Pagination from "@/Components/Pagination.vue";
import { Bell, ShoppingCart } from "lucide-vue-next";
import { toast } from '@steveyuowo/vue-hot-toast';

const page = usePage();

const props = defineProps({
    notifications: Object,
    types: Array,
    filters: Object,
});

const selectedIds = ref([]);

const searchForm = ref({
    search: props.filters?.search || '',
    type: props.filters?.type || '',
    status: props.filters?.status || '',
    date_from: props.filters?.date_from || '',
    date_to: props.filters?.date_to || '',
});

function formatDate(v) {
    if (!v) return "";
    return new Date(v).toLocaleString();
}

// Debounced search
let searchTimeout;
function debouncedSearch() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        applyFilters();
    }, 500);
}

function applyFilters() {
    router.get(route('admin.notifications.index'), searchForm.value, {
        preserveState: true,
        preserveScroll: true,
    });
}

function clearFilters() {
    searchForm.value = {
        search: '',
        type: '',
        status: '',
        date_from: '',
        date_to: '',
    };
    applyFilters();
}

function toggleSelection(id) {
    const index = selectedIds.value.indexOf(id);
    if (index > -1) {
        selectedIds.value.splice(index, 1);
    } else {
        selectedIds.value.push(id);
    }
}

async function handleNotificationClick(notification) {
    try {
        // Mark as read
        await axios.post(route('admin.notifications.mark-as-read', notification.id));

        // Navigate to related page
        let url = null;

        if (notification.order_id) {
            url = `/admin/orders/${notification.order_id}`;
        } else if (notification.data?.product_id) {
            url = `/admin/products/${notification.data.product_id}/edit`;
        }

        if (url) {
            router.visit(url);
        } else {
            // Just reload to show updated read status
            router.reload({ preserveScroll: true });
        }

        toast.success('Notification marked as read');
    } catch (error) {
        console.error('Error:', error);
        toast.error('Failed to mark notification as read');
    }
}

async function markAllAsRead() {
    try {
        await axios.post(route('admin.notifications.mark-all-read'));
        router.reload({ preserveScroll: true });
        toast.success('All notifications marked as read');
    } catch (error) {
        console.error('Error:', error);
        toast.error('Failed to mark all as read');
    }
}

async function markSelectedAsRead() {
    if (selectedIds.value.length === 0) return;

    try {
        await axios.post(route('admin.notifications.mark-multiple-read'), {
            ids: selectedIds.value
        });
        selectedIds.value = [];
        router.reload({ preserveScroll: true });
        toast.success('Selected notifications marked as read');
    } catch (error) {
        console.error('Error:', error);
        toast.error('Failed to mark selected as read');
    }
}

async function clearReadNotifications() {
    if (!confirm('Are you sure you want to delete all read notifications?')) return;

    try {
        await axios.delete(route('admin.notifications.clear-read'));
        router.reload({ preserveScroll: true });
        toast.success('Read notifications cleared');
    } catch (error) {
        console.error('Error:', error);
        toast.error('Failed to clear notifications');
    }
}
</script>
