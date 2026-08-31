<template>
  <div class="flex min-h-screen bg-gray-100">
    <Sidebar :mobile-open="sidebarOpen" @close="sidebarOpen = false" />
    <div v-if="sidebarOpen" class="fixed inset-0 bg-black/50 z-30 lg:hidden" @click="sidebarOpen = false"></div>
    <div class="flex-1 lg:ml-64 flex flex-col min-h-screen w-full">
      <Navbar @toggle-sidebar="sidebarOpen = !sidebarOpen" />
      <main class="flex-1 overflow-y-auto p-3 sm:p-6 mt-16">
        <router-view></router-view>
      </main>
      <footer class="bg-white p-4 text-center text-gray-500 text-sm">
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
