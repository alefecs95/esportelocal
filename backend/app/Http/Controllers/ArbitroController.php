<?php

namespace App\Http\Controllers;

use App\Models\Partida;
use App\Models\Campeonato;
use App\Models\PartidaEscalacao;
use App\Models\Equipe;
use App\Services\EventoPartidaService;
use App\Services\RankingService;
use App\Services\TabelaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ArbitroController extends Controller
{
    protected $tabelaService;
    protected $eventoPartidaService;
    protected $rankingService;

    public function __construct(
        TabelaService $tabelaService,
        EventoPartidaService $eventoPartidaService,
        RankingService $rankingService
    )
    {
        $this->middleware('auth:sanctum');
        $this->tabelaService = $tabelaService;
        $this->eventoPartidaService = $eventoPartidaService;
        $this->rankingService = $rankingService;
    }

    public function minhasPartidas(Request $request)
    {
        $user = Auth::guard('sanctum')->user();
        if (!$user) {
            return response()->json(['message' => 'Não autenticado.'], 401);
        }
        
        // Buscamos apenas as partidas deste árbitro
        $partidas = Partida::where('arbitro_id', $user->id)
            ->with(['fase.campeonato']) // Carregar dados do campeonato
            ->orderBy('data_jogo', 'asc')
            ->get();
            
        // Usar map para formatar as partidas de forma segura
        $partidasFormatadas = $partidas->map(function($partida) {
            // Em vez de usar toArray que pode engatilhar accessors com erro, montamos manualmente
            $fase = null;
            try {
                if ($partida->relationLoaded('fase') && $partida->fase) {
                     $fase = $partida->fase->toArray();
                } elseif ($partida->fase_id) {
                     $faseObj = \App\Models\Fase::find($partida->fase_id);
                     if ($faseObj) $fase = $faseObj->toArray();
                }
            } catch (\Throwable $e) {
                // Silently fail if fase is problematic
            }

            $array = [
                'id' => $partida->id,
                'fase_id' => $partida->fase_id,
                'data_jogo' => $partida->data_jogo,
                'local' => $partida->local,
                'status' => $partida->status,
                'placar_mandante' => $partida->placar_mandante,
                'placar_visitante' => $partida->placar_visitante,
                'arbitro_id' => $partida->arbitro_id,
                'fase' => $fase,
            ];
            
            // Tentar obter mandante e visitante de forma segura
            try {
                // Verificar se getMandanteAttribute existe e não lança exceção
                $mandante = null;
                if (!empty($partida->mandante_id)) {
                     // Check if relationLoaded to avoid N+1 issues or loops
                     $mandante = $partida->getMandanteAttribute();
                }
                $array['mandante'] = $mandante;

                $visitante = null;
                if (!empty($partida->visitante_id)) {
                     $visitante = $partida->getVisitanteAttribute();
                }
                $array['visitante'] = $visitante;

            } catch (\Throwable $e) {
                // Se falhar, enviamos nulo mas não quebra a requisição
                $array['mandante'] = null;
                $array['visitante'] = null;
                Log::error('Erro ao carregar mandante/visitante na partida ' . $partida->id . ': ' . $e->getMessage());
            }
            
            return $array;
        });

        return response()->json($partidasFormatadas);
    }

    public function storeEvento(Request $request, $id)
    {
        $request->validate([
            'jogador_id' => 'required|exists:atletas,id',
            'jogador_saiu_id' => 'nullable|exists:atletas,id|required_if:tipo_evento,SUBSTITUICAO',
            'time_id' => 'required|exists:equipes,id',
            'request_id' => 'nullable|string|max:100',
            'tipo_evento' => 'required|string|in:GOL,GOL_CONTRA,CARTAO_AMARELO,CARTAO_VERMELHO,SUBSTITUICAO',
            'minuto' => 'required|integer|min:0',
        ]);

        $partida = Partida::findOrFail($id);

        if ($partida->arbitro_id !== Auth::guard('sanctum')->id()) {
            return response()->json(['error' => 'Você não é o árbitro desta partida.'], 403);
        }

        if ($partida->placar_bloqueado || $partida->status === 'finalizada') {
            return response()->json(['error' => 'Partida já finalizada.'], 400);
        }

        try {
            $evento = $this->eventoPartidaService->criarEvento([
                'partida_id' => $partida->id,
                'jogador_id' => $request->jogador_id,
                'jogador_saiu_id' => $request->jogador_saiu_id,
                'time_id' => $request->time_id,
                'request_id' => $request->request_id,
                'tipo_evento' => $request->tipo_evento,
                'minuto' => $request->minuto,
                'observacao' => $request->observacao
            ]);

            return response()->json($evento, 201);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function storeEventosLote(Request $request, $id)
    {
        $request->validate([
            'eventos' => 'required|array|min:1',
            'eventos.*.jogador_id' => 'required|exists:atletas,id',
            'eventos.*.jogador_saiu_id' => 'nullable|exists:atletas,id|required_if:eventos.*.tipo_evento,SUBSTITUICAO',
            'eventos.*.time_id' => 'required|exists:equipes,id',
            'eventos.*.request_id' => 'nullable|string|max:100',
            'eventos.*.tipo_evento' => 'required|string|in:GOL,GOL_CONTRA,CARTAO_AMARELO,CARTAO_VERMELHO,SUBSTITUICAO',
            'eventos.*.minuto' => 'required|integer|min:0',
            'eventos.*.observacao' => 'nullable|string',
        ]);

        $partida = Partida::findOrFail($id);

        if ($partida->arbitro_id !== Auth::guard('sanctum')->id()) {
            return response()->json(['error' => 'Você não é o árbitro desta partida.'], 403);
        }

        if ($partida->placar_bloqueado || $partida->status === 'finalizada') {
            return response()->json(['error' => 'Partida já finalizada.'], 400);
        }

        $sincronizados = [];
        $descartados = [];
        $errosValidacao = [];
        $pendentes = [];
        $eventos = $request->input('eventos', []);

        for ($i = 0; $i < count($eventos); $i++) {
            $eventoPayload = $eventos[$i];

            try {
                $this->eventoPartidaService->criarEvento([
                    'partida_id' => $partida->id,
                    'jogador_id' => $eventoPayload['jogador_id'],
                    'jogador_saiu_id' => $eventoPayload['jogador_saiu_id'] ?? null,
                    'time_id' => $eventoPayload['time_id'],
                    'request_id' => $eventoPayload['request_id'] ?? null,
                    'tipo_evento' => $eventoPayload['tipo_evento'],
                    'minuto' => $eventoPayload['minuto'],
                    'observacao' => $eventoPayload['observacao'] ?? null,
                ]);

                if (!empty($eventoPayload['request_id'])) {
                    $sincronizados[] = $eventoPayload['request_id'];
                }
            } catch (ValidationException $e) {
                if (!empty($eventoPayload['request_id'])) {
                    $descartados[] = $eventoPayload['request_id'];
                }
                $erros = $e->errors();
                $primeiraLista = collect($erros)->first();
                $errosValidacao[] = is_array($primeiraLista) && count($primeiraLista) > 0
                    ? $primeiraLista[0]
                    : 'Evento inválido.';
                continue;
            } catch (\Exception $e) {
                for ($j = $i; $j < count($eventos); $j++) {
                    if (!empty($eventos[$j]['request_id'])) {
                        $pendentes[] = $eventos[$j]['request_id'];
                    }
                }
                break;
            }
        }

        return response()->json([
            'sincronizados' => array_values(array_unique($sincronizados)),
            'descartados' => array_values(array_unique($descartados)),
            'erros_validacao' => array_values(array_unique($errosValidacao)),
            'pendentes' => array_values(array_unique($pendentes)),
        ]);
    }

    public function atualizarPlacar(Request $request, $id)
    {
        $request->validate([
            'placar_mandante' => 'required|integer|min:0',
            'placar_visitante' => 'required|integer|min:0',
            'finalizar' => 'boolean',
            'assinaturas' => 'nullable|array',
            'assinaturas.arbitro' => 'nullable|string',
            'assinaturas.mandante' => 'nullable|string',
            'assinaturas.visitante' => 'nullable|string',
        ]);

        $partida = Partida::findOrFail($id);

        // Verificar permissão
        if ($partida->arbitro_id !== Auth::guard('sanctum')->id()) {
            return response()->json(['error' => 'Você não é o árbitro desta partida.'], 403);
        }

        if ($partida->placar_bloqueado || $partida->status === 'finalizada') {
            return response()->json(['error' => 'Partida já finalizada.'], 400);
        }

        try {
            DB::transaction(function () use ($request, $partida) {
                $this->tabelaService->atualizarPlacar(
                    $partida,
                    $request->placar_mandante,
                    $request->placar_visitante,
                    $request->boolean('finalizar')
                );

                if ($request->boolean('finalizar')) {
                    if ($request->has('assinaturas')) {
                        $partida->update([
                            'assinatura_arbitro' => $request->input('assinaturas.arbitro'),
                            'assinatura_mandante' => $request->input('assinaturas.mandante'),
                            'assinatura_visitante' => $request->input('assinaturas.visitante'),
                        ]);
                    }
                    $this->tabelaService->finalizarPartida($partida);
                    $this->finalizarCampeonatoSeConcluido($partida);
                }
            });

            return response()->json(['message' => 'Placar atualizado com sucesso.', 'partida' => $partida->refresh()]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function getEscalacao($id)
    {
        $partida = Partida::with(['escalacoes.atleta'])->findOrFail($id);

        if ($partida->arbitro_id !== Auth::guard('sanctum')->id()) {
            return response()->json(['error' => 'Você não é o árbitro desta partida.'], 403);
        }

        $escalacoes = $partida->escalacoes->sortBy([
            ['titular', 'desc'],
            ['ordem', 'asc'],
        ])->values();

        return response()->json([
            'mandante' => $escalacoes->where('equipe_id', $partida->mandante_id)->values(),
            'visitante' => $escalacoes->where('equipe_id', $partida->visitante_id)->values(),
        ]);
    }

    public function salvarEscalacao(Request $request, $id)
    {
        $request->validate([
            'time_id' => 'required|exists:equipes,id',
            'atletas' => 'required|array|min:1',
            'atletas.*.atleta_id' => 'required|exists:atletas,id',
            'atletas.*.titular' => 'required|boolean',
            'atletas.*.capitao' => 'nullable|boolean',
        ]);

        $partida = Partida::with('fase.campeonato')->findOrFail($id);

        if ($partida->arbitro_id !== Auth::guard('sanctum')->id()) {
            return response()->json(['error' => 'Você não é o árbitro desta partida.'], 403);
        }

        if ($partida->placar_bloqueado || $partida->status === 'finalizada') {
            return response()->json(['error' => 'Partida já finalizada.'], 400);
        }

        $timeId = (int) $request->input('time_id');
        if (!in_array($timeId, [(int) $partida->mandante_id, (int) $partida->visitante_id], true)) {
            return response()->json(['error' => 'Equipe informada não participa desta partida.'], 422);
        }

        $atletasPayload = collect($request->input('atletas', []));
        $atletasIds = $atletasPayload->pluck('atleta_id')->map(fn ($id) => (int) $id)->values();
        if ($atletasIds->count() !== $atletasIds->unique()->count()) {
            return response()->json(['error' => 'Existem atletas repetidos na escalação.'], 422);
        }

        $qtdCapitaes = $atletasPayload->filter(fn ($item) => (bool) ($item['capitao'] ?? false))->count();
        if ($qtdCapitaes > 1) {
            return response()->json(['error' => 'A escalação deve possuir no máximo um capitão.'], 422);
        }

        $equipe = Equipe::findOrFail($timeId);
        $aprovadosIds = $equipe->atletas()
            ->wherePivot('status', 'aprovado')
            ->pluck('atletas.id')
            ->map(fn ($id) => (int) $id);

        $foraDoElenco = $atletasIds->diff($aprovadosIds);
        if ($foraDoElenco->isNotEmpty()) {
            return response()->json(['error' => 'A escalação contém atleta fora do elenco aprovado.'], 422);
        }

        foreach ($atletasPayload as $item) {
            $atletaId = (int) ($item['atleta_id'] ?? 0);
            $status = $this->eventoPartidaService->obterStatusSuspensaoPartida($partida, $atletaId, $timeId);
            if ($status['suspenso']) {
                return response()->json([
                    'error' => "Atleta suspenso não pode ser escalado. {$status['motivo']}"
                ], 422);
            }
        }

        DB::transaction(function () use ($partida, $timeId, $atletasPayload) {
            PartidaEscalacao::where('partida_id', $partida->id)
                ->where('equipe_id', $timeId)
                ->delete();

            $ordem = 1;
            foreach ($atletasPayload as $item) {
                PartidaEscalacao::create([
                    'partida_id' => $partida->id,
                    'equipe_id' => $timeId,
                    'atleta_id' => (int) $item['atleta_id'],
                    'titular' => (bool) $item['titular'],
                    'capitao' => (bool) ($item['capitao'] ?? false),
                    'ordem' => $ordem,
                ]);
                $ordem++;
            }
        });

        $escalacoes = PartidaEscalacao::with('atleta')
            ->where('partida_id', $partida->id)
            ->where('equipe_id', $timeId)
            ->orderByDesc('titular')
            ->orderBy('ordem')
            ->get();

        return response()->json([
            'message' => 'Escalação salva com sucesso.',
            'time_id' => $timeId,
            'escalacao' => $escalacoes,
        ]);
    }

    private function finalizarCampeonatoSeConcluido(Partida $partida): void
    {
        $partida->loadMissing('fase');
        $campeonatoId = $partida->fase?->campeonato_id;
        if (!$campeonatoId) {
            return;
        }

        $campeonato = Campeonato::find($campeonatoId);
        if (!$campeonato) {
            return;
        }

        $partidasPendentes = Partida::whereHas('fase', function ($query) use ($campeonatoId) {
            $query->where('campeonato_id', $campeonatoId);
        })->where('status', '!=', 'finalizada')->exists();

        if (!$partidasPendentes && $campeonato->status !== 'finalizado') {
            $campeonato->update(['status' => 'finalizado']);
            $this->rankingService->atualizarRanking($campeonato->refresh());
        }
    }
}
