<template>
    <div class="bg-white p-6 rounded-lg shadow-md">
        <h2 class="text-xl font-semibold mb-4">{{ isEditing ? 'Editar Equipe' : 'Nova Equipe' }}</h2>
        <form @submit.prevent="submitForm">
            <!-- Seleção de Entidade (Opcional) -->
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">
                    Vincular a Clube/Entidade (Recomendado)
                </label>
                <select 
                    v-model="form.entidade_id" 
                    @change="onEntidadeChange"
                    class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                >
                    <option :value="null">-- Selecione um Clube (ou preencha manualmente abaixo) --</option>
                    <option v-for="entidade in entidades" :key="entidade.id" :value="entidade.id">
                        {{ entidade.nome }}
                    </option>
                </select>
                <p class="text-xs text-gray-500 mt-1">
                    Selecionar um clube preencherá automaticamente os dados abaixo.
                </p>
            </div>

            <!-- Seleção de Responsável (Admin Feature) -->
            <div class="mb-4 bg-yellow-50 p-4 rounded-lg border border-yellow-200">
                <label class="block text-gray-700 text-sm font-bold mb-2">
                    Vincular Usuário Responsável (Login)
                </label>
                <p class="text-xs text-gray-600 mb-2">
                    Busque por nome ou CPF para permitir que este usuário gerencie a equipe.
                </p>
                
                <div class="relative">
                    <input 
                        type="text" 
                        v-model="userSearch"
                        @input="searchUsers"
                        placeholder="Buscar responsável..."
                        class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                    >
                    <div v-if="userResults.length > 0" class="absolute z-10 w-full bg-white border border-gray-300 rounded mt-1 max-h-40 overflow-y-auto shadow-lg">
                        <div 
                            v-for="user in userResults" 
                            :key="user.id"
                            @click="selectUser(user)"
                            class="px-3 py-2 hover:bg-gray-100 cursor-pointer text-sm"
                        >
                            <span class="font-bold">{{ user.name }}</span> - {{ user.cpf }}
                        </div>
                    </div>
                </div>

                <div v-if="selectedUser" class="mt-2 flex items-center justify-between bg-white p-2 rounded border border-gray-200">
                    <span class="text-sm font-medium text-green-700">
                        Selecionado: {{ selectedUser.name }}
                    </span>
                    <button type="button" @click="clearUser" class="text-red-500 text-xs hover:underline">
                        Remover
                    </button>
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="nome">
                    Nome da Equipe no Campeonato *
                </label>
                <input 
                    v-model="form.nome" 
                    id="nome" 
                    type="text" 
                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" 
                    required
                    placeholder="Ex: Time A"
                >
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="responsavel">
                    Nome do Responsável (Exibição)
                </label>
                <input 
                    v-model="form.responsavel" 
                    id="responsavel" 
                    type="text" 
                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                    placeholder="Nome do responsável"
                >
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="contato">
                    Contato
                </label>
                <input 
                    v-model="form.contato" 
                    id="contato" 
                    type="text" 
                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                    placeholder="Telefone ou Email"
                >
            </div>
            
            <div class="flex items-center justify-end gap-2">
                <button 
                    type="button" 
                    @click="$emit('cancel')" 
                    class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline"
                >
                    Cancelar
                </button>
                <button 
                    type="submit" 
                    class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline"
                    :disabled="loading"
                >
                    {{ loading ? 'Salvando...' : 'Salvar' }}
                </button>
            </div>
        </form>
    </div>
</template>

<script setup>
import { reactive, computed, watch, ref, onMounted } from 'vue';
import entidadeService from '@/modules/entidades/services/entidadeService';
import userService from '@/modules/admin/services/userService';

const props = defineProps({
    initialData: {
        type: Object,
        default: () => ({})
    },
    loading: {
        type: Boolean,
        default: false
    },
    campeonatoId: {
        type: [String, Number],
        required: true
    }
});

const emit = defineEmits(['submit', 'cancel']);

const form = reactive({
    nome: '',
    responsavel: '',
    contato: '',
    campeonato_id: props.campeonatoId,
    entidade_id: null,
    user_id: null
});

const entidades = ref([]);
const userSearch = ref('');
const userResults = ref([]);
const selectedUser = ref(null);

const isEditing = computed(() => !!props.initialData.id);

const loadEntidades = async () => {
    try {
        const { data } = await entidadeService.getAll({ per_page: 100 });
        entidades.value = Array.isArray(data) ? data : (data?.data || []);
    } catch (e) {
        console.error('Erro ao carregar entidades', e);
    }
};

const searchUsers = async () => {
    if (userSearch.value.length < 3) {
        userResults.value = [];
        return;
    }
    try {
        const { data } = await userService.searchResponsavel(userSearch.value);
        userResults.value = data;
    } catch (e) {
        console.error(e);
    }
};

const selectUser = (user) => {
    selectedUser.value = user;
    form.user_id = user.id;
    // Auto-fill responsible info if empty
    if (!form.responsavel) form.responsavel = user.name;
    if (!form.contato) form.contato = user.email; // or phone if available
    
    userResults.value = [];
    userSearch.value = '';
};

const clearUser = () => {
    selectedUser.value = null;
    form.user_id = null;
};

const onEntidadeChange = () => {
    if (form.entidade_id) {
        const selected = entidades.value.find(e => e.id === form.entidade_id);
        if (selected) {
            form.nome = selected.nome;
            form.responsavel = selected.responsavel || '';
            form.contato = selected.contato || '';
        }
    }
};

onMounted(loadEntidades);

watch(() => props.initialData, (newVal) => {
    if (newVal) {
        form.nome = newVal.nome || '';
        form.responsavel = newVal.responsavel || '';
        form.contato = newVal.contato || '';
        form.entidade_id = newVal.entidade_id || null;
        form.user_id = newVal.user_id || null;
        if (newVal.id) form.id = newVal.id;
        
        if (newVal.user) {
            selectedUser.value = newVal.user;
        }
    }
}, { immediate: true });

const submitForm = () => {
    emit('submit', { ...form, campeonato_id: props.campeonatoId });
};
</script>
