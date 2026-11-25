<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Campeonato;
use App\Models\Inscricao;
use App\Services\PremiacaoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PremiacaoController extends Controller
{
    public function __construct(private PremiacaoService $premiacaoService)
    {
    }

    private function response(bool $success, string $message, $data = [], int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => $success,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    /** GET campeonatos/{id}/premiacao (admin) */
    public function podio(int $id): JsonResponse
    {
        $campeonato = Campeonato::with('categorias')->find($id);
        if (!$campeonato) {
            return $this->response(false, 'Campeonato não encontrado.', [], 404);
        }

        return $this->response(true, 'Pódio calculado com sucesso.', $this->premiacaoService->montarPodio($campeonato));
    }

    /** POST inscricoes/{id}/premiar (admin) */
    public function premiar(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'premiacao_descricao' => 'nullable|string|max:255',
        ]);

        $inscricao = Inscricao::find($id);
        if (!$inscricao) {
            return $this->response(false, 'Inscrição não encontrada.', [], 404);
        }

        try {
            $atualizada = $this->premiacaoService->registrarPremiacao($inscricao, $request->input('premiacao_descricao'));
            return $this->response(true, 'Premiação registrada com sucesso.', $atualizada);
        } catch (\InvalidArgumentException $e) {
            return $this->response(false, $e->getMessage(), [], 422);
        }
    }

    /** POST inscricoes/{id}/marcar-chegada (admin) */
    public function marcarChegada(int $id): JsonResponse
    {
        $inscricao = Inscricao::find($id);
        if (!$inscricao) {
            return $this->response(false, 'Inscrição não encontrada.', [], 404);
        }

        try {
            $atualizada = $this->premiacaoService->marcarFinalizacaoManual($inscricao);
            return $this->response(true, 'Chegada registrada com sucesso.', $atualizada);
        } catch (\InvalidArgumentException $e) {
            return $this->response(false, $e->getMessage(), [], 422);
        }
    }

    /** GET campeonatos/{id}/medalhas-finisher (admin) */
    public function medalhasFinisher(int $id): JsonResponse
    {
        $campeonato = Campeonato::find($id);
        if (!$campeonato) {
            return $this->response(false, 'Campeonato não encontrado.', [], 404);
        }

        return $this->response(true, 'Lista de medalha finisher.', $this->premiacaoService->medalhasFinisher($campeonato));
    }

    /**
     * GET public/campeonatos/{id}/resultados-corrida
     * Resultados públicos por categoria, sem CPF, telefone ou dados de contato.
     */
    public function resultadosPublicos(string $id): JsonResponse
    {
        $campeonato = Campeonato::resolvePublic($id, ['categorias']);
        if (!$campeonato) {
            return $this->response(false, 'Campeonato não encontrado.', [], 404);
        }

        $inscricoes = Inscricao::with(['atleta:id,nome', 'categoria:id,nome,distancia'])
            ->where('campeonato_id', $campeonato->id)
            ->whereNotNull('tempo_ms')
            ->orderBy('tempo_ms')
            ->get()
            ->map(fn (Inscricao $i) => [
                'id' => $i->id,
                'atleta_nome' => $i->atleta?->nome,
                'categoria_id' => $i->campeonato_categoria_id,
                'categoria_nome' => $i->categoria?->nome ?? 'Geral',
                'distancia' => $i->categoria?->distancia,
                'tempo_visual' => $i->tempo_visual,
                'colocacao' => $i->colocacao,
                'colocacao_categoria' => $i->colocacao_categoria,
                'premiado' => $i->premiado_em !== null,
            ])
            ->values();

        return $this->response(true, 'Resultados carregados com sucesso.', [
            'campeonato' => ['id' => $campeonato->id, 'nome' => $campeonato->nome],
            'resultados' => $inscricoes,
        ]);
    }
}
