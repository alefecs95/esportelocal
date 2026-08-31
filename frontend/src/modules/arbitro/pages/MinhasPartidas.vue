<template>
  <div class="max-w-7xl mx-auto px-3 sm:px-4 py-4 sm:py-8">
    <div class="mb-5 sm:mb-8">
      <h1 class="text-xl sm:text-2xl font-bold text-gray-800">Minhas Partidas</h1>
      <p class="text-sm sm:text-base text-gray-600">Atualize placares e opere a súmula eletrônica.</p>
    </div>

    <div v-if="loading" class="text-center py-12">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto mb-4"></div>
      <p class="text-gray-500">Carregando partidas...</p>
    </div>

    <div v-else-if="error" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
      {{ error }}
    </div>

    <div v-else>
      <div v-if="partidas.length === 0" class="text-center py-12 bg-white rounded-lg shadow-sm">
        <p class="text-gray-500 text-lg">Nenhuma partida atribuída a você.</p>
      </div>

      <div class="grid grid-cols-1 xl:grid-cols-2 2xl:grid-cols-3 gap-4 sm:gap-6">
        <PartidaArbitroCard 
          v-for="partida in partidas" 
          :key="partida.id" 
          :partida="partida"
          @update="handleUpdate"
        />
      </div>
    </div>
  </div>
</template>

<script>
import { onMounted, computed } from 'vue';
import { useToast } from 'vue-toastification';
import { useArbitroStore } from '../store/arbitroStore';
import PartidaArbitroCard from '../components/PartidaArbitroCard.vue';

export default {
  components: { PartidaArbitroCard },
  setup() {
    const store = useArbitroStore();
    const toast = useToast();

    onMounted(() => {
      store.fetchMinhasPartidas();
    });

    const handleUpdate = async (payload) => {
      try {
        await store.atualizarPlacar(
          payload.id, 
          payload.placar_mandante, 
          payload.placar_visitante, 
          payload.finalizar
        );
        toast.success('Placar atualizado com sucesso.');
      } catch (error) {
        toast.error('Erro ao atualizar: ' + (error.response?.data?.error || error.message));
      }
    };

    return {
      partidas: computed(() => store.partidas),
      loading: computed(() => store.loading),
      error: computed(() => store.error),
      handleUpdate
    };
  }
}
</script>
