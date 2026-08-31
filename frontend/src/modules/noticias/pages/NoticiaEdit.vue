<template>
  <div class="p-6 max-w-5xl">
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-gray-800">Editar Notícia</h1>
      <p class="text-gray-500 mt-1">Atualize as informações da notícia.</p>
    </div>

    <div v-if="loading" class="flex justify-center py-12">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
    </div>

    <form v-else @submit.prevent="submit" class="bg-white p-6 rounded-xl shadow-sm border border-gray-200 space-y-6">
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
          <div v-if="capaPreview" class="mt-3 relative group">
            <img :src="capaPreview" class="w-full h-40 object-cover rounded-lg border" alt="Capa" />
            <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center rounded-lg">
                <span class="text-white text-xs">Nova imagem selecionada</span>
            </div>
          </div>
          <div v-else-if="currentCapaUrl" class="mt-3 relative group">
            <img :src="currentCapaUrl" class="w-full h-40 object-cover rounded-lg border" alt="Capa Atual" />
             <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center rounded-lg">
                <span class="text-white text-xs">Imagem atual</span>
            </div>
          </div>
        </div>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Conteúdo</label>
        <p class="text-xs text-gray-500 mb-2">Use os blocos Galeria e Carrossel dentro do editor para inserir fotos e vídeos. Você pode criar vários blocos separados.</p>
        <NewsEditor ref="newsEditorRef" v-model="form.conteudo" />
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-6 p-4 bg-gray-50 rounded-lg border border-gray-200">
        <div>
          <label class="flex items-center gap-2 mb-2 font-medium text-gray-700">
            <input v-model="form.publicado" type="checkbox" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 w-5 h-5" />
            Publicar Notícia
          </label>
          <p class="text-sm text-gray-500 ml-7">Se desmarcado, ficará como rascunho.</p>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Data/Hora da Publicação</label>
          <input 
            v-model="form.publicado_em" 
            type="datetime-local" 
            class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 disabled:bg-gray-100 disabled:text-gray-400" 
            :disabled="!form.publicado"
          />
          <p class="text-xs text-gray-500 mt-1">Deixe em branco para manter a data original ou publicar agora.</p>
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

      <!-- Comments Management -->
      <div class="p-4 bg-gray-50 rounded-lg border border-gray-200">
        <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider mb-4">Gerenciar Comentários</h3>
        
        <div v-if="loadingComments" class="text-center py-4 text-gray-500">Carregando comentários...</div>
        <div v-else-if="comments.length === 0" class="text-center py-4 text-gray-500 bg-white rounded border border-gray-200">
            Nenhum comentário ainda.
        </div>
        <div v-else class="space-y-3">
            <div v-for="comment in comments" :key="comment.id" class="bg-white p-3 rounded border border-gray-200 flex justify-between items-start">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="font-bold text-sm text-gray-900">{{ comment.user?.name || comment.nome || 'Anônimo' }}</span>
                        <span class="text-xs text-gray-500">{{ new Date(comment.created_at).toLocaleString() }}</span>
                    </div>
                    <p class="text-sm text-gray-700">{{ comment.conteudo }}</p>
                </div>
                <button 
                    type="button" 
                    @click="deleteComment(comment.id)" 
                    class="text-red-600 hover:text-red-800 text-xs font-medium px-2 py-1 rounded hover:bg-red-50"
                >
                    Excluir
                </button>
            </div>
        </div>
      </div>

      <div class="flex gap-2">
        <button type="submit" :disabled="submitting" class="bg-blue-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-blue-700 transition-colors disabled:opacity-50">
          {{ submitting ? 'Salvando...' : 'Atualizar Notícia' }}
        </button>
        <router-link to="/admin/noticias" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg font-medium hover:bg-gray-200 transition-colors">Cancelar</router-link>
      </div>

      <div v-if="error" class="bg-red-100 text-red-700 p-3 rounded">{{ error }}</div>
      <div v-if="success" class="bg-green-100 text-green-700 p-3 rounded">Notícia atualizada com sucesso.</div>
    </form>
  </div>
</template>

<script setup>
import { reactive, ref, onMounted, watch } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import noticiaService from '../services/noticiaService';
import categoriaService from '../services/categoriaService';
import NewsEditor from '@/components/NewsEditor.vue';

const router = useRouter();
const route = useRoute();
const loading = ref(true);
const newsEditorRef = ref(null);

const form = reactive({
  titulo: '',
  conteudo: {},
  publicado: true,
  publicado_em: '',
  imagem_capa: null,
  categoria_id: '',
  meta_description: ''
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

const currentCapaUrl = ref('');
const capaPreview = ref('');
const comments = ref([]);
const loadingComments = ref(false);

const submitting = ref(false);
const error = ref('');
const success = ref(false);

const loadComments = async (id) => {
    loadingComments.value = true;
    try {
        const { data } = await noticiaService.getComentariosAdmin(id);
        comments.value = data;
    } catch (e) {
        console.error('Erro ao carregar comentários', e);
    } finally {
        loadingComments.value = false;
    }
};

const deleteComment = async (commentId) => {
    if (!confirm('Tem certeza que deseja excluir este comentário?')) return;
    try {
        await noticiaService.deleteComentario(commentId);
        comments.value = comments.value.filter(c => c.id !== commentId);
    } catch (e) {
        alert('Erro ao excluir comentário');
    }
};

const load = async () => {
  loading.value = true;
  await loadCategorias(); // Load categories first
  try {
    const { data } = await noticiaService.get(route.params.id);
    form.titulo = data.titulo;
    form.categoria_id = data.categoria_id || '';
    form.meta_description = data.meta_description || '';
    
    // Load comments
    loadComments(data.id);
    
    // Handle Content (JSON string or object)
    try {
        form.conteudo = typeof data.conteudo === 'string' ? JSON.parse(data.conteudo) : data.conteudo;
    } catch (e) {
        form.conteudo = {}; // Fallback
    }

    form.publicado = !!data.publicado;
    
    if (data.publicado_em) {
        // Format for datetime-local input: YYYY-MM-DDThh:mm
        const d = new Date(data.publicado_em);
        // Adjust to local timezone logic if needed, but simple slice works for basic needs
        // Need to be careful with timezones.
        // Quick hack for local ISO string:
        const offset = d.getTimezoneOffset() * 60000;
        const localISOTime = (new Date(d - offset)).toISOString().slice(0, 16);
        form.publicado_em = localISOTime;
    }

    currentCapaUrl.value = data.imagem_capa_url;
  } catch (e) {
    error.value = 'Erro ao carregar notícia.';
    console.error(e);
  } finally {
    loading.value = false;
  }
};

onMounted(load);

const onCapaChange = (e) => {
  const file = e.target.files[0] || null;
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
    
    if (form.publicado && form.publicado_em) {
      fd.append('publicado_em', form.publicado_em.replace('T', ' '));
    }

    if (form.imagem_capa) {
      fd.append('imagem_capa', form.imagem_capa);
    }

    
    await noticiaService.update(route.params.id, fd);
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
      error.value = 'Erro ao atualizar notícia.';
    }
  } finally {
    submitting.value = false;
  }
};
</script>
