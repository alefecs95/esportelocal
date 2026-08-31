<?php

namespace Tests\Feature;

use App\Enums\CriterioClassificacaoFase;
use App\Models\Campeonato;
use App\Models\Equipe;
use App\Models\Fase;
use App\Models\Partida;
use App\Services\CompeticaoClassificacaoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CompeticaoClassificacaoTest extends TestCase
{
    use RefreshDatabase;

    private CompeticaoClassificacaoService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CompeticaoClassificacaoService();
    }

    public function test_deve_calcular_classificacao_corretamente_por_pontos()
    {
        $campeonato = Campeonato::factory()->create(['tipo_competicao' => 'equipe']);
        $fase = Fase::factory()->create(['campeonato_id' => $campeonato->id]);
        $grupo = $fase->grupos()->create(['nome' => 'A', 'ordem' => 1]);

        $equipeA = Equipe::factory()->create(['campeonato_id' => $campeonato->id, 'nome' => 'Time A']);
        $equipeB = Equipe::factory()->create(['campeonato_id' => $campeonato->id, 'nome' => 'Time B']);
        $equipeC = Equipe::factory()->create(['campeonato_id' => $campeonato->id, 'nome' => 'Time C']);

        // Time A vence Time B (3x0)
        Partida::create([
            'prefeitura_id' => $campeonato->prefeitura_id, // Adicionado
            'fase_id' => $fase->id,
            'fase_grupo_id' => $grupo->id,
            'mandante_id' => $equipeA->id,
            'visitante_id' => $equipeB->id,
            'placar_mandante' => 3,
            'placar_visitante' => 0,
            'status' => 'finalizada'
        ]);

        // Time B empata com Time C (1x1)
        Partida::create([
            'prefeitura_id' => $campeonato->prefeitura_id, // Adicionado
            'fase_id' => $fase->id,
            'fase_grupo_id' => $grupo->id,
            'mandante_id' => $equipeB->id,
            'visitante_id' => $equipeC->id,
            'placar_mandante' => 1,
            'placar_visitante' => 1,
            'status' => 'finalizada'
        ]);

        // Time C vence Time A (2x1)
        Partida::create([
            'prefeitura_id' => $campeonato->prefeitura_id, // Adicionado
            'fase_id' => $fase->id,
            'fase_grupo_id' => $grupo->id,
            'mandante_id' => $equipeC->id,
            'visitante_id' => $equipeA->id,
            'placar_mandante' => 2,
            'placar_visitante' => 1,
            'status' => 'finalizada'
        ]);

        /*
         * Classificação Esperada:
         * 1. Time C: 4 pts (1V, 1E), SG +1 (3-2)
         * 2. Time A: 3 pts (1V, 1D), SG +2 (4-2)
         * 3. Time B: 1 pt  (1E, 1D), SG -3 (1-4)
         */

        $classificacao = $this->service->calcularClassificacaoFase($fase);
        $tabela = $classificacao['A'];

        $this->assertEquals($equipeC->id, $tabela[0]['id']);
        $this->assertEquals(4, $tabela[0]['pontos']);
        
        $this->assertEquals($equipeA->id, $tabela[1]['id']);
        $this->assertEquals(3, $tabela[1]['pontos']);

        $this->assertEquals($equipeB->id, $tabela[2]['id']);
        $this->assertEquals(1, $tabela[2]['pontos']);
    }

    public function test_deve_respeitar_criterio_de_saldo_de_gols()
    {
        $campeonato = Campeonato::factory()->create();
        $fase = Fase::factory()->create(['campeonato_id' => $campeonato->id]);
        $grupo = $fase->grupos()->create(['nome' => 'A', 'ordem' => 1]);

        $equipeA = Equipe::factory()->create(['campeonato_id' => $campeonato->id, 'nome' => 'Time A']);
        $equipeB = Equipe::factory()->create(['campeonato_id' => $campeonato->id, 'nome' => 'Time B']);

        // Ambos ganham de um terceiro time dummy para ter pontos iguais
        $dummy = Equipe::factory()->create(['campeonato_id' => $campeonato->id, 'nome' => 'Dummy']);

        // Time A vence por 5x0 (SG +5)
        Partida::create([
            'prefeitura_id' => $campeonato->prefeitura_id,
            'fase_id' => $fase->id,
            'fase_grupo_id' => $grupo->id,
            'mandante_id' => $equipeA->id,
            'visitante_id' => $dummy->id,
            'placar_mandante' => 5,
            'placar_visitante' => 0,
            'status' => 'finalizada'
        ]);

        // Time B vence por 2x0 (SG +2)
        Partida::create([
            'prefeitura_id' => $campeonato->prefeitura_id,
            'fase_id' => $fase->id,
            'fase_grupo_id' => $grupo->id,
            'mandante_id' => $equipeB->id,
            'visitante_id' => $dummy->id,
            'placar_mandante' => 2,
            'placar_visitante' => 0,
            'status' => 'finalizada'
        ]);

        // Regra explícita: Pontos -> Saldo
        $fase->regrasClassificacao()->create(['criterio' => CriterioClassificacaoFase::PONTOS, 'ordem' => 1]);
        $fase->regrasClassificacao()->create(['criterio' => CriterioClassificacaoFase::SALDO_GOLS, 'ordem' => 2]);

        $classificacao = $this->service->calcularClassificacaoFase($fase);
        $tabela = $classificacao['A'];

        $this->assertEquals($equipeA->id, $tabela[0]['id']); // Time A primeiro por saldo
        $this->assertEquals($equipeB->id, $tabela[1]['id']);
    }

    public function test_deve_calcular_melhores_terceiros_entre_grupos()
    {
        $campeonato = Campeonato::factory()->create();
        $fase = Fase::factory()->create(['campeonato_id' => $campeonato->id]);
        $grupoA = $fase->grupos()->create(['nome' => 'A', 'ordem' => 1]);
        $grupoB = $fase->grupos()->create(['nome' => 'B', 'ordem' => 2]);

        // Setup Grupo A (3 times)
        $a1 = Equipe::factory()->create(['nome' => 'A1']);
        $a2 = Equipe::factory()->create(['nome' => 'A2']);
        $a3 = Equipe::factory()->create(['nome' => 'A3']); // Será o 3º com 1 ponto

        // Simula jogos para deixar A3 em terceiro
        Partida::create(['prefeitura_id' => $campeonato->prefeitura_id, 'fase_id' => $fase->id, 'fase_grupo_id' => $grupoA->id, 'mandante_id' => $a1->id, 'visitante_id' => $a3->id, 'placar_mandante' => 2, 'placar_visitante' => 0, 'status' => 'finalizada']); // A1 vence A3
        Partida::create(['prefeitura_id' => $campeonato->prefeitura_id, 'fase_id' => $fase->id, 'fase_grupo_id' => $grupoA->id, 'mandante_id' => $a2->id, 'visitante_id' => $a3->id, 'placar_mandante' => 0, 'placar_visitante' => 0, 'status' => 'finalizada']); // A2 empata A3

        // Setup Grupo B (3 times)
        $b1 = Equipe::factory()->create(['nome' => 'B1']);
        $b2 = Equipe::factory()->create(['nome' => 'B2']);
        $b3 = Equipe::factory()->create(['nome' => 'B3']); // Será o 3º com 3 pontos

        // Simula jogos para deixar B3 em terceiro (mas com mais pontos que A3)
        Partida::create(['prefeitura_id' => $campeonato->prefeitura_id, 'fase_id' => $fase->id, 'fase_grupo_id' => $grupoB->id, 'mandante_id' => $b1->id, 'visitante_id' => $b3->id, 'placar_mandante' => 1, 'placar_visitante' => 0, 'status' => 'finalizada']); // B1 vence B3
        Partida::create(['prefeitura_id' => $campeonato->prefeitura_id, 'fase_id' => $fase->id, 'fase_grupo_id' => $grupoB->id, 'mandante_id' => $b3->id, 'visitante_id' => $b2->id, 'placar_mandante' => 1, 'placar_visitante' => 0, 'status' => 'finalizada']); // B3 vence B2

        // Recalcular quem é quem nos grupos
        // Grupo A: 
        // A1 (3 pts, venceu A3)
        // A2 (1 pt, empatou A3)
        // A3 (1 pt, perdeu A1, empatou A2). Saldo: -2 (0-2, 0-0).
        // A2 tem 1 pt, 0-0. Saldo 0.
        // Logo A2 é 2º, A3 é 3º.
        
        // Grupo B:
        // B1 (3 pts, venceu B3)
        // B3 (3 pts, perdeu B1, venceu B2). Saldo 0 (0-1, 1-0).
        // B2 (0 pts, perdeu B3). 
        // Ops, B3 venceu B2. B1 venceu B3. Falta jogo B1 x B2 para fechar triangulo ou definir B2 como ultimo.
        // Vamos garantir B3 em terceiro com 3 pts? Não, se B3 tem 3 pts e B2 tem 0, B3 é 2º.
        // Precisamos ajustar para B3 ser terceiro.
        // B1 vence B2, B1 vence B3 => B1 (6)
        // B2 vence B3 => B2 (3)
        // B3 perde tudo => B3 (0)
        // Assim B3 seria terceiro com 0.
        // Quero B3 terceiro com MAIS pontos que A3 (que tem 1).
        
        // Cenário Ajustado:
        // A3 precisa ser terceiro com 1 pt. (Feito: A1 3, A2 2, A3 1)
        // B3 precisa ser terceiro com 2 pts.
        // B1 (4), B2 (3), B3 (2)? Dificil com 2 jogos.
        
        // Vamos fazer B3 ser terceiro com 3 pontos.
        // B1 vence B2, B1 perde B3 (B1 3)
        // B2 vence B3 (B2 3)
        // B3 vence B1 (B3 3)
        // Todo mundo 3 pontos. Desempate saldo.
        // B1 ganha de 2x0 de B2. (Saldo +2, Gols 2)
        // B2 ganha de 1x0 de B3. (Saldo -1, Gols 1)
        // B3 ganha de 1x0 de B1. (Saldo -1, Gols 1)
        // B1: +1
        // B2: -1
        // B3: 0
        // Espera: B1 +1 (1º), B3 0 (2º), B2 -1 (3º).
        
        // Simplificando:
        // A: A1(4), A2(4), A3(0).
        // B: B1(4), B2(4), B3(1).
        // B3 > A3.
        
        // Limpar Partidas Anteriores
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Partida::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        
        // Grupo A: A3 com 0 pontos
        Partida::create(['prefeitura_id' => $campeonato->prefeitura_id, 'fase_id' => $fase->id, 'fase_grupo_id' => $grupoA->id, 'mandante_id' => $a1->id, 'visitante_id' => $a3->id, 'placar_mandante' => 1, 'placar_visitante' => 0, 'status' => 'finalizada']);
        Partida::create(['prefeitura_id' => $campeonato->prefeitura_id, 'fase_id' => $fase->id, 'fase_grupo_id' => $grupoA->id, 'mandante_id' => $a2->id, 'visitante_id' => $a3->id, 'placar_mandante' => 1, 'placar_visitante' => 0, 'status' => 'finalizada']);
        
        // Grupo B: B3 com 1 ponto (empate com B1)
        Partida::create(['prefeitura_id' => $campeonato->prefeitura_id, 'fase_id' => $fase->id, 'fase_grupo_id' => $grupoB->id, 'mandante_id' => $b1->id, 'visitante_id' => $b3->id, 'placar_mandante' => 1, 'placar_visitante' => 1, 'status' => 'finalizada']);
        Partida::create(['prefeitura_id' => $campeonato->prefeitura_id, 'fase_id' => $fase->id, 'fase_grupo_id' => $grupoB->id, 'mandante_id' => $b2->id, 'visitante_id' => $b3->id, 'placar_mandante' => 1, 'placar_visitante' => 0, 'status' => 'finalizada']); // B2 vence B3
        // B3 tem 1 pt. B1 tem 1 pt. B2 tem 3 pts.
        // Precisamos garantir que B3 fique em 3º e B1 em 1º ou 2º.
        // B1 vence B2. => B1 (4), B2 (3), B3 (1).
        Partida::create(['prefeitura_id' => $campeonato->prefeitura_id, 'fase_id' => $fase->id, 'fase_grupo_id' => $grupoB->id, 'mandante_id' => $b1->id, 'visitante_id' => $b2->id, 'placar_mandante' => 1, 'placar_visitante' => 0, 'status' => 'finalizada']);
        
        // Resultado esperado:
        // A3: 0 pontos (3º do A)
        // B3: 1 ponto (3º do B)
        // Melhores terceiros: B3, depois A3.

        $melhoresTerceiros = $this->service->calcularMelhoresXColocados($fase, 3);

        $this->assertCount(2, $melhoresTerceiros);
        $this->assertEquals($b3->id, $melhoresTerceiros[0]['id']); // B3 (1 pt)
        $this->assertEquals($a3->id, $melhoresTerceiros[1]['id']); // A3 (0 pts)
    }
}
