<template>
    <Head title="Notifications" />
    <AdminLayout>
        <div>
            <h2 class="text-lg font-semibold mb-4">Notifications</h2>

            <div class="space-y-2">
            <div v-for="n in notifications.data" :key="n.id" class="p-4 bg-white dark:bg-gray-900 rounded-lg border border-gray-100 dark:border-gray-800">
                <div class="flex justify-between items-start">
                <div>
                    <div class="font-medium text-sm text-gray-800 dark:text-white truncate">{{ n.data.order_number ?? n.data.title ?? n.type }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ n.data.customer_name ? `${n.data.customer_name} • ${n.data.total}` : n.data.total }}</div>
                </div>
                <div class="text-xs text-gray-400">{{ formatDate(n.created_at) }}</div>
                </div>
            </div>

            <div class="mt-4">
                <Pagination :meta="notifications.meta" :links="notifications.links" />
            </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { usePage, Head } from "@inertiajs/vue3";
import { computed } from 'vue';
import AdminLayout from "@/Layouts/AdminLayout.vue";
import Pagination from "@/Components/Pagination.vue";

const page = usePage();

const notifications = computed(() => {
  try {
    if (page && page.props && page.props.value && page.props.value.notifications) return page.props.value.notifications;
    if (page && page.props && page.props.notifications) return page.props.notifications;
  } catch (e) {
    // ignore
  }
  return { data: [], meta: {}, links: [] };
});

function formatDate(v) {
  if (!v) return "";
  return new Date(v).toLocaleString();
}
</script>
