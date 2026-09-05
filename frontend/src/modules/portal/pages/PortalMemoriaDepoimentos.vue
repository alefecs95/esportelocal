<template>
  <div class="min-h-screen bg-slate-50">
    <MemoriaPageHero
      title="História oral"
      description="Depoimentos e áudios do acervo. Ouça quem viveu os fatos da cidade."
    />
    <section class="container mx-auto px-4 py-6 max-w-3xl">
      <div v-if="loading" class="space-y-3">
        <div v-for="n in 3" :key="n" class="h-28 bg-slate-200 rounded-2xl animate-pulse"></div>
      </div>
      <div v-else-if="itens.length === 0" class="py-16 text-center text-gray-500 bg-white rounded-2xl border">
        <p class="font-semibold text-gray-800">Ainda não há depoimentos publicados.</p>
        <router-link to="/memoria/enviar" class="inline-flex mt-4 bg-yellow-400 text-slate-900 px-5 py-3 rounded-full font-bold">Enviar um áudio</router-link>
      </div>
      <article v-else v-for="item in itens" :key="item.id" class="bg-white border rounded-2xl p-5 mb-4">
        <p class="text-xs font-bold uppercase text-amber-700">{{ item.ano || 'Ano a confirmar' }}<span v-if="item.personalidade?.nome"> · {{ item.personalidade.nome }}</span></p>
        <h2 class="font-bold text-lg mt-1">{{ item.titulo }}</h2>
        <p v-if="item.descricao" class="text-sm text-gray-600 mt-2 whitespace-pre-line">{{ item.descricao }}</p>
        <audio :src="item.arquivo_url" controls class="w-full mt-4"></audio>
        <router-link :to="{ name: 'PortalMemoriaAcervo', params: { slug: item.slug || item.id } }" class="inline-block mt-3 text-sm font-bold text-blue-700">
          Ver ficha completa
        </router-link>
      </article>
    </section>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import portalService from '../services/portalService';
import MemoriaPageHero from '../components/MemoriaPageHero.vue';

const loading = ref(true);
const itens = ref([]);

onMounted(async () => {
  try {
    const { data } = await portalService.getMemoriaDepoimentos();
    itens.value = data || [];
  } finally {
    loading.value = false;
  }
});
</script>
