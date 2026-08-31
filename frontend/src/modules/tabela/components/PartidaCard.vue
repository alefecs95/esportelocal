<template>
  <div class="border rounded-lg p-4 shadow-sm bg-white hover:shadow-md transition-shadow">
    <div class="flex justify-between items-center mb-2">
      <span class="text-xs font-semibold text-gray-500 uppercase">
        {{ partida.chave || (partida.rodada ? 'Rodada ' + partida.rodada : 'Jogo ' + (partida.ordem_na_fase || '')) }}
      </span>
      <span 
        class="text-xs px-2 py-1 rounded-full"
        :class="{
          'bg-green-100 text-green-800': partida.status === 'finalizada',
          'bg-yellow-100 text-yellow-800': partida.status === 'em_andamento',
          'bg-gray-100 text-gray-800': partida.status === 'agendada'
        }"
      >
        {{ formatStatus(partida.status) }}
      </span>
    </div>

    <div class="flex items-center justify-between gap-4">
      <!-- Mandante -->
      <div class="flex-1 text-center">
        <div class="font-bold text-lg truncate" :title="partida.mandante?.nome || 'A definir'">
          {{ partida.mandante?.nome || 'A definir' }}
        </div>
      </div>

      <!-- Placar -->
      <div class="flex items-center gap-2">
        <input 
          v-if="editando"
          type="number" 
          v-model.number="placarLocal.mandante"
          class="w-12 text-center border rounded p-1 font-mono text-lg"
          min="0"
        >
        <span v-else class="text-2xl font-mono font-bold">{{ partida.placar_mandante ?? '-' }}</span>
        
        <span class="text-gray-400">x</span>
        
        <input 
          v-if="editando"
          type="number" 
          v-model.number="placarLocal.visitante"
          class="w-12 text-center border rounded p-1 font-mono text-lg"
          min="0"
        >
        <span v-else class="text-2xl font-mono font-bold">{{ partida.placar_visitante ?? '-' }}</span>
      </div>

      <!-- Visitante -->
      <div class="flex-1 text-center">
        <div class="font-bold text-lg truncate" :title="partida.visitante?.nome || 'A definir'">
          {{ partida.visitante?.nome || 'A definir' }}
        </div>
      </div>
    </div>

    <div class="mt-3 text-center text-sm text-gray-500" v-if="partida.data_jogo || partida.local">
      <span v-if="partida.data_jogo">{{ formatDate(partida.data_jogo) }}</span>
      <span v-if="partida.data_jogo && partida.local"> - </span>
      <span v-if="partida.local">{{ partida.local }}</span>
    </div>

    <!-- Ações -->
    <div class="mt-4 flex justify-between items-center">
      <router-link 
        :to="{ name: 'PartidaDetail', params: { id: partida.id } }"
        class="text-sm text-blue-600 hover:text-blue-800 hover:underline flex items-center gap-1"
      >
        <Eye :size="16" /> Detalhes
      </router-link>

      <div class="flex gap-2" v-if="podeEditar">
        <button 
          v-if="!editando" 
          @click="iniciarEdicao"
          class="text-sm text-blue-600 hover:text-blue-800 font-medium"
        >
          Atualizar Placar
        </button>
        <template v-else>
          <button 
            @click="cancelarEdicao"
            class="text-sm text-gray-600 hover:text-gray-800 font-medium"
          >
            Cancelar
          </button>
          <button 
            @click="salvarPlacar"
            class="text-sm text-green-600 hover:text-green-800 font-bold"
            :disabled="salvando"
          >
            {{ salvando ? 'Salvando...' : 'Salvar' }}
          </button>
        </template>
      </div>
    </div>
  </div>
</template>

<script>
import { ref } from 'vue';
import { useTabelaStore } from '../store/tabelaStore';
import { Eye } from 'lucide-vue-next';

export default {
  components: { Eye },
  props: {
    partida: {
      type: Object,
      required: true
    },
    podeEditar: {
      type: Boolean,
      default: true
    }
  },
  setup(props) {
    const store = useTabelaStore();
    const editando = ref(false);
    const salvando = ref(false);
    const placarLocal = ref({
      mandante: props.partida.placar_mandante,
      visitante: props.partida.placar_visitante
    });

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

    const iniciarEdicao = () => {
      placarLocal.value = {
        mandante: props.partida.placar_mandante ?? 0,
        visitante: props.partida.placar_visitante ?? 0
      };
      editando.value = true;
    };

    const cancelarEdicao = () => {
      editando.value = false;
    };

    const salvarPlacar = async () => {
      salvando.value = true;
      try {
        await store.atualizarPlacar(
          props.partida.id, 
          placarLocal.value.mandante, 
          placarLocal.value.visitante
        );
        editando.value = false;
      } catch (error) {
        alert('Erro ao atualizar placar: ' + error);
      } finally {
        salvando.value = false;
      }
    };

    return {
      editando,
      salvando,
      placarLocal,
      formatStatus,
      formatDate,
      iniciarEdicao,
      cancelarEdicao,
      salvarPlacar
    };
  }
}
</script>
