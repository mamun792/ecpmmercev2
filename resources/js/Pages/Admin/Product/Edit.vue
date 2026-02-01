<script setup>
import { Head } from "@inertiajs/vue3";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import ProductForm from "@/Components/Product/ProductForm.vue";

defineProps({
    product: Object,
    attributeValues: Array,
    categories: Array,
    brands: Array,
    locations: Array,
});
</script>

<template>
    <Head title="Edit Product" />
    <AdminLayout>
        <!-- <pre>{{ product }}</pre> -->
        <ProductForm
            :product="product"
            :attributes="attributeValues"
            :categories="categories"
            :brands="brands"
            :locations="locations"
        />
    </AdminLayout>
</template>
