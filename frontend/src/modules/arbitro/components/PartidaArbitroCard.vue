<template>
  <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-200">
    <div class="bg-gray-50 px-3 sm:px-4 py-2 border-b flex justify-between items-center gap-2">
      <span class="text-xs font-bold text-gray-500 uppercase tracking-wide min-w-0 truncate">
        {{ partida.fase?.campeonato?.nome }} - {{ partida.fase?.nome }}
      </span>
      <span 
        class="text-xs px-2 py-1 rounded-full font-medium"
        :class="{
          'bg-green-100 text-green-800': partida.status === 'finalizada',
          'bg-yellow-100 text-yellow-800': partida.status === 'em_andamento',
          'bg-gray-100 text-gray-800': partida.status === 'agendada'
        }"
      >
        {{ formatStatus(partida.status) }}
      </span>
    </div>

    <div class="p-4 sm:p-6">
      <div class="grid grid-cols-[1fr_auto_1fr] items-center gap-2 sm:gap-3 mb-4 sm:mb-6">
        <!-- Mandante -->
        <div class="text-center min-w-0">
          <h3 class="font-bold text-base sm:text-lg text-gray-800 mb-1 truncate" :title="partida.mandante?.nome">
            {{ partida.mandante?.nome || 'A definir' }}
          </h3>
          <span class="text-xs text-gray-500">Mandante</span>
        </div>

        <!-- Placar -->
        <div class="mx-0 sm:mx-4 flex items-center gap-2 sm:gap-3">
          <input 
            type="number" 
            v-model.number="placarLocal.mandante"
            class="w-14 h-14 sm:w-16 sm:h-16 text-center text-2xl sm:text-3xl font-bold border-2 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all"
            :class="{'bg-gray-100 cursor-not-allowed': bloqueado}"
            :disabled="bloqueado"
            min="0"
          >
          <span class="text-gray-400 font-bold text-xl">X</span>
          <input 
            type="number" 
            v-model.number="placarLocal.visitante"
            class="w-14 h-14 sm:w-16 sm:h-16 text-center text-2xl sm:text-3xl font-bold border-2 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all"
            :class="{'bg-gray-100 cursor-not-allowed': bloqueado}"
            :disabled="bloqueado"
            min="0"
          >
        </div>

        <!-- Visitante -->
        <div class="text-center min-w-0">
          <h3 class="font-bold text-base sm:text-lg text-gray-800 mb-1 truncate" :title="partida.visitante?.nome">
            {{ partida.visitante?.nome || 'A definir' }}
          </h3>
          <span class="text-xs text-gray-500">Visitante</span>
        </div>
      </div>

      <div class="text-center text-xs sm:text-sm text-gray-500 mb-4 sm:mb-6">
        <div v-if="partida.data_jogo">📅 {{ formatDate(partida.data_jogo) }}</div>
        <div v-if="partida.local">📍 {{ partida.local }}</div>
      </div>

      <!-- Ações -->
      <div v-if="!bloqueado" class="flex flex-col gap-2.5">
        <router-link 
          :to="{ name: 'SumulaOperacao', params: { id: partida.id } }"
          class="w-full py-3 px-4 bg-indigo-600 hover:bg-indigo-700 text-white text-sm sm:text-base font-bold rounded-lg transition-colors flex justify-center items-center gap-2 shadow-sm"
        >
          📱 Operar Súmula Eletrônica
        </router-link>

        <button 
          @click="salvar(false)"
          class="w-full py-2.5 px-4 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors flex justify-center items-center gap-2"
          :disabled="loading"
        >
          <span v-if="loading" class="animate-spin">⌛</span>
          Atualizar Placar
        </button>

        <p class="text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-md px-3 py-2">
          A finalização oficial deve ser feita na Súmula Eletrônica.
        </p>
      </div>
      
      <div v-else class="text-center p-3 bg-gray-50 rounded-lg border border-gray-200 text-gray-500 font-medium">
        Partida Finalizada
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed } from 'vue';

export default {
  props: {
    partida: {
      type: Object,
      required: true
    }
  },
  emits: ['update'],
  setup(props, { emit }) {
    const placarLocal = ref({
      mandante: props.partida.placar_mandante ?? 0,
      visitante: props.partida.placar_visitante ?? 0
    });
    const loading = ref(false);

    const bloqueado = computed(() => props.partida.placar_bloqueado || props.partida.status === 'finalizada');

    const formatStatus = (status) => {
      const map = {
        'agendada': 'Agendada',
        'em_andamento': 'Em Andamento',
        'finalizada': 'Finalizada'
      };
      return map[status] || status;
    };

    const formatDate = (dateStr) => {
      if (!dateStr) return '';
      return new Date(dateStr).toLocaleString('pt-BR');
    };

    const salvar = async (finalizar) => {
      loading.value = true;
      try {
        await emit('update', {
          id: props.partida.id,
          placar_mandante: placarLocal.value.mandante,
          placar_visitante: placarLocal.value.visitante,
          finalizar
        });
      } finally {
        loading.value = false;
      }
    };

    return {
      placarLocal,
      loading,
      bloqueado,
      formatStatus,
      formatDate,
      salvar
    };
  }
}
</script>
