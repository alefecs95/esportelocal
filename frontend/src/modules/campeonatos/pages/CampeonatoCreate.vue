<template>
  <div class="container mx-auto px-4 py-8">
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-gray-800">Nova Competição</h1>
    </div>

    <BaseCard>
      <div v-if="error" class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
        <span class="block sm:inline">{{ error }}</span>
      </div>

      <CampeonatoForm
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
import { useCampeonatoStore } from '../store/campeonatoStore';
import BaseCard from '@/components/ui/BaseCard.vue';
import CampeonatoForm from '../components/CampeonatoForm.vue';

const router = useRouter();
const store = useCampeonatoStore();

const loading = computed(() => store.loading);
const error = computed(() => store.error);

const handleSubmit = async (data) => {
  try {
    await store.createCampeonato(data);
    router.push('/admin/competicoes');
  } catch (err) {
    // Error is handled in store
  }
};

const cancel = () => {
  router.push('/admin/competicoes');
};
</script>
