<template>
  <div class="container mx-auto px-4 py-8">
    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
      <h1 class="text-2xl font-bold text-gray-800">Competições</h1>
      <BaseButton variant="primary" @click="$router.push('/admin/competicoes/novo')">
        Nova Competição
      </BaseButton>
    </div>

    <!-- Filtros -->
    <BaseCard class="mb-6">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Modalidade</label>
          <select v-model="filters.modalidade_id" class="block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm sm:text-sm">
            <option value="">Todas</option>
            <option v-for="mod in modalidades" :key="mod.id" :value="mod.id">{{ mod.nome }}</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
          <select v-model="filters.status" class="block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm sm:text-sm">
            <option value="">Todos</option>
            <option value="draft">Rascunho</option>
            <option value="inscricoes_abertas">Inscrições Abertas</option>
            <option value="inscricoes_encerradas">Inscrições Encerradas</option>
            <option value="em_andamento">Em Andamento</option>
            <option value="finalizado">Finalizado</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Data Início (A partir de)</label>
          <input type="date" v-model="filters.data_inicio" class="block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm sm:text-sm" />
        </div>
        <div class="flex items-end">
          <BaseButton variant="secondary" class="w-full" @click="applyFilters">
            Filtrar
          </BaseButton>
        </div>
      </div>
    </BaseCard>

    <BaseCard>
      <div v-if="loading" class="text-center py-8">
        <p class="text-gray-500">Carregando...</p>
      </div>

      <div v-else-if="error" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
        <strong class="font-bold">Erro!</strong>
        <span class="block sm:inline">{{ error }}</span>
      </div>

      <div v-else-if="!campeonatos.length">
        <EmptyState
          title="Nenhuma competição cadastrada"
          description="Crie a primeira competição da temporada para abrir inscrições e gerar a tabela."
        >
          <template #action>
            <BaseButton variant="primary" @click="$router.push('/admin/competicoes/novo')">Nova Competição</BaseButton>
          </template>
        </EmptyState>
      </div>

      <div v-else>
        <CampeonatoTable
          :items="campeonatos"
          :pagination="pagination"
          @edit="handleEdit"
          @delete="handleDelete"
          @page-change="handlePageChange"
        />
      </div>
    </BaseCard>

    <BaseModal
      :is-open="showDeleteModal"
      title="Confirmar Exclusão"
      @close="showDeleteModal = false"
      @confirm="confirmDelete"
    >
      <p class="text-sm text-gray-500">
        Tem certeza que deseja excluir a competição <strong>{{ campeonatoToDelete?.nome }}</strong>? Esta ação não pode ser desfeita.
      </p>
    </BaseModal>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, reactive } from 'vue';
import { useRouter } from 'vue-router';
import { useCampeonatoStore } from '../store/campeonatoStore';
import { useModalidadeStore } from '@/modules/modalidades/store/modalidadeStore';
import BaseButton from '@/components/ui/BaseButton.vue';
import BaseCard from '@/components/ui/BaseCard.vue';
import BaseModal from '@/components/ui/BaseModal.vue';
import CampeonatoTable from '../components/CampeonatoTable.vue';
import EmptyState from '@/components/ui/EmptyState.vue';

const router = useRouter();
const store = useCampeonatoStore();
const modalidadeStore = useModalidadeStore();

const loading = computed(() => store.loading);
const error = computed(() => store.error);
const campeonatos = computed(() => store.campeonatos);
const pagination = computed(() => store.pagination);
const modalidades = computed(() => modalidadeStore.modalidades);

const filters = reactive({
  modalidade_id: '',
  status: '',
  data_inicio: '',
});

onMounted(() => {
  store.fetchCampeonatos();
  modalidadeStore.fetchModalidades({ per_page: 100 });
});

const applyFilters = () => {
  store.fetchCampeonatos(filters);
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
