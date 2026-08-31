<template>
  <div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200">
      <thead class="bg-gray-50">
        <tr>
          <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
            #
          </th>
          <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
            {{ type === 'equipe' ? 'Equipe' : 'Atleta' }}
          </th>
          <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
            Pontos
          </th>
          <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
            Títulos
          </th>
          <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
            Participações
          </th>
        </tr>
      </thead>
      <tbody class="bg-white divide-y divide-gray-200">
        <tr v-for="(item, index) in rankingData" :key="index" :class="getRowClass(index)">
          <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
            {{ index + 1 }}
            <span v-if="index === 0" class="ml-2">🥇</span>
            <span v-if="index === 1" class="ml-2">🥈</span>
            <span v-if="index === 2" class="ml-2">🥉</span>
          </td>
          <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
            {{ type === 'equipe' ? item.equipe?.nome : item.atleta?.nome }}
          </td>
          <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 font-bold">
            {{ item.total_pontos || item.pontos }}
          </td>
          <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
            {{ item.total_titulos || item.titulos }}
          </td>
          <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
            {{ item.total_participacoes || item.participacoes }}
          </td>
        </tr>
        <tr v-if="rankingData.length === 0">
          <td colspan="5" class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-500">
            Nenhum registro encontrado.
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>
defineProps({
  rankingData: {
    type: Array,
    required: true,
    default: () => []
  },
  type: {
    type: String,
    default: 'atleta'
  }
});

const getRowClass = (index) => {
  if (index === 0) return 'bg-yellow-50';
  if (index === 1) return 'bg-gray-50';
  if (index === 2) return 'bg-orange-50';
  return '';
};
</script>
