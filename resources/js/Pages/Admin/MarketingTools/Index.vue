<template>
  <Head>
    <title>Marketing Tools</title>
  </Head>
  <AdminLayout>
    <div class="container mx-auto px-4 py-6">
      <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold text-gray-800">Marketing Tools</h1>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Add Script Form -->
        <div class="bg-white rounded-xl shadow-md p-6">
          <h2 class="text-xl font-semibold text-gray-800 mb-4">Add New Script</h2>
          <form @submit.prevent="submitForm" class="space-y-4">
            <div>
              <label for="tool_name" class="block text-sm font-medium text-gray-700 mb-1">Tool Name</label>
              <select 
                v-model="form.tool_name" 
                id="tool_name" 
                class="select select-bordered w-full focus:ring-2 focus:ring-primary focus:border-transparent"
              >
                <option value="Facebook Pixel">Facebook Pixel</option>
                <option value="Google Analytics">Google Analytics</option>
                <option value="Google Tag Manager">Google Tag Manager</option>
                <!-- <option value="Custom Script">Custom Script</option> -->
              </select>
            </div>
            
            <div>
              <label for="script_code" class="block text-sm font-medium text-gray-700 mb-1">Script Code</label>
              <textarea 
                v-model="form.script_code" 
                id="script_code" 
                rows="8"
                class="textarea textarea-bordered w-full font-mono text-sm focus:ring-2 focus:ring-primary focus:border-transparent"
                placeholder="Paste your script code here..."
              ></textarea>
            </div>
            
            <div class="flex justify-end">
              <button 
                type="submit" 
                class="btn btn-primary px-6 py-2 rounded-lg"
                :disabled="!form.script_code"
              >
                <span v-if="!processing">Save Script</span>
                <span v-else class="loading loading-spinner"></span>
              </button>
            </div>
          </form>
        </div>

        <!-- Active Scripts -->
        <div class="bg-white rounded-xl shadow-md p-6">
          <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-semibold text-gray-800">Active Scripts</h2>
            <span class="badge badge-primary">{{ tools.length }} installed</span>
          </div>
          
          <div v-if="tools.length > 0" class="space-y-4">
            <div 
              v-for="tool in tools" 
              :key="tool.id" 
              class="border rounded-lg p-4 hover:shadow-sm transition-shadow"
            >
              <div class="flex justify-between items-start">
                <div>
                  <h3 class="font-medium text-gray-800 flex items-center gap-2">
                    <span class="badge badge-ghost">{{ tool.tool_name }}</span>
                  </h3>
                  <p class="text-xs text-gray-500 mt-1">Last updated: {{ formatDate(tool.updated_at) }}</p>
                </div>
                <button 
                  @click="deleteTool(tool.id)"
                  class="btn btn-ghost btn-sm text-error"
                >
                  <Trash2 />
                </button>
              </div>
              
              <div class="mt-3 relative">
                <button 
                  @click="copyToClipboard(tool.script_code)"
                  class="absolute right-2 top-2 btn btn-xs btn-ghost"
                >
                  <Copy />
                </button>
                <pre class="bg-gray-50 p-3 rounded-md text-xs overflow-x-auto">{{ tool.script_code }}</pre>
              </div>
            </div>
          </div>
          
          <div v-else class="text-center py-8">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
            </svg>
            <p class="mt-2 text-gray-500">No marketing scripts installed yet</p>
            <p class="text-sm text-gray-400">Add your first script using the form</p>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { ref } from 'vue';
import { router, Head } from '@inertiajs/vue3';
import { Copy, Trash2 } from 'lucide-vue-next';

const props = defineProps({
  tools: Array
});

const processing = ref(false);
const form = ref({
  tool_name: 'Facebook Pixel',
  script_code: ''
});

const submitForm = () => {
  processing.value = true;
  router.post(route('admin.marketing-tools.store'), form.value, {
    onSuccess: () => {
      form.value.script_code = '';
      processing.value = false;
    },
    onError: () => {
      processing.value = false;
    }
  });
};

const deleteTool = (id) => {
  if (confirm('Are you sure you want to delete this script?')) {
    router.delete(route('admin.marketing-tools.destroy', id));
  }
};

const copyToClipboard = (text) => {
  navigator.clipboard.writeText(text);
  // You might want to add a toast notification here
};

const formatDate = (dateString) => {
  const options = { year: 'numeric', month: 'short', day: 'numeric' };
  return new Date(dateString).toLocaleDateString(undefined, options);
};
</script>

<style scoped>
/* Custom scrollbar for code blocks */
pre::-webkit-scrollbar {
  height: 6px;
}

pre::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 0 0 4px 4px;
}

pre::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 4px;
}

pre::-webkit-scrollbar-thumb:hover {
  background: #a8a8a8;
}
</style>