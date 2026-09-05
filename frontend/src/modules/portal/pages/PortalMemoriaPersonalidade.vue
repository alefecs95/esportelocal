<template>
  <MemoriaEmpty
    v-if="!loading && missing"
    title="Personalidade ainda não está no Hall"
    description="Esse perfil pode estar em curadoria ou o link está desatualizado. Veja quem já faz parte da história da cidade."
  />
  <div v-else-if="loading" class="min-h-screen bg-gray-50">
    <div class="container mx-auto px-4 py-10 max-w-4xl grid md:grid-cols-[16rem_1fr] gap-8">
      <div class="h-72 bg-slate-200 rounded-3xl animate-pulse"></div>
      <div class="space-y-3 pt-6">
        <div class="h-4 bg-slate-200 rounded w-32 animate-pulse"></div>
        <div class="h-10 bg-slate-200 rounded w-2/3 animate-pulse"></div>
        <div class="h-28 bg-slate-200 rounded animate-pulse"></div>
      </div>
    </div>
  </div>
  <article v-else class="container mx-auto px-4 py-10 max-w-4xl">
    <nav class="text-sm text-gray-500 mb-6 flex flex-wrap gap-1">
      <router-link to="/memoria" class="hover:text-blue-700">Memória</router-link>
      <span>/</span>
      <router-link to="/memoria/hall" class="hover:text-blue-700">Hall da cidade</router-link>
      <span>/</span>
      <span class="text-gray-900 truncate max-w-[50vw]">{{ item.nome }}</span>
    </nav>
    <div class="grid md:grid-cols-[16rem_1fr] gap-8">
      <div class="rounded-3xl overflow-hidden bg-slate-200 h-72 md:h-80">
        <img v-if="item.foto_url" :src="item.foto_url" class="w-full h-full object-cover object-top" :alt="item.nome" />
      </div>
      <div>
        <p class="uppercase text-xs font-bold text-blue-700 tracking-wide">
          {{ papelLabels[item.papel] || item.papel }}
          <span v-if="item.modalidade?.nome"> · {{ item.modalidade.nome }}</span>
        </p>
        <h1 class="text-4xl font-extrabold mt-1">{{ item.nome }}</h1>
        <p v-if="item.apelido" class="text-lg text-slate-500 mt-1">“{{ item.apelido }}”</p>
        <p v-if="item.data_nascimento" class="text-sm text-gray-500 mt-2">
          {{ formatDate(item.data_nascimento) }}
          <span v-if="item.data_falecimento"> — {{ formatDate(item.data_falecimento) }}</span>
        </p>
        <p class="mt-4 text-gray-700 leading-relaxed whitespace-pre-line">{{ item.mini_bio }}</p>
        <p v-if="!item.mini_bio" class="mt-4 text-gray-500 bg-slate-50 border rounded-2xl p-4">
          A biografia ainda está sendo reunida. Se você tem recortes ou fotos, envie para o acervo.
        </p>
        <div class="mt-5 bg-amber-50 border border-amber-100 rounded-2xl p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
          <div>
            <p class="text-xs font-bold uppercase tracking-wide text-amber-800">Hall da cidade</p>
            <p class="text-2xl font-black text-slate-900 tabular-nums">{{ item.votos_hall || 0 }}</p>
            <p class="text-sm text-slate-600">voto{{ (item.votos_hall || 0) === 1 ? '' : 's' }} do público</p>
          </div>
          <button type="button" class="px-5 py-3 rounded-full bg-yellow-400 text-slate-900 text-sm font-bold min-h-11 disabled:opacity-60" :disabled="voting || voteMsg === 'ok'" @click="votar">
            {{ voteLabel }}
          </button>
        </div>
        <div class="mt-4 flex flex-wrap gap-2">
          <a :href="whatsappHref" target="_blank" rel="noopener" class="px-4 py-2 rounded-full bg-green-600 text-white text-sm font-bold">WhatsApp</a>
          <a :href="facebookHref" target="_blank" rel="noopener" class="px-4 py-2 rounded-full bg-blue-700 text-white text-sm font-bold">Facebook</a>
        </div>
      </div>
    </div>
    <div v-if="item.equipes?.length" class="mt-12">
      <h2 class="text-xl font-bold mb-4">Equipes</h2>
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
    <div v-if="item.marcos?.length" class="mt-12">
      <h2 class="text-xl font-bold mb-4">Na linha do tempo</h2>
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
    <div v-if="item.acervo?.length" class="mt-12">
      <h2 class="text-xl font-bold mb-4">Acervo associado</h2>
      <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
        <router-link
          v-for="m in item.acervo"
          :key="m.id"
          :to="{ name: 'PortalMemoriaAcervo', params: { slug: m.slug || m.id } }"
          class="rounded-2xl overflow-hidden bg-white border"
        >
          <img v-if="m.tipo === 'foto'" :src="m.arquivo_url" loading="lazy" class="w-full h-40 object-cover" :alt="m.alt_text || m.titulo" />
          <video v-else-if="m.tipo === 'video'" :src="m.arquivo_url" preload="none" controls class="w-full h-40 bg-black"></video>
          <audio v-else-if="m.tipo === 'audio'" :src="m.arquivo_url" controls class="w-full mt-8 px-2"></audio>
          <div class="p-3 text-sm font-medium">{{ m.titulo }}</div>
        </router-link>
      </div>
    </div>
    <div v-if="!item.marcos?.length && !item.acervo?.length" class="mt-12 bg-slate-50 border rounded-2xl p-6 text-center text-gray-600">
      Ainda não há fatos ou mídias ligados a esta pessoa.
      <router-link to="/memoria/enviar" class="block mt-3 font-bold text-blue-700">Enviar uma foto</router-link>
    </div>
  </article>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import portalService from '../services/portalService';
import { papelLabels } from '@/modules/memoria/utils/memoriaLabels';
import { memoriaShareUrl } from '@/modules/memoria/utils/share';
import MemoriaEmpty from '../components/MemoriaEmpty.vue';

const route = useRoute();
const item = ref({});
const loading = ref(true);
const missing = ref(false);
const voting = ref(false);
const voteMsg = ref('');

const shareUrl = computed(() => memoriaShareUrl('personalidades', item.value.slug || route.params.slug));
const whatsappHref = computed(() => `https://wa.me/?text=${encodeURIComponent(`${item.value.nome || ''} ${shareUrl.value}`)}`);
const facebookHref = computed(() => `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(shareUrl.value)}`);
const voteLabel = computed(() => (voteMsg.value === 'ok' ? 'Você já votou' : (voting.value ? 'Registrando...' : 'Votar no Hall')));

const formatDate = (value) => {
  try {
    return new Date(value).toLocaleDateString('pt-BR');
  } catch {
    return '';
  }
};

const load = async () => {
  loading.value = true;
  missing.value = false;
  try {
    const { data } = await portalService.getMemoriaPersonalidade(route.params.slug);
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

const votar = async () => {
  if (voteMsg.value === 'ok' || voting.value) return;
  voting.value = true;
  try {
    const { data } = await portalService.votarMemoriaHall(item.value.slug || route.params.slug);
    item.value.votos_hall = data.votos_hall;
    voteMsg.value = 'ok';
  } catch (e) {
    if (e?.response?.status === 409) {
      item.value.votos_hall = e.response.data?.votos_hall ?? item.value.votos_hall;
      voteMsg.value = 'ok';
    }
  } finally {
    voting.value = false;
  }
};
</script>
