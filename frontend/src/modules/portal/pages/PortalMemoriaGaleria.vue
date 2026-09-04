<template>
  <div class="min-h-screen bg-gray-50">
    <section class="bg-slate-900 text-white py-10 sm:py-12">
      <div class="container mx-auto px-4">
        <router-link to="/memoria" class="text-yellow-400 text-sm font-bold">← Memória</router-link>
        <h1 class="text-3xl font-extrabold mt-2">Galeria histórica</h1>
        <p class="text-slate-300 mt-1 max-w-2xl">Fotos, vídeos e documentos. Filtre pela época, pelo tipo de arquivo ou pelo lugar.</p>
      </div>
    </section>
    <section class="container mx-auto px-4 py-6">
      <form class="bg-white border rounded-2xl p-4 mb-6 grid sm:grid-cols-2 lg:grid-cols-4 gap-3" @submit.prevent="load">
        <label class="text-sm font-semibold text-gray-700">
          Década
          <select v-model="filters.decada" class="mt-1 w-full rounded-xl border-gray-300 min-h-11" @change="load">
            <option value="">Todas</option>
            <option v-for="d in decadas" :key="d" :value="d">{{ d }}s</option>
          </select>
        </label>
        <label class="text-sm font-semibold text-gray-700">
          Tipo
          <select v-model="filters.tipo" class="mt-1 w-full rounded-xl border-gray-300 min-h-11" @change="load">
            <option value="">Fotos, vídeos e documentos</option>
            <option value="foto">Só fotos</option>
            <option value="video">Só vídeos</option>
            <option value="documento">Só documentos</option>
          </select>
        </label>
        <label class="text-sm font-semibold text-gray-700">
          Local
          <select v-model="filters.local_id" class="mt-1 w-full rounded-xl border-gray-300 min-h-11" @change="load">
            <option value="">Todos os lugares</option>
            <option v-for="l in locais" :key="l.id" :value="l.id">{{ l.nome }}</option>
          </select>
        </label>
        <label class="text-sm font-semibold text-gray-700">
          Buscar
          <div class="mt-1 flex gap-2">
            <input v-model="filters.q" placeholder="Nome, time, título..." class="w-full rounded-xl border-gray-300 min-h-11" />
            <button class="bg-slate-900 text-white px-4 rounded-xl font-semibold min-h-11 shrink-0">Buscar</button>
          </div>
        </label>
      </form>

      <div v-if="loading" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
        <div v-for="n in 8" :key="n" class="bg-white rounded-2xl overflow-hidden border">
          <div class="h-40 bg-slate-200 animate-pulse"></div>
          <div class="p-3 space-y-2">
            <div class="h-3 bg-slate-200 rounded animate-pulse"></div>
            <div class="h-3 bg-slate-100 rounded w-1/2 animate-pulse"></div>
          </div>
        </div>
      </div>
      <div v-else-if="itens.length === 0" class="py-16 text-center text-gray-500 bg-white rounded-2xl border">
        <p class="font-semibold text-gray-800">{{ emptyTitle }}</p>
        <p class="mt-1">Tente limpar os filtros ou envie uma memória que esteja faltando neste lugar.</p>
        <router-link to="/memoria/enviar" class="inline-flex mt-4 bg-yellow-400 text-slate-900 px-5 py-3 rounded-full font-bold">Enviar foto</router-link>
      </div>
      <div v-else class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
        <a v-for="item in itens" :key="item.id" :href="item.arquivo_url" target="_blank" class="bg-white rounded-2xl overflow-hidden border hover:shadow-md group">
          <div class="h-40 bg-gray-100">
            <img v-if="item.tipo === 'foto'" :src="item.arquivo_url" loading="lazy" class="w-full h-full object-cover group-hover:scale-[1.02] transition-transform" :alt="item.alt_text || item.titulo" />
            <video v-else-if="item.tipo === 'video'" :src="item.arquivo_url" preload="none" muted class="w-full h-full object-cover bg-black" :aria-label="item.alt_text || item.titulo"></video>
            <div v-else class="h-full flex items-center justify-center text-sm font-semibold text-gray-500">
              {{ tipoAcervoLabels[item.tipo] || item.tipo }}
            </div>
          </div>
          <div class="p-3">
            <p class="font-semibold text-sm line-clamp-2 text-gray-900">{{ item.titulo }}</p>
            <p class="text-xs text-gray-500 mt-1">{{ item.ano || 'Ano a confirmar' }}<span v-if="item.local?.nome"> · {{ item.local.nome }}</span></p>
          </div>
        </a>
      </div>
    </section>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import portalService from '../services/portalService';
import { tipoAcervoLabels } from '@/modules/memoria/utils/memoriaLabels';

const loading = ref(true);
const itens = ref([]);
const locais = ref([]);
const decadas = ref([]);
const filters = ref({ decada: '', tipo: '', local_id: '', q: '' });

const emptyTitle = computed(() => {
  const local = locais.value.find((l) => String(l.id) === String(filters.value.local_id));
  if (local) return `Ainda não há mídias de ${local.nome}.`;
  return 'Nada encontrado com esses filtros.';
});

const load = async () => {
  loading.value = true;
  try {
    const { data } = await portalService.getMemoriaGaleria(filters.value);
    itens.value = data.data || [];
  } finally {
    loading.value = false;
  }
};

onMounted(async () => {
  const [locs, tl] = await Promise.all([
    portalService.getMemoriaLocais(),
    portalService.getMemoriaTimeline(),
  ]);
  locais.value = locs.data || [];
  decadas.value = tl.data?.decadas || [];
  await load();
});
</script>
