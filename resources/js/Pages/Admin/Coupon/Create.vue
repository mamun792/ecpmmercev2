<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';

const form = useForm({
  code: '',
  name: '',
  discount_value: '',
  discount_type: '',
  max_uses: '',
  start_date: '',
  expiry_date: '',
  is_active: '1',
  festival_name: '',
  apply_to_all_products: false,
});

// Date picker states
const showDatePicker = ref(false);
const currentMonth = ref(new Date().getMonth());
const currentYear = ref(new Date().getFullYear());
const selectedStartDate = ref(null);
const selectedEndDate = ref(null);
const isSelectingEndDate = ref(false);

// Generate random coupon code
const generateCouponCode = () => {
  const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
  let result = '';
  for (let i = 0; i < 8; i++) {
    result += chars.charAt(Math.floor(Math.random() * chars.length));
  }
  form.code = result;
};

// Date utilities
const monthNames = [
  'January', 'February', 'March', 'April', 'May', 'June',
  'July', 'August', 'September', 'October', 'November', 'December'
];

const getDaysInMonth = (month, year) => {
  return new Date(year, month + 1, 0).getDate();
};

const getFirstDayOfMonth = (month, year) => {
  return new Date(year, month, 1).getDay();
};

const formatDate = (date) => {
  if (!date) return '';
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
};

const formatDateDisplay = (date) => {
  if (!date) return '';
  return date.toLocaleDateString('en-US', { 
    year: 'numeric', 
    month: 'short', 
    day: 'numeric' 
  });
};

// Calendar computed properties
const calendarDays = computed(() => {
  const daysInMonth = getDaysInMonth(currentMonth.value, currentYear.value);
  const firstDay = getFirstDayOfMonth(currentMonth.value, currentYear.value);
  const days = [];
  
  // Empty cells for days before the first day of the month
  for (let i = 0; i < firstDay; i++) {
    days.push(null);
  }
  
  // Days of the month
  for (let day = 1; day <= daysInMonth; day++) {
    days.push(new Date(currentYear.value, currentMonth.value, day));
  }
  
  return days;
});

const displayDateRange = computed(() => {
  if (selectedStartDate.value && selectedEndDate.value) {
    return `${formatDateDisplay(selectedStartDate.value)} - ${formatDateDisplay(selectedEndDate.value)}`;
  } else if (selectedStartDate.value) {
    return `${formatDateDisplay(selectedStartDate.value)} - Select end date`;
  }
  return 'Select date range';
});

// Date picker methods
const selectDate = (date) => {
  if (!selectedStartDate.value || isSelectingEndDate.value) {
    if (!selectedStartDate.value) {
      selectedStartDate.value = date;
      isSelectingEndDate.value = true;
      form.start_date = formatDate(date);
    } else if (date >= selectedStartDate.value) {
      selectedEndDate.value = date;
      isSelectingEndDate.value = false;
      form.expiry_date = formatDate(date);
      showDatePicker.value = false;
    } else {
      // If selected end date is before start date, make it the new start date
      selectedStartDate.value = date;
      selectedEndDate.value = null;
      form.start_date = formatDate(date);
      form.expiry_date = '';
    }
  } else {
    // Reset selection
    selectedStartDate.value = date;
    selectedEndDate.value = null;
    isSelectingEndDate.value = true;
    form.start_date = formatDate(date);
    form.expiry_date = '';
  }
};

const isDateInRange = (date) => {
  if (!selectedStartDate.value || !selectedEndDate.value) return false;
  return date >= selectedStartDate.value && date <= selectedEndDate.value;
};

const isDateSelected = (date) => {
  return (selectedStartDate.value && date.getTime() === selectedStartDate.value.getTime()) ||
         (selectedEndDate.value && date.getTime() === selectedEndDate.value.getTime());
};

const previousMonth = () => {
  if (currentMonth.value === 0) {
    currentMonth.value = 11;
    currentYear.value--;
  } else {
    currentMonth.value--;
  }
};

const nextMonth = () => {
  if (currentMonth.value === 11) {
    currentMonth.value = 0;
    currentYear.value++;
  } else {
    currentMonth.value++;
  }
};

const submit = () => {
  form.post(route('admin.coupons.store'), {
    onSuccess: () => {
      form.reset();
      selectedStartDate.value = null;
      selectedEndDate.value = null;
      isSelectingEndDate.value = false;
    },
    onError: (errors) => {
      console.error(errors);
    },
  });
};

// Discount type options with descriptions
const discountTypes = [
  { value: 'percentage', label: 'Percentage (%)', description: 'e.g., 10% off' },
  { value: 'fixed', label: 'Fixed Amount', description: 'e.g., $50 off' }
];

// Close date picker when clicking outside
onMounted(() => {
  document.addEventListener('click', (e) => {
    if (!e.target.closest('.date-picker-container')) {
      showDatePicker.value = false;
    }
  });
});
</script>

<template>
  <Head title="Create Coupon" />
  <AdminLayout>
    <div class="min-h-screen dark:from-gray-900 dark:to-gray-800 py-8">
      <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header Section -->
        <div class="text-center mb-8">
          <div class="inline-flex items-center justify-center w-12 h-12 bg-blue-100 dark:bg-blue-900 rounded-xl mb-4">
            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
            </svg>
          </div>
          <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">Create New Coupon</h1>
          <p class="text-gray-600 dark:text-gray-400">Set up discount codes to boost your sales</p>
        </div>

        <!-- Main Form Card -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 pb-20">
          <form @submit.prevent="submit" class="p-8 space-y-8">
            
            <!-- Basic Information Section -->
            <div class="space-y-6">
              <div class="flex items-center space-x-3 mb-6">
                <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center">
                  <span class="text-blue-600 dark:text-blue-400 font-semibold text-sm">1</span>
                </div>
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Basic Information</h2>
              </div>

              <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Coupon Code -->
                <div class="space-y-2">
                  <label for="code" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Coupon Code <span class="text-red-500">*</span>
                  </label>
                  <div class="relative">
                    <input
                      v-model="form.code"
                      type="text"
                      id="code"
                      placeholder="Enter coupon code"
                      class="block w-full pl-4 pr-12 py-3 border border-gray-300 dark:border-gray-600 rounded-xl shadow-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                      :class="{ 'border-red-500 ring-red-500': form.errors.code }"
                      required
                    />
                    <button
                      type="button"
                      @click="generateCouponCode"
                      class="absolute right-2 top-1/2 transform -translate-y-1/2 p-2 text-gray-400 hover:text-blue-600 transition-colors"
                      title="Generate random code"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                      </svg>
                    </button>
                  </div>
                  <p v-if="form.errors.code" class="text-sm text-red-600">{{ form.errors.code }}</p>
                </div>

                <!-- Coupon Name -->
                <div class="space-y-2">
                  <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Coupon Name <span class="text-red-500">*</span>
                  </label>
                  <input
                    v-model="form.name"
                    type="text"
                    id="name"
                    placeholder="Enter coupon name"
                    class="block w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl shadow-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                    :class="{ 'border-red-500 ring-red-500': form.errors.name }"
                    required
                  />
                  <p v-if="form.errors.name" class="text-sm text-red-600">{{ form.errors.name }}</p>
                </div>

                <!-- Festival Name -->
                <div class="space-y-2 lg:col-span-2">
                  <label for="festival_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Festival/Event Name
                  </label>
                  <input
                    v-model="form.festival_name"
                    type="text"
                    id="festival_name"
                    placeholder="e.g., Black Friday, Christmas Sale"
                    class="block w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl shadow-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                    :class="{ 'border-red-500 ring-red-500': form.errors.festival_name }"
                  />
                  <p v-if="form.errors.festival_name" class="text-sm text-red-600">{{ form.errors.festival_name }}</p>
                </div>
              </div>
            </div>

            <!-- Discount Settings Section -->
            <div class="space-y-6 border-t border-gray-200 dark:border-gray-700 pt-8">
              <div class="flex items-center space-x-3 mb-6">
                <div class="w-8 h-8 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center">
                  <span class="text-green-600 dark:text-green-400 font-semibold text-sm">2</span>
                </div>
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Discount Settings</h2>
              </div>

              <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Discount Type -->
                <div class="space-y-2">
                  <label for="discount_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Discount Type <span class="text-red-500">*</span>
                  </label>
                  <select
                    v-model="form.discount_type"
                    id="discount_type"
                    class="block w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl shadow-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                    :class="{ 'border-red-500 ring-red-500': form.errors.discount_type }"
                    required
                  >
                    <option value="" disabled>Select discount type</option>
                    <option v-for="type in discountTypes" :key="type.value" :value="type.value">
                      {{ type.label }}
                    </option>
                  </select>
                  <p v-if="form.errors.discount_type" class="text-sm text-red-600">{{ form.errors.discount_type }}</p>
                </div>

                <!-- Discount Value -->
                <div class="space-y-2">
                  <label for="discount_value" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Discount Value <span class="text-red-500">*</span>
                  </label>
                  <div class="relative">
                    <input
                      v-model="form.discount_value"
                      type="number"
                      step="0.01"
                      min="0"
                      id="discount_value"
                      :placeholder="form.discount_type === 'percentage' ? 'e.g., 10' : 'e.g., 50'"
                      class="block w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl shadow-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                      :class="{ 'border-red-500 ring-red-500': form.errors.discount_value }"
                      required
                    />
                    <div class="absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-500 dark:text-gray-400">
                      {{ form.discount_type === 'percentage' ? '%' : '$' }}
                    </div>
                  </div>
                  <p v-if="form.errors.discount_value" class="text-sm text-red-600">{{ form.errors.discount_value }}</p>
                </div>

                <!-- Max Uses -->
                <div class="space-y-2">
                  <label for="max_uses" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Maximum Uses
                  </label>
                  <input
                    v-model="form.max_uses"
                    type="number"
                    min="1"
                    id="max_uses"
                    placeholder="Leave empty for unlimited"
                    class="block w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl shadow-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                    :class="{ 'border-red-500 ring-red-500': form.errors.max_uses }"
                  />
                  <p v-if="form.errors.max_uses" class="text-sm text-red-600">{{ form.errors.max_uses }}</p>
                  <p class="text-xs text-gray-500 dark:text-gray-400">How many times this coupon can be used</p>
                </div>

                <!-- Status -->
                <div class="space-y-2">
                  <label for="is_active" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Status
                  </label>
                  <select
                    v-model="form.is_active"
                    id="is_active"
                    class="block w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl shadow-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                    :class="{ 'border-red-500 ring-red-500': form.errors.is_active }"
                    required
                  >
                    <option value="1">🟢 Active</option>
                    <option value="0">🔴 Inactive</option>
                  </select>
                  <p v-if="form.errors.is_active" class="text-sm text-red-600">{{ form.errors.is_active }}</p>
                </div>
              </div>
            </div>

            <!-- Validity Period Section -->
            <div class="space-y-6 border-t border-gray-200 dark:border-gray-700 pt-8">
              <div class="flex items-center space-x-3 mb-6">
                <div class="w-8 h-8 bg-purple-100 dark:bg-purple-900 rounded-lg flex items-center justify-center">
                  <span class="text-purple-600 dark:text-purple-400 font-semibold text-sm">3</span>
                </div>
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Validity Period</h2>
              </div>

              <!-- Date Range Picker -->
              <div class="space-y-2 date-picker-container relative">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                  Valid Period <span class="text-red-500">*</span>
                </label>
                
                <!-- Date Range Display Input -->
                <div 
                  @click="showDatePicker = !showDatePicker"
                  class="block w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl shadow-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 cursor-pointer hover:border-blue-400 focus-within:ring-2 focus-within:ring-blue-500 focus-within:border-transparent transition-all duration-200"
                  :class="{ 'border-red-500 ring-red-500': form.errors.start_date || form.errors.expiry_date }"
                >
                  <div class="flex items-center justify-between">
                    <span :class="{ 'text-gray-400': !selectedStartDate }">
                      {{ displayDateRange }}
                    </span>
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                  </div>
                </div>

                <!-- Custom Date Range Picker -->
                <div 
                  v-if="showDatePicker"
                  class="absolute top-full left-0 right-0 mt-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-xl shadow-2xl z-50 p-4"
                >
                  <!-- Calendar Header -->
                  <div class="flex items-center justify-between mb-4">
                    <button
                      type="button"
                      @click="previousMonth"
                      class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
                    >
                      <svg class="w-4 h-4 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                      </svg>
                    </button>
                    
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                      {{ monthNames[currentMonth] }} {{ currentYear }}
                    </h3>
                    
                    <button
                      type="button"
                      @click="nextMonth"
                      class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
                    >
                      <svg class="w-4 h-4 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                      </svg>
                    </button>
                  </div>

                  <!-- Day Headers -->
                  <div class="grid grid-cols-7 mb-2">
                    <div v-for="day in ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']" 
                         :key="day"
                         class="p-2 text-center text-xs font-medium text-gray-500 dark:text-gray-400">
                      {{ day }}
                    </div>
                  </div>

                  <!-- Calendar Days -->
                  <div class="grid grid-cols-6 gap-1">
                    <button
                      v-for="(date, index) in calendarDays"
                      :key="index"
                      type="button"
                      @click="date && selectDate(date)"
                      :disabled="!date"
                      class="h-10 w-10 rounded-lg text-sm font-medium transition-all duration-200 relative"
                      :class="{
                        'text-gray-400 cursor-not-allowed': !date,
                        'hover:bg-blue-100 dark:hover:bg-blue-900 text-gray-700 dark:text-gray-300': date && !isDateSelected(date) && !isDateInRange(date),
                        'bg-blue-600 text-white': date && isDateSelected(date),
                        'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300': date && isDateInRange(date) && !isDateSelected(date),
                        'opacity-50': date && date < new Date().setHours(0,0,0,0)
                      }"
                    >
                      <span v-if="date">{{ date.getDate() }}</span>
                    </button>
                  </div>

                  <!-- Selection Status -->
                  <div class="mt-4 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <div class="text-sm text-gray-600 dark:text-gray-400">
                      <div v-if="!selectedStartDate" class="text-blue-600 dark:text-blue-400">
                        📅 Select start date
                      </div>
                      <div v-else-if="!selectedEndDate" class="text-primary">
                        📅 Select end date
                      </div>
                      <div v-else class="text-green-600 dark:text-green-400">
                        ✅ {{ formatDateDisplay(selectedStartDate) }} to {{ formatDateDisplay(selectedEndDate) }}
                      </div>
                    </div>
                  </div>
                </div>

                <p class="text-xs text-gray-500 dark:text-gray-400">
                  Click to select start date, then click again to select end date
                </p>
                <p v-if="form.errors.start_date" class="text-sm text-red-600">{{ form.errors.start_date }}</p>
                <p v-if="form.errors.expiry_date" class="text-sm text-red-600">{{ form.errors.expiry_date }}</p>
              </div>
            </div>

            <!-- Product Application Section -->
            <div class="space-y-6 border-t border-gray-200 dark:border-gray-700 pt-8">
              <div class="flex items-center space-x-3 mb-6">
                <div class="w-8 h-8 bg-primary/10 dark:bg-primary/90 rounded-lg flex items-center justify-center">
                  <span class="text-primary font-semibold text-sm">4</span>
                </div>
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Product Application</h2>
              </div>

              <!-- Apply to All Products -->
              <div class="bg-gray-50 dark:bg-gray-700 rounded-xl p-6">
                <div class="flex items-start space-x-3">
                  <div class="flex items-center h-5">
                    <input
                      v-model="form.apply_to_all_products"
                      type="checkbox"
                      id="apply_to_all_products"
                      class="w-5 h-5 text-blue-600 border-gray-300 dark:border-gray-600 rounded focus:ring-blue-500 focus:ring-2"
                    />
                  </div>
                  <div class="flex-1">
                    <label for="apply_to_all_products" class="text-sm font-medium text-gray-900 dark:text-gray-100 cursor-pointer">
                      Apply to all products
                    </label>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                      When enabled, this coupon will be applicable to all products in your store
                    </p>
                  </div>
                </div>
                <p v-if="form.errors.apply_to_all_products" class="mt-2 text-sm text-red-600">{{ form.errors.apply_to_all_products }}</p>
              </div>
            </div>

            <!-- Action Buttons -->
            <div class="border-t border-gray-200 dark:border-gray-700 pt-8">
              <div class="flex flex-col sm:flex-row gap-4 sm:justify-end">
                <button
                  type="button"
                  class="px-6 py-3 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 font-medium transition-all duration-200 focus:ring-2 focus:ring-gray-500 focus:ring-offset-2"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  :disabled="form.processing"
                  class="px-8 py-3 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                >
                  <span v-if="form.processing" class="flex items-center">
                    <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Creating...
                  </span>
                  <span v-else class="flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Create Coupon
                  </span>
                </button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>