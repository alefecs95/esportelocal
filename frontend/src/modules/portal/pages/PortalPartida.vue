<template>
  <div class="p-3 sm:p-4 max-w-6xl mx-auto space-y-4">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
      <div>
        <h1 class="text-xl sm:text-2xl font-black text-slate-800 flex items-center gap-2">
          <Shield :size="20" class="text-blue-600" />
          Detalhes da Partida
        </h1>
      </div>
      <div class="flex items-center gap-2">
        <span class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-[11px] font-bold" :class="statusTempoRealAtivo ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600'">
          <span class="h-2.5 w-2.5 rounded-full" :class="statusTempoRealAtivo ? 'bg-emerald-500 animate-pulse' : 'bg-slate-400'"></span>
          {{ statusTempoRealAtivo ? 'ONLINE' : 'OFFLINE' }}
        </span>
        <button @click="$router.go(-1)" class="inline-flex items-center gap-1.5 rounded-lg border border-blue-200 bg-blue-50 px-3 py-1.5 text-sm font-bold text-blue-700 hover:bg-blue-100">
          <ArrowLeft :size="16" /> Voltar
        </button>
      </div>
    </div>

    <div v-if="loading" class="text-center py-12">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
    </div>

    <div v-else-if="error" class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded">
      <p class="font-bold">Erro</p>
      <p>{{ error }}</p>
    </div>

    <div v-else class="space-y-4">
      <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="px-4 sm:px-6 py-3 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
          <div class="text-sm font-medium text-slate-500 flex flex-wrap items-center gap-3">
            <span class="inline-flex items-center gap-1.5">
              <Calendar :size="14" /> {{ partida.data_jogo ? formatDate(partida.data_jogo) : 'Data a definir' }}
            </span>
            <span v-if="partida.local" class="inline-flex items-center gap-1.5">
              <MapPin :size="14" /> {{ partida.local }}
            </span>
          </div>
          <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-bold uppercase tracking-wide" :class="badgeStatusPartida">
            <Circle :size="10" class="fill-current" />
            {{ formatStatus(partida.status) }}
          </span>
        </div>

        <div class="p-4 sm:p-8">
          <div class="grid grid-cols-1 md:grid-cols-[1fr_auto_1fr] items-center gap-4 sm:gap-6">
            <div class="text-center flex flex-col items-center">
              <img v-if="partida.mandante?.logo_url" :src="partida.mandante.logo_url" class="w-20 h-20 sm:w-24 sm:h-24 rounded-full object-contain bg-white shadow-sm border border-slate-100 mb-3" />
              <div v-else class="w-20 h-20 sm:w-24 sm:h-24 rounded-full bg-slate-100 flex items-center justify-center text-xl sm:text-2xl font-black text-slate-400 uppercase shadow-sm border border-slate-200 mb-3">
                {{ partida.mandante?.sigla || partida.mandante?.nome?.substring(0,2) || '?' }}
              </div>
              <h2 class="text-xl sm:text-3xl font-black text-slate-800 truncate w-full">{{ partida.mandante?.nome || 'A definir' }}</h2>
              <p class="text-[11px] text-slate-500 uppercase font-bold tracking-widest mt-1">Mandante</p>
            </div>
            <div class="rounded-xl bg-gradient-to-br from-slate-900 to-slate-800 text-white px-5 sm:px-8 py-3 shadow-lg flex items-center justify-center">
              <div class="text-4xl sm:text-5xl font-mono font-black tracking-widest">
                {{ partida.placar_mandante ?? 0 }} <span class="text-slate-500 mx-2">:</span> {{ partida.placar_visitante ?? 0 }}
              </div>
            </div>
            <div class="text-center flex flex-col items-center">
              <img v-if="partida.visitante?.logo_url" :src="partida.visitante.logo_url" class="w-20 h-20 sm:w-24 sm:h-24 rounded-full object-contain bg-white shadow-sm border border-slate-100 mb-3" />
              <div v-else class="w-20 h-20 sm:w-24 sm:h-24 rounded-full bg-slate-100 flex items-center justify-center text-xl sm:text-2xl font-black text-slate-400 uppercase shadow-sm border border-slate-200 mb-3">
                {{ partida.visitante?.sigla || partida.visitante?.nome?.substring(0,2) || '?' }}
              </div>
              <h2 class="text-xl sm:text-3xl font-black text-slate-800 truncate w-full">{{ partida.visitante?.nome || 'A definir' }}</h2>
              <p class="text-[11px] text-slate-500 uppercase font-bold tracking-widest mt-1">Visitante</p>
            </div>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-4 sm:p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-black text-slate-800 flex items-center gap-2">
            <Timer :size="18" class="text-blue-600" />
            Cronologia do Jogo
            </h3>
            <span v-if="partida.status === 'em_andamento'" class="bg-slate-100 text-slate-700 px-3 py-1 rounded-lg font-mono font-bold text-sm">
                {{ partida.tempo_atual || '00:00' }}
            </span>
        </div>

        <div v-if="eventos.length === 0" class="text-slate-500 text-center py-12 bg-slate-50 rounded-xl border border-dashed border-slate-200">
          Nenhum evento registrado nesta partida.
        </div>

        <div v-else class="space-y-2 relative">
          <div class="absolute left-[30px] top-0 bottom-0 w-0.5 bg-slate-200"></div>
          <div v-for="evento in eventosOrdenados" :key="evento.id" class="relative pl-16 sm:pl-20 py-2">
            <div class="absolute left-0 top-1/2 -translate-y-1/2 w-14 sm:w-16 text-right pr-3">
              <span class="inline-flex items-center gap-1 text-[11px] sm:text-xs font-black text-slate-600">
                <Clock3 :size="12" />
                {{ evento.minuto }}'
              </span>
            </div>
            <div class="absolute left-[23px] sm:left-[26px] top-1/2 -translate-y-1/2 w-4 h-4 rounded-full border-2 border-white shadow-sm z-10" :class="getEventoColorClass(evento.tipo_evento)"></div>
            <div class="rounded-xl p-3 border" :class="getEventoContainerClass(evento.tipo_evento)">
              <div class="flex items-center gap-2 mb-1.5">
                <component :is="getEventoIcon(evento.tipo_evento)" :size="14" />
                <span class="text-sm font-black">{{ formatTipoEvento(evento.tipo_evento) }}</span>
              </div>
              <div class="text-sm text-slate-700">
                <span class="font-semibold text-slate-900">{{ evento.jogador?.nome || 'Atleta não identificado' }}</span>
                <span class="text-slate-500"> • {{ evento.time?.nome || 'Equipe não identificada' }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { useRoute } from 'vue-router';
import portalService from '../services/portalService';
import { Calendar, MapPin, ArrowLeft, Timer, Circle, Shield, Clock3, Target, AlertTriangle, Repeat2 } from 'lucide-vue-next';

const route = useRoute();
const partida = ref({});
const eventos = ref([]);
const loading = ref(true);
const error = ref(null);
const isConexaoOnline = ref(typeof navigator !== 'undefined' ? navigator.onLine : true);
let pollInterval = null;

const eventosOrdenados = computed(() => [...eventos.value].sort((a, b) => a.minuto - b.minuto));
const statusTempoRealAtivo = computed(() => partida.value.status === 'em_andamento' && isConexaoOnline.value);
const badgeStatusPartida = computed(() => {
  if (partida.value.status === 'finalizada') return 'bg-emerald-100 text-emerald-700';
  if (partida.value.status === 'em_andamento') return 'bg-yellow-100 text-yellow-800';
  return 'bg-slate-100 text-slate-700';
});

const loadData = async (isPolling = false) => {
  if (!isPolling) loading.value = true;
  try {
    const { data } = await portalService.getPartida(route.params.id);
    partida.value = data;
    eventos.value = data.eventos || [];
    if (partida.value.status === 'em_andamento' && !pollInterval) {
      startPolling();
    } else if (partida.value.status !== 'em_andamento' && pollInterval) {
      stopPolling();
    }
  } catch (e) {
    if (!isPolling) error.value = 'Erro ao carregar detalhes da partida.';
    console.error(e);
  } finally {
    if (!isPolling) loading.value = false;
  }
};

const startPolling = () => {
  pollInterval = setInterval(() => {
    loadData(true);
  }, 10000);
};

const stopPolling = () => {
  if (pollInterval) {
    clearInterval(pollInterval);
    pollInterval = null;
  }
};

const formatStatus = (status) => {
  const map = { agendada: 'Agendada', em_andamento: 'Ao Vivo', finalizada: 'Finalizada' };
  return map[status] || status;
};

const formatDate = (dateString) => {
  if (!dateString) return '';
  const date = new Date(dateString);
  return new Intl.DateTimeFormat('pt-BR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  }).format(date);
};

const formatTipoEvento = (tipo) => {
  const map = { GOL: 'Gol', CARTAO_AMARELO: 'Cartão Amarelo', CARTAO_VERMELHO: 'Cartão Vermelho', SUBSTITUICAO: 'Substituição' };
  return map[tipo] || tipo;
};

const getEventoIcon = (tipo) => {
  if (tipo === 'GOL') return Target;
  if (tipo === 'SUBSTITUICAO') return Repeat2;
  return AlertTriangle;
};

const getEventoColorClass = (tipo) => {
  if (tipo === 'GOL') return 'bg-green-500';
  if (tipo === 'CARTAO_AMARELO') return 'bg-yellow-500';
  if (tipo === 'CARTAO_VERMELHO') return 'bg-red-500';
  if (tipo === 'SUBSTITUICAO') return 'bg-blue-500';
  return 'bg-slate-500';
};

const getEventoContainerClass = (tipo) => {
  if (tipo === 'GOL') return 'bg-green-50 border-green-200 text-green-700';
  if (tipo === 'CARTAO_AMARELO') return 'bg-yellow-50 border-yellow-200 text-yellow-700';
  if (tipo === 'CARTAO_VERMELHO') return 'bg-red-50 border-red-200 text-red-700';
  if (tipo === 'SUBSTITUICAO') return 'bg-blue-50 border-blue-200 text-blue-700';
  return 'bg-slate-50 border-slate-200 text-slate-700';
};

const marcarOnline = () => {
  isConexaoOnline.value = true;
};

const marcarOffline = () => {
  isConexaoOnline.value = false;
};

onMounted(() => {
  loadData();
  window.addEventListener('online', marcarOnline);
  window.addEventListener('offline', marcarOffline);
});

onUnmounted(() => {
  stopPolling();
  window.removeEventListener('online', marcarOnline);
  window.removeEventListener('offline', marcarOffline);
});
</script>
