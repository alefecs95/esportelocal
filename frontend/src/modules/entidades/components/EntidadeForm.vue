<template>
  <div class="bg-white rounded-lg shadow p-6">
    <h2 class="text-xl font-bold mb-6 text-gray-800">{{ isEditing ? 'Editar Clube' : 'Novo Clube' }}</h2>
    
    <form @submit.prevent="handleSubmit" class="space-y-4">
      <!-- Logo Upload -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Logo do Clube (Opcional)</label>
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

      <!-- Nome -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Nome do Clube/Entidade *</label>
        <input 
          v-model="form.nome" 
          type="text" 
          required 
          class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
          placeholder="Ex: Flamengo, Vasco, Associação Esportiva X"
        >
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Sigla -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Sigla</label>
          <input 
            v-model="form.sigla" 
            type="text" 
            class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
            placeholder="Ex: CRF"
            maxlength="10"
          >
        </div>

        <!-- Data Fundação -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Data de Fundação</label>
          <input 
            v-model="form.data_fundacao" 
            type="date" 
            class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
          >
        </div>
      </div>

      <!-- Responsável do Clube (Login) -->
      <div class="mb-4 bg-yellow-50 p-4 rounded-lg border border-yellow-200">
          <label class="block text-gray-700 text-sm font-bold mb-2">
              Vincular Usuário Responsável (Login)
          </label>
          <p class="text-xs text-gray-600 mb-2">
              Busque por nome ou CPF para permitir que este usuário gerencie o clube e suas equipes.
          </p>
          
          <div class="relative">
              <input 
                  type="text" 
                  v-model="userSearch"
                  @input="searchUsers"
                  placeholder="Buscar responsável..."
                  class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
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

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Responsável -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Nome do Presidente/Responsável</label>
          <input 
            v-model="form.responsavel" 
            type="text" 
            class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
            placeholder="Nome do presidente ou contato"
          >
        </div>

        <!-- Contato -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Contato (Tel/Email)</label>
          <input 
            v-model="form.contato" 
            type="text" 
            class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
            placeholder="(00) 00000-0000"
          >
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Cidade -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Cidade Sede</label>
          <input 
            v-model="form.cidade" 
            type="text" 
            class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
          >
        </div>

        <!-- Bairro -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Bairro Sede</label>
          <input 
            v-model="form.bairro" 
            type="text" 
            class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
          >
        </div>
      </div>

      <!-- Actions -->
      <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
        <button 
          type="button" 
          @click="$emit('cancel')"
          class="px-4 py-2 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg font-medium transition-colors"
        >
          Cancelar
        </button>
        <button 
          type="submit" 
          class="px-4 py-2 bg-blue-600 text-white hover:bg-blue-700 rounded-lg font-medium transition-colors shadow-sm disabled:opacity-50"
          :disabled="loading"
        >
          {{ loading ? 'Salvando...' : 'Salvar Clube' }}
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
import userService from '@/modules/admin/services/userService';

const props = defineProps({
  initialData: {
    type: Object,
    default: () => ({})
  },
  loading: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits(['submit', 'cancel']);

const form = ref({
  nome: '',
  sigla: '',
  data_fundacao: '',
  responsavel: '',
  contato: '',
  cidade: '',
  bairro: '',
  logo: null,
  user_id: null
});

const previewLogo = ref(null);
const isEditing = ref(false);
const userSearch = ref('');
const userResults = ref([]);
const selectedUser = ref(null);

watch(() => props.initialData, (newVal) => {
  if (newVal) {
    isEditing.value = true;
    form.value = { 
        ...newVal,
        data_fundacao: newVal.data_fundacao ? newVal.data_fundacao.split('T')[0] : ''
    };
    if (newVal.logo_url) {
        previewLogo.value = newVal.logo_url;
    }
    if (newVal.user) {
        selectedUser.value = newVal.user;
    }
  } else {
    isEditing.value = false;
    form.value = {
      nome: '',
      sigla: '',
      data_fundacao: '',
      responsavel: '',
      contato: '',
      cidade: '',
      bairro: '',
      logo: null,
      user_id: null
    };
    previewLogo.value = null;
    selectedUser.value = null;
  }
}, { immediate: true });

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
    form.value.user_id = user.id;
    // Auto-fill responsible info if empty
    if (!form.value.responsavel) form.value.responsavel = user.name;
    if (!form.value.contato) form.value.contato = user.email;
    
    userResults.value = [];
    userSearch.value = '';
};

const clearUser = () => {
    selectedUser.value = null;
    form.value.user_id = null;
};

const handleLogoUpload = (event) => {
    const file = event.target.files[0];
    if (file) {
        form.value.logo = file;
        previewLogo.value = URL.createObjectURL(file);
    }
};

const handleSubmit = () => {
    // Convert to FormData
    const formData = new FormData();
    Object.keys(form.value).forEach(key => {
        if (key === 'logo') {
            if (form.value.logo instanceof File) {
                formData.append('logo', form.value.logo);
            }
        } else if (form.value[key] !== null && form.value[key] !== undefined) {
            formData.append(key, form.value[key]);
        }
    });

  emit('submit', formData);
};
</script>
