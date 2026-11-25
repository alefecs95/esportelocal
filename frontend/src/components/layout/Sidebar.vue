<template>
  <div class="h-dvh w-64 bg-gray-900 text-white fixed top-0 left-0 z-40 overflow-y-auto overscroll-contain flex flex-col border-r border-gray-800 pt-[env(safe-area-inset-top,0px)] pb-[env(safe-area-inset-bottom,0px)] transform transition-transform duration-300 ease-drawer lg:translate-x-0" :class="mobileOpen ? 'translate-x-0' : '-translate-x-full'">
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
      <template v-if="isArbitro">
        <router-link to="/admin/arbitro/minhas-partidas" class="nav-link" active-class="bg-gray-800 text-white font-medium">
          <Trophy :size="20" />
          Painel do Árbitro
        </router-link>
      </template>

      <template v-else-if="isRedator">
        <router-link to="/admin/noticias" class="nav-link" active-class="bg-gray-800 text-white font-medium">
          <FileText :size="20" />
          Notícias
        </router-link>
        <router-link to="/admin/memoria" class="nav-link" active-class="bg-gray-800 text-white font-medium">
          <Landmark :size="20" />
          Memória
        </router-link>
      </template>

      <template v-else>
        <p class="section-label">Início</p>
        <router-link to="/admin/dashboard" class="nav-link" active-class="bg-gray-800 text-white font-medium">
          <LayoutDashboard :size="20" />
          Painel
        </router-link>
        <router-link to="/admin/calendario" class="nav-link" active-class="bg-gray-800 text-white font-medium">
          <Calendar :size="20" />
          Calendário
        </router-link>

        <p class="section-label">Esportes</p>
        <router-link to="/admin/atletas" class="nav-link" active-class="bg-gray-800 text-white font-medium">
          <Users :size="20" />
          Atletas
        </router-link>
        <router-link to="/admin/entidades" class="nav-link" active-class="bg-gray-800 text-white font-medium">
          <Shield :size="20" />
          Clubes
        </router-link>
        <router-link to="/admin/competicoes" class="nav-link" active-class="bg-gray-800 text-white font-medium">
          <Trophy :size="20" />
          Competições
        </router-link>
        <router-link to="/admin/inscricoes" class="nav-link" active-class="bg-gray-800 text-white font-medium">
          <ClipboardList :size="20" />
          Inscrições
        </router-link>

        <button
          type="button"
          class="nav-link w-full justify-between"
          @click.stop="configurarAberto = !configurarAberto"
        >
          <span class="flex items-center gap-3">
            <Settings :size="20" />
            Configurar
          </span>
          <ChevronDown :size="16" class="transition-transform duration-200 ease-in-out-strong" :class="configurarAberto ? 'rotate-180' : ''" />
        </button>
        <div v-if="configurarAberto" class="ml-4 space-y-1">
          <router-link to="/admin/temporadas" class="nav-link text-sm" active-class="bg-gray-800 text-white font-medium">
            <History :size="18" />
            Temporadas
          </router-link>
          <router-link to="/admin/modalidades" class="nav-link text-sm" active-class="bg-gray-800 text-white font-medium">
            <Dumbbell :size="18" />
            Modalidades
          </router-link>
        </div>

        <p class="section-label">Comunicação</p>
        <router-link to="/admin/noticias" class="nav-link" active-class="bg-gray-800 text-white font-medium">
          <FileText :size="20" />
          Notícias
        </router-link>
        <router-link to="/admin/memoria" class="nav-link" active-class="bg-gray-800 text-white font-medium">
          <Landmark :size="20" />
          Memória
        </router-link>

        <p class="section-label">Relatórios</p>
        <router-link to="/admin/ranking" class="nav-link" active-class="bg-gray-800 text-white font-medium">
          <BarChart3 :size="20" />
          Ranking
        </router-link>

        <p class="section-label">Configurações</p>
        <router-link to="/admin/usuarios" class="nav-link" active-class="bg-gray-800 text-white font-medium">
          <Users :size="20" />
          Usuários
        </router-link>
        <router-link to="/admin/diretoria" class="nav-link" active-class="bg-gray-800 text-white font-medium">
          <Briefcase :size="20" />
          Diretoria
        </router-link>
        <router-link to="/admin/institucional" class="nav-link" active-class="bg-gray-800 text-white font-medium">
          <Building :size="20" />
          Institucional
        </router-link>
      </template>
    </nav>

    <div class="p-4 border-t border-gray-800">
      <router-link to="/" class="nav-link text-gray-400">
        <LogOut :size="20" />
        Ver portal
      </router-link>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { useRoute } from 'vue-router';
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
  LogOut,
  Building,
  Briefcase,
  Landmark,
  ClipboardList,
  Settings,
  ChevronDown,
} from 'lucide-vue-next';

defineProps({
  mobileOpen: {
    type: Boolean,
    default: false,
  },
});
const emit = defineEmits(['close']);
const route = useRoute();

const user = JSON.parse(localStorage.getItem('user') || '{}');
const isArbitro = computed(() => user?.perfil === 'ARBITRO');
const isRedator = computed(() => user?.perfil === 'REDATOR');
const configurarAberto = ref(
  route.path.startsWith('/admin/temporadas') || route.path.startsWith('/admin/modalidades')
);

const handleNavigate = (event) => {
  if (event.target.closest('a')) {
    emit('close');
  }
};
</script>

<style scoped>
.nav-link {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.625rem 0.75rem;
  border-radius: 0.5rem;
  color: #d1d5db;
  transition: background-color 160ms ease, color 160ms ease;
}
.nav-link:active {
  background-color: #1f2937;
  color: #fff;
}
@media (hover: hover) and (pointer: fine) {
  .nav-link:hover {
    background-color: #1f2937;
    color: #fff;
  }
}
.section-label {
  padding: 1rem 0.75rem 0.5rem;
  font-size: 0.75rem;
  font-weight: 600;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}
</style>
