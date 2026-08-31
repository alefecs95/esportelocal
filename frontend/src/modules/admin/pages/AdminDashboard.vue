<template>
  <div class="space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <BaseCard class="p-6 border-l-4 border-blue-500">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-500 font-medium">Prefeituras Ativas</p>
            <h3 class="text-3xl font-bold text-gray-900 mt-2">{{ stats.activeTenants }}</h3>
          </div>
          <Building2 :size="40" class="text-blue-100" />
        </div>
      </BaseCard>

      <BaseCard class="p-6 border-l-4 border-green-500">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-500 font-medium">Total Usuários</p>
            <h3 class="text-3xl font-bold text-gray-900 mt-2">{{ stats.totalUsers }}</h3>
          </div>
          <Users :size="40" class="text-green-100" />
        </div>
      </BaseCard>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { Building2, Users } from 'lucide-vue-next';
import BaseCard from '../../../components/ui/BaseCard.vue';
import api from '../../../services/api';

const stats = ref({
    activeTenants: 0,
    totalUsers: 0
});

onMounted(async () => {
    // TODO: Criar endpoint de dashboard admin
    // Por enquanto mockado ou pegando contagem de prefeituras
    try {
        const { data } = await api.get('admin/prefeituras');
        stats.value.activeTenants = data.filter(p => p.ativo).length;
    } catch (e) {
        console.error(e);
    }
});
</script>
