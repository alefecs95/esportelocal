<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Inscricao;
use App\Models\Atleta;
use App\Models\Campeonato;
use App\Models\CampeonatoCategoria;
use App\Services\AtletaService;
use App\Services\CadastroCamposService;
use App\Services\WhatsAppNotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class InscricaoController extends Controller
{
    public function __construct(
        private CadastroCamposService $cadastroCamposService,
        private AtletaService $atletaService,
        private WhatsAppNotificationService $whatsAppNotificationService
    ) {
    }

    private function response(bool $success, string $message, $data = [], int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => $success,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    public function indexByCampeonato(Request $request, $id): JsonResponse
    {
        $campeonato = Campeonato::find($id);
        if (!$campeonato) {
            return $this->response(false, 'Campeonato não encontrado', [], 404);
        }

        $query = Inscricao::with(['atleta', 'categoria'])
            ->where('campeonato_id', $id);

        $categoriaId = $request->query('categoria_id') ?? $request->query('campeonato_categoria_id');
        if ($categoriaId !== null && $categoriaId !== '') {
            $query->where('campeonato_categoria_id', (int) $categoriaId);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('genero')) {
            $genero = $this->normalizarGenero((string) $request->query('genero'));
            $query->whereHas('atleta', function ($q) use ($genero) {
                if ($genero === 'M') {
                    $q->whereRaw("UPPER(sexo) IN ('M', 'MASCULINO')");
                } elseif ($genero === 'F') {
                    $q->whereRaw("UPPER(sexo) IN ('F', 'FEMININO')");
                } else {
                    $q->whereRaw('LOWER(sexo) = ?', [strtolower($genero)]);
                }
            });
        }

        $contribFiltro = $this->parseBooleanFilter($request->query('contribuicao_recebida'));
        if ($contribFiltro === true) {
            $query->whereNotNull('contribuicao_recebida_em');
        } elseif ($contribFiltro === false) {
            $query->whereNull('contribuicao_recebida_em');
        }

        $kitFiltro = $this->parseBooleanFilter($request->query('kit_retirado'));
        if ($kitFiltro === true) {
            $query->whereNotNull('kit_retirado_em');
        } elseif ($kitFiltro === false) {
            $query->whereNull('kit_retirado_em');
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->query('search'));
            $digits = preg_replace('/\D/', '', $search);
            $query->where(function ($q) use ($search, $digits) {
                $q->whereHas('atleta', function ($aq) use ($search, $digits) {
                    $aq->where('nome', 'like', "%{$search}%");
                    if ($digits !== '') {
                        $aq->orWhere('cpf', 'like', "%{$digits}%")
                            ->orWhereRaw("REPLACE(REPLACE(REPLACE(cpf, '.', ''), '-', ''), ' ', '') LIKE ?", ["%{$digits}%"]);
                    }
                });
                if ($digits !== '' && ctype_digit($digits)) {
                    $q->orWhere('numero_peito', (int) $digits);
                }
            });
        }

        $inscricoes = $query
            ->orderByRaw('CASE WHEN numero_peito IS NULL THEN 1 ELSE 0 END ASC')
            ->orderBy('numero_peito', 'asc')
            ->orderBy('id', 'desc')
            ->get();

        $inscricoes->transform(function (Inscricao $inscricao) use ($campeonato) {
            $menor = $this->cadastroCamposService->isMenorDeIdade(
                $inscricao->atleta?->data_nascimento,
                $campeonato
            );
            $inscricao->setAttribute('menor_idade', $menor);
            $inscricao->setAttribute(
                'depende_termo_menor',
                $menor && (bool) $campeonato->exige_termo_menor && $inscricao->termo_responsavel_assinado_em === null
            );
            return $inscricao;
        });

        return $this->response(true, 'Inscrições listadas com sucesso', $inscricoes);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'atleta_id' => 'required|exists:atletas,id',
            'campeonato_id' => 'required|exists:campeonatos,id',
            'campeonato_categoria_id' => 'nullable|exists:campeonato_categorias,id',
            'categoria_id' => 'nullable|exists:campeonato_categorias,id',
            'forma_contribuicao' => 'nullable|in:dinheiro,alimento,brinquedo',
            'responsavel_nome' => 'nullable|string|max:255',
            'aceitou_regulamento' => 'nullable|boolean',
            'aceitou_termos_imagem' => 'nullable|boolean',
        ]);

        $atleta = Atleta::find($request->atleta_id);
        $campeonato = Campeonato::with('categorias')->find($request->campeonato_id);

        if (!$atleta || !$campeonato) {
            return $this->response(false, 'Atleta ou campeonato não encontrado.', [], 404);
        }

        $categoriaInputId = $request->input('campeonato_categoria_id') ?? $request->input('categoria_id');

        return $this->criarInscricaoParaAtleta(
            $atleta,
            $campeonato,
            $categoriaInputId ? (int) $categoriaInputId : null,
            $request->input('forma_contribuicao'),
            $request->input('responsavel_nome'),
            $request->boolean('aceitou_regulamento', false),
            $request->boolean('aceitou_termos_imagem', false)
        );
    }

    /**
     * Inscrição pública: cadastra/localiza atleta conforme configuração de campos do campeonato e cria a inscrição.
     */
    public function storePublico(Request $request, string $campeonatoId): JsonResponse
    {
        $campeonato = Campeonato::resolvePublic($campeonatoId, ['categorias']);
        if (!$campeonato) {
            return $this->response(false, 'Campeonato não encontrado.', [], 404);
        }

        if (!$campeonato->permite_inscricao_online || $campeonato->status !== 'inscricoes_abertas') {
            return $this->response(false, 'Inscrições online não estão abertas para esta competição.', [], 422);
        }

        $tenantId = App::has('current_tenant')
            ? App::get('current_tenant')?->id
            : $campeonato->prefeitura_id;

        $atletaRules = $this->cadastroCamposService->buildAtletaValidationRules(
            $campeonato,
            $request->all(),
            $tenantId,
            true
        );

        $eventRules = [
            'campeonato_categoria_id' => 'nullable|exists:campeonato_categorias,id',
            'categoria_id' => 'nullable|exists:campeonato_categorias,id',
            'forma_contribuicao' => 'nullable|in:dinheiro,alimento,brinquedo',
            'aceitou_regulamento' => 'required|accepted',
            'aceitou_termos_imagem' => 'required|accepted',
            'aceitou_declaracao_veracidade' => 'nullable|boolean',
        ];

        $validator = Validator::make($request->all(), array_merge($atletaRules, $eventRules), [
            'aceitou_regulamento.accepted' => 'É obrigatório aceitar o regulamento da competição.',
            'aceitou_termos_imagem.accepted' => 'É obrigatório autorizar o uso de imagem.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();
        $cpfLimpo = isset($validated['cpf']) ? preg_replace('/\D/', '', (string) $validated['cpf']) : '';

        try {
            $atleta = null;
            if (strlen($cpfLimpo) === 11) {
                $atleta = Atleta::where('cpf', $cpfLimpo)
                    ->orWhereRaw("REPLACE(REPLACE(REPLACE(cpf, '.', ''), '-', ''), ' ', '') = ?", [$cpfLimpo])
                    ->first();
            }

            if ($atleta) {
                $atualizacoes = array_filter([
                    'telefone' => $validated['telefone'] ?? null,
                    'email' => $validated['email'] ?? null,
                    'cidade' => $validated['cidade'] ?? null,
                    'tamanho_camiseta' => $validated['tamanho_camiseta'] ?? null,
                    'contato_emergencia' => $validated['contato_emergencia'] ?? null,
                    'responsavel_nome' => $validated['responsavel_nome'] ?? null,
                    'responsavel_cpf' => isset($validated['responsavel_cpf']) ? preg_replace('/\D/', '', (string) $validated['responsavel_cpf']) : null,
                    'responsavel_telefone' => $validated['responsavel_telefone'] ?? null,
                ], fn ($v) => $v !== null && $v !== '');

                if (!empty($atualizacoes)) {
                    $atleta->update($atualizacoes);
                }
            } else {
                $atletaData = collect($validated)->only([
                    'nome',
                    'data_nascimento',
                    'sexo',
                    'nome_mae',
                    'nome_pai',
                    'responsavel_nome',
                    'responsavel_cpf',
                    'responsavel_telefone',
                    'titulo_eleitor',
                    'zona_eleitoral',
                    'secao_eleitoral',
                    'bairro_eleitoral',
                    'cidade_eleitoral',
                    'uf_eleitoral',
                    'cep',
                    'endereco',
                    'numero',
                    'complemento',
                    'bairro',
                    'cidade',
                    'estado',
                    'telefone',
                    'email',
                    'tamanho_camiseta',
                    'contato_emergencia',
                ])->toArray();

                $atletaData['cpf'] = $cpfLimpo !== '' ? $cpfLimpo : null;
                if (!empty($atletaData['responsavel_cpf'])) {
                    $atletaData['responsavel_cpf'] = preg_replace('/\D/', '', (string) $atletaData['responsavel_cpf']);
                }
                if (!empty($atletaData['cep'])) {
                    $atletaData['cep'] = preg_replace('/\D/', '', (string) $atletaData['cep']);
                }
                $atletaData['prefeitura_id'] = $campeonato->prefeitura_id;
                $atletaData['acesso_ativo'] = true;
                $atletaData['pode_participar_competicoes'] = true;

                $atleta = $this->atletaService->create($atletaData);
            }

            $categoriaInputId = $request->input('campeonato_categoria_id') ?? $request->input('categoria_id');

            return $this->criarInscricaoParaAtleta(
                $atleta,
                $campeonato,
                $categoriaInputId ? (int) $categoriaInputId : null,
                $request->input('forma_contribuicao'),
                $request->input('responsavel_nome') ?: $atleta->responsavel_nome,
                true,
                true
            );
        } catch (\Throwable $e) {
            return $this->response(false, 'Erro ao realizar inscrição: ' . $e->getMessage(), [], 500);
        }
    }

    public function update(Request $request, $id): JsonResponse
    {
        $inscricao = Inscricao::find($id);
        if (!$inscricao) {
            return $this->response(false, 'Inscrição não encontrada', [], 404);
        }

        $request->validate([
            'status' => 'sometimes|in:pendente,confirmada,cancelada,rejeitada',
            'status_pagamento' => 'sometimes|in:nao_pago,pago,isento',
            'valor_pago' => 'nullable|numeric',
            'data_pagamento' => 'nullable|date',
            'forma_contribuicao' => 'nullable|in:dinheiro,alimento,brinquedo',
            'contribuicao_quantidade' => 'nullable|numeric|min:0',
            'responsavel_nome' => 'nullable|string|max:255',
        ]);

        $inscricao->update($request->only([
            'status',
            'status_pagamento',
            'valor_pago',
            'data_pagamento',
            'forma_contribuicao',
            'contribuicao_quantidade',
            'responsavel_nome',
        ]));

        return $this->response(true, 'Inscrição atualizada com sucesso', $inscricao->fresh(['atleta', 'categoria']));
    }

    /**
     * Confirma o recebimento da contribuição solidária/taxa, ativa a inscrição e gera número de peito sequencial único.
     */
    public function confirmarContribuicao(Request $request, $id): JsonResponse
    {
        $request->validate([
            'forma_contribuicao' => 'nullable|in:dinheiro,alimento,brinquedo',
            'contribuicao_quantidade' => 'nullable|numeric|min:0',
            'valor_pago' => 'nullable|numeric|min:0',
        ]);

        try {
            $inscricaoAtualizada = DB::transaction(function () use ($request, $id) {
                $inscricao = Inscricao::with(['campeonato', 'categoria', 'atleta'])
                    ->where('id', $id)
                    ->lockForUpdate()
                    ->first();

                if (!$inscricao) {
                    return null;
                }

                // Lock no campeonato para serializar geração do numero_peito por campeonato
                $campeonato = Campeonato::where('id', $inscricao->campeonato_id)
                    ->lockForUpdate()
                    ->first();

                $forma = $request->input('forma_contribuicao') ?: $inscricao->forma_contribuicao;
                if (!$forma) {
                    throw new \InvalidArgumentException('Informe a forma de contribuição (dinheiro, alimento ou brinquedo).');
                }

                $categoria = $inscricao->categoria;
                if ($campeonato?->inscricao_solidaria && $categoria) {
                    $erroForma = $this->validarFormaContribuicaoParaCategoria($forma, $categoria, $campeonato);
                    if ($erroForma !== null) {
                        throw new \InvalidArgumentException($erroForma);
                    }
                }

                $numeroPeito = $inscricao->numero_peito;
                if ($numeroPeito === null) {
                    $ultimoNumero = (int) Inscricao::withTrashed()
                        ->where('campeonato_id', $inscricao->campeonato_id)
                        ->lockForUpdate()
                        ->max('numero_peito');
                    $numeroPeito = $ultimoNumero + 1;
                }

                $quantidade = $request->input('contribuicao_quantidade');
                if ($quantidade === null || $quantidade === '') {
                    if ($forma === 'alimento') {
                        $quantidade = $categoria?->contribuicao_alimento_kg;
                    } elseif ($forma === 'brinquedo') {
                        $quantidade = 1;
                    }
                }

                $valorPago = $request->input('valor_pago');
                if ($forma === 'dinheiro' && ($valorPago === null || $valorPago === '')) {
                    $valorPago = $categoria?->valor_inscricao ?? $campeonato?->valor_inscricao ?? 0;
                }

                $inscricao->update([
                    'forma_contribuicao' => $forma,
                    'contribuicao_quantidade' => $quantidade,
                    'contribuicao_recebida_em' => now(),
                    'status' => 'confirmada',
                    'status_pagamento' => $forma === 'dinheiro' ? 'pago' : 'isento',
                    'valor_pago' => $forma === 'dinheiro' ? $valorPago : null,
                    'data_pagamento' => now()->toDateString(),
                    'numero_peito' => $numeroPeito,
                ]);

                return $inscricao->fresh(['atleta', 'categoria']);
            });

            if (!$inscricaoAtualizada) {
                return $this->response(false, 'Inscrição não encontrada.', [], 404);
            }

            return $this->response(true, 'Contribuição confirmada e número de peito gerado com sucesso.', $inscricaoAtualizada);
        } catch (\InvalidArgumentException $e) {
            return $this->response(false, $e->getMessage(), [], 422);
        } catch (\Throwable $e) {
            return $this->response(false, 'Erro ao confirmar contribuição: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Registra a retirada do kit (número de peito), validando retirada por terceiro e termo de menor.
     */
    public function retirarKit(Request $request, $id): JsonResponse
    {
        $inscricao = Inscricao::with(['campeonato', 'atleta', 'categoria'])->find($id);
        if (!$inscricao) {
            return $this->response(false, 'Inscrição não encontrada.', [], 404);
        }

        if ($inscricao->status !== 'confirmada') {
            return $this->response(false, 'Apenas inscrições confirmadas podem retirar o kit.', [], 422);
        }

        $request->validate([
            'retirado_por_terceiro' => 'nullable|boolean',
            'kit_retirado_por_nome' => 'nullable|string|max:255',
            'kit_retirado_por_documento' => 'nullable|string|max:50',
            'termo_responsavel_assinado' => 'nullable|boolean',
            'responsavel_nome' => 'nullable|string|max:255',
        ]);

        $porTerceiro = $request->boolean('retirado_por_terceiro', false)
            || $request->filled('kit_retirado_por_nome')
            || $request->filled('kit_retirado_por_documento');

        if ($porTerceiro) {
            if (!trim((string) $request->input('kit_retirado_por_nome')) || !trim((string) $request->input('kit_retirado_por_documento'))) {
                return $this->response(
                    false,
                    'Na retirada por terceiro, informe o nome e o documento de quem está retirando o kit.',
                    [],
                    422
                );
            }
        }

        $campeonato = $inscricao->campeonato;
        $isMenor = $this->cadastroCamposService->isMenorDeIdade($inscricao->atleta?->data_nascimento, $campeonato);

        $updateData = [
            'kit_retirado_em' => now(),
            'kit_retirado_por_nome' => $porTerceiro ? trim((string) $request->input('kit_retirado_por_nome')) : null,
            'kit_retirado_por_documento' => $porTerceiro ? trim((string) $request->input('kit_retirado_por_documento')) : null,
        ];

        if ($isMenor && $campeonato?->exige_termo_menor) {
            $termoAssinado = $request->boolean('termo_responsavel_assinado', false)
                || $inscricao->termo_responsavel_assinado_em !== null;
            $responsavelNome = trim((string) ($request->input('responsavel_nome') ?: $inscricao->responsavel_nome ?: $inscricao->atleta?->responsavel_nome));

            if (!$termoAssinado || $responsavelNome === '') {
                return $this->response(
                    false,
                    'Atleta menor de 18 anos só pode retirar o kit com termo de autorização assinado e nome do responsável registrado.',
                    [],
                    422
                );
            }

            $updateData['termo_responsavel_assinado_em'] = $inscricao->termo_responsavel_assinado_em ?? now();
            $updateData['responsavel_nome'] = $responsavelNome;
        }

        $inscricao->update($updateData);

        return $this->response(true, 'Retirada do kit registrada com sucesso.', $inscricao->fresh(['atleta', 'categoria']));
    }

    public function comprovantePublico($id): JsonResponse
    {
        $inscricao = Inscricao::with(['atleta:id,nome,sexo,cidade', 'categoria', 'campeonato'])->find($id);
        if (!$inscricao) {
            return $this->response(false, 'Inscrição não encontrada.', [], 404);
        }

        $isMenor = $this->cadastroCamposService->isMenorDeIdade(
            $inscricao->atleta?->data_nascimento,
            $inscricao->campeonato
        );

        return $this->response(true, 'Comprovante carregado com sucesso.', [
            'id' => $inscricao->id,
            'atleta_nome' => $inscricao->atleta?->nome,
            'campeonato_id' => $inscricao->campeonato_id,
            'campeonato_nome' => $inscricao->campeonato?->nome,
            'campeonato_data' => $inscricao->campeonato?->data_inicio?->format('Y-m-d'),
            'campeonato_local' => $inscricao->campeonato?->local,
            'categoria_nome' => $inscricao->categoria?->nome,
            'distancia' => $inscricao->categoria?->distancia,
            'largada_prevista' => $inscricao->categoria?->largada_prevista,
            'forma_contribuicao' => $inscricao->forma_contribuicao,
            'status' => $inscricao->status,
            'status_pagamento' => $inscricao->status_pagamento,
            'numero_peito' => $inscricao->numero_peito,
            'kit_retirada_info' => $inscricao->campeonato?->kit_retirada_info,
            'depende_termo_menor' => $isMenor && (bool) ($inscricao->campeonato?->exige_termo_menor),
            'responsavel_nome' => $inscricao->responsavel_nome,
            'created_at' => $inscricao->created_at?->toIso8601String(),
            'comprovante_pdf_url' => URL::temporarySignedRoute(
                'pdf.v1.comprovante-inscricao',
                now()->addDays(30),
                ['inscricaoId' => $inscricao->id]
            ),
        ]);
    }

    private function criarInscricaoParaAtleta(
        Atleta $atleta,
        Campeonato $campeonato,
        ?int $categoriaInputId,
        ?string $formaContribuicao,
        ?string $responsavelNome,
        bool $aceitouRegulamento,
        bool $aceitouTermosImagem
    ): JsonResponse {
        if (!$atleta->pode_participar_competicoes) {
            return $this->response(false, 'Atleta está impedido de participar de competições.', [], 422);
        }

        // 0. Validar status e prazo do campeonato
        if ($campeonato->status !== 'inscricoes_abertas') {
            return $this->response(false, 'Inscrições não estão abertas para esta competição.', [], 422);
        }

        if ($campeonato->inicio_inscricao && now()->lt(Carbon::parse($campeonato->inicio_inscricao)->startOfDay())) {
            return $this->response(false, 'Período de inscrição ainda não iniciou.', [], 422);
        }

        if ($campeonato->fim_inscricao && now()->gt(Carbon::parse($campeonato->fim_inscricao)->endOfDay())) {
            return $this->response(false, 'Período de inscrição já foi encerrado.', [], 422);
        }

        // 1. Verificar duplicidade
        $activeExists = Inscricao::where('atleta_id', $atleta->id)
            ->where('campeonato_id', $campeonato->id)
            ->whereIn('status', ['pendente', 'confirmada'])
            ->exists();

        if ($activeExists) {
            return $this->response(false, 'Atleta já possui inscrição ativa neste campeonato.', [], 422);
        }

        // 2. Validar limite de participantes
        if ($campeonato->limite_participantes > 0) {
            $totalInscritos = Inscricao::where('campeonato_id', $campeonato->id)
                ->whereIn('status', ['pendente', 'confirmada'])
                ->count();

            if ($totalInscritos >= $campeonato->limite_participantes) {
                return $this->response(false, 'Limite de participantes atingido.', [], 422);
            }
        }

        // 3. Validar elegibilidade Global
        if (!$this->validarElegibilidade($atleta, $campeonato)) {
            return $this->response(false, 'Atleta não atende aos critérios do campeonato.', [], 422);
        }

        // 4. Validar Categoria Específica
        $categoriaSelecionada = null;
        $categoriaId = null;

        if ($campeonato->categorias->isNotEmpty()) {
            $idade = $campeonato->inscricao_solidaria
                ? $this->cadastroCamposService->calcularIdadeNaDataEvento($atleta->data_nascimento, $campeonato)
                : Carbon::parse($atleta->data_nascimento)->age;
            $sexoAtletaNorm = $this->normalizarGenero((string) ($atleta->sexo ?? ''));

            $matchingCategories = $campeonato->categorias->filter(function ($cat) use ($idade, $sexoAtletaNorm) {
                if ($cat->genero) {
                    $catGeneroNorm = $this->normalizarGenero((string) $cat->genero);
                    if ($catGeneroNorm !== 'MISTO' && $sexoAtletaNorm !== '' && $catGeneroNorm !== $sexoAtletaNorm) {
                        return false;
                    }
                    if ($catGeneroNorm !== 'MISTO' && $sexoAtletaNorm === '') {
                        return false;
                    }
                }
                if ($cat->idade_min !== null && $idade < $cat->idade_min) {
                    return false;
                }
                if ($cat->idade_max !== null && $idade > $cat->idade_max) {
                    return false;
                }
                return true;
            });

            if ($matchingCategories->isEmpty()) {
                return $this->response(false, 'Atleta não se encaixa em nenhuma categoria deste campeonato (Idade/Gênero).', [], 422);
            }

            if ($categoriaInputId) {
                $categoriaSelecionada = $matchingCategories->firstWhere('id', $categoriaInputId);
                if (!$categoriaSelecionada) {
                    return $this->response(false, 'Categoria selecionada inválida para este atleta.', [], 422);
                }
                $categoriaId = $categoriaSelecionada->id;
            } else {
                // Em campeonatos solidários, se o atleta se encaixa em mais de uma prova/categoria, exige escolha explícita
                if ($campeonato->inscricao_solidaria && $matchingCategories->count() > 1) {
                    return $this->response(
                        false,
                        'Escolha a prova/categoria desejada para concluir a inscrição.',
                        ['categorias_disponiveis' => $matchingCategories->values()],
                        422
                    );
                }
                $categoriaSelecionada = $matchingCategories->first();
                $categoriaId = $categoriaSelecionada->id;
            }

            // Validar limite de vagas da categoria
            if ($categoriaSelecionada && $categoriaSelecionada->vagas_limite > 0) {
                $totalCat = Inscricao::where('campeonato_id', $campeonato->id)
                    ->where('campeonato_categoria_id', $categoriaSelecionada->id)
                    ->whereIn('status', ['pendente', 'confirmada'])
                    ->count();
                if ($totalCat >= $categoriaSelecionada->vagas_limite) {
                    return $this->response(false, 'Limite de vagas atingido para a categoria selecionada.', [], 422);
                }
            }
        }

        // 5. Validar forma de contribuição (quando inscrição solidária ativa)
        if ($campeonato->inscricao_solidaria) {
            if (!$formaContribuicao) {
                return $this->response(false, 'Escolha a forma de contribuição (dinheiro, alimento ou brinquedo).', [], 422);
            }
            if ($categoriaSelecionada) {
                $erroForma = $this->validarFormaContribuicaoParaCategoria($formaContribuicao, $categoriaSelecionada, $campeonato);
                if ($erroForma !== null) {
                    return $this->response(false, $erroForma, [], 422);
                }
            }
        }

        // 6. Definir status iniciais (preservando comportamento atual quando inscricao_solidaria = false)
        $status = 'pendente';
        $statusPagamento = 'nao_pago';

        if (!$campeonato->inscricao_solidaria && (float) ($campeonato->valor_inscricao ?? 0) == 0.0) {
            $status = 'confirmada';
            $statusPagamento = 'isento';
        }

        $isMenor = $this->cadastroCamposService->isMenorDeIdade($atleta->data_nascimento, $campeonato);
        $dependeTermoMenor = $isMenor && (bool) $campeonato->exige_termo_menor;

        // Corrida solidária: o número de peito é gerado já no cadastro (identificação do atleta)
        $inscricao = DB::transaction(function () use ($atleta, $campeonato, $categoriaId, $status, $statusPagamento, $formaContribuicao, $responsavelNome, $dependeTermoMenor, $aceitouRegulamento, $aceitouTermosImagem) {
            $numeroPeito = $campeonato->inscricao_solidaria
                ? $this->proximoNumeroPeito($campeonato)
                : null;

            return Inscricao::create([
                'prefeitura_id' => $campeonato->prefeitura_id,
                'atleta_id' => $atleta->id,
                'campeonato_id' => $campeonato->id,
                'campeonato_categoria_id' => $categoriaId,
                'numero_peito' => $numeroPeito,
                'status' => $status,
                'status_pagamento' => $statusPagamento,
                'forma_contribuicao' => $formaContribuicao,
                'responsavel_nome' => $responsavelNome ?: ($dependeTermoMenor ? $atleta->responsavel_nome : null),
                'data_inscricao' => now(),
                'aceitou_regulamento_em' => $aceitouRegulamento ? now() : null,
                'aceitou_termos_imagem_em' => $aceitouTermosImagem ? now() : null,
            ]);
        });

        $inscricao->load(['atleta', 'categoria', 'campeonato']);
        $inscricao->setAttribute('menor_idade', $isMenor);
        $inscricao->setAttribute('depende_termo_menor', $dependeTermoMenor);
        $inscricao->setAttribute('comprovante_url', URL::temporarySignedRoute(
            'inscricoes.v1.comprovante.signed',
            now()->addDays(30),
            ['id' => $inscricao->id]
        ));
        $comprovantePdfUrl = URL::temporarySignedRoute(
            'pdf.v1.comprovante-inscricao',
            now()->addDays(30),
            ['inscricaoId' => $inscricao->id]
        );
        $inscricao->setAttribute('comprovante_pdf_url', $comprovantePdfUrl);

        try {
            $this->whatsAppNotificationService->notifyInscricaoRealizada($inscricao, $comprovantePdfUrl);
        } catch (\Throwable $e) {
            Log::warning('Falha ao disparar notificação WhatsApp da inscrição: ' . $e->getMessage(), [
                'inscricao_id' => $inscricao->id,
            ]);
        }

        return $this->response(true, 'Inscrição realizada com sucesso', $inscricao, 201);
    }

    /**
     * Próximo número de peito do campeonato (sequencial). Deve ser chamado dentro de uma transação:
     * o lock no campeonato serializa a geração para evitar números repetidos.
     */
    private function proximoNumeroPeito(Campeonato $campeonato): int
    {
        Campeonato::where('id', $campeonato->id)->lockForUpdate()->first();

        $ultimoNumero = (int) Inscricao::withTrashed()
            ->where('campeonato_id', $campeonato->id)
            ->max('numero_peito');

        return $ultimoNumero + 1;
    }

    private function validarFormaContribuicaoParaCategoria(
        string $forma,
        CampeonatoCategoria $categoria,
        Campeonato $campeonato
    ): ?string {
        if ($forma === 'brinquedo' && !$categoria->aceita_brinquedo) {
            return 'A categoria selecionada não aceita doação de brinquedo.';
        }

        if ($forma === 'alimento' && (float) ($categoria->contribuicao_alimento_kg ?? 0) <= 0) {
            return 'A categoria selecionada não aceita doação de alimento.';
        }

        if ($forma === 'dinheiro') {
            $valor = $categoria->valor_inscricao !== null
                ? (float) $categoria->valor_inscricao
                : (float) ($campeonato->valor_inscricao ?? 0);
            if ($valor <= 0) {
                return 'A categoria selecionada não possui taxa em dinheiro configurada.';
            }
        }

        return null;
    }

    private function normalizarGenero(string $valor): string
    {
        $v = strtoupper(trim($valor));
        if (in_array($v, ['M', 'MASCULINO', 'MASC'], true)) {
            return 'M';
        }
        if (in_array($v, ['F', 'FEMININO', 'FEM'], true)) {
            return 'F';
        }
        if ($v === 'MISTO') {
            return 'MISTO';
        }
        return $v;
    }

    private function parseBooleanFilter($value): ?bool
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (in_array(strtolower((string) $value), ['1', 'true', 'sim', 'yes'], true)) {
            return true;
        }
        if (in_array(strtolower((string) $value), ['0', 'false', 'nao', 'não', 'no'], true)) {
            return false;
        }
        return null;
    }

    private function validarElegibilidade($atleta, $campeonato)
    {
        // idade
        $idade = Carbon::parse($atleta->data_nascimento)->age;

        if ($campeonato->idade_min && $idade < $campeonato->idade_min) {
            return false;
        }

        if ($campeonato->idade_max && $idade > $campeonato->idade_max) {
            return false;
        }

        // cidade eleitoral
        if ($campeonato->cidade_eleitoral &&
            $atleta->cidade_eleitoral !== $campeonato->cidade_eleitoral) {
            return false;
        }

        // bairro eleitoral
        if ($campeonato->bairro_eleitoral &&
            $atleta->bairro_eleitoral !== $campeonato->bairro_eleitoral) {
            return false;
        }

        return true;
    }
}
