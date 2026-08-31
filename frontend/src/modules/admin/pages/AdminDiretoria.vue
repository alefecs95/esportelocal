<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Diretoria</h1>
        <p class="text-gray-500">Gerencie os membros da diretoria e equipe administrativa</p>
      </div>
      <button
        @click="openModal()"
        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center gap-2 transition-colors"
      >
        <Plus :size="20" /> Novo Membro
      </button>
    </div>

    <!-- Lista de Membros -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <div v-for="membro in membros" :key="membro.id" class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden group hover:shadow-md transition-all">
        <div class="relative h-48 bg-gray-100">
            <img 
                :src="membro.foto_url || 'https://ui-avatars.com/api/?name=' + membro.nome + '&background=random'" 
                class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
                alt="Foto"
            />
            <div class="absolute inset-0 bg-black/40 opacity-100 md:opacity-0 md:group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                <button @click="openModal(membro)" class="p-2 bg-white rounded-full text-blue-600 hover:bg-blue-50 transition-colors">
                    <Edit2 :size="18" />
                </button>
                <button @click="confirmDelete(membro)" class="p-2 bg-white rounded-full text-red-600 hover:bg-red-50 transition-colors">
                    <Trash2 :size="18" />
                </button>
            </div>
        </div>
        <div class="p-4">
            <h3 class="font-bold text-lg text-gray-900">{{ membro.nome }}</h3>
            <p class="text-blue-600 font-medium text-sm">{{ membro.funcao }}</p>
        </div>
      </div>
      
      <!-- Card Vazio se não houver registros -->
      <div v-if="membros.length === 0" class="col-span-full py-12 text-center bg-gray-50 rounded-xl border border-dashed border-gray-300">
         <Users :size="48" class="mx-auto text-gray-300 mb-3" />
         <p class="text-gray-500">Nenhum membro cadastrado ainda.</p>
      </div>
    </div>

    <!-- Modal de Cadastro/Edição -->
    <TransitionRoot appear :show="isOpen" as="template">
      <Dialog as="div" @close="closeModal" class="relative z-50">
        <TransitionChild
          as="template"
          enter="duration-300 ease-out"
          enter-from="opacity-0"
          enter-to="opacity-100"
          leave="duration-200 ease-in"
          leave-from="opacity-100"
          leave-to="opacity-0"
        >
          <div class="fixed inset-0 bg-black/25 backdrop-blur-sm" />
        </TransitionChild>

        <div class="fixed inset-0 overflow-y-auto">
          <div class="flex min-h-full items-center justify-center p-4 text-center">
            <TransitionChild
              as="template"
              enter="duration-300 ease-out"
              enter-from="opacity-0 scale-95"
              enter-to="opacity-100 scale-100"
              leave="duration-200 ease-in"
              leave-from="opacity-100 scale-100"
              leave-to="opacity-0 scale-95"
            >
              <DialogPanel class="w-full max-w-md transform overflow-hidden rounded-2xl bg-white p-6 text-left align-middle shadow-xl transition-all">
                <DialogTitle as="h3" class="text-lg font-medium leading-6 text-gray-900 flex justify-between items-center mb-4">
                  {{ isEditing ? 'Editar Membro' : 'Novo Membro' }}
                  <button @click="closeModal" class="text-gray-400 hover:text-gray-600">
                    <X :size="20" />
                  </button>
                </DialogTitle>
                
                <form @submit.prevent="saveMembro" class="space-y-4">
                    <!-- Foto Upload -->
                    <div class="flex flex-col items-center gap-4 mb-6">
                        <div class="w-32 h-32 rounded-full overflow-hidden bg-gray-100 border-4 border-white shadow-lg relative group cursor-pointer" @click="$refs.fileInput.click()">
                            <img 
                                v-if="previewUrl" 
                                :src="previewUrl" 
                                class="w-full h-full object-cover" 
                            />
                            <div v-else class="w-full h-full flex items-center justify-center text-gray-400">
                                <Camera :size="32" />
                            </div>
                            <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-white font-medium text-xs">
                                Alterar Foto
                            </div>
                        </div>
                        <input 
                            type="file" 
                            ref="fileInput" 
                            class="hidden" 
                            accept="image/*"
                            @change="handleFileChange"
                        />
                        <span class="text-xs text-gray-500">Clique na imagem para alterar</span>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nome Completo</label>
                        <input v-model="form.nome" type="text" required class="w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" placeholder="Ex: João da Silva" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Função / Cargo</label>
                        <input v-model="form.funcao" type="text" required class="w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" placeholder="Ex: Secretário de Esportes" />
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Ordem de Exibição</label>
                        <input v-model="form.ordem" type="number" class="w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" />
                    </div>

                    <div class="mt-6 flex justify-end gap-3">
                        <button type="button" @click="closeModal" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">
                            Cancelar
                        </button>
                        <button type="submit" :disabled="loading" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 flex items-center gap-2">
                            <Loader2 v-if="loading" :size="16" class="animate-spin" />
                            {{ isEditing ? 'Salvar Alterações' : 'Cadastrar' }}
                        </button>
                    </div>
                </form>
              </DialogPanel>
            </TransitionChild>
          </div>
        </div>
      </Dialog>
    </TransitionRoot>
  </div>
</template>

<script setup>
import { ref, onMounted, reactive } from 'vue';
import { 
  TransitionRoot, 
  TransitionChild, 
  Dialog, 
  DialogPanel, 
  DialogTitle 
} from '@headlessui/vue';
import { 
  Plus, 
  Edit2, 
  Trash2, 
  X, 
  Camera, 
  Loader2, 
  Users 
} from 'lucide-vue-next';
import api from '@/services/api';
import { useToast } from 'vue-toastification';

const toast = useToast();
const membros = ref([]);
const isOpen = ref(false);
const isEditing = ref(false);
const loading = ref(false);
const previewUrl = ref(null);
const fileInput = ref(null);
const selectedFile = ref(null);

const form = reactive({
    id: null,
    nome: '',
    funcao: '',
    ordem: 0,
    ativo: true
});

const fetchMembros = async () => {
    try {
        const { data } = await api.get('/diretoria');
        membros.value = data;
    } catch (error) {
        console.error(error);
        toast.error('Erro ao carregar membros');
    }
};

const openModal = (membro = null) => {
    if (membro) {
        isEditing.value = true;
        form.id = membro.id;
        form.nome = membro.nome;
        form.funcao = membro.funcao;
        form.ordem = membro.ordem;
        form.ativo = membro.ativo;
        previewUrl.value = membro.foto_url;
    } else {
        isEditing.value = false;
        Object.assign(form, { id: null, nome: '', funcao: '', ordem: 0, ativo: true });
        previewUrl.value = null;
    }
    selectedFile.value = null;
    isOpen.value = true;
};

const closeModal = () => {
    isOpen.value = false;
};

const handleFileChange = (event) => {
    const file = event.target.files[0];
    if (file) {
        selectedFile.value = file;
        previewUrl.value = URL.createObjectURL(file);
    }
};

const saveMembro = async () => {
    loading.value = true;
    try {
        if (isEditing.value) {
            if (!form.id) {
                throw new Error('ID do membro não informado para edição');
            }
            if (selectedFile.value) {
                const formData = new FormData();
                formData.append('nome', form.nome);
                formData.append('funcao', form.funcao);
                formData.append('ordem', String(form.ordem ?? 0));
                formData.append('ativo', form.ativo ? '1' : '0');
                formData.append('foto', selectedFile.value);
                formData.append('_method', 'PUT');
                await api.post(`/diretoria/${form.id}`, formData);
            } else {
                const formData = new FormData();
                formData.append('nome', form.nome);
                formData.append('funcao', form.funcao);
                formData.append('ordem', String(form.ordem ?? 0));
                formData.append('ativo', form.ativo ? '1' : '0');
                formData.append('_method', 'PUT');
                await api.post(`/diretoria/${form.id}`, formData);
            }
            toast.success('Membro atualizado com sucesso!');
        } else {
            const formData = new FormData();
            formData.append('nome', form.nome);
            formData.append('funcao', form.funcao);
            formData.append('ordem', String(form.ordem ?? 0));
            formData.append('ativo', form.ativo ? '1' : '0');
            if (selectedFile.value) {
                formData.append('foto', selectedFile.value);
            }
            await api.post('/diretoria', formData);
            toast.success('Membro cadastrado com sucesso!');
        }
        
        closeModal();
        await fetchMembros();
    } catch (error) {
        console.error(error);
        if (error.response && error.response.data && error.response.data.errors) {
            Object.values(error.response.data.errors).forEach(err => {
                toast.error(err[0]);
            });
        } else if (error.response && error.response.data) {
            toast.error(error.response.data.message || 'Erro ao salvar membro');
        } else {
            toast.error('Erro ao salvar membro');
        }
    } finally {
        loading.value = false;
    }
};

const confirmDelete = async (membro) => {
    if (confirm(`Tem certeza que deseja remover ${membro.nome}?`)) {
        try {
            await api.delete(`/diretoria/${membro.id}`);
            toast.success('Membro removido');
            await fetchMembros();
        } catch (error) {
            console.error(error);
            toast.error(error.response?.data?.message || 'Erro ao remover');
        }
    }
};

onMounted(fetchMembros);
</script>
