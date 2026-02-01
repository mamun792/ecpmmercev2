<template>
    <div
        v-if="campaigns.length > 0"
        class="w-full px-2 md:px-6 lg:px-6 relative mx-auto py-8"
    >
        <div v-for="campaign in campaigns" :key="campaign.id" class="mb-12">
            <!-- Highlighted Campaign Section -->
            <div
                class="campaign-highlight relative overflow-hidden rounded-2xl bg-gradient-to-r from-slate-200 to-slate-100 p-1 shadow-lg border border-slate-300"
            >
                <div class="campaign-inner bg-white rounded-xl p-8">
                    <!-- Campaign Header with Countdown -->
                    <div
                        class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4"
                    >
                        <!-- Campaign Title with Badge -->
                        <div class="flex items-center gap-4">
                            <span
                                class="campaign-badge px-4 py-1.5 bg-blue-600 text-white text-xs font-semibold uppercase rounded-full"
                            >
                                Limited Offer
                            </span>
                            <h3
                                class="text-2xl md:text-3xl font-bold text-slate-900"
                            >
                                {{ campaign.name }}
                            </h3>
                        </div>

                        <!-- Highlighted Countdown Timer -->
                        <div
                            v-if="
                                campaign.status === 'active' &&
                                getTimeRemaining(campaign.end_date) > 0
                            "
                            class="countdown-container"
                        >
                            <div
                                class="countdown-wrapper flex items-center gap-2 bg-white rounded-xl shadow-md border border-slate-200 p-1"
                            >
                                <div
                                    class="countdown-inner bg-slate-50 rounded-lg px-4 py-3 flex items-center gap-3"
                                >
                                    <span
                                        class="text-slate-700 text-sm font-semibold uppercase tracking-wide"
                                        >Ends In</span
                                    >
                                    <div class="countdown-boxes flex gap-2">
                                        <!-- Days -->
                                        <div
                                            v-if="
                                                getCountdownParts(
                                                    campaign.end_date,
                                                ).days > 0
                                            "
                                            class="countdown-box"
                                        >
                                            <span class="countdown-number">{{
                                                getCountdownParts(
                                                    campaign.end_date,
                                                ).days
                                            }}</span>
                                            <span class="countdown-label"
                                                >Days</span
                                            >
                                        </div>
                                        <!-- Hours -->
                                        <div class="countdown-box">
                                            <span class="countdown-number">{{
                                                String(
                                                    getCountdownParts(
                                                        campaign.end_date,
                                                    ).hours,
                                                ).padStart(2, "0")
                                            }}</span>
                                            <span class="countdown-label"
                                                >H</span
                                            >
                                        </div>
                                        <!-- Minutes -->
                                        <div class="countdown-box">
                                            <span class="countdown-number">{{
                                                String(
                                                    getCountdownParts(
                                                        campaign.end_date,
                                                    ).minutes,
                                                ).padStart(2, "0")
                                            }}</span>
                                            <span class="countdown-label"
                                                >M</span
                                            >
                                        </div>
                                        <!-- Seconds -->
                                        <div class="countdown-box">
                                            <span class="countdown-number">{{
                                                String(
                                                    getCountdownParts(
                                                        campaign.end_date,
                                                    ).seconds,
                                                ).padStart(2, "0")
                                            }}</span>
                                            <span class="countdown-label"
                                                >S</span
                                            >
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Campaign Ended Badge -->
                        <div
                            v-else-if="
                                campaign.status !== 'active' ||
                                getTimeRemaining(campaign.end_date) <= 0
                            "
                            class="ended-badge"
                        >
                            <span
                                class="px-4 py-2 bg-slate-200 text-slate-600 rounded-full font-medium"
                            >
                                Campaign Ended
                            </span>
                        </div>
                    </div>

                    <!-- Products Grid -->
                    <div
                        class="grid grid-cols-2 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4"
                    >
                        <div
                            v-for="product in campaign.products"
                            :key="product.id"
                            class="product-card-wrapper transform transition-all duration-300"
                        >
                            <ProductCard :product="product" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Decorative Elements -->
            <!-- <div
        class="absolute -top-4 -right-4 w-24 h-24 bg-yellow-400 rounded-full opacity-20 blur-xl"
      ></div>
      <div
        class="absolute -bottom-4 -left-4 w-32 h-32 bg-pink-400 rounded-full opacity-20 blur-xl"
      ></div> -->
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, defineProps } from "vue";
import ProductCard from "@/Components/Frontend/Product/ProductCard.vue";

const props = defineProps({
    campaigns: Object,
});

// Use useState for caching campaigns across navigation

const currentTime = ref(Date.now());
let timerInterval = null;

// const fetchCampaigns = async () => {
//     // Skip fetch if already loaded
//     if (campaignsLoaded.value) {
//         return;
//     }
//     try {
//         const response = await $fetch("/campaigns", {
//             baseURL: apiBase,
//         });
//         // $fetch returns the data directly, not wrapped in .data
//         campaigns.value = response.data;
//         campaignsLoaded.value = true;
//         console.log("Campaigns loaded:", campaigns.value);
//     } catch (error) {
//         console.error("Error fetching campaigns:", error);
//     }
// };

// Calculate time remaining dynamically from end_date
const getTimeRemaining = (endDate) => {
    const end = new Date(endDate).getTime();
    const remaining = Math.floor((end - currentTime.value) / 1000);
    return remaining > 0 ? remaining : 0;
};

// Get countdown parts for individual display boxes
const getCountdownParts = (endDate) => {
    const seconds = getTimeRemaining(endDate);
    return {
        days: Math.floor(seconds / 86400),
        hours: Math.floor((seconds % 86400) / 3600),
        minutes: Math.floor((seconds % 3600) / 60),
        seconds: seconds % 60,
    };
};

const formatTime = (seconds) => {
    const days = Math.floor(seconds / 86400);
    const hours = Math.floor((seconds % 86400) / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    const secs = seconds % 60;

    if (days > 0) {
        return `${days}d ${hours}h ${minutes}m ${secs}s`;
    } else if (hours > 0) {
        return `${hours}h ${minutes}m ${secs}s`;
    } else if (minutes > 0) {
        return `${minutes}m ${secs}s`;
    } else {
        return `${secs}s`;
    }
};

// Start interval to update countdown every second
const startCountdown = () => {
    timerInterval = setInterval(() => {
        currentTime.value = Date.now();
    }, 1000);
};

onMounted(() => {
    // fetchCampaigns();
    startCountdown();
});

// Clean up interval on unmount to prevent memory leaks
onUnmounted(() => {
    if (timerInterval) {
        clearInterval(timerInterval);
    }
});
</script>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Campaign Highlight Styles */
.campaign-highlight {
    position: relative;
}

.campaign-inner {
    position: relative;
    z-index: 1;
}

/* Countdown Box Styles */
.countdown-box {
    display: flex;
    flex-direction: column;
    align-items: center;
    background: #f8fafc;
    border-radius: 8px;
    padding: 8px 12px;
    min-width: 50px;
    border: 1px solid #e2e8f0;
}

.countdown-number {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1e293b;
    line-height: 1;
}

.countdown-label {
    font-size: 0.65rem;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-top: 2px;
}

/* Product Card Hover Effect */

/* Responsive Adjustments */
@media (max-width: 768px) {
    .countdown-box {
        padding: 6px 8px;
        min-width: 40px;
    }

    .countdown-number {
        font-size: 1.25rem;
    }

    .countdown-label {
        font-size: 0.55rem;
    }
}
</style>
