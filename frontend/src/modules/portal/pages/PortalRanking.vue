<template>
  <div class="container mx-auto px-4 py-8">
    <div class="bg-white rounded-lg shadow-sm p-6 mb-8">
      <h1 class="text-3xl font-bold text-blue-900 mb-2">Ranking Municipal {{ temporadaAtiva ? temporadaAtiva.ano : '' }}</h1>
      <p class="text-gray-600">Confira a classificação geral dos atletas e equipes na temporada atual.</p>
    </div>

    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
      <div class="border-b border-gray-200">
        <nav class="-mb-px flex">
          <button
            @click="setType('atleta')"
            :class="[
              type === 'atleta'
                ? 'border-blue-500 text-blue-600'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
              'w-1/2 py-4 px-1 text-center border-b-2 font-medium text-lg'
            ]"
          >
            Atletas
          </button>
          <button
            @click="setType('equipe')"
            :class="[
              type === 'equipe'
                ? 'border-blue-500 text-blue-600'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
              'w-1/2 py-4 px-1 text-center border-b-2 font-medium text-lg'
            ]"
          >
            Equipes
          </button>
        </nav>
      </div>

      <div class="p-6">
        <div v-if="loading" class="flex justify-center py-12">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
        </div>

        <div v-else-if="error" class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded relative">
          {{ error }}
        </div>

        <RankingTable v-else :ranking-data="rankingData" :type="type" />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import RankingTable from '../../ranking/components/RankingTable.vue';
import * as rankingService from '../../ranking/services/rankingService';

const rankingData = ref([]);
const temporadaAtiva = ref(null);
const loading = ref(false);
const error = ref(null);
const type = ref('atleta');

const fetchData = async () => {
  loading.value = true;
  error.value = null;
  try {
    // 1. Get active season if not already loaded
    if (!temporadaAtiva.value) {
      const { data } = await rankingService.getTemporadaAtiva();
      temporadaAtiva.value = data;
    }

    if (temporadaAtiva.value && temporadaAtiva.value.id) {
      // 2. Get Ranking
      const { data } = await rankingService.getRankingGeral(temporadaAtiva.value.id, { type: type.value });
      rankingData.value = data || [];
    } else {
        error.value = 'Nenhuma temporada ativa encontrada.';
    }
  } catch (e) {
    console.error('Erro ao carregar ranking:', e);
    error.value = 'Não foi possível carregar o ranking. Tente novamente mais tarde.';
  } finally {
    loading.value = false;
  }
};

const setType = (newType) => {
  type.value = newType;
  fetchData();
};

onMounted(() => {
  fetchData();
});
</script>
