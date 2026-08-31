<template>
  <div class="p-6">
    <div class="mb-6 flex justify-between items-center">
      <h1 class="text-2xl font-bold text-gray-800">Tabela de Jogos</h1>
      <button 
        @click="$router.go(-1)"
        class="text-gray-600 hover:text-gray-800"
      >
        Voltar
      </button>
    </div>

    <div v-if="loading" class="text-center py-8">
      <span class="text-gray-500">Carregando tabela...</span>
    </div>

    <div v-else-if="error" class="bg-red-100 text-red-700 p-4 rounded mb-4">
      {{ error }}
    </div>

    <div v-else>
      <div v-if="!store.temTabela" class="bg-white rounded-lg shadow p-8 text-center max-w-2xl mx-auto">
        <h2 class="text-xl font-semibold mb-4">Nenhuma tabela gerada</h2>
        <p class="text-gray-600 mb-6">Escolha o formato da competição para gerar os confrontos automaticamente.</p>
        
        <div v-if="formatosDisponiveis.length > 0 && !mostrarConfigGruposManual && !mostrarEscolhaModoGrupos" class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <button 
            v-for="formato in formatosDisponiveis"
            :key="formato.id"
            @click="selecionarFormato(formato.id)"
            class="p-4 border rounded-lg hover:bg-blue-50 hover:border-blue-500 transition-colors flex flex-col items-center gap-2"
          >
            <span class="text-2xl">{{ getIcon(formato.id) }}</span>
            <span class="font-bold">{{ formato.nome }}</span>
            <span class="text-xs text-gray-500">{{ getDescricao(formato.id) }}</span>
          </button>
        </div>
        <div v-if="mostrarEscolhaModoGrupos" class="max-w-2xl mx-auto border rounded-lg p-5 text-left">
          <h3 class="font-semibold text-gray-800 mb-3">Fase de Grupos: escolha o modo</h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <button
              @click="gerar('grupos')"
              class="p-4 rounded border border-indigo-300 bg-indigo-50 hover:bg-indigo-100 text-left"
            >
              <p class="font-semibold text-indigo-900">Automático</p>
              <p class="text-xs text-indigo-700 mt-1">Mantém o comportamento atual de sorteio automático.</p>
            </button>
            <button
              @click="iniciarConfiguracaoManualGrupos"
              class="p-4 rounded border border-emerald-300 bg-emerald-50 hover:bg-emerald-100 text-left"
            >
              <p class="font-semibold text-emerald-900">Manual</p>
              <p class="text-xs text-emerald-700 mt-1">Você define grupos, nomes e times de cada grupo.</p>
            </button>
          </div>
          <div class="mt-4 flex justify-end">
            <button
              @click="cancelarFluxoGrupos"
              class="px-3 py-2 rounded border border-gray-300 text-gray-700 hover:bg-gray-50 text-sm"
            >
              Voltar
            </button>
          </div>
        </div>
        <div v-if="mostrarConfigGruposManual" class="max-w-4xl mx-auto border rounded-lg p-5 text-left">
          <h3 class="font-semibold text-gray-800 mb-3">Configuração Manual de Grupos</h3>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-3 items-end mb-4">
            <div>
              <label class="block text-xs text-gray-500 mb-1">Quantidade de grupos</label>
              <input
                v-model.number="quantidadeGruposManual"
                type="number"
                min="1"
                max="16"
                class="border border-gray-300 rounded px-3 py-2 w-full"
              >
            </div>
            <div class="md:col-span-2 flex gap-2">
              <button
                @click="gerarEstruturaGruposManuais"
                class="px-3 py-2 rounded bg-gray-100 hover:bg-gray-200 text-sm"
              >
                Criar Grupos
              </button>
              <button
                @click="cancelarFluxoGrupos"
                class="px-3 py-2 rounded border border-gray-300 text-gray-700 hover:bg-gray-50 text-sm"
              >
                Cancelar
              </button>
            </div>
          </div>

          <div v-if="gruposManuais.length" class="space-y-3">
            <div
              v-for="(grupo, grupoIndex) in gruposManuais"
              :key="grupo.local_id"
              class="border rounded p-3 bg-gray-50"
            >
              <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                  <label class="block text-xs text-gray-500 mb-1">Nome do grupo</label>
                  <input
                    v-model="grupo.nome"
                    type="text"
                    class="border border-gray-300 rounded px-3 py-2 w-full"
                    placeholder="Ex: Grupo A"
                  >
                </div>
                <div>
                  <label class="block text-xs text-gray-500 mb-1">Times do grupo</label>
                  <select
                    v-model="grupo.equipe_ids"
                    multiple
                    class="border border-gray-300 rounded px-2 py-2 w-full min-h-[110px]"
                    @change="garantirTimesUnicosNosGrupos(grupoIndex)"
                  >
                    <option
                      v-for="eq in store.equipes || []"
                      :key="eq.id"
                      :value="String(eq.id)"
                    >
                      {{ eq.nome }}
                    </option>
                  </select>
                </div>
              </div>
            </div>

            <div class="flex justify-end">
              <button
                @click="salvarConfiguracaoManualGrupos"
                class="px-4 py-2 rounded bg-emerald-600 text-white hover:bg-emerald-700 disabled:opacity-50"
                :disabled="salvandoConfigGruposManual"
              >
                {{ salvandoConfigGruposManual ? 'Salvando...' : 'Salvar Grupos e Ir para Gerenciador' }}
              </button>
            </div>
          </div>
        </div>
        <div v-if="formatosDisponiveis.length === 0 && !mostrarConfigGruposManual && !mostrarEscolhaModoGrupos" class="text-center text-gray-500 p-4">
            <span v-if="store.loading">Carregando formatos...</span>
            <span v-else>Nenhum formato disponível para esta modalidade.</span>
        </div>
      </div>

      <div v-else>
        <div class="mb-4">
          <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm font-medium">
            Formato: {{ formatFormato(formatoEfetivo) }}
          </span>
        </div>

        <ChaveamentoBracket 
          v-if="formatoEfetivo === 'mata_mata'" 
          :fases="store.fases" 
        />

        <GrupoTable 
          v-else-if="formatoEfetivo === 'grupos'" 
          :partidas="getTodasPartidas(store.fases)" 
        />

        <div v-else-if="formatoEfetivo === 'pontos_corridos'" class="space-y-4">
            <GrupoTable :partidas="getTodasPartidas(store.fases)" />
        </div>

        <CorridaManager
          v-else-if="formatoEfetivo === 'corrida'"
          :inscricoes="store.inscricoes"
          :campeonato-id="store.campeonato.id"
        />
        
        <div v-else class="text-gray-500">
            Formato não suportado ou desconhecido.
        </div>
      </div>

      <div class="bg-white rounded-lg shadow p-6 mt-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-4">
          <h2 class="text-xl font-semibold text-gray-800">Editor Avançado de Fases</h2>
          <div class="flex gap-2">
            <button
              @click="carregarEstrutura"
              class="px-3 py-2 rounded border border-gray-300 text-gray-700 hover:bg-gray-50"
              :disabled="loadingEstrutura"
            >
              {{ loadingEstrutura ? 'Carregando...' : 'Recarregar' }}
            </button>
            <button
              @click="adicionarFase"
              class="px-3 py-2 rounded bg-gray-100 text-gray-800 hover:bg-gray-200"
            >
              Nova Fase
            </button>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-4">
          <select
            v-model="faseSugestaoId"
            class="border border-gray-300 rounded px-3 py-2"
          >
            <option :value="null">Selecione uma fase para gerar jogos</option>
            <option v-for="fase in estrutura.fases" :key="fase.local_id" :value="fase.id">
              {{ fase.nome || 'Fase sem nome' }}
            </option>
          </select>

          <button
            @click="sugerirConfrontos"
            class="px-3 py-2 rounded bg-indigo-600 text-white hover:bg-indigo-700 disabled:opacity-50"
            :disabled="sugerindoConfrontos || !faseSugestaoId"
          >
            {{ sugerindoConfrontos ? 'Gerando...' : 'Gerar Jogos Automáticos' }}
          </button>

          <button
            @click="resolverClassificados"
            class="px-3 py-2 rounded bg-purple-600 text-white hover:bg-purple-700 disabled:opacity-50"
            :disabled="resolvendoClassificados"
          >
            {{ resolvendoClassificados ? 'Processando...' : 'Preencher Classificados' }}
          </button>

          <button
            @click="salvarEstrutura"
            class="px-3 py-2 rounded bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-50"
            :disabled="salvandoEstrutura"
          >
            {{ salvandoEstrutura ? 'Salvando...' : 'Salvar Alterações' }}
          </button>
        </div>

        <div v-if="estruturaErro" class="mb-4 bg-red-100 text-red-700 rounded p-3">
          {{ estruturaErro }}
        </div>

        <div v-if="!estrutura.fases.length" class="text-gray-500 border border-dashed rounded p-6 text-center">
          Nenhuma fase configurada no editor.
        </div>

        <div v-for="(fase, faseIndex) in estrutura.fases" :key="fase.local_id" class="border rounded-lg p-4 mb-4">
          <div class="grid grid-cols-1 md:grid-cols-5 gap-3">
            <div>
                <label class="block text-xs text-gray-500 mb-1">Nome da Fase</label>
                <input v-model="fase.nome" type="text" class="border border-gray-300 rounded px-3 py-2 w-full" placeholder="Nome da fase">
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Ordem (Sequência)</label>
                <input v-model.number="fase.ordem" type="number" min="1" class="border border-gray-300 rounded px-3 py-2 w-full" placeholder="Ordem">
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Tipo de Disputa</label>
                <select v-model="fase.tipo" class="border border-gray-300 rounded px-3 py-2 w-full">
                    <option value="custom">Personalizado</option>
                    <option value="grupos">Fase de Grupos</option>
                    <option value="mata_mata">Mata-mata (Eliminatória)</option>
                    <option value="pontos_corridos">Pontos Corridos</option>
                </select>
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Status</label>
                <select v-model="fase.status" class="border border-gray-300 rounded px-3 py-2 w-full">
                    <option value="planejada">Planejada (Futura)</option>
                    <option value="ativa">Ativa (Em Andamento)</option>
                    <option value="encerrada">Encerrada</option>
                </select>
            </div>
            <div class="flex items-end">
                <button @click="removerFase(faseIndex)" class="px-3 py-2 w-full rounded bg-red-100 text-red-700 hover:bg-red-200">
                    Remover Fase
                </button>
            </div>
          </div>

          <div class="mt-3">
            <label class="flex items-center gap-2 text-sm text-gray-700">
              <input type="checkbox" v-model="fase.permite_edicao_manual">
              Permite edição manual
            </label>
            <label class="mt-2 flex items-center gap-2 text-sm text-gray-700">
              <input
                type="checkbox"
                :checked="faseZeraCartoesNaFase(fase)"
                @change="setFaseZeraCartoesNaFase(fase, $event.target.checked)"
              >
              Zerar cartões ao iniciar esta fase
            </label>
            <p class="text-xs text-gray-500 mt-1">
              Útil para semifinais/finais quando o regulamento reinicia os cartões acumulados.
            </p>
          </div>

          <div class="mt-4">
            <div class="flex items-center justify-between mb-2">
              <h3 class="font-semibold text-gray-700">Classificação Atual</h3>
              <button
                @click="carregarClassificacao(fase)"
                class="px-2 py-1 rounded bg-indigo-100 hover:bg-indigo-200 text-indigo-700 text-sm"
                :disabled="!fase.id"
              >
                Atualizar Classificação
              </button>
            </div>
            
            <div v-if="fase.classificacao && Object.keys(fase.classificacao).length">
              <div v-for="(tabela, grupoNome) in fase.classificacao" :key="grupoNome" class="mb-3">
                <h4 class="text-sm font-bold text-gray-600 mb-1">{{ grupoNome !== 'Geral' ? 'Grupo ' + grupoNome : 'Classificação Geral' }}</h4>
                <div class="overflow-x-auto border rounded">
                  <table class="min-w-full divide-y divide-gray-200 text-xs">
                    <thead class="bg-gray-50">
                      <tr>
                        <th class="px-2 py-1 text-left">Pos</th>
                        <th class="px-2 py-1 text-left">Equipe</th>
                        <th v-if="grupoNome === 'Geral'" class="px-2 py-1 text-center">Grupo</th>
                        <th class="px-2 py-1 text-center">P</th>
                        <th class="px-2 py-1 text-center">J</th>
                        <th class="px-2 py-1 text-center">V</th>
                        <th class="px-2 py-1 text-center">E</th>
                        <th class="px-2 py-1 text-center">D</th>
                        <th class="px-2 py-1 text-center">SG</th>
                      </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                      <tr v-for="time in tabela" :key="time.id">
                        <td class="px-2 py-1 font-bold">{{ time.posicao }}º</td>
                        <td class="px-2 py-1">{{ time.nome }}</td>
                        <td v-if="grupoNome === 'Geral'" class="px-2 py-1 text-center font-semibold text-gray-500">{{ time.grupo || '-' }}</td>
                        <td class="px-2 py-1 text-center font-bold">{{ time.pontos }}</td>
                        <td class="px-2 py-1 text-center">{{ time.jogos }}</td>
                        <td class="px-2 py-1 text-center text-green-600">{{ time.vitorias }}</td>
                        <td class="px-2 py-1 text-center text-gray-500">{{ time.empates }}</td>
                        <td class="px-2 py-1 text-center text-red-500">{{ time.derrotas }}</td>
                        <td class="px-2 py-1 text-center">{{ time.saldo_gols }}</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
            <div v-else class="text-xs text-gray-500 italic">
              Nenhuma classificação calculada ainda. Clique em atualizar.
            </div>
          </div>

          <div class="mt-4">
            <div class="flex items-center justify-between mb-2">
              <h3 class="font-semibold text-gray-700">Grupos</h3>
              <button @click="adicionarGrupo(fase)" class="px-2 py-1 rounded bg-gray-100 hover:bg-gray-200 text-sm">
                Adicionar Grupo
              </button>
            </div>
            <div v-if="!fase.grupos.length" class="text-xs text-gray-500">Sem grupos cadastrados.</div>
            <div v-for="(grupo, grupoIndex) in fase.grupos" :key="grupo.local_id" class="grid grid-cols-1 md:grid-cols-4 gap-2 mb-2 items-end">
              <div class="col-span-2">
                <label class="block text-xs text-gray-500 mb-1">Nome do Grupo</label>
                <input v-model="grupo.nome" type="text" class="border border-gray-300 rounded px-3 py-2 w-full" placeholder="Ex: Grupo A">
              </div>
              <div class="col-span-2">
                <label class="block text-xs text-gray-500 mb-1">Times pré-definidos no grupo</label>
                <select
                  v-model="grupo.equipe_ids"
                  multiple
                  class="border border-gray-300 rounded px-2 py-2 w-full min-h-[88px]"
                >
                  <option v-for="eq in store.equipes || []" :key="eq.id" :value="String(eq.id)">
                    {{ eq.nome }}
                  </option>
                </select>
              </div>
              <button @click="removerGrupo(fase, grupoIndex)" class="px-2 py-2 rounded bg-red-100 text-red-700 hover:bg-red-200 h-[42px]">
                Remover
              </button>
            </div>
            <div v-for="grupo in fase.grupos" :key="`resumo_${grupo.local_id}`" class="text-xs text-gray-500 mb-2">
              {{ grupo.nome || 'Grupo sem nome' }}: {{ listarNomesTimesDoGrupo(grupo) || 'Nenhum time definido' }}
            </div>
          </div>

          <div class="mt-4">
            <div class="flex items-center justify-between mb-2">
              <h3 class="font-semibold text-gray-700">Regras de Classificação</h3>
              <div class="flex gap-2">
                <button @click="adicionarRegraClassificacao(fase)" class="px-2 py-1 rounded bg-gray-100 hover:bg-gray-200 text-sm">
                  Adicionar Critério
                </button>
                <button
                  @click="salvarRegrasClassificacao(fase)"
                  class="px-2 py-1 rounded bg-emerald-600 text-white hover:bg-emerald-700 text-sm disabled:opacity-50"
                  :disabled="!fase.id || fase.salvando_regras"
                >
                  {{ fase.salvando_regras ? 'Salvando...' : 'Salvar Regras' }}
                </button>
              </div>
            </div>
            <p class="text-xs text-gray-500 mb-2">Defina a ordem dos critérios de desempate (quem fica em 1º lugar na tabela).</p>
            <div v-if="!fase.regras_classificacao.length" class="text-xs text-gray-500">Sem regras cadastradas.</div>
            <div v-for="(regra, regraIndex) in fase.regras_classificacao" :key="regra.local_id" class="grid grid-cols-1 md:grid-cols-4 gap-2 mb-2 items-center">
              <div class="col-span-2 flex items-center gap-2">
                <span class="text-sm font-bold text-gray-400 w-6">{{ regraIndex + 1 }}º</span>
                <select v-model="regra.criterio" class="border border-gray-300 rounded px-3 py-2 w-full">
                  <option value="">Selecione o critério</option>
                  <option v-for="criterio in criteriosClassificacao" :key="criterio" :value="criterio">{{ formatCriterio(criterio) }}</option>
                </select>
              </div>
              <input v-model.number="regra.ordem" type="number" min="1" class="hidden border border-gray-300 rounded px-3 py-2" placeholder="Ordem">
              <!-- <input v-model="regra.configuracao_texto" type="text" class="border border-gray-300 rounded px-3 py-2" placeholder='Configuração JSON'> -->
              <button @click="removerRegraClassificacao(fase, regraIndex)" class="px-2 py-2 rounded bg-red-100 text-red-700 hover:bg-red-200 text-sm col-span-1 md:col-span-1">
                Remover
              </button>
            </div>
          </div>

          <div class="mt-4">
            <div class="flex items-center justify-between mb-2">
              <h3 class="font-semibold text-gray-700">Confrontos (Jogos)</h3>
              <button @click="adicionarPartida(fase)" class="px-2 py-1 rounded bg-gray-100 hover:bg-gray-200 text-sm">
                Adicionar Confronto
              </button>
            </div>
            <div v-if="!fase.partidas.length" class="text-xs text-gray-500">Sem confrontos cadastrados.</div>
            <div v-for="(partida, partidaIndex) in fase.partidas" :key="partida.local_id" class="border rounded p-3 mb-3 bg-gray-50">
              <!-- Cabeçalho do Confronto (Ordem e Status) -->
              <div class="flex items-center gap-3 mb-3 border-b pb-2">
                <div class="w-24">
                  <label class="block text-xs font-bold text-gray-600 mb-1">Nº do Jogo</label>
                  <input v-model.number="partida.ordem_na_fase" type="number" min="1" class="border border-gray-300 rounded px-2 py-1 w-full text-sm">
                </div>
                
                <!-- Seleção de Grupo (Se houver grupos na fase) -->
                <div v-if="fase.grupos && fase.grupos.length > 0" class="w-40">
                    <label class="block text-xs font-bold text-gray-600 mb-1">Grupo</label>
                    <select v-model="partida.fase_grupo_id" class="border border-gray-300 rounded px-2 py-1 w-full text-sm">
                        <option :value="null">Geral</option>
                        <option v-for="gp in fase.grupos" :key="gp.local_id" :value="gp.id || gp.local_id">
                            {{ gp.nome }}
                        </option>
                    </select>
                </div>

                <div class="flex-1">
                  <label class="block text-xs font-bold text-gray-600 mb-1">Status</label>
                  <select v-model="partida.status" class="border border-gray-300 rounded px-2 py-1 w-full text-sm">
                    <option value="agendada">Agendada (A realizar)</option>
                    <option value="em_andamento">Em andamento (Ao vivo)</option>
                    <option value="finalizada">Finalizada (Encerrada)</option>
                  </select>
                </div>
                <div class="flex-1">
                  <label class="block text-xs font-bold text-gray-600 mb-1">Data/Hora</label>
                  <input
                    v-model="partida.data_jogo"
                    type="datetime-local"
                    step="60"
                    @change="aplicarMascaraDataHoraPartida(partida)"
                    @blur="aplicarMascaraDataHoraPartida(partida)"
                    :class="[
                      'border rounded px-2 py-1 w-full text-sm',
                      partidaSemDataHora(partida) ? 'border-red-400 bg-red-50' : 'border-gray-300'
                    ]"
                  >
                  <p v-if="partidaSemDataHora(partida)" class="mt-1 text-[11px] font-semibold text-red-600">
                    Data/Hora é obrigatória para partida agendada.
                  </p>
                </div>
                <div class="flex-1">
                  <label class="block text-xs font-bold text-gray-600 mb-1">Local</label>
                  <input v-model="partida.local" type="text" class="border border-gray-300 rounded px-2 py-1 w-full text-sm" placeholder="Estádio/Campo">
                </div>
                <div class="flex-1">
                  <label class="block text-xs font-bold text-gray-600 mb-1">Árbitro</label>
                  <select v-model="partida.arbitro_id" class="border border-gray-300 rounded px-2 py-1 w-full text-sm">
                    <option :value="null">Sem Árbitro</option>
                    <option v-for="arbitro in arbitros" :key="arbitro.id" :value="arbitro.id">
                      {{ arbitro.name }}
                    </option>
                  </select>
                </div>
                <div class="self-end">
                  <button @click="removerPartida(fase, partidaIndex)" class="text-red-600 hover:text-red-800 text-xs font-bold px-2 py-1">
                    Excluir Jogo
                  </button>
                </div>
              </div>

              <!-- Definição dos Times (Versão Simplificada) -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-start">
                
                <!-- Time da Casa (Mandante) -->
                <div class="bg-white p-3 rounded border border-gray-200">
                    <h4 class="font-bold text-sm text-gray-700 mb-2 text-center border-b pb-1">Time da Casa (Mandante)</h4>
                    
                    <!-- Seleção do Tipo de Origem -->
                    <label class="block text-xs text-gray-500 mb-1">Quem joga aqui?</label>
                    <select
                        :value="partida.origem_mandante.type"
                        @change="alterarTipoOrigem(partida, 'mandante', $event.target.value)"
                        class="border border-blue-200 bg-blue-50 rounded px-2 py-1 w-full text-sm mb-2 font-medium text-blue-800"
                    >
                        <option v-for="tipo in origemTipos" :key="tipo.value" :value="tipo.value">{{ tipo.label }}</option>
                    </select>

                    <!-- Opções Específicas Baseadas na Escolha -->
                    
                    <!-- 1. Equipe Específica (Select simples) -->
                    <div v-if="partida.origem_mandante.type === 'EQUIPE'">
                        <select
                            :value="getCampoOrigem(partida, 'mandante', 'id', '')"
                            @change="setCampoOrigem(partida, 'mandante', 'id', $event.target.value)"
                            class="border border-gray-300 rounded px-2 py-1 w-full text-sm"
                        >
                            <option value="">Selecione a equipe...</option>
                            <option v-for="eq in store.equipes || []" :key="eq.id" :value="eq.id">{{ eq.nome }}</option>
                        </select>
                    </div>

                    <!-- 2. Classificado de Grupo -->
                    <div v-else-if="partida.origem_mandante.type === 'GROUP_POSITION'" class="flex gap-2">
                        <select
                            :value="getCampoOrigem(partida, 'mandante', 'group', '')"
                            @change="setCampoOrigem(partida, 'mandante', 'group', $event.target.value)"
                            class="border border-gray-300 rounded px-2 py-1 w-full text-sm flex-1"
                        >
                            <option value="">Qual Grupo?</option>
                            <option v-for="grupo in getTodosGrupos(estrutura.fases)" :key="'all_'+grupo.nome" :value="grupo.nome">{{ grupo.nome }}</option>
                        </select>
                        <input
                            :value="getCampoOrigem(partida, 'mandante', 'position', 1)"
                            @input="setCampoOrigem(partida, 'mandante', 'position', $event.target.value)"
                            type="number" min="1" max="10"
                            class="border border-gray-300 rounded px-2 py-1 w-16 text-sm text-center"
                            placeholder="Pos"
                            title="Posição (ex: 1º)"
                        >
                    </div>

                    <!-- 3. Vencedor/Perdedor de Jogo Anterior -->
                    <div v-else-if="['MATCH_WINNER', 'MATCH_LOSER'].includes(partida.origem_mandante.type)">
                        <select
                            :value="getCampoOrigem(partida, 'mandante', 'match_id', '')"
                            @change="setCampoOrigem(partida, 'mandante', 'match_id', $event.target.value)"
                            class="border border-gray-300 rounded px-2 py-1 w-full text-sm"
                        >
                            <option value="">Selecione o Jogo Anterior...</option>
                            <option
                                v-for="partidaRef in getPartidasElegiveis(estrutura.fases, partida)"
                                :key="partidaRef.local_id"
                                :value="partidaRef.id"
                            >
                                {{ getLabelPartida(partidaRef) }}
                            </option>
                        </select>
                    </div>

                    <!-- 4. Melhor 3º Colocado -->
                    <div v-else-if="partida.origem_mandante.type === 'BEST_THIRD'">
                        <select
                            :value="getCampoOrigem(partida, 'mandante', 'ranking_index', 1)"
                            @change="setCampoOrigem(partida, 'mandante', 'ranking_index', $event.target.value)"
                            class="border border-gray-300 rounded px-2 py-1 w-full text-sm"
                        >
                            <option value="1">1º Melhor Colocado</option>
                            <option value="2">2º Melhor Colocado</option>
                            <option value="3">3º Melhor Colocado</option>
                            <option value="4">4º Melhor Colocado</option>
                        </select>
                    </div>
                </div>

                <!-- Time Visitante (Fora) -->
                <div class="bg-white p-3 rounded border border-gray-200">
                    <h4 class="font-bold text-sm text-gray-700 mb-2 text-center border-b pb-1">Time Visitante (Fora)</h4>
                    
                    <label class="block text-xs text-gray-500 mb-1">Quem joga aqui?</label>
                    <select
                        :value="partida.origem_visitante.type"
                        @change="alterarTipoOrigem(partida, 'visitante', $event.target.value)"
                        class="border border-blue-200 bg-blue-50 rounded px-2 py-1 w-full text-sm mb-2 font-medium text-blue-800"
                    >
                        <option v-for="tipo in origemTipos" :key="tipo.value" :value="tipo.value">{{ tipo.label }}</option>
                    </select>

                    <!-- Lógica Visitante (Espelho do Mandante) -->
                    <div v-if="partida.origem_visitante.type === 'EQUIPE'">
                        <select
                            :value="getCampoOrigem(partida, 'visitante', 'id', '')"
                            @change="setCampoOrigem(partida, 'visitante', 'id', $event.target.value)"
                            class="border border-gray-300 rounded px-2 py-1 w-full text-sm"
                        >
                            <option value="">Selecione a equipe...</option>
                            <option v-for="eq in store.equipes || []" :key="eq.id" :value="eq.id">{{ eq.nome }}</option>
                        </select>
                    </div>

                    <div v-else-if="partida.origem_visitante.type === 'GROUP_POSITION'" class="flex gap-2">
                        <select
                            :value="getCampoOrigem(partida, 'visitante', 'group', '')"
                            @change="setCampoOrigem(partida, 'visitante', 'group', $event.target.value)"
                            class="border border-gray-300 rounded px-2 py-1 w-full text-sm flex-1"
                        >
                            <option value="">Qual Grupo?</option>
                            <option v-for="grupo in getTodosGrupos(estrutura.fases)" :key="'all_'+grupo.nome" :value="grupo.nome">{{ grupo.nome }}</option>
                        </select>
                        <input
                            :value="getCampoOrigem(partida, 'visitante', 'position', 1)"
                            @input="setCampoOrigem(partida, 'visitante', 'position', $event.target.value)"
                            type="number" min="1" max="10"
                            class="border border-gray-300 rounded px-2 py-1 w-16 text-sm text-center"
                            placeholder="Pos"
                            title="Posição (ex: 2º)"
                        >
                    </div>

                    <div v-else-if="['MATCH_WINNER', 'MATCH_LOSER'].includes(partida.origem_visitante.type)">
                        <select
                            :value="getCampoOrigem(partida, 'visitante', 'match_id', '')"
                            @change="setCampoOrigem(partida, 'visitante', 'match_id', $event.target.value)"
                            class="border border-gray-300 rounded px-2 py-1 w-full text-sm"
                        >
                            <option value="">Selecione o Jogo Anterior...</option>
                            <option
                                v-for="partidaRef in getPartidasElegiveis(estrutura.fases, partida)"
                                :key="partidaRef.local_id"
                                :value="partidaRef.id"
                            >
                                {{ getLabelPartida(partidaRef) }}
                            </option>
                        </select>
                    </div>

                    <div v-else-if="partida.origem_visitante.type === 'BEST_THIRD'">
                        <select
                            :value="getCampoOrigem(partida, 'visitante', 'ranking_index', 1)"
                            @change="setCampoOrigem(partida, 'visitante', 'ranking_index', $event.target.value)"
                            class="border border-gray-300 rounded px-2 py-1 w-full text-sm"
                        >
                            <option value="1">1º Melhor Colocado</option>
                            <option value="2">2º Melhor Colocado</option>
                            <option value="3">3º Melhor Colocado</option>
                            <option value="4">4º Melhor Colocado</option>
                        </select>
                    </div>
                </div>

              </div>
              
              <!-- Checkbox Manual (Oculto pois a UI já trata isso via 'Equipe Específica') -->
              <!-- <div class="mt-2 text-xs">
                 <label><input type="checkbox" v-model="partida.definido_manualmente"> Definido manualmente</label>
              </div> -->
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { onMounted, computed, ref } from 'vue';
import { useRoute } from 'vue-router';
import { useTabelaStore } from '../store/tabelaStore';
import campeonatoService from '@/modules/campeonatos/services/campeonatoService';
import ChaveamentoBracket from '../components/ChaveamentoBracket.vue';
import GrupoTable from '../components/GrupoTable.vue';
import CorridaManager from '../components/CorridaManager.vue';

export default {
  components: { ChaveamentoBracket, GrupoTable, CorridaManager },
  setup() {
    const route = useRoute();
    const store = useTabelaStore();
    const campeonatoId = route.params.id;
    const formatosDisponiveis = ref([]);
    const loadingEstrutura = ref(false);
    const salvandoEstrutura = ref(false);
    const sugerindoConfrontos = ref(false);
    const resolvendoClassificados = ref(false);
    const mostrarEscolhaModoGrupos = ref(false);
    const mostrarConfigGruposManual = ref(false);
    const quantidadeGruposManual = ref(4);
    const gruposManuais = ref([]);
    const salvandoConfigGruposManual = ref(false);
    const estruturaErro = ref('');
    const faseSugestaoId = ref(null);
    const criteriosClassificacao = ref([]);
    const arbitros = ref([]);
    const origemTipos = [
      { value: 'MANUAL', label: 'Manual (A definir)' },
      { value: 'EQUIPE', label: 'Equipe Específica' },
      { value: 'GROUP_POSITION', label: 'Classificado de Grupo' },
      { value: 'MATCH_WINNER', label: 'Vencedor de Jogo' },
      { value: 'MATCH_LOSER', label: 'Perdedor de Jogo' },
      { value: 'BEST_THIRD', label: 'Melhor 3º Colocado' },
      // { value: 'BEST_LOSER', label: 'Melhor Perdedor' },
      { value: 'BYE', label: 'Classificado Direto (BYE)' }
    ];
    const estrutura = ref({
      fases: []
    });

    const carregarArbitros = async () => {
      try {
        const response = await campeonatoService.getArbitros();
        arbitros.value = response.data || [];
      } catch (error) {
        console.error('Erro ao carregar árbitros:', error);
      }
    };

    onMounted(async () => {
      await store.fetchTabela(campeonatoId);
      // Carregar equipes do campeonato para os selects de confrontos manuais
      await store.fetchEquipes(campeonatoId);
      await carregarCriteriosClassificacao();
      await carregarArbitros();
      await carregarEstrutura();
      
      if (!store.temTabela) {
        try {
            const res = await campeonatoService.getFormatos();
            let formatos = res.data;
            const modalidadeNome = store.campeonato?.modalidade?.nome?.toLowerCase() || '';
            if (modalidadeNome.includes('corrida') || modalidadeNome.includes('maratona') || modalidadeNome.includes('atletismo')) {
                formatos = formatos.filter(f => f.id === 'corrida');
            } else {
                formatos = formatos.filter(f => f.id !== 'corrida');
            }

            formatosDisponiveis.value = formatos;
        } catch (error) {
            console.error("Erro ao carregar formatos:", error);
            formatosDisponiveis.value = [
                { id: 'mata_mata', nome: 'Mata-Mata' },
                { id: 'grupos', nome: 'Fase de Grupos' },
                { id: 'pontos_corridos', nome: 'Pontos Corridos' },
                { id: 'corrida', nome: 'Corrida / Maratona' }
            ];
        }
      }
    });

    const gerar = async (formato) => {
      if (!confirm(`Deseja gerar a tabela no formato ${formato}? Isso não pode ser desfeito.`)) return;
      await store.gerarTabela(campeonatoId, formato);
      cancelarFluxoGrupos();
      await carregarEstrutura();
    };

    const selecionarFormato = async (formato) => {
      if (formato !== 'grupos') {
        await gerar(formato);
        return;
      }
      mostrarEscolhaModoGrupos.value = true;
      mostrarConfigGruposManual.value = false;
    };

    const cancelarFluxoGrupos = () => {
      mostrarEscolhaModoGrupos.value = false;
      mostrarConfigGruposManual.value = false;
      quantidadeGruposManual.value = 4;
      gruposManuais.value = [];
    };

    const criarGrupoManual = (index) => ({
      id: null,
      local_id: criarIdLocal(),
      nome: `Grupo ${String.fromCharCode(65 + index)}`,
      ordem: index + 1,
      equipe_ids: []
    });

    const iniciarConfiguracaoManualGrupos = () => {
      mostrarEscolhaModoGrupos.value = false;
      mostrarConfigGruposManual.value = true;
      if (!gruposManuais.value.length) {
        gruposManuais.value = Array.from({ length: quantidadeGruposManual.value || 1 }, (_, index) => criarGrupoManual(index));
      }
    };

    const gerarEstruturaGruposManuais = () => {
      const quantidade = Math.max(1, Number(quantidadeGruposManual.value || 1));
      gruposManuais.value = Array.from({ length: quantidade }, (_, index) => {
        const existente = gruposManuais.value[index];
        if (existente) {
          return {
            ...existente,
            ordem: index + 1,
            nome: existente.nome || `Grupo ${String.fromCharCode(65 + index)}`
          };
        }
        return criarGrupoManual(index);
      });
    };

    const garantirTimesUnicosNosGrupos = (grupoAtualIndex) => {
      const grupoAtual = gruposManuais.value[grupoAtualIndex];
      if (!grupoAtual) return;
      const selecionados = new Set((grupoAtual.equipe_ids || []).map(String));
      gruposManuais.value.forEach((grupo, index) => {
        if (index === grupoAtualIndex) return;
        grupo.equipe_ids = (grupo.equipe_ids || []).filter((id) => !selecionados.has(String(id)));
      });
    };

    const listarNomesTimesDoGrupo = (grupo) => {
      const ids = (grupo?.equipe_ids || []).map((id) => Number(id)).filter(Boolean);
      if (!ids.length) return '';
      const nomes = ids
        .map((id) => (store.equipes || []).find((eq) => Number(eq.id) === Number(id))?.nome)
        .filter(Boolean);
      return nomes.join(', ');
    };

    const salvarConfiguracaoManualGrupos = async () => {
      if (!gruposManuais.value.length) {
        estruturaErro.value = 'Crie ao menos um grupo para seguir.';
        return;
      }
      salvandoConfigGruposManual.value = true;
      estruturaErro.value = '';
      try {
        const payload = {
          fases: [
            {
              nome: 'Fase de Grupos',
              ordem: 1,
              tipo: 'grupos',
              status: 'planejada',
              permite_edicao_manual: true,
              configuracao: null,
              grupos: gruposManuais.value.map((grupo, index) => ({
                nome: grupo.nome || `Grupo ${String.fromCharCode(65 + index)}`,
                ordem: index + 1,
                configuracao: {
                  equipe_ids: (grupo.equipe_ids || []).map((id) => Number(id)).filter(Boolean)
                }
              })),
              partidas: []
            }
          ]
        };

        await campeonatoService.saveEstrutura(campeonatoId, payload);
        await carregarEstrutura();
        await store.fetchTabela(campeonatoId);
        cancelarFluxoGrupos();
      } catch (error) {
        estruturaErro.value = error.response?.data?.message || 'Erro ao salvar configuração manual de grupos.';
      } finally {
        salvandoConfigGruposManual.value = false;
      }
    };

    const formatFormato = (formato) => {
      const map = {
        'mata_mata': 'Mata-Mata',
        'grupos': 'Fase de Grupos',
        'pontos_corridos': 'Pontos Corridos',
        'corrida': 'Corrida / Maratona'
      };
      return map[formato] || formato;
    };
    
    const getIcon = (id) => {
        const map = {
            'mata_mata': '🥊',
            'grupos': '🏟',
            'pontos_corridos': '🏁',
            'corrida': '🏃'
        };
        return map[id] || '📋';
    };

    const getDescricao = (id) => {
        const map = {
            'mata_mata': 'Eliminatórias simples',
            'grupos': 'Grupos + Classificação',
            'pontos_corridos': 'Todos contra todos',
            'corrida': 'Ranking por tempo'
        };
        return map[id] || '';
    };

    const getTodasPartidas = (fases) => {
      return fases.flatMap(f => f.partidas || []);
    };

    // Helper para buscar todos os grupos de todas as fases para preencher selects
    const getTodosGrupos = (fases) => {
      return fases.flatMap(f => f.grupos || []);
    };

    const criarIdLocal = () => `tmp_${Date.now()}_${Math.random().toString(36).slice(2, 8)}`;

    const parseJsonSeguro = (valor, fallback = null) => {
      if (valor === null || valor === undefined || valor === '') return fallback;
      if (typeof valor === 'object') return valor;
      try {
        return JSON.parse(valor);
      } catch {
        return fallback;
      }
    };

    const formatarPayloadTexto = (valor) => {
      if (!valor) return '';
      if (typeof valor === 'string') return valor;
      return JSON.stringify(valor);
    };

    const getConfiguracaoFase = (fase) => {
      return parseJsonSeguro(fase?.configuracao_texto, {}) || {};
    };

    const faseZeraCartoesNaFase = (fase) => {
      const config = getConfiguracaoFase(fase);
      return !!config.zerar_cartoes_na_fase;
    };

    const setFaseZeraCartoesNaFase = (fase, value) => {
      const config = getConfiguracaoFase(fase);
      config.zerar_cartoes_na_fase = !!value;
      fase.configuracao_texto = formatarPayloadTexto(config);
    };

    const toDatetimeLocalInput = (valor) => {
      if (!valor) return '';
      const texto = String(valor).trim();
      if (!texto) return '';

      const isoComTimezone = /^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}(?::\d{2}(?:\.\d+)?)?(Z|[+-]\d{2}:\d{2})$/i;
      if (isoComTimezone.test(texto)) {
        const data = new Date(texto);
        if (Number.isNaN(data.getTime())) {
          return '';
        }
        const pad = (n) => String(n).padStart(2, '0');
        return `${data.getFullYear()}-${pad(data.getMonth() + 1)}-${pad(data.getDate())}T${pad(data.getHours())}:${pad(data.getMinutes())}`;
      }

      const matchSemTimezone = texto.match(/^(\d{4}-\d{2}-\d{2})[ T](\d{2}:\d{2})/);
      if (matchSemTimezone) {
        return `${matchSemTimezone[1]}T${matchSemTimezone[2]}`;
      }

      const data = new Date(texto);
      if (Number.isNaN(data.getTime())) return '';
      const pad = (n) => String(n).padStart(2, '0');
      return `${data.getFullYear()}-${pad(data.getMonth() + 1)}-${pad(data.getDate())}T${pad(data.getHours())}:${pad(data.getMinutes())}`;
    };

    const toApiDateTime = (valor) => {
      const local = toDatetimeLocalInput(valor);
      if (!local) return null;
      return `${local.replace('T', ' ')}:00`;
    };

    const aplicarMascaraDataHoraPartida = (partida) => {
      if (!partida) return;
      if (!partida.data_jogo) {
        partida.data_jogo = '';
        return;
      }
      partida.data_jogo = toDatetimeLocalInput(partida.data_jogo);
    };

    const partidaSemDataHora = (partida) => partida?.status === 'agendada' && !toDatetimeLocalInput(partida?.data_jogo);

    const getLabelPartida = (partida) => {
      const ordem = partida.ordem_na_fase ? `Jogo ${partida.ordem_na_fase}` : `Partida ${partida.id || 'nova'}`;
      const mandante = partida.mandante_id || '-';
      const visitante = partida.visitante_id || '-';
      return `${ordem} (${mandante} x ${visitante})`;
    };

    const getPartidasElegiveis = (fases, partidaAtual) => {
      const listaFases = Array.isArray(fases) ? fases : [];
      if (!listaFases.length || !partidaAtual) return [];

      const faseAtual = listaFases.find((fase) =>
        (fase.partidas || []).some(
          (partida) => partida.local_id === partidaAtual.local_id || (partida.id && partida.id === partidaAtual.id)
        )
      );

      const ordemFaseAtual = Number(faseAtual?.ordem || 999999);
      const ordemPartidaAtual = Number(partidaAtual?.ordem_na_fase || 999999);

      return listaFases
        .flatMap((fase) =>
          (fase.partidas || []).map((partida) => ({
            ...partida,
            _ordem_fase: Number(fase.ordem || 0)
          }))
        )
        .filter((partida) => Number(partida.id) > 0)
        .filter((partida) => partida.local_id !== partidaAtual.local_id && partida.id !== partidaAtual.id)
        .filter((partida) => {
          if (partida._ordem_fase < ordemFaseAtual) return true;
          if (partida._ordem_fase > ordemFaseAtual) return false;
          return Number(partida.ordem_na_fase || 0) < ordemPartidaAtual;
        })
        .sort((a, b) => {
          if (a._ordem_fase !== b._ordem_fase) return a._ordem_fase - b._ordem_fase;
          return Number(a.ordem_na_fase || 0) - Number(b.ordem_na_fase || 0);
        });
    };

    const getOrigemRef = (partida, lado) => partida[`origem_${lado}`];

    const getPayloadOrigem = (partida, lado) => {
      const origem = getOrigemRef(partida, lado);
      return parseJsonSeguro(origem?.payload_texto, {});
    };

    const setPayloadOrigem = (partida, lado, payload) => {
      const origem = getOrigemRef(partida, lado);
      origem.payload_texto = formatarPayloadTexto(payload);
    };

    const getCampoOrigem = (partida, lado, campo, fallback = '') => {
      const payload = getPayloadOrigem(partida, lado);
      if (payload[campo] === undefined || payload[campo] === null || payload[campo] === '') return fallback;
      return payload[campo];
    };

    const setCampoOrigem = (partida, lado, campo, valor) => {
      const payload = getPayloadOrigem(partida, lado);
      const valorNormalizado = ['id', 'position', 'ranking_index', 'match_id'].includes(campo)
        ? (valor === '' ? null : Number(valor))
        : valor;
      payload[campo] = valorNormalizado;
      setPayloadOrigem(partida, lado, payload);
    };

    const alterarTipoOrigem = (partida, lado, tipo) => {
      const origem = getOrigemRef(partida, lado);
      origem.type = tipo;
      let payloadPadrao = {};
      if (tipo === 'EQUIPE') payloadPadrao = { id: null };
      if (tipo === 'GROUP_POSITION') payloadPadrao = { group: '', position: 1 };
      if (tipo === 'MATCH_WINNER' || tipo === 'MATCH_LOSER') payloadPadrao = { match_id: null };
      if (tipo === 'BEST_THIRD' || tipo === 'BEST_LOSER') payloadPadrao = { ranking_index: 1 };
      if (tipo === 'BYE') payloadPadrao = { motivo: 'A definir' };
      setPayloadOrigem(partida, lado, payloadPadrao);
    };

    const normalizarPartida = (partida = {}, gruposDaFase = []) => {
      const grupoPorNome = new Map(
        gruposDaFase
          .filter((grupo) => grupo.nome)
          .map((grupo) => [grupo.nome, grupo.id || grupo.local_id])
      );
      return {
      id: partida.id || null,
      local_id: partida.local_id || criarIdLocal(),
      fase_grupo_id: partida.fase_grupo_id || grupoPorNome.get(partida.grupo) || null,
      grupo: partida.grupo || null,
      mandante_id: partida.mandante_id || null,
      visitante_id: partida.visitante_id || null,
      ordem_na_fase: partida.ordem_na_fase || null,
      status: partida.status || 'agendada',
      data_jogo: toDatetimeLocalInput(partida.data_jogo),
      local: partida.local || null,
      rodada: partida.rodada || null,
      chave: partida.chave || null,
      proxima_partida_id: partida.proxima_partida_id || null,
      vencedor_id: partida.vencedor_id || null,
      origem_mandante: {
        type: partida.origem_mandante?.type || partida.origem_mandante_type || 'MANUAL',
        payload_texto: formatarPayloadTexto(partida.origem_mandante?.payload ?? partida.origem_mandante_payload)
      },
      origem_visitante: {
        type: partida.origem_visitante?.type || partida.origem_visitante_type || 'MANUAL',
        payload_texto: formatarPayloadTexto(partida.origem_visitante?.payload ?? partida.origem_visitante_payload)
      },
      definido_manualmente: partida.definido_manualmente ?? true,
      arbitro_id: partida.arbitro_id || null
      };
    };

    const normalizarGrupo = (grupo = {}) => ({
      id: grupo.id || null,
      local_id: grupo.local_id || criarIdLocal(),
      nome: grupo.nome || '',
      ordem: grupo.ordem || 1,
      configuracao_texto: formatarPayloadTexto(grupo.configuracao),
      equipe_ids: (parseJsonSeguro(grupo.configuracao, {})?.equipe_ids || []).map((id) => String(id))
    });

    const normalizarRegraClassificacao = (regra = {}) => ({
      id: regra.id || null,
      local_id: regra.local_id || criarIdLocal(),
      criterio: regra.criterio || '',
      ordem: regra.ordem || 1,
      configuracao_texto: formatarPayloadTexto(regra.configuracao)
    });

    const normalizarFase = (fase = {}) => {
      const gruposNormalizados = (fase.grupos || []).map(normalizarGrupo);
      return {
        id: fase.id || null,
        local_id: fase.local_id || criarIdLocal(),
        nome: fase.nome || '',
        ordem: fase.ordem || 1,
        tipo: fase.tipo || 'custom',
        status: fase.status || 'planejada',
        permite_edicao_manual: fase.permite_edicao_manual ?? true,
        configuracao_texto: formatarPayloadTexto(fase.configuracao),
        salvando_regras: false,
        classificacao: {},
        grupos: gruposNormalizados,
        regras_classificacao: (fase.regrasClassificacao || fase.regras_classificacao || []).map(normalizarRegraClassificacao),
        partidas: (fase.partidas || []).map((partida) => normalizarPartida(partida, gruposNormalizados))
      };
    };

    const formatoEfetivo = computed(() => {
      if (store.campeonato?.formato) return store.campeonato.formato;
      const tipos = (estrutura.value.fases || []).map((fase) => fase.tipo);
      if (tipos.includes('grupos')) return 'grupos';
      if (tipos.includes('mata_mata')) return 'mata_mata';
      if (tipos.includes('pontos_corridos')) return 'pontos_corridos';
      return null;
    });

    const carregarCriteriosClassificacao = async () => {
      try {
        const response = await campeonatoService.getCriteriosClassificacao();
        criteriosClassificacao.value = response.data?.data || [];
      } catch (error) {
        criteriosClassificacao.value = ['pontos', 'vitorias', 'saldo_gols', 'gols_pro', 'gols_contra', 'confronto_direto', 'fair_play', 'sorteio'];
      }
    };

    const carregarEstrutura = async () => {
      loadingEstrutura.value = true;
      estruturaErro.value = '';
      try {
        const response = await campeonatoService.getEstrutura(campeonatoId);
        const payload = response.data?.data || {};
        estrutura.value = {
          fases: (payload.fases || []).map(normalizarFase)
        };
        if (!faseSugestaoId.value && estrutura.value.fases.length) {
          faseSugestaoId.value = estrutura.value.fases[0].id || null;
        }
      } catch (error) {
        estrutura.value = { fases: [] };
        estruturaErro.value = error.response?.data?.message || 'Não foi possível carregar a estrutura flexível.';
      } finally {
        loadingEstrutura.value = false;
      }
    };

    const adicionarFase = () => {
      estrutura.value.fases.push(normalizarFase());
    };

    const removerFase = (index) => {
      estrutura.value.fases.splice(index, 1);
    };

    const adicionarGrupo = (fase) => {
      fase.grupos.push(normalizarGrupo());
    };

    const removerGrupo = (fase, index) => {
      fase.grupos.splice(index, 1);
    };

    const adicionarPartida = (fase) => {
      fase.partidas.push(normalizarPartida());
    };

    const removerPartida = (fase, index) => {
      fase.partidas.splice(index, 1);
    };

    const adicionarRegraClassificacao = (fase) => {
      fase.regras_classificacao.push(normalizarRegraClassificacao({
        criterio: criteriosClassificacao.value[0] || 'pontos',
        ordem: fase.regras_classificacao.length + 1
      }));
    };

    const removerRegraClassificacao = (fase, index) => {
      fase.regras_classificacao.splice(index, 1);
    };

    const salvarRegrasClassificacao = async (fase) => {
      if (!fase.id) {
        estruturaErro.value = 'Salve a estrutura da fase antes de salvar regras de classificação.';
        return;
      }

      fase.salvando_regras = true;
      estruturaErro.value = '';
      try {
        const payload = {
          regras: fase.regras_classificacao.map((regra, idx) => ({
            id: regra.id || undefined,
            criterio: regra.criterio || 'pontos',
            ordem: Number(regra.ordem || idx + 1),
            configuracao: parseJsonSeguro(regra.configuracao_texto, null)
          }))
        };
        const response = await campeonatoService.saveRegrasClassificacao(fase.id, payload);
        fase.regras_classificacao = (response.data?.data || []).map(normalizarRegraClassificacao);
      } catch (error) {
        estruturaErro.value = error.response?.data?.message || 'Erro ao salvar regras de classificação.';
      } finally {
        fase.salvando_regras = false;
      }
    };

    const toNullableInt = (value) => {
      if (value === null || value === undefined || value === '') return null;
      const parsed = Number(value);
      if (!Number.isInteger(parsed) || parsed <= 0) return null;
      return parsed;
    };

    const montarPayloadEstrutura = () => ({
      fases: estrutura.value.fases.map((fase, faseIndex) => ({
        id: fase.id || undefined,
        nome: fase.nome || `Fase ${faseIndex + 1}`,
        ordem: Number(fase.ordem || faseIndex + 1),
        tipo: fase.tipo || 'custom',
        status: fase.status || 'planejada',
        permite_edicao_manual: !!fase.permite_edicao_manual,
        configuracao: parseJsonSeguro(fase.configuracao_texto, null),
        grupos: fase.grupos.map((grupo, grupoIndex) => ({
          id: grupo.id || undefined,
          nome: grupo.nome || `Grupo ${grupoIndex + 1}`,
          ordem: Number(grupo.ordem || grupoIndex + 1),
          configuracao: {
            ...(parseJsonSeguro(grupo.configuracao_texto, {}) || {}),
            equipe_ids: (grupo.equipe_ids || []).map((id) => Number(id)).filter(Boolean)
          }
        })),
        partidas: fase.partidas.map((partida, partidaIndex) => ({
          id: partida.id || undefined,
          fase_grupo_id: toNullableInt(partida.fase_grupo_id),
          mandante_id: toNullableInt(partida.mandante_id),
          visitante_id: toNullableInt(partida.visitante_id),
          status: partida.status || 'agendada',
          data_jogo: toApiDateTime(partida.data_jogo),
          local: partida.local,
          rodada: partida.rodada || null,
          chave: partida.chave || null,
          ordem_na_fase: Number(partida.ordem_na_fase || partidaIndex + 1),
          proxima_partida_id: toNullableInt(partida.proxima_partida_id),
          vencedor_id: toNullableInt(partida.vencedor_id),
          origem_mandante: {
            type: partida.origem_mandante?.type || 'MANUAL',
            payload: parseJsonSeguro(partida.origem_mandante?.payload_texto, null)
          },
          origem_visitante: {
            type: partida.origem_visitante?.type || 'MANUAL',
            payload: parseJsonSeguro(partida.origem_visitante?.payload_texto, null)
          },
          definido_manualmente: !!partida.definido_manualmente,
          arbitro_id: partida.arbitro_id ? Number(partida.arbitro_id) : null
        }))
      }))
    });

    const salvarEstrutura = async () => {
      salvandoEstrutura.value = true;
      estruturaErro.value = '';
      try {
        const payload = montarPayloadEstrutura();
        await campeonatoService.saveEstrutura(campeonatoId, payload);
        await carregarEstrutura();
        await store.fetchTabela(campeonatoId);
      } catch (error) {
        estruturaErro.value = error.response?.data?.message || 'Erro ao salvar estrutura.';
      } finally {
        salvandoEstrutura.value = false;
      }
    };

    const sugerirConfrontos = async () => {
      if (!faseSugestaoId.value) return;
      sugerindoConfrontos.value = true;
      estruturaErro.value = '';
      try {
        await campeonatoService.sugerirConfrontos(campeonatoId, {
          fase_id: faseSugestaoId.value,
          persistir: true
        });
        await carregarEstrutura();
        await store.fetchTabela(campeonatoId);
      } catch (error) {
        estruturaErro.value = error.response?.data?.message || 'Erro ao sugerir confrontos.';
      } finally {
        sugerindoConfrontos.value = false;
      }
    };

    const resolverClassificados = async () => {
      resolvendoClassificados.value = true;
      estruturaErro.value = '';
      try {
        const res = await campeonatoService.resolverClassificados(campeonatoId);
        if (res.data.data && res.data.data.length > 0) {
          alert(res.data.message);
          await carregarEstrutura();
          await store.fetchTabela(campeonatoId);
        } else {
          alert('Nenhum confronto precisou ser atualizado.');
        }
      } catch (error) {
        estruturaErro.value = error.response?.data?.message || 'Erro ao resolver classificados.';
      } finally {
        resolvendoClassificados.value = false;
      }
    };

    const carregarClassificacao = async (fase) => {
      if (!fase.id) return;
      try {
        const res = await campeonatoService.getClassificacaoFase(fase.id);
        fase.classificacao = res.data?.data || {};
      } catch (error) {
        console.error('Erro ao carregar classificação:', error);
      }
    };

    return {
      store,
      loading: computed(() => store.loading),
      error: computed(() => store.error),
      gerar,
      formatFormato,
      formatoEfetivo,
      getTodasPartidas,
      getTodosGrupos,
      formatosDisponiveis,
      getIcon,
      getDescricao,
      selecionarFormato,
      mostrarEscolhaModoGrupos,
      mostrarConfigGruposManual,
      quantidadeGruposManual,
      gruposManuais,
      salvandoConfigGruposManual,
      iniciarConfiguracaoManualGrupos,
      gerarEstruturaGruposManuais,
      garantirTimesUnicosNosGrupos,
      salvarConfiguracaoManualGrupos,
      cancelarFluxoGrupos,
      aplicarMascaraDataHoraPartida,
      partidaSemDataHora,
      listarNomesTimesDoGrupo,
      getLabelPartida,
      getPartidasElegiveis,
      getCampoOrigem,
      setCampoOrigem,
      alterarTipoOrigem,
      faseZeraCartoesNaFase,
      setFaseZeraCartoesNaFase,
      estrutura,
      arbitros,
      criteriosClassificacao,
      formatCriterio: (c) => {
        const map = { 
          pontos: 'Pontos', 
          vitorias: 'Vitórias', 
          saldo_gols: 'Saldo de Gols', 
          gols_pro: 'Gols Pró', 
          gols_contra: 'Gols Contra',
          confronto_direto: 'Confronto Direto',
          fair_play: 'Fair Play',
          sorteio: 'Sorteio'
        };
        return map[c] || c;
      },
      origemTipos,
      faseSugestaoId,
      loadingEstrutura,
      salvandoEstrutura,
      sugerindoConfrontos,
      estruturaErro,
      carregarEstrutura,
      adicionarFase,
      removerFase,
      adicionarGrupo,
      removerGrupo,
      adicionarPartida,
      removerPartida,
      adicionarRegraClassificacao,
      removerRegraClassificacao,
      salvarRegrasClassificacao,
      salvarEstrutura,
      sugerirConfrontos,
      resolverClassificados,
      carregarClassificacao,
      resolvendoClassificados
    };
  }
}
</script>
