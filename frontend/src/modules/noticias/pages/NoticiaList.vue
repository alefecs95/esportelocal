<template>
  <div class="p-6">
    <div class="mb-6 flex justify-between items-center">
      <div>
        <h1 class="text-2xl font-bold text-gray-800">Notícias</h1>
        <p class="text-gray-500 mt-1">Gerencie as notícias do portal.</p>
      </div>
      <router-link
        to="/admin/noticias/nova"
        class="bg-blue-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-blue-700 transition-colors shadow-sm"
      >
        Nova Notícia
      </router-link>
    </div>

    <div v-if="loading" class="text-center py-12">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
    </div>

    <div v-else-if="error" class="bg-red-100 text-red-700 p-4 rounded mb-4">
      {{ error }}
    </div>

    <div v-else class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Título</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Destaque</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Publicado</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data Publicação</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="item in noticias" :key="item.id" class="hover:bg-gray-50 transition-colors">
            <td class="px-6 py-4 text-sm text-gray-900 font-medium">
              <div class="flex flex-col">
                <span>{{ item.titulo }}</span>
                <span v-if="item.categoria" class="text-xs text-blue-500 font-bold uppercase mt-1">{{ item.categoria.nome }}</span>
              </div>
            </td>
            <td class="px-6 py-4 text-sm">
              <button 
                @click="toggleDestaque(item)"
                class="relative inline-flex flex-shrink-0 h-6 w-11 border-2 border-transparent rounded-full cursor-pointer transition-colors ease-in-out duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500"
                :class="item.destaque ? 'bg-yellow-400' : 'bg-gray-200'"
              >
                <span class="sr-only">Toggle Destaque</span>
                <span
                  aria-hidden="true"
                  class="pointer-events-none inline-block h-5 w-5 rounded-full bg-white shadow transform ring-0 transition ease-in-out duration-200"
                  :class="item.destaque ? 'translate-x-5' : 'translate-x-0'"
                ></span>
              </button>
            </td>
            <td class="px-6 py-4 text-sm">
              <span 
                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                :class="item.publicado ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'"
              >
                {{ item.publicado ? 'Publicado' : 'Rascunho' }}
              </span>
            </td>
            <td class="px-6 py-4 text-sm text-gray-600">
              {{ item.publicado_em ? new Date(item.publicado_em).toLocaleString() : '-' }}
            </td>
            <td class="px-6 py-4 text-sm text-right space-x-3">
              <a :href="`/portal/noticias/${item.slug || item.id}`" target="_blank" class="text-gray-400 hover:text-gray-600" title="Ver no Portal">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                </svg>
              </a>
              <router-link :to="`/admin/noticias/${item.id}/editar`" class="text-blue-600 hover:text-blue-900 font-medium">Editar</router-link>
              <button @click="deleteItem(item.id)" class="text-red-600 hover:text-red-900 font-medium">Excluir</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import noticiaService from '../services/noticiaService';

const noticias = ref([]);
const loading = ref(true);
const error = ref('');

const loadData = async () => {
  loading.value = true;
  error.value = '';
  try {
    const { data } = await noticiaService.getAll({ per_page: 20 });
    noticias.value = data.data || [];
  } catch (e) {
    error.value = 'Erro ao carregar notícias.';
    console.error(e);
  } finally {
    loading.value = false;
  }
};

const toggleDestaque = async (item) => {
  const newState = !item.destaque;
  // Otimistic update
  item.destaque = newState;
  
  try {
    await noticiaService.toggleDestaque(item.id);
  } catch (e) {
    // Revert on error
    item.destaque = !newState;
    alert('Erro ao atualizar destaque. Tente novamente.');
    console.error(e);
  }
};

const deleteItem = async (id) => {
  if (!confirm('Tem certeza que deseja excluir esta notícia?')) return;
  try {
    await noticiaService.delete(id);
    noticias.value = noticias.value.filter(n => n.id !== id);
  } catch (e) {
    alert('Erro ao excluir notícia.');
    console.error(e);
  }
};

onMounted(loadData);
</script>
