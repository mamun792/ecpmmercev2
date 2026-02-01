<template>
  <Head :title="product.name" />
  <GuestLayout>
    <div class="container mx-auto p-4">
      <div class="bg-white shadow-md rounded-lg overflow-hidden">
        <div class="p-6">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <div class="aspect-w-1 aspect-h-1 w-full overflow-hidden rounded-lg bg-gray-200">
                <img
                  :src="currentImage"
                  :alt="product.name"
                  class="h-full w-full object-cover object-center transition-opacity duration-300"
                  @error="handleImageError"
                />
              </div>
              <div class="mt-4 flex gap-2 overflow-x-auto">
                <button
                  v-for="(image, index) in productImages"
                  :key="index"
                  @click="selectImage(image)"
                  class="flex-shrink-0 cursor-pointer focus:outline-none"
                >
                  <img
                    :src="image"
                    :alt="`${product.name} thumbnail ${index + 1}`"
                    class="h-16 w-16 rounded-md object-cover"
                    :class="{ 'ring-2 ring-blue-500': currentImage === image }"
                  />
                </button>
              </div>
            </div>
            <div>
              <h1 class="text-3xl font-bold mb-2">{{ product.name }}</h1>
              <p class="text-gray-600 mb-4">{{ product.description }}</p>
              <div class="flex items-baseline mb-4">
                <span class="text-2xl font-semibold text-gray-900">${{ currentPrice }}</span>
                <span v-if="isDiscounted" class="ml-2 text-sm font-medium text-gray-500 line-through">${{ product.price }}</span>
              </div>
              <p class="text-sm text-gray-600 mb-4">
                Stock: <span class="font-medium">{{ currentStock }}</span>
              </p>
              <div v-for="(values, attrName) in groupedAttributes" :key="attrName" class="mb-4">
                <h3 class="text-sm font-medium text-gray-900 mb-2">{{ attrName }}</h3>
                <div class="grid grid-cols-3 gap-2">
                  <button
                    v-for="value in values"
                    :key="value"
                    @click="selectAttribute(attrName, value)"
                    class="px-3 py-2 text-sm font-medium rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                    :class="[
                      selectedAttributes[attrName] === value
                        ? 'bg-blue-600 text-white'
                        : 'bg-gray-200 text-gray-900 hover:bg-gray-300',
                    ]"
                  >
                    {{ value }}
                  </button>
                </div>
              </div>
              <div class="quantity-selector">
                <label for="quantity" class="block text-sm font-medium text-gray-700">Quantity</label>
                <input type="number" id="quantity" v-model="form.quantity" class=" px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
              </div>
              <button
                @click="addToCart"
                :disabled="!isAllAttributesSelected"
                class="w-full mt-6 bg-blue-600 text-white py-3 px-4 rounded-md font-medium text-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-colors duration-200"
              >
                {{ isAllAttributesSelected ? 'Add to Cart' : 'Select Options' }}
              </button>
            </div>
            <div v-html="product.description"></div>
          </div>
        </div>
      </div>
    </div>

</GuestLayout>
</template>

<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { useForm, Head } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import { toast } from '@steveyuowo/vue-hot-toast';

const props = defineProps({
  product: {
    type: Object,
    required: true
  }
});

const selectedAttributes = ref({});
const selectedVariation = ref(null);
const currentImage = ref(props.product.feature_image ? '/' + props.product.feature_image : '/placeholder.svg?height=400&width=400');

const productImages = computed(() => {
  const images = props.product.variations
    .map(v => v.image_path)
    .filter(Boolean)
    .map(path => '/' + path);
  
  if (props.product.feature_image) {
    images.unshift('/' + props.product.feature_image);
  }
  
  return [...new Set(images)]; // Remove duplicates
});

const groupedAttributes = computed(() => {
  const grouped = {};
  props.product.variations.forEach(variation => {
    variation.attributes.forEach(attr => {
      const name = attr.value.attribute.name;
      if (!grouped[name]) {
        grouped[name] = new Set();
      }
      grouped[name].add(attr.value.value);
    });
  });
  return grouped;
});

const isAllAttributesSelected = computed(() => {
  return Object.keys(groupedAttributes.value).every(attr => selectedAttributes.value[attr]);
});

const currentPrice = computed(() => {
  return selectedVariation.value ? selectedVariation.value.price : props.product.price;
});

const currentStock = computed(() => {
  return selectedVariation.value ? selectedVariation.value.stock : props.product.stock;
});

const isDiscounted = computed(() => {
  return selectedVariation.value && parseFloat(selectedVariation.value.price) < parseFloat(props.product.price);
});

const updateSelectedVariation = () => {
  selectedVariation.value = props.product.variations.find(variation =>
    variation.attributes.every(attr =>
      selectedAttributes.value[attr.value.attribute.name] === attr.value.value
    )
  ) || null;

  if (selectedVariation.value && selectedVariation.value.image_path) {
    currentImage.value = '/' + selectedVariation.value.image_path;
  }
};

const selectAttribute = (attrName, value) => {
  selectedAttributes.value = {
    ...selectedAttributes.value,
    [attrName]: value
  };
};

const selectImage = (image) => {
  currentImage.value = image;
};


const form = useForm({
  product_id: props.product.id,
  product_variation_id: null,
  quantity: 1,
});

const addToCart = () => {
  if (!selectedVariation.value) {
    toast.error('Please select all options before adding to cart.');
    return;
  }

  // Update the form data
  form.product_variation_id = selectedVariation.value.id;

  // Send a POST request to the backend
  form.post(route('products.addToCart'), {
    onSuccess: () => {
      toast.success('Item added to cart successfully!');
    },
    onError: (errors) => {
      console.error('Add to cart error:', errors);
      toast.error('Failed to add item to cart. Please try again.');
    },
    preserveScroll: true,
  });
};

const handleImageError = (e) => {
  e.target.src = '/placeholder.svg?height=400&width=400';
};

watch(selectedAttributes, () => {
  updateSelectedVariation();
}, { deep: true });
</script>