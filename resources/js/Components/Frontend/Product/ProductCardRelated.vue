<script setup>
import { Link } from "@inertiajs/vue3";
import { computed, ref } from "vue";
import { useWishlist } from "@/Composables/useWishlist";
import { useCompare } from "@/Composables/useCompare";
import { GitCompare, Heart, ShoppingCart } from "lucide-vue-next";
import StarRating from "@/Components/Frontend/Product/StarRating.vue";

const props = defineProps({
    product: {
        type: Object,
        required: true,
    },
});

const imageLoaded = ref(false);

const { toggleWishlist, isInWishlist } = useWishlist();
const { toggleCompare, isInCompare } = useCompare();

const activeCampaign = computed(() => {
    if (!props.product.campaigns || props.product.campaigns.length === 0)
        return null;
    const now = new Date();
    return props.product.campaigns.find((c) => {
        const start = new Date(c.start_date);
        const end = new Date(c.end_date);
        return c.status === "active" && now >= start && now <= end;
    });
});

const pricing = computed(() => {
    // If product is variable, display lowest variation price by default
    if (
        props.product.type === "variable" &&
        props.product.variations &&
        props.product.variations.length > 0
    ) {
        const numericVars = props.product.variations.map((v) => ({
            price: parseFloat(v.price || 0),
            previous_price:
                v.previous_price !== null && v.previous_price !== undefined
                    ? parseFloat(v.previous_price)
                    : null,
        }));

        const minVar = numericVars.reduce(
            (acc, v) => (acc === null || v.price < acc.price ? v : acc),
            null,
        );
        let main = minVar ? minVar.price : parseFloat(props.product.price);
        let cross = null;
        let discountValue = 0;
        let discountType = null;

        if (minVar) {
            const candidates = numericVars
                .filter((v) => v.previous_price && v.previous_price > main)
                .map((v) => v.previous_price);
            if (candidates.length > 0) {
                cross = Math.max(...candidates);
                discountValue = ((cross - main) / cross) * 100;
                discountType = "percentage";
            }
        }

        return { main, cross, discountValue, discountType };
    }

    // Fallback to previous logic for simple products
    let main = parseFloat(props.product.price);
    let cross = null;
    let discountValue = 0;
    let discountType = null;

    if (activeCampaign.value) {
        cross = main;
        discountValue = parseFloat(activeCampaign.value.discount_amount);
        discountType = activeCampaign.value.discount_type;

        if (discountType === "percentage") {
            main = main - main * (discountValue / 100);
        } else if (discountType === "fixed") {
            main = main - discountValue;
        }
    } else if (
        props.product.previous_price &&
        parseFloat(props.product.previous_price) >
            parseFloat(props.product.price)
    ) {
        cross = parseFloat(props.product.previous_price);
        main = parseFloat(props.product.price);
        discountValue = ((cross - main) / cross) * 100;
        discountType = "percentage";
    }

    return { main, cross, discountValue, discountType };
});

const formatPrice = (price) => {
    return new Intl.NumberFormat("en-BD", {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    }).format(price);
};

const isOutOfStock = computed(() => props.product.stock <= 0);

const discountBadgeText = computed(() => {
    if (!pricing.value.cross) return null;
    if (pricing.value.discountType === "percentage") {
        return `${Math.round(pricing.value.discountValue)}%`;
    }
    return `৳ ${formatPrice(pricing.value.discountValue)}`;
});

const handleWishlist = () => {
    toggleWishlist(props.product.id);
};

const handleCompare = () => {
    toggleCompare(props.product.id);
};
</script>

<template>
    <div
        class="group relative overflow-hidden rounded-lg bg-white shadow-sm hover:shadow-xl transition-all duration-300 border border-gray-100 hover:border-gray-200 flex flex-row h-full"
        :class="{ 'opacity-60': isOutOfStock }"
    >
        <!-- Discount Badge -->
        <!-- <div
            v-if="pricing.cross && !isOutOfStock"
            class="absolute top-3 right-3 z-20 bg-gradient-to-r from-orange-500 to-red-500 text-white px-2 py-1 rounded-full text-[10px] font-bold shadow-md"
        >
            <span v-if="pricing.discountType === 'percentage'">
                SAVE {{ discountBadgeText }}
            </span>
            <span v-else>{{ discountBadgeText }} OFF</span>
        </div> -->

        <!-- Out of Stock Badge -->
        <div
            v-if="isOutOfStock"
            class="absolute inset-0 z-30 flex items-center justify-center bg-gradient-to-t from-black/40 to-transparent rounded-lg"
        >
            <span
                class="bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-bold shadow-lg"
            >
                Out of Stock
            </span>
        </div>

        <!-- Action Buttons (Top Right) -->
        <!-- <div
            class="absolute top-3 left-3 z-20 flex gap-2 opacity-0 group-hover:opacity-100 transition-opacity duration-300"
            v-if="!isOutOfStock"
        >
            <button
                @click.prevent="handleWishlist"
                :class="[
                    'p-2.5 rounded-full shadow-md backdrop-blur-sm transition-all duration-200 hover:scale-110',
                    isInWishlist(product.id)
                        ? 'bg-red-50 text-red-600'
                        : 'bg-white/90 text-gray-500 hover:text-red-500 hover:bg-red-50',
                ]"
                :title="isInWishlist(product.id) ? 'Remove from wishlist' : 'Add to wishlist'"
            >
                <Heart :size="18" :fill="isInWishlist(product.id) ? 'currentColor' : 'none'" />
            </button>
            <button
                @click.prevent="handleCompare"
                :class="[
                    'p-2.5 rounded-full shadow-md backdrop-blur-sm transition-all duration-200 hover:scale-110',
                    isInCompare(product.id)
                        ? 'bg-blue-50 text-blue-600'
                        : 'bg-white/90 text-gray-500 hover:text-blue-500 hover:bg-blue-50',
                ]"
                :title="isInCompare(product.id) ? 'Remove from compare' : 'Add to compare'"
            >
                <GitCompare :size="18" />
            </button>
        </div> -->

        <!-- Product Image -->
        <div class="w-1/3 flex items-center justify-center p-2 bg-gray-50">
            <Link
                :href="route('product.details', product.slug)"
                class="block relative aspect-square overflow-hidden bg-gray-50"
            >
                <img
                    :src="product.feature_image"
                    :alt="product.name"
                    class="w-full h-full object-contain group-hover:scale-110 transition-transform duration-500"
                    @load="imageLoaded = true"
                />
            </Link>
        </div>

        <!-- Product Info -->
        <div class="flex flex-col flex-1 sm:p-4 p-2 gap-3">
            <!-- Product Name -->
            <Link :href="route('product.details', product.slug)" class="block">
                <h3
                    class="text-[12px] md:text-[14px] line-clamp-2 font-semibold text-gray-900 hover:text-primary transition-colors leading-snug"
                >
                    {{ product.name }}
                </h3>
            </Link>

            <!-- Rating -->
            <div
                v-if="product.reviews_count > 0"
                class="flex items-center gap-2"
            >
                <StarRating
                    :rating="product.avg_rating"
                    size="sm"
                    :show-count="false"
                />
                <span class="text-xs text-gray-800"
                    >({{ product.reviews_count }})</span
                >
            </div>

            <!-- Price Section -->
            <div class="space-y-1">
                <div class="flex items-baseline gap-2">
                    <span class="text-sm md:text-xl font-bold text-gray-900">
                        ৳ {{ formatPrice(pricing.main) }}
                    </span>
                    <span
                        v-if="pricing.cross"
                        class="text-xs sm:text-sm text-gray-400 line-through"
                    >
                        ৳ {{ formatPrice(pricing.cross) }}
                    </span>
                </div>
            </div>

            <!-- CTA Button -->
            <!-- <Link
                :href="route('product.details', product.slug)"
                class="mt-auto inline-flex items-center justify-center gap-2 w-full sm:px-4 px-2 py-2.5 bg-primary text-white text-[12px] sm:text-sm font-semibold rounded-lg hover:bg-primary/90 transition-all duration-200 shadow-sm hover:shadow-md active:scale-95"
                :class="{ 'opacity-50 pointer-events-none': isOutOfStock }"
            >
                <ShoppingCart :size="16" />
                <span>View Details</span>
            </Link> -->
        </div>
    </div>
</template>
