<template>
  <aside
    class="w-64 bg-white border-r border-gray-200 fixed left-0 top-0 bottom-0 z-40 flex flex-col transition-transform duration-200 ease-out lg:translate-x-0"
    :class="mobileOpen ? 'translate-x-0' : '-translate-x-full'"
  >
    <div class="h-16 flex items-center justify-between border-b border-gray-200 px-4">
      <span class="text-xl font-bold text-blue-600">SEMEL Manager</span>
      <button
        @click="emit('close')"
        class="lg:hidden p-2 rounded-md text-gray-500 hover:text-gray-700 hover:bg-gray-100"
      >
        <X :size="20" />
      </button>
    </div>

    <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1">
      <router-link
        to="/manager"
        @click="handleNavigate"
        class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors"
        active-class="bg-blue-50 text-blue-700"
      >
        <LayoutDashboard :size="20" />
        <span class="ml-3 font-medium">Painel</span>
      </router-link>

      <router-link
        to="/manager/competicoes"
        @click="handleNavigate"
        class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors"
        active-class="bg-blue-50 text-blue-700"
      >
        <Trophy :size="20" />
        <span class="ml-3 font-medium">Competições</span>
      </router-link>

      <router-link
        to="/manager/minhas-equipes"
        @click="handleNavigate"
        class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors"
        active-class="bg-blue-50 text-blue-700"
      >
        <Users :size="20" />
        <span class="ml-3 font-medium">Minhas Equipes</span>
      </router-link>

      <router-link
        v-if="user?.is_atleta"
        to="/athlete/dashboard"
        @click="handleNavigate"
        class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors"
      >
        <UserCircle :size="20" />
        <span class="ml-3 font-medium">Área do Atleta</span>
      </router-link>
    </nav>

    <div class="p-4 border-t border-gray-200">
      <button
        @click="handleLogout"
        class="flex items-center w-full px-4 py-3 text-red-600 rounded-lg hover:bg-red-50 transition-colors"
      >
        <LogOut :size="20" />
        <span class="ml-3 font-medium">Sair</span>
      </button>
    </div>
  </aside>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { LayoutDashboard, Trophy, Users, LogOut, UserCircle, X } from 'lucide-vue-next';
import authService from '../modules/auth/services/authService';

defineProps({
  mobileOpen: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits(['close']);
const router = useRouter();
const user = ref(JSON.parse(localStorage.getItem('user') || 'null'));

onMounted(async () => {
    try {
        const { data } = await authService.me();
        user.value = data;
        localStorage.setItem('user', JSON.stringify(data));
    } catch (error) {
        console.error('Erro ao atualizar dados do usuário:', error);
    }
});

const handleLogout = async () => {
  try {
    await authService.logout();
  } catch (error) {
    console.error('Erro ao fazer logout:', error);
  } finally {
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    emit('close');
    router.push('/login');
  }
};

const handleNavigate = () => {
  emit('close');
};
</script>
