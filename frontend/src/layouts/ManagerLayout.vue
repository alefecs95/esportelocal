<template>
  <div class="min-h-screen bg-gray-50">
    <ManagerSidebar :mobile-open="sidebarOpen" @close="sidebarOpen = false" />
    <div
      v-if="sidebarOpen"
      class="fixed inset-0 bg-black/50 z-30 lg:hidden"
      @click="sidebarOpen = false"
    ></div>
    <div class="min-h-screen lg:ml-64">
      <header class="bg-white border-b border-gray-200 h-16 flex items-center justify-between px-3 sm:px-6 sticky top-0 z-20">
        <div class="flex items-center gap-2 min-w-0">
          <button
            class="lg:hidden p-2 rounded-md text-gray-600 hover:text-gray-900 hover:bg-gray-100"
            @click="sidebarOpen = true"
          >
            <Menu :size="22" />
          </button>
          <h1 class="text-base sm:text-xl font-semibold text-gray-800 truncate">{{ $route.meta.title || 'Painel do Responsável' }}</h1>
        </div>
        <div class="flex items-center gap-2 sm:gap-4 min-w-0">
          
          <!-- Alternar Perfil -->
          <router-link 
            v-if="user?.is_atleta" 
            to="/athlete/dashboard"
            class="text-xs sm:text-sm font-medium text-blue-600 hover:text-blue-800 flex items-center gap-1 bg-blue-50 px-2 sm:px-3 py-1.5 rounded-full transition-colors whitespace-nowrap"
          >
            <UserCircle :size="18" />
            <span class="hidden sm:inline">Ir para Área do Atleta</span>
            <span class="sm:hidden">Área do Atleta</span>
          </router-link>

          <div class="h-6 w-px bg-gray-300 mx-1 sm:mx-2 hidden sm:block" v-if="user?.is_atleta"></div>

          <div class="text-right hidden md:block">
            <p class="text-sm font-medium text-gray-900 truncate max-w-[220px]">{{ user.name }}</p>
            <p class="text-xs text-gray-500">Responsável por Equipe</p>
          </div>
          <div class="h-9 w-9 sm:h-10 sm:w-10 bg-green-100 rounded-full flex items-center justify-center text-green-700 font-bold shrink-0">
            {{ user.name?.charAt(0).toUpperCase() }}
          </div>
        </div>
      </header>
      <main class="p-4 sm:p-6 lg:p-8">
        <router-view></router-view>
      </main>
    </div>
  </div>
</template>

<script setup>
import ManagerSidebar from '../components/ManagerSidebar.vue';
import { ref, onMounted } from 'vue';
import { UserCircle, Menu } from 'lucide-vue-next';
import authService from '../modules/auth/services/authService';

const user = ref(JSON.parse(localStorage.getItem('user') || '{}'));
const sidebarOpen = ref(false);

onMounted(async () => {
  try {
    const { data } = await authService.me();
    user.value = data;
    localStorage.setItem('user', JSON.stringify(data));
  } catch (error) {
    console.error('Erro ao atualizar dados do usuário:', error);
  }
});
</script>
