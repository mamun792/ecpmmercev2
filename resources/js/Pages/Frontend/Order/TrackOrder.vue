<script setup>
import { ref } from "vue";
import { useForm, Head } from "@inertiajs/vue3";
import FrontendLayout from "@/Layouts/FrontendLayout.vue";

const props = defineProps({
    orders: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({ track_input: "" }),
    },
    searched: {
        type: Boolean,
        default: false,
    },
});

const form = useForm({
    track_input: props.filters.track_input || "",
});

const submit = () => {
    form.get(route("order.track"), {
        preserveState: true,
        preserveScroll: true,
    });
};

const statuses = ["pending", "processing", "shipped", "delivered"];

const cancellationStatuses = ["cancelled", "returned"];

const getStatusIndex = (status) => {
    return statuses.indexOf(status);
};

const isStatusCompleted = (currentStatus, stepStatus) => {
    if (currentStatus === "cancelled" || currentStatus === "returned") {
        return currentStatus === stepStatus;
    }
    const currentIndex = getStatusIndex(currentStatus);
    const stepIndex = getStatusIndex(stepStatus);
    return stepIndex <= currentIndex;
};
</script>

<template>
    <Head title="Track Order" />
    <FrontendLayout>
        <div class="min-h-screen bg-white py-12 px-4 sm:px-6 lg:px-8 relative">
            <div class="max-w-4xl mx-auto relative z-10">
                <!-- Header -->
                <div class="text-center mb-12">
                    <h1
                        class="text-3xl md:text-5xl font-bold text-gray-900 mb-4"
                    >
                        Track Your Order
                    </h1>
                    <p class="text-gray-600 text-lg">
                        Enter your Order Number or Phone Number to check status.
                    </p>
                </div>

                <!-- Search Card -->
                <div
                    class="bg-gray-50 border border-gray-100 rounded-2xl p-8 mb-12 shadow-sm"
                >
                    <form @submit.prevent="submit" class="space-y-6">
                        <div class="max-w-xl mx-auto">
                            <label
                                for="track_input"
                                class="block text-sm font-medium text-gray-700 mb-2"
                            >
                                Order Number or Phone Number
                            </label>
                            <div class="relative">
                                <input
                                    id="track_input"
                                    v-model="form.track_input"
                                    type="text"
                                    placeholder="Enter Order # or Phone Number"
                                    class="w-full px-5 py-4 text-lg border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-colors bg-white shadow-sm"
                                    required
                                />
                                <button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="absolute right-2 top-2 bottom-2 bg-primary text-white font-bold px-6 rounded-md hover:bg-primary/90 transition-colors disabled:opacity-50"
                                >
                                    <svg
                                        v-if="form.processing"
                                        class="animate-spin h-5 w-5"
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
                                    <span v-else>TRACK</span>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Results -->
                <div v-if="searched">
                    <div v-if="orders.length > 0" class="space-y-8">
                        <div
                            v-for="order in orders"
                            :key="order.id"
                            class="bg-white rounded-xl p-6 shadow-md border border-gray-100"
                        >
                            <!-- Order Header -->
                            <div
                                class="flex flex-col md:flex-row justify-between md:items-center mb-6 border-b border-gray-100 pb-4 gap-4"
                            >
                                <div>
                                    <h3
                                        class="text-xl font-bold text-gray-900 flex items-center gap-3"
                                    >
                                        Order {{ order.order_number }}
                                        <span
                                            :class="{
                                                'bg-yellow-100 text-yellow-800':
                                                    order.status === 'pending',
                                                'bg-blue-100 text-blue-800':
                                                    order.status ===
                                                    'processing',
                                                'bg-indigo-100 text-indigo-800':
                                                    order.status === 'shipped',
                                                'bg-green-100 text-green-800':
                                                    order.status ===
                                                        'delivered' ||
                                                    order.status ===
                                                        'confirmed',
                                                'bg-red-100 text-red-800':
                                                    order.status ===
                                                        'cancelled' ||
                                                    order.status === 'returned',
                                                'bg-gray-100 text-gray-800':
                                                    order.status ===
                                                        'on_hold' ||
                                                    order.status ===
                                                        'incomplete',
                                            }"
                                            class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wide"
                                        >
                                            {{ order.status }}
                                        </span>
                                    </h3>
                                    <p class="text-gray-500 text-sm mt-1">
                                        Date:
                                        {{
                                            new Date(
                                                order.created_at
                                            ).toLocaleDateString()
                                        }}
                                    </p>
                                </div>
                                <div class="text-left md:text-right">
                                    <p class="text-sm text-gray-500">
                                        Total Amount
                                    </p>
                                    <p class="text-xl font-bold text-primary">
                                        {{ order.total }}
                                    </p>
                                </div>
                            </div>

                            <!-- Progress Bar (Desktop) -->
                            <div
                                v-if="
                                    [
                                        'pending',
                                        'processing',
                                        'shipped',
                                        'delivered',
                                        'cancelled',
                                        'returned',
                                    ].includes(order.status)
                                "
                                class="mb-8 px-2 hidden md:block"
                            >
                                <div class="relative">
                                    <div
                                        class="overflow-hidden h-2 mb-4 text-xs flex rounded bg-gray-100"
                                    >
                                        <div
                                            v-if="
                                                !cancellationStatuses.includes(
                                                    order.status
                                                )
                                            "
                                            :class="{
                                                'w-[25%]':
                                                    order.status === 'pending',
                                                'w-[50%]':
                                                    order.status ===
                                                    'processing',
                                                'w-[75%]':
                                                    order.status === 'shipped',
                                                'w-[100%]':
                                                    order.status ===
                                                    'delivered',
                                            }"
                                            class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bg-primary transition-all duration-500"
                                        ></div>
                                        <div
                                            v-else
                                            class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bg-red-500 w-full transition-all duration-500"
                                        ></div>
                                    </div>
                                    <div
                                        class="flex justify-between text-xs font-medium text-gray-400"
                                    >
                                        <div
                                            :class="{
                                                'text-primary font-bold':
                                                    isStatusCompleted(
                                                        order.status,
                                                        'pending'
                                                    ),
                                            }"
                                        >
                                            Pending
                                        </div>
                                        <div
                                            :class="{
                                                'text-primary font-bold':
                                                    isStatusCompleted(
                                                        order.status,
                                                        'processing'
                                                    ),
                                            }"
                                        >
                                            Processing
                                        </div>
                                        <div
                                            :class="{
                                                'text-primary font-bold':
                                                    isStatusCompleted(
                                                        order.status,
                                                        'shipped'
                                                    ),
                                            }"
                                        >
                                            Shipped
                                        </div>
                                        <div
                                            :class="{
                                                'text-primary font-bold':
                                                    isStatusCompleted(
                                                        order.status,
                                                        'delivered'
                                                    ),
                                            }"
                                        >
                                            Delivered
                                        </div>
                                        <div
                                            v-if="order.status === 'cancelled'"
                                            class="text-red-600 font-bold"
                                        >
                                            Cancelled
                                        </div>
                                        <div
                                            v-if="order.status === 'returned'"
                                            class="text-red-600 font-bold"
                                        >
                                            Returned
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Progress Bar (Mobile Vertical) -->
                            <div
                                v-if="
                                    [
                                        'pending',
                                        'processing',
                                        'shipped',
                                        'delivered',
                                        'cancelled',
                                        'returned',
                                    ].includes(order.status)
                                "
                                class="mb-8 px-4 md:hidden"
                            >
                                <div
                                    class="relative pl-6 border-l-2 border-gray-200 ml-2 space-y-8"
                                >
                                    <!-- Pending -->
                                    <div class="relative">
                                        <div
                                            class="absolute -left-[21px] bg-white border-2 rounded-full w-4 h-4 mt-0.5"
                                            :class="
                                                isStatusCompleted(
                                                    order.status,
                                                    'pending'
                                                )
                                                    ? 'border-primary bg-primary'
                                                    : 'border-gray-300'
                                            "
                                        ></div>
                                        <p
                                            class="text-sm font-medium -mt-1"
                                            :class="
                                                isStatusCompleted(
                                                    order.status,
                                                    'pending'
                                                )
                                                    ? 'text-primary font-bold'
                                                    : 'text-gray-400'
                                            "
                                        >
                                            Pending
                                        </p>
                                    </div>
                                    <!-- Processing -->
                                    <div class="relative">
                                        <div
                                            class="absolute -left-[21px] bg-white border-2 rounded-full w-4 h-4 mt-0.5"
                                            :class="
                                                isStatusCompleted(
                                                    order.status,
                                                    'processing'
                                                )
                                                    ? 'border-primary bg-primary'
                                                    : 'border-gray-300'
                                            "
                                        ></div>
                                        <p
                                            class="text-sm font-medium -mt-1"
                                            :class="
                                                isStatusCompleted(
                                                    order.status,
                                                    'processing'
                                                )
                                                    ? 'text-primary font-bold'
                                                    : 'text-gray-400'
                                            "
                                        >
                                            Processing
                                        </p>
                                    </div>
                                    <!-- Shipped -->
                                    <div class="relative">
                                        <div
                                            class="absolute -left-[31px] bg-white border-2 rounded-full w-4 h-4 mt-0.5"
                                            :class="
                                                isStatusCompleted(
                                                    order.status,
                                                    'shipped'
                                                )
                                                    ? 'border-primary bg-primary'
                                                    : 'border-gray-300'
                                            "
                                        ></div>
                                        <p
                                            class="text-sm font-medium -mt-1"
                                            :class="
                                                isStatusCompleted(
                                                    order.status,
                                                    'shipped'
                                                )
                                                    ? 'text-primary font-bold'
                                                    : 'text-gray-400'
                                            "
                                        >
                                            Shipped
                                        </p>
                                    </div>
                                    <!-- Delivered -->
                                    <div class="relative">
                                        <div
                                            class="absolute -left-[31px] bg-white border-2 rounded-full w-4 h-4 mt-0.5"
                                            :class="
                                                isStatusCompleted(
                                                    order.status,
                                                    'delivered'
                                                )
                                                    ? 'border-primary bg-primary'
                                                    : 'border-gray-300'
                                            "
                                        ></div>
                                        <p
                                            class="text-sm font-medium -mt-1"
                                            :class="
                                                isStatusCompleted(
                                                    order.status,
                                                    'delivered'
                                                )
                                                    ? 'text-primary font-bold'
                                                    : 'text-gray-400'
                                            "
                                        >
                                            Delivered
                                        </p>
                                    </div>

                                    <!-- Internal Cancellation Step -->
                                    <div
                                        v-if="order.status === 'cancelled'"
                                        class="relative"
                                    >
                                        <div
                                            class="absolute -left-[21px] bg-red-500 border-2 border-red-500 rounded-full w-4 h-4 mt-0.5"
                                        ></div>
                                        <p
                                            class="text-sm font-bold text-red-600 -mt-1"
                                        >
                                            Cancelled
                                        </p>
                                    </div>

                                    <!-- Internal Return Step -->
                                    <div
                                        v-if="order.status === 'returned'"
                                        class="relative"
                                    >
                                        <div
                                            class="absolute -left-[21px] bg-red-500 border-2 border-red-500 rounded-full w-4 h-4 mt-0.5"
                                        ></div>
                                        <p
                                            class="text-sm font-bold text-red-600 -mt-1"
                                        >
                                            Returned
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Items Summary -->
                            <div class="bg-gray-50 rounded-lg p-4">
                                <h4
                                    class="text-sm font-semibold text-gray-900 mb-3 uppercase tracking-wider"
                                >
                                    Items in Order
                                </h4>
                                <div class="space-y-3">
                                    <div
                                        v-for="item in order.items"
                                        :key="item.id"
                                        class="flex gap-4 items-center bg-white p-3 rounded border border-gray-100"
                                    >
                                        <div
                                            class="h-12 w-12 bg-gray-100 rounded flex-shrink-0 flex items-center justify-center overflow-hidden"
                                        >
                                            <img
                                                v-if="
                                                    item.product &&
                                                    item.product
                                                        .feature_image_url
                                                "
                                                :src="
                                                    item.product
                                                        .feature_image_url
                                                "
                                                class="h-full w-full object-cover"
                                            />
                                            <svg
                                                v-else
                                                class="w-6 h-6 text-gray-400"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                                                ></path>
                                            </svg>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p
                                                class="text-sm font-medium text-gray-900 truncate"
                                            >
                                                {{
                                                    item.product
                                                        ? item.product.name
                                                        : item.product_name ||
                                                          "Product"
                                                }}
                                            </p>
                                            <div
                                                v-if="
                                                    item.product_variation &&
                                                    item.product_variation
                                                        .attributes
                                                "
                                                class="flex flex-wrap gap-2 mt-1"
                                            >
                                                <span
                                                    v-for="attr in item
                                                        .product_variation
                                                        .attributes"
                                                    :key="attr.id"
                                                    class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded"
                                                >
                                                    {{
                                                        attr.value.attribute
                                                            .name
                                                    }}: {{ attr.value.value }}
                                                </span>
                                            </div>
                                            <p
                                                class="text-xs text-gray-500 mt-1"
                                            >
                                                Qty: {{ item.quantity }}
                                            </p>
                                        </div>
                                        <div
                                            class="text-sm font-semibold text-gray-900"
                                        >
                                            {{ item.total }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div
                        v-else
                        class="text-center py-12 bg-gray-50 rounded-2xl border border-gray-100"
                    >
                        <div
                            class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-200 mb-4"
                        >
                            <svg
                                class="w-8 h-8 text-gray-500"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                                ></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 mb-1">
                            No Orders Found
                        </h3>
                        <p class="text-gray-500 max-w-sm mx-auto">
                            We couldn't find any orders matching "<strong>{{
                                form.track_input
                            }}</strong
                            >". Please try again.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </FrontendLayout>
</template>
