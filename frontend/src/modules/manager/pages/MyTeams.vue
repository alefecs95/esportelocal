<template>
  <div class="space-y-6">
    <div class="flex justify-between items-center">
      <h1 class="text-2xl font-bold text-gray-800">Minhas Equipes</h1>
      <router-link 
        to="/manager/competicoes" 
        class="bg-blue-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-blue-700 flex items-center gap-2"
      >
        <PlusCircle :size="18" />
        Nova Equipe
      </router-link>
    </div>

    <div v-if="loading" class="flex justify-center py-12">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
    </div>

    <div v-else-if="equipes.length === 0" class="text-center py-12 bg-white rounded-lg border border-gray-200">
      <p class="text-gray-500">Você ainda não possui equipes cadastradas.</p>
    </div>

    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <div v-for="equipe in equipes" :key="equipe.id" class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden flex flex-col relative">
        <div class="absolute top-4 right-4">
          <span 
            :class="getStatusBadge(equipe.status)" 
            class="px-2.5 py-0.5 rounded-full text-xs font-semibold uppercase tracking-wide"
          >
            {{ equipe.status || 'Pendente' }}
          </span>
        </div>
        
        <div class="p-6 flex-1">
          <h3 class="text-xl font-bold text-gray-900 mb-1">{{ equipe.nome }}</h3>
          <p class="text-sm text-gray-500 mb-4">{{ equipe.campeonato?.nome }}</p>
          
          <div class="space-y-2 text-sm text-gray-600 mb-6">
            <div class="flex items-center gap-2">
              <Users :size="16" />
              <span>{{ equipe.atletas_count || 0 }} Atletas inscritos</span>
            </div>
            <div class="flex items-center gap-2">
              <Phone :size="16" />
              <span>{{ equipe.contato || 'Sem contato' }}</span>
            </div>
          </div>
        </div>
        
        <div class="bg-gray-50 px-6 py-4 border-t border-gray-100 flex gap-2">
          <button 
            @click="manageTeam(equipe)"
            :disabled="equipe.status !== 'aprovada'"
            class="flex-1 bg-white border border-gray-300 text-gray-700 py-2 rounded-lg font-medium hover:bg-gray-50 transition-colors flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <Settings :size="16" />
            Gerenciar
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import managerService from '../services/managerService';
import { PlusCircle, Users, Phone, Settings } from 'lucide-vue-next';

const router = useRouter();
const equipes = ref([]);
const loading = ref(true);

const loadData = async () => {
  loading.value = true;
  try {
    const { data } = await managerService.getMyTeams();
    equipes.value = data;
  } catch (e) {
    console.error(e);
  } finally {
    loading.value = false;
  }
};

const getStatusBadge = (status) => {
  const map = {
    'pendente': 'bg-yellow-100 text-yellow-800',
    'aprovada': 'bg-green-100 text-green-800',
    'rejeitada': 'bg-red-100 text-red-800'
  };
  return map[status] || 'bg-gray-100 text-gray-800';
};

const manageTeam = (equipe) => {
  router.push(`/manager/equipes/${equipe.id}`);
};

onMounted(loadData);
</script>
