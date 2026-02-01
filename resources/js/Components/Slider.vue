<template>
  <div class="relative w-full h-auto overflow-hidden">
    <!-- Slider Wrapper -->
    <div
      class="flex transition-transform duration-500 ease-in-out"
      :style="{ transform: `translateX(-${currentIndex * 100}%)` }"
    >
      <!-- Slides -->
      <div
        v-for="(slide, index) in slides"
        :key="index"
        class="min-w-full flex items-center justify-center"
        :class="slide.bgColor"
      >
        <div class="grid grid-cols-1 md:grid-cols-2 items-center w-full max-w-7xl mx-auto">
          <!-- Text Section -->
          <div class="p-8 md:p-16 text-left">
            <p class="text-sm uppercase text-gray-600">{{ slide.subtitle }}</p>
            <h2 class="text-3xl md:text-5xl font-bold text-gray-900">
              {{ slide.title }}
            </h2>
            <p class="text-gray-500 mt-4">{{ slide.description }}</p>
            <button
              class="mt-6 px-6 py-2 bg-black text-white text-sm font-medium uppercase rounded shadow hover:bg-gray-800"
            >
              {{ slide.buttonText }}
            </button>
          </div>
          <!-- Image Section -->
          <div class="p-4 md:p-8">
            <img :src="slide.image" alt="Slide image" class="w-full h-auto rounded shadow" />
          </div>
        </div>
      </div>
    </div>

    <!-- Navigation Arrows -->
    <button
      class="absolute top-1/2 left-4 transform -translate-y-1/2 bg-gray-200 hover:bg-gray-300 p-2 rounded-full shadow"
      @click="prevSlide"
    >
      &#x25C0; <!-- Left arrow -->
    </button>
    <button
      class="absolute top-1/2 right-4 transform -translate-y-1/2 bg-gray-200 hover:bg-gray-300 p-2 rounded-full shadow"
      @click="nextSlide"
    >
      &#x25B6; <!-- Right arrow -->
    </button>

    <!-- Indicators -->
    <div class="absolute bottom-4 left-1/2 transform -translate-x-1/2 flex space-x-2">
      <span
        v-for="(slide, index) in slides"
        :key="'indicator-' + index"
        class="w-3 h-3 rounded-full cursor-pointer"
        :class="currentIndex === index ? 'bg-gray-800' : 'bg-gray-400'"
        @click="goToSlide(index)"
      ></span>
    </div>
  </div>
</template>

<script setup>
import { ref } from "vue";

const slides = ref([
  {
    subtitle: "THIS WEEK'S HIGHLIGHTS",
    title: "Limited Edition For Queen Styles Fashion",
    description: "Awesome products for the dynamic urban lifestyle",
    buttonText: "Shop Now",
    image: "https://images.pexels.com/photos/1462637/pexels-photo-1462637.jpeg?auto=compress&cs=tinysrgb&w=600", // Replace with your image URL
    bgColor: "bg-gray-100", // Tailwind background color class
  },
  {
    subtitle: "NEW COLLECTION",
    title: "Unleash Your Style with Modern Designs",
    description: "Discover the trendiest apparel for every occasion",
    buttonText: "Discover Now",
    image: "https://images.pexels.com/photos/974911/pexels-photo-974911.jpeg?auto=compress&cs=tinysrgb&w=600", // Replace with your image URL
    bgColor: "bg-white",
  },
  {
    subtitle: "LIMITED TIME OFFER",
    title: "Exclusive Deals on Latest Arrivals",
    description: "Hurry up and grab your favorite items now!",
    buttonText: "View Offers",
    image: "https://demos.codezeel.com/wordpress/WCM07/WCM070172/default/wp-content/uploads/2024/11/cms-banner-1-600x307.jpg", // Replace with your image URL
    bgColor: "bg-gray-50",
  },
]);

const currentIndex = ref(0);

const prevSlide = () => {
  currentIndex.value = (currentIndex.value - 1 + slides.value.length) % slides.value.length;
};

const nextSlide = () => {
  currentIndex.value = (currentIndex.value + 1) % slides.value.length;
};

const goToSlide = (index) => {
  currentIndex.value = index;
};
</script>

<style scoped>
/* Customize your slider transitions and styles here */
</style>
