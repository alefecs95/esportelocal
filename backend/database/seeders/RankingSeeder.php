<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Temporada;
use App\Models\Modalidade;
use App\Models\Atleta;
use App\Models\Equipe;
use App\Models\Campeonato;
use App\Models\Inscricao;
use App\Services\RankingService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RankingSeeder extends Seeder
{
    protected $rankingService;

    public function __construct(RankingService $rankingService)
    {
        $this->rankingService = $rankingService;
    }

    public function run()
    {
        // Limpar dados anteriores para evitar duplicidade ou inconsistência no teste
        // CUIDADO: Isso limpa tabelas importantes. Em produção não faríamos isso.
        // Como é um ambiente de dev local (assumido pelo contexto do usuário pedindo "alimente nosso sistema"), ok.
        
        // Vamos apenas criar dados novos se não existirem, ou forçar criação.
        // Para garantir um cenário limpo de teste de ranking, vamos criar uma Temporada de Teste específica.
        
        $temporada = Temporada::firstOrCreate(
            ['ano' => 2026],
            [
                'nome' => 'Temporada 2026',
                'data_inicio' => '2026-01-01',
                'data_fim' => '2026-12-31',
                'status' => 'ativa'
            ]
        );

        // Modalidades
        $futsal = Modalidade::firstOrCreate(['nome' => 'Futsal'], ['tipo' => 'coletivo']);
        $atletismo = Modalidade::firstOrCreate(['nome' => 'Atletismo'], ['tipo' => 'individual']);
        $volei = Modalidade::firstOrCreate(['nome' => 'Vôlei'], ['tipo' => 'coletivo']);

        // Criar Atletas
        $atletas = Atleta::factory()->count(20)->create();

        // --- CENÁRIO 1: CAMPEONATO DE EQUIPE (FUTSAL) ---
        
        $nomesEquipes = ['Tigres FC', 'Águias da Serra', 'Leões do Norte', 'Real Municipal', 'Atlético Central'];

        // Criar Campeonato Finalizado
        $campFutsal = Campeonato::create([
            'temporada_id' => $temporada->id,
            'modalidade_id' => $futsal->id,
            'nome' => 'Copa Municipal de Futsal 2026',
            'tipo_competicao' => 'equipe',
            'status' => 'finalizado',
            'data_inicio' => '2026-03-01',
            'data_fim' => '2026-03-30',
            'formato' => 'mata_mata',
            'categoria' => 'Adulto',
            'genero' => 'masculino',
            'valor_inscricao' => 0,
            'limite_participantes' => 16,
            'idade_min' => 18,
            'idade_max' => 99,
            'min_atletas_equipe' => 5,
            'max_atletas_equipe' => 12
        ]);

        foreach ($nomesEquipes as $index => $nome) {
            Equipe::create([
                'campeonato_id' => $campFutsal->id,
                'nome' => $nome,
                'responsavel' => 'Técnico ' . $nome,
                'contato' => '99999-9999',
                'colocacao' => $index + 1 // 1, 2, 3, 4, 5
            ]);
        }

        // --- CENÁRIO 2: CAMPEONATO INDIVIDUAL (ATLETISMO) ---
        
        $campAtletismo = Campeonato::create([
            'temporada_id' => $temporada->id,
            'modalidade_id' => $atletismo->id,
            'nome' => 'Corrida de Rua 5km',
            'tipo_competicao' => 'individual',
            'status' => 'finalizado',
            'data_inicio' => '2026-04-15',
            'data_fim' => '2026-04-15',
            'formato' => 'pontos_corridos',
            'categoria' => 'Livre',
            'genero' => 'misto',
            'valor_inscricao' => 0,
            'limite_participantes' => 100,
            'idade_min' => 16,
            'idade_max' => 99
        ]);

        // Inscrever atletas e definir colocação
        // Vamos pegar os 10 primeiros atletas
        foreach ($atletas->take(10) as $index => $atleta) {
            Inscricao::create([
                'campeonato_id' => $campAtletismo->id,
                'atleta_id' => $atleta->id,
                'status' => 'confirmada',
                'data_pagamento' => now(),
                'valor_pago' => 0,
                'colocacao' => $index + 1 // 1 a 10
            ]);
        }

        // --- CENÁRIO 3: OUTRO CAMPEONATO FUTSAL (Para somar pontos) ---
        // Para testar ranking acumulado
        
        $campFutsal2 = Campeonato::create([
            'temporada_id' => $temporada->id,
            'modalidade_id' => $futsal->id,
            'nome' => 'Torneio de Inverno Futsal 2026',
            'tipo_competicao' => 'equipe',
            'status' => 'finalizado',
            'data_inicio' => '2026-06-01',
            'data_fim' => '2026-06-10',
            'formato' => 'grupos',
            'categoria' => 'Adulto',
            'genero' => 'masculino',
            'valor_inscricao' => 0,
            'limite_participantes' => 8,
            'idade_min' => 18,
            'idade_max' => 99,
            'min_atletas_equipe' => 5,
            'max_atletas_equipe' => 12
        ]);

        // A equipe "Tigres FC" (index 0) ganhou de novo!
        // A equipe "Águias" (index 1) ficou em 3º dessa vez.
        
        // Tigres (1º)
        Equipe::create([
            'campeonato_id' => $campFutsal2->id,
            'nome' => $nomesEquipes[0],
            'colocacao' => 1,
            'responsavel' => 'Técnico',
            'contato' => '99999-9999'
        ]);

        // Águias (3º)
        Equipe::create([
            'campeonato_id' => $campFutsal2->id,
            'nome' => $nomesEquipes[1],
            'colocacao' => 3,
            'responsavel' => 'Técnico',
            'contato' => '99999-9999'
        ]);

        // Leões (2º)
        Equipe::create([
            'campeonato_id' => $campFutsal2->id,
            'nome' => $nomesEquipes[2],
            'colocacao' => 2,
            'responsavel' => 'Técnico',
            'contato' => '99999-9999'
        ]);
        
        // Recalcular Ranking
        $this->command->info('Recalculando ranking...');
        $this->rankingService->recalcularRankingTemporada($temporada->id);
        $this->command->info('Ranking populado com sucesso!');
    }
}
