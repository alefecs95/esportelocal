<template>
  <div class="p-6">
    <PageHeader title="Notícias" description="Gerencie as notícias do portal.">
      <template #actions>
        <router-link
          to="/admin/noticias/nova"
          class="bg-blue-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-blue-700 transition-colors shadow-sm"
        >
          Nova Notícia
        </router-link>
      </template>
    </PageHeader>

    <div v-if="loading" class="text-center py-12">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
    </div>

    <div v-else-if="error" class="bg-red-100 text-red-700 p-4 rounded mb-4">
      {{ error }}
    </div>

    <EmptyState
      v-else-if="!noticias.length"
      title="Nenhuma notícia publicada"
      description="Crie a primeira notícia para o portal da secretaria."
    >
      <template #action>
        <router-link to="/admin/noticias/nova" class="bg-blue-600 text-white px-4 py-2 rounded-lg font-medium">Nova Notícia</router-link>
      </template>
    </EmptyState>

    <BaseTable v-else :headers="headers" :items="noticias" actions>
      <template #titulo="{ item }">
        <div class="flex flex-col">
          <span class="font-medium text-gray-900">{{ item.titulo }}</span>
          <span v-if="item.categoria" class="text-xs text-blue-500 font-bold uppercase mt-1">{{ item.categoria.nome }}</span>
        </div>
      </template>
      <template #destaque="{ item }">
        <button
          @click="toggleDestaque(item)"
          class="relative inline-flex flex-shrink-0 h-6 w-11 border-2 border-transparent rounded-full cursor-pointer transition-colors"
          :class="item.destaque ? 'bg-yellow-400' : 'bg-gray-200'"
        >
          <span
            class="pointer-events-none inline-block h-5 w-5 rounded-full bg-white shadow transform transition"
            :class="item.destaque ? 'translate-x-5' : 'translate-x-0'"
          />
        </button>
      </template>
      <template #publicado="{ item }">
        <StatusBadge :status="item.publicado ? 'publicado' : 'rascunho'" />
      </template>
      <template #publicado_em="{ item }">
        {{ item.publicado_em ? new Date(item.publicado_em).toLocaleString() : '-' }}
      </template>
      <template #actions="{ item }">
        <a :href="`/noticias/${item.slug || item.id}`" target="_blank" class="text-gray-500 hover:text-gray-800 mr-3">Ver</a>
        <router-link :to="`/admin/noticias/${item.id}/editar`" class="text-blue-600 hover:text-blue-900 font-medium mr-3">Editar</router-link>
        <button @click="deleteItem(item.id)" class="text-red-600 hover:text-red-900 font-medium">Excluir</button>
      </template>
    </BaseTable>

    <ConfirmDialog
      :is-open="confirm.isOpen.value"
      :title="confirm.title.value"
      :message="confirm.message.value"
      @confirm="confirm.confirm"
      @cancel="confirm.cancel"
    />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useToast } from 'vue-toastification';
import noticiaService from '../services/noticiaService';
import PageHeader from '@/components/ui/PageHeader.vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import BaseTable from '@/components/ui/BaseTable.vue';
import StatusBadge from '@/components/ui/StatusBadge.vue';
import ConfirmDialog from '@/components/ui/ConfirmDialog.vue';
import { useConfirmDialog } from '@/composables/useConfirmDialog';

const toast = useToast();
const confirm = useConfirmDialog();
const noticias = ref([]);
const loading = ref(true);
const error = ref('');
const headers = [
  { key: 'titulo', label: 'Título' },
  { key: 'destaque', label: 'Destaque' },
  { key: 'publicado', label: 'Publicado' },
  { key: 'publicado_em', label: 'Data' },
];

const loadData = async () => {
  loading.value = true;
  error.value = '';
  try {
    const { data } = await noticiaService.getAll({ per_page: 20 });
    noticias.value = data.data || [];
  } catch (e) {
    error.value = 'Erro ao carregar notícias.';
  } finally {
    loading.value = false;
  }
};

const toggleDestaque = async (item) => {
  const newState = !item.destaque;
  item.destaque = newState;
  try {
    await noticiaService.toggleDestaque(item.id);
  } catch (e) {
    item.destaque = !newState;
    toast.error('Erro ao atualizar destaque. Tente novamente.');
  }
};

const deleteItem = async (id) => {
  const ok = await confirm.ask('Excluir esta notícia?', 'Excluir notícia');
  if (!ok) return;
  try {
    await noticiaService.delete(id);
    noticias.value = noticias.value.filter((n) => n.id !== id);
    toast.success('Notícia excluída.');
  } catch (e) {
    toast.error('Erro ao excluir notícia.');
  }
};

onMounted(loadData);
</script>
