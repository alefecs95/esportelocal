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

    <EmptyState
      v-else-if="!temporadas.length"
      title="Nenhuma temporada cadastrada"
      description="Crie uma temporada para organizar competições, ranking e inscrições."
    >
      <template #action>
        <button @click="showCreateModal = true" class="bg-blue-600 text-white px-4 py-2 rounded-lg font-medium">Nova Temporada</button>
      </template>
    </EmptyState>

    <TemporadaTable 
      v-else 
      :temporadas="temporadas" 
      @ativar="handleAtivar" 
      @delete="handleDelete"
    />

    <ConfirmDialog
      :is-open="confirm.isOpen.value"
      :title="confirm.title.value"
      :message="confirm.message.value"
      @confirm="confirm.confirm"
      @cancel="confirm.cancel"
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
import { useToast } from 'vue-toastification';
import { useTemporadaStore } from '../store/temporadaStore';
import TemporadaTable from '../components/TemporadaTable.vue';
import TemporadaForm from '../components/TemporadaForm.vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import ConfirmDialog from '@/components/ui/ConfirmDialog.vue';
import { useConfirmDialog } from '@/composables/useConfirmDialog';

const toast = useToast();
const confirm = useConfirmDialog();
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
  const ok = await confirm.ask(`Ativar a temporada "${temporada.nome}" encerra a temporada atual.`, 'Ativar temporada');
  if (!ok) return;
  try {
    await store.ativarTemporada(temporada.id);
    toast.success('Temporada ativada.');
  } catch (e) {
    toast.error(store.error || 'Erro ao ativar temporada.');
  }
};

const handleDelete = async (temporada) => {
  const ok = await confirm.ask(`Excluir a temporada "${temporada.nome}"?`, 'Excluir temporada');
  if (!ok) return;
  try {
    await store.deleteTemporada(temporada.id);
    toast.success('Temporada excluída.');
  } catch (e) {
    toast.error(store.error || 'Erro ao excluir temporada.');
  }
};
</script>
