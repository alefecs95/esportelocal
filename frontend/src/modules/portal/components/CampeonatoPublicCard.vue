<template>
  <div class="bg-white rounded-lg shadow-sm overflow-hidden border border-gray-100 hover:shadow-md transition-shadow">
    <div class="h-32 bg-gradient-to-r from-blue-600 to-blue-800 flex items-center justify-center text-white p-4 text-center">
      <h3 class="text-xl font-bold line-clamp-2">{{ campeonato.nome }}</h3>
    </div>
    <div class="p-4">
      <div class="flex justify-between items-center mb-3">
        <span class="text-xs font-semibold px-2 py-1 rounded bg-blue-50 text-blue-700 uppercase">
          {{ campeonato.modalidade?.nome || 'Modalidade' }}
        </span>
        <span 
          class="text-xs font-semibold px-2 py-1 rounded uppercase"
          :class="{
            'bg-green-100 text-green-700': campeonato.status === 'em_andamento',
            'bg-gray-100 text-gray-700': campeonato.status === 'finalizado',
            'bg-yellow-100 text-yellow-700': campeonato.status === 'inscricoes_abertas',
            'bg-purple-100 text-purple-700': campeonato.status === 'draft'
          }"
        >
          {{ formatStatus(campeonato.status) }}
        </span>
      </div>
      
      <div class="space-y-2 text-sm text-gray-600 mb-4">
        <p class="flex items-center gap-2">
          <Calendar :size="16" />
          {{ formatDate(campeonato.data_inicio) }} a {{ formatDate(campeonato.data_fim) }}
        </p>
        <p class="flex items-center gap-2">
          <MapPin :size="16" />
          {{ campeonato.cidade_eleitoral || 'Local a definir' }}
        </p>
      </div>

      <router-link 
        :to="{ name: 'PortalCampeonato', params: { id: campeonato.id } }"
        class="block w-full text-center bg-gray-50 hover:bg-gray-100 text-blue-600 font-medium py-2 rounded-lg border border-gray-200 transition-colors"
      >
        Ver Tabela e Jogos
      </router-link>
    </div>
  </div>
</template>

<script setup>
import { Calendar, MapPin } from 'lucide-vue-next';

defineProps({
  campeonato: {
    type: Object,
    required: true
  }
});

const formatStatus = (status) => {
  const map = {
    draft: 'Em Breve',
    inscricoes_abertas: 'Inscrições Abertas',
    inscricoes_encerradas: 'Inscrições Encerradas',
    em_andamento: 'Em Andamento',
    finalizado: 'Finalizado',
  };
  return map[status] || status;
};

const formatDate = (dateString) => {
  if (!dateString) return '--/--';
  return new Date(dateString).toLocaleDateString('pt-BR');
};
</script>
