<template>
  <div class="min-h-screen bg-slate-50">
    <MemoriaPageHero
      title="Galeria histórica"
      description="Fotos, vídeos, áudios e documentos. Toque para abrir no tamanho da tela."
    />
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
            <option value="">Fotos, vídeos, áudios e documentos</option>
            <option value="foto">Só fotos</option>
            <option value="video">Só vídeos</option>
            <option value="audio">Só áudios</option>
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
          Pessoa
          <select v-model="filters.personalidade_id" class="mt-1 w-full rounded-xl border-gray-300 min-h-11" @change="load">
            <option value="">Todas as pessoas</option>
            <option v-for="p in pessoas" :key="p.id" :value="p.id">{{ p.nome }}</option>
          </select>
        </label>
        <label class="text-sm font-semibold text-gray-700">
          Equipe
          <select v-model="filters.equipe_id" class="mt-1 w-full rounded-xl border-gray-300 min-h-11" @change="load">
            <option value="">Todas as equipes</option>
            <option v-for="e in equipes" :key="e.id" :value="e.id">{{ e.nome }}</option>
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
        <article v-for="(item, idx) in itens" :key="item.id" class="bg-white rounded-2xl overflow-hidden border hover:shadow-md group">
          <button type="button" class="block w-full h-40 bg-gray-100 text-left" @click="lightboxIndex = idx">
            <img v-if="item.tipo === 'foto'" :src="item.arquivo_url" loading="lazy" class="w-full h-full object-cover group-hover:scale-[1.02] transition-transform" :alt="item.alt_text || item.titulo" />
            <video v-else-if="item.tipo === 'video'" :src="item.arquivo_url" preload="none" muted class="w-full h-full object-cover bg-black pointer-events-none" :aria-label="item.alt_text || item.titulo"></video>
            <div v-else-if="item.tipo === 'audio'" class="h-full flex items-center justify-center text-sm font-semibold text-gray-600">Áudio</div>
            <div v-else class="h-full flex items-center justify-center text-sm font-semibold text-gray-500">
              {{ tipoAcervoLabels[item.tipo] || item.tipo }}
            </div>
          </button>
          <div class="p-3">
          <router-link
            v-if="item.slug || item.id"
            :to="{ name: 'PortalMemoriaAcervo', params: { slug: item.slug || item.id } }"
            class="font-semibold text-sm line-clamp-2 text-gray-900 hover:text-blue-700"
          >{{ item.titulo }}</router-link>
          <p v-else class="font-semibold text-sm line-clamp-2 text-gray-900">{{ item.titulo }}</p>
            <p v-if="item.precisa_identificar" class="text-[11px] font-bold uppercase text-amber-700 mt-1">
              <router-link to="/memoria/quem-e" class="hover:underline">Quem é essa pessoa?</router-link>
            </p>
            <p class="text-xs text-gray-500 mt-1">{{ item.ano || 'Ano a confirmar' }}<span v-if="item.local?.nome"> · </span>
              <router-link
                v-if="item.local?.nome"
                :to="{ name: 'PortalMemoriaLocal', params: { slug: item.local.slug || item.local.id } }"
                class="hover:text-blue-700"
              >{{ item.local.nome }}</router-link>
            </p>
          </div>
        </article>
      </div>
    </section>
    <MemoriaLightbox :items="itens" :index="lightboxIndex" @update:index="lightboxIndex = $event" @close="lightboxIndex = -1" />
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import portalService from '../services/portalService';
import { tipoAcervoLabels } from '@/modules/memoria/utils/memoriaLabels';
import MemoriaPageHero from '../components/MemoriaPageHero.vue';
import MemoriaLightbox from '../components/MemoriaLightbox.vue';

const route = useRoute();
const loading = ref(true);
const itens = ref([]);
const locais = ref([]);
const pessoas = ref([]);
const equipes = ref([]);
const decadas = ref([]);
const lightboxIndex = ref(-1);
const filters = ref({
  decada: '',
  tipo: '',
  local_id: route.query.local_id || '',
  personalidade_id: route.query.personalidade_id || '',
  equipe_id: route.query.equipe_id || '',
  q: '',
});

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
  const [locs, tl, pers, eqs] = await Promise.all([
    portalService.getMemoriaLocais(),
    portalService.getMemoriaTimeline(),
    portalService.getMemoriaPersonalidades(),
    portalService.getMemoriaEquipes().catch(() => ({ data: [] })),
  ]);
  locais.value = locs.data || [];
  decadas.value = tl.data?.decadas || [];
  pessoas.value = Array.isArray(pers.data) ? pers.data : (pers.data?.data || []);
  equipes.value = eqs.data || [];
  await load();
});
</script>
