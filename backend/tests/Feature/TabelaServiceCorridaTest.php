<?php

namespace Tests\Feature;

use App\Enums\FormatoCampeonato;
use App\Models\Atleta;
use App\Models\Campeonato;
use App\Models\Inscricao;
use App\Models\Modalidade;
use App\Models\Prefeitura;
use App\Services\TabelaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Tests\TestCase;

class TabelaServiceCorridaTest extends TestCase
{
    use RefreshDatabase;

    private Prefeitura $tenant;
    private Campeonato $campeonato;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Prefeitura::factory()->create(['slug' => 'tabela-corrida-test']);
        App::instance('current_tenant', $this->tenant);

        $modalidade = Modalidade::factory()->create([
            'prefeitura_id' => $this->tenant->id,
            'tipo' => 'individual',
        ]);

        $this->campeonato = Campeonato::factory()->create([
            'prefeitura_id' => $this->tenant->id,
            'modalidade_id' => $modalidade->id,
            'tipo_competicao' => 'individual',
            'formato' => FormatoCampeonato::CORRIDA,
            'status' => 'em_andamento',
        ]);
    }

    public function test_calcula_colocacao_categoria_sem_alterar_colocacao_geral(): void
    {
        $cat5 = $this->campeonato->categorias()->create([
            'prefeitura_id' => $this->tenant->id,
            'nome' => '5 km Masculino',
            'genero' => 'M',
            'competitiva' => true,
        ]);
        $cat3 = $this->campeonato->categorias()->create([
            'prefeitura_id' => $this->tenant->id,
            'nome' => '3 km Masculino',
            'genero' => 'M',
            'competitiva' => true,
        ]);

        $a1 = Atleta::factory()->create(['prefeitura_id' => $this->tenant->id]);
        $a2 = Atleta::factory()->create(['prefeitura_id' => $this->tenant->id]);
        $a3 = Atleta::factory()->create(['prefeitura_id' => $this->tenant->id]);

        $i1 = Inscricao::create([
            'prefeitura_id' => $this->tenant->id,
            'atleta_id' => $a1->id,
            'campeonato_id' => $this->campeonato->id,
            'campeonato_categoria_id' => $cat3->id,
            'status' => 'confirmada',
            'kit_retirado_em' => now(),
        ]);
        $i2 = Inscricao::create([
            'prefeitura_id' => $this->tenant->id,
            'atleta_id' => $a2->id,
            'campeonato_id' => $this->campeonato->id,
            'campeonato_categoria_id' => $cat5->id,
            'status' => 'confirmada',
            'kit_retirado_em' => now(),
        ]);
        $i3 = Inscricao::create([
            'prefeitura_id' => $this->tenant->id,
            'atleta_id' => $a3->id,
            'campeonato_id' => $this->campeonato->id,
            'campeonato_categoria_id' => $cat5->id,
            'status' => 'confirmada',
            'kit_retirado_em' => now(),
        ]);

        app(TabelaService::class)->salvarResultadosCorrida($this->campeonato, [
            ['inscricao_id' => $i1->id, 'tempo' => '00:12:00'],
            ['inscricao_id' => $i2->id, 'tempo' => '00:18:00'],
            ['inscricao_id' => $i3->id, 'tempo' => '00:20:00'],
        ]);

        $i1->refresh();
        $i2->refresh();
        $i3->refresh();

        // Ranking geral intacto
        $this->assertSame(1, $i1->colocacao);
        $this->assertSame(2, $i2->colocacao);
        $this->assertSame(3, $i3->colocacao);

        // Ranking por categoria
        $this->assertSame(1, $i1->colocacao_categoria);
        $this->assertSame(1, $i2->colocacao_categoria);
        $this->assertSame(2, $i3->colocacao_categoria);
    }

    public function test_finalizou_em_so_e_marcado_quando_kit_retirado_e_tempo_registrado(): void
    {
        $cat = $this->campeonato->categorias()->create([
            'prefeitura_id' => $this->tenant->id,
            'nome' => '5 km Masculino',
            'genero' => 'M',
            'competitiva' => true,
        ]);

        $comKit = Inscricao::create([
            'prefeitura_id' => $this->tenant->id,
            'atleta_id' => Atleta::factory()->create(['prefeitura_id' => $this->tenant->id])->id,
            'campeonato_id' => $this->campeonato->id,
            'campeonato_categoria_id' => $cat->id,
            'status' => 'confirmada',
            'kit_retirado_em' => now(),
        ]);

        $semKit = Inscricao::create([
            'prefeitura_id' => $this->tenant->id,
            'atleta_id' => Atleta::factory()->create(['prefeitura_id' => $this->tenant->id])->id,
            'campeonato_id' => $this->campeonato->id,
            'campeonato_categoria_id' => $cat->id,
            'status' => 'confirmada',
            'kit_retirado_em' => null,
        ]);

        app(TabelaService::class)->salvarResultadosCorrida($this->campeonato, [
            ['inscricao_id' => $comKit->id, 'tempo' => '00:19:30'],
            ['inscricao_id' => $semKit->id, 'tempo' => '00:21:00'],
        ]);

        $this->assertNotNull($comKit->fresh()->finalizou_em);
        $this->assertNull($semKit->fresh()->finalizou_em);
    }
}
