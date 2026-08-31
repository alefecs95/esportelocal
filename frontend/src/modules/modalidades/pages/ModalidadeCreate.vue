<template>
  <div class="container mx-auto px-4 py-8">
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-gray-800">Nova Modalidade</h1>
    </div>

    <BaseCard>
      <div v-if="error" class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
        <span class="block sm:inline">{{ error }}</span>
      </div>

      <ModalidadeForm
        :loading="loading"
        @submit="handleSubmit"
        @cancel="handleCancel"
      />
    </BaseCard>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useRouter } from 'vue-router';
import { useModalidadeStore } from '../store/modalidadeStore';
import BaseCard from '@/components/ui/BaseCard.vue';
import ModalidadeForm from '../components/ModalidadeForm.vue';

const router = useRouter();
const store = useModalidadeStore();

const loading = computed(() => store.loading);
const error = computed(() => store.error);

const handleSubmit = async (data) => {
  try {
    await store.createModalidade(data);
    router.push('/admin/modalidades');
  } catch (err) {
    // Error is handled in store
  }
};

const cancel = () => {
  router.push('/admin/modalidades');
};
</script>
