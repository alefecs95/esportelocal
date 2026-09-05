<template>
  <div class="min-h-screen bg-slate-50">
    <MemoriaPageHero title="Equipes históricas" description="Times, escolinhas e associações que marcaram o esporte do município." />
    <section class="container mx-auto px-4 py-8">
      <div v-if="loading" class="grid md:grid-cols-3 gap-4">
        <div v-for="n in 6" :key="n" class="h-40 bg-slate-200 rounded-2xl animate-pulse"></div>
      </div>
      <div v-else-if="equipes.length === 0" class="py-16 text-center text-gray-500 bg-white rounded-2xl border">
        As equipes históricas ainda estão sendo reunidas pela secretaria.
      </div>
      <div v-else class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <router-link
          v-for="e in equipes"
          :key="e.id"
          :to="{ name: 'PortalMemoriaEquipe', params: { slug: e.slug || e.id } }"
          class="bg-white border rounded-2xl p-5 hover:border-blue-500 flex gap-4 items-center"
        >
          <img v-if="e.escudo_url" :src="e.escudo_url" class="h-16 w-16 object-contain" :alt="e.nome" />
          <div>
            <h2 class="font-bold">{{ e.nome }}</h2>
            <p class="text-sm text-slate-500">{{ e.modalidade?.nome }} <span v-if="e.ano_inicio">· {{ e.ano_inicio }}</span></p>
          </div>
        </router-link>
      </div>
    </section>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import portalService from '../services/portalService';
import MemoriaPageHero from '../components/MemoriaPageHero.vue';

const loading = ref(true);
const equipes = ref([]);

onMounted(async () => {
  try {
    const { data } = await portalService.getMemoriaEquipes();
    equipes.value = data || [];
  } finally {
    loading.value = false;
  }
});
</script>
