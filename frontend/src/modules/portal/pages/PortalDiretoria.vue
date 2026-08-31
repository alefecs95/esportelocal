<template>
  <div class="min-h-screen bg-gray-50 py-12">
    <div class="container mx-auto px-4">
      <div class="text-center mb-16">
        <h1 class="text-4xl font-black text-gray-900 mb-4 tracking-tight uppercase">Diretoria</h1>
        <div class="w-24 h-1 bg-yellow-400 mx-auto rounded-full mb-6"></div>
        <p class="text-gray-600 text-lg max-w-2xl mx-auto">
          Conheça a equipe responsável pela gestão esportiva do município.
        </p>
      </div>

      <div v-if="loading" class="flex justify-center py-20">
        <Loader2 :size="48" class="animate-spin text-blue-600" />
      </div>

      <div v-else-if="membros.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
        <div v-for="membro in membros" :key="membro.id" class="bg-white rounded-2xl shadow-lg overflow-hidden group hover:-translate-y-2 transition-transform duration-300">
          <div class="relative h-80 overflow-hidden">
            <img 
              :src="membro.foto_url || `https://ui-avatars.com/api/?name=${membro.nome}&background=0D47A1&color=fff&size=256`" 
              class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110"
              :alt="membro.nome"
            />
            <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent opacity-90"></div>
            
            <div class="absolute bottom-0 left-0 w-full p-6 text-white">
                <h3 class="text-xl font-bold mb-1 group-hover:text-yellow-400 transition-colors">{{ membro.nome }}</h3>
                <p class="text-blue-200 text-sm uppercase tracking-wider font-semibold">{{ membro.funcao }}</p>
            </div>
          </div>
        </div>
      </div>

      <div v-else class="text-center py-20 bg-white rounded-3xl shadow-sm border border-gray-100">
        <Users :size="64" class="mx-auto text-gray-300 mb-4" />
        <h3 class="text-xl font-bold text-gray-800 mb-2">Nenhum membro encontrado</h3>
        <p class="text-gray-500">A equipe diretiva ainda não foi cadastrada.</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { Loader2, Users } from 'lucide-vue-next';
import api from '@/services/api';

const membros = ref([]);
const loading = ref(true);

const fetchMembros = async () => {
  try {
    const { data } = await api.get('/public/diretoria');
    membros.value = data;
  } catch (error) {
    console.error('Erro ao carregar diretoria:', error);
  } finally {
    loading.value = false;
  }
};

onMounted(fetchMembros);
</script>
