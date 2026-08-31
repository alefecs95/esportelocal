<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\RankingAtleta;
use App\Models\RankingEquipe;
use App\Models\Temporada;
use App\Services\RankingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RankingController extends Controller
{
    protected $rankingService;

    public function __construct(RankingService $rankingService)
    {
        $this->rankingService = $rankingService;
    }

    /**
     * Retorna a temporada ativa.
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function temporadaAtiva()
    {
        $temporada = Temporada::where('status', 'ativa')->first();
        if (!$temporada) {
            return response()->json(['message' => 'Nenhuma temporada ativa encontrada.'], 404);
        }
        return response()->json($temporada);
    }

    /**
     * Recalcula o ranking da temporada ativa.
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function recalcular(Request $request)
    {
        // TODO: Validar permissões de ADMIN ou SUPER_ADMIN
        // if (!$request->user()->isAdmin()) { ... }

        $temporada = Temporada::where('status', 'ativa')->first();
        if (!$temporada) {
            return response()->json(['message' => 'Nenhuma temporada ativa encontrada.'], 404);
        }

        try {
            $this->rankingService->recalcularRankingTemporada($temporada->id);
            return response()->json(['message' => 'Ranking recalculado com sucesso.']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erro ao recalcular ranking.', 'error' => $e->getMessage()], 500);
        }
    }
    /**
     * Retorna o ranking geral da temporada (soma de todas as modalidades).
     * 
     * @param int $temporadaId
     * @return \Illuminate\Http\JsonResponse
     */
    public function index($temporadaId)
    {
        $type = request('type', 'atleta'); // 'atleta' ou 'equipe'

        if ($type === 'equipe') {
            $ranking = RankingEquipe::where('temporada_id', $temporadaId)
                ->with('equipe')
                ->select('equipe_id', 
                    DB::raw('SUM(pontos) as total_pontos'), 
                    DB::raw('SUM(participacoes) as total_participacoes'),
                    DB::raw('SUM(titulos) as total_titulos')
                )
                ->groupBy('equipe_id')
                ->orderByDesc('total_pontos')
                ->get();
        } else {
            $ranking = RankingAtleta::where('temporada_id', $temporadaId)
                ->with('atleta')
                ->select('atleta_id', 
                    DB::raw('SUM(pontos) as total_pontos'), 
                    DB::raw('SUM(participacoes) as total_participacoes'),
                    DB::raw('SUM(titulos) as total_titulos')
                )
                ->groupBy('atleta_id')
                ->orderByDesc('total_pontos')
                ->get();
        }

        return response()->json($ranking);
    }

    /**
     * Retorna o ranking de uma modalidade específica na temporada.
     * 
     * @param int $temporadaId
     * @param int $modalidadeId
     * @return \Illuminate\Http\JsonResponse
     */
    public function porModalidade($temporadaId, $modalidadeId)
    {
        // Aqui podemos detectar automaticamente se a modalidade é individual ou equipe?
        // Ou aceitamos o parametro type?
        // O ideal seria verificar o tipo da modalidade no banco, mas para simplificar vamos aceitar type.
        // Se não passado, tentamos ambos ou retornamos erro?
        // Vamos assumir que o frontend sabe o que pedir.
        
        $type = request('type', 'atleta');

        if ($type === 'equipe') {
            $ranking = RankingEquipe::where('temporada_id', $temporadaId)
                ->where('modalidade_id', $modalidadeId)
                ->with('equipe')
                ->orderByDesc('pontos')
                ->get();
        } else {
            $ranking = RankingAtleta::where('temporada_id', $temporadaId)
                ->where('modalidade_id', $modalidadeId)
                ->with('atleta')
                ->orderByDesc('pontos')
                ->get();
        }

        return response()->json($ranking);
    }

    /**
     * Retorna o Top 10 geral da temporada.
     * 
     * @param int $temporadaId
     * @return \Illuminate\Http\JsonResponse
     */
    public function top10($temporadaId)
    {
        $type = request('type', 'atleta');

        if ($type === 'equipe') {
            $ranking = RankingEquipe::where('temporada_id', $temporadaId)
                ->with('equipe')
                ->select('equipe_id', 
                    DB::raw('SUM(pontos) as total_pontos'), 
                    DB::raw('SUM(participacoes) as total_participacoes'),
                    DB::raw('SUM(titulos) as total_titulos')
                )
                ->groupBy('equipe_id')
                ->orderByDesc('total_pontos')
                ->take(10)
                ->get();
        } else {
            $ranking = RankingAtleta::where('temporada_id', $temporadaId)
                ->with('atleta')
                ->select('atleta_id', 
                    DB::raw('SUM(pontos) as total_pontos'), 
                    DB::raw('SUM(participacoes) as total_participacoes'),
                    DB::raw('SUM(titulos) as total_titulos')
                )
                ->groupBy('atleta_id')
                ->orderByDesc('total_pontos')
                ->take(10)
                ->get();
        }

        return response()->json($ranking);
    }
}
