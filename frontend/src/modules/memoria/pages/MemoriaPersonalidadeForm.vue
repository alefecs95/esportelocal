<template>
  <div class="p-4 sm:p-6 w-full">
    <router-link to="/admin/memoria" class="text-sm font-semibold text-blue-700 hover:text-blue-900">← Voltar para a Memória</router-link>
    <h1 class="text-2xl font-bold text-gray-800 mt-3">{{ isEdit ? 'Editar personalidade' : 'Nova personalidade' }}</h1>
    <p class="text-gray-500 mt-1">Atleta, técnico ou dirigente que a cidade reconhece. O nome é o único campo obrigatório.</p>

    <form class="mt-6 space-y-6" @submit.prevent="submit">
      <section class="bg-white p-5 sm:p-6 rounded-2xl border border-gray-200 shadow-sm space-y-5">
        <MemoriaFileDrop
          label="Foto de retrato"
          hint="Foto nítida do rosto ou em ação. Aparece no Hall da cidade."
          empty-title="Enviar retrato"
          empty-subtitle="JPG, PNG ou WEBP"
          :existing-url="existingFoto"
          @change="onFile"
        />
        <MemoriaField label="Nome completo" required>
          <input v-model="form.nome" required :class="inputClass" placeholder="Como a cidade conhece essa pessoa" maxlength="255" />
        </MemoriaField>
        <MemoriaField label="Apelido" hint="Como o público chamava. Ex.: Garrincha da Vila.">
          <input v-model="form.apelido" :class="inputClass" placeholder="Opcional" maxlength="120" />
        </MemoriaField>
        <MemoriaField label="Endereço da página (slug)" hint="Gerado do nome. Edite se quiser um link curto e estável.">
          <input v-model="form.slug" :class="inputClass" placeholder="deixe em branco para gerar automaticamente" maxlength="255" />
        </MemoriaField>
        <MemoriaField label="Como atuou" hint="Escolha o papel principal. Se não estiver na lista, inclua — fica disponível da próxima vez.">
          <MemoriaChoicePicker
            v-model="form.papel"
            grupo="papel"
            :defaults="papelOptions"
            :hidden-ids="['outro']"
            grid-class="grid grid-cols-2 gap-2"
            add-label="Outra atuação"
            placeholder="Ex.: Árbitro, mascote, torcida..."
          />
        </MemoriaField>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <MemoriaField label="Modalidade principal">
            <select v-model="form.modalidade_id" :class="inputClass">
              <option value="">Não se aplica / várias</option>
              <option v-for="m in modalidades" :key="m.id" :value="String(m.id)">{{ m.nome }}</option>
            </select>
          </MemoriaField>
          <MemoriaField label="Nascimento" hint="Opcional. Só o ano também pode ir na biografia.">
            <input v-model="form.data_nascimento" type="date" :class="inputClass" />
          </MemoriaField>
          <MemoriaField label="Falecimento" hint="Preencha apenas se for o caso.">
            <input v-model="form.data_falecimento" type="date" :class="inputClass" />
          </MemoriaField>
        </div>
        <MemoriaField label="Mini-biografia" hint="3 a 8 linhas: origem, conquistas e por que essa pessoa importa.">
          <textarea v-model="form.mini_bio" rows="7" :class="inputClass" class="!min-h-[9rem]" placeholder="Começou nas escolinhas do bairro, foi campeã municipal em... " />
        </MemoriaField>
      </section>

      <section class="bg-gray-50 p-5 sm:p-6 rounded-2xl border border-gray-200 grid sm:grid-cols-2 gap-4">
        <label class="flex gap-3 items-start p-3 bg-white rounded-xl border border-gray-200 cursor-pointer">
          <input v-model="form.publicado" type="checkbox" class="mt-1 rounded border-gray-300 text-blue-600 w-5 h-5" />
          <span>
            <span class="block font-semibold text-gray-800">Publicar no portal</span>
            <span class="block text-sm text-gray-500">Fica visível na Memória pública.</span>
          </span>
        </label>
        <label class="flex gap-3 items-start p-3 bg-white rounded-xl border border-gray-200 cursor-pointer">
          <input v-model="form.destaque" type="checkbox" class="mt-1 rounded border-gray-300 text-yellow-500 w-5 h-5" />
          <span>
            <span class="block font-semibold text-gray-800">Hall da cidade</span>
            <span class="block text-sm text-gray-500">Aparece em destaque na página da Memória.</span>
          </span>
        </label>
      </section>

      <MemoriaQrCard
        v-if="isEdit && form.slug"
        title="QR da personalidade"
        hint="Imprima e afixe no espaço. Quem apontar a câmera abre esta página da Memória."
        :path="`/memoria/personalidades/${form.slug}`"
        :unpublished="!form.publicado"
      />

      <div v-if="error" class="text-red-600 text-sm bg-red-50 border border-red-100 rounded-xl px-4 py-3">{{ error }}</div>
      <div class="flex flex-wrap gap-3">
        <button class="bg-blue-600 text-white px-5 py-3 rounded-xl font-semibold min-h-11 disabled:opacity-50" :disabled="saving">
          {{ saving ? 'Salvando...' : 'Salvar personalidade' }}
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
import MemoriaChoicePicker from '../components/MemoriaChoicePicker.vue';
import { inputClass } from '../utils/memoriaLabels';

const papelOptions = [
  { id: 'atleta', label: 'Atleta' },
  { id: 'tecnico', label: 'Técnico(a)' },
  { id: 'dirigente', label: 'Dirigente' },
  { id: 'outro', label: 'Outra atuação' },
];

const route = useRoute();
const router = useRouter();
const isEdit = !!route.params.id;
const saving = ref(false);
const error = ref('');
const file = ref(null);
const existingFoto = ref('');
const modalidades = ref([]);
const form = ref({
  nome: '',
  apelido: '',
  slug: '',
  papel: 'atleta',
  mini_bio: '',
  data_nascimento: '',
  data_falecimento: '',
  modalidade_id: '',
  destaque: false,
  publicado: true,
});

const onFile = (next) => { file.value = next; };

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
    error.value = e.response?.data?.message || 'Não foi possível salvar a personalidade.';
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
      apelido: data.apelido || '',
      slug: data.slug || '',
      papel: data.papel || 'atleta',
      mini_bio: data.mini_bio || '',
      data_nascimento: data.data_nascimento ? String(data.data_nascimento).slice(0, 10) : '',
      data_falecimento: data.data_falecimento ? String(data.data_falecimento).slice(0, 10) : '',
      modalidade_id: data.modalidade_id ? String(data.modalidade_id) : '',
      destaque: !!data.destaque,
      publicado: !!data.publicado,
    };
    existingFoto.value = data.foto_url || '';
  }
});
</script>
