<template>
  <div class="container mx-auto px-4 py-8">
    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
      <h1 class="text-2xl font-bold text-gray-800">Gestão de Inscrições</h1>
      <router-link to="/admin/inscricoes/nova" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
        + Nova Inscrição
      </router-link>
    </div>

    <!-- Filtro de Campeonato -->
    <div class="bg-white p-4 rounded-lg shadow mb-6">
        <label class="block text-sm font-medium text-gray-700 mb-2">Selecione o Campeonato para ver as inscrições</label>
        <select 
            v-model="selectedCampeonatoId" 
            @change="carregarInscricoes"
            class="block w-full md:w-1/2 border border-gray-300 rounded-md shadow-sm p-2"
        >
            <option value="" disabled>Selecione...</option>
            <option v-for="camp in campeonatos" :key="camp.id" :value="camp.id">
                {{ camp.nome }}
            </option>
        </select>
    </div>

    <div v-if="loading" class="text-center py-8">
      <p class="text-gray-500">Carregando...</p>
    </div>

    <div v-else-if="error" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
      {{ error }}
    </div>

    <!-- Estado Vazio -->
    <div v-else-if="selectedCampeonatoId && isEmpty" class="bg-blue-50 text-blue-700 p-4 rounded">
        Nenhuma inscrição encontrada para este campeonato.
    </div>

    <div v-else-if="!selectedCampeonatoId" class="text-center text-gray-500 py-8">
        Selecione um campeonato acima.
    </div>

    <!-- Tabela de Equipes (Se for competição de equipes) -->
    <div v-else-if="isTeamCompetition" class="bg-white shadow-md rounded-lg overflow-hidden">
        <div class="p-4 border-b flex justify-between items-center bg-gray-50">
            <span class="font-semibold text-gray-700">Total: {{ equipes.length }} equipes inscritas</span>
        </div>
        <table class="min-w-full leading-normal">
            <thead>
                <tr>
                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                        Equipe
                    </th>
                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                        Responsável
                    </th>
                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                        Atletas
                    </th>
                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                        Status
                    </th>
                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                        Ações
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="equipe in equipes" :key="equipe.id">
                    <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                        <p class="text-gray-900 font-medium">{{ equipe.nome }}</p>
                        <p class="text-gray-500 text-xs">{{ equipe.entidade?.nome || 'Sem Entidade' }}</p>
                    </td>
                    <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                        <p class="text-gray-900">{{ equipe.responsavel || 'N/A' }}</p>
                        <p class="text-gray-500 text-xs">{{ equipe.contato }}</p>
                    </td>
                    <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                        {{ equipe.atletas_count || 0 }}
                    </td>
                    <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                        <span :class="statusClassEquipe(equipe.status_inscricao)" class="px-2 py-1 font-semibold leading-tight rounded-full text-xs">
                            {{ equipe.status_inscricao || 'pendente' }}
                        </span>
                    </td>
                    <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                        <div class="flex flex-col gap-1">
                             <button 
                                v-if="equipe.status_inscricao === 'pendente'"
                                @click="atualizarStatusEquipe(equipe, 'aprovada')"
                                class="text-green-600 hover:text-green-900 text-xs font-bold"
                            >
                                Aprovar
                            </button>
                            <button 
                                v-if="equipe.status_inscricao !== 'rejeitada'"
                                @click="atualizarStatusEquipe(equipe, 'rejeitada')"
                                class="text-red-600 hover:text-red-900 text-xs"
                            >
                                Rejeitar
                            </button>
                            <router-link :to="`/equipes/${equipe.id}`" class="text-blue-600 hover:text-blue-900 text-xs mt-1">
                                Ver Detalhes
                            </router-link>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Tabela de Atletas Individuais -->
    <div v-else class="bg-white shadow-md rounded-lg overflow-hidden">
        <div class="p-4 border-b flex justify-between items-center bg-gray-50">
            <span class="font-semibold text-gray-700">Total: {{ inscricoes.length }} inscritos</span>
            <!-- Filtros de status poderiam entrar aqui -->
        </div>
      <table class="min-w-full leading-normal">
        <thead>
          <tr>
            <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
              Atleta
            </th>
            <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
              Data
            </th>
            <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
              Status
            </th>
            <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
              Pagamento
            </th>
            <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
              Ações
            </th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="inscricao in inscricoes" :key="inscricao.id">
            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
              <p class="text-gray-900 font-medium">{{ inscricao.atleta?.nome || 'Desconhecido' }}</p>
              <p class="text-gray-500 text-xs">{{ inscricao.atleta?.cpf }}</p>
            </td>
            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
              {{ new Date(inscricao.created_at).toLocaleDateString() }}
            </td>
            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
              <span :class="statusClass(inscricao.status)" class="px-2 py-1 font-semibold leading-tight rounded-full text-xs">
                {{ inscricao.status }}
              </span>
            </td>
            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
               <span :class="pagamentoClass(inscricao.status_pagamento)" class="px-2 py-1 font-semibold leading-tight rounded-full text-xs">
                {{ inscricao.status_pagamento.replace('_', ' ') }}
              </span>
              <div v-if="inscricao.valor_pago" class="text-xs text-gray-500 mt-1">
                  R$ {{ inscricao.valor_pago }}
              </div>
            </td>
            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                <div class="flex flex-col gap-1">
                    <button 
                        v-if="inscricao.status === 'pendente'"
                        @click="atualizarStatus(inscricao, 'confirmada')"
                        class="text-green-600 hover:text-green-900 text-xs font-bold"
                    >
                        Confirmar
                    </button>
                    <button 
                         v-if="inscricao.status !== 'rejeitada' && inscricao.status !== 'cancelada'"
                        @click="atualizarStatus(inscricao, 'cancelada')"
                        class="text-red-600 hover:text-red-900 text-xs"
                    >
                        Cancelar
                    </button>
                     <button 
                        v-if="inscricao.status_pagamento === 'nao_pago'"
                        @click="confirmarPagamento(inscricao)"
                        class="text-blue-600 hover:text-blue-900 text-xs"
                    >
                        Confirmar Pagamento
                    </button>
                </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useInscricaoStore } from '../store/inscricaoStore';
import { useCampeonatoStore } from '../../campeonatos/store/campeonatoStore';
import { useEquipeStore } from '@/modules/equipes/store/equipeStore';

const inscricaoStore = useInscricaoStore();
const campeonatoStore = useCampeonatoStore();
const equipeStore = useEquipeStore();

const selectedCampeonatoId = ref('');
const campeonatos = computed(() => campeonatoStore.campeonatos);
const inscricoes = computed(() => inscricaoStore.inscricoes);
const equipes = computed(() => equipeStore.equipes);

const loading = computed(() => inscricaoStore.loading || campeonatoStore.loading || equipeStore.loading);
const error = computed(() => inscricaoStore.error || campeonatoStore.error || equipeStore.error);

const selectedCampeonato = computed(() => {
    return campeonatos.value.find(c => c.id === selectedCampeonatoId.value);
});

const isTeamCompetition = computed(() => {
    return selectedCampeonato.value?.tipo_competicao === 'equipe';
});

const isEmpty = computed(() => {
    if (isTeamCompetition.value) {
        return equipes.value.length === 0;
    }
    return inscricoes.value.length === 0;
});

onMounted(async () => {
    await campeonatoStore.fetchCampeonatos();
});

const carregarInscricoes = () => {
    if (selectedCampeonatoId.value) {
        // Se for campeonato de equipes, buscar equipes
        // Se não, buscar inscrições de atletas
        // Como o selectedCampeonato pode demorar a atualizar (computed), usamos o find direto
        const camp = campeonatos.value.find(c => c.id === selectedCampeonatoId.value);
        if (camp && camp.tipo_competicao === 'equipe') {
             equipeStore.fetchEquipes(selectedCampeonatoId.value);
        } else {
             inscricaoStore.fetchInscricoes(selectedCampeonatoId.value);
        }
    }
};

const statusClass = (status) => {
    switch(status) {
        case 'confirmada': return 'text-green-900 bg-green-200';
        case 'pendente': return 'text-yellow-900 bg-yellow-200';
        case 'cancelada': return 'text-gray-900 bg-gray-200';
        case 'rejeitada': return 'text-red-900 bg-red-200';
        default: return 'text-gray-900 bg-gray-200';
    }
};

const statusClassEquipe = (status) => {
    switch(status) {
        case 'aprovada': return 'text-green-900 bg-green-200';
        case 'pendente': return 'text-yellow-900 bg-yellow-200';
        case 'rejeitada': return 'text-red-900 bg-red-200';
        default: return 'text-gray-900 bg-gray-200';
    }
};

const pagamentoClass = (status) => {
    switch(status) {
        case 'pago': return 'text-green-900 bg-green-200';
        case 'isento': return 'text-blue-900 bg-blue-200';
        case 'nao_pago': return 'text-red-900 bg-red-200';
        default: return 'text-gray-900 bg-gray-200';
    }
};

const atualizarStatus = async (inscricao, novoStatus) => {
    if (confirm(`Deseja alterar o status para ${novoStatus}?`)) {
        await inscricaoStore.updateInscricao(inscricao.id, { status: novoStatus });
    }
};

const atualizarStatusEquipe = async (equipe, novoStatus) => {
    if (confirm(`Deseja alterar o status da equipe para ${novoStatus}?`)) {
        await equipeStore.updateStatus(equipe.id, novoStatus);
    }
};

const confirmarPagamento = async (inscricao) => {
    const valor = prompt("Informe o valor pago:", "0.00");
    if (valor !== null) {
        await inscricaoStore.updateInscricao(inscricao.id, { 
            status_pagamento: 'pago',
            valor_pago: valor,
            data_pagamento: new Date().toISOString().split('T')[0]
        });
    }
};
</script>
