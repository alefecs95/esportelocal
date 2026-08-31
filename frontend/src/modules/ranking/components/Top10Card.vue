<template>
  <div class="bg-white overflow-hidden shadow rounded-lg">
    <div class="px-4 py-5 sm:px-6 flex justify-between items-center">
      <h3 class="text-lg leading-6 font-medium text-gray-900">
        Top 10 {{ type === 'equipe' ? 'Equipes' : 'Atletas' }}
      </h3>
      <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
        {{ temporadaLabel }}
      </span>
    </div>
    <div class="px-4 py-5 sm:p-0">
      <ul class="divide-y divide-gray-200">
        <li v-for="(item, index) in rankingData" :key="index" class="px-4 py-4 sm:px-6 hover:bg-gray-50">
          <div class="flex items-center justify-between">
            <div class="flex items-center">
              <span class="flex-shrink-0 h-8 w-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-bold">
                {{ index + 1 }}
              </span>
              <div class="ml-4">
                <div class="text-sm font-medium text-indigo-600 truncate">
                  {{ type === 'equipe' ? item.equipe?.nome : item.atleta?.nome }}
                </div>
                <div class="flex items-center text-sm text-gray-500">
                  <span class="mr-2">🏆 {{ item.total_titulos || item.titulos }} Títulos</span>
                  <span>📍 {{ item.total_participacoes || item.participacoes }} Part.</span>
                </div>
              </div>
            </div>
            <div class="flex flex-col items-end">
              <div class="text-lg font-bold text-gray-900">
                {{ item.total_pontos || item.pontos }} pts
              </div>
            </div>
          </div>
        </li>
        <li v-if="rankingData.length === 0" class="px-4 py-4 sm:px-6 text-center text-gray-500">
          Sem dados para exibir.
        </li>
      </ul>
    </div>
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
  },
  temporadaLabel: {
    type: String,
    default: 'Temporada Atual'
  }
});
</script>
