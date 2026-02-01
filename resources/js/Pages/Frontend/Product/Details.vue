<script setup>
import { defineProps, ref, computed, watch } from "vue";
import ImageGallery from "@/Components/Frontend/Product/ImageGallery.vue";
import ProductInfo from "@/Components/Frontend/Product/ProductInfo.vue";
import ProductReviews from "@/Components/Frontend/Product/ProductReviews.vue";
import FrontendLayout from "@/Layouts/FrontendLayout.vue";
import { Link, Head } from "@inertiajs/vue3";
import StarRating from "@/Components/Frontend/Product/StarRating.vue";
import ProductCard from "@/Components/Frontend/Product/ProductCard.vue";
import ProductCardRelated from "@/Components/Frontend/Product/ProductCardRelated.vue";
import RelatedProducts from "@/Components/Frontend/Product/RelatedProducts.vue";
// --- Tabbed sections + smooth scroll / active-state ---
import { onMounted, onBeforeUnmount, nextTick } from "vue";

const props = defineProps({
    product: {
        type: Object,
        required: true,
    },
    reviews: {
        type: Array,
        default: () => [],
    },
    reviewStats: {
        type: Object,
        default: () => ({}),
    },
    canReview: {
        type: Object,
        default: () => ({}),
    },
    relatedProducts: {
        type: Array,
        default: () => [],
    },
    settings: {
        type: Object,
        default: () => ({}),
    },
});

const selectedVariationImage = ref(null);
const recentProducts = ref([]);

const updateRecentViewed = (product) => {
    try {
        const STORAGE_KEY = "recently_viewed_products";
        const raw = localStorage.getItem(STORAGE_KEY);
        let items = raw ? JSON.parse(raw) : [];

        // 1. Populate the display list (everything currently in history, EXCLUDING current product)
        // We filter by ID to ensure we don't show the product the user is currently looking at
        recentProducts.value = items.filter((item) => item.id !== product.id);

        // 2. Update the storage with the current product
        // Remove current product if it exists (to move it to the top)
        items = items.filter((item) => item.id !== product.id);

        // Minify product data to save space in localStorage
        const minifiedProduct = {
            id: product.id,
            name: product.name,
            slug: product.slug,
            price: product.price,
            previous_price: product.previous_price,
            feature_image: product.feature_image,
            avg_rating: product.avg_rating,
            reviews_count: product.reviews_count,
            type: product.type,
            stock: product.stock,
            campaigns: product.campaigns, // needed for pricing calc
            variations: product.variations
                ? product.variations.map((v) => ({
                      price: v.price,
                      previous_price: v.previous_price,
                  }))
                : [], // simplify variations
        };

        // Add to front
        items.unshift(minifiedProduct);

        // Limit to 10 items
        if (items.length > 10) {
            items = items.slice(0, 10);
        }

        localStorage.setItem(STORAGE_KEY, JSON.stringify(items));
    } catch (e) {
        console.error("Failed to update recent products", e);
    }
};

// Watch for product changes to update history
watch(
    () => props.product,
    (newProduct) => {
        if (newProduct) {
            updateRecentViewed(newProduct);
        }
    },
    { immediate: true },
);

const productImages = computed(() => {
    const images = [];
    if (props.product.feature_image) {
        images.push(props.product.feature_image);
    }
    if (
        props.product.gallery_images &&
        Array.isArray(props.product.gallery_images)
    ) {
        images.push(...props.product.gallery_images);
    }
    return images;
});

// Page title (uses site level product_page_title if available)
const pageTitle = computed(() => {
    const gp = props.settings?.generalSettings;
    const siteTitle = gp?.product_page_title;
    return siteTitle
        ? `${props.product.name} - ${siteTitle}`
        : props.product.name;
});

const handleVariationChange = (payload) => {
    // Payload can be either a variation object (legacy) or an object { variation, attributeImage }
    let variation = null;
    let attributeImage = null;

    if (
        payload &&
        typeof payload === "object" &&
        Object.prototype.hasOwnProperty.call(payload, "variation")
    ) {
        variation = payload.variation;
        attributeImage = payload.attributeImage || null;
    } else {
        // Legacy single-argument emit (variation)
        variation = payload;
    }

    // Prefer full variation image when available; otherwise fall back to color attribute image
    if (variation && variation.image_path) {
        selectedVariationImage.value = variation.image_path;
    } else if (attributeImage) {
        selectedVariationImage.value = attributeImage;
    } else {
        selectedVariationImage.value = null;
    }
};

const activeTab = ref("specs");
const specsSection = ref(null);
const descriptionSection = ref(null);
const warrantySection = ref(null);
const reviewsSection = ref(null);
let sectionsObserver = null;

// Height of the fixed header (provided by user). Used to offset scroll positions so
// anchored sections are not hidden beneath the header.
const HEADER_OFFSET = 152;

const hasSpecification = computed(() => {
    return (
        Array.isArray(props.product?.specification) &&
        props.product.specification.length > 0
    );
});

const hasWarranty = computed(() => {
    return !!props.product.warranty; // simplistic check, assumes warranty field exists
});

const scrollToSection = async (key) => {
    // wait for DOM to be settled
    await nextTick();
    const elMap = {
        specs: specsSection,
        description: descriptionSection,
        warranty: warrantySection,
        reviews: reviewsSection,
    };
    const elRef = elMap[key];
    if (!elRef || !elRef.value) return;

    // Compute absolute top and subtract the fixed header height so the section
    // is fully visible below the header.
    const rect = elRef.value.getBoundingClientRect();
    const absoluteTop = rect.top + window.scrollY;
    const target = Math.max(0, absoluteTop - HEADER_OFFSET - 12); // 12px breathing room

    window.scrollTo({ top: target, behavior: "smooth" });

    // update active tab immediately so UI responds while scrolling
    activeTab.value = key;
};

onMounted(() => {
    const observeTargets = [
        { key: "specs", ref: specsSection },
        { key: "description", ref: descriptionSection },
        { key: "warranty", ref: warrantySection },
        { key: "reviews", ref: reviewsSection },
    ];

    sectionsObserver = new IntersectionObserver(
        (entries) => {
            // pick the entry with highest intersectionRatio that's visible
            const visible = entries
                .filter((e) => e.isIntersecting)
                .sort((a, b) => b.intersectionRatio - a.intersectionRatio);
            if (visible.length > 0) {
                const id = visible[0].target.getAttribute("data-section");
                if (id) activeTab.value = id;
            }
        },
        // rootMargin pushes the intersection area down by HEADER_OFFSET so that a
        // section is considered "visible" only when it's scrolled into view below
        // the fixed header. The bottom margin (-40%) helps pick the section that's
        // roughly centered in the viewport for better active-tab accuracy.
        {
            root: null,
            rootMargin: `-${HEADER_OFFSET + 8}px 0px -40% 0px`,
            threshold: [0, 0.25, 0.5, 0.75],
        },
    );

    observeTargets.forEach((s) => {
        if (s.ref && s.ref.value) {
            s.ref.value.setAttribute("data-section", s.key);
            sectionsObserver.observe(s.ref.value);
        }
    });
});

onBeforeUnmount(() => {
    if (sectionsObserver) sectionsObserver.disconnect();
});
</script>

<template>
    <FrontendLayout>
        <Head :title="pageTitle" />
        <div class="min-h-screen bg-gray-50/30 font-sans pb-12">
            <!-- <pre>{{ props.product }}</pre> -->
            <div class="container mx-auto px-4 sm:px-6 lg:px-8 md:py-8 py-4">
                <!-- Breadcrumb -->
                <nav
                    class="md:flex hidden items-center text-sm text-gray-500 overflow-x-auto whitespace-nowrap pb-2"
                    aria-label="Breadcrumb"
                >
                    <ol class="flex items-center gap-2">
                        <li>
                            <Link
                                href="/"
                                class="hover:text-primary transition-colors flex items-center gap-1"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="h-4 w-4"
                                    viewBox="0 0 20 20"
                                    fill="currentColor"
                                >
                                    <path
                                        d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"
                                    />
                                </svg>
                                Home
                            </Link>
                        </li>

                        <li class="text-gray-300 pointer-events-none">/</li>

                        <!-- Parent category (if any) -->
                        <li v-if="props.product?.category?.parent_recursive">
                            <Link
                                :href="
                                    route
                                        ? route(
                                              'category.show',
                                              props.product.category
                                                  .parent_recursive.slug,
                                          )
                                        : `/category/${props.product.category.parent_recursive.slug}`
                                "
                                class="hover:text-primary transition-colors"
                            >
                                {{
                                    props.product.category.parent_recursive.name
                                }}
                            </Link>
                        </li>

                        <li
                            v-if="props.product?.category?.parent_recursive"
                            class="text-gray-300 pointer-events-none"
                        >
                            /
                        </li>

                        <!-- Category -->
                        <li v-if="props.product?.category">
                            <Link
                                :href="
                                    route
                                        ? route(
                                              'category.show',
                                              props.product.category.slug,
                                          )
                                        : `/category/${props.product.category.slug}`
                                "
                                class="hover:text-primary transition-colors"
                            >
                                {{ props.product.category.name }}
                            </Link>
                        </li>

                        <li class="text-gray-300 pointer-events-none">/</li>

                        <!-- Current product -->
                        <li
                            aria-current="page"
                            class="text-gray-900 font-medium truncate max-w-[200px] md:max-w-xs"
                        >
                            {{ props.product.name }}
                        </li>
                    </ol>
                </nav>

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12">
                    <!-- Left Column: Image Gallery -->
                    <div class="lg:col-span-6 xl:col-span-6">
                        <div class="sticky top-20">
                            <ImageGallery
                                :images="productImages"
                                :selected-variation-image="
                                    selectedVariationImage
                                "
                                :video="
                                    product.youtube_video ||
                                    product.upload_video
                                "
                            />
                        </div>
                    </div>

                    <!-- Right Column: Product Info + Similar Products -->
                    <div class="lg:col-span-6 xl:col-span-6">
                        <ProductInfo
                            :product="product"
                            @variation-change="handleVariationChange"
                            :settings="settings"
                        />
                    </div>
                </div>

                <div class="flex flex-col lg:flex-row gap-8 mt-16 md:mt-24">
                    <!-- Tabbed product details (specification / description / warranty / reviews) -->
                    <div class="w-full lg:w-3/4 mx-auto">
                        <div
                            class="border-b border-gray-200 mb-8 sticky top-[70px] z-20 bg-gray-50/95 backdrop-blur-sm -mx-4 px-4 md:mx-0 md:px-0"
                        >
                            <nav
                                class="flex items-center gap-6 overflow-x-auto no-scrollbar"
                                role="tablist"
                                aria-label="Product sections"
                            >
                                <button
                                    v-if="hasSpecification"
                                    @click.prevent="scrollToSection('specs')"
                                    class="pb-4 text-sm font-semibold transition-all whitespace-nowrap border-b-2"
                                    :class="[
                                        activeTab === 'specs'
                                            ? 'border-primary text-primary'
                                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                                    ]"
                                >
                                    Specification
                                </button>

                                <!-- <button
                                    v-if="product.description"
                                    @click.prevent="scrollToSection('description')"
                                    class="pb-4 text-sm font-semibold transition-all whitespace-nowrap border-b-2"
                                    :class="[
                                        activeTab === 'description'
                                            ? 'border-primary text-primary'
                                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                                    ]"
                                >
                                    Description
                                </button> -->

                                <button
                                    @click.prevent="scrollToSection('reviews')"
                                    class="pb-4 text-sm font-semibold transition-all whitespace-nowrap border-b-2"
                                    :class="[
                                        activeTab === 'reviews'
                                            ? 'border-primary text-primary'
                                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                                    ]"
                                >
                                    Reviews ({{ reviews.length }})
                                </button>
                            </nav>
                        </div>

                        <!-- Specifications -->
                        <section
                            v-if="hasSpecification"
                            ref="specsSection"
                            id="specs"
                            class="mb-16 scroll-mt-28"
                        >
                            <h2
                                class="text-2xl font-bold text-gray-900 mb-6 flex items-center gap-2"
                            >
                                Specifications
                            </h2>

                            <div
                                class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm"
                            >
                                <table class="w-full text-sm text-left">
                                    <tbody class="divide-y divide-gray-100">
                                        <tr
                                            v-for="(
                                                row, idx
                                            ) in product.specification"
                                            :key="idx"
                                            class="group hover:bg-gray-50 transition-colors"
                                        >
                                            <td
                                                class="px-6 py-4 w-1/3 text-gray-500 font-medium bg-gray-50/50 group-hover:bg-gray-50"
                                            >
                                                {{ row.title }}
                                            </td>
                                            <td
                                                class="px-6 py-4 font-semibold text-gray-900"
                                            >
                                                {{ row.value }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </section>

                        <!-- Description -->
                        <!-- <section
                            v-if="product.description"
                            ref="descriptionSection"
                            id="description"
                            class="mb-16 scroll-mt-28"
                        >
                            <h2 class="text-2xl font-bold text-gray-900 mb-6">
                                Descriptions
                            </h2>
                            <div
                                class="prose prose-lg max-w-none text-gray-600 leading-relaxed bg-white p-6 rounded-xl border border-gray-100 shadow-sm"
                                v-html="product.description"
                            ></div>
                        </section> -->

                        <!-- Reviews (anchor target) -->
                        <section
                            ref="reviewsSection"
                            id="reviews"
                            class="mb-16 scroll-mt-28"
                        >
                            <ProductReviews
                                :product-id="product.id"
                                :reviews="reviews"
                                :review-stats="reviewStats"
                                :can-review="canReview"
                            />
                        </section>
                    </div>

                    <!-- recent view products (display as a vertical list on right side) -->
                    <!-- recent view products (display as a vertical list on right side) -->
                    <div
                        v-if="recentProducts && recentProducts.length"
                        class="mt-6 w-full lg:w-1/3"
                    >
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">
                            Recently Viewed
                        </h3>
                        <div class="space-y-3">
                            <div
                                v-for="rp in recentProducts"
                                :key="rp.id"
                                class="flex items-center gap-3 bg-white rounded-lg border border-gray-100 p-3"
                            >
                                <ProductCardRelated :product="rp" />
                            </div>
                        </div>
                    </div>
                </div>
                <RelatedProducts :products="relatedProducts" />
            </div>
        </div>
    </FrontendLayout>
</template>
