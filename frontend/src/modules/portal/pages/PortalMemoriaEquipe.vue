<template>
  <MemoriaEmpty
    v-if="!loading && missing"
    title="Essa equipe ainda não está no Memorial"
    description="O cadastro pode estar em curadoria. Veja outras equipes históricas da cidade."
  />
  <div v-else-if="loading" class="min-h-screen bg-slate-50">
    <div class="container mx-auto px-4 py-10 max-w-4xl space-y-3">
      <div class="h-24 w-24 bg-slate-200 rounded-2xl animate-pulse"></div>
      <div class="h-8 bg-slate-200 rounded w-1/2 animate-pulse"></div>
    </div>
  </div>
  <article v-else class="min-h-screen bg-slate-50">
    <MemoriaPageHero :title="item.nome" :description="periodo" :image="item.escudo_url" />
    <section class="container mx-auto px-4 py-8 max-w-4xl">
      <p v-if="item.apelido || item.nome_anterior" class="text-slate-500">
        <span v-if="item.apelido">Apelido: {{ item.apelido }}</span>
        <span v-if="item.nome_anterior"> · Nome anterior: {{ item.nome_anterior }}</span>
      </p>
      <p v-if="item.descricao" class="mt-4 text-lg text-slate-700 whitespace-pre-line">{{ item.descricao }}</p>
      <p v-if="item.historia" class="mt-6 text-slate-700 whitespace-pre-line">{{ item.historia }}</p>

      <div v-if="item.pessoas?.length" class="mt-10">
        <h2 class="font-bold text-xl mb-3">Quem vestiu essa camisa</h2>
        <div class="flex flex-wrap gap-2">
          <router-link
            v-for="p in item.pessoas"
            :key="p.id"
            :to="{ name: 'PortalMemoriaPersonalidade', params: { slug: p.slug || p.id } }"
            class="px-4 py-2 bg-white border rounded-full font-medium hover:border-blue-500"
          >{{ p.nome }}</router-link>
        </div>
      </div>

      <div v-if="item.marcos?.length" class="mt-10">
        <h2 class="font-bold text-xl mb-3">Na linha do tempo</h2>
        <div class="grid gap-3">
          <router-link
            v-for="m in item.marcos"
            :key="m.id"
            :to="{ name: 'PortalMemoriaMarco', params: { slug: m.slug || m.id } }"
            class="bg-white border rounded-2xl p-4 hover:border-blue-500"
          >
            <p class="text-sm text-amber-700 font-bold">{{ m.ano }}</p>
            <p class="font-semibold">{{ m.titulo }}</p>
          </router-link>
        </div>
      </div>

      <div v-if="item.acervo?.length" class="mt-10">
        <h2 class="font-bold text-xl mb-3">Fotografias desta equipe</h2>
        <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
          <router-link
            v-for="m in item.acervo"
            :key="m.id"
            :to="{ name: 'PortalMemoriaAcervo', params: { slug: m.slug || m.id } }"
            class="rounded-2xl overflow-hidden bg-white border"
          >
            <img v-if="m.tipo === 'foto'" :src="m.arquivo_url" class="w-full h-40 object-cover" :alt="m.alt_text || m.titulo" />
            <div class="p-3 text-sm font-medium">{{ m.titulo }}</div>
          </router-link>
        </div>
      </div>
    </section>
  </article>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import portalService from '../services/portalService';
import MemoriaPageHero from '../components/MemoriaPageHero.vue';
import MemoriaEmpty from '../components/MemoriaEmpty.vue';

const route = useRoute();
const item = ref({});
const loading = ref(true);
const missing = ref(false);

const periodo = computed(() => {
  const a = item.value.ano_inicio;
  const b = item.value.ano_fim;
  if (a && b) return `${a}–${b}`;
  if (a) return `Desde ${a}`;
  return item.value.modalidade?.nome || 'Equipe histórica';
});

const load = async () => {
  loading.value = true;
  missing.value = false;
  try {
    const { data } = await portalService.getMemoriaEquipe(route.params.slug);
    item.value = data;
    document.title = `${data.nome} | Memorial Esportivo`;
  } catch {
    missing.value = true;
  } finally {
    loading.value = false;
  }
};

onMounted(load);
watch(() => route.params.slug, load);
</script>
