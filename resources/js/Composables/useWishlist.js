import { computed, ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';

export function useWishlist() {
    const page = usePage();
    const isLoading = ref(false);

    const wishlistCount = computed(() => page.props.wishlistCount || 0);
    const wishlistIds = computed(() => page.props.wishlistIds || []);

    const isInWishlist = (productId) => {
        const ids = Array.isArray(wishlistIds.value) ? wishlistIds.value : Object.values(wishlistIds.value || {});
        return ids.some(id => Number(id) === Number(productId));
    };

    const addToWishlist = (productId) => {
        isLoading.value = true;
        router.post(route('wishlist.add'), { product_id: productId }, {
            preserveScroll: true,
            onFinish: () => {
                isLoading.value = false;
            }
        });
    };

    const removeFromWishlist = (productId) => {
        isLoading.value = true;
        router.post(route('wishlist.remove'), { product_id: productId }, {
            preserveScroll: true,
            onFinish: () => {
                isLoading.value = false;
            }
        });
    };

    const toggleWishlist = (productId) => {
        if (isInWishlist(productId)) {
            removeFromWishlist(productId);
        } else {
            addToWishlist(productId);
        }
    };

    return {
        wishlistCount,
        wishlistIds,
        isLoading,
        isInWishlist,
        addToWishlist,
        removeFromWishlist,
        toggleWishlist
    };
}
