<template>
  <div class="space-y-5">
    <!-- Contadores por prova -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
      <div class="bg-white rounded-xl border border-gray-200 p-3">
        <div class="text-xs text-gray-500">Total filtrado</div>
        <div class="text-2xl font-bold text-gray-900">{{ inscricoes.length }}</div>
      </div>
      <div
        v-for="item in contadoresPorProva"
        :key="item.nome"
        class="bg-white rounded-xl border border-gray-200 p-3"
      >
        <div class="text-xs text-gray-500 truncate" :title="item.nome">{{ item.nome }}</div>
        <div class="text-2xl font-bold text-blue-700">{{ item.total }}</div>
      </div>
    </div>

    <!-- Filtros -->
    <div class="bg-white rounded-xl border border-gray-200 p-4 space-y-3">
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
        <input
          v-model="filtros.search"
          type="text"
          placeholder="Buscar por nome, CPF ou nº peito..."
          class="lg:col-span-2 w-full px-3.5 py-2.5 border border-gray-300 rounded-lg text-sm"
          @input="debouncedLoad"
        />

        <select v-model="filtros.categoria_id" @change="carregar" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm bg-white">
          <option value="">Todas as provas</option>
          <option v-for="cat in categorias" :key="cat.id" :value="cat.id">{{ cat.nome }}</option>
        </select>

        <select v-model="filtros.genero" @change="carregar" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm bg-white">
          <option value="">Todos os gêneros</option>
          <option value="M">Masculino</option>
          <option value="F">Feminino</option>
        </select>

        <select v-model="filtros.status" @change="carregar" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm bg-white">
          <option value="">Todos os status</option>
          <option value="pendente">Pendente</option>
          <option value="confirmada">Confirmada</option>
          <option value="cancelada">Cancelada</option>
        </select>

        <div class="flex gap-2">
          <select v-model="filtros.contribuicao_recebida" @change="carregar" class="w-1/2 px-2 py-2.5 border border-gray-300 rounded-lg text-xs bg-white">
            <option value="">Contrib.</option>
            <option value="1">Recebida</option>
            <option value="0">Pendente</option>
          </select>
          <select v-model="filtros.kit_retirado" @change="carregar" class="w-1/2 px-2 py-2.5 border border-gray-300 rounded-lg text-xs bg-white">
            <option value="">Kit</option>
            <option value="1">Retirado</option>
            <option value="0">Não</option>
          </select>
        </div>
      </div>

      <div class="flex justify-between items-center pt-1">
        <button type="button" @click="limparFiltros" class="text-xs font-medium text-gray-500 hover:text-gray-800">
          Limpar filtros
        </button>
        <button
          type="button"
          @click="exportarCsv"
          class="px-3 py-1.5 rounded-lg bg-emerald-600 text-white text-xs font-semibold hover:bg-emerald-700"
        >
          Exportar CSV
        </button>
      </div>
    </div>

    <!-- Lista -->
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
      <div v-if="loading" class="p-8 text-center text-gray-500 text-sm">Carregando inscritos...</div>
      <div v-else-if="inscricoes.length === 0" class="p-8 text-center text-gray-500 text-sm">
        Nenhum inscrito encontrado com os filtros atuais.
      </div>
      <div v-else class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
          <thead class="bg-gray-50 text-xs uppercase text-gray-500">
            <tr>
              <th class="px-3 py-3 text-left">Peito</th>
              <th class="px-3 py-3 text-left">Participante</th>
              <th class="px-3 py-3 text-left">Prova</th>
              <th class="px-3 py-3 text-left">Status</th>
              <th class="px-3 py-3 text-left">Contribuição</th>
              <th class="px-3 py-3 text-left">Kit</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="insc in inscricoes" :key="insc.id" class="hover:bg-gray-50">
              <td class="px-3 py-3 font-bold text-blue-700 whitespace-nowrap">
                {{ insc.numero_peito ? `#${formatNumeroPeito(insc.numero_peito)}` : '—' }}
              </td>
              <td class="px-3 py-3">
                <div class="font-semibold text-gray-900">{{ insc.atleta?.nome }}</div>
                <div class="text-xs text-gray-500">
                  {{ insc.atleta?.cpf || 'Sem CPF' }} · {{ insc.atleta?.telefone || 'Sem fone' }}
                  <span v-if="insc.menor_idade" class="ml-1 px-1.5 py-0.5 rounded bg-amber-100 text-amber-800 font-semibold">Menor</span>
                </div>
              </td>
              <td class="px-3 py-3 whitespace-nowrap">{{ insc.categoria?.nome || 'Geral' }}</td>
              <td class="px-3 py-3 whitespace-nowrap">
                <span
                  class="px-2 py-0.5 rounded-full text-xs font-semibold uppercase"
                  :class="insc.status === 'confirmada' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'"
                >
                  {{ insc.status }}
                </span>
              </td>
              <td class="px-3 py-3 whitespace-nowrap text-xs">
                <span v-if="insc.contribuicao_recebida_em" class="text-emerald-700 font-semibold">
                  Recebida ({{ insc.forma_contribuicao || '-' }})
                </span>
                <span v-else class="text-gray-500">
                  Pendente ({{ insc.forma_contribuicao || '-' }})
                </span>
              </td>
              <td class="px-3 py-3 whitespace-nowrap text-xs">
                <span v-if="insc.kit_retirado_em" class="text-blue-700 font-semibold">Retirado</span>
                <span v-else class="text-gray-400">Aguardando</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { formatNumeroPeito } from '@/utils/formatNumeroPeito';
import { useRoute } from 'vue-router';
import inscricaoService from '@/modules/inscricoes/services/inscricaoService';

const props = defineProps({ campeonato: { type: Object, default: null } });
const route = useRoute();
const inscricoes = ref([]);
const loading = ref(false);
let timer = null;

const filtros = reactive({
  search: '',
  categoria_id: '',
  genero: '',
  status: '',
  contribuicao_recebida: '',
  kit_retirado: '',
});

const categorias = computed(() => props.campeonato?.categorias || []);

const contadoresPorProva = computed(() => {
  const map = new Map();
  inscricoes.value.forEach((i) => {
    const nome = i.categoria?.nome || 'Geral';
    map.set(nome, (map.get(nome) || 0) + 1);
  });
  return Array.from(map.entries()).map(([nome, total]) => ({ nome, total }));
});

const carregar = async () => {
  loading.value = true;
  try {
    const params = {};
    Object.entries(filtros).forEach(([k, v]) => {
      if (v !== '' && v !== null) params[k] = v;
    });
    const resp = await inscricaoService.getInscricoesPorCampeonato(route.params.id, params);
    inscricoes.value = resp.data?.data || [];
  } catch (e) {
    console.error('Erro ao carregar inscritos', e);
  } finally {
    loading.value = false;
  }
};

const debouncedLoad = () => {
  clearTimeout(timer);
  timer = setTimeout(carregar, 300);
};

const limparFiltros = () => {
  Object.keys(filtros).forEach((k) => { filtros[k] = ''; });
  carregar();
};

const exportarCsv = () => {
  const header = ['Peito', 'Nome', 'CPF', 'Telefone', 'Prova', 'Status', 'Contribuicao', 'Recebida', 'KitRetirado'];
  const rows = inscricoes.value.map((i) => [
    formatNumeroPeito(i.numero_peito),
    `"${(i.atleta?.nome || '').replace(/"/g, '""')}"`,
    i.atleta?.cpf || '',
    i.atleta?.telefone || '',
    `"${(i.categoria?.nome || 'Geral').replace(/"/g, '""')}"`,
    i.status || '',
    i.forma_contribuicao || '',
    i.contribuicao_recebida_em ? 'Sim' : 'Nao',
    i.kit_retirado_em ? 'Sim' : 'Nao',
  ]);
  const csv = [header.join(';'), ...rows.map((r) => r.join(';'))].join('\n');
  const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });
  const link = document.createElement('a');
  link.href = URL.createObjectURL(blob);
  link.download = `inscritos_corrida_${route.params.id}.csv`;
  link.click();
};

onMounted(carregar);
</script>
