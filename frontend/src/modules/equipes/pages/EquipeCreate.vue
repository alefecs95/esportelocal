<template>
    <div class="p-6 max-w-2xl mx-auto">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Nova Inscrição de Equipe</h1>
            <button @click="goBack" class="text-gray-500 hover:text-gray-700">
                Voltar
            </button>
        </div>
        
        <div v-if="error" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
            {{ error }}
        </div>

        <form @submit.prevent="submitForm" class="bg-white p-6 rounded-lg shadow-md space-y-4">
            <!-- Logo Upload -->
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2">
                    Logo da Equipe (Opcional)
                </label>
                <div class="flex items-center gap-4">
                    <div 
                        class="w-20 h-20 bg-gray-100 rounded-full border-2 border-dashed border-gray-300 flex items-center justify-center overflow-hidden relative"
                    >
                        <img v-if="previewLogo" :src="previewLogo" class="w-full h-full object-cover" />
                        <span v-else class="text-xs text-gray-400 text-center px-1">Sem Logo</span>
                    </div>
                    <input 
                        type="file" 
                        @change="handleLogoUpload" 
                        accept="image/*"
                        class="text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                    >
                </div>
            </div>

            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2">
                    Nome da Equipe
                </label>
                <input 
                    v-model="form.nome" 
                    type="text" 
                    required
                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                    placeholder="Ex: Real Madrid, Clube Atlético..."
                >
                <p class="text-xs text-gray-500 mt-1">Este nome será usado na tabela de jogos.</p>
            </div>

            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2">
                    Responsável Técnico
                </label>
                <input 
                    v-model="form.responsavel" 
                    type="text" 
                    required
                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                >
            </div>

            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2">
                    Contato (Telefone/WhatsApp)
                </label>
                <input 
                    v-model="form.contato" 
                    type="text" 
                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                >
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100 mt-4">
                <button 
                    type="button" 
                    @click="goBack" 
                    class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg font-medium"
                >
                    Cancelar
                </button>
                <button 
                    type="submit"
                    :disabled="loading"
                    class="px-6 py-2 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 flex items-center gap-2"
                >
                    <Loader2 v-if="loading" :size="18" class="animate-spin" />
                    <span>Confirmar Inscrição</span>
                </button>
            </div>
        </form>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useEquipeStore } from '../store/equipeStore';
import { Loader2 } from 'lucide-vue-next';

const route = useRoute();
const router = useRouter();
const store = useEquipeStore();

const campeonatoId = route.params.id;
const loading = ref(false);
const error = ref(null);

const form = ref({
    nome: '',
    responsavel: '',
    contato: '',
    logo: null
});
const previewLogo = ref(null);

onMounted(() => {
    // Pre-fill with user data if available
    try {
        const userStr = localStorage.getItem('user');
        if (userStr) {
            const user = JSON.parse(userStr);
            form.value.responsavel = user.name || '';
            // Don't auto-fill contact with email as it might be phone
        }
    } catch (e) {
        // ignore
    }
});

const handleLogoUpload = (event) => {
    const file = event.target.files[0];
    if (file) {
        form.value.logo = file;
        previewLogo.value = URL.createObjectURL(file);
    }
};

const submitForm = async () => {
    loading.value = true;
    error.value = null;

    try {
        // Use FormData for file upload
        const formData = new FormData();
        formData.append('campeonato_id', campeonatoId);
        formData.append('nome', form.value.nome);
        formData.append('responsavel', form.value.responsavel);
        formData.append('contato', form.value.contato);
        
        if (form.value.logo) {
            formData.append('logo', form.value.logo);
        }

        await store.createEquipe(formData); // Store must handle FormData
        
        // Redirect back to list
        router.push(`/admin/competicoes/${campeonatoId}/equipes`);
        
    } catch (e) {
        error.value = e.response?.data?.message || 'Erro ao criar equipe.';
    } finally {
        loading.value = false;
    }
};

const goBack = () => {
    router.push(`/admin/competicoes/${campeonatoId}/equipes`);
};
</script>
