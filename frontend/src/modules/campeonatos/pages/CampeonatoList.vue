<template>
  <div class="admin-competicoes max-w-7xl mx-auto px-2 sm:px-4 py-4 sm:py-6 space-y-6">
    <!-- Cabeçalho -->
    <header class="flex flex-col sm:flex-row sm:items-end justify-between gap-4">
      <div>
        <div class="flex items-center gap-2.5">
          <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-950">
            Competições
          </h1>
          <span
            v-if="!loading"
            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-200/80 text-slate-700 tabular-nums"
          >
            {{ filteredCampeonatos.length }}
          </span>
        </div>
        <p class="text-sm text-slate-500 mt-1">
          Gerencie inscrições, operação de corrida, participantes, tabelas e resultados.
        </p>
      </div>

      <button
        type="button"
        @click="$router.push('/admin/competicoes/novo')"
        class="pressable inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold shadow-sm self-start sm:self-auto"
      >
        <Plus :size="18" />
        Nova Competição
      </button>
    </header>

    <!-- Barra de Busca e Filtros -->
    <section class="bg-white rounded-2xl border border-slate-200/90 shadow-[0_1px_2px_rgba(15,23,42,0.03)] p-4 space-y-4">
      <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
        <!-- Busca por nome ou local -->
        <div class="md:col-span-5 relative">
          <Search :size="16" class="text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" />
          <input
            v-model.trim="searchTerm"
            type="text"
            placeholder="Buscar competição por nome ou local..."
            class="w-full pl-10 pr-4 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl text-slate-900 placeholder:text-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-colors"
          />
        </div>

        <!-- Modalidade -->
        <div class="md:col-span-3">
          <select
            v-model="filters.modalidade_id"
            @change="applyFilters"
            aria-label="Filtrar por modalidade"
            class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-colors"
          >
            <option value="">Todas as modalidades</option>
            <option v-for="mod in modalidades" :key="mod.id" :value="mod.id">{{ mod.nome }}</option>
          </select>
        </div>

        <!-- Data Início -->
        <div class="md:col-span-3">
          <input
            v-model="filters.data_inicio"
            type="date"
            aria-label="Data de início a partir de"
            @change="applyFilters"
            class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-colors"
          />
        </div>

        <!-- Limpar -->
        <div class="md:col-span-1 flex items-center justify-end">
          <button
            v-if="hasActiveFilters"
            type="button"
            @click="clearFilters"
            class="pressable w-full md:w-auto px-3 py-2.5 rounded-xl text-xs font-semibold text-slate-600 hover:text-slate-950 hover:bg-slate-100"
          >
            Limpar
          </button>
        </div>
      </div>

      <!-- Pills de Status -->
      <div class="flex flex-wrap items-center gap-1.5 pt-1 border-t border-slate-100">
        <button
          v-for="pill in statusPills"
          :key="pill.value"
          type="button"
          @click="selectStatus(pill.value)"
          class="pressable px-3 py-1.5 rounded-full text-xs font-semibold border transition-colors"
          :class="
            filters.status === pill.value
              ? 'bg-slate-900 text-white border-slate-900'
              : 'bg-white text-slate-600 border-slate-200 hover:border-slate-300 hover:text-slate-900'
          "
        >
          {{ pill.label }}
        </button>
      </div>
    </section>

    <!-- Conteúdo -->
    <div v-if="loading" class="space-y-3.5 animate-pulse">
      <div
        v-for="n in 3"
        :key="n"
        class="bg-white rounded-2xl border border-slate-200/80 p-5 flex flex-col lg:flex-row gap-4"
      >
        <div class="w-full lg:w-44 h-36 lg:h-28 rounded-xl bg-slate-200"></div>
        <div class="flex-1 space-y-3 py-1">
          <div class="h-4 w-28 bg-slate-200 rounded"></div>
          <div class="h-6 w-2/3 bg-slate-200 rounded"></div>
          <div class="h-4 w-1/2 bg-slate-100 rounded"></div>
        </div>
      </div>
    </div>

    <div
      v-else-if="error"
      class="bg-red-50 border border-red-200 text-red-700 px-4 py-3.5 rounded-2xl text-sm"
      role="alert"
    >
      <strong class="font-bold">Erro:</strong> {{ error }}
    </div>

    <div v-else-if="!filteredCampeonatos.length" class="bg-white rounded-2xl border border-slate-200/80 py-10 px-6">
      <EmptyState
        :title="hasActiveFilters ? 'Nenhuma competição encontrada' : 'Nenhuma competição cadastrada'"
        :description="
          hasActiveFilters
            ? 'Tente ajustar os filtros ou limpar a busca para ver todas as competições.'
            : 'Crie a primeira competição da temporada para abrir inscrições e gerar a tabela.'
        "
      >
        <template #action>
          <button
            v-if="hasActiveFilters"
            type="button"
            @click="clearFilters"
            class="pressable px-4 py-2.5 rounded-xl border border-slate-300 bg-white text-sm font-semibold text-slate-800 hover:bg-slate-50"
          >
            Limpar filtros
          </button>
          <button
            v-else
            type="button"
            @click="$router.push('/admin/competicoes/novo')"
            class="pressable inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold"
          >
            <Plus :size="16" />
            Nova Competição
          </button>
        </template>
      </EmptyState>
    </div>

    <CampeonatoTable
      v-else
      :items="filteredCampeonatos"
      :pagination="pagination"
      @edit="handleEdit"
      @delete="handleDelete"
      @page-change="handlePageChange"
    />

    <BaseModal
      :is-open="showDeleteModal"
      title="Confirmar Exclusão"
      @close="showDeleteModal = false"
      @confirm="confirmDelete"
    >
      <p class="text-sm text-slate-600 leading-relaxed">
        Tem certeza que deseja excluir a competição
        <strong class="text-slate-900">{{ campeonatoToDelete?.nome }}</strong>? Esta ação não pode ser desfeita.
      </p>
    </BaseModal>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, reactive } from 'vue';
import { useRouter } from 'vue-router';
import { Plus, Search } from 'lucide-vue-next';
import { useCampeonatoStore } from '../store/campeonatoStore';
import { useModalidadeStore } from '@/modules/modalidades/store/modalidadeStore';
import BaseModal from '@/components/ui/BaseModal.vue';
import CampeonatoTable from '../components/CampeonatoTable.vue';
import EmptyState from '@/components/ui/EmptyState.vue';

const router = useRouter();
const store = useCampeonatoStore();
const modalidadeStore = useModalidadeStore();

const loading = computed(() => store.loading);
const error = computed(() => store.error);
const campeonatos = computed(() => store.campeonatos || []);
const pagination = computed(() => store.pagination);
const modalidades = computed(() => modalidadeStore.modalidades || []);

const searchTerm = ref('');
const filters = reactive({
  modalidade_id: '',
  status: '',
  data_inicio: '',
});

const statusPills = [
  { value: '', label: 'Todas' },
  { value: 'inscricoes_abertas', label: 'Inscrições Abertas' },
  { value: 'em_andamento', label: 'Em Andamento' },
  { value: 'inscricoes_encerradas', label: 'Inscrições Encerradas' },
  { value: 'draft', label: 'Rascunho' },
  { value: 'finalizado', label: 'Finalizado' },
];

const filteredCampeonatos = computed(() => {
  const q = searchTerm.value.toLowerCase();
  if (!q) return campeonatos.value;
  return campeonatos.value.filter((item) => {
    const nome = (item.nome || '').toLowerCase();
    const local = (item.local || item.cidade_eleitoral || '').toLowerCase();
    const mod = (item.modalidade?.nome || '').toLowerCase();
    return nome.includes(q) || local.includes(q) || mod.includes(q);
  });
});

const hasActiveFilters = computed(() => {
  return Boolean(searchTerm.value || filters.modalidade_id || filters.status || filters.data_inicio);
});

onMounted(() => {
  store.fetchCampeonatos();
  modalidadeStore.fetchModalidades({ per_page: 100 });
});

const applyFilters = () => {
  store.fetchCampeonatos({ ...filters });
};

const selectStatus = (status) => {
  filters.status = status;
  applyFilters();
};

const clearFilters = () => {
  searchTerm.value = '';
  filters.modalidade_id = '';
  filters.status = '';
  filters.data_inicio = '';
  store.fetchCampeonatos();
};

const handleEdit = (campeonato) => {
  router.push(`/admin/competicoes/${campeonato.id}/editar`);
};

const handleDelete = (campeonato) => {
  campeonatoToDelete.value = campeonato;
  showDeleteModal.value = true;
};

const confirmDelete = async () => {
  if (campeonatoToDelete.value) {
    await store.deleteCampeonato(campeonatoToDelete.value.id);
    showDeleteModal.value = false;
    campeonatoToDelete.value = null;
  }
};

const handlePageChange = (page) => {
  store.fetchCampeonatos({ page, ...filters });
};

const showDeleteModal = ref(false);
const campeonatoToDelete = ref(null);
</script>

<style scoped>
.admin-competicoes {
  --ease-out: cubic-bezier(0.23, 1, 0.32, 1);
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

@media (prefers-reduced-motion: reduce) {
  .pressable {
    transition: none;
  }
  .pressable:active {
    transform: none;
  }
}
</style>
