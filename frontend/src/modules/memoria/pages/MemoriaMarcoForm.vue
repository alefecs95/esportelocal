<template>
  <div class="p-6 max-w-4xl">
    <h1 class="text-2xl font-bold text-gray-800 mb-1">{{ isEdit ? 'Editar marco' : 'Novo marco' }}</h1>
    <p class="text-gray-500 mb-6">Evento histórico da linha do tempo.</p>

    <form class="bg-white p-6 rounded-xl border border-gray-200 space-y-5" @submit.prevent="submit">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="md:col-span-2">
          <label class="block text-sm font-medium mb-1">Título</label>
          <input v-model="form.titulo" required class="w-full rounded-lg border-gray-300" />
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Data do evento</label>
          <input v-model="form.data_evento" type="date" class="w-full rounded-lg border-gray-300" />
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Ano</label>
          <input v-model="form.ano" type="number" min="1900" class="w-full rounded-lg border-gray-300" />
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Tipo</label>
          <select v-model="form.tipo" class="w-full rounded-lg border-gray-300">
            <option value="conquista">Conquista</option>
            <option value="campeonato">Campeonato</option>
            <option value="inauguracao">Inauguração</option>
            <option value="atleta">Atleta em destaque</option>
            <option value="marco">Outro marco</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Modalidade</label>
          <select v-model="form.modalidade_id" class="w-full rounded-lg border-gray-300">
            <option value="">Sem modalidade</option>
            <option v-for="m in modalidades" :key="m.id" :value="String(m.id)">{{ m.nome }}</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Local histórico</label>
          <select v-model="form.local_id" class="w-full rounded-lg border-gray-300">
            <option value="">Sem local</option>
            <option v-for="l in locais" :key="l.id" :value="String(l.id)">{{ l.nome }}</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Capa</label>
          <input type="file" accept="image/*" @change="onFile" />
        </div>
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">Resumo</label>
        <textarea v-model="form.resumo" rows="2" maxlength="500" class="w-full rounded-lg border-gray-300"></textarea>
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">História</label>
        <textarea v-model="form.conteudo" rows="6" class="w-full rounded-lg border-gray-300"></textarea>
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">Personalidades ligadas</label>
        <select v-model="form.personalidade_ids" multiple class="w-full rounded-lg border-gray-300 min-h-[6rem]">
          <option v-for="p in personalidades" :key="p.id" :value="String(p.id)">{{ p.nome }}</option>
        </select>
      </div>
      <label class="flex items-center gap-2">
        <input v-model="form.destaque" type="checkbox" /> Destaque
      </label>
      <label class="flex items-center gap-2">
        <input v-model="form.publicado" type="checkbox" /> Publicado
      </label>
      <div v-if="error" class="text-red-600 text-sm">{{ error }}</div>
      <div class="flex gap-3">
        <button class="bg-blue-600 text-white px-4 py-2 rounded-lg font-medium" :disabled="saving">{{ saving ? 'Salvando...' : 'Salvar' }}</button>
        <router-link to="/admin/memoria" class="px-4 py-2 rounded-lg border">Cancelar</router-link>
      </div>
    </form>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import memoriaService from '../services/memoriaService';
import modalidadeService from '@/modules/modalidades/services/modalidadeService';

const route = useRoute();
const router = useRouter();
const isEdit = !!route.params.id;
const saving = ref(false);
const error = ref('');
const file = ref(null);
const modalidades = ref([]);
const locais = ref([]);
const personalidades = ref([]);
const form = ref({
  titulo: '',
  resumo: '',
  conteudo: '',
  data_evento: '',
  ano: '',
  tipo: 'conquista',
  modalidade_id: '',
  local_id: '',
  personalidade_ids: [],
  destaque: false,
  publicado: true,
});

const onFile = (e) => { file.value = e.target.files?.[0] || null; };

const buildFormData = () => {
  const fd = new FormData();
  Object.entries(form.value).forEach(([key, value]) => {
    if (key === 'personalidade_ids') {
      fd.append('personalidade_ids', JSON.stringify(value || []));
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
    const fd = buildFormData();
    if (isEdit) await memoriaService.updateMarco(route.params.id, fd);
    else await memoriaService.createMarco(fd);
    router.push('/admin/memoria');
  } catch (e) {
    error.value = e.response?.data?.message || 'Erro ao salvar marco.';
  } finally {
    saving.value = false;
  }
};

onMounted(async () => {
  const [mods, locs, pessoas] = await Promise.all([
    modalidadeService.getAll(),
    memoriaService.locais(),
    memoriaService.personalidades(),
  ]);
  modalidades.value = mods.data?.data || mods.data || [];
  locais.value = locs.data || [];
  personalidades.value = pessoas.data || [];
  if (isEdit) {
    const { data } = await memoriaService.getMarco(route.params.id);
    form.value = {
      titulo: data.titulo,
      resumo: data.resumo || '',
      conteudo: data.conteudo || '',
      data_evento: data.data_evento ? String(data.data_evento).slice(0, 10) : '',
      ano: data.ano || '',
      tipo: data.tipo || 'marco',
      modalidade_id: data.modalidade_id ? String(data.modalidade_id) : '',
      local_id: data.local_id ? String(data.local_id) : '',
      personalidade_ids: (data.personalidades || []).map((p) => String(p.id)),
      destaque: !!data.destaque,
      publicado: !!data.publicado,
    };
  }
});
</script>
