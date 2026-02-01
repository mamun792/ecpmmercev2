<template>
  <div
    v-if="visible"
    class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
  >
    <div class="bg-white rounded-lg shadow-lg w-[900px] p-6 relative">
      <!-- Close Button -->
      <button
        class="absolute top-4 right-4 text-gray-600 hover:text-red-500 text-xl font-bold"
        @click="$emit('close')"
      >
        ✕
      </button>

      <div class="flex gap-6">
        <!-- Left Side - Logo and Success Rate -->
        <div class="w-[350px] border border-gray-200 rounded-lg p-6 text-center">
          <!-- Logo -->
          <div class="mb-6">
            <img src="/assets/img/logo/logo.png" alt="logo" class="mx-auto h-10">
          </div>

          <!-- Title -->
          <h2 class="text-xl font-semibold mb-6 text-gray-800">Delivery Success Ratio</h2>

          <!-- Success Rate Circle -->
          <div class="flex justify-center mb-6">
            <div class="relative w-32 h-32 rounded-full border-8 border-green-500 flex items-center justify-center">
              <span class="text-2xl font-bold text-gray-800">{{ aggregated.success_rate.toFixed(1) }}%</span>
            </div>
          </div>

          <!-- Status -->
          <div class="text-xl font-bold text-gray-800 mb-2">Excellent</div>
          <div class="text-sm text-gray-600">এটি একটি নির্ভরযোগ্য ডেলিভারি।</div>

        </div>

        <!-- Right Side - Phone Number and Details -->
        <div class="flex-1">
          <!-- Phone Number Input Section -->
          <div class="mb-6 p-4">
            <div class="text-center text-sm text-gray-600">
              Number: <span class="font-semibold"><strong>{{ customerNumber }}</strong></span>
            </div>
          </div>

          <!-- Stats Summary -->
          <div class="grid grid-cols-3 gap-4 mb-6">
            <div class="text-center bg-yellow-500 text-white rounded-lg p-4">
              <div class="text-3xl font-bold">{{ aggregated.total_success }}</div>
              <div class="text-sm ">মোট অর্ডার</div>
            </div>
            <div class="text-center bg-green-500 text-white rounded-lg p-4">
              <div class="text-3xl font-bold ">{{ aggregated.total_success }}</div>
              <div class="text-sm ">মোট ডেলিভারি</div>
            </div>
            <div class="text-center bg-red-500 text-white rounded-lg p-4">
              <div class="text-3xl font-bold">{{ aggregated.total_cancel }}</div>
              <div class="text-sm ">মোট বাতিল</div>
            </div>
          </div>

          <!-- Courier Details Table -->
          <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
            <!-- Table Header -->
            <div class="grid grid-cols-5 gap-4 bg-gray-50 px-4 py-3 text-sm font-semibold text-gray-700 border-b border-gray-200">
              <div>কুরিয়ার</div>
              <div class="text-center">অর্ডার</div>
              <div class="text-center">ডেলিভারি</div>
              <div class="text-center">বাতিল</div>
              <div class="text-center">বাতিল হার</div>
            </div>

            <!-- Steadfast Row -->
            <div class="grid grid-cols-5 gap-4 px-4 py-3 text-sm border-b border-gray-200 items-center">
              <div class="flex items-center gap-2">
                <span class="font-medium">SteadFast</span>
              </div>
              <div class="text-center">{{ steadfastData.total }}</div>
              <div class="text-center">{{ steadfastData.success }}</div>
              <div class="text-center">{{ steadfastData.cancel }}</div>
              <div class="text-center">{{ steadfastCancelRate }}%</div>
            </div>

            <!-- Pathao Row -->
            <div class="grid grid-cols-5 gap-4 px-4 py-3 text-sm border-b border-gray-200 items-center">
              <div class="flex items-center gap-2">
                <span class="font-medium">pathao</span>
              </div>
              <div class="text-center">{{ pathaoData.total_delivery }}</div>
              <div class="text-center">{{ pathaoData.successful_delivery }}</div>
              <div class="text-center">{{ pathaoData.total_delivery - pathaoData.successful_delivery }}</div>
              <div class="text-center">{{ pathaoCancelRate }}%</div>
            </div>

            <!-- REDX Row -->
            <div class="grid grid-cols-5 gap-4 px-4 py-3 text-sm border-b border-gray-200 items-center">
              <div class="flex items-center gap-2">
                <span class="font-bold text-red-600">REDX</span>
              </div>
              <div class="text-center">0</div>
              <div class="text-center">0</div>
              <div class="text-center">0</div>
              <div class="text-center">0%</div>
            </div>


          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const { visible, fraudData } = defineProps({
  visible: Boolean,
  fraudData: Object,
});

const aggregated = computed(() => fraudData?.aggregated ?? {
  success_rate: 0,
  total_success: 0,
  total_cancel: 0,
  total_orders: 0,
});

const pathaoData = computed(() => fraudData?.pathao ?? {
  total_delivery: 0,
  successful_delivery: 0,
});

const steadfastData = computed(() => fraudData?.steadfast ?? {
  total: 0,
  success: 0,
  cancel: 0,
});

const customerNumber = computed(() => fraudData?.phone ?? 'N/A');

const pathaoCancelRate = computed(() => {
  const total = pathaoData.value.total_delivery;
  const successful = pathaoData.value.successful_delivery;
  const cancelled = total - successful;
  return total > 0 ? ((cancelled / total) * 100).toFixed(1) : '0.0';
});

const steadfastCancelRate = computed(() => {
  const total = steadfastData.value.total;
  const cancelled = steadfastData.value.cancel;
  return total > 0 ? ((cancelled / total) * 100).toFixed(1) : '0.0';
});
</script>