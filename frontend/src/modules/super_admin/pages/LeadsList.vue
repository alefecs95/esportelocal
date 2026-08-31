<template>
  <div class="space-y-6">
    <div class="flex justify-between items-center">
      <h1 class="text-2xl font-bold text-gray-900">Leads e Contatos</h1>
    </div>

    <BaseCard>
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Data
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Contato
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Cidade/Prefeitura
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Status
              </th>
              <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                Ações
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-if="loading">
              <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">
                Carregando leads...
              </td>
            </tr>
            <tr v-else-if="leads.length === 0">
              <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">
                Nenhum lead encontrado.
              </td>
            </tr>
            <tr v-for="lead in leads" :key="lead.id">
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ formatDate(lead.created_at) }}
              </td>
              <td class="px-6 py-4">
                <div class="text-sm font-medium text-gray-900">{{ lead.nome }}</div>
                <div class="text-sm text-gray-500">{{ lead.email }}</div>
                <div class="text-sm text-gray-500">{{ lead.telefone }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-medium">
                {{ lead.cidade }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <select 
                  v-model="lead.status" 
                  @change="updateStatus(lead)"
                  class="text-sm rounded-full px-3 py-1 font-semibold border-0 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-blue-600 cursor-pointer"
                  :class="{
                    'bg-blue-50 text-blue-700': lead.status === 'NOVO',
                    'bg-yellow-50 text-yellow-700': lead.status === 'EM_ATENDIMENTO',
                    'bg-green-50 text-green-700': lead.status === 'FECHADO',
                    'bg-red-50 text-red-700': lead.status === 'PERDIDO'
                  }"
                >
                  <option value="NOVO" class="bg-white text-gray-900">Novo</option>
                  <option value="EM_ATENDIMENTO" class="bg-white text-gray-900">Em Atendimento</option>
                  <option value="FECHADO" class="bg-white text-gray-900">Fechado (Ganho)</option>
                  <option value="PERDIDO" class="bg-white text-gray-900">Perdido</option>
                </select>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <button @click="deleteLead(lead.id)" class="text-red-600 hover:text-red-900 transition-colors" title="Excluir">
                  <Trash2 :size="18" />
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Paginação básica -->
      <div v-if="totalPages > 1" class="px-6 py-4 border-t border-gray-200 flex items-center justify-between">
        <button 
          @click="loadLeads(currentPage - 1)" 
          :disabled="currentPage === 1"
          class="px-3 py-1 border rounded text-sm disabled:opacity-50"
        >
          Anterior
        </button>
        <span class="text-sm text-gray-600">Página {{ currentPage }} de {{ totalPages }}</span>
        <button 
          @click="loadLeads(currentPage + 1)" 
          :disabled="currentPage === totalPages"
          class="px-3 py-1 border rounded text-sm disabled:opacity-50"
        >
          Próxima
        </button>
      </div>
    </BaseCard>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { Trash2 } from 'lucide-vue-next';
import BaseCard from '../../../components/ui/BaseCard.vue';
import api from '../../../services/api';

const leads = ref([]);
const loading = ref(true);
const currentPage = ref(1);
const totalPages = ref(1);

const formatDate = (dateString) => {
  if (!dateString) return '-';
  const date = new Date(dateString);
  return date.toLocaleDateString('pt-BR') + ' ' + date.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
};

const loadLeads = async (page = 1) => {
  loading.value = true;
  try {
    const { data } = await api.get('/admin/leads', { params: { page } });
    leads.value = data.data;
    currentPage.value = data.current_page;
    totalPages.value = data.last_page;
  } catch (error) {
    console.error('Erro ao carregar leads:', error);
  } finally {
    loading.value = false;
  }
};

const updateStatus = async (lead) => {
  try {
    await api.put(`/admin/leads/${lead.id}`, { status: lead.status });
  } catch (error) {
    console.error('Erro ao atualizar status:', error);
    alert('Erro ao atualizar status.');
    loadLeads(currentPage.value); // recarrega para voltar ao status anterior
  }
};

const deleteLead = async (id) => {
  if (!confirm('Tem certeza que deseja excluir este lead?')) return;
  
  try {
    await api.delete(`/admin/leads/${id}`);
    loadLeads(currentPage.value);
  } catch (error) {
    console.error('Erro ao excluir lead:', error);
    alert('Erro ao excluir lead.');
  }
};

onMounted(() => {
  loadLeads();
});
</script>
