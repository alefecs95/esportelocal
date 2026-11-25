<template>
  <div class="space-y-5">
    <!-- Busca grande para celular -->
    <div class="bg-white rounded-xl border border-gray-200 p-4 flex flex-col sm:flex-row gap-3">
      <input
        v-model="search"
        type="text"
        placeholder="Buscar por nome, CPF ou nº de peito..."
        class="flex-1 px-4 py-3.5 border border-gray-300 rounded-xl text-base"
        @input="debouncedLoad"
      />
      <select v-model="filtroKit" @change="carregar" class="px-4 py-3.5 border border-gray-300 rounded-xl text-sm bg-white">
        <option value="0">Aguardando retirada</option>
        <option value="1">Já retiraram</option>
        <option value="">Todos</option>
      </select>
    </div>

    <div v-if="mensagemSucesso" class="rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 text-sm font-semibold">
      {{ mensagemSucesso }}
    </div>

    <div v-if="loading" class="p-8 text-center text-gray-500">Carregando...</div>
    <div v-else-if="inscricoes.length === 0" class="bg-white rounded-xl border border-gray-200 p-8 text-center text-gray-500">
      Nenhum participante encontrado.
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
            <span v-if="insc.menor_idade" class="px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 text-xs font-bold">
              Menor de 18
            </span>
          </div>
          <div class="text-xs text-gray-600 mt-1">
            {{ insc.categoria?.nome || 'Geral' }} · Status: <strong class="uppercase">{{ insc.status }}</strong>
            <span v-if="insc.kit_retirado_por_nome"> · Retirado por: {{ insc.kit_retirado_por_nome }} ({{ insc.kit_retirado_por_documento }})</span>
          </div>
        </div>

        <div>
          <span v-if="insc.kit_retirado_em" class="inline-block px-3 py-1.5 rounded-lg bg-blue-100 text-blue-800 text-xs font-bold">
            Kit retirado
          </span>
          <button
            v-else
            type="button"
            @click="abrirModal(insc)"
            class="w-full sm:w-auto px-4 py-3 rounded-xl bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700 min-h-[46px]"
          >
            Retirar kit
          </button>
        </div>
      </div>
    </div>

    <!-- Modal Retirada de Kit -->
    <div v-if="modalInscricao" class="fixed inset-0 bg-black/50 flex items-center justify-center p-4 z-50">
      <div class="bg-white rounded-2xl shadow-xl max-w-md w-full p-5 space-y-4">
        <h3 class="text-lg font-bold text-gray-900">Registrar retirada de kit</h3>
        <div class="text-sm text-gray-700">
          <div><strong>{{ modalInscricao.atleta?.nome }}</strong></div>
          <div class="text-xs text-gray-500">
            {{ modalInscricao.categoria?.nome }} · Peito {{ modalInscricao.numero_peito ? `#${modalInscricao.numero_peito}` : 'não gerado' }}
          </div>
        </div>

        <div v-if="modalInscricao.status !== 'confirmada'" class="rounded-lg bg-red-50 border border-red-200 text-red-700 p-3 text-sm font-medium">
          Inscrição ainda não está confirmada. Confirme a contribuição antes de entregar o kit.
        </div>

        <div v-if="modalErro" class="rounded-lg bg-red-50 border border-red-200 text-red-700 p-3 text-sm">
          {{ modalErro }}
        </div>

        <!-- Terceiro -->
        <label class="flex items-center gap-2 text-sm font-medium text-gray-800">
          <input v-model="modalForm.retirado_por_terceiro" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-blue-600" />
          Retirado por terceiro (com documento do atleta)
        </label>

        <div v-if="modalForm.retirado_por_terceiro" class="space-y-3 rounded-lg bg-gray-50 border border-gray-200 p-3">
          <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Nome de quem está retirando *</label>
            <input v-model="modalForm.kit_retirado_por_nome" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white text-sm" />
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Documento (RG/CPF) *</label>
            <input v-model="modalForm.kit_retirado_por_documento" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white text-sm" />
          </div>
        </div>

        <!-- Menor de 18 -->
        <div v-if="modalInscricao.menor_idade && campeonato?.exige_termo_menor" class="space-y-3 rounded-lg bg-amber-50 border border-amber-200 p-3">
          <div class="text-xs font-bold text-amber-900">Obrigatório para menor de 18 anos:</div>
          <label class="flex items-start gap-2 text-sm text-amber-900">
            <input v-model="modalForm.termo_responsavel_assinado" type="checkbox" class="mt-1 h-4 w-4 rounded border-amber-400 text-amber-600" />
            <span>Termo de autorização assinado pelo responsável foi entregue</span>
          </label>
          <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Nome do responsável *</label>
            <input v-model="modalForm.responsavel_nome" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white text-sm" />
          </div>
        </div>

        <div class="flex gap-2 justify-end pt-2">
          <button type="button" @click="modalInscricao = null" class="px-4 py-2 rounded-lg border border-gray-300 text-sm font-medium text-gray-700">
            Cancelar
          </button>
          <button
            type="button"
            :disabled="salvando || modalInscricao.status !== 'confirmada'"
            @click="confirmarRetirada"
            class="px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700 disabled:opacity-50"
          >
            {{ salvando ? 'Salvando...' : 'Confirmar entrega do kit' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import inscricaoService from '@/modules/inscricoes/services/inscricaoService';

const props = defineProps({ campeonato: { type: Object, default: null } });
const route = useRoute();
const inscricoes = ref([]);
const loading = ref(false);
const search = ref('');
const filtroKit = ref('0');
const modalInscricao = ref(null);
const modalErro = ref('');
const salvando = ref(false);
const mensagemSucesso = ref('');
let timer = null;

const modalForm = reactive({
  retirado_por_terceiro: false,
  kit_retirado_por_nome: '',
  kit_retirado_por_documento: '',
  termo_responsavel_assinado: false,
  responsavel_nome: '',
});

const carregar = async () => {
  loading.value = true;
  try {
    const params = {};
    if (search.value) params.search = search.value;
    if (filtroKit.value !== '') params.kit_retirado = filtroKit.value;
    const resp = await inscricaoService.getInscricoesPorCampeonato(route.params.id, params);
    inscricoes.value = resp.data?.data || [];
  } catch (e) {
    console.error('Erro ao carregar retirada de kit', e);
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
  modalForm.retirado_por_terceiro = false;
  modalForm.kit_retirado_por_nome = '';
  modalForm.kit_retirado_por_documento = '';
  modalForm.termo_responsavel_assinado = Boolean(insc.termo_responsavel_assinado_em);
  modalForm.responsavel_nome = insc.responsavel_nome || insc.atleta?.responsavel_nome || '';
};

const confirmarRetirada = async () => {
  if (!modalInscricao.value) return;
  modalErro.value = '';

  if (modalForm.retirado_por_terceiro && (!modalForm.kit_retirado_por_nome.trim() || !modalForm.kit_retirado_por_documento.trim())) {
    modalErro.value = 'Na retirada por terceiro, informe nome e documento de quem está retirando.';
    return;
  }

  if (modalInscricao.value.menor_idade && props.campeonato?.exige_termo_menor) {
    if (!modalForm.termo_responsavel_assinado || !modalForm.responsavel_nome.trim()) {
      modalErro.value = 'Atleta menor de 18 anos só pode retirar o kit com termo assinado e nome do responsável.';
      return;
    }
  }

  salvando.value = true;
  try {
    await inscricaoService.retirarKit(modalInscricao.value.id, { ...modalForm });
    mensagemSucesso.value = `Kit entregue para ${modalInscricao.value.atleta?.nome}!`;
    modalInscricao.value = null;
    await carregar();
  } catch (e) {
    modalErro.value = e.response?.data?.message || 'Erro ao registrar retirada do kit.';
  } finally {
    salvando.value = false;
  }
};

onMounted(carregar);
</script>
