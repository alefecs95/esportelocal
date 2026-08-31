<template>
  <div class="p-4 max-w-7xl mx-auto">
    <div class="flex justify-between items-center mb-8">
      <h1 class="text-3xl font-bold text-gray-800">Equipes</h1>
      <div class="w-full md:w-1/3">
        <input 
          v-model="search" 
          @input="handleSearch" 
          type="text" 
          placeholder="Buscar equipe ou cidade..." 
          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none transition-shadow"
        />
      </div>
    </div>

    <div v-if="loading" class="flex justify-center py-20">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
    </div>
    
    <div v-else-if="equipes.length === 0" class="text-center py-20 bg-gray-50 rounded-lg">
      <p class="text-gray-500 text-lg">Nenhuma equipe encontrada.</p>
    </div>

    <div v-else class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
      <router-link 
        v-for="equipe in equipes" 
        :key="equipe.id"
        :to="{ name: 'PortalEquipeDetalhe', params: { id: equipe.id } }"
        class="group bg-white rounded-xl shadow-sm hover:shadow-lg transition-all duration-300 p-6 flex flex-col items-center text-center border border-gray-100 relative overflow-hidden"
      >
        <div class="absolute top-0 left-0 w-full h-1 bg-blue-600 transform scale-x-0 group-hover:scale-x-100 transition-transform duration-300"></div>
        
        <div class="w-24 h-24 mb-4 rounded-full bg-gray-50 flex items-center justify-center p-2 group-hover:bg-blue-50 transition-colors">
          <img 
            :src="equipe.logo_url || equipe.entidade?.logo_url || '/placeholder-shield.png'" 
            class="w-full h-full object-contain" 
            alt="Escudo"
          />
        </div>
        
        <h3 class="font-bold text-gray-800 text-lg mb-1 group-hover:text-blue-700 transition-colors line-clamp-1">{{ equipe.nome }}</h3>
        
        <div class="flex items-center gap-1 text-sm text-gray-500 mb-3">
          <MapPin :size="14" />
          <span>{{ equipe.entidade?.cidade || 'Local não informado' }}</span>
        </div>
        
        <div class="mt-auto pt-4 w-full border-t border-gray-100 flex justify-between items-center text-xs text-gray-500">
          <span class="bg-gray-100 px-2 py-1 rounded font-medium text-gray-700">
            {{ equipe.campeonato?.modalidade?.nome || 'Esporte' }}
          </span>
          <span>{{ equipe.campeonato?.ano || new Date().getFullYear() }}</span>
        </div>
      </router-link>
    </div>
    
    <!-- Paginação Simples -->
    <div v-if="pagination.last_page > 1" class="mt-8 flex justify-center gap-2">
      <button 
        @click="changePage(pagination.current_page - 1)"
        :disabled="pagination.current_page === 1"
        class="px-4 py-2 border rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
      >
        Anterior
      </button>
      <span class="px-4 py-2 text-gray-600">
        Página {{ pagination.current_page }} de {{ pagination.last_page }}
      </span>
      <button 
        @click="changePage(pagination.current_page + 1)"
        :disabled="pagination.current_page === pagination.last_page"
        class="px-4 py-2 border rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
      >
        Próxima
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import portalService from '@/modules/portal/services/portalService';
import { MapPin } from 'lucide-vue-next';
import { debounce } from 'lodash';

const equipes = ref([]);
const loading = ref(true);
const search = ref('');
const pagination = ref({ current_page: 1, last_page: 1 });

const loadEquipes = async (page = 1) => {
  loading.value = true;
  try {
    const { data } = await portalService.getEquipes({ 
      page, 
      search: search.value 
    });
    equipes.value = data.data;
    pagination.value = {
      current_page: data.current_page,
      last_page: data.last_page,
      total: data.total
    };
  } catch (e) {
    console.error('Erro ao carregar equipes', e);
  } finally {
    loading.value = false;
  }
};

const handleSearch = debounce(() => {
  loadEquipes(1);
}, 500);

const changePage = (page) => {
  if (page >= 1 && page <= pagination.value.last_page) {
    loadEquipes(page);
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }
};

onMounted(() => loadEquipes());
</script>
