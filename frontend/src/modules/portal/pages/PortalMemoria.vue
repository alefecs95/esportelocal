<template>
  <div class="overflow-x-clip min-h-screen bg-slate-950 text-white">
    <section class="relative min-h-[70svh] flex items-end">
      <img
        v-if="heroImage"
        :src="heroImage"
        class="absolute inset-0 w-full h-full object-cover"
        alt=""
      />
      <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/70 to-black/30"></div>
      <div class="container mx-auto px-4 pb-12 pt-24 relative z-10">
        <p class="text-yellow-400 font-bold uppercase tracking-[0.2em] text-xs mb-3">Memória do Esporte</p>
        <h1 class="text-3xl sm:text-5xl lg:text-6xl font-extrabold max-w-4xl leading-tight">
          {{ nesteDia?.titulo || 'A história do esporte da cidade, em um só lugar.' }}
        </h1>
        <p class="mt-4 text-slate-200 max-w-2xl text-base sm:text-lg">
          {{ nesteDia?.resumo || 'Conquistas, ídolos, ginásios e fotos que a cidade não pode esquecer.' }}
        </p>
        <div class="mt-8 flex flex-wrap gap-3">
          <router-link to="/memoria/galeria" class="bg-yellow-400 text-slate-900 px-5 py-3 rounded-full font-bold">Ver galeria</router-link>
          <router-link to="/memoria/enviar" class="border border-white/30 px-5 py-3 rounded-full font-bold hover:bg-white/10">Envie sua foto</router-link>
        </div>
      </div>
    </section>

    <section class="container mx-auto px-4 py-12">
      <div class="flex items-end justify-between gap-4 mb-8">
        <div>
          <h2 class="text-2xl sm:text-3xl font-bold">Linha do tempo</h2>
          <p class="text-slate-400">Décadas que marcaram o esporte municipal</p>
        </div>
      </div>

      <div class="flex gap-2 overflow-x-auto pb-3 mb-8">
        <button
          v-for="d in ['todas', ...decadas]"
          :key="d"
          type="button"
          class="shrink-0 px-4 py-2 rounded-full text-sm font-bold"
          :class="filtroDecada === d ? 'bg-yellow-400 text-slate-900' : 'bg-white/10 text-white'"
          @click="filtroDecada = d"
        >
          {{ d === 'todas' ? 'Todas' : d + 's' }}
        </button>
      </div>

      <div v-if="loading" class="text-center py-16 text-slate-400">Carregando história...</div>
      <div v-else-if="marcosFiltrados.length === 0" class="text-center py-16 text-slate-400 bg-white/5 rounded-2xl">
        Ainda não há marcos publicados nesta linha do tempo.
      </div>
      <div v-else class="relative border-l border-white/15 ml-3 space-y-8">
        <router-link
          v-for="item in marcosFiltrados"
          :key="item.id"
          :to="{ name: 'PortalMemoriaMarco', params: { slug: item.slug || item.id } }"
          class="relative block ml-6 group"
        >
          <span class="absolute -left-1.5 mt-2 w-3 h-3 rounded-full bg-yellow-400"></span>
          <p class="text-yellow-400 font-black text-sm">{{ item.ano || 'Sem ano' }}</p>
          <h3 class="text-xl font-bold group-hover:text-yellow-300">{{ item.titulo }}</h3>
          <p class="text-slate-400 line-clamp-2 mt-1">{{ item.resumo }}</p>
        </router-link>
      </div>
    </section>

    <section class="bg-white text-slate-900 py-12">
      <div class="container mx-auto px-4">
        <div class="flex items-end justify-between mb-8">
          <h2 class="text-2xl sm:text-3xl font-bold">Hall da cidade</h2>
          <span class="text-sm text-slate-500">Atletas, técnicos e dirigentes</span>
        </div>
        <div v-if="personalidades.length === 0" class="text-slate-500">Nenhuma personalidade em destaque ainda.</div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <router-link
            v-for="p in personalidades"
            :key="p.id"
            :to="{ name: 'PortalMemoriaPersonalidade', params: { slug: p.slug || p.id } }"
            class="rounded-2xl overflow-hidden bg-slate-100 border border-slate-200 hover:shadow-lg"
          >
            <div class="h-40 bg-slate-200">
              <img v-if="p.foto_url" :src="p.foto_url" class="w-full h-full object-cover" alt="" />
            </div>
            <div class="p-3">
              <p class="font-bold leading-tight">{{ p.nome }}</p>
              <p class="text-xs uppercase text-blue-700 mt-1">{{ p.papel }}</p>
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

const loading = ref(true);
const nesteDia = ref(null);
const marcos = ref([]);
const decadas = ref([]);
const personalidades = ref([]);
const filtroDecada = ref('todas');

const heroImage = computed(() => nesteDia.value?.capa_url || marcos.value[0]?.capa_url || null);
const marcosFiltrados = computed(() => {
  if (filtroDecada.value === 'todas') return marcos.value;
  return marcos.value.filter((m) => String(m.decada) === String(filtroDecada.value));
});

onMounted(async () => {
  try {
    const [ov, tl] = await Promise.all([
      portalService.getMemoria(),
      portalService.getMemoriaTimeline(),
    ]);
    nesteDia.value = ov.data?.neste_dia;
    personalidades.value = ov.data?.personalidades || [];
    marcos.value = tl.data?.marcos || [];
    decadas.value = tl.data?.decadas || [];
  } finally {
    loading.value = false;
  }
});
</script>
