<template>
  <div class="overflow-x-auto">
    <table class="min-w-full bg-white border border-gray-200">
      <thead>
        <tr>
          <th class="px-6 py-3 border-b border-gray-200 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome</th>
          <th class="px-6 py-3 border-b border-gray-200 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ano</th>
          <th class="px-6 py-3 border-b border-gray-200 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
          <th class="px-6 py-3 border-b border-gray-200 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="temporada in temporadas" :key="temporada.id" class="hover:bg-gray-50">
          <td class="px-6 py-4 whitespace-nowrap border-b border-gray-200">{{ temporada.nome }}</td>
          <td class="px-6 py-4 whitespace-nowrap border-b border-gray-200">{{ temporada.ano }}</td>
          <td class="px-6 py-4 whitespace-nowrap border-b border-gray-200">
            <span :class="getStatusClass(temporada.status)">
              {{ temporada.status === 'ativa' ? 'Ativa' : 'Encerrada' }}
            </span>
          </td>
          <td class="px-6 py-4 whitespace-nowrap border-b border-gray-200">
            <button 
              v-if="temporada.status !== 'ativa'"
              @click="$emit('ativar', temporada)" 
              class="text-green-600 hover:text-green-900 mr-3"
              title="Ativar Temporada"
            >
              Ativar
            </button>
            <button 
              @click="$emit('delete', temporada)" 
              class="text-red-600 hover:text-red-900"
              title="Excluir Temporada"
            >
              Excluir
            </button>
          </td>
        </tr>
        <tr v-if="temporadas.length === 0">
          <td colspan="4" class="px-6 py-4 text-center text-gray-500">Nenhuma temporada encontrada.</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>
defineProps({
  temporadas: {
    type: Array,
    required: true
  }
});

defineEmits(['ativar', 'delete']);

const getStatusClass = (status) => {
  if (status === 'ativa') {
    return 'px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800';
  }
  return 'px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800';
};
</script>
