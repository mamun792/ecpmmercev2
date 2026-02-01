<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { ref } from 'vue';
import { useForm, Head } from '@inertiajs/vue3';
import { toast } from "@steveyuowo/vue-hot-toast";

const props = defineProps({
  settings: Object,
});

const form = useForm({
  client_id: props.settings?.client_id || '',
  client_secret: props.settings?.client_secret || '',
  username: props.settings?.username || '',
  password: props.settings?.password || '',
  is_enabled: props.settings?.is_enabled || 'yes',
  StoreId: props.settings?.StoreId || '',
});

const submit = () => {
  form.post(route('admin.courier.settings.store'), {
    onSuccess: () => {
      toast.success('Pathao settings saved successfully!');
    },
    onError: (errors) => {
      toast.error('Failed to save Pathao settings.');
      console.error('Form errors:', errors);
    },
  });
};
</script>

<template>
  <Head title="Courier Settings" />
  <AdminLayout>
    <div class="max-w-lg mx-auto sm:px-6 lg:px-8">
      <div class="bg-white overflow-hidden shadow-md rounded-lg">
        <div class="p-6 bg-white border-b border-gray-200">
          <div class="flex flex-col items-center mb-6">
            <img src="/assets/img/logo/Pathao.png" alt="Pathao" class="h-16 mb-1" />
            <div class="flex items-center mt-3">
              <span class="text-sm mr-2">Select Pathao</span>
              <div class="relative inline-block w-12 align-middle select-none transition duration-200 ease-in">
                <input 
                  type="checkbox" 
                  :checked="form.is_enabled === 'yes'"
                  @change="form.is_enabled = form.is_enabled === 'yes' ? 'no' : 'yes'"
                  class="toggle-checkbox absolute block w-6 h-6 rounded-full bg-white border-4 appearance-none cursor-pointer"
                />
                <label class="toggle-label block overflow-hidden h-6 rounded-full bg-gray-300 cursor-pointer"></label>
              </div>
            </div>
          </div>
          
          <form @submit.prevent="submit">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label for="client_id" class="block text-sm text-gray-600">Client ID</label>
                <input id="client_id" v-model="form.client_id" type="text"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                  :class="{ 'border-red-500': form.errors.client_id }" />
                <p v-if="form.errors.client_id" class="text-red-500 text-xs mt-1">{{ form.errors.client_id }}</p>
              </div>

              <div>
                <label for="username" class="block text-sm text-gray-600">Username</label>
                <input id="username" v-model="form.username" type="text"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                  :class="{ 'border-red-500': form.errors.username }" />
                <p v-if="form.errors.username" class="text-red-500 text-xs mt-1">{{ form.errors.username }}</p>
              </div>

              <div>
                <label for="client_secret" class="block text-sm text-gray-600">Client Secret</label>
                <input id="client_secret" v-model="form.client_secret" type="text"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                  :class="{ 'border-red-500': form.errors.client_secret }" />
                <p v-if="form.errors.client_secret" class="text-red-500 text-xs mt-1">{{ form.errors.client_secret }}</p>
              </div>

              <div>
                <label for="password" class="block text-sm text-gray-600">Password</label>
                <input id="password" v-model="form.password" type="password"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                  :class="{ 'border-red-500': form.errors.password }" />
                <p v-if="form.errors.password" class="text-red-500 text-xs mt-1">{{ form.errors.password }}</p>
              </div>
            </div>

            <div class="mt-4">
              <label for="StoreId" class="block text-sm text-gray-600">Store ID</label>
              <input id="StoreId" v-model.number="form.StoreId" type="number"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                :class="{ 'border-red-500': form.errors.StoreId }" />
              <p v-if="form.errors.StoreId" class="text-red-500 text-xs mt-1">{{ form.errors.StoreId }}</p>
            </div>

            <div class="mt-6">
              <button type="submit"
                class="w-full py-3 bg-blue-500 border border-transparent rounded-md font-semibold text-white hover:bg-blue-600 focus:outline-none"
                :disabled="form.processing">
                Generate Token
              </button>
            </div>
          </form>

          <!-- <div v-if="props.settings?.access_token" class="mt-6">
            <h3 class="text-lg font-medium text-gray-900">Current Token</h3>
            <p class="mt-2 text-sm text-gray-600">Access Token: {{ '********' }}</p>
            <p class="mt-2 text-sm text-gray-600">Expires At: {{ props.settings.expires_at || 'N/A' }}</p>
          </div> -->
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<style scoped>
.toggle-checkbox:checked {
  right: 0;
  border-color: #4f46e5;
}
.toggle-checkbox:checked + .toggle-label {
  background-color: #4f46e5;
}
.toggle-label {
  transition: background-color 0.2s ease-in;
}
</style>