<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\CampeonatoService;
use App\Http\Requests\StoreCampeonatoRequest;
use App\Http\Requests\UpdateCampeonatoRequest;
use App\Enums\FormatoCampeonato;
use App\Services\WhatsAppNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CampeonatoController extends Controller
{
    protected $campeonatoService;

    public function __construct(
        CampeonatoService $campeonatoService,
        private WhatsAppNotificationService $whatsAppNotificationService
    ) {
        $this->campeonatoService = $campeonatoService;
    }

    private function response(bool $success, string $message, $data = [], int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => $success,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    /**
     * Retorna os formatos de campeonato disponíveis (Enum)
     */
    public function getFormatos(): JsonResponse
    {
        return response()->json(FormatoCampeonato::toArray());
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['modalidade_id', 'status', 'categoria', 'data_inicio', 'data_fim', 'temporada_id']);
        $perPage = $request->get('per_page', 10);
        
        $campeonatos = $this->campeonatoService->getAll($filters, $perPage);
        
        return $this->response(true, 'Campeonatos listados com sucesso', $campeonatos);
    }

    public function indexPublic(Request $request): JsonResponse
    {
        $filters = $request->only([
            'modalidade_id', 
            'temporada_id', 
            'status', 
            'categoria', 
            'cidade',
            'bairro'
        ]);
        
        $query = \App\Models\Campeonato::with(['modalidade', 'temporada'])
            ->where('status', '!=', 'cancelado');

        if (!empty($filters['modalidade_id'])) {
            $query->where('modalidade_id', $filters['modalidade_id']);
        }

        if (!empty($filters['temporada_id'])) {
            $query->where('temporada_id', $filters['temporada_id']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['categoria'])) {
            $query->where('categoria', 'like', '%' . $filters['categoria'] . '%');
        }

        if (!empty($filters['cidade'])) {
            $query->where('cidade_eleitoral', 'like', '%' . $filters['cidade'] . '%');
        }

        if (!empty($filters['bairro'])) {
            $query->where('bairro_eleitoral', 'like', '%' . $filters['bairro'] . '%');
        }

        // Ordenar por data de início decrescente (mais recentes primeiro)
        $campeonatos = $query->orderBy('data_inicio', 'desc')
            ->paginate($request->get('per_page', 12));
            
        return $this->response(true, 'Campeonatos listados com sucesso', $campeonatos);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCampeonatoRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            if ($request->hasFile('banner')) {
                $data['banner'] = $request->file('banner')->store('campeonatos/banners', 'public');
            }
            $campeonato = $this->campeonatoService->create($data);
            if ($campeonato->status === 'inscricoes_abertas') {
                $this->whatsAppNotificationService->notifyCompetitionCreated($campeonato);
            }
            return $this->response(true, 'Campeonato criado com sucesso', $campeonato, 201);
        } catch (\Exception $e) {
            return $this->response(false, 'Erro ao criar campeonato: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $campeonato = $this->campeonatoService->find($id);
            return $this->response(true, 'Campeonato encontrado com sucesso', $campeonato);
        } catch (\Exception $e) {
            return $this->response(false, 'Campeonato não encontrado', [], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCampeonatoRequest $request, string $id): JsonResponse
    {
        try {
            $campeonatoAtual = $this->campeonatoService->find($id);
            $statusAnterior = $campeonatoAtual->status;
            $data = $request->validated();
            if ($request->hasFile('banner')) {
                $data['banner'] = $request->file('banner')->store('campeonatos/banners', 'public');
                if (!empty($campeonatoAtual->banner)) {
                    Storage::disk('public')->delete($campeonatoAtual->banner);
                }
            }
            $campeonato = $this->campeonatoService->update($id, $data);
            if ($statusAnterior !== 'inscricoes_abertas' && $campeonato->status === 'inscricoes_abertas') {
                $this->whatsAppNotificationService->notifyCompetitionCreated($campeonato);
            }
            return $this->response(true, 'Campeonato atualizado com sucesso', $campeonato);
        } catch (\Exception $e) {
            return $this->response(false, 'Erro ao atualizar campeonato: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $this->campeonatoService->delete($id);
            return $this->response(true, 'Campeonato excluído com sucesso');
        } catch (\Exception $e) {
            return $this->response(false, 'Erro ao excluir campeonato: ' . $e->getMessage(), [], 500);
        }
    }

    public function simularElegibilidade(Request $request)
    {
        if ($request->filled('atleta_id') && $request->filled('campeonato_id')) {
            $request->validate([
                'atleta_id' => 'required|exists:atletas,id',
                'campeonato_id' => 'required|exists:campeonatos,id',
            ]);

            $resultado = $this->campeonatoService->verificarElegibilidade(
                (int) $request->atleta_id,
                (int) $request->campeonato_id
            );

            return response()->json([
                'success' => true,
                'data' => $resultado,
            ]);
        }

        $request->validate([
            'idade_min' => 'nullable|integer|min:0',
            'idade_max' => 'nullable|integer|min:0',
            'cidade_eleitoral' => 'nullable|string|max:255',
            'bairro_eleitoral' => 'nullable|string|max:255',
            'genero' => 'nullable|in:masculino,feminino,misto',
        ]);

        $returnList = $request->boolean('return_list');
        $resultado = $this->campeonatoService->simularElegiveis($request->only([
            'idade_min',
            'idade_max',
            'cidade_eleitoral',
            'bairro_eleitoral',
            'genero',
        ]), $returnList);

        if ($returnList) {
            return response()->json([
                'success' => true,
                'data' => [
                    'total_elegiveis' => $resultado->count(),
                    'atletas' => $resultado,
                ],
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'total_elegiveis' => $resultado,
            ],
        ]);
    }

    public function camposCadastro(int $id): JsonResponse
    {
        $campeonato = \App\Models\Campeonato::with(['modalidade', 'categorias'])->find($id);
        if (!$campeonato) {
            return $this->response(false, 'Campeonato não encontrado', [], 404);
        }

        /** @var \App\Services\CadastroCamposService $cadastroService */
        $cadastroService = app(\App\Services\CadastroCamposService::class);
        $config = $cadastroService->getConfiguracaoEfetiva($campeonato);

        return $this->response(true, 'Configuração de campos carregada com sucesso', [
            ...$config,
            'campeonato' => [
                'id' => $campeonato->id,
                'nome' => $campeonato->nome,
                'local' => $campeonato->local,
                'data_inicio' => $campeonato->data_inicio?->format('Y-m-d'),
                'data_fim' => $campeonato->data_fim?->format('Y-m-d'),
                'inicio_inscricao' => $campeonato->inicio_inscricao?->format('Y-m-d'),
                'fim_inscricao' => $campeonato->fim_inscricao?->format('Y-m-d'),
                'status' => $campeonato->status,
                'permite_inscricao_online' => (bool) $campeonato->permite_inscricao_online,
                'inscricao_solidaria' => (bool) $campeonato->inscricao_solidaria,
                'exige_termo_menor' => (bool) $campeonato->exige_termo_menor,
                'kit_retirada_info' => $campeonato->kit_retirada_info,
                'regulamento' => $campeonato->regulamento,
                'valor_inscricao' => $campeonato->valor_inscricao,
                'categorias' => $campeonato->categorias->map(fn ($cat) => [
                    'id' => $cat->id,
                    'nome' => $cat->nome,
                    'descricao' => $cat->descricao,
                    'genero' => $cat->genero,
                    'idade_min' => $cat->idade_min,
                    'idade_max' => $cat->idade_max,
                    'distancia' => $cat->distancia,
                    'valor_inscricao' => $cat->valor_inscricao,
                    'vagas_limite' => $cat->vagas_limite,
                    'competitiva' => (bool) ($cat->competitiva ?? true),
                    'contribuicao_alimento_kg' => $cat->contribuicao_alimento_kg,
                    'aceita_brinquedo' => (bool) ($cat->aceita_brinquedo ?? false),
                    'premiacao_descricao' => $cat->premiacao_descricao,
                    'largada_prevista' => $cat->largada_prevista,
                    'ordem_premiacao' => $cat->ordem_premiacao,
                ])->values(),
            ],
        ]);
    }
}
