<template>
  <div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-bold text-gray-800">Modalidades</h1>
      <BaseButton variant="primary" @click="$router.push('/admin/modalidades/novo')">
        Nova Modalidade
      </BaseButton>
    </div>

    <BaseCard>
      <div v-if="loading" class="text-center py-8">
        <p class="text-gray-500">Carregando...</p>
      </div>

      <div v-else-if="error" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
        <strong class="font-bold">Erro!</strong>
        <span class="block sm:inline">{{ error }}</span>
      </div>

      <ModalidadeTable
        v-else
        :items="modalidades"
        :pagination="pagination"
        @edit="handleEdit"
        @delete="handleDelete"
        @page-change="handlePageChange"
      />
    </BaseCard>

    <BaseModal
      :is-open="showDeleteModal"
      title="Confirmar Exclusão"
      @close="showDeleteModal = false"
      @confirm="confirmDelete"
    >
      <p class="text-sm text-gray-500">
        Tem certeza que deseja excluir a modalidade <strong>{{ modalidadeToDelete?.nome }}</strong>? Esta ação não pode ser desfeita.
      </p>
    </BaseModal>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useModalidadeStore } from '../store/modalidadeStore';
import BaseButton from '@/components/ui/BaseButton.vue';
import BaseCard from '@/components/ui/BaseCard.vue';
import BaseModal from '@/components/ui/BaseModal.vue';
import ModalidadeTable from '../components/ModalidadeTable.vue';

const router = useRouter();
const store = useModalidadeStore();

const loading = computed(() => store.loading);
const error = computed(() => store.error);
const modalidades = computed(() => store.modalidades);
const pagination = computed(() => store.pagination);

const showDeleteModal = ref(false);
const modalidadeToDelete = ref(null);

onMounted(() => {
  store.fetchModalidades();
});

const handleEdit = (modalidade) => {
  router.push(`/admin/modalidades/${modalidade.id}/editar`);
};

const handleDelete = (modalidade) => {
  modalidadeToDelete.value = modalidade;
  showDeleteModal.value = true;
};

const confirmDelete = async () => {
  if (modalidadeToDelete.value) {
    await store.deleteModalidade(modalidadeToDelete.value.id);
    showDeleteModal.value = false;
    modalidadeToDelete.value = null;
  }
};

const handlePageChange = (page) => {
  store.fetchModalidades({ page });
};
</script>
