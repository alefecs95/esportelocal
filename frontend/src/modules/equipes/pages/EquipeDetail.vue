<template>
    <div class="p-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold">Detalhes da Equipe</h1>
            <button 
                @click="goBack" 
                class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded"
            >
                Voltar
            </button>
        </div>
        
        <div v-if="loading" class="text-center py-4">Carregando...</div>
        <div v-else-if="error" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
            {{ error }}
        </div>
        
        <div v-else-if="equipe" class="bg-white shadow overflow-hidden sm:rounded-lg mb-6">
            <div class="px-4 py-5 sm:px-6 flex justify-between items-start">
                <div>
                    <div class="flex items-center gap-3">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">{{ equipe.nome }}</h3>
                        <span v-if="equipe.entidade" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            {{ equipe.entidade.nome }}
                        </span>
                    </div>
                    <p class="mt-1 max-w-2xl text-sm text-gray-500">
                        Responsável: {{ equipe.responsavel || 'N/A' }} | Contato: {{ equipe.contato || 'N/A' }}
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <button
                        @click="printAtletasEquipePdf"
                        class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded flex items-center gap-2"
                    >
                        <FileText :size="16" />
                        PDF Atletas
                    </button>
                    <button 
                        @click="openAddAtleta" 
                        class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded"
                    >
                        Adicionar Atleta
                    </button>
                </div>
            </div>
            
            <div class="border-t border-gray-200">
                <dl>
                    <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Atletas</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            <div class="mb-3 flex flex-wrap items-center gap-2">
                                <label class="text-xs text-gray-600">Filtrar status:</label>
                                <select
                                    v-model="statusFilter"
                                    class="px-2 py-1 rounded border border-gray-300 text-xs bg-white"
                                >
                                    <option value="todos">Todos</option>
                                    <option value="pendente">Pendentes</option>
                                    <option value="aprovado">Aprovados</option>
                                    <option value="rejeitado">Rejeitados</option>
                                </select>
                                <span class="text-xs text-gray-500">
                                    Total: {{ equipe.atletas?.length || 0 }} |
                                    Pendentes: {{ atletasPendentes.length }} |
                                    Aprovados: {{ atletasAprovados.length }} |
                                    Rejeitados: {{ atletasRejeitados.length }}
                                </span>
                            </div>
                            <div v-if="canApproveAtletas && atletasPendentes.length > 0" class="mb-3 flex flex-wrap items-center gap-2">
                                <button
                                    @click="selectAllPendentes"
                                    class="px-3 py-1 rounded bg-gray-100 text-gray-700 hover:bg-gray-200 text-xs font-medium"
                                >
                                    Selecionar pendentes
                                </button>
                                <button
                                    @click="clearSelection"
                                    class="px-3 py-1 rounded bg-gray-100 text-gray-700 hover:bg-gray-200 text-xs font-medium"
                                >
                                    Limpar seleção
                                </button>
                                <button
                                    @click="updateAtletasStatusLote('aprovado')"
                                    :disabled="selectedAtletaIds.length === 0"
                                    class="px-3 py-1 rounded bg-green-600 text-white hover:bg-green-700 text-xs font-medium disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    Aprovar selecionados ({{ selectedAtletaIds.length }})
                                </button>
                                <button
                                    @click="updateAtletasStatusLote('rejeitado')"
                                    :disabled="selectedAtletaIds.length === 0"
                                    class="px-3 py-1 rounded bg-amber-600 text-white hover:bg-amber-700 text-xs font-medium disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    Rejeitar selecionados ({{ selectedAtletaIds.length }})
                                </button>
                            </div>
                            <ul class="border border-gray-200 rounded-md divide-y divide-gray-200">
                                <li v-for="atleta in atletasFiltrados" :key="atleta.id" class="pl-3 pr-4 py-3 flex items-center justify-between text-sm">
                                    <div class="w-0 flex-1 flex items-center">
                                        <input
                                            v-if="canApproveAtletas && atleta.pivot?.status === 'pendente'"
                                            :checked="selectedAtletaIds.includes(atleta.id)"
                                            @change="toggleSelection(atleta.id)"
                                            type="checkbox"
                                            class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                        />
                                        <span class="ml-2 flex-1 w-0 truncate">{{ atleta.nome }} ({{ atleta.cpf }})</span>
                                        <span
                                            class="ml-3 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold"
                                            :class="getAtletaStatusClass(atleta.pivot?.status)"
                                        >
                                            {{ formatAtletaStatus(atleta.pivot?.status) }}
                                        </span>
                                    </div>
                                    <div class="ml-4 flex-shrink-0 flex items-center gap-3">
                                        <button
                                            v-if="canApproveAtletas && atleta.pivot?.status === 'pendente'"
                                            @click="updateAtletaStatus(atleta.id, 'aprovado')"
                                            class="font-medium text-green-700 hover:text-green-900"
                                        >
                                            Aprovar
                                        </button>
                                        <button
                                            v-if="canApproveAtletas && atleta.pivot?.status === 'pendente'"
                                            @click="updateAtletaStatus(atleta.id, 'rejeitado')"
                                            class="font-medium text-amber-700 hover:text-amber-900"
                                        >
                                            Rejeitar
                                        </button>
                                        <button 
                                            @click="printCarteirinha(atleta.id)" 
                                            class="text-blue-600 hover:text-blue-800 flex items-center gap-1 text-xs"
                                            title="Imprimir Carteirinha"
                                        >
                                            <CreditCard :size="16" />
                                            Carteirinha
                                        </button>
                                        <button 
                                            @click="removeAtleta(atleta.id)" 
                                            class="font-medium text-red-600 hover:text-red-500"
                                        >
                                            Remover
                                        </button>
                                    </div>
                                </li>
                                <li v-if="!equipe.atletas || equipe.atletas.length === 0" class="pl-3 pr-4 py-3 text-sm text-gray-500 text-center">
                                    Nenhum atleta na equipe.
                                </li>
                                <li v-else-if="atletasFiltrados.length === 0" class="pl-3 pr-4 py-3 text-sm text-gray-500 text-center">
                                    Nenhum atleta encontrado para o filtro selecionado.
                                </li>
                            </ul>
                        </dd>
                    </div>
                </dl>
            </div>
        </div>
        
        <AtletaSelector 
            v-if="showSelector" 
            :equipe-id="equipe.id"
            :campeonato-id="equipe.campeonato_id" 
            :exclude-ids="equipe.atletas.map(a => a.id)"
            @select="addAtleta" 
            @close="closeSelector" 
        />
    </div>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useEquipeStore } from '../store/equipeStore';
import AtletaSelector from '../components/AtletaSelector.vue';
import { CreditCard, FileText } from 'lucide-vue-next';
import api from '../../../services/api';

const route = useRoute();
const router = useRouter();
const store = useEquipeStore();

const equipeId = route.params.id;
const equipe = computed(() => store.currentEquipe);
const loading = computed(() => store.loading);
const error = computed(() => store.error);
const user = JSON.parse(localStorage.getItem('user') || '{}');
const isAdmin = computed(() => ['ADMIN', 'SUPER_ADMIN'].includes(user.perfil));
const canApproveAtletas = computed(() => ['ADMIN', 'SUPER_ADMIN'].includes(user.perfil));
const selectedAtletaIds = ref([]);
const statusFilter = ref('todos');
const atletasPendentes = computed(() => (equipe.value?.atletas || []).filter(a => a.pivot?.status === 'pendente'));
const atletasAprovados = computed(() => (equipe.value?.atletas || []).filter(a => (a.pivot?.status || 'aprovado') === 'aprovado'));
const atletasRejeitados = computed(() => (equipe.value?.atletas || []).filter(a => a.pivot?.status === 'rejeitado'));
const atletasFiltrados = computed(() => {
    const lista = equipe.value?.atletas || [];
    if (statusFilter.value === 'todos') return lista;
    if (statusFilter.value === 'aprovado') {
        return lista.filter(a => (a.pivot?.status || 'aprovado') === 'aprovado');
    }
    return lista.filter(a => a.pivot?.status === statusFilter.value);
});

const showSelector = ref(false);

const printCarteirinha = async (atletaId) => {
    if (!equipe.value) return;
    try {
        const response = await api.get(`/atletas/${atletaId}/pdf-carteirinha`, {
            params: { campeonato_id: equipe.value.campeonato_id }
        });
        const signedUrl = response.data?.url;
        if (!signedUrl) {
            throw new Error('URL da carteirinha não encontrada.');
        }
        window.open(signedUrl, '_blank');
    } catch (e) {
        alert(e.response?.data?.message || 'Não foi possível abrir a carteirinha.');
    }
};

const printAtletasEquipePdf = async () => {
    if (!equipe.value?.id) return;
    try {
        const response = await api.get(`/equipes/${equipe.value.id}/pdf-atletas`);
        const signedUrl = response.data?.url;
        if (!signedUrl) {
            throw new Error('URL do PDF não encontrada.');
        }
        window.open(signedUrl, '_blank');
    } catch (e) {
        alert(e.response?.data?.message || 'Não foi possível gerar o PDF da equipe.');
    }
};

onMounted(() => {
    store.fetchEquipe(equipeId);
});

watch(equipe, () => {
    selectedAtletaIds.value = [];
});

const goBack = () => {
    if (equipe.value && equipe.value.campeonato_id) {
        router.push(`/admin/competicoes/${equipe.value.campeonato_id}/equipes`);
    } else {
        router.back();
    }
};

const openAddAtleta = () => {
    showSelector.value = true;
};

const closeSelector = () => {
    showSelector.value = false;
};

const addAtleta = async (atleta) => {
    try {
        const inscricaoDireta = isAdmin.value;
        const response = await store.addAtleta(equipeId, atleta.id, inscricaoDireta);
        closeSelector();
        if (response?.message) {
            alert(response.message);
        }
    } catch (e) {
        alert(e.response?.data?.message || 'Erro ao adicionar atleta');
    }
};

const removeAtleta = async (atletaId) => {
    if (confirm('Remover atleta da equipe?')) {
        try {
            await store.removeAtleta(equipeId, atletaId);
        } catch (e) {
            alert('Erro ao remover atleta');
        }
    }
};

const updateAtletaStatus = async (atletaId, status) => {
    try {
        await store.updateAtletaStatus(equipeId, atletaId, status);
        selectedAtletaIds.value = selectedAtletaIds.value.filter(id => id !== atletaId);
    } catch (e) {
        alert(e.response?.data?.message || 'Erro ao atualizar status do atleta');
    }
};

const toggleSelection = (atletaId) => {
    if (selectedAtletaIds.value.includes(atletaId)) {
        selectedAtletaIds.value = selectedAtletaIds.value.filter(id => id !== atletaId);
        return;
    }
    selectedAtletaIds.value = [...selectedAtletaIds.value, atletaId];
};

const selectAllPendentes = () => {
    selectedAtletaIds.value = atletasPendentes.value.map(a => a.id);
};

const clearSelection = () => {
    selectedAtletaIds.value = [];
};

const updateAtletasStatusLote = async (status) => {
    if (selectedAtletaIds.value.length === 0) return;
    try {
        await store.updateAtletasStatusLote(equipeId, selectedAtletaIds.value, status);
        selectedAtletaIds.value = [];
    } catch (e) {
        alert(e.response?.data?.message || 'Erro ao atualizar atletas em lote');
    }
};

const formatAtletaStatus = (status) => {
    const map = {
        pendente: 'Pendente',
        aprovado: 'Aprovado',
        rejeitado: 'Rejeitado'
    };
    return map[status] || 'Aprovado';
};

const getAtletaStatusClass = (status) => {
    const map = {
        pendente: 'bg-yellow-100 text-yellow-800',
        aprovado: 'bg-green-100 text-green-800',
        rejeitado: 'bg-red-100 text-red-800'
    };
    return map[status] || 'bg-green-100 text-green-800';
};
</script>
