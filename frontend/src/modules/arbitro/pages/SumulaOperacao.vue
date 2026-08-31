<template>
  <div class="min-h-screen bg-slate-900 text-white font-sans flex flex-col">
    <!-- Header Fixo -->
    <header class="bg-slate-800 border-b border-slate-700 sticky top-0 z-50 shadow-md">
      <div class="px-3 sm:px-4 py-2.5 sm:py-3 flex items-center justify-between gap-1">
        <button @click="$router.push('/admin/arbitro/minhas-partidas')" class="p-2 -ml-2 text-slate-400 hover:text-white">
          <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
        </button>
        <div class="text-center flex-1">
          <h1 class="font-bold text-sm text-slate-300">SÚMULA ELETRÔNICA</h1>
          <div class="text-xs text-slate-500">{{ statusSync === 'syncing' ? 'Sincronizando...' : (statusSync === 'offline' ? 'Modo Offline (Aguardando Internet)' : 'Online e Sincronizado') }}</div>
        </div>
        <button @click="sincronizarDados" :class="{'animate-spin': statusSync === 'syncing', 'text-green-400': statusSync === 'synced', 'text-yellow-400': statusSync === 'offline'}" class="p-2 -mr-2">
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
        </button>
      </div>
      
      <!-- Placar -->
      <div class="px-3 sm:px-4 py-3 sm:py-4 bg-slate-900 flex items-center justify-center gap-2 sm:gap-6">
        <div class="flex-1 text-right min-w-0">
          <div class="text-[10px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider mb-1 truncate">{{ partida?.mandante?.nome || 'Mandante' }}</div>
          <div class="text-2xl sm:text-3xl font-black">{{ placarMandante }}</div>
        </div>
        <div class="flex flex-col items-center">
          <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-full bg-slate-800 border-4 border-slate-700 flex items-center justify-center text-lg sm:text-xl font-mono font-bold text-blue-400 shadow-inner">
            {{ tempoFormatado }}
          </div>
          <div class="text-[10px] text-slate-500 mt-1 uppercase font-bold">{{ tempoStatus }}</div>
        </div>
        <div class="flex-1 text-left min-w-0">
          <div class="text-[10px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider mb-1 truncate">{{ partida?.visitante?.nome || 'Visitante' }}</div>
          <div class="text-2xl sm:text-3xl font-black">{{ placarVisitante }}</div>
        </div>
      </div>
      
      <!-- Controles de Tempo -->
      <div class="flex border-t border-slate-800 bg-slate-800/50">
        <button @click="toggleCronometro" class="flex-1 py-2.5 sm:py-3 text-xs sm:text-sm font-bold flex items-center justify-center gap-2" :class="cronometroRodando ? 'text-red-400 hover:bg-slate-800' : 'text-green-400 hover:bg-slate-800'">
          <svg v-if="cronometroRodando" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
          <svg v-else class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664zM21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
          {{ cronometroRodando ? 'PAUSAR' : 'INICIAR' }}
        </button>
        <div class="w-px bg-slate-700"></div>
        <button @click="abrirFinalizacao" class="flex-1 py-2.5 sm:py-3 text-xs sm:text-sm font-bold flex items-center justify-center gap-2 text-blue-400 hover:bg-slate-800">
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
          FINALIZAR
        </button>
      </div>
    </header>

    <!-- Loading State -->
    <div v-if="loading" class="flex-1 flex items-center justify-center">
      <div class="animate-spin w-10 h-10 border-4 border-blue-500 border-t-transparent rounded-full"></div>
    </div>

    <!-- Conteúdo Principal -->
    <main v-else class="flex-1 overflow-y-auto pb-28 sm:pb-20">
      <!-- Tabs -->
      <div class="flex border-b border-slate-700 bg-slate-800/95 backdrop-blur sticky top-0 z-40 shadow-sm">
        <button @click="abaAtiva = 'mandante'" class="flex-1 py-3 sm:py-4 text-[11px] sm:text-sm font-bold uppercase transition-colors" :class="abaAtiva === 'mandante' ? 'text-blue-400 border-b-2 border-blue-400' : 'text-slate-500'">Mandante</button>
        <button @click="abaAtiva = 'visitante'" class="flex-1 py-3 sm:py-4 text-[11px] sm:text-sm font-bold uppercase transition-colors" :class="abaAtiva === 'visitante' ? 'text-blue-400 border-b-2 border-blue-400' : 'text-slate-500'">Visitante</button>
        <button @click="abaAtiva = 'timeline'" class="flex-1 py-3 sm:py-4 text-[11px] sm:text-sm font-bold uppercase transition-colors" :class="abaAtiva === 'timeline' ? 'text-white border-b-2 border-white' : 'text-slate-500'">Timeline</button>
      </div>
      <div v-if="abaAtiva !== 'timeline'" class="px-4 py-2 text-xs text-slate-300 bg-slate-800/40 border-b border-slate-800">
        Toque em um atleta para registrar eventos em tempo real. Atletas suspensos ficam bloqueados.
      </div>
      <div v-if="abaAtiva !== 'timeline'" class="px-3 py-2.5 border-b border-slate-800 bg-slate-900/60">
        <div class="flex items-center justify-between gap-2 mb-2">
          <div class="min-w-0">
            <div class="text-[10px] uppercase tracking-wider text-slate-400">Equipe Ativa</div>
            <div class="text-sm font-bold text-slate-100 truncate">{{ nomeEquipeAtual }}</div>
          </div>
          <button @click="abrirModalEscalacao(timeAtualId)" class="px-3 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-[11px] font-bold text-white shrink-0">
            Escalação
          </button>
        </div>
        <div class="text-[11px] text-slate-300 leading-relaxed mb-2">
          {{ statusEscalacaoAba }}
        </div>
        <div v-if="possuiEscalacaoNoTime(timeAtualId)" class="grid grid-cols-2 gap-2">
          <button @click="definirFiltroAba('campo')" class="rounded-lg py-2 text-xs font-bold border" :class="filtroAbaAtual === 'campo' ? 'bg-emerald-600 border-emerald-500 text-white' : 'bg-slate-800 border-slate-600 text-slate-300'">
            Em Campo ({{ totalEmCampoAba }})
          </button>
          <button @click="definirFiltroAba('banco')" class="rounded-lg py-2 text-xs font-bold border" :class="filtroAbaAtual === 'banco' ? 'bg-indigo-600 border-indigo-500 text-white' : 'bg-slate-800 border-slate-600 text-slate-300'">
            Banco ({{ totalBancoAba }})
          </button>
        </div>
      </div>

      <!-- Elenco -->
      <div v-if="abaAtiva === 'mandante' || abaAtiva === 'visitante'" class="p-2 sm:p-3">
        <div v-for="atleta in atletasDaAba" :key="atleta.id" class="bg-slate-800/50 rounded-xl p-3 mb-2 border border-slate-700/50">
          <div class="flex items-center gap-3 overflow-hidden">
            <div class="w-10 h-10 rounded-full bg-slate-700 flex items-center justify-center text-slate-300 font-bold shrink-0">
              {{ getIniciais(atleta.nome) }}
            </div>
            <div class="truncate">
              <div class="font-bold text-sm truncate text-slate-200">{{ atleta.nome }}</div>
              <div v-if="atleta.suspenso" class="text-[11px] text-red-300 mt-0.5">
                Suspenso: {{ atleta.motivo_suspensao || 'indisponível para esta partida' }}
              </div>
              <div class="flex gap-1 mt-1">
                <span v-if="atleta.em_campo" class="px-1.5 py-0.5 rounded bg-emerald-500/90 text-[10px] font-bold text-white">
                  EM CAMPO
                </span>
                <span v-else-if="atleta.escalado" class="px-1.5 py-0.5 rounded bg-slate-500/80 text-[10px] font-bold text-white">
                  BANCO
                </span>
                <span v-if="atleta.escalado" class="px-1.5 py-0.5 rounded bg-indigo-500/90 text-[10px] font-bold text-white">
                  {{ atleta.titular ? 'TIT' : 'RES' }}
                </span>
                <span v-if="atleta.capitao" class="px-1.5 py-0.5 rounded bg-cyan-500/90 text-[10px] font-bold text-white">
                  CAP
                </span>
                <!-- Badges de Eventos locais -->
                <span v-for="(evt, idx) in getEventosJogador(atleta.id)" :key="idx" class="w-4 h-5 rounded-sm flex items-center justify-center text-[10px]" :class="getEventoBadgeClass(evt.tipo_evento)">
                  {{ getEventoBadgeLabel(evt.tipo_evento) }}
                </span>
                <span v-if="atleta.suspenso" class="px-2 py-0.5 rounded bg-red-600/90 text-[10px] font-bold text-white">
                  SUSPENSO
                </span>
              </div>
            </div>
          </div>
          <div v-if="!atleta.suspenso && atleta.em_campo" class="grid grid-cols-5 gap-1 mt-3">
            <button @click="registrarEvento('GOL', atleta.id, timeAtualId)" class="h-10 rounded-lg bg-slate-700 hover:bg-slate-600 flex items-center justify-center text-lg shadow-sm">⚽</button>
            <button @click="registrarEvento('GOL_CONTRA', atleta.id, timeAtualId)" class="h-10 rounded-lg bg-slate-700 hover:bg-slate-600 flex items-center justify-center text-[10px] font-black shadow-sm">GC</button>
            <button @click="registrarEvento('CARTAO_AMARELO', atleta.id, timeAtualId)" class="h-10 rounded-lg bg-slate-700 hover:bg-slate-600 flex items-center justify-center shadow-sm"><div class="w-4 h-5 bg-yellow-400 rounded-sm"></div></button>
            <button @click="registrarEvento('CARTAO_VERMELHO', atleta.id, timeAtualId)" class="h-10 rounded-lg bg-slate-700 hover:bg-slate-600 flex items-center justify-center shadow-sm"><div class="w-4 h-5 bg-red-500 rounded-sm"></div></button>
            <button @click="abrirSubstituicao(atleta, timeAtualId)" class="h-10 rounded-lg bg-slate-700 hover:bg-slate-600 flex items-center justify-center text-sm font-black shadow-sm">⇄</button>
          </div>
          <div v-else-if="!atleta.suspenso && !atleta.em_campo" class="grid grid-cols-2 gap-1 mt-3">
            <button @click="registrarEvento('CARTAO_AMARELO', atleta.id, timeAtualId)" class="h-10 rounded-lg bg-slate-700 hover:bg-slate-600 flex items-center justify-center shadow-sm"><div class="w-4 h-5 bg-yellow-400 rounded-sm"></div></button>
            <button @click="registrarEvento('CARTAO_VERMELHO', atleta.id, timeAtualId)" class="h-10 rounded-lg bg-slate-700 hover:bg-slate-600 flex items-center justify-center shadow-sm"><div class="w-4 h-5 bg-red-500 rounded-sm"></div></button>
          </div>
          <div v-else class="mt-3 text-[11px] font-semibold text-red-300">
            Bloqueado
          </div>
        </div>
        <div v-if="atletasDaAba.length === 0" class="mx-2 mt-3 p-4 rounded-xl border border-amber-500/40 bg-amber-500/10 text-amber-200 text-sm">
          {{ filtroAbaAtual === 'campo' ? 'Nenhum atleta em campo neste momento.' : 'Nenhum atleta disponível no banco.' }}
        </div>
      </div>

      <!-- Timeline -->
      <div v-if="abaAtiva === 'timeline'" class="p-4 space-y-4 relative">
        <div class="absolute left-1/2 top-4 bottom-4 w-px bg-slate-700 -ml-px"></div>
        <div v-for="evento in eventosOrdenados" :key="evento.local_id || evento.request_id || evento.id" class="relative flex items-center justify-between">
          <div class="w-1/2 pr-4 text-right" v-if="evento.time_id === partida.mandante_id">
            <div class="bg-slate-800 rounded-lg p-2 border border-slate-700 inline-block text-left w-full">
              <div class="font-bold text-xs text-blue-400">{{ formatTipoEvento(evento.tipo_evento) }}</div>
              <div class="text-xs text-slate-300 truncate">{{ getDescricaoEvento(evento) }}</div>
            </div>
          </div>
          <div class="w-1/2 pr-4" v-else></div>

          <div class="absolute left-1/2 -ml-4 w-8 h-8 rounded-full bg-slate-900 border-2 border-slate-700 flex items-center justify-center text-xs font-bold text-slate-300 z-10">
            {{ evento.minuto }}'
          </div>

          <div class="w-1/2 pl-4" v-if="evento.time_id === partida.visitante_id">
            <div class="bg-slate-800 rounded-lg p-2 border border-slate-700 inline-block text-left w-full">
              <div class="font-bold text-xs text-blue-400">{{ formatTipoEvento(evento.tipo_evento) }}</div>
              <div class="text-xs text-slate-300 truncate">{{ getDescricaoEvento(evento) }}</div>
            </div>
          </div>
          <div class="w-1/2 pl-4" v-else></div>
        </div>
        <div v-if="eventos.length === 0" class="text-center text-slate-500 text-sm py-8 italic relative z-10 bg-slate-900/80 rounded-lg">
          Nenhum evento registrado ainda.
        </div>
      </div>
    </main>

    <div v-if="modalSubstituicao.aberto" class="fixed inset-0 bg-black/70 z-[55] flex items-center justify-center p-4">
      <div class="w-full max-w-sm rounded-2xl border border-slate-700 bg-slate-800 p-5">
        <h3 class="text-lg font-black text-white mb-2">Registrar Substituição</h3>
        <p class="text-xs text-slate-400 mb-4">Sai: {{ modalSubstituicao.jogadorSai?.nome }}</p>
        <p class="text-xs text-slate-300 mb-4">Substituições: {{ totalSubstituicoesTime(modalSubstituicao.timeId) }}/{{ configuracaoPartida.maxSubstituicoes }}</p>
        <select v-model="modalSubstituicao.jogadorEntraId" class="w-full rounded-lg bg-slate-900 border border-slate-600 px-3 py-2 text-sm text-slate-100">
          <option :value="null">Selecione quem entra</option>
          <option v-for="atleta in reservasDaSubstituicao" :key="atleta.id" :value="atleta.id">
            {{ atleta.nome }}
          </option>
        </select>
        <div class="flex gap-2 mt-4">
          <button @click="modalSubstituicao.aberto = false" class="flex-1 rounded-lg bg-slate-700 py-2 text-sm font-bold text-white">Cancelar</button>
          <button @click="confirmarSubstituicao" class="flex-1 rounded-lg bg-blue-600 py-2 text-sm font-bold text-white">Confirmar</button>
        </div>
      </div>
    </div>

    <div v-if="configuracaoPartida.modalAberto" class="fixed inset-0 bg-black/80 z-[59] flex items-end sm:items-center justify-center p-0 sm:p-4">
      <div class="w-full sm:max-w-md rounded-t-2xl sm:rounded-2xl border border-slate-700 bg-slate-800 overflow-hidden">
        <div class="px-4 py-4 border-b border-slate-700">
          <h3 class="text-lg font-black text-white">Pré-jogo da Súmula</h3>
          <p class="text-xs text-slate-400 mt-1">Defina o limite de substituições para esta partida.</p>
        </div>
        <div class="p-4 space-y-4">
          <div>
            <label class="block text-xs font-bold text-slate-300 mb-2">Quantidade máxima de substituições por time</label>
            <input v-model.number="configuracaoPartida.maxSubstituicoes" type="number" min="0" max="20" class="w-full rounded-lg bg-slate-900 border border-slate-600 px-3 py-2 text-sm text-slate-100" />
          </div>
          <div class="grid grid-cols-2 gap-2">
            <button @click="cancelarConfiguracaoInicial" class="rounded-lg bg-slate-700 py-2.5 text-sm font-bold text-white">Cancelar</button>
            <button @click="confirmarConfiguracaoInicial" class="rounded-lg bg-blue-600 py-2.5 text-sm font-bold text-white">Continuar</button>
          </div>
        </div>
      </div>
    </div>

    <div v-if="modalEscalacao.aberto" class="fixed inset-0 bg-black/75 z-[58] flex items-end sm:items-center justify-center p-0 sm:p-4">
      <div class="w-full sm:max-w-xl rounded-t-2xl sm:rounded-2xl border border-slate-700 bg-slate-800 max-h-[92vh] overflow-hidden flex flex-col">
        <div class="px-4 pt-4 pb-3 border-b border-slate-700">
          <h3 class="text-lg font-black text-white mb-1">Escalação da Partida</h3>
          <p class="text-[11px] text-slate-300 font-semibold mb-1">{{ nomeEquipeModalEscalacao }}</p>
          <p class="text-xs text-slate-400">Selecione titulares e reservas do time atual. É obrigatório ter 11 titulares.</p>
          <div class="mt-3">
            <input
              v-model.trim="modalEscalacao.busca"
              type="text"
              placeholder="Buscar atleta por nome..."
              class="w-full rounded-lg bg-slate-900 border border-slate-600 px-3 py-2 text-sm text-slate-100 placeholder:text-slate-500"
            />
          </div>
          <div class="mt-3 grid grid-cols-2 sm:grid-cols-4 gap-2">
            <button @click="montarEscalacaoRapida" class="rounded-lg bg-emerald-600 hover:bg-emerald-500 px-2 py-2 text-[11px] font-bold text-white">
              Rápida 11 + Banco
            </button>
            <button @click="definirSoTitularesRapido" class="rounded-lg bg-blue-600 hover:bg-blue-500 px-2 py-2 text-[11px] font-bold text-white">
              Só 11 Titulares
            </button>
            <button @click="selecionarReservasAutomaticamente" class="rounded-lg bg-indigo-600 hover:bg-indigo-500 px-2 py-2 text-[11px] font-bold text-white">
              Completar Banco
            </button>
            <button @click="limparEscalacaoModal" class="rounded-lg bg-slate-700 hover:bg-slate-600 px-2 py-2 text-[11px] font-bold text-white">
              Limpar
            </button>
          </div>
          <div class="mt-2 text-[11px] text-slate-300">
            Selecionados: {{ selecionadosEscalacaoModal }} | Titulares: {{ titularesEscalacaoModal }} | Reservas: {{ reservasEscalacaoModal }}
          </div>
        </div>
        <div class="p-3 sm:p-4 overflow-y-auto space-y-2">
          <div v-for="item in itensEscalacaoFiltrados" :key="item.atleta_id" class="rounded-xl border border-slate-700 p-3 bg-slate-900/60">
            <div class="flex items-center justify-between gap-2">
              <button @click="alternarSelecaoEscalacao(item)" :disabled="item.suspenso" class="flex-1 text-left flex items-center gap-3 disabled:opacity-60">
                <span class="h-5 w-5 rounded border flex items-center justify-center text-[11px] font-black" :class="item.selecionado ? 'border-blue-400 bg-blue-500 text-white' : 'border-slate-500 text-transparent'">✓</span>
                <span class="text-sm text-slate-100 truncate">{{ item.nome }}</span>
              </button>
              <span v-if="item.suspenso" class="text-[10px] font-bold text-red-300">SUSPENSO</span>
            </div>
            <div class="mt-3 grid grid-cols-2 gap-2 text-xs">
              <button
                @click="alternarTitularEscalacao(item, true)"
                :disabled="item.suspenso"
                class="rounded-lg px-2 py-2 font-bold border disabled:opacity-50"
                :class="item.titular ? 'bg-blue-600 text-white border-blue-500' : 'bg-slate-800 text-slate-300 border-slate-600'"
              >
                Titular
              </button>
              <button
                @click="alternarTitularEscalacao(item, false)"
                :disabled="item.suspenso"
                class="rounded-lg px-2 py-2 font-bold border disabled:opacity-50"
                :class="item.selecionado && !item.titular ? 'bg-indigo-600 text-white border-indigo-500' : 'bg-slate-800 text-slate-300 border-slate-600'"
              >
                Reserva
              </button>
              <button
                @click="definirCapitao(item.atleta_id)"
                :disabled="item.suspenso || !item.selecionado"
                class="col-span-2 rounded-lg px-2 py-2 font-bold border disabled:opacity-50"
                :class="item.capitao ? 'bg-cyan-600 text-white border-cyan-500' : 'bg-slate-800 text-slate-300 border-slate-600'"
              >
                {{ item.capitao ? 'Capitão Definido' : 'Definir como Capitão' }}
              </button>
              <div class="col-span-2 text-[10px] text-slate-400">
                Atalho operacional: clique direto em <strong>Titular</strong> ou <strong>Reserva</strong> para classificar em 1 ação.
              </div>
            </div>
          </div>
          <div v-if="itensEscalacaoFiltrados.length === 0" class="rounded-xl border border-slate-700 p-3 bg-slate-900/60 text-xs text-slate-300">
            Nenhum atleta encontrado para "{{ modalEscalacao.busca }}".
          </div>
        </div>
        <div class="grid grid-cols-2 gap-2 p-3 sm:p-4 border-t border-slate-700 bg-slate-800 pb-[max(0.75rem,env(safe-area-inset-bottom))]">
          <button @click="fecharModalEscalacao" class="rounded-lg bg-slate-700 py-3 text-sm font-bold text-white">{{ modalEscalacao.modoPreJogo ? 'Depois' : 'Cancelar' }}</button>
          <button @click="salvarEscalacaoAtual" class="rounded-lg bg-indigo-600 py-3 text-sm font-bold text-white flex items-center justify-center gap-2">
            <span v-if="modalEscalacao.salvando" class="animate-spin">⌛</span>
            {{ modalEscalacao.modoPreJogo ? 'Salvar e Continuar' : 'Salvar Escalação' }}
          </button>
        </div>
      </div>
    </div>

    <div v-if="modalConfirmacao.aberto" class="fixed inset-0 bg-black/70 z-[70] flex items-center justify-center p-4">
      <div class="w-full max-w-sm rounded-2xl border border-slate-700 bg-slate-800 p-5">
        <h3 class="text-lg font-black text-white mb-2">{{ modalConfirmacao.titulo }}</h3>
        <p class="text-sm text-slate-300 whitespace-pre-line">{{ modalConfirmacao.mensagem }}</p>
        <div class="flex gap-2 mt-4">
          <button @click="responderConfirmacao(false)" class="flex-1 rounded-lg bg-slate-700 py-2 text-sm font-bold text-white">Cancelar</button>
          <button @click="responderConfirmacao(true)" :class="modalConfirmacao.perigoso ? 'bg-amber-600 hover:bg-amber-500' : 'bg-blue-600 hover:bg-blue-500'" class="flex-1 rounded-lg py-2 text-sm font-bold text-white">
            {{ modalConfirmacao.textoConfirmar }}
          </button>
        </div>
      </div>
    </div>

    <!-- Modal Assinatura & Finalização -->
    <div v-if="modalFinalizacao" class="fixed inset-0 bg-black/90 z-[60] flex flex-col p-4 overflow-y-auto">
      <div class="bg-slate-800 rounded-2xl p-5 w-full max-w-md mx-auto my-auto shadow-2xl border border-slate-700">
        <h3 class="text-xl font-black text-white mb-4 text-center">Finalizar Partida</h3>
        <p class="text-slate-400 text-sm text-center mb-6">Colete as assinaturas digitais na tela para gerar o PDF oficial.</p>

        <div class="space-y-6">
          <!-- Assinatura Árbitro -->
          <div>
            <label class="block text-sm font-bold text-slate-300 mb-2">Assinatura do Árbitro</label>
            <div class="bg-white rounded-lg p-1 border-2 border-slate-600">
              <canvas ref="canvasArbitro" width="300" height="100" class="w-full h-[100px] touch-none" @touchstart="startDraw($event, 'arbitro')" @touchmove="draw($event, 'arbitro')" @touchend="stopDraw" @mousedown="startDraw($event, 'arbitro')" @mousemove="draw($event, 'arbitro')" @mouseup="stopDraw" @mouseleave="stopDraw"></canvas>
            </div>
            <button @click="clearCanvas('arbitro')" class="text-xs text-slate-400 mt-1 hover:text-white">Limpar</button>
          </div>

          <!-- Assinatura Mandante -->
          <div>
            <label class="block text-sm font-bold text-slate-300 mb-2">Assinatura Cap. Mandante</label>
            <div class="bg-white rounded-lg p-1 border-2 border-slate-600">
              <canvas ref="canvasMandante" width="300" height="100" class="w-full h-[100px] touch-none" @touchstart="startDraw($event, 'mandante')" @touchmove="draw($event, 'mandante')" @touchend="stopDraw" @mousedown="startDraw($event, 'mandante')" @mousemove="draw($event, 'mandante')" @mouseup="stopDraw" @mouseleave="stopDraw"></canvas>
            </div>
            <button @click="clearCanvas('mandante')" class="text-xs text-slate-400 mt-1 hover:text-white">Limpar</button>
          </div>

          <!-- Assinatura Visitante -->
          <div>
            <label class="block text-sm font-bold text-slate-300 mb-2">Assinatura Cap. Visitante</label>
            <div class="bg-white rounded-lg p-1 border-2 border-slate-600">
              <canvas ref="canvasVisitante" width="300" height="100" class="w-full h-[100px] touch-none" @touchstart="startDraw($event, 'visitante')" @touchmove="draw($event, 'visitante')" @touchend="stopDraw" @mousedown="startDraw($event, 'visitante')" @mousemove="draw($event, 'visitante')" @mouseup="stopDraw" @mouseleave="stopDraw"></canvas>
            </div>
            <button @click="clearCanvas('visitante')" class="text-xs text-slate-400 mt-1 hover:text-white">Limpar</button>
          </div>
        </div>

        <div class="flex gap-3 mt-8">
          <button @click="modalFinalizacao = false" class="flex-1 py-3 bg-slate-700 text-white rounded-xl font-bold hover:bg-slate-600 transition-colors">Cancelar</button>
          <button @click="confirmarFinalizacao" class="flex-1 py-3 bg-blue-600 text-white rounded-xl font-bold hover:bg-blue-500 transition-colors flex items-center justify-center gap-2">
            <span v-if="enviandoFinalizacao" class="animate-spin">⌛</span>
            Concluir Jogo
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useToast } from 'vue-toastification';
import api from '@/services/api';
import arbitroService from '../services/arbitroService';

const route = useRoute();
const router = useRouter();
const toast = useToast();

const partida = ref(null);
const atletasMandante = ref([]);
const atletasVisitante = ref([]);
const eventos = ref([]);

const loading = ref(true);
const statusSync = ref('synced'); // 'synced', 'offline', 'syncing'
const abaAtiva = ref('mandante');
const filtroElenco = ref({
  mandante: 'campo',
  visitante: 'campo'
});
const configuracaoPartida = ref({
  modalAberto: false,
  maxSubstituicoes: 5,
  configurada: false
});
const fluxoPreJogo = ref({
  ativo: false,
  timesPendentes: []
});

// Cronômetro
const segundos = ref(0);
const cronometroRodando = ref(false);
let intervalo = null;

const tempoFormatado = computed(() => {
  const min = Math.floor(segundos.value / 60);
  const seg = segundos.value % 60;
  return `${min.toString().padStart(2, '0')}:${seg.toString().padStart(2, '0')}`;
});
const minutoAtual = computed(() => Math.floor(segundos.value / 60));
const tempoStatus = computed(() => cronometroRodando.value ? 'Em Andamento' : 'Pausado');

const placarMandante = computed(() => eventos.value.filter((e) => {
  if (!partida.value) return false;
  return (e.tipo_evento === 'GOL' && e.time_id === partida.value.mandante_id)
    || (e.tipo_evento === 'GOL_CONTRA' && e.time_id === partida.value.visitante_id);
}).length);
const placarVisitante = computed(() => eventos.value.filter((e) => {
  if (!partida.value) return false;
  return (e.tipo_evento === 'GOL' && e.time_id === partida.value.visitante_id)
    || (e.tipo_evento === 'GOL_CONTRA' && e.time_id === partida.value.mandante_id);
}).length);
const timeAtualId = computed(() => abaAtiva.value === 'mandante' ? partida.value?.mandante_id : partida.value?.visitante_id);
const nomeAbaAtual = computed(() => abaAtiva.value === 'mandante' ? 'mandante' : 'visitante');
const nomeEquipeAtual = computed(() => {
  if (abaAtiva.value === 'mandante') return partida.value?.mandante?.nome || 'Mandante';
  if (abaAtiva.value === 'visitante') return partida.value?.visitante?.nome || 'Visitante';
  return 'Partida';
});
const modalSubstituicao = ref({
  aberto: false,
  jogadorSai: null,
  timeId: null,
  jogadorEntraId: null
});
const modalConfirmacao = ref({
  aberto: false,
  titulo: '',
  mensagem: '',
  textoConfirmar: 'Confirmar',
  perigoso: false,
  resolver: null
});
const modalEscalacao = ref({
  aberto: false,
  timeId: null,
  itens: [],
  salvando: false,
  modoPreJogo: false,
  busca: ''
});

const filtroAbaAtual = computed(() => abaAtiva.value === 'mandante' ? filtroElenco.value.mandante : filtroElenco.value.visitante);
const timeAbaAtual = computed(() => abaAtiva.value === 'mandante' ? partida.value?.mandante_id : partida.value?.visitante_id);
const getNomeEquipeById = (timeId) => {
  if (timeId === partida.value?.mandante_id) return partida.value?.mandante?.nome || 'Mandante';
  if (timeId === partida.value?.visitante_id) return partida.value?.visitante?.nome || 'Visitante';
  return 'Equipe';
};
const nomeEquipeModalEscalacao = computed(() => getNomeEquipeById(modalEscalacao.value.timeId));
const getElencoDoTime = (timeId) => {
  if (timeId === partida.value?.mandante_id) return atletasMandante.value;
  if (timeId === partida.value?.visitante_id) return atletasVisitante.value;
  return [];
};
const possuiEscalacaoNoTime = (timeId) => getElencoDoTime(timeId).some((a) => a.escalado);
const totalSubstituicoesTime = (timeId) => eventos.value.filter((e) => e.tipo_evento === 'SUBSTITUICAO' && e.time_id === timeId).length;
const totalEmCampoAba = computed(() => getElencoDoTime(timeAbaAtual.value).filter((a) => a.escalado && a.em_campo).length);
const totalBancoAba = computed(() => getElencoDoTime(timeAbaAtual.value).filter((a) => a.escalado && !a.em_campo).length);
const atletasDaAba = computed(() => {
  const elenco = abaAtiva.value === 'mandante' ? atletasMandante.value : atletasVisitante.value;
  const temEscalacao = elenco.some((a) => a.escalado);
  if (!temEscalacao) return elenco;
  const filtrado = [...elenco]
    .filter((a) => a.escalado)
    .sort((a, b) => {
      if (a.em_campo !== b.em_campo) return a.em_campo ? -1 : 1;
      if (a.titular !== b.titular) return a.titular ? -1 : 1;
      return (a.ordem_escalacao || 999) - (b.ordem_escalacao || 999);
    });
  if (filtroAbaAtual.value === 'banco') return filtrado.filter((a) => !a.em_campo);
  return filtrado.filter((a) => a.em_campo);
});
const statusEscalacaoAba = computed(() => {
  const elenco = abaAtiva.value === 'mandante' ? atletasMandante.value : atletasVisitante.value;
  const escalados = elenco.filter((a) => a.escalado);
  const titulares = escalados.filter((a) => a.titular).length;
  if (escalados.length === 0) return 'Sem escalação oficial definida para este time.';
  return `Escalação ativa: ${titulares} titular(es), ${Math.max(escalados.length - titulares, 0)} reserva(s) e ${totalSubstituicoesTime(timeAbaAtual.value)}/${configuracaoPartida.value.maxSubstituicoes} substituições.`;
});
const reservasDaSubstituicao = computed(() => {
  if (!modalSubstituicao.value.timeId) return [];
  const elenco = modalSubstituicao.value.timeId === partida.value?.mandante_id
    ? atletasMandante.value
    : atletasVisitante.value;
  return elenco.filter((a) => {
    if (a.id === modalSubstituicao.value.jogadorSai?.id) return false;
    if (a.suspenso) return false;
    if (!a.escalado) return false;
    return !a.em_campo;
  });
});
const selecionadosEscalacaoModal = computed(() => modalEscalacao.value.itens.filter((item) => item.selecionado).length);
const titularesEscalacaoModal = computed(() => modalEscalacao.value.itens.filter((item) => item.selecionado && item.titular).length);
const reservasEscalacaoModal = computed(() => Math.max(selecionadosEscalacaoModal.value - titularesEscalacaoModal.value, 0));
const itensEscalacaoFiltrados = computed(() => {
  const busca = (modalEscalacao.value.busca || '').trim().toLowerCase();
  if (!busca) return modalEscalacao.value.itens;
  return modalEscalacao.value.itens.filter((item) => (item.nome || '').toLowerCase().includes(busca));
});

const eventosOrdenados = computed(() => [...eventos.value].sort((a, b) => b.minuto - a.minuto));

// Lifecycle
onMounted(async () => {
  const partidaId = route.params.id;
  await carregarDadosLocaisOuRemotos(partidaId);
  
  // Se já tiver dados locais de tempo, restaurar
  const savedTime = localStorage.getItem(`sumula_${partidaId}_tempo`);
  if (savedTime) {
    segundos.value = parseInt(savedTime);
  }
  
  // Tentar sincronizar eventos não enviados
  await sincronizarDados();
  await abrirFluxoPreJogoSeNecessario(partidaId);
});

onUnmounted(() => {
  if (intervalo) clearInterval(intervalo);
  if (modalConfirmacao.value.resolver) {
    modalConfirmacao.value.resolver(false);
    modalConfirmacao.value.resolver = null;
  }
});

const carregarDadosLocaisOuRemotos = async (partidaId) => {
  loading.value = true;
  try {
    // Tenta carregar do backend
    const { data } = await api.get(`/public/partidas/${partidaId}`);
    partida.value = data;
    
    // Busca elencos do backend
    if (data.mandante_id) {
      atletasMandante.value = await carregarElencoEquipe(data.mandante_id);
    }
    if (data.visitante_id) {
      atletasVisitante.value = await carregarElencoEquipe(data.visitante_id);
    }

    const campeonatoId = data?.fase?.campeonato_id || data?.fase?.campeonato?.id;
    await carregarSuspensoesCampeonato(campeonatoId);
    await carregarEscalacaoPartida(partidaId);
    
    eventos.value = data.eventos || [];
    
    // Salvar cache local para Offline
    localStorage.setItem(`sumula_partida_${partidaId}`, JSON.stringify(partida.value));
    localStorage.setItem(`sumula_atletas_m_${partidaId}`, JSON.stringify(atletasMandante.value));
    localStorage.setItem(`sumula_atletas_v_${partidaId}`, JSON.stringify(atletasVisitante.value));
    
  } catch (error) {
    statusSync.value = 'offline';
    // Falhou, carrega do localStorage
    const p = localStorage.getItem(`sumula_partida_${partidaId}`);
    if (p) partida.value = JSON.parse(p);
    
    const am = localStorage.getItem(`sumula_atletas_m_${partidaId}`);
    if (am) atletasMandante.value = JSON.parse(am);
    
    const av = localStorage.getItem(`sumula_atletas_v_${partidaId}`);
    if (av) atletasVisitante.value = JSON.parse(av);
  }
  
  // Adicionar eventos locais que ainda não foram sincronizados
  const eventosLocais = JSON.parse(localStorage.getItem(`sumula_eventos_pendentes_${partidaId}`) || '[]');
  eventos.value = [...eventos.value, ...eventosLocais];
  aplicarEstadoEmCampoPorEventos();
  
  loading.value = false;
};

const extrairElencoAprovado = (payload) => {
  const elenco = payload?.atletas
    || payload?.equipe?.atletas
    || payload?.equipe?.atletasAprovados
    || payload?.equipe?.atletas_aprovados
    || [];

  return (Array.isArray(elenco) ? elenco : []).filter((a) => (a?.pivot?.status || 'aprovado') === 'aprovado');
};

const carregarElencoEquipe = async (equipeId) => {
  if (!equipeId) return [];

  try {
    const respPublico = await api.get(`/public/equipes/${equipeId}`);
    return extrairElencoAprovado(respPublico.data);
  } catch (errorPublico) {
    // Fallback para rota administrativa/autenticada quando a rota pública falhar.
    try {
      const respInterno = await api.get(`/equipes/${equipeId}`);
      return extrairElencoAprovado(respInterno.data);
    } catch (errorInterno) {
      return [];
    }
  }
};

const aplicarEscalacaoNoElenco = (elenco, escalacaoDoTime) => {
  const mapa = new Map((Array.isArray(escalacaoDoTime) ? escalacaoDoTime : []).map((item) => [item.atleta_id, item]));
  return elenco.map((atleta) => {
    const item = mapa.get(atleta.id);
    return {
      ...atleta,
      escalado: Boolean(item),
      titular: Boolean(item?.titular),
      capitao: Boolean(item?.capitao),
      ordem_escalacao: item?.ordem ?? null,
      em_campo: Boolean(item?.titular)
    };
  });
};

const carregarEscalacaoPartida = async (partidaId) => {
  if (!partidaId) return;
  try {
    const { data } = await arbitroService.getEscalacao(partidaId);
    atletasMandante.value = aplicarEscalacaoNoElenco(atletasMandante.value, data?.mandante || []);
    atletasVisitante.value = aplicarEscalacaoNoElenco(atletasVisitante.value, data?.visitante || []);
  } catch {
    atletasMandante.value = atletasMandante.value.map((atleta) => ({ ...atleta, escalado: false, titular: false, capitao: false, ordem_escalacao: null, em_campo: false }));
    atletasVisitante.value = atletasVisitante.value.map((atleta) => ({ ...atleta, escalado: false, titular: false, capitao: false, ordem_escalacao: null, em_campo: false }));
  }
  aplicarEstadoEmCampoPorEventos();
};

const carregarSuspensoesCampeonato = async (campeonatoId) => {
  if (!campeonatoId) return;
  try {
    const { data } = await api.get(`/public/campeonatos/${campeonatoId}/cartoes`);
    const suspensoes = new Map(
      (Array.isArray(data) ? data : [])
        .filter((item) => item?.suspenso)
        .map((item) => [`${item.equipe_id}:${item.id}`, item])
    );

    atletasMandante.value = atletasMandante.value.map((atleta) => {
      const info = suspensoes.get(`${partida.value?.mandante_id}:${atleta.id}`);
      return {
        ...atleta,
        suspenso: Boolean(info),
        motivo_suspensao: info?.motivo_suspensao || ''
      };
    });

    atletasVisitante.value = atletasVisitante.value.map((atleta) => {
      const info = suspensoes.get(`${partida.value?.visitante_id}:${atleta.id}`);
      return {
        ...atleta,
        suspenso: Boolean(info),
        motivo_suspensao: info?.motivo_suspensao || ''
      };
    });
  } catch {
    atletasMandante.value = atletasMandante.value.map((atleta) => ({ ...atleta, suspenso: false, motivo_suspensao: '' }));
    atletasVisitante.value = atletasVisitante.value.map((atleta) => ({ ...atleta, suspenso: false, motivo_suspensao: '' }));
  }
};

// Ações
const toggleCronometro = () => {
  cronometroRodando.value = !cronometroRodando.value;
  if (cronometroRodando.value) {
    intervalo = setInterval(() => {
      segundos.value++;
      if (segundos.value % 10 === 0) {
        localStorage.setItem(`sumula_${partida.value.id}_tempo`, segundos.value.toString());
      }
    }, 1000);
  } else {
    clearInterval(intervalo);
    localStorage.setItem(`sumula_${partida.value.id}_tempo`, segundos.value.toString());
  }
};

const gerarRequestIdEvento = () => {
  if (typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function') {
    return crypto.randomUUID();
  }
  return `evt_${Date.now()}_${Math.random().toString(36).slice(2, 10)}`;
};

const extrairMensagemErroApi = (error) => {
  const data = error?.response?.data;
  if (!data) return error?.message || 'Erro desconhecido.';
  if (typeof data.error === 'string' && data.error) return data.error;
  if (typeof data.message === 'string' && data.message) return data.message;
  if (data.errors && typeof data.errors === 'object') {
    const primeiraLista = Object.values(data.errors).find((v) => Array.isArray(v) && v.length > 0);
    if (primeiraLista) return primeiraLista[0];
  }
  return error?.message || 'Erro desconhecido.';
};

const solicitarConfirmacao = ({ titulo, mensagem, textoConfirmar = 'Confirmar', perigoso = false }) => {
  if (modalConfirmacao.value.resolver) {
    modalConfirmacao.value.resolver(false);
  }
  return new Promise((resolve) => {
    modalConfirmacao.value = {
      aberto: true,
      titulo,
      mensagem,
      textoConfirmar,
      perigoso,
      resolver: resolve
    };
  });
};

const responderConfirmacao = (confirmado) => {
  if (modalConfirmacao.value.resolver) {
    modalConfirmacao.value.resolver(confirmado);
  }
  modalConfirmacao.value = {
    aberto: false,
    titulo: '',
    mensagem: '',
    textoConfirmar: 'Confirmar',
    perigoso: false,
    resolver: null
  };
};

const definirFiltroAba = (filtro) => {
  if (abaAtiva.value === 'mandante') filtroElenco.value.mandante = filtro;
  if (abaAtiva.value === 'visitante') filtroElenco.value.visitante = filtro;
};

const chaveConfiguracaoPartida = (partidaId) => `sumula_config_partida_${partidaId}`;

const carregarConfiguracaoPartida = (partidaId) => {
  const salvo = JSON.parse(localStorage.getItem(chaveConfiguracaoPartida(partidaId)) || '{}');
  configuracaoPartida.value.maxSubstituicoes = Number.isFinite(Number(salvo.maxSubstituicoes)) ? Number(salvo.maxSubstituicoes) : 5;
  configuracaoPartida.value.configurada = Boolean(salvo.configurada);
};

const salvarConfiguracaoPartida = (partidaId) => {
  localStorage.setItem(chaveConfiguracaoPartida(partidaId), JSON.stringify({
    maxSubstituicoes: configuracaoPartida.value.maxSubstituicoes,
    configurada: configuracaoPartida.value.configurada
  }));
};

const possuiEscalacaoCompleta = (timeId) => {
  const elenco = getElencoDoTime(timeId);
  const titulares = elenco.filter((a) => a.escalado && a.titular).length;
  return titulares === 11;
};

const abrirFluxoPreJogoSeNecessario = async (partidaId) => {
  if (!partida.value?.mandante_id || !partida.value?.visitante_id) return;
  carregarConfiguracaoPartida(partidaId);
  if (!configuracaoPartida.value.configurada) {
    configuracaoPartida.value.modalAberto = true;
    return;
  }
  const precisaEscalacao = !possuiEscalacaoCompleta(partida.value.mandante_id) || !possuiEscalacaoCompleta(partida.value.visitante_id);
  if (!precisaEscalacao) return;
  fluxoPreJogo.value = {
    ativo: true,
    timesPendentes: [partida.value.mandante_id, partida.value.visitante_id].filter((id) => !possuiEscalacaoCompleta(id))
  };
  const proximoTime = fluxoPreJogo.value.timesPendentes[0];
  if (proximoTime) abrirModalEscalacao(proximoTime, { modoPreJogo: true });
};

const cancelarConfiguracaoInicial = () => {
  configuracaoPartida.value.modalAberto = false;
};

const confirmarConfiguracaoInicial = () => {
  const valor = Number(configuracaoPartida.value.maxSubstituicoes);
  if (!Number.isInteger(valor) || valor < 0 || valor > 20) {
    toast.warning('Informe um valor válido entre 0 e 20 substituições.');
    return;
  }
  configuracaoPartida.value.maxSubstituicoes = valor;
  configuracaoPartida.value.configurada = true;
  configuracaoPartida.value.modalAberto = false;
  if (partida.value?.id) salvarConfiguracaoPartida(partida.value.id);
  abrirFluxoPreJogoSeNecessario(partida.value?.id);
};

const abrirModalEscalacao = (timeId, opcoes = {}) => {
  const elenco = timeId === partida.value?.mandante_id ? atletasMandante.value : atletasVisitante.value;
  modalEscalacao.value = {
    aberto: true,
    timeId,
    salvando: false,
    modoPreJogo: Boolean(opcoes.modoPreJogo),
    busca: '',
    itens: elenco.map((atleta) => ({
      atleta_id: atleta.id,
      nome: atleta.nome,
      suspenso: Boolean(atleta.suspenso),
      selecionado: Boolean(atleta.escalado),
      titular: Boolean(atleta.titular),
      capitao: Boolean(atleta.capitao)
    }))
  };
};

const alternarSelecaoEscalacao = (item) => {
  if (item.suspenso) return;
  item.selecionado = !item.selecionado;
  if (!item.selecionado) {
    item.capitao = false;
    item.titular = false;
  }
};

const alternarTitularEscalacao = (item, titular) => {
  if (item.suspenso) return;
  item.selecionado = true;
  item.titular = titular;
  if (!titular) {
    item.capitao = false;
  }
};

const definirCapitao = (atletaId) => {
  const selecionadoAtual = modalEscalacao.value.itens.find((item) => item.atleta_id === atletaId);
  if (!selecionadoAtual?.selecionado) return;
  const removerCapitaoAtual = Boolean(selecionadoAtual.capitao);
  modalEscalacao.value.itens = modalEscalacao.value.itens.map((item) => ({
    ...item,
    capitao: removerCapitaoAtual ? false : item.atleta_id === atletaId
  }));
};

const montarEscalacaoRapida = () => {
  const elegiveis = modalEscalacao.value.itens.filter((item) => !item.suspenso);
  const titularesSet = new Set(elegiveis.slice(0, 11).map((item) => item.atleta_id));
  const capitaoAtual = modalEscalacao.value.itens.find((item) => item.capitao && titularesSet.has(item.atleta_id));
  const capitaoId = capitaoAtual?.atleta_id ?? elegiveis[0]?.atleta_id ?? null;

  modalEscalacao.value.itens = modalEscalacao.value.itens.map((item) => {
    if (item.suspenso) {
      return { ...item, selecionado: false, titular: false, capitao: false };
    }
    const titular = titularesSet.has(item.atleta_id);
    return {
      ...item,
      selecionado: true,
      titular,
      capitao: titular && capitaoId === item.atleta_id
    };
  });
};

const definirSoTitularesRapido = () => {
  const elegiveis = modalEscalacao.value.itens.filter((item) => !item.suspenso);
  const titularesSet = new Set(elegiveis.slice(0, 11).map((item) => item.atleta_id));
  const capitaoAtual = modalEscalacao.value.itens.find((item) => item.capitao && titularesSet.has(item.atleta_id));
  const capitaoId = capitaoAtual?.atleta_id ?? elegiveis[0]?.atleta_id ?? null;

  modalEscalacao.value.itens = modalEscalacao.value.itens.map((item) => {
    const titular = titularesSet.has(item.atleta_id);
    return {
      ...item,
      selecionado: titular,
      titular,
      capitao: titular && capitaoId === item.atleta_id
    };
  });
};

const selecionarReservasAutomaticamente = () => {
  modalEscalacao.value.itens = modalEscalacao.value.itens.map((item) => {
    if (item.suspenso) {
      return { ...item, selecionado: false, titular: false, capitao: false };
    }
    if (item.selecionado) {
      return item;
    }
    return { ...item, selecionado: true, titular: false, capitao: false };
  });
};

const limparEscalacaoModal = () => {
  modalEscalacao.value.itens = modalEscalacao.value.itens.map((item) => ({
    ...item,
    selecionado: false,
    titular: false,
    capitao: false
  }));
};

const fecharModalEscalacao = () => {
  modalEscalacao.value.aberto = false;
};

const aplicarEstadoEmCampoPorEventos = () => {
  const resetar = (elenco) => elenco.map((a) => ({ ...a, em_campo: Boolean(a.titular) }));
  atletasMandante.value = resetar(atletasMandante.value);
  atletasVisitante.value = resetar(atletasVisitante.value);

  const substituicoes = [...eventos.value]
    .filter((e) => e.tipo_evento === 'SUBSTITUICAO')
    .sort((a, b) => a.minuto - b.minuto);

  for (const evt of substituicoes) {
    aplicarEstadoSubstituicao(evt.time_id, evt.jogador_saiu_id, evt.jogador_id);
  }
};

const aplicarEstadoSubstituicao = (timeId, jogadorSaiuId, jogadorEntraId) => {
  const atualizar = (lista) => lista.map((atleta) => {
    if (atleta.id === jogadorSaiuId) return { ...atleta, em_campo: false, titular: false };
    if (atleta.id === jogadorEntraId) return { ...atleta, em_campo: true, titular: true };
    return atleta;
  });
  if (timeId === partida.value?.mandante_id) atletasMandante.value = atualizar(atletasMandante.value);
  if (timeId === partida.value?.visitante_id) atletasVisitante.value = atualizar(atletasVisitante.value);
};

const salvarEscalacaoAtual = async () => {
  if (!partida.value?.id || !modalEscalacao.value.timeId) return;
  const selecionados = modalEscalacao.value.itens.filter((item) => item.selecionado);
  if (selecionados.length === 0) {
    toast.warning('Selecione pelo menos um atleta para salvar a escalação.');
    return;
  }
  const titulares = selecionados.filter((item) => item.titular).length;
  if (titulares !== 11) {
    toast.warning('A escalação precisa ter exatamente 11 titulares.');
    return;
  }

  modalEscalacao.value.salvando = true;
  try {
    await arbitroService.salvarEscalacao(partida.value.id, {
      time_id: modalEscalacao.value.timeId,
      atletas: selecionados.map((item) => ({
        atleta_id: item.atleta_id,
        titular: Boolean(item.titular),
        capitao: Boolean(item.capitao)
      }))
    });

    await carregarEscalacaoPartida(partida.value.id);
    modalEscalacao.value.aberto = false;
    toast.success('Escalação salva com sucesso.');

    if (modalEscalacao.value.modoPreJogo && fluxoPreJogo.value.ativo) {
      fluxoPreJogo.value.timesPendentes = fluxoPreJogo.value.timesPendentes.filter((id) => id !== modalEscalacao.value.timeId);
      const proximoTime = fluxoPreJogo.value.timesPendentes[0];
      if (proximoTime) {
        abrirModalEscalacao(proximoTime, { modoPreJogo: true });
      } else {
        fluxoPreJogo.value.ativo = false;
        toast.success('Pré-jogo concluído. Agora registre os eventos da partida.');
      }
    }
  } catch (error) {
    toast.error(`Erro ao salvar escalação: ${extrairMensagemErroApi(error)}`);
  } finally {
    modalEscalacao.value.salvando = false;
  }
};

const registrarEvento = async (tipo, jogadorId, timeId, extras = {}) => {
  const atletaPrincipal = obterAtletaDoTime(jogadorId, timeId);
  if (atletaPrincipal && atletaPrincipal.escalado === false) {
    toast.error('Atleta não está escalado para esta partida.');
    return false;
  }
  if (tipo !== 'SUBSTITUICAO' && atletaPrincipal && atletaPrincipal.em_campo === false && !['CARTAO_AMARELO', 'CARTAO_VERMELHO'].includes(tipo)) {
    toast.error('Somente atletas em campo podem receber essa ação.');
    return false;
  }
  if (tipo === 'SUBSTITUICAO' && atletaPrincipal?.em_campo) {
    toast.error('O atleta que entra na substituição deve estar no banco.');
    return false;
  }
  if (atletaPrincipal?.suspenso) {
    toast.error(`Atleta suspenso para esta partida. ${atletaPrincipal.motivo_suspensao || ''}`.trim());
    return false;
  }
  if (extras.jogador_saiu_id) {
    const atletaSaiu = obterAtletaDoTime(extras.jogador_saiu_id, timeId);
    if (atletaSaiu && atletaSaiu.escalado === false) {
      toast.error('Atleta que sai não está escalado para esta partida.');
      return false;
    }
    if (atletaSaiu?.suspenso) {
      toast.error(`Atleta suspenso para esta partida. ${atletaSaiu.motivo_suspensao || ''}`.trim());
      return false;
    }
    if (!atletaSaiu?.em_campo) {
      toast.error('Apenas atletas em campo podem sair na substituição.');
      return false;
    }
  }

  const confirmou = await solicitarConfirmacao({
    titulo: 'Confirmar Evento',
    mensagem: `Registrar ${formatTipoEvento(tipo)} agora?`,
    textoConfirmar: 'Registrar'
  });
  if (!confirmou) return false;
  if (!partida.value?.id) return false;
  const requestId = gerarRequestIdEvento();
  const novoEvento = {
    local_id: requestId,
    request_id: requestId,
    partida_id: partida.value.id,
    jogador_id: jogadorId,
    time_id: timeId,
    tipo_evento: tipo,
    minuto: minutoAtual.value === 0 ? 1 : minutoAtual.value,
    jogador_saiu_id: extras.jogador_saiu_id ?? null,
    observacao: extras.observacao ?? null,
    sincronizado: false
  };
  
  eventos.value.push(novoEvento);
  
  // Salvar na fila de pendentes
  const pendentes = JSON.parse(localStorage.getItem(`sumula_eventos_pendentes_${partida.value.id}`) || '[]');
  pendentes.push(novoEvento);
  localStorage.setItem(`sumula_eventos_pendentes_${partida.value.id}`, JSON.stringify(pendentes));
  if (tipo === 'SUBSTITUICAO') {
    aplicarEstadoSubstituicao(timeId, extras.jogador_saiu_id ?? null, jogadorId);
  }
  
  statusSync.value = 'offline';
  toast.success(`${formatTipoEvento(tipo)} registrado na súmula.`);
  sincronizarDados();
  return true;
};

const abrirSubstituicao = (jogadorSai, timeId) => {
  if (jogadorSai?.escalado === false) {
    toast.error('Atleta não está escalado para esta partida.');
    return;
  }
  if (!jogadorSai?.em_campo) {
    toast.error('Somente atletas em campo podem ser substituídos.');
    return;
  }
  if (jogadorSai?.suspenso) {
    toast.error(`Atleta suspenso para esta partida. ${jogadorSai.motivo_suspensao || ''}`.trim());
    return;
  }
  if (totalSubstituicoesTime(timeId) >= configuracaoPartida.value.maxSubstituicoes) {
    toast.error('Limite máximo de substituições atingido para este time.');
    return;
  }
  modalSubstituicao.value = {
    aberto: true,
    jogadorSai,
    timeId,
    jogadorEntraId: null
  };
};

const confirmarSubstituicao = async () => {
  if (!modalSubstituicao.value.jogadorEntraId || !modalSubstituicao.value.jogadorSai) {
    toast.warning('Selecione o atleta que entra para registrar a substituição.');
    return;
  }
  if (totalSubstituicoesTime(modalSubstituicao.value.timeId) >= configuracaoPartida.value.maxSubstituicoes) {
    toast.error('Limite máximo de substituições atingido para este time.');
    return;
  }

  const registrado = await registrarEvento(
    'SUBSTITUICAO',
    modalSubstituicao.value.jogadorEntraId,
    modalSubstituicao.value.timeId,
    {
      jogador_saiu_id: modalSubstituicao.value.jogadorSai.id,
      observacao: `Saiu: ${modalSubstituicao.value.jogadorSai.nome}`
    }
  );

  if (registrado) {
    modalSubstituicao.value.aberto = false;
    definirFiltroAba('campo');
  }
};

const sincronizarDados = async () => {
  if (!partida.value) return;
  const chavePendentes = `sumula_eventos_pendentes_${partida.value.id}`;
  const pendentes = JSON.parse(localStorage.getItem(chavePendentes) || '[]');
  
  if (pendentes.length === 0) {
    statusSync.value = 'synced';
    return;
  }
  
  statusSync.value = 'syncing';
  
  try {
    const { data } = await api.post(`/arbitro/partidas/${partida.value.id}/eventos/lote`, {
      eventos: pendentes
    });

    const pendentesIds = new Set((data?.pendentes || []).filter(Boolean));
    const restantes = pendentes.filter((evt) => pendentesIds.has(evt.request_id));

    if (restantes.length > 0) localStorage.setItem(chavePendentes, JSON.stringify(restantes));
    else localStorage.removeItem(chavePendentes);

    const errosValidacao = Array.isArray(data?.erros_validacao) ? data.erros_validacao : [];
    if (errosValidacao.length > 0) {
      const mensagens = [...new Set(errosValidacao)].join('\n');
      toast.warning(`Alguns eventos foram descartados por validação:\n${mensagens}`);
    }

    await arbitroService.atualizarPlacar(partida.value.id, placarMandante.value, placarVisitante.value, false);

    statusSync.value = 'synced';

    carregarDadosLocaisOuRemotos(partida.value.id);
  } catch (error) {
    const mensagem = extrairMensagemErroApi(error);
    if (error?.response?.status === 422) {
      toast.warning(`Falha de validação na sincronização:\n${mensagem}`);
    } else {
      toast.error(`Falha ao sincronizar eventos: ${mensagem}`);
    }
    statusSync.value = 'offline';
  }
};

// Canvas Drawing Logic
const canvasArbitro = ref(null);
const canvasMandante = ref(null);
const canvasVisitante = ref(null);
const isDrawing = ref(false);
const currentCtx = ref(null);
const modalFinalizacao = ref(false);
const enviandoFinalizacao = ref(false);

const abrirFinalizacao = () => {
  if (cronometroRodando.value) toggleCronometro();
  modalFinalizacao.value = true;
};

const getCanvasAndCtx = (type) => {
  let cvs;
  if (type === 'arbitro') cvs = canvasArbitro.value;
  else if (type === 'mandante') cvs = canvasMandante.value;
  else if (type === 'visitante') cvs = canvasVisitante.value;
  if (!cvs) return null;
  return { cvs, ctx: cvs.getContext('2d') };
};

const getCoordinates = (e, cvs) => {
  const rect = cvs.getBoundingClientRect();
  const scaleX = cvs.width / rect.width;
  const scaleY = cvs.height / rect.height;

  if (e.touches && e.touches.length > 0) {
    return {
      x: (e.touches[0].clientX - rect.left) * scaleX,
      y: (e.touches[0].clientY - rect.top) * scaleY
    };
  }
  return {
    x: (e.clientX - rect.left) * scaleX,
    y: (e.clientY - rect.top) * scaleY
  };
};

const startDraw = (e, type) => {
  e.preventDefault();
  isDrawing.value = true;
  const data = getCanvasAndCtx(type);
  if (!data) return;
  currentCtx.value = data.ctx;
  currentCtx.value.beginPath();
  const { x, y } = getCoordinates(e, data.cvs);
  currentCtx.value.moveTo(x, y);
  currentCtx.value.lineWidth = 2;
  currentCtx.value.strokeStyle = '#000';
};

const draw = (e, type) => {
  e.preventDefault();
  if (!isDrawing.value) return;
  const data = getCanvasAndCtx(type);
  if (!data) return;
  const { x, y } = getCoordinates(e, data.cvs);
  currentCtx.value.lineTo(x, y);
  currentCtx.value.stroke();
};

const stopDraw = () => {
  isDrawing.value = false;
  currentCtx.value = null;
};

const clearCanvas = (type) => {
  const data = getCanvasAndCtx(type);
  if (data) {
    data.ctx.clearRect(0, 0, data.cvs.width, data.cvs.height);
  }
};

const isCanvasEmpty = (cvs) => {
  if (!cvs) return true;
  const blank = document.createElement('canvas');
  blank.width = cvs.width;
  blank.height = cvs.height;
  return cvs.toDataURL() === blank.toDataURL();
};

const confirmarFinalizacao = async () => {
  // Sincroniza eventos primeiro
  await sincronizarDados();
  
  if (statusSync.value === 'offline') {
    toast.error('Você está offline. Conecte-se para finalizar e assinar a partida.');
    modalFinalizacao.value = false;
    return;
  }
  
  if (isCanvasEmpty(canvasArbitro.value) || isCanvasEmpty(canvasMandante.value) || isCanvasEmpty(canvasVisitante.value)) {
    const confirmouSemAssinaturas = await solicitarConfirmacao({
      titulo: 'Assinaturas incompletas',
      mensagem: 'Existem assinaturas em branco. Deseja finalizar mesmo assim?',
      textoConfirmar: 'Finalizar Mesmo Assim',
      perigoso: true
    });
    if (!confirmouSemAssinaturas) return;
  }

  enviandoFinalizacao.value = true;
  try {
    const assinaturas = {
      arbitro: canvasArbitro.value?.toDataURL('image/png'),
      mandante: canvasMandante.value?.toDataURL('image/png'),
      visitante: canvasVisitante.value?.toDataURL('image/png')
    };

    // Atualiza placar com flag de finalizar e envia assinaturas
    await api.put(`/arbitro/partidas/${partida.value.id}/placar`, {
      placar_mandante: placarMandante.value,
      placar_visitante: placarVisitante.value,
      finalizar: true,
      assinaturas: assinaturas
    });
    
    const { data: pdfData } = await api.get(`/partidas/${partida.value.id}/pdf-sumula`);

    // Limpa storage
    localStorage.removeItem(`sumula_${partida.value.id}_tempo`);

    if (pdfData?.url) {
      window.open(pdfData.url, '_blank', 'noopener');
      toast.success('Partida finalizada com sucesso. A súmula oficial foi aberta em nova aba.');
    } else {
      toast.info('Partida finalizada com sucesso. Não foi possível abrir o PDF automaticamente.');
    }
    router.push('/admin/arbitro/minhas-partidas');
  } catch (error) {
    toast.error(`Erro ao finalizar partida: ${extrairMensagemErroApi(error)}`);
  } finally {
    enviandoFinalizacao.value = false;
  }
};

// Helpers
const getIniciais = (nome) => nome ? nome.substring(0, 2).toUpperCase() : '??';
const formatTipoEvento = (tipo) => {
  const map = {
    GOL: 'GOL',
    GOL_CONTRA: 'Gol Contra',
    CARTAO_AMARELO: 'C. Amarelo',
    CARTAO_VERMELHO: 'C. Vermelho',
    SUBSTITUICAO: 'Substituição'
  };
  return map[tipo] || tipo;
};
const getEventoBadgeClass = (tipo) => {
  if (tipo === 'GOL') return 'bg-green-500 text-white';
  if (tipo === 'GOL_CONTRA') return 'bg-orange-500 text-white';
  if (tipo === 'CARTAO_AMARELO') return 'bg-yellow-400';
  if (tipo === 'CARTAO_VERMELHO') return 'bg-red-500 text-white';
  if (tipo === 'SUBSTITUICAO') return 'bg-blue-500 text-white';
  return 'bg-gray-400';
};
const getEventoBadgeLabel = (tipo) => {
  if (tipo === 'GOL') return '⚽';
  if (tipo === 'GOL_CONTRA') return 'GC';
  if (tipo === 'SUBSTITUICAO') return '⇄';
  return '';
};
const getEventosJogador = (jogadorId) => eventos.value.filter(e => e.jogador_id === jogadorId);
const obterAtletaDoTime = (jogadorId, timeId) => {
  if (timeId === partida.value?.mandante_id) return atletasMandante.value.find((a) => a.id === jogadorId);
  if (timeId === partida.value?.visitante_id) return atletasVisitante.value.find((a) => a.id === jogadorId);
  return [...atletasMandante.value, ...atletasVisitante.value].find((a) => a.id === jogadorId);
};
const getNomeJogador = (id) => {
  const j = [...atletasMandante.value, ...atletasVisitante.value].find(a => a.id === id);
  return j ? j.nome : 'Jogador não encontrado';
};
const getDescricaoEvento = (evento) => {
  if (evento.tipo_evento === 'SUBSTITUICAO') {
    return `${getNomeJogador(evento.jogador_id)} entrou por ${getNomeJogador(evento.jogador_saiu_id)}`;
  }
  if (evento.tipo_evento === 'GOL_CONTRA') {
    return `${getNomeJogador(evento.jogador_id)} (contra)`;
  }
  return getNomeJogador(evento.jogador_id);
};
</script>
