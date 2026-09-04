<template>
  <div class="overflow-x-clip p-4 max-w-7xl mx-auto">
    <div class="mb-6 flex flex-col-reverse sm:flex-row sm:justify-between sm:items-start gap-3">
      <h1 class="text-xl sm:text-2xl font-bold text-gray-800 leading-tight min-w-0 break-words">{{ campeonato?.nome }}</h1>
      <router-link 
        to="/campeonatos"
        class="text-blue-600 hover:text-blue-800 inline-flex items-center gap-2 font-medium min-h-11 shrink-0 self-start"
      >
        <ArrowLeft :size="20" /> Voltar
      </router-link>
    </div>

    <div v-if="loading" class="text-center py-12">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
    </div>

    <div v-else-if="!campeonato" class="text-center py-12 bg-white rounded-lg shadow-sm">
      <p class="text-gray-500">Campeonato não encontrado.</p>
    </div>

    <div v-else>
      <div v-if="getBannerUrl(campeonato)" class="mb-6">
        <img
          :src="getBannerUrl(campeonato)"
          alt="Banner da competição"
          class="w-full h-40 sm:h-56 md:h-72 object-cover rounded-xl border border-gray-100 shadow-sm"
        />
      </div>

      <!-- Info Card -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 sm:p-6 mb-6 sm:mb-8">
        <div class="flex flex-wrap gap-4 text-sm text-gray-600 mb-4">
          <span class="bg-blue-50 text-blue-700 px-3 py-1 rounded-full font-bold uppercase text-xs tracking-wide">
            {{ campeonato.modalidade?.nome }}
          </span>
          <span class="flex items-center gap-1">
            <Calendar :size="16" /> {{ formatDate(campeonato.data_inicio) }} - {{ formatDate(campeonato.data_fim) }}
          </span>
          <span class="flex items-center gap-1">
            <MapPin :size="16" /> {{ campeonato.cidade_eleitoral }}
          </span>
          <span class="flex items-center gap-1">
            <Info :size="16" /> Status: {{ formatStatus(campeonato.status) }}
          </span>
        </div>
      </div>

      <!-- Abas de Navegação -->
      <div class="flex border-b border-gray-200 mb-6 sm:mb-8 overflow-x-auto -mx-4 px-4 sm:mx-0 sm:px-0">
        <button 
          @click="activeTab = 'classificacao'"
          class="px-4 sm:px-6 py-3 font-medium text-sm flex items-center gap-2 whitespace-nowrap border-b-2 transition-colors min-h-12"
          :class="activeTab === 'classificacao' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
        >
          <Trophy :size="18" /> Classificação
        </button>
        <button 
          @click="activeTab = 'artilharia'"
          class="px-6 py-3 font-medium text-sm flex items-center gap-2 whitespace-nowrap border-b-2 transition-colors"
          :class="activeTab === 'artilharia' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
        >
          <Target :size="18" /> Artilharia
        </button>
        <button 
          @click="activeTab = 'cartoes'"
          class="px-6 py-3 font-medium text-sm flex items-center gap-2 whitespace-nowrap border-b-2 transition-colors"
          :class="activeTab === 'cartoes' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
        >
          <Square :size="18" /> Cartões
        </button>
      </div>

      <!-- Conteúdo das Abas -->
      
      <!-- ABA CLASSIFICAÇÃO -->
      <div v-if="activeTab === 'classificacao'" class="space-y-8">
        <!-- Mata-Mata -->
        <ChaveamentoBracket 
          v-if="campeonato.formato === 'mata_mata'" 
          :fases="fases"
          partida-route="PortalPartida"
        />

        <!-- Grupos / Pontos Corridos -->
        <div v-else class="space-y-8">
            <div v-for="fase in fases" :key="fase.id">
                <div v-if="fase.classificacao && Object.keys(fase.classificacao).length" class="mb-8">
                    <h3 class="text-lg font-bold text-gray-700 mb-4 bg-gray-100 p-2 rounded">
                        Classificação - {{ fase.nome }}
                    </h3>
                    
                    <!-- Abas de Grupos -->
                    <div v-if="Object.keys(fase.classificacao).length > 1" class="flex border-b border-gray-200 mb-6 overflow-x-auto">
                        <button 
                            v-for="(tabela, grupoNome) in fase.classificacao" 
                            :key="grupoNome"
                            @click="activeGrupoTab[fase.id] = grupoNome"
                            class="px-6 py-3 font-medium text-sm whitespace-nowrap border-b-2 transition-colors"
                            :class="(activeGrupoTab[fase.id] || Object.keys(fase.classificacao)[0]) === grupoNome ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                        >
                            {{ grupoNome !== 'Geral' ? 'Grupo ' + grupoNome : 'Geral' }}
                        </button>
                    </div>

                    <!-- Tabela do Grupo Ativo -->
                    <div class="bg-white rounded-lg shadow-sm overflow-hidden border border-gray-200">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead class="bg-gray-50 text-gray-500">
                                    <tr>
                                        <th class="px-3 py-2 text-left font-medium">Pos</th>
                                        <th class="px-3 py-2 text-left font-medium">Equipe</th>
                                        <th class="px-3 py-2 text-center font-medium">P</th>
                                        <th class="px-3 py-2 text-center font-medium">J</th>
                                        <th class="px-3 py-2 text-center font-medium">V</th>
                                        <th class="px-3 py-2 text-center font-medium hidden sm:table-cell">E</th>
                                        <th class="px-3 py-2 text-center font-medium hidden sm:table-cell">D</th>
                                        <th class="px-3 py-2 text-center font-medium hidden sm:table-cell">GP</th>
                                        <th class="px-3 py-2 text-center font-medium hidden sm:table-cell">GC</th>
                                        <th class="px-3 py-2 text-center font-medium hidden sm:table-cell">SG</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <tr v-for="time in fase.classificacao[activeGrupoTab[fase.id] || Object.keys(fase.classificacao)[0]]" :key="time.id" class="hover:bg-gray-50">
                                        <td class="px-3 py-2 font-bold text-gray-700">{{ time.posicao }}º</td>
                                        <td class="px-3 py-2 font-medium text-gray-900">
                                            <div class="flex items-center gap-2 min-w-0 max-w-[10rem] sm:max-w-none">
                                            <img v-if="time.logo" :src="time.logo" class="w-6 h-6 object-contain shrink-0" alt="" />
                                            <span class="truncate">{{ time.nome }}</span>
                                            </div>
                                        </td>
                                        <td class="px-3 py-2 text-center font-bold text-blue-600">{{ time.pontos }}</td>
                                        <td class="px-3 py-2 text-center text-gray-600">{{ time.jogos }}</td>
                                        <td class="px-3 py-2 text-center text-green-600 font-medium">{{ time.vitorias }}</td>
                                        <td class="px-3 py-2 text-center text-gray-500 hidden sm:table-cell">{{ time.empates }}</td>
                                        <td class="px-3 py-2 text-center text-red-500 hidden sm:table-cell">{{ time.derrotas }}</td>
                                        <td class="px-3 py-2 text-center text-gray-600 hidden sm:table-cell">{{ time.gols_pro }}</td>
                                        <td class="px-3 py-2 text-center text-gray-600 hidden sm:table-cell">{{ time.gols_contra }}</td>
                                        <td class="px-3 py-2 text-center text-gray-600 hidden sm:table-cell">{{ time.saldo_gols }}</td>
                                    </tr>
                                    <tr v-if="!fase.classificacao[activeGrupoTab[fase.id] || Object.keys(fase.classificacao)[0]]?.length">
                                        <td colspan="10" class="px-3 py-8 text-center text-gray-500">
                                            Nenhuma equipe com partidas finalizadas neste grupo.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Confrontos detalhados -->
            <div class="mt-8 space-y-6">
              <div class="flex flex-wrap items-center justify-between gap-3">
                <h3 class="text-lg font-bold text-gray-700 border-l-4 border-green-500 pl-3">Confrontos Detalhados</h3>
                <div class="flex items-center gap-2 text-xs">
                  <span class="px-2.5 py-1 rounded-full bg-blue-50 text-blue-700 font-semibold">
                    {{ resumoConfrontos.total }} jogos
                  </span>
                  <span class="px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 font-semibold">
                    {{ resumoConfrontos.finalizadas }} encerrados
                  </span>
                  <span class="px-2.5 py-1 rounded-full bg-amber-50 text-amber-700 font-semibold">
                    {{ resumoConfrontos.pendentes }} pendentes
                  </span>
                </div>
              </div>

              <div v-if="!jogosPorFase.length" class="text-center py-8 text-gray-500 bg-gray-50 rounded-lg border border-gray-200">
                Nenhuma partida cadastrada.
              </div>

              <div v-for="faseBloco in jogosPorFase" :key="faseBloco.id" class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 md:p-5">
                <div class="flex flex-wrap items-center justify-between gap-3 mb-4 pb-3 border-b border-gray-100">
                  <div>
                    <p class="text-xs uppercase tracking-wide text-gray-500">Fase</p>
                    <h4 class="text-base md:text-lg font-bold text-gray-800">{{ faseBloco.nome }}</h4>
                  </div>
                  <div class="flex items-center gap-2 text-xs">
                    <span class="px-2 py-1 rounded-full bg-slate-100 text-slate-700 font-medium">
                      {{ faseBloco.partidas.length }} jogos
                    </span>
                    <span class="px-2 py-1 rounded-full bg-green-100 text-green-700 font-medium">
                      {{ faseBloco.partidasFinalizadas }} finalizados
                    </span>
                  </div>
                </div>

                <div v-if="faseBloco.temGrupos" class="space-y-5">
                  <div v-for="grupoBloco in faseBloco.grupos" :key="`${faseBloco.id}-${grupoBloco.chave}`" class="space-y-3">
                    <div class="flex items-center gap-2">
                      <span class="text-xs font-bold uppercase tracking-wide text-indigo-700 bg-indigo-50 px-2.5 py-1 rounded-full">
                        {{ formatarNomeGrupo(grupoBloco.nome) }}
                      </span>
                      <span class="text-xs text-gray-500">{{ grupoBloco.partidas.length }} jogos</span>
                    </div>
                    <div class="space-y-3">
                      <PartidaPublicCard
                        v-for="partida in grupoBloco.partidas"
                        :key="partida.id"
                        :partida="partida"
                      />
                    </div>
                  </div>
                </div>

                <div v-else class="space-y-3">
                  <PartidaPublicCard
                    v-for="partida in faseBloco.partidas"
                    :key="partida.id"
                    :partida="partida"
                  />
                </div>
              </div>
            </div>
        </div>
      </div>

      <!-- ABA ARTILHARIA -->
      <div v-else-if="activeTab === 'artilharia'" class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div v-if="loadingArtilharia" class="p-8 text-center text-gray-500">Carregando artilharia...</div>
        <div v-else-if="artilharia.length === 0" class="p-8 text-center text-gray-500">Nenhum gol registrado.</div>
        <div v-else class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pos</th>
              <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Atleta</th>
              <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Equipe</th>
              <th class="px-3 sm:px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Gols</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="(atleta, index) in artilharia" :key="atleta.id">
              <td class="px-3 sm:px-6 py-4 whitespace-nowrap text-sm text-gray-500 font-bold">{{ index + 1 }}º</td>
              <td class="px-3 sm:px-6 py-4">
                <div class="flex items-center min-w-0">
                  <div class="h-9 w-9 sm:h-10 sm:w-10 flex-shrink-0">
                    <img class="h-full w-full rounded-full object-cover bg-gray-100" :src="atleta.foto_url || '/placeholder-user.png'" alt="" />
                  </div>
                  <div class="ml-3 min-w-0">
                    <div class="text-sm font-medium text-gray-900 truncate max-w-[9rem] sm:max-w-none">{{ atleta.nome }}</div>
                  </div>
                </div>
              </td>
              <td class="px-3 sm:px-6 py-4">
                <div class="flex items-center min-w-0">
                  <img v-if="atleta.equipe_logo_url" :src="atleta.equipe_logo_url" class="w-6 h-6 mr-2 object-contain shrink-0" alt="" />
                  <span class="text-sm text-gray-500 truncate max-w-[8rem] sm:max-w-none">{{ atleta.equipe_nome }}</span>
                </div>
              </td>
              <td class="px-3 sm:px-6 py-4 whitespace-nowrap text-center text-sm font-bold text-blue-600">{{ atleta.gols }}</td>
            </tr>
          </tbody>
        </table>
        </div>
      </div>

      <!-- ABA CARTÕES -->
      <div v-else-if="activeTab === 'cartoes'" class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="p-4 bg-yellow-50 border-b border-yellow-100 text-yellow-800 text-sm flex items-start gap-2">
          <Info :size="16" class="mt-0.5" />
          <div>
            <strong>Regra de Suspensão:</strong> {{ textoRegraSuspensao }}
          </div>
        </div>
        
        <div v-if="loadingCartoes" class="p-8 text-center text-gray-500">Carregando cartões...</div>
        <div v-else-if="cartoes.length === 0" class="p-8 text-center text-gray-500">Nenhum cartão registrado.</div>
        <div v-else class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Atleta</th>
              <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Equipe</th>
              <th class="px-3 sm:px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Am.</th>
              <th class="px-3 sm:px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Verm.</th>
              <th class="px-3 sm:px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="atleta in cartoes" :key="atleta.id">
              <td class="px-3 sm:px-6 py-4">
                <div class="flex items-center min-w-0">
                  <div class="h-9 w-9 sm:h-10 sm:w-10 flex-shrink-0">
                    <img class="h-full w-full rounded-full object-cover bg-gray-100" :src="atleta.foto_url || '/placeholder-user.png'" alt="" />
                  </div>
                  <div class="ml-3 min-w-0">
                    <div class="text-sm font-medium text-gray-900 truncate max-w-[8rem] sm:max-w-none">{{ atleta.nome }}</div>
                  </div>
                </div>
              </td>
              <td class="px-3 sm:px-6 py-4">
                <div class="flex items-center min-w-0">
                  <img v-if="atleta.equipe_logo_url" :src="atleta.equipe_logo_url" class="w-6 h-6 mr-2 object-contain shrink-0" alt="" />
                  <span class="text-sm text-gray-500 truncate max-w-[7rem] sm:max-w-none">{{ atleta.equipe_nome }}</span>
                </div>
              </td>
              <td class="px-3 sm:px-6 py-4 whitespace-nowrap text-center">
                <span class="inline-flex items-center justify-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                  {{ atleta.amarelos }}
                </span>
              </td>
              <td class="px-3 sm:px-6 py-4 whitespace-nowrap text-center">
                <span class="inline-flex items-center justify-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                  {{ atleta.vermelhos }}
                </span>
              </td>
              <td class="px-3 sm:px-6 py-4 whitespace-nowrap text-center">
                <span v-if="atleta.suspenso" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800" :title="atleta.motivo_suspensao">
                  Suspenso
                </span>
                <span v-else class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                  Regular
                </span>
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
import { useRoute } from 'vue-router';
import portalService from '@/modules/portal/services/portalService';
import ChaveamentoBracket from '@/modules/tabela/components/ChaveamentoBracket.vue';
import PartidaPublicCard from '@/modules/portal/components/PartidaPublicCard.vue';
import { Calendar, MapPin, ArrowLeft, Trophy, Target, Square, Info } from 'lucide-vue-next';

const route = useRoute();
const campeonato = ref(null);
const fases = ref([]);
const loading = ref(true);

const activeTab = ref('classificacao');
const activeGrupoTab = ref({});
const artilharia = ref([]);
const cartoes = ref([]);
const loadingArtilharia = ref(false);
const loadingCartoes = ref(false);

const textoRegraSuspensao = computed(() => {
  if (!campeonato.value?.suspensao_automatica_ativa) {
    return 'Suspensão automática desativada para esta competição.'
  }

  const amarelos = Number(campeonato.value?.cartoes_amarelos_para_suspensao || 2)
  const regraAmarelo = `${amarelos} cartão${amarelos > 1 ? 'es' : ''} amarelo${amarelos > 1 ? 's' : ''}`
  const vermelhoAtivo = Boolean(campeonato.value?.cartao_vermelho_suspende ?? true)

  if (vermelhoAtivo) {
    return `${regraAmarelo} ou 1 cartão vermelho implicam suspensão automática na próxima partida.`
  }
  return `${regraAmarelo} implicam suspensão automática na próxima partida.`
})

const ordenarPartidas = (partidas = []) => {
  return [...partidas].sort((a, b) => {
    const rodadaA = Number(a?.rodada || 0);
    const rodadaB = Number(b?.rodada || 0);
    if (rodadaA !== rodadaB) return rodadaA - rodadaB;

    const ordemA = Number(a?.ordem_na_fase || 0);
    const ordemB = Number(b?.ordem_na_fase || 0);
    if (ordemA !== ordemB) return ordemA - ordemB;

    const dataA = a?.data_jogo ? new Date(a.data_jogo).getTime() : Number.MAX_SAFE_INTEGER;
    const dataB = b?.data_jogo ? new Date(b.data_jogo).getTime() : Number.MAX_SAFE_INTEGER;
    if (dataA !== dataB) return dataA - dataB;

    return Number(a?.id || 0) - Number(b?.id || 0);
  });
};

const getNomeGrupo = (grupo) => {
  if (!grupo) return '';
  if (typeof grupo === 'string') return grupo.trim();
  if (typeof grupo === 'object') {
    return String(grupo.nome || grupo.name || grupo.grupo || '').trim();
  }
  return String(grupo).trim();
};

const formatarNomeGrupo = (grupo) => {
  const nome = getNomeGrupo(grupo);
  if (!nome) return 'Sem Grupo';
  return nome.toLowerCase().startsWith('grupo') ? nome : `Grupo ${nome}`;
};

const jogosPorFase = computed(() => {
  return (fases.value || [])
    .map((fase) => {
      const partidasOrdenadas = ordenarPartidas(fase?.partidas || []);
      const gruposMap = new Map();

      partidasOrdenadas.forEach((partida) => {
        const grupoNome = getNomeGrupo(partida?.grupo);
        if (grupoNome === 'Geral') return;
        if (!grupoNome) return;
        if (!gruposMap.has(grupoNome)) gruposMap.set(grupoNome, []);
        gruposMap.get(grupoNome).push(partida);
      });

      const grupos = Array.from(gruposMap.entries())
        .map(([nome, partidas]) => ({
          chave: nome,
          nome,
          partidas: ordenarPartidas(partidas)
        }))
        .sort((a, b) => a.nome.localeCompare(b.nome, 'pt-BR'));

      return {
        id: fase.id || `fase_${fase.ordem || fase.nome}`,
        ordem: Number(fase?.ordem || 0),
        nome: fase?.nome || 'Fase',
        partidas: partidasOrdenadas,
        partidasFinalizadas: partidasOrdenadas.filter((p) => p.status === 'finalizada').length,
        temGrupos: grupos.length > 0,
        grupos
      };
    })
    .filter((fase) => fase.partidas.length > 0)
    .sort((a, b) => a.ordem - b.ordem);
});

const resumoConfrontos = computed(() => {
  const total = jogosPorFase.value.reduce((acc, fase) => acc + fase.partidas.length, 0);
  const finalizadas = jogosPorFase.value.reduce((acc, fase) => acc + fase.partidasFinalizadas, 0);
  return {
    total,
    finalizadas,
    pendentes: Math.max(0, total - finalizadas)
  };
});

const loadData = async () => {
  loading.value = true;
  try {
    // Carregar Tabela
    const { data } = await portalService.getTabela(route.params.id);
    
    // Processar dados da tabela
    const dadosNormalizados = {
        ...data,
        fases: (data.fases || []).map(fase => ({
            ...fase,
            classificacao: fase.classificacao || {}, 
            partidas: (fase.partidas || []).map(p => ({
                ...p,
                mandante: p.mandante || { nome: p.mandante_id ? 'Carregando...' : 'A definir' },
                visitante: p.visitante || { nome: p.visitante_id ? 'Carregando...' : 'A definir' },
                grupo: p.grupo || null,
                chave: p.chave || null
            }))
        }))
    };

    campeonato.value = dadosNormalizados;
    fases.value = dadosNormalizados.fases;
    
    // Carregar dados iniciais das outras abas (opcional, pode ser lazy load)
    loadArtilharia();
    loadCartoes();

  } catch (e) {
    console.error('Erro ao carregar tabela', e);
  } finally {
    loading.value = false;
  }
};

const loadArtilharia = async () => {
  loadingArtilharia.value = true;
  try {
    const { data } = await portalService.getArtilharia(route.params.id);
    artilharia.value = data;
  } catch (e) {
    console.error('Erro ao carregar artilharia', e);
  } finally {
    loadingArtilharia.value = false;
  }
};

const loadCartoes = async () => {
  loadingCartoes.value = true;
  try {
    const { data } = await portalService.getCartoes(route.params.id);
    cartoes.value = data;
  } catch (e) {
    console.error('Erro ao carregar cartões', e);
  } finally {
    loadingCartoes.value = false;
  }
};

const formatDate = (dateString) => {
  if (!dateString) return '';
  return new Date(dateString).toLocaleDateString('pt-BR');
};

const formatStatus = (status) => {
    const map = {
        'em_andamento': 'Em Andamento',
        'finalizado': 'Finalizado',
        'agendado': 'Agendado',
        'inscricoes_abertas': 'Inscrições Abertas'
    };
    return map[status] || status;
};

const getBannerUrl = (campeonatoData) => {
  if (!campeonatoData) return '';
  if (campeonatoData.banner_url) return campeonatoData.banner_url;
  if (!campeonatoData.banner) return '';
  if (campeonatoData.banner.startsWith('http://') || campeonatoData.banner.startsWith('https://')) {
    return campeonatoData.banner;
  }
  return campeonatoData.banner; // Fallback, but banner_url from API is preferred
};

onMounted(loadData);
</script>
