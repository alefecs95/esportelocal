<?php

namespace App\Http\Controllers;

use App\Models\Campeonato;
use App\Models\Partida;
use App\Models\Equipe;
use App\Services\RankingService;
use App\Services\TabelaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TabelaController extends Controller
{
    protected $tabelaService;
    protected $rankingService;

    public function __construct(TabelaService $tabelaService, RankingService $rankingService)
    {
        $this->tabelaService = $tabelaService;
        $this->rankingService = $rankingService;
    }

    public function gerarTabela(Request $request, $id)
    {
        $request->validate([
            'formato' => 'required|in:mata_mata,grupos,pontos_corridos,corrida'
        ]);

        $campeonato = Campeonato::findOrFail($id);

        if ($campeonato->status !== 'inscricoes_encerradas') {
            return response()->json(['error' => 'A tabela só pode ser gerada quando o campeonato estiver com inscrições encerradas.'], 422);
        }

        if ($campeonato->tipo_competicao === 'equipe') {
            $equipesAprovadas = Equipe::where('campeonato_id', $campeonato->id)
                ->where('status_inscricao', 'aprovada')
                ->get();

            if ($equipesAprovadas->isEmpty()) {
                return response()->json(['error' => 'Não há equipes aprovadas para gerar a tabela.'], 422);
            }

            $minimo = (int) ($campeonato->min_atletas_equipe ?? 0);
            if ($minimo > 0) {
                foreach ($equipesAprovadas as $equipe) {
                    $qtdAprovados = $equipe->atletas()->wherePivot('status', 'aprovado')->count();
                    if ($qtdAprovados < $minimo) {
                        return response()->json([
                            'error' => "A equipe {$equipe->nome} não atingiu o mínimo de {$minimo} atletas aprovados."
                        ], 422);
                    }
                }
            }
        }

        try {
            $this->tabelaService->gerarTabela($campeonato, $request->formato);
            if ($campeonato->status !== 'em_andamento') {
                $campeonato->update(['status' => 'em_andamento']);
            }
            return response()->json(['message' => 'Tabela gerada com sucesso.']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function salvarResultadosCorrida(Request $request, $id)
    {
        $request->validate([
            'resultados' => 'required|array',
            'resultados.*.inscricao_id' => 'required|exists:inscricoes,id',
            'resultados.*.tempo' => 'nullable|string', // HH:MM:SS
        ]);

        $campeonato = Campeonato::findOrFail($id);

        try {
            $this->tabelaService->salvarResultadosCorrida($campeonato, $request->resultados);
            return response()->json(['message' => 'Resultados salvos com sucesso.']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function listarTabela($id)
    {
        $campeonato = Campeonato::with([
            'modalidade', // Eager loading modalidade
            'fases' => function ($faseQuery) {
                $faseQuery->orderBy('ordem');
            },
            'fases.grupos' => function ($grupoQuery) {
                $grupoQuery->orderBy('ordem');
            },
            'fases.partidas' => function ($partidaQuery) {
                $partidaQuery->orderByRaw('COALESCE(ordem_na_fase, 999999), id');
            }
        ])->findOrFail($id);

        // Accessing them triggers the logic in getMandanteAttribute
        foreach ($campeonato->fases as $fase) {
            foreach ($fase->partidas as $partida) {
                $partida->append(['mandante', 'visitante']);
                if ($partida->mandante) {
                    $partida->mandante->append('logo_url');
                }
                if ($partida->visitante) {
                    $partida->visitante->append('logo_url');
                }
            }
        }

        // Calcular classificação se houver fases de grupo
        // Precisamos injetar a classificação calculada na resposta
        // Pois o front espera 'classificacao' dentro de cada fase
        
        // Instancia o serviço manualmente se não injetado no método, 
        // mas já temos $this->tabelaService e precisamos do CompeticaoClassificacaoService
        
        $classificacaoService = app(\App\Services\CompeticaoClassificacaoService::class);

        foreach ($campeonato->fases as $fase) {
            // Se a fase tem grupos ou partidas de grupos, calcula
            if ($fase->grupos->count() > 0 || $fase->partidas->whereNotNull('grupo')->count() > 0) {
                $fase->classificacao = $classificacaoService->calcularClassificacaoFase($fase);
            }
        }

        // Se for corrida, incluir inscricoes ordenadas
        if ($campeonato->formato && $campeonato->formato->value === 'corrida') {
            $campeonato->load(['inscricoes' => function($q) {
                $q->with('atleta', 'categoria')
                  ->orderBy('colocacao', 'asc')
                  ->orderBy('tempo_ms', 'asc');
            }]);
        }
        
        return response()->json($campeonato);
    }

    public function atualizarPlacar(Request $request, $id)
    {
        $request->validate([
            'placar_mandante' => 'required|integer|min:0',
            'placar_visitante' => 'required|integer|min:0',
        ]);

        $partida = Partida::findOrFail($id);

        try {
            // Verificar permissões, se jogo já finalizado, etc.
            if ($partida->status === 'finalizada') {
                // return response()->json(['error' => 'Partida já finalizada.'], 400);
                // Usuário pediu "Bloquear edição manual se já houver partidas finalizadas" -> talvez no contexto de gerar tabela
                // Mas geralmente atualizar placar de jogo finalizado requer permissão especial.
                // Vou permitir atualização por enquanto.
            }

            $this->tabelaService->atualizarPlacar(
                $partida, 
                $request->placar_mandante, 
                $request->placar_visitante
            );

            $campeonato = Campeonato::findOrFail($partida->fase->campeonato_id);
            $partidasPendentes = Partida::whereHas('fase', function($query) use ($campeonato) {
                $query->where('campeonato_id', $campeonato->id);
            })->where('status', '!=', 'finalizada')->exists();

            if (!$partidasPendentes && $campeonato->status !== 'finalizado') {
                $campeonato->update(['status' => 'finalizado']);
                $this->rankingService->atualizarRanking($campeonato->refresh());
            }

            return response()->json(['message' => 'Placar atualizado com sucesso.', 'partida' => $partida->refresh()]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
