<script setup>
/**
 * Enhanced Admin Order Card Component
 * Displays comprehensive order information for admin decision making
 */
import { computed } from 'vue';
import {
    Calendar,
    Clock,
    MapPin,
    Phone,
    Mail,
    CreditCard,
    Package,
    Truck,
    AlertCircle,
    CheckCircle,
    XCircle,
    DollarSign,
    User,
    FileText,
    ExternalLink,
    Edit,
    Trash2,
    Eye,
} from 'lucide-vue-next';
import StatusBadge from './StatusBadge.vue';
import PaymentBadge from './PaymentBadge.vue';

const props = defineProps({
    order: {
        type: Object,
        required: true,
    },
    onEdit: {
        type: Function,
        default: () => {},
    },
    onDelete: {
        type: Function,
        default: () => {},
    },
    onView: {
        type: Function,
        default: () => {},
    },
});

const formatCurrency = (amount) => {
    return new Intl.NumberFormat("en-BD", {
        style: "currency",
        currency: "BDT",
        minimumFractionDigits: 0,
    }).format(amount).replace("BDT", "৳");
};

const formatDate = (dateString) => {
    if (!dateString) return "";
    const date = new Date(dateString);
    return date.toLocaleDateString("en-GB", {
        day: "2-digit",
        month: "short",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    });
};

const isToday = (dateString) => {
    if (!dateString) return false;
    const d = new Date(dateString);
    const today = new Date();
    return (
        d.getFullYear() === today.getFullYear() &&
        d.getMonth() === today.getMonth() &&
        d.getDate() === today.getDate()
    );
};

const orderAge = computed(() => {
    if (!props.order.date) return "";
    const orderDate = new Date(props.order.date);
    const now = new Date();
    const diffMs = now - orderDate;
    const diffHours = Math.floor(diffMs / (1000 * 60 * 60));
    const diffDays = Math.floor(diffHours / 24);

    if (diffHours < 1) return "Just now";
    if (diffHours < 24) return `${diffHours}h ago`;
    if (diffDays === 1) return "Yesterday";
    return `${diffDays} days ago`;
});

const riskLevel = computed(() => {
    const risks = [];

    // High-value order
    if (parseFloat(props.order.total) > 10000) risks.push("High Value");

    // Cash on delivery
    if (props.order.payment_method === 'cod') risks.push("COD");

    // First-time customer (check if customer has previous orders)
    // This would need to be passed from backend
    if (props.order.customer?.order_count === 1) risks.push("New Customer");

    // Unpaid after time
    if (props.order.payment_status === 'unpaid' && orderAge.value.includes('days')) {
        risks.push("Payment Overdue");
    }

    return risks;
});

const priorityLevel = computed(() => {
    if (riskLevel.value.length >= 3) return 'high';
    if (riskLevel.value.length >= 1) return 'medium';
    return 'low';
});

const truncateText = (text, limit) => {
    if (!text) return "";
    const words = text.split(" ");
    if (words.length > limit) {
        return words.slice(0, limit).join(" ") + "...";
    }
    return text;
};
</script>

<template>
    <div
        class="bg-white rounded-2xl shadow-sm border border-gray-200 hover:shadow-md transition-all duration-200 overflow-hidden"
        :class="{
            'ring-2 ring-red-200 border-red-300': priorityLevel === 'high',
            'ring-1 ring-amber-200 border-amber-300': priorityLevel === 'medium',
        }"
    >
        <!-- Header with Order Info and Priority -->
        <div class="p-4 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <!-- Order Number -->
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center px-3 py-1 bg-gradient-to-r from-emerald-500 to-emerald-600 text-white text-sm font-bold rounded-lg shadow-sm">
                            {{ order.order_number }}
                        </span>
                        <span
                            v-if="isToday(order.date)"
                            class="inline-flex items-center px-2 py-0.5 bg-green-100 text-green-700 text-xs font-semibold rounded-full animate-pulse"
                        >
                            NEW TODAY
                        </span>
                    </div>

                    <!-- Admin Notes Indicator -->
                    <div v-if="order.admin_notes" class="relative group">
                        <div class="w-6 h-6 bg-blue-100 rounded-full flex items-center justify-center cursor-help">
                            <FileText class="w-3 h-3 text-blue-600" />
                        </div>
                        <div class="absolute left-0 top-8 w-64 bg-gray-900 text-white text-xs rounded-lg p-2 opacity-0 group-hover:opacity-100 transition-opacity z-10 pointer-events-none">
                            <strong>Admin Notes:</strong> {{ truncateText(order.admin_notes, 20) }}
                        </div>
                    </div>
                </div>

                <!-- Priority and Age -->
                <div class="flex items-center gap-2">
                    <div class="text-right">
                        <div class="text-xs text-gray-500">{{ orderAge }}</div>
                        <div class="text-xs font-medium">{{ formatDate(order.date) }}</div>
                    </div>

                    <!-- Priority Indicator -->
                    <div
                        v-if="riskLevel.length > 0"
                        class="px-2 py-1 rounded-full text-xs font-semibold"
                        :class="{
                            'bg-red-100 text-red-700': priorityLevel === 'high',
                            'bg-amber-100 text-amber-700': priorityLevel === 'medium',
                            'bg-blue-100 text-blue-700': priorityLevel === 'low',
                        }"
                    >
                        {{ priorityLevel.toUpperCase() }}
                    </div>
                </div>
            </div>

            <!-- Risk Indicators -->
            <div v-if="riskLevel.length > 0" class="flex flex-wrap gap-1 mt-2">
                <span
                    v-for="risk in riskLevel"
                    :key="risk"
                    class="inline-flex items-center gap-1 px-2 py-0.5 bg-red-50 text-red-700 text-xs rounded-full border border-red-200"
                >
                    <AlertCircle class="w-3 h-3" />
                    {{ risk }}
                </span>
            </div>
        </div>

        <!-- Main Content Grid -->
        <div class="p-4 grid grid-cols-1 lg:grid-cols-3 gap-4">
            <!-- Customer Information -->
            <div class="bg-gray-50 rounded-xl p-3">
                <div class="flex items-center gap-2 mb-3">
                    <User class="w-4 h-4 text-blue-600" />
                    <h3 class="font-semibold text-gray-900">Customer</h3>
                </div>

                <div class="space-y-2">
                    <div class="flex items-center gap-2">
                        <span class="font-medium text-gray-900">{{ order.customer?.name }}</span>
                        <span
                            v-if="order.customer?.order_count === 1"
                            class="px-1.5 py-0.5 bg-purple-100 text-purple-700 text-xs rounded"
                        >
                            First Order
                        </span>
                    </div>

                    <div class="flex items-center gap-2 text-sm text-gray-600">
                        <Phone class="w-3 h-3" />
                        <span>{{ order.customer?.phone }}</span>
                    </div>

                    <div v-if="order.customer?.email" class="flex items-center gap-2 text-sm text-gray-600">
                        <Mail class="w-3 h-3" />
                        <span class="truncate">{{ order.customer?.email }}</span>
                    </div>

                    <div class="flex items-start gap-2 text-sm text-gray-600">
                        <MapPin class="w-3 h-3 mt-0.5 flex-shrink-0" />
                        <span class="line-clamp-2">{{ order.customer?.address }}</span>
                    </div>

                    <!-- Customer Note -->
                    <div v-if="order.customer?.note && order.customer.note !== 'N/A'" class="mt-2 p-2 bg-emerald-50 border border-emerald-200 rounded">
                        <div class="flex items-start gap-2">
                            <FileText class="w-3 h-3 text-emerald-600 mt-0.5" />
                            <div>
                                <div class="text-xs font-semibold text-emerald-800">Customer Note:</div>
                                <div class="text-xs text-emerald-700">{{ order.customer.note }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Order Details -->
            <div class="bg-gray-50 rounded-xl p-3">
                <div class="flex items-center gap-2 mb-3">
                    <Package class="w-4 h-4 text-green-600" />
                    <h3 class="font-semibold text-gray-900">Order Details</h3>
                </div>

                <div class="space-y-3">
                    <!-- Status and Payment -->
                    <div class="flex items-center justify-between">
                        <StatusBadge :status="order.status" size="sm" />
                        <PaymentBadge :status="order.payment_status" size="sm" />
                    </div>

                    <!-- Total Amount -->
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">Total:</span>
                        <span class="text-lg font-bold text-gray-900">{{ formatCurrency(order.total) }}</span>
                    </div>

                    <!-- Payment Method -->
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">Payment:</span>
                        <div class="flex items-center gap-1">
                            <CreditCard class="w-3 h-3 text-gray-500" />
                            <span class="text-sm font-medium capitalize">{{ order.payment_method || 'COD' }}</span>
                        </div>
                    </div>

                    <!-- Items Count -->
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">Items:</span>
                        <span class="text-sm font-medium">{{ order.items?.length || 0 }} product(s)</span>
                    </div>

                    <!-- Key Product Preview -->
                    <div v-if="order.items && order.items.length > 0" class="border-t border-gray-200 pt-2">
                        <div class="flex items-center gap-2">
                            <img
                                :src="order.items[0].product?.image || '/placeholder.png'"
                                :alt="order.items[0].product?.name"
                                class="w-8 h-8 rounded object-cover border"
                            />
                            <div class="flex-1 min-w-0">
                                <div class="text-xs font-medium truncate">{{ order.items[0].product?.name }}</div>
                                <div class="text-xs text-gray-500">Qty: {{ order.items[0].quantity }}</div>
                            </div>
                        </div>
                        <div v-if="order.items.length > 1" class="text-xs text-gray-500 mt-1">
                            +{{ order.items.length - 1 }} more item(s)
                        </div>
                    </div>
                </div>
            </div>

            <!-- Shipping & Actions -->
            <div class="bg-gray-50 rounded-xl p-3">
                <div class="flex items-center gap-2 mb-3">
                    <Truck class="w-4 h-4 text-orange-600" />
                    <h3 class="font-semibold text-gray-900">Fulfillment</h3>
                </div>

                <div class="space-y-3">
                    <!-- Courier Info -->
                    <div v-if="order.courier_name" class="space-y-2">
                        <div class="flex items-center gap-2">
                            <span class="text-xs bg-blue-100 text-blue-700 px-2 py-1 rounded-full font-medium">
                                {{ order.courier_name }}
                            </span>
                        </div>

                        <div v-if="order.consignment_id" class="text-xs text-gray-600 font-mono">
                            ID: {{ order.consignment_id }}
                        </div>

                        <a
                            v-if="order.tracking_number && order.tracking_number !== 'N/A'"
                            :href="`https://steadfast.com.bd/t/${order.tracking_number}`"
                            target="_blank"
                            class="inline-flex items-center gap-1 text-xs text-blue-600 hover:text-blue-800 hover:underline"
                        >
                            <ExternalLink class="w-3 h-3" />
                            Track Package
                        </a>
                    </div>

                    <div v-else class="text-xs text-gray-500 italic">
                        No courier assigned
                    </div>

                    <!-- Area Status -->
                    <div v-if="order.area_id" class="flex items-center gap-1">
                        <CheckCircle class="w-3 h-3 text-green-500" />
                        <span class="text-xs text-green-700">Address Verified</span>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex flex-col gap-2 pt-2 border-t border-gray-200">
                        <button
                            @click="onView(order)"
                            class="flex items-center justify-center gap-2 px-3 py-1.5 bg-blue-600 text-white text-xs font-medium rounded-lg hover:bg-blue-700 transition-colors"
                        >
                            <Eye class="w-3 h-3" />
                            View Details
                        </button>

                        <div class="grid grid-cols-2 gap-2">
                            <button
                                @click="onEdit(order)"
                                class="flex items-center justify-center gap-1 px-2 py-1.5 bg-amber-100 text-amber-700 text-xs font-medium rounded hover:bg-amber-200 transition-colors"
                            >
                                <Edit class="w-3 h-3" />
                                Edit
                            </button>

                            <button
                                @click="onDelete(order)"
                                class="flex items-center justify-center gap-1 px-2 py-1.5 bg-red-100 text-red-700 text-xs font-medium rounded hover:bg-red-200 transition-colors"
                            >
                                <Trash2 class="w-3 h-3" />
                                Delete
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
