<template>
    <Head title="Create Order" />
    <AdminLayout>
        <div class="max-w-7xl mx-auto">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-bold text-gray-900">Create New Order</h2>
                <Link :href="route('admin.orders.index')" class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700">
                    Back to Orders
                </Link>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <form @submit.prevent="submitOrder">
                    <!-- Customer Information -->
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold mb-4 text-gray-800 border-b pb-2">Customer Information</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Customer Name *</label>
                                <input
                                    v-model="form.customer_name"
                                    type="text"
                                    required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="Enter customer name"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Customer Phone *</label>
                                <input
                                    v-model="form.customer_phone"
                                    type="tel"
                                    required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="01XXXXXXXXX"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Customer Email</label>
                                <input
                                    v-model="form.customer_email"
                                    type="email"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="customer@example.com"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Shipping Address -->
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold mb-4 text-gray-800 border-b pb-2">Shipping Address</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Full Address *</label>
                                <textarea
                                    v-model="form.shipping_address"
                                    required
                                    rows="2"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="House/Flat No, Road, Area"
                                ></textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">City *</label>
                                <input
                                    v-model="form.shipping_city"
                                    type="text"
                                    required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="Dhaka"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Postal Code</label>
                                <input
                                    v-model="form.shipping_postal_code"
                                    type="text"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="1212"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Order Items -->
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold mb-4 text-gray-800 border-b pb-2">Order Items</h3>

                        <div v-for="(item, index) in form.items" :key="index" class="mb-4 p-4 border border-gray-200 rounded-md">
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Product *</label>
                                    <select
                                        v-model="item.product_id"
                                        required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    >
                                        <option value="">Select Product</option>
                                        <option v-for="product in products" :key="product.id" :value="product.id">
                                            {{ product.name }} - ৳{{ product.price }}
                                        </option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Quantity *</label>
                                    <input
                                        v-model.number="item.quantity"
                                        type="number"
                                        min="1"
                                        required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    />
                                </div>
                                <div class="flex items-end">
                                    <button
                                        type="button"
                                        @click="removeItem(index)"
                                        v-if="form.items.length > 1"
                                        class="w-full px-3 py-2 bg-red-600 text-white rounded-md hover:bg-red-700"
                                    >
                                        Remove
                                    </button>
                                </div>
                            </div>
                        </div>

                        <button
                            type="button"
                            @click="addItem"
                            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
                        >
                            + Add Item
                        </button>
                    </div>

                    <!-- Order Details -->
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold mb-4 text-gray-800 border-b pb-2">Order Details</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Payment Method *</label>
                                <select
                                    v-model="form.payment_method"
                                    required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                >
                                    <option value="">Select Method</option>
                                    <option v-for="(label, value) in paymentMethods" :key="value" :value="value">
                                        {{ label }}
                                    </option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Order Status *</label>
                                <select
                                    v-model="form.status"
                                    required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                >
                                    <option v-for="(label, value) in orderStatuses" :key="value" :value="value">
                                        {{ label }}
                                    </option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Shipping Cost</label>
                                <input
                                    v-model.number="form.shipping_cost"
                                    type="number"
                                    min="0"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="0"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Order Notes</label>
                        <textarea
                            v-model="form.customer_notes"
                            rows="3"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            placeholder="Any special instructions or notes..."
                        ></textarea>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex justify-end gap-3">
                        <Link
                            :href="route('admin.orders.index')"
                            class="px-6 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50"
                        >
                            Cancel
                        </Link>
                        <button
                            type="submit"
                            :disabled="processing"
                            class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            {{ processing ? 'Creating...' : 'Create Order' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { toast } from '@steveyuowo/vue-hot-toast';

const props = defineProps({
    products: Array,
    paymentMethods: Object,
    orderStatuses: Object,
});

const processing = ref(false);

const form = ref({
    customer_name: '',
    customer_phone: '',
    customer_email: '',
    shipping_address: '',
    shipping_city: '',
    shipping_postal_code: '',
    payment_method: 'cod',
    status: 'pending',
    shipping_cost: 0,
    customer_notes: '',
    items: [
        { product_id: '', quantity: 1 }
    ]
});

function addItem() {
    form.value.items.push({ product_id: '', quantity: 1 });
}

function removeItem(index) {
    form.value.items.splice(index, 1);
}

function submitOrder() {
    processing.value = true;

    router.post(route('admin.orders.store'), form.value, {
        onSuccess: () => {
            toast.success('Order created successfully!');
        },
        onError: (errors) => {
            console.error('Order creation errors:', errors);
            toast.error('Failed to create order. Please check the form.');
        },
        onFinish: () => {
            processing.value = false;
        }
    });
}
</script>
