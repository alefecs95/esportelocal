<?php

namespace App\Services;

use App\Models\EventoPartida;
use App\Models\Partida;
use App\Models\Equipe;
use App\Models\PartidaEscalacao;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EventoPartidaService
{
    public function criarEvento(array $dados)
    {
        return DB::transaction(function () use ($dados) {
            if (!empty($dados['request_id'])) {
                $existente = EventoPartida::where('partida_id', $dados['partida_id'])
                    ->where('request_id', $dados['request_id'])
                    ->first();

                if ($existente) {
                    return $existente;
                }
            }

            $this->validarEvento($dados);

            $evento = EventoPartida::create($dados);

            if (in_array($evento->tipo_evento, ['GOL', 'GOL_CONTRA'], true)) {
                $this->atualizarPlacar($evento->partida_id);
            }

            return $evento;
        });
    }

    public function deletarEvento($id)
    {
        return DB::transaction(function () use ($id) {
            $evento = EventoPartida::findOrFail($id);
            $partidaId = $evento->partida_id;
            
            $evento->delete();

            if (in_array($evento->tipo_evento, ['GOL', 'GOL_CONTRA'], true)) {
                $this->atualizarPlacar($partidaId);
            }
        });
    }

    private function validarEvento(array $dados)
    {
        if ($dados['minuto'] < 0) {
            throw ValidationException::withMessages(['minuto' => 'O minuto deve ser um número positivo.']);
        }

        // Check for duplicates (same player, same minute, same type)
        $exists = EventoPartida::where('partida_id', $dados['partida_id'])
            ->where('jogador_id', $dados['jogador_id'])
            ->where('tipo_evento', $dados['tipo_evento'])
            ->where('minuto', $dados['minuto'])
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages(['evento' => 'Este evento já foi registrado.']);
        }

        $partida = Partida::with('fase.campeonato')->findOrFail($dados['partida_id']);
        $tipoCompeticao = $partida->fase->campeonato->tipo_competicao ?? 'equipe';

        if (
            (int) $dados['time_id'] !== (int) $partida->mandante_id
            && (int) $dados['time_id'] !== (int) $partida->visitante_id
        ) {
            throw ValidationException::withMessages(['time_id' => 'Equipe informada não participa desta partida.']);
        }

        if ($dados['tipo_evento'] === 'GOL_CONTRA' && $tipoCompeticao !== 'equipe') {
            throw ValidationException::withMessages(['tipo_evento' => 'Gol contra só é permitido em competições por equipe.']);
        }

        if ($tipoCompeticao === 'equipe') {
            $titularesMandante = PartidaEscalacao::where('partida_id', (int) $partida->id)
                ->where('equipe_id', (int) $partida->mandante_id)
                ->where('titular', true)
                ->count();
            $titularesVisitante = PartidaEscalacao::where('partida_id', (int) $partida->id)
                ->where('equipe_id', (int) $partida->visitante_id)
                ->where('titular', true)
                ->count();

            if ($titularesMandante !== 11 || $titularesVisitante !== 11) {
                throw ValidationException::withMessages([
                    'escalacao' => 'Antes de registrar eventos, informe os 11 titulares de cada equipe para esta partida.'
                ]);
            }

            $equipe = Equipe::findOrFail($dados['time_id']);
            $aprovado = $equipe->atletas()
                ->where('atletas.id', $dados['jogador_id'])
                ->wherePivot('status', 'aprovado')
                ->exists();

            if (!$aprovado) {
                throw ValidationException::withMessages(['jogador_id' => 'Somente atletas aprovados no elenco podem receber eventos na partida.']);
            }

            $existeEscalacaoTime = PartidaEscalacao::where('partida_id', $partida->id)
                ->where('equipe_id', (int) $dados['time_id'])
                ->exists();

            if ($existeEscalacaoTime) {
                $escalado = PartidaEscalacao::where('partida_id', $partida->id)
                    ->where('equipe_id', (int) $dados['time_id'])
                    ->where('atleta_id', (int) $dados['jogador_id'])
                    ->exists();

                if (!$escalado) {
                    throw ValidationException::withMessages([
                        'jogador_id' => 'Atleta não está escalado para esta partida.'
                    ]);
                }
            }

            $statusSuspensaoJogador = $this->obterStatusSuspensao($partida, (int) $dados['jogador_id'], (int) $dados['time_id']);
            if ($statusSuspensaoJogador['suspenso']) {
                throw ValidationException::withMessages([
                    'jogador_id' => "Atleta suspenso para esta partida. {$statusSuspensaoJogador['motivo']}"
                ]);
            }

            if ($dados['tipo_evento'] === 'SUBSTITUICAO') {
                if (empty($dados['jogador_saiu_id'])) {
                    throw ValidationException::withMessages(['jogador_saiu_id' => 'Informe o atleta que saiu na substituição.']);
                }

                if ((int) $dados['jogador_saiu_id'] === (int) $dados['jogador_id']) {
                    throw ValidationException::withMessages(['jogador_saiu_id' => 'Atleta que sai deve ser diferente do atleta que entra.']);
                }

                $saiuAprovado = $equipe->atletas()
                    ->where('atletas.id', $dados['jogador_saiu_id'])
                    ->wherePivot('status', 'aprovado')
                    ->exists();

                if (!$saiuAprovado) {
                    throw ValidationException::withMessages(['jogador_saiu_id' => 'Somente atletas aprovados no elenco podem sair na substituição.']);
                }

                if ($existeEscalacaoTime) {
                    $saiuEscalado = PartidaEscalacao::where('partida_id', $partida->id)
                        ->where('equipe_id', (int) $dados['time_id'])
                        ->where('atleta_id', (int) $dados['jogador_saiu_id'])
                        ->exists();

                    if (!$saiuEscalado) {
                        throw ValidationException::withMessages([
                            'jogador_saiu_id' => 'Atleta que saiu não está escalado para esta partida.'
                        ]);
                    }
                }

                $statusSuspensaoSaiu = $this->obterStatusSuspensao($partida, (int) $dados['jogador_saiu_id'], (int) $dados['time_id']);
                if ($statusSuspensaoSaiu['suspenso']) {
                    throw ValidationException::withMessages([
                        'jogador_saiu_id' => "Atleta suspenso para esta partida. {$statusSuspensaoSaiu['motivo']}"
                    ]);
                }
            }
        }
    }

    private function obterStatusSuspensao(Partida $partida, int $jogadorId, int $timeId): array
    {
        $partida->loadMissing('fase.campeonato');
        $campeonato = $partida->fase?->campeonato;
        if (!$campeonato || !($campeonato->suspensao_automatica_ativa ?? true)) {
            return ['suspenso' => false, 'motivo' => ''];
        }

        $campeonatoId = (int) $campeonato->id;
        $faseAtualZeraCartoes = $this->faseZeraCartoes($partida->fase?->configuracao);
        $limiteAmarelos = max(1, (int) ($campeonato->cartoes_amarelos_para_suspensao ?? 2));
        $vermelhoSuspende = (bool) ($campeonato->cartao_vermelho_suspende ?? true);

        $partidasFinalizadas = Partida::query()
            ->whereHas('fase', function ($q) use ($campeonatoId) {
                $q->where('campeonato_id', $campeonatoId);
            })
            ->where(function ($q) use ($timeId) {
                $q->where('mandante_id', $timeId)->orWhere('visitante_id', $timeId);
            })
            ->where('status', 'finalizada')
            ->where('id', '!=', $partida->id);

        if ($faseAtualZeraCartoes) {
            // Se a fase atual zera cartões, só consideramos histórico da própria fase.
            $partidasFinalizadas->where('fase_id', $partida->fase_id);
        }

        if ($partida->data_jogo) {
            $partidasFinalizadas->where('data_jogo', '<', $partida->data_jogo);
        } else {
            $partidasFinalizadas->where('id', '<', $partida->id);
        }

        $partidasFinalizadas = $partidasFinalizadas
            ->with('fase:id,configuracao')
            ->orderBy('data_jogo')
            ->orderBy('id')
            ->get(['id', 'fase_id']);

        if ($partidasFinalizadas->isEmpty()) {
            return ['suspenso' => false, 'motivo' => ''];
        }

        $partidasIds = $partidasFinalizadas->pluck('id')->all();

        $cartoesPorPartida = EventoPartida::query()
            ->join('partidas', 'eventos_partida.partida_id', '=', 'partidas.id')
            ->join('fases', 'partidas.fase_id', '=', 'fases.id')
            ->where('fases.campeonato_id', $campeonatoId)
            ->where('eventos_partida.jogador_id', $jogadorId)
            ->where('eventos_partida.time_id', $timeId)
            ->whereIn('eventos_partida.partida_id', $partidasIds)
            ->whereIn('eventos_partida.tipo_evento', ['CARTAO_AMARELO', 'CARTAO_VERMELHO'])
            ->select(
                'eventos_partida.partida_id',
                'eventos_partida.tipo_evento',
                'eventos_partida.id'
            )
            ->orderBy('eventos_partida.partida_id')
            ->orderBy('eventos_partida.id')
            ->get()
            ->groupBy('partida_id');

        $suspensoesPendentes = 0;
        $amarelosAcumulados = 0;
        $motivoAtual = '';
        $ultimaFaseId = null;

        foreach ($partidasFinalizadas as $partidaFinalizada) {
            $faseIdAtual = (int) ($partidaFinalizada->fase_id ?? 0);
            $configuracaoFaseAtual = $partidaFinalizada->fase?->configuracao;
            $entrouNovaFase = $ultimaFaseId !== null && $faseIdAtual !== $ultimaFaseId;
            if ($entrouNovaFase && $this->faseZeraCartoes($configuracaoFaseAtual)) {
                $suspensoesPendentes = 0;
                $amarelosAcumulados = 0;
                $motivoAtual = '';
            }
            $ultimaFaseId = $faseIdAtual;

            if ($suspensoesPendentes > 0) {
                $suspensoesPendentes--;
                if ($suspensoesPendentes === 0) {
                    $amarelosAcumulados = 0;
                    $motivoAtual = '';
                }
                continue;
            }

            $eventosDaPartida = $cartoesPorPartida->get($partidaFinalizada->id, collect());
            if ($eventosDaPartida->isEmpty()) {
                continue;
            }

            $qtdAmarelosPartida = $eventosDaPartida->where('tipo_evento', 'CARTAO_AMARELO')->count();
            if ($qtdAmarelosPartida > 0) {
                $amarelosAcumulados += $qtdAmarelosPartida;
            }

            if ($vermelhoSuspende && $eventosDaPartida->contains(fn ($e) => $e->tipo_evento === 'CARTAO_VERMELHO')) {
                $suspensoesPendentes++;
                $motivoAtual = 'Motivo: vermelho direto na última partida disputada.';
            }

            if ($amarelosAcumulados >= $limiteAmarelos) {
                $novasSuspensoes = intdiv($amarelosAcumulados, $limiteAmarelos);
                $suspensoesPendentes += $novasSuspensoes;
                $amarelosAcumulados = $amarelosAcumulados % $limiteAmarelos;
                $motivoAtual = "Motivo: acúmulo de {$limiteAmarelos} amarelos.";
            }
        }

        return [
            'suspenso' => $suspensoesPendentes > 0,
            'motivo' => $suspensoesPendentes > 0 ? $motivoAtual : '',
        ];
    }

    private function faseZeraCartoes($configuracaoFase): bool
    {
        if (is_string($configuracaoFase)) {
            $configuracaoFase = json_decode($configuracaoFase, true);
        } elseif (is_object($configuracaoFase)) {
            $configuracaoFase = (array) $configuracaoFase;
        }
        $config = is_array($configuracaoFase) ? $configuracaoFase : [];
        return (bool) ($config['zerar_cartoes_na_fase'] ?? false);
    }

    public function obterStatusSuspensaoPartida(Partida $partida, int $jogadorId, int $timeId): array
    {
        return $this->obterStatusSuspensao($partida, $jogadorId, $timeId);
    }

    private function atualizarPlacar($partidaId)
    {
        $partida = Partida::with('fase.campeonato')->findOrFail($partidaId);
        $tipo = $partida->fase->campeonato->tipo_competicao ?? 'equipe';
        
        if ($tipo === 'equipe') {
            $golsMandante = EventoPartida::where('partida_id', $partidaId)
                ->where(function ($query) use ($partida) {
                    $query->where(function ($q) use ($partida) {
                        $q->where('tipo_evento', 'GOL')
                            ->where('time_id', $partida->mandante_id);
                    })->orWhere(function ($q) use ($partida) {
                        $q->where('tipo_evento', 'GOL_CONTRA')
                            ->where('time_id', $partida->visitante_id);
                    });
                })
                ->count();

            $golsVisitante = EventoPartida::where('partida_id', $partidaId)
                ->where(function ($query) use ($partida) {
                    $query->where(function ($q) use ($partida) {
                        $q->where('tipo_evento', 'GOL')
                            ->where('time_id', $partida->visitante_id);
                    })->orWhere(function ($q) use ($partida) {
                        $q->where('tipo_evento', 'GOL_CONTRA')
                            ->where('time_id', $partida->mandante_id);
                    });
                })
                ->count();
        } else {
            // Count goals for mandante (Athlete)
            $golsMandante = EventoPartida::where('partida_id', $partidaId)
                ->where('tipo_evento', 'GOL')
                ->where('jogador_id', $partida->mandante_id)
                ->count();

            // Count goals for visitante (Athlete)
            $golsVisitante = EventoPartida::where('partida_id', $partidaId)
                ->where('tipo_evento', 'GOL')
                ->where('jogador_id', $partida->visitante_id)
                ->count();
        }

        $partida->update([
            'placar_mandante' => $golsMandante,
            'placar_visitante' => $golsVisitante
        ]);
    }
    
    public function listarEventos($partidaId)
    {
        return EventoPartida::with(['jogador', 'jogadorSaiu', 'time'])
            ->where('partida_id', $partidaId)
            ->orderBy('minuto')
            ->get();
    }
}
