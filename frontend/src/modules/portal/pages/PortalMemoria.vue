<template>
  <div class="overflow-x-clip min-h-screen bg-slate-950 text-white">
    <section class="relative min-h-[72svh] flex items-end">
      <img
        v-if="heroImage"
        :src="heroImage"
        class="absolute inset-0 w-full h-full object-cover"
        alt=""
      />
      <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/75 to-black/25"></div>
      <div class="container mx-auto px-4 pb-12 pt-24 relative z-10">
        <p class="text-yellow-400 font-bold uppercase tracking-[0.2em] text-xs mb-3">Memória do Esporte</p>
        <h1 class="text-3xl sm:text-5xl lg:text-6xl font-extrabold max-w-4xl leading-tight">
          {{ nesteDia?.titulo || 'A história do esporte da cidade, em um só lugar.' }}
        </h1>
        <p class="mt-4 text-slate-200 max-w-2xl text-base sm:text-lg">
          {{ nesteDia?.resumo || 'Conquistas, ídolos, ginásios e fotos que a cidade não pode esquecer.' }}
        </p>
        <div class="mt-8 flex flex-wrap gap-3">
          <a href="#linha-do-tempo" class="bg-yellow-400 text-slate-900 px-5 py-3 rounded-full font-bold min-h-11 inline-flex items-center">Ver linha do tempo</a>
          <router-link to="/memoria/galeria" class="border border-white/30 px-5 py-3 rounded-full font-bold hover:bg-white/10 min-h-11 inline-flex items-center">Galeria</router-link>
          <router-link to="/memoria/enviar" class="border border-white/30 px-5 py-3 rounded-full font-bold hover:bg-white/10 min-h-11 inline-flex items-center">Envie uma foto</router-link>
        </div>
      </div>
    </section>

    <section id="linha-do-tempo" class="container mx-auto px-4 py-12 scroll-mt-20">
      <div class="mb-6">
        <h2 class="text-2xl sm:text-3xl font-bold">Linha do tempo</h2>
        <p class="text-slate-400 mt-1">Escolha uma década ou veja tudo o que marcou o esporte municipal.</p>
      </div>

      <div class="flex gap-2 overflow-x-auto pb-3 mb-8 -mx-4 px-4">
        <button
          v-for="d in ['todas', ...decadas]"
          :key="d"
          type="button"
          class="shrink-0 px-4 py-2 rounded-full text-sm font-bold min-h-11"
          :class="filtroDecada === d ? 'bg-yellow-400 text-slate-900' : 'bg-white/10 text-white'"
          @click="filtroDecada = d"
        >
          {{ d === 'todas' ? 'Todas as épocas' : d + 's' }}
        </button>
      </div>

      <div v-if="loading" class="text-center py-16 text-slate-400">Carregando história...</div>
      <div v-else-if="marcosFiltrados.length === 0" class="text-center py-16 text-slate-400 bg-white/5 rounded-3xl px-4">
        <p class="font-semibold text-white">Ainda não há fatos nesta época.</p>
        <p class="mt-2">Você pode enviar uma foto antiga para a prefeitura incluir no acervo.</p>
        <router-link to="/memoria/enviar" class="inline-flex mt-4 bg-yellow-400 text-slate-900 px-5 py-3 rounded-full font-bold">Enviar memória</router-link>
      </div>
      <div v-else class="space-y-4">
        <router-link
          v-for="item in marcosFiltrados"
          :key="item.id"
          :to="{ name: 'PortalMemoriaMarco', params: { slug: item.slug || item.id } }"
          class="group grid grid-cols-[auto_1fr] sm:grid-cols-[6rem_1fr_8rem] gap-4 items-center bg-white/5 hover:bg-white/10 border border-white/10 rounded-2xl p-4"
        >
          <p class="text-yellow-400 font-black text-lg sm:text-xl tabular-nums">{{ item.ano || '—' }}</p>
          <div class="min-w-0">
            <p class="text-[11px] uppercase tracking-wide text-slate-400">{{ tipoMarcoLabels[item.tipo] || 'Fato' }}</p>
            <h3 class="text-lg sm:text-xl font-bold group-hover:text-yellow-300 leading-snug">{{ item.titulo }}</h3>
            <p class="text-slate-400 line-clamp-2 mt-1 text-sm">{{ item.resumo }}</p>
          </div>
          <img v-if="item.capa_url" :src="item.capa_url" class="hidden sm:block h-20 w-full object-cover rounded-xl" alt="" />
        </router-link>
      </div>
    </section>

    <section class="bg-white text-slate-900 py-12">
      <div class="container mx-auto px-4">
        <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-3 mb-8">
          <div>
            <h2 class="text-2xl sm:text-3xl font-bold">Hall da cidade</h2>
            <p class="text-slate-500 mt-1">Pessoas que fizeram história no esporte local.</p>
          </div>
          <router-link to="/memoria/enviar" class="text-sm font-bold text-blue-700">Tem uma foto de alguém da lista? Envie</router-link>
        </div>
        <div v-if="personalidades.length === 0" class="text-slate-500 bg-slate-50 rounded-2xl p-8 text-center">
          O hall ainda está sendo montado pela secretaria.
        </div>
        <div v-else class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <router-link
            v-for="p in personalidades"
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
import { papelLabels, tipoMarcoLabels } from '@/modules/memoria/utils/memoriaLabels';

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

const iniciais = (nome) => {
  const parts = String(nome || '').trim().split(/\s+/).filter(Boolean);
  if (!parts.length) return '?';
  if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase();
  return (parts[0][0] + parts[1][0]).toUpperCase();
};

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
