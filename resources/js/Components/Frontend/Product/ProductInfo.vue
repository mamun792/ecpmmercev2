<script setup>
import { ref, computed, watch, onMounted } from "vue";
import { useCart } from "@/Composables/useCart";
import { useWishlist } from "@/Composables/useWishlist";
import { useCompare } from "@/Composables/useCompare";
import { useToast } from "@/Composables/useToast";
import { GitCompare, Truck } from "lucide-vue-next";
import { router, usePage } from "@inertiajs/vue3";
import StarRating from "@/Components/Frontend/Product/StarRating.vue";
import { trackViewItem, trackAddToCart } from "@/Composables/gtm";

const props = defineProps({
    product: {
        type: Object,
        required: true,
    },
    settings: {
        type: Object,
        default: () => ({}),
    },
});

// Use centralized cart composable
const { addToCart: addToCartAction, isLoading: cartLoading } = useCart();
const { toggleWishlist, isInWishlist } = useWishlist();
const { toggleCompare, isInCompare } = useCompare();
const { warning, error } = useToast();

const selectedAttributes = ref({});
const quantity = ref(1);
const isAddingToCart = ref(false);

const emit = defineEmits(["variation-change"]);

// Color name to hex mapping for V2 structure (when color hex not stored in DB)
const colorNameToHex = {
    'red': '#FF0000',
    'blue': '#0000FF',
    'green': '#00FF00',
    'yellow': '#FFFF00',
    'black': '#000000',
    'white': '#FFFFFF',
    'pink': '#FFC0CB',
    'purple': '#800080',
    'orange': '#FFA500',
    'brown': '#A52A2A',
    'gray': '#808080',
    'grey': '#808080',
    'navy': '#000080',
    'maroon': '#800000',
    'gold': '#FFD700',
    'silver': '#C0C0C0',
    'beige': '#F5F5DC',
    'cyan': '#00FFFF',
    'magenta': '#FF00FF',
    'olive': '#808000',
};

const getColorHex = (colorName) => {
    if (!colorName) return null;
    const normalized = colorName.toLowerCase().trim();
    return colorNameToHex[normalized] || null;
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

// Helper: find the variation that matches current selection (if any)
const selectedVariation = computed(() => getMatchingVariation());

const pricing = computed(() => {
    // If a variation is explicitly selected, show its prices
    const selected = selectedVariation.value;
    let main = null;
    let cross = null;
    let discountValue = 0;
    let discountType = null;

    // Campaign logic should still prefer the product-level price/campaign when no variation selected
    if (selected) {
        main = parseFloat(selected.price);
        if (
            selected.previous_price &&
            parseFloat(selected.previous_price) > parseFloat(selected.price)
        ) {
            cross = parseFloat(selected.previous_price);
            discountValue = ((cross - main) / cross) * 100;
            discountType = "percentage";
        }
    } else if (
        props.product.type === "variable" &&
        props.product.variations &&
        props.product.variations.length > 0
    ) {
        // No selection: show lowest variation price as main
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
        if (minVar) {
            main = minVar.price;
            // Find any previous_price greater than main — prefer the maximum previous_price for a clear cross price
            const candidates = numericVars
                .filter((v) => v.previous_price && v.previous_price > main)
                .map((v) => v.previous_price);
            if (candidates.length > 0) {
                cross = Math.max(...candidates);
                discountValue = ((cross - main) / cross) * 100;
                discountType = "percentage";
            }
        }

        // If there's an active campaign on product level, we still apply it to product price display ONLY when product.price is meaningful (fallback)
        if (activeCampaign.value && main === null) {
            main = parseFloat(props.product.price);
            cross = main;
            discountValue = parseFloat(activeCampaign.value.discount_amount);
            discountType = activeCampaign.value.discount_type;

            if (discountType === "percentage") {
                main = main - main * (discountValue / 100);
            } else if (discountType === "fixed") {
                main = main - discountValue;
            }
        }
    } else {
        // Simple product or fallback
        main = parseFloat(props.product.price);

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
    }

    return {
        main,
        cross,
        discountValue,
        discountType,
    };
});

const discountBadgeText = computed(() => {
    if (!pricing.value.cross) return null;
    if (pricing.value.discountType === "percentage") {
        return `${Math.round(pricing.value.discountValue)}% OFF`;
    }
    return `৳ ${parseFloat(pricing.value.discountValue).toFixed(0)} OFF`;
});

// WhatsApp share link using global settings (provided via Inertia page props)
const page = usePage();
const settings = computed(() => page.props.settings || {});
// guard against settings.generalSettings being null
const whatsappNumberRaw = computed(
    () => settings.value.generalSettings?.whatsapp_number || "",
);
// ensure we always operate on a string
const whatsappNumberClean = computed(() =>
    (whatsappNumberRaw.value || "").replace(/\D/g, ""),
);
const whatsappMessage = computed(() => {
    const url = typeof window !== "undefined" ? window.location.href : "";
    const name = props.product.name || "";
    const code = props.product.product_code
        ? ` (Code: ${props.product.product_code})`
        : "";

    return encodeURIComponent(
        `Hello, I'm interested in ${name}${code}. Here's the link: ${url}`,
    );
});
const whatsappLink = computed(() => {
    if (!whatsappNumberClean.value) return null;
    return `https://wa.me/${whatsappNumberClean.value}?text=${whatsappMessage.value}`;
});
const showWhatsapp = computed(() => !!whatsappLink.value);

// const whatsappLink = computed(() => {
//     if (!whatsappNumberClean.value) return null;
//     return `https://api.whatsapp.com/send?phone=${whatsappNumberClean.value}&text=${whatsappMessage.value}`;
// });
// const showWhatsapp = computed(() => !!whatsappNumberClean.value);

// Extract all unique attributes dynamically from variations
const availableAttributes = computed(() => {
    const attributesMap = new Map();

    // First pass: collect unique attribute values
    // V2 structure: attributeValues[].{id, value: 'BLUE', attribute: {id, name: 'COLOR'}}
    // V1 structure: attributes[].{value: {id, value: 'BLUE', color, attribute: {name: 'COLOR'}}}
    props.product.variations?.forEach((variation) => {
        const attrs = variation.attributeValues || variation.attributes || [];
        attrs.forEach((attr) => {
            // V2: attr.attribute.name, attr.value (string)
            // V1: attr.value.attribute.name, attr.value.value (string)
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
                return; // Unknown structure
            }

            if (!attrName) return;

            if (!attributesMap.has(attrName)) {
                attributesMap.set(attrName, {
                    name: attrDisplayName,
                    values: new Set(),
                });
            }

            attributesMap.get(attrName).values.add(
                JSON.stringify({
                    id: attrId,
                    value: attrValue,
                    color: attrColor,
                    image: null,
                }),
            );
        });
    });

    // Second pass: attach variation images to attribute values
    props.product.variations?.forEach((variation) => {
        if (!variation.image_path) return;
        const attrs = variation.attributeValues || variation.attributes || [];
        attrs.forEach((attr) => {
            let attrName, attrId;
            if (attr.attribute && typeof attr.value === 'string') {
                attrName = attr.attribute.name?.toLowerCase();
                attrId = attr.id;
            } else if (attr.value && typeof attr.value === 'object') {
                attrName = attr.value.attribute?.name?.toLowerCase();
                attrId = attr.value.id;
            } else {
                return;
            }

            const set = attributesMap.get(attrName)?.values;
            if (!set) return;

            for (const item of Array.from(set)) {
                try {
                    const obj = JSON.parse(item);
                    if (obj.id === attrId) {
                        set.delete(item);
                        obj.image = variation.image_path;
                        set.add(JSON.stringify(obj));
                        break;
                    }
                } catch (err) {}
            }
        });
    });

    // Convert Map to array with parsed values
    const attributesArray = [];
    attributesMap.forEach((attrData, attrKey) => {
        attributesArray.push({
            key: attrKey,
            name: attrData.name,
            values: Array.from(attrData.values).map((item) => JSON.parse(item)),
        });
    });

    return attributesArray;
});

const selectAttribute = (type, value) => {
    selectedAttributes.value[type] = value;
};

// Find matching variation based on selected attributes
const getMatchingVariation = () => {
    if (!props.product.variations || props.product.variations.length === 0) {
        return null;
    }

    const selectedKeys = Object.keys(selectedAttributes.value);
    if (selectedKeys.length === 0) {
        return null;
    }

    // Find variation that matches all selected attributes EXACTLY
    return props.product.variations.find((variation) => {
        const attrs = variation.attributeValues || variation.attributes || [];
        if (!attrs.length) return false;

        // Ensure the number of attributes matches the number of selections
        if (attrs.length !== selectedKeys.length) return false;

        // Check if every selected attribute matches the variation's attributes
        return selectedKeys.every((attrKey) => {
            const selectedAttr = selectedAttributes.value[attrKey];
            return attrs.some((varAttr) => {
                let varAttrName, varAttrId;
                if (varAttr.attribute && typeof varAttr.value === 'string') {
                    // V2 structure
                    varAttrName = varAttr.attribute.name?.toLowerCase();
                    varAttrId = varAttr.id;
                } else if (varAttr.value && typeof varAttr.value === 'object') {
                    // V1 structure
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

// Stock Logic
const currentStock = computed(() => {
    const variation = getMatchingVariation();
    if (variation) {
        return variation.stock;
    }
    // If no variation selected, show global stock
    return props.product.stock;
});

const hasStock = computed(() => {
    return currentStock.value > 0;
});

const decreaseQuantity = () => {
    if (quantity.value > 1) {
        quantity.value--;
    }
};

const increaseQuantity = () => {
    if (quantity.value < currentStock.value) {
        quantity.value++;
    }
};

watch(
    selectedAttributes,
    () => {
        const variation = getMatchingVariation();
        // If a color attribute is selected, capture its image (if any)
        const colorSelection = selectedAttributes.value["color"];
        const attributeImage = colorSelection
            ? colorSelection.image || null
            : null;

        // Emit both the resolved variation (may be null) and attributeImage (for color swatches)
        emit("variation-change", { variation, attributeImage });

        // Reset quantity if it exceeds new stock limit
        if (variation && quantity.value > variation.stock) {
            quantity.value = Math.max(1, variation.stock);
        }
    },
    { deep: true },
);

const addToCart = () => {
    // Check if all attributes are selected
    if (availableAttributes.value.length > 0) {
        const selectedCount = Object.keys(selectedAttributes.value).length;
        if (selectedCount < availableAttributes.value.length) {
            const missing = availableAttributes.value
                .filter((attr) => !selectedAttributes.value[attr.key])
                .map((attr) => attr.name)
                .join(", ");
            error(`Please select: ${missing}`);
            return;
        }
    }

    // Get matching variation
    const matchingVariation = getMatchingVariation();

    // Validate: Check if attributes are required and selected
    if (
        props.product.variations &&
        props.product.variations.length > 0 &&
        !matchingVariation
    ) {
        error("Please select all product options");
        return;
    }

    isAddingToCart.value = true;

    // Use centralized cart composable
    addToCartAction({
        productId: props.product.id,
        quantity: quantity.value,
        variationId: matchingVariation ? matchingVariation.id : null,
        productOptions: {
            gift_wrap: false,
            message: "",
        },
        onSuccess: () => {
            isAddingToCart.value = false;
            // Track add_to_cart in Google Tag Manager
            trackAddToCart(props.product, matchingVariation, quantity.value);
        },
        onError: () => {
            isAddingToCart.value = false;
        },
    });
};

const buyNow = () => {
    // Check if all attributes are selected
    if (availableAttributes.value.length > 0) {
        const selectedCount = Object.keys(selectedAttributes.value).length;
        if (selectedCount < availableAttributes.value.length) {
            const missing = availableAttributes.value
                .filter((attr) => !selectedAttributes.value[attr.key])
                .map((attr) => attr.name)
                .join(", ");
            error(`Please select: ${missing}`);
            return;
        }
    }

    const matchingVariation = getMatchingVariation();

    if (
        props.product.variations &&
        props.product.variations.length > 0 &&
        !matchingVariation
    ) {
        error("Please select all product options");
        return;
    }

    isAddingToCart.value = true;

    addToCartAction({
        productId: props.product.id,
        quantity: quantity.value,
        variationId: matchingVariation ? matchingVariation.id : null,
        productOptions: {
            gift_wrap: false,
            message: "",
        },
        onSuccess: () => {
            isAddingToCart.value = false;
            trackAddToCart(props.product, matchingVariation, quantity.value);
            router.visit("/order/checkout");
        },
        onError: () => {
            isAddingToCart.value = false;
        },
    });
};

const handleWishlist = () => {
    toggleWishlist(props.product.id);
};

const handleCompare = () => {
    toggleCompare(props.product.id);
};

// Check if attribute is color type
const isColorAttribute = (attrKey) => {
    return attrKey === "color";
};

// Check if color is light (white-ish) for better border visibility
const isLightColor = (color) => {
    if (!color) return false;

    // Convert hex to RGB
    const hex = color.replace("#", "");
    const r = parseInt(hex.substr(0, 2), 16);
    const g = parseInt(hex.substr(2, 2), 16);
    const b = parseInt(hex.substr(4, 2), 16);

    // Calculate brightness (perceived luminance)
    const brightness = (r * 299 + g * 587 + b * 114) / 1000;

    // Return true if color is light (brightness > 200)
    return brightness > 200;
};

// Check if an attribute option is available (has > 0 stock) given other current selections
const isOptionAvailable = (key, valueObj) => {
    if (!props.product.variations || props.product.variations.length === 0)
        return true;

    const potentialSelection = { ...selectedAttributes.value, [key]: valueObj };

    return props.product.variations.some((variation) => {
        if (variation.stock <= 0) return false;

        const attrs = variation.attributeValues || variation.attributes || [];
        if (!attrs.length) return false;

        return Object.keys(potentialSelection).every((selKey) => {
            const desiredVal = potentialSelection[selKey];

            const matchingAttr = attrs.find((a) => {
                let aName;
                if (a.attribute && typeof a.value === 'string') {
                    aName = a.attribute.name?.toLowerCase();
                } else if (a.value && typeof a.value === 'object') {
                    aName = a.value.attribute?.name?.toLowerCase();
                }
                return aName === selKey;
            });

            if (!matchingAttr) return false;

            let matchingAttrId;
            if (matchingAttr.attribute && typeof matchingAttr.value === 'string') {
                matchingAttrId = matchingAttr.id;
            } else if (matchingAttr.value && typeof matchingAttr.value === 'object') {
                matchingAttrId = matchingAttr.value.id;
            }
            return matchingAttrId === desiredVal.id;
        });
    });
};

// Helper function to wait for dataLayer to be ready
const waitForDataLayer = () => {
    return new Promise((resolve) => {
        if (typeof window !== "undefined" && window.dataLayer) {
            resolve();
        } else {
            // Check every 100ms for up to 5 seconds
            let attempts = 0;
            const maxAttempts = 50;
            const interval = setInterval(() => {
                attempts++;
                if (window.dataLayer) {
                    clearInterval(interval);
                    resolve();
                } else if (attempts >= maxAttempts) {
                    clearInterval(interval);
                    console.warn("DataLayer not initialized after 5 seconds");
                    resolve(); // Resolve anyway to not block
                }
            }, 100);
        }
    });
};

// Track view when product is loaded (works for both navigation and reload)
watch(
    () => props.product,
    async (newProduct) => {
        if (newProduct) {
            // Wait for dataLayer to be ready
            await waitForDataLayer();
            // Track product view in Google Tag Manager
            //console.log("Tracking view_item for:", newProduct);
            // Pass the computed main price for accurate ecommerce value
            trackViewItem(newProduct, pricing.value.main);
        }
    },
    { immediate: true }, // Run immediately if product is already loaded
);
</script>

<template>
    <div class="font-sans">
        <!-- <pre>{{ settings }}</pre> -->
        <!-- <pre>{{ whatsappNumberRaw }}</pre> -->
        <!-- Product Title & Code -->
        <div class="mb-4 mt-4">
            <h1
                class="text-xl md:text-3xl font-bold text-gray-900 leading-tight mb-2"
            >
                {{ product.name }}
            </h1>
            <div
                class="flex items-center gap-4 mb-3"
                v-if="product.reviews_count > 0"
            >
                <StarRating
                    :rating="product.avg_rating"
                    size="md"
                    :show-count="true"
                />
                <span class="text-sm text-gray-500">
                    ({{ product.reviews_count }} reviews)
                </span>
            </div>
            <div class="flex items-center gap-4 text-sm text-gray-500">
                <span class="bg-gray-100 text-gray-600 px-2 py-1 rounded"
                    >Code: {{ product.product_code }}</span
                >

                <p
                    v-if="hasStock"
                    class="text-xs text-green-600 font-medium flex items-center gap-1"
                >
                    <svg
                        class="w-4 h-4"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M5 13l4 4L19 7"
                        />
                    </svg>
                    In Stock
                </p>
                <p v-else class="text-xs text-red-600 mt-2 font-medium">
                    * This item is currently unavailable
                </p>
            </div>

            <div v-if="product.brand && product.brand.brand_image" class="my-2">
                <img
                    :src="product.brand.brand_image"
                    alt="brand"
                    class="w-20"
                />
            </div>

            <p
                v-if="product.is_free_delivery"
                class="text-xs text-green-700 mt-2 font-medium flex items-center gap-2"
            >
                <Truck :size="16" />
                <span>Free Delivery</span>
            </p>
        </div>

        <!-- Price Section -->
        <!-- Price Section -->
        <div class="flex items-end gap-3 flex-wrap mb-4">
            <div class="flex items-baseline gap-2">
                <span class="text-2xl md:text-4xl font-extrabold text-primary">
                    ৳ {{ parseFloat(pricing.main).toFixed(2) }}
                </span>
                <span
                    v-if="pricing.cross"
                    class="text-lg text-gray-400 line-through decoration-red-500/50"
                >
                    ৳ {{ parseFloat(pricing.cross).toFixed(2) }}
                </span>
            </div>

            <!-- <div v-if="pricing.cross" class="flex gap-2 mb-1">
                <span
                    class="bg-red-100 text-red-600 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wide"
                >
                    {{ discountBadgeText }}
                </span>
                <span
                    class="bg-blue-100 text-blue-600 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wide"
                >
                    Save ৳
                    {{
                        (
                            parseFloat(pricing.cross) - parseFloat(pricing.main)
                        ).toFixed(2)
                    }}
                </span>
            </div> -->

            <!-- WhatsApp share button (shows if store number is configured) -->
            <div class="flex items-center">
                <a
                    v-if="showWhatsapp"
                    :href="whatsappLink"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="flex items-center gap-2"
                    :title="`Message on WhatsApp`"
                    aria-label="Share product on WhatsApp"
                >
                    <!-- <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" class="w-5 h-5" fill="currentColor" aria-hidden="true">
                        <path d="M19.11 17.58c-.32-.16-1.88-.93-2.17-1.04-.29-.11-.5-.16-.71.16-.22.32-.86 1.04-1.05 1.25-.19.21-.38.24-.7.08-.32-.16-1.36-.5-2.59-1.6-.96-.86-1.6-1.93-1.79-2.25-.19-.32-.02-.49.14-.65.15-.15.32-.38.48-.57.16-.19.21-.33.32-.53.11-.19.05-.36-.02-.52-.07-.16-.71-1.7-.98-2.33-.26-.61-.53-.53-.71-.53-.19 0-.41-.02-.62-.02-.21 0-.55.08-.84.39-.29.32-1.11 1.08-1.11 2.63 0 1.54 1.14 3.03 1.3 3.24.16.21 2.23 3.41 5.4 4.78 3.17 1.37 3.17.92 3.74.86.56-.06 1.88-.77 2.15-1.5.27-.73.27-1.37.19-1.5-.08-.12-.29-.19-.6-.35z"/>
                        <path d="M16.005 3C9.383 3 3.999 8.385 3.999 15c0 2.638.86 4.88 2.325 6.75L3 29l7.528-3.063A11.91 11.91 0 0016.005 27c6.621 0 12.005-5.385 12.005-12S22.626 3 16.005 3z"/>
                    </svg>
                    <span class="hidden md:inline text-sm font-semibold">WhatsApp</span> -->
                    <img
                        src="/assets/img/logo/whatsapp-button.webp"
                        alt=""
                        class="w-[150px]"
                    />
                </a>
            </div>
        </div>

        <!-- Short Description -->
        <div
            v-if="product.short_description"
            class="text-sm text-gray-600"
            v-html="product.short_description"
        ></div>

        <!-- Stock Status (Global) -->
        <div
            v-if="!hasStock"
            class="bg-red-50 border pt-4 border-red-200 text-red-700 px-4 py-3 rounded-lg flex items-center gap-2"
            role="alert"
        >
            <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-5 w-5"
                viewBox="0 0 20 20"
                fill="currentColor"
            >
                <path
                    fill-rule="evenodd"
                    d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                    clip-rule="evenodd"
                />
            </svg>
            <span class="font-medium">Currently Out of Stock</span>
        </div>

        <!-- Dynamic Attributes -->
        <div v-if="availableAttributes.length > 0" class="space-y-5 py-3">
            <div v-for="attribute in availableAttributes" :key="attribute.key">
                <div class="flex justify-between items-center mb-2">
                    <h3
                        class="text-sm font-bold text-gray-900 uppercase tracking-wide"
                    >
                        {{ attribute.name }}:
                        <span
                            class="text-primary normal-case ml-1"
                            v-if="selectedAttributes[attribute.key]"
                        >
                            {{ selectedAttributes[attribute.key].value }}
                        </span>
                    </h3>
                </div>

                <!-- Color Attribute -->
                <div
                    v-if="isColorAttribute(attribute.key)"
                    class="flex flex-wrap gap-3"
                >
                    <div
                        v-for="attrValue in attribute.values"
                        :key="attrValue.id"
                        role="radio"
                        :aria-checked="
                            selectedAttributes[attribute.key]?.id ===
                            attrValue.id
                        "
                        @click="
                            isOptionAvailable(attribute.key, attrValue) &&
                            selectAttribute(attribute.key, attrValue)
                        "
                        class="group relative transition-opacity duration-200"
                        :class="[
                            !isOptionAvailable(attribute.key, attrValue)
                                ? 'opacity-40 cursor-not-allowed'
                                : 'cursor-pointer',
                        ]"
                    >
                        <!-- Selection Ring -->
                        <div
                            class="absolute -inset-1 rounded-full border-2 transition-all duration-200"
                            :class="
                                selectedAttributes[attribute.key]?.id ===
                                attrValue.id
                                    ? 'border-primary opacity-100 scale-105'
                                    : 'border-transparent opacity-0 group-hover:opacity-100'
                            "
                        ></div>

                        <!-- Cross Overlay for Unavailable -->
                        <div
                            v-if="!isOptionAvailable(attribute.key, attrValue)"
                            class="absolute inset-0 z-50 flex items-center justify-center pointer-events-none"
                        >
                            <svg
                                class="w-full h-full text-gray-500/80"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <line x1="3" y1="3" x2="21" y2="21" />
                                <line x1="21" y1="3" x2="3" y2="21" />
                            </svg>
                        </div>

                        <!-- Image Swatch -->
                        <template v-if="attrValue.image">
                            <img
                                :src="attrValue.image"
                                :alt="attrValue.value"
                                class="w-10 h-10 rounded-full object-cover relative z-10 block ring-1 ring-gray-200"
                            />
                        </template>

                        <!-- Color Swatch -->
                        <template v-else>
                            <div
                                :style="{
                                    backgroundColor:
                                        attrValue.color || '#ffffff',
                                }"
                                class="w-10 h-10 rounded-full relative z-10 shadow-sm ring-1 ring-inset ring-black/10"
                            ></div>
                        </template>
                    </div>
                </div>

                <!-- Other Attributes (Buttons) -->
                <div v-else class="flex flex-wrap gap-3">
                    <button
                        v-for="attrValue in attribute.values"
                        :key="attrValue.id"
                        type="button"
                        @click="selectAttribute(attribute.key, attrValue)"
                        class="px-5 py-2 rounded-lg text-sm font-medium transition-all duration-200 border relative overflow-hidden"
                        :class="[
                            selectedAttributes[attribute.key]?.id ===
                            attrValue.id
                                ? 'border-primary bg-primary text-white shadow-md transform scale-105'
                                : 'border-gray-200 bg-white text-gray-700 hover:border-primary/50 hover:bg-gray-50',
                        ]"
                    >
                        {{ attrValue.value }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Quantity & Actions -->
        <div class="pt-6 border-t border-gray-100">
            <div class="flex flex-col sm:flex-row gap-4">
                <!-- Quantity -->
                <div
                    class="flex items-center bg-gray-50 rounded-lg p-1 border border-gray-200 w-fit"
                >
                    <button
                        @click="decreaseQuantity"
                        :disabled="quantity <= 1 || !hasStock"
                        class="p-3 text-gray-600 hover:text-primary disabled:opacity-30 transition-colors"
                        aria-label="Decrease quantity"
                    >
                        <svg
                            class="w-4 h-4"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M20 12H4"
                            />
                        </svg>
                    </button>
                    <input
                        type="number"
                        v-model.number="quantity"
                        class="w-12 text-center bg-transparent border-none p-0 text-gray-900 font-semibold focus:ring-0"
                        min="1"
                        :max="currentStock"
                        readonly
                    />
                    <button
                        @click="increaseQuantity"
                        :disabled="quantity >= currentStock || !hasStock"
                        class="p-3 text-gray-600 hover:text-primary disabled:opacity-30 transition-colors"
                        aria-label="Increase quantity"
                    >
                        <svg
                            class="w-4 h-4"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 4v16m8-8H4"
                            />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Action Buttons (Desktop) -->
            <div class="hidden md:flex flex-1 gap-3 mt-4">
                <button
                    @click="addToCart"
                    :disabled="isAddingToCart || !hasStock"
                    class="flex-1 px-6 py-3.5 bg-gray-900 text-white font-bold rounded-lg hover:bg-gray-800 transition-all active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed disabled:active:scale-100 flex items-center justify-center gap-2 shadow-lg shadow-gray-200"
                >
                    <svg
                        v-if="isAddingToCart"
                        class="animate-spin h-5 w-5"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                    >
                        <circle
                            class="opacity-25"
                            cx="12"
                            cy="12"
                            r="10"
                            stroke="currentColor"
                            stroke-width="4"
                        ></circle>
                        <path
                            class="opacity-75"
                            fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                        ></path>
                    </svg>
                    <span v-else>Add to Cart</span>
                </button>
                <button
                    @click="buyNow"
                    :disabled="isAddingToCart || !hasStock"
                    class="flex-1 px-6 py-3.5 bg-primary text-white font-bold rounded-lg hover:bg-primary-dark transition-all active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed disabled:active:scale-100 shadow-lg shadow-primary/20"
                >
                    Buy Now
                </button>
            </div>

            <!-- Action Buttons (Mobile Sticky Footer) -->
            <Teleport to="body">
                <div
                    class="md:hidden fixed bottom-16 left-0 right-0 p-4 bg-white/95 backdrop-blur-md border-t border-gray-100 z-[40] animate-in slide-in-from-bottom duration-500"
                >
                    <div class="flex gap-3 max-w-screen-sm mx-auto">
                        <button
                            @click="addToCart"
                            :disabled="isAddingToCart || !hasStock"
                            class="flex-[1.2] px-4 py-3.5 bg-gray-900 text-white text-sm font-bold rounded-xl hover:bg-gray-800 transition-all active:scale-95 disabled:opacity-50 flex items-center justify-center gap-2 shadow-lg shadow-gray-200"
                        >
                            <svg
                                v-if="isAddingToCart"
                                class="animate-spin h-4 w-4"
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                            >
                                <circle
                                    class="opacity-25"
                                    cx="12"
                                    cy="12"
                                    r="10"
                                    stroke="currentColor"
                                    stroke-width="4"
                                ></circle>
                                <path
                                    class="opacity-75"
                                    fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                ></path>
                            </svg>
                            <span v-else>Add to Cart</span>
                        </button>
                        <button
                            @click="buyNow"
                            :disabled="isAddingToCart || !hasStock"
                            class="flex-1 px-4 py-3.5 bg-primary text-white text-sm font-bold rounded-xl hover:bg-primary-dark transition-all active:scale-95 disabled:opacity-50 shadow-lg shadow-primary/20"
                        >
                            Buy Now
                        </button>
                    </div>
                </div>
            </Teleport>
        </div>

        <!-- Accordion for Product Details -->
        <div class="mt-6">
            <details open class="border-t pt-4 product_details">
                <summary class="font-semibold text-lg mb-2 cursor-pointer">
                    Product Details
                </summary>
                <div v-html="product?.description"></div>
            </details>
        </div>

        <!-- Wishlist & Compare -->
        <!-- <div
            class="flex items-center gap-6 pt-2 text-sm font-medium text-gray-500"
        >
            <button
                @click="handleWishlist"
                class="flex items-center gap-2 transition-colors hover:text-red-500 group"
            >
                <div
                    class="p-2 bg-gray-50 rounded-full group-hover:bg-red-50 transition-colors"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-5 w-5 transition-transform group-hover:scale-110"
                        :class="
                            isInWishlist(product.id)
                                ? 'fill-red-500 text-red-500'
                                : 'text-gray-400 group-hover:text-red-500'
                        "
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="1.5"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"
                        />
                    </svg>
                </div>
                <span>{{
                    isInWishlist(product.id)
                        ? "Saved to Wishlist"
                        : "Add to Wishlist"
                }}</span>
            </button>

            <button
                @click="handleCompare"
                class="flex items-center gap-2 transition-colors hover:text-primary group"
            >
                <div
                    class="p-2 bg-gray-50 rounded-full group-hover:bg-primary/10 transition-colors"
                >
                    <GitCompare
                        class="h-5 w-5 transition-transform group-hover:scale-110"
                        :class="
                            isInCompare(product.id)
                                ? 'text-primary'
                                : 'text-gray-400 group-hover:text-primary'
                        "
                    />
                </div>
                <span>{{
                    isInCompare(product.id) ? "Added to Compare" : "Compare"
                }}</span>
            </button>
        </div> -->
    </div>
</template>

<style scoped>
details summary::-webkit-details-marker {
    display: none;
}

details summary {
    position: relative;
    padding-right: 20px;
}

details summary::after {
    content: "+";
    position: absolute;
    right: 0;
    top: 0;
}

details[open] summary::after {
    content: "-";
}
</style>
