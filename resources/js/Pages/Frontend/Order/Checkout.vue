<script setup>
import { ref, computed, watch, onMounted, nextTick } from "vue";
import { useForm, usePage, Head, Link } from "@inertiajs/vue3";
import FrontendLayout from "@/Layouts/FrontendLayout.vue";
import { useCart } from "@/Composables/useCart";
import { useToast } from "@/Composables/useToast";
import axios from "axios";
import { trackCheckout, trackPurchase } from "@/Composables/gtm";

const toast = useToast();

const props = defineProps({
    cart: {
        type: Object,
        required: true,
    },
    user: {
        type: Object,
        default: null,
    },
});

const page = usePage();
const settings = computed(() => page.props.settings || {});
const cartSessionId = computed(() => page.props.cartSessionId || "");

// Use cart composable for cart operations
const { incrementQuantity, decrementQuantity, removeItem } = useCart();

// Track if incomplete order has already been sent
const incompleteOrderSent = ref(false);

// Helper to get cookie value
const getCookie = (name) => {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return parts.pop().split(";").shift();
    return "";
};

// Initialize form with Inertia useForm
const form = useForm({
    user_id: props.user?.id || null,
    session_id: "",
    customer_name: props.user?.name || "",
    customer_phone: props.user?.phone || "",
    shipping_address: props.user?.address || "",
    shipping_cost: 0,
    area: "inside_dhaka",
    customer_notes: "",
    payment_method: "cod",
    payment_status: "unpaid",
    coupon_code: props.cart?.data?.coupon_code || null,
    discount_type: props.cart?.data?.coupon_type || null,
    discount_amount: props.cart?.data?.coupon_value || 0,
    items: [],
});

// Set session_id and items on mount
onMounted(() => {
    form.session_id = getCookie("cart_session_id") || cartSessionId.value;

    // Map cart items to order items format
    if (props.cart?.data?.items) {
        form.items = props.cart.data.items.map((item) => ({
            product_id: item.product_id,
            product_variation_id: item.variation_id || null,
            quantity: item.quantity,
            coupon_code: item.coupon_code || null,
            discount_type: item.discount_type || null,
            discount_amount: item.discount_amount || 0,
        }));
    }

    // Set initial shipping cost
    updateShippingCost(form.area);
});

// Watch cart items for changes to keep form.items in sync
watch(
    () => props.cart?.data?.items,
    (newItems) => {
        if (newItems) {
            form.items = newItems.map((item) => ({
                product_id: item.product_id,
                product_variation_id: item.variation_id || null,
                quantity: item.quantity,
                coupon_code: item.coupon_code || null,
                discount_type: item.discount_type || null,
                discount_amount: item.discount_amount || 0,
            }));
        }
        // Update root coupon info
        form.coupon_code = props.cart?.data?.coupon_code || null;
        form.discount_type = props.cart?.data?.coupon_type || null;
        form.discount_amount = props.cart?.data?.coupon_value || 0;

        // Recompute shipping cost when cart items change (handles items with free delivery)
        updateShippingCost(form.area);
    },
    { deep: true }
);

// Calculate subtotal from cart
const subtotal = computed(() => {
    if (!props.cart?.data?.items) return 0;
    return props.cart.data.items.reduce((sum, item) => {
        return sum + parseFloat(item.price) * item.quantity;
    }, 0);
});

// Calculate total
const total = computed(() => {
    const discount = props.cart?.data?.discount || 0;
    return subtotal.value + form.shipping_cost - parseFloat(discount);
});

// Coupon form
const couponForm = useForm({
    code: props.cart?.data?.coupon_code || "",
});

// Mobile-friendly coupon UX helper
const showCoupon = ref(false);
const focusCoupon = () => {
    showCoupon.value = true;
    nextTick(() => {
        const el = document.getElementById("coupon_code");
        if (el) {
            el.focus();
            el.scrollIntoView({ behavior: "smooth", block: "center" });
        }
    });
};

// Keep coupon input in sync when cart changes
watch(
    () => props.cart?.data?.coupon_code,
    (newCode) => {
        couponForm.code = newCode || "";
    }
);

const applyCoupon = () => {
    if (!couponForm.code) {
        toast.warning("Please enter a coupon code");
        return;
    }

    couponForm.post(route("cart.apply-coupon"), {
        preserveScroll: true,
        onSuccess: (page) => {
            if (page.props.flash?.success) {
                toast.success(page.props.flash.success);
                // blur and hide coupon input on success
                document.getElementById("coupon_code")?.blur();
                showCoupon.value = false;
            }
            if (page.props.flash?.error) {
                toast.error(page.props.flash.error);
            }
        },
        onError: (errors) => {
            const firstError = Object.values(errors)[0];
            toast.error(firstError || "Failed to apply coupon");
        },
    });
};

// Update shipping cost based on area and item-level free-delivery flag
const updateShippingCost = (area) => {
    // If every item in the cart has is_free_delivery === true, shipping is waived
    const items = props.cart?.data?.items || [];
    const allFree = items.length > 0 && items.every((it) => !!it.is_free_delivery);

    if (allFree) {
        form.shipping_cost = 0;
        return;
    }

    if (area === "inside_dhaka") {
        form.shipping_cost = parseFloat(
            settings.value?.generalSettings?.shipping_charge_inside_dhaka || 0
        );
    } else {
        form.shipping_cost = parseFloat(
            settings.value?.generalSettings?.shipping_charge_outside_dhaka ||
                0
        );
    }
};

// Watch area changes for dynamic shipping
watch(
    () => form.area,
    (newArea) => {
        updateShippingCost(newArea);
    }
);

// Validate Bangladesh phone number (11 digits starting with 01)
const isValidBDPhone = (phone) => {
    return /^01[3-9]\d{8}$/.test(phone);
};

// UI state for phone validation (client-side only)
const phoneTouched = ref(false);
const isPhoneValid = computed(() => isValidBDPhone(form.customer_phone));

// Watch phone number for incomplete order creation
watch(
    () => form.customer_phone,
    (newPhone) => {
        if (isValidBDPhone(newPhone) && !incompleteOrderSent.value) {
            // Construct incomplete order payload
            const incompletePayload = {
                user_id: form.user_id,
                session_id: form.session_id,
                customer_name: form.customer_name || "Guest",
                customer_phone: newPhone,
                shipping_address: form.shipping_address || "incomplete address",
                shipping_cost: form.shipping_cost || 0,
                area: form.area || "",
                customer_notes: form.customer_notes || "",
                payment_method: form.payment_method || "cod",
                payment_status: "unpaid",
                status: "incomplete",
                items: form.items,
            };

            // Send background request
            axios
                .post(route("order.store"), incompletePayload)
                .then((res) => {
                    console.log("Incomplete order tracked successfully");
                    incompleteOrderSent.value = true;
                })
                .catch((err) => {
                    console.error("Tracking failed", err);
                });
        }
    }
);

// Form submission handler
const submitOrder = () => {
    // Client-side phone validation only (UI)
    if (!isPhoneValid.value) {
        phoneTouched.value = true;
        // Optionally scroll to the phone input
        document.getElementById("customer_phone")?.focus();
        return;
    }

    form.post(route("order.store"), {
        preserveScroll: true,
        onSuccess: (page) => {
            console.log("Order submission successful", page.props.order);
            if (page.props?.order) {
                // Track purchase via GTM
                trackPurchase(page.props.order);
            }
            if (page.props.flash?.error) {
                console.error("Order error:", page.props.flash.error);
            }
        },
        onError: (errors) => {
            console.error("Order submission failed:", errors);
        },
    });
};



onMounted(() => {
    trackCheckout(props.cart);
});



</script>

<template>
    <Head title="Checkout" />
    <FrontendLayout>
        <div class="min-h-screen bg-gray-50 py-8 overflow-x-hidden">
            <!-- <pre>{{ settings }}</pre> -->
             <!-- <pre>{{ cart }}</pre> -->
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Page Header -->
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-gray-900">Checkout</h1>
                    <p class="mt-2 text-sm text-gray-600">
                        Complete your order by filling in the details below.
                    </p>
                    <!-- Mobile quick coupon action -->
                    <div class="mt-4 lg:hidden">
                        <button
                            type="button"
                            @click="focusCoupon"
                            class="inline-flex items-center px-3 py-2 bg-primary text-white rounded-md text-sm"
                        >
                            Have a coupon? Apply
                        </button>
                    </div>
                </div>

                <!-- Empty Cart Message -->
                <div
                    v-if="!cart?.data?.items || cart.data.items.length === 0"
                    class="flex flex-col items-center justify-center py-16"
                >
                    <div
                        class="bg-white rounded-2xl shadow-sm border border-gray-100 p-12 text-center max-w-md"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-24 w-24 text-gray-300 mb-4 mx-auto"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"
                            />
                        </svg>
                        <h2 class="text-2xl font-bold text-gray-900 mb-2">
                            Your cart is empty
                        </h2>
                        <p class="text-gray-600 mb-6">
                            Add some products to your cart before checking out.
                        </p>
                        <Link
                            href="/"
                            class="inline-flex items-center px-6 py-3 bg-primary text-white font-medium rounded-lg hover:bg-primary/90 transition-colors"
                        >
                            <svg
                                class="w-5 h-5 mr-2"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M10 19l-7-7m0 0l7-7m-7 7h18"
                                />
                            </svg>
                            Continue Shopping
                        </Link>
                    </div>
                </div>

                <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Left Column: Customer Details Form -->
                    <div class="lg:col-span-2">
                        <form @submit.prevent="submitOrder" class="space-y-6">
                            <!-- Customer Information Card -->
                            <div
                                class="bg-white rounded-xl shadow-sm border border-gray-100 p-6"
                            >
                                <h2
                                    class="text-lg font-semibold text-gray-900 mb-4 flex items-center"
                                >
                                    <svg
                                        class="w-5 h-5 mr-2 text-primary"
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
                                    Customer Information
                                </h2>

                                <div
                                    class="grid grid-cols-1 md:grid-cols-2 gap-4"
                                >
                                    <!-- Customer Name -->
                                    <div>
                                        <label
                                            for="customer_name"
                                            class="block text-sm font-medium text-gray-700 mb-1"
                                        >
                                            Full Name
                                            <span class="text-red-500">*</span>
                                        </label>
                                        <input
                                            id="customer_name"
                                            v-model="form.customer_name"
                                            type="text"
                                            placeholder="Enter your full name"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-colors"
                                            :class="{
                                                'border-red-500':
                                                    form.errors.customer_name,
                                            }"
                                            required
                                        />
                                        <p
                                            v-if="form.errors.customer_name"
                                            class="mt-1 text-sm text-red-500"
                                        >
                                            {{ form.errors.customer_name }}
                                        </p>
                                    </div>

                                    <!-- Customer Phone -->
                                    <div>
                                        <label
                                            for="customer_phone"
                                            class="block text-sm font-medium text-gray-700 mb-1"
                                        >
                                            Phone Number
                                            <span class="text-red-500">*</span>
                                        </label>
                                        <input
                                            id="customer_phone"
                                            v-model="form.customer_phone"
                                            @blur="phoneTouched = true"
                                            type="tel"
                                            inputmode="numeric"
                                            pattern="[0-9]*"
                                            placeholder="01XXXXXXXXX"
                                            maxlength="11"
                                            class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-colors"
                                            :class="{
                                                'border-red-500':
                                                    (phoneTouched &&
                                                        !isPhoneValid) ||
                                                    form.errors.customer_phone,
                                                'border-green-500':
                                                    phoneTouched &&
                                                    isPhoneValid,
                                            }"
                                            required
                                        />
                                        <p
                                            v-if="form.errors.customer_phone"
                                            class="mt-1 text-sm text-red-500"
                                        >
                                            {{ form.errors.customer_phone }}
                                        </p>
                                        <p
                                            v-else-if="
                                                phoneTouched && !isPhoneValid
                                            "
                                            class="mt-1 text-sm text-red-500"
                                        >
                                            Please enter a valid Bangladeshi
                                            phone number (11 digits, starting
                                            with 01).
                                        </p>
                                        <p
                                            v-else
                                            class="mt-1 text-xs text-gray-500"
                                        >
                                            Bangladesh format: 01XXXXXXXXX
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Shipping Information Card -->
                            <div
                                class="bg-white rounded-xl shadow-sm border border-gray-100 p-6"
                            >
                                <h2
                                    class="text-lg font-semibold text-gray-900 mb-4 flex items-center"
                                >
                                    <svg
                                        class="w-5 h-5 mr-2 text-primary"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"
                                        />
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"
                                        />
                                    </svg>
                                    Shipping Address
                                </h2>

                                <!-- Delivery Area Selection -->
                                <div class="mb-4">
                                    <label
                                        class="block text-sm font-medium text-gray-700 mb-2"
                                        >Delivery Area</label
                                    >
                                    <div class="grid grid-cols-2 gap-4">
                                        <label
                                            class="relative flex items-center p-4 border rounded-lg cursor-pointer transition-all"
                                            :class="
                                                form.area === 'inside_dhaka'
                                                    ? 'border-primary bg-primary/10 ring-2 ring-primary'
                                                    : 'border-gray-300 hover:border-gray-400'
                                            "
                                        >
                                            <input
                                                type="radio"
                                                v-model="form.area"
                                                value="inside_dhaka"
                                                class="sr-only"
                                            />
                                            <div>
                                                <span
                                                    class="block font-medium text-gray-900"
                                                    >Inside Dhaka</span
                                                >
                                                <span
                                                    class="block text-sm text-gray-500"
                                                    >৳{{
                                                        settings
                                                            ?.generalSettings
                                                            ?.shipping_charge_inside_dhaka ||
                                                        60
                                                    }}</span
                                                >
                                            </div>
                                        </label>
                                        <label
                                            class="relative flex items-center p-4 border rounded-lg cursor-pointer transition-all"
                                            :class="
                                                form.area === 'outside_dhaka'
                                                    ? 'border-primary bg-primary/10 ring-2 ring-primary'
                                                    : 'border-gray-300 hover:border-gray-400'
                                            "
                                        >
                                            <input
                                                type="radio"
                                                v-model="form.area"
                                                value="outside_dhaka"
                                                class="sr-only"
                                            />
                                            <div>
                                                <span
                                                    class="block font-medium text-gray-900"
                                                    >Outside Dhaka</span
                                                >
                                                <span
                                                    class="block text-sm text-gray-500"
                                                    >৳{{
                                                        settings
                                                            ?.generalSettings
                                                            ?.shipping_charge_outside_dhaka ||
                                                        120
                                                    }}</span
                                                >
                                            </div>
                                        </label>
                                    </div>
                                </div>

                                <!-- Full Address -->
                                <div>
                                    <label
                                        for="shipping_address"
                                        class="block text-sm font-medium text-gray-700 mb-1"
                                    >
                                        Full Address
                                        <span class="text-red-500">*</span>
                                    </label>
                                    <textarea
                                        id="shipping_address"
                                        v-model="form.shipping_address"
                                        rows="3"
                                        placeholder="House no, Road, Area, District"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-colors resize-none"
                                        :class="{
                                            'border-red-500':
                                                form.errors.shipping_address,
                                        }"
                                        required
                                    ></textarea>
                                    <p
                                        v-if="form.errors.shipping_address"
                                        class="mt-1 text-sm text-red-500"
                                    >
                                        {{ form.errors.shipping_address }}
                                    </p>
                                </div>

                                <!-- Customer Notes -->
                                <div class="mt-4">
                                    <label
                                        for="customer_notes"
                                        class="block text-sm font-medium text-gray-700 mb-1"
                                    >
                                        Order Notes (Optional)
                                    </label>
                                    <textarea
                                        id="customer_notes"
                                        v-model="form.customer_notes"
                                        rows="2"
                                        placeholder="Any special instructions for delivery..."
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-colors resize-none"
                                    ></textarea>
                                </div>
                            </div>

                            <!-- Payment Method Card -->
                            <div
                                class="bg-white rounded-xl shadow-sm border border-gray-100 p-6"
                            >
                                <h2
                                    class="text-lg font-semibold text-gray-900 mb-4 flex items-center"
                                >
                                    <svg
                                        class="w-5 h-5 mr-2 text-primary"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"
                                        />
                                    </svg>
                                    Payment Method
                                </h2>

                                <div class="space-y-3">
                                    <label
                                        class="relative flex items-center p-4 border rounded-lg cursor-pointer transition-all"
                                        :class="
                                            form.payment_method === 'cod'
                                                ? 'border-primary bg-primary/10 ring-2 ring-primary'
                                                : 'border-gray-300 hover:border-gray-400'
                                        "
                                    >
                                        <input
                                            type="radio"
                                            v-model="form.payment_method"
                                            value="cod"
                                            class="sr-only"
                                        />
                                        <div class="flex items-center">
                                            <div
                                                class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center mr-4"
                                            >
                                                <svg
                                                    class="w-5 h-5 text-green-600"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"
                                                    />
                                                </svg>
                                            </div>
                                            <div>
                                                <span
                                                    class="block font-medium text-gray-900"
                                                    >Cash on Delivery</span
                                                >
                                                <span
                                                    class="block text-sm text-gray-500"
                                                    >Pay when you receive</span
                                                >
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <!-- Submit Button (Mobile) -->
                            <div class="lg:hidden">
                                <button
                                    type="submit"
                                    :disabled="form.processing || !isPhoneValid"
                                    class="w-full py-4 px-6 bg-primary hover:bg-primary/90 disabled:bg-primary/50 text-white font-semibold rounded-xl shadow-lg transition-colors flex items-center justify-center"
                                >
                                    <svg
                                        v-if="form.processing"
                                        class="animate-spin -ml-1 mr-3 h-5 w-5 text-white"
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
                                    {{
                                        form.processing
                                            ? "Processing..."
                                            : "Place Order"
                                    }}
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Right Column: Order Summary -->
                    <div class="lg:col-span-1">
                        <div
                            class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 sticky top-4 overflow-hidden w-full"
                        >
                            <h2
                                class="text-lg font-semibold text-gray-900 mb-4"
                            >
                                Order Summary
                            </h2>

                            <!-- Cart Items -->
                            <div
                                class="space-y-4 max-h-80 overflow-y-auto mb-4"
                            >
                                <div
                                    v-for="item in cart?.data?.items"
                                    :key="item.id"
                                    class="pb-4 border-b border-gray-100 last:border-0"
                                >
                                    <div class="flex items-start space-x-3">
                                        <!-- Product Image -->
                                        <div
                                            class="w-16 h-16 bg-gray-100 rounded-lg overflow-hidden flex-shrink-0"
                                        >
                                            <img
                                                v-if="item.product_image"
                                                :src="item.product_image"
                                                :alt="item.product_name"
                                                class="w-full h-full object-cover"
                                            />
                                            <div
                                                v-else
                                                class="w-full h-full flex items-center justify-center text-gray-400"
                                            >
                                                <svg
                                                    class="w-8 h-8"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                                                    />
                                                </svg>
                                            </div>
                                        </div>

                                        <!-- Product Details -->
                                        <div class="flex-1 min-w-0 w-full">
                                            <div
                                                class="flex items-start justify-between"
                                            >
                                                <div class="flex-1">
                                                    <h4
                                                        class="text-sm font-medium text-gray-900 break-words"
                                                    >
                                                        {{ item.product_name }}
                                                    </h4>
                                                    <!-- Variation Attributes -->
                                                    <p
                                                        v-if="
                                                            item
                                                                .variation_attributes
                                                                ?.length
                                                        "
                                                        class="text-xs text-gray-500 mt-1"
                                                    >
                                                        <span
                                                            v-for="(
                                                                attr, index
                                                            ) in item.variation_attributes"
                                                            :key="index"
                                                        >
                                                            {{ attr.name }}:
                                                            {{ attr.value }}
                                                            <span
                                                                v-if="
                                                                    index <
                                                                    item
                                                                        .variation_attributes
                                                                        .length -
                                                                        1
                                                                "
                                                                >,
                                                            </span>
                                                        </span>
                                                    </p>
                                                    <p
                                                        class="text-sm font-medium text-gray-900 mt-1"
                                                    >
                                                        ৳{{
                                                            parseFloat(
                                                                item.price || 0
                                                            ).toFixed(2)
                                                        }}
                                                    </p>
                                                </div>
                                                <!-- Delete Button -->
                                                <button
                                                    @click="removeItem(item.id)"
                                                    class="text-red-500 hover:text-red-700 p-1"
                                                    type="button"
                                                >
                                                    <svg
                                                        class="w-5 h-5"
                                                        fill="none"
                                                        stroke="currentColor"
                                                        viewBox="0 0 24 24"
                                                    >
                                                        <path
                                                            stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                                        />
                                                    </svg>
                                                </button>
                                            </div>

                                            <!-- Quantity Controls -->
                                            <div
                                                class="flex items-center gap-2 mt-2"
                                            >
                                                <button
                                                    @click="
                                                        decrementQuantity(
                                                            item.id,
                                                            item.quantity
                                                        )
                                                    "
                                                    type="button"
                                                    class="w-7 h-7 flex items-center justify-center border border-gray-300 rounded hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed"
                                                >
                                                    <svg
                                                        class="w-4 h-4"
                                                        fill="none"
                                                        stroke="currentColor"
                                                        viewBox="0 0 24 24"
                                                    >
                                                        <path
                                                            stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M20 12H4"
                                                        />
                                                    </svg>
                                                </button>
                                                <span
                                                    class="text-sm font-medium w-8 text-center"
                                                    >{{ item.quantity }}</span
                                                >
                                                <button
                                                    @click="
                                                        incrementQuantity(
                                                            item.id
                                                        )
                                                    "
                                                    type="button"
                                                    class="w-7 h-7 flex items-center justify-center border border-gray-300 rounded hover:bg-gray-100"
                                                >
                                                    <svg
                                                        class="w-4 h-4"
                                                        fill="none"
                                                        stroke="currentColor"
                                                        viewBox="0 0 24 24"
                                                    >
                                                        <path
                                                            stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M12 6v6m0 0v6m0-6h6m-6 0H6"
                                                        />
                                                    </svg>
                                                </button>
                                                <span
                                                    class="text-xs text-gray-500 ml-2"
                                                >
                                                    ৳{{
                                                        (
                                                            parseFloat(
                                                                item.price || 0
                                                            ) * item.quantity
                                                        ).toFixed(2)
                                                    }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Coupon Section -->
                            <div class="mt-4 pb-4 border-b border-gray-100">
                                <label
                                    for="coupon_code"
                                    class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-2"
                                    >Have a coupon?</label
                                >
                                <div class="flex flex-col sm:flex-row gap-2 items-stretch">
                                    <input
                                        id="coupon_code"
                                        v-model="couponForm.code"
                                        @keydown.enter.prevent="applyCoupon"
                                        type="text"
                                        placeholder="Enter code"
                                        class="min-w-0 w-full sm:flex-1 px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-1 focus:ring-primary focus:border-primary outline-none transition-all"
                                        :disabled="couponForm.processing"
                                    />
                                    <button
                                        type="button"
                                        @click="applyCoupon"
                                        :disabled="couponForm.processing"
                                        class="w-full sm:w-auto px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800 transition-colors disabled:opacity-50"
                                    >
                                        <span v-if="couponForm.processing"
                                            >...</span
                                        >
                                        <span v-else>Apply</span>
                                    </button>
                                </div>
                                <div
                                    v-if="cart?.data?.coupon_code"
                                    class="mt-2 flex items-center justify-between"
                                >
                                    <span
                                        class="text-xs text-green-600 flex items-center"
                                    >
                                        <svg
                                            class="w-3 h-3 mr-1"
                                            fill="currentColor"
                                            viewBox="0 0 20 20"
                                        >
                                            <path
                                                fill-rule="evenodd"
                                                d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                clip-rule="evenodd"
                                            ></path>
                                        </svg>
                                        Applied:
                                        <strong>{{
                                            cart.data.coupon_code
                                        }}</strong>
                                    </span>
                                </div>
                            </div>

                            <!-- Totals -->
                            <div
                                class="border-t border-gray-200 pt-4 space-y-3"
                            >
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600">Subtotal</span>
                                    <span class="font-medium text-gray-900"
                                        >৳{{ subtotal.toFixed(2) }}</span
                                    >
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600">Shipping</span>
                                    <span class="font-medium text-gray-900"
                                        >৳{{
                                            form.shipping_cost.toFixed(2)
                                        }}</span
                                    >
                                </div>
                                <div
                                    v-if="cart?.data?.discount > 0"
                                    class="flex justify-between text-sm text-green-600"
                                >
                                    <span
                                        >Discount ({{
                                            cart.data.coupon_code
                                        }})</span
                                    >
                                    <span class="font-medium"
                                        >-৳{{
                                            parseFloat(
                                                cart.data.discount
                                            ).toFixed(2)
                                        }}</span
                                    >
                                </div>
                                <div
                                    class="flex justify-between text-lg font-bold border-t border-gray-200 pt-3"
                                >
                                    <span class="text-gray-900">Total</span>
                                    <span class="text-primary"
                                        >৳{{ total.toFixed(2) }}</span
                                    >
                                </div>
                            </div>

                            <!-- Submit Button (Desktop) -->
                            <div class="hidden lg:block mt-6">
                                <button
                                    @click="submitOrder"
                                    :disabled="form.processing || !isPhoneValid"
                                    class="w-full py-4 px-6 bg-primary hover:bg-primary/90 disabled:bg-primary/50 text-white font-semibold rounded-xl shadow-lg transition-colors flex items-center justify-center"
                                >
                                    <svg
                                        v-if="form.processing"
                                        class="animate-spin -ml-1 mr-3 h-5 w-5 text-white"
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
                                    {{
                                        form.processing
                                            ? "Processing..."
                                            : "Place Order"
                                    }}
                                </button>
                            </div>

                            <!-- Security Note -->
                            <p
                                class="mt-4 text-xs text-gray-500 text-center flex items-center justify-center"
                            >
                                <svg
                                    class="w-4 h-4 mr-1"
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
                                Secure checkout
                            </p>
                        </div>
                    </div>
                </div>
                <!-- End v-else -->
            </div>
        </div>

        <!-- Floating coupon action on mobile -->
        <div class="fixed bottom-4 left-1/2 transform -translate-x-1/2 lg:hidden z-40">
            <button @click="focusCoupon" class="px-4 py-3 bg-primary text-white rounded-full shadow-lg">
                Apply Coupon
            </button>
        </div>
    </FrontendLayout>
</template>
