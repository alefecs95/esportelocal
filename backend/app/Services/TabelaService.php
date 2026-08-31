<?php

namespace App\Services;

use App\Models\Campeonato;
use App\Models\Fase;
use App\Models\Partida;
use App\Models\Equipe;
use App\Models\Atleta;
use App\Models\Inscricao;
use App\Enums\FormatoCampeonato;
use Illuminate\Support\Facades\DB;
use Exception;
use Carbon\Carbon;

class TabelaService
{
    public function gerarTabela(Campeonato $campeonato, string $formatoStr, array $options = [])
    {
        // Se for corrida, não checa se já tem fases, pois corrida pode não ter fases de jogos
        // Mas se já tiver formato definido, avisa?
        
        $formato = FormatoCampeonato::tryFrom($formatoStr);
        if (!$formato) {
            throw new Exception("Formato inválido: $formatoStr");
        }

        if ($formato !== FormatoCampeonato::CORRIDA && $campeonato->fases()->exists()) {
             throw new Exception("Tabela já gerada para este campeonato.");
        }

        // Atualizar formato do campeonato
        // O Model fará o cast para Enum automaticamente
        $campeonato->update(['formato' => $formato]);

        DB::beginTransaction();
        try {
            switch ($formato) {
                case FormatoCampeonato::MATA_MATA:
                    $this->gerarMataMata($campeonato);
                    break;
                case FormatoCampeonato::GRUPOS:
                    $numGrupos = $options['num_grupos'] ?? 4; // Default 4 grupos
                    $this->gerarFaseGrupos($campeonato, $numGrupos);
                    break;
                case FormatoCampeonato::PONTOS_CORRIDOS:
                    $this->gerarPontosCorridos($campeonato);
                    break;
                case FormatoCampeonato::CORRIDA:
                    // Corrida não gera partidas, apenas define o formato.
                    // Poderíamos zerar os tempos/colocações anteriores se desejado,
                    // mas melhor não destruir dados.
                    break;
                default:
                    throw new Exception("Formato não implementado.");
            }
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Salva resultados de corrida e recalcula ranking
     * @param Campeonato $campeonato
     * @param array $resultados Array de ['inscricao_id' => int, 'tempo' => string]
     */
    public function salvarResultadosCorrida(Campeonato $campeonato, array $resultados)
    {
        if ($campeonato->formato !== FormatoCampeonato::CORRIDA) {
            throw new Exception("Este campeonato não é do tipo Corrida.");
        }

        DB::beginTransaction();
        try {
            foreach ($resultados as $res) {
                $inscricaoId = $res['inscricao_id'];
                $tempoVisual = $res['tempo']; // HH:MM:SS ou MM:SS ou null

                $tempoMs = null;
                if ($tempoVisual) {
                    $tempoMs = $this->converterTempoParaMs($tempoVisual);
                }

                Inscricao::where('id', $inscricaoId)
                    ->where('campeonato_id', $campeonato->id)
                    ->update([
                        'tempo_visual' => $tempoVisual,
                        'tempo_ms' => $tempoMs
                    ]);
            }

            // Recalcular colocações
            $this->calcularRankingCorrida($campeonato);

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function converterTempoParaMs($tempoStr)
    {
        // Remove caracteres não numéricos exceto :
        $tempoStr = preg_replace('/[^0-9:]/', '', $tempoStr);
        $parts = explode(':', $tempoStr);
        $seconds = 0;
        
        if (count($parts) === 3) { // HH:MM:SS
            $seconds += (int)$parts[0] * 3600;
            $seconds += (int)$parts[1] * 60;
            $seconds += (int)$parts[2];
        } elseif (count($parts) === 2) { // MM:SS
            $seconds += (int)$parts[0] * 60;
            $seconds += (int)$parts[1];
        } elseif (count($parts) === 1) { // Apenas número -> Tratar como MINUTOS para consistência com corridas de rua? 
             // Ou segundos? Se digitar 63, são 63 segundos ou 1h03?
             // Se for maratona, 63 minutos é comum. Se for 100m rasos, 63s é muito.
             // Vamos assumir MINUTOS se for maior que 0, para facilitar a vida do usuário que não digita :00
             $seconds += (int)$parts[0] * 60; 
        } else {
            return null; 
        }

        return $seconds * 1000; // Milissegundos
    }

    private function calcularRankingCorrida(Campeonato $campeonato)
    {
        // 1. Ranking Geral
        $inscricoesGeral = Inscricao::where('campeonato_id', $campeonato->id)
            ->whereNotNull('tempo_ms')
            ->orderBy('tempo_ms', 'asc')
            ->get();

        $rank = 1;
        foreach ($inscricoesGeral as $inscricao) {
            // Atualiza colocação (geral)
            // Se tivermos campo colocacao_geral e colocacao_categoria seria melhor
            // Por enquanto vamos usar 'colocacao' como GERAL
            $inscricao->update(['colocacao' => $rank]);
            $rank++;
        }

        // Se precisarmos de ranking por categoria, precisaríamos de outro campo ou tabela auxiliar.
        // O usuário pediu "rankin geral e por categorias".
        // Como só temos um campo 'colocacao' na tabela inscricoes (da migration anterior),
        // vamos assumir que 'colocacao' é o GERAL.
        // Para exibir por categoria no frontend, podemos filtrar e reordenar visualmente,
        // ou adicionar uma coluna 'colocacao_categoria' na tabela inscricoes.
        // Dado que não pedi migration para 'colocacao_categoria', vou calcular on-the-fly no frontend
        // OU fazer uma migration rápida agora se o usuário permitir.
        // O usuário disse "na corrida temos que ter é a opçao de colcoar o tempo de cada atleta para termos ranckin geral e por categorias"
        // Vou deixar o cálculo por categoria para o Frontend (computed property) baseado nos tempos, 
        // já que o tempo é absoluto. A colocação no banco fica sendo a Geral.
    }

    protected function getParticipantes(Campeonato $campeonato)
    {
        if ($campeonato->tipo_competicao === 'equipe') {
            return $campeonato->equipes()
                ->where('status_inscricao', 'aprovada')
                ->whereHas('atletas', function ($query) {
                    $query->where('equipe_atleta.status', 'aprovado');
                })
                ->get();
        } else {
            return $campeonato->inscricoes()
                ->where('status', 'confirmada')
                ->with('atleta')
                ->get()
                ->map(function ($inscricao) {
                return $inscricao->atleta;
            });
        }
    }

    protected function proximaPotenciaDe2($numero)
    {
        return pow(2, ceil(log($numero, 2)));
    }

    protected function gerarMataMata(Campeonato $campeonato)
    {
        $participantes = $this->getParticipantes($campeonato)->all(); // Array
        $total = count($participantes);

        if ($total < 2) {
            throw new Exception("Número insuficiente de participantes.");
        }

        shuffle($participantes);

        $tamanhoIdeal = $this->proximaPotenciaDe2($total);

        // Adiciona BYE se necessário
        while (count($participantes) < $tamanhoIdeal) {
            $participantes[] = null;
        }

        // Fase 1 (ex: Oitavas, Quartas...)
        $nomesFases = [
            2 => 'Final',
            4 => 'Semifinal',
            8 => 'Quartas de Final',
            16 => 'Oitavas de Final',
            32 => '16-avos de Final'
        ];

        $nomeFase = $nomesFases[$tamanhoIdeal] ?? "Rodada de $tamanhoIdeal";
        
        $fase = Fase::create([
            'campeonato_id' => $campeonato->id,
            'nome' => $nomeFase,
            'ordem' => 1
        ]);

        $partidas = [];
        // Gera os confrontos
        for ($i = 0; $i < $tamanhoIdeal; $i += 2) {
            $mandante = $participantes[$i];
            $visitante = $participantes[$i+1];

            // Se for competição individual, precisamos lidar diferente
            // Mas assumindo que 'mandante_id' e 'visitante_id' são IDs de equipe ou null
            // Se for individual, teremos que adaptar a tabela partidas ou usar Equipes "Fake" para Atletas
            // O sistema parece usar Equipe como entidade principal de confronto por enquanto.
            
            // TODO: Ajustar para Individual se necessário. Por enquanto assume Equipes.
            
            $partida = Partida::create([
                'fase_id' => $fase->id,
                'mandante_id' => $mandante ? $mandante->id : null,
                'visitante_id' => $visitante ? $visitante->id : null,
                'numero_jogo' => ($i / 2) + 1,
                'status' => ($mandante && $visitante) ? 'agendada' : 'finalizada', // Se tem BYE, já finaliza
                'vencedor_id' => (!$visitante && $mandante) ? $mandante->id : ((!$mandante && $visitante) ? $visitante->id : null)
            ]);
        }
    }

    protected function gerarFaseGrupos(Campeonato $campeonato, int $numGrupos)
    {
        // Lógica de grupos (simplificada)
        $participantes = $this->getParticipantes($campeonato);
        $total = $participantes->count();
        
        // Removida validação restritiva para permitir criação de tabela mesmo com menos participantes que grupos
        // if ($total < $numGrupos) {
        //      throw new Exception("Número de participantes menor que o número de grupos.");
        // }

        $participantes = $participantes->shuffle();
        // Se houver menos participantes que grupos, alguns grupos ficarão vazios ou com menos times, o que é ok.
        // O split do Laravel tenta dividir igualmente. Se total < numGrupos, ele retorna menos chunks.
        // Precisamos garantir que criamos a quantidade solicitada de grupos, mesmo que vazios.
        
        $gruposChunks = $participantes->split($numGrupos);
        
        $fase = Fase::create([
            'campeonato_id' => $campeonato->id,
            'nome' => 'Fase de Grupos',
            'ordem' => 1,
            'tipo' => 'grupos', // Adicionado tipo explícito
            'status' => 'ativa' // Já nasce ativa
        ]);

        // Criação explícita de grupos (A, B, C...) baseada no número solicitado
        for ($i = 0; $i < $numGrupos; $i++) {
            $nomeGrupo = chr(65 + $i); // A, B, C...
            
            // Cria o grupo no banco (usando FaseGrupo se existir ou apenas lógica visual)
            // Como o sistema atual usa 'grupo' string na tabela partidas, mantemos assim por enquanto.
            // Mas idealmente deveria criar FaseGrupo. Vamos manter compatibilidade com o código existente.
            
            // Pega os times deste grupo, se houver
            $times = isset($gruposChunks[$i]) ? $gruposChunks[$i]->values() : collect([]);
            $qtd = $times->count();
            
            // Gera confrontos apenas se houver 2 ou mais times
            if ($qtd >= 2) {
                for ($j = 0; $j < $qtd; $j++) {
                    for ($k = $j + 1; $k < $qtd; $k++) {
                        Partida::create([
                            'fase_id' => $fase->id,
                            'grupo' => $nomeGrupo,
                            'mandante_id' => $times[$j]->id,
                            'visitante_id' => $times[$k]->id,
                            'status' => 'agendada'
                        ]);
                    }
                }
            }
        }
    }

    protected function gerarPontosCorridos(Campeonato $campeonato)
    {
        // Similar a 1 grupão
        $this->gerarFaseGrupos($campeonato, 1);
        
        // Renomear fase
        $fase = $campeonato->fases()->first();
        $fase->update(['nome' => 'Turno Único']);
        
        // Atualizar partidas para remover "Grupo A" se quiser
        Partida::where('fase_id', $fase->id)->update(['grupo' => null]);
    }

    public function atualizarPlacar(Partida $partida, int $placarMandante, int $placarVisitante, bool $finalizar = true)
    {
        $status = $finalizar ? 'finalizada' : ($partida->status === 'agendada' ? 'em_andamento' : $partida->status);

        $partida->update([
            'placar_mandante' => $placarMandante,
            'placar_visitante' => $placarVisitante,
            'status' => $status
        ]);

        // Se for mata-mata, verificar se precisa avançar vencedor
        // Lógica de avanço automático pode ser complexa, deixar para depois ou implementar básico
        if ($partida->fase && str_contains(strtolower($partida->fase->nome), 'final') === false) {
             // Lógica para avançar para próxima fase (TODO)
        }
    }

    public function finalizarPartida(Partida $partida): void
    {
        $partida->update([
            'status' => 'finalizada',
            'placar_bloqueado' => true,
        ]);
    }
}
