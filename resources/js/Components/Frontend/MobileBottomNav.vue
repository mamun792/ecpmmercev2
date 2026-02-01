<script setup>
import { Link } from "@inertiajs/vue3";
import { Home, Search, Heart, GitCompare, MapPin, User } from "lucide-vue-next";

defineProps({
    wishlistCount: {
        type: Number,
        default: 0,
    },
    compareCount: {
        type: Number,
        default: 0,
    },
});

const emit = defineEmits(["open-search"]);
</script>

<template>
    <div
        class="lg:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-gray-100 z-[30] pb-safe"
    >
        <div class="flex items-center justify-around py-2">
            <Link
                href="/"
                class="flex flex-col items-center gap-1 p-2 text-gray-600 hover:text-primary transition-colors"
                :class="{ 'text-primary': $page.url === '/' }"
            >
                <Home class="w-5 h-5" />
                <span class="text-[10px] font-medium">Home</span>
            </Link>

            <button
                @click="emit('open-search')"
                class="flex flex-col items-center gap-1 p-2 text-gray-600 hover:text-primary transition-colors"
            >
                <Search class="w-5 h-5" />
                <span class="text-[10px] font-medium">Search</span>
            </button>

            <Link
                href="/order/track"
                class="flex flex-col items-center gap-1 p-2 text-gray-600 hover:text-primary transition-colors"
            >
                <MapPin class="w-5 h-5" />
                <span class="text-[10px] font-medium">Track</span>
            </Link>

            <Link
                :href="route('user.dashboard')"
                class="flex flex-col items-center gap-1 p-2 text-gray-600 hover:text-primary transition-colors"
            >
                <User class="w-5 h-5" />
                <span class="text-[10px] font-medium">Account</span>
            </Link>

            <!-- <Link
                href="/wishlist"
                class="relative flex flex-col items-center gap-1 p-2 text-gray-600 hover:text-primary transition-colors"
                :class="{ 'text-primary': $page.url === '/wishlist' }"
            >
                <Heart class="w-5 h-5" />
                <span
                    v-if="wishlistCount > 0"
                    class="absolute top-1 right-2 w-4 h-4 bg-primary text-white text-[10px] rounded-full flex items-center justify-center"
                >
                    {{ wishlistCount }}
                </span>
                <span class="text-[10px] font-medium">Wishlist</span>
            </Link> -->

            <!-- <Link
                href="/compare"
                class="relative flex flex-col items-center gap-1 p-2 text-gray-600 hover:text-primary transition-colors"
                :class="{ 'text-primary': $page.url === '/compare' }"
            >
                <GitCompare class="w-5 h-5" />
                <span
                    v-if="compareCount > 0"
                    class="absolute top-1 right-2 w-4 h-4 bg-primary text-white text-[10px] rounded-full flex items-center justify-center"
                >
                    {{ compareCount }}
                </span>
                <span class="text-[10px] font-medium">Compare</span>
            </Link> -->
        </div>
    </div>
</template>

<style scoped>
.pb-safe {
    padding-bottom: env(safe-area-inset-bottom);
}
</style>
