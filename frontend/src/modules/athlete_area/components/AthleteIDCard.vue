<template>
  <div id="athlete-card" class="relative w-[350px] h-[550px] bg-white rounded-xl overflow-hidden font-sans select-none border border-gray-300 print:shadow-none">
    
    <!-- Background Texture / Pattern (Simplificado para canvas) -->
    <div class="absolute inset-0 z-0 opacity-10" 
         :style="{
            backgroundColor: tenantStore.primaryColor || '#2563EB',
            backgroundImage: 'radial-gradient(circle, #000000 1px, transparent 1px)',
            backgroundSize: '20px 20px'
         }">
    </div>

    <!-- Marca d'água -->
    <div class="absolute inset-0 z-0 flex items-center justify-center opacity-[0.03] pointer-events-none">
       <img v-if="tenantStore.logo" :src="tenantStore.logo" class="w-64 h-64 object-contain grayscale" />
    </div>

    <!-- Header / Topo -->
    <div class="relative z-10 h-24 bg-gradient-to-r from-gray-900 to-gray-800 flex items-center justify-center px-6 shadow-md">
      <div class="h-16 w-16 bg-white rounded-full p-1 flex items-center justify-center shadow-lg transform hover:scale-105 transition-transform duration-300">
        <img v-if="tenantStore.logo" :src="tenantStore.logo" class="h-full w-full object-contain" alt="Logo" />
        <span v-else class="font-bold text-xs text-gray-800">SEMEL</span>
      </div>
    </div>

    <!-- Corpo Principal -->
    <div class="relative z-10 flex flex-col items-center pt-8 px-6 space-y-4">
      
      <!-- Foto do Atleta -->
      <div class="relative group">
        <div class="w-36 h-36 rounded-full border-4 border-white shadow-xl overflow-hidden bg-gray-100 flex items-center justify-center ring-4 ring-gray-100/50">
          <img v-if="athlete.foto_url" :src="athlete.foto_url" class="w-full h-full object-cover" alt="Foto" />
          <User v-else :size="64" class="text-gray-300" />
        </div>
      </div>

      <!-- Nome e Função -->
      <div class="text-center w-full">
        <h1 class="text-2xl font-black text-gray-900 uppercase leading-none tracking-tight truncate px-2 mb-1">{{ athlete.nome }}</h1>
        <div class="inline-block bg-blue-600 text-white text-xs font-bold px-3 py-1 rounded-full uppercase tracking-wider shadow-sm">
          {{ tenantStore.tenant?.nome || 'PREFEITURA MUNICIPAL' }}
        </div>
      </div>

      <!-- Detalhes em Grid -->
      <div class="w-full bg-gray-50/80 backdrop-blur-sm rounded-xl border border-gray-200 p-4 space-y-3 shadow-inner text-left">
        <div class="grid grid-cols-2 gap-3">
          <div class="flex flex-col">
            <span class="text-[9px] uppercase text-gray-400 font-bold">Nascimento</span>
            <span class="text-gray-800 font-semibold text-sm">{{ formatDate(athlete.data_nascimento) }}</span>
          </div>
          <div class="flex flex-col">
             <span class="text-[9px] uppercase text-gray-400 font-bold">CPF</span>
             <span class="text-gray-800 font-semibold text-sm font-mono tracking-tighter">{{ formatCPF(athlete.cpf) }}</span>
          </div>
        </div>

        <div class="flex flex-col border-t border-gray-200 pt-2">
          <span class="text-[9px] uppercase text-gray-400 font-bold">Filiação (Mãe)</span>
          <span class="text-gray-800 font-semibold text-sm truncate" :title="athlete.nome_mae">{{ athlete.nome_mae }}</span>
        </div>
      </div>
    </div>

    <!-- Rodapé / Validação -->
    <div class="absolute bottom-0 w-full bg-gray-50 border-t border-gray-200 py-3 px-6 flex items-center justify-center">
      <div class="flex flex-col items-center">
         <span class="text-[8px] uppercase text-gray-400 font-bold">CÓDIGO DE IDENTIFICAÇÃO</span>
         <span class="font-mono text-lg font-bold text-gray-700 tracking-widest">#{{ String(athlete.id).padStart(6, '0') }}</span>
      </div>
    </div>

    <!-- Faixa Decorativa Lateral -->
    <div class="absolute top-24 bottom-14 left-0 w-1.5 bg-gradient-to-b from-blue-500 via-blue-400 to-blue-600"></div>
  </div>
</template>

<script setup>
import { useTenantStore } from '../../../stores/tenant';
import { User } from 'lucide-vue-next';

const props = defineProps({
  athlete: {
    type: Object,
    required: true
  }
});

const tenantStore = useTenantStore();

const formatDate = (dateString) => {
  if (!dateString) return '---';
  return new Date(dateString).toLocaleDateString('pt-BR');
};

const formatCPF = (cpf) => {
  if (!cpf) return '---';
  return cpf.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
};
</script>

<style scoped>
/* Fonte mono para números */
.font-mono {
  font-family: 'Courier New', Courier, monospace;
}
</style>
