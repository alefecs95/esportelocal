<template>
  <div class="min-h-dvh bg-gray-50 flex flex-col">
    <!-- Navbar -->
    <header class="bg-white shadow-sm z-10 pt-[env(safe-area-inset-top,0px)]">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
          <div class="flex">
            <div class="flex-shrink-0 flex items-center">
              <span class="text-xl font-bold text-blue-600">Área do Atleta</span>
            </div>
          </div>
          <div class="flex items-center space-x-4">
            <router-link 
              to="/athlete/dashboard" 
              class="text-sm font-medium text-gray-700 hover:text-blue-600 flex items-center gap-1 transition-colors"
              active-class="text-blue-600 font-bold"
            >
              <LayoutDashboard :size="18" />
              Início
            </router-link>

            <router-link 
              to="/athlete/profile" 
              class="text-sm font-medium text-gray-700 hover:text-blue-600 flex items-center gap-1 transition-colors"
              active-class="text-blue-600 font-bold"
            >
              <User :size="18" />
              Meu Perfil
            </router-link>

            <div class="h-6 w-px bg-gray-300 mx-2"></div>

            <!-- Botão Voltar para Responsável -->
            <router-link 
              v-if="user?.is_manager" 
              to="/manager" 
              class="text-sm font-medium text-blue-600 hover:text-blue-800 flex items-center gap-1 bg-blue-50 px-3 py-1.5 rounded-full transition-colors"
            >
              <Briefcase :size="18" />
              Painel do Responsável
            </router-link>

            <div class="h-6 w-px bg-gray-300 mx-2" v-if="user?.is_manager"></div>

            <div class="text-sm text-gray-700">
              Olá, <span class="font-semibold">{{ user?.name || 'Atleta' }}</span>
            </div>
            <button 
              @click="handleLogout" 
              class="flex items-center text-gray-500 hover:text-red-600 transition-colors"
              title="Sair"
            >
              <LogOut :size="20" />
              <span class="ml-2 hidden sm:inline">Sair</span>
            </button>
          </div>
        </div>
      </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <router-view></router-view>
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-gray-200 mt-auto">
      <div class="max-w-7xl mx-auto pt-6 pb-[calc(1.5rem+env(safe-area-inset-bottom,0px))] px-4 sm:px-6 lg:px-8">
        <p class="text-center text-sm text-gray-500">
          &copy; {{ new Date().getFullYear() }} SEMEL. Todos os direitos reservados.
        </p>
      </div>
    </footer>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import { LogOut, LayoutDashboard, User, Briefcase } from 'lucide-vue-next';
import authService from '../../auth/services/authService';

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
    router.push('/login');
  }
};
</script>
