<template>
  <div class="p-6 max-w-3xl">
    <h1 class="text-2xl font-bold text-gray-800 mb-1">{{ isEdit ? 'Editar personalidade' : 'Nova personalidade' }}</h1>
    <p class="text-gray-500 mb-6">Atleta, técnico ou dirigente que fez história.</p>

    <form class="bg-white p-6 rounded-xl border border-gray-200 space-y-5" @submit.prevent="submit">
      <div>
        <label class="block text-sm font-medium mb-1">Nome</label>
        <input v-model="form.nome" required class="w-full rounded-lg border-gray-300" />
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium mb-1">Papel</label>
          <select v-model="form.papel" class="w-full rounded-lg border-gray-300">
            <option value="atleta">Atleta</option>
            <option value="tecnico">Técnico</option>
            <option value="dirigente">Dirigente</option>
            <option value="outro">Outro</option>
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
          <label class="block text-sm font-medium mb-1">Nascimento</label>
          <input v-model="form.data_nascimento" type="date" class="w-full rounded-lg border-gray-300" />
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Falecimento</label>
          <input v-model="form.data_falecimento" type="date" class="w-full rounded-lg border-gray-300" />
        </div>
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">Foto</label>
        <input type="file" accept="image/*" @change="onFile" />
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">Mini-biografia</label>
        <textarea v-model="form.mini_bio" rows="6" class="w-full rounded-lg border-gray-300"></textarea>
      </div>
      <label class="flex items-center gap-2"><input v-model="form.destaque" type="checkbox" /> Hall da Fama / destaque</label>
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
const form = ref({
  nome: '',
  papel: 'atleta',
  mini_bio: '',
  data_nascimento: '',
  data_falecimento: '',
  modalidade_id: '',
  destaque: false,
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
    if (file.value) fd.append('foto', file.value);
    if (isEdit) await memoriaService.updatePersonalidade(route.params.id, fd);
    else await memoriaService.createPersonalidade(fd);
    router.push('/admin/memoria');
  } catch (e) {
    error.value = e.response?.data?.message || 'Erro ao salvar.';
  } finally {
    saving.value = false;
  }
};

onMounted(async () => {
  const mods = await modalidadeService.getAll();
  modalidades.value = mods.data?.data || mods.data || [];
  if (isEdit) {
    const { data } = await memoriaService.getPersonalidade(route.params.id);
    form.value = {
      nome: data.nome,
      papel: data.papel || 'atleta',
      mini_bio: data.mini_bio || '',
      data_nascimento: data.data_nascimento ? String(data.data_nascimento).slice(0, 10) : '',
      data_falecimento: data.data_falecimento ? String(data.data_falecimento).slice(0, 10) : '',
      modalidade_id: data.modalidade_id ? String(data.modalidade_id) : '',
      destaque: !!data.destaque,
      publicado: !!data.publicado,
    };
  }
});
</script>
