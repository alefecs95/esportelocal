<template>
  <div class="space-y-6">
    <div class="flex justify-between items-center">
      <div class="flex items-center gap-4">
        <button @click="$router.back()" class="p-2 hover:bg-gray-100 rounded-full">
          <ArrowLeft :size="24" class="text-gray-600" />
        </button>
        <div>
          <h1 class="text-2xl font-bold text-gray-800">{{ equipe.nome }}</h1>
          <p class="text-gray-500 text-sm">{{ equipe.campeonato?.nome }}</p>
        </div>
      </div>
      
      <span 
        v-if="equipe.status"
        :class="getStatusBadge(equipe.status)" 
        class="px-3 py-1 rounded-full text-sm font-semibold uppercase tracking-wide"
      >
        {{ equipe.status }}
      </span>
    </div>

    <div v-if="loading" class="flex justify-center py-12">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
    </div>

    <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Athletes List -->
      <div class="lg:col-span-2 space-y-4">
        <div class="flex justify-between items-center">
          <h2 class="text-lg font-bold text-gray-800 flex items-center gap-2">
            <Users :size="20" />
            Elenco ({{ atletasAprovadosCount }})
          </h2>
          <div class="text-xs text-gray-500">
            Pendentes de aceite: {{ atletasPendentesCount }}
          </div>
          <button 
            @click="showAddModal = true"
            class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700 flex items-center gap-2"
          >
            <PlusCircle :size="16" />
            Adicionar Atleta
          </button>
        </div>

        <div v-if="!equipe.atletas?.length" class="bg-white p-8 rounded-xl border border-gray-200 text-center text-gray-500">
          Nenhum atleta adicionado a esta equipe ainda.
        </div>

        <div v-else class="bg-white rounded-xl shadow-sm border border-gray-200 divide-y divide-gray-100">
          <div v-for="atleta in equipe.atletas" :key="atleta.id" class="p-4 flex justify-between items-center hover:bg-gray-50 transition-colors">
            <div class="flex items-center gap-4">
              <div class="h-10 w-10 bg-gray-100 rounded-full flex items-center justify-center font-bold text-gray-600">
                {{ atleta.nome?.charAt(0) }}
              </div>
              <div>
                <h4 class="font-medium text-gray-900">{{ atleta.nome }}</h4>
                <p class="text-xs text-gray-500">CPF: {{ atleta.cpf }}</p>
              </div>
            </div>
            <div class="flex items-center gap-3">
              <span
                :class="getAtletaStatusBadge(atleta.pivot?.status)"
                class="px-2 py-1 rounded-full text-[11px] font-semibold uppercase tracking-wide"
              >
                {{ formatAtletaStatus(atleta.pivot?.status) }}
              </span>
            <button 
              @click="removeAthlete(atleta)"
              class="text-red-500 hover:text-red-700 p-2 rounded-full hover:bg-red-50 transition-colors"
              title="Remover atleta"
            >
              <Trash2 :size="18" />
            </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Team Info / Stats -->
      <div class="space-y-6">
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
          <h3 class="font-bold text-gray-800 mb-4">Informações</h3>
          <div class="space-y-3 text-sm">
            <div class="flex justify-between">
              <span class="text-gray-500">Status</span>
              <span class="font-medium">{{ equipe.status }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-500">Convites pendentes</span>
              <span class="font-medium">{{ atletasPendentesCount }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-500">Contato</span>
              <span class="font-medium">{{ equipe.contato }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-500">Responsável</span>
              <span class="font-medium">{{ equipe.responsavel || 'N/A' }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Add Athlete Modal -->
    <div v-if="showAddModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
      <div class="bg-white rounded-xl max-w-md w-full p-6 shadow-xl">
        <h3 class="text-lg font-bold text-gray-900 mb-4">Adicionar Atleta</h3>
        <p class="text-sm text-gray-500 mb-6">
          Busque o atleta pelo CPF para adicionar à equipe.
        </p>

        <form @submit.prevent="searchAndAdd">
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">CPF do Atleta</label>
            <div class="flex gap-2">
              <input 
                v-model="cpfSearch" 
                type="text" 
                required
                class="flex-1 border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none"
                placeholder="000.000.000-00"
              >
              <button 
                type="submit" 
                :disabled="searching"
                class="bg-blue-600 text-white px-4 rounded-lg hover:bg-blue-700 flex items-center"
              >
                <Search v-if="!searching" :size="20" />
                <Loader2 v-else :size="20" class="animate-spin" />
              </button>
            </div>
          </div>

          <div v-if="foundAthlete" class="mb-6 bg-blue-50 p-4 rounded-lg border border-blue-100">
            <h4 class="font-bold text-blue-900">{{ foundAthlete.nome }}</h4>
            <p class="text-sm text-blue-700 mb-3">CPF: {{ foundAthlete.cpf }}</p>
            
            <div v-if="!foundAthlete.id" class="mb-3 text-sm text-orange-700 bg-orange-100 p-2 rounded">
               Este atleta ainda não está cadastrado no sistema.
               <br>Por favor, realize o cadastro completo antes de adicionar.
            </div>

            <button 
              type="button" 
              @click="confirmAdd"
              :disabled="adding || !foundAthlete.id"
              class="w-full bg-blue-600 text-white py-2 rounded-lg font-medium hover:bg-blue-700 flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <PlusCircle v-if="!adding" :size="18" />
              <Loader2 v-else :size="18" class="animate-spin" />
              Adicionar à Equipe
            </button>
          </div>
          
          <div v-if="searchError" class="mb-6 text-red-600 text-sm bg-red-50 p-3 rounded-lg">
            {{ searchError }}
          </div>

          <div class="flex justify-end">
            <button 
              type="button" 
              @click="closeModal"
              class="text-gray-600 hover:text-gray-800 font-medium"
            >
              Fechar
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import managerService from '../services/managerService';
import { ArrowLeft, Users, PlusCircle, Trash2, Search, Loader2 } from 'lucide-vue-next';

const route = useRoute();
const router = useRouter();
const equipe = ref({});
const loading = ref(true);
const showAddModal = ref(false);

const cpfSearch = ref('');
const searching = ref(false);
const foundAthlete = ref(null);
const searchError = ref('');
const adding = ref(false);

const atletasPendentesCount = computed(() => (equipe.value?.atletas || []).filter((a) => a.pivot?.status === 'pendente').length);
const atletasAprovadosCount = computed(() => (equipe.value?.atletas || []).filter((a) => (a.pivot?.status || 'aprovado') === 'aprovado').length);

const loadData = async () => {
  loading.value = true;
  try {
    const { data } = await managerService.getTeamDetails(route.params.id);
    equipe.value = data;
  } catch (e) {
    console.error(e);
    if (e.response?.status === 403) {
      alert('Você não tem permissão para acessar esta equipe.');
      router.push('/manager/minhas-equipes');
    }
  } finally {
    loading.value = false;
  }
};

const getStatusBadge = (status) => {
  const map = {
    'pendente': 'bg-yellow-100 text-yellow-800',
    'aprovada': 'bg-green-100 text-green-800',
    'rejeitada': 'bg-red-100 text-red-800'
  };
  return map[status] || 'bg-gray-100 text-gray-800';
};

const getAtletaStatusBadge = (status) => {
  const map = {
    pendente: 'bg-amber-100 text-amber-800',
    aprovado: 'bg-emerald-100 text-emerald-800',
    rejeitado: 'bg-red-100 text-red-800'
  };
  return map[status] || 'bg-slate-100 text-slate-700';
};

const formatAtletaStatus = (status) => {
  const map = {
    pendente: 'Pendente',
    aprovado: 'Aprovado',
    rejeitado: 'Rejeitado'
  };
  return map[status] || 'Aprovado';
};

const searchAndAdd = async () => {
  if (!cpfSearch.value) return;
  
  searching.value = true;
  searchError.value = '';
  foundAthlete.value = null;
  
  try {
    // Remove formatting
    const cleanCpf = cpfSearch.value.replace(/\D/g, '');
    const { data } = await managerService.searchAthleteByCpf(cleanCpf);
    
    if (data && data.data) {
      foundAthlete.value = data.data;
    } else {
      searchError.value = 'Atleta não encontrado.';
    }
  } catch (e) {
    searchError.value = 'Atleta não encontrado ou erro na busca.';
  } finally {
    searching.value = false;
  }
};

const confirmAdd = async () => {
  if (!foundAthlete.value) return;
  
  adding.value = true;
  try {
    await managerService.addAthlete(equipe.value.id, foundAthlete.value.id);
    // Refresh list
    await loadData();
    closeModal();
    alert('Atleta adicionado com sucesso!');
  } catch (e) {
    alert(e.response?.data?.message || 'Erro ao adicionar atleta.');
  } finally {
    adding.value = false;
  }
};

const removeAthlete = async (atleta) => {
  if (!confirm(`Tem certeza que deseja remover ${atleta.nome} da equipe?`)) return;
  
  try {
    await managerService.removeAthlete(equipe.value.id, atleta.id);
    await loadData();
  } catch (e) {
    alert('Erro ao remover atleta.');
  }
};

const closeModal = () => {
  showAddModal.value = false;
  cpfSearch.value = '';
  foundAthlete.value = null;
  searchError.value = '';
};

onMounted(loadData);
</script>
