<template>
    <Head title="General Settings | Basic Information" />
    <AdminLayout>
        <div class="container mx-auto p-4">
            <h1 class="text-2xl font-bold mb-4">General Settings</h1>

            <form @submit.prevent="submitForm">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="mb-4">
                        <label class="block text-gray-700">App Name</label>
                        <input
                            v-model="form.app_name"
                            type="text"
                            class="w-full p-2 border rounded"
                            :class="{ 'border-red-500': form.errors.app_name }"
                        />
                        <span
                            v-if="form.errors.app_name"
                            class="text-red-500 text-sm"
                        >
                            {{ form.errors.app_name }}
                        </span>
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700">Primary Color</label>
                        <div class="flex items-center gap-2">
                            <input
                                v-model="form.primary_color"
                                type="color"
                                class="h-10 w-14 p-1 border rounded cursor-pointer"
                                :class="{
                                    'border-red-500': form.errors.primary_color,
                                }"
                            />
                            <input
                                v-model="form.primary_color"
                                type="text"
                                class="w-full p-2 border rounded"
                                :class="{
                                    'border-red-500': form.errors.primary_color,
                                }"
                                placeholder="#000000"
                            />
                        </div>

                        <span
                            v-if="form.errors.primary_color"
                            class="text-red-500 text-sm"
                        >
                            {{ form.errors.primary_color }}
                        </span>
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700"
                            >Home Page Title</label
                        >
                        <input
                            v-model="form.home_page_title"
                            type="text"
                            class="w-full p-2 border rounded"
                            :class="{
                                'border-red-500': form.errors.home_page_title,
                            }"
                        />
                        <span
                            v-if="form.errors.home_page_title"
                            class="text-red-500 text-sm"
                        >
                            {{ form.errors.home_page_title }}
                        </span>
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700"
                            >Product Page Title</label
                        >
                        <input
                            v-model="form.product_page_title"
                            type="text"
                            class="w-full p-2 border rounded"
                            :class="{
                                'border-red-500':
                                    form.errors.product_page_title,
                            }"
                        />
                        <span
                            v-if="form.errors.product_page_title"
                            class="text-red-500 text-sm"
                        >
                            {{ form.errors.product_page_title }}
                        </span>
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700">Phone Number</label>
                        <input
                            v-model="form.phone_number"
                            type="text"
                            class="w-full p-2 border rounded"
                            :class="{
                                'border-red-500': form.errors.phone_number,
                            }"
                        />
                        <span
                            v-if="form.errors.phone_number"
                            class="text-red-500 text-sm"
                        >
                            {{ form.errors.phone_number }}
                        </span>
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700"
                            >WhatsApp Number</label
                        >
                        <input
                            v-model="form.whatsapp_number"
                            type="text"
                            class="w-full p-2 border rounded"
                            :class="{
                                'border-red-500': form.errors.whatsapp_number,
                            }"
                        />
                        <span
                            v-if="form.errors.whatsapp_number"
                            class="text-red-500 text-sm"
                        >
                            {{ form.errors.whatsapp_number }}
                        </span>
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700">Address</label>
                        <input
                            v-model="form.address"
                            type="text"
                            class="w-full p-2 border rounded"
                            :class="{ 'border-red-500': form.errors.address }"
                        />
                        <span
                            v-if="form.errors.address"
                            class="text-red-500 text-sm"
                        >
                            {{ form.errors.address }}
                        </span>
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700"
                            >Store Phone Number</label
                        >
                        <input
                            v-model="form.store_phone_number"
                            type="text"
                            class="w-full p-2 border rounded"
                            :class="{
                                'border-red-500':
                                    form.errors.store_phone_number,
                            }"
                        />
                        <span
                            v-if="form.errors.store_phone_number"
                            class="text-red-500 text-sm"
                        >
                            {{ form.errors.store_phone_number }}
                        </span>
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700">Store Email</label>
                        <input
                            v-model="form.store_email"
                            type="email"
                            class="w-full p-2 border rounded"
                            :class="{
                                'border-red-500': form.errors.store_email,
                            }"
                        />
                        <span
                            v-if="form.errors.store_email"
                            class="text-red-500 text-sm"
                        >
                            {{ form.errors.store_email }}
                        </span>
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700"
                            >Shipping Charge (Inside Dhaka)</label
                        >
                        <input
                            v-model="form.shipping_charge_inside_dhaka"
                            type="number"
                            step="0.01"
                            class="w-full p-2 border rounded"
                            :class="{
                                'border-red-500':
                                    form.errors.shipping_charge_inside_dhaka,
                            }"
                        />
                        <span
                            v-if="form.errors.shipping_charge_inside_dhaka"
                            class="text-red-500 text-sm"
                        >
                            {{ form.errors.shipping_charge_inside_dhaka }}
                        </span>
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700"
                            >Shipping Charge (Outside Dhaka)</label
                        >
                        <input
                            v-model="form.shipping_charge_outside_dhaka"
                            type="number"
                            step="0.01"
                            class="w-full p-2 border rounded"
                            :class="{
                                'border-red-500':
                                    form.errors.shipping_charge_outside_dhaka,
                            }"
                        />
                        <span
                            v-if="form.errors.shipping_charge_outside_dhaka"
                            class="text-red-500 text-sm"
                        >
                            {{ form.errors.shipping_charge_outside_dhaka }}
                        </span>
                    </div>

                    <div class="mb-4 md:col-span-2">
                        <label class="block text-gray-700">Top Notice</label>
                        <ckeditor
                            v-if="isLayoutReady"
                            :editor="editor"
                            :config="editorConfigWithProductId"
                            v-model="form.top_notice"
                            class="w-full p-2 border rounded"
                            :class="{
                                'border-red-500': form.errors.top_notice,
                            }"
                        />
                        <span
                            v-if="form.errors.top_notice"
                            class="text-red-500 text-sm"
                        >
                            {{ form.errors.top_notice }}
                        </span>
                    </div>

                    <div class="mb-4 md:col-span-2">
                        <label class="block text-gray-700"
                            >Facebook iFrame</label
                        >
                        <textarea
                            v-model="form.facebook_iframe"
                            class="w-full p-2 border rounded"
                            rows="4"
                            :class="{
                                'border-red-500': form.errors.facebook_iframe,
                            }"
                        ></textarea>
                        <span
                            v-if="form.errors.facebook_iframe"
                            class="text-red-500 text-sm"
                        >
                            {{ form.errors.facebook_iframe }}
                        </span>
                    </div>

                    <!-- <div class="mb-4 md:col-span-2">
            <label class="block text-gray-700">Footer Text</label>
            <textarea 
              v-model="form.footer_text" 
              class="w-full p-2 border rounded" 
              rows="4"
              :class="{ 'border-red-500': form.errors.footer_text }"
            ></textarea>
            <span v-if="form.errors.footer_text" class="text-red-500 text-sm">
              {{ form.errors.footer_text }}
            </span>
          </div> -->

                    <div class="mt-6 w-full md:col-span-2">
                        <label
                            for="short_description"
                            class="block text-sm font-medium text-gray-700 mb-2"
                            >Footer Text</label
                        >
                        <div ref="shortEditorElement">
                            <ckeditor
                                v-if="isLayoutReady"
                                id="short_description"
                                v-model="form.footer_text"
                                :editor="editor"
                                :config="editorConfigWithProductId"
                            />
                        </div>
                    </div>

                    <div class="mb-4 md:col-span-2">
                        <label class="block text-gray-700"
                            >Facebook page ID</label
                        >
                        <input
                            v-model="form.facebook_page_id"
                            class="w-full p-2 border rounded"
                            rows="4"
                            :class="{
                                'border-red-500': form.errors.facebook_page_id,
                            }"
                        />
                        <span
                            v-if="form.errors.facebook_page_id"
                            class="text-red-500 text-sm"
                        >
                            {{ form.errors.facebook_page_id }}
                        </span>
                    </div>

                    <div class="mb-4 md:col-span-2">
                        <label class="block text-gray-700"
                            >Attention Notice</label
                        >

                        <ckeditor
                            v-if="isLayoutReady"
                            :editor="editor"
                            :config="editorConfigWithProductId"
                            v-model="form.attention_notice"
                            class="w-full p-2 border rounded"
                            :class="{
                                'border-red-500': form.errors.attention_notice,
                            }"
                        />

                        <span
                            v-if="form.errors.attention_notice"
                            class="text-red-500 text-sm"
                        >
                            {{ form.errors.attention_notice }}
                        </span>
                    </div>
                </div>

                <button
                    type="submit"
                    class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600"
                    :disabled="form.processing"
                >
                    Save Settings
                </button>
            </form>
        </div>
    </AdminLayout>
</template>

<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { useForm, Head } from "@inertiajs/vue3";
import { toast } from "@steveyuowo/vue-hot-toast";
import { ref, computed, onMounted } from "vue";
import { Ckeditor } from "@ckeditor/ckeditor5-vue";
import "ckeditor5/ckeditor5.css";
import { ClassicEditor, editorConfig } from "@/Helpers/ckeditor";

const isLayoutReady = ref(false);
const editor = ClassicEditor;
const config = editorConfig;

const editorConfigWithProductId = computed(() => {
    return {
        ...editorConfig,
        simpleUpload: {
            ...editorConfig.simpleUpload,
            uploadUrl: `/admin/upload?product_id=${props.product?.id || ""}`,
        },
    };
});

onMounted(() => {
    isLayoutReady.value = true;
});

// Define props
const props = defineProps({
    settings: Object,
    errors: Object,
});

// Initialize form with settings data
const form = useForm({
    app_name: props.settings?.app_name || "",
    primary_color: props.settings?.primary_color || "#f0512e",
    home_page_title: props.settings?.home_page_title || "",
    product_page_title: props.settings?.product_page_title || "",
    phone_number: props.settings?.phone_number || "",
    whatsapp_number: props.settings?.whatsapp_number || "",
    address: props.settings?.address || "",
    store_phone_number: props.settings?.store_phone_number || "",
    store_email: props.settings?.store_email || "",
    shipping_charge_inside_dhaka:
        props.settings?.shipping_charge_inside_dhaka || "",
    shipping_charge_outside_dhaka:
        props.settings?.shipping_charge_outside_dhaka || "",
    top_notice: props.settings?.top_notice || "",
    facebook_iframe: props.settings?.facebook_iframe || "",
    facebook_page_id: props.settings?.facebook_page_id || "",
    attention_notice: props.settings?.attention_notice || "",
    footer_text: props.settings?.footer_text || "",
});

// Form submission function
const submitForm = () => {
    form.post(route("admin.settings.store"), {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            toast.success("Settings updated successfully");
        },
        onError: (errors) => {
            toast.error("Please fix the errors in the form");
        },
    });
};
</script>
