<template>
  <router-link
    :to="{ name: 'PortalPartida', params: { id: partida.id } }"
    class="block bg-white rounded-xl shadow-sm border border-gray-100 p-4 hover:shadow-md transition-all min-w-0"
  >
    <div class="flex justify-between items-start gap-2 mb-3">
      <span class="text-[11px] sm:text-xs font-medium text-gray-500 uppercase tracking-wide min-w-0 line-clamp-2">
        {{ formatLabel(partida) }}
      </span>
      <span
        class="text-[10px] sm:text-xs px-2 py-1 rounded-full font-bold inline-flex items-center gap-1.5 shrink-0"
        :class="{
          'bg-green-100 text-green-800': partida.status === 'finalizada',
          'bg-yellow-100 text-yellow-800': partida.status === 'em_andamento',
          'bg-gray-100 text-gray-600': partida.status === 'agendada'
        }"
      >
        <span v-if="partida.status === 'em_andamento'" class="relative flex h-2.5 w-2.5">
          <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
          <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500"></span>
        </span>
        {{ formatStatus(partida.status) }}
      </span>
    </div>

    <!-- Mobile: placar à direita -->
    <div class="sm:hidden space-y-2 py-1">
      <div class="flex items-center gap-2 min-w-0">
        <img v-if="partida.mandante?.logo_url" :src="partida.mandante.logo_url" class="w-8 h-8 rounded-full object-contain shrink-0 bg-gray-50 border border-gray-100" alt="" />
        <div v-else class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center text-[10px] font-bold text-gray-500 uppercase shrink-0">
          {{ iniciais(partida.mandante) }}
        </div>
        <span class="font-bold text-gray-800 text-sm leading-tight line-clamp-2 flex-1 min-w-0">{{ partida.mandante?.nome || 'A definir' }}</span>
        <span class="font-mono font-black text-lg tabular-nums w-7 text-right shrink-0">{{ placarMandante }}</span>
      </div>
      <div class="flex items-center gap-2 min-w-0">
        <img v-if="partida.visitante?.logo_url" :src="partida.visitante.logo_url" class="w-8 h-8 rounded-full object-contain shrink-0 bg-gray-50 border border-gray-100" alt="" />
        <div v-else class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center text-[10px] font-bold text-gray-500 uppercase shrink-0">
          {{ iniciais(partida.visitante) }}
        </div>
        <span class="font-bold text-gray-800 text-sm leading-tight line-clamp-2 flex-1 min-w-0">{{ partida.visitante?.nome || 'A definir' }}</span>
        <span class="font-mono font-black text-lg tabular-nums w-7 text-right shrink-0">{{ placarVisitante }}</span>
      </div>
    </div>

    <!-- Desktop -->
    <div class="hidden sm:flex items-center justify-between gap-3 py-2">
      <div class="flex-1 text-right flex items-center justify-end gap-2 min-w-0">
        <span class="font-bold text-gray-800 leading-tight line-clamp-2">{{ partida.mandante?.nome || 'A definir' }}</span>
        <img v-if="partida.mandante?.logo_url" :src="partida.mandante.logo_url" class="w-8 h-8 rounded-full object-contain shrink-0 bg-gray-50 border border-gray-100" alt="" />
        <div v-else class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center text-xs font-bold text-gray-500 uppercase shrink-0">
          {{ iniciais(partida.mandante) }}
        </div>
      </div>

      <div class="bg-gray-50 px-3 py-1 rounded-lg border border-gray-200 font-mono font-bold text-lg min-w-[4.5rem] text-center shrink-0">
        <span v-if="partida.status !== 'agendada'">
          {{ partida.placar_mandante ?? 0 }} x {{ partida.placar_visitante ?? 0 }}
        </span>
        <span v-else class="text-gray-400 text-sm">vs</span>
      </div>

      <div class="flex-1 text-left flex items-center justify-start gap-2 min-w-0">
        <img v-if="partida.visitante?.logo_url" :src="partida.visitante.logo_url" class="w-8 h-8 rounded-full object-contain shrink-0 bg-gray-50 border border-gray-100" alt="" />
        <div v-else class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center text-xs font-bold text-gray-500 uppercase shrink-0">
          {{ iniciais(partida.visitante) }}
        </div>
        <span class="font-bold text-gray-800 leading-tight line-clamp-2">{{ partida.visitante?.nome || 'A definir' }}</span>
      </div>
    </div>

    <div class="mt-3 text-center text-xs text-gray-500 flex flex-wrap justify-center gap-x-3 gap-y-1 min-w-0">
      <span v-if="partida.data_jogo" class="inline-flex items-center gap-1">
        <Calendar :size="12" class="shrink-0" /> {{ formatDate(partida.data_jogo) }}
      </span>
      <span v-if="partida.local" class="inline-flex items-center gap-1 min-w-0 max-w-full">
        <MapPin :size="12" class="shrink-0" /> <span class="truncate">{{ partida.local }}</span>
      </span>
    </div>

    <div class="mt-3 pt-3 border-t border-gray-100 flex justify-center">
      <span class="text-blue-600 text-sm font-medium inline-flex items-center gap-1 min-h-8">
        Ver detalhes <ArrowRight :size="14" />
      </span>
    </div>
  </router-link>
</template>

<script setup>
import { computed } from 'vue';
import { Calendar, MapPin, ArrowRight } from 'lucide-vue-next';

const props = defineProps({
  partida: {
    type: Object,
    required: true
  }
});

const placarMandante = computed(() => {
  if (props.partida.status === 'agendada') return '-';
  return props.partida.placar_mandante ?? 0;
});

const placarVisitante = computed(() => {
  if (props.partida.status === 'agendada') return '-';
  return props.partida.placar_visitante ?? 0;
});

const iniciais = (equipe) => {
  return equipe?.sigla || equipe?.nome?.substring(0, 2) || '?';
};

const formatStatus = (status) => {
  const map = { agendada: 'Agendada', em_andamento: 'Ao Vivo', finalizada: 'Encerrado' };
  return map[status] || status;
};

const formatDate = (dateString) => {
  if (!dateString) return '';
  const date = new Date(dateString);
  return new Intl.DateTimeFormat('pt-BR', {
    day: '2-digit',
    month: '2-digit',
    hour: '2-digit',
    minute: '2-digit'
  }).format(date);
};

const getNomeGrupo = (grupo) => {
  if (!grupo) return '';
  if (typeof grupo === 'string') return grupo.trim();
  if (typeof grupo === 'object') {
    return String(grupo.nome || grupo.name || grupo.grupo || '').trim();
  }
  return String(grupo).trim();
};

const formatLabel = (partida) => {
  if (partida.chave) return partida.chave;

  const partes = [];
  const grupoNome = getNomeGrupo(partida.grupo);
  if (grupoNome && grupoNome !== 'Geral') {
    partes.push(grupoNome.toLowerCase().startsWith('grupo') ? grupoNome : `Grupo ${grupoNome}`);
  }

  if (partida.rodada) {
    partes.push(`Rodada ${partida.rodada}`);
  } else if (partida.ordem_na_fase) {
    partes.push(`Jogo ${partida.ordem_na_fase}`);
  }

  return partes.length > 0 ? partes.join(' • ') : 'Partida';
};
</script>
