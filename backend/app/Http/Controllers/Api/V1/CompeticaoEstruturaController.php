<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\CriterioClassificacaoFase;
use App\Enums\OrigemConfrontoTipo;
use App\Http\Controllers\Controller;
use App\Models\Campeonato;
use App\Models\Fase;
use App\Services\CompeticaoClassificacaoService;
use App\Services\CompeticaoEstruturaService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class CompeticaoEstruturaController extends Controller
{
    public function __construct(
        private CompeticaoEstruturaService $service,
        private CompeticaoClassificacaoService $classificacaoService
    ) {
    }

    public function show(int $campeonatoId): JsonResponse
    {
        $campeonato = Campeonato::findOrFail($campeonatoId);
        $estrutura = $this->service->obterEstrutura($campeonato);

        return response()->json([
            'success' => true,
            'message' => 'Estrutura da competição carregada com sucesso.',
            'data' => $estrutura,
        ]);
    }

    public function update(Request $request, int $campeonatoId): JsonResponse
    {
        $tiposOrigem = OrigemConfrontoTipo::values();

        $request->validate([
            'fases' => 'required|array|min:1',
            'fases.*.id' => 'nullable|integer|exists:fases,id',
            'fases.*.nome' => 'required|string|max:255',
            'fases.*.ordem' => 'nullable|integer|min:1',
            'fases.*.tipo' => ['nullable', Rule::in(['grupos', 'mata_mata', 'pontos_corridos', 'custom'])],
            'fases.*.status' => ['nullable', Rule::in(['planejada', 'ativa', 'encerrada'])],
            'fases.*.configuracao' => 'nullable|array',
            'fases.*.permite_edicao_manual' => 'nullable|boolean',
            'fases.*.grupos' => 'nullable|array',
            'fases.*.grupos.*.id' => 'nullable|integer|exists:fase_grupos,id',
            'fases.*.grupos.*.nome' => 'required|string|max:20',
            'fases.*.grupos.*.ordem' => 'nullable|integer|min:1',
            'fases.*.grupos.*.configuracao' => 'nullable|array',
            'fases.*.partidas' => 'nullable|array',
            'fases.*.partidas.*.id' => 'nullable|integer|exists:partidas,id',
            'fases.*.partidas.*.fase_grupo_id' => 'nullable|integer|exists:fase_grupos,id',
            'fases.*.partidas.*.mandante_id' => 'nullable|integer',
            'fases.*.partidas.*.visitante_id' => 'nullable|integer',
            'fases.*.partidas.*.arbitro_id' => 'nullable|integer|exists:users,id',
            'fases.*.partidas.*.status' => ['nullable', Rule::in(['agendada', 'em_andamento', 'finalizada'])],
            'fases.*.partidas.*.data_jogo' => ['nullable', 'regex:/^\d{4}-\d{2}-\d{2}( \d{2}:\d{2}:\d{2}|T\d{2}:\d{2}(:\d{2})?)$/'],
            'fases.*.partidas.*.origem_mandante' => 'nullable|array',
            'fases.*.partidas.*.origem_mandante.type' => ['nullable', Rule::in($tiposOrigem)],
            'fases.*.partidas.*.origem_mandante.payload' => 'nullable|array',
            'fases.*.partidas.*.origem_visitante' => 'nullable|array',
            'fases.*.partidas.*.origem_visitante.type' => ['nullable', Rule::in($tiposOrigem)],
            'fases.*.partidas.*.origem_visitante.payload' => 'nullable|array',
            'fases.*.partidas.*.definido_manualmente' => 'nullable|boolean',
        ], [
            'fases.*.partidas.*.data_jogo.regex' => 'Data/Hora da partida inválida. Use YYYY-MM-DD HH:MM:SS ou YYYY-MM-DDTHH:MM.',
        ]);

        $campeonato = Campeonato::findOrFail($campeonatoId);
        try {
            $estrutura = $this->service->salvarEstrutura($campeonato, $request->all());
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Estrutura da competição atualizada com sucesso.',
            'data' => $estrutura,
        ]);
    }

    public function sugerirConfrontos(Request $request, int $campeonatoId): JsonResponse
    {
        $request->validate([
            'fase_id' => 'nullable|integer|exists:fases,id',
            'persistir' => 'nullable|boolean',
        ]);

        $campeonato = Campeonato::findOrFail($campeonatoId);
        try {
            $sugestoes = $this->service->sugerirConfrontos($campeonato, $request->all());
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Sugestões de confrontos geradas com sucesso.',
            'data' => $sugestoes,
        ]);
    }

    public function resolverClassificados(int $campeonatoId): JsonResponse
    {
        $campeonato = Campeonato::findOrFail($campeonatoId);
        try {
            $atualizados = $this->service->aplicarClassificacaoNasFases($campeonato);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => count($atualizados) . ' confrontos foram atualizados com base na classificação.',
            'data' => $atualizados,
        ]);
    }

    public function criteriosClassificacao(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Critérios de classificação listados com sucesso.',
            'data' => CriterioClassificacaoFase::values(),
        ]);
    }

    public function getRegrasClassificacao(int $faseId): JsonResponse
    {
        $fase = Fase::findOrFail($faseId);
        $regras = $this->service->listarRegrasClassificacao($fase);

        return response()->json([
            'success' => true,
            'message' => 'Regras de classificação listadas com sucesso.',
            'data' => $regras,
        ]);
    }

    public function putRegrasClassificacao(Request $request, int $faseId): JsonResponse
    {
        $request->validate([
            'regras' => 'required|array',
            'regras.*.id' => 'nullable|integer|exists:fase_regras_classificacao,id',
            'regras.*.criterio' => ['required', Rule::in(CriterioClassificacaoFase::values())],
            'regras.*.ordem' => 'nullable|integer|min:1',
            'regras.*.configuracao' => 'nullable|array',
        ]);

        $fase = Fase::findOrFail($faseId);
        try {
            $regras = $this->service->salvarRegrasClassificacao($fase, $request->all());
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Regras de classificação atualizadas com sucesso.',
            'data' => $regras,
        ]);
    }

    public function getClassificacaoFase(int $faseId): JsonResponse
    {
        $fase = Fase::findOrFail($faseId);
        $classificacao = $this->classificacaoService->calcularClassificacaoFase($fase);

        return response()->json([
            'success' => true,
            'message' => 'Classificação da fase carregada com sucesso.',
            'data' => $classificacao,
        ]);
    }
}
