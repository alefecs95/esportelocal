<template>
  <div class="bg-white p-6 rounded-lg shadow-md">
    <h2 class="text-xl font-bold mb-4">Nova Inscrição</h2>
    
    <div v-if="error" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
      {{ error }}
    </div>

    <form @submit.prevent="submit">
      <!-- Campeonato -->
      <div class="mb-6">
        <label class="block text-sm font-medium text-gray-700 mb-1">Competição</label>
        <select 
          v-model="form.campeonato_id" 
          @change="onCampeonatoChange"
          class="block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500"
          required
        >
          <option value="" disabled>Selecione uma competição</option>
          <option v-for="camp in campeonatos" :key="camp.id" :value="camp.id">
            {{ camp.nome }}
          </option>
        </select>
        
        <div v-if="selectedCampeonato" class="mt-2 text-sm text-gray-600 bg-gray-50 p-3 rounded border border-gray-200">
           <p><strong>Modalidade:</strong> {{ selectedCampeonato.modalidade?.nome }}</p>
           <p><strong>Local:</strong> {{ selectedCampeonato.local || 'Não informado' }}</p>
           <p><strong>Status:</strong> 
              <span :class="{
                  'text-green-600': selectedCampeonato.status === 'inscricoes_abertas',
                  'text-red-600': ['inscricoes_encerradas', 'finalizado'].includes(selectedCampeonato.status),
                  'text-yellow-600': selectedCampeonato.status === 'draft'
              }" class="font-bold uppercase">{{ selectedCampeonato.status }}</span>
           </p>
           <p v-if="selectedCampeonato.idade_min || selectedCampeonato.idade_max">
             <strong>Idade Permitida:</strong> {{ selectedCampeonato.idade_min || 0 }} a {{ selectedCampeonato.idade_max || 99 }} anos
           </p>
           
           <div v-if="selectedCampeonato.status !== 'inscricoes_abertas'" class="mt-2 bg-red-100 text-red-700 p-2 rounded text-center font-bold">
               Inscrições encerradas para esta competição.
           </div>
        </div>
      </div>

      <!-- Atleta -->
      <div class="mb-6" v-if="selectedCampeonato && selectedCampeonato.status === 'inscricoes_abertas'">
        <label class="block text-sm font-medium text-gray-700 mb-1">Atleta</label>
        
        <div v-if="!form.campeonato_id" class="text-gray-500 text-sm italic p-2 border border-dashed rounded bg-gray-50">
           Selecione uma competição primeiro para ver os atletas elegíveis.
        </div>
        
        <div v-else>
            <div class="flex gap-2 mb-2">
                <input 
                    type="text" 
                    v-model="searchAtleta" 
                    placeholder="Buscar atleta por nome..." 
                    class="block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500"
                >
            </div>
            
            <div v-if="loadingAtletas" class="text-center py-4 text-gray-500">
               Carregando atletas elegíveis...
            </div>
            
            <div v-else-if="atletasElegiveis.length === 0" class="text-red-600 bg-red-50 p-3 rounded border border-red-200 text-sm">
               Nenhum atleta elegível encontrado para esta competição. Verifique os critérios de idade e domicílio eleitoral.
            </div>

            <select 
              v-else
              v-model="form.atleta_id" 
              class="block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500"
              required
            >
              <option value="" disabled>Selecione um atleta</option>
              <option v-for="atleta in atletasFiltrados" :key="atleta.id" :value="atleta.id">
                {{ atleta.nome }} ({{ atleta.idade }} anos)
              </option>
            </select>
            
            <p v-if="atletasElegiveis.length > 0" class="text-xs text-gray-500 mt-1">
                {{ atletasFiltrados.length }} atletas encontrados.
            </p>
            
            <!-- Seleção de Categoria (se houver múltiplas disponíveis para o atleta) -->
            <div v-if="categoriasDisponiveis.length > 0" class="mt-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Categoria</label>
                <select 
                  v-model="form.categoria_id" 
                  class="block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500"
                  required
                >
                   <option value="" disabled>Selecione a categoria</option>
                   <option v-for="cat in categoriasDisponiveis" :key="cat.id" :value="cat.id">
                     {{ cat.nome }} ({{ cat.idade_min || 0 }}-{{ cat.idade_max || 99 }} anos)
                   </option>
                </select>
            </div>
             <div v-else-if="form.atleta_id && categoriasDisponiveis.length === 0 && selectedCampeonato?.categorias?.length > 0" class="mt-2 text-red-600 text-sm">
                Este atleta não se encaixa em nenhuma categoria específica (idade/peso/gênero).
            </div>
        </div>
      </div>

      <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
        <button 
          type="button" 
          @click="$router.back()" 
          class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
        >
          Cancelar
        </button>
        <button 
          type="submit" 
          :disabled="loading || (categoriasDisponiveis.length === 0 && selectedCampeonato?.categorias?.length > 0)"
          class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          <span v-if="loading">Salvando...</span>
          <span v-else>Realizar Inscrição</span>
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, reactive, watch } from 'vue';
import { useRouter } from 'vue-router';
import { useInscricaoStore } from '../store/inscricaoStore';
import { useCampeonatoStore } from '@/modules/campeonatos/store/campeonatoStore';
import api from '@/services/api'; // Import direto para chamada customizada

const router = useRouter();
const inscricaoStore = useInscricaoStore();
const campeonatoStore = useCampeonatoStore();

const form = reactive({
  campeonato_id: '',
  atleta_id: '',
  categoria_id: '' // Adicionado para suportar categorias específicas
});

const searchAtleta = ref('');
const loading = computed(() => inscricaoStore.loading);
const error = computed(() => inscricaoStore.error);
const campeonatos = computed(() => campeonatoStore.campeonatos);

const atletasElegiveis = ref([]);
const loadingAtletas = ref(false);

const selectedCampeonato = computed(() => {
    return campeonatos.value.find(c => String(c.id) === String(form.campeonato_id));
});

onMounted(async () => {
  await campeonatoStore.fetchCampeonatos(); 
});

const onCampeonatoChange = async () => {
    form.atleta_id = '';
    form.categoria_id = '';
    atletasElegiveis.value = [];
    
    if (!form.campeonato_id) return;

    loadingAtletas.value = true;
    try {
        const camp = selectedCampeonato.value;
        // Chama endpoint de simulação com flag return_list=true
        const response = await api.post('/competicoes/simular-elegibilidade', {
            idade_min: camp.idade_min,
            idade_max: camp.idade_max,
            cidade_eleitoral: camp.cidade_eleitoral,
            bairro_eleitoral: camp.bairro_eleitoral,
            genero: camp.genero,
            return_list: true
        });
        
        if (response.data.success) {
            atletasElegiveis.value = response.data.data.atletas;
        }
    } catch (e) {
        console.error("Erro ao buscar atletas elegíveis", e);
    } finally {
        loadingAtletas.value = false;
    }
};

const atletasFiltrados = computed(() => {
    let lista = atletasElegiveis.value;
    if (searchAtleta.value) {
        const termo = searchAtleta.value.toLowerCase();
        lista = lista.filter(a => a.nome.toLowerCase().includes(termo));
    }
    return lista;
});

// Lógica para determinar categorias disponíveis para o atleta selecionado
const categoriasDisponiveis = computed(() => {
    if (!form.atleta_id || !selectedCampeonato.value || !selectedCampeonato.value.categorias) return [];
    
    const atleta = atletasElegiveis.value.find(a => a.id === form.atleta_id);
    if (!atleta) return [];

    return selectedCampeonato.value.categorias.filter(cat => {
        // Filtra por idade
        if (cat.idade_min && atleta.idade < cat.idade_min) return false;
        if (cat.idade_max && atleta.idade > cat.idade_max) return false;
        
        // Filtra por gênero (se categoria tiver gênero específico)
        // Assumindo que atleta tem gênero no backend, se não tiver, ignora ou melhora a query
        // if (cat.genero && cat.genero !== 'Misto' && cat.genero !== atleta.genero) return false;

        return true;
    });
});

// Auto-selecionar categoria se houver apenas uma compatível
watch(categoriasDisponiveis, (cats) => {
    if (cats.length === 1) {
        form.categoria_id = cats[0].id;
    } else {
        form.categoria_id = '';
    }
});

const submit = async () => {
  try {
    await inscricaoStore.createInscricao(form);
    router.push('/admin/inscricoes'); 
  } catch (err) {
    // Erro tratado no store
  }
};
</script>
