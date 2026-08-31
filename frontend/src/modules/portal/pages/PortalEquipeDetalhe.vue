<template>
  <div class="p-4 max-w-7xl mx-auto">
    <div v-if="loading" class="flex justify-center py-20">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
    </div>

    <div v-else-if="!equipe" class="text-center py-20 bg-gray-50 rounded-lg">
      <p class="text-gray-500 text-lg">Equipe não encontrada.</p>
    </div>

    <div v-else>
      <!-- Header -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8 mb-8 flex flex-col md:flex-row items-center gap-8">
        <div class="w-32 h-32 flex-shrink-0 bg-gray-50 rounded-full flex items-center justify-center p-4 border border-gray-100">
          <img :src="equipe.logo_url || equipe.entidade?.logo_url || '/placeholder-shield.png'" class="w-full h-full object-contain" />
        </div>
        <div class="text-center md:text-left flex-grow">
          <h1 class="text-3xl font-bold text-gray-800 mb-2">{{ equipe.nome }}</h1>
          <div class="flex flex-wrap justify-center md:justify-start gap-4 text-sm text-gray-600">
            <span class="flex items-center gap-1">
              <MapPin :size="16" /> {{ equipe.entidade?.cidade || 'Cidade não informada' }}
            </span>
            <span class="flex items-center gap-1">
              <Trophy :size="16" /> {{ equipe.campeonato?.modalidade?.nome }}
            </span>
            <span class="flex items-center gap-1">
              <Calendar :size="16" /> {{ equipe.entidade?.data_fundacao ? new Date(equipe.entidade.data_fundacao).getFullYear() : 'Ano não informado' }}
            </span>
          </div>
        </div>
      </div>

      <!-- Abas -->
      <div class="flex border-b border-gray-200 mb-8 overflow-x-auto">
        <button 
          v-for="tab in tabs" 
          :key="tab.id"
          @click="activeTab = tab.id"
          class="px-6 py-3 font-medium text-sm flex items-center gap-2 whitespace-nowrap border-b-2 transition-colors"
          :class="activeTab === tab.id ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
        >
          <component :is="tab.icon" :size="18" /> {{ tab.label }}
        </button>
      </div>

      <!-- Conteúdo: Elenco -->
      <div v-if="activeTab === 'elenco'" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
        <div 
          v-for="atleta in equipe.atletas_aprovados" 
          :key="atleta.id"
          class="bg-white rounded-lg shadow-sm p-4 flex items-center gap-4 border border-gray-100 hover:shadow-md transition-shadow"
        >
          <img :src="atleta.foto_url || '/placeholder-user.png'" class="w-16 h-16 rounded-full object-cover bg-gray-100" />
          <div>
            <h3 class="font-bold text-gray-800 line-clamp-1">{{ atleta.nome }}</h3>
            <p class="text-xs text-gray-500">Atleta</p>
          </div>
        </div>
        <div v-if="!equipe.atletas_aprovados?.length" class="col-span-full text-center py-8 text-gray-500">
          Nenhum atleta cadastrado neste elenco.
        </div>
      </div>

      <!-- Conteúdo: Competições -->
      <div v-else-if="activeTab === 'competicoes'" class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Campeonato</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ano</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Modalidade</th>
              <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Colocação</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="comp in historicoCompeticoes" :key="comp.id">
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ comp.campeonato_nome }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ comp.ano }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ comp.modalidade }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-bold text-blue-600">{{ comp.colocacao ? comp.colocacao + 'º' : '-' }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Conteúdo: Estatísticas (placeholder simples pois backend não retorna estatísticas detalhadas ainda) -->
      <div v-else-if="activeTab === 'estatisticas'" class="bg-white rounded-lg shadow-sm border border-gray-200 p-8 text-center text-gray-500">
        <p>Estatísticas detalhadas em breve.</p>
      </div>

      <!-- Conteúdo: Confrontos Recentes -->
      <div v-else-if="activeTab === 'confrontos'" class="space-y-4">
        <div v-for="jogo in ultimosJogos" :key="jogo.id" class="bg-white rounded-lg shadow-sm border border-gray-100 p-4 flex items-center justify-between">
            <div class="text-sm text-gray-500 w-24">{{ formatDate(jogo.data) }}</div>
            
            <div class="flex-1 flex items-center justify-center gap-4">
                <span class="font-bold text-gray-800 text-right w-1/3 truncate">{{ jogo.is_mandante ? equipe.nome : jogo.adversario_nome }}</span>
                <div class="flex items-center gap-2 bg-gray-100 px-3 py-1 rounded-lg font-mono font-bold text-lg">
                    <span>{{ jogo.placar_mandante }}</span>
                    <span class="text-gray-400">x</span>
                    <span>{{ jogo.placar_visitante }}</span>
                </div>
                <span class="font-bold text-gray-800 text-left w-1/3 truncate">{{ jogo.is_mandante ? jogo.adversario_nome : equipe.nome }}</span>
            </div>

            <div class="w-10 flex justify-center">
                <span 
                    class="w-8 h-8 flex items-center justify-center rounded-full font-bold text-xs text-white"
                    :class="{
                        'bg-green-500': jogo.resultado === 'V',
                        'bg-gray-400': jogo.resultado === 'E',
                        'bg-red-500': jogo.resultado === 'D'
                    }"
                >
                    {{ jogo.resultado }}
                </span>
            </div>
        </div>
        <div v-if="!ultimosJogos.length" class="text-center py-8 text-gray-500">
            Nenhum jogo recente encontrado.
        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import portalService from '@/modules/portal/services/portalService';
import { MapPin, Trophy, Calendar, Users, Activity, Target } from 'lucide-vue-next';

const route = useRoute();
const equipe = ref(null);
const historicoCompeticoes = ref([]);
const ultimosJogos = ref([]);
const loading = ref(true);
const activeTab = ref('elenco');

const tabs = [
  { id: 'elenco', label: 'Elenco', icon: Users },
  { id: 'competicoes', label: 'Competições', icon: Trophy },
  { id: 'estatisticas', label: 'Estatísticas', icon: Activity },
  { id: 'confrontos', label: 'Confrontos', icon: Target },
];

const loadData = async () => {
  loading.value = true;
  try {
    const { data } = await portalService.getEquipe(route.params.id);
    equipe.value = data.equipe;
    historicoCompeticoes.value = data.historico_competicoes || [];
    ultimosJogos.value = data.ultimos_jogos || [];
  } catch (e) {
    console.error('Erro ao carregar equipe', e);
  } finally {
    loading.value = false;
  }
};

const formatDate = (dateString) => {
  if (!dateString) return '';
  return new Date(dateString).toLocaleDateString('pt-BR');
};

onMounted(loadData);
</script>
