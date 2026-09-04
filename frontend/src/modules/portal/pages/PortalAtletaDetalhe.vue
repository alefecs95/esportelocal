<template>
  <div class="overflow-x-clip p-4 max-w-7xl mx-auto">
    <div v-if="loading" class="flex justify-center py-20">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
    </div>

    <div v-else-if="!atleta" class="text-center py-20 bg-gray-50 rounded-lg">
      <p class="text-gray-500 text-lg">Atleta não encontrado.</p>
    </div>

    <div v-else>
      <!-- Header -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 sm:p-8 mb-6 sm:mb-8 flex flex-col md:flex-row items-center gap-5 sm:gap-8">
        <div class="w-24 h-24 sm:w-32 sm:h-32 flex-shrink-0 bg-gray-50 rounded-full flex items-center justify-center overflow-hidden border-2 border-green-500">
          <img :src="atleta.foto_url || '/placeholder-user.png'" class="w-full h-full object-cover" alt="" />
        </div>
        <div class="text-center md:text-left flex-grow min-w-0">
          <h1 class="text-2xl sm:text-3xl font-bold text-gray-800 mb-2 leading-tight break-words">{{ atleta.nome }}</h1>
          <div class="flex flex-wrap justify-center md:justify-start gap-4 text-sm text-gray-600">
            <span class="flex items-center gap-1">
              <User :size="16" /> {{ calculateAge(atleta.data_nascimento) }} anos
            </span>
            <span class="flex items-center gap-1" v-if="atleta.modalidades?.length">
              <Trophy :size="16" /> {{ atleta.modalidades.map(m => m.nome).join(', ') }}
            </span>
          </div>
        </div>
      </div>

      <!-- Abas -->
      <div class="flex border-b border-gray-200 mb-6 sm:mb-8 overflow-x-auto -mx-4 px-4 sm:mx-0 sm:px-0">
        <button 
          v-for="tab in tabs" 
          :key="tab.id"
          @click="activeTab = tab.id"
          class="px-4 sm:px-6 py-3 font-medium text-sm flex items-center gap-2 whitespace-nowrap border-b-2 transition-colors min-h-12"
          :class="activeTab === tab.id ? 'border-green-600 text-green-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
        >
          <component :is="tab.icon" :size="18" /> {{ tab.label }}
        </button>
      </div>

      <!-- Conteúdo: Competições -->
      <div v-if="activeTab === 'competicoes'">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Competição</th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Modalidade</th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ano</th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Equipe</th>
              <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Posição</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="comp in competicoes" :key="comp.campeonato + comp.ano">
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ comp.campeonato }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ comp.modalidade }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ comp.ano }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ comp.equipe }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-bold text-green-600">{{ comp.posicao ? comp.posicao + 'º' : '-' }}</td>
            </tr>
            <tr v-if="!competicoes.length">
                <td colspan="5" class="px-6 py-8 text-center text-gray-500">Nenhuma competição encontrada.</td>
            </tr>
          </tbody>
        </table>
        </div>
        </div>
      </div>

      <!-- Conteúdo: Estatísticas -->
      <div v-else-if="activeTab === 'estatisticas'" class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100 flex flex-col items-center">
            <div class="w-12 h-12 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center mb-4">
                <Target :size="24" />
            </div>
            <span class="text-3xl font-bold text-gray-800">{{ stats?.total_gols || 0 }}</span>
            <span class="text-xs sm:text-sm text-gray-500 uppercase tracking-wide font-medium mt-1 text-center">Total de Gols</span>
        </div>

        <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100 flex flex-col items-center">
            <div class="w-12 h-12 rounded-full bg-yellow-100 text-yellow-600 flex items-center justify-center mb-4">
                <Square :size="24" />
            </div>
            <span class="text-3xl font-bold text-gray-800">{{ stats?.total_amarelos || 0 }}</span>
            <span class="text-sm text-gray-500 uppercase tracking-wide font-medium mt-1">Cartões Amarelos</span>
        </div>

        <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100 flex flex-col items-center">
            <div class="w-12 h-12 rounded-full bg-red-100 text-red-600 flex items-center justify-center mb-4">
                <Square :size="24" />
            </div>
            <span class="text-3xl font-bold text-gray-800">{{ stats?.total_vermelhos || 0 }}</span>
            <span class="text-sm text-gray-500 uppercase tracking-wide font-medium mt-1">Cartões Vermelhos</span>
        </div>
        
        <!-- Histórico de Eventos -->
        <div class="col-span-full bg-white p-6 rounded-lg shadow-sm border border-gray-100 mt-4">
             <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
                <Calendar :size="18" /> Histórico de Eventos
             </h3>
             <div class="text-gray-600">
                <p>Eventos disputados: {{ competicoes.length }}</p>
                <!-- Mais detalhes poderiam ser listados aqui se tivéssemos a tabela de eventos -->
             </div>
        </div>
      </div>

      <!-- Conteúdo: Histórico (Equipes que já jogou) -->
      <div v-else-if="activeTab === 'historico'" class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
         <div class="overflow-x-auto">
         <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Equipe</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ano</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="comp in competicoes" :key="comp.equipe + comp.ano">
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ comp.equipe }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ comp.ano }}</td>
            </tr>
             <tr v-if="!competicoes.length">
                <td colspan="2" class="px-6 py-8 text-center text-gray-500">Nenhum histórico encontrado.</td>
            </tr>
          </tbody>
        </table>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import portalService from '@/modules/portal/services/portalService';
import { User, Trophy, Calendar, Target, Square, History, Activity } from 'lucide-vue-next';

const route = useRoute();
const atleta = ref(null);
const competicoes = ref([]);
const stats = ref(null);
const loading = ref(true);
const activeTab = ref('competicoes');

const tabs = [
  { id: 'competicoes', label: 'Competições', icon: Trophy },
  { id: 'estatisticas', label: 'Estatísticas', icon: Activity },
  { id: 'historico', label: 'Histórico de Equipes', icon: History },
];

const loadData = async () => {
  loading.value = true;
  try {
    const { data } = await portalService.getAtleta(route.params.id);
    atleta.value = data.atleta;
    competicoes.value = data.competicoes || [];
    stats.value = data.stats || {};
  } catch (e) {
    console.error('Erro ao carregar atleta', e);
  } finally {
    loading.value = false;
  }
};

const calculateAge = (dateString) => {
  if (!dateString) return '';
  const today = new Date();
  const birthDate = new Date(dateString);
  let age = today.getFullYear() - birthDate.getFullYear();
  const m = today.getMonth() - birthDate.getMonth();
  if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
    age--;
  }
  return age;
};

onMounted(loadData);
</script>
