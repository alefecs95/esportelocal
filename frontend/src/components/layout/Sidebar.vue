<template>
  <div class="h-screen w-64 bg-gray-900 text-white fixed top-0 left-0 z-40 overflow-y-auto flex flex-col border-r border-gray-800 transform transition-transform duration-200 ease-out lg:translate-x-0" :class="mobileOpen ? 'translate-x-0' : '-translate-x-full'">
    <div class="p-4 sm:p-6 flex items-center gap-3 border-b border-gray-800">
      <div class="bg-primary-600 p-2 rounded-lg">
        <Trophy :size="20" class="text-white" />
      </div>
      <span class="text-xl font-bold tracking-tight">SEMEL</span>
      <button @click="emit('close')" class="ml-auto lg:hidden p-2 rounded-md text-gray-400 hover:text-white hover:bg-gray-800">
        ✕
      </button>
    </div>

    <nav class="flex-1 py-6 px-3 space-y-1" @click="handleNavigate">
      
      <!-- MENU EXCLUSIVO PARA ARBITRO -->
      <template v-if="isArbitro">
          <router-link to="/admin/arbitro/minhas-partidas" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-300 hover:bg-gray-800 hover:text-white transition-colors" active-class="bg-gray-800 text-white font-medium">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-whistle"><path d="M12 2v2"/><path d="M12 2c2.76 0 5 2.24 5 5v1c0 2.76-2.24 5-5 5S7 10.76 7 16a5 5 0 0 0 5 5"/><path d="M12 21a5 5 0 0 0 5-5v-1c0-2.76-2.24-5-5-5"/><path d="M14.5 2C15.8 2 17 3.2 17 4.5V9a2 2 0 0 1-2 2h-6a2 2 0 0 1-2-2V4.5C7 3.2 8.2 2 9.5 2Z"/></svg>
            Painel do Árbitro
          </router-link>
      </template>

      <template v-else-if="isRedator">
        <router-link to="/admin/noticias" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-300 hover:bg-gray-800 hover:text-white transition-colors" active-class="bg-gray-800 text-white font-medium">
          <FileText :size="20" />
          Notícias
        </router-link>
      </template>

      <!-- MENU PARA ADMIN (original) -->
      <template v-else>
        <router-link to="/admin/dashboard" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-300 hover:bg-gray-800 hover:text-white transition-colors" active-class="bg-gray-800 text-white font-medium">
          <LayoutDashboard :size="20" />
          Dashboard
        </router-link>

        <router-link to="/admin/calendario" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-300 hover:bg-gray-800 hover:text-white transition-colors" active-class="bg-gray-800 text-white font-medium">
          <Calendar :size="20" />
          Calendário
        </router-link>

        <div class="pt-4 pb-2 px-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">
          Gestão
        </div>

        <router-link to="/admin/temporadas" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-300 hover:bg-gray-800 hover:text-white transition-colors" active-class="bg-gray-800 text-white font-medium">
          <History :size="20" />
          Temporadas
        </router-link>

        <router-link to="/admin/atletas" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-300 hover:bg-gray-800 hover:text-white transition-colors" active-class="bg-gray-800 text-white font-medium">
          <Users :size="20" />
          Atletas
        </router-link>

        <router-link to="/admin/entidades" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-300 hover:bg-gray-800 hover:text-white transition-colors" active-class="bg-gray-800 text-white font-medium">
          <Shield :size="20" />
          Clubes/Entidades
        </router-link>

        <router-link to="/admin/noticias" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-300 hover:bg-gray-800 hover:text-white transition-colors" active-class="bg-gray-800 text-white font-medium">
          <FileText :size="20" />
          Notícias
        </router-link>

        <div class="pt-4 pb-2 px-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">
          Competições
        </div>

        <router-link to="/admin/modalidades" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-300 hover:bg-gray-800 hover:text-white transition-colors" active-class="bg-gray-800 text-white font-medium">
          <Dumbbell :size="20" />
          Modalidades
        </router-link>

        <router-link to="/admin/competicoes" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-300 hover:bg-gray-800 hover:text-white transition-colors" active-class="bg-gray-800 text-white font-medium">
          <Trophy :size="20" />
          Competições
        </router-link>

        <router-link to="/admin/arbitro/minhas-partidas" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-300 hover:bg-gray-800 hover:text-white transition-colors" active-class="bg-gray-800 text-white font-medium">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-whistle"><path d="M12 2v2"/><path d="M12 2c2.76 0 5 2.24 5 5v1c0 2.76-2.24 5-5 5S7 10.76 7 16a5 5 0 0 0 5 5"/><path d="M12 21a5 5 0 0 0 5-5v-1c0-2.76-2.24-5-5-5"/><path d="M14.5 2C15.8 2 17 3.2 17 4.5V9a2 2 0 0 1-2 2h-6a2 2 0 0 1-2-2V4.5C7 3.2 8.2 2 9.5 2Z"/></svg>
          Painel do Árbitro
        </router-link>

        <router-link to="/admin/inscricoes" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-300 hover:bg-gray-800 hover:text-white transition-colors" active-class="bg-gray-800 text-white font-medium">
          <FileText :size="20" />
          Inscrições
        </router-link>

        <div class="pt-4 pb-2 px-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">
          Relatórios
        </div>

        <router-link to="/admin/ranking" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-300 hover:bg-gray-800 hover:text-white transition-colors" active-class="bg-gray-800 text-white font-medium">
          <BarChart3 :size="20" />
          Ranking Geral
        </router-link>

        <router-link to="/admin/ranking/modalidade" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-300 hover:bg-gray-800 hover:text-white transition-colors" active-class="bg-gray-800 text-white font-medium">
          <TrendingUp :size="20" />
          Ranking Mod.
        </router-link>

        <div class="pt-4 pb-2 px-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">
          Configurações
        </div>

        <router-link to="/admin/usuarios" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-300 hover:bg-gray-800 hover:text-white transition-colors" active-class="bg-gray-800 text-white font-medium">
          <Users :size="20" />
          Usuários
        </router-link>

        <router-link to="/admin/diretoria" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-300 hover:bg-gray-800 hover:text-white transition-colors" active-class="bg-gray-800 text-white font-medium">
          <Briefcase :size="20" />
          Diretoria
        </router-link>

        <router-link to="/admin/institucional" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-300 hover:bg-gray-800 hover:text-white transition-colors" active-class="bg-gray-800 text-white font-medium">
          <Building :size="20" />
          Institucional
        </router-link>
      </template>
    </nav>
    
    <div class="p-4 border-t border-gray-800">
      <router-link to="/" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-400 hover:bg-gray-800 hover:text-white transition-colors">
        <LogOut :size="20" />
        Sair para Portal
      </router-link>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { 
  LayoutDashboard, 
  Calendar, 
  History, 
  Users, 
  Shield, 
  Dumbbell, 
  Trophy, 
  FileText, 
  BarChart3, 
  TrendingUp,
  LogOut,
  Building,
  Briefcase
} from 'lucide-vue-next';

defineProps({
  mobileOpen: {
    type: Boolean,
    default: false
  }
});
const emit = defineEmits(['close']);

const user = JSON.parse(localStorage.getItem('user') || '{}');
const isArbitro = computed(() => user?.perfil === 'ARBITRO');
const isRedator = computed(() => user?.perfil === 'REDATOR');

const handleNavigate = () => {
  emit('close');
};
</script>
