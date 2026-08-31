<template>
  <div class="min-h-screen bg-gray-50 flex flex-col">
    <!-- Header Fixo -->
    <header class="bg-white border-b border-gray-200 sticky top-0 z-20 shadow-sm">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
          <div class="flex items-center gap-4">
            <button @click="$router.go(-1)" class="text-gray-500 hover:text-gray-700 p-2 rounded-full hover:bg-gray-100">
              <span class="text-xl">←</span>
            </button>
            <div>
              <h1 class="text-xl font-bold text-gray-900 leading-tight">Súmula Eletrônica</h1>
              <p class="text-xs text-gray-500 flex items-center gap-2">
                {{ partida.local || 'Local a definir' }} • {{ partida.data_jogo ? formatDate(partida.data_jogo) : 'Data a definir' }}
              </p>
            </div>
          </div>

          <div class="flex items-center gap-4">
            <!-- Timer (Simulado por enquanto, poderia ser real) -->
            <div class="bg-gray-100 px-3 py-1 rounded-lg flex items-center gap-2">
              <span class="text-xs font-bold text-gray-500 uppercase">Tempo</span>
              <input type="number" v-model="tempoJogo" class="w-12 bg-transparent border-none p-0 text-center font-mono font-bold text-gray-800 focus:ring-0" placeholder="00" min="0">
              <span class="text-xs text-gray-400">'</span>
            </div>

            <!-- Placar -->
            <div class="flex items-center gap-3 bg-gray-900 text-white px-4 py-2 rounded-lg shadow-md">
              <span class="font-bold text-lg">{{ partida.placar_mandante ?? 0 }}</span>
              <span class="text-gray-400 text-xs mx-1">vs</span>
              <span class="font-bold text-lg">{{ partida.placar_visitante ?? 0 }}</span>
            </div>
            
            <button @click="printSumula" class="text-gray-500 hover:text-blue-600 p-2" title="Imprimir PDF">
              <FileText :size="20" />
            </button>
          </div>
        </div>
      </div>
    </header>

    <div v-if="loading" class="flex-1 flex justify-center items-center">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
    </div>

    <div v-else class="flex-1 max-w-7xl mx-auto w-full p-4">
      <div
        v-if="!escalacaoCompletaPartida"
        class="mb-4 rounded-xl border border-amber-200 bg-amber-50 p-4 text-amber-800"
      >
        <p class="text-sm font-semibold">Escalação incompleta para registrar eventos.</p>
        <p class="text-xs mt-1">
          Mandante: {{ titularesMandante }}/11 titulares • Visitante: {{ titularesVisitante }}/11 titulares.
          Defina os titulares dos dois times antes de lançar gols, cartões e substituições.
        </p>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
      
      <!-- Coluna Mandante -->
      <div class="lg:col-span-4 space-y-4">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <div class="bg-blue-50 px-4 py-3 border-b border-blue-100 flex justify-between items-center gap-3">
            <div class="flex items-center gap-2 min-w-0">
              <img
                v-if="getEquipeLogoUrl(partida.mandante)"
                :src="getEquipeLogoUrl(partida.mandante)"
                alt="Logo mandante"
                class="w-8 h-8 rounded-full object-contain bg-white border border-blue-100"
              >
              <div
                v-else
                class="w-8 h-8 rounded-full bg-blue-100 border border-blue-200 flex items-center justify-center text-[10px] font-bold text-blue-700 uppercase"
              >
                {{ getIniciais(partida.mandante?.nome || 'MD') }}
              </div>
              <h2 class="font-bold text-blue-900 truncate" :title="partida.mandante?.nome">{{ partida.mandante?.nome || 'Mandante' }}</h2>
            </div>
            <span class="text-xs font-bold text-blue-600 bg-blue-100 px-2 py-0.5 rounded">Mandante</span>
          </div>
          
          <div class="divide-y divide-gray-100 max-h-[600px] overflow-y-auto">
            <div v-for="atleta in atletasMandante" :key="atleta.id" class="p-3 hover:bg-gray-50 group relative transition-colors">
              <div class="flex justify-between items-center">
                <div class="flex items-center gap-3">
                  <div class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center text-xs font-bold text-gray-500">
                    {{ getIniciais(atleta.nome) }}
                  </div>
                  <div>
                    <p class="text-sm font-medium text-gray-900 line-clamp-1">{{ atleta.nome }}</p>
                    <div class="mt-0.5 flex items-center gap-1">
                      <span
                        v-if="isTitular(partida.mandante_id, atleta.id)"
                        class="inline-flex items-center rounded px-1.5 py-0.5 text-[10px] font-bold bg-blue-100 text-blue-700"
                      >
                        TIT
                      </span>
                      <span
                        v-else-if="isEscalado(partida.mandante_id, atleta.id)"
                        class="inline-flex items-center rounded px-1.5 py-0.5 text-[10px] font-bold bg-slate-100 text-slate-600"
                      >
                        RES
                      </span>
                    </div>
                    <div class="flex gap-1 mt-0.5">
                      <!-- Badges de eventos já ocorridos -->
                      <span v-for="evt in getEventosAtleta(atleta.id)" :key="evt.id" 
                            class="inline-block w-2 h-3 rounded-sm"
                            :class="getEventoBadgeClass(evt.tipo_evento)"
                            :title="formatTipoEvento(evt.tipo_evento) + ' (' + evt.minuto + '\')'"
                      ></span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Ações Rápidas (Hover/Click) -->
              <div class="absolute inset-y-0 right-0 bg-gradient-to-l from-white via-white to-transparent pl-8 flex items-center gap-1 pr-3 opacity-0 group-hover:opacity-100 transition-opacity">
                <button :disabled="!escalacaoCompletaPartida" @click="quickEvento('GOL', atleta.id, partida.mandante_id)" class="w-7 h-7 rounded-full bg-green-100 text-green-700 hover:bg-green-600 hover:text-white flex items-center justify-center shadow-sm disabled:opacity-40 disabled:cursor-not-allowed" title="Gol">⚽</button>
                <button :disabled="!escalacaoCompletaPartida" @click="quickEvento('CARTAO_AMARELO', atleta.id, partida.mandante_id)" class="w-7 h-7 rounded-full bg-yellow-100 text-yellow-700 hover:bg-yellow-500 hover:text-white flex items-center justify-center shadow-sm disabled:opacity-40 disabled:cursor-not-allowed" title="Cartão Amarelo">🟨</button>
                <button :disabled="!escalacaoCompletaPartida" @click="quickEvento('CARTAO_VERMELHO', atleta.id, partida.mandante_id)" class="w-7 h-7 rounded-full bg-red-100 text-red-700 hover:bg-red-600 hover:text-white flex items-center justify-center shadow-sm disabled:opacity-40 disabled:cursor-not-allowed" title="Cartão Vermelho">🟥</button>
                <button :disabled="!escalacaoCompletaPartida" @click="abrirSubstituicao(atleta, partida.mandante_id)" class="w-7 h-7 rounded-full bg-blue-100 text-blue-700 hover:bg-blue-600 hover:text-white flex items-center justify-center shadow-sm disabled:opacity-40 disabled:cursor-not-allowed" title="Substituição">🔄</button>
              </div>
            </div>
            <div v-if="atletasMandante.length === 0" class="p-4 text-center text-gray-400 text-sm">
              Nenhum atleta listado.
            </div>
          </div>
        </div>
      </div>

      <!-- Coluna Central (Timeline) -->
      <div class="lg:col-span-4 space-y-4">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 min-h-[400px]">
          <h3 class="text-sm font-bold text-gray-500 uppercase tracking-wider mb-4 text-center">Timeline do Jogo</h3>
          
          <div class="space-y-4 relative px-4">
            <div class="absolute left-1/2 top-0 bottom-0 w-px bg-gray-200 -ml-px"></div>

            <div v-for="evento in eventosOrdenados" :key="evento.id" class="relative flex items-center justify-between group">
              <!-- Lado Esquerdo (Mandante) -->
              <div class="w-1/2 pr-4 text-right" v-if="evento.time_id === partida.mandante_id">
                <div class="bg-blue-50 rounded-lg p-2 border border-blue-100 inline-block text-left w-full relative">
                  <div class="font-bold text-xs text-blue-900">{{ formatTipoEvento(evento.tipo_evento) }}</div>
                  <div class="text-xs text-blue-700 truncate">{{ evento.jogador?.nome }}</div>
                  <div v-if="evento.tipo_evento === 'SUBSTITUICAO'" class="text-[10px] text-gray-500 mt-1">
                    Entra: {{ evento.observacao }} <!-- TODO: Melhorar com nome do substituto -->
                  </div>
                  <button v-if="isAdmin" @click="deletarEvento(evento.id)" class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-4 h-4 flex items-center justify-center text-xs opacity-0 group-hover:opacity-100 shadow-sm hover:scale-110 transition-all">×</button>
                </div>
              </div>
              <div class="w-1/2 pr-4" v-else></div>

              <!-- Centro (Minuto) -->
              <div class="absolute left-1/2 -ml-3 w-6 h-6 rounded-full bg-white border-2 border-gray-300 flex items-center justify-center text-[10px] font-bold text-gray-600 z-10 shadow-sm">
                {{ evento.minuto }}'
              </div>

              <!-- Lado Direito (Visitante) -->
              <div class="w-1/2 pl-4" v-if="evento.time_id === partida.visitante_id">
                <div class="bg-red-50 rounded-lg p-2 border border-red-100 inline-block text-left w-full relative">
                  <div class="font-bold text-xs text-red-900">{{ formatTipoEvento(evento.tipo_evento) }}</div>
                  <div class="text-xs text-red-700 truncate">{{ evento.jogador?.nome }}</div>
                  <button v-if="isAdmin" @click="deletarEvento(evento.id)" class="absolute -top-2 -left-2 bg-red-500 text-white rounded-full w-4 h-4 flex items-center justify-center text-xs opacity-0 group-hover:opacity-100 shadow-sm hover:scale-110 transition-all">×</button>
                </div>
              </div>
              <div class="w-1/2 pl-4" v-else></div>
            </div>
            
            <div v-if="eventos.length === 0" class="text-center text-gray-400 text-xs py-8 italic">
              O jogo ainda não começou ou nenhum evento foi registrado.
            </div>
          </div>
        </div>
      </div>

      <!-- Coluna Visitante -->
      <div class="lg:col-span-4 space-y-4">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <div class="bg-red-50 px-4 py-3 border-b border-red-100 flex justify-between items-center gap-3">
            <div class="flex items-center gap-2 min-w-0">
              <img
                v-if="getEquipeLogoUrl(partida.visitante)"
                :src="getEquipeLogoUrl(partida.visitante)"
                alt="Logo visitante"
                class="w-8 h-8 rounded-full object-contain bg-white border border-red-100"
              >
              <div
                v-else
                class="w-8 h-8 rounded-full bg-red-100 border border-red-200 flex items-center justify-center text-[10px] font-bold text-red-700 uppercase"
              >
                {{ getIniciais(partida.visitante?.nome || 'VT') }}
              </div>
              <h2 class="font-bold text-red-900 truncate" :title="partida.visitante?.nome">{{ partida.visitante?.nome || 'Visitante' }}</h2>
            </div>
            <span class="text-xs font-bold text-red-600 bg-red-100 px-2 py-0.5 rounded">Visitante</span>
          </div>
          
          <div class="divide-y divide-gray-100 max-h-[600px] overflow-y-auto">
            <div v-for="atleta in atletasVisitante" :key="atleta.id" class="p-3 hover:bg-gray-50 group relative transition-colors">
              <div class="flex justify-between items-center">
                <div class="flex items-center gap-3">
                  <div class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center text-xs font-bold text-gray-500">
                    {{ getIniciais(atleta.nome) }}
                  </div>
                  <div>
                    <p class="text-sm font-medium text-gray-900 line-clamp-1">{{ atleta.nome }}</p>
                    <div class="mt-0.5 flex items-center gap-1">
                      <span
                        v-if="isTitular(partida.visitante_id, atleta.id)"
                        class="inline-flex items-center rounded px-1.5 py-0.5 text-[10px] font-bold bg-blue-100 text-blue-700"
                      >
                        TIT
                      </span>
                      <span
                        v-else-if="isEscalado(partida.visitante_id, atleta.id)"
                        class="inline-flex items-center rounded px-1.5 py-0.5 text-[10px] font-bold bg-slate-100 text-slate-600"
                      >
                        RES
                      </span>
                    </div>
                    <div class="flex gap-1 mt-0.5">
                      <span v-for="evt in getEventosAtleta(atleta.id)" :key="evt.id" 
                            class="inline-block w-2 h-3 rounded-sm"
                            :class="getEventoBadgeClass(evt.tipo_evento)"
                            :title="formatTipoEvento(evt.tipo_evento) + ' (' + evt.minuto + '\')'"
                      ></span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Ações Rápidas (Hover/Click) -->
              <div class="absolute inset-y-0 right-0 bg-gradient-to-l from-white via-white to-transparent pl-8 flex items-center gap-1 pr-3 opacity-0 group-hover:opacity-100 transition-opacity">
                <button :disabled="!escalacaoCompletaPartida" @click="quickEvento('GOL', atleta.id, partida.visitante_id)" class="w-7 h-7 rounded-full bg-green-100 text-green-700 hover:bg-green-600 hover:text-white flex items-center justify-center shadow-sm disabled:opacity-40 disabled:cursor-not-allowed" title="Gol">⚽</button>
                <button :disabled="!escalacaoCompletaPartida" @click="quickEvento('CARTAO_AMARELO', atleta.id, partida.visitante_id)" class="w-7 h-7 rounded-full bg-yellow-100 text-yellow-700 hover:bg-yellow-500 hover:text-white flex items-center justify-center shadow-sm disabled:opacity-40 disabled:cursor-not-allowed" title="Cartão Amarelo">🟨</button>
                <button :disabled="!escalacaoCompletaPartida" @click="quickEvento('CARTAO_VERMELHO', atleta.id, partida.visitante_id)" class="w-7 h-7 rounded-full bg-red-100 text-red-700 hover:bg-red-600 hover:text-white flex items-center justify-center shadow-sm disabled:opacity-40 disabled:cursor-not-allowed" title="Cartão Vermelho">🟥</button>
                <button :disabled="!escalacaoCompletaPartida" @click="abrirSubstituicao(atleta, partida.visitante_id)" class="w-7 h-7 rounded-full bg-blue-100 text-blue-700 hover:bg-blue-600 hover:text-white flex items-center justify-center shadow-sm disabled:opacity-40 disabled:cursor-not-allowed" title="Substituição">🔄</button>
              </div>
            </div>
            <div v-if="atletasVisitante.length === 0" class="p-4 text-center text-gray-400 text-sm">
              Nenhum atleta listado.
            </div>
          </div>
        </div>
      </div>
      </div>
    </div>

    <!-- Modal de Substituição -->
    <div v-if="modalSubstituicao.aberto" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
      <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-6">
        <h3 class="text-lg font-bold text-gray-900 mb-4">Substituição</h3>
        
        <div class="space-y-4">
          <div>
            <label class="text-xs font-bold text-red-500 uppercase">Sai</label>
            <div class="p-3 bg-red-50 rounded-lg text-red-900 font-medium flex items-center gap-2">
              <span class="text-xl">⬇️</span> {{ modalSubstituicao.jogadorSai.nome }}
            </div>
          </div>

          <div>
            <label class="text-xs font-bold text-green-500 uppercase">Entra</label>
            <select v-model="modalSubstituicao.jogadorEntraId" class="w-full mt-1 rounded-lg border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500">
              <option :value="null" disabled>Selecione quem entra</option>
              <option v-for="atleta in getReservasDisponiveis(modalSubstituicao.timeId)" :key="atleta.id" :value="atleta.id">
                {{ atleta.nome }}
              </option>
            </select>
          </div>

          <div>
            <label class="text-xs font-bold text-gray-500 uppercase">Minuto</label>
            <input type="number" v-model="tempoJogo" class="w-full mt-1 rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" min="0">
          </div>
        </div>

        <div class="flex gap-3 mt-6">
          <button @click="modalSubstituicao.aberto = false" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 font-medium">Cancelar</button>
          <button 
            @click="confirmarSubstituicao" 
            class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium disabled:opacity-50 disabled:cursor-not-allowed"
            :disabled="!modalSubstituicao.jogadorEntraId"
          >
            Confirmar
          </button>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute } from 'vue-router';
import partidaService from '../services/partidaService';
import api from '@/services/api';
import { FileText } from 'lucide-vue-next';

const route = useRoute();
const partida = ref({});
const eventos = ref([]);
const atletasMandante = ref([]);
const atletasVisitante = ref([]);
const loading = ref(true);
const tempoJogo = ref(0); // Minuto atual simulado

const modalSubstituicao = ref({
  aberto: false,
  jogadorSai: null,
  timeId: null,
  jogadorEntraId: null
});

// Simple admin check
const user = JSON.parse(localStorage.getItem('user') || '{}');
const isAdmin = computed(() => user.perfil === 'ADMIN');

const eventosOrdenados = computed(() => {
  return [...eventos.value].sort((a, b) => b.minuto - a.minuto); // Mais recentes no topo
});

const titularesMandante = computed(() => (partida.value?.escalacoes || [])
  .filter((e) => Number(e.equipe_id) === Number(partida.value?.mandante_id) && Boolean(e.titular))
  .length);

const titularesVisitante = computed(() => (partida.value?.escalacoes || [])
  .filter((e) => Number(e.equipe_id) === Number(partida.value?.visitante_id) && Boolean(e.titular))
  .length);

const escalacaoCompletaPartida = computed(() => titularesMandante.value === 11 && titularesVisitante.value === 11);

const loadData = async () => {
  loading.value = true;
  try {
    const { data } = await partidaService.getPartida(route.params.id);
    partida.value = data;
    eventos.value = data.eventos || [];
    
    // Carregar elencos
    if (data.mandante_id) await carregarElenco(data.mandante_id, 'mandante');
    if (data.visitante_id) await carregarElenco(data.visitante_id, 'visitante');
    
  } catch (e) {
    console.error('Erro ao carregar partida', e);
  } finally {
    loading.value = false;
  }
};

const carregarElenco = async (timeId, lado) => {
  try {
    const { data } = await api.get(`/equipes/${timeId}`);
    const aprovados = (data.atletas || []).filter(a => a.pivot?.status === 'aprovado');
    if (lado === 'mandante') atletasMandante.value = aprovados;
    else atletasVisitante.value = aprovados;
  } catch (e) {
    console.error(`Erro ao carregar elenco ${lado}`, e);
  }
};

const quickEvento = async (tipo, jogadorId, timeId) => {
  if (!confirm(`Confirmar ${formatTipoEvento(tipo)} aos ${tempoJogo.value}'?`)) return;
  
  try {
    await partidaService.addEvento({
      partida_id: partida.value.id,
      tipo_evento: tipo,
      jogador_id: jogadorId,
      time_id: timeId,
      minuto: tempoJogo.value
    });
    // Recarrega dados para atualizar placar e lista de eventos
    const { data } = await partidaService.getPartida(partida.value.id);
    partida.value = data;
    eventos.value = data.eventos || [];
  } catch (e) {
    alert(e.response?.data?.message || 'Erro ao registrar evento');
  }
};

const abrirSubstituicao = (jogadorSai, timeId) => {
  if (!escalacaoCompletaPartida.value) {
    alert(`Escalação incompleta. Mandante: ${titularesMandante.value}/11 • Visitante: ${titularesVisitante.value}/11 titulares.`);
    return;
  }
  modalSubstituicao.value = {
    aberto: true,
    jogadorSai,
    timeId,
    jogadorEntraId: null
  };
};

const confirmarSubstituicao = async () => {
  try {
    await partidaService.addEvento({
      partida_id: partida.value.id,
      tipo_evento: 'SUBSTITUICAO',
      jogador_id: modalSubstituicao.value.jogadorEntraId, // Quem entra (o model espera jogador_id como principal?)
      // Ajuste: A migration nova tem jogador_saiu_id.
      // O EventoPartidaService precisa saber lidar com isso.
      // Vou assumir que o backend espera jogador_id (quem entra ou quem sofre a ação) e jogador_saiu_id (quem sai)
      // Mas o service genérico usa 'jogador_id'. 
      // Para SUBSTITUICAO, convencionamos: jogador_id = QUEM ENTRA, jogador_saiu_id = QUEM SAI.
      jogador_saiu_id: modalSubstituicao.value.jogadorSai.id,
      time_id: modalSubstituicao.value.timeId,
      minuto: tempoJogo.value,
      observacao: `Saiu: ${modalSubstituicao.value.jogadorSai.nome}`
    });
    
    modalSubstituicao.value.aberto = false;
    const { data } = await partidaService.getPartida(partida.value.id);
    partida.value = data;
    eventos.value = data.eventos || [];
  } catch (e) {
    alert(e.response?.data?.message || 'Erro ao registrar substituição');
  }
};

const deletarEvento = async (id) => {
  if (!confirm('Remover este evento?')) return;
  try {
    await partidaService.deleteEvento(id);
    const { data } = await partidaService.getPartida(partida.value.id);
    partida.value = data;
    eventos.value = data.eventos || [];
  } catch (e) {
    alert('Erro ao remover evento');
  }
};

// Helpers
const getIniciais = (nome) => nome ? nome.substring(0, 2).toUpperCase() : '??';
const getEquipeLogoUrl = (equipe) => {
  if (!equipe) return null;
  const logo = equipe.logo_url || equipe.logo || null;
  if (!logo) return null;
  if (logo.startsWith('http://') || logo.startsWith('https://')) return logo;
  if (logo.startsWith('/api/') || logo.startsWith('/storage/')) return logo;
  return `/api/v1/public/media/${String(logo).replace(/^\/+/, '')}`;
};
const formatTipoEvento = (tipo) => {
  const map = { GOL: 'GOL', CARTAO_AMARELO: 'Cartão Amarelo', CARTAO_VERMELHO: 'Cartão Vermelho', SUBSTITUICAO: 'Substituição' };
  return map[tipo] || tipo;
};
const formatDate = (d) => new Date(d).toLocaleDateString('pt-BR');
const getEventosAtleta = (atletaId) => eventos.value.filter(e => e.jogador_id === atletaId || e.jogador_saiu_id === atletaId);
const getEscalacaoAtleta = (timeId, atletaId) => (partida.value?.escalacoes || []).find(
  (esc) => Number(esc.equipe_id) === Number(timeId) && Number(esc.atleta_id) === Number(atletaId)
);
const isEscalado = (timeId, atletaId) => Boolean(getEscalacaoAtleta(timeId, atletaId));
const isTitular = (timeId, atletaId) => Boolean(getEscalacaoAtleta(timeId, atletaId)?.titular);
const getEventoBadgeClass = (tipo) => {
  if (tipo === 'GOL') return 'bg-green-500';
  if (tipo === 'CARTAO_AMARELO') return 'bg-yellow-400';
  if (tipo === 'CARTAO_VERMELHO') return 'bg-red-500';
  if (tipo === 'SUBSTITUICAO') return 'bg-blue-500';
  return 'bg-gray-400';
};
const getReservasDisponiveis = (timeId) => {
  const lista = timeId === partida.value.mandante_id ? atletasMandante.value : atletasVisitante.value;
  // Filtrar quem já jogou? Por enquanto mostra todos menos o que sai
  return lista.filter(a => a.id !== modalSubstituicao.value.jogadorSai?.id);
};
const printSumula = async () => {
  if (!partida.value.id) return;
  try {
    const { data } = await api.get(`/partidas/${partida.value.id}/pdf-sumula`);
    if (!data?.url) {
      throw new Error('URL da súmula não encontrada.');
    }
    window.open(data.url, '_blank', 'noopener');
  } catch (e) {
    alert(e.response?.data?.message || 'Não foi possível abrir a súmula em PDF.');
  }
};

onMounted(loadData);
</script>
