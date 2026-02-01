
<script setup>
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { toast } from "@steveyuowo/vue-hot-toast";

const props = defineProps({
  itemId: {
    type: [Number, String],
    required: true
  },
  itemName: {
    type: String,
    default: 'item'
  },
  routeName: {
    type: String,
    required: true
  },
  visible: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits(['update:visible', 'deleted']);

const isVisible = ref(props.visible);
const isDeleting = ref(false);

watch(() => props.visible, (newValue) => {
  isVisible.value = newValue;
});

const confirmDelete = () => {
  isDeleting.value = true;
  router.delete(route(props.routeName, props.itemId), {
    preserveScroll: true,
    onSuccess: () => {
      toast.success(`${props.itemName} deleted successfully!`);
      emit('deleted');
      closeModal();
    },
    onError: (errors) => {
      toast.error(`Failed to delete ${props.itemName}`);
      console.error('Delete failed:', errors);
    },
    onFinish: () => {
      isDeleting.value = false;
    }
  });
};

const closeModal = () => {
  isVisible.value = false;
  emit('update:visible', false);
};
</script>

<template>
  <transition name="modal">
    <div v-if="isVisible" class="fixed inset-0 z-50 flex items-center justify-center">
      <!-- Backdrop -->
      <div class="fixed inset-0 bg-black bg-opacity-50" @click="closeModal"></div>
      
      <!-- Modal Content -->
      <div class="relative bg-white rounded-lg shadow-xl p-6 w-full max-w-md">
        <h3 class="text-lg font-semibold mb-4">Confirm Deletion</h3>
        <p class="text-gray-600 mb-6">
          Are you sure you want to delete this {{ itemName }}? This action cannot be undone.
        </p>
        
        <div class="flex justify-end space-x-3">
          <button
            @click="closeModal"
            class="px-4 py-2 bg-gray-200 text-gray-800 rounded hover:bg-gray-300 transition"
            :disabled="isDeleting"
          >
            Cancel
          </button>
          <button
            @click="confirmDelete"
            class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 transition"
            :disabled="isDeleting"
          >
            {{ isDeleting ? 'Deleting...' : 'Delete' }}
          </button>
        </div>
      </div>
    </div>
  </transition>
</template>

<style scoped>
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}

.modal-enter-active .bg-white,
.modal-leave-active .bg-white {
  transition: transform 0.3s ease;
}

.modal-enter-from .bg-white,
.modal-leave-to .bg-white {
  transform: scale(0.95);
}
</style>