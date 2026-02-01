<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { defineProps } from 'vue';
import { Link, Head } from '@inertiajs/vue3'; // Import Link for navigation

const props = defineProps({
  order: Object,
  settings: Object,
  logo: String,
});

// Format date to a readable format
const formatDate = () => {
  const date = new Date();
  return `${date.getDate().toString().padStart(2, '0')}.${(date.getMonth() + 1).toString().padStart(2, '0')}.${date.getFullYear()}`;
};

// Calculate totals
const calculateSubtotal = () => {
  if (!props.order?.items) return 0;
  return props.order.items.reduce((total, item) => total + parseFloat(item.subtotal), 0);
};

const formatPrice = (price) => {
  return parseFloat(price).toFixed(2);
};

// Get variation attributes as string
const getAttributes = (item) => {
  if (!item.product_variation?.attributes) return '';

  return item.product_variation.attributes
    .filter(attr => attr.value?.attribute) // Only filter if truly null (not just soft-deleted)
    .map(attr => {
      return `${attr.value.attribute.name}: ${attr.value.value}`;
    }).join(', ');
};

// Function to handle printing
const printInvoice = () => {
  // Store the current document title
  const originalTitle = document.title;

  // Change document title for the print job
  document.title = `Invoice #${props.order?.order_number || 'N/A'}`;

  // Add print-specific CSS
  const style = document.createElement('style');
  style.type = 'text/css';
  style.id = 'print-style';
  style.innerHTML = `
    @media print {
      @page {
        size: A4;
        margin: 1cm;
      }
      body {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
      }
      .no-print {
        display: none !important;
      }
      .admin-layout {
        display: none !important;
      }
      .invoice-container {
        position: absolute !important;
        top: 0 !important;
        left: 0 !important;
        width: 100% !important;
        max-width: 100% !important;
        padding: 2cm !important;
        margin: 0 !important;
        box-shadow: none !important;
      }
      .invoice {
        width: 100% !important;
        max-width: 100% !important;
        padding: 0 !important;
        margin: 0 !important;
        box-shadow: none !important;
      }
    }
  `;
  document.head.appendChild(style);

  // Trigger print dialog
  window.print();

  // Cleanup: restore title and remove print style
  setTimeout(() => {
    document.title = originalTitle;
    const printStyle = document.getElementById('print-style');
    if (printStyle) printStyle.remove();
  }, 100);
};
</script>

<template>
  <Head title="Order Invoice" />
  <AdminLayout>
    <div class="invoice-container relative bg-white p-8 max-w-4xl mx-auto shadow-md">
      <div class="invoice">
        <!-- Action Buttons -->
        <div class="mb-6 flex justify-end space-x-3 no-print">
          <Link :href="route('admin.orders.index')" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-md flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Back
          </Link>
          <button @click="printInvoice" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
            </svg>
            Print
          </button>
        </div>
        <!-- Invoice Header -->
        <div class="flex justify-between items-center mb-8">
          <div class="logo">
            <!-- <h1 class="text-3xl font-bold">{{ settings?.app_name || 'Ecom' }}</h1> -->
            <img :src="logo?.logo" alt="Logo" class="w-32 h-auto mb-4" />
          </div>
          <div class="text-right">
            <h1 class="text-4xl font-bold mb-2">INVOICE</h1>
            <div class="text-sm">
              <div class="flex justify-end space-x-2">
                <span class="font-medium">Invoice No:</span>
                <span>{{ order?.order_number || 'N/A' }}</span>
              </div>
              <div class="flex justify-end space-x-2">
                <span class="font-medium">Date:</span>
                <span>{{ formatDate() }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Invoice Address Information -->
        <div class="flex justify-between mb-8">
          <div class="invoice-to">
            <h2 class="font-bold text-lg mb-2">Invoice To:</h2>
            <p class="text-sm">{{ order?.customer_name || 'N/A' }}</p>
            <p class="text-sm">{{ order?.shipping_address || 'N/A' }}</p>
            <p class="text-sm">{{ order?.area || 'N/A' }}</p>
            <p class="text-sm">{{ order?.customer_email || 'N/A' }}</p>
            <p class="text-sm">{{ order?.customer_phone || 'N/A' }}</p>
          </div>
          <div class="pay-to text-right">
            <h2 class="font-bold text-lg mb-2">Pay To:</h2>
            <p class="text-sm">{{ settings?.app_name || 'Ecom' }}</p>
            <p class="text-sm">{{ settings?.address || 'N/A' }}</p>
            <p class="text-sm">{{ settings?.store_email || 'N/A' }}</p>
            <p class="text-sm">{{ settings?.store_phone_number || 'N/A' }}</p>
          </div>
        </div>

        <!-- Invoice Items Table -->
        <div class="overflow-x-auto">
          <table class="min-w-full border-collapse">
            <thead>
              <tr class="bg-gray-100">
                <th class="py-2 px-4 text-left border-b border-gray-200">Item</th>
                <th class="py-2 px-4 text-left border-b border-gray-200">Attributes</th>
                <th class="py-2 px-4 text-left border-b border-gray-200">Type</th>
                <th class="py-2 px-4 text-right border-b border-gray-200">Price</th>
                <th class="py-2 px-4 text-center border-b border-gray-200">Qty</th>
                <th class="py-2 px-4 text-right border-b border-gray-200">Total</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(item, index) in order?.items" :key="index" class="border-b border-gray-100">
                <td class="py-3 px-4">{{ item.product.name }}</td>
                <td class="py-3 px-4">{{ getAttributes(item) }}</td>
                <td class="py-3 px-4"><span v-if="item.is_pre_order" class="badge badge-warning">Pre-Order</span><span v-else>Regular</span></td>
                <td class="py-3 px-4 text-right">{{ formatPrice(item.unit_price) }}</td>
                <td class="py-3 px-4 text-center">{{ item.quantity }}</td>
                <td class="py-3 px-4 text-right">{{ formatPrice(item.subtotal) }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Payment Summary -->
        <div class="mt-8 flex justify-end">
          <div class="w-1/2">
            <div class="flex justify-between py-2">
              <span class="font-medium">Subtotal:</span>
              <span>৳{{ formatPrice(order?.subtotal || 0) }}</span>
            </div>
            <div class="flex justify-between py-2">
              <span class="font-medium">Shipping:</span>
              <span>৳{{ formatPrice(order?.shipping_cost || 0) }}</span>
            </div>
            <div class="flex justify-between py-2">
              <span class="font-medium">Discount:</span>
              <span>-৳{{ formatPrice(order?.discount_total || 0) }}</span>
            </div>
            <div class="flex justify-between py-2 border-t border-gray-300 font-bold">
              <span>Grand Total:</span>
              <span>৳{{ formatPrice(order?.total || 0) }}</span>
            </div>
          </div>
        </div>

        <!-- Payment Info -->
        <div class="mt-8 border-t border-gray-200 pt-4">
          <h3 class="font-bold mb-2">Payment Information:</h3>
          <p>Payment Method: {{ order?.payment_method === 'cod' ? 'Cash on Delivery' : order?.payment_method }}</p>
          <p>Payment Status: <span class="capitalize">{{ order?.payment_status }}</span></p>
          <p v-if="order?.transaction_id">Transaction ID: {{ order.transaction_id }}</p>
        </div>

        <!-- Notes and Terms -->
        <div class="mt-8 text-sm text-gray-600">
          <p v-if="order?.customer_notes" class="mb-2">Notes: {{ order.customer_notes }}</p>
          <p class="mt-4 text-center">Thank you for your business!</p>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<style>
/* This ensures the A4 print size works correctly */
@media print {
  @page {
    size: A4;
    margin: 1cm;
  }

  body * {
    visibility: hidden;
  }

  .invoice-container, .invoice-container * {
    visibility: visible;
  }

  .no-print {
    display: none !important;
  }

  .invoice-container {
    position: absolute;
    left: 0;
    top: 0;
    width: 100%;
    padding: 1cm;
    margin: 0;
    box-shadow: none;
  }
}
</style>
