<template>
  <div class="p-6 max-w-3xl">
    <h1 class="text-2xl font-bold text-gray-800 mb-1">{{ isEdit ? 'Editar acervo' : 'Novo item do acervo' }}</h1>
    <p class="text-gray-500 mb-6">Foto, vídeo ou documento digitalizado, com metadados de curadoria.</p>

    <form class="bg-white p-6 rounded-xl border border-gray-200 space-y-5" @submit.prevent="submit">
      <div>
        <label class="block text-sm font-medium mb-1">Título</label>
        <input v-model="form.titulo" required class="w-full rounded-lg border-gray-300" />
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium mb-1">Tipo</label>
          <select v-model="form.tipo" class="w-full rounded-lg border-gray-300">
            <option value="foto">Foto</option>
            <option value="video">Vídeo</option>
            <option value="documento">Documento</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Arquivo</label>
          <input type="file" :required="!isEdit" accept="image/*,video/mp4,video/webm,application/pdf" @change="onFile" />
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Data aproximada</label>
          <input v-model="form.data_aproximada" type="date" class="w-full rounded-lg border-gray-300" />
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Ano</label>
          <input v-model="form.ano" type="number" min="1900" class="w-full rounded-lg border-gray-300" />
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Modalidade</label>
          <select v-model="form.modalidade_id" class="w-full rounded-lg border-gray-300">
            <option value="">—</option>
            <option v-for="m in modalidades" :key="m.id" :value="String(m.id)">{{ m.nome }}</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Local</label>
          <select v-model="form.local_id" class="w-full rounded-lg border-gray-300">
            <option value="">—</option>
            <option v-for="l in locais" :key="l.id" :value="String(l.id)">{{ l.nome }}</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Vincular a marco</label>
          <select v-model="form.marco_id" class="w-full rounded-lg border-gray-300">
            <option value="">—</option>
            <option v-for="m in marcos" :key="m.id" :value="String(m.id)">{{ m.titulo }}</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Vincular a personalidade</label>
          <select v-model="form.personalidade_id" class="w-full rounded-lg border-gray-300">
            <option value="">—</option>
            <option v-for="p in personalidades" :key="p.id" :value="String(p.id)">{{ p.nome }}</option>
          </select>
        </div>
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">Pessoas envolvidas</label>
        <input v-model="form.pessoas_envolvidas" class="w-full rounded-lg border-gray-300" />
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">Fonte / doação</label>
        <input v-model="form.fonte" class="w-full rounded-lg border-gray-300" />
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">Descrição</label>
        <textarea v-model="form.descricao" rows="4" class="w-full rounded-lg border-gray-300"></textarea>
      </div>
      <label class="flex items-center gap-2"><input v-model="form.publicado" type="checkbox" /> Publicado</label>
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
const marcos = ref([]);
const personalidades = ref([]);
const form = ref({
  titulo: '',
  tipo: 'foto',
  descricao: '',
  data_aproximada: '',
  ano: '',
  modalidade_id: '',
  local_id: '',
  marco_id: '',
  personalidade_id: '',
  pessoas_envolvidas: '',
  fonte: '',
  publicado: true,
});

const onFile = (e) => { file.value = e.target.files?.[0] || null; };

const submit = async () => {
  saving.value = true;
  error.value = '';
  try {
    const fd = new FormData();
    Object.entries(form.value).forEach(([key, value]) => {
      if (typeof value === 'boolean') fd.append(key, value ? '1' : '0');
      else fd.append(key, value ?? '');
    });
    if (file.value) fd.append('arquivo', file.value);
    if (isEdit) await memoriaService.updateAcervo(route.params.id, fd);
    else await memoriaService.createAcervo(fd);
    router.push('/admin/memoria');
  } catch (e) {
    error.value = e.response?.data?.message || 'Erro ao salvar acervo.';
  } finally {
    saving.value = false;
  }
};

onMounted(async () => {
  const [mods, locs, m, p] = await Promise.all([
    modalidadeService.getAll(),
    memoriaService.locais(),
    memoriaService.marcos(),
    memoriaService.personalidades(),
  ]);
  modalidades.value = mods.data?.data || mods.data || [];
  locais.value = locs.data || [];
  marcos.value = m.data || [];
  personalidades.value = p.data || [];
  if (isEdit) {
    const { data } = await memoriaService.getAcervo(route.params.id);
    form.value = {
      titulo: data.titulo,
      tipo: data.tipo || 'foto',
      descricao: data.descricao || '',
      data_aproximada: data.data_aproximada ? String(data.data_aproximada).slice(0, 10) : '',
      ano: data.ano || '',
      modalidade_id: data.modalidade_id ? String(data.modalidade_id) : '',
      local_id: data.local_id ? String(data.local_id) : '',
      marco_id: data.marco_id ? String(data.marco_id) : '',
      personalidade_id: data.personalidade_id ? String(data.personalidade_id) : '',
      pessoas_envolvidas: data.pessoas_envolvidas || '',
      fonte: data.fonte || '',
      publicado: !!data.publicado,
    };
  }
});
</script>
