<template>
  <div class="p-4 sm:p-6">
    <div class="mb-6 flex flex-col lg:flex-row lg:items-end lg:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-gray-800">Memória do Esporte</h1>
        <p class="text-gray-500 mt-1">Cadastre fatos, pessoas e fotos. O que estiver publicado aparece no portal da cidade.</p>
      </div>
      <div class="flex flex-wrap gap-2">
        <router-link to="/admin/memoria/marcos/novo" class="bg-blue-600 text-white px-4 py-2.5 rounded-xl font-semibold min-h-11 inline-flex items-center">Novo fato</router-link>
        <router-link to="/admin/memoria/personalidades/nova" class="bg-white border border-gray-200 px-4 py-2.5 rounded-xl font-semibold min-h-11 inline-flex items-center">Nova pessoa</router-link>
        <router-link to="/admin/memoria/acervo/novo" class="bg-white border border-gray-200 px-4 py-2.5 rounded-xl font-semibold min-h-11 inline-flex items-center">Nova mídia</router-link>
      </div>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-5 gap-3 mb-6">
      <button
        v-for="card in cards"
        :key="card.tab"
        type="button"
        class="bg-white border rounded-2xl p-4 text-left hover:border-blue-300 transition-colors"
        :class="activeTab === card.tab ? 'border-blue-600 ring-2 ring-blue-100' : 'border-gray-200'"
        @click="activeTab = card.tab"
      >
        <p class="text-xs uppercase tracking-wide text-gray-500">{{ card.label }}</p>
        <p class="text-2xl font-bold text-gray-900 mt-1">{{ card.value }}</p>
        <p v-if="card.hint" class="text-xs text-gray-500 mt-1">{{ card.hint }}</p>
      </button>
    </div>
    <p v-if="stats.orfaos" class="mb-4 text-sm text-amber-800 bg-amber-50 border border-amber-100 rounded-xl px-4 py-3">
      {{ stats.orfaos }} mídia(s) aprovada(s) sem vínculo com fato ou pessoa — aparecem só na galeria.
      <button type="button" class="font-semibold underline ml-1" @click="filtrarOrfaos">Ver órfãs</button>
    </p>
    <p v-if="stats.sem_alt" class="mb-4 text-sm text-slate-700 bg-slate-50 border border-slate-200 rounded-xl px-4 py-3">
      {{ stats.sem_alt }} foto(s) sem texto alternativo — preencha o alt na edição para acessibilidade.
    </p>

    <div class="flex gap-2 overflow-x-auto pb-3 mb-4">
      <button
        v-for="tab in tabs"
        :key="tab.id"
        type="button"
        class="shrink-0 px-4 py-2 rounded-full text-sm font-semibold border min-h-11"
        :class="activeTab === tab.id ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-600 border-gray-200'"
        @click="activeTab = tab.id"
      >
        {{ tab.label }}
        <span v-if="tab.id === 'pendentes' && stats.pendentes" class="ml-1 bg-yellow-400 text-blue-900 text-xs px-1.5 py-0.5 rounded-full">{{ stats.pendentes }}</span>
      </button>
    </div>

    <div v-if="loading" class="text-center py-12">
      <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-blue-600 mx-auto"></div>
    </div>
    <div v-else-if="error" class="bg-red-100 text-red-700 p-4 rounded-xl">{{ error }}</div>

    <div v-else class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
      <div v-if="activeTab === 'marcos'" class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fato histórico</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Quando</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">No portal</th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Ações</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="item in marcos" :key="item.id" class="hover:bg-gray-50">
              <td class="px-4 py-3 text-sm font-medium text-gray-900">
                <div class="flex items-center gap-3">
                  <img v-if="item.capa_url" :src="item.capa_url" class="h-12 w-16 rounded-lg object-cover bg-gray-100 shrink-0" alt="" />
                  <div>
                    <p>{{ item.titulo }}</p>
                    <p class="text-xs text-gray-500 font-normal mt-0.5">{{ tipoMarcoLabels[item.tipo] || item.tipo }}</p>
                  </div>
                </div>
              </td>
              <td class="px-4 py-3 text-sm text-gray-600">{{ item.ano || 'Sem ano' }}</td>
              <td class="px-4 py-3 text-sm">
                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-semibold" :class="item.publicado ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600'">
                  {{ item.publicado ? 'Publicado' : 'Rascunho' }}
                </span>
                <span v-if="item.destaque" class="ml-1 text-[10px] uppercase bg-yellow-100 text-yellow-800 px-2 py-0.5 rounded-full">Home</span>
              </td>
              <td class="px-4 py-3 text-sm text-right space-x-3 whitespace-nowrap">
                <router-link :to="`/admin/memoria/marcos/${item.id}/editar`" class="text-blue-600 font-medium">Editar</router-link>
                <button class="text-gray-600 font-medium" @click="duplicar('marco', item.id)">Duplicar</button>
                <button class="text-red-600 font-medium" @click="remove('marco', item.id)">Excluir</button>
              </td>
            </tr>
          </tbody>
        </table>
        <div v-if="marcos.length === 0" class="px-6 py-12 text-center">
          <p class="text-gray-700 font-semibold">A linha do tempo ainda está vazia</p>
          <p class="text-sm text-gray-500 mt-1">Comece por um fato que a cidade inteira conhece.</p>
          <router-link to="/admin/memoria/marcos/novo" class="inline-flex mt-4 bg-blue-600 text-white px-4 py-2 rounded-xl font-semibold">Cadastrar primeiro fato</router-link>
        </div>
      </div>

      <div v-else-if="activeTab === 'pessoas'" class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pessoa</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Atuação</th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Ações</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="item in personalidades" :key="item.id" class="hover:bg-gray-50">
              <td class="px-4 py-3 text-sm font-medium">
                <div class="flex items-center gap-3">
                  <img v-if="item.foto_url" :src="item.foto_url" class="h-10 w-10 rounded-full object-cover bg-gray-100" alt="" />
                  <span>{{ item.nome }}</span>
                </div>
              </td>
              <td class="px-4 py-3 text-sm text-gray-600">{{ papelLabels[item.papel] || item.papel }}</td>
              <td class="px-4 py-3 text-sm text-right space-x-3">
                <router-link :to="`/admin/memoria/personalidades/${item.id}/editar`" class="text-blue-600 font-medium">Editar</router-link>
                <button class="text-gray-600 font-medium" @click="duplicar('pessoa', item.id)">Duplicar</button>
                <button class="text-red-600 font-medium" @click="remove('pessoa', item.id)">Excluir</button>
              </td>
            </tr>
          </tbody>
        </table>
        <div v-if="personalidades.length === 0" class="px-6 py-12 text-center text-gray-500">
          Nenhuma personalidade ainda.
          <router-link to="/admin/memoria/personalidades/nova" class="block mt-3 text-blue-700 font-semibold">Cadastrar ídolo ou dirigente</router-link>
        </div>
      </div>

      <div v-else-if="activeTab === 'acervo'" class="overflow-x-auto">
        <div v-if="soOrfaos" class="px-4 py-3 text-sm bg-amber-50 border-b text-amber-900 flex justify-between gap-3">
          <span>Mostrando só mídias sem fato nem pessoa.</span>
          <button type="button" class="font-semibold underline" @click="limparFiltroOrfaos">Ver todas</button>
        </div>
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mídia</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Origem</th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Ações</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="item in acervo" :key="item.id" class="hover:bg-gray-50">
              <td class="px-4 py-3 text-sm font-medium">
                <div class="flex items-center gap-3">
                  <img v-if="item.tipo === 'foto' && item.arquivo_url" :src="item.arquivo_url" class="h-12 w-16 rounded-lg object-cover bg-gray-100" :alt="item.alt_text || item.titulo" />
                  <div>
                    <span>{{ item.titulo }}</span>
                    <div class="flex flex-wrap gap-1 mt-1">
                      <span v-if="!item.marco_id && !item.personalidade_id" class="text-[10px] uppercase font-bold tracking-wide bg-amber-100 text-amber-800 px-1.5 py-0.5 rounded">Órfã</span>
                      <span v-if="item.tipo === 'foto' && !item.alt_text" class="text-[10px] uppercase font-bold tracking-wide bg-slate-200 text-slate-700 px-1.5 py-0.5 rounded">Sem alt</span>
                    </div>
                  </div>
                </div>
              </td>
              <td class="px-4 py-3 text-sm">{{ tipoAcervoLabels[item.tipo] || item.tipo }}</td>
              <td class="px-4 py-3 text-sm">{{ originLabels[item.origem] || item.origem }}</td>
              <td class="px-4 py-3 text-sm text-right space-x-3">
                <router-link :to="`/admin/memoria/acervo/${item.id}/editar`" class="text-blue-600 font-medium">Editar</router-link>
                <button class="text-red-600 font-medium" @click="remove('acervo', item.id)">Excluir</button>
              </td>
            </tr>
          </tbody>
        </table>
        <div v-if="acervo.length === 0" class="px-6 py-12 text-center text-gray-500">
          {{ soOrfaos ? 'Nenhuma mídia órfã no momento.' : 'O acervo ainda não tem itens publicados.' }}
          <router-link v-if="!soOrfaos" to="/admin/memoria/acervo/novo" class="block mt-3 text-blue-700 font-semibold">Enviar primeira mídia</router-link>
        </div>
      </div>

      <div v-else-if="activeTab === 'pendentes'" class="divide-y">
        <div v-if="pendentes.length" class="p-4 flex flex-wrap gap-2 bg-slate-50 border-b">
          <button type="button" class="px-3 py-2 rounded-xl bg-green-600 text-white text-sm font-semibold" :disabled="!selectedIds.length" @click="lote('aprovar')">Aprovar selecionados ({{ selectedIds.length }})</button>
          <button type="button" class="px-3 py-2 rounded-xl bg-red-50 text-red-700 text-sm font-semibold" :disabled="!selectedIds.length" @click="lote('rejeitar')">Recusar selecionados</button>
        </div>
        <div v-if="pendentes.length === 0" class="px-6 py-12 text-center text-gray-500">
          Nenhuma contribuição da comunidade esperando análise.
        </div>
        <article v-for="item in pendentes" :key="item.id" class="p-4 sm:p-5 flex flex-col md:flex-row gap-4">
          <label class="md:pt-2">
            <input type="checkbox" class="rounded border-gray-300" :value="item.id" v-model="selectedIds" />
          </label>
          <img v-if="item.tipo === 'foto' && item.arquivo_url" :src="item.arquivo_url" class="w-full md:w-44 h-32 object-cover rounded-xl bg-gray-100" alt="" />
          <div class="flex-1 min-w-0">
            <p class="text-xs font-bold uppercase text-amber-700">Aguardando curadoria</p>
            <h3 class="font-semibold text-gray-900 text-lg">{{ item.titulo }}</h3>
            <p class="text-sm text-gray-600 mt-1 whitespace-pre-line">{{ item.descricao || 'Sem descrição da pessoa que enviou.' }}</p>
            <dl class="mt-3 grid sm:grid-cols-2 gap-1 text-xs text-gray-500">
              <div><span class="font-semibold text-gray-700">Quem enviou:</span> {{ item.doador_nome }}</div>
              <div><span class="font-semibold text-gray-700">E-mail:</span> {{ item.doador_email }}</div>
              <div v-if="item.ano"><span class="font-semibold text-gray-700">Ano:</span> {{ item.ano }}</div>
              <div v-if="item.pessoas_envolvidas"><span class="font-semibold text-gray-700">Pessoas:</span> {{ item.pessoas_envolvidas }}</div>
              <div v-if="item.fonte"><span class="font-semibold text-gray-700">Fonte:</span> {{ item.fonte }}</div>
            </dl>
            <div v-if="rejectingId === item.id" class="mt-3 space-y-2">
              <textarea v-model="rejectReason" rows="2" class="w-full rounded-xl border-gray-300 text-sm" placeholder="Motivo visível só internamente (opcional)" />
              <div class="flex gap-2">
                <button type="button" class="bg-red-600 text-white px-3 py-2 rounded-xl text-sm font-semibold" @click="confirmReject(item)">Confirmar rejeição</button>
                <button type="button" class="px-3 py-2 rounded-xl text-sm border" @click="rejectingId = null">Cancelar</button>
              </div>
            </div>
            <div v-else class="flex flex-wrap gap-2 mt-4">
              <button class="bg-green-600 text-white px-4 py-2 rounded-xl text-sm font-semibold min-h-11" @click="aprovar(item)">Aprovar e publicar</button>
              <button class="bg-red-50 text-red-700 px-4 py-2 rounded-xl text-sm font-semibold min-h-11" @click="startReject(item)">Recusar</button>
              <a v-if="item.arquivo_url" :href="item.arquivo_url" target="_blank" class="px-4 py-2 rounded-xl text-sm font-semibold border min-h-11 inline-flex items-center">Abrir arquivo</a>
            </div>
          </div>
        </article>
      </div>

      <div v-else-if="activeTab === 'locais'" class="p-4 sm:p-5 space-y-5">
        <p class="text-sm text-gray-500">Locais aparecem nos filtros da galeria e nos fatos da timeline. Ex.: ginásio antigo, estádio municipal.</p>
        <form class="grid sm:grid-cols-2 lg:grid-cols-4 gap-3 items-end bg-gray-50 p-4 rounded-2xl border" @submit.prevent="salvarLocal">
          <MemoriaField label="Nome do espaço" required>
            <input v-model="localForm.nome" required class="w-full rounded-xl border-gray-300 min-h-11" placeholder="Ginásio Municipal" />
          </MemoriaField>
          <MemoriaField label="Tipo">
            <select v-model="localForm.tipo" class="w-full rounded-xl border-gray-300 min-h-11">
              <option v-for="(label, key) in tipoLocalLabels" :key="key" :value="key">{{ label }}</option>
            </select>
          </MemoriaField>
          <MemoriaField label="Endereço ou bairro" hint="Opcional.">
            <input v-model="localForm.endereco" class="w-full rounded-xl border-gray-300 min-h-11" placeholder="Centro, rua..." />
          </MemoriaField>
          <button class="bg-blue-600 text-white px-4 py-2.5 rounded-xl font-semibold min-h-11">Adicionar local</button>
        </form>
        <ul v-if="locais.length" class="divide-y">
          <li v-for="item in locais" :key="item.id" class="py-3 flex justify-between gap-3 items-center">
            <div>
              <p class="font-medium">{{ item.nome }}</p>
              <p class="text-xs text-gray-500">{{ tipoLocalLabels[item.tipo] || item.tipo }}<span v-if="item.endereco"> · {{ item.endereco }}</span></p>
            </div>
            <button class="text-red-600 text-sm font-medium" @click="remove('local', item.id)">Excluir</button>
          </li>
        </ul>
        <p v-else class="text-sm text-gray-500">Nenhum local cadastrado ainda.</p>
      </div>
      <div v-else class="divide-y">
        <p v-if="!auditoria.length" class="p-6 text-sm text-gray-500">Ainda não há alterações registradas nesta curadoria.</p>
        <article v-for="log in auditoria" :key="log.id" class="px-4 py-3 text-sm">
          <p class="font-medium text-gray-900">{{ log.acao }} · {{ log.entidade_tipo }}<span v-if="log.entidade_id"> #{{ log.entidade_id }}</span></p>
          <p v-if="log.resumo" class="text-gray-700 text-sm">{{ log.resumo }}</p>
          <p class="text-gray-500 text-xs mt-0.5">{{ log.user?.name || 'Sistema' }} · {{ formatDate(log.created_at) }}</p>
        </article>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import memoriaService from '../services/memoriaService';
import MemoriaField from '../components/MemoriaField.vue';
import { originLabels, papelLabels, tipoAcervoLabels, tipoLocalLabels, tipoMarcoLabels } from '../utils/memoriaLabels';

const tabs = [
  { id: 'marcos', label: 'Linha do tempo' },
  { id: 'pessoas', label: 'Pessoas' },
  { id: 'acervo', label: 'Acervo' },
  { id: 'pendentes', label: 'Envios da cidade' },
  { id: 'locais', label: 'Locais' },
  { id: 'auditoria', label: 'Histórico' },
];

const activeTab = ref('marcos');
const loading = ref(true);
const error = ref('');
const stats = ref({});
const marcos = ref([]);
const personalidades = ref([]);
const acervo = ref([]);
const pendentes = ref([]);
const locais = ref([]);
const localForm = ref({ nome: '', tipo: 'ginasio', endereco: '' });
const rejectingId = ref(null);
const rejectReason = ref('');

const router = useRouter();
const selectedIds = ref([]);
const auditoria = ref([]);
const soOrfaos = ref(false);

const cards = computed(() => [
  { tab: 'marcos', label: 'Fatos', value: stats.value.marcos || 0, hint: `${stats.value.marcos_publicados || 0} no ar · ${stats.value.marcos_rascunho || 0} rascunho` },
  { tab: 'pessoas', label: 'Pessoas', value: stats.value.personalidades || 0, hint: `${stats.value.personalidades_publicadas || 0} no ar · ${stats.value.personalidades_rascunho || 0} rascunho` },
  { tab: 'acervo', label: 'Mídias', value: stats.value.acervo || 0, hint: stats.value.orfaos ? `${stats.value.orfaos} órfã(s)` : '' },
  { tab: 'pendentes', label: 'Para revisar', value: stats.value.pendentes || 0 },
  { tab: 'locais', label: 'Locais', value: stats.value.locais || 0 },
]);

const formatDate = (iso) => {
  if (!iso) return '';
  try {
    return new Date(iso).toLocaleString('pt-BR');
  } catch {
    return iso;
  }
};

const load = async () => {
  loading.value = true;
  error.value = '';
  try {
    const [dash, m, p, a, pend, l, logs] = await Promise.all([
      memoriaService.dashboard(),
      memoriaService.marcos(),
      memoriaService.personalidades(),
      memoriaService.acervo({ per_page: 30, ...(soOrfaos.value ? { orfaos: 1 } : {}) }),
      memoriaService.acervo({ status: 'pendente', per_page: 50 }),
      memoriaService.locais(),
      memoriaService.auditoria(),
    ]);
    stats.value = dash.data || {};
    marcos.value = m.data || [];
    personalidades.value = p.data || [];
    acervo.value = a.data?.data || a.data || [];
    pendentes.value = pend.data?.data || pend.data || [];
    locais.value = l.data || [];
    auditoria.value = logs.data || [];
    selectedIds.value = [];
  } catch (e) {
    error.value = 'Não foi possível carregar a Memória.';
  } finally {
    loading.value = false;
  }
};

const filtrarOrfaos = async () => {
  soOrfaos.value = true;
  activeTab.value = 'acervo';
  await load();
};

const limparFiltroOrfaos = async () => {
  soOrfaos.value = false;
  await load();
};

const duplicar = async (tipo, id) => {
  try {
    if (tipo === 'marco') {
      const { data } = await memoriaService.duplicarMarco(id);
      router.push(`/admin/memoria/marcos/${data.id}/editar`);
    } else {
      const { data } = await memoriaService.duplicarPersonalidade(id);
      router.push(`/admin/memoria/personalidades/${data.id}/editar`);
    }
  } catch (e) {
    alert('Não foi possível duplicar.');
  }
};

const lote = async (acao) => {
  if (!selectedIds.value.length) return;
  let motivo = '';
  if (acao === 'rejeitar') {
    motivo = prompt('Motivo da recusa (enviado por e-mail a quem informou contato):') || '';
  }
  try {
    await memoriaService.lote({ ids: selectedIds.value, acao, motivo_rejeicao: motivo });
    await load();
  } catch (e) {
    alert('Não foi possível aplicar a ação em lote.');
  }
};

const remove = async (tipo, id) => {
  if (!confirm('Excluir este registro? Essa ação não publica nada no portal, só remove do cadastro.')) return;
  try {
    if (tipo === 'marco') await memoriaService.deleteMarco(id);
    if (tipo === 'pessoa') await memoriaService.deletePersonalidade(id);
    if (tipo === 'acervo') await memoriaService.deleteAcervo(id);
    if (tipo === 'local') await memoriaService.deleteLocal(id);
    await load();
  } catch (e) {
    alert('Não foi possível excluir.');
  }
};

const aprovar = async (item) => {
  await memoriaService.aprovar(item.id);
  await load();
};

const startReject = (item) => {
  rejectingId.value = item.id;
  rejectReason.value = '';
};

const confirmReject = async (item) => {
  await memoriaService.rejeitar(item.id, { motivo_rejeicao: rejectReason.value });
  rejectingId.value = null;
  await load();
};

const salvarLocal = async () => {
  const fd = new FormData();
  fd.append('nome', localForm.value.nome);
  fd.append('tipo', localForm.value.tipo);
  fd.append('endereco', localForm.value.endereco || '');
  fd.append('publicado', '1');
  await memoriaService.createLocal(fd);
  localForm.value = { nome: '', tipo: 'ginasio', endereco: '' };
  await load();
};

onMounted(load);
</script>
