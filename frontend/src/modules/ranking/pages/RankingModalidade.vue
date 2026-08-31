<template>
  <div class="space-y-6">
    <div class="flex flex-col md:flex-row justify-between items-center gap-4">
      <h1 class="text-2xl font-bold text-gray-900">Ranking por Modalidade</h1>
      <div class="flex flex-wrap gap-4">
        <select v-model="selectedTemporada" @change="fetchData" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
          <option v-for="temp in temporadas" :key="temp.id" :value="temp.id">
            {{ temp.nome }}
          </option>
        </select>
        
        <select v-model="selectedModalidade" @change="fetchData" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm min-w-[200px]">
          <option :value="null" disabled>Selecione uma modalidade</option>
          <option v-for="mod in modalidades" :key="mod.id" :value="mod.id">
            {{ mod.nome }}
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

    <div v-if="selectedModalidade" class="bg-white shadow rounded-lg p-6">
      <h2 class="text-lg font-medium text-gray-900 mb-4">
        Classificação - {{ modalidadeLabel }}
      </h2>
      <RankingTable :rankingData="rankingModalidade" :type="type" />
    </div>
    
    <div v-else class="text-center py-10 text-gray-500">
      Selecione uma modalidade para visualizar o ranking.
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRankingStore } from '../store/rankingStore';
import { useTemporadaStore } from '@/modules/temporadas/store/temporadaStore';
import { useModalidadeStore } from '@/modules/modalidades/store/modalidadeStore';
import RankingTable from '../components/RankingTable.vue';
import { storeToRefs } from 'pinia';

const rankingStore = useRankingStore();
const temporadaStore = useTemporadaStore();
const modalidadeStore = useModalidadeStore();

const { rankingModalidade } = storeToRefs(rankingStore);
const { temporadas, currentTemporada } = storeToRefs(temporadaStore);
const { modalidades } = storeToRefs(modalidadeStore);

const selectedTemporada = ref(null);
const selectedModalidade = ref(null);
const type = ref('atleta');

const modalidadeLabel = computed(() => {
  const mod = modalidades.value.find(m => m.id === selectedModalidade.value);
  return mod ? mod.nome : '';
});

onMounted(async () => {
  await Promise.all([
    temporadaStore.fetchTemporadas(),
    modalidadeStore.fetchModalidades()
  ]);
  
  if (currentTemporada.value) {
    selectedTemporada.value = currentTemporada.value.id;
  } else if (temporadas.value.length > 0) {
    selectedTemporada.value = temporadas.value[0].id;
  }
});

const fetchData = () => {
  if (!selectedTemporada.value || !selectedModalidade.value) return;
  rankingStore.fetchRankingPorModalidade(selectedTemporada.value, selectedModalidade.value, { type: type.value });
};

const setType = (newType) => {
  type.value = newType;
  fetchData();
};
</script>
