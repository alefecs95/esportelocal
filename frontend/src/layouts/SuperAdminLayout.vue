<template>
  <div class="min-h-dvh bg-gray-100 flex">
    <aside class="w-64 bg-gray-900 text-white flex flex-col">
      <div class="p-4 border-b border-gray-800">
        <h1 class="text-xl font-bold text-center">Super Admin</h1>
        <p class="text-xs text-center text-gray-500 mt-1">Gestão Global</p>
      </div>

      <nav class="flex-1 p-4 space-y-2">
        <router-link 
          to="/super-admin/dashboard" 
          class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-gray-800 transition-colors"
          active-class="bg-blue-600 text-white"
        >
          <LayoutDashboard :size="20" />
          <span>Dashboard</span>
        </router-link>

        <router-link 
          to="/super-admin/prefeituras" 
          class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-gray-800 transition-colors"
          active-class="bg-blue-600 text-white"
        >
          <Building2 :size="20" />
          <span>Prefeituras</span>
        </router-link>

        <router-link 
          to="/super-admin/leads" 
          class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-gray-800 transition-colors"
          active-class="bg-blue-600 text-white"
        >
          <Users :size="20" />
          <span>Leads & Contatos</span>
        </router-link>

        <router-link 
          to="/super-admin/landing-page" 
          class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-gray-800 transition-colors"
          active-class="bg-blue-600 text-white"
        >
          <Globe :size="20" />
          <span>Landing Page</span>
        </router-link>

        <div class="pt-4 mt-4 border-t border-gray-800">
            <button @click="logout" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-red-900/50 text-red-400 w-full transition-colors">
                <LogOut :size="20" />
                <span>Sair</span>
            </button>
        </div>
      </nav>
    </aside>

    <main class="flex-1 overflow-auto">
      <header class="bg-white shadow-sm h-16 flex items-center px-8 justify-between">
         <h2 class="text-lg font-medium text-gray-800">{{ route.name }}</h2>
         <div class="flex items-center gap-4">
             <div class="text-sm text-right">
                 <p class="font-medium text-gray-900">{{ user?.name }}</p>
                 <p class="text-xs text-gray-500">Super Admin</p>
             </div>
         </div>
      </header>

      <div class="p-8">
        <router-view></router-view>
      </div>
    </main>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { LayoutDashboard, Building2, Globe, Users, LogOut } from 'lucide-vue-next';
import authService from '../services/authService';

const route = useRoute();
const router = useRouter();

const user = computed(() => {
    const userData = localStorage.getItem('user');
    return userData ? JSON.parse(userData) : null;
});

const logout = async () => {
    await authService.logout();
    router.push('/login');
};
</script>
