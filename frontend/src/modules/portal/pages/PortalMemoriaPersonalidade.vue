<template>
  <div class="min-h-screen bg-gray-50">
    <div v-if="loading" class="py-20 text-center text-gray-500">Carregando...</div>
    <div v-else-if="error" class="container mx-auto px-4 py-12 text-red-600">{{ error }}</div>
    <article v-else class="container mx-auto px-4 py-10 max-w-4xl">
      <router-link to="/memoria" class="text-blue-700 text-sm font-bold">← Memória</router-link>
      <div class="mt-6 grid md:grid-cols-[16rem_1fr] gap-8">
        <div>
          <div class="rounded-2xl overflow-hidden bg-slate-200 h-72">
            <img v-if="item.foto_url" :src="item.foto_url" class="w-full h-full object-cover" alt="" />
          </div>
        </div>
        <div>
          <p class="uppercase text-xs font-bold text-blue-700">{{ item.papel }} {{ item.modalidade?.nome ? '· ' + item.modalidade.nome : '' }}</p>
          <h1 class="text-4xl font-extrabold mt-1">{{ item.nome }}</h1>
          <p class="mt-4 text-gray-700 leading-relaxed whitespace-pre-line">{{ item.mini_bio }}</p>
        </div>
      </div>
      <div v-if="item.marcos?.length" class="mt-12">
        <h2 class="text-xl font-bold mb-4">Marcos na história</h2>
        <div class="grid gap-3">
          <router-link
            v-for="m in item.marcos"
            :key="m.id"
            :to="{ name: 'PortalMemoriaMarco', params: { slug: m.slug || m.id } }"
            class="bg-white border rounded-xl p-4 hover:border-blue-500"
          >
            <p class="text-sm text-yellow-700 font-bold">{{ m.ano }}</p>
            <p class="font-semibold">{{ m.titulo }}</p>
          </router-link>
        </div>
      </div>
      <div v-if="item.acervo?.length" class="mt-12 grid grid-cols-2 md:grid-cols-3 gap-3">
        <a v-for="m in item.acervo" :key="m.id" :href="m.arquivo_url" target="_blank" class="rounded-xl overflow-hidden bg-gray-200">
          <img v-if="m.tipo === 'foto'" :src="m.arquivo_url" class="w-full h-40 object-cover" alt="" />
          <div v-else class="p-3 text-sm">{{ m.titulo }}</div>
        </a>
      </div>
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
    const { data } = await portalService.getMemoriaPersonalidade(route.params.slug);
    item.value = data;
  } catch {
    error.value = 'Personalidade não encontrada.';
  } finally {
    loading.value = false;
  }
});
</script>
