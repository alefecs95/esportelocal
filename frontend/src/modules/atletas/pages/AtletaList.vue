<template>
  <div class="container mx-auto px-4 py-8">
    <PageHeader title="Atletas" description="Cadastro, acesso e validação eleitoral dos atletas.">
      <template #actions>
        <router-link :to="{ name: 'AtletaCreate' }" class="bg-blue-600 hover:bg-blue-700 text-white font-bold min-h-11 py-2 px-4 rounded inline-flex items-center">
          + Novo Atleta
        </router-link>
      </template>
    </PageHeader>

    <div class="bg-white border rounded-lg p-4 mb-4 space-y-4">
      <div>
        <input
          v-model="search"
          type="text"
          placeholder="Buscar por nome ou CPF..."
          class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
        />
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
        <select v-model="filters.acesso_ativo" class="px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
          <option value="">Acesso: Todos</option>
          <option value="1">Acesso ativo</option>
          <option value="0">Acesso inativo</option>
        </select>
        <select v-model="filters.pode_participar_competicoes" class="px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
          <option value="">Competições: Todos</option>
          <option value="1">Pode competir</option>
          <option value="0">Não pode competir</option>
        </select>
        <input
          v-model="filters.cidade"
          type="text"
          placeholder="Filtrar por cidade"
          class="px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
        />
        <select v-model="filters.titulo_verificado" class="px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
          <option value="">Título: Todos</option>
          <option value="1">Título verificado</option>
          <option value="0">Título não verificado</option>
        </select>
        <input
          v-model="filters.idade_min"
          type="number"
          min="0"
          placeholder="Idade mínima"
          class="px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
        />
        <div class="flex gap-2">
          <input
            v-model="filters.idade_max"
            type="number"
            min="0"
            placeholder="Idade máxima"
            class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
          <button
            type="button"
            @click="limparFiltros"
            class="px-3 py-2 border rounded-lg text-gray-700 hover:bg-gray-50"
          >
            Limpar
          </button>
        </div>
      </div>
    </div>

    <div v-if="store.loading" class="text-center py-8">
      <p class="text-gray-500">Carregando...</p>
    </div>

    <div v-else-if="store.error" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
      <strong class="font-bold">Erro!</strong>
      <span class="block sm:inline">{{ store.error }}</span>
    </div>

    <div v-else class="bg-white shadow-md rounded-lg overflow-hidden">
      <div v-if="atletasPendentes.length" class="mx-5 mt-5 mb-2 p-4 rounded-lg border border-amber-200 bg-amber-50 text-amber-800">
        <div class="flex items-center justify-between gap-3">
          <div>
            <p class="text-sm font-semibold">Validação eleitoral pendente: {{ atletasPendentes.length }}</p>
            <p class="text-xs mt-1">Esses atletas estão bloqueados até aprovação manual ou nova consulta via TSE.</p>
          </div>
        </div>
      </div>
      <div class="px-5 py-3 border-b text-sm text-gray-600">
        Total de atletas encontrados: <strong>{{ store.pagination.total }}</strong>
      </div>
      <div class="overflow-x-auto">
      <table class="min-w-full leading-normal">
        <thead>
          <tr>
            <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-800 uppercase tracking-wider">
              Nome
            </th>
            <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-800 uppercase tracking-wider">
              CPF
            </th>
            <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-800 uppercase tracking-wider">
              Idade
            </th>
            <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-800 uppercase tracking-wider">
              Cidade/UF
            </th>
            <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-800 uppercase tracking-wider">
              Dados Eleitorais
            </th>
            <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-800 uppercase tracking-wider">
              Acesso
            </th>
            <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-800 uppercase tracking-wider">
              Competições
            </th>
            <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-800 uppercase tracking-wider">
              Ações
            </th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="atleta in store.atletas" :key="atleta.id">
            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
              <p class="text-gray-900 whitespace-no-wrap">{{ atleta.nome }}</p>
            </td>
            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
              <p class="text-gray-900 whitespace-no-wrap">{{ formatCpfDisplay(atleta.cpf) }}</p>
            </td>
            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
              <p class="text-gray-900 whitespace-no-wrap">{{ calcularIdade(atleta.data_nascimento) }} anos</p>
            </td>
            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
              <p class="text-gray-900 whitespace-no-wrap">{{ atleta.cidade }} / {{ atleta.estado }}</p>
            </td>
            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
              <div class="space-y-1">
                <p class="text-gray-900 whitespace-no-wrap">{{ atleta.cidade_eleitoral || '-' }} / {{ atleta.uf_eleitoral || '-' }}</p>
                <p class="text-gray-500 whitespace-no-wrap text-xs">Zona {{ atleta.zona_eleitoral || '-' }} • Seção {{ atleta.secao_eleitoral || '-' }}</p>
                <span :class="atleta.titulo_verificado ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'" class="inline-flex px-2 py-0.5 rounded-full text-[11px] font-semibold">
                  {{ atleta.titulo_verificado ? 'Título verificado' : 'Título não verificado' }}
                </span>
                <span v-if="atleta.validacao_eleitoral_pendente" class="inline-flex px-2 py-0.5 rounded-full text-[11px] font-semibold bg-orange-100 text-orange-700">
                  Pendente de aprovação
                </span>
              </div>
            </td>
            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
              <span :class="atleta.acesso_ativo ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'" class="px-2 py-1 rounded-full text-xs font-semibold">
                {{ atleta.acesso_ativo ? 'Ativo' : 'Inativo' }}
              </span>
            </td>
            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
              <span :class="atleta.pode_participar_competicoes ? 'bg-blue-100 text-blue-700' : 'bg-orange-100 text-orange-700'" class="px-2 py-1 rounded-full text-xs font-semibold">
                {{ atleta.pode_participar_competicoes ? 'Liberado' : 'Bloqueado' }}
              </span>
            </td>
            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
              <div class="flex items-center gap-1 min-w-[220px]">
                <button
                  @click="baixarFicha(atleta)"
                  :disabled="pdfLoadingId === atleta.id"
                  class="inline-flex items-center justify-center w-8 h-8 rounded-md border border-gray-300 text-gray-700 hover:bg-gray-50 disabled:opacity-60"
                  title="Gerar ficha PDF"
                >
                  <FileText :size="15" />
                </button>
                <button
                  @click="alternarAcesso(atleta)"
                  class="inline-flex items-center justify-center w-8 h-8 rounded-md border border-gray-300 text-gray-700 hover:bg-gray-50"
                  :title="atleta.acesso_ativo ? 'Inativar acesso' : 'Ativar acesso'"
                >
                  <UserX v-if="atleta.acesso_ativo" :size="15" />
                  <UserCheck v-else :size="15" />
                </button>
                <button
                  @click="alternarCompeticao(atleta)"
                  class="inline-flex items-center justify-center w-8 h-8 rounded-md border border-gray-300 text-gray-700 hover:bg-gray-50"
                  :title="atleta.pode_participar_competicoes ? 'Bloquear competições' : 'Liberar competições'"
                >
                  <ShieldOff v-if="atleta.pode_participar_competicoes" :size="15" />
                  <ShieldCheck v-else :size="15" />
                </button>
                <router-link
                  :to="{ name: 'AtletaEdit', params: { id: atleta.id } }"
                  class="inline-flex items-center justify-center w-8 h-8 rounded-md border border-blue-200 text-blue-600 hover:bg-blue-50"
                  title="Editar atleta"
                >
                  <Pencil :size="15" />
                </router-link>
                <button
                  v-if="!atleta.titulo_verificado && !isMenorDe16(atleta.data_nascimento)"
                  @click="revalidarTitulo(atleta)"
                  :disabled="validandoTituloId === atleta.id"
                  class="inline-flex items-center justify-center w-8 h-8 rounded-md border border-amber-200 text-amber-700 hover:bg-amber-50 disabled:opacity-60"
                  title="Validar título no TSE"
                >
                  <RefreshCw :size="15" :class="validandoTituloId === atleta.id ? 'animate-spin' : ''" />
                </button>
                <button
                  v-if="atleta.validacao_eleitoral_pendente"
                  @click="aprovarDadosEleitoraisManual(atleta)"
                  class="inline-flex items-center justify-center w-8 h-8 rounded-md border border-emerald-200 text-emerald-700 hover:bg-emerald-50"
                  title="Aprovar dados eleitorais manualmente"
                >
                  <Check :size="15" />
                </button>
                <button
                  v-if="atleta.possui_usuario"
                  @click="resetPassword(atleta)"
                  class="inline-flex items-center justify-center w-8 h-8 rounded-md border border-purple-200 text-purple-700 hover:bg-purple-50"
                  title="Resetar senha (últimos 4 dígitos do CPF)"
                >
                  <Key :size="15" />
                </button>
                <button
                  v-else
                  @click="gerarUsuario(atleta)"
                  class="inline-flex items-center justify-center w-8 h-8 rounded-md border border-indigo-200 text-indigo-700 hover:bg-indigo-50"
                  title="Gerar usuário para este atleta"
                >
                  <UserPlus :size="15" />
                </button>
                <button
                  @click="deletarAtleta(atleta.id)"
                  class="inline-flex items-center justify-center w-8 h-8 rounded-md border border-red-200 text-red-600 hover:bg-red-50"
                  title="Excluir atleta"
                >
                  <Trash2 :size="15" />
                </button>
              </div>
            </td>
          </tr>
          <tr v-if="store.atletas.length === 0">
            <td colspan="8" class="px-5 py-8">
              <EmptyState title="Nenhum atleta encontrado" description="Ajuste os filtros ou cadastre o primeiro atleta da secretaria.">
                <template #action>
                  <router-link :to="{ name: 'AtletaCreate' }" class="bg-blue-600 text-white px-4 py-2 rounded-lg font-medium">Novo Atleta</router-link>
                </template>
              </EmptyState>
            </td>
          </tr>
        </tbody>
      </table>
      </div>

      <ConfirmDialog
        :is-open="confirm.isOpen.value"
        :title="confirm.title.value"
        :message="confirm.message.value"
        @confirm="confirm.confirm"
        @cancel="confirm.cancel"
      />
      
      <div class="px-5 py-5 bg-white border-t flex flex-col xs:flex-row items-center xs:justify-between          ">
        <span class="text-xs xs:text-sm text-gray-900">
          Mostrando página {{ store.pagination.current_page }} de {{ store.pagination.last_page }}
        </span>
        <div class="inline-flex mt-2 xs:mt-0">
          <button 
            @click="mudarPagina(store.pagination.current_page - 1)" 
            :disabled="store.pagination.current_page <= 1"
            class="text-sm bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold py-2 px-4 rounded-l disabled:opacity-50">
            Anterior
          </button>
          <button 
            @click="mudarPagina(store.pagination.current_page + 1)" 
            :disabled="store.pagination.current_page >= store.pagination.last_page"
            class="text-sm bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold py-2 px-4 rounded-r disabled:opacity-50">
            Próxima
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch, computed } from 'vue';
import { FileText, Pencil, Trash2, UserX, UserCheck, ShieldCheck, ShieldOff, RefreshCw, Check, Key, UserPlus } from 'lucide-vue-next';
import { useToast } from 'vue-toastification';
import { useAtletaStore } from '../store/atletaStore';
import atletaService from '../services/atletaService';
import { formatCpfDisplay } from '@/utils/formatCpf';
import PageHeader from '@/components/ui/PageHeader.vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import ConfirmDialog from '@/components/ui/ConfirmDialog.vue';
import { useConfirmDialog } from '@/composables/useConfirmDialog';

const toast = useToast();
const confirm = useConfirmDialog();
const store = useAtletaStore();
const search = ref('');
const filters = ref({
  acesso_ativo: '',
  pode_participar_competicoes: '',
  cidade: '',
  titulo_verificado: '',
  idade_min: '',
  idade_max: ''
});
let searchTimer = null;
const pdfLoadingId = ref(null);
const validandoTituloId = ref(null);

onMounted(() => {
  buscarAtletas(1);
});

const payloadFiltros = () => ({
  acesso_ativo: filters.value.acesso_ativo !== '' ? filters.value.acesso_ativo : undefined,
  pode_participar_competicoes: filters.value.pode_participar_competicoes !== '' ? filters.value.pode_participar_competicoes : undefined,
  cidade: filters.value.cidade || undefined,
  titulo_verificado: filters.value.titulo_verificado !== '' ? filters.value.titulo_verificado : undefined,
  idade_min: filters.value.idade_min || undefined,
  idade_max: filters.value.idade_max || undefined
});

const buscarAtletas = (page = 1) => {
  store.fetchAtletas(page, search.value, payloadFiltros());
};

const mudarPagina = (page) => {
  buscarAtletas(page);
};

watch(search, () => {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => buscarAtletas(1), 300);
});

watch(filters, () => {
  buscarAtletas(1);
}, { deep: true });

const limparFiltros = () => {
  filters.value = {
    acesso_ativo: '',
    pode_participar_competicoes: '',
    cidade: '',
    titulo_verificado: '',
    idade_min: '',
    idade_max: ''
  };
  search.value = '';
  buscarAtletas(1);
};

const calcularIdade = (dataNascimento) => {
  if (!dataNascimento) return '-';
  const nascimento = new Date(dataNascimento);
  const hoje = new Date();
  let idade = hoje.getFullYear() - nascimento.getFullYear();
  const mes = hoje.getMonth() - nascimento.getMonth();
  if (mes < 0 || (mes === 0 && hoje.getDate() < nascimento.getDate())) {
    idade--;
  }
  return idade < 0 ? '-' : idade;
};

const isMenorDe16 = (dataNascimento) => {
  const idade = calcularIdade(dataNascimento);
  return idade !== '-' && idade < 16;
};

const atletasPendentes = computed(() =>
  store.atletas.filter((atleta) => Boolean(atleta.validacao_eleitoral_pendente))
);

const baixarFicha = async (atleta) => {
  try {
    pdfLoadingId.value = atleta.id;
    const response = await atletaService.downloadFichaAtleta(atleta.id);
    const blob = new Blob([response.data], { type: 'application/pdf' });
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `ficha_atleta_${atleta.id}.pdf`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    window.URL.revokeObjectURL(url);
  } catch (error) {
    toast.error(error.response?.data?.message || 'Erro ao gerar ficha do atleta.');
  } finally {
    pdfLoadingId.value = null;
  }
};

const alternarAcesso = async (atleta) => {
  const ok = await store.updateAtletaStatus(atleta.id, {
    acesso_ativo: !atleta.acesso_ativo,
    pode_participar_competicoes: atleta.pode_participar_competicoes
  });
  if (!ok && store.error) {
    toast.error(store.error);
  }
};

const alternarCompeticao = async (atleta) => {
  const ok = await store.updateAtletaStatus(atleta.id, {
    acesso_ativo: atleta.acesso_ativo,
    pode_participar_competicoes: !atleta.pode_participar_competicoes
  });
  if (!ok && store.error) {
    toast.error(store.error);
  }
};

const deletarAtleta = async (id) => {
  const accepted = await confirm.ask('Excluir este atleta? Esta ação não pode ser desfeita.', 'Excluir atleta');
  if (!accepted) return;
  const ok = await store.deleteAtleta(id);
  if (!ok && store.error) {
    toast.error(store.error);
  } else {
    toast.success('Atleta excluído.');
  }
};

const revalidarTitulo = async (atleta) => {
  validandoTituloId.value = atleta.id;
  const resultado = await store.revalidarTitulo(atleta.id);
  if (resultado) {
    toast.success('Título validado com sucesso.');
  } else if (store.error) {
    toast.error(store.error);
  }
  validandoTituloId.value = null;
};

const aprovarDadosEleitoraisManual = async (atleta) => {
  const resultado = await store.aprovarDadosEleitoraisManual(atleta.id);
  if (resultado) {
    toast.success('Dados eleitorais aprovados manualmente.');
  } else if (store.error) {
    toast.error(store.error);
  }
};

const resetPassword = async (atleta) => {
  const accepted = await confirm.ask(`Resetar a senha de "${atleta.nome}"? A nova senha será os últimos 4 dígitos do CPF.`, 'Resetar senha');
  if (!accepted) return;
  const resultado = await store.resetPassword(atleta.id);
  if (resultado) {
    toast.success(`Senha resetada. Nova senha: ${resultado.nova_senha}`);
  } else if (store.error) {
    toast.error(store.error);
  }
};

const gerarUsuario = async (atleta) => {
  const accepted = await confirm.ask(`Gerar usuário para "${atleta.nome}"? A senha inicial será os últimos 4 dígitos do CPF.`, 'Gerar usuário');
  if (!accepted) return;
  const resultado = await store.gerarUsuario(atleta.id);
  if (resultado) {
    toast.success(`Usuário gerado. Senha inicial: ${resultado.nova_senha}`);
  } else if (store.error) {
    toast.error(store.error);
  }
};
</script>
