<template>
  <div class="competicoes-list space-y-3.5">
    <article
      v-for="item in items"
      :key="item.id"
      class="comp-card group bg-white rounded-2xl border border-slate-200/90 shadow-[0_1px_2px_rgba(15,23,42,0.04)] overflow-hidden"
    >
      <div class="p-4 sm:p-5 flex flex-col lg:flex-row lg:items-center gap-4 sm:gap-5">
        <!-- Thumbnail do Banner -->
        <div class="relative w-full lg:w-44 h-36 lg:h-28 rounded-xl overflow-hidden bg-slate-100 shrink-0 border border-slate-200/70">
          <img
            v-if="getBannerUrl(item)"
            :src="getBannerUrl(item)"
            :alt="item.nome"
            class="comp-img w-full h-full object-cover"
          />
          <div
            v-else
            class="w-full h-full bg-gradient-to-br from-slate-800 to-slate-900 flex flex-col items-center justify-center text-white/80 p-3 text-center"
          >
            <Trophy :size="24" class="text-white/60 mb-1" />
            <span class="text-[11px] font-semibold uppercase tracking-wider text-white/75 line-clamp-1">
              {{ item.modalidade?.nome || 'Competição' }}
            </span>
          </div>
          <span
            class="absolute top-2 left-2 inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold border shadow-sm backdrop-blur-sm"
            :class="getStatusBadgeClass(item.status)"
          >
            <span
              v-if="item.status === 'inscricoes_abertas' || item.status === 'em_andamento'"
              class="h-1.5 w-1.5 rounded-full"
              :class="item.status === 'inscricoes_abertas' ? 'bg-emerald-500' : 'bg-blue-500'"
            ></span>
            {{ formatStatus(item.status) }}
          </span>
        </div>

        <!-- Informações Principais -->
        <div class="flex-1 min-w-0 space-y-2">
          <div class="flex flex-wrap items-center gap-1.5 text-xs">
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-md bg-slate-100 text-slate-700 font-semibold">
              {{ item.modalidade?.nome || 'Sem modalidade' }}
            </span>
            <span
              v-if="item.tipo_competicao === 'equipe'"
              class="inline-flex items-center px-2 py-0.5 rounded-md bg-indigo-50 text-indigo-700 font-medium"
            >
              Equipes
            </span>
            <span
              v-else
              class="inline-flex items-center px-2 py-0.5 rounded-md bg-sky-50 text-sky-700 font-medium"
            >
              Individual
            </span>
            <span
              v-if="item.inscricao_solidaria"
              class="inline-flex items-center px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-700 font-medium"
            >
              Solidária
            </span>
          </div>

          <div class="flex items-start justify-between gap-2">
            <h3 class="text-base sm:text-lg font-bold text-slate-950 tracking-tight leading-snug break-words">
              {{ item.nome }}
            </h3>
          </div>

          <div class="flex flex-wrap items-center gap-x-4 gap-y-1.5 text-xs sm:text-sm text-slate-600">
            <span class="inline-flex items-center gap-1.5 tabular-nums">
              <Calendar :size="14" class="text-slate-400 shrink-0" />
              {{ formatPeriodo(item.data_inicio, item.data_fim) }}
            </span>
            <span v-if="item.local || item.cidade_eleitoral" class="inline-flex items-center gap-1.5 min-w-0">
              <MapPin :size="14" class="text-slate-400 shrink-0" />
              <span class="truncate">{{ item.local || item.cidade_eleitoral }}</span>
            </span>
            <a
              :href="`/campeonatos/${item.slug || item.id}`"
              target="_blank"
              rel="noopener noreferrer"
              class="inline-flex items-center gap-1 text-blue-600 hover:text-blue-800 font-medium"
            >
              Página pública
              <ExternalLink :size="13" />
            </a>
          </div>
        </div>

        <!-- Ações Organizadas -->
        <div class="flex flex-wrap lg:flex-nowrap items-center gap-2 pt-3 lg:pt-0 border-t lg:border-t-0 border-slate-100 shrink-0">
          <button
            v-if="item.tipo_competicao === 'individual' || item.inscricao_solidaria"
            type="button"
            @click="$router.push(`/admin/competicoes/${item.id}/corrida/inscritos`)"
            class="pressable inline-flex items-center justify-center gap-1.5 px-3.5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs sm:text-sm font-semibold shadow-sm"
          >
            <Flag :size="15" />
            Corrida
          </button>

          <button
            v-if="item.tipo_competicao === 'equipe'"
            type="button"
            @click="$router.push(`/admin/competicoes/${item.id}/equipes`)"
            class="pressable inline-flex items-center justify-center gap-1.5 px-3.5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs sm:text-sm font-semibold shadow-sm"
          >
            <Shield :size="15" />
            Equipes
          </button>

          <button
            type="button"
            @click="$router.push(`/admin/competicoes/${item.id}/participantes`)"
            class="pressable inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl bg-slate-100 hover:bg-slate-200/80 text-slate-800 text-xs sm:text-sm font-semibold"
          >
            <Users :size="15" class="text-slate-500" />
            Participantes
          </button>

          <button
            type="button"
            @click="$router.push(`/admin/competicoes/${item.id}/tabela`)"
            class="pressable inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl bg-slate-100 hover:bg-slate-200/80 text-slate-800 text-xs sm:text-sm font-semibold"
          >
            <TableProperties :size="15" class="text-slate-500" />
            Tabela
          </button>

          <div class="flex items-center gap-1 ml-auto lg:ml-1 pl-1 lg:border-l border-slate-200">
            <button
              type="button"
              title="Editar competição"
              aria-label="Editar competição"
              @click="$emit('edit', item)"
              class="pressable inline-flex items-center justify-center w-9 h-9 rounded-xl text-slate-500 hover:text-slate-900 hover:bg-slate-100"
            >
              <Pencil :size="16" />
            </button>
            <button
              type="button"
              title="Excluir competição"
              aria-label="Excluir competição"
              @click="$emit('delete', item)"
              class="pressable inline-flex items-center justify-center w-9 h-9 rounded-xl text-slate-400 hover:text-red-600 hover:bg-red-50"
            >
              <Trash2 :size="16" />
            </button>
          </div>
        </div>
      </div>
    </article>

    <!-- Paginação -->
    <div
      v-if="pagination.last_page > 1"
      class="pt-4 flex flex-col sm:flex-row justify-between items-center gap-3 text-sm text-slate-600"
    >
      <span class="tabular-nums">
        Página <strong class="text-slate-900">{{ pagination.current_page }}</strong> de
        <strong class="text-slate-900">{{ pagination.last_page }}</strong>
      </span>
      <div class="flex items-center gap-2">
        <button
          type="button"
          :disabled="pagination.current_page === 1"
          @click="$emit('page-change', pagination.current_page - 1)"
          class="pressable px-3.5 py-2 rounded-xl border border-slate-200 bg-white text-slate-700 font-semibold hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed"
        >
          Anterior
        </button>
        <button
          type="button"
          :disabled="pagination.current_page === pagination.last_page"
          @click="$emit('page-change', pagination.current_page + 1)"
          class="pressable px-3.5 py-2 rounded-xl border border-slate-200 bg-white text-slate-700 font-semibold hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed"
        >
          Próxima
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import {
  Calendar,
  MapPin,
  Trophy,
  Users,
  TableProperties,
  Flag,
  Shield,
  Pencil,
  Trash2,
  ExternalLink,
} from 'lucide-vue-next';

defineProps({
  items: {
    type: Array,
    required: true,
  },
  pagination: {
    type: Object,
    required: true,
  },
});

defineEmits(['edit', 'delete', 'page-change']);

const getBannerUrl = (item) => {
  if (!item) return '';
  if (item.banner_url) return item.banner_url;
  if (item.banner && (item.banner.startsWith('http://') || item.banner.startsWith('https://'))) {
    return item.banner;
  }
  return '';
};

const formatDate = (dateString) => {
  if (!dateString) return '';
  const match = String(dateString).match(/^(\d{4})-(\d{2})-(\d{2})/);
  if (match) return `${match[3]}/${match[2]}/${match[1]}`;
  return new Date(dateString).toLocaleDateString('pt-BR');
};

const formatPeriodo = (inicio, fim) => {
  const d1 = formatDate(inicio);
  const d2 = formatDate(fim);
  if (!d1 && !d2) return 'Data a definir';
  if (!d2 || d1 === d2) return d1;
  return `${d1} a ${d2}`;
};

const formatStatus = (status) => {
  const map = {
    draft: 'Rascunho',
    inscricoes_abertas: 'Inscrições Abertas',
    inscricoes_encerradas: 'Inscrições Encerradas',
    em_andamento: 'Em Andamento',
    finalizado: 'Finalizado',
  };
  return map[status] || status;
};

const getStatusBadgeClass = (status) => {
  const map = {
    draft: 'bg-white/95 text-slate-700 border-slate-200/80',
    inscricoes_abertas: 'bg-emerald-50/95 text-emerald-800 border-emerald-200/80',
    inscricoes_encerradas: 'bg-amber-50/95 text-amber-800 border-amber-200/80',
    em_andamento: 'bg-blue-50/95 text-blue-800 border-blue-200/80',
    finalizado: 'bg-slate-100/95 text-slate-700 border-slate-200/80',
  };
  return map[status] || 'bg-white/95 text-slate-700 border-slate-200/80';
};
</script>

<style scoped>
.competicoes-list {
  --ease-out: cubic-bezier(0.23, 1, 0.32, 1);
}

.comp-card {
  transition:
    transform 220ms var(--ease-out),
    box-shadow 220ms var(--ease-out),
    border-color 220ms ease;
}

.comp-img {
  transition: transform 420ms var(--ease-out);
}

.pressable {
  transition:
    transform 150ms var(--ease-out),
    background-color 150ms ease,
    border-color 150ms ease,
    color 150ms ease;
}

.pressable:active {
  transform: scale(0.97);
}

@media (hover: hover) and (pointer: fine) {
  .comp-card:hover {
    border-color: rgba(148, 163, 184, 0.55);
    box-shadow: 0 8px 24px -14px rgba(15, 23, 42, 0.14);
  }

  .comp-card:hover .comp-img {
    transform: scale(1.04);
  }
}

@media (prefers-reduced-motion: reduce) {
  .comp-card,
  .comp-img,
  .pressable {
    transition: none;
  }

  .pressable:active,
  .comp-card:hover .comp-img {
    transform: none;
  }
}
</style>
