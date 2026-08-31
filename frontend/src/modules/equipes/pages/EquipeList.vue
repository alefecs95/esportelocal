<template>
    <div class="p-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold">Equipes do Campeonato</h1>
            <div class="flex gap-2">
                <button 
                    @click="showLinkModal = true" 
                    class="bg-emerald-500 hover:bg-emerald-700 text-white font-bold py-2 px-4 rounded flex items-center gap-2"
                >
                    <Link :size="20" />
                    Vincular Equipe Existente
                </button>
                <button 
                    @click="goToCreate" 
                    class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded flex items-center gap-2"
                >
                    <Plus :size="20" />
                    Nova Equipe
                </button>
            </div>
        </div>
        
        <div v-if="loading" class="text-center py-4">Carregando...</div>
        <div v-else-if="error" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
            {{ error }}
        </div>
        
        <EquipeTable 
            v-else 
            :equipes="equipes" 
            :campeonato="campeonato"
            @edit="editEquipe" 
            @delete="deleteEquipe" 
            @manage="manageEquipe"
        />

        <!-- Modal de Vinculação -->
        <div v-if="showLinkModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg p-6 w-full max-w-md shadow-xl">
                <h2 class="text-xl font-bold mb-4">Vincular Equipe Existente</h2>
                <p class="text-sm text-gray-600 mb-4">Selecione uma entidade (clube) já cadastrada para inscrever neste campeonato.</p>
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Selecione a Entidade</label>
                    <select v-model="selectedEntidadeId" class="border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        <option :value="null">Selecione...</option>
                        <option v-for="entidade in entidadesDisponiveis" :key="entidade.id" :value="entidade.id">
                            {{ entidade.nome }} ({{ entidade.sigla || '-' }})
                        </option>
                    </select>
                </div>

                <div v-if="linkError" class="text-red-500 text-sm mb-4">{{ linkError }}</div>

                <div class="flex justify-end gap-2">
                    <button 
                        @click="showLinkModal = false" 
                        class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded"
                    >
                        Cancelar
                    </button>
                    <button 
                        @click="confirmarVinculo" 
                        :disabled="!selectedEntidadeId || linking"
                        class="px-4 py-2 bg-emerald-600 text-white rounded hover:bg-emerald-700 disabled:opacity-50"
                    >
                        {{ linking ? 'Vinculando...' : 'Confirmar Vinculação' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { onMounted, computed, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useEquipeStore } from '../store/equipeStore';
import { useCampeonatoStore } from '@/modules/campeonatos/store/campeonatoStore';
import EquipeTable from '../components/EquipeTable.vue';
import entidadeService from '@/modules/entidades/services/entidadeService';
import { Plus, Link } from 'lucide-vue-next';

const route = useRoute();
const router = useRouter();
const store = useEquipeStore();

const campeonatoId = route.params.id;
const equipes = computed(() => store.equipes);
const loading = computed(() => store.loading);
const error = computed(() => store.error);

// Estado do Modal
const showLinkModal = ref(false);
const entidadesDisponiveis = ref([]);
const selectedEntidadeId = ref(null);
const linking = ref(false);
const linkError = ref(null);

onMounted(() => {
    store.fetchEquipes(campeonatoId);
});

// Carregar entidades quando abrir o modal
watch(showLinkModal, async (newVal) => {
    if (newVal) {
        try {
            // Recarrega sempre para garantir atualização, mas poderia ser otimizado
            const response = await entidadeService.getAll();
            const todasEntidades = response.data;

            // Filtra para remover entidades que já estão neste campeonato
            // Verifica pelo ID da entidade vinculado à equipe
            const entidadesJaVinculadasIds = equipes.value
                .map(e => e.entidade_id)
                .filter(id => id !== null);

            entidadesDisponiveis.value = todasEntidades.filter(entidade => 
                !entidadesJaVinculadasIds.includes(entidade.id)
            );
            
            selectedEntidadeId.value = null; // Reseta seleção

        } catch (e) {
            console.error("Erro ao carregar entidades", e);
        }
    }
});

const goToCreate = () => {
  router.push(`/admin/competicoes/${campeonatoId}/equipes/nova`);
};

const confirmarVinculo = async () => {
    if (!selectedEntidadeId.value) return;
    
    linking.value = true;
    linkError.value = null;

    try {
        const entidade = entidadesDisponiveis.value.find(e => e.id === selectedEntidadeId.value);
        
        // Cria a equipe usando dados da entidade
        await store.createEquipe({
            campeonato_id: campeonatoId,
            entidade_id: entidade.id,
            nome: entidade.nome,
            responsavel: entidade.responsavel,
            contato: entidade.contato,
            status_inscricao: 'aprovada' // Já nasce aprovada pois é adm vinculando
        });

        showLinkModal.value = false;
        selectedEntidadeId.value = null;
        // Atualiza a lista
        await store.fetchEquipes(campeonatoId);
    } catch (e) {
        linkError.value = e.response?.data?.message || 'Erro ao vincular equipe.';
    } finally {
        linking.value = false;
    }
};

const editEquipe = (equipe) => {
    // For now we don't have a dedicated edit page route defined in the plan,
    // but we can add one if needed.
    console.log('Edit', equipe);
};

const deleteEquipe = async (id) => {
    if (confirm('Tem certeza que deseja excluir esta equipe?')) {
        try {
            await store.deleteEquipe(id);
        } catch (e) {
            alert('Erro ao excluir equipe');
        }
    }
};

const manageEquipe = (equipe) => {
  router.push(`/admin/equipes/${equipe.id}`);
};
</script>
