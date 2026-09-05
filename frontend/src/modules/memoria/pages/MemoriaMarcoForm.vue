<template>
  <div class="p-4 sm:p-6 w-full">
    <router-link to="/admin/memoria" class="text-sm font-semibold text-blue-700 hover:text-blue-900">← Voltar para a Memória</router-link>
    <h1 class="text-2xl font-bold text-gray-800 mt-3">{{ isEdit ? 'Editar fato histórico' : 'Novo fato na linha do tempo' }}</h1>
    <p class="text-gray-500 mt-1">Conte o que aconteceu, quando foi e quem participou. Só o título é obrigatório.</p>

    <form class="mt-6 space-y-6" @submit.prevent="submit">
      <section class="bg-white p-5 sm:p-6 rounded-2xl border border-gray-200 shadow-sm space-y-5">
        <h2 class="text-sm font-bold uppercase tracking-wide text-gray-500">O que aconteceu</h2>
        <MemoriaField label="Título do fato" hint="Ex.: Inauguração do Ginásio Municipal" required>
          <input v-model="form.titulo" required :class="inputClass" placeholder="Qual fato a cidade deve lembrar?" maxlength="255" />
        </MemoriaField>
        <MemoriaField label="Endereço da página (slug)" hint="Gerado do título. Edite se quiser um link curto. Se já existir, o sistema acrescenta um número.">
          <input v-model="form.slug" :class="inputClass" placeholder="deixe em branco para gerar automaticamente" maxlength="255" />
        </MemoriaField>

        <MemoriaField label="Tipo de fato" hint="Ajuda o público a filtrar a linha do tempo. Se faltar um tipo, inclua — ele aparece nos próximos cadastros.">
          <MemoriaChoicePicker
            v-model="form.tipo"
            grupo="marco_tipo"
            :defaults="tipoOptions"
            :hidden-ids="['marco']"
            add-label="Novo tipo de fato"
            placeholder="Ex.: Festival, recorde escolar..."
          />
        </MemoriaField>

        <MemoriaField label="Resumo curto" hint="Uma ou duas frases para o card da linha do tempo. Máx. 500 caracteres.">
          <textarea v-model="form.resumo" rows="2" maxlength="500" :class="inputClass" class="!min-h-[4.5rem]" placeholder="Em 1998 a cidade parou para ver a final..." />
          <p class="text-xs text-gray-400 mt-1 text-right">{{ (form.resumo || '').length }}/500</p>
        </MemoriaField>
      </section>

      <section class="bg-white p-5 sm:p-6 rounded-2xl border border-gray-200 shadow-sm space-y-5">
        <h2 class="text-sm font-bold uppercase tracking-wide text-gray-500">Quando e onde</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <MemoriaField label="Data completa" hint="Use se souber o dia. O ano é preenchido sozinho.">
            <input v-model="form.data_evento" type="date" :class="inputClass" @change="syncYear" />
          </MemoriaField>
          <MemoriaField label="Só o ano" hint="Use se não souber o dia. Ex.: 1998.">
            <input v-model="form.ano" type="number" min="1900" :max="currentYear" :class="inputClass" :disabled="!!form.data_evento" placeholder="1998" />
          </MemoriaField>
          <MemoriaField label="Modalidade" hint="Futebol, vôlei, atletismo...">
            <select v-model="form.modalidade_id" :class="inputClass">
              <option value="">Não se aplica / várias</option>
              <option v-for="m in modalidades" :key="m.id" :value="String(m.id)">{{ m.nome }}</option>
            </select>
          </MemoriaField>
          <MemoriaField label="Local histórico" hint="Cadastre ginásios e estádios na aba Locais.">
            <select v-model="form.local_id" :class="inputClass">
              <option value="">Ainda não identificado</option>
              <option v-for="l in locais" :key="l.id" :value="String(l.id)">{{ l.nome }}</option>
            </select>
          </MemoriaField>
        </div>
      </section>

      <section class="bg-white p-5 sm:p-6 rounded-2xl border border-gray-200 shadow-sm space-y-5">
        <h2 class="text-sm font-bold uppercase tracking-wide text-gray-500">História e pessoas</h2>
        <MemoriaFileDrop
          label="Foto de capa"
          hint="Aparece no topo da página pública. JPG, PNG ou WEBP, até 20 MB."
          empty-title="Enviar foto de capa"
          empty-subtitle="Uma imagem forte do fato histórico"
          :existing-url="existingCapa"
          @change="onFile"
        />
        <MemoriaField label="Texto da história" hint="Conte o contexto, o resultado e por que isso importa para a cidade.">
          <textarea v-model="form.conteudo" rows="8" :class="inputClass" class="!min-h-[10rem]" placeholder="O que aconteceu, quem estava lá e o que mudou depois..." />
        </MemoriaField>
        <MemoriaField label="Quem fez parte" hint="Clique para marcar atletas, técnicos ou dirigentes já cadastrados.">
          <p v-if="personalidades.length === 0" class="text-sm text-gray-500">
            Nenhuma personalidade cadastrada ainda.
            <router-link to="/admin/memoria/personalidades/nova" class="text-blue-700 font-semibold">Cadastrar agora</router-link>
          </p>
          <div v-else class="flex flex-wrap gap-2">
            <button
              v-for="p in personalidades"
              :key="p.id"
              type="button"
              class="px-3 py-2 rounded-full text-sm font-medium border min-h-11"
              :class="form.personalidade_ids.includes(String(p.id)) ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 border-gray-200 hover:border-blue-300'"
              @click="togglePessoa(p.id)"
            >
              {{ p.nome }}
            </button>
          </div>
        </MemoriaField>
        <MemoriaField label="Equipes históricas" hint="Times da época, não as equipes do campeonato atual.">
          <p v-if="equipes.length === 0" class="text-sm text-gray-500">Cadastre equipes na aba Equipes da Memória.</p>
          <div v-else class="flex flex-wrap gap-2">
            <button
              v-for="e in equipes"
              :key="e.id"
              type="button"
              class="px-3 py-2 rounded-full text-sm font-medium border min-h-11"
              :class="form.equipe_ids.includes(String(e.id)) ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 border-gray-200 hover:border-blue-300'"
              @click="toggleEquipe(e.id)"
            >
              {{ e.nome }}
            </button>
          </div>
        </MemoriaField>
      </section>

      <section class="bg-gray-50 p-5 sm:p-6 rounded-2xl border border-gray-200 grid sm:grid-cols-2 gap-4">
        <label class="flex gap-3 items-start p-3 bg-white rounded-xl border border-gray-200 cursor-pointer">
          <input v-model="form.publicado" type="checkbox" class="mt-1 rounded border-gray-300 text-blue-600 focus:ring-blue-500 w-5 h-5" />
          <span>
            <span class="block font-semibold text-gray-800">Publicar no portal</span>
            <span class="block text-sm text-gray-500">Desmarcado fica só como rascunho interno.</span>
          </span>
        </label>
        <label class="flex gap-3 items-start p-3 bg-white rounded-xl border border-gray-200 cursor-pointer">
          <input v-model="form.destaque" type="checkbox" class="mt-1 rounded border-gray-300 text-yellow-500 focus:ring-yellow-500 w-5 h-5" />
          <span>
            <span class="block font-semibold text-gray-800">Destacar na home</span>
            <span class="block text-sm text-gray-500">Aparece no bloco Memória da página inicial.</span>
          </span>
        </label>
      </section>

      <div v-if="error" class="text-red-600 text-sm bg-red-50 border border-red-100 rounded-xl px-4 py-3">{{ error }}</div>
      <div class="flex flex-wrap gap-3">
        <button class="bg-blue-600 text-white px-5 py-3 rounded-xl font-semibold min-h-11 disabled:opacity-50" :disabled="saving">
          {{ saving ? 'Salvando...' : (isEdit ? 'Salvar alterações' : 'Publicar fato') }}
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
import MemoriaFileDrop from '../components/MemoriaFileDrop.vue';
import MemoriaChoicePicker from '../components/MemoriaChoicePicker.vue';
import { inputClass, yearFromDate } from '../utils/memoriaLabels';

const tipoOptions = [
  { id: 'conquista', label: 'Conquista', hint: 'Título, medalha, recorde' },
  { id: 'campeonato', label: 'Campeonato', hint: 'Edição ou final histórica' },
  { id: 'inauguracao', label: 'Inauguração', hint: 'Ginásio, campo, pista' },
  { id: 'atleta', label: 'Pessoa', hint: 'Ídolo ou geração de ouro' },
  { id: 'marco', label: 'Outro fato', hint: 'Qualquer marco da cidade' },
];

const route = useRoute();
const router = useRouter();
const isEdit = !!route.params.id;
const currentYear = new Date().getFullYear();
const saving = ref(false);
const error = ref('');
const file = ref(null);
const existingCapa = ref('');
const modalidades = ref([]);
const locais = ref([]);
const personalidades = ref([]);
const equipes = ref([]);
const form = ref({
  titulo: '',
  slug: '',
  resumo: '',
  conteudo: '',
  data_evento: '',
  ano: '',
  tipo: 'conquista',
  modalidade_id: '',
  local_id: '',
  personalidade_ids: [],
  equipe_ids: [],
  destaque: false,
  publicado: true,
});

const onFile = (next) => { file.value = next; };
const syncYear = () => {
  if (form.value.data_evento) form.value.ano = yearFromDate(form.value.data_evento);
};
const togglePessoa = (id) => {
  const key = String(id);
  const list = form.value.personalidade_ids;
  form.value.personalidade_ids = list.includes(key) ? list.filter((x) => x !== key) : [...list, key];
};
const toggleEquipe = (id) => {
  const key = String(id);
  const list = form.value.equipe_ids;
  form.value.equipe_ids = list.includes(key) ? list.filter((x) => x !== key) : [...list, key];
};

const buildFormData = () => {
  const fd = new FormData();
  Object.entries(form.value).forEach(([key, value]) => {
    if (key === 'personalidade_ids' || key === 'equipe_ids') {
      fd.append(key, JSON.stringify(value || []));
      return;
    }
    if (typeof value === 'boolean') {
      fd.append(key, value ? '1' : '0');
      return;
    }
    fd.append(key, value ?? '');
  });
  if (file.value) fd.append('capa', file.value);
  return fd;
};

const submit = async () => {
  saving.value = true;
  error.value = '';
  try {
    if (isEdit) await memoriaService.updateMarco(route.params.id, buildFormData());
    else await memoriaService.createMarco(buildFormData());
    router.push('/admin/memoria');
  } catch (e) {
    error.value = e.response?.data?.message || 'Não foi possível salvar. Confira os campos e tente de novo.';
  } finally {
    saving.value = false;
  }
};

onMounted(async () => {
  const [mods, locs, pessoas, eqs] = await Promise.all([
    modalidadeService.getAll(),
    memoriaService.locais(),
    memoriaService.personalidades(),
    memoriaService.equipes().catch(() => ({ data: [] })),
  ]);
  modalidades.value = mods.data?.data || mods.data || [];
  locais.value = locs.data || [];
  personalidades.value = pessoas.data || [];
  equipes.value = eqs.data || [];
  if (isEdit) {
    const { data } = await memoriaService.getMarco(route.params.id);
    form.value = {
      titulo: data.titulo,
      slug: data.slug || '',
      resumo: data.resumo || '',
      conteudo: data.conteudo || '',
      data_evento: data.data_evento ? String(data.data_evento).slice(0, 10) : '',
      ano: data.ano || '',
      tipo: data.tipo || 'marco',
      modalidade_id: data.modalidade_id ? String(data.modalidade_id) : '',
      local_id: data.local_id ? String(data.local_id) : '',
      personalidade_ids: (data.personalidades || []).map((p) => String(p.id)),
      equipe_ids: (data.equipes || []).map((e) => String(e.id)),
      destaque: !!data.destaque,
      publicado: !!data.publicado,
    };
    existingCapa.value = data.capa_url || '';
  }
});
</script>
