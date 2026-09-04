<template>
  <div class="min-h-screen bg-gray-50">
    <section class="bg-slate-900 text-white py-10">
      <div class="container mx-auto px-4">
        <router-link to="/memoria" class="text-yellow-400 text-sm font-bold">← Memória</router-link>
        <h1 class="text-3xl font-extrabold mt-2">Galeria histórica</h1>
        <p class="text-slate-300">Filtre por década, modalidade ou local</p>
      </div>
    </section>
    <section class="container mx-auto px-4 py-6">
      <div class="flex flex-wrap gap-3 mb-6">
        <select v-model="filters.decada" class="rounded-lg border-gray-300" @change="load">
          <option value="">Todas as décadas</option>
          <option v-for="d in decadas" :key="d" :value="d">{{ d }}s</option>
        </select>
        <select v-model="filters.tipo" class="rounded-lg border-gray-300" @change="load">
          <option value="">Todos os tipos</option>
          <option value="foto">Fotos</option>
          <option value="video">Vídeos</option>
          <option value="documento">Documentos</option>
        </select>
        <select v-model="filters.local_id" class="rounded-lg border-gray-300" @change="load">
          <option value="">Todos os locais</option>
          <option v-for="l in locais" :key="l.id" :value="l.id">{{ l.nome }}</option>
        </select>
        <input v-model="filters.q" placeholder="Buscar pessoas, título..." class="rounded-lg border-gray-300 flex-1 min-w-[12rem]" @keyup.enter="load" />
      </div>
      <div v-if="loading" class="py-16 text-center text-gray-500">Carregando acervo...</div>
      <div v-else-if="itens.length === 0" class="py-16 text-center text-gray-500 bg-white rounded-2xl">Nenhum item publicado com esses filtros.</div>
      <div v-else class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
        <a v-for="item in itens" :key="item.id" :href="item.arquivo_url" target="_blank" class="bg-white rounded-xl overflow-hidden border hover:shadow-md">
          <div class="h-40 bg-gray-100">
            <img v-if="item.tipo === 'foto'" :src="item.arquivo_url" class="w-full h-full object-cover" alt="" />
            <div v-else class="h-full flex items-center justify-center text-sm font-semibold text-gray-500 uppercase">{{ item.tipo }}</div>
          </div>
          <div class="p-3">
            <p class="font-semibold text-sm line-clamp-2">{{ item.titulo }}</p>
            <p class="text-xs text-gray-500 mt-1">{{ item.ano || 'Ano indefinido' }}</p>
          </div>
        </a>
      </div>
    </section>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import portalService from '../services/portalService';

const loading = ref(true);
const itens = ref([]);
const locais = ref([]);
const decadas = ref([]);
const filters = ref({ decada: '', tipo: '', local_id: '', q: '' });

const load = async () => {
  loading.value = true;
  try {
    const { data } = await portalService.getMemoriaGaleria(filters.value);
    itens.value = data.data || [];
    decadas.value = [...new Set(itens.value.map((i) => i.decada).filter(Boolean))].sort((a, b) => b - a);
  } finally {
    loading.value = false;
  }
};

onMounted(async () => {
  const locs = await portalService.getMemoriaLocais();
  locais.value = locs.data || [];
  await load();
});
</script>
