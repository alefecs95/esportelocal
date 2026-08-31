<template>
  <header class="bg-white shadow h-16 flex items-center justify-between px-3 sm:px-6 fixed top-0 left-0 lg:left-64 right-0 z-20">
    <div class="flex items-center gap-2 min-w-0">
      <button @click="emit('toggle-sidebar')" class="lg:hidden p-2 rounded-md text-gray-600 hover:text-gray-900 hover:bg-gray-100">
        <Menu :size="20" />
      </button>
      <div class="text-base sm:text-xl font-semibold text-gray-800 truncate">
        {{ title }}
      </div>
    </div>
    <div class="flex items-center gap-2 sm:gap-4">
      <div class="relative flex items-center gap-2 sm:gap-4">
        <button class="flex items-center space-x-2 text-gray-700 hover:text-gray-900 focus:outline-none">
          <img
            class="h-8 w-8 rounded-full object-cover"
            src="https://ui-avatars.com/api/?name=Admin+User&background=random"
            alt="User avatar"
          />
          <span class="font-medium hidden sm:inline">Admin</span>
        </button>
        
        <button 
          @click="handleLogout" 
          class="flex items-center text-red-500 hover:text-red-700 transition-colors"
          title="Sair do sistema"
        >
          <LogOut :size="20" />
          <span class="ml-2 text-sm font-medium hidden sm:inline">Sair</span>
        </button>
      </div>
    </div>
  </header>
</template>

<script setup>
import { computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { LogOut, Menu } from 'lucide-vue-next';
import authService from '../../modules/auth/services/authService';

const emit = defineEmits(['toggle-sidebar']);
const route = useRoute();
const router = useRouter();
const title = computed(() => route.meta.title || 'Dashboard');

const handleLogout = async () => {
  try {
    await authService.logout();
  } catch (error) {
    console.error('Erro ao fazer logout:', error);
  } finally {
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    router.push('/login');
  }
};
</script>
