<?php

namespace App\Services;

use App\Models\Campeonato;
use App\Models\RankingAtleta;
use App\Models\RankingEquipe;
use Illuminate\Support\Facades\DB;
use Exception;

class RankingService
{
    /**
     * Atualiza o ranking da temporada e modalidade com base nos campeonatos finalizados.
     * Recalcula tudo para garantir consistência.
     */
    public function atualizarRanking(Campeonato $campeonato)
    {
        // Verifica se a temporada existe e está ativa (opcional, mas recomendado)
        if ($campeonato->temporada && $campeonato->temporada->status !== 'ativa') {
            // Se a temporada não está ativa, não atualizamos o ranking (histórico congelado)
            return;
        }

        // Se quisermos manter o método incremental ou apenas chamar o método geral
        // Vamos usar o método geral para garantir consistência sempre.
        // Mas o método geral 'recalcularRankingTemporada' apaga TUDO.
        // Isso pode ser overkill para cada update.
        // Mas como solicitado, vamos isolar o cálculo e garantir que funcione.
        
        $this->recalcularRankingTemporada($campeonato->temporada_id, $campeonato->modalidade_id);
    }

    /**
     * Recalcula o ranking completo de uma temporada.
     * Pode filtrar por modalidade opcionalmente.
     */
    public function recalcularRankingTemporada($temporadaId, $modalidadeId = null)
    {
        DB::beginTransaction();
        try {
            // 1. Limpar ranking atual
            $queryEquipe = RankingEquipe::where('temporada_id', $temporadaId);
            $queryAtleta = RankingAtleta::where('temporada_id', $temporadaId);
            
            if ($modalidadeId) {
                $queryEquipe->where('modalidade_id', $modalidadeId);
                $queryAtleta->where('modalidade_id', $modalidadeId);
            }
            
            $queryEquipe->delete();
            $queryAtleta->delete();

            // 2. Buscar campeonatos finalizados
            $queryCamp = Campeonato::where('temporada_id', $temporadaId)
                ->where('status', 'finalizado');
            
            if ($modalidadeId) {
                $queryCamp->where('modalidade_id', $modalidadeId);
            }
            
            $campeonatos = $queryCamp->get();

            // 3. Calcular pontos
            $pontosEquipes = []; // [modalidade_id][equipe_id] => ['pontos' => x, 'titulos' => y, 'participacoes' => z]
            $pontosAtletas = []; // [modalidade_id][atleta_id] => ['pontos' => x, 'titulos' => y, 'participacoes' => z]

            foreach ($campeonatos as $camp) {
                $classificacao = $camp->classificacaoFinal();
                
                foreach ($classificacao as $index => $participante) {
                    $posicao = $index + 1;
                    $pontos = $this->calcularPontos($posicao);
                    
                    if ($camp->tipo_competicao === 'equipe') {
                        $key = $camp->modalidade_id . '_' . $participante->id;
                        if (!isset($pontosEquipes[$key])) {
                            $pontosEquipes[$key] = [
                                'modalidade_id' => $camp->modalidade_id,
                                'equipe_id' => $participante->id,
                                'pontos' => 0,
                                'participacoes' => 0,
                                'titulos' => 0
                            ];
                        }
                        $pontosEquipes[$key]['pontos'] += $pontos;
                        $pontosEquipes[$key]['participacoes'] += 1;
                        if ($posicao == 1) $pontosEquipes[$key]['titulos'] += 1;
                    } else {
                        $key = $camp->modalidade_id . '_' . $participante->id;
                        if (!isset($pontosAtletas[$key])) {
                            $pontosAtletas[$key] = [
                                'modalidade_id' => $camp->modalidade_id,
                                'atleta_id' => $participante->id,
                                'pontos' => 0,
                                'participacoes' => 0,
                                'titulos' => 0
                            ];
                        }
                        $pontosAtletas[$key]['pontos'] += $pontos;
                        $pontosAtletas[$key]['participacoes'] += 1;
                        if ($posicao == 1) $pontosAtletas[$key]['titulos'] += 1;
                    }
                }
            }

            // 4. Salvar e gerar posições
            // Agrupar por modalidade para calcular posição dentro da modalidade
            
            // Equipes
            $groupedEquipes = [];
            foreach ($pontosEquipes as $p) {
                $groupedEquipes[$p['modalidade_id']][] = $p;
            }
            
            foreach ($groupedEquipes as $modId => $lista) {
                // Ordenar por pontos desc
                usort($lista, function($a, $b) {
                    return $b['pontos'] <=> $a['pontos'];
                });
                
                foreach ($lista as $idx => $dados) {
                    RankingEquipe::create([
                        'temporada_id' => $temporadaId,
                        'modalidade_id' => $dados['modalidade_id'],
                        'equipe_id' => $dados['equipe_id'],
                        'pontos' => $dados['pontos'],
                        'participacoes' => $dados['participacoes'],
                        'titulos' => $dados['titulos'],
                        'posicao' => $idx + 1
                    ]);
                }
            }

            // Atletas
            $groupedAtletas = [];
            foreach ($pontosAtletas as $p) {
                $groupedAtletas[$p['modalidade_id']][] = $p;
            }
            
            foreach ($groupedAtletas as $modId => $lista) {
                usort($lista, function($a, $b) {
                    return $b['pontos'] <=> $a['pontos'];
                });
                
                foreach ($lista as $idx => $dados) {
                    RankingAtleta::create([
                        'temporada_id' => $temporadaId,
                        'modalidade_id' => $dados['modalidade_id'],
                        'atleta_id' => $dados['atleta_id'],
                        'pontos' => $dados['pontos'],
                        'participacoes' => $dados['participacoes'],
                        'titulos' => $dados['titulos'],
                        'posicao' => $idx + 1
                    ]);
                }
            }
            
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function calcularPontos($posicao)
    {
        // Regra padrão:
        // 1º 10
        // 2º 7
        // 3º 5
        // 4º 3
        // Participação 1
        
        switch ($posicao) {
            case 1: return 10;
            case 2: return 7;
            case 3: return 5;
            case 4: return 3;
            default: return 1;
        }
    }
}
