<template>
  <div class="min-h-screen bg-slate-950 text-white">
    <MemoriaPageHero
      title="Hall da cidade"
      description="Pessoas que fizeram história no esporte local. Toque para ler a biografia e votar."
      :image="heroImage"
    />
    <section class="bg-white text-slate-900 py-8 sm:py-12">
      <div class="container mx-auto px-4">
        <div v-if="loading" class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <div v-for="n in 8" :key="n" class="h-64 bg-slate-100 rounded-2xl animate-pulse"></div>
        </div>
        <div v-else-if="pessoas.length === 0" class="py-16 text-center text-gray-500 bg-slate-50 rounded-2xl">
          O hall ainda está sendo montado pela secretaria.
        </div>
        <div v-else class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <router-link
            v-for="p in pessoas"
            :key="p.id"
            :to="{ name: 'PortalMemoriaPersonalidade', params: { slug: p.slug || p.id } }"
            class="rounded-2xl overflow-hidden bg-slate-50 border border-slate-200 hover:shadow-lg hover:-translate-y-0.5 transition-all"
          >
            <div class="h-44 bg-slate-200">
              <img v-if="p.foto_url" :src="p.foto_url" class="w-full h-full object-cover object-top" :alt="p.nome" />
              <div v-else class="w-full h-full flex items-center justify-center text-3xl font-black text-slate-400">
                {{ iniciais(p.nome) }}
              </div>
            </div>
            <div class="p-3">
              <p class="font-bold leading-tight">{{ p.nome }}</p>
              <p class="text-xs uppercase text-blue-700 mt-1 font-semibold">{{ papelLabels[p.papel] || p.papel }}</p>
              <p v-if="p.votos_hall" class="text-xs text-slate-500 mt-1">{{ p.votos_hall }} voto{{ p.votos_hall === 1 ? '' : 's' }} no Hall</p>
            </div>
          </router-link>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import portalService from '../services/portalService';
import { papelLabels } from '@/modules/memoria/utils/memoriaLabels';
import MemoriaPageHero from '../components/MemoriaPageHero.vue';

const loading = ref(true);
const pessoas = ref([]);

const heroImage = computed(() => pessoas.value.find((p) => p.foto_url)?.foto_url || '');

const iniciais = (nome) => {
  const parts = String(nome || '').trim().split(/\s+/).filter(Boolean);
  if (!parts.length) return '?';
  if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase();
  return (parts[0][0] + parts[1][0]).toUpperCase();
};

onMounted(async () => {
  try {
    const { data } = await portalService.getMemoriaPersonalidades();
    pessoas.value = Array.isArray(data) ? data : (data?.data || []);
  } finally {
    loading.value = false;
  }
});
</script>
