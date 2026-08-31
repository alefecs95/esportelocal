<template>
  <div class="space-y-6">
    <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-blue-600">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
          <!-- Foto de Perfil -->
          <div class="relative group">
            <div 
              class="w-20 h-20 rounded-full bg-blue-100 flex items-center justify-center overflow-hidden border-2 border-white shadow-md cursor-pointer"
              @click="$refs.photoInput.click()"
            >
              <img v-if="form.foto_url" :src="form.foto_url" class="w-full h-full object-cover" alt="Foto de Perfil" />
              <UserCircle v-else :size="48" class="text-blue-600" />
              
              <!-- Overlay Hover -->
              <div class="absolute inset-0 bg-black/30 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity rounded-full">
                <Camera :size="24" class="text-white" />
              </div>
            </div>
            <input 
              type="file" 
              ref="photoInput" 
              class="hidden" 
              accept="image/*"
              @change="handlePhotoUpload"
            >
            <div v-if="photoUploading" class="absolute -bottom-2 -right-2">
               <Loader2 :size="20" class="animate-spin text-blue-600" />
            </div>
          </div>

          <div>
            <h1 class="text-2xl font-bold text-gray-900">Meu Perfil</h1>
            <p class="text-gray-600 mt-1">Mantenha seus dados atualizados e envie seus documentos.</p>
          </div>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Formulário de Dados -->
      <div class="lg:col-span-2 space-y-6">
        <form @submit.prevent="handleUpdateProfile" class="bg-white rounded-xl shadow-sm overflow-hidden">
          <div class="bg-gray-50 px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-semibold text-gray-800 flex items-center gap-2">
              <User :size="20" class="text-blue-600" />
              Dados Pessoais
            </h3>
          </div>
          
          <div class="p-6 space-y-6">
            <!-- Readonly Fields -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-gray-50 p-4 rounded-lg border border-gray-100">
              <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Nome Completo</label>
                <div class="font-medium text-gray-900">{{ form.nome }}</div>
              </div>
              <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">CPF</label>
                <div class="font-medium text-gray-900">{{ form.cpf }}</div>
              </div>
              <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Data de Nascimento</label>
                <div class="font-medium text-gray-900">{{ formatDate(form.data_nascimento) }}</div>
              </div>
              <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Sexo</label>
                <div class="font-medium text-gray-900 capitalize">{{ form.sexo || 'Não informado' }}</div>
              </div>
               <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Nome da Mãe</label>
                <div class="font-medium text-gray-900">{{ form.nome_mae || 'Não informado' }}</div>
              </div>
              <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Nome do Pai</label>
                <div class="font-medium text-gray-900">{{ form.nome_pai || 'Não informado' }}</div>
              </div>
            </div>

            <div class="border-t border-gray-100 pt-4">
              <h4 class="text-sm font-semibold text-gray-900 mb-4">Contato e Endereço</h4>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                  <input v-model="form.email" type="email" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Telefone / WhatsApp</label>
                  <input v-model="form.telefone" type="text" v-mask="'(##) #####-####'" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">CEP</label>
                  <input v-model="form.cep" type="text" v-mask="'#####-###'" @blur="buscaCep" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                </div>
                <div class="md:col-span-2">
                  <label class="block text-sm font-medium text-gray-700 mb-1">Endereço</label>
                  <input v-model="form.endereco" type="text" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Número</label>
                  <input v-model="form.numero" type="text" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Complemento</label>
                  <input v-model="form.complemento" type="text" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Bairro</label>
                  <input v-model="form.bairro" type="text" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Cidade</label>
                  <input v-model="form.cidade" type="text" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                  <input v-model="form.estado" type="text" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                </div>
              </div>
            </div>

            <!-- Dados Eleitorais Ocultos/Removidos conforme solicitação -->
          </div>

          <div class="bg-gray-50 px-6 py-4 border-t border-gray-100 flex justify-end">
             <button 
              type="submit" 
              :disabled="loading"
              class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 focus:ring-4 focus:ring-blue-100 transition-all flex items-center gap-2 disabled:opacity-50"
            >
              <Loader2 v-if="loading" :size="20" class="animate-spin" />
              <span>Salvar Alterações</span>
            </button>
          </div>
        </form>
      </div>

      <!-- Documentos -->
      <div class="space-y-6">
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
          <div class="bg-gray-50 px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-semibold text-gray-800 flex items-center gap-2">
              <FileText :size="20" class="text-orange-500" />
              Documentos
            </h3>
          </div>
          
          <div class="p-6 space-y-4">
            <!-- Upload Area -->
            <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:bg-gray-50 transition-colors cursor-pointer" @click="$refs.fileInput.click()">
              <input 
                type="file" 
                ref="fileInput" 
                class="hidden" 
                @change="handleFileUpload"
                accept="image/*,.pdf"
              >
              <UploadCloud :size="32" class="mx-auto text-gray-400 mb-2" />
              <p class="text-sm font-medium text-gray-900">Clique para enviar</p>
              <p class="text-xs text-gray-500">PDF, JPG ou PNG (Máx 10MB)</p>
            </div>

            <!-- Lista de Arquivos -->
            <div v-if="arquivos.length > 0" class="space-y-3 mt-4">
              <div v-for="arq in arquivos" :key="arq.id" class="flex items-center justify-between p-3 bg-gray-50 rounded-lg border border-gray-100">
                <div class="flex items-center gap-3 overflow-hidden">
                  <FileText :size="20" class="text-gray-400 flex-shrink-0" />
                  <div class="truncate">
                    <p class="text-sm font-medium text-gray-900 truncate" :title="arq.nome">{{ arq.nome }}</p>
                    <p class="text-xs text-gray-500">{{ formatDate(arq.created_at) }}</p>
                  </div>
                </div>
                <div class="flex items-center gap-2">
                  <a :href="arq.url" target="_blank" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="Visualizar">
                    <Eye :size="16" />
                  </a>
                  <button @click="deleteFile(arq.id)" class="p-1.5 text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Excluir">
                    <Trash2 :size="16" />
                  </button>
                </div>
              </div>
            </div>
            <div v-else class="text-center py-4 text-gray-500 text-sm">
              Nenhum documento enviado.
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Upload Name -->
    <div v-if="showUploadModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-6">
        <h3 class="text-lg font-bold text-gray-900 mb-4">Nome do Documento</h3>
        <input 
          v-model="uploadName" 
          type="text" 
          placeholder="Ex: RG, CPF, Comprovante de Residência" 
          class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500 mb-4"
          autofocus
        >
        <div class="flex justify-end gap-2">
          <button @click="cancelUpload" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg">Cancelar</button>
          <button 
            @click="confirmUpload" 
            :disabled="!uploadName || uploading"
            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 flex items-center gap-2"
          >
            <Loader2 v-if="uploading" :size="16" class="animate-spin" />
            Enviar
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { UserCircle, User, FileText, UploadCloud, Eye, Trash2, Loader2, Camera } from 'lucide-vue-next';
import athleteService from '../services/athleteService';
import axios from 'axios';

const loading = ref(false);
const uploading = ref(false);
const photoUploading = ref(false);
const form = ref({});
const arquivos = ref([]);
const showUploadModal = ref(false);
const uploadName = ref('');
const selectedFile = ref(null);
const photoInput = ref(null);

const loadProfile = async () => {
  loading.value = true;
  try {
    const { data } = await athleteService.getProfile();
    form.value = { ...data };
    arquivos.value = data.arquivos || [];
  } catch (error) {
    console.error('Erro ao carregar perfil:', error);
    alert('Erro ao carregar perfil.');
  } finally {
    loading.value = false;
  }
};

const handlePhotoUpload = async (event) => {
  const file = event.target.files[0];
  if (!file) return;

  if (file.size > 5 * 1024 * 1024) {
    alert('A foto deve ter no máximo 5MB.');
    return;
  }

  photoUploading.value = true;
  const formData = new FormData();
  formData.append('foto', file);

  try {
    const { data } = await athleteService.uploadPhoto(formData);
    form.value.foto_url = data.foto_url;
    alert('Foto atualizada com sucesso!');
  } catch (error) {
    console.error('Erro ao atualizar foto:', error);
    alert('Erro ao atualizar foto.');
  } finally {
    photoUploading.value = false;
    event.target.value = '';
  }
};

const handleUpdateProfile = async () => {
  loading.value = true;
  try {
    const { data } = await athleteService.updateProfile(form.value);
    alert('Perfil atualizado com sucesso!');
    // Atualizar dados locais se necessário
  } catch (error) {
    console.error('Erro ao atualizar perfil:', error);
    alert('Erro ao atualizar perfil. Verifique os dados.');
  } finally {
    loading.value = false;
  }
};

const handleFileUpload = (event) => {
  const file = event.target.files[0];
  if (!file) return;
  
  if (file.size > 10 * 1024 * 1024) {
    alert('O arquivo deve ter no máximo 10MB.');
    return;
  }

  selectedFile.value = file;
  uploadName.value = file.name;
  showUploadModal.value = true;
  
  // Reset input
  event.target.value = '';
};

const cancelUpload = () => {
  showUploadModal.value = false;
  selectedFile.value = null;
  uploadName.value = '';
};

const confirmUpload = async () => {
  if (!selectedFile.value || !uploadName.value) return;

  uploading.value = true;
  const formData = new FormData();
  formData.append('file', selectedFile.value);
  formData.append('nome', uploadName.value);

  try {
    const { data } = await athleteService.uploadDocument(formData);
    arquivos.value.unshift(data);
    cancelUpload();
  } catch (error) {
    console.error('Erro ao enviar documento:', error);
    alert('Erro ao enviar documento.');
  } finally {
    uploading.value = false;
  }
};

const deleteFile = async (id) => {
  if (!confirm('Tem certeza que deseja excluir este documento?')) return;

  try {
    await athleteService.deleteDocument(id);
    arquivos.value = arquivos.value.filter(a => a.id !== id);
  } catch (error) {
    console.error('Erro ao excluir documento:', error);
    alert('Erro ao excluir documento.');
  }
};

const buscaCep = async () => {
  const cep = form.value.cep?.replace(/\D/g, '');
  if (cep?.length !== 8) return;

  try {
    const { data } = await axios.get(`https://viacep.com.br/ws/${cep}/json/`);
    if (!data.erro) {
      form.value.endereco = data.logradouro;
      form.value.bairro = data.bairro;
      form.value.cidade = data.localidade;
      form.value.estado = data.uf;
    }
  } catch (error) {
    console.error('Erro ao buscar CEP:', error);
  }
};

const formatDate = (dateString) => {
  if (!dateString) return '';
  return new Date(dateString).toLocaleDateString('pt-BR');
};

onMounted(loadProfile);
</script>
