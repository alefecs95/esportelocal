<template>
  <div class="event-detail-page min-h-screen bg-[#f8fafc] text-slate-900 overflow-x-clip">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">
      <!-- Breadcrumb / Voltar -->
      <div class="mb-5">
        <router-link
          to="/campeonatos"
          class="pressable inline-flex items-center gap-2 text-sm font-semibold text-slate-600 hover:text-slate-950 min-h-10"
        >
          <ArrowLeft :size="16" /> Voltar para competições
        </router-link>
      </div>

      <!-- Loading skeleton -->
      <div v-if="loading" class="space-y-6 animate-pulse">
        <div class="bg-white rounded-2xl border border-slate-200/80 overflow-hidden">
          <div class="h-52 sm:h-72 bg-slate-200"></div>
          <div class="p-6 sm:p-8 space-y-4">
            <div class="h-8 w-2/3 bg-slate-200 rounded-lg"></div>
            <div class="h-4 w-1/2 bg-slate-100 rounded"></div>
          </div>
        </div>
      </div>

      <div v-else-if="!campeonato" class="bg-white rounded-2xl border border-slate-200/80 px-6 py-16 text-center max-w-xl mx-auto">
        <p class="text-slate-600 font-medium">Campeonato não encontrado.</p>
      </div>

      <div v-else class="space-y-6 sm:space-y-8">
        <!-- Hero Card do Evento -->
        <section class="bg-white rounded-2xl border border-slate-200/90 shadow-[0_1px_2px_rgba(15,23,42,0.04)] overflow-hidden">
          <div v-if="getBannerUrl(campeonato)" class="relative h-48 sm:h-64 md:h-80 bg-slate-900 overflow-hidden">
            <img
              :src="getBannerUrl(campeonato)"
              :alt="campeonato.nome"
              class="w-full h-full object-cover"
            />
            <div class="absolute inset-0 bg-gradient-to-t from-slate-950/60 via-transparent to-transparent"></div>
            <div class="absolute top-4 left-4 flex flex-wrap items-center gap-2">
              <span class="px-3 py-1 rounded-full text-xs font-bold bg-white/95 text-slate-900 shadow-sm">
                {{ campeonato.modalidade?.nome || 'Esporte' }}
              </span>
              <span
                class="px-3 py-1 rounded-full text-xs font-bold shadow-sm"
                :class="campeonato.status === 'inscricoes_abertas' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200/80' : 'bg-white/90 text-slate-800'"
              >
                {{ formatStatus(campeonato.status) }}
              </span>
            </div>
          </div>

          <div class="p-5 sm:p-8">
            <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-5">
              <div class="min-w-0 flex-1">
                <div v-if="!getBannerUrl(campeonato)" class="flex flex-wrap items-center gap-2 mb-3">
                  <span class="px-3 py-1 rounded-full text-xs font-bold bg-blue-50 text-blue-700">
                    {{ campeonato.modalidade?.nome || 'Esporte' }}
                  </span>
                  <span class="px-3 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-700">
                    {{ formatStatus(campeonato.status) }}
                  </span>
                </div>

                <h1 class="text-2xl sm:text-4xl font-extrabold tracking-tight text-slate-950 leading-[1.12] break-words">
                  {{ campeonato.nome }}
                </h1>

                <div class="flex flex-wrap items-center gap-x-5 gap-y-2.5 text-sm text-slate-600 mt-4">
                  <span class="inline-flex items-center gap-1.5 tabular-nums">
                    <Calendar :size="16" class="text-slate-400" />
                    {{ formatPeriodo(campeonato.data_inicio, campeonato.data_fim) }}
                  </span>
                  <span v-if="eventoInfo?.local || campeonato.local || campeonato.cidade_eleitoral" class="inline-flex items-center gap-1.5">
                    <MapPin :size="16" class="text-slate-400" />
                    {{ eventoInfo?.local || campeonato.local || campeonato.cidade_eleitoral }}
                  </span>
                  <button
                    type="button"
                    @click="activeTab = 'inscritos'"
                    class="pressable inline-flex items-center gap-1.5 bg-emerald-50 text-emerald-800 hover:bg-emerald-100 border border-emerald-200/80 px-3 py-1 rounded-full font-bold text-xs tabular-nums"
                  >
                    <Users :size="14" /> {{ totalInscritos }} {{ totalInscritos === 1 ? 'inscrito' : 'inscritos' }}
                  </button>
                </div>
              </div>

              <div v-if="eventoInfo?.permite_inscricao_online && eventoInfo?.status === 'inscricoes_abertas'" class="shrink-0">
                <router-link
                  :to="`/campeonatos/${campeonato.slug || campeonato.id}/inscricao`"
                  class="pressable inline-flex items-center justify-center w-full sm:w-auto px-6 py-3.5 rounded-xl bg-emerald-600 text-white font-bold text-sm hover:bg-emerald-700 shadow-sm"
                >
                  Fazer Inscrição Online
                </router-link>
              </div>
            </div>

            <!-- Bloco Público de Corrida / Inscrição Solidária -->
            <div v-if="isEventoCorridaOuSolidario" class="mt-6 pt-6 border-t border-slate-100 space-y-4">
              <div v-if="eventoInfo?.inicio_inscricao || eventoInfo?.fim_inscricao || eventoInfo?.kit_retirada_info" class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                <div v-if="eventoInfo?.inicio_inscricao || eventoInfo?.fim_inscricao" class="rounded-xl bg-slate-50 border border-slate-200/70 p-3.5">
                  <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">Prazo de inscrição</p>
                  <p class="font-semibold text-slate-900 tabular-nums">
                    {{ formatDate(eventoInfo.inicio_inscricao) }} a {{ formatDate(eventoInfo.fim_inscricao) }}
                  </p>
                </div>
                <div v-if="eventoInfo?.kit_retirada_info" class="rounded-xl bg-slate-50 border border-slate-200/70 p-3.5">
                  <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">Retirada do kit</p>
                  <p class="text-slate-800">{{ eventoInfo.kit_retirada_info }}</p>
                </div>
              </div>

              <div v-if="eventoInfo?.inscricao_solidaria" class="rounded-xl bg-emerald-50/70 border border-emerald-200/80 p-4 text-sm text-emerald-950 space-y-1.5">
                <h3 class="font-bold text-emerald-950">Como contribuir</h3>
                <p class="leading-relaxed">
                  <strong>Adulto (3 km e 5 km):</strong> R$ 30,00 ou 4 kg de alimento ou 1 brinquedo.
                  <br />
                  <strong>Criança (Kids / Teens):</strong> 1 kg de alimento ou 1 brinquedo.
                </p>
              </div>

              <details v-if="eventoInfo?.regulamento" class="group rounded-xl bg-slate-50 border border-slate-200/80">
                <summary class="cursor-pointer select-none px-4 py-3 text-sm font-bold text-slate-800 flex items-center justify-between list-none">
                  <span>Ler regulamento completo</span>
                  <span class="text-xs font-semibold text-blue-600 group-open:hidden">Expandir</span>
                  <span class="text-xs font-semibold text-slate-500 hidden group-open:inline">Recolher</span>
                </summary>
                <div class="px-4 pb-4 pt-1 border-t border-slate-200/60">
                  <p class="text-sm text-slate-700 whitespace-pre-line leading-relaxed">{{ eventoInfo.regulamento }}</p>
                </div>
              </details>
            </div>
          </div>
        </section>

      <!-- Animação: Últimos Atletas Inscritos -->
      <div
        v-if="ultimosInscritos.length > 0"
        class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-6 sm:mb-8 overflow-hidden"
      >
        <div class="flex items-center justify-between gap-2 mb-3">
          <div class="flex items-center gap-2">
            <span class="relative flex h-2.5 w-2.5">
              <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
              <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500"></span>
            </span>
            <h2 class="text-sm font-bold text-gray-800">Últimos atletas inscritos</h2>
            <span class="text-xs font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full">
              {{ totalInscritos }} no total
            </span>
          </div>
          <button
            type="button"
            @click="activeTab = 'inscritos'"
            class="text-xs font-semibold text-blue-600 hover:text-blue-800"
          >
            Ver todos →
          </button>
        </div>

        <div class="marquee-container relative overflow-hidden">
          <div
            class="marquee-track flex gap-3 w-max"
            :class="{ 'marquee-animate': ultimosInscritos.length >= 3 }"
          >
            <div
              v-for="(item, idx) in ultimosInscritosLoop"
              :key="`${item.id}-${idx}`"
              class="flex items-center gap-2.5 bg-gray-50 border border-gray-200 rounded-full pl-1.5 pr-3.5 py-1.5 shrink-0"
            >
              <img
                v-if="item.foto_url"
                :src="item.foto_url"
                :alt="item.nome"
                class="w-8 h-8 rounded-full object-cover border border-white shadow-sm shrink-0"
              />
              <div
                v-else
                class="w-8 h-8 rounded-full bg-blue-600 text-white text-xs font-bold flex items-center justify-center shrink-0"
              >
                {{ getIniciais(item.nome) }}
              </div>
              <div class="leading-tight">
                <p class="text-xs font-bold text-gray-900 whitespace-nowrap">{{ item.nome }}</p>
                <p class="text-[11px] text-gray-500 whitespace-nowrap">
                  <span v-if="item.numero_peito" class="font-bold text-blue-700 mr-1">#{{ formatNumeroPeito(item.numero_peito) }}</span>
                  <span v-if="item.categoria">{{ item.categoria }}</span>
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Abas de Navegação -->
      <div class="flex border-b border-gray-200 mb-6 sm:mb-8 overflow-x-auto -mx-4 px-4 sm:mx-0 sm:px-0">
        <button 
          @click="activeTab = 'inscritos'"
          class="px-4 sm:px-6 py-3 font-medium text-sm flex items-center gap-2 whitespace-nowrap border-b-2 transition-colors min-h-12"
          :class="activeTab === 'inscritos' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
        >
          <Users :size="18" /> Inscritos ({{ totalInscritos }})
        </button>
        <button 
          v-if="isEventoCorridaOuSolidario"
          @click="activeTab = 'resultados_corrida'"
          class="px-4 sm:px-6 py-3 font-medium text-sm flex items-center gap-2 whitespace-nowrap border-b-2 transition-colors min-h-12"
          :class="activeTab === 'resultados_corrida' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
        >
          <Trophy :size="18" /> Resultados por Categoria
        </button>
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

      <!-- ABA INSCRITOS PÚBLICA (NOME, FOTO, CATEGORIA E NÚMERO DE PEITO) -->
      <div v-if="activeTab === 'inscritos'" class="space-y-4">
        <div class="bg-white rounded-xl border border-gray-200 p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
          <div>
            <h2 class="text-lg font-bold text-gray-800">Atletas Inscritos</h2>
            <p class="text-xs text-gray-500">
              Total de {{ totalInscritos }} {{ totalInscritos === 1 ? 'participante inscrito' : 'participantes inscritos' }} nesta competição.
            </p>
          </div>
          <div class="flex flex-col sm:flex-row gap-2">
            <input
              v-model="buscaInscrito"
              type="text"
              placeholder="Buscar por nome..."
              class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
            <select
              v-if="categoriasInscritosNomes.length > 0"
              v-model="filtroCategoriaInscrito"
              class="rounded-lg border border-gray-300 px-3 py-2 text-sm bg-white"
            >
              <option value="">Todas as categorias</option>
              <option v-for="cat in categoriasInscritosNomes" :key="cat" :value="cat">{{ cat }}</option>
            </select>
          </div>
        </div>

        <div v-if="loadingInscritos" class="text-center py-10 text-gray-500">Carregando atletas inscritos...</div>
        <div v-else-if="inscritosFiltrados.length === 0" class="bg-white rounded-xl border border-gray-200 p-8 text-center text-gray-500">
          Nenhum atleta inscrito encontrado.
        </div>
        <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
          <div
            v-for="item in inscritosFiltrados"
            :key="item.id"
            class="bg-white rounded-xl border border-gray-200 p-3.5 flex items-center gap-3 shadow-sm hover:shadow transition-shadow"
          >
            <img
              v-if="item.foto_url"
              :src="item.foto_url"
              :alt="item.nome"
              class="w-12 h-12 rounded-full object-cover border border-gray-200 shrink-0"
            />
            <div
              v-else
              class="w-12 h-12 rounded-full bg-blue-100 text-blue-700 font-bold text-sm flex items-center justify-center shrink-0"
            >
              {{ getIniciais(item.nome) }}
            </div>
            <div class="min-w-0 flex-1">
              <p class="font-bold text-sm text-gray-900 truncate">{{ item.nome }}</p>
              <div class="flex items-center gap-2 mt-0.5 text-xs text-gray-500">
                <span v-if="item.numero_peito" class="font-bold text-blue-700 bg-blue-50 px-2 py-0.5 rounded-full">
                  #{{ formatNumeroPeito(item.numero_peito) }}
                </span>
                <span v-if="item.categoria" class="truncate">{{ item.categoria }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ABA RESULTADOS POR CATEGORIA (CORRIDA PÚBLICA - SEM DADOS SENSÍVEIS) -->
      <div v-else-if="activeTab === 'resultados_corrida'" class="space-y-4">
        <div class="bg-white rounded-xl border border-gray-200 p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
          <div class="text-sm font-semibold text-gray-700">Filtrar por categoria / prova</div>
          <select v-model="filtroCategoriaResultado" class="px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white">
            <option value="">Todas as categorias</option>
            <option v-for="catNome in categoriasResultadosNomes" :key="catNome" :value="catNome">{{ catNome }}</option>
          </select>
        </div>

        <div v-if="loadingResultadosCorrida" class="bg-white rounded-xl border border-gray-200 p-8 text-center text-gray-500">
          Carregando resultados...
        </div>
        <div v-else-if="resultadosCorridaFiltrados.length === 0" class="bg-white rounded-xl border border-gray-200 p-8 text-center text-gray-500">
          Nenhum resultado oficial publicado ainda.
        </div>
        <div v-else class="bg-white rounded-xl border border-gray-200 overflow-hidden">
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
              <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                <tr>
                  <th class="px-4 py-3 text-left">Pos. Cat.</th>
                  <th class="px-4 py-3 text-left">Pos. Geral</th>
                  <th class="px-4 py-3 text-left">Atleta</th>
                  <th class="px-4 py-3 text-left">Categoria</th>
                  <th class="px-4 py-3 text-left">Tempo</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <tr v-for="res in resultadosCorridaFiltrados" :key="res.id" class="hover:bg-gray-50">
                  <td class="px-4 py-3 font-bold text-blue-700">{{ res.colocacao_categoria ? `${res.colocacao_categoria}º` : '-' }}</td>
                  <td class="px-4 py-3 text-gray-600">{{ res.colocacao ? `${res.colocacao}º` : '-' }}</td>
                  <td class="px-4 py-3 font-medium text-gray-900">
                    {{ res.atleta_nome }}
                    <span v-if="res.premiado" class="ml-1.5 px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 text-[11px] font-bold">Pódio</span>
                  </td>
                  <td class="px-4 py-3 text-gray-600">{{ res.categoria_nome }}</td>
                  <td class="px-4 py-3 font-mono font-semibold text-gray-800">{{ res.tempo_visual || '-' }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- ABA CLASSIFICAÇÃO -->
      <div v-else-if="activeTab === 'classificacao'" class="space-y-8">
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
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { formatNumeroPeito } from '@/utils/formatNumeroPeito';
import { useRoute, useRouter } from 'vue-router';
import portalService from '@/modules/portal/services/portalService';
import inscricaoService from '@/modules/inscricoes/services/inscricaoService';
import ChaveamentoBracket from '@/modules/tabela/components/ChaveamentoBracket.vue';
import PartidaPublicCard from '@/modules/portal/components/PartidaPublicCard.vue';
import { Calendar, MapPin, ArrowLeft, Trophy, Target, Square, Info, Users } from 'lucide-vue-next';

const route = useRoute();
const router = useRouter();
const campeonato = ref(null);
const fases = ref([]);
const loading = ref(true);

const activeTab = ref('classificacao');
const activeGrupoTab = ref({});
const artilharia = ref([]);
const cartoes = ref([]);
const loadingArtilharia = ref(false);
const loadingCartoes = ref(false);
const eventoInfo = ref(null);
const resultadosCorrida = ref([]);
const loadingResultadosCorrida = ref(false);
const filtroCategoriaResultado = ref('');
const totalInscritos = ref(0);
const ultimosInscritos = ref([]);
const inscritosLista = ref([]);
const loadingInscritos = ref(false);
const buscaInscrito = ref('');
const filtroCategoriaInscrito = ref('');

const ultimosInscritosLoop = computed(() => {
  if (ultimosInscritos.value.length >= 3) {
    return [...ultimosInscritos.value, ...ultimosInscritos.value];
  }
  return ultimosInscritos.value;
});

const categoriasInscritosNomes = computed(() => {
  const set = new Set();
  inscritosLista.value.forEach((i) => {
    if (i.categoria) set.add(i.categoria);
  });
  return Array.from(set);
});

const inscritosFiltrados = computed(() => {
  const q = buscaInscrito.value.trim().toLowerCase();
  return inscritosLista.value.filter((i) => {
    if (filtroCategoriaInscrito.value && i.categoria !== filtroCategoriaInscrito.value) {
      return false;
    }
    if (q && !(i.nome || '').toLowerCase().includes(q)) {
      return false;
    }
    return true;
  });
});

const getIniciais = (nome) => {
  if (!nome) return '?';
  const partes = nome.trim().split(/\s+/).filter(Boolean);
  if (partes.length === 1) return partes[0].slice(0, 2).toUpperCase();
  return (partes[0][0] + partes[partes.length - 1][0]).toUpperCase();
};

const isEventoCorridaOuSolidario = computed(() => {
  return Boolean(
    eventoInfo.value?.inscricao_solidaria ||
    campeonato.value?.formato === 'corrida' ||
    campeonato.value?.inscricao_solidaria
  );
});

const categoriasResultadosNomes = computed(() => {
  const set = new Set();
  resultadosCorrida.value.forEach((r) => {
    if (r.categoria_nome) set.add(r.categoria_nome);
  });
  return Array.from(set);
});

const resultadosCorridaFiltrados = computed(() => {
  if (!filtroCategoriaResultado.value) return resultadosCorrida.value;
  return resultadosCorrida.value.filter((r) => r.categoria_nome === filtroCategoriaResultado.value);
});

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

    if (data?.slug && String(route.params.id) !== String(data.slug)) {
      router.replace({ name: 'PortalCampeonato', params: { id: data.slug } });
    }
    
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
    loadInscritosPublicos();
    loadEventoEResultadosCorrida();

  } catch (e) {
    console.error('Erro ao carregar tabela', e);
  } finally {
    loading.value = false;
  }
};

const loadInscritosPublicos = async () => {
  loadingInscritos.value = true;
  try {
    const { data } = await portalService.getCampeonatoInscritos(route.params.id);
    totalInscritos.value = Number(data?.total || 0);
    ultimosInscritos.value = Array.isArray(data?.ultimos) ? data.ultimos : [];
    inscritosLista.value = Array.isArray(data?.inscritos) ? data.inscritos : [];
  } catch (e) {
    console.error('Erro ao carregar inscritos públicos', e);
  } finally {
    loadingInscritos.value = false;
  }
};

const loadEventoEResultadosCorrida = async () => {
  try {
    const cfgResp = await inscricaoService.getCamposCadastro(route.params.id);
    eventoInfo.value = cfgResp.data?.data?.campeonato || null;
  } catch {
    eventoInfo.value = null;
  }

  if (eventoInfo.value?.inscricao_solidaria || campeonato.value?.formato === 'corrida') {
    loadingResultadosCorrida.value = true;
    try {
      const resResp = await inscricaoService.getResultadosPublicos(route.params.id);
      resultadosCorrida.value = resResp.data?.data?.resultados || [];
      activeTab.value = 'resultados_corrida';
    } catch (e) {
      console.error('Erro ao carregar resultados da corrida', e);
    } finally {
      loadingResultadosCorrida.value = false;
    }
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
  const match = String(dateString).match(/^(\d{4})-(\d{2})-(\d{2})/);
  if (match) return `${match[3]}/${match[2]}/${match[1]}`;
  return new Date(dateString).toLocaleDateString('pt-BR');
};

const formatPeriodo = (inicio, fim) => {
  const d1 = formatDate(inicio);
  const d2 = formatDate(fim);
  if (!d2 || d1 === d2) return d1;
  return `${d1} a ${d2}`;
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

<style scoped>
.event-detail-page {
  --ease-out: cubic-bezier(0.23, 1, 0.32, 1);
}

.pressable {
  transition:
    transform 160ms var(--ease-out),
    background-color 160ms ease,
    border-color 160ms ease,
    color 160ms ease;
}

.pressable:active {
  transform: scale(0.97);
}

.marquee-animate {
  animation: marquee-inscritos 28s linear infinite;
}

.marquee-container:hover .marquee-animate {
  animation-play-state: paused;
}

@keyframes marquee-inscritos {
  0% {
    transform: translateX(0);
  }
  100% {
    transform: translateX(-50%);
  }
}

@media (prefers-reduced-motion: reduce) {
  .pressable {
    transition: none;
  }
  .pressable:active {
    transform: none;
  }
  .marquee-animate {
    animation: none;
  }
}
</style>
