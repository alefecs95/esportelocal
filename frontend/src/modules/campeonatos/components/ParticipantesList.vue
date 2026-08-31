<template>
  <div class="container mx-auto px-4 py-8">
    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
      <div>
        <h1 class="text-2xl font-bold text-gray-800">Participantes</h1>
        <p class="text-gray-600">Gestão de atletas inscritos por categoria</p>
      </div>
      <div class="flex gap-2">
        <BaseButton variant="secondary" @click="$router.push('/admin/competicoes')">
          Voltar
        </BaseButton>
        <BaseButton variant="primary" @click="exportarPDF">
          Exportar PDF
        </BaseButton>
      </div>
    </div>

    <!-- Header com Estatísticas -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
      <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-100">
        <h3 class="text-sm font-medium text-gray-500">Total de Inscritos</h3>
        <p class="text-2xl font-bold text-blue-600">{{ inscricoes.length }}</p>
      </div>
      <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-100">
        <h3 class="text-sm font-medium text-gray-500">Confirmados</h3>
        <p class="text-2xl font-bold text-green-600">{{ inscricoes.filter(i => i.status === 'confirmada').length }}</p>
      </div>
      <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-100">
        <h3 class="text-sm font-medium text-gray-500">Pendentes</h3>
        <p class="text-2xl font-bold text-yellow-600">{{ inscricoes.filter(i => i.status === 'pendente').length }}</p>
      </div>
    </div>

    <!-- Lista Agrupada por Categoria -->
    <div v-if="loading" class="text-center py-8 text-gray-500">
      Carregando participantes...
    </div>

    <div v-else-if="Object.keys(inscricoesPorCategoria).length === 0" class="text-center py-12 bg-white rounded-lg border border-dashed border-gray-300">
      <p class="text-gray-500 mb-2">Nenhum participante inscrito ainda.</p>
    </div>

    <div v-else class="space-y-6">
      <div v-for="(lista, categoriaNome) in inscricoesPorCategoria" :key="categoriaNome" class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="bg-gray-50 px-4 py-3 border-b border-gray-200 flex justify-between items-center">
          <h3 class="font-bold text-gray-800 flex items-center gap-2">
            <span class="bg-blue-100 text-blue-800 text-xs px-2 py-0.5 rounded-full uppercase">Categoria</span>
            {{ categoriaNome }}
            <span v-if="getCategoriaDetalhes(lista)" class="text-sm font-normal text-gray-500 ml-2">
               ({{ getCategoriaDetalhes(lista) }})
            </span>
          </h3>
          <span class="text-sm text-gray-500">{{ lista.length }} atleta(s)</span>
        </div>
        
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Atleta</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Idade</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pagamento</th>
                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="inscricao in lista" :key="inscricao.id" class="hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="flex items-center">
                    <div class="text-sm font-medium text-gray-900">{{ inscricao.atleta?.nome || 'Atleta Removido' }}</div>
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span class="text-sm text-gray-500">
                    {{ calcularIdade(inscricao.atleta?.data_nascimento) }} anos
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span :class="getStatusClass(inscricao.status)" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
                    {{ formatStatus(inscricao.status) }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                   <span :class="getPagamentoClass(inscricao.status_pagamento)" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
                    {{ formatPagamento(inscricao.status_pagamento) }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                  <button v-if="inscricao.status !== 'cancelada'" @click="cancelarInscricao(inscricao)" class="text-red-600 hover:text-red-900 font-medium">
                    Cancelar
                  </button>
                  <span v-else class="text-gray-400 italic">Cancelado</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '@/services/api';
import BaseButton from '@/components/ui/BaseButton.vue';

const route = useRoute();
const router = useRouter();
const inscricoes = ref([]);
const campeonato = ref(null);
const loading = ref(true);

const fetchCampeonato = async () => {
  try {
    const response = await api.get(`/campeonatos/${route.params.id}`);
    if (response.data.success) {
      campeonato.value = response.data.data;
    }
  } catch (error) {
    console.error('Erro ao buscar campeonato:', error);
  }
};

const fetchInscricoes = async () => {
  loading.value = true;
  try {
    const response = await api.get(`/campeonatos/${route.params.id}/inscricoes`);
    if (response.data.success) {
      inscricoes.value = response.data.data;
    }
  } catch (error) {
    console.error('Erro ao buscar inscritos:', error);
    // Opcional: mostrar toast de erro
  } finally {
    loading.value = false;
  }
};

const isModalidade = (term) => {
    if (!campeonato.value?.modalidade?.nome) return false;
    const nome = campeonato.value.modalidade.nome.toLowerCase();
    return nome.includes(term.toLowerCase());
};

const getCategoriaDetalhes = (lista) => {
    if (!lista || lista.length === 0) return '';
    const cat = lista[0].categoria;
    if (!cat) return '';

    const detalhes = [];
    
    // Regras por Modalidade
    
    // Corrida / Atletismo
    if (isModalidade('corrida') || isModalidade('atletismo') || isModalidade('ciclismo')) {
        if (cat.distancia) {
            const valor = String(cat.distancia).trim();
            // Remove qualquer texto que não seja numero ou ponto
            const apenasNumeros = valor.replace(/[^0-9.]/g, '');
            
            // Verifica se o valor original era puramente numérico ou se continha unidade
            const temUnidadeExplícita = /[a-zA-Z]/.test(valor);

            if (!temUnidadeExplícita && apenasNumeros) {
                const dist = parseFloat(apenasNumeros);
                if (dist < 1000) {
                    detalhes.push(`${dist}m`);
                } else {
                    // Tenta converter para km sem decimais desnecessários (ex: 5000 -> 5km, 1500 -> 1.5km)
                    const km = parseFloat((dist / 1000).toFixed(2)); // Remove zeros desnecessários
                    detalhes.push(`${km}km`);
                }
            } else {
                // Já tem texto/unidade ou formato complexo, exibe como está
                detalhes.push(valor);
            }
        }
    }
    
    // Lutas (Judo, Jiu, etc)
    if (isModalidade('judo') || isModalidade('jiu') || isModalidade('boxe') || isModalidade('luta') || isModalidade('karate')) {
        if (cat.peso_min || cat.peso_max) {
            if (cat.peso_min && cat.peso_max) detalhes.push(`${cat.peso_min}kg a ${cat.peso_max}kg`);
            else if (cat.peso_min) detalhes.push(`> ${cat.peso_min}kg`);
            else if (cat.peso_max) detalhes.push(`< ${cat.peso_max}kg`);
        }
    }

    // Idade (Geral)
    if (cat.idade_min || cat.idade_max) {
        if (cat.idade_min && cat.idade_max) detalhes.push(`${cat.idade_min} a ${cat.idade_max} anos`);
        else if (cat.idade_min) detalhes.push(`Min. ${cat.idade_min} anos`);
        else if (cat.idade_max) detalhes.push(`Até ${cat.idade_max} anos`);
    }
    
    // Gênero
    if (cat.genero && cat.genero !== 'Misto') {
        detalhes.push(cat.genero);
    }

    return detalhes.join(' • ');
};

const inscricoesPorCategoria = computed(() => {
  const grupos = {};
  
  inscricoes.value.forEach(inscricao => {
    let catNome = 'Geral';
    
    // Prioriza a categoria vinculada na inscrição
    if (inscricao.categoria && inscricao.categoria.nome) {
        catNome = inscricao.categoria.nome;
    } 
    // Fallback: Se não tiver categoria vinculada (legado ou erro), tenta inferir ou agrupar em Geral
    
    if (!grupos[catNome]) {
      grupos[catNome] = [];
    }
    grupos[catNome].push(inscricao);
  });
  
  // Ordenar as chaves (nomes das categorias) se necessário
  return Object.keys(grupos).sort().reduce(
    (obj, key) => { 
      obj[key] = grupos[key]; 
      return obj;
    }, 
    {}
  );
});

const calcularIdade = (dataNascimento) => {
    if (!dataNascimento) return 'N/A';
    const hoje = new Date();
    const nasc = new Date(dataNascimento);
    let idade = hoje.getFullYear() - nasc.getFullYear();
    const m = hoje.getMonth() - nasc.getMonth();
    if (m < 0 || (m === 0 && hoje.getDate() < nasc.getDate())) {
        idade--;
    }
    return idade;
};

const getStatusClass = (status) => {
  const classes = {
    pendente: 'bg-yellow-100 text-yellow-800',
    confirmada: 'bg-green-100 text-green-800',
    cancelada: 'bg-red-100 text-red-800',
    rejeitada: 'bg-gray-100 text-gray-800'
  };
  return classes[status] || 'bg-gray-100 text-gray-800';
};

const formatStatus = (status) => {
    const map = {
        pendente: 'Pendente',
        confirmada: 'Confirmada',
        cancelada: 'Cancelada',
        rejeitada: 'Rejeitada'
    };
    return map[status] || status;
};

const getPagamentoClass = (status) => {
    const classes = {
    nao_pago: 'bg-red-100 text-red-800',
    pago: 'bg-green-100 text-green-800',
    isento: 'bg-blue-100 text-blue-800'
  };
  return classes[status] || 'bg-gray-100 text-gray-800';
}

const formatPagamento = (status) => {
    const map = {
        nao_pago: 'Não Pago',
        pago: 'Pago',
        isento: 'Isento'
    };
    return map[status] || status;
};

const cancelarInscricao = async (inscricao) => {
    if(!confirm(`Tem certeza que deseja cancelar a inscrição de ${inscricao.atleta?.nome}?`)) return;
    
    try {
        await api.put(`/inscricoes/${inscricao.id}`, { status: 'cancelada' });
        // Atualiza localmente para evitar reload completo
        const index = inscricoes.value.findIndex(i => i.id === inscricao.id);
        if (index !== -1) {
            inscricoes.value[index].status = 'cancelada';
        }
    } catch (e) {
        alert('Erro ao cancelar inscrição');
        console.error(e);
    }
}

const exportarPDF = () => {
    const url = `${import.meta.env.VITE_API_URL}/pdf/participantes/${route.params.id}`;
    window.open(url, '_blank');
};

onMounted(() => {
  fetchCampeonato();
  fetchInscricoes();
});
</script>