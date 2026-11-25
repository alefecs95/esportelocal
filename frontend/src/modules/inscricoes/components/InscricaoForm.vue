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
                <label class="block text-sm font-medium text-gray-700 mb-1">Prova / Categoria</label>
                <select 
                  v-model="form.categoria_id" 
                  class="block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500"
                  required
                >
                   <option value="" disabled>Selecione a categoria</option>
                   <option v-for="cat in categoriasDisponiveis" :key="cat.id" :value="cat.id">
                     {{ cat.nome }}<span v-if="cat.idade_min || cat.idade_max"> ({{ cat.idade_min || 0 }}-{{ cat.idade_max || 99 }} anos)</span>
                   </option>
                </select>
            </div>
             <div v-else-if="form.atleta_id && categoriasDisponiveis.length === 0 && categoriasDoCampeonato.length > 0" class="mt-2 text-red-600 text-sm">
                Este atleta não se encaixa em nenhuma categoria específica (idade/peso/gênero).
            </div>

            <!-- Campos específicos de inscrição solidária -->
            <div v-if="isSolidaria && form.atleta_id" class="mt-4 space-y-4 rounded-lg border border-emerald-200 bg-emerald-50/50 p-4">
              <div>
                <label class="block text-sm font-medium text-gray-800 mb-1">Forma de contribuição *</label>
                <select
                  v-model="form.forma_contribuicao"
                  class="block w-full border border-gray-300 rounded-md shadow-sm p-2 bg-white focus:ring-emerald-500 focus:border-emerald-500"
                  required
                >
                  <option value="" disabled>Escolha como contribuir</option>
                  <option v-for="opt in opcoesContribuicao" :key="opt.value" :value="opt.value">
                    {{ opt.label }}
                  </option>
                </select>
                <p class="text-xs text-emerald-800 mt-1">
                  Sua inscrição será confirmada quando a contribuição for entregue.
                </p>
              </div>

              <div v-if="atletaSelecionadoEhMenor" class="rounded-md bg-amber-50 border border-amber-200 p-3 space-y-2">
                <p class="text-xs font-semibold text-amber-800">
                  Menor de 18 anos na data do evento: o termo de autorização assinado pelo responsável deve ser entregue na retirada do kit.
                </p>
                <div>
                  <label class="block text-xs font-medium text-gray-700 mb-1">Nome do responsável *</label>
                  <input
                    v-model="form.responsavel_nome"
                    type="text"
                    required
                    class="block w-full border border-gray-300 rounded-md shadow-sm p-2 bg-white text-sm"
                    placeholder="Nome completo do pai, mãe ou responsável"
                  />
                </div>
              </div>

              <div class="space-y-2 pt-2 border-t border-emerald-200 text-sm text-gray-700">
                <label class="flex items-start gap-2">
                  <input v-model="form.aceitou_regulamento" type="checkbox" required class="mt-1 h-4 w-4 rounded border-gray-300 text-emerald-600" />
                  <span>Li e aceito o regulamento da competição.</span>
                </label>
                <label class="flex items-start gap-2">
                  <input v-model="form.aceitou_termos_imagem" type="checkbox" required class="mt-1 h-4 w-4 rounded border-gray-300 text-emerald-600" />
                  <span>Autorizo o uso de imagem para divulgação oficial do evento.</span>
                </label>
                <label class="flex items-start gap-2">
                  <input v-model="form.aceitou_declaracao_veracidade" type="checkbox" required class="mt-1 h-4 w-4 rounded border-gray-300 text-emerald-600" />
                  <span>Declaro a veracidade das informações, ciência do percurso e que estou em condições físicas adequadas para participar (Art. 18).</span>
                </label>
                <p class="text-xs text-gray-500">
                  Privacidade (LGPD): os dados informados são utilizados exclusivamente para organização, controle de retirada de kit, cronometragem e premiação do evento.
                </p>
              </div>
            </div>
        </div>
      </div>

      <div v-if="inscricaoCriada" class="mb-6 rounded-lg border border-green-200 bg-green-50 p-4 space-y-2">
        <p class="font-semibold text-green-800">Inscrição registrada com sucesso!</p>
        <p class="text-sm text-green-700">
          Status: <strong class="uppercase">{{ inscricaoCriada.status === 'pendente' ? 'Aguardando contribuição' : inscricaoCriada.status }}</strong>
          <span v-if="inscricaoCriada.numero_peito"> — Peito #{{ formatNumeroPeito(inscricaoCriada.numero_peito) }}</span>
        </p>
        <div class="flex flex-wrap gap-3 pt-1">
          <a
            v-if="inscricaoCriada.comprovante_pdf_url"
            :href="inscricaoCriada.comprovante_pdf_url"
            target="_blank"
            rel="noopener"
            class="inline-flex items-center px-3 py-1.5 rounded bg-green-700 text-white text-sm font-medium hover:bg-green-800"
          >
            Baixar Comprovante (PDF)
          </a>
          <button
            type="button"
            @click="$router.push(retornoSeguro)"
            class="inline-flex items-center px-3 py-1.5 rounded border border-green-600 text-green-800 text-sm font-medium hover:bg-green-100"
          >
            {{ retornoSeguro === '/admin/inscricoes' ? 'Ir para lista de inscrições' : 'Voltar para participantes' }}
          </button>
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
import { formatNumeroPeito } from '@/utils/formatNumeroPeito';
import { useRouter, useRoute } from 'vue-router';
import { useInscricaoStore } from '../store/inscricaoStore';
import { useCampeonatoStore } from '@/modules/campeonatos/store/campeonatoStore';
import inscricaoService from '../services/inscricaoService';
import api from '@/services/api'; // Import direto para chamada customizada

const router = useRouter();
const route = useRoute();
const inscricaoStore = useInscricaoStore();
const campeonatoStore = useCampeonatoStore();

const form = reactive({
  campeonato_id: '',
  atleta_id: '',
  categoria_id: '', // Adicionado para suportar categorias específicas
  forma_contribuicao: '',
  responsavel_nome: '',
  aceitou_regulamento: false,
  aceitou_termos_imagem: false,
  aceitou_declaracao_veracidade: false,
});

const searchAtleta = ref('');
const loading = computed(() => inscricaoStore.loading);
const error = computed(() => inscricaoStore.error);
const campeonatos = computed(() => campeonatoStore.campeonatos);

const atletasElegiveis = ref([]);
const loadingAtletas = ref(false);
const camposCadastroInfo = ref(null);
const inscricaoCriada = ref(null);

const selectedCampeonato = computed(() => {
    return campeonatos.value.find(c => String(c.id) === String(form.campeonato_id));
});

const isSolidaria = computed(() => {
    return Boolean(camposCadastroInfo.value?.campeonato?.inscricao_solidaria ?? selectedCampeonato.value?.inscricao_solidaria);
});

const categoriasDoCampeonato = computed(() => {
    return camposCadastroInfo.value?.campeonato?.categorias || selectedCampeonato.value?.categorias || [];
});

const normalizarGenero = (valor) => {
    const v = String(valor || '').trim().toUpperCase();
    if (['M', 'MASCULINO', 'MASC'].includes(v)) return 'M';
    if (['F', 'FEMININO', 'FEM'].includes(v)) return 'F';
    if (v === 'MISTO') return 'MISTO';
    return v;
};

onMounted(async () => {
  await campeonatoStore.fetchCampeonatos();
  if (route.query.campeonato_id) {
      form.campeonato_id = Number(route.query.campeonato_id) || route.query.campeonato_id;
      await onCampeonatoChange();
  }
});

const onCampeonatoChange = async () => {
    form.atleta_id = '';
    form.categoria_id = '';
    form.forma_contribuicao = '';
    form.responsavel_nome = '';
    atletasElegiveis.value = [];
    camposCadastroInfo.value = null;
    inscricaoCriada.value = null;
    
    if (!form.campeonato_id) return;

    loadingAtletas.value = true;
    try {
        try {
            const cfgResp = await inscricaoService.getCamposCadastro(form.campeonato_id);
            camposCadastroInfo.value = cfgResp.data?.data || null;
        } catch {
            camposCadastroInfo.value = null;
        }

        const camp = selectedCampeonato.value;
        // Chama endpoint de simulação com flag return_list=true
        const response = await api.post('/competicoes/simular-elegibilidade', {
            idade_min: camp?.idade_min,
            idade_max: camp?.idade_max,
            cidade_eleitoral: camp?.cidade_eleitoral,
            bairro_eleitoral: camp?.bairro_eleitoral,
            genero: camp?.genero,
            return_list: true
        });
        
        if (response.data?.success) {
            atletasElegiveis.value = Array.isArray(response.data?.data?.atletas) ? response.data.data.atletas : [];
        }
    } catch (e) {
        console.error("Erro ao buscar atletas elegíveis", e);
        atletasElegiveis.value = [];
    } finally {
        loadingAtletas.value = false;
    }
};

const atletasFiltrados = computed(() => {
    let lista = Array.isArray(atletasElegiveis.value) ? atletasElegiveis.value : [];
    if (searchAtleta.value) {
        const termo = searchAtleta.value.toLowerCase();
        lista = lista.filter(a => (a.nome || '').toLowerCase().includes(termo));
    }
    return lista;
});

const atletaSelecionado = computed(() => {
    const lista = Array.isArray(atletasElegiveis.value) ? atletasElegiveis.value : [];
    return lista.find(a => a.id === form.atleta_id) || null;
});

const atletaSelecionadoEhMenor = computed(() => {
    const atleta = atletaSelecionado.value;
    if (!atleta) return false;
    if (typeof atleta.idade === 'number') return atleta.idade < 18;
    return false;
});

// Lógica para determinar categorias disponíveis para o atleta selecionado
const categoriasDisponiveis = computed(() => {
    if (!form.atleta_id || categoriasDoCampeonato.value.length === 0) return [];
    
    const atleta = atletaSelecionado.value;
    if (!atleta) return [];
    const sexoAtleta = normalizarGenero(atleta.sexo || atleta.genero);

    return categoriasDoCampeonato.value.filter(cat => {
        if (cat.idade_min !== null && cat.idade_min !== undefined && atleta.idade < cat.idade_min) return false;
        if (cat.idade_max !== null && cat.idade_max !== undefined && atleta.idade > cat.idade_max) return false;
        if (cat.genero) {
            const catGen = normalizarGenero(cat.genero);
            if (catGen !== 'MISTO' && sexoAtleta && catGen !== sexoAtleta) return false;
        }
        return true;
    });
});

const categoriaSelecionadaObj = computed(() => {
    return categoriasDisponiveis.value.find(c => String(c.id) === String(form.categoria_id)) || null;
});

const opcoesContribuicao = computed(() => {
    const cat = categoriaSelecionadaObj.value;
    const opcoes = [];
    const valor = cat?.valor_inscricao !== null && cat?.valor_inscricao !== undefined
        ? Number(cat.valor_inscricao)
        : Number(selectedCampeonato.value?.valor_inscricao || 0);
    if (valor > 0) {
        opcoes.push({ value: 'dinheiro', label: `Dinheiro (R$ ${valor.toFixed(2).replace('.', ',')})` });
    }
    if (Number(cat?.contribuicao_alimento_kg || 0) > 0) {
        opcoes.push({ value: 'alimento', label: `Alimento (${cat.contribuicao_alimento_kg} kg)` });
    }
    if (cat?.aceita_brinquedo) {
        opcoes.push({ value: 'brinquedo', label: '1 brinquedo' });
    }
    return opcoes;
});

// Auto-selecionar categoria se houver apenas uma compatível
watch(categoriasDisponiveis, (cats) => {
    if (cats.length === 1) {
        form.categoria_id = cats[0].id;
    } else {
        form.categoria_id = '';
    }
});

watch(() => form.categoria_id, () => {
    form.forma_contribuicao = '';
});

// Retorno seguro: só aceita caminhos internos do painel admin (ex.: lista de participantes da competição)
const retornoSeguro = computed(() => {
    const retorno = route.query.retorno;
    if (typeof retorno === 'string' && retorno.startsWith('/admin/') && !retorno.startsWith('//')) {
        return retorno;
    }
    return '/admin/inscricoes';
});

const submit = async () => {
  try {
    const resp = await inscricaoStore.createInscricao(form);
    if (isSolidaria.value && resp) {
      inscricaoCriada.value = resp.data || resp;
      return;
    }
    router.push(retornoSeguro.value);
  } catch (err) {
    // Erro tratado no store
  }
};
</script>
