<template>
  <div class="space-y-5">
    <!-- Resumo arrecadado -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
      <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-4">
        <div class="text-xs font-semibold uppercase text-emerald-700">Alimentos arrecadados</div>
        <div class="text-2xl font-bold text-emerald-900">{{ resumo.alimentosKg.toFixed(1) }} kg</div>
      </div>
      <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
        <div class="text-xs font-semibold uppercase text-blue-700">Brinquedos arrecadados</div>
        <div class="text-2xl font-bold text-blue-900">{{ resumo.brinquedos }}</div>
      </div>
      <div class="bg-amber-50 border border-amber-200 rounded-xl p-4">
        <div class="text-xs font-semibold uppercase text-amber-700">Em dinheiro</div>
        <div class="text-2xl font-bold text-amber-900">R$ {{ resumo.dinheiro.toFixed(2).replace('.', ',') }}</div>
      </div>
    </div>

    <!-- Busca rápida -->
    <div class="bg-white rounded-xl border border-gray-200 p-4 flex flex-col sm:flex-row gap-3">
      <input
        v-model="search"
        type="text"
        placeholder="Busca rápida por nome, CPF ou nº de peito..."
        class="flex-1 px-4 py-3 border border-gray-300 rounded-lg text-base"
        @input="debouncedLoad"
      />
      <select v-model="filtroRecebida" @change="carregar" class="px-4 py-3 border border-gray-300 rounded-lg text-sm bg-white">
        <option value="0">Pendentes de contribuição</option>
        <option value="1">Já confirmadas</option>
        <option value="">Todas</option>
      </select>
    </div>

    <div v-if="mensagemSucesso" class="rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 text-sm font-semibold">
      {{ mensagemSucesso }}
    </div>

    <!-- Cards/Lista mobile-friendly -->
    <div v-if="loading" class="p-8 text-center text-gray-500">Carregando...</div>
    <div v-else-if="inscricoes.length === 0" class="bg-white rounded-xl border border-gray-200 p-8 text-center text-gray-500">
      Nenhuma inscrição encontrada.
    </div>
    <div v-else class="space-y-3">
      <div
        v-for="insc in inscricoes"
        :key="insc.id"
        class="bg-white rounded-xl border border-gray-200 p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3"
      >
        <div>
          <div class="flex items-center gap-2 flex-wrap">
            <span v-if="insc.numero_peito" class="px-2.5 py-0.5 rounded-full bg-blue-600 text-white text-xs font-bold">
              Peito #{{ insc.numero_peito }}
            </span>
            <span class="font-bold text-gray-900 text-base">{{ insc.atleta?.nome }}</span>
          </div>
          <div class="text-xs text-gray-600 mt-1">
            {{ insc.categoria?.nome || 'Geral' }} · CPF: {{ insc.atleta?.cpf || 'Sem CPF' }} · Forma escolhida:
            <strong class="uppercase">{{ insc.forma_contribuicao || 'não informada' }}</strong>
          </div>
        </div>

        <div class="flex items-center gap-2">
          <span v-if="insc.contribuicao_recebida_em" class="px-3 py-1.5 rounded-lg bg-emerald-100 text-emerald-800 text-xs font-bold">
            Confirmada
          </span>
          <button
            v-else
            type="button"
            @click="abrirModal(insc)"
            class="w-full sm:w-auto px-4 py-2.5 rounded-lg bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700 min-h-[44px]"
          >
            Confirmar contribuição
          </button>
        </div>
      </div>
    </div>

    <!-- Modal Confirmar Contribuição -->
    <div v-if="modalInscricao" class="fixed inset-0 bg-black/50 flex items-center justify-center p-4 z-50">
      <div class="bg-white rounded-2xl shadow-xl max-w-md w-full p-5 space-y-4">
        <h3 class="text-lg font-bold text-gray-900">Confirmar contribuição</h3>
        <p class="text-sm text-gray-600">
          Participante: <strong>{{ modalInscricao.atleta?.nome }}</strong> ({{ modalInscricao.categoria?.nome }})
        </p>

        <div v-if="modalErro" class="rounded-lg bg-red-50 border border-red-200 text-red-700 p-3 text-sm">
          {{ modalErro }}
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Forma recebida</label>
          <select v-model="modalForm.forma_contribuicao" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg bg-white">
            <option value="alimento">Alimento (kg)</option>
            <option value="brinquedo">Brinquedo</option>
            <option value="dinheiro">Dinheiro</option>
          </select>
        </div>

        <div v-if="modalForm.forma_contribuicao === 'alimento'">
          <label class="block text-sm font-medium text-gray-700 mb-1">Quantidade (kg)</label>
          <input v-model="modalForm.contribuicao_quantidade" type="number" step="0.5" min="0" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg" />
        </div>

        <div v-if="modalForm.forma_contribuicao === 'dinheiro'">
          <label class="block text-sm font-medium text-gray-700 mb-1">Valor pago (R$)</label>
          <input v-model="modalForm.valor_pago" type="number" step="0.01" min="0" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg" />
        </div>

        <div class="flex gap-2 justify-end pt-2">
          <button type="button" @click="modalInscricao = null" class="px-4 py-2 rounded-lg border border-gray-300 text-sm font-medium text-gray-700">
            Cancelar
          </button>
          <button
            type="button"
            :disabled="salvando"
            @click="confirmar"
            class="px-4 py-2 rounded-lg bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700 disabled:opacity-60"
          >
            {{ salvando ? 'Confirmando...' : 'Confirmar e gerar peito' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import inscricaoService from '@/modules/inscricoes/services/inscricaoService';

const route = useRoute();
const inscricoes = ref([]);
const todas = ref([]);
const loading = ref(false);
const search = ref('');
const filtroRecebida = ref('0');
const modalInscricao = ref(null);
const modalErro = ref('');
const salvando = ref(false);
const mensagemSucesso = ref('');
let timer = null;

const modalForm = reactive({
  forma_contribuicao: 'alimento',
  contribuicao_quantidade: '',
  valor_pago: '',
});

const resumo = computed(() => {
  let alimentosKg = 0;
  let brinquedos = 0;
  let dinheiro = 0;
  todas.value.forEach((i) => {
    if (!i.contribuicao_recebida_em) return;
    if (i.forma_contribuicao === 'alimento') alimentosKg += Number(i.contribuicao_quantidade || 0);
    if (i.forma_contribuicao === 'brinquedo') brinquedos += Number(i.contribuicao_quantidade || 1);
    if (i.forma_contribuicao === 'dinheiro') dinheiro += Number(i.valor_pago || 0);
  });
  return { alimentosKg, brinquedos, dinheiro };
});

const carregar = async () => {
  loading.value = true;
  try {
    const [respTodas, respFiltrada] = await Promise.all([
      inscricaoService.getInscricoesPorCampeonato(route.params.id),
      inscricaoService.getInscricoesPorCampeonato(route.params.id, {
        ...(search.value ? { search: search.value } : {}),
        ...(filtroRecebida.value !== '' ? { contribuicao_recebida: filtroRecebida.value } : {}),
      }),
    ]);
    todas.value = respTodas.data?.data || [];
    inscricoes.value = respFiltrada.data?.data || [];
  } catch (e) {
    console.error('Erro ao carregar contribuições', e);
  } finally {
    loading.value = false;
  }
};

const debouncedLoad = () => {
  clearTimeout(timer);
  timer = setTimeout(carregar, 300);
};

const abrirModal = (insc) => {
  modalErro.value = '';
  modalInscricao.value = insc;
  modalForm.forma_contribuicao = insc.forma_contribuicao || 'alimento';
  modalForm.contribuicao_quantidade = insc.categoria?.contribuicao_alimento_kg ?? '';
  modalForm.valor_pago = insc.categoria?.valor_inscricao ?? '';
};

const confirmar = async () => {
  if (!modalInscricao.value) return;
  salvando.value = true;
  modalErro.value = '';
  try {
    const resp = await inscricaoService.confirmarContribuicao(modalInscricao.value.id, {
      forma_contribuicao: modalForm.forma_contribuicao,
      contribuicao_quantidade: modalForm.forma_contribuicao === 'alimento' ? modalForm.contribuicao_quantidade : undefined,
      valor_pago: modalForm.forma_contribuicao === 'dinheiro' ? modalForm.valor_pago : undefined,
    });
    const atualizada = resp.data?.data;
    mensagemSucesso.value = `Contribuição confirmada para ${modalInscricao.value.atleta?.nome}! Número de peito gerado: #${atualizada?.numero_peito}`;
    modalInscricao.value = null;
    await carregar();
  } catch (e) {
    modalErro.value = e.response?.data?.message || 'Erro ao confirmar contribuição.';
  } finally {
    salvando.value = false;
  }
};

onMounted(carregar);
</script>
