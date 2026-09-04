<template>
  <div class="min-h-screen bg-gray-50">
    <section class="bg-slate-900 text-white py-10">
      <div class="container mx-auto px-4 max-w-2xl">
        <router-link to="/memoria" class="text-yellow-400 text-sm font-bold">← Memória</router-link>
        <h1 class="text-3xl font-extrabold mt-2">Envie um pedaço da história</h1>
        <p class="text-slate-300 mt-2">Fotos, recortes e vídeos passam pela curadoria da prefeitura antes de entrar no acervo.</p>
      </div>
    </section>
    <section class="container mx-auto px-4 py-8 max-w-2xl">
      <div v-if="ok" class="bg-green-50 text-green-800 p-6 rounded-2xl">
        Contribuição enviada. Obrigado — a equipe vai avaliar e, se aprovado, o item entra na galeria pública.
      </div>
      <form v-else class="bg-white p-6 rounded-2xl border space-y-4" @submit.prevent="submit">
        <div>
          <label class="block text-sm font-medium mb-1">Título</label>
          <input v-model="form.titulo" required class="w-full rounded-lg border-gray-300" />
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Arquivo (foto, vídeo ou PDF)</label>
          <input type="file" required accept="image/*,video/mp4,video/webm,application/pdf" @change="onFile" />
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Descrição</label>
          <textarea v-model="form.descricao" rows="4" class="w-full rounded-lg border-gray-300"></textarea>
        </div>
        <div class="grid md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium mb-1">Ano aproximado</label>
            <input v-model="form.ano" type="number" min="1900" class="w-full rounded-lg border-gray-300" />
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Local</label>
            <select v-model="form.local_id" class="w-full rounded-lg border-gray-300">
              <option value="">Não sei / outro</option>
              <option v-for="l in locais" :key="l.id" :value="l.id">{{ l.nome }}</option>
            </select>
          </div>
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Pessoas na foto</label>
          <input v-model="form.pessoas_envolvidas" class="w-full rounded-lg border-gray-300" />
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Fonte</label>
          <input v-model="form.fonte" placeholder="Álbum de família, jornal..." class="w-full rounded-lg border-gray-300" />
        </div>
        <div class="grid md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium mb-1">Seu nome</label>
            <input v-model="form.doador_nome" required class="w-full rounded-lg border-gray-300" />
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">E-mail</label>
            <input v-model="form.doador_email" type="email" required class="w-full rounded-lg border-gray-300" />
          </div>
        </div>
        <div v-if="error" class="text-red-600 text-sm">{{ error }}</div>
        <button class="bg-blue-600 text-white px-5 py-3 rounded-full font-bold" :disabled="saving">{{ saving ? 'Enviando...' : 'Enviar para curadoria' }}</button>
      </form>
    </section>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import portalService from '../services/portalService';

const saving = ref(false);
const ok = ref(false);
const error = ref('');
const file = ref(null);
const locais = ref([]);
const form = ref({
  titulo: '',
  descricao: '',
  ano: '',
  local_id: '',
  pessoas_envolvidas: '',
  fonte: '',
  doador_nome: '',
  doador_email: '',
});

const onFile = (e) => { file.value = e.target.files?.[0] || null; };

const submit = async () => {
  saving.value = true;
  error.value = '';
  try {
    const fd = new FormData();
    Object.entries(form.value).forEach(([k, v]) => fd.append(k, v ?? ''));
    fd.append('arquivo', file.value);
    await portalService.enviarMemoriaContribuicao(fd);
    ok.value = true;
  } catch (e) {
    error.value = e.response?.data?.message || 'Não foi possível enviar. Verifique o arquivo (máx. 20MB).';
  } finally {
    saving.value = false;
  }
};

onMounted(async () => {
  const { data } = await portalService.getMemoriaLocais();
  locais.value = data || [];
});
</script>
