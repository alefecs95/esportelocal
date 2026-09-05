<template>
  <MemoriaEmpty v-if="!loading && missing" />
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
      <img v-if="item.capa_url" :src="item.capa_url" class="absolute inset-0 w-full h-full object-cover opacity-60" :alt="item.titulo" />
      <div class="absolute inset-0 bg-gradient-to-t from-slate-900"></div>
      <div class="container mx-auto px-4 relative z-10 h-full flex flex-col justify-end pb-8">
        <nav class="text-xs sm:text-sm text-slate-300 mb-3 flex flex-wrap gap-1">
          <router-link to="/memoria" class="hover:text-yellow-400">Memória</router-link>
          <span>/</span>
          <router-link to="/memoria" class="hover:text-yellow-400">Linha do tempo</router-link>
          <span>/</span>
          <span class="text-white truncate max-w-[50vw]">{{ item.titulo }}</span>
        </nav>
        <p class="uppercase tracking-widest text-xs text-yellow-300">
          {{ item.ano || 'Ano a confirmar' }} · {{ tipoMarcoLabels[item.tipo] || item.tipo }}
          <span v-if="item.modalidade?.nome"> · {{ item.modalidade.nome }}</span>
        </p>
        <h1 class="text-3xl sm:text-5xl font-extrabold leading-tight">{{ item.titulo }}</h1>
        <p v-if="item.local?.nome" class="text-slate-300 mt-2 text-sm">
          <router-link :to="{ name: 'PortalMemoriaLocal', params: { slug: item.local.slug || item.local.id } }" class="hover:text-yellow-400">{{ item.local.nome }}</router-link>
        </p>
      </div>
    </section>
    <section class="container mx-auto px-4 py-8 max-w-3xl">
      <div class="flex flex-wrap gap-2 mb-6">
        <a :href="whatsappHref" target="_blank" rel="noopener" class="px-4 py-2 rounded-full bg-green-600 text-white text-sm font-bold">Compartilhar no WhatsApp</a>
        <a :href="facebookHref" target="_blank" rel="noopener" class="px-4 py-2 rounded-full bg-blue-700 text-white text-sm font-bold">Facebook</a>
        <router-link to="/memoria/galeria" class="px-4 py-2 rounded-full border text-sm font-bold">Galeria</router-link>
        <button type="button" class="px-4 py-2 rounded-full border text-sm font-bold" @click="printQr">Imprimir QR</button>
      </div>
      <MemoriaQrCard
        class="mb-8"
        title="QR no espaço"
        hint="Imprima e afixe no local do fato. Quem apontar a câmera abre esta página."
        :path="`/memoria/marcos/${item.slug || route.params.slug}`"
        alt="QR code deste fato"
      />
      <p class="text-lg text-gray-700 leading-relaxed whitespace-pre-line">{{ item.conteudo || item.resumo }}</p>
      <div v-if="!item.conteudo && !item.resumo && !item.acervo?.length" class="mt-6 bg-amber-50 border border-amber-100 rounded-2xl p-5 text-amber-900">
        Este fato já está na linha do tempo, mas a secretaria ainda está reunindo o relato e as fotos. Volte em breve ou envie o que você tiver.
      </div>
      <div v-if="item.personalidades?.length" class="mt-10">
        <h2 class="font-bold text-xl mb-4">Personalidades ligadas</h2>
        <div class="flex flex-wrap gap-3">
          <router-link
            v-for="p in item.personalidades"
            :key="p.id"
            :to="{ name: 'PortalMemoriaPersonalidade', params: { slug: p.slug || p.id } }"
            class="px-4 py-2 bg-white border rounded-full font-medium hover:border-blue-500 min-h-11 inline-flex items-center"
          >
            {{ p.nome }}
          </router-link>
        </div>
      </div>
      <div v-if="item.equipes?.length" class="mt-10">
        <h2 class="font-bold text-xl mb-4">Equipes deste fato</h2>
        <div class="flex flex-wrap gap-3">
          <router-link
            v-for="e in item.equipes"
            :key="e.id"
            :to="{ name: 'PortalMemoriaEquipe', params: { slug: e.slug || e.id } }"
            class="px-4 py-2 bg-white border rounded-full font-medium hover:border-blue-500 min-h-11 inline-flex items-center"
          >
            {{ e.nome }}
          </router-link>
        </div>
      </div>
      <div v-if="item.acervo?.length" class="mt-10">
        <h2 class="font-bold text-xl mb-4">Mídias deste fato</h2>
        <div class="grid grid-cols-2 gap-3">
          <router-link
            v-for="m in item.acervo"
            :key="m.id"
            :to="{ name: 'PortalMemoriaAcervo', params: { slug: m.slug || m.id } }"
            class="block rounded-2xl overflow-hidden bg-white border"
          >
            <img v-if="m.tipo === 'foto'" :src="m.arquivo_url" loading="lazy" class="w-full h-40 object-cover" :alt="m.alt_text || m.titulo" />
            <video v-else-if="m.tipo === 'video'" :src="m.arquivo_url" preload="none" controls class="w-full h-40 object-cover bg-black" :aria-label="m.alt_text || m.titulo"></video>
            <audio v-else-if="m.tipo === 'audio'" :src="m.arquivo_url" controls class="w-full mt-8 px-2"></audio>
            <div class="p-3 text-sm font-medium">{{ m.titulo }}</div>
          </router-link>
        </div>
      </div>
      <div v-if="item.relacionados?.length" class="mt-10">
        <h2 class="font-bold text-xl mb-4">Marcos relacionados</h2>
        <div class="grid gap-3">
          <router-link
            v-for="rel in item.relacionados"
            :key="rel.id"
            :to="{ name: 'PortalMemoriaMarco', params: { slug: rel.slug || rel.id } }"
            class="bg-white border rounded-2xl p-4 hover:border-blue-500"
          >
            <p class="text-sm text-amber-700 font-bold">{{ rel.ano }} · {{ tipoMarcoLabels[rel.tipo] || rel.tipo }}</p>
            <p class="font-semibold">{{ rel.titulo }}</p>
          </router-link>
        </div>
      </div>
      <router-link to="/memoria/enviar" class="mt-10 inline-flex text-sm font-bold text-blue-700">Tem foto deste fato? Envie para o acervo</router-link>
    </section>
  </article>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import portalService from '../services/portalService';
import { tipoMarcoLabels } from '@/modules/memoria/utils/memoriaLabels';
import { memoriaShareUrl } from '@/modules/memoria/utils/share';
import MemoriaEmpty from '../components/MemoriaEmpty.vue';
import MemoriaQrCard from '@/modules/memoria/components/MemoriaQrCard.vue';

const route = useRoute();
const item = ref({});
const loading = ref(true);
const missing = ref(false);

const shareUrl = computed(() => memoriaShareUrl('marcos', item.value.slug || route.params.slug));
const whatsappHref = computed(() => `https://wa.me/?text=${encodeURIComponent(`${item.value.titulo || ''} ${shareUrl.value}`)}`);
const facebookHref = computed(() => `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(shareUrl.value)}`);
const printQr = () => window.print();

const load = async () => {
  loading.value = true;
  missing.value = false;
  try {
    const { data } = await portalService.getMemoriaMarco(route.params.slug);
    item.value = data;
    document.title = `${data.titulo} | Memória do Esporte`;
  } catch {
    missing.value = true;
  } finally {
    loading.value = false;
  }
};

onMounted(load);
watch(() => route.params.slug, load);
</script>
