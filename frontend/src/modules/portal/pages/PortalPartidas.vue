<template>
  <div class="p-4 max-w-7xl mx-auto">
    <div class="mb-6 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-black text-gray-800 flex items-center gap-2">
          <Shield :size="22" class="text-blue-600" />
          Partidas
        </h1>
        <p class="text-gray-500 mt-1 flex items-center gap-2">
          <span class="h-2.5 w-2.5 rounded-full" :class="onlinePortal ? 'bg-emerald-500 animate-pulse' : 'bg-slate-400'"></span>
          Acompanhe jogos agendados, ao vivo e finalizados.
        </p>
      </div>
      <div class="w-full sm:w-auto relative">
        <Filter :size="15" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none" />
        <select v-model="statusFiltro" class="w-full sm:w-56 rounded-lg border border-gray-300 bg-white pl-9 pr-3 py-2 text-sm text-gray-700">
          <option value="">Todos os status</option>
          <option value="em_andamento">Ao Vivo</option>
          <option value="agendada">Agendada</option>
          <option value="finalizada">Finalizada</option>
        </select>
      </div>
    </div>

    <div v-if="loading" class="grid grid-cols-1 lg:grid-cols-2 gap-4">
      <div v-for="i in 6" :key="i" class="h-40 rounded-xl bg-gray-100 animate-pulse"></div>
    </div>

    <div v-else-if="erro" class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
      {{ erro }}
    </div>

    <div v-else>
      <div v-if="partidas.length === 0" class="text-center py-14 bg-white rounded-xl border border-gray-100 text-gray-500">
        Nenhuma partida encontrada para o filtro selecionado.
      </div>

      <div v-else class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <PartidaPublicCard v-for="partida in partidas" :key="partida.id" :partida="partida" />
      </div>

      <div v-if="paginacao.hasMore" class="mt-6 flex justify-center">
        <button @click="carregarMais" :disabled="carregandoMais" class="px-5 py-2.5 rounded-lg bg-gray-900 text-white text-sm font-bold hover:bg-gray-800 disabled:opacity-60">
          {{ carregandoMais ? 'Carregando...' : 'Carregar mais partidas' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, onUnmounted, ref, watch } from 'vue';
import portalService from '@/modules/portal/services/portalService';
import PartidaPublicCard from '@/modules/portal/components/PartidaPublicCard.vue';
import { Shield, Filter } from 'lucide-vue-next';

const loading = ref(true);
const carregandoMais = ref(false);
const erro = ref('');
const partidas = ref([]);
const statusFiltro = ref('');
const onlinePortal = ref(typeof navigator !== 'undefined' ? navigator.onLine : true);
const paginacao = ref({
  page: 1,
  hasMore: false
});
const marcarOnline = () => {
  onlinePortal.value = true;
};
const marcarOffline = () => {
  onlinePortal.value = false;
};

const carregarPartidas = async ({ append = false } = {}) => {
  if (!append) {
    loading.value = true;
    erro.value = '';
  } else {
    carregandoMais.value = true;
  }

  try {
    const { data } = await portalService.getPartidas({
      per_page: 12,
      page: paginacao.value.page,
      status: statusFiltro.value || undefined
    });

    const lista = data?.data || [];
    partidas.value = append ? [...partidas.value, ...lista] : lista;
    paginacao.value.hasMore = Boolean(data?.next_page_url);
  } catch (e) {
    erro.value = 'Não foi possível carregar as partidas neste momento.';
  } finally {
    loading.value = false;
    carregandoMais.value = false;
  }
};

const carregarMais = async () => {
  if (!paginacao.value.hasMore || carregandoMais.value) return;
  paginacao.value.page += 1;
  await carregarPartidas({ append: true });
};

watch(statusFiltro, async () => {
  paginacao.value.page = 1;
  await carregarPartidas();
});

onMounted(async () => {
  window.addEventListener('online', marcarOnline);
  window.addEventListener('offline', marcarOffline);
  await carregarPartidas();
});

onUnmounted(() => {
  window.removeEventListener('online', marcarOnline);
  window.removeEventListener('offline', marcarOffline);
});
</script>
