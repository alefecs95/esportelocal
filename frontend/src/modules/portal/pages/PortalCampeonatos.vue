<template>
  <div class="container mx-auto px-4 py-8">
    <div class="bg-white rounded-lg shadow-sm p-6 mb-8">
      <h1 class="text-3xl font-bold text-blue-900 mb-2">Competições</h1>
      <p class="text-gray-600">Acompanhe os campeonatos em andamento e finalizados.</p>
    </div>

    <div v-if="loading" class="flex justify-center py-12">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
    </div>

    <div v-else-if="campeonatos.length === 0" class="text-center py-12 bg-white rounded-lg shadow-sm">
      <p class="text-gray-500">Nenhum campeonato encontrado.</p>
    </div>

    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <router-link
        v-for="camp in campeonatos"
        :key="camp.id"
        :to="{ name: 'PortalCampeonato', params: { id: camp.id } }"
        class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow group"
      >
        <div
          class="h-40 bg-blue-900 relative bg-cover bg-center"
          :style="getBannerStyle(camp)"
        >
            <div class="absolute inset-0 bg-black/20 group-hover:bg-black/10 transition-colors"></div>
            <div class="absolute bottom-0 left-0 p-4 w-full bg-gradient-to-t from-black/80 to-transparent">
                <span class="text-xs font-bold text-white bg-blue-600 px-2 py-1 rounded uppercase tracking-wider">
                    {{ camp.modalidade?.nome || 'Esporte' }}
                </span>
            </div>
        </div>
        <div class="p-6">
            <h3 class="font-bold text-xl text-gray-800 mb-2 group-hover:text-blue-600 transition-colors">
                {{ camp.nome }}
            </h3>
            <div class="flex items-center gap-2 text-sm text-gray-500 mb-4">
                <span :class="getStatusClass(camp.status)" class="px-2 py-0.5 rounded text-xs font-bold uppercase">
                    {{ formatStatus(camp.status) }}
                </span>
                <span>•</span>
                <span>{{ camp.ano || '2026' }}</span>
            </div>
            <p class="text-sm text-gray-600 line-clamp-2 mb-4">
                {{ camp.descricao || 'Sem descrição.' }}
            </p>
            <div class="text-blue-600 font-bold text-sm flex items-center gap-1 group-hover:gap-2 transition-all">
                Ver Detalhes <span class="text-lg">→</span>
            </div>
        </div>
      </router-link>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import portalService from '../services/portalService';

const campeonatos = ref([]);
const loading = ref(true);

const loadCampeonatos = async () => {
  loading.value = true;
  try {
    const { data } = await portalService.getCampeonatos();
    // API returns { success: true, data: { current_page: 1, data: [...] } }
    // So data.data is the paginator, and data.data.data is the array.
    if (data.data && data.data.data) {
        campeonatos.value = data.data.data;
    } else if (Array.isArray(data.data)) {
        campeonatos.value = data.data;
    } else {
        campeonatos.value = [];
    }
  } catch (e) {
    console.error('Erro ao carregar campeonatos', e);
  } finally {
    loading.value = false;
  }
};

const getStatusClass = (status) => {
    const map = {
        'inscricoes_abertas': 'bg-green-100 text-green-700',
        'inscricoes_encerradas': 'bg-yellow-100 text-yellow-700',
        'em_andamento': 'bg-blue-100 text-blue-700',
        'finalizado': 'bg-gray-100 text-gray-700',
    };
    return map[status] || 'bg-gray-100 text-gray-700';
};

const formatStatus = (status) => {
    return status ? status.replace('_', ' ') : '';
};

const getBannerStyle = (camp) => {
  const banner = camp?.banner_url || camp?.banner;
  if (!banner) return {};
  const src = banner.startsWith('http://') || banner.startsWith('https://')
    ? banner
    : banner; // Fallback to banner, but banner_url should already be absolute from API
  return { backgroundImage: `url(${src})` };
};

onMounted(loadCampeonatos);
</script>
