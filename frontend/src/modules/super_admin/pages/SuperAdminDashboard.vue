<template>
  <div class="space-y-6">
    <div class="flex justify-between items-center">
      <h1 class="text-2xl font-bold text-gray-900">Dashboard Global</h1>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <BaseCard class="p-6 bg-gradient-to-br from-blue-600 to-indigo-700 text-white">
        <div class="flex justify-between items-start">
          <div>
            <p class="text-blue-100 font-medium mb-1">Prefeituras Ativas</p>
            <h3 class="text-4xl font-bold">{{ stats.prefeituras }}</h3>
          </div>
          <Building2 :size="32" class="text-blue-200 opacity-80" />
        </div>
        <div class="mt-4 text-sm text-blue-200">
          Total de municípios na plataforma
        </div>
      </BaseCard>

      <BaseCard class="p-6 bg-gradient-to-br from-emerald-500 to-green-600 text-white">
        <div class="flex justify-between items-start">
          <div>
            <p class="text-emerald-100 font-medium mb-1">Total de Atletas</p>
            <h3 class="text-4xl font-bold">{{ stats.atletas }}</h3>
          </div>
          <Users :size="32" class="text-emerald-200 opacity-80" />
        </div>
        <div class="mt-4 text-sm text-emerald-200">
          Atletas cadastrados globalmente
        </div>
      </BaseCard>

      <BaseCard class="p-6 bg-gradient-to-br from-purple-500 to-purple-700 text-white">
        <div class="flex justify-between items-start">
          <div>
            <p class="text-purple-100 font-medium mb-1">Receita Estimada</p>
            <h3 class="text-4xl font-bold">R$ --</h3>
          </div>
          <TrendingUp :size="32" class="text-purple-200 opacity-80" />
        </div>
        <div class="mt-4 text-sm text-purple-200">
          Módulo financeiro em breve
        </div>
      </BaseCard>
    </div>

    <!-- Tabela de Prefeituras Recentes -->
    <BaseCard class="mt-8">
        <div class="p-6 border-b border-gray-100 flex justify-between items-center">
            <h2 class="text-lg font-bold text-gray-800">Prefeituras Recentes</h2>
            <router-link to="/super-admin/prefeituras" class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                Ver todas &rarr;
            </router-link>
        </div>
        <div class="p-6">
            <p class="text-gray-500 text-sm">Aqui você pode listar as últimas prefeituras adicionadas...</p>
        </div>
    </BaseCard>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { Building2, Users, TrendingUp } from 'lucide-vue-next';
import BaseCard from '../../../components/ui/BaseCard.vue';
import api from '../../../services/api';

const stats = ref({
    prefeituras: 0,
    atletas: 0
});

const loadStats = async () => {
    try {
        const { data } = await api.get('admin/prefeituras', { params: { per_page: -1 } });
        const list = Array.isArray(data) ? data : (data.data || []);
        stats.value.prefeituras = list.length;
        // Atletas globalmente exigiria um endpoint específico de stats globais, 
        // mas vamos deixar mockado por enquanto ou 0 se não houver
        stats.value.atletas = '12.4k'; // Mock
    } catch (e) {
        console.error(e);
    }
};

onMounted(() => {
    loadStats();
});
</script>
