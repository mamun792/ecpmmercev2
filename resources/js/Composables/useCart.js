import { computed, ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { useToast } from '@/Composables/useToast';

/**
 * Cart Composable - Centralized cart management
 * Import and use in any component:
 * 
 * import { useCart } from '@/Composables/useCart';
 * const { cart, cartCount, addToCart, incrementQuantity, decrementQuantity, removeItem, clearCart, isLoading } = useCart();
 */
export function useCart() {
    const page = usePage();
    const isLoading = ref(false);
    const error = ref(null);
    const { success, error: showError } = useToast();

    // Reactive cart data from Inertia shared props
    const cart = computed(() => {
        const cartData = page.props.cart;
        // Handle both wrapped (data.items) and unwrapped (items) responses
        return cartData?.data || cartData;
    });

    const cartItems = computed(() => cart.value?.items || []);
    const cartTotal = computed(() => cart.value?.total || 0);
    const cartCount = computed(() => page.props.cartCount || cart.value?.items_count || 0);
    const sessionId = computed(() => page.props.cartSessionId);
    const isEmpty = computed(() => cartItems.value.length === 0);

    /**
     * Add product to cart
     * @param {Object} options - { productId, quantity, variationId, options }
     */
    const addToCart = (options = {}) => {
        const {
            productId,
            quantity = 1,
            variationId = null,
            productOptions = { gift_wrap: false, message: '' },
            onSuccess = null,
            onError = null
        } = options;

        if (!productId) {
            console.error('Product ID is required');
            return;
        }

        isLoading.value = true;
        error.value = null;

        const payload = {
            product_id: productId,
            quantity: quantity,
            product_variation_id: variationId,
            options: productOptions,
            user_id: page.props.auth?.user?.id || null,
            session_id: sessionId.value
        };

        router.post(route('cart.add'), payload, {
            preserveScroll: true,
            onSuccess: () => {
                isLoading.value = false;
                if (onSuccess) onSuccess();
            },
            onError: (errors) => {
                isLoading.value = false;
                error.value = errors;
                if (onError) onError(errors);
            },
            onFinish: () => {
                isLoading.value = false;
            }
        });
    };

    /**
     * Increment item quantity by 1
     * @param {number} itemId - Cart item ID
     */
    const incrementQuantity = (itemId, onSuccess = null) => {
        isLoading.value = true;
        error.value = null;

        router.post(route('cart.update'), {
            item_id: itemId,
            quantity: 1  // positive = increase by 1
        }, {
            preserveScroll: true,
            onSuccess: () => {
                if (onSuccess) onSuccess();
            },
            onError: (errors) => {
                error.value = errors;
            },
            onFinish: () => {
                isLoading.value = false;
            }
        });
    };

    /**
     * Decrement item quantity by 1
     * @param {number} itemId - Cart item ID
     * @param {number} currentQuantity - Current quantity (to check if should remove)
     */
    const decrementQuantity = (itemId, currentQuantity = 2, onSuccess = null) => {
        if (currentQuantity <= 1) {
            // If quantity is 1, remove the item instead
            removeItem(itemId, onSuccess);
            return;
        }

        isLoading.value = true;
        error.value = null;

        router.post(route('cart.update'), {
            item_id: itemId,
            quantity: -1  // negative = decrease by 1
        }, {
            preserveScroll: true,
            onSuccess: () => {
                if (onSuccess) onSuccess();
            },
            onError: (errors) => {
                error.value = errors;
            },
            onFinish: () => {
                isLoading.value = false;
            }
        });
    };

    /**
     * Update item to specific quantity
     * @param {number} itemId - Cart item ID
     * @param {number} newQuantity - New quantity to set
     */
    const updateQuantity = (itemId, newQuantity, onSuccess = null) => {
        // Find current item quantity
        const item = cartItems.value.find(i => i.id === itemId);
        if (!item) return;

        const delta = newQuantity - item.quantity;
        
        if (newQuantity <= 0) {
            removeItem(itemId, onSuccess);
            return;
        }

        if (delta === 0) return;

        isLoading.value = true;
        error.value = null;

        router.post(route('cart.update'), {
            item_id: itemId,
            quantity: delta
        }, {
            preserveScroll: true,
            onSuccess: () => {
                if (onSuccess) onSuccess();
            },
            onError: (errors) => {
                error.value = errors;
            },
            onFinish: () => {
                isLoading.value = false;
            }
        });
    };

    /**
     * Remove item from cart
     * @param {number} itemId - Cart item ID
     */
    const removeItem = (itemId, onSuccess = null) => {
        isLoading.value = true;
        error.value = null;

        router.post(route('cart.remove'), {
            item_id: itemId
        }, {
            preserveScroll: true,
            onSuccess: (page) => {
                // Check if cart is now empty and we're on checkout page
                const currentPath = window.location.pathname;
                const newCartCount = page.props.cartCount || 0;
                
                if (newCartCount === 0 && currentPath.includes('/checkout')) {
                    // Redirect to cart page if checkout becomes empty
                    router.visit(route('cart.index'));
                } else if (onSuccess) {
                    onSuccess();
                }
            },
            onError: (errors) => {
                error.value = errors;
            },
            onFinish: () => {
                isLoading.value = false;
            }
        });
    };

    /**
     * Clear entire cart
     */
    const clearCart = (onSuccess = null) => {
        isLoading.value = true;
        error.value = null;

        router.post(route('cart.clear'), {}, {
            preserveScroll: true,
            onSuccess: () => {
                if (onSuccess) onSuccess();
            },
            onError: (errors) => {
                error.value = errors;
            },
            onFinish: () => {
                isLoading.value = false;
            }
        });
    };

    /**
     * Get item by product ID
     * @param {number} productId 
     * @param {number|null} variationId 
     */
    const getCartItem = (productId, variationId = null) => {
        return cartItems.value.find(item => {
            if (variationId) {
                return item.product_id === productId && item.product_variation_id === variationId;
            }
            return item.product_id === productId;
        });
    };

    /**
     * Check if product is in cart
     * @param {number} productId 
     * @param {number|null} variationId 
     */
    const isInCart = (productId, variationId = null) => {
        return !!getCartItem(productId, variationId);
    };

    /**
     * Get quantity of product in cart
     * @param {number} productId 
     * @param {number|null} variationId 
     */
    const getQuantityInCart = (productId, variationId = null) => {
        const item = getCartItem(productId, variationId);
        return item ? item.quantity : 0;
    };

    return {
        // State
        cart,
        cartItems,
        cartTotal,
        cartCount,
        sessionId,
        isEmpty,
        isLoading,
        error,
        
        // Actions
        addToCart,
        incrementQuantity,
        decrementQuantity,
        updateQuantity,
        removeItem,
        clearCart,
        
        // Helpers
        getCartItem,
        isInCart,
        getQuantityInCart
    };
}
