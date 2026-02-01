<template>
    <Toaster />
    <div
        class="h-screen flex overflow-hidden bg-gray-50"
    >
        <!-- Sidebar -->
        <div
            class="inset-y-0 left-0 z-50 transition-all duration-300 ease-in-out"
            :class="{
                'fixed -translate-x-full': !sidebarOpen && isMobile,
                'fixed translate-x-0 shadow-xl': sidebarOpen && isMobile,
                'relative': !isMobile
            }"
            @mouseenter="!isMobile && !sidebarOpen ? (isHovered = true) : null"
            @mouseleave="!isMobile && !sidebarOpen ? (isHovered = false) : null"
        >
            <Sidebar
                :sidebarOpen="sidebarOpen || (isHovered && !isMobile)"
                :openSubmenus="openSubmenus"
                :toggleSubmenu="toggleSubmenu"
            />
        </div>

        <!-- Overlay for mobile -->
        <div
            v-if="sidebarOpen && isMobile"
            class="fixed inset-0 bg-black/50 backdrop-blur-sm z-40"
            @click="toggleSidebar"
        ></div>

        <!-- Main Content -->
        <div
            class="flex-1 flex flex-col min-w-0 transition-all duration-300 ease-in-out"
        >
            <!-- Header -->
            <Header
                :sidebarOpen="sidebarOpen"
                :toggleSidebar="toggleSidebar"
                :isDark="isDark"
                :toggleDarkMode="toggleDarkMode"
                :isFullscreen="isFullscreen"
                :toggleFullscreen="toggleFullscreen"
                :isProfileOpen="isProfileOpen"
                :toggleProfile="() => (isProfileOpen = !isProfileOpen)"
            />

            <!-- Main Content Area -->
            <main class="flex-1 overflow-y-auto p-0 sm:p-6">
                <div class="w-full mx-auto p-4 sm:p-6">
                    <slot />
                </div>
            </main>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted, onUnmounted } from "vue";
import Sidebar from "@/Components/AdminLayout/Sidebar.vue";
import Header from "@/Components/AdminLayout/Header.vue";
import { Toaster } from "@steveyuowo/vue-hot-toast";
import "@steveyuowo/vue-hot-toast/vue-hot-toast.css";

// State
const sidebarOpen = ref(false); // Start closed by default
const isDark = ref(false);
const isFullscreen = ref(false);
const isProfileOpen = ref(false);
const openSubmenus = reactive({});
const isMobile = ref(false);
const isHovered = ref(false);

// Toggle functions
const toggleSidebar = () => {
    sidebarOpen.value = !sidebarOpen.value;
};

const toggleDarkMode = () => {
    isDark.value = !isDark.value;
    localStorage.setItem("darkMode", isDark.value);
    document.documentElement.classList.toggle("dark", isDark.value);
};

const toggleFullscreen = () => {
    if (!document.fullscreenElement) {
        document.documentElement
            .requestFullscreen()
            .then(() => (isFullscreen.value = true))
            .catch((err) => console.error(`Fullscreen error: ${err.message}`));
    } else if (document.exitFullscreen) {
        document
            .exitFullscreen()
            .then(() => (isFullscreen.value = false))
            .catch((err) =>
                console.error(`Exit fullscreen error: ${err.message}`)
            );
    }
};

const toggleSubmenu = (index) => {
    openSubmenus[index] = !openSubmenus[index];
};

// Check screen size
const checkScreenSize = () => {
    isMobile.value = window.innerWidth < 768; // md breakpoint
    if (!isMobile.value && !sidebarOpen.value) {
        // On desktop, keep sidebar open by default
        sidebarOpen.value = true;
    }
    // On mobile, keep whatever state user set
};

onMounted(() => {
    const savedDarkMode = localStorage.getItem("darkMode");
    if (savedDarkMode) {
        isDark.value = savedDarkMode === "true";
        document.documentElement.classList.toggle("dark", isDark.value);
    }

    checkScreenSize();
    window.addEventListener("resize", checkScreenSize);
});

onUnmounted(() => {
    window.removeEventListener("resize", checkScreenSize);
});
</script>
