<template>
  <MemoriaEmpty
    v-if="!loading && missing"
    title="Esta peça ainda não está no Memorial"
    description="O item pode estar em curadoria ou o link está desatualizado. Veja a galeria histórica da cidade."
  />
  <div v-else-if="loading" class="min-h-screen bg-slate-50">
    <div class="h-[48vh] bg-slate-200 animate-pulse"></div>
    <div class="container mx-auto px-4 py-8 max-w-3xl space-y-3">
      <div class="h-4 bg-slate-200 rounded w-1/3 animate-pulse"></div>
      <div class="h-8 bg-slate-200 rounded w-2/3 animate-pulse"></div>
    </div>
  </div>
  <article v-else class="min-h-screen bg-slate-50">
    <section class="bg-slate-950 text-white">
      <div class="container mx-auto px-4 py-8 max-w-5xl">
        <nav class="text-xs sm:text-sm text-slate-300 mb-4 flex flex-wrap gap-1">
          <router-link to="/memoria" class="hover:text-yellow-400">Memória</router-link>
          <span>/</span>
          <router-link to="/memoria/galeria" class="hover:text-yellow-400">Acervo</router-link>
          <span>/</span>
          <span class="text-white truncate max-w-[50vw]">{{ item.titulo }}</span>
        </nav>
        <div class="bg-black/40 rounded-2xl overflow-hidden flex items-center justify-center min-h-[40vh]">
          <img
            v-if="item.tipo === 'foto'"
            :src="item.arquivo_url"
            class="max-h-[72vh] w-full object-contain"
            :alt="item.alt_text || item.titulo"
          />
          <video
            v-else-if="item.tipo === 'video'"
            :src="item.arquivo_url"
            controls
            class="max-h-[72vh] w-full bg-black"
            :aria-label="item.alt_text || item.titulo"
          ></video>
          <div v-else-if="item.tipo === 'audio'" class="p-10 w-full">
            <p class="text-sm uppercase tracking-widest text-yellow-400 mb-4">Áudio</p>
            <audio :src="item.arquivo_url" controls class="w-full"></audio>
          </div>
          <div v-else class="p-10 text-center">
            <p class="font-semibold">Documento</p>
            <a :href="item.arquivo_url" target="_blank" rel="noopener" class="inline-flex mt-4 bg-yellow-400 text-slate-900 px-5 py-3 rounded-full font-bold">Abrir original</a>
          </div>
        </div>
      </div>
    </section>

    <section class="container mx-auto px-4 py-8 max-w-3xl">
      <p class="uppercase tracking-widest text-xs font-bold text-amber-800">
        {{ periodo }}
        <span v-if="item.local?.nome"> · </span>
        <router-link
          v-if="item.local?.nome"
          :to="{ name: 'PortalMemoriaLocal', params: { slug: item.local.slug || item.local.id } }"
          class="hover:underline"
        >{{ item.local.nome }}</router-link>
        <span v-if="item.modalidade?.nome"> · {{ item.modalidade.nome }}</span>
      </p>
      <h1 class="text-3xl sm:text-4xl font-extrabold text-slate-900 mt-2 leading-tight">{{ item.titulo }}</h1>
      <p v-if="item.descricao" class="mt-4 text-lg text-slate-700 leading-relaxed whitespace-pre-line">{{ item.descricao }}</p>

      <dl class="mt-6 grid sm:grid-cols-2 gap-3 text-sm">
        <div v-if="credito" class="bg-white border rounded-2xl p-4">
          <dt class="text-xs uppercase font-bold text-slate-500">Crédito</dt>
          <dd class="mt-1 text-slate-800">{{ credito }}</dd>
        </div>
        <div v-if="item.fotografo" class="bg-white border rounded-2xl p-4">
          <dt class="text-xs uppercase font-bold text-slate-500">Fotógrafo / autor</dt>
          <dd class="mt-1 text-slate-800">{{ item.fotografo }}</dd>
        </div>
        <div class="bg-white border rounded-2xl p-4">
          <dt class="text-xs uppercase font-bold text-slate-500">Direitos de uso</dt>
          <dd class="mt-1 text-slate-800">{{ licencaLabels[item.licenca] || 'Não informado' }}</dd>
        </div>
        <div v-if="item.doador_nome && item.origem === 'comunidade'" class="bg-white border rounded-2xl p-4">
          <dt class="text-xs uppercase font-bold text-slate-500">Doação</dt>
          <dd class="mt-1 text-slate-800">{{ item.doacao || item.doador_nome }}</dd>
        </div>
      </dl>

      <div v-if="item.historia" class="mt-8">
        <h2 class="font-bold text-xl mb-3">Contexto histórico</h2>
        <p class="text-slate-700 leading-relaxed whitespace-pre-line">{{ item.historia }}</p>
      </div>

      <div v-if="item.transcricao" class="mt-8 bg-white border rounded-2xl p-5">
        <h2 class="font-bold text-xl mb-3">{{ item.tipo === 'documento' ? 'Texto do documento' : 'Transcrição' }}</h2>
        <p class="text-slate-700 leading-relaxed whitespace-pre-line">{{ item.transcricao }}</p>
      </div>

      <div v-if="pessoas.length" class="mt-10">
        <h2 class="font-bold text-xl mb-3">Pessoas identificadas</h2>
        <div class="flex flex-wrap gap-2">
          <router-link
            v-for="p in pessoas"
            :key="p.id"
            :to="{ name: 'PortalMemoriaPersonalidade', params: { slug: p.slug || p.id } }"
            class="px-4 py-2 bg-white border rounded-full font-medium hover:border-blue-500 min-h-11 inline-flex items-center gap-2"
          >
            <span>{{ p.nome }}</span>
            <span class="text-xs text-slate-500">{{ papelPecaLabels[p.pivot?.papel] || papelLabels[p.papel] || p.pivot?.papel }}</span>
          </router-link>
        </div>
      </div>
      <p v-else-if="item.pessoas_envolvidas" class="mt-8 text-sm text-slate-600">Pessoas citadas: {{ item.pessoas_envolvidas }}</p>

      <div v-if="item.equipes?.length" class="mt-10">
        <h2 class="font-bold text-xl mb-3">Equipes</h2>
        <div class="flex flex-wrap gap-2">
          <router-link
            v-for="e in item.equipes"
            :key="e.id"
            :to="{ name: 'PortalMemoriaEquipe', params: { slug: e.slug || e.id } }"
            class="px-4 py-2 bg-white border rounded-full font-medium hover:border-blue-500"
          >{{ e.nome }}</router-link>
        </div>
      </div>

      <div v-if="marcosLigados.length" class="mt-10">
        <h2 class="font-bold text-xl mb-3">Na linha do tempo</h2>
        <div class="grid gap-3">
          <router-link
            v-for="m in marcosLigados"
            :key="m.id"
            :to="{ name: 'PortalMemoriaMarco', params: { slug: m.slug || m.id } }"
            class="block bg-white border rounded-2xl p-4 hover:border-blue-500"
          >
            <p class="text-sm text-amber-700 font-bold">{{ m.ano }}</p>
            <p class="font-semibold">{{ m.titulo }}</p>
          </router-link>
        </div>
      </div>

      <div v-if="item.tags?.length" class="mt-8 flex flex-wrap gap-2">
        <span v-for="t in item.tags" :key="t.id" class="text-xs font-bold uppercase tracking-wide bg-slate-200 text-slate-700 px-3 py-1 rounded-full">{{ t.nome }}</span>
      </div>

      <div v-if="item.relacionados?.length" class="mt-10">
        <h2 class="font-bold text-xl mb-3">Outras peças deste momento</h2>
        <div class="grid grid-cols-2 gap-3">
          <router-link
            v-for="rel in item.relacionados"
            :key="rel.id"
            :to="{ name: 'PortalMemoriaAcervo', params: { slug: rel.slug || rel.id } }"
            class="rounded-2xl overflow-hidden bg-white border hover:border-blue-500"
          >
            <img v-if="rel.tipo === 'foto'" :src="rel.arquivo_url" class="w-full h-32 object-cover" :alt="rel.alt_text || rel.titulo" />
            <div class="p-3 text-sm font-medium">{{ rel.titulo }}</div>
          </router-link>
        </div>
      </div>

      <div class="mt-10 flex flex-wrap gap-2">
        <a :href="whatsappHref" target="_blank" rel="noopener" class="px-4 py-2 rounded-full bg-green-600 text-white text-sm font-bold">Compartilhar no WhatsApp</a>
        <a :href="facebookHref" target="_blank" rel="noopener" class="px-4 py-2 rounded-full bg-blue-700 text-white text-sm font-bold">Facebook</a>
        <a v-if="item.arquivo_url" :href="item.arquivo_url" target="_blank" rel="noopener" class="px-4 py-2 rounded-full border text-sm font-bold">Abrir original</a>
      </div>

      <div class="mt-10 grid sm:grid-cols-2 gap-3">
        <form class="bg-white border rounded-2xl p-5 space-y-3" @submit.prevent="enviarCorrecao">
          <p class="text-xs font-bold uppercase text-amber-700">Encontrou um erro?</p>
          <p class="font-semibold">Sugira local, data, equipe ou o que está acontecendo.</p>
          <select v-model="correcao.tipo" class="w-full rounded-xl border-gray-300 min-h-11" required>
            <option value="o_que">O que está acontecendo?</option>
            <option value="local">Onde foi?</option>
            <option value="data">Quando foi?</option>
            <option value="equipe">Qual equipe?</option>
            <option value="marco">Qual evento / fato?</option>
            <option value="pessoa">Quem é?</option>
            <option value="outro">Outra correção</option>
          </select>
          <input v-model="correcao.valor" class="w-full rounded-xl border-gray-300 min-h-11" placeholder="Sua sugestão" required />
          <input v-model="correcao.doador_nome" class="w-full rounded-xl border-gray-300 min-h-11" placeholder="Seu nome (opcional)" />
          <p v-if="correcao.ok" class="text-sm text-green-700 font-semibold">Sugestão enviada. Obrigado.</p>
          <p v-else-if="correcao.error" class="text-sm text-red-700">{{ correcao.error }}</p>
          <button class="bg-slate-900 text-white px-4 py-2 rounded-full font-bold min-h-11" :disabled="correcao.saving">
            {{ correcao.saving ? 'Enviando...' : 'Enviar à curadoria' }}
          </button>
        </form>
        <router-link to="/memoria/enviar" class="bg-white border rounded-2xl p-5 hover:border-blue-500">
          <p class="text-xs font-bold uppercase text-amber-700">Tem outra foto deste momento?</p>
          <p class="font-semibold mt-1">Envie para a curadoria da prefeitura.</p>
        </router-link>
      </div>
    </section>
  </article>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import portalService from '../services/portalService';
import { licencaLabels, papelLabels, papelPecaLabels } from '@/modules/memoria/utils/memoriaLabels';
import { memoriaShareUrl } from '@/modules/memoria/utils/share';
import MemoriaEmpty from '../components/MemoriaEmpty.vue';

const route = useRoute();
const item = ref({});
const loading = ref(true);
const missing = ref(false);

const pessoas = computed(() => {
  if (item.value.pessoas?.length) return item.value.pessoas;
  return item.value.personalidade ? [item.value.personalidade] : [];
});

const marcosLigados = computed(() => {
  const list = item.value.marcos?.length ? item.value.marcos : (item.value.marco ? [item.value.marco] : []);
  return list;
});

const correcao = reactive({ tipo: 'o_que', valor: '', doador_nome: '', saving: false, ok: false, error: '' });

const enviarCorrecao = async () => {
  correcao.saving = true;
  correcao.ok = false;
  correcao.error = '';
  try {
    await portalService.corrigirMemoriaAcervo(item.value.id, {
      tipo: correcao.tipo,
      valor: correcao.valor,
      doador_nome: correcao.doador_nome,
      ano_sugerido: correcao.tipo === 'data' ? correcao.valor : null,
    });
    correcao.ok = true;
    correcao.valor = '';
  } catch (e) {
    correcao.error = e.response?.data?.message || 'Não foi possível enviar.';
  } finally {
    correcao.saving = false;
  }
};

const periodo = computed(() => {
  if (item.value.data_incerta) return 'Data a confirmar';
  return item.value.ano || 'Ano a confirmar';
});

const credito = computed(() => item.value.credito || item.value.fonte || item.value.doacao || '');

const shareUrl = computed(() => memoriaShareUrl('acervo', item.value.slug || route.params.slug));
const whatsappHref = computed(() => `https://wa.me/?text=${encodeURIComponent(`${item.value.titulo || ''} ${shareUrl.value}`)}`);
const facebookHref = computed(() => `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(shareUrl.value)}`);

const load = async () => {
  loading.value = true;
  missing.value = false;
  try {
    const { data } = await portalService.getMemoriaAcervo(route.params.slug);
    item.value = data;
    document.title = `${data.titulo} | Memorial Esportivo`;
  } catch {
    missing.value = true;
  } finally {
    loading.value = false;
  }
};

onMounted(load);
watch(() => route.params.slug, load);
</script>
