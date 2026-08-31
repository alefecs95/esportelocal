<?php

namespace App\Services;

use App\Enums\CriterioClassificacaoFase;
use App\Enums\OrigemConfrontoTipo;
use App\Models\Campeonato;
use App\Models\Equipe;
use App\Models\Fase;
use App\Models\FaseGrupo;
use App\Models\FaseRegraClassificacao;
use App\Models\Partida;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CompeticaoEstruturaService
{
    public function __construct(
        private CompeticaoClassificacaoService $classificacaoService
    ) {}

    public function obterEstrutura(Campeonato $campeonato): Campeonato
    {
        return $campeonato->load([
            'fases' => function ($faseQuery) {
                $faseQuery->orderBy('ordem');
            },
            'fases.grupos' => function ($grupoQuery) {
                $grupoQuery->orderBy('ordem');
            },
            'fases.regrasClassificacao' => function ($regraQuery) {
                $regraQuery->orderBy('ordem');
            },
            'fases.partidas' => function ($partidaQuery) {
                $partidaQuery->orderByRaw('COALESCE(ordem_na_fase, 999999), id');
            },
        ]);
    }

    public function salvarEstrutura(Campeonato $campeonato, array $payload): Campeonato
    {
        DB::transaction(function () use ($campeonato, $payload) {
            $fasesPayload = $payload['fases'] ?? [];
            $fasesMantidas = [];

            foreach ($fasesPayload as $faseData) {
                $fase = $this->upsertFase($campeonato, $faseData);
                $fasesMantidas[] = $fase->id;
                $this->sincronizarGrupos($fase, $faseData['grupos'] ?? []);
                $this->sincronizarPartidas($fase, $faseData['partidas'] ?? []);
            }

            if (!empty($fasesMantidas)) {
                Fase::where('campeonato_id', $campeonato->id)
                    ->whereNotIn('id', $fasesMantidas)
                    ->delete();
            }
        });

        return $this->obterEstrutura($campeonato->fresh());
    }

    public function sugerirConfrontos(Campeonato $campeonato, array $payload): array
    {
        $faseId = $payload['fase_id'] ?? null;
        if (!$faseId) {
            throw new InvalidArgumentException('A fase_id é obrigatória para sugerir confrontos.');
        }

        $fase = Fase::where('campeonato_id', $campeonato->id)->findOrFail($faseId);
        $sugestoes = [];
        $ordem = 1;

        // Se a fase tem grupos configurados com equipes
        if ($fase->grupos()->count() > 0) {
            $todasEquipesCampeonato = collect($this->buscarParticipantes($campeonato))->keyBy('id');
            
            foreach ($fase->grupos()->orderBy('ordem')->get() as $grupo) {
                $config = $grupo->configuracao;
                if (is_string($config)) {
                    $config = json_decode($config, true);
                }
                
                $equipesIds = $config['equipe_ids'] ?? [];
                if (empty($equipesIds)) continue;

                // Gerar todos contra todos (round-robin) dentro do grupo
                $qtd = count($equipesIds);
                for ($j = 0; $j < $qtd; $j++) {
                    for ($k = $j + 1; $k < $qtd; $k++) {
                        $mandanteId = $equipesIds[$j];
                        $visitanteId = $equipesIds[$k];
                        $mandante = $todasEquipesCampeonato->get($mandanteId);
                        $visitante = $todasEquipesCampeonato->get($visitanteId);

                        if (!$mandante || !$visitante) continue;

                        $sugestoes[] = [
                            'fase_grupo_id' => $grupo->id,
                            'ordem_na_fase' => $ordem++,
                            'mandante_id' => $mandante['id'],
                            'visitante_id' => $visitante['id'],
                            'origem_mandante' => [
                                'type' => OrigemConfrontoTipo::EQUIPE->value,
                                'payload' => ['id' => $mandante['id'], 'nome' => $mandante['nome']],
                            ],
                            'origem_visitante' => [
                                'type' => OrigemConfrontoTipo::EQUIPE->value,
                                'payload' => ['id' => $visitante['id'], 'nome' => $visitante['nome']],
                            ],
                            'status' => 'agendada',
                            'vencedor_id' => null,
                            'definido_manualmente' => false,
                        ];
                    }
                }
            }
        }

        // Se não gerou sugestões por grupo (mata-mata ou sem grupos), gera o padrão
        if (empty($sugestoes)) {
            $participantes = $this->buscarParticipantes($campeonato);
            if (count($participantes) < 2) {
                throw new InvalidArgumentException('Número insuficiente de participantes aprovados para sugerir confrontos.');
            }

            usort($participantes, fn ($a, $b) => $a['id'] <=> $b['id']);

            for ($i = 0; $i < count($participantes); $i += 2) {
                $mandante = $participantes[$i];
                $visitante = $participantes[$i + 1] ?? null;

                $sugestoes[] = [
                    'fase_grupo_id' => null,
                    'ordem_na_fase' => $ordem++,
                    'mandante_id' => $mandante['id'],
                    'visitante_id' => $visitante['id'] ?? null,
                    'origem_mandante' => [
                        'type' => OrigemConfrontoTipo::EQUIPE->value,
                        'payload' => ['id' => $mandante['id'], 'nome' => $mandante['nome']],
                    ],
                    'origem_visitante' => $visitante
                        ? [
                            'type' => OrigemConfrontoTipo::EQUIPE->value,
                            'payload' => ['id' => $visitante['id'], 'nome' => $visitante['nome']],
                        ]
                        : [
                            'type' => OrigemConfrontoTipo::BYE->value,
                            'payload' => ['motivo' => 'Ajuste para número ímpar de participantes'],
                        ],
                    'status' => $visitante ? 'agendada' : 'finalizada',
                    'vencedor_id' => $visitante ? null : $mandante['id'],
                    'definido_manualmente' => false,
                ];
            }
        }

        if (!empty($payload['persistir'])) {
            $this->persistirSugestoes($fase, $sugestoes);
        }

        return $sugestoes;
    }

    public function aplicarClassificacaoNasFases(Campeonato $campeonato): array
    {
        $fases = $campeonato->fases()->orderBy('ordem')->get();
        $atualizacoes = [];
        $classificacoesCache = [];

        foreach ($fases as $fase) {
            // Se a fase tem grupos ou é pontos corridos, calculamos a classificação
            if ($fase->grupos()->count() > 0 || $fase->tipo === 'pontos_corridos') {
                $classificacoesCache[$fase->id] = $this->classificacaoService->calcularClassificacaoFase($fase);
            }

            // Tenta resolver partidas desta fase com origens pendentes
            $partidasPendentes = $fase->partidas()
                ->where(function ($q) {
                    $q->where(function ($qm) {
                        $qm->whereNull('mandante_id')->whereNotNull('origem_mandante_type');
                    })->orWhere(function ($qv) {
                        $qv->whereNull('visitante_id')->whereNotNull('origem_visitante_type');
                    });
                })
                ->get();

            foreach ($partidasPendentes as $partida) {
                $mudou = false;

                if (!$partida->mandante_id && $partida->origem_mandante_type) {
                    $payload = $partida->origem_mandante_payload;
                    if (is_string($payload)) {
                        $payload = json_decode($payload, true);
                    }
                    
                    $id = $this->resolverOrigem($partida->origem_mandante_type, $payload, $classificacoesCache, $fases);
                    if ($id) {
                        $partida->mandante_id = $id;
                        $mudou = true;
                    }
                }

                if (!$partida->visitante_id && $partida->origem_visitante_type) {
                    $payload = $partida->origem_visitante_payload;
                    if (is_string($payload)) {
                        $payload = json_decode($payload, true);
                    }

                    $id = $this->resolverOrigem($partida->origem_visitante_type, $payload, $classificacoesCache, $fases);
                    if ($id) {
                        $partida->visitante_id = $id;
                        $mudou = true;
                    }
                }

                if ($mudou) {
                    $partida->save();
                    $atualizacoes[] = $partida->id;
                }
            }
        }

        return $atualizacoes;
    }

    private function resolverOrigem(string $type, $payload, array $classificacoesCache, $todasFases): ?int
    {
        // Garante que o payload é array
        if (is_string($payload)) {
            $payload = json_decode($payload, true);
        }
        
        if (!$payload || !is_array($payload)) return null;

        // Lógica para GROUP_POSITION (1º do Grupo A, 2º do Grupo B, etc.)
        if ($type === OrigemConfrontoTipo::GROUP_POSITION->value) {
            $grupoNome = $payload['group'] ?? null;
            $posicaoAlvo = $payload['position'] ?? null;
            
            if (!$grupoNome || !$posicaoAlvo) return null;

            // Percorre todas as fases que têm classificação calculada
            foreach ($classificacoesCache as $faseId => $classificacaoFase) {
                // Verifica se essa fase tem o grupo que estamos procurando
                if (isset($classificacaoFase[$grupoNome])) {
                    $tabelaDoGrupo = $classificacaoFase[$grupoNome];
                    
                    // Procura o time na posição desejada dentro deste grupo
                    foreach ($tabelaDoGrupo as $time) {
                        if (isset($time['posicao']) && (int)$time['posicao'] === (int)$posicaoAlvo) {
                            return $time['id'];
                        }
                    }
                }
            }
            return null;
        }

        if ($type === OrigemConfrontoTipo::MATCH_WINNER->value) {
            $matchId = $payload['match_id'] ?? null;
            if (!$matchId) return null;
            $partida = Partida::find($matchId);
            return $partida?->vencedor_id;
        }

        if ($type === OrigemConfrontoTipo::MATCH_LOSER->value) {
            $matchId = $payload['match_id'] ?? null;
            if (!$matchId) return null;
            $partida = Partida::find($matchId);
            // O perdedor é quem não é o vencedor, se a partida acabou e não foi empate
            if ($partida && $partida->status === 'finalizada' && $partida->vencedor_id) {
                if ($partida->mandante_id === $partida->vencedor_id) return $partida->visitante_id;
                if ($partida->visitante_id === $partida->vencedor_id) return $partida->mandante_id;
            }
            return null;
        }

        if ($type === OrigemConfrontoTipo::BEST_THIRD->value || $type === OrigemConfrontoTipo::BEST_LOSER->value) {
            $rankingIndex = $payload['ranking_index'] ?? null;
            if (!$rankingIndex) return null;

            // Busca reversa no cache para encontrar a fase mais recente com grupos
            $fasesIds = array_keys($classificacoesCache);
            $fasesIds = array_reverse($fasesIds);

            foreach ($fasesIds as $fId) {
                // Recupera a fase original (objeto) para calcular melhores colocados
                $faseObj = $todasFases->firstWhere('id', $fId);
                
                // Só aplica se a fase tiver grupos (ignora mata-mata ou pontos corridos puros se não for o caso)
                if ($faseObj && $faseObj->grupos()->count() > 1) {
                    // Determina qual posição estamos buscando (3º lugar, 2º lugar, etc.)
                    // Por padrão BEST_THIRD busca os 3ºs colocados. BEST_LOSER é mais complexo, vamos focar no BEST_THIRD por enquanto
                    // Assumimos que BEST_THIRD busca entre os 3ºs colocados de todos os grupos
                    
                    $posicaoAlvo = ($type === OrigemConfrontoTipo::BEST_THIRD->value) ? 3 : 2; // Exemplo: BEST_LOSER poderia ser 2ºs ou perdedores de mata-mata (outro contexto)
                    
                    // Se for BEST_LOSER em contexto de mata-mata, a lógica é diferente (perdedores das partidas)
                    // Mas aqui estamos falando de classificação de grupos.
                    // Vamos assumir que BEST_THIRD é "Melhores X colocados entre grupos"
                    
                    $melhores = $this->classificacaoService->calcularMelhoresXColocados($faseObj, $posicaoAlvo);
                    
                    // O ranking_index é 1-based (1º melhor terceiro, 2º melhor terceiro...)
                    $time = collect($melhores)->firstWhere('posicao_relativa', $rankingIndex);
                    
                    return $time['id'] ?? null;
                }
            }
            return null;
        }

        return null;
    }

    public function listarRegrasClassificacao(Fase $fase)
    {
        return $fase->regrasClassificacao()->orderBy('ordem')->get();
    }

    public function salvarRegrasClassificacao(Fase $fase, array $payload)
    {
        $regras = $payload['regras'] ?? [];
        $mantidas = [];

        foreach ($regras as $index => $regraData) {
            $criterio = $regraData['criterio'] ?? null;
            if (!$criterio || !in_array($criterio, CriterioClassificacaoFase::values(), true)) {
                throw new InvalidArgumentException('Critério de classificação inválido.');
            }

            $ordem = (int) ($regraData['ordem'] ?? ($index + 1));
            if ($ordem < 1) {
                throw new InvalidArgumentException('A ordem da regra deve ser maior que zero.');
            }

            $configuracao = $regraData['configuracao'] ?? null;
            if (!is_null($configuracao) && !is_array($configuracao)) {
                throw new InvalidArgumentException('Configuração de regra inválida.');
            }

            $data = [
                'criterio' => $criterio,
                'ordem' => $ordem,
                'configuracao' => $configuracao,
            ];

            if (!empty($regraData['id'])) {
                $regra = FaseRegraClassificacao::where('fase_id', $fase->id)->findOrFail($regraData['id']);
                $regra->update($data);
            } else {
                $regra = $fase->regrasClassificacao()->create($data);
            }

            $mantidas[] = $regra->id;
        }

        if (!empty($mantidas)) {
            FaseRegraClassificacao::where('fase_id', $fase->id)->whereNotIn('id', $mantidas)->delete();
        } elseif (count($regras) === 0) {
            FaseRegraClassificacao::where('fase_id', $fase->id)->delete();
        }

        return $this->listarRegrasClassificacao($fase->fresh());
    }

    private function upsertFase(Campeonato $campeonato, array $faseData): Fase
    {
        $data = [
            'nome' => $faseData['nome'],
            'ordem' => (int) ($faseData['ordem'] ?? 1),
            'tipo' => $faseData['tipo'] ?? 'custom',
            'status' => $faseData['status'] ?? 'planejada',
            'configuracao' => $faseData['configuracao'] ?? null,
            'permite_edicao_manual' => (bool) ($faseData['permite_edicao_manual'] ?? true),
        ];

        if (!empty($faseData['id'])) {
            $fase = Fase::where('campeonato_id', $campeonato->id)->findOrFail($faseData['id']);
            $fase->update($data);
            return $fase;
        }

        return $campeonato->fases()->create($data);
    }

    private function sincronizarGrupos(Fase $fase, array $gruposPayload): void
    {
        $gruposMantidos = [];
        foreach ($gruposPayload as $grupoData) {
            $data = [
                'nome' => $grupoData['nome'],
                'ordem' => (int) ($grupoData['ordem'] ?? 1),
                'configuracao' => $grupoData['configuracao'] ?? null,
            ];

            if (!empty($grupoData['id'])) {
                $grupo = FaseGrupo::where('fase_id', $fase->id)->findOrFail($grupoData['id']);
                $grupo->update($data);
            } else {
                $grupo = $fase->grupos()->create($data);
            }

            $gruposMantidos[] = $grupo->id;
        }

        if (!empty($gruposMantidos)) {
            FaseGrupo::where('fase_id', $fase->id)->whereNotIn('id', $gruposMantidos)->delete();
        } elseif (count($gruposPayload) === 0) {
            FaseGrupo::where('fase_id', $fase->id)->delete();
        }
    }

    private function sincronizarPartidas(Fase $fase, array $partidasPayload): void
    {
        $partidasMantidas = [];
        $gruposDaFase = $fase->grupos()->pluck('nome')->all();
        foreach ($partidasPayload as $partidaData) {
            $this->validarPartidaPayload($fase, $partidaData, $gruposDaFase);
            // Extrair ID direto se type = EQUIPE e estiver no payload para forçar o vinculo
            $mandanteId = $partidaData['mandante_id'] ?? null;
            $visitanteId = $partidaData['visitante_id'] ?? null;

            if (($partidaData['origem_mandante']['type'] ?? '') === OrigemConfrontoTipo::EQUIPE->value) {
                $mId = $partidaData['origem_mandante']['payload']['id'] ?? null;
                if ($mId) $mandanteId = $mId;
            }

            if (($partidaData['origem_visitante']['type'] ?? '') === OrigemConfrontoTipo::EQUIPE->value) {
                $vId = $partidaData['origem_visitante']['payload']['id'] ?? null;
                if ($vId) $visitanteId = $vId;
            }

            $data = [
                'fase_grupo_id' => $partidaData['fase_grupo_id'] ?? null,
                'mandante_id' => $mandanteId,
                'visitante_id' => $visitanteId,
                'arbitro_id' => $partidaData['arbitro_id'] ?? null,
                'data_jogo' => $this->normalizarDataJogo($partidaData['data_jogo'] ?? null),
                'local' => $partidaData['local'] ?? null,
                'status' => $partidaData['status'] ?? 'agendada',
                'rodada' => $partidaData['rodada'] ?? null,
                'chave' => $partidaData['chave'] ?? null,
                'ordem_na_fase' => $partidaData['ordem_na_fase'] ?? null,
                'proxima_partida_id' => $partidaData['proxima_partida_id'] ?? null,
                'vencedor_id' => $partidaData['vencedor_id'] ?? null,
                'origem_mandante_type' => $partidaData['origem_mandante']['type'] ?? null,
                'origem_mandante_payload' => $partidaData['origem_mandante']['payload'] ?? null,
                'origem_visitante_type' => $partidaData['origem_visitante']['type'] ?? null,
                'origem_visitante_payload' => $partidaData['origem_visitante']['payload'] ?? null,
                'definido_manualmente' => (bool) ($partidaData['definido_manualmente'] ?? false),
            ];

            if (!empty($partidaData['id'])) {
                $partida = Partida::where('fase_id', $fase->id)->findOrFail($partidaData['id']);
                $partida->update($data);
            } else {
                $partida = $fase->partidas()->create($data);
            }

            $partidasMantidas[] = $partida->id;
        }

        if (!empty($partidasMantidas)) {
            Partida::where('fase_id', $fase->id)->whereNotIn('id', $partidasMantidas)->delete();
        } elseif (count($partidasPayload) === 0) {
            Partida::where('fase_id', $fase->id)->delete();
        }
    }

    private function validarPartidaPayload(Fase $fase, array $partidaData, array $gruposDaFase): void
    {
        if (!empty($partidaData['fase_grupo_id'])) {
            $grupoValido = FaseGrupo::where('fase_id', $fase->id)
                ->where('id', $partidaData['fase_grupo_id'])
                ->exists();

            if (!$grupoValido) {
                throw new InvalidArgumentException('A partida referencia um grupo inválido para a fase informada.');
            }
        }

        $this->validarOrigem($partidaData, 'origem_mandante', $gruposDaFase);
        $this->validarOrigem($partidaData, 'origem_visitante', $gruposDaFase);
    }

    private function validarOrigem(array $partidaData, string $campo, array $gruposDaFase): void
    {
        if (empty($partidaData[$campo])) {
            return;
        }

        $origem = $partidaData[$campo];
        $type = $origem['type'] ?? null;
        $payload = $origem['payload'] ?? [];
        if (!in_array($type, OrigemConfrontoTipo::values(), true)) {
            throw new InvalidArgumentException("Tipo de origem inválido para {$campo}.");
        }

        if (!is_array($payload)) {
            throw new InvalidArgumentException("Payload inválido para {$campo}.");
        }

        if ($type === OrigemConfrontoTipo::MANUAL->value) {
            return;
        }

        if ($type === OrigemConfrontoTipo::EQUIPE->value) {
            // Ignora a validação se o ID da equipe não estiver preenchido (modo manual na UI onde a equipe ainda será escolhida)
            if (!isset($payload['id']) || $payload['id'] === null || $payload['id'] === '') {
                return;
            }
            if (!$this->isPositiveInt($payload['id'])) {
                throw new InvalidArgumentException("Origem {$campo} do tipo EQUIPE exige payload.id positivo.");
            }

            return;
        }

        if ($type === OrigemConfrontoTipo::GROUP_POSITION->value) {
            $grupo = (string) ($payload['group'] ?? '');
            if ($grupo === '') {
                throw new InvalidArgumentException("Origem {$campo} do tipo GROUP_POSITION exige grupo válido da fase.");
            }

            if (!$this->isPositiveInt($payload['position'] ?? null)) {
                throw new InvalidArgumentException("Origem {$campo} do tipo GROUP_POSITION exige position positiva.");
            }

            return;
        }

        if (in_array($type, [OrigemConfrontoTipo::MATCH_WINNER->value, OrigemConfrontoTipo::MATCH_LOSER->value], true)) {
            if (!$this->isPositiveInt($payload['match_id'] ?? null)) {
                throw new InvalidArgumentException("Origem {$campo} do tipo {$type} exige match_id positivo.");
            }

            return;
        }

        if (in_array($type, [OrigemConfrontoTipo::BEST_THIRD->value, OrigemConfrontoTipo::BEST_LOSER->value], true)) {
            if (!$this->isPositiveInt($payload['ranking_index'] ?? null)) {
                throw new InvalidArgumentException("Origem {$campo} do tipo {$type} exige ranking_index positivo.");
            }

            return;
        }

        if ($type === OrigemConfrontoTipo::BYE->value) {
            if (isset($payload['motivo']) && !is_string($payload['motivo'])) {
                throw new InvalidArgumentException("Origem {$campo} do tipo BYE exige motivo textual.");
            }
        }
    }

    private function isPositiveInt(mixed $value): bool
    {
        if (is_int($value)) {
            return $value > 0;
        }

        if (is_string($value) && ctype_digit($value)) {
            return (int) $value > 0;
        }

        return false;
    }

    private function normalizarDataJogo(mixed $valor): ?string
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        $texto = trim((string) $valor);
        if ($texto === '') {
            return null;
        }

        $formato = null;
        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $texto)) {
            $formato = 'Y-m-d H:i:s';
        } elseif (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $texto)) {
            $formato = 'Y-m-d\TH:i';
        } elseif (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}$/', $texto)) {
            $formato = 'Y-m-d\TH:i:s';
        } else {
            throw new InvalidArgumentException('Data/Hora da partida inválida. Use YYYY-MM-DD HH:MM:SS ou YYYY-MM-DDTHH:MM.');
        }

        try {
            $data = Carbon::createFromFormat($formato, $texto, config('app.timezone'));
            if (!$data || $data->format($formato) !== $texto) {
                throw new InvalidArgumentException('Data/Hora da partida inválida. Use YYYY-MM-DD HH:MM:SS ou YYYY-MM-DDTHH:MM.');
            }

            return $data->format('Y-m-d H:i:s');
        } catch (\Throwable) {
            throw new InvalidArgumentException('Data/Hora da partida inválida. Use YYYY-MM-DD HH:MM:SS ou YYYY-MM-DDTHH:MM.');
        }
    }

    private function buscarParticipantes(Campeonato $campeonato): array
    {
        if ($campeonato->tipo_competicao === 'equipe') {
            return Equipe::where('campeonato_id', $campeonato->id)
                ->where('status_inscricao', 'aprovada')
                ->orderBy('id')
                ->get(['id', 'nome'])
                ->map(fn ($equipe) => ['id' => $equipe->id, 'nome' => $equipe->nome])
                ->all();
        }

        return $campeonato->inscricoes()
            ->where('status', 'confirmada')
            ->with('atleta:id,nome')
            ->get()
            ->filter(fn ($inscricao) => !is_null($inscricao->atleta))
            ->map(fn ($inscricao) => ['id' => $inscricao->atleta->id, 'nome' => $inscricao->atleta->nome])
            ->values()
            ->all();
    }

    private function persistirSugestoes(Fase $fase, array $sugestoes): void
    {
        foreach ($sugestoes as $sugestao) {
            $fase->partidas()->create([
                'fase_grupo_id' => $sugestao['fase_grupo_id'] ?? null,
                'mandante_id' => $sugestao['mandante_id'],
                'visitante_id' => $sugestao['visitante_id'],
                'status' => $sugestao['status'],
                'ordem_na_fase' => $sugestao['ordem_na_fase'],
                'vencedor_id' => $sugestao['vencedor_id'],
                'origem_mandante_type' => $sugestao['origem_mandante']['type'],
                'origem_mandante_payload' => $sugestao['origem_mandante']['payload'],
                'origem_visitante_type' => $sugestao['origem_visitante']['type'],
                'origem_visitante_payload' => $sugestao['origem_visitante']['payload'],
                'definido_manualmente' => false,
            ]);
        }
    }
}
