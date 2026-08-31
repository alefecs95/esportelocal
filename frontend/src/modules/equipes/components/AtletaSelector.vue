<template>
    <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full flex items-center justify-center z-50">
        <div class="bg-white p-5 rounded-lg shadow-lg w-full max-w-md">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold">Adicionar Atleta</h3>
                <button @click="$emit('close')" class="text-gray-500 hover:text-gray-700">
                    <span class="text-2xl">&times;</span>
                </button>
            </div>
            
            <input 
                v-model="search" 
                placeholder="Buscar atleta..." 
                class="w-full p-2 border rounded mb-4" 
            />
            
            <div v-if="loading" class="text-center py-4">Carregando...</div>
            <div v-else-if="loadError" class="mb-3 p-3 rounded border border-red-200 bg-red-50 text-red-700 text-sm">
                {{ loadError }}
            </div>
            
            <ul v-else class="max-h-60 overflow-y-auto border rounded">
                <li 
                    v-for="atleta in filteredAtletas" 
                    :key="atleta.id" 
                    class="p-3 border-b hover:bg-gray-50 cursor-pointer flex justify-between items-center"
                    @click="select(atleta)"
                >
                    <div>
                        <div class="font-medium">{{ atleta.nome }}</div>
                        <div class="text-sm text-gray-500">CPF: {{ atleta.cpf }}</div>
                    </div>
                    <button class="bg-green-500 hover:bg-green-600 text-white px-3 py-1 rounded text-sm">
                        Adicionar
                    </button>
                </li>
                <li v-if="filteredAtletas.length === 0" class="p-4 text-center text-gray-500">
                    Nenhum atleta encontrado ou elegível.
                </li>
            </ul>
            
            <div class="mt-4 flex justify-end">
                <button 
                    @click="$emit('close')" 
                    class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded"
                >
                    Fechar
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import api from '@/services/api';

const props = defineProps({
    equipeId: {
        type: [String, Number],
        required: true
    },
    campeonatoId: {
        type: [String, Number],
        required: true
    },
    excludeIds: {
        type: Array,
        default: () => []
    }
});

const emit = defineEmits(['select', 'close']);

const atletas = ref([]);
const loading = ref(false);
const search = ref('');
const loadError = ref('');

const filteredAtletas = computed(() => {
    return atletas.value.filter(a => 
        !props.excludeIds.includes(a.id) &&
        (a.nome.toLowerCase().includes(search.value.toLowerCase()) || 
         a.cpf.includes(search.value))
    );
});

const fetchEligibleAtletas = async () => {
    loading.value = true;
    loadError.value = '';
    try {
        const response = await api.get(`/equipes/${props.equipeId}/atletas-elegiveis`);
        atletas.value = response.data?.data || [];
    } catch (error) {
        console.error('Erro ao buscar atletas:', error);
        loadError.value = error.response?.data?.message || 'Erro ao carregar atletas elegíveis.';
    } finally {
        loading.value = false;
    }
};

const select = (atleta) => {
    emit('select', atleta);
};

onMounted(() => {
    fetchEligibleAtletas();
});
</script>
