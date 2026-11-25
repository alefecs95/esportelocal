<template>
  <div class="flex min-h-dvh bg-gray-100">
    <Sidebar :mobile-open="sidebarOpen" @close="sidebarOpen = false" />
    <Transition
      enter-active-class="transition-opacity duration-200 ease-out-strong"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition-opacity duration-150 ease-out-strong"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div v-if="sidebarOpen" class="fixed inset-0 bg-black/50 z-30 lg:hidden" @click="sidebarOpen = false"></div>
    </Transition>
    <div class="flex-1 lg:ml-64 flex flex-col min-h-dvh w-full">
      <Navbar @toggle-sidebar="sidebarOpen = !sidebarOpen" />
      <main class="flex-1 overflow-y-auto overscroll-contain p-3 sm:p-6 mt-[calc(4rem+env(safe-area-inset-top,0px))]">
        <router-view></router-view>
      </main>
      <footer class="bg-white px-4 pt-4 pb-[max(1rem,env(safe-area-inset-bottom,0px))] text-center text-gray-500 text-sm">
        &copy; 2026 SEMEL. Todos os direitos reservados.
      </footer>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import Sidebar from '@/components/layout/Sidebar.vue';
import Navbar from '@/components/layout/Navbar.vue';

const sidebarOpen = ref(false);
</script>
