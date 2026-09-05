<template>
  <div class="p-6">
    <div class="mb-6 flex justify-between items-center">
      <div>
        <h1 class="text-2xl font-bold text-gray-800">Clubes</h1>
        <p class="text-gray-500 mt-1">Gerencie os clubes que participam das competições.</p>
      </div>
      <button 
        @click="showCreateModal = true"
        class="bg-blue-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-blue-700 transition-colors shadow-sm flex items-center gap-2"
      >
        <span>+</span> Novo Clube
      </button>
    </div>

    <!-- Filtros/Busca -->
    <div class="mb-6 bg-white p-4 rounded-lg shadow-sm border border-gray-100 flex gap-4">
      <input 
        v-model="searchQuery" 
        type="text" 
        placeholder="Buscar por nome, sigla ou cidade..." 
        class="flex-1 rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
        @input="onSearchInput"
      >
    </div>

    <div v-if="loading" class="text-center py-12">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
    </div>

    <div v-else-if="error" class="bg-red-100 text-red-700 p-4 rounded mb-4">
      {{ error }}
    </div>

    <EntidadeList 
      v-else 
      :entidades="entidades" 
      @edit="handleEdit" 
      @delete="handleDelete" 
    />
    <div v-if="pagination.last_page > 1" class="mt-4 flex items-center justify-between text-sm text-gray-600">
      <span>Página {{ pagination.current_page }} de {{ pagination.last_page }} ({{ pagination.total }} clubes)</span>
      <div class="flex gap-2">
        <button type="button" class="px-3 py-1 border rounded disabled:opacity-50" :disabled="pagination.current_page <= 1" @click="loadEntidades(pagination.current_page - 1)">Anterior</button>
        <button type="button" class="px-3 py-1 border rounded disabled:opacity-50" :disabled="pagination.current_page >= pagination.last_page" @click="loadEntidades(pagination.current_page + 1)">Próxima</button>
      </div>
    </div>

    <!-- Modal Create/Edit -->
    <div v-if="showCreateModal || showEditModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <EntidadeForm 
          :initialData="selectedEntidade" 
          :loading="saving"
          @submit="handleSave" 
          @cancel="closeModals" 
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import entidadeService from '../services/entidadeService';
import EntidadeList from '../components/EntidadeList.vue';
import EntidadeForm from '../components/EntidadeForm.vue';

const entidades = ref([]);
const loading = ref(true);
const saving = ref(false);
const error = ref(null);
const searchQuery = ref('');
const pagination = ref({ current_page: 1, last_page: 1, total: 0 });
let searchTimer = null;

const showCreateModal = ref(false);
const showEditModal = ref(false);
const selectedEntidade = ref(null);

const unwrapList = (payload) => {
  if (Array.isArray(payload)) return payload;
  if (Array.isArray(payload?.data)) return payload.data;
  return [];
};

const loadEntidades = async (page = 1) => {
  loading.value = true;
  try {
    const { data } = await entidadeService.getAll({
      page,
      per_page: 20,
      search: searchQuery.value || undefined,
    });
    entidades.value = unwrapList(data);
    pagination.value = {
      current_page: data.current_page || 1,
      last_page: data.last_page || 1,
      total: data.total || entidades.value.length,
    };
  } catch (e) {
    error.value = 'Erro ao carregar clubes.';
    console.error(e);
  } finally {
    loading.value = false;
  }
};

const onSearchInput = () => {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => loadEntidades(1), 300);
};

const handleEdit = (entidade) => {
  selectedEntidade.value = { ...entidade };
  showEditModal.value = true;
};

const handleDelete = async (id) => {
  if (!confirm('Tem certeza que deseja remover este clube? Isso não afetará históricos passados.')) return;
  
  try {
    await entidadeService.delete(id);
    await loadEntidades(pagination.value.current_page);
  } catch (e) {
    alert('Erro ao excluir clube.');
  }
};

const handleSave = async (formData) => {
  saving.value = true;
  try {
    if (showEditModal.value && selectedEntidade.value) {
      await entidadeService.update(selectedEntidade.value.id, formData);
    } else {
      await entidadeService.create(formData);
    }
    await loadEntidades(pagination.value.current_page);
    closeModals();
  } catch (e) {
    alert(e.response?.data?.message || 'Erro ao salvar clube.');
  } finally {
    saving.value = false;
  }
};

const closeModals = () => {
  showCreateModal.value = false;
  showEditModal.value = false;
  selectedEntidade.value = null;
};

onMounted(() => loadEntidades(1));
</script>
