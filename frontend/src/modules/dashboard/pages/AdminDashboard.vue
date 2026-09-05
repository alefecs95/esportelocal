<template>
  <div class="space-y-6">
    <div class="flex justify-between items-center">
      <h1 class="text-2xl font-bold text-gray-800">Painel Administrativo</h1>
      <span class="text-sm text-gray-500">{{ currentDate }}</span>
    </div>

    <div v-if="loading" class="flex justify-center py-12">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
    </div>

    <div v-else class="space-y-6">
      <div v-if="error" class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
        {{ error }}
      </div>

      <div v-if="pendencias > 0" class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <router-link to="/admin/atletas" class="bg-amber-50 border border-amber-200 rounded-xl p-4 hover:bg-amber-100">
          <p class="text-sm font-semibold text-amber-900">Validação TSE pendente</p>
          <p class="text-2xl font-bold text-amber-800 mt-1">{{ stats.cards?.atletas_tse_pendentes || 0 }}</p>
        </router-link>
        <router-link to="/admin/memoria" class="bg-blue-50 border border-blue-200 rounded-xl p-4 hover:bg-blue-100">
          <p class="text-sm font-semibold text-blue-900">Fila da Memória</p>
          <p class="text-2xl font-bold text-blue-800 mt-1">{{ stats.cards?.memoria_fila || 0 }}</p>
        </router-link>
        <router-link to="/admin/calendario" class="bg-orange-50 border border-orange-200 rounded-xl p-4 hover:bg-orange-100">
          <p class="text-sm font-semibold text-orange-900">Jogos sem local</p>
          <p class="text-2xl font-bold text-orange-800 mt-1">{{ stats.cards?.jogos_sem_local || 0 }}</p>
        </router-link>
      </div>

      <div v-if="stats.cards?.jogos_sem_sumula" class="bg-white border border-gray-100 rounded-xl p-4 text-sm text-gray-700">
        Há <strong>{{ stats.cards.jogos_sem_sumula }}</strong> jogos finalizados sem placar/súmula.
      </div>
      <!-- Stats Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6">
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center gap-4">
          <div class="p-3 bg-blue-100 text-blue-600 rounded-lg">
            <Users :size="24" />
          </div>
          <div>
            <p class="text-sm text-gray-500 font-medium">Total de Atletas</p>
            <h3 class="text-2xl font-bold text-gray-800">{{ stats.cards?.atletas || 0 }}</h3>
          </div>
        </div>

        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center gap-4">
          <div class="p-3 bg-green-100 text-green-600 rounded-lg">
            <Trophy :size="24" />
          </div>
          <div>
            <p class="text-sm text-gray-500 font-medium">Campeonatos Ativos</p>
            <h3 class="text-2xl font-bold text-gray-800">{{ stats.cards?.campeonatos_ativos || 0 }}</h3>
          </div>
        </div>

        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center gap-4">
          <div class="p-3 bg-purple-100 text-purple-600 rounded-lg">
            <Shield :size="24" />
          </div>
          <div>
            <p class="text-sm text-gray-500 font-medium">Clubes Cadastrados</p>
            <h3 class="text-2xl font-bold text-gray-800">{{ stats.cards?.clubes || 0 }}</h3>
          </div>
        </div>

        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center gap-4">
          <div class="p-3 bg-yellow-100 text-yellow-600 rounded-lg">
            <FileText :size="24" />
          </div>
          <div>
            <p class="text-sm text-gray-500 font-medium">Inscrições Abertas</p>
            <h3 class="text-2xl font-bold text-gray-800">{{ stats.cards?.inscricoes_abertas || 0 }}</h3>
          </div>
        </div>

        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center gap-4">
          <div class="p-3 bg-orange-100 text-orange-600 rounded-lg">
            <TrendingUp :size="24" />
          </div>
          <div>
            <p class="text-sm text-gray-500 font-medium">Novas Inscrições (7d)</p>
            <h3 class="text-2xl font-bold text-gray-800">{{ stats.cards?.inscricoes_recentes || 0 }}</h3>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Inscrições Pendentes -->
        <div class="lg:col-span-3 bg-white rounded-xl shadow-sm border border-gray-100 p-6" v-if="stats.inscricoes_pendentes?.length">
          <div class="flex justify-between items-center mb-6">
            <h2 class="text-lg font-bold text-gray-800 flex items-center gap-2 text-yellow-600">
              <AlertCircle :size="20" /> Inscrições Pendentes
            </h2>
          </div>
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Equipe</th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Campeonato</th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Responsável</th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="inscricao in stats.inscricoes_pendentes" :key="inscricao.id">
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-medium text-gray-900">{{ inscricao.nome }}</div>
                    <div class="text-xs text-gray-500">{{ inscricao.entidade?.nome }}</div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ inscricao.campeonato?.nome }}</td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-gray-900">{{ inscricao.responsavel }}</div>
                    <div class="text-xs text-gray-500">{{ inscricao.contato }}</div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium flex gap-2">
                    <button @click="approveInscricao(inscricao.id)" class="text-green-600 hover:text-green-900 bg-green-50 px-3 py-1 rounded-md border border-green-200">Aprovar</button>
                    <button @click="rejectInscricao(inscricao.id)" class="text-red-600 hover:text-red-900 bg-red-50 px-3 py-1 rounded-md border border-red-200">Rejeitar</button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Próximos Jogos -->
        <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100 p-6">
          <div class="flex justify-between items-center mb-6">
            <h2 class="text-lg font-bold text-gray-800 flex items-center gap-2">
              <Calendar :size="20" /> Próximos Jogos
            </h2>
            <router-link to="/admin/calendario" class="text-blue-600 text-sm font-medium hover:underline">Ver Calendário</router-link>
          </div>

          <div v-if="!stats.proximos_jogos?.length" class="text-center py-8 text-gray-500 text-sm">
            Nenhum jogo agendado para os próximos dias.
          </div>

          <div v-else class="space-y-4">
            <div v-for="jogo in stats.proximos_jogos" :key="jogo.id" class="flex items-center justify-between p-4 bg-gray-50 rounded-lg border border-gray-100 hover:bg-gray-100 transition-colors">
              <div class="flex items-center gap-4">
                <div class="text-center min-w-[60px]">
                  <div class="font-bold text-gray-800">{{ formatDate(jogo.data_jogo) }}</div>
                  <div class="text-xs text-gray-500">{{ formatTime(jogo.data_jogo) }}</div>
                </div>
                <div>
                  <div class="text-sm font-bold text-gray-800">
                    {{ jogo.mandante?.nome }} <span class="text-gray-400 mx-1">vs</span> {{ jogo.visitante?.nome }}
                  </div>
                  <div class="text-xs text-gray-500 flex items-center gap-1">
                    <MapPin :size="10" /> {{ jogo.local || 'Local a definir' }}
                    <span class="mx-1">•</span>
                    {{ jogo.fase?.campeonato?.nome }}
                  </div>
                </div>
              </div>
              <router-link :to="`/admin/partidas/${jogo.id}`" class="p-2 text-gray-400 hover:text-blue-600 transition-colors">
                <ArrowRight :size="18" />
              </router-link>
            </div>
          </div>
        </div>

        <!-- Atletas por Modalidade (Mini Gráfico/Lista) -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
          <h2 class="text-lg font-bold text-gray-800 mb-6 flex items-center gap-2">
            <BarChart3 :size="20" /> Top Modalidades
          </h2>
          
          <div v-if="!stats.graficos?.atletas_modalidade?.length" class="text-center py-8 text-gray-500 text-sm">
            Sem dados suficientes.
          </div>

          <div v-else class="space-y-4">
            <div v-for="(item, index) in stats.graficos.atletas_modalidade" :key="index" class="space-y-1">
              <div class="flex justify-between text-sm">
                <span class="font-medium text-gray-700">{{ item.nome }}</span>
                <span class="text-gray-500">{{ item.total }} atletas</span>
              </div>
              <div class="w-full bg-gray-100 rounded-full h-2">
                <div 
                  class="bg-blue-600 h-2 rounded-full" 
                  :style="{ width: `${(item.total / maxAtletas) * 100}%` }"
                ></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <ConfirmDialog
      :is-open="confirm.isOpen.value"
      :title="confirm.title.value"
      :message="confirm.message.value"
      @confirm="confirm.confirm"
      @cancel="confirm.cancel"
    />
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useToast } from 'vue-toastification';
import dashboardService from '../services/dashboardService';
import managerService from '../../manager/services/managerService';
import { Users, Trophy, Shield, FileText, Calendar, MapPin, ArrowRight, BarChart3, TrendingUp, AlertCircle } from 'lucide-vue-next';
import ConfirmDialog from '@/components/ui/ConfirmDialog.vue';
import { useConfirmDialog } from '@/composables/useConfirmDialog';

const toast = useToast();
const confirm = useConfirmDialog();
const stats = ref({});
const loading = ref(true);
const error = ref('');

const currentDate = new Date().toLocaleDateString('pt-BR', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });

const maxAtletas = computed(() => {
  if (!stats.value.graficos?.atletas_modalidade) return 1;
  return Math.max(...stats.value.graficos.atletas_modalidade.map(i => i.total));
});

const pendencias = computed(() => {
  const cards = stats.value.cards || {};
  return (cards.atletas_tse_pendentes || 0) + (cards.memoria_fila || 0) + (cards.jogos_sem_local || 0);
});

const loadStats = async () => {
  loading.value = true;
  error.value = '';
  try {
    const response = await dashboardService.getStats();
    stats.value = response.data || {};
  } catch (e) {
    error.value = 'Não foi possível carregar o painel. Tente novamente.';
  } finally {
    loading.value = false;
  }
};

const formatDate = (dateString) => {
  if (!dateString) return '';
  return new Date(dateString).toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit' });
};

const formatTime = (dateString) => {
  if (!dateString) return '';
  return new Date(dateString).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
};

const approveInscricao = async (id) => {
  const ok = await confirm.ask('Aprovar esta inscrição de equipe?', 'Aprovar inscrição');
  if (!ok) return;
  try {
    await managerService.updateTeamStatus(id, 'aprovada');
    toast.success('Inscrição aprovada.');
    await loadStats();
  } catch (e) {
    toast.error('Erro ao aprovar inscrição.');
  }
};

const rejectInscricao = async (id) => {
  const ok = await confirm.ask('Rejeitar esta inscrição de equipe?', 'Rejeitar inscrição');
  if (!ok) return;
  try {
    await managerService.updateTeamStatus(id, 'rejeitada');
    toast.success('Inscrição rejeitada.');
    await loadStats();
  } catch (e) {
    toast.error('Erro ao rejeitar inscrição.');
  }
};

onMounted(loadStats);
</script>
