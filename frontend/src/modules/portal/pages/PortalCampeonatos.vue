<template>
  <div class="events-page min-h-screen bg-[#f8fafc] text-slate-900 overflow-x-clip">
    <!-- Cabeçalho editorial limpo -->
    <header class="bg-white border-b border-slate-200/80">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12">
        <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-6">
          <div class="max-w-2xl">
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-blue-600 mb-2">
              Calendário Esportivo
            </p>
            <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-slate-950 leading-[1.1]">
              Competições e Eventos
            </h1>
            <p class="text-slate-600 text-sm sm:text-base mt-2.5 leading-relaxed">
              Inscrições abertas, tabelas em andamento e resultados oficiais do esporte municipal.
            </p>
          </div>

          <!-- Busca rápida -->
          <div class="w-full lg:w-80">
            <label for="busca-eventos" class="sr-only">Buscar competição</label>
            <div class="relative">
              <Search :size="16" class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none" />
              <input
                id="busca-eventos"
                v-model="busca"
                type="search"
                placeholder="Buscar por nome, modalidade ou local..."
                class="w-full rounded-xl border border-slate-200 bg-slate-50/70 pl-10 pr-4 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:bg-white focus:border-blue-500 focus:outline-none focus:ring-4 focus:ring-blue-500/10 transition-colors"
              />
            </div>
          </div>
        </div>

        <!-- Filtros de status -->
        <div class="flex items-center gap-2 mt-6 overflow-x-auto pb-1 -mb-1 no-scrollbar">
          <button
            v-for="tab in statusTabs"
            :key="tab.value"
            type="button"
            @click="filtroStatus = tab.value"
            class="pressable inline-flex items-center gap-2 px-3.5 py-2 rounded-full text-xs sm:text-sm font-semibold whitespace-nowrap border"
            :class="
              filtroStatus === tab.value
                ? 'bg-slate-950 text-white border-slate-950 shadow-sm'
                : 'bg-white text-slate-600 border-slate-200 hover:border-slate-300 hover:text-slate-900'
            "
          >
            <span>{{ tab.label }}</span>
            <span
              class="text-[11px] px-1.5 py-0.5 rounded-full tabular-nums"
              :class="
                filtroStatus === tab.value
                  ? 'bg-white/15 text-white'
                  : 'bg-slate-100 text-slate-500'
              "
            >
              {{ contarPorStatus(tab.value) }}
            </span>
          </button>
        </div>
      </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-10 space-y-10">
      <!-- Loading skeleton -->
      <div v-if="loading" class="space-y-8">
        <div class="bg-white rounded-2xl border border-slate-200/80 overflow-hidden grid grid-cols-1 lg:grid-cols-12 animate-pulse">
          <div class="lg:col-span-7 aspect-[16/9] lg:aspect-auto lg:min-h-[320px] bg-slate-200"></div>
          <div class="lg:col-span-5 p-6 sm:p-8 space-y-4">
            <div class="h-5 w-28 bg-slate-200 rounded-full"></div>
            <div class="h-8 w-4/5 bg-slate-200 rounded-lg"></div>
            <div class="h-4 w-2/3 bg-slate-100 rounded"></div>
            <div class="h-4 w-1/2 bg-slate-100 rounded"></div>
            <div class="h-11 w-40 bg-slate-200 rounded-xl mt-6"></div>
          </div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
          <div
            v-for="n in 3"
            :key="n"
            class="bg-white rounded-2xl border border-slate-200/80 overflow-hidden animate-pulse"
          >
            <div class="aspect-[16/9] bg-slate-200"></div>
            <div class="p-5 space-y-3">
              <div class="h-4 w-24 bg-slate-200 rounded-full"></div>
              <div class="h-6 w-3/4 bg-slate-200 rounded"></div>
              <div class="h-4 w-1/2 bg-slate-100 rounded"></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty state -->
      <div
        v-else-if="campeonatosFiltrados.length === 0"
        class="bg-white rounded-2xl border border-slate-200/80 px-6 py-16 text-center max-w-xl mx-auto"
      >
        <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-4">
          <Trophy :size="22" />
        </div>
        <h2 class="text-lg font-bold text-slate-900">Nenhuma competição encontrada</h2>
        <p class="text-sm text-slate-500 mt-1.5">
          Tente limpar a busca ou selecionar outro filtro de status.
        </p>
        <button
          v-if="busca || filtroStatus !== 'todos'"
          type="button"
          @click="limparFiltros"
          class="pressable mt-5 inline-flex items-center px-4 py-2 rounded-xl bg-slate-950 text-white text-sm font-semibold hover:bg-slate-800"
        >
          Ver todas as competições
        </button>
      </div>

      <template v-else>
        <!-- Evento em destaque -->
        <section v-if="eventoDestaque" aria-label="Competição em destaque">
          <router-link
            :to="{ name: 'PortalCampeonato', params: { id: eventoDestaque.slug || eventoDestaque.id } }"
            class="event-card group bg-white rounded-2xl border border-slate-200/90 overflow-hidden grid grid-cols-1 lg:grid-cols-12 shadow-[0_1px_2px_rgba(15,23,42,0.04)]"
          >
            <div class="lg:col-span-7 relative overflow-hidden bg-slate-900 aspect-[16/9] lg:aspect-auto lg:min-h-[340px]">
              <img
                v-if="getBannerUrl(eventoDestaque)"
                :src="getBannerUrl(eventoDestaque)"
                :alt="eventoDestaque.nome"
                class="card-media w-full h-full object-cover"
              />
              <div
                v-else
                class="card-media w-full h-full bg-gradient-to-br from-slate-900 via-blue-950 to-slate-900 flex items-center justify-center"
              >
                <Trophy :size="56" class="text-white/20" />
              </div>
              <div class="absolute inset-0 bg-gradient-to-t from-slate-950/60 via-transparent to-transparent"></div>
              <div class="absolute top-4 left-4 flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-white/95 text-slate-900 shadow-sm">
                  {{ eventoDestaque.modalidade?.nome || 'Esporte' }}
                </span>
                <span
                  :class="getStatusPillClass(eventoDestaque.status)"
                  class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold shadow-sm"
                >
                  <span
                    v-if="eventoDestaque.status === 'inscricoes_abertas'"
                    class="w-1.5 h-1.5 rounded-full bg-emerald-500"
                  ></span>
                  {{ formatStatus(eventoDestaque.status) }}
                </span>
              </div>
            </div>

            <div class="lg:col-span-5 p-6 sm:p-8 flex flex-col justify-between gap-6">
              <div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-emerald-700 mb-2">
                  Destaque
                </p>
                <h2 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-950 group-hover:text-blue-700 transition-colors leading-tight">
                  {{ eventoDestaque.nome }}
                </h2>

                <dl class="mt-5 space-y-2.5 text-sm text-slate-600">
                  <div v-if="eventoDestaque.data_inicio" class="flex items-center gap-2.5">
                    <Calendar :size="16" class="text-slate-400 shrink-0" />
                    <dd class="tabular-nums">{{ formatPeriodo(eventoDestaque.data_inicio, eventoDestaque.data_fim) }}</dd>
                  </div>
                  <div v-if="eventoDestaque.local" class="flex items-center gap-2.5">
                    <MapPin :size="16" class="text-slate-400 shrink-0" />
                    <dd class="line-clamp-1">{{ eventoDestaque.local }}</dd>
                  </div>
                  <div v-if="eventoDestaque.categoria" class="flex items-center gap-2.5">
                    <Tag :size="16" class="text-slate-400 shrink-0" />
                    <dd>{{ eventoDestaque.categoria }}</dd>
                  </div>
                </dl>
              </div>

              <div class="pt-4 border-t border-slate-100 flex items-center justify-between gap-3">
                <span class="inline-flex items-center gap-2 text-sm font-bold text-slate-950 group-hover:text-blue-700 transition-colors">
                  {{ eventoDestaque.status === 'inscricoes_abertas' ? 'Ver evento e inscrever-se' : 'Abrir competição' }}
                  <ArrowRight :size="16" class="card-arrow" />
                </span>
              </div>
            </div>
          </router-link>
        </section>

        <!-- Grade de competições -->
        <section v-if="demaisCampeonatos.length > 0" aria-label="Lista de competições">
          <div class="flex items-center justify-between mb-5">
            <h2 class="text-base sm:text-lg font-bold text-slate-900">
              {{ eventoDestaque ? 'Outras competições' : 'Competições' }}
            </h2>
            <span class="text-xs font-medium text-slate-500 tabular-nums">
              {{ campeonatosFiltrados.length }} {{ campeonatosFiltrados.length === 1 ? 'evento' : 'eventos' }}
            </span>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <router-link
              v-for="camp in demaisCampeonatos"
              :key="camp.id"
              :to="{ name: 'PortalCampeonato', params: { id: camp.slug || camp.id } }"
              class="event-card group bg-white rounded-2xl border border-slate-200/90 overflow-hidden flex flex-col min-w-0 shadow-[0_1px_2px_rgba(15,23,42,0.04)]"
            >
              <div class="relative aspect-[16/9] overflow-hidden bg-slate-900">
                <img
                  v-if="getBannerUrl(camp)"
                  :src="getBannerUrl(camp)"
                  :alt="camp.nome"
                  class="card-media w-full h-full object-cover"
                />
                <div
                  v-else
                  class="card-media w-full h-full bg-gradient-to-br from-slate-900 via-blue-950 to-slate-900 flex items-center justify-center"
                >
                  <Trophy :size="36" class="text-white/20" />
                </div>
                <div class="absolute inset-0 bg-gradient-to-t from-slate-950/65 via-transparent to-transparent"></div>
                <div class="absolute bottom-3 left-3 right-3 flex items-center justify-between gap-2">
                  <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-white/95 text-slate-900 shadow-sm truncate">
                    {{ camp.modalidade?.nome || 'Esporte' }}
                  </span>
                  <span
                    :class="getStatusPillClass(camp.status)"
                    class="px-2.5 py-1 rounded-full text-[11px] font-bold shadow-sm shrink-0"
                  >
                    {{ formatStatus(camp.status) }}
                  </span>
                </div>
              </div>

              <div class="p-5 flex-1 flex flex-col min-w-0">
                <h3 class="font-bold text-lg text-slate-950 group-hover:text-blue-700 transition-colors line-clamp-2 leading-snug">
                  {{ camp.nome }}
                </h3>

                <div class="mt-3 space-y-1.5 text-xs sm:text-sm text-slate-500 flex-1">
                  <p v-if="camp.data_inicio" class="flex items-center gap-2 tabular-nums">
                    <Calendar :size="14" class="text-slate-400 shrink-0" />
                    <span>{{ formatPeriodo(camp.data_inicio, camp.data_fim) }}</span>
                  </p>
                  <p v-if="camp.local" class="flex items-center gap-2">
                    <MapPin :size="14" class="text-slate-400 shrink-0" />
                    <span class="truncate">{{ camp.local }}</span>
                  </p>
                </div>

                <div class="mt-5 pt-3.5 border-t border-slate-100 flex items-center justify-between text-sm font-semibold text-slate-900 group-hover:text-blue-700 transition-colors">
                  <span>Ver detalhes</span>
                  <ArrowRight :size="15" class="card-arrow" />
                </div>
              </div>
            </router-link>
          </div>
        </section>
      </template>
    </main>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { Calendar, MapPin, Trophy, Search, ArrowRight, Tag } from 'lucide-vue-next';
import portalService from '../services/portalService';

const campeonatos = ref([]);
const loading = ref(true);
const busca = ref('');
const filtroStatus = ref('todos');

const statusTabs = [
  { value: 'todos', label: 'Todos' },
  { value: 'inscricoes_abertas', label: 'Inscrições abertas' },
  { value: 'em_andamento', label: 'Em andamento' },
  { value: 'finalizado', label: 'Finalizados' },
];

const loadCampeonatos = async () => {
  loading.value = true;
  try {
    const { data } = await portalService.getCampeonatos();
    if (data?.data?.data) {
      campeonatos.value = data.data.data;
    } else if (Array.isArray(data?.data)) {
      campeonatos.value = data.data;
    } else {
      campeonatos.value = [];
    }
  } catch (e) {
    console.error('Erro ao carregar campeonatos', e);
  } finally {
    loading.value = false;
  }
};

const contarPorStatus = (status) => {
  if (status === 'todos') return campeonatos.value.length;
  return campeonatos.value.filter((c) => c.status === status).length;
};

const campeonatosFiltrados = computed(() => {
  const q = busca.value.trim().toLowerCase();
  return campeonatos.value.filter((c) => {
    if (filtroStatus.value !== 'todos' && c.status !== filtroStatus.value) {
      return false;
    }
    if (q) {
      const alvo = [c.nome, c.modalidade?.nome, c.local, c.categoria]
        .filter(Boolean)
        .join(' ')
        .toLowerCase();
      if (!alvo.includes(q)) return false;
    }
    return true;
  });
});

const eventoDestaque = computed(() => {
  if (busca.value.trim() || filtroStatus.value !== 'todos') return null;
  if (campeonatosFiltrados.value.length === 0) return null;
  const aberto = campeonatosFiltrados.value.find((c) => c.status === 'inscricoes_abertas');
  return aberto || campeonatosFiltrados.value[0];
});

const demaisCampeonatos = computed(() => {
  if (!eventoDestaque.value) return campeonatosFiltrados.value;
  return campeonatosFiltrados.value.filter((c) => c.id !== eventoDestaque.value.id);
});

const limparFiltros = () => {
  busca.value = '';
  filtroStatus.value = 'todos';
};

const getStatusPillClass = (status) => {
  const map = {
    inscricoes_abertas: 'bg-emerald-50 text-emerald-800 border border-emerald-200/80',
    inscricoes_encerradas: 'bg-amber-50 text-amber-800 border border-amber-200/80',
    em_andamento: 'bg-blue-50 text-blue-800 border border-blue-200/80',
    finalizado: 'bg-slate-100 text-slate-700 border border-slate-200/80',
  };
  return map[status] || 'bg-slate-100 text-slate-700';
};

const formatStatus = (status) => {
  const map = {
    inscricoes_abertas: 'Inscrições abertas',
    inscricoes_encerradas: 'Inscrições encerradas',
    em_andamento: 'Em andamento',
    finalizado: 'Finalizado',
    draft: 'Em breve',
  };
  return map[status] || (status ? status.replace(/_/g, ' ') : '');
};

const formatDate = (dateStr) => {
  if (!dateStr) return '';
  const match = String(dateStr).match(/^(\d{4})-(\d{2})-(\d{2})/);
  if (match) return `${match[3]}/${match[2]}/${match[1]}`;
  return new Date(dateStr).toLocaleDateString('pt-BR');
};

const formatPeriodo = (inicio, fim) => {
  const d1 = formatDate(inicio);
  const d2 = formatDate(fim);
  if (!d2 || d1 === d2) return d1;
  return `${d1} a ${d2}`;
};

const getBannerUrl = (camp) => {
  return camp?.banner_url || camp?.banner || '';
};

onMounted(loadCampeonatos);
</script>

<style scoped>
.events-page {
  --ease-out: cubic-bezier(0.23, 1, 0.32, 1);
}

.event-card {
  transition:
    transform 220ms var(--ease-out),
    box-shadow 220ms var(--ease-out),
    border-color 180ms ease;
}

.event-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 12px 28px -12px rgba(15, 23, 42, 0.14);
  border-color: rgba(148, 163, 184, 0.6);
}

.event-card:active {
  transform: scale(0.992);
}

.card-media {
  transition: transform 420ms var(--ease-out);
}

.event-card:hover .card-media {
  transform: scale(1.03);
}

.card-arrow {
  transition: transform 200ms var(--ease-out);
}

.event-card:hover .card-arrow {
  transform: translateX(3px);
}

.pressable {
  transition:
    transform 160ms var(--ease-out),
    background-color 160ms ease,
    border-color 160ms ease,
    color 160ms ease;
}

.pressable:active {
  transform: scale(0.97);
}

.no-scrollbar::-webkit-scrollbar {
  display: none;
}

.no-scrollbar {
  -ms-overflow-style: none;
  scrollbar-width: none;
}

@media (prefers-reduced-motion: reduce) {
  .event-card,
  .card-media,
  .card-arrow,
  .pressable {
    transition: none;
  }
  .event-card:hover,
  .event-card:active,
  .event-card:hover .card-media,
  .event-card:hover .card-arrow,
  .pressable:active {
    transform: none;
  }
}
</style>
