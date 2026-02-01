import { computed, ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';

export function useCompare() {
    const page = usePage();
    const isLoading = ref(false);

    const compareCount = computed(() => page.props.compareCount || 0);
    const compareIds = computed(() => page.props.compareIds || []);

    const isInCompare = (productId) => {
        const ids = Array.isArray(compareIds.value) ? compareIds.value : Object.values(compareIds.value || {});
        return ids.some(id => Number(id) === Number(productId));
    };

    const addToCompare = (productId) => {
        isLoading.value = true;
        router.post(route('compare.add'), { product_id: productId }, {
            preserveScroll: true,
            onFinish: () => {
                isLoading.value = false;
            }
        });
    };

    const removeFromCompare = (productId) => {
        isLoading.value = true;
        router.post(route('compare.remove'), { product_id: productId }, {
            preserveScroll: true,
            onFinish: () => {
                isLoading.value = false;
            }
        });
    };

    const toggleCompare = (productId) => {
        if (isInCompare(productId)) {
            removeFromCompare(productId);
        } else {
            addToCompare(productId);
        }
    };

    const clearCompare = () => {
        isLoading.value = true;
        router.post(route('compare.clear'), {}, {
            preserveScroll: true,
            onFinish: () => {
                isLoading.value = false;
            }
        });
    };

    return {
        compareCount,
        compareIds,
        isLoading,
        isInCompare,
        addToCompare,
        removeFromCompare,
        toggleCompare,
        clearCompare
    };
}
