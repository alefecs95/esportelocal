<template>
  <div class="min-h-screen bg-slate-50">
    <section class="bg-slate-900 text-white py-10 sm:py-14">
      <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <router-link to="/memoria" class="text-yellow-400 text-sm font-bold">← Voltar à Memória</router-link>
        <h1 class="text-3xl sm:text-4xl font-extrabold mt-3 leading-tight">Envie uma memória da cidade</h1>
        <p class="text-slate-300 mt-3 text-base sm:text-lg max-w-3xl">Foto antiga, recorte de jornal ou vídeo. A prefeitura confere antes de publicar — nada entra no ar automaticamente.</p>
      </div>
    </section>

    <section class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div v-if="ok" class="bg-white border border-green-100 rounded-3xl p-8 text-center shadow-sm">
        <div class="mx-auto w-14 h-14 rounded-full bg-green-100 text-green-700 flex items-center justify-center mb-4">✓</div>
        <h2 class="text-2xl font-bold text-gray-900">Recebemos o seu envio</h2>
        <p class="text-gray-600 mt-2">A curadoria vai avaliar. Se for aprovado, o item aparece na galeria histórica.</p>
        <div class="mt-6 flex flex-wrap justify-center gap-3">
          <router-link to="/memoria" class="bg-slate-900 text-white px-5 py-3 rounded-full font-bold min-h-11">Ver a Memória</router-link>
          <button type="button" class="border border-gray-300 px-5 py-3 rounded-full font-bold min-h-11" @click="resetForm">Enviar outro arquivo</button>
        </div>
      </div>

      <form v-else class="space-y-6" @submit.prevent="submit">
        <ol class="flex gap-2 text-xs font-bold uppercase tracking-wide text-slate-500">
          <li class="flex-1 h-1.5 rounded-full bg-yellow-400"></li>
          <li class="flex-1 h-1.5 rounded-full" :class="stepReady ? 'bg-yellow-400' : 'bg-slate-200'"></li>
          <li class="flex-1 h-1.5 rounded-full" :class="contactReady ? 'bg-yellow-400' : 'bg-slate-200'"></li>
        </ol>

        <div class="grid lg:grid-cols-2 gap-6 items-start">
        <section class="bg-white p-5 sm:p-6 lg:p-8 rounded-3xl border border-gray-200 shadow-sm space-y-4 lg:min-h-[22rem]">
          <p class="text-xs font-bold uppercase tracking-wide text-slate-400">1. Arquivo</p>
          <MemoriaFileDrop
            label="Sua foto, vídeo ou PDF"
            hint="Antes de enviar: JPG, PNG, WEBP, GIF, MP4, WEBM ou PDF. Tamanho máximo 20 MB."
            accept="image/jpeg,image/png,image/webp,image/gif,video/mp4,video/webm,application/pdf"
            required
            empty-title="Toque para escolher o arquivo"
            empty-subtitle="Até 20 MB · foto, vídeo ou PDF"
            @change="onFile"
          />
          <ul class="text-xs text-slate-500 grid sm:grid-cols-2 gap-1">
            <li>Fotos: JPG, PNG, WEBP ou GIF</li>
            <li>Vídeo: MP4 ou WEBM</li>
            <li>Documento: PDF de recorte ou ata</li>
            <li>Limite: 20 MB por arquivo</li>
          </ul>
        </section>

        <div class="space-y-6">
        <section class="bg-white p-5 sm:p-6 lg:p-8 rounded-3xl border border-gray-200 shadow-sm space-y-4">
          <p class="text-xs font-bold uppercase tracking-wide text-slate-400">2. O que é isso?</p>
          <MemoriaField label="Como chamar esta memória" required hint="Uma frase que qualquer morador entenda.">
            <input v-model="form.titulo" required :class="inputClass" placeholder="Final do campeonato no ginásio, anos 90" maxlength="255" />
          </MemoriaField>
          <MemoriaField label="Conte o que você sabe" hint="Uma frase curta sobre o que aparece.">
            <textarea v-model="form.descricao" rows="2" :class="inputClass" class="!min-h-[4rem]" placeholder="Time do bairro X na final..." />
          </MemoriaField>
          <MemoriaField label="A história por trás (opcional)" hint="Como você chegou nesse arquivo, o que acontecia na cidade, quem te contou. Ajuda a curadoria.">
            <textarea v-model="form.historia" rows="4" :class="inputClass" class="!min-h-[6rem]" placeholder="Meu avô guardou essa foto. Era o último jogo no ginásio antigo..." />
          </MemoriaField>
          <div class="grid sm:grid-cols-2 gap-4">
            <MemoriaField label="Ano, se lembrar" hint="Se não souber, deixe em branco.">
              <input v-model="form.ano" type="number" min="1900" :max="currentYear" :class="inputClass" placeholder="Ex.: 1998" />
            </MemoriaField>
            <MemoriaField label="Onde foi" hint="Ginásio, estádio, quadra...">
              <select v-model="form.local_id" :class="inputClass">
                <option value="">Não sei / outro lugar</option>
                <option v-for="l in locais" :key="l.id" :value="l.id">{{ l.nome }}</option>
              </select>
            </MemoriaField>
          </div>
          <MemoriaField v-if="modalidades.length" label="Esporte">
            <select v-model="form.modalidade_id" :class="inputClass">
              <option value="">Não sei / vários</option>
              <option v-for="m in modalidades" :key="m.id" :value="m.id">{{ m.nome }}</option>
            </select>
          </MemoriaField>
          <MemoriaField label="Quem aparece" hint="Nomes que você reconhece, separados por vírgula.">
            <input v-model="form.pessoas_envolvidas" :class="inputClass" placeholder="Apelido, nome do time, técnico..." />
          </MemoriaField>
          <MemoriaField label="Doação / origem do arquivo" hint="Álbum de família, jornal, clube... Fica separado da fonte institucional da prefeitura.">
            <input v-model="form.doacao" :class="inputClass" placeholder="Álbum da família, Jornal da Cidade..." />
          </MemoriaField>
        </section>

        <section class="bg-white p-5 sm:p-6 rounded-3xl border border-gray-200 shadow-sm space-y-4">
          <p class="text-xs font-bold uppercase tracking-wide text-slate-400">3. Para a prefeitura falar com você</p>
          <div class="grid sm:grid-cols-2 gap-4">
            <MemoriaField label="Seu nome" required>
              <input v-model="form.doador_nome" required :class="inputClass" placeholder="Nome e sobrenome" autocomplete="name" />
            </MemoriaField>
            <MemoriaField label="Telefone / WhatsApp" hint="Opcional. Só a curadoria vê, para falar com você se precisar. Não enviamos mensagem automática.">
              <input v-model="form.doador_telefone" type="tel" :class="inputClass" placeholder="(15) 99999-0000" autocomplete="tel" />
            </MemoriaField>
          </div>
          <p class="text-xs text-gray-500">Ao enviar, você autoriza o uso desta mídia no acervo histórico municipal. A prefeitura avalia antes de publicar.</p>
        </section>
        </div>
        </div>

        <div v-if="error" class="text-red-700 text-sm bg-red-50 border border-red-100 rounded-2xl px-4 py-3">{{ error }}</div>
        <button class="w-full sm:w-auto bg-yellow-400 hover:bg-yellow-300 text-slate-900 px-8 py-3.5 rounded-full font-black min-h-12 disabled:opacity-50" :disabled="saving || !file">
          {{ saving ? 'Enviando...' : 'Enviar para a prefeitura' }}
        </button>
      </form>
    </section>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import portalService from '../services/portalService';
import modalidadeService from '@/modules/modalidades/services/modalidadeService';
import MemoriaField from '@/modules/memoria/components/MemoriaField.vue';
import MemoriaFileDrop from '@/modules/memoria/components/MemoriaFileDrop.vue';
import { detectAcervoTipo, inputClass } from '@/modules/memoria/utils/memoriaLabels';

const currentYear = new Date().getFullYear();
const saving = ref(false);
const ok = ref(false);
const error = ref('');
const file = ref(null);
const locais = ref([]);
const modalidades = ref([]);
const form = ref({
  titulo: '',
  descricao: '',
  historia: '',
  ano: '',
  local_id: '',
  modalidade_id: '',
  pessoas_envolvidas: '',
  doacao: '',
  doador_nome: '',
  doador_telefone: '',
  tipo: 'foto',
});

const stepReady = computed(() => !!form.value.titulo);
const contactReady = computed(() => !!form.value.doador_nome);

const MAX_BYTES = 20 * 1024 * 1024;
const allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'video/mp4', 'video/webm', 'application/pdf'];

const onFile = (next) => {
  file.value = next;
  error.value = '';
  if (!next) return;
  if (next.size > MAX_BYTES) {
    error.value = 'Este arquivo passa de 20 MB. Envie uma versão menor.';
    file.value = null;
    return;
  }
  if (next.type && !allowed.includes(next.type)) {
    error.value = 'Use JPG, PNG, WEBP, GIF, MP4, WEBM ou PDF.';
    file.value = null;
    return;
  }
  form.value.tipo = detectAcervoTipo(next);
};

const resetForm = () => {
  ok.value = false;
  file.value = null;
  form.value = {
    titulo: '',
    descricao: '',
    historia: '',
    ano: '',
    local_id: '',
    modalidade_id: '',
    pessoas_envolvidas: '',
    doacao: '',
    doador_nome: '',
    doador_telefone: '',
    tipo: 'foto',
  };
};

const submit = async () => {
  if (!file.value) {
    error.value = 'Escolha um arquivo para enviar.';
    return;
  }
  if (file.value.size > MAX_BYTES) {
    error.value = 'Este arquivo passa de 20 MB.';
    return;
  }
  saving.value = true;
  error.value = '';
  try {
    const fd = new FormData();
    Object.entries(form.value).forEach(([k, v]) => fd.append(k, v ?? ''));
    fd.append('arquivo', file.value);
    await portalService.enviarMemoriaContribuicao(fd);
    ok.value = true;
  } catch (e) {
    const first = e.response?.data?.errors ? Object.values(e.response.data.errors).flat()[0] : null;
    error.value = first || e.response?.data?.message || 'Não foi possível enviar. Confira o arquivo (máx. 20 MB) e tente de novo.';
  } finally {
    saving.value = false;
  }
};

onMounted(async () => {
  try {
    const [{ data: locData }, mods] = await Promise.all([
      portalService.getMemoriaLocais(),
      modalidadeService.getAll().catch(() => ({ data: [] })),
    ]);
    locais.value = locData || [];
    modalidades.value = mods.data?.data || mods.data || [];
  } catch {
    locais.value = [];
  }
});
</script>
