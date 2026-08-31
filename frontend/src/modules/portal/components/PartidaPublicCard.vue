<template>
  <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-4 hover:shadow-md transition-all group">
    <div class="flex justify-between items-center mb-2">
      <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">
        {{ formatLabel(partida) }}
      </span>
      <span 
        class="text-xs px-2 py-1 rounded-full font-bold inline-flex items-center gap-1.5"
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

    <div class="flex items-center justify-between gap-4 py-2">
      <!-- Mandante -->
      <div class="flex-1 text-right flex items-center justify-end gap-2">
        <span class="font-bold text-gray-800 truncate">{{ partida.mandante?.nome || 'A definir' }}</span>
        
        <img v-if="partida.mandante?.logo_url" :src="partida.mandante.logo_url" class="w-8 h-8 rounded-full object-contain shrink-0 bg-gray-50 border border-gray-100" />
        <div v-else class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center text-xs font-bold text-gray-500 uppercase shrink-0">
          {{ partida.mandante?.sigla || partida.mandante?.nome?.substring(0,2) || '?' }}
        </div>
      </div>

      <!-- Placar -->
      <div class="bg-gray-50 px-3 py-1 rounded-lg border border-gray-200 font-mono font-bold text-lg min-w-[80px] text-center">
        <span v-if="partida.status !== 'agendada'">
          {{ partida.placar_mandante ?? 0 }} x {{ partida.placar_visitante ?? 0 }}
        </span>
        <span v-else class="text-gray-400 text-sm">vs</span>
      </div>

      <!-- Visitante -->
      <div class="flex-1 text-left flex items-center justify-start gap-2">
        <img v-if="partida.visitante?.logo_url" :src="partida.visitante.logo_url" class="w-8 h-8 rounded-full object-contain shrink-0 bg-gray-50 border border-gray-100" />
        <div v-else class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center text-xs font-bold text-gray-500 uppercase shrink-0">
          {{ partida.visitante?.sigla || partida.visitante?.nome?.substring(0,2) || '?' }}
        </div>
        
        <span class="font-bold text-gray-800 truncate">{{ partida.visitante?.nome || 'A definir' }}</span>
      </div>
    </div>

    <div class="mt-2 text-center text-xs text-gray-500 flex justify-center gap-3">
      <span v-if="partida.data_jogo" class="flex items-center gap-1">
        <Calendar :size="12" /> {{ formatDate(partida.data_jogo) }}
      </span>
      <span v-if="partida.local" class="flex items-center gap-1">
        <MapPin :size="12" /> {{ partida.local }}
      </span>
    </div>
    
    <div class="mt-3 pt-3 border-t border-gray-50 flex justify-center opacity-0 group-hover:opacity-100 transition-opacity">
        <router-link 
            :to="{ name: 'PortalPartida', params: { id: partida.id } }"
            class="text-blue-600 hover:text-blue-800 text-sm font-medium flex items-center gap-1"
        >
            Ver Detalhes e Lances <ArrowRight :size="14" />
        </router-link>
    </div>
  </div>
</template>

<script setup>
import { Calendar, MapPin, ArrowRight } from 'lucide-vue-next';

defineProps({
  partida: {
    type: Object,
    required: true
  }
});

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
