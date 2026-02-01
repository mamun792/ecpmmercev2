<script setup>
import { ref, watch, onMounted } from 'vue';
import { X, Send, Truck } from 'lucide-vue-next';
import { router, useForm } from '@inertiajs/vue3';
import { toast } from '@steveyuowo/vue-hot-toast';
import axios from 'axios';

const props = defineProps({
  show: Boolean,
  orderId: [Number, String],
  order: Object,
  cities: Array,
});

const emit = defineEmits(['close', 'submit']);

// Form selections
const selectedCourier = ref('');
const selectedCity = ref('');
const selectedZone = ref('');
const selectedArea = ref('');

// Data lists
const filteredCities = ref([]);
const zones = ref([]);
const areas = ref([]);

// Search terms
const citySearchTerm = ref('');
const zoneSearchTerm = ref('');
const areaSearchTerm = ref('');

// Dropdown visibility
const showCityDropdown = ref(false);
const showZoneDropdown = ref(false);
const showAreaDropdown = ref(false);

// Filtered results
const filteredZones = ref([]);
const filteredAreas = ref([]);

// Loading states
const loadingCities = ref(false);
const loadingZones = ref(false);
const loadingAreas = ref(false);
const loadingSteadfast = ref(false);

// Inertia form for Pathao submission
const pathaoForm = useForm({
  orderId: props.orderId,
  recipient_city: null,
  recipient_zone: null,
  recipient_area: null,
});

// Courier options
const couriers = [
  { value: 'pathao', label: 'Pathao' },
  { value: 'redex', label: 'Redex' },
  { value: 'steadfast', label: 'Steadfast' },
];

const resetForm = () => {
  selectedCourier.value = '';
  selectedCity.value = '';
  selectedZone.value = '';
  selectedArea.value = '';
  citySearchTerm.value = '';
  zoneSearchTerm.value = '';
  areaSearchTerm.value = '';
  showCityDropdown.value = false;
  showZoneDropdown.value = false;
  showAreaDropdown.value = false;
  zones.value = [];
  areas.value = [];
  pathaoForm.reset();
};

const close = () => {
  resetForm();
  emit('close');
};

const submit = async () => {
  if (!selectedCourier.value) {
    toast.error('Please select a courier');
    return;
  }

  // Handle Steadfast courier
  if (selectedCourier.value === 'steadfast') {
    await sendToSteadfast();
    return;
  }

  if (selectedCourier.value === 'pathao') {
    if (!selectedCity.value) {
      toast.error('Please select a city');
      return;
    }
    if (!selectedZone.value) {
      toast.error('Please select a zone');
      return;
    }
    if (!selectedArea.value) {
      toast.error('Please select an area');
      return;
    }
  }

  const form = useForm({
    orderId: props.orderId,
    courier_name: selectedCourier.value,
    city_name: null,
    zone_name: null,
    area_name: null,
    city_id: null,
    zone_id: null,
    area_id: null,
  });

  if (selectedCourier.value === 'pathao') {
    const city = filteredCities.value.find(c => c.city_id === selectedCity.value);
    const zone = zones.value.find(z => z.zone_id === selectedZone.value);
    const area = areas.value.find(a => a.area_id === selectedArea.value);

    form.city_name = city?.city_name || null;
    form.zone_name = zone?.zone_name || null;
    form.area_name = area?.area_name || null;
    form.city_id = city?.city_id || null;
    form.zone_id = zone?.zone_id || null;
    form.area_id = area?.area_id || null;
  }

  form.put(`/admin/orders/${props.orderId}/courier-details`, {
    onSuccess: () => {
      toast.success('Courier details updated successfully');
      emit('submit', form.data());
      close();
    },
    onError: (errors) => {
      toast.error(errors.message || 'Failed to update courier details');
    },
  });
};

const sendToSteadfast = async () => {
  loadingSteadfast.value = true;
  
  try {
    const response = await axios.post('/api/courier/orders', {
      oders_id: props.orderId.toString()
    });

    toast.success('Order sent to Steadfast successfully');
    
    emit('submit', {
      orderId: props.orderId,
      courier_name: 'steadfast',
      city_name: null,
      zone_name: null,
      area_name: null,
      city_id: null,
      zone_id: null,
      area_id: null,
    });
    
    router.reload();
    close();
  } catch (error) {
    console.error('Error sending to Steadfast:', error);
    const errorMessage = error.response?.data?.message || 'Failed to send order to Steadfast';
    toast.error(errorMessage);
  } finally {
    loadingSteadfast.value = false;
  }
};

const sendToPathao = () => {
  if (selectedCourier.value !== 'pathao') {
    toast.error('Please select Pathao as the courier');
    return;
  }

  if (!selectedCity.value || !selectedZone.value || !selectedArea.value) {
    toast.error('Please select city, zone, and area');
    return;
  }

  pathaoForm.orderId = props.orderId;
  pathaoForm.recipient_city = selectedCity.value;
  pathaoForm.recipient_zone = selectedZone.value;
  pathaoForm.recipient_area = selectedArea.value;

  pathaoForm.post(route('admin.couriers.pathao.shipment'), {
    onSuccess: (page) => {
      toast.success(page.props.flash?.message || 'Order sent to Pathao successfully');
      const city = filteredCities.value.find(c => c.city_id === selectedCity.value);
      const zone = zones.value.find(z => z.zone_id === selectedZone.value);
      const area = areas.value.find(a => a.area_id === selectedArea.value);

      emit('submit', {
        orderId: props.orderId,
        city_name: city?.city_name || null,
        zone_name: zone?.zone_name || null,
        area_name: area?.area_name || null,
        city_id: city?.city_id || null,
        zone_id: zone?.zone_id || null,
        area_id: area?.area_id || null,
      });
      router.reload();
      close();
    },
    onError: (errors) => {
      const errorMessage = Object.values(errors)[0] || 'Failed to send order to Pathao';
      toast.error(errorMessage);
    },
  });
};

// Fetch cities for Pathao
const loadCities = async () => {
  loadingCities.value = true;
  try {
    const response = await axios.get('/api/couriers/pathao/cities');
    filteredCities.value = response.data.data;
  } catch (error) {
    console.error('Error loading cities:', error);
    toast.error('Pathao credential is not valid. Please contact support.');
    filteredCities.value = [];
  } finally {
    loadingCities.value = false;
  }
};

// City filtering
const filterCities = () => {
  if (!citySearchTerm.value) {
    filteredCities.value = filteredCities.value || [];
  } else {
    const term = citySearchTerm.value.toLowerCase();
    filteredCities.value = filteredCities.value.filter(city =>
      city.city_name.toLowerCase().includes(term)
    );
  }
};

// Zone filtering
const filterZones = () => {
  if (!zoneSearchTerm.value) {
    filteredZones.value = zones.value;
  } else {
    const term = zoneSearchTerm.value.toLowerCase();
    filteredZones.value = zones.value.filter(zone =>
      zone.zone_name.toLowerCase().includes(term)
    );
  }
};

// Area filtering
const filterAreas = () => {
  if (!areaSearchTerm.value) {
    filteredAreas.value = areas.value;
  } else {
    const term = areaSearchTerm.value.toLowerCase();
    filteredAreas.value = areas.value.filter(area =>
      area.area_name.toLowerCase().includes(term)
    );
  }
};

// Selection handlers
const selectCity = (city) => {
  selectedCity.value = city.city_id;
  citySearchTerm.value = city.city_name;
  showCityDropdown.value = false;
  selectedZone.value = '';
  selectedArea.value = '';
  zoneSearchTerm.value = '';
  areaSearchTerm.value = '';
  zones.value = [];
  areas.value = [];
  if (selectedCourier.value === 'pathao') {
    loadZonesByCity(city.city_id);
  }
};

const selectZone = (zone) => {
  selectedZone.value = zone.zone_id;
  zoneSearchTerm.value = zone.zone_name;
  showZoneDropdown.value = false;
  selectedArea.value = '';
  areaSearchTerm.value = '';
  areas.value = [];
  if (selectedCourier.value === 'pathao') {
    loadAreasByZone(zone.zone_id);
  }
};

const selectArea = (area) => {
  selectedArea.value = area.area_id;
  areaSearchTerm.value = area.area_name;
  showAreaDropdown.value = false;
};

// API calls for zones and areas
const loadZonesByCity = async (cityId) => {
  loadingZones.value = true;
  try {
    const response = await axios.get(`/api/couriers/pathao/zones/${cityId}`);
    zones.value = response.data.data;
    filteredZones.value = response.data.data;
  } catch (error) {
    console.error('Error loading zones:', error);
    toast.error('Failed to load zones. Please try again.');
  } finally {
    loadingZones.value = false;
  }
};

const loadAreasByZone = async (zoneId) => {
  loadingAreas.value = true;
  try {
    const response = await axios.get(`/api/couriers/pathao/areas/${zoneId}`);
    areas.value = response.data.data;
    filteredAreas.value = response.data.data;
  } catch (error) {
    console.error('Error loading areas:', error);
    toast.error('Failed to load areas. Please try again.');
  } finally {
    loadingAreas.value = false;
  }
};

// Watch for courier change
watch(() => selectedCourier.value, (newCourier) => {
  selectedCity.value = '';
  selectedZone.value = '';
  selectedArea.value = '';
  citySearchTerm.value = '';
  zoneSearchTerm.value = '';
  areaSearchTerm.value = '';
  zones.value = [];
  areas.value = [];
  filteredCities.value = [];
  if (newCourier === 'pathao') {
    loadCities();
  }
});

// Watch for search term changes
watch(() => citySearchTerm.value, filterCities);
watch(() => zoneSearchTerm.value, filterZones);
watch(() => areaSearchTerm.value, filterAreas);

// Watch for orderId changes to update form
watch(() => props.orderId, (newOrderId) => {
  pathaoForm.orderId = newOrderId;
});

// Close dropdowns when clicking outside
const cityDropdownRef = ref(null);
const zoneDropdownRef = ref(null);
const areaDropdownRef = ref(null);

const handleClickOutside = (event) => {
  if (cityDropdownRef.value && !cityDropdownRef.value.contains(event.target)) {
    showCityDropdown.value = false;
  }
  if (zoneDropdownRef.value && !zoneDropdownRef.value.contains(event.target)) {
    showZoneDropdown.value = false;
  }
  if (areaDropdownRef.value && !areaDropdownRef.value.contains(event.target)) {
    showAreaDropdown.value = false;
  }
};

onMounted(() => {
  document.addEventListener('click', handleClickOutside);
});

const preventClose = (e) => {
  e.stopPropagation();
};
</script>

<template>
  <div v-if="show" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex justify-center items-center p-4"
    @click="close">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl" @click="preventClose">
      <div class="p-4 border-b flex justify-between items-center">
        <h2 class="text-lg font-bold">Select Courier Service</h2>
        <button @click="close" class="text-gray-500 hover:text-gray-700">
          <X :size="20" />
        </button>
      </div>

      <div class="p-4">
        <!-- Courier Selection -->
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-1">Courier Service</label>
          <select v-model="selectedCourier" class="block w-full rounded-md border-gray-300 shadow-sm"
            :disabled="pathaoForm.processing || loadingSteadfast">
            <option value="">Select a courier</option>
            <option v-for="courier in couriers" :key="courier.value" :value="courier.value">
              {{ courier.label }}
            </option>
          </select>
        </div>

        <!-- City Selection (for Pathao) -->
        <div class="mb-4" v-if="selectedCourier === 'pathao'">
          <label class="block text-sm font-medium text-gray-700 mb-1">City</label>
          <div class="relative" ref="cityDropdownRef">
            <input type="text" v-model="citySearchTerm" @focus="showCityDropdown = true"
              class="block w-full rounded-md border-gray-300 shadow-sm" placeholder="Search and select city"
              :disabled="loadingCities || pathaoForm.processing" />
            <div v-if="loadingCities" class="absolute right-3 top-2 text-blue-500">
              <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor"
                  d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                </path>
              </svg>
            </div>
            <div v-if="showCityDropdown"
              class="absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-y-auto">
              <div v-if="filteredCities.length === 0" class="p-2 text-sm text-gray-500">
                No cities found
              </div>
              <div v-for="city in filteredCities" :key="city.city_id" @click="selectCity(city)"
                class="p-2 hover:bg-gray-100 cursor-pointer text-sm"
                :class="{ 'bg-blue-50': selectedCity === city.city_id }">
                {{ city.city_name }}
              </div>
            </div>
          </div>
        </div>

        <!-- Zone Selection (for Pathao) -->
        <div class="mb-4" v-if="selectedCourier === 'pathao' && selectedCity">
          <label class="block text-sm font-medium text-gray-700 mb-1">Zone</label>
          <div class="relative" ref="zoneDropdownRef">
            <input type="text" v-model="zoneSearchTerm" @focus="showZoneDropdown = true"
              class="block w-full rounded-md border-gray-300 shadow-sm" placeholder="Search and select zone"
              :disabled="loadingZones || pathaoForm.processing" />
            <div v-if="loadingZones" class="absolute right-3 top-2 text-blue-500">
              <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor"
                  d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                </path>
              </svg>
            </div>
            <div v-if="showZoneDropdown && !loadingZones"
              class="absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-y-auto">
              <div v-if="filteredZones.length === 0" class="p-2 text-sm text-gray-500">
                No zones found
              </div>
              <div v-for="zone in filteredZones" :key="zone.zone_id" @click="selectZone(zone)"
                class="p-2 hover:bg-gray-100 cursor-pointer text-sm"
                :class="{ 'bg-blue-50': selectedZone === zone.zone_id }">
                {{ zone.zone_name }}
              </div>
            </div>
          </div>
        </div>

        <!-- Area Selection (for Pathao) -->
        <div class="mb-4" v-if="selectedCourier === 'pathao' && selectedZone">
          <label class="block text-sm font-medium text-gray-700 mb-1">Area</label>
          <div class="relative" ref="areaDropdownRef">
            <input type="text" v-model="areaSearchTerm" @focus="showAreaDropdown = true"
              class="block w-full rounded-md border-gray-300 shadow-sm" placeholder="Search and select area"
              :disabled="loadingAreas || pathaoForm.processing" />
            <div v-if="loadingAreas" class="absolute right-3 top-2 text-blue-500">
              <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor"
                  d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                </path>
              </svg>
            </div>
            <div v-if="showAreaDropdown && !loadingAreas"
              class="absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-y-auto">
              <div v-if="filteredAreas.length === 0" class="p-2 text-sm text-gray-500">
                No areas found
              </div>
              <div v-for="area in filteredAreas" :key="area.area_id" @click="selectArea(area)"
                class="p-2 hover:bg-gray-100 cursor-pointer text-sm"
                :class="{ 'bg-blue-50': selectedArea === area.area_id }">
                {{ area.area_name }}
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="p-4 border-t flex justify-end">
        <button @click="close" class="px-4 py-2 mr-2 bg-gray-200 hover:bg-gray-300 rounded-md text-sm font-medium"
          :disabled="pathaoForm.processing || loadingSteadfast">
          Cancel
        </button>
        
        <button v-if="selectedCourier === 'pathao'" @click="sendToPathao"
          class="px-4 py-2 mr-2 bg-green-500 hover:bg-green-600 text-white rounded-md text-sm font-medium flex items-center"
          :disabled="pathaoForm.processing || loadingSteadfast">
          <Truck :size="16" class="mr-1" />
          {{ pathaoForm.processing ? 'Sending...' : 'Send to Pathao' }}
        </button>

        <button v-if="selectedCourier === 'steadfast'" @click="sendToSteadfast"
          class="px-4 py-2 mr-2 bg-primary hover:bg-primary/90 text-white rounded-md text-sm font-medium flex items-center"
          :disabled="loadingSteadfast || pathaoForm.processing">
          <Truck :size="16" class="mr-1" />
          {{ loadingSteadfast ? 'Sending...' : 'Send to Steadfast' }}
        </button>

        <button @click="submit"
          class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-md text-sm font-medium flex items-center"
          :disabled="pathaoForm.processing || loadingSteadfast">
          <Send :size="16" class="mr-1" />
          {{ (pathaoForm.processing || loadingSteadfast) ? 'Submitting...' : 'Submit' }}
        </button>
      </div>
    </div>
  </div>
</template>