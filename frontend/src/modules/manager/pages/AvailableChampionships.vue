<template>
  <div class="space-y-6">
    <div class="flex justify-between items-center">
      <h1 class="text-2xl font-bold text-gray-800">Competições Disponíveis</h1>
    </div>

    <div v-if="loading" class="flex justify-center py-12">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
    </div>

    <div v-else-if="campeonatos.length === 0" class="text-center py-12 bg-white rounded-lg border border-gray-200">
      <p class="text-gray-500">Nenhum campeonato com inscrições abertas no momento.</p>
    </div>

    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <div v-for="camp in campeonatos" :key="camp.id" class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden flex flex-col">
        <div class="p-6 flex-1">
          <div class="flex justify-between items-start mb-4">
            <span class="bg-blue-100 text-blue-700 text-xs font-semibold px-2.5 py-0.5 rounded uppercase tracking-wide">
              {{ camp.modalidade?.nome }}
            </span>
            <span class="text-xs text-gray-500 font-medium bg-gray-100 px-2 py-1 rounded">
              {{ camp.categoria }}
            </span>
          </div>
          
          <h3 class="text-xl font-bold text-gray-900 mb-2">{{ camp.nome }}</h3>
          
          <div class="space-y-2 text-sm text-gray-600 mb-6">
            <div class="flex items-center gap-2">
              <Calendar :size="16" />
              <span>Início: {{ formatDate(camp.data_inicio) }}</span>
            </div>
            <div class="flex items-center gap-2">
              <Users :size="16" />
              <span>Max: {{ camp.max_atletas_equipe || 'Sem limite' }} atletas/equipe</span>
            </div>
          </div>
        </div>
        
        <div class="bg-gray-50 px-6 py-4 border-t border-gray-100 flex justify-center">
          <button
            v-if="camp.ja_inscrito && camp.equipe_id"
            @click="goToManage(camp.equipe_id)"
            class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-medium py-2 px-4 rounded transition-colors text-sm flex items-center justify-center gap-2"
          >
            <Settings :size="16" />
            Gerenciar Equipe na Competição
          </button>
          <button
            v-else-if="camp.permite_inscricao_online"
            @click="handleSubscribe(camp)"
            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded transition-colors text-sm flex items-center justify-center gap-2"
          >
            <Users :size="16" />
            Solicitar Inscrição
          </button>
          <p v-else class="text-xs text-gray-500 text-center">
            Para inscrever sua equipe, entre em contato com a Secretaria.
          </p>
        </div>
      </div>
    </div>

    <!-- Modal de Inscrição -->
    <BaseModal
      :is-open="showSubscribeModal"
      title="Solicitar Inscrição"
      @close="closeSubscribeModal"
      @confirm="submitSubscription"
      :loading="submitting"
    >
      <div v-if="selectedChampionship" class="space-y-4">
        <p class="text-sm text-gray-600">
          Você está solicitando inscrição para o campeonato <strong>{{ selectedChampionship.nome }}</strong>.
        </p>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Nome da Equipe</label>
          <input
            v-model="newTeam.nome"
            type="text"
            class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
            placeholder="Digite o nome da equipe"
          />
        </div>

        <div>
           <label class="block text-sm font-medium text-gray-700 mb-1">Responsável Técnico</label>
           <input
             v-model="newTeam.responsavel"
             type="text"
             class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
             placeholder="Nome do responsável técnico"
           />
        </div>

        <div>
           <label class="block text-sm font-medium text-gray-700 mb-1">Contato</label>
           <input
             v-model="newTeam.contato"
             type="text"
             class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
             placeholder="Telefone/WhatsApp"
           />
        </div>
      </div>
    </BaseModal>
  </div>
</template>

<script setup>
import { ref, onMounted, reactive } from 'vue';
import { useRouter } from 'vue-router';
import managerService from '../services/managerService';
import { Calendar, Users, Settings } from 'lucide-vue-next';
import BaseModal from '@/components/ui/BaseModal.vue';

const router = useRouter();
const campeonatos = ref([]);
const loading = ref(true);

const showSubscribeModal = ref(false);
const selectedChampionship = ref(null);
const submitting = ref(false);

const newTeam = reactive({
  nome: '',
  responsavel: '',
  contato: ''
});

const loadData = async () => {
  loading.value = true;
  try {
    const { data } = await managerService.getAvailableChampionships();
    campeonatos.value = data;
  } catch (e) {
    console.error(e);
  } finally {
    loading.value = false;
  }
};

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('pt-BR');
};

const handleSubscribe = (camp) => {
  selectedChampionship.value = camp;
  newTeam.nome = '';
  newTeam.responsavel = ''; 
  newTeam.contato = '';
  showSubscribeModal.value = true;
};

const closeSubscribeModal = () => {
  showSubscribeModal.value = false;
  selectedChampionship.value = null;
};

const goToManage = (equipeId) => {
  router.push(`/manager/equipes/${equipeId}`);
};

const submitSubscription = async () => {
  if (!newTeam.nome) {
    alert('Por favor, preencha o nome da equipe.');
    return;
  }

  submitting.value = true;
  try {
    await managerService.registerTeam({
      campeonato_id: selectedChampionship.value.id,
      ...newTeam,
      status_inscricao: 'pendente'
    });
    alert('Solicitação de inscrição enviada com sucesso! Aguarde a aprovação do administrador.');
    closeSubscribeModal();
    // Poderíamos redirecionar para "Minhas Equipes" ou atualizar a lista
  } catch (error) {
    console.error(error);
    alert('Erro ao solicitar inscrição.');
  } finally {
    submitting.value = false;
  }
};

onMounted(loadData);
</script>
