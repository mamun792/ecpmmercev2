<template>
    <div class="relative">
        <select
            :value="localSelected"
            @change="onChange($event)"
            :disabled="isLoading"
            class="appearance-none w-full rounded-md border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            :class="getStatusClasses"
        >
            <option
                v-for="option in statusOptions"
                :key="option.value"
                :value="option.value"
            >
                {{ option.label }}
            </option>
        </select>
        <div
            class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2"
        >
            <svg
                v-if="!isLoading"
                class="h-4 w-4"
                :class="getArrowColor"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
                xmlns="http://www.w3.org/2000/svg"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M19 9l-7 7-7-7"
                ></path>
            </svg>
            <svg
                v-else
                class="h-4 w-4 text-blue-500 animate-spin"
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
        </div>
    </div>
</template>

<script setup>
import { computed, ref, watch } from "vue";

// Define props
const props = defineProps({
    modelValue: {
        type: String,
        required: true,
    },
    orderId: {
        type: [String, Number],
        required: true,
    },
    isLoading: {
        type: Boolean,
        default: false,
    },
});

// Define emits — only notify parent about status-change (no optimistic update)
const emit = defineEmits(["status-change"]);

// Local selection mirrors the prop but will NOT be changed optimistically
const localSelected = ref(props.modelValue);
watch(() => props.modelValue, (val) => {
    localSelected.value = val;
});

// Status options with exact colors from the second image
const statusOptions = [
    { value: "pending", label: "Pending", bgClass: "bg-blue-500 text-white" },
    {
        value: "processing",
        label: "Processing",
        bgClass: "bg-primary text-white",
    },
    { value: "shipped", label: "Shipped", bgClass: "bg-yellow-500 text-black" },
    {
        value: "delivered",
        label: "Delivered",
        bgClass: "bg-green-500 text-white",
    },
    {
        value: "cancelled",
        label: "Cancelled",
        bgClass: "bg-red-500 text-white",
    },
    {
        value: "returned",
        label: "Returned",
        bgClass: "bg-purple-500 text-white",
    },
    {
        value: "incomplete",
        label: "Incomplete",
        bgClass: "bg-gray-500 text-white",
    },
    { value: "on_hold", label: "On Hold", bgClass: "bg-teal-500 text-white" },
    {
        value: "confirmed",
        label: "Confirmed",
        bgClass: "bg-indigo-500 text-white",
    },
];

// Computed property to get the appropriate styling based on status
const getStatusClasses = computed(() => {
    const currentOption = statusOptions.find(
        (option) => option.value === localSelected.value
    );

    // Default styling if no matching status is found
    const baseClass = currentOption?.bgClass || "bg-gray-500 text-white";

    return [
        baseClass,
        props.isLoading ? "opacity-50" : "",
        "border-0", // Remove border for cleaner look
    ];
});

// Get appropriate arrow color based on text color
const getArrowColor = computed(() => {
    const isBlackText = localSelected.value === "shipped"; // Yellow background has black text
    return isBlackText ? "text-black" : "text-white";
});

// Handle change without optimistic UI update — emit event and keep select showing previous value
const onChange = (event) => {
    const newStatus = event.target.value;

    // Revert visible value to previous selection until parent confirms
    event.target.value = localSelected.value;

    // Notify parent to perform the status change
    emit("status-change", props.orderId, newStatus);
};
</script>

<style>
/* Target the option elements inside select to style them 
   Note: browser support for styling options is limited, this may not work in all browsers */
select option {
    padding: 8px;
}
select option[value="pending"] {
    background-color: rgb(59, 130, 246); /* blue-500 */
    color: white;
}
select option[value="processing"] {
    background-color: #f0512e; /* primary */
    color: white;
}
select option[value="shipped"] {
    background-color: rgb(234, 179, 8); /* yellow-500 */
    color: black;
}
select option[value="delivered"] {
    background-color: rgb(34, 197, 94); /* green-500 */
    color: white;
}
select option[value="cancelled"] {
    background-color: rgb(239, 68, 68); /* red-500 */
    color: white;
}
select option[value="returned"] {
    background-color: rgb(168, 85, 247); /* purple-500 */
    color: white;
}
select option[value="on_hold"] {
    background-color: rgb(20, 184, 166); /* teal-500 */
    color: white;
}
select option[value="confirmed"] {
    background-color: rgb(99, 102, 241); /* indigo-500 */
    color: white;
}
</style>
