<template>
  <div class="p-6 max-w-5xl">
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-gray-800">Nova Notícia</h1>
      <p class="text-gray-500 mt-1">Publique uma notícia no portal.</p>
    </div>

    <form @submit.prevent="submit" class="bg-white p-6 rounded-xl shadow-sm border border-gray-200 space-y-6">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Título</label>
          <input v-model="form.titulo" type="text" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Categoria</label>
          <div class="flex gap-2">
            <select v-model="form.categoria_id" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
              <option value="">Sem categoria</option>
              <option v-for="cat in categorias" :key="cat.id" :value="cat.id">{{ cat.nome }}</option>
            </select>
            <button type="button" @click="createCategoria" class="bg-gray-100 px-3 rounded-lg border border-gray-300 hover:bg-gray-200" title="Nova Categoria">
              +
            </button>
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Imagem de capa</label>
          <input @change="onCapaChange" type="file" accept="image/*" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" />
          <p class="text-xs text-gray-500 mt-1">Formatos: JPG, PNG, WEBP • Máx: 20MB.</p>
          <div v-if="capaPreview" class="mt-3">
            <img :src="capaPreview" class="w-full h-40 object-cover rounded-lg border" alt="Capa" />
          </div>
        </div>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Conteúdo</label>
        <p class="text-xs text-gray-500 mb-2">Dentro do editor, os blocos Galeria e Carrossel aceitam fotos e vídeos. Você pode inserir vários blocos separados.</p>
        <NewsEditor ref="newsEditorRef" v-model="form.conteudo" />
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-6 p-4 bg-gray-50 rounded-lg border border-gray-200">
        <div class="space-y-4">
          <div>
            <label class="flex items-center gap-2 mb-2 font-medium text-gray-700">
              <input v-model="form.publicado" type="checkbox" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 w-5 h-5" />
              Publicar Notícia
            </label>
            <p class="text-sm text-gray-500 ml-7">Se desmarcado, ficará como rascunho.</p>
          </div>
          
          <div>
            <label class="flex items-center gap-2 mb-2 font-medium text-gray-700">
              <input v-model="form.destaque" type="checkbox" class="rounded border-gray-300 text-yellow-500 focus:ring-yellow-500 w-5 h-5" />
              Destacar na Home
            </label>
            <p class="text-sm text-gray-500 ml-7">Exibe a notícia com destaque na página inicial.</p>
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Data/Hora da Publicação</label>
          <input 
            v-model="form.publicado_em" 
            type="datetime-local" 
            class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 disabled:bg-gray-100 disabled:text-gray-400" 
            :disabled="!form.publicado"
          />
          <p class="text-xs text-gray-500 mt-1">Deixe em branco para publicar agora.</p>
        </div>
      </div>

      <!-- SEO / Meta Data -->
      <div class="p-4 bg-gray-50 rounded-lg border border-gray-200">
        <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider mb-4">Otimização para Buscas (SEO)</h3>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Descrição Meta (Resumo)</label>
            <textarea 
                v-model="form.meta_description" 
                rows="2" 
                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" 
                placeholder="Breve resumo para aparecer no Google e redes sociais (máx 160 caracteres)"
                maxlength="160"
            ></textarea>
            <div class="text-right text-xs text-gray-500 mt-1">
                {{ form.meta_description?.length || 0 }}/160
            </div>
        </div>
      </div>

      <div class="flex gap-2">
        <button type="submit" :disabled="submitting" class="bg-blue-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-blue-700 transition-colors disabled:opacity-50">
          {{ submitting ? 'Salvando...' : 'Salvar Notícia' }}
        </button>
        <router-link to="/admin/noticias" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg font-medium hover:bg-gray-200 transition-colors">Cancelar</router-link>
      </div>

      <div v-if="error" class="bg-red-100 text-red-700 p-3 rounded">{{ error }}</div>
      <div v-if="success" class="bg-green-100 text-green-700 p-3 rounded">Notícia salva com sucesso.</div>
    </form>
  </div>
</template>

<script setup>
import { reactive, ref, watch, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import noticiaService from '../services/noticiaService';
import categoriaService from '../services/categoriaService';
import NewsEditor from '@/components/NewsEditor.vue';

const router = useRouter();
const newsEditorRef = ref(null);
const form = reactive({
  titulo: '',
  conteudo: {},
  publicado: true,
  publicado_em: '',
  imagem_capa: null,
  categoria_id: '',
  meta_description: '',
  destaque: false
});

const categorias = ref([]);
const loadCategorias = async () => {
  try {
    const { data } = await categoriaService.getAll();
    categorias.value = data;
  } catch (e) {
    console.error('Erro ao carregar categorias', e);
  }
};

const createCategoria = async () => {
    const nome = prompt('Nome da nova categoria:');
    if (!nome) return;
    try {
        const { data } = await categoriaService.create({ nome });
        categorias.value.push(data);
        form.categoria_id = data.id; // Auto select
    } catch (e) {
        alert('Erro ao criar categoria');
    }
};

onMounted(loadCategorias);

// Watch publicado changes to handle date logic if needed
watch(() => form.publicado, (newVal) => {
  if (!newVal) {
    // If draft, maybe clear date or just keep it? 
    // Usually keep it in case they re-enable.
  }
});

const capaPreview = ref('');

const submitting = ref(false);
const error = ref('');
const success = ref(false);

const onCapaChange = (e) => {
  const file = e.target.files[0] || null;
  if (file && file.size > 20 * 1024 * 1024) {
    alert('A imagem de capa deve ter no máximo 20MB.');
    e.target.value = '';
    return;
  }
  form.imagem_capa = file;
  capaPreview.value = file ? URL.createObjectURL(file) : '';
};

const submit = async () => {
  submitting.value = true;
  error.value = '';
  success.value = false;
  try {
    const conteudoAtual = await newsEditorRef.value?.forceSave?.();
    if (conteudoAtual) {
      form.conteudo = conteudoAtual;
    }

    const fd = new FormData();
    fd.append('titulo', form.titulo);
    fd.append('categoria_id', form.categoria_id || '');
    fd.append('meta_description', form.meta_description || '');
    fd.append('conteudo', JSON.stringify(form.conteudo));
    fd.append('publicado', form.publicado ? '1' : '0');
    fd.append('destaque', form.destaque ? '1' : '0');
    
    if (form.publicado && form.publicado_em) {
      // Format datetime-local to backend format (Y-m-d H:i:s)
      fd.append('publicado_em', form.publicado_em.replace('T', ' '));
    }

    if (form.imagem_capa) {
      fd.append('imagem_capa', form.imagem_capa);
    }
    
    await noticiaService.create(fd);
    success.value = true;
    setTimeout(() => router.push('/admin/noticias'), 800);
  } catch (e) {
    console.error(e);
    if (e.response && e.response.data && e.response.data.message) {
      error.value = e.response.data.message;
      if (e.response.data.errors) {
        error.value += ' ' + Object.values(e.response.data.errors).join(', ');
      }
    } else {
      error.value = 'Erro ao publicar notícia.';
    }
  } finally {
    submitting.value = false;
  }
};
</script>
