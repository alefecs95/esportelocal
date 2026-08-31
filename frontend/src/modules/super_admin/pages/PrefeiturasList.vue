<template>
  <div class="space-y-6">
    <div class="flex justify-between items-center">
      <h1 class="text-2xl font-bold text-gray-900">Prefeituras</h1>
      <BaseButton variant="primary" @click="openModal">
        <Plus :size="20" class="mr-2" />
        Nova Prefeitura
      </BaseButton>
    </div>

    <BaseCard>
      <BaseTable
        :headers="[
          { key: 'id', label: 'ID' },
          { key: 'nome', label: 'Nome' },
          { key: 'slug', label: 'Slug' },
          { key: 'dominio', label: 'Domínio' },
          { key: 'limites', label: 'Limites de Consulta' },
          { key: 'status', label: 'Status' }
        ]"
        :items="prefeituras"
        :actions="true"
      >
        <template #limites="{ item }">
          <div class="text-xs text-gray-700 leading-5">
            <div>CPF: {{ item.limite_consultas_cpf || '-' }}/dia</div>
            <div>TSE: {{ item.limite_consultas_tse || '-' }}/dia</div>
          </div>
        </template>
        <template #status="{ item }">
          <span 
            class="px-2 py-1 text-xs font-semibold rounded-full"
            :class="item.ativo ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
          >
            {{ item.ativo ? 'Ativo' : 'Inativo' }}
          </span>
        </template>
        <template #actions="{ item }">
          <div class="flex items-center gap-2">
            <button @click="edit(item)" class="text-blue-600 hover:text-blue-800" title="Editar">
              <Edit :size="18" />
            </button>
            <button @click="remove(item)" class="text-red-600 hover:text-red-800" title="Excluir">
              <Trash2 :size="18" />
            </button>
            <button @click="openHistorico(item)" class="text-violet-600 hover:text-violet-800" title="Histórico de consultas">
              <History :size="18" />
            </button>
            <a 
                :href="getPainelUrl(item)" 
                target="_blank"
                class="text-gray-500 hover:text-gray-700"
                title="Acessar Painel"
            >
                <ExternalLink :size="18" />
            </a>
            <img v-if="item.logo_url" :src="item.logo_url" class="h-6 w-6 object-contain" alt="Logo" title="Logo" />
          </div>
        </template>
      </BaseTable>
    </BaseCard>

    <BaseModal 
      :isOpen="showModal" 
      @close="showModal = false"
      :title="editing ? 'Editar Prefeitura' : 'Nova Prefeitura'"
    >
      <div class="mb-4 flex items-center justify-between">
        <input
          v-model="search"
          type="text"
          placeholder="Buscar por nome, slug ou domínio..."
          class="w-full border border-gray-300 rounded-lg px-3 py-2"
          @keyup.enter="load"
        />
      </div>

      <form id="prefeitura-form" @submit.prevent="save" class="space-y-4">
        <BaseInput id="pref-nome" v-model="form.nome" label="Nome da Prefeitura" required />
        <div v-if="errors.nome" class="text-red-600 text-xs">{{ errors.nome[0] }}</div>
        
        <div class="grid grid-cols-2 gap-4">
            <BaseInput id="pref-slug" v-model="form.slug" label="Subdomínio (Slug)" required />
            <div v-if="errors.slug" class="text-red-600 text-xs">{{ errors.slug[0] }}</div>
            <BaseInput id="pref-dominio" v-model="form.dominio" label="Domínio Personalizado" placeholder="ex: esporte.cidade.sp.gov.br" />
            <div v-if="errors.dominio" class="text-red-600 text-xs">{{ errors.dominio[0] }}</div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <BaseInput id="pref-limite-cpf" v-model.number="form.limite_consultas_cpf" label="Limite diário CPF" type="number" min="1" />
            <div v-if="errors.limite_consultas_cpf" class="text-red-600 text-xs">{{ errors.limite_consultas_cpf[0] }}</div>
            <BaseInput id="pref-limite-tse" v-model.number="form.limite_consultas_tse" label="Limite diário TSE" type="number" min="1" />
            <div v-if="errors.limite_consultas_tse" class="text-red-600 text-xs">{{ errors.limite_consultas_tse[0] }}</div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Cor Primária</label>
                <div class="flex items-center gap-2">
                    <input type="color" v-model="form.cor_primaria" class="h-10 w-20 rounded border border-gray-300" />
                    <span class="text-sm text-gray-600">{{ form.cor_primaria }}</span>
                </div>
                <div v-if="errors.cor_primaria" class="text-red-600 text-xs mt-1">{{ errors.cor_primaria[0] }}</div>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Brasão / Logo</label>
                <input 
                    type="file" 
                    @change="handleFile" 
                    accept="image/*"
                    class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                />
                <div v-if="errors.logo" class="text-red-600 text-xs mt-1">{{ errors.logo[0] }}</div>
            </div>
        </div>

        <div class="flex items-center mt-2">
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" v-model="form.ativo" class="w-4 h-4 text-blue-600 rounded">
                <span class="text-gray-700">Ativo</span>
            </label>
        </div>

        <div v-if="!editing" class="border-t pt-4 mt-4">
            <h4 class="font-medium text-gray-900 mb-4">Admin Inicial</h4>
            <BaseInput id="pref-admin-email" v-model="form.admin_email" label="Email do Admin" type="email" required />
            <div v-if="errors.admin_email" class="text-red-600 text-xs">{{ errors.admin_email[0] }}</div>
            <BaseInput id="pref-admin-pass" v-model="form.admin_password" label="Senha Inicial" type="password" required />
            <div v-if="errors.admin_password" class="text-red-600 text-xs">{{ errors.admin_password[0] }}</div>
        </div>
      </form>

      <template #footer>
        <div class="flex justify-end gap-3">
          <BaseButton type="button" variant="secondary" @click="showModal = false">Cancelar</BaseButton>
          <BaseButton type="submit" form="prefeitura-form" :disabled="saving">
            {{ saving ? 'Salvando...' : 'Salvar' }}
          </BaseButton>
        </div>
      </template>
    </BaseModal>

    <BaseModal
      :isOpen="showHistoryModal"
      @close="showHistoryModal = false"
      title="Histórico de Consultas da Prefeitura"
    >
      <div class="space-y-3">
        <p class="text-sm text-gray-700" v-if="historicoTenantNome">
          Prefeitura: <strong>{{ historicoTenantNome }}</strong>
        </p>
        <div class="grid grid-cols-2 gap-2 text-xs text-gray-700 bg-gray-50 rounded-lg p-3">
          <div>Total: {{ historicoResumo.total || 0 }}</div>
          <div>Total hoje: {{ historicoResumo.total_hoje || 0 }}</div>
          <div>CPF total: {{ historicoResumo.cpf_total || 0 }}</div>
          <div>TSE total: {{ historicoResumo.tse_total || 0 }}</div>
          <div>CPF hoje: {{ historicoResumo.cpf_hoje || 0 }}</div>
          <div>TSE hoje: {{ historicoResumo.tse_hoje || 0 }}</div>
        </div>

        <div v-if="loadingHistorico" class="text-sm text-gray-500">Carregando histórico...</div>
        <div v-else class="max-h-72 overflow-y-auto border rounded-lg">
          <table class="w-full text-xs">
            <thead class="bg-gray-100 text-gray-700">
              <tr>
                <th class="px-2 py-2 text-left">Data</th>
                <th class="px-2 py-2 text-left">Tipo</th>
                <th class="px-2 py-2 text-left">Status</th>
                <th class="px-2 py-2 text-left">Origem</th>
                <th class="px-2 py-2 text-left">CPF</th>
                <th class="px-2 py-2 text-left">Nome</th>
                <th class="px-2 py-2 text-left">API Externa</th>
                <th class="px-2 py-2 text-left">Motivo</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in historicoItems" :key="item.id" class="border-t">
                <td class="px-2 py-2">{{ formatDate(item.consultado_em) }}</td>
                <td class="px-2 py-2 uppercase">{{ item.tipo }}</td>
                <td class="px-2 py-2">
                  <span :class="item.status === 'sucesso' ? 'text-green-700' : 'text-red-700'">
                    {{ item.status }}
                  </span>
                </td>
                <td class="px-2 py-2">{{ item.origem || '-' }}</td>
                <td class="px-2 py-2">{{ item.cpf_consultado || '-' }}</td>
                <td class="px-2 py-2">{{ resolveNomeHistorico(item) }}</td>
                <td class="px-2 py-2">{{ resolveApiCalledHistorico(item) }}</td>
                <td class="px-2 py-2">{{ resolveMotivoHistorico(item) }}</td>
              </tr>
              <tr v-if="historicoItems.length === 0">
                <td colspan="8" class="px-2 py-4 text-center text-gray-500">Nenhuma consulta registrada.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      <template #footer>
        <div class="flex justify-end">
          <BaseButton type="button" variant="secondary" @click="showHistoryModal = false">Fechar</BaseButton>
        </div>
      </template>
    </BaseModal>

    <div class="mt-4 flex items-center justify-between" v-if="pagination.total > 0">
      <div class="text-sm text-gray-600">
        Total de registros: {{ pagination.total }}
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { Plus, Edit, Trash2, ExternalLink, History } from 'lucide-vue-next';
import BaseCard from '../../../components/ui/BaseCard.vue';
import BaseTable from '../../../components/ui/BaseTable.vue';
import BaseButton from '../../../components/ui/BaseButton.vue';
import BaseModal from '../../../components/ui/BaseModal.vue';
import BaseInput from '../../../components/ui/BaseInput.vue';
import api from '../../../services/api';
import { buildTenantSubdomainUrl } from '@/utils/domainHelpers';

const prefeituras = ref([]);
const showModal = ref(false);
const showHistoryModal = ref(false);
const saving = ref(false);
const editing = ref(null);
const search = ref('');
const loadingHistorico = ref(false);
const historicoItems = ref([]);
const historicoResumo = ref({});
const historicoTenantNome = ref('');
const pagination = ref({
    current_page: 1,
    last_page: 1,
    total: 0,
    per_page: 10
});
const errors = ref({});

const form = ref({
    nome: '',
    slug: '',
    dominio: '',
    cor_primaria: '#000000',
    ativo: true,
    limite_consultas_cpf: 200,
    limite_consultas_tse: 200,
    admin_email: '',
    admin_password: '',
    logo: null
});

const handleFile = (event) => {
    form.value.logo = event.target.files[0];
};

const getPainelUrl = (item) => {
    const dominio = String(item?.dominio || '').trim();
    if (dominio) {
        return /^https?:\/\//i.test(dominio) ? dominio : `https://${dominio}`;
    }

    const slug = String(item?.slug || '').trim();
    if (!slug) {
        return '#';
    }

    return buildTenantSubdomainUrl(slug);
};

const load = async () => {
    const { data } = await api.get('admin/prefeituras', {
        params: {
            q: search.value || undefined,
            per_page: -1 // Solicitar todos os registros
        }
    });
    // Se vier paginado (data.data) ou lista direta (data)
    prefeituras.value = Array.isArray(data) ? data : (data.data || []);
    
    // Ajustar paginação local se necessário, ou apenas mostrar total
    pagination.value.total = prefeituras.value.length;
    pagination.value.current_page = 1;
    pagination.value.last_page = 1;
};

const openModal = () => {
    editing.value = null;
    errors.value = {};
    form.value = {
        nome: '',
        slug: '',
        dominio: '',
        cor_primaria: '#000000',
        ativo: true,
        limite_consultas_cpf: 200,
        limite_consultas_tse: 200,
        admin_email: '',
        admin_password: '',
        logo: null
    };
    showModal.value = true;
};

const goPrev = async () => {
    if (pagination.value.current_page > 1) {
        pagination.value.current_page -= 1;
        await load();
    }
};

const goNext = async () => {
    if (pagination.value.current_page < pagination.value.last_page) {
        pagination.value.current_page += 1;
        await load();
    }
};

const edit = (row) => {
    editing.value = row;
    form.value = { 
        ...row,
        limite_consultas_cpf: Number(row.limite_consultas_cpf || 200),
        limite_consultas_tse: Number(row.limite_consultas_tse || 200),
        logo: null // Reset logo input on edit
    };
    showModal.value = true;
};

const save = async () => {
    saving.value = true;
    try {
        const formData = new FormData();
        formData.append('nome', form.value.nome);
        formData.append('slug', form.value.slug);
        if (form.value.dominio) formData.append('dominio', form.value.dominio);
        formData.append('cor_primaria', form.value.cor_primaria);
        formData.append('ativo', form.value.ativo ? '1' : '0');
        formData.append('limite_consultas_cpf', String(form.value.limite_consultas_cpf || 200));
        formData.append('limite_consultas_tse', String(form.value.limite_consultas_tse || 200));
        
        if (form.value.logo) {
            formData.append('logo', form.value.logo);
        }

        if (editing.value) {
            // Laravel requires POST with _method=PUT for file uploads in update
            formData.append('_method', 'PUT');
            
            await api.post(`admin/prefeituras/${editing.value.id}`, formData, {
                headers: { 'Content-Type': 'multipart/form-data' }
            });
        } else {
            formData.append('admin_email', form.value.admin_email);
            formData.append('admin_password', form.value.admin_password);
            
            await api.post('admin/prefeituras', formData, {
                headers: { 'Content-Type': 'multipart/form-data' }
            });
        }
        await load();
        showModal.value = false;
    } catch (error) {
        console.error('Erro ao salvar:', error);
        console.error('Validation errors:', error.response?.data?.errors);
        errors.value = error.response?.data?.errors || {};
        const msg = error.response?.data?.message || 'Erro ao salvar prefeitura';
        alert(msg);
    } finally {
        saving.value = false;
    }
};

const remove = async (row) => {
    if (!confirm('Tem certeza? Isso irá desativar/remover a prefeitura.')) return;
    try {
        await api.delete(`admin/prefeituras/${row.id}`);
        await load();
    } catch (error) {
        console.error(error);
    }
};

const openHistorico = async (row) => {
    showHistoryModal.value = true;
    historicoTenantNome.value = row.nome;
    historicoItems.value = [];
    historicoResumo.value = {};
    loadingHistorico.value = true;
    try {
        const { data } = await api.get(`admin/prefeituras/${row.id}/historico-consultas`, {
            params: { limit: 100 }
        });
        historicoResumo.value = data?.resumo || {};
        historicoItems.value = data?.historico?.data || [];
    } catch (error) {
        console.error(error);
    } finally {
        loadingHistorico.value = false;
    }
};

const formatDate = (value) => {
    if (!value) return '-';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return value;
    return date.toLocaleString('pt-BR');
};

const resolveNomeHistorico = (item) => {
  return item?.meta?.nome_consultado || '-';
};

const resolveApiCalledHistorico = (item) => {
  if (item?.meta?.api_called === true) {
    return 'Sim';
  }
  if (item?.meta?.api_called === false) {
    return 'Não';
  }
  return '-';
};

const resolveMotivoHistorico = (item) => {
  if (item?.status !== 'erro') {
    return '-';
  }
  return item?.mensagem || item?.meta?.mensagem || 'Erro não informado';
};

onMounted(load);
</script>
