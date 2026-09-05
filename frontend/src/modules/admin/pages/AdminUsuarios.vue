<template>
  <div class="space-y-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
          <Users :size="24" class="text-blue-600" />
          Usuários e Permissões
        </h1>
        <p class="text-gray-500">Cadastre usuários da prefeitura e defina perfis de acesso.</p>
      </div>
      <button
        @click="abrirModal()"
        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center gap-2 transition-colors"
      >
        <Plus :size="18" />
        Novo Usuário
      </button>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
        <div class="md:col-span-2 relative">
          <Search :size="16" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
          <input
            v-model="filtros.q"
            type="text"
            class="w-full pl-9 pr-3 py-2 border border-gray-300 rounded-lg"
            placeholder="Buscar por nome, e-mail ou CPF"
          />
        </div>
        <select v-model="filtros.perfil" class="px-3 py-2 border border-gray-300 rounded-lg">
          <option value="">Todos os perfis</option>
          <option value="ADMIN">Administrador</option>
          <option value="ARBITRO">Árbitro</option>
          <option value="RESPONSAVEL">Responsável do clube</option>
          <option value="REDATOR">Redator</option>
          <option value="ATLETA">Atleta</option>
        </select>
        <select v-model="filtros.status" class="px-3 py-2 border border-gray-300 rounded-lg">
          <option value="">Todos os status</option>
          <option value="ATIVO">Ativo</option>
          <option value="INATIVO">Inativo</option>
        </select>
      </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
      <div v-if="loading" class="p-8 text-center text-gray-500">Carregando usuários...</div>
      <EmptyState v-else-if="usuarios.length === 0" title="Nenhum usuário encontrado" description="Cadastre um usuário da secretaria para começar." />
      <BaseTable v-else :headers="headers" :items="usuarios" actions>
        <template #cpf="{ item }">{{ formatCpfDisplay(item.cpf) }}</template>
        <template #perfil="{ item }">
          <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold" :class="perfilBadge(item.perfil)">
            {{ perfilLabel(item.perfil) }}
          </span>
        </template>
        <template #status="{ item }">
          <StatusBadge :status="item.status === 'ATIVO' ? 'ativo' : 'inativo'" />
        </template>
        <template #actions="{ item }">
          <div class="flex justify-end gap-2">
            <button @click="abrirModal(item)" class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-semibold rounded-md border border-blue-200 text-blue-700 hover:bg-blue-50">
              <Pencil :size="14" />
              Editar
            </button>
            <button @click="deletarUsuario(item)" class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-semibold rounded-md border border-red-200 text-red-700 hover:bg-red-50">
              <Trash2 :size="14" />
              Deletar
            </button>
          </div>
        </template>
      </BaseTable>
    </div>

    <div v-if="modal.aberto" class="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4">
      <div class="w-full max-w-2xl bg-white rounded-xl shadow-xl overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-200 flex items-center justify-between">
          <h2 class="text-lg font-bold text-gray-900">{{ modal.editando ? 'Editar Usuário' : 'Novo Usuário' }}</h2>
          <button @click="fecharModal" class="text-gray-400 hover:text-gray-700">
            <X :size="20" />
          </button>
        </div>

        <form @submit.prevent="salvar" class="p-5 space-y-4">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="text-sm font-medium text-gray-700">Nome</label>
              <input v-model="form.name" type="text" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-lg" />
              <p v-if="errors.name" class="mt-1 text-xs text-red-600">{{ errors.name }}</p>
            </div>
            <div>
              <label class="text-sm font-medium text-gray-700">CPF</label>
              <input v-model="form.cpf" type="text" maxlength="14" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-lg" @input="form.cpf = formatCpf($event.target.value)" />
              <p v-if="errors.cpf" class="mt-1 text-xs text-red-600">{{ errors.cpf }}</p>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="text-sm font-medium text-gray-700">E-mail</label>
              <input v-model="form.email" type="email" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-lg" />
              <p v-if="errors.email" class="mt-1 text-xs text-red-600">{{ errors.email }}</p>
            </div>
            <div>
              <label class="text-sm font-medium text-gray-700">Senha {{ modal.editando ? '(opcional)' : '' }}</label>
              <input v-model="form.password" type="password" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-lg" />
              <p v-if="errors.password" class="mt-1 text-xs text-red-600">{{ errors.password }}</p>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <label class="text-sm font-medium text-gray-700">Perfil</label>
              <select v-model="form.perfil" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-lg">
                <option value="ADMIN">Administrador</option>
                <option value="ARBITRO">Árbitro</option>
                <option value="RESPONSAVEL">Responsável do clube</option>
                <option value="REDATOR">Redator</option>
                <option value="ATLETA">Atleta</option>
              </select>
              <p v-if="errors.perfil" class="mt-1 text-xs text-red-600">{{ errors.perfil }}</p>
            </div>
            <div>
              <label class="text-sm font-medium text-gray-700">Status</label>
              <select v-model="form.status" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-lg">
                <option value="ATIVO">Ativo</option>
                <option value="INATIVO">Inativo</option>
              </select>
              <p v-if="errors.status" class="mt-1 text-xs text-red-600">{{ errors.status }}</p>
            </div>
            <div class="flex items-end">
              <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                <input v-model="form.forcar_troca_senha" type="checkbox" class="rounded border-gray-300 text-blue-600" />
                Forçar troca de senha
              </label>
            </div>
          </div>

          <div class="pt-3 border-t border-gray-100 flex justify-end gap-2">
            <button type="button" @click="fecharModal" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">Cancelar</button>
            <button type="submit" :disabled="salvando" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-60">
              {{ salvando ? 'Salvando...' : 'Salvar' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <ConfirmDialog
      :is-open="confirmDlg.isOpen.value"
      :title="confirmDlg.title.value"
      :message="confirmDlg.message.value"
      @confirm="confirmDlg.confirm"
      @cancel="confirmDlg.cancel"
    />
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, watch } from 'vue';
import { Plus, Users, Search, Pencil, Trash2, X } from 'lucide-vue-next';
import { useToast } from 'vue-toastification';
import api from '@/services/api';
import { formatCpf, formatCpfDisplay } from '@/utils/formatCpf';
import { perfilLabel } from '@/utils/perfilLabels';
import BaseTable from '@/components/ui/BaseTable.vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import StatusBadge from '@/components/ui/StatusBadge.vue';
import ConfirmDialog from '@/components/ui/ConfirmDialog.vue';
import { useConfirmDialog } from '@/composables/useConfirmDialog';

const toast = useToast();
const confirmDlg = useConfirmDialog();
const headers = [
  { key: 'name', label: 'Nome' },
  { key: 'cpf', label: 'CPF' },
  { key: 'email', label: 'E-mail' },
  { key: 'perfil', label: 'Perfil' },
  { key: 'status', label: 'Status' },
];
const loading = ref(true);
const salvando = ref(false);
const usuarios = ref([]);
const filtersTimeout = ref(null);

const filtros = reactive({
  q: '',
  perfil: '',
  status: ''
});

const modal = reactive({
  aberto: false,
  editando: false,
  id: null
});

const form = reactive({
  name: '',
  cpf: '',
  email: '',
  password: '',
  perfil: 'ADMIN',
  status: 'ATIVO',
  forcar_troca_senha: true
});

const errors = reactive({});

const resetErrors = () => {
  Object.keys(errors).forEach((key) => {
    delete errors[key];
  });
};

const resetForm = () => {
  form.name = '';
  form.cpf = '';
  form.email = '';
  form.password = '';
  form.perfil = 'ADMIN';
  form.status = 'ATIVO';
  form.forcar_troca_senha = true;
};

const carregar = async () => {
  loading.value = true;
  try {
    const { data } = await api.get('/usuarios', {
      params: {
        q: filtros.q || undefined,
        perfil: filtros.perfil || undefined,
        status: filtros.status || undefined,
        per_page: 100
      }
    });
    usuarios.value = data?.data || [];
  } catch (error) {
    toast.error('Erro ao carregar usuários.');
  } finally {
    loading.value = false;
  }
};

const abrirModal = (usuario = null) => {
  resetErrors();
  if (usuario) {
    modal.aberto = true;
    modal.editando = true;
    modal.id = usuario.id;
    form.name = usuario.name || '';
    form.cpf = usuario.cpf || '';
    form.email = usuario.email || '';
    form.password = '';
    form.perfil = usuario.perfil || 'ADMIN';
    form.status = usuario.status || 'ATIVO';
    form.forcar_troca_senha = Boolean(usuario.forcar_troca_senha);
    return;
  }
  modal.aberto = true;
  modal.editando = false;
  modal.id = null;
  resetForm();
};

const fecharModal = () => {
  modal.aberto = false;
  modal.editando = false;
  modal.id = null;
  resetErrors();
  resetForm();
};

const salvar = async () => {
  salvando.value = true;
  resetErrors();
  const payload = {
    name: form.name,
    cpf: form.cpf,
    email: form.email || null,
    perfil: form.perfil,
    status: form.status,
    forcar_troca_senha: form.forcar_troca_senha
  };
  if (form.password) payload.password = form.password;
  if (!modal.editando && !form.password) payload.password = '';

  try {
    if (modal.editando) {
      await api.put(`/usuarios/${modal.id}`, payload);
      toast.success('Usuário atualizado com sucesso.');
    } else {
      await api.post('/usuarios', payload);
      toast.success('Usuário criado com sucesso.');
    }
    fecharModal();
    await carregar();
  } catch (error) {
    const apiErrors = error.response?.data?.errors || {};
    Object.keys(apiErrors).forEach((key) => {
      errors[key] = Array.isArray(apiErrors[key]) ? apiErrors[key][0] : apiErrors[key];
    });
    if (!Object.keys(apiErrors).length) {
      toast.error(error.response?.data?.message || 'Erro ao salvar usuário.');
    }
  } finally {
    salvando.value = false;
  }
};

const deletarUsuario = async (usuario) => {
  const ok = await confirmDlg.ask(`Deletar o usuário "${usuario.name}"?`, 'Deletar usuário');
  if (!ok) return;
  try {
    await api.delete(`/usuarios/${usuario.id}`);
    toast.success('Usuário deletado com sucesso.');
    await carregar();
  } catch (error) {
    toast.error(error.response?.data?.message || 'Erro ao deletar usuário.');
  }
};

const perfilBadge = (perfil) => {
  if (perfil === 'ADMIN') return 'bg-blue-100 text-blue-700';
  if (perfil === 'ARBITRO') return 'bg-purple-100 text-purple-700';
  if (perfil === 'RESPONSAVEL') return 'bg-emerald-100 text-emerald-700';
  if (perfil === 'REDATOR') return 'bg-amber-100 text-amber-700';
  if (perfil === 'ATLETA') return 'bg-cyan-100 text-cyan-700';
  return 'bg-gray-100 text-gray-700';
};

watch(
  () => [filtros.q, filtros.perfil, filtros.status],
  () => {
    if (filtersTimeout.value) clearTimeout(filtersTimeout.value);
    filtersTimeout.value = setTimeout(() => {
      carregar();
    }, 300);
  }
);

onMounted(() => {
  carregar();
});
</script>
