<template>
  <div class="space-y-6">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
          <Calendar :size="28" class="text-blue-600" />
          Calendário de Jogos
        </h1>
        <p class="text-gray-500 text-sm mt-1">Visualize e gerencie os jogos agendados</p>
      </div>
      
      <div class="flex gap-2">
        <div class="relative">
          <input 
            type="date" 
            v-model="filters.start" 
            class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
            @change="loadEvents"
          >
          <CalendarIcon :size="16" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
        </div>
        <span class="self-center text-gray-400">até</span>
        <div class="relative">
          <input 
            type="date" 
            v-model="filters.end" 
            class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
            @change="loadEvents"
          >
          <CalendarIcon :size="16" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
        </div>
      </div>
    </div>

    <div v-if="loading" class="flex justify-center py-12">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
    </div>

    <div v-else-if="!groupedEvents || Object.keys(groupedEvents).length === 0" class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center">
      <div class="bg-gray-50 inline-flex p-4 rounded-full mb-4">
        <CalendarOff :size="32" class="text-gray-400" />
      </div>
      <h3 class="text-lg font-medium text-gray-900">Nenhum jogo encontrado</h3>
      <p class="text-gray-500 mt-1">Tente ajustar os filtros de data para ver mais resultados.</p>
    </div>

    <div v-else class="space-y-8">
      <div v-for="(events, date) in groupedEvents" :key="date" class="space-y-3">
        <h3 class="flex items-center gap-2 text-lg font-semibold text-gray-800 sticky top-0 bg-gray-50/95 backdrop-blur py-2 z-10">
          <div class="w-2 h-2 rounded-full bg-blue-500"></div>
          {{ formatDateHeader(date) }}
          <span class="text-xs font-normal text-gray-500 bg-gray-200 px-2 py-0.5 rounded-full">{{ events.length }} jogos</span>
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <div v-for="event in events" :key="event.id" class="bg-white rounded-lg border border-gray-200 p-4 hover:shadow-md transition-shadow relative overflow-hidden group">
            <div class="absolute top-0 left-0 w-1 h-full" :class="getStatusColor(event.status)"></div>
            
            <div class="flex justify-between items-start mb-3 pl-3">
              <span class="text-xs font-semibold px-2 py-0.5 rounded" :class="getStatusBadge(event.status)">
                {{ getStatusLabel(event.status) }}
              </span>
              <span class="text-sm font-medium text-gray-600 flex items-center gap-1">
                <Clock :size="14" /> {{ formatTime(event.data_jogo) }}
              </span>
            </div>

            <div class="pl-3 space-y-3">
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2 flex-1">
                  <div class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center text-xs font-bold text-gray-600">
                    {{ getInitials(event.mandante?.nome) }}
                  </div>
                  <span class="font-medium text-gray-800 truncate">{{ event.mandante?.nome }}</span>
                </div>
                <div class="font-bold text-lg text-gray-800 px-2">{{ event.placar_mandante ?? '-' }}</div>
              </div>

              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2 flex-1">
                  <div class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center text-xs font-bold text-gray-600">
                    {{ getInitials(event.visitante?.nome) }}
                  </div>
                  <span class="font-medium text-gray-800 truncate">{{ event.visitante?.nome }}</span>
                </div>
                <div class="font-bold text-lg text-gray-800 px-2">{{ event.placar_visitante ?? '-' }}</div>
              </div>
            </div>

            <div class="mt-4 pt-3 border-t border-gray-100 pl-3 flex justify-between items-center text-xs text-gray-500">
              <div class="flex items-center gap-1 truncate max-w-[70%]">
                <MapPin :size="12" />
                <span class="truncate">{{ event.local || 'Local não definido' }}</span>
              </div>
              <router-link :to="`/partidas/${event.id}`" class="text-blue-600 hover:underline font-medium">
                Detalhes
              </router-link>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import dashboardService from '../services/dashboardService';
import { Calendar, Calendar as CalendarIcon, Clock, MapPin, CalendarOff } from 'lucide-vue-next';

const events = ref([]);
const loading = ref(true);
const filters = ref({
  start: new Date().toISOString().split('T')[0], // Today
  end: new Date(new Date().setDate(new Date().getDate() + 30)).toISOString().split('T')[0] // +30 days
});

const loadEvents = async () => {
  loading.value = true;
  try {
    const { data } = await dashboardService.getCalendarEvents(filters.value);
    events.value = data;
  } catch (e) {
    console.error('Erro ao carregar eventos', e);
  } finally {
    loading.value = false;
  }
};

const groupedEvents = computed(() => {
  const groups = {};
  events.value.forEach(event => {
    const date = event.data_jogo.split('T')[0]; // Assumes ISO format or YYYY-MM-DD
    if (!groups[date]) groups[date] = [];
    groups[date].push(event);
  });
  return groups;
});

const formatDateHeader = (dateString) => {
  const options = { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' };
  return new Date(dateString + 'T00:00:00').toLocaleDateString('pt-BR', options);
};

const formatTime = (dateString) => {
  return new Date(dateString).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
};

const getInitials = (name) => {
  if (!name) return '?';
  return name.substring(0, 2).toUpperCase();
};

const getStatusLabel = (status) => {
  const map = {
    'agendada': 'Agendada',
    'em_andamento': 'Em Andamento',
    'finalizada': 'Finalizada',
    'cancelada': 'Cancelada'
  };
  return map[status] || status;
};

const getStatusBadge = (status) => {
  const map = {
    'agendada': 'bg-blue-100 text-blue-700',
    'em_andamento': 'bg-green-100 text-green-700 animate-pulse',
    'finalizada': 'bg-gray-100 text-gray-700',
    'cancelada': 'bg-red-100 text-red-700'
  };
  return map[status] || 'bg-gray-100 text-gray-700';
};

const getStatusColor = (status) => {
  const map = {
    'agendada': 'bg-blue-500',
    'em_andamento': 'bg-green-500',
    'finalizada': 'bg-gray-400',
    'cancelada': 'bg-red-500'
  };
  return map[status] || 'bg-gray-300';
};

onMounted(() => {
  // Adjust start date to beginning of month if needed, but defaulting to today -> +30 days is practical for "Upcoming"
  loadEvents();
});
</script>