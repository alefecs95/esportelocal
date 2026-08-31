<template>
  <div class="h-screen w-64 bg-gray-900 text-white fixed top-0 left-0 overflow-y-auto flex flex-col border-r border-gray-800">
    <div class="p-6 flex items-center gap-3 border-b border-gray-800">
      <div class="bg-green-600 p-2 rounded-lg">
        <Users :size="20" class="text-white" />
      </div>
      <div class="flex flex-col">
        <span class="text-lg font-bold tracking-tight">Área do</span>
        <span class="text-xs text-gray-400 uppercase tracking-wider">Responsável</span>
      </div>
    </div>

    <nav class="flex-1 py-6 px-3 space-y-1">
      <router-link to="/manager" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-300 hover:bg-gray-800 hover:text-white transition-colors" active-class="bg-gray-800 text-white font-medium">
        <LayoutDashboard :size="20" />
        Visão Geral
      </router-link>

      <router-link to="/manager/competicoes" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-300 hover:bg-gray-800 hover:text-white transition-colors" active-class="bg-gray-800 text-white font-medium">
        <Trophy :size="20" />
        Competições Disponíveis
      </router-link>

      <router-link to="/manager/minhas-equipes" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-300 hover:bg-gray-800 hover:text-white transition-colors" active-class="bg-gray-800 text-white font-medium">
        <Shield :size="20" />
        Minhas Equipes
      </router-link>
    </nav>
    
    <div class="p-4 border-t border-gray-800">
      <button @click="logout" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-red-400 hover:bg-gray-800 hover:text-red-300 transition-colors">
        <LogOut :size="20" />
        Sair
      </button>
    </div>
  </div>
</template>

<script setup>
import { LayoutDashboard, Trophy, Shield, Users, LogOut } from 'lucide-vue-next';
import { useRouter } from 'vue-router';
import authService from '../../auth/services/authService';

const router = useRouter();

const logout = async () => {
  try {
    await authService.logout();
  } catch (e) {
    console.error(e);
  } finally {
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    router.push('/login');
  }
};
</script>
