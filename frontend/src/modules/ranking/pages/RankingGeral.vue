<template>
  <div class="space-y-6">
    <div class="flex justify-between items-center">
      <h1 class="text-2xl font-bold text-gray-900">Ranking Geral</h1>
      <div class="flex space-x-4 items-center">
        <button
          v-if="isTemporadaAtiva"
          type="button"
          @click="handleRecalculate"
          :disabled="recalculating"
          class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
        >
          <span v-if="recalculating" class="mr-2 animate-spin">🔄</span>
          <span v-else class="mr-2">🔄</span>
          {{ recalculating ? 'Recalculando...' : 'Recalcular Ranking' }}
        </button>

        <select v-model="selectedTemporada" @change="fetchData" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
          <option v-for="temp in temporadas" :key="temp.id" :value="temp.id">
            {{ temp.nome }}
          </option>
        </select>
        <div class="flex rounded-md shadow-sm">
          <button
            type="button"
            @click="setType('atleta')"
            :class="[
              type === 'atleta' ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50',
              'relative inline-flex items-center px-4 py-2 rounded-l-md border border-gray-300 text-sm font-medium focus:z-10 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500'
            ]"
          >
            Atletas
          </button>
          <button
            type="button"
            @click="setType('equipe')"
            :class="[
              type === 'equipe' ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50',
              'relative -ml-px inline-flex items-center px-4 py-2 rounded-r-md border border-gray-300 text-sm font-medium focus:z-10 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500'
            ]"
          >
            Equipes
          </button>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <Top10Card :rankingData="top10" :type="type" :temporadaLabel="temporadaLabel" />
      <!-- Poderia ter outro card aqui ou gráfico -->
    </div>

    <div class="bg-white shadow rounded-lg p-6">
      <h2 class="text-lg font-medium text-gray-900 mb-4">Classificação Completa</h2>
      <RankingTable :rankingData="rankingGeral" :type="type" />
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { useRankingStore } from '../store/rankingStore';
import { useTemporadaStore } from '@/modules/temporadas/store/temporadaStore';
import RankingTable from '../components/RankingTable.vue';
import Top10Card from '../components/Top10Card.vue';
import { storeToRefs } from 'pinia';

const rankingStore = useRankingStore();
const temporadaStore = useTemporadaStore();

const { rankingGeral, top10, recalculating } = storeToRefs(rankingStore);
const { temporadas, currentTemporada } = storeToRefs(temporadaStore);

const selectedTemporada = ref(null);
const type = ref('atleta');

const temporadaLabel = computed(() => {
  const temp = temporadas.value.find(t => t.id === selectedTemporada.value);
  return temp ? temp.nome : '';
});

const isTemporadaAtiva = computed(() => {
  const temp = temporadas.value.find(t => t.id === selectedTemporada.value);
  return temp ? temp.status === 'ativa' : false;
});

onMounted(async () => {
  await temporadaStore.fetchTemporadas();
  if (currentTemporada.value) {
    selectedTemporada.value = currentTemporada.value.id;
  } else if (temporadas.value.length > 0) {
    selectedTemporada.value = temporadas.value[0].id;
  }
  
  if (selectedTemporada.value) {
    fetchData();
  }
});

const fetchData = () => {
  if (!selectedTemporada.value) return;
  rankingStore.fetchRankingGeral(selectedTemporada.value, { type: type.value });
  rankingStore.fetchTop10(selectedTemporada.value, { type: type.value });
};

const handleRecalculate = async () => {
  if (!confirm('Tem certeza que deseja recalcular o ranking da temporada ativa? Isso atualizará todos os dados com base nos campeonatos finalizados.')) {
    return;
  }
  
  try {
    await rankingStore.recalcularRanking();
    alert('Ranking recalculado com sucesso!');
    fetchData(); // Atualiza a visualização
  } catch (error) {
    alert('Erro ao recalcular ranking. Verifique o console para mais detalhes.');
  }
};

const setType = (newType) => {
  type.value = newType;
  fetchData();
};
</script>
