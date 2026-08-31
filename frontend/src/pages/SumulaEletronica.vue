<template>
  <div class="min-h-screen bg-slate-950 text-white">
    <div class="mx-auto max-w-6xl px-4 py-10 sm:px-6 lg:px-8">
      <div class="mb-8 rounded-2xl border border-slate-800 bg-slate-900/70 p-6 backdrop-blur">
        <p class="text-xs font-bold uppercase tracking-[0.25em] text-blue-400">Súmula Eletrônica</p>
        <h1 class="mt-3 text-3xl font-black leading-tight sm:text-4xl">Painel oficial da partida</h1>
        <p class="mt-3 text-slate-300">Visual moderno com dados reais da partida, eventos e geração do PDF oficial.</p>
        <div class="mt-6 flex flex-col gap-3 sm:flex-row">
          <input
            v-model="partidaIdInput"
            type="number"
            min="1"
            placeholder="Informe o ID da partida (ex: 17)"
            class="w-full rounded-xl border border-slate-700 bg-slate-800 px-4 py-3 text-sm text-white outline-none focus:border-blue-500"
          >
          <button
            @click="carregarPartida"
            :disabled="loading"
            class="rounded-xl bg-blue-600 px-5 py-3 text-sm font-bold hover:bg-blue-500 disabled:opacity-60"
          >
            {{ loading ? 'Carregando...' : 'Carregar Partida' }}
          </button>
          <button
            @click="abrirSumulaPdf"
            :disabled="!partida?.id || loadingPdf"
            class="rounded-xl border border-blue-400/40 bg-blue-500/10 px-5 py-3 text-sm font-bold text-blue-300 hover:bg-blue-500/20 disabled:opacity-40"
          >
            {{ loadingPdf ? 'Abrindo PDF...' : 'Visualizar Súmula PDF' }}
          </button>
        </div>
        <p v-if="erro" class="mt-4 rounded-lg border border-red-500/30 bg-red-500/10 px-3 py-2 text-sm text-red-300">{{ erro }}</p>
      </div>

      <div v-if="partida" class="space-y-6">
        <div class="grid gap-4 sm:grid-cols-3">
          <div class="rounded-2xl border border-slate-800 bg-slate-900 p-4">
            <p class="text-xs text-slate-400">Competição</p>
            <p class="mt-1 text-lg font-bold">{{ campeonatoNome }}</p>
          </div>
          <div class="rounded-2xl border border-slate-800 bg-slate-900 p-4">
            <p class="text-xs text-slate-400">Data e Local</p>
            <p class="mt-1 text-lg font-bold">{{ dataLocalLabel }}</p>
          </div>
          <div class="rounded-2xl border border-slate-800 bg-slate-900 p-4">
            <p class="text-xs text-slate-400">Placar</p>
            <p class="mt-1 text-lg font-bold">{{ placarLabel }}</p>
          </div>
        </div>

        <div class="rounded-2xl border border-slate-800 bg-gradient-to-r from-slate-900 to-slate-800 p-6">
          <div class="grid items-center gap-4 md:grid-cols-3">
            <div class="text-center md:text-left">
              <p class="text-xs uppercase tracking-widest text-slate-400">Mandante</p>
              <p class="mt-2 text-2xl font-black">{{ mandanteNome }}</p>
            </div>
            <div class="text-center">
              <p class="text-xs uppercase tracking-widest text-slate-400">Resultado</p>
              <p class="mt-2 text-4xl font-black">{{ placarMandante }} <span class="text-slate-500">x</span> {{ placarVisitante }}</p>
            </div>
            <div class="text-center md:text-right">
              <p class="text-xs uppercase tracking-widest text-slate-400">Visitante</p>
              <p class="mt-2 text-2xl font-black">{{ visitanteNome }}</p>
            </div>
          </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
          <div class="rounded-xl border border-slate-800 bg-slate-900 p-4">
            <p class="text-xs text-slate-400">Eventos</p>
            <p class="mt-1 text-2xl font-extrabold">{{ eventos.length }}</p>
          </div>
          <div class="rounded-xl border border-slate-800 bg-slate-900 p-4">
            <p class="text-xs text-slate-400">Gols</p>
            <p class="mt-1 text-2xl font-extrabold text-green-400">{{ totalGols }}</p>
          </div>
          <div class="rounded-xl border border-slate-800 bg-slate-900 p-4">
            <p class="text-xs text-slate-400">Cartões</p>
            <p class="mt-1 text-2xl font-extrabold text-amber-400">{{ totalCartoes }}</p>
          </div>
          <div class="rounded-xl border border-slate-800 bg-slate-900 p-4">
            <p class="text-xs text-slate-400">Substituições</p>
            <p class="mt-1 text-2xl font-extrabold text-blue-400">{{ totalSubstituicoes }}</p>
          </div>
        </div>

        <div class="rounded-2xl border border-slate-800 bg-slate-900 p-6">
          <div class="mb-4 flex items-center justify-between">
            <h2 class="text-lg font-black">Timeline de eventos</h2>
            <span class="text-xs text-slate-400">Do mais recente para o mais antigo</span>
          </div>
          <div v-if="eventosOrdenados.length" class="space-y-3">
            <div v-for="evento in eventosOrdenados" :key="evento.id" class="rounded-xl border border-slate-800 bg-slate-950 px-4 py-3">
              <div class="flex items-center justify-between">
                <p class="text-sm font-bold">{{ formatTipoEvento(evento.tipo_evento) }}</p>
                <span class="rounded-full bg-slate-800 px-2 py-0.5 text-xs font-bold text-slate-300">{{ evento.minuto }}'</span>
              </div>
              <p class="mt-1 text-sm text-slate-300">{{ evento.jogador?.nome || 'Jogador não informado' }}</p>
            </div>
          </div>
          <p v-else class="text-sm text-slate-400">Nenhum evento registrado para esta partida.</p>
        </div>
      </div>
      <div v-else class="space-y-6">
        <div class="rounded-2xl border border-slate-800 bg-slate-900 p-6">
          <p class="text-xs font-bold uppercase tracking-[0.2em] text-blue-400">Modo demonstração</p>
          <h2 class="mt-2 text-2xl font-black">Veja como a súmula funciona na prática</h2>
          <p class="mt-2 text-slate-300">Este é um exemplo visual para apresentar o fluxo do app mesmo antes de informar o ID de uma partida.</p>
        </div>
        <div class="rounded-2xl border border-slate-800 bg-gradient-to-r from-slate-900 to-slate-800 p-6">
          <div class="grid items-center gap-4 md:grid-cols-3">
            <div class="text-center md:text-left">
              <p class="text-xs uppercase tracking-widest text-slate-400">Mandante</p>
              <p class="mt-2 text-2xl font-black">{{ demoPartida.mandante }}</p>
            </div>
            <div class="text-center">
              <p class="text-xs uppercase tracking-widest text-slate-400">Resultado</p>
              <p class="mt-2 text-4xl font-black">{{ demoPartida.placar }}</p>
            </div>
            <div class="text-center md:text-right">
              <p class="text-xs uppercase tracking-widest text-slate-400">Visitante</p>
              <p class="mt-2 text-2xl font-black">{{ demoPartida.visitante }}</p>
            </div>
          </div>
        </div>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
          <div class="rounded-xl border border-slate-800 bg-slate-900 p-4">
            <p class="text-xs text-slate-400">Eventos</p>
            <p class="mt-1 text-2xl font-extrabold">{{ demoEventos.length }}</p>
          </div>
          <div class="rounded-xl border border-slate-800 bg-slate-900 p-4">
            <p class="text-xs text-slate-400">Gols</p>
            <p class="mt-1 text-2xl font-extrabold text-green-400">3</p>
          </div>
          <div class="rounded-xl border border-slate-800 bg-slate-900 p-4">
            <p class="text-xs text-slate-400">Cartões</p>
            <p class="mt-1 text-2xl font-extrabold text-amber-400">2</p>
          </div>
          <div class="rounded-xl border border-slate-800 bg-slate-900 p-4">
            <p class="text-xs text-slate-400">Substituições</p>
            <p class="mt-1 text-2xl font-extrabold text-blue-400">4</p>
          </div>
        </div>
        <div class="rounded-2xl border border-slate-800 bg-slate-900 p-6">
          <div class="mb-4 flex items-center justify-between">
            <h2 class="text-lg font-black">Timeline simulada</h2>
            <span class="text-xs text-slate-400">Exemplo de registro em tempo real</span>
          </div>
          <div class="space-y-3">
            <div v-for="evento in demoEventos" :key="evento.id" class="rounded-xl border border-slate-800 bg-slate-950 px-4 py-3">
              <div class="flex items-center justify-between">
                <p class="text-sm font-bold">{{ evento.tipo }}</p>
                <span class="rounded-full bg-slate-800 px-2 py-0.5 text-xs font-bold text-slate-300">{{ evento.minuto }}</span>
              </div>
              <p class="mt-1 text-sm text-slate-300">{{ evento.descricao }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import api from '@/services/api';
import partidaService from '@/modules/tabela/services/partidaService';

const route = useRoute();
const partidaIdInput = ref(route.query.partida_id ? String(route.query.partida_id) : '');
const partida = ref(null);
const eventos = ref([]);
const erro = ref('');
const loading = ref(false);
const loadingPdf = ref(false);
const demoPartida = {
  mandante: 'Bela Vista',
  visitante: 'União FC',
  placar: '2 x 1'
};
const demoEventos = [
  { id: 1, tipo: 'Gol', minuto: "12'", descricao: 'Pedro Santos abre o placar para Bela Vista.' },
  { id: 2, tipo: 'Cartão Amarelo', minuto: "24'", descricao: 'João Silva advertido por falta tática.' },
  { id: 3, tipo: 'Substituição', minuto: "37'", descricao: 'Lucas entra no lugar de Carlos.' },
  { id: 4, tipo: 'Gol', minuto: "51'", descricao: 'União FC empata com finalização de Rafael.' },
  { id: 5, tipo: 'Gol', minuto: "74'", descricao: 'Bela Vista marca o gol da vitória com Matheus.' }
];

const eventosOrdenados = computed(() => [...eventos.value].sort((a, b) => (b.minuto || 0) - (a.minuto || 0)));
const totalGols = computed(() => eventos.value.filter((e) => ['GOL', 'GOL_CONTRA'].includes(e.tipo_evento)).length);
const totalCartoes = computed(() => eventos.value.filter((e) => ['CARTAO_AMARELO', 'CARTAO_VERMELHO'].includes(e.tipo_evento)).length);
const totalSubstituicoes = computed(() => eventos.value.filter((e) => e.tipo_evento === 'SUBSTITUICAO').length);

const mandanteNome = computed(() => partida.value?.mandante?.nome || 'Mandante');
const visitanteNome = computed(() => partida.value?.visitante?.nome || 'Visitante');
const placarMandante = computed(() => partida.value?.placar_mandante ?? 0);
const placarVisitante = computed(() => partida.value?.placar_visitante ?? 0);
const placarLabel = computed(() => `${placarMandante.value} x ${placarVisitante.value}`);
const campeonatoNome = computed(() => partida.value?.fase?.campeonato?.nome || partida.value?.campeonato?.nome || 'Não informado');
const dataLocalLabel = computed(() => {
  const local = partida.value?.local || 'Local a definir';
  if (!partida.value?.data_jogo) return `Data a definir • ${local}`;
  return `${formatDateTime(partida.value.data_jogo)} • ${local}`;
});

const formatDateTime = (valor) => {
  if (!valor) return '-';
  const d = new Date(valor);
  if (Number.isNaN(d.getTime())) return String(valor);
  return d.toLocaleString('pt-BR');
};

const formatTipoEvento = (tipo) => {
  const map = {
    GOL: 'Gol',
    GOL_CONTRA: 'Gol Contra',
    CARTAO_AMARELO: 'Cartão Amarelo',
    CARTAO_VERMELHO: 'Cartão Vermelho',
    SUBSTITUICAO: 'Substituição'
  };
  return map[tipo] || tipo;
};

const carregarPorApiPrivada = async (id) => {
  const { data } = await partidaService.getPartida(id);
  return data;
};

const carregarPorApiPublica = async (id) => {
  const { data } = await api.get(`/public/partidas/${id}`);
  return data?.data || data;
};

const carregarPartida = async () => {
  erro.value = '';
  const id = Number(partidaIdInput.value);
  if (!id) {
    erro.value = 'Informe um ID de partida válido.';
    return;
  }

  loading.value = true;
  try {
    let payload = null;
    try {
      payload = await carregarPorApiPrivada(id);
    } catch {
      payload = await carregarPorApiPublica(id);
    }
    partida.value = payload;
    eventos.value = payload?.eventos || [];
  } catch (e) {
    erro.value = e.response?.data?.message || 'Não foi possível carregar os dados da partida.';
    partida.value = null;
    eventos.value = [];
  } finally {
    loading.value = false;
  }
};

const abrirSumulaPdf = async () => {
  if (!partida.value?.id) return;
  loadingPdf.value = true;
  erro.value = '';
  try {
    const { data } = await api.get(`/partidas/${partida.value.id}/pdf-sumula`);
    if (!data?.url) {
      throw new Error('URL da súmula não encontrada.');
    }
    window.open(data.url, '_blank', 'noopener');
  } catch (e) {
    erro.value = e.response?.data?.message || 'Não foi possível abrir a súmula em PDF.';
  } finally {
    loadingPdf.value = false;
  }
};

watch(
  () => route.query.partida_id,
  (valor) => {
    if (!valor) return;
    partidaIdInput.value = String(valor);
    carregarPartida();
  }
);

onMounted(() => {
  if (partidaIdInput.value) {
    carregarPartida();
  }
});
</script>
