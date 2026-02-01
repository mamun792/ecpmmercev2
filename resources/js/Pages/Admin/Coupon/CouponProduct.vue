<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Head, useForm, Link, router } from "@inertiajs/vue3";
import { defineProps, ref, watch } from "vue";
import { debounce } from "lodash";
import { toast } from "@steveyuowo/vue-hot-toast";

const props = defineProps({
    coupon: Object,
    products: Object,
    filters: Object,
    assignedProductIds: Array,
});

// Debug props
console.log("Props:", {
    coupon: props.coupon,
    filters: props.filters,
    assignedProductIds: props.assignedProductIds,
});

const form = useForm({
    product_ids: props.assignedProductIds || [], // Initialize with assigned IDs
});

const search = ref(props.filters?.search || "");

// Debounced search
const updateSearch = debounce((value) => {
    router.get(
        route("admin.coupons.addedToProducts", props.coupon.data.id),
        { search: value },
        { preserveState: true, replace: true, preserveScroll: true }
    );
}, 300);

watch(search, (newValue) => {
    updateSearch(newValue);
});

const toggleProduct = (productId) => {
    if (form.product_ids.includes(productId)) {
        form.product_ids = form.product_ids.filter((id) => id !== productId);
    } else {
        form.product_ids.push(productId);
    }
};

const submit = () => {
    if (form.product_ids.length === 0) {
        toast.error("Please select at least one product to assign the coupon.");
        return;
    }
    form.post(route("admin.coupons.storeProductCoupon", props.coupon.data.id), {
        onSuccess: () => {
            //form.reset();
            toast.success("Products assigned successfully!");
            router.reload();
        },
        onError: (errors) => {
            console.error("Form Errors:", errors);
            toast.error("Failed to assign products. Please try again.");
        },
    });
};
</script>

<template>
    <Head title="Assign Coupon To Products" />
    <AdminLayout>
        <div
            class="p-4 max-w-7xl mx-auto bg-white dark:bg-gray-800 min-h-screen"
        >
            <div class="flex justify-between items-center mb-6">
                <h1
                    class="text-2xl font-bold mb-6 text-gray-900 dark:text-gray-100"
                >
                    Assign Coupon (ID: {{ coupon.id }}) to Products
                </h1>
                <Link :href="route('admin.coupons.index')" class="btn-primary"
                    >Back</Link
                >
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Search Bar -->
                <div class="flex justify-between items-center mb-4">
                    <div class="w-full md:w-1/3">
                        <label
                            for="search"
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300"
                            >Search Products</label
                        >
                        <input
                            v-model="search"
                            type="text"
                            id="search"
                            placeholder="Search by name, ID, price, or status..."
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-blue-500 focus:border-blue-500"
                        />
                    </div>
                </div>

                <!-- Product Table -->
                <div class="overflow-x-auto">
                    <table
                        class="min-w-full divide-y divide-gray-200 dark:divide-gray-700"
                    >
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider"
                                >
                                    Select
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider"
                                >
                                    ID
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider"
                                >
                                    Image
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider"
                                >
                                    Name
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider"
                                >
                                    Price
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider"
                                >
                                    Status
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider"
                                >
                                    Category
                                </th>
                            </tr>
                        </thead>
                        <tbody
                            class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700"
                        >
                            <tr
                                v-for="product in products.data"
                                :key="product.id"
                            >
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <input
                                        type="checkbox"
                                        :value="product.id"
                                        :checked="
                                            form.product_ids.includes(
                                                product.id
                                            )
                                        "
                                        @change="toggleProduct(product.id)"
                                        class="h-4 w-4 text-blue-600 border-gray-300 dark:border-gray-600 rounded"
                                    />
                                </td>
                                <td
                                    class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100"
                                >
                                    {{ product.id }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <img
                                        v-if="product.feature_image"
                                        :src="product.feature_image"
                                        alt="Product Image"
                                        class="h-12 w-12 object-cover rounded"
                                    />
                                    <span
                                        v-else
                                        class="text-sm text-gray-500 dark:text-gray-400"
                                        >No Image</span
                                    >
                                </td>
                                <td
                                    class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100"
                                >
                                    {{ product.name }}
                                </td>
                                <td
                                    class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100"
                                >
                                    ${{ product.price }}
                                </td>
                                <td
                                    class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100"
                                >
                                    {{ product.status }}
                                </td>
                                <td
                                    class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100"
                                >
                                    {{ product.category?.name || "N/A" }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="mt-4 flex justify-between items-center">
                    <div class="text-sm text-gray-700 dark:text-gray-300">
                        Showing {{ products.from }} to {{ products.to }} of
                        {{ products.total }} products
                    </div>
                    <div class="flex space-x-2">
                        <template
                            v-for="link in products.links"
                            :key="link.label"
                        >
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                class="px-3 py-1 text-sm rounded-md"
                                :class="{
                                    'bg-blue-600 text-white': link.active,
                                    'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300':
                                        !link.active,
                                }"
                                preserve-state
                                preserve-scroll
                                v-html="link.label"
                            />
                            <span
                                v-else
                                class="px-3 py-1 text-sm rounded-md text-gray-400 dark:text-gray-500 cursor-not-allowed"
                                v-html="link.label"
                            ></span>
                        </template>
                    </div>
                </div>

                <!-- Submit Button -->
                <div>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-white hover:bg-blue-700 disabled:opacity-50 dark:bg-blue-500 dark:hover:bg-blue-600"
                    >
                        {{
                            form.processing ? "Assigning..." : "Assign Products"
                        }}
                    </button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
