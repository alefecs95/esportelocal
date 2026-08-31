<template>
  <div>
    <BaseTable :headers="headers" :items="items" actions>
      <template #status="{ item }">
        <span
          :class="[
            'px-2 inline-flex text-xs leading-5 font-semibold rounded-full',
            item.status ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
          ]"
        >
          {{ item.status ? 'Ativo' : 'Inativo' }}
        </span>
      </template>
      <template #actions="{ item }">
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
  { key: 'tipo', label: 'Tipo' },
  { key: 'descricao', label: 'Descrição' },
  { key: 'status', label: 'Status' },
];
</script>
