<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { ref, onMounted, defineProps, computed, watch } from "vue";
import { Head, Link, router } from "@inertiajs/vue3";
import axios from "axios";
import { toast } from "@steveyuowo/vue-hot-toast";
import { X } from "lucide-vue-next";

const props = defineProps({
    products: {
        type: Object,
        required: true,
    },
    users: {
        type: Array,
        required: true,
    },
});

// State management
const searchQuery = ref("");
const selectedCategory = ref("");
const cartData = ref(null);
const selectedVariations = ref({});
const showVariationModal = ref(false);
const currentProduct = ref(null);
const selectedAttributeValues = ref({});
const loading = ref(false);
const cartLoading = ref(false);
const userId = ref(null); // Initialize as null, will be set in onMounted
const currentPage = ref(props.products?.current_page || 1);
const perPage = ref(props.products?.per_page || 10);

// New state for user selection and order form
const selectedUser = ref(null);
const userSearchQuery = ref("");
const showUserModal = ref(false);
const newUser = ref({
    name: "",
    email: "",
    password: "",
    password_confirmation: "",
    phone_number: "",
});
const shippingForm = ref({
    customer_name: "",
    customer_phone: "",
    shipping_address: "Offline Store",
    shipping_cost: 50,
    area: "inside_dhaka",
    payment_method: "cod",
    payment_status: "unpaid",
    // POS specific discount fields
    pos_discount: 0.0,
    discount_type: "fixed", // 'fixed' or 'percentage'
});

// Computed properties
const filteredProducts = computed(() => {
    if (!props.products?.data) return [];
    return props.products.data;
});

const categories = computed(() => {
    if (!props.products?.data) return [];
    const cats = [
        ...new Set(
            props.products.data.map((p) => p.category?.name).filter(Boolean)
        ),
    ];
    return cats;
});

const cartTotal = computed(() => {
    return cartData.value ? parseFloat(cartData.value.total) : 0;
});

// Compute POS discount amount depending on discount_type
const posDiscountAmount = computed(() => {
    const base = cartTotal.value + (shippingForm.value.shipping_cost || 0);
    const val = parseFloat(shippingForm.value.pos_discount) || 0;

    if (!val || val <= 0) return 0;

    if (shippingForm.value.discount_type === "percentage") {
        // treat pos_discount as percentage (0-100)
        return (base * Math.min(val, 100)) / 100;
    }

    // fixed amount
    return Math.min(val, base);
});

const orderGrandTotal = computed(() => {
    const base = cartTotal.value + (shippingForm.value.shipping_cost || 0);
    const discount = posDiscountAmount.value || 0;
    return Math.max(0, base - discount);
});

const cartItemsCount = computed(() => {
    return cartData.value ? cartData.value.items_count : 0;
});

const cartItems = computed(() => {
    return cartData.value ? cartData.value.items : [];
});

const paginationInfo = computed(() => ({
    current_page: props.products?.current_page || 1,
    last_page: props.products?.last_page || 1,
    per_page: props.products?.per_page || 10,
    total: props.products?.total || 0,
    from: props.products?.from || 0,
    to: props.products?.to || 0,
}));

const filteredUsers = computed(() => {
    if (!props.users) return [];
    return props.users.filter(
        (user) =>
            user.name
                ?.toLowerCase()
                .includes(userSearchQuery.value.toLowerCase()) ||
            user.email
                ?.toLowerCase()
                .includes(userSearchQuery.value.toLowerCase()) ||
            user.phone_number
                ?.toLowerCase()
                .includes(userSearchQuery.value.toLowerCase())
    );
});

// Extract unique attributes from current product variations
const productAttributes = computed(() => {
  if (!currentProduct.value?.variations) return [];

  const attributeMap = new Map();

  currentProduct.value.variations.forEach(variation => {
    variation.attributes?.forEach(attr => {
      const attrName = attr.value?.attribute?.name;
      const attrValue = attr.value?.value;
      const attrValueId = attr.value?.id;
      const attrImage = attr.value?.image; // If attribute has image (like color swatches)

      if (attrName && attrValue) {
        if (!attributeMap.has(attrName)) {
          attributeMap.set(attrName, {
            name: attrName,
            attributeId: attr.value?.attribute?.id,
            values: new Map()
          });
        }

        if (!attributeMap.get(attrName).values.has(attrValueId)) {
          attributeMap.get(attrName).values.set(attrValueId, {
            id: attrValueId,
            value: attrValue,
            image: attrImage
          });
        }
      }
    });
  });

  // Convert to array format
  return Array.from(attributeMap.values()).map(attr => ({
    name: attr.name,
    attributeId: attr.attributeId,
    values: Array.from(attr.values.values())
  }));
});

// Find matching variation based on selected attributes
const matchedVariation = computed(() => {
  if (!currentProduct.value?.variations || !productAttributes.value.length) return null;

  // Check if all attributes are selected
  const allSelected = productAttributes.value.every(attr =>
    selectedAttributeValues.value[attr.name] !== undefined
  );

  if (!allSelected) return null;

  // Find variation that matches all selected attribute values
  const matched = currentProduct.value.variations.find(variation => {
    return productAttributes.value.every(attr => {
      const selectedValueId = selectedAttributeValues.value[attr.name];
      return variation.attributes?.some(
        vAttr => vAttr.value?.attribute?.name === attr.name && vAttr.value?.id === selectedValueId
      );
    });
  });

  // Debug logging
  if (matched) {
    console.log('🔍 Matched Variation Found:', {
      variation_id: matched.id,
      variation_price: matched.price,
      variation_price_type: typeof matched.price,
      product_price: currentProduct.value.price,
      product_price_type: typeof currentProduct.value.price,
      total_price: (matched.price || 0) + (currentProduct.value.price || 0),
      raw_variation: matched,
      raw_product: currentProduct.value
    });
  }

  return matched;
});

// Check if an attribute value is available based on current selections
const isAttributeValueAvailable = (attrName, valueId) => {
  if (!currentProduct.value?.variations) return false;

  // Get other selected attributes (excluding current one)
  const otherSelections = { ...selectedAttributeValues.value };
  delete otherSelections[attrName];

  // Find if any variation exists with this value and all other selected values
  return currentProduct.value.variations.some(variation => {
    // Check if this variation has the value we're checking
    const hasThisValue = variation.attributes?.some(
      attr => attr.value?.attribute?.name === attrName && attr.value?.id === valueId
    );

    if (!hasThisValue) return false;

    // Check if this variation matches all other selected attributes
    return Object.entries(otherSelections).every(([otherAttrName, otherValueId]) => {
      return variation.attributes?.some(
        attr => attr.value?.attribute?.name === otherAttrName && attr.value?.id === otherValueId
      );
    });
  });
};

// Methods
const handleSearch = () => {
    loading.value = true;
    router.get(
        "/admin/pos",
        {
            search: searchQuery.value,
            page: 1,
            per_page: perPage.value,
            category: selectedCategory.value,
        },
        {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                loading.value = false;
                currentPage.value = 1;
            },
            onError: () => {
                loading.value = false;
            },
        }
    );
};

const changePage = (page) => {
    if (page >= 1 && page <= paginationInfo.value.last_page) {
        loading.value = true;
        router.get(
            "/admin/pos",
            {
                search: searchQuery.value,
                page: page,
                per_page: perPage.value,
                category: selectedCategory.value,
            },
            {
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => {
                    loading.value = false;
                    currentPage.value = page;
                },
                onError: () => {
                    loading.value = false;
                },
            }
        );
    }
};

const changePerPage = (newPerPage) => {
    perPage.value = parseInt(newPerPage);
    loading.value = true;
    router.get(
        "/admin/pos",
        {
            search: searchQuery.value,
            page: 1,
            per_page: perPage.value,
            category: selectedCategory.value,
        },
        {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                loading.value = false;
                currentPage.value = 1;
            },
            onError: () => {
                loading.value = false;
            },
        }
    );
};

const handleCategoryChange = () => {
    loading.value = true;
    router.get(
        "/admin/pos",
        {
            search: searchQuery.value,
            page: 1,
            per_page: perPage.value,
            category: selectedCategory.value,
        },
        {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                loading.value = false;
                currentPage.value = 1;
            },
            onError: () => {
                loading.value = false;
            },
        }
    );
};

const fetchCartData = async () => {
    try {
        cartLoading.value = true;
        const response = await axios.get(`/api/cart?user_id=${userId.value}`);
        if (response.data.success) {
            cartData.value = response.data.data;
        } else {
            cartData.value = null;
            if (response.data.error_code !== "CART_NOT_FOUND") {
                toast.error(
                    response.data.message || "Failed to fetch cart data"
                );
            }
        }
    } catch (error) {
        console.error("Error fetching cart data:", error);
        cartData.value = null;
        const errorMessage = error.response?.data?.message;
        const errorCode = error.response?.data?.error_code;
        if (errorCode !== "CART_NOT_FOUND") {
            toast.error(errorMessage || "Error fetching cart data");
        }
    } finally {
        cartLoading.value = false;
    }
};

const openVariationModal = (product) => {
  console.log('🚀 Opening Variation Modal:', {
    product_id: product.id,
    product_name: product.name,
    product_price: product.price,
    product_price_type: typeof product.price,
    product_type: product.type,
    variations_count: product.variations?.length,
    first_variation: product.variations?.[0],
    all_variations: product.variations
  });

  if (product.type === 'variable' && product.variations.length > 0) {
    currentProduct.value = product;
    selectedAttributeValues.value = {}; // Reset selections
    showVariationModal.value = true;
  } else {
    addToCart(product);
  }
};

// Select an attribute value
const selectAttributeValue = (attrName, valueId) => {
  selectedAttributeValues.value = {
    ...selectedAttributeValues.value,
    [attrName]: valueId
  };
};

// Add matched variation to cart
const addMatchedVariationToCart = () => {
  if (matchedVariation.value) {
    addToCart(currentProduct.value, matchedVariation.value);
  }
};

const addToCart = async (product, variation = null) => {
    try {
        cartLoading.value = true;
        const payload = {
            product_id: product.id,
            quantity: 1,
            product_variation_id: variation ? variation.id : null,
            user_id: userId.value,
        };

        const response = await axios.post("/api/cart/add", payload);

        if (response.data.success) {
            await fetchCartData();
            showVariationModal.value = false;
            toast.success(
                response.data.message || "Item added to cart successfully"
            );
        } else {
            toast.error(response.data.message || "Failed to add item to cart");
        }
    } catch (error) {
        console.error("Error adding to cart:", error);
        toast.error(
            error.response?.data?.message || "Failed to add item to cart"
        );
    } finally {
        cartLoading.value = false;
    }
};

const updateQuantity = async (item, increment) => {
    try {
        cartLoading.value = true;
        const payload = {
            item_id: item.id,
            quantity: increment,
            user_id: userId.value,
        };

        const response = await axios.put("/api/cart/update-quantity", payload);

        if (response.data.success) {
            await fetchCartData();
            toast.success(
                response.data.message || "Quantity updated successfully"
            );
        } else {
            toast.error(response.data.message || "Failed to update quantity");
        }
    } catch (error) {
        console.error("Error updating quantity:", error);
        toast.error(
            error.response?.data?.message || "Failed to update quantity"
        );
    } finally {
        cartLoading.value = false;
    }
};

const incrementQuantity = async (item) => {
    await updateQuantity(item, 1);
};

const decrementQuantity = async (item) => {
    await updateQuantity(item, -1);
};

const removeFromCart = async (item) => {
    try {
        cartLoading.value = true;
        const payload = {
            item_id: item.id,
        };

        const response = await axios.delete("/api/cart/cartitem/destroy", {
            data: payload,
        });

        if (response.data) {
            // Check if this was the last item before fetching
            const wasLastItem = cartItems.value.length === 0;

            if (wasLastItem) {
                // Clear local cart state instead of fetching
                cartItems.value = [];
                cartTotal.value = 0;
                // Don't call fetchCartData() here
            } else {
                await fetchCartData();
            }

            toast.success(
                response.data.message || "Item removed from cart successfully"
            );
        } else {
            toast.error(
                response.data.message || "Failed to remove item from cart"
            );
        }
    } catch (error) {
        console.error("Error removing from cart:", error);
        toast.error(
            error.response?.data?.message || "Failed to remove item from cart"
        );
    } finally {
        cartLoading.value = false;
    }
};

const getVariationAttributes = (variation) => {
    return variation.attributes.map((attr) => ({
        name: attr.value.attribute.name,
        value: attr.value.value,
    }));
};

const formatPrice = (price) => {
    return parseFloat(price).toFixed(2);
};

const getProductFromCart = (cartItem) => {
    const product = props.products?.data?.find(
        (p) => p.id === cartItem.product_id
    );
    if (!product) return null;

    if (cartItem.variation_id) {
        const variation = product.variations?.find(
            (v) => v.id === cartItem.variation_id
        );
        return {
            ...product,
            selectedVariation: variation,
            attributes: variation ? getVariationAttributes(variation) : [],
        };
    }

    return product;
};

// Modified selectUser to update userId and fetch cart data
const selectUser = async (user) => {
    userId.value = user.id;
    selectedUser.value = user;
    shippingForm.value.customer_name = user.name;
    shippingForm.value.customer_phone = user.phone_number || "01000000000";
    shippingForm.value.shipping_address = "Offline Store";
    userSearchQuery.value = "";
    await fetchCartData();
};

// Modified clearUserSelection to reset to Walk-in Customer
const clearUserSelection = async () => {
    const walkInCustomer = props.users.find(
        (user) => user.email === "walkin_customer@gmail.com" || user.email === "working_customer@gmail.com" || user.id === 7
    );

    if (walkInCustomer) {
        userId.value = walkInCustomer.id;
        selectedUser.value = walkInCustomer;
        shippingForm.value.customer_name = walkInCustomer.name;
        shippingForm.value.customer_phone =
            walkInCustomer.phone_number || "01000000000";
        shippingForm.value.shipping_address = "Offline Store";
    } else {
        userId.value = null;
        selectedUser.value = null;
        shippingForm.value.customer_name = "Walk-in Customer";
        shippingForm.value.customer_phone = "01000000000";
        shippingForm.value.shipping_address = "Offline Store";
    }

    userSearchQuery.value = "";
    await fetchCartData();
};

// Updated createUser to fetch user details via /api/auth/me
const createUser = async () => {
    try {
        cartLoading.value = true;
        const response = await axios.post("/api/auth/register", newUser.value);

        if (response.data.access_token) {
            // Fetch user details using /api/auth/me
            const userResponse = await axios.get("/api/auth/me", {
                headers: {
                    Authorization: `Bearer ${response.data.access_token}`,
                },
            });

            let newUserData;
            if (userResponse.data && userResponse.data.id) {
                newUserData = userResponse.data; // Direct response: { id, name, email, phone_number, ... }
            } else {
                // Fallback: Use form data if /api/auth/me fails
                newUserData = {
                    id: null,
                    name: newUser.value.name,
                    email: newUser.value.email,
                    phone_number: newUser.value.phone_number,
                };
                toast.warn(
                    "User created, but ID not retrieved. Please refresh the page."
                );
            }

            await selectUser(newUserData);
            showUserModal.value = false;
            resetNewUserForm();
            toast.success("User created successfully");

            // Refresh user list to include new user
            router.reload({ only: ["users"], preserveState: true });
        } else {
            toast.error(response.data.message || "Failed to create user");
        }
    } catch (error) {
        console.error("Error creating user:", error);
        toast.error(error.response?.data?.message || "Failed to create user");
    } finally {
        cartLoading.value = false;
    }
};

const resetNewUserForm = () => {
    newUser.value = {
        name: "",
        email: "",
        password: "",
        password_confirmation: "",
        phone_number: "",
    };
};

const handleCheckout = async () => {
    try {
        cartLoading.value = true;

        // Validate discount before checkout
        const base = cartTotal.value + (shippingForm.value.shipping_cost || 0);
        const discountValue = parseFloat(shippingForm.value.pos_discount) || 0;

        if (
            shippingForm.value.discount_type === "percentage" &&
            discountValue > 100
        ) {
            toast.error("Discount percentage cannot exceed 100%");
            cartLoading.value = false;
            return;
        }

        if (
            shippingForm.value.discount_type === "fixed" &&
            discountValue > base
        ) {
            toast.error(
                "Discount cannot exceed total amount of ৳" + formatPrice(base)
            );
            cartLoading.value = false;
            return;
        }

        const items = cartItems.value.map((item) => ({
            product_id: item.product_id,
            product_variation_id: item.variation_id || null,
            quantity: item.quantity,
        }));

        const sessionId = selectedUser.value
            ? null
            : `anon-${btoa(navigator.userAgent).slice(0, 20)}`;

        const payload = {
            user_id: selectedUser.value?.id || null,
            session_id: sessionId,
            customer_name: shippingForm.value.customer_name,
            customer_phone: shippingForm.value.customer_phone,
            shipping_address: shippingForm.value.shipping_address,
            shipping_cost: shippingForm.value.shipping_cost,
            area: shippingForm.value.area,
            payment_method: shippingForm.value.payment_method,
            payment_status: shippingForm.value.payment_status,
            items: items,
            // POS discount values
            pos_discount: shippingForm.value.pos_discount || 0,
            discount_type: shippingForm.value.discount_type || null,
        };

        const response = await axios.post("/api/orders", payload);

        await fetchCartData();
        await clearUserSelection();
        shippingForm.value.shipping_cost = 50;
        shippingForm.value.area = "inside_dhaka";
        shippingForm.value.payment_method = "cod";
        shippingForm.value.payment_status = "unpaid";
        toast.success(response.data.message || "Order created successfully");
    } catch (error) {
        console.error("Error creating order:", error);
        toast.error(error.response?.data?.message || "Failed to create order");
    } finally {
        cartLoading.value = false;
    }
};

// Watch for POS discount to ensure it doesn't exceed total
watch(
    () => shippingForm.value.pos_discount,
    (newValue) => {
        const base = cartTotal.value + (shippingForm.value.shipping_cost || 0);

        if (shippingForm.value.discount_type === "percentage") {
            // For percentage, max is 100%
            if (newValue > 100) {
                shippingForm.value.pos_discount = 100;
                toast.error("Discount percentage cannot exceed 100%");
            }
        } else {
            // For fixed amount, cannot exceed base total
            if (newValue > base) {
                shippingForm.value.pos_discount = base;
                toast.error(
                    "Discount cannot exceed total amount of ৳" +
                        formatPrice(base)
                );
            }
        }
    }
);

// Watch for search changes with debounce
let searchTimeout;
watch(searchQuery, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        handleSearch();
    }, 500);
});

// Watch for category changes
watch(selectedCategory, () => {
    handleCategoryChange();
});

// Watch for userId changes to fetch cart data
watch(userId, () => {
    fetchCartData();
});

// Mount
onMounted(async () => {
    const walkInCustomer = props.users.find(
        (user) => user.email === "walkin_customer@gmail.com" || user.email === "working_customer@gmail.com" || user.id === 7
    );

    if (walkInCustomer) {
        userId.value = walkInCustomer.id;
        selectedUser.value = walkInCustomer;
        shippingForm.value.customer_name = walkInCustomer.name;
        shippingForm.value.customer_phone =
            walkInCustomer.phone_number || "01000000000";
        shippingForm.value.shipping_address = "Offline Store";
    } else {
        userId.value = null;
        selectedUser.value = null;
        shippingForm.value.customer_name = "Walk-in Customer";
        shippingForm.value.customer_phone = "01000000000";
        shippingForm.value.shipping_address = "Offline Store";
    }

    await fetchCartData();

    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get("search")) {
        searchQuery.value = urlParams.get("search");
    }
    if (urlParams.get("per_page")) {
        perPage.value = parseInt(urlParams.get("per_page"));
    }
    if (urlParams.get("category")) {
        selectedCategory.value = urlParams.get("category");
    }
});
</script>

<template>
    <Head title="Point of Sale" />
    <AdminLayout>
        <div class="min-h-screen bg-gray-50">
            <!-- Header -->
            <div class="bg-white shadow-sm border-b">
                <div class="px-2 py-4">
                    <div class="flex items-center justify-between">
                        <h1 class="text-2xl font-bold text-gray-900">
                            Point of Sale
                        </h1>
                        <div class="flex items-center space-x-3">
                            <div
                                class="px-3 py-1 rounded-full text-sm font-medium border border-gray-200 text-gray-700 bg-white"
                            >
                                {{ cartItemsCount }} items
                            </div>
                            <div
                                class="px-3 py-1 rounded-full text-sm font-medium border border-gray-200 text-gray-700 bg-white"
                            >
                                ৳{{ formatPrice(cartTotal) }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex flex-col lg:flex-row h-full">
                <!-- Products Section -->
                <div class="w-full xl:w-2/3 p-3">
                    <!-- Search and Filters -->
                    <div class="bg-white rounded-lg shadow-sm border p-4 mb-6">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-4 flex-1">
                                <div class="flex-1 relative">
                                    <input
                                        v-model="searchQuery"
                                        type="text"
                                        placeholder="Search products by name or code..."
                                        class="w-full pl-4 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    />
                                </div>
                            </div>

                            <!-- Per Page Selector -->
                            <div class="ml-4 flex items-center space-x-2">
                                <label class="text-sm text-gray-600"
                                    >Show:</label
                                >
                                <select
                                    :value="perPage"
                                    @change="changePerPage($event.target.value)"
                                    class="px-3 py-1 border border-gray-300 rounded text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                >
                                    <option value="10">10</option>
                                    <option value="25">25</option>
                                    <option value="50">50</option>
                                    <option value="100">100</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Products Grid -->
                    <div class="bg-white rounded-lg shadow-sm border">
                        <div
                            class="p-4 border-b flex justify-between items-center"
                        >
                            <h2 class="text-lg font-semibold text-slate-900">
                                Products
                            </h2>
                            <div class="text-sm text-slate-500">
                                Showing {{ paginationInfo.from }}-{{
                                    paginationInfo.to
                                }}
                                of {{ paginationInfo.total }} results
                            </div>
                        </div>

                        <!-- Loading State for Products -->
                        <div v-if="loading" class="p-12 text-center">
                            <div
                                class="animate-spin rounded-full h-8 w-8 border-b-2 border-sky-600 mx-auto mb-4"
                            ></div>
                            <p class="text-slate-500">Loading products...</p>
                        </div>

                        <div
                            v-else
                            class="p-4 grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-4"
                        >
                            <div
                                v-for="product in filteredProducts"
                                :key="product.id"
                                class="bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow duration-200 overflow-hidden border border-gray-100"
                            >
                                <img
                                    :src="product.feature_image"
                                    :alt="product.name"
                                    class="aspect-square object-cover"
                                    v-if="product.feature_image"
                                />
                                <div class="p-4">
                                    <div
                                        class="flex items-start justify-between mb-2"
                                    >
                                        <div>
                                            <h3
                                                class="font-medium text-[12px] text-gray-900"
                                            >
                                                {{ product.name }}
                                            </h3>
                                            <p
                                                class="text-sm text-gray-500 mt-1"
                                            >
                                                {{
                                                    product.category?.name ||
                                                    "N/A"
                                                }}
                                            </p>
                                        </div>
                                    </div>
                                    <!-- <div
                                        class="text-sm font-semibold text-gray-900"
                                    >
                                        ৳{{ formatPrice(product.price) }}
                                    </div> -->

                                    <div
                                        class="mt-4 flex flex-col gap-2 items-center justify-between"
                                    >
                                        <span
                                            v-if="product.is_pre_order"
                                            class="bg-blue-50 text-blue-700 border-blue-200 text-xs font-medium px-2 py-1 rounded-full border"
                                        >
                                            Pre-Order
                                        </span>
                                        <span
                                            v-else
                                            :class="{
                                                'bg-green-50 text-green-700 border-green-200':
                                                    product.stock > 10,
                                                'bg-yellow-50 text-yellow-700 border-yellow-200':
                                                    product.stock > 0 &&
                                                    product.stock <= 10,
                                                'bg-red-50 text-red-700 border-red-200':
                                                    product.stock === 0,
                                            }"
                                            class="text-xs font-medium px-2 py-1 rounded-full border"
                                        >
                                            {{ product.stock }} in stock
                                        </span>
                                        <button
                                            @click="openVariationModal(product)"
                                            :disabled="
                                                (product.stock === 0 &&
                                                    !product.is_pre_order) ||
                                                cartLoading
                                            "
                                            class="px-2 py-1 rounded-lg text-sm font-medium transition-colors"
                                            :class="[
                                                product.stock === 0 &&
                                                !product.is_pre_order
                                                    ? 'bg-gray-100 text-gray-400 cursor-not-allowed'
                                                    : cartLoading
                                                    ? 'bg-gray-100 text-gray-500'
                                                    : 'bg-gray-900 text-white hover:bg-gray-800',
                                            ]"
                                        >
                                            {{
                                                cartLoading
                                                    ? "Adding..."
                                                    : product.stock === 0 &&
                                                      !product.is_pre_order
                                                    ? "Out of Stock"
                                                    : product.type ===
                                                          "variable" &&
                                                      product.variations &&
                                                      product.variations.length >
                                                          0
                                                    ? "Select Options"
                                                    : "Add to Cart"
                                            }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Pagination -->
                        <div
                            class="bg-white px-4 py-3 border-t border-gray-200 sm:px-2"
                        >
                            <div class="flex items-center justify-between">
                                <div
                                    class="flex-1 flex justify-between sm:hidden"
                                >
                                    <button
                                        @click="
                                            changePage(
                                                paginationInfo.current_page - 1
                                            )
                                        "
                                        :disabled="
                                            paginationInfo.current_page <= 1
                                        "
                                        class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                                    >
                                        Previous
                                    </button>
                                    <button
                                        @click="
                                            changePage(
                                                paginationInfo.current_page + 1
                                            )
                                        "
                                        :disabled="
                                            paginationInfo.current_page >=
                                            paginationInfo.last_page
                                        "
                                        class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                                    >
                                        Next
                                    </button>
                                </div>
                                <div
                                    class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between"
                                >
                                    <div>
                                        <p class="text-sm text-gray-700">
                                            Showing
                                            <span class="font-medium">{{
                                                paginationInfo.from
                                            }}</span>
                                            to
                                            <span class="font-medium">{{
                                                paginationInfo.to
                                            }}</span>
                                            of
                                            <span class="font-medium">{{
                                                paginationInfo.total
                                            }}</span>
                                            results
                                        </p>
                                    </div>
                                    <div>
                                        <nav
                                            class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px"
                                            aria-label="Pagination"
                                        >
                                            <button
                                                @click="
                                                    changePage(
                                                        paginationInfo.current_page -
                                                            1
                                                    )
                                                "
                                                :disabled="
                                                    paginationInfo.current_page <=
                                                    1
                                                "
                                                class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                                            >
                                                Previous
                                            </button>
                                            <template
                                                v-for="page in Array.from(
                                                    {
                                                        length: paginationInfo.last_page,
                                                    },
                                                    (_, i) => i + 1
                                                )"
                                                :key="page"
                                            >
                                                <button
                                                    v-if="
                                                        page <= 5 ||
                                                        page >
                                                            paginationInfo.last_page -
                                                                5 ||
                                                        Math.abs(
                                                            page -
                                                                paginationInfo.current_page
                                                        ) <= 2
                                                    "
                                                    @click="changePage(page)"
                                                    :class="
                                                        page ===
                                                        paginationInfo.current_page
                                                            ? 'z-10 bg-indigo-50 border-indigo-500 text-indigo-600'
                                                            : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'
                                                    "
                                                    class="relative inline-flex items-center px-4 py-2 border text-sm font-medium"
                                                >
                                                    {{ page }}
                                                </button>
                                                <span
                                                    v-else-if="
                                                        (page === 6 &&
                                                            paginationInfo.current_page >
                                                                8) ||
                                                        (page ===
                                                            paginationInfo.last_page -
                                                                5 &&
                                                            paginationInfo.current_page <
                                                                paginationInfo.last_page -
                                                                    7)
                                                    "
                                                    class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700"
                                                >
                                                    ...
                                                </span>
                                            </template>
                                            <button
                                                @click="
                                                    changePage(
                                                        paginationInfo.current_page +
                                                            1
                                                    )
                                                "
                                                :disabled="
                                                    paginationInfo.current_page >=
                                                    paginationInfo.last_page
                                                "
                                                class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                                            >
                                                Next
                                            </button>
                                        </nav>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Cart Section -->
                <div
                    class="w-full xl:w-1/3 bg-white border-l shadow-lg relative"
                >
                    <!-- Cart Loading Overlay -->
                    <div
                        v-if="cartLoading"
                        class="absolute inset-0 bg-white bg-opacity-75 flex items-center justify-center z-10"
                    >
                        <div class="text-center">
                            <div
                                class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto mb-4"
                            ></div>
                            <p class="text-gray-500">Updating cart...</p>
                        </div>
                    </div>

                    <div class="p-4 border-b">
                        <div class="flex items-center justify-between">
                            <h2 class="text-lg font-semibold text-gray-900">
                                Shopping Cart
                            </h2>
                        </div>
                    </div>

                    <!-- User Selection -->
                    <div class="p-4 border-b">
                        <h3 class="text-sm font-medium text-gray-700 mb-2">
                            Select Customer
                        </h3>
                        <div class="relative">
                            <input
                                v-model="userSearchQuery"
                                type="text"
                                placeholder="Search users by name, phone or email..."
                                class="w-full pl-4 pr-10 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            />
                            <button
                                v-if="selectedUser"
                                @click="clearUserSelection"
                                class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                            >
                                ×
                            </button>
                        </div>
                        <div
                            v-if="userSearchQuery || !selectedUser"
                            class="mt-2 max-h-40 overflow-y-auto bg-white border rounded-lg"
                        >
                            <div
                                v-for="user in filteredUsers"
                                :key="user.id"
                                @click="selectUser(user)"
                                class="px-4 py-2 hover:bg-gray-100 cursor-pointer"
                            >
                                <p class="text-sm font-medium">
                                    {{ user.name }}
                                </p>
                                <p class="text-xs text-gray-500">
                                    {{ user.email }}
                                </p>
                            </div>
                        </div>
                        <button
                            @click="showUserModal = true"
                            class="mt-2 w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition-colors"
                        >
                            Create New User
                        </button>
                    </div>

                    <!-- Shipping Details -->
                    <div class="p-4 border-b bg-white">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-sm font-medium text-gray-700">
                                Shipping Details
                            </h3>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <!-- Customer Details -->
                            <div>
                                <label class="text-xs text-gray-500 block mb-1"
                                    >Name</label
                                >
                                <input
                                    v-model="shippingForm.customer_name"
                                    type="text"
                                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-1 focus:ring-gray-400 focus:border-gray-400"
                                />
                            </div>

                            <div>
                                <label class="text-xs text-gray-500 block mb-1"
                                    >Phone</label
                                >
                                <input
                                    v-model="shippingForm.customer_phone"
                                    type="text"
                                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-1 focus:ring-gray-400 focus:border-gray-400"
                                />
                            </div>

                            <div>
                                <label class="text-xs text-gray-500 block mb-1"
                                    >Area</label
                                >
                                <select
                                    v-model="shippingForm.area"
                                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-1 focus:ring-gray-400 focus:border-gray-400"
                                >
                                    <option value="inside_dhaka">
                                        Inside Dhaka
                                    </option>
                                    <option value="outside_dhaka">
                                        Outside Dhaka
                                    </option>
                                </select>
                            </div>

                            <div>
                                <label class="text-xs text-gray-500 block mb-1"
                                    >Payment Method</label
                                >
                                <select
                                    v-model="shippingForm.payment_method"
                                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-1 focus:ring-gray-400 focus:border-gray-400"
                                >
                                    <option value="cod">
                                        Cash on Delivery
                                    </option>
                                    <option value="card">Card Payment</option>
                                    <option value="mobile">
                                        Mobile Payment
                                    </option>
                                </select>
                            </div>

                            <div>
                                <label class="text-xs text-gray-500 block mb-1"
                                    >Payment Status</label
                                >
                                <select
                                    v-model="shippingForm.payment_status"
                                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-1 focus:ring-gray-400 focus:border-gray-400"
                                >
                                    <option value="unpaid">Unpaid</option>
                                    <option value="paid">Paid</option>
                                    <option value="refunded">Refunded</option>
                                    <option value="failed">Failed</option>
                                </select>
                            </div>

                            <div>
                                <label class="text-xs text-gray-500 block mb-1"
                                    >Shipping Cost</label
                                >
                                <input
                                    v-model.number="shippingForm.shipping_cost"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-1 focus:ring-gray-400 focus:border-gray-400"
                                />
                            </div>

                            <div class="col-span-2">
                                <label class="text-xs text-gray-500 block mb-1"
                                    >Shipping Address</label
                                >
                                <input
                                    v-model="shippingForm.shipping_address"
                                    type="text"
                                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-1 focus:ring-gray-400 focus:border-gray-400"
                                />
                            </div>
                        </div>

                        <!-- Discount Settings (Always Editable) -->
                        <div
                            class="mt-6 p-4 bg-gray-50 rounded-lg border border-gray-200"
                        >
                            <div class="flex items-center justify-between mb-3">
                                <h3 class="text-sm font-medium text-gray-700">
                                    Discount Settings
                                </h3>
                                <div class="text-xs text-gray-500">
                                    Always editable
                                </div>
                            </div>
                            <div class="flex items-end space-x-2">
                                <div class="flex-1">
                                    <label
                                        class="text-xs text-gray-500 block mb-1"
                                        >POS Discount</label
                                    >
                                    <input
                                        v-model.number="
                                            shippingForm.pos_discount
                                        "
                                        type="number"
                                        min="0"
                                        :max="
                                            shippingForm.discount_type ===
                                            'percentage'
                                                ? 100
                                                : cartTotal +
                                                  (shippingForm.shipping_cost ||
                                                      0)
                                        "
                                        step="0.01"
                                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-1 focus:ring-gray-400 focus:border-gray-400"
                                        :class="{
                                            'border-red-500':
                                                shippingForm.discount_type ===
                                                    'fixed' &&
                                                shippingForm.pos_discount >
                                                    cartTotal +
                                                        (shippingForm.shipping_cost ||
                                                            0),
                                        }"
                                        placeholder="Enter discount amount"
                                    />
                                </div>
                                <div class="w-32">
                                    <label
                                        class="text-xs text-gray-500 block mb-1"
                                        >Type</label
                                    >
                                    <select
                                        v-model="shippingForm.discount_type"
                                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-1 focus:ring-gray-400 focus:border-gray-400"
                                    >
                                        <option value="fixed">Fixed</option>
                                        <option value="percentage">
                                            Percent
                                        </option>
                                    </select>
                                </div>
                            </div>
                            <div
                                class="mt-2 text-xs"
                                :class="
                                    shippingForm.discount_type === 'fixed' &&
                                    shippingForm.pos_discount >
                                        cartTotal +
                                            (shippingForm.shipping_cost || 0)
                                        ? 'text-red-500'
                                        : 'text-gray-500'
                                "
                            >
                                {{
                                    shippingForm.discount_type === "percentage"
                                        ? "Percentage will be applied to total (Max: 100%)"
                                        : "Fixed amount will be deducted from total (Max: ৳" +
                                          formatPrice(
                                              cartTotal +
                                                  (shippingForm.shipping_cost ||
                                                      0)
                                          ) +
                                          ")"
                                }}
                            </div>
                        </div>
                    </div>

                    <!-- Cart Items -->
                    <div class="flex-1 overflow-y-auto max-h-96">
                        <div
                            v-if="cartItems.length === 0 && !cartLoading"
                            class="p-8 text-center text-gray-500"
                        >
                            <div class="w-12 h-12 mx-auto mb-4 text-gray-300">
                                🛒
                            </div>
                            <p>Your cart is empty</p>
                        </div>

                        <div v-else class="p-4 space-y-4">
                            <div
                                v-for="item in cartItems"
                                :key="item.id"
                                class="bg-gray-50 rounded-lg p-4"
                            >
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center mb-2">
                                            <img
                                                :src="item.product_image"
                                                :alt="item.product_name"
                                                class="w-10 h-10 rounded object-cover mr-3"
                                                v-if="item.product_image"
                                            />
                                            <div>
                                                <h3
                                                    class="font-medium text-gray-900 text-sm"
                                                >
                                                    {{ item.product_name }}
                                                </h3>
                                                <div
                                                    v-if="item.variation_id"
                                                    class="mt-1"
                                                >
                                                    <span
                                                        v-for="attr in getProductFromCart(
                                                            item
                                                        )?.attributes || []"
                                                        :key="attr.name"
                                                        class="inline-block bg-gray-200 text-gray-700 text-xs px-2 py-1 rounded mr-1"
                                                    >
                                                        {{ attr.name }}:
                                                        {{ attr.value }}
                                                    </span>
                                                </div>
                                                <div
                                                    v-if="item.is_pre_order"
                                                    class="mt-1"
                                                >
                                                    <span
                                                        class="inline-block bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded"
                                                    >
                                                        Pre-Order
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <p class="text-sm text-gray-500">
                                            ৳{{ formatPrice(item.price) }} each
                                        </p>
                                    </div>
                                    <button
                                        @click="removeFromCart(item)"
                                        :disabled="cartLoading"
                                        class="text-red-500 hover:text-red-700 ml-2 disabled:opacity-50"
                                    >
                                        <X />
                                    </button>
                                </div>

                                <div
                                    class="flex items-center justify-between mt-3"
                                >
                                    <div class="flex items-center space-x-2">
                                        <button
                                            @click="decrementQuantity(item)"
                                            :disabled="
                                                cartLoading ||
                                                item.quantity <= 1
                                            "
                                            class="w-8 h-8 rounded-full bg-gray-200 hover:bg-gray-300 disabled:opacity-50 flex items-center justify-center"
                                        >
                                            -
                                        </button>
                                        <span
                                            class="w-8 text-center font-medium"
                                            >{{ item.quantity }}</span
                                        >
                                        <button
                                            @click="incrementQuantity(item)"
                                            :disabled="cartLoading"
                                            class="w-8 h-8 rounded-full bg-gray-200 hover:bg-gray-300 disabled:opacity-50 flex items-center justify-center"
                                        >
                                            +
                                        </button>
                                    </div>
                                    <div class="font-medium text-gray-900">
                                        ৳{{ formatPrice(item.subtotal) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Cart Total -->
                    <div
                        v-if="cartItems.length > 0"
                        class="border-t p-5 bg-gray-50"
                    >
                        <div class="bg-white rounded-lg shadow-sm p-4">
                            <div class="mb-3 text-sm text-gray-600">
                                Order summary
                            </div>
                            <div class="space-y-3">
                                <div
                                    class="flex justify-between text-sm text-gray-700"
                                >
                                    <span>Items ({{ cartItemsCount }})</span>
                                    <span class="font-medium"
                                        >৳{{ formatPrice(cartTotal) }}</span
                                    >
                                </div>
                                <div
                                    class="flex justify-between text-sm text-gray-700"
                                >
                                    <span>Shipping</span>
                                    <span class="font-medium"
                                        >৳{{
                                            formatPrice(
                                                shippingForm.shipping_cost
                                            )
                                        }}</span
                                    >
                                </div>
                                <div
                                    class="flex justify-between text-sm text-gray-700"
                                >
                                    <span
                                        >Discount
                                        <span class="text-xs text-gray-500"
                                            >({{
                                                shippingForm.discount_type ===
                                                "percentage"
                                                    ? shippingForm.pos_discount +
                                                      "%"
                                                    : "৳" +
                                                      formatPrice(
                                                          shippingForm.pos_discount
                                                      )
                                            }})</span
                                        ></span
                                    >
                                    <span class="font-medium text-gray-800"
                                        >-৳{{
                                            formatPrice(posDiscountAmount)
                                        }}</span
                                    >
                                </div>
                                <div
                                    class="border-t pt-3 flex items-center justify-between"
                                >
                                    <span
                                        class="text-lg font-semibold text-gray-900"
                                        >Total</span
                                    >
                                    <span
                                        class="text-lg font-bold text-gray-900"
                                        >৳{{
                                            formatPrice(orderGrandTotal)
                                        }}</span
                                    >
                                </div>
                            </div>
                        </div>
                        <button
                            @click="handleCheckout"
                            :disabled="
                                cartLoading ||
                                !shippingForm.customer_name ||
                                !shippingForm.shipping_address
                            "
                            class="mt-3 w-full bg-slate-900 hover:bg-slate-800 disabled:bg-gray-300 text-white font-medium py-3 px-4 rounded-lg transition-colors"
                        >
                            Complete Sale
                        </button>
                    </div>
                </div>
            </div>

      <!-- Variation Selection Modal -->
      <div v-if="showVariationModal && currentProduct"
           class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl max-w-lg w-full mx-4 max-h-[80vh] overflow-y-auto">
          <div class="p-6">
            <!-- Header -->
            <div class="flex justify-between items-center mb-6">
              <h3 class="text-lg font-semibold text-gray-900">{{ currentProduct.name }}</h3>
              <button
                @click="showVariationModal = false"
                class="text-gray-400 hover:text-gray-600 p-1"
              >
                <X class="w-5 h-5" />
              </button>
            </div>

            <!-- Attribute Selection -->
            <div class="space-y-6">
              <div v-for="attr in productAttributes" :key="attr.name" class="border-b pb-4 last:border-b-0">
                <h4 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-3">
                  Select {{ attr.name }}
                </h4>
                <div class="flex flex-wrap gap-2">
                  <button
                    v-for="val in attr.values"
                    :key="val.id"
                    @click="selectAttributeValue(attr.name, val.id)"
                    :disabled="!isAttributeValueAvailable(attr.name, val.id)"
                    :class="[
                      'px-4 py-2 rounded-full border-2 text-sm font-medium transition-all',
                      selectedAttributeValues[attr.name] === val.id
                        ? 'border-blue-600 bg-blue-50 text-blue-700'
                        : isAttributeValueAvailable(attr.name, val.id)
                          ? 'border-gray-200 bg-white text-gray-700 hover:border-gray-400'
                          : 'border-gray-100 bg-gray-50 text-gray-300 cursor-not-allowed'
                    ]"
                  >
                    {{ val.value }}
                  </button>
                </div>
              </div>
            </div>

            <!-- Selected Variation Info -->
            <div v-if="matchedVariation" class="mt-6 p-4 bg-gray-50 rounded-lg">
              <div class="flex justify-between items-center">
                <div>
                  <p class="text-sm text-gray-500">Selected Variation</p>
                  <p class="text-lg font-bold text-gray-900">৳{{ formatPrice(parseFloat(matchedVariation.price || 0) + parseFloat(currentProduct.price || 0)) }}</p>
                  <p class="text-sm" :class="matchedVariation.stock > 0 ? 'text-green-600' : 'text-red-600'">
                    {{ matchedVariation.stock > 0 ? `${matchedVariation.stock} in stock` : 'Out of stock' }}
                  </p>
                </div>
                <button
                  @click="addMatchedVariationToCart"
                  :disabled="matchedVariation.stock === 0 || cartLoading"
                  :class="[
                    'px-6 py-3 rounded-lg font-medium transition-colors',
                    matchedVariation.stock === 0
                      ? 'bg-gray-300 text-gray-500 cursor-not-allowed'
                      : cartLoading
                        ? 'bg-blue-400 text-white'
                        : 'bg-blue-600 hover:bg-blue-700 text-white'
                  ]"
                >
                  {{ cartLoading ? 'Adding...' : matchedVariation.stock === 0 ? 'Out of Stock' : 'Add to Cart' }}
                </button>
              </div>
            </div>

            <!-- No selection message -->
            <div v-else class="mt-6 p-4 bg-gray-50 rounded-lg text-center text-gray-500">
              <p>Please select all options to see availability</p>
            </div>
          </div>
        </div>
      </div>

            <!-- User Creation Modal -->
            <div
                v-if="showUserModal"
                class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
            >
                <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-semibold">
                                Create New User
                            </h3>
                            <button
                                @click="
                                    showUserModal = false;
                                    resetNewUserForm();
                                "
                                class="text-gray-400 hover:text-gray-600"
                            >
                                ×
                            </button>
                        </div>
                        <div class="space-y-3">
                            <div>
                                <label class="text-sm text-gray-600"
                                    >Name</label
                                >
                                <input
                                    v-model="newUser.name"
                                    type="text"
                                    class="w-full pl-4 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                />
                            </div>
                            <div>
                                <label class="text-sm text-gray-600"
                                    >Email</label
                                >
                                <input
                                    v-model="newUser.email"
                                    type="email"
                                    class="w-full pl-4 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                />
                            </div>
                            <div>
                                <label class="text-sm text-gray-600"
                                    >Phone Number</label
                                >
                                <input
                                    v-model="newUser.phone_number"
                                    type="text"
                                    class="w-full pl-4 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                />
                            </div>
                            <div>
                                <label class="text-sm text-gray-600"
                                    >Password</label
                                >
                                <input
                                    v-model="newUser.password"
                                    type="password"
                                    class="w-full pl-4 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                />
                            </div>
                            <div>
                                <label class="text-sm text-gray-600"
                                    >Confirm Password</label
                                >
                                <input
                                    v-model="newUser.password_confirmation"
                                    type="password"
                                    class="w-full pl-4 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                />
                            </div>
                        </div>
                        <div class="mt-4 flex justify-end space-x-2">
                            <button
                                @click="
                                    showUserModal = false;
                                    resetNewUserForm();
                                "
                                class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300"
                            >
                                Cancel
                            </button>
                            <button
                                @click="createUser"
                                :disabled="
                                    cartLoading ||
                                    !newUser.name ||
                                    !newUser.email ||
                                    !newUser.password ||
                                    newUser.password !==
                                        newUser.password_confirmation
                                "
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:bg-gray-400"
                            >
                                Create User
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
.card {
    background: white;
    border-radius: 0.5rem;
    box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
    border: 1px solid #f3f4f6;
    overflow: hidden;
    transition: all 0.2s;
}

.card:hover {
    box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
    border-color: #e5e7eb;
}

/* Input styles */
input[type="text"],
input[type="email"],
input[type="number"],
select,
textarea {
    width: 100%;
    padding: 0.5rem 0.75rem;
    border: 1px solid #e5e7eb;
    border-radius: 0.375rem;
    font-size: 0.875rem;
    outline: none;
    transition: all 0.2s;
}

input[type="text"]:focus,
input[type="email"]:focus,
input[type="number"]:focus,
select:focus,
textarea:focus {
    border-color: #9ca3af;
    box-shadow: 0 0 0 1px #9ca3af;
}

/* Button styles */
.btn-primary {
    padding: 0.5rem 1rem;
    background-color: #111827;
    color: white;
    border-radius: 0.5rem;
    font-size: 0.875rem;
    font-weight: 500;
    transition: background-color 0.2s;
}

.btn-primary:hover {
    background-color: #1f2937;
}

.btn-primary:disabled {
    background-color: #d1d5db;
    cursor: not-allowed;
}

/* Status badges */
.status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 500;
}

.status-badge.paid {
    background-color: #ecfdf5;
    color: #047857;
    border: 1px solid #a7f3d0;
}

.status-badge.unpaid {
    background-color: #f3f4f6;
    color: #374151;
    border: 1px solid #e5e7eb;
}

.status-badge.failed {
    background-color: #fef2f2;
    color: #b91c1c;
    border: 1px solid #fecaca;
}

.status-badge.refunded {
    background-color: #fffbeb;
    color: #b45309;
    border: 1px solid #fcd34d;
}
</style>
