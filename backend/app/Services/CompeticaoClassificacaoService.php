<?php

namespace App\Services;

use App\Enums\CriterioClassificacaoFase;
use App\Models\Fase;
use App\Models\FaseGrupo;
use App\Models\Partida;
use Illuminate\Support\Collection;

class CompeticaoClassificacaoService
{
    public function calcularClassificacaoFase(Fase $fase): array
    {
        $classificacao = [];

        // Se a fase tem grupos, calcula por grupo
        if ($fase->grupos()->count() > 0) {
            foreach ($fase->grupos as $grupo) {
                $classificacao[$grupo->nome] = $this->calcularClassificacaoGrupo($fase, $grupo);
            }
            // Calcula também uma classificação geral (para comparação inter-grupos)
            $classificacao['Geral'] = $this->calcularClassificacaoGrupo($fase, null);
        } else {
            // Se for pontos corridos sem grupo, é uma classificação única
            $classificacao['Geral'] = $this->calcularClassificacaoGrupo($fase, null);
        }

        return $classificacao;
    }

    private function calcularClassificacaoGrupoPorNome(Fase $fase, string $nomeGrupo): array
    {
        $partidas = $fase->partidas->filter(function ($partida) use ($nomeGrupo) {
            return $partida->status === 'finalizada' &&
                   $partida->grupo === $nomeGrupo;
        });

        return $this->gerarTabelaClassificacao($fase, $partidas);
    }

    public function calcularMelhoresXColocados(Fase $fase, int $posicao): array
    {
        $classificacaoPorGrupo = $this->calcularClassificacaoFase($fase);
        $timesNaPosicao = [];

        foreach ($classificacaoPorGrupo as $grupoNome => $tabela) {
            // Ignora classificação geral se houver grupos
            if ($grupoNome === 'Geral' && count($classificacaoPorGrupo) > 1) {
                continue;
            }
            
            // Encontra o time na posição X
            $time = collect($tabela)->firstWhere('posicao', $posicao);
            if ($time) {
                $time['grupo_origem'] = $grupoNome;
                $timesNaPosicao[] = $time;
            }
        }

        // Ordena esse "grupo virtual" usando as mesmas regras da fase
        $fase->load('regrasClassificacao');
        $regras = $fase->regrasClassificacao->sortBy('ordem');

        if ($regras->isEmpty()) {
            $regras = collect([
                (object)['criterio' => CriterioClassificacaoFase::PONTOS->value],
                (object)['criterio' => CriterioClassificacaoFase::VITORIAS->value],
                (object)['criterio' => CriterioClassificacaoFase::SALDO_GOLS->value],
                (object)['criterio' => CriterioClassificacaoFase::GOLS_PRO->value],
            ]);
        }

        // Para comparação entre grupos, confronto direto geralmente não se aplica, mas vamos manter a lógica genérica
        // Se houver confronto direto configurado, ele só funcionará se os times jogaram entre si (o que não acontece entre grupos diferentes)
        // Então na prática o sort vai cair nos outros critérios
        
        // Precisamos das partidas de todos para passar para o comparador, caso seja necessário
        // Mas o comparador espera partidas do grupo. Para inter-grupos, confronto direto é irrelevante.
        // Vamos passar todas as partidas da fase.
        $todasPartidas = $fase->partidas->where('status', 'finalizada');

        usort($timesNaPosicao, function ($a, $b) use ($regras, $todasPartidas) {
            foreach ($regras as $regra) {
                // Confronto direto não faz sentido entre grupos diferentes, pular
                if ($regra->criterio === CriterioClassificacaoFase::CONFRONTO_DIRETO->value) {
                    continue;
                }
                
                $resultado = $this->compararPorCriterio($a, $b, $regra->criterio, $todasPartidas);
                if ($resultado !== 0) {
                    return $resultado;
                }
            }
            return 0;
        });

        // Reindexa posições relativas
        foreach ($timesNaPosicao as $index => &$item) {
            $item['posicao_relativa'] = $index + 1;
        }

        return $timesNaPosicao;
    }

    private function calcularClassificacaoGrupo(Fase $fase, ?FaseGrupo $grupo): array
    {
        $partidas = $fase->partidas->filter(function ($partida) use ($grupo) {
            return $partida->status === 'finalizada' &&
                   (!$grupo || $partida->fase_grupo_id === $grupo->id || $partida->grupo === $grupo->nome);
        });

        return $this->gerarTabelaClassificacao($fase, $partidas, $grupo);
    }

    private function gerarTabelaClassificacao(Fase $fase, $partidas, $grupo = null): array
    {
        $participantes = $this->extrairParticipantes($partidas, $fase, $grupo);
        $tabela = [];

        foreach ($participantes as $id => $nome) {
            $stats = $this->calcularEstatisticasParticipante($id, $nome, $partidas);
            $tabela[] = $stats;
        }

        $regras = $fase->regrasClassificacao->sortBy('ordem');
        
        // Se não tiver regras configuradas, usa padrão: Pontos > Vitórias > Saldo > Gols Pró
        if ($regras->isEmpty()) {
            $regras = collect([
                (object)['criterio' => CriterioClassificacaoFase::PONTOS->value],
                (object)['criterio' => CriterioClassificacaoFase::VITORIAS->value],
                (object)['criterio' => CriterioClassificacaoFase::SALDO_GOLS->value],
                (object)['criterio' => CriterioClassificacaoFase::GOLS_PRO->value],
            ]);
        }

        usort($tabela, function ($a, $b) use ($regras, $partidas) {
            foreach ($regras as $regra) {
                $resultado = $this->compararPorCriterio($a, $b, $regra->criterio, $partidas);
                if ($resultado !== 0) {
                    return $resultado;
                }
            }
            return 0; // Empate total
        });

        // Adiciona posição
        foreach ($tabela as $index => &$item) {
            $item['posicao'] = $index + 1;
        }

        return $tabela;
    }

    private function extrairParticipantes(Collection $partidas, Fase $fase, $grupo = null): array
    {
        $participantes = [];
        
        // 1. Extrair de grupos configurados (mesmo sem partidas finalizadas)
        if ($grupo instanceof FaseGrupo) {
            $config = $grupo->configuracao;
            if (is_string($config)) {
                $config = json_decode($config, true);
            }
            $equipeIds = $config['equipe_ids'] ?? [];
            if (!empty($equipeIds)) {
                $equipes = \App\Models\Equipe::whereIn('id', $equipeIds)->get();
                foreach ($equipes as $equipe) {
                    $participantes[$equipe->id] = $equipe->nome;
                }
            }
        } elseif (!$grupo) {
            // Geral: pega de todos os grupos da fase se houver
            foreach ($fase->grupos as $g) {
                $config = $g->configuracao;
                if (is_string($config)) {
                    $config = json_decode($config, true);
                }
                $equipeIds = $config['equipe_ids'] ?? [];
                if (!empty($equipeIds)) {
                    $equipes = \App\Models\Equipe::whereIn('id', $equipeIds)->get();
                    foreach ($equipes as $equipe) {
                        $participantes[$equipe->id] = $equipe->nome;
                    }
                }
            }
        }

        // 2. Fallback: extrair das partidas (para times que jogaram mas não estavam na config do grupo)
        foreach ($partidas as $partida) {
            if ($partida->mandante_id) {
                $participantes[$partida->mandante_id] = $partida->mandante->nome ?? 'Desconhecido';
            }
            if ($partida->visitante_id) {
                $participantes[$partida->visitante_id] = $partida->visitante->nome ?? 'Desconhecido';
            }
        }
        return $participantes;
    }

    private function calcularEstatisticasParticipante(int $id, string $nome, Collection $partidas): array
    {
        $stats = [
            'id' => $id,
            'nome' => $nome,
            'pontos' => 0,
            'jogos' => 0,
            'vitorias' => 0,
            'empates' => 0,
            'derrotas' => 0,
            'gols_pro' => 0,
            'gols_contra' => 0,
            'saldo_gols' => 0,
            'fair_play' => 0, // TODO: Implementar cartões
        ];

        foreach ($partidas as $partida) {
            if ($partida->mandante_id == $id) {
                $golsPro = $partida->placar_mandante;
                $golsContra = $partida->placar_visitante;
                $this->computarResultado($stats, $golsPro, $golsContra);
            } elseif ($partida->visitante_id == $id) {
                $golsPro = $partida->placar_visitante;
                $golsContra = $partida->placar_mandante;
                $this->computarResultado($stats, $golsPro, $golsContra);
            }
        }

        return $stats;
    }

    private function computarResultado(array &$stats, int $golsPro, int $golsContra): void
    {
        $stats['jogos']++;
        $stats['gols_pro'] += $golsPro;
        $stats['gols_contra'] += $golsContra;
        $stats['saldo_gols'] = $stats['gols_pro'] - $stats['gols_contra'];

        if ($golsPro > $golsContra) {
            $stats['pontos'] += 3;
            $stats['vitorias']++;
        } elseif ($golsPro == $golsContra) {
            $stats['pontos'] += 1;
            $stats['empates']++;
        } else {
            $stats['derrotas']++;
        }
    }

    private function compararPorCriterio(array $a, array $b, string $criterio, Collection $partidas): int
    {
        switch ($criterio) {
            case CriterioClassificacaoFase::PONTOS->value:
                return $b['pontos'] <=> $a['pontos'];
            case CriterioClassificacaoFase::VITORIAS->value:
                return $b['vitorias'] <=> $a['vitorias'];
            case CriterioClassificacaoFase::SALDO_GOLS->value:
                return $b['saldo_gols'] <=> $a['saldo_gols'];
            case CriterioClassificacaoFase::GOLS_PRO->value:
                return $b['gols_pro'] <=> $a['gols_pro'];
            case CriterioClassificacaoFase::GOLS_CONTRA->value:
                return $a['gols_contra'] <=> $b['gols_contra']; // Menos gols sofridos é melhor
            case CriterioClassificacaoFase::CONFRONTO_DIRETO->value:
                return $this->calcularConfrontoDireto($a['id'], $b['id'], $partidas);
            case CriterioClassificacaoFase::FAIR_PLAY->value:
                return $b['fair_play'] <=> $a['fair_play']; // Maior fair play (menos cartões -> pontuação negativa?) TODO: Definir regra
            case CriterioClassificacaoFase::SORTEIO->value:
                return 0; // Sorteio é manual ou externo
            default:
                return 0;
        }
    }

    private function calcularConfrontoDireto(int $idA, int $idB, Collection $partidas): int
    {
        // Filtra partidas apenas entre A e B
        $confrontos = $partidas->filter(function ($p) use ($idA, $idB) {
            return ($p->mandante_id == $idA && $p->visitante_id == $idB) ||
                   ($p->mandante_id == $idB && $p->visitante_id == $idA);
        });

        if ($confrontos->isEmpty()) {
            return 0;
        }

        $pontosA = 0;
        $pontosB = 0;
        $saldoA = 0;
        $saldoB = 0;

        foreach ($confrontos as $p) {
            $golsA = ($p->mandante_id == $idA) ? $p->placar_mandante : $p->placar_visitante;
            $golsB = ($p->mandante_id == $idB) ? $p->placar_mandante : $p->placar_visitante;

            if ($golsA > $golsB) {
                $pontosA += 3;
            } elseif ($golsA < $golsB) {
                $pontosB += 3;
            } else {
                $pontosA += 1;
                $pontosB += 1;
            }

            $saldoA += ($golsA - $golsB);
            $saldoB += ($golsB - $golsA);
        }

        if ($pontosA !== $pontosB) {
            return $pontosB <=> $pontosA;
        }

        return $saldoB <=> $saldoA;
    }
}
