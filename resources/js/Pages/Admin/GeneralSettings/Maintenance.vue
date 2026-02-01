<script setup>
import { Head, useForm, router } from "@inertiajs/vue3";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { toast } from "@steveyuowo/vue-hot-toast";
import { ref, computed } from "vue";
import { 
  Settings, 
  Database, 
  Trash2, 
  AlertTriangle, 
  CheckCircle2, 
  Cpu, 
  HardDrive, 
  History,
  ShieldAlert,
  Save,
  Loader2,
  Lock,
  Unlock,
  RefreshCw,
  Terminal,
  Activity
} from "lucide-vue-next";

const props = defineProps({
  isMaintenanceMode: Boolean,
  systemInfo: Object
});

const isClearingCache = ref(false);
const isBackingUp = ref(false);
const isTogglingMaintenance = ref(false);
const maintenancePassword = ref("");

const isPasswordCorrect = computed(() => maintenancePassword.value === 'mamun');

const handleClearCache = () => {
  isClearingCache.value = true;
  router.post(route('admin.maintenance.clear-cache'), {}, {
    onSuccess: () => {
      toast.success("System cache cleared successfully");
      isClearingCache.value = false;
    },
    onError: () => {
      isClearingCache.value = false;
    }
  });
};

const handleBackup = () => {
  isBackingUp.value = true;
  window.location.href = route('admin.maintenance.backup');
  setTimeout(() => {
    isBackingUp.value = false;
    toast.success("Backup process initiated");
  }, 2000);
};

const handleToggleMaintenance = () => {
  if (!isPasswordCorrect.value) {
    toast.error("Invalid security password");
    return;
  }

  const mode = props.isMaintenanceMode ? "ACTIVATE SITE" : "ENTER MAINTENANCE MODE";
  
  isTogglingMaintenance.value = true;
  router.post(route('admin.maintenance.toggle'), {
    secret: 'admin-access',
    password: maintenancePassword.value
  }, {
    onSuccess: () => {
      isTogglingMaintenance.value = false;
      maintenancePassword.value = "";
    },
    onFinish: () => {
      isTogglingMaintenance.value = false;
    }
  });
};
</script>

<template>
  <Head title="System Maintenance" />
  <AdminLayout>
    <div class="min-h-screen bg-[#FDFDFF] dark:bg-gray-950 p-6 lg:p-12 relative overflow-hidden">
      <!-- Ambient Background Effects -->
      <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] bg-blue-400/10 dark:bg-blue-600/5 blur-[120px] rounded-full pointer-events-none"></div>
      <div class="absolute bottom-[-10%] right-[-10%] w-[30%] h-[30%] bg-purple-400/10 dark:bg-purple-600/5 blur-[120px] rounded-full pointer-events-none"></div>

      <div class="max-w-[1300px] mx-auto relative z-10">
        <!-- Header Section -->
        <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-8 mb-16">
          <div class="space-y-4">
            <div class="inline-flex items-center gap-2 px-3 py-1 bg-gray-100 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm">
              <Terminal class="w-3.5 h-3.5 text-gray-500" />
              <span class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-500">Infrastructure Control</span>
            </div>
            <h1 class="text-5xl lg:text-6xl font-black text-gray-900 dark:text-white tracking-tighter leading-none">
              System<span class="text-blue-600">.</span>Maintenance
            </h1>
            <p class="text-lg text-gray-500 dark:text-gray-400 font-medium max-w-xl leading-relaxed">
              Enterprise-grade administrative console for environment state management, asset optimization, and data security.
            </p>
          </div>

          <!-- Live Status Badge (Modernized) -->
          <div 
            :class="[
              'p-6 rounded-[2rem] border transition-all duration-500 backdrop-blur-md min-w-[240px]',
              isMaintenanceMode 
                ? 'bg-amber-500/10 border-amber-200/50 text-amber-900 dark:text-amber-400' 
                : 'bg-emerald-500/10 border-emerald-200/50 text-emerald-900 dark:text-emerald-400'
            ]"
          >
            <div class="flex items-center justify-between mb-2">
              <span class="text-[11px] font-black uppercase tracking-widest opacity-60">Deployment Health</span>
              <div :class="['w-2 h-2 rounded-full animate-ping', isMaintenanceMode ? 'bg-amber-500' : 'bg-emerald-500']"></div>
            </div>
            <div class="flex items-center gap-3">
              <Activity class="w-6 h-6" />
              <span class="text-2xl font-black tracking-tight">{{ isMaintenanceMode ? 'UNDER MAINTENANCE' : 'SYSTEM OPERATIONAL' }}</span>
            </div>
          </div>
        </div>

        <!-- Main Workspace Grid -->
        <div class="grid lg:grid-cols-12 gap-10">
          
          <!-- State Control Panel (Enhanced) -->
          <div class="lg:col-span-5 space-y-8">
            <div class="bg-white dark:bg-gray-900 p-10 rounded-[3rem] border border-gray-100 dark:border-gray-800 shadow-2xl shadow-blue-900/5 relative overflow-hidden group">
              <!-- Decorative background icon -->
              <ShieldAlert class="absolute right-[-20px] top-[-20px] w-48 h-48 opacity-[0.03] group-hover:rotate-12 transition-transform duration-700" />
              
              <div class="relative z-10">
                <div class="inline-flex p-4 bg-gray-950 dark:bg-white rounded-[1.5rem] mb-8 shadow-xl">
                  <Lock v-if="!isMaintenanceMode" class="w-8 h-8 text-white dark:text-gray-950" />
                  <Unlock v-else class="w-8 h-8 text-white dark:text-gray-950" />
                </div>
                
                <h2 class="text-3xl font-black text-gray-900 dark:text-white mb-4 tracking-tight">Access Control</h2>
                <p class="text-gray-500 dark:text-gray-400 font-medium text-base mb-8 leading-relaxed">
                  Toggle production state to prevent user fallout during critical migrations.
                </p>

                <!-- Modern Input UI -->
                <div class="space-y-6 mb-10">
                  <div class="relative group/input">
                    <label class="absolute -top-2.5 left-5 px-2 bg-white dark:bg-gray-900 text-[10px] font-black text-gray-400 uppercase tracking-widest z-10">Administrative Key</label>
                    <input 
                      v-model="maintenancePassword"
                      type="password"
                      placeholder="••••••••"
                      class="w-full px-6 py-5 bg-gray-50 dark:bg-gray-800/50 border-2 border-transparent rounded-[1.5rem] ring-1 ring-gray-200 dark:ring-gray-700 focus:ring-4 focus:ring-blue-100 dark:focus:ring-blue-900/20 focus:border-blue-500 transition-all font-mono text-xl tracking-widest text-gray-900 dark:text-white"
                    />
                    <div v-if="isPasswordCorrect" class="absolute right-6 top-1/2 -translate-y-1/2 text-emerald-500 animate-in zoom-in-50 duration-300">
                      <CheckCircle2 class="w-6 h-6" />
                    </div>
                  </div>

                  <!-- Bypass link box -->
                  <div v-if="isMaintenanceMode" class="p-5 bg-blue-50/50 dark:bg-blue-900/10 rounded-2xl border border-blue-100/50 dark:border-blue-800/50">
                    <div class="flex items-center gap-3 text-blue-700 dark:text-blue-400">
                      <span class="text-xs font-black uppercase tracking-wider">Secret Bypass URL:</span>
                      <a href="/admin-access" target="_blank" class="font-mono text-sm font-bold underline hover:opacity-70 transition-opacity">/admin-access</a>
                    </div>
                  </div>
                </div>

                <button 
                  @click="handleToggleMaintenance"
                  :disabled="isTogglingMaintenance || !isPasswordCorrect"
                  :class="[
                    'w-full py-5 rounded-[1.5rem] font-black tracking-[0.1em] uppercase transition-all duration-500 flex items-center justify-center gap-4 relative overflow-hidden group/btn shadow-2xl',
                    isPasswordCorrect
                      ? (isMaintenanceMode ? 'bg-emerald-600 hover:bg-emerald-500 text-white shadow-emerald-500/40' : 'bg-gray-950 hover:bg-blue-600 text-white shadow-gray-950/40')
                      : 'bg-gray-100 dark:bg-gray-800 text-gray-300 dark:text-gray-700 cursor-not-allowed shadow-none border-dashed border-2 border-gray-200 dark:border-gray-700'
                  ]"
                >
                  <span class="relative z-10 flex items-center gap-3">
                    <Loader2 v-if="isTogglingMaintenance" class="w-5 h-5 animate-spin" />
                    <template v-else>
                      <RefreshCw class="w-5 h-5 group-hover/btn:rotate-180 transition-transform duration-700" />
                      {{ isMaintenanceMode ? 'Reactivate Fleet' : 'Secure Maintenance' }}
                    </template>
                  </span>
                </button>
              </div>
            </div>
          </div>

          <!-- Utilities Grid -->
          <div class="lg:col-span-7 grid sm:grid-cols-2 gap-10">
            <!-- Cache Optimization -->
            <div class="bg-white dark:bg-gray-900 p-8 rounded-[3rem] border border-gray-100 dark:border-gray-800 shadow-xl shadow-gray-200/30 flex flex-col group hover:shadow-2xl hover:translate-y-[-4px] transition-all duration-500">
              <div class="w-16 h-16 bg-blue-50 dark:bg-blue-900/20 rounded-[1.25rem] flex items-center justify-center mb-6 group-hover:scale-110 group-hover:rotate-6 transition-transform">
                <Trash2 class="w-8 h-8 text-blue-600" />
              </div>
              <h3 class="text-2xl font-black text-gray-900 dark:text-white mb-3">Asset Purge</h3>
              <p class="text-sm text-gray-500 dark:text-gray-400 font-medium mb-10 leading-relaxed flex-grow">
                Complete environment flush. Truncates database cache tables and purges compiled view objects.
              </p>
              <button 
                @click="handleClearCache"
                :disabled="isClearingCache"
                class="w-full py-4 bg-gray-50 dark:bg-gray-800 hover:bg-blue-600 hover:text-white dark:hover:bg-blue-600 rounded-[1.25rem] font-black text-xs uppercase tracking-widest text-gray-900 dark:text-white border-2 border-transparent hover:border-blue-400 transition-all flex items-center justify-center gap-2"
              >
                <Loader2 v-if="isClearingCache" class="w-4 h-4 animate-spin" />
                Flush Environment
              </button>
            </div>

            <!-- Database Backup -->
            <div class="bg-white dark:bg-gray-900 p-8 rounded-[3rem] border border-gray-100 dark:border-gray-800 shadow-xl shadow-gray-200/30 flex flex-col group hover:shadow-2xl hover:translate-y-[-4px] transition-all duration-500">
              <div class="w-16 h-16 bg-purple-50 dark:bg-purple-900/20 rounded-[1.25rem] flex items-center justify-center mb-6 group-hover:scale-110 group-hover:rotate-6 transition-transform">
                <Save class="w-8 h-8 text-purple-600" />
              </div>
              <h3 class="text-2xl font-black text-gray-900 dark:text-white mb-3">Full Backup</h3>
              <p class="text-sm text-gray-500 dark:text-gray-400 font-medium mb-10 leading-relaxed flex-grow">
                Generate a point-in-time SQL snapshot of your entire database architecture. Includes all products, orders, customers, and system configurations.
              </p>
              <button 
                @click="handleBackup"
                :disabled="isBackingUp"
                class="w-full py-4 bg-gray-50 dark:bg-gray-800 hover:bg-purple-600 hover:text-white dark:hover:bg-blue-600 rounded-[1.25rem] font-black text-xs uppercase tracking-widest text-gray-900 dark:text-white border-2 border-transparent hover:border-purple-400 transition-all flex items-center justify-center gap-2"
              >
                <Loader2 v-if="isBackingUp" class="w-4 h-4 animate-spin" />
                Download SQL Archive
              </button>
            </div>

            <!-- Stats Bar (Integrated) -->
            <div class="sm:col-span-2 bg-gray-900 dark:bg-gray-800 p-8 rounded-[3rem] text-white shadow-2xl relative overflow-hidden">
               <div class="absolute inset-0 opacity-10" style="background-image: radial-gradient(#ffffff 1px, transparent 1px); background-size: 20px 20px;"></div>
               <div class="relative z-10 grid grid-cols-2 md:grid-cols-4 gap-8">
                 <div class="space-y-1">
                   <div class="flex items-center gap-2 opacity-50 mb-2">
                     <Cpu class="w-3.5 h-3.5" />
                     <span class="text-[9px] font-black uppercase tracking-tighter">Engine</span>
                   </div>
                   <div class="text-lg font-black tracking-tight">PHP {{ systemInfo.php_version }}</div>
                 </div>
                 <div class="space-y-1">
                   <div class="flex items-center gap-2 opacity-50 mb-2">
                     <History class="w-3.5 h-3.5" />
                     <span class="text-[9px] font-black uppercase tracking-tighter">Stack</span>
                   </div>
                   <div class="text-lg font-black tracking-tight">v{{ systemInfo.laravel_version }}</div>
                 </div>
                 <div class="space-y-1">
                   <div class="flex items-center gap-2 opacity-50 mb-2">
                     <HardDrive class="w-3.5 h-3.5" />
                     <span class="text-[9px] font-black uppercase tracking-tighter">Storage</span>
                   </div>
                   <div class="text-lg font-black tracking-tight uppercase">{{ systemInfo.database }}</div>
                 </div>
                 <div class="space-y-1">
                   <div class="flex items-center gap-2 opacity-50 mb-2">
                     <CheckCircle2 class="w-3.5 h-3.5 text-emerald-400" />
                     <span class="text-[9px] font-black uppercase tracking-tighter text-emerald-400">Status</span>
                   </div>
                   <div class="text-lg font-black tracking-tight text-emerald-400">NOMINAL</div>
                 </div>
               </div>
            </div>
          </div>
        </div>

        <!-- Warning Footer -->
        <div class="mt-16 flex flex-col md:flex-row items-center justify-between p-8 bg-white dark:bg-gray-900 bg-opacity-40 backdrop-blur-xl border border-gray-100 dark:border-gray-800 rounded-[2.5rem]">
          <div class="flex items-center gap-4 mb-4 md:mb-0">
             <div class="p-3 bg-red-100 dark:bg-red-900/30 rounded-2xl">
               <AlertTriangle class="w-6 h-6 text-red-600" />
             </div>
             <p class="text-xs font-bold text-gray-500 dark:text-gray-400 max-w-md">
               Operations performed here are destructive and potentially environment-breaking. 
               Proceed with extreme caution.
             </p>
          </div>
          <div class="flex items-center gap-3">
            <div class="h-2 w-2 rounded-full bg-blue-500 shadow-[0_0_12px_rgba(59,130,246,0.8)] animate-pulse"></div>
            <span class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Secure Node 04_ADMIN</span>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&display=swap');

:deep(body) {
  font-family: 'Inter', sans-serif;
}

::-webkit-scrollbar {
  width: 6px;
}
::-webkit-scrollbar-track {
  background: transparent;
}
::-webkit-scrollbar-thumb {
  background: rgba(0,0,0,0.05);
  border-radius: 10px;
}
.dark ::-webkit-scrollbar-thumb {
  background: rgba(255,255,255,0.05);
}
</style>
