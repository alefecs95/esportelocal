<template>
  <div class="container mx-auto px-4 py-8">
    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
      <h1 class="text-2xl font-bold text-gray-800">Temporadas</h1>
      <button 
        @click="showCreateModal = true"
        class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
      >
        Nova Temporada
      </button>
    </div>

    <div v-if="error" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
      {{ error }}
    </div>

    <div v-if="loading && temporadas.length === 0" class="text-center py-4">
      Carregando...
    </div>

    <TemporadaTable 
      v-else 
      :temporadas="temporadas" 
      @ativar="handleAtivar" 
      @delete="handleDelete"
    />

    <!-- Modal de Criação -->
    <div v-if="showCreateModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full flex items-center justify-center z-50">
      <div class="bg-white p-5 rounded-lg shadow-xl w-full max-w-md">
        <h2 class="text-xl font-bold mb-4">Nova Temporada</h2>
        <TemporadaForm 
          :loading="loading" 
          :errors="formErrors"
          @submit="handleCreate" 
          @cancel="showCreateModal = false" 
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { useTemporadaStore } from '../store/temporadaStore';
import TemporadaTable from '../components/TemporadaTable.vue';
import TemporadaForm from '../components/TemporadaForm.vue';

const store = useTemporadaStore();
const temporadas = computed(() => store.temporadas);
const loading = computed(() => store.loading);
const error = computed(() => store.error);

const showCreateModal = ref(false);
const formErrors = ref({});

onMounted(() => {
  store.fetchTemporadas();
});

watch(showCreateModal, (val) => {
  if (val) {
    formErrors.value = {};
  }
});

const handleCreate = async (data) => {
  formErrors.value = {};
  try {
    await store.createTemporada(data);
    showCreateModal.value = false;
  } catch (e) {
    if (e.response && e.response.status === 422) {
      formErrors.value = e.response.data.errors;
    }
  }
};

const handleAtivar = async (temporada) => {
  if (confirm(`Deseja realmente ativar a temporada "${temporada.nome}"? Isso encerrará a temporada atual.`)) {
    try {
      await store.ativarTemporada(temporada.id);
    } catch (e) {
      alert(store.error || 'Erro ao ativar temporada.');
    }
  }
};

const handleDelete = async (temporada) => {
  if (confirm(`Tem certeza que deseja excluir a temporada "${temporada.nome}"?`)) {
    try {
      await store.deleteTemporada(temporada.id);
    } catch (e) {
      alert(store.error || 'Erro ao excluir temporada.');
    }
  }
};
</script>
