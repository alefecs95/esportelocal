<template>
  <div class="min-h-screen bg-gray-50">
    <div v-if="loading" class="py-20 text-center text-gray-500">Carregando...</div>
    <div v-else-if="error" class="container mx-auto px-4 py-12 text-red-600">{{ error }}</div>
    <article v-else>
      <section class="relative h-[40vh] min-h-[16rem] bg-slate-900 text-white">
        <img v-if="item.capa_url" :src="item.capa_url" class="absolute inset-0 w-full h-full object-cover opacity-60" alt="" />
        <div class="absolute inset-0 bg-gradient-to-t from-slate-900"></div>
        <div class="container mx-auto px-4 relative z-10 h-full flex flex-col justify-end pb-8">
          <router-link to="/memoria" class="text-yellow-400 text-sm font-bold mb-3">← Linha do tempo</router-link>
          <p class="uppercase tracking-widest text-xs text-yellow-300">{{ item.ano }} · {{ item.tipo }}</p>
          <h1 class="text-3xl sm:text-5xl font-extrabold">{{ item.titulo }}</h1>
        </div>
      </section>
      <section class="container mx-auto px-4 py-8 max-w-3xl">
        <p class="text-lg text-gray-700 leading-relaxed whitespace-pre-line">{{ item.conteudo || item.resumo }}</p>
        <div v-if="item.personalidades?.length" class="mt-10">
          <h2 class="font-bold text-xl mb-4">Quem fez parte</h2>
          <div class="flex flex-wrap gap-3">
            <router-link
              v-for="p in item.personalidades"
              :key="p.id"
              :to="{ name: 'PortalMemoriaPersonalidade', params: { slug: p.slug || p.id } }"
              class="px-4 py-2 bg-white border rounded-full font-medium hover:border-blue-500"
            >
              {{ p.nome }}
            </router-link>
          </div>
        </div>
        <div v-if="item.acervo?.length" class="mt-10 grid grid-cols-2 gap-3">
          <a v-for="m in item.acervo" :key="m.id" :href="m.arquivo_url" target="_blank" class="block rounded-xl overflow-hidden bg-gray-200">
            <img v-if="m.tipo === 'foto'" :src="m.arquivo_url" class="w-full h-40 object-cover" alt="" />
            <div v-else class="p-4 text-sm font-medium">{{ m.titulo }}</div>
          </a>
        </div>
      </section>
    </article>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import portalService from '../services/portalService';

const route = useRoute();
const item = ref({});
const loading = ref(true);
const error = ref('');

onMounted(async () => {
  try {
    const { data } = await portalService.getMemoriaMarco(route.params.slug);
    item.value = data;
  } catch {
    error.value = 'Marco não encontrado.';
  } finally {
    loading.value = false;
  }
});
</script>
