<template>
  <div>
    <BaseTable :headers="headers" :items="items" actions>
      <template #modalidade="{ item }">
        {{ item.modalidade?.nome || '-' }}
      </template>
      
      <template #data_inicio="{ item }">
        {{ formatDate(item.data_inicio) }}
      </template>
      
      <template #data_fim="{ item }">
        {{ formatDate(item.data_fim) }}
      </template>
      
      <template #status="{ item }">
        <span
          :class="[
            'px-2 inline-flex text-xs leading-5 font-semibold rounded-full',
            getStatusClass(item.status)
          ]"
        >
          {{ formatStatus(item.status) }}
        </span>
      </template>
      
      <template #actions="{ item }">
        <BaseButton 
          variant="secondary" 
          size="sm" 
          class="mr-2" 
          @click="$router.push(`/admin/competicoes/${item.id}/participantes`)"
        >
          Participantes
        </BaseButton>
        <div v-if="item.tipo_competicao === 'equipe'" class="inline-block mr-2">
           <BaseButton 
            variant="primary" 
            size="sm" 
            @click="$router.push(`/admin/competicoes/${item.id}/equipes`)"
           >
            Equipes
           </BaseButton>
        </div>
        <BaseButton 
          variant="secondary" 
          size="sm" 
          class="mr-2" 
          @click="$router.push(`/admin/competicoes/${item.id}/tabela`)"
        >
          Tabela
        </BaseButton>
        <BaseButton
          v-if="item.tipo_competicao === 'individual' || item.inscricao_solidaria"
          variant="primary"
          size="sm"
          class="mr-2"
          @click="$router.push(`/admin/competicoes/${item.id}/corrida/inscritos`)"
        >
          Corrida
        </BaseButton>
        <BaseButton variant="secondary" size="sm" class="mr-2" @click="$emit('edit', item)">
          Editar
        </BaseButton>
        <BaseButton variant="danger" size="sm" @click="$emit('delete', item)">
          Excluir
        </BaseButton>
      </template>
    </BaseTable>
    
    <div v-if="pagination.last_page > 1" class="mt-4 flex justify-between items-center">
      <span class="text-sm text-gray-700">
        Página {{ pagination.current_page }} de {{ pagination.last_page }}
      </span>
      <div class="space-x-2">
        <BaseButton
          variant="secondary"
          :disabled="pagination.current_page === 1"
          @click="$emit('page-change', pagination.current_page - 1)"
        >
          Anterior
        </BaseButton>
        <BaseButton
          variant="secondary"
          :disabled="pagination.current_page === pagination.last_page"
          @click="$emit('page-change', pagination.current_page + 1)"
        >
          Próxima
        </BaseButton>
      </div>
    </div>
  </div>
</template>

<script setup>
import BaseTable from '@/components/ui/BaseTable.vue';
import BaseButton from '@/components/ui/BaseButton.vue';

defineProps({
  items: {
    type: Array,
    required: true,
  },
  pagination: {
    type: Object,
    required: true,
  },
});

defineEmits(['edit', 'delete', 'page-change']);

const headers = [
  { key: 'nome', label: 'Nome' },
  { key: 'local', label: 'Local' },
  { key: 'modalidade', label: 'Modalidade' },
  { key: 'categoria', label: 'Categoria' },
  { key: 'data_inicio', label: 'Início' },
  { key: 'data_fim', label: 'Fim' },
  { key: 'status', label: 'Status' },
];

const formatDate = (dateString) => {
  if (!dateString) return '-';
  const date = new Date(dateString);
  return date.toLocaleDateString('pt-BR');
};

const formatStatus = (status) => {
  const map = {
    draft: 'Rascunho',
    inscricoes_abertas: 'Inscrições Abertas',
    inscricoes_encerradas: 'Inscrições Encerradas',
    em_andamento: 'Em Andamento',
    finalizado: 'Finalizado',
  };
  return map[status] || status;
};

const getStatusClass = (status) => {
  const map = {
    draft: 'bg-gray-100 text-gray-800',
    inscricoes_abertas: 'bg-green-100 text-green-800',
    inscricoes_encerradas: 'bg-yellow-100 text-yellow-800',
    em_andamento: 'bg-blue-100 text-blue-800',
    finalizado: 'bg-purple-100 text-purple-800',
  };
  return map[status] || 'bg-gray-100 text-gray-800';
};
</script>
