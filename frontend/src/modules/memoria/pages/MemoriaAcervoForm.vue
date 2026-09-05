<template>
  <div class="p-4 sm:p-6 w-full">
    <router-link to="/admin/memoria" class="text-sm font-semibold text-blue-700 hover:text-blue-900">← Voltar para a Memória</router-link>
    <h1 class="text-2xl font-bold text-gray-800 mt-3">{{ isEdit ? 'Editar item do acervo' : 'Novo item do acervo' }}</h1>
    <p class="text-gray-500 mt-1">Foto, vídeo, áudio ou documento. O arquivo e um título curto bastam para publicar.</p>

    <form class="mt-6 space-y-6" @submit.prevent="submit">
      <section class="bg-white p-5 sm:p-6 rounded-2xl border border-gray-200 shadow-sm space-y-5">
        <h2 class="text-sm font-bold uppercase tracking-wide text-gray-500">Arquivo</h2>
        <MemoriaFileDrop
          label="Mídia"
          hint="Foto, vídeo MP4/WEBM, áudio (MP3/OGG/WAV) ou PDF. Máx. 50 MB no admin."
          accept="image/*,video/mp4,video/webm,audio/mpeg,audio/mp4,audio/ogg,audio/webm,audio/wav,application/pdf"
          :required="!isEdit"
          empty-title="Enviar foto, vídeo, áudio ou PDF"
          empty-subtitle="O tipo é detectado automaticamente"
          :existing-url="existingArquivo"
          @change="onFile"
        />
        <MemoriaField label="Título visível" required hint="O que a pessoa vê na galeria.">
          <input v-model="form.titulo" required :class="inputClass" placeholder="Time campeão de 1998 no ginásio" maxlength="255" @blur="sugerirAltSeVazio" />
        </MemoriaField>
        <MemoriaField
          :label="form.tipo === 'foto' ? 'Texto alternativo (obrigatório em fotos)' : 'Texto alternativo'"
          :required="form.tipo === 'foto'"
          hint="Descreve a imagem para leitores de tela. Pode sugerir a partir do título."
        >
          <div class="flex flex-col sm:flex-row gap-2">
            <input
              v-model="form.alt_text"
              :required="form.tipo === 'foto'"
              :class="inputClass"
              class="flex-1"
              placeholder="Foto em preto e branco do time no ginásio lotado"
              maxlength="255"
            />
            <button type="button" class="px-4 py-2 rounded-xl border border-gray-300 text-sm font-semibold min-h-11 shrink-0" @click="sugerirAlt">Sugerir texto</button>
          </div>
        </MemoriaField>
        <MemoriaField label="Tipo" hint="Ajuste se a detecção automática errar.">
          <div class="flex flex-wrap gap-2">
            <button
              v-for="option in tipoOptions"
              :key="option.id"
              type="button"
              class="px-4 py-2 rounded-full border text-sm font-semibold min-h-11"
              :class="form.tipo === option.id ? 'bg-blue-600 text-white border-blue-600' : 'bg-white border-gray-200'"
              @click="form.tipo = option.id"
            >
              {{ option.label }}
            </button>
          </div>
        </MemoriaField>
        <MemoriaField label="O que essa mídia mostra">
          <textarea v-model="form.descricao" rows="3" :class="inputClass" class="!min-h-[5rem]" placeholder="Quem está na imagem, o que estava acontecendo..." />
        </MemoriaField>
        <MemoriaField label="História por trás (opcional)" hint="Contexto rico para a curadoria: como o arquivo chegou, o que acontecia na cidade.">
          <textarea v-model="form.historia" rows="4" :class="inputClass" class="!min-h-[6rem]" placeholder="Recorte do jornal de 1998, doado pela família..." />
        </MemoriaField>
        <MemoriaField
          v-if="form.tipo === 'audio' || form.tipo === 'video' || form.tipo === 'documento'"
          label="Transcrição ou texto do documento"
          hint="O cidadão lê na ficha. Cole a fala do depoimento ou o texto do recorte; não há OCR automático."
        >
          <textarea v-model="form.transcricao" rows="6" :class="inputClass" class="!min-h-[8rem]" placeholder="Texto integral ou trecho da fala..." />
        </MemoriaField>
      </section>

      <section class="bg-white p-5 sm:p-6 rounded-2xl border border-gray-200 shadow-sm space-y-5">
        <h2 class="text-sm font-bold uppercase tracking-wide text-gray-500">Quando, onde e quem</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <MemoriaField label="Data aproximada" hint="Opcional. Preenche o ano sozinho.">
            <input v-model="form.data_aproximada" type="date" :class="inputClass" @change="syncYear" />
          </MemoriaField>
          <MemoriaField label="Ano" hint="Se só souber a década, use 1990, 2000...">
            <input v-model="form.ano" type="number" min="1900" :max="currentYear" :class="inputClass" :disabled="!!form.data_aproximada" placeholder="1998" />
          </MemoriaField>
          <MemoriaField label="Modalidade">
            <select v-model="form.modalidade_id" :class="inputClass">
              <option value="">Não identificado</option>
              <option v-for="m in modalidades" :key="m.id" :value="String(m.id)">{{ m.nome }}</option>
            </select>
          </MemoriaField>
          <MemoriaField label="Local">
            <select v-model="form.local_id" :class="inputClass">
              <option value="">Não identificado</option>
              <option v-for="l in locais" :key="l.id" :value="String(l.id)">{{ l.nome }}</option>
            </select>
          </MemoriaField>
          <MemoriaField label="Ligar a um fato da timeline" hint="Opcional. A mídia aparece na página daquele fato.">
            <select v-model="form.marco_id" :class="inputClass">
              <option value="">Nenhum fato</option>
              <option v-for="m in marcos" :key="m.id" :value="String(m.id)">{{ m.ano ? m.ano + ' · ' : '' }}{{ m.titulo }}</option>
            </select>
          </MemoriaField>
        </div>
        <MemoriaField label="Pessoas nesta peça" hint="Uma foto pode ter várias pessoas, cada uma com um papel.">
          <div class="space-y-2">
            <div v-for="(row, idx) in pessoas" :key="idx" class="grid grid-cols-1 sm:grid-cols-[1fr_10rem_auto] gap-2">
              <select v-model="row.personalidade_id" :class="inputClass">
                <option value="">Escolher pessoa</option>
                <option v-for="p in personalidades" :key="p.id" :value="String(p.id)">{{ p.nome }}</option>
              </select>
              <select v-model="row.papel" :class="inputClass">
                <option v-for="(label, id) in papelPecaLabels" :key="id" :value="id">{{ label }}</option>
              </select>
              <button type="button" class="px-3 rounded-xl border text-sm font-semibold min-h-11" @click="pessoas.splice(idx, 1)">Remover</button>
            </div>
            <button type="button" class="text-sm font-semibold text-blue-700" @click="pessoas.push({ personalidade_id: '', papel: 'atleta' })">+ Adicionar pessoa</button>
          </div>
        </MemoriaField>
        <MemoriaField label="Pessoas ainda não cadastradas" hint="Nomes em texto, até virarem ficha no Hall.">
          <input v-model="form.pessoas_envolvidas" :class="inputClass" placeholder="Maria Silva, João Costa, técnico Pedro" />
        </MemoriaField>
        <MemoriaField label="Equipes nesta peça">
          <select v-model="form.equipe_ids" multiple class="w-full rounded-xl border-gray-300 min-h-[5.5rem]">
            <option v-for="e in equipes" :key="e.id" :value="String(e.id)">{{ e.nome }}</option>
          </select>
        </MemoriaField>
        <MemoriaField label="Tags" hint="Separadas por vírgula: final, escolinha, inauguração...">
          <input v-model="form.tag_nomes" :class="inputClass" placeholder="final, escolinha" />
        </MemoriaField>
        <label class="flex items-center gap-2 text-sm font-medium text-gray-700">
          <input v-model="form.aparece_na_timeline" type="checkbox" class="rounded border-gray-300" />
          Mostrar esta peça também na linha do tempo pública
        </label>
        <label v-if="form.tipo === 'foto'" class="flex items-center gap-2 text-sm font-medium text-gray-700">
          <input v-model="form.precisa_identificar" type="checkbox" class="rounded border-gray-300" />
          Pedir ajuda da cidade (“Quem é essa pessoa?”)
        </label>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <MemoriaField label="Tipo de fonte institucional" hint="De onde veio o original da prefeitura. Separado da doação da comunidade.">
            <select v-model="form.fonte_tipo" :class="inputClass">
              <option value="">Não informado</option>
              <option value="arquivo_publico">Arquivo público</option>
              <option value="jornal">Jornal</option>
              <option value="secretaria">Secretaria</option>
              <option value="clube">Clube</option>
              <option value="outro">Outro</option>
            </select>
          </MemoriaField>
          <MemoriaField label="Fonte (detalhe)" hint="Nome do jornal, pasta do arquivo, secretaria...">
            <input v-model="form.fonte" :class="inputClass" placeholder="Arquivo Municipal, Jornal da Cidade..." />
          </MemoriaField>
          <MemoriaField label="Crédito visível no portal" hint="O que o cidadão lê na ficha. Se vazio, usa a fonte.">
            <input v-model="form.credito" :class="inputClass" placeholder="Arquivo da Secretaria / Família Souza" />
          </MemoriaField>
          <MemoriaField label="Fotógrafo ou autor">
            <input v-model="form.fotografo" :class="inputClass" placeholder="Nome, se conhecido" />
          </MemoriaField>
          <MemoriaField label="Direitos de uso">
            <select v-model="form.licenca" :class="inputClass">
              <option v-for="(label, id) in licencaLabels" :key="id" :value="id">{{ label }}</option>
            </select>
          </MemoriaField>
        </div>
        <MemoriaField label="Doação da comunidade" hint="Quem entregou o arquivo, se não for acervo institucional.">
          <input v-model="form.doacao" :class="inputClass" placeholder="Família Souza, clube de veteranos..." />
        </MemoriaField>
      </section>

      <label class="flex gap-3 items-start p-4 bg-white rounded-2xl border border-gray-200 cursor-pointer">
        <input v-model="form.publicado" type="checkbox" class="mt-1 rounded border-gray-300 text-blue-600 w-5 h-5" />
        <span>
          <span class="block font-semibold text-gray-800">Mostrar na galeria pública</span>
          <span class="block text-sm text-gray-500">Desmarque para guardar só no acervo interno.</span>
        </span>
      </label>

      <MemoriaQrCard
        v-if="isEdit && publicSlug"
        title="QR da peça"
        hint="Imprima e afixe junto à exposição ou no espaço. Quem apontar a câmera abre esta ficha da Memória."
        :path="`/memoria/acervo/${publicSlug}`"
        :unpublished="!form.publicado"
      />

      <div v-if="error" class="text-red-600 text-sm bg-red-50 border border-red-100 rounded-xl px-4 py-3">{{ error }}</div>
      <div class="flex flex-wrap gap-3">
        <button class="bg-blue-600 text-white px-5 py-3 rounded-xl font-semibold min-h-11 disabled:opacity-50" :disabled="saving">
          {{ saving ? 'Salvando...' : 'Salvar no acervo' }}
        </button>
        <router-link to="/admin/memoria" class="px-5 py-3 rounded-xl border border-gray-300 font-medium min-h-11">Cancelar</router-link>
      </div>
    </form>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import memoriaService from '../services/memoriaService';
import modalidadeService from '@/modules/modalidades/services/modalidadeService';
import MemoriaField from '../components/MemoriaField.vue';
import MemoriaQrCard from '../components/MemoriaQrCard.vue';
import MemoriaFileDrop from '../components/MemoriaFileDrop.vue';
import { detectAcervoTipo, inputClass, licencaLabels, papelPecaLabels, yearFromDate } from '../utils/memoriaLabels';

const tipoOptions = [
  { id: 'foto', label: 'Foto' },
  { id: 'video', label: 'Vídeo' },
  { id: 'audio', label: 'Áudio' },
  { id: 'documento', label: 'Documento' },
];

const route = useRoute();
const router = useRouter();
const isEdit = !!route.params.id;
const currentYear = new Date().getFullYear();
const saving = ref(false);
const error = ref('');
const file = ref(null);
const existingArquivo = ref('');
const publicSlug = ref('');
const modalidades = ref([]);
const locais = ref([]);
const marcos = ref([]);
const personalidades = ref([]);
const equipes = ref([]);
const pessoas = ref([]);
const form = ref({
  titulo: '',
  alt_text: '',
  tipo: 'foto',
  descricao: '',
  historia: '',
  transcricao: '',
  data_aproximada: '',
  ano: '',
  data_incerta: false,
  modalidade_id: '',
  local_id: '',
  marco_id: '',
  personalidade_id: '',
  pessoas_envolvidas: '',
  fonte: '',
  fonte_tipo: '',
  doacao: '',
  credito: '',
  fotografo: '',
  licenca: 'nao_informado',
  equipe_ids: [],
  tag_nomes: '',
  aparece_na_timeline: false,
  publicado: true,
  precisa_identificar: false,
});

const onFile = (next) => {
  file.value = next;
  if (next) form.value.tipo = detectAcervoTipo(next);
};
const syncYear = () => {
  if (form.value.data_aproximada) form.value.ano = yearFromDate(form.value.data_aproximada);
};

const sugerirAlt = () => {
  const bits = [form.value.titulo];
  if (form.value.ano) bits.push(`em ${form.value.ano}`);
  if (form.value.pessoas_envolvidas) bits.push(`com ${form.value.pessoas_envolvidas}`);
  form.value.alt_text = `Foto: ${bits.filter(Boolean).join(', ')}`.slice(0, 255);
};

const sugerirAltSeVazio = () => {
  if (!form.value.alt_text && form.value.titulo) sugerirAlt();
};

const submit = async () => {
  saving.value = true;
  error.value = '';
  try {
    const fd = new FormData();
    Object.entries(form.value).forEach(([key, value]) => {
      if (key === 'equipe_ids') fd.append('equipe_ids', JSON.stringify(value || []));
      else if (typeof value === 'boolean') fd.append(key, value ? '1' : '0');
      else fd.append(key, value ?? '');
    });
    fd.append('marco_ids', JSON.stringify(form.value.marco_id ? [form.value.marco_id] : []));
    fd.append('pessoas_json', JSON.stringify(pessoas.value.filter((row) => row.personalidade_id)));
    if (file.value) fd.append('arquivo', file.value);
    if (isEdit) await memoriaService.updateAcervo(route.params.id, fd);
    else await memoriaService.createAcervo(fd);
    router.push('/admin/memoria');
  } catch (e) {
    error.value = e.response?.data?.message || 'Não foi possível salvar o item do acervo.';
  } finally {
    saving.value = false;
  }
};

onMounted(async () => {
  const [mods, locs, m, p, eqs] = await Promise.all([
    modalidadeService.getAll(),
    memoriaService.locais(),
    memoriaService.marcos(),
    memoriaService.personalidades(),
    memoriaService.equipes().catch(() => ({ data: [] })),
  ]);
  modalidades.value = mods.data?.data || mods.data || [];
  locais.value = locs.data || [];
  marcos.value = m.data || [];
  personalidades.value = p.data || [];
  equipes.value = eqs.data || [];
  if (isEdit) {
    const { data } = await memoriaService.getAcervo(route.params.id);
    form.value = {
      titulo: data.titulo,
      alt_text: data.alt_text || '',
      tipo: data.tipo || 'foto',
      descricao: data.descricao || '',
      historia: data.historia || '',
      transcricao: data.transcricao || '',
      data_aproximada: data.data_aproximada ? String(data.data_aproximada).slice(0, 10) : '',
      ano: data.ano || '',
      modalidade_id: data.modalidade_id ? String(data.modalidade_id) : '',
      local_id: data.local_id ? String(data.local_id) : '',
      marco_id: data.marco_id ? String(data.marco_id) : '',
      personalidade_id: data.personalidade_id ? String(data.personalidade_id) : '',
      pessoas_envolvidas: data.pessoas_envolvidas || '',
      fonte: data.fonte || '',
      fonte_tipo: data.fonte_tipo || '',
      doacao: data.doacao || '',
      credito: data.credito || '',
      fotografo: data.fotografo || '',
      licenca: data.licenca || 'nao_informado',
      equipe_ids: (data.equipes || []).map((e) => String(e.id)),
      tag_nomes: (data.tags || []).map((t) => t.nome).join(', '),
      data_incerta: !!data.data_incerta,
      aparece_na_timeline: !!data.aparece_na_timeline,
      publicado: !!data.publicado,
      precisa_identificar: !!data.precisa_identificar,
    };
    pessoas.value = (data.pessoas || []).map((p) => ({
      personalidade_id: String(p.id),
      papel: p.pivot?.papel || p.papel || 'outro',
    }));
    if (!pessoas.value.length && data.personalidade_id) {
      pessoas.value = [{ personalidade_id: String(data.personalidade_id), papel: 'outro' }];
    }
    existingArquivo.value = data.arquivo_url || '';
    publicSlug.value = data.slug || '';
  }
});
</script>
