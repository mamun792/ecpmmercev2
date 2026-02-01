import { ref } from 'vue';

const toasts = ref([]);
let toastId = 0;

/**
 * Toast Composable - Centralized toast notifications
 * 
 * Usage:
 * import { useToast } from '@/Composables/useToast';
 * const { success, error, info, warning } = useToast();
 * 
 * success('Item added to cart!');
 * error('Failed to add item');
 */
export function useToast() {
    const addToast = (message, type = 'info', duration = 3000) => {
        const id = ++toastId;
        const toast = {
            id,
            message,
            type, // 'success', 'error', 'warning', 'info'
            duration
        };

        toasts.value.push(toast);

        // Auto remove after duration
        if (duration > 0) {
            setTimeout(() => {
                removeToast(id);
            }, duration);
        }

        return id;
    };

    const removeToast = (id) => {
        const index = toasts.value.findIndex(t => t.id === id);
        if (index > -1) {
            toasts.value.splice(index, 1);
        }
    };

    const success = (message, duration = 3000) => {
        return addToast(message, 'success', duration);
    };

    const error = (message, duration = 4000) => {
        return addToast(message, 'error', duration);
    };

    const warning = (message, duration = 3500) => {
        return addToast(message, 'warning', duration);
    };

    const info = (message, duration = 3000) => {
        return addToast(message, 'info', duration);
    };

    const clearAll = () => {
        toasts.value = [];
    };

    return {
        toasts,
        success,
        error,
        warning,
        info,
        removeToast,
        clearAll
    };
}
