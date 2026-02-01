<script setup>
import { Headset, CreditCard, ShieldCheck, Truck } from "lucide-vue-next";
</script>

<template>
    <section class="py-12 bg-white border-t border-gray-100">
        <div class="container mx-auto px-4">
            <div
                class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8 md:gap-12"
            >
                <!-- Support 24/7 -->
                <div class="flex flex-col items-center text-center group">
                    <div class="mb-4">
                        <Headset
                            class="w-12 h-12 text-blue-500 group-hover:scale-110 transition-transform duration-300"
                            stroke-width="1.5"
                        />
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2 uppercase">
                        SUPPORT 24/7
                    </h3>
                    <p class="text-sm text-gray-500 max-w-[200px]">
                        We Support Online 24 Hours
                    </p>
                </div>

                <!-- Online Payment -->
                <div class="flex flex-col items-center text-center group">
                    <div class="mb-4">
                        <CreditCard
                            class="w-12 h-12 text-cyan-500 group-hover:scale-110 transition-transform duration-300"
                            stroke-width="1.5"
                        />
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">
                        Online Payment
                    </h3>
                    <p class="text-sm text-gray-500 max-w-[200px]">
                        Make payments hands free very easily
                    </p>
                </div>

                <!-- Authentic Product -->
                <div class="flex flex-col items-center text-center group">
                    <div class="mb-4">
                        <ShieldCheck
                            class="w-12 h-12 text-amber-500 group-hover:scale-110 transition-transform duration-300"
                            stroke-width="1.5"
                        />
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">
                        Authentic Product
                    </h3>
                    <p class="text-sm text-gray-500 max-w-[200px]">
                        Guaranteed 100% authentic product
                    </p>
                </div>

                <!-- Fast Delivery -->
                <div class="flex flex-col items-center text-center group">
                    <div class="mb-4">
                        <Truck
                            class="w-12 h-12 text-orange-500 group-hover:scale-110 transition-transform duration-300"
                            stroke-width="1.5"
                        />
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">
                        Fast Delivery
                    </h3>
                    <p class="text-sm text-gray-500 max-w-[200px]">
                        Fast Delivery is our first moto
                    </p>
                </div>
            </div>
        </div>
    </section>
</template>
