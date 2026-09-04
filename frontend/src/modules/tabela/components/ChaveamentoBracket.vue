<template>
  <div class="overflow-x-auto pb-4">
    <div class="flex items-center gap-8 sm:gap-12 min-w-max p-4 sm:p-8">
      <div 
        v-for="(fase, index) in fasesOrdenadas" 
        :key="fase.id" 
        class="flex flex-col relative"
        :style="{ gap: getGapForRound(index) + 'px' }"
      >
        <!-- Título da Fase -->
        <h3 class="text-center font-bold text-gray-700 mb-4 sticky top-0 bg-gray-50 py-2 border-b z-10">
          {{ fase.nome }}
        </h3>

        <!-- Lista de Jogos da Fase -->
        <div 
          v-for="partida in fase.partidas" 
          :key="partida.id" 
          class="relative flex items-center"
        >
          <!-- Card do Jogo -->
          <div class="w-72 bg-white border border-gray-200 rounded-lg shadow-sm hover:shadow-md transition-shadow relative z-10">
            <div class="flex justify-between items-center px-3 py-2 bg-gray-50 border-b border-gray-100 rounded-t-lg">
              <span class="text-xs font-semibold text-gray-500 uppercase">Jogo {{ partida.id }}</span>
              <span 
                class="w-2 h-2 rounded-full"
                :class="{
                  'bg-green-500': partida.status === 'finalizada',
                  'bg-yellow-400': partida.status === 'em_andamento',
                  'bg-gray-300': partida.status === 'agendada'
                }"
                :title="partida.status"
              ></span>
            </div>
            
            <div class="p-3">
              <!-- Mandante -->
              <div class="flex justify-between items-center mb-2" :class="{'font-bold text-green-700': partida.vencedor_id === partida.mandante_id && partida.status === 'finalizada'}">
                <div class="flex items-center gap-2 truncate flex-1">
                  <span class="text-xs bg-gray-100 px-1 rounded text-gray-500 w-5 text-center">{{ partida.mandante_id || '?' }}</span>
                  <span class="truncate text-sm" :title="partida.mandante?.nome || 'A definir'">{{ partida.mandante?.nome || 'A definir' }}</span>
                </div>
                <span class="font-mono font-bold ml-2">{{ partida.placar_mandante ?? '-' }}</span>
              </div>
              
              <!-- Visitante -->
              <div class="flex justify-between items-center" :class="{'font-bold text-green-700': partida.vencedor_id === partida.visitante_id && partida.status === 'finalizada'}">
                <div class="flex items-center gap-2 truncate flex-1">
                  <span class="text-xs bg-gray-100 px-1 rounded text-gray-500 w-5 text-center">{{ partida.visitante_id || '?' }}</span>
                  <span class="truncate text-sm" :title="partida.visitante?.nome || 'A definir'">{{ partida.visitante?.nome || 'A definir' }}</span>
                </div>
                <span class="font-mono font-bold ml-2">{{ partida.placar_visitante ?? '-' }}</span>
              </div>
            </div>

            <div class="px-3 pb-3">
              <router-link 
                :to="{ name: partidaRoute, params: { id: partida.id } }"
                class="block w-full text-center px-3 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 min-h-10"
              >
                Ver detalhes
              </router-link>
            </div>
          </div>

          <!-- Conector de Saída (Direita) -->
          <div 
            v-if="index < fasesOrdenadas.length - 1" 
            class="absolute -right-6 w-6 h-px bg-gray-300 top-1/2"
          ></div>

          <!-- Conector Vertical e Entrada (Lógica simplificada para árvore binária perfeita) -->
          <!-- Em um cenário real complexo, precisaríamos de coordenadas SVG. Aqui usamos aproximação visual -->
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { computed } from 'vue';

export default {
  props: {
    fases: {
      type: Array,
      required: true
    },
    partidaRoute: {
      type: String,
      default: 'PartidaDetail'
    }
  },
  setup(props) {
    const fasesOrdenadas = computed(() => {
      // Ordena fases e garante partidas ordenadas
      return [...props.fases]
        .sort((a, b) => a.ordem - b.ordem)
        .map(fase => ({
          ...fase,
          partidas: [...(fase.partidas || [])].sort((a, b) => (a.ordem_na_fase || a.id) - (b.ordem_na_fase || b.id))
        }));
    });

    // Função para aumentar o espaçamento vertical a cada round
    // Round 0 (Oitavas): gap normal
    // Round 1 (Quartas): gap maior para alinhar com o meio de dois jogos do round anterior
    const getGapForRound = (roundIndex) => {
      const baseGap = 16; // 1rem
      // Progressão geométrica aproximada para manter alinhamento em árvore binária perfeita
      // Round 0: gap 16
      // Round 1: gap 16 + altura_card? Não é tão simples com CSS Grid/Flex puro sem altura fixa.
      // Vamos usar gaps fixos crescentes visualmente agradáveis
      if (roundIndex === 0) return 16;
      if (roundIndex === 1) return 80; // Aproximação
      if (roundIndex === 2) return 200;
      return 300;
    };

    return { fasesOrdenadas, getGapForRound };
  }
}
</script>

<style scoped>
/* Estilização extra se necessário */
</style>
