<template>
  <div class="bg-white rounded-lg shadow p-6">
    <div class="flex justify-between items-center mb-6">
      <h2 class="text-xl font-bold text-gray-800 flex items-center gap-2">
        <span class="text-2xl">🏃</span>
        Resultados da Corrida
      </h2>
      <div class="flex gap-2">
        <button 
          @click="save" 
          :disabled="saving"
          class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 disabled:opacity-50 flex items-center gap-2"
        >
          <span v-if="saving" class="animate-spin h-4 w-4 border-2 border-white border-t-transparent rounded-full"></span>
          Salvar Tempos
        </button>
      </div>
    </div>

    <!-- Filtros / Tabs -->
    <div class="mb-4 border-b border-gray-200">
      <nav class="-mb-px flex space-x-8" aria-label="Tabs">
        <button
          @click="activeTab = 'geral'"
          :class="[
            activeTab === 'geral'
              ? 'border-blue-500 text-blue-600'
              : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
            'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm'
          ]"
        >
          Classificação Geral
        </button>
        <button
          @click="activeTab = 'categorias'"
          :class="[
            activeTab === 'categorias'
              ? 'border-blue-500 text-blue-600'
              : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
            'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm'
          ]"
        >
          Por Categoria
        </button>
      </nav>
    </div>

    <!-- Tabela Geral -->
    <div v-if="activeTab === 'geral'" class="overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Pos.
            </th>
            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Atleta
            </th>
            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Categoria
            </th>
            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Tempo (HH:MM:SS)
            </th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="(inscricao, index) in inscricoesOrdenadas" :key="inscricao.id">
            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900">
              {{ inscricao.colocacao || '-' }}º
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="text-sm font-medium text-gray-900">{{ inscricao.atleta?.nome }}</div>
              <div class="text-sm text-gray-500">{{ inscricao.atleta?.equipe_nome || 'Individual' }}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              {{ inscricao.categoria?.nome || inscricao.campeonato?.categoria || 'Geral' }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              <input 
                type="text" 
                v-model="temposLocais[inscricao.id]" 
                placeholder="00:00:00"
                class="border border-gray-300 rounded px-2 py-1 w-32 focus:ring-blue-500 focus:border-blue-500"
                @blur="validateTime(inscricao.id)"
              />
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Tabela Por Categoria -->
    <div v-else class="space-y-8">
      <div v-for="(grupo, categoriaNome) in inscricoesPorCategoria" :key="categoriaNome" class="border rounded-lg p-4">
        <h3 class="text-lg font-semibold text-gray-800 mb-3 bg-gray-50 p-2 rounded">
          {{ categoriaNome }}
        </h3>
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Pos. Cat.
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Atleta
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Tempo
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="(inscricao, index) in grupo" :key="inscricao.id">
              <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900">
                {{ index + 1 }}º
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ inscricao.atleta?.nome }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ temposLocais[inscricao.id] || '-' }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</template>

<script>
import { ref, computed, onMounted, watch } from 'vue';
import { useTabelaStore } from '../store/tabelaStore';
import tabelaService from '../services/tabelaService';

export default {
  props: {
    inscricoes: {
      type: Array,
      required: true
    },
    campeonatoId: {
      type: [String, Number],
      required: true
    }
  },
  setup(props) {
    const store = useTabelaStore();
    const activeTab = ref('geral');
    const saving = ref(false);
    const temposLocais = ref({});

    // Inicializar tempos locais com o que veio do banco
    watch(() => props.inscricoes, (newVal) => {
      newVal.forEach(insc => {
        if (insc.tempo_visual) {
            temposLocais.value[insc.id] = insc.tempo_visual;
        }
      });
    }, { immediate: true });

    const inscricoesOrdenadas = computed(() => {
        // Ordena visualmente por tempo (se tiver) ou colocação
        // Mas a prop já vem ordenada do backend pelo controller
        return props.inscricoes;
    });

    const inscricoesPorCategoria = computed(() => {
        const grupos = {};
        // Primeiro ordenar por tempo ms para calcular posição correta dentro da categoria
        const sorted = [...props.inscricoes].sort((a, b) => {
            const tA = a.tempo_ms || Infinity;
            const tB = b.tempo_ms || Infinity;
            return tA - tB;
        });

        sorted.forEach(insc => {
            const cat = insc.categoria?.nome || insc.campeonato?.categoria || 'Geral';
            if (!grupos[cat]) grupos[cat] = [];
            grupos[cat].push(insc);
        });
        return grupos;
    });

    const validateTime = (id) => {
        let val = temposLocais.value[id];
        if (!val) return;
        
        // Remove caracteres não numéricos exceto :
        val = val.replace(/[^0-9:]/g, '');
        
        // Lógica de "Smart Format"
        // Se usuário digitou apenas números (ex: 65 -> 1h05m ou 65min?)
        // Backend assume minutos. Aqui podemos formatar para visualização.
        if (!val.includes(':')) {
             const num = parseInt(val);
             if (!isNaN(num)) {
                 if (num >= 60) {
                     const h = Math.floor(num / 60);
                     const m = num % 60;
                     val = `${h}:${m.toString().padStart(2, '0')}:00`;
                 } else {
                     val = `00:${num.toString().padStart(2, '0')}:00`;
                 }
             }
        } else {
            // Se tem :, verificar formato
            const parts = val.split(':');
            if (parts.length === 2) {
                // MM:SS -> adicionar 00:
                val = `00:${parts[0].padStart(2, '0')}:${parts[1].padStart(2, '0')}`;
            } else if (parts.length === 3) {
                val = `${parts[0].padStart(2, '0')}:${parts[1].padStart(2, '0')}:${parts[2].padStart(2, '0')}`;
            }
        }
        
        temposLocais.value[id] = val;
    };

    const save = async () => {
        saving.value = true;
        try {
            const payload = Object.entries(temposLocais.value).map(([id, tempo]) => ({
                inscricao_id: id,
                tempo: tempo
            }));
            
            await tabelaService.salvarResultadosCorrida(props.campeonatoId, payload);
            await store.fetchTabela(props.campeonatoId); // Recarrega para atualizar ordenação e colocações
            alert('Tempos salvos com sucesso!');
        } catch (error) {
            console.error(error);
            alert('Erro ao salvar tempos.');
        } finally {
            saving.value = false;
        }
    };

    return {
        activeTab,
        saving,
        temposLocais,
        inscricoesOrdenadas,
        inscricoesPorCategoria,
        validateTime,
        save
    };
  }
}
</script>
