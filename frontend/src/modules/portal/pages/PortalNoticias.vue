<template>
  <div class="overflow-x-clip min-h-screen bg-gray-50">
    <section class="bg-blue-900 text-white py-8 sm:py-10">
      <div class="container mx-auto px-4">
        <h1 class="text-2xl sm:text-3xl font-bold">Notícias</h1>
        <p class="opacity-80 text-sm sm:text-base mt-1">Acompanhe as novidades da Secretaria</p>
      </div>
    </section>

    <section class="container mx-auto px-4 py-6 sm:py-8">
      <div v-if="loading" class="text-center py-12">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
      </div>

      <div v-else-if="error" class="bg-red-100 text-red-700 p-4 rounded-xl">
        {{ error }}
      </div>

      <div v-else-if="noticias.length === 0" class="text-center py-16 bg-white rounded-2xl border border-gray-100 text-gray-500">
        Nenhuma notícia publicada ainda.
      </div>

      <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
        <router-link
          v-for="item in noticias"
          :key="item.id"
          :to="{ name: 'PortalNoticiaDetalhe', params: { slug: item.slug || item.id } }"
          class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow min-w-0 flex flex-col"
        >
          <img
            v-if="item.imagem_capa_url"
            :src="item.imagem_capa_url"
            @error="handleCardImageError"
            class="w-full h-40 sm:h-44 object-cover bg-gray-100"
            alt="Imagem de capa"
          />
          <div class="p-4 flex-1 flex flex-col min-w-0">
            <h3 class="text-base sm:text-lg font-bold text-gray-800 mb-2 leading-snug line-clamp-3">{{ item.titulo }}</h3>
            <p class="text-xs text-gray-500 mb-3">{{ formatDate(item.publicado_em || item.created_at) }}</p>
            <p class="text-sm text-gray-600 line-clamp-3">{{ getExcerpt(item.conteudo) }}</p>
          </div>
        </router-link>
      </div>
    </section>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import portalService from '../services/portalService';

const noticias = ref([]);
const loading = ref(true);
const error = ref('');

const getExcerpt = (content) => {
  if (!content) return '';

  try {
    const data = typeof content === 'string' ? JSON.parse(content) : content;

    if (data.blocks && Array.isArray(data.blocks)) {
      const paragraph = data.blocks.find(b => b.type === 'paragraph');
      if (paragraph && paragraph.data && paragraph.data.text) {
        const div = document.createElement('div');
        div.innerHTML = paragraph.data.text;
        return (div.textContent || div.innerText || '').trim();
      }
    }
  } catch (e) {
  }

  const div = document.createElement('div');
  div.innerHTML = content || '';
  return (div.textContent || div.innerText || '').trim();
};

const formatDate = (d) => {
  try {
    return new Date(d).toLocaleDateString('pt-BR');
  } catch {
    return '';
  }
};

const handleCardImageError = (e) => {
  if (!e?.target) return;
  e.target.style.display = 'none';
};

const load = async () => {
  loading.value = true;
  error.value = '';
  try {
    const { data } = await portalService.getNoticias({ per_page: 9 });
    noticias.value = data.data || [];
  } catch (e) {
    error.value = 'Erro ao carregar notícias.';
  } finally {
    loading.value = false;
  }
};

onMounted(load);
</script>
