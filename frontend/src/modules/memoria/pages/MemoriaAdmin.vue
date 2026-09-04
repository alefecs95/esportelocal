<template>
  <div class="p-6">
    <div class="mb-6 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-gray-800">Memória do Esporte</h1>
        <p class="text-gray-500 mt-1">Curadoria da linha do tempo, personalidades e acervo histórico.</p>
      </div>
      <div class="flex flex-wrap gap-2">
        <router-link to="/admin/memoria/marcos/novo" class="bg-blue-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-blue-700">Novo marco</router-link>
        <router-link to="/admin/memoria/personalidades/nova" class="bg-white border border-gray-200 px-4 py-2 rounded-lg font-medium hover:bg-gray-50">Nova personalidade</router-link>
        <router-link to="/admin/memoria/acervo/novo" class="bg-white border border-gray-200 px-4 py-2 rounded-lg font-medium hover:bg-gray-50">Novo item do acervo</router-link>
      </div>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-5 gap-3 mb-6">
      <div v-for="card in cards" :key="card.label" class="bg-white border border-gray-200 rounded-xl p-4">
        <p class="text-xs uppercase tracking-wide text-gray-500">{{ card.label }}</p>
        <p class="text-2xl font-bold text-gray-900 mt-1">{{ card.value }}</p>
      </div>
    </div>

    <div class="flex flex-wrap gap-2 mb-4">
      <button
        v-for="tab in tabs"
        :key="tab.id"
        type="button"
        class="px-4 py-2 rounded-full text-sm font-semibold border"
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
    <div v-else-if="error" class="bg-red-100 text-red-700 p-4 rounded-lg">{{ error }}</div>

    <div v-else class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
      <table v-if="activeTab === 'marcos'" class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Marco</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ano</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Ações</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr v-for="item in marcos" :key="item.id" class="hover:bg-gray-50">
            <td class="px-4 py-3 text-sm font-medium text-gray-900">
              {{ item.titulo }}
              <span v-if="item.destaque" class="ml-2 text-[10px] uppercase bg-yellow-100 text-yellow-800 px-2 py-0.5 rounded-full">Destaque</span>
            </td>
            <td class="px-4 py-3 text-sm text-gray-600">{{ item.ano || '—' }}</td>
            <td class="px-4 py-3 text-sm">{{ item.publicado ? 'Publicado' : 'Rascunho' }}</td>
            <td class="px-4 py-3 text-sm text-right space-x-3">
              <router-link :to="`/admin/memoria/marcos/${item.id}/editar`" class="text-blue-600 font-medium">Editar</router-link>
              <button class="text-red-600 font-medium" @click="remove('marco', item.id)">Excluir</button>
            </td>
          </tr>
          <tr v-if="marcos.length === 0">
            <td colspan="4" class="px-4 py-8 text-center text-gray-500">Nenhum marco cadastrado.</td>
          </tr>
        </tbody>
      </table>

      <table v-else-if="activeTab === 'pessoas'" class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nome</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Papel</th>
            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Ações</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr v-for="item in personalidades" :key="item.id" class="hover:bg-gray-50">
            <td class="px-4 py-3 text-sm font-medium">{{ item.nome }}</td>
            <td class="px-4 py-3 text-sm capitalize text-gray-600">{{ item.papel }}</td>
            <td class="px-4 py-3 text-sm text-right space-x-3">
              <router-link :to="`/admin/memoria/personalidades/${item.id}/editar`" class="text-blue-600 font-medium">Editar</router-link>
              <button class="text-red-600 font-medium" @click="remove('pessoa', item.id)">Excluir</button>
            </td>
          </tr>
          <tr v-if="personalidades.length === 0">
            <td colspan="3" class="px-4 py-8 text-center text-gray-500">Nenhuma personalidade cadastrada.</td>
          </tr>
        </tbody>
      </table>

      <table v-else-if="activeTab === 'acervo'" class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Item</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Origem</th>
            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Ações</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr v-for="item in acervo" :key="item.id" class="hover:bg-gray-50">
            <td class="px-4 py-3 text-sm font-medium">{{ item.titulo }}</td>
            <td class="px-4 py-3 text-sm capitalize">{{ item.tipo }}</td>
            <td class="px-4 py-3 text-sm">{{ item.origem }}</td>
            <td class="px-4 py-3 text-sm text-right space-x-3">
              <router-link :to="`/admin/memoria/acervo/${item.id}/editar`" class="text-blue-600 font-medium">Editar</router-link>
              <button class="text-red-600 font-medium" @click="remove('acervo', item.id)">Excluir</button>
            </td>
          </tr>
          <tr v-if="acervo.length === 0">
            <td colspan="4" class="px-4 py-8 text-center text-gray-500">Acervo vazio.</td>
          </tr>
        </tbody>
      </table>

      <div v-else-if="activeTab === 'pendentes'" class="divide-y">
        <div v-if="pendentes.length === 0" class="px-4 py-8 text-center text-gray-500">Nenhuma contribuição aguardando moderação.</div>
        <div v-for="item in pendentes" :key="item.id" class="p-4 flex flex-col md:flex-row gap-4">
          <img v-if="item.tipo === 'foto' && item.arquivo_url" :src="item.arquivo_url" class="w-full md:w-40 h-28 object-cover rounded-lg bg-gray-100" alt="" />
          <div class="flex-1 min-w-0">
            <h3 class="font-semibold text-gray-900">{{ item.titulo }}</h3>
            <p class="text-sm text-gray-600 mt-1">{{ item.descricao || 'Sem descrição' }}</p>
            <p class="text-xs text-gray-500 mt-2">Doação: {{ item.doador_nome }} · {{ item.doador_email }}</p>
            <div class="flex flex-wrap gap-2 mt-3">
              <button class="bg-green-600 text-white px-3 py-1.5 rounded-lg text-sm font-medium" @click="aprovar(item)">Aprovar</button>
              <button class="bg-red-50 text-red-700 px-3 py-1.5 rounded-lg text-sm font-medium" @click="rejeitar(item)">Rejeitar</button>
            </div>
          </div>
        </div>
      </div>

      <div v-else class="p-4 space-y-4">
        <form class="flex flex-wrap gap-3 items-end" @submit.prevent="salvarLocal">
          <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Nome do local</label>
            <input v-model="localForm.nome" class="rounded-lg border-gray-300" required />
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Tipo</label>
            <select v-model="localForm.tipo" class="rounded-lg border-gray-300">
              <option value="ginasio">Ginásio</option>
              <option value="estadio">Estádio</option>
              <option value="quadra">Quadra</option>
              <option value="pista">Pista</option>
              <option value="outro">Outro</option>
            </select>
          </div>
          <button class="bg-blue-600 text-white px-4 py-2 rounded-lg font-medium">Salvar local</button>
        </form>
        <ul class="divide-y">
          <li v-for="item in locais" :key="item.id" class="py-3 flex justify-between gap-3">
            <span>{{ item.nome }} <span class="text-xs text-gray-500 capitalize">{{ item.tipo }}</span></span>
            <button class="text-red-600 text-sm" @click="remove('local', item.id)">Excluir</button>
          </li>
        </ul>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import memoriaService from '../services/memoriaService';

const tabs = [
  { id: 'marcos', label: 'Linha do tempo' },
  { id: 'pessoas', label: 'Personalidades' },
  { id: 'acervo', label: 'Acervo' },
  { id: 'pendentes', label: 'Contribuições' },
  { id: 'locais', label: 'Locais' },
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
const localForm = ref({ nome: '', tipo: 'ginasio' });

const cards = computed(() => [
  { label: 'Marcos', value: stats.value.marcos || 0 },
  { label: 'Personalidades', value: stats.value.personalidades || 0 },
  { label: 'Acervo', value: stats.value.acervo || 0 },
  { label: 'Pendentes', value: stats.value.pendentes || 0 },
  { label: 'Locais', value: stats.value.locais || 0 },
]);

const load = async () => {
  loading.value = true;
  error.value = '';
  try {
    const [dash, m, p, a, pend, l] = await Promise.all([
      memoriaService.dashboard(),
      memoriaService.marcos(),
      memoriaService.personalidades(),
      memoriaService.acervo({ per_page: 30 }),
      memoriaService.acervo({ status: 'pendente', per_page: 50 }),
      memoriaService.locais(),
    ]);
    stats.value = dash.data || {};
    marcos.value = m.data || [];
    personalidades.value = p.data || [];
    acervo.value = a.data?.data || a.data || [];
    pendentes.value = pend.data?.data || pend.data || [];
    locais.value = l.data || [];
  } catch (e) {
    error.value = 'Erro ao carregar a Memória.';
  } finally {
    loading.value = false;
  }
};

const remove = async (tipo, id) => {
  if (!confirm('Excluir este registro?')) return;
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

const rejeitar = async (item) => {
  const motivo = prompt('Motivo da rejeição (opcional):') || '';
  await memoriaService.rejeitar(item.id, { motivo_rejeicao: motivo });
  await load();
};

const salvarLocal = async () => {
  const fd = new FormData();
  fd.append('nome', localForm.value.nome);
  fd.append('tipo', localForm.value.tipo);
  fd.append('publicado', '1');
  await memoriaService.createLocal(fd);
  localForm.value = { nome: '', tipo: 'ginasio' };
  await load();
};

onMounted(load);
</script>
