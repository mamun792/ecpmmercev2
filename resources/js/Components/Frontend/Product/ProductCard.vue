<script setup>
import { Link } from "@inertiajs/vue3";
import { computed, ref, watch, onUnmounted } from "vue";
import { useWishlist } from "@/Composables/useWishlist";
import { useCompare } from "@/Composables/useCompare";
import {
    GitCompare,
    Heart,
    ShoppingCart,
    Truck,
    X,
    Plus,
    Minus,
} from "lucide-vue-next";
import StarRating from "@/Components/Frontend/Product/StarRating.vue";
import { useCart } from "@/Composables/useCart";

const props = defineProps({
    product: {
        type: Object,
        required: true,
    },
});

const imageLoaded = ref(false);

const { toggleWishlist, isInWishlist } = useWishlist();
const { toggleCompare, isInCompare } = useCompare();
const { addToCart: addToCartAction } = useCart();
const showOptions = ref(false);
const selectedAttributes = ref({});
const quantity = ref(1);
const addingToCart = ref(false);

const isVariable = computed(() => props.product.type === "variable");

// Color name to hex mapping for V2 structure
const colorNameToHex = {
    'red': '#FF0000', 'blue': '#0000FF', 'green': '#00FF00', 'yellow': '#FFFF00',
    'black': '#000000', 'white': '#FFFFFF', 'pink': '#FFC0CB', 'purple': '#800080',
    'orange': '#FFA500', 'brown': '#A52A2A', 'gray': '#808080', 'grey': '#808080',
    'navy': '#000080', 'maroon': '#800000', 'gold': '#FFD700', 'silver': '#C0C0C0',
};
const getColorHex = (colorName) => {
    if (!colorName) return null;
    return colorNameToHex[colorName.toLowerCase().trim()] || null;
};

// Extract all unique attributes dynamically from variations (V2 compatible)
const availableAttributes = computed(() => {
    if (!props.product.variations) return [];

    const attributesMap = new Map();

    props.product.variations.forEach((variation) => {
        const attrs = variation.attributeValues || variation.attributes || [];
        attrs.forEach((attr) => {
            let attrName, attrDisplayName, attrValue, attrId, attrColor;

            if (attr.attribute && typeof attr.value === 'string') {
                // V2 structure
                attrName = attr.attribute.name?.toLowerCase();
                attrDisplayName = attr.attribute.name;
                attrValue = attr.value;
                attrId = attr.id;
                attrColor = attr.attribute.name?.toLowerCase() === 'color' ? getColorHex(attr.value) : null;
            } else if (attr.value && typeof attr.value === 'object') {
                // V1 structure
                attrName = attr.value.attribute?.name?.toLowerCase();
                attrDisplayName = attr.value.attribute?.name;
                attrValue = attr.value.value;
                attrId = attr.value.id;
                attrColor = attr.value.color;
            } else {
                return;
            }

            if (!attrName) return;

            if (!attributesMap.has(attrName)) {
                attributesMap.set(attrName, {
                    name: attrDisplayName,
                    values: new Map(),
                });
            }
            const valuesMap = attributesMap.get(attrName).values;
            if (!valuesMap.has(attrId)) {
                valuesMap.set(attrId, {
                    id: attrId,
                    value: attrValue,
                    color: attrColor,
                });
            }
        });
    });

    const result = [];
    attributesMap.forEach((attrData, attrKey) => {
        result.push({
            key: attrKey,
            name: attrData.name,
            values: Array.from(attrData.values.values()),
        });
    });
    return result;
});

const selectAttribute = (type, value) => {
    selectedAttributes.value[type] = value;
};

const getMatchingVariation = () => {
    if (!props.product.variations) return null;
    const selectedKeys = Object.keys(selectedAttributes.value);

    if (selectedKeys.length === 0 && availableAttributes.value.length > 0)
        return null;

    return props.product.variations.find((variation) => {
        const attrs = variation.attributeValues || variation.attributes || [];
        if (!attrs.length) return false;
        if (attrs.length !== selectedKeys.length) return false;

        return selectedKeys.every((attrKey) => {
            const selectedAttr = selectedAttributes.value[attrKey];
            return attrs.some((varAttr) => {
                let varAttrName, varAttrId;
                if (varAttr.attribute && typeof varAttr.value === 'string') {
                    varAttrName = varAttr.attribute.name?.toLowerCase();
                    varAttrId = varAttr.id;
                } else if (varAttr.value && typeof varAttr.value === 'object') {
                    varAttrName = varAttr.value.attribute?.name?.toLowerCase();
                    varAttrId = varAttr.value.id;
                } else {
                    return false;
                }
                return varAttrName === attrKey && varAttrId === selectedAttr.id;
            });
        });
    });
};

const currentStock = computed(() => {
    if (isVariable.value) {
        const variation = getMatchingVariation();
        return variation ? variation.stock : 0;
    }
    return props.product.stock;
});

// For variable products, we only allow adding if we have a match OR if no attributes are needed (unlikely for variable)
const canAddToCart = computed(() => {
    if (!isVariable.value) return props.product.stock > 0;

    // Check if all attributes are selected
    const requiredAttrs = availableAttributes.value.map((a) => a.key);
    const selected = Object.keys(selectedAttributes.value);
    const allSelected = requiredAttrs.every((k) => selected.includes(k));

    if (!allSelected) return false;

    const variation = getMatchingVariation();
    return variation && variation.stock > 0;
});

const handleAddToCartClick = (e) => {
    if (isVariable.value) {
        showOptions.value = true;
        // Pre-select first options if available to save clicks?
        // Optional: Implement auto-select first values
        if (Object.keys(selectedAttributes.value).length === 0) {
            availableAttributes.value.forEach((attr) => {
                if (attr.values.length > 0) {
                    selectAttribute(attr.key, attr.values[0]);
                }
            });
        }
    } else {
        performAddToCart();
    }
};

const performAddToCart = () => {
    addingToCart.value = true;

    let variationId = null;
    if (isVariable.value) {
        const v = getMatchingVariation();
        if (v) variationId = v.id;
    }

    addToCartAction({
        productId: props.product.id,
        quantity: quantity.value,
        variationId: variationId,
        productOptions: {},
        onSuccess: () => {
            addingToCart.value = false;
            showOptions.value = false;
            quantity.value = 1;
        },
        onError: () => {
            addingToCart.value = false;
        },
    });
};

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

const selectedVariation = computed(() => getMatchingVariation());

const pricing = computed(() => {
    // If a variation is explicitly selected, show its prices
    const selected = selectedVariation.value;
    if (selected) {
        let main = parseFloat(selected.price || 0);
        let cross = null;
        let discountValue = 0;
        let discountType = null;

        if (
            selected.previous_price &&
            parseFloat(selected.previous_price) > parseFloat(selected.price)
        ) {
            cross = parseFloat(selected.previous_price);
            discountValue = ((cross - main) / cross) * 100;
            discountType = "percentage";
        }
        return { main, cross, discountValue, discountType };
    }

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

// Lock body scroll on mobile when options modal is open
watch(showOptions, (newVal) => {
    if (typeof window !== "undefined") {
        if (newVal && window.innerWidth < 768) {
            document.body.style.overflow = "hidden";
        } else {
            document.body.style.overflow = "";
        }
    }
});

onUnmounted(() => {
    if (typeof window !== "undefined") {
        document.body.style.overflow = "";
    }
});
</script>

<template>
    <div
        class="group relative flex flex-col h-full bg-white"
        :class="{ 'opacity-75 grayscale': isOutOfStock }"
    >
        <!-- Action Buttons (Hover) -->
        <!-- <div
            class="absolute top-12 right-3 z-20 flex flex-col gap-2 opacity-0 group-hover:opacity-100 transition-all duration-300 translate-x-4 group-hover:translate-x-0"
            v-if="!isOutOfStock"
        >
            <button
                @click.prevent="handleWishlist"
                class="p-2 rounded-full shadow-md bg-white text-gray-500 hover:text-red-500 hover:bg-red-50 transition-colors"
                :title="
                    isInWishlist(product.id)
                        ? 'Remove from wishlist'
                        : 'Add to wishlist'
                "
            >
                <Heart
                    :size="16"
                    :fill="isInWishlist(product.id) ? 'currentColor' : 'none'"
                    :class="{ 'text-red-500': isInWishlist(product.id) }"
                />
            </button>
            <button
                @click.prevent="handleCompare"
                class="p-2 rounded-full shadow-md bg-white text-gray-500 hover:text-blue-500 hover:bg-blue-50 transition-colors"
                :title="
                    isInCompare(product.id)
                        ? 'Remove from compare'
                        : 'Add to compare'
                "
            >
                <GitCompare
                    :size="16"
                    :class="{ 'text-blue-500': isInCompare(product.id) }"
                />
            </button>
        </div> -->

        <div class="relative">
            <!-- Product Image -->
            <Link
                :href="route('product.details', product.slug)"
                class="block relative w-full aspect-[2/3] rounded-xl overflow-hidden"
            >
                <img
                    :src="product.feature_image"
                    :alt="product.name"
                    loading="lazy"
                    class="w-full h-full object-cover mix-blend-multiply group-hover:scale-110 transition-transform duration-500"
                    @load="imageLoaded = true"
                />

                <!-- Out of Stock Overlay -->
                <div
                    v-if="isOutOfStock"
                    class="absolute inset-0 z-10 flex items-center justify-center bg-white/50"
                >
                    <span
                        class="bg-gray-900 text-white px-3 py-1 text-xs font-bold rounded uppercase tracking-wider"
                    >
                        Sold Out
                    </span>
                </div>
            </Link>

            <!-- Quick View Overlay (Desktop) -->
            <div
                v-if="showOptions"
                class="hidden md:flex absolute inset-x-0 bottom-0 bg-white z-[30] p-4 shadow-lg rounded-t-xl border-t border-gray-100 flex-col gap-3 animate-in slide-in-from-bottom duration-300"
                @click.stop.prevent
            >
                <div class="flex justify-between items-center mb-1">
                    <span
                        class="text-xs font-bold text-gray-800 uppercase tracking-wide"
                        >Select Options</span
                    >
                    <button
                        @click="showOptions = false"
                        class="text-gray-400 hover:text-red-500"
                    >
                        <X :size="16" />
                    </button>
                </div>

                <!-- Dynamic Attributes -->
                <div
                    v-for="attr in availableAttributes"
                    :key="attr.key"
                    class="border-b border-gray-50 pb-2 last:border-0 last:pb-0"
                >
                    <div class="flex justify-between items-baseline mb-1">
                        <span
                            class="text-[10px] font-bold text-gray-500 uppercase"
                        >
                            {{ attr.name }}:
                            <span
                                class="text-primary"
                                v-if="selectedAttributes[attr.key]"
                                >{{ selectedAttributes[attr.key].value }}</span
                            >
                        </span>
                    </div>
                    <div class="flex flex-wrap gap-1.5">
                        <button
                            v-for="val in attr.values"
                            :key="val.id"
                            @click="selectAttribute(attr.key, val)"
                            class="px-2 py-0.5 text-[10px] uppercase font-bold border rounded transition-all duration-200"
                            :class="
                                selectedAttributes[attr.key]?.id === val.id
                                    ? 'bg-primary border-primary text-white shadow-sm'
                                    : 'bg-white border-gray-200 text-gray-600 hover:border-gray-300'
                            "
                        >
                            {{ val.value }}
                        </button>
                    </div>
                </div>

                <!-- Quantity & Add -->
                <div class="flex gap-2 mt-1">
                    <div
                        class="flex items-center border border-gray-200 rounded h-8 bg-gray-50"
                    >
                        <button
                            @click="quantity > 1 ? quantity-- : null"
                            class="px-2 h-full flex items-center text-gray-500 hover:text-primary disabled:opacity-50"
                            :disabled="quantity <= 1"
                        >
                            <Minus :size="12" />
                        </button>
                        <span
                            class="w-6 text-center text-xs font-bold text-gray-800"
                            >{{ quantity }}</span
                        >
                        <button
                            @click="quantity++"
                            class="px-2 h-full flex items-center text-gray-500 hover:text-primary"
                        >
                            <Plus :size="12" />
                        </button>
                    </div>
                    <button
                        @click="performAddToCart"
                        :disabled="!canAddToCart || addingToCart"
                        class="flex-1 bg-primary text-white text-[10px] font-bold uppercase tracking-wider rounded hover:bg-primary/90 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-1 h-8 transition-colors"
                    >
                        <span v-if="addingToCart">...</span>
                        <span v-else class="flex items-center gap-1">
                            Add
                        </span>
                    </button>
                </div>
            </div>

            <!-- Quick View Modal (Mobile Teleport) -->
            <Teleport to="body">
                <div
                    v-if="showOptions"
                    class="md:hidden fixed inset-0 z-[100] flex flex-col justify-end"
                    @click.stop
                >
                    <!-- Backdrop -->
                    <div
                        class="absolute inset-0 bg-black/60 backdrop-blur-sm animate-in fade-in duration-300"
                        @click="showOptions = false"
                    ></div>

                    <!-- Bottom Sheet -->
                    <div
                        class="relative bg-white rounded-t-[2.5rem] p-6 shadow-2xl animate-in slide-in-from-bottom duration-500 max-h-[90vh] overflow-y-auto"
                    >
                        <!-- Drag Handle -->
                        <div
                            class="w-12 h-1.5 bg-gray-200 rounded-full mx-auto mb-6"
                        ></div>

                        <div class="flex justify-between items-start mb-6">
                            <div class="flex-1">
                                <span
                                    class="text-[10px] font-bold text-primary uppercase tracking-[0.2em] mb-1 block"
                                    >Quick Add</span
                                >
                                <h3
                                    class="text-lg font-bold text-gray-900 leading-tight"
                                >
                                    {{ product.name }}
                                </h3>
                                <div class="flex items-center gap-2 mt-2">
                                    <span class="text-xl font-bold text-primary"
                                        >৳ {{ formatPrice(pricing.main) }}</span
                                    >
                                    <span
                                        v-if="pricing.cross"
                                        class="text-sm text-gray-400 line-through"
                                        >৳
                                        {{ formatPrice(pricing.cross) }}</span
                                    >
                                </div>
                            </div>
                            <button
                                @click="showOptions = false"
                                class="p-2.5 bg-gray-50 text-gray-400 hover:text-red-500 rounded-2xl transition-all"
                            >
                                <X :size="24" />
                            </button>
                        </div>

                        <!-- Mobile Attributes -->
                        <div class="space-y-6 mb-8">
                            <div
                                v-for="attr in availableAttributes"
                                :key="attr.key"
                            >
                                <div
                                    class="flex justify-between items-center mb-3"
                                >
                                    <span
                                        class="text-xs font-bold text-gray-500 uppercase tracking-wider"
                                        >{{ attr.name }}</span
                                    >
                                    <span
                                        class="text-xs font-medium text-primary bg-primary/5 px-2 py-0.5 rounded-full"
                                        v-if="selectedAttributes[attr.key]"
                                    >
                                        {{ selectedAttributes[attr.key].value }}
                                    </span>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <button
                                        v-for="val in attr.values"
                                        :key="val.id"
                                        @click="selectAttribute(attr.key, val)"
                                        class="px-4 py-2 text-sm font-semibold border-2 rounded-xl transition-all duration-300"
                                        :class="
                                            selectedAttributes[attr.key]?.id ===
                                            val.id
                                                ? 'bg-primary border-primary text-white shadow-lg shadow-primary/20 scale-105'
                                                : 'bg-white border-gray-100 text-gray-600 active:scale-95'
                                        "
                                    >
                                        {{ val.value }}
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Mobile Footer Action -->
                        <div class="flex gap-3 pt-4 border-t border-gray-100">
                            <div
                                class="flex items-center bg-gray-50 rounded-2xl p-1 border border-gray-100"
                            >
                                <button
                                    @click="quantity > 1 ? quantity-- : null"
                                    class="w-10 h-10 flex items-center justify-center text-gray-500 active:scale-90 transition-transform"
                                    :disabled="quantity <= 1"
                                >
                                    <Minus :size="16" />
                                </button>
                                <span
                                    class="w-8 text-center font-bold text-gray-900"
                                    >{{ quantity }}</span
                                >
                                <button
                                    @click="quantity++"
                                    class="w-10 h-10 flex items-center justify-center text-gray-500 active:scale-90 transition-transform"
                                >
                                    <Plus :size="16" />
                                </button>
                            </div>
                            <button
                                @click="performAddToCart"
                                :disabled="!canAddToCart || addingToCart"
                                class="flex-1 bg-primary text-white font-bold rounded-2xl shadow-xl shadow-primary/25 hover:bg-primary/90 disabled:opacity-50 flex items-center justify-center gap-2 h-12 transition-all active:scale-[0.98]"
                            >
                                <ShoppingCart v-if="!addingToCart" :size="20" />
                                <span v-if="addingToCart">Processing...</span>
                                <span v-else>Add to Cart</span>
                            </button>
                        </div>
                    </div>
                </div>
            </Teleport>
        </div>

        <!-- Content -->
        <div class="flex flex-col flex-1 mt-3">
            <div class="flex items-center justify-between mb-2">
                <!-- Top Badges & Brand -->
                <div class="relative h-6 flex items-center">
                    <!-- Discount Badge -->
                    <div
                        v-if="pricing.discountValue > 0 && !isOutOfStock"
                        class="border border-lime-500 text-lime-600 bg-white text-[11px] font-bold px-2.5 py-0.5 rounded-full"
                    >
                        <span v-if="pricing.discountType === 'percentage'">
                            -{{ Math.round(pricing.discountValue) }}%
                        </span>
                        <span v-else>
                            -৳{{ formatPrice(pricing.discountValue) }}
                        </span>
                    </div>
                    <div v-else class="h-6">
                        <span
                            class="border border-lime-500 text-lime-600 bg-white text-[11px] font-bold px-2.5 py-0.5 rounded-full"
                            >0%</span
                        >
                        <!-- Spacer to maintain height if no discount -->
                    </div>
                </div>

                <!-- Quick Add Button -->
                <button
                    @click.stop.prevent="handleAddToCartClick"
                    class="md:bg-gray-200/80 md:hover:bg-gray-300 md:text-gray-700 bg-primary text-white px-3 py-1.5 rounded-lg text-xs font-bold transition-all duration-200 flex items-center gap-1.5"
                    :disabled="isOutOfStock"
                    :title="isVariable ? 'Select Options' : 'Add to Cart'"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        width="12"
                        height="16"
                        viewBox="0 0 12 16"
                        fill="currentColor"
                        class="icon icon-cart"
                    >
                        <path
                            d="M8.46861 14.9963V12.437H5.81787V11.5839H8.46861V9.02457H9.35219V11.5839H12.0029V12.437H9.35219V14.9963H8.46861Z"
                            fill="currentColor"
                        ></path>
                        <path
                            d="M10.2257 4.68741C11.3251 4.80893 12.1358 5.81656 11.9845 6.94131L11.461 10.8319H10.3507L10.8946 6.79483L10.9024 6.69424C10.9141 6.19668 10.5127 5.77432 10.0021 5.77432H2.05283C1.50823 5.77452 1.08858 6.25506 1.16123 6.79483L2.06748 13.5292C2.12759 13.9758 2.50948 14.3094 2.96006 14.3095H7.22373V15.4091H2.96006L2.77354 15.4013C1.91622 15.3218 1.20382 14.6984 1.01084 13.8593L0.977637 13.6767L0.0704101 6.94131C-0.0807972 5.81681 0.730126 4.80926 1.8292 4.68741L2.05283 4.67471H10.0021L10.2257 4.68741Z"
                            fill="currentColor"
                        ></path>
                        <path
                            d="M6.20898 0.419098C8.06868 0.513381 9.5476 2.05068 9.54785 3.93375V7.49429H8.44824V3.93375C8.44798 2.59759 7.36447 1.51409 6.02832 1.51382C4.69194 1.51382 3.60769 2.59743 3.60742 3.93375V7.49429H2.50781V3.93375C2.50808 1.98992 4.08443 0.414215 6.02832 0.414215L6.20898 0.419098Z"
                            fill="currentColor"
                        ></path>
                    </svg>
                    <span class="hidden md:block">{{
                        isOutOfStock ? "Sold Out" : "Add To Cart"
                    }}</span>
                </button>
            </div>
            <!-- Title -->
            <Link
                :href="route('product.details', product.slug)"
                class="group-hover:text-primary transition-colors"
            >
                <h3
                    class="text-sm rounded font-bold text-gray-800 leading-tight line-clamp-2"
                    :title="product.name"
                >
                    {{ product.name }}
                </h3>
            </Link>

            <!-- Price -->
            <div class="flex items-end gap-1 mt-1">
                <span class="text-lg font-bold text-primary">
                    ৳ {{ formatPrice(pricing.main) }}
                </span>
                <span
                    v-if="pricing.cross"
                    class="text-xs text-gray-400 line-through mb-1"
                >
                    ৳ {{ formatPrice(pricing.cross) }}
                </span>
            </div>

            <!-- Footer: Rating & Cart -->
            <div
                class="flex items-center justify-between border-t border-gray-50"
            >
                <!-- Rating -->
                <!-- Rating -->
                <div class="flex items-center gap-1">
                    <StarRating
                        :rating="Number(product.avg_rating || 0)"
                        size="sm"
                        :show-count="false"
                    />
                    <span
                        v-if="product.reviews_count > 0"
                        class="text-xs text-gray-800"
                    >
                        ({{ product.reviews_count }})
                    </span>
                </div>

                <!-- Cart Action -->
                <!-- <button
                    @click.stop.prevent="handleAddToCartClick"
                    class="text-primary hover:bg-primary hover:text-white p-2 rounded-lg transition-all duration-200 flex items-center justify-center bg-gray-50"
                    :disabled="isOutOfStock"
                    :title="isVariable ? 'Select Options' : 'Add to Cart'"
                >
                    <ShoppingCart :size="18" stroke-width="2.5" />
                </button> -->
            </div>
        </div>
    </div>
</template>
