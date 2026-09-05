<template>
  <div class="min-h-screen bg-slate-50">
    <MemoriaPageHero
      title="Quem é essa pessoa?"
      description="Fotos em que a secretaria ainda não identificou todo mundo. Sua sugestão só entra no acervo depois da curadoria."
    />
    <section class="container mx-auto px-4 py-6">
      <div v-if="loading" class="grid md:grid-cols-2 gap-4">
        <div v-for="n in 4" :key="n" class="h-64 bg-slate-200 rounded-2xl animate-pulse"></div>
      </div>
      <div v-else-if="fotos.length === 0" class="py-16 text-center text-gray-500 bg-white rounded-2xl border">
        <p class="font-semibold text-gray-800">Nenhuma foto aguardando identificação agora.</p>
        <router-link to="/memoria/galeria" class="inline-flex mt-4 bg-slate-900 text-white px-5 py-3 rounded-full font-bold">Ver galeria</router-link>
      </div>
      <div v-else class="grid lg:grid-cols-2 gap-6">
        <article v-for="foto in fotos" :key="foto.id" class="bg-white border rounded-3xl overflow-hidden">
          <img :src="foto.arquivo_url" loading="lazy" class="w-full h-56 object-cover bg-slate-100" :alt="foto.alt_text || foto.titulo" />
          <div class="p-5 space-y-3">
            <p class="text-xs text-amber-700 font-bold uppercase">{{ foto.ano || 'Ano a confirmar' }}<span v-if="foto.local?.nome"> · {{ foto.local.nome }}</span></p>
            <h2 class="font-bold text-lg">{{ foto.titulo }}</h2>
            <p v-if="foto.pessoas_envolvidas" class="text-sm text-gray-500">Já no acervo: {{ foto.pessoas_envolvidas }}</p>
            <form class="space-y-3" @submit.prevent="enviar(foto)">
              <input v-model="forms[foto.id].nome_sugerido" required class="w-full rounded-xl border-gray-300 min-h-11" placeholder="Nome de quem você reconhece" maxlength="255" />
              <select v-model="forms[foto.id].personalidade_id" class="w-full rounded-xl border-gray-300 min-h-11">
                <option value="">Não está no Hall / não sei o perfil</option>
                <option v-for="p in personalidades" :key="p.id" :value="p.id">{{ p.nome }}</option>
              </select>
              <input v-model="forms[foto.id].doador_nome" class="w-full rounded-xl border-gray-300 min-h-11" placeholder="Seu nome (opcional)" />
              <textarea v-model="forms[foto.id].comentario" rows="2" class="w-full rounded-xl border-gray-300" placeholder="Onde ela aparece na foto, apelido..." />
              <p v-if="forms[foto.id].ok" class="text-sm text-green-700 font-semibold">Sugestão enviada. Obrigado.</p>
              <p v-else-if="forms[foto.id].error" class="text-sm text-red-700">{{ forms[foto.id].error }}</p>
              <button class="bg-yellow-400 text-slate-900 px-5 py-2.5 rounded-full font-bold min-h-11 disabled:opacity-50" :disabled="forms[foto.id].saving">
                {{ forms[foto.id].saving ? 'Enviando...' : 'Enviar identificação' }}
              </button>
            </form>
          </div>
        </article>
      </div>
    </section>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import portalService from '../services/portalService';
import MemoriaPageHero from '../components/MemoriaPageHero.vue';

const loading = ref(true);
const fotos = ref([]);
const personalidades = ref([]);
const forms = reactive({});

const emptyForm = () => ({
  nome_sugerido: '',
  personalidade_id: '',
  doador_nome: '',
  comentario: '',
  saving: false,
  ok: false,
  error: '',
});

onMounted(async () => {
  try {
    const { data } = await portalService.getMemoriaQuemE();
    fotos.value = data.fotos || [];
    personalidades.value = data.personalidades || [];
    fotos.value.forEach((foto) => {
      forms[foto.id] = emptyForm();
    });
  } finally {
    loading.value = false;
  }
});

const enviar = async (foto) => {
  const form = forms[foto.id];
  form.saving = true;
  form.error = '';
  try {
    await portalService.identificarMemoriaAcervo(foto.id, {
      nome_sugerido: form.nome_sugerido,
      personalidade_id: form.personalidade_id || null,
      doador_nome: form.doador_nome || null,
      comentario: form.comentario || null,
    });
    form.ok = true;
  } catch (e) {
    form.error = e.response?.data?.message || 'Não foi possível enviar a sugestão.';
  } finally {
    form.saving = false;
  }
};
</script>
