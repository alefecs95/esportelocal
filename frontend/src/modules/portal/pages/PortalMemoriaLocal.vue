<template>
  <MemoriaEmpty
    v-if="!loading && missing"
    title="Este local ainda não está no acervo"
    description="O espaço pode estar em curadoria ou o link está desatualizado. Veja a linha do tempo e a galeria da cidade."
  />
  <div v-else-if="loading" class="min-h-screen bg-gray-50">
    <div class="h-[42vh] bg-slate-200 animate-pulse"></div>
    <div class="container mx-auto px-4 py-8 max-w-3xl space-y-3">
      <div class="h-4 bg-slate-200 rounded animate-pulse w-1/3"></div>
      <div class="h-8 bg-slate-200 rounded animate-pulse w-2/3"></div>
      <div class="h-24 bg-slate-200 rounded animate-pulse"></div>
    </div>
  </div>
  <article v-else>
    <section class="relative min-h-[16rem] h-[42vh] bg-slate-900 text-white">
      <img v-if="heroImage" :src="heroImage" class="absolute inset-0 w-full h-full object-cover opacity-60" :alt="item.nome" />
      <div class="absolute inset-0 bg-gradient-to-t from-slate-900"></div>
      <div class="container mx-auto px-4 relative z-10 h-full flex flex-col justify-end pb-8">
        <nav class="text-xs sm:text-sm text-slate-300 mb-3 flex flex-wrap gap-1">
          <router-link to="/memoria" class="hover:text-yellow-400">Memória</router-link>
          <span>/</span>
          <router-link to="/memoria/mapa" class="hover:text-yellow-400">Mapa</router-link>
          <span>/</span>
          <span class="text-white truncate max-w-[50vw]">{{ item.nome }}</span>
        </nav>
        <p class="uppercase tracking-widest text-xs text-yellow-300">
          {{ tipoLocalLabels[item.tipo] || item.tipo || 'Espaço esportivo' }}
          <span v-if="item.endereco"> · {{ item.endereco }}</span>
        </p>
        <h1 class="text-3xl sm:text-5xl font-extrabold leading-tight">{{ item.nome }}</h1>
      </div>
    </section>

    <section class="container mx-auto px-4 py-8 max-w-3xl">
      <div class="flex flex-wrap gap-2 mb-6">
        <a :href="whatsappHref" target="_blank" rel="noopener" class="px-4 py-2 rounded-full bg-green-600 text-white text-sm font-bold">WhatsApp</a>
        <a :href="facebookHref" target="_blank" rel="noopener" class="px-4 py-2 rounded-full bg-blue-700 text-white text-sm font-bold">Facebook</a>
        <router-link to="/memoria/mapa" class="px-4 py-2 rounded-full border text-sm font-bold">Ver no mapa</router-link>
        <router-link :to="{ path: '/memoria/galeria', query: { local_id: item.id } }" class="px-4 py-2 rounded-full border text-sm font-bold">Galeria deste lugar</router-link>
        <button type="button" class="px-4 py-2 rounded-full border text-sm font-bold" @click="printQr">Imprimir QR</button>
      </div>

      <p v-if="item.descricao" class="text-lg text-gray-700 leading-relaxed whitespace-pre-line">{{ item.descricao }}</p>
      <p v-else class="text-gray-500 bg-slate-50 border rounded-2xl p-4">A secretaria ainda está reunindo o texto deste espaço. Você pode enviar uma foto antiga para o acervo.</p>

      <div v-if="comparePair" class="mt-10">
        <h2 class="font-bold text-xl mb-2">Antes e depois</h2>
        <p class="text-sm text-gray-500 mb-4">Arraste o controle para comparar o espaço ao longo do tempo.</p>
        <div class="relative rounded-2xl overflow-hidden border bg-slate-900 aspect-[16/10]">
          <img :src="comparePair.depois.foto_url" class="absolute inset-0 w-full h-full object-cover" :alt="comparePair.depois.titulo || 'Depois'" />
          <img
            :src="comparePair.antes.foto_url"
            class="absolute inset-0 w-full h-full object-cover"
            :style="{ clipPath: `inset(0 ${100 - antesPct}% 0 0)` }"
            :alt="comparePair.antes.titulo || 'Antes'"
          />
          <input
            v-model.number="antesPct"
            type="range"
            min="5"
            max="95"
            class="absolute inset-x-4 bottom-4 z-10"
            :aria-label="`Comparar ${comparePair.antes.ano || 'antes'} e ${comparePair.depois.ano || 'depois'}`"
          />
        </div>
        <p class="text-xs text-gray-500 mt-2">{{ comparePair.antes.ano || comparePair.antes.titulo || 'Antes' }} ← → {{ comparePair.depois.ano || comparePair.depois.titulo || 'Hoje' }}</p>
      </div>

      <div v-if="momentos.length" class="mt-10">
        <h2 class="font-bold text-xl mb-4">Momentos deste espaço</h2>
        <div class="grid sm:grid-cols-2 gap-3">
          <article v-for="m in momentos" :key="m.id" class="bg-white border rounded-2xl overflow-hidden">
            <img v-if="m.foto_url" :src="m.foto_url" class="w-full h-40 object-cover" :alt="m.titulo || item.nome" />
            <div class="p-4">
              <p class="text-sm text-amber-700 font-bold">{{ m.ano || 'Ano a confirmar' }}</p>
              <p class="font-semibold">{{ m.titulo || 'Registro do espaço' }}</p>
              <p v-if="m.descricao" class="text-sm text-gray-600 mt-1">{{ m.descricao }}</p>
            </div>
          </article>
        </div>
      </div>

      <div class="mt-10 bg-white border rounded-2xl p-5 print:block">
        <h2 class="font-bold text-xl mb-2">QR no local</h2>
        <p class="text-sm text-gray-600 mb-4">Imprima e afixe no espaço. Quem apontar a câmera abre esta página da Memória.</p>
        <div class="flex flex-col sm:flex-row items-center gap-4">
          <img :src="qrSrc" alt="QR code desta página" class="w-40 h-40 bg-white border rounded-xl" />
          <p class="text-xs text-gray-500 break-all">{{ pageUrl }}</p>
        </div>
      </div>

      <div v-if="item.marcos?.length" class="mt-10">
        <h2 class="font-bold text-xl mb-4">Fatos neste lugar</h2>
        <div class="grid gap-3">
          <router-link
            v-for="m in item.marcos"
            :key="m.id"
            :to="{ name: 'PortalMemoriaMarco', params: { slug: m.slug || m.id } }"
            class="bg-white border rounded-2xl p-4 hover:border-blue-500"
          >
            <p class="text-sm text-amber-700 font-bold">{{ m.ano || 'Ano a confirmar' }}</p>
            <p class="font-semibold">{{ m.titulo }}</p>
          </router-link>
        </div>
      </div>

      <div v-if="item.acervo?.length" class="mt-10">
        <h2 class="font-bold text-xl mb-4">Acervo deste lugar</h2>
        <div class="grid grid-cols-2 gap-3">
          <router-link
            v-for="m in item.acervo"
            :key="m.id"
            :to="{ name: 'PortalMemoriaAcervo', params: { slug: m.slug || m.id } }"
            class="block rounded-2xl overflow-hidden bg-white border"
          >
            <img v-if="m.tipo === 'foto'" :src="m.arquivo_url" loading="lazy" class="w-full h-40 object-cover" :alt="m.alt_text || m.titulo" />
            <video v-else-if="m.tipo === 'video'" :src="m.arquivo_url" preload="none" controls class="w-full h-40 object-cover bg-black"></video>
            <audio v-else-if="m.tipo === 'audio'" :src="m.arquivo_url" controls class="w-full mt-8 px-2"></audio>
            <div class="p-3 text-sm font-medium">{{ m.titulo }}</div>
          </router-link>
        </div>
      </div>

      <router-link to="/memoria/enviar" class="mt-10 inline-flex text-sm font-bold text-blue-700">Tem foto deste lugar? Envie para o acervo</router-link>
    </section>
  </article>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import portalService from '../services/portalService';
import { tipoLocalLabels } from '@/modules/memoria/utils/memoriaLabels';
import { memoriaShareUrl } from '@/modules/memoria/utils/share';
import MemoriaEmpty from '../components/MemoriaEmpty.vue';

const route = useRoute();
const item = ref({});
const loading = ref(true);
const missing = ref(false);
const antesPct = ref(50);

const heroImage = computed(() => {
  const last = [...momentos.value].reverse().find((m) => m.foto_url);
  return last?.foto_url || item.value.foto_url || item.value.foto_depois_url || item.value.foto_antes_url;
});
const momentos = computed(() => item.value.momentos || []);
const comparePair = computed(() => {
  const withPhoto = momentos.value.filter((m) => m.foto_url);
  if (withPhoto.length >= 2) {
    const sorted = [...withPhoto].sort((a, b) => (a.ano || 0) - (b.ano || 0));
    return { antes: sorted[0], depois: sorted[sorted.length - 1] };
  }
  if (item.value.foto_antes_url && item.value.foto_depois_url) {
    return {
      antes: { foto_url: item.value.foto_antes_url, ano: item.value.ano_antes, titulo: 'Antes' },
      depois: { foto_url: item.value.foto_depois_url, ano: item.value.ano_depois, titulo: 'Depois' },
    };
  }
  return null;
});
const shareUrl = computed(() => memoriaShareUrl('locais', item.value.slug || route.params.slug));
const pageUrl = computed(() => `${window.location.origin}/memoria/locais/${item.value.slug || route.params.slug}`);
const qrSrc = computed(() => `https://api.qrserver.com/v1/create-qr-code/?size=240x240&data=${encodeURIComponent(pageUrl.value)}`);
const whatsappHref = computed(() => `https://wa.me/?text=${encodeURIComponent(`${item.value.nome || ''} ${shareUrl.value}`)}`);
const facebookHref = computed(() => `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(shareUrl.value)}`);

const printQr = () => window.print();

const load = async () => {
  loading.value = true;
  missing.value = false;
  try {
    const { data } = await portalService.getMemoriaLocal(route.params.slug);
    item.value = data;
    document.title = `${data.nome} | Memória do Esporte`;
  } catch {
    missing.value = true;
  } finally {
    loading.value = false;
  }
};

onMounted(load);
watch(() => route.params.slug, load);
</script>
