<template>
  <div class="overflow-x-clip p-4 max-w-7xl mx-auto">
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 mb-6 sm:mb-8">
      <h1 class="text-2xl sm:text-3xl font-bold text-gray-800">Atletas</h1>
      <div class="w-full sm:max-w-sm">
        <input 
          v-model="search" 
          @input="handleSearch" 
          type="search" 
          placeholder="Buscar atleta..." 
          class="w-full min-h-11 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none transition-shadow"
        />
      </div>
    </div>

    <div v-if="loading" class="flex justify-center py-20">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
    </div>
    
    <div v-else-if="atletas.length === 0" class="text-center py-20 bg-gray-50 rounded-lg">
      <p class="text-gray-500 text-lg">Nenhum atleta encontrado.</p>
    </div>

    <div v-else class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
      <router-link 
        v-for="atleta in atletas" 
        :key="atleta.id"
        :to="{ name: 'PortalAtletaDetalhe', params: { id: atleta.id } }"
        class="group bg-white rounded-xl shadow-sm hover:shadow-lg transition-all duration-300 p-6 flex flex-col items-center text-center border border-gray-100 relative overflow-hidden"
      >
        <div class="absolute top-0 left-0 w-full h-1 bg-green-500 transform scale-x-0 group-hover:scale-x-100 transition-transform duration-300"></div>
        
        <div class="w-24 h-24 mb-4 rounded-full bg-gray-50 flex items-center justify-center overflow-hidden border-2 border-transparent group-hover:border-green-500 transition-colors">
          <img 
            :src="atleta.foto_url || '/placeholder-user.png'" 
            class="w-full h-full object-cover" 
            alt="Foto"
          />
        </div>
        
        <h3 class="font-bold text-gray-800 text-lg mb-1 group-hover:text-green-600 transition-colors line-clamp-2 leading-snug w-full">{{ atleta.nome }}</h3>
        
        <div class="flex items-center gap-1 text-sm text-gray-500 mb-3">
            <!-- Idade se disponível -->
             <span v-if="atleta.data_nascimento">{{ calculateAge(atleta.data_nascimento) }} anos</span>
             <span v-else>Idade não informada</span>
        </div>
      </router-link>
    </div>
    
    <!-- Paginação -->
    <div v-if="pagination.last_page > 1" class="mt-8 flex flex-wrap justify-center items-center gap-2">
      <button 
        @click="changePage(pagination.current_page - 1)"
        :disabled="pagination.current_page === 1"
        class="min-h-11 px-4 py-2 border rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
      >
        Anterior
      </button>
      <span class="px-3 py-2 text-sm text-gray-600">
        {{ pagination.current_page }} / {{ pagination.last_page }}
      </span>
      <button 
        @click="changePage(pagination.current_page + 1)"
        :disabled="pagination.current_page === pagination.last_page"
        class="min-h-11 px-4 py-2 border rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
      >
        Próxima
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import portalService from '@/modules/portal/services/portalService';
import { debounce } from 'lodash';

const atletas = ref([]);
const loading = ref(true);
const search = ref('');
const pagination = ref({ current_page: 1, last_page: 1 });

const loadAtletas = async (page = 1) => {
  loading.value = true;
  try {
    const { data } = await portalService.getAtletas({ 
      page, 
      search: search.value 
    });
    atletas.value = data.data;
    pagination.value = {
      current_page: data.current_page,
      last_page: data.last_page,
      total: data.total
    };
  } catch (e) {
    console.error('Erro ao carregar atletas', e);
  } finally {
    loading.value = false;
  }
};

const handleSearch = debounce(() => {
  loadAtletas(1);
}, 500);

const changePage = (page) => {
  if (page >= 1 && page <= pagination.value.last_page) {
    loadAtletas(page);
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }
};

const calculateAge = (dateString) => {
  if (!dateString) return '';
  const today = new Date();
  const birthDate = new Date(dateString);
  let age = today.getFullYear() - birthDate.getFullYear();
  const m = today.getMonth() - birthDate.getMonth();
  if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
    age--;
  }
  return age;
};

onMounted(() => loadAtletas());
</script>
