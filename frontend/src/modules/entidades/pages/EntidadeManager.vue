<template>
  <div class="p-6">
    <div class="mb-6 flex justify-between items-center">
      <div>
        <h1 class="text-2xl font-bold text-gray-800">Base de Clubes/Entidades</h1>
        <p class="text-gray-500 mt-1">Gerencie os clubes que participam dos campeonatos.</p>
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
      :entidades="filteredEntidades" 
      @edit="handleEdit" 
      @delete="handleDelete" 
    />

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
import { ref, onMounted, computed } from 'vue';
import entidadeService from '../services/entidadeService';
import EntidadeList from '../components/EntidadeList.vue';
import EntidadeForm from '../components/EntidadeForm.vue';

const entidades = ref([]);
const loading = ref(true);
const saving = ref(false);
const error = ref(null);
const searchQuery = ref('');

const showCreateModal = ref(false);
const showEditModal = ref(false);
const selectedEntidade = ref(null);

const filteredEntidades = computed(() => {
  if (!searchQuery.value) return entidades.value;
  const lower = searchQuery.value.toLowerCase();
  return entidades.value.filter(e => 
    e.nome.toLowerCase().includes(lower) || 
    (e.sigla && e.sigla.toLowerCase().includes(lower)) ||
    (e.cidade && e.cidade.toLowerCase().includes(lower))
  );
});

const loadEntidades = async () => {
  loading.value = true;
  try {
    const { data } = await entidadeService.getAll();
    entidades.value = data;
  } catch (e) {
    error.value = 'Erro ao carregar clubes.';
    console.error(e);
  } finally {
    loading.value = false;
  }
};

const handleEdit = (entidade) => {
  selectedEntidade.value = { ...entidade };
  showEditModal.value = true;
};

const handleDelete = async (id) => {
  if (!confirm('Tem certeza que deseja remover este clube? Isso não afetará históricos passados.')) return;
  
  try {
    await entidadeService.delete(id);
    entidades.value = entidades.value.filter(e => e.id !== id);
  } catch (e) {
    alert('Erro ao excluir clube.');
  }
};

const handleSave = async (formData) => {
  saving.value = true;
  try {
    if (showEditModal.value && selectedEntidade.value) {
      const { data } = await entidadeService.update(selectedEntidade.value.id, formData);
      const index = entidades.value.findIndex(e => e.id === data.id);
      if (index !== -1) entidades.value[index] = data;
    } else {
      const { data } = await entidadeService.create(formData);
      entidades.value.push(data);
      // Sort again?
      entidades.value.sort((a, b) => a.nome.localeCompare(b.nome));
    }
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

onMounted(loadEntidades);
</script>
