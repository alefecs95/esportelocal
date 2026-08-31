<?php

namespace Tests\Feature;

use App\Models\Atleta;
use App\Models\Campeonato;
use App\Models\Equipe;
use App\Models\EventoPartida;
use App\Models\Fase;
use App\Models\Modalidade;
use App\Models\Partida;
use App\Models\Prefeitura;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Tests\TestCase;

class RegrasCartaoCampeonatoTest extends TestCase
{
    use RefreshDatabase;

    public function test_deve_respeitar_configuracao_de_suspensao_automatica_desativada(): void
    {
        [$tenant, $campeonato, $equipe, $jogador] = $this->criarCenarioCompeticao([
            'suspensao_automatica_ativa' => false,
            'cartoes_amarelos_para_suspensao' => 2,
            'cartao_vermelho_suspende' => true,
        ]);

        $fase = $campeonato->fases()->first();
        $partida1 = $this->criarPartida($tenant, $fase->id, $equipe->id, '2026-03-10 10:00:00');
        $partida2 = $this->criarPartida($tenant, $fase->id, $equipe->id, '2026-03-11 10:00:00');

        EventoPartida::create([
            'partida_id' => $partida1->id,
            'jogador_id' => $jogador->id,
            'time_id' => $equipe->id,
            'tipo_evento' => 'CARTAO_AMARELO',
            'minuto' => 12,
        ]);

        EventoPartida::create([
            'partida_id' => $partida2->id,
            'jogador_id' => $jogador->id,
            'time_id' => $equipe->id,
            'tipo_evento' => 'CARTAO_AMARELO',
            'minuto' => 38,
        ]);

        $response = $this->withHeaders($this->tenantHeaders($tenant))
            ->getJson("/api/v1/public/campeonatos/{$campeonato->id}/cartoes")
            ->assertOk();

        $jogadorCartoes = collect($response->json())->firstWhere('id', $jogador->id);
        $this->assertNotNull($jogadorCartoes);
        $this->assertFalse($jogadorCartoes['suspenso']);
        $this->assertFalse($jogadorCartoes['suspensao_automatica_ativa']);
    }

    public function test_cartoes_ficam_isolados_por_competicao(): void
    {
        [$tenant, $campeonatoA, $equipeA, $jogador] = $this->criarCenarioCompeticao();
        [, $campeonatoB] = $this->criarCenarioCompeticao([], $tenant, $jogador);

        $faseA = $campeonatoA->fases()->first();
        $partidaA1 = $this->criarPartida($tenant, $faseA->id, $equipeA->id, '2026-03-12 10:00:00');
        $partidaA2 = $this->criarPartida($tenant, $faseA->id, $equipeA->id, '2026-03-13 10:00:00');

        EventoPartida::create([
            'partida_id' => $partidaA1->id,
            'jogador_id' => $jogador->id,
            'time_id' => $equipeA->id,
            'tipo_evento' => 'CARTAO_AMARELO',
            'minuto' => 11,
        ]);

        EventoPartida::create([
            'partida_id' => $partidaA2->id,
            'jogador_id' => $jogador->id,
            'time_id' => $equipeA->id,
            'tipo_evento' => 'CARTAO_AMARELO',
            'minuto' => 44,
        ]);

        $responseA = $this->withHeaders($this->tenantHeaders($tenant))
            ->getJson("/api/v1/public/campeonatos/{$campeonatoA->id}/cartoes")
            ->assertOk();
        $jogadorA = collect($responseA->json())->firstWhere('id', $jogador->id);
        $this->assertTrue($jogadorA['suspenso']);

        $responseB = $this->withHeaders($this->tenantHeaders($tenant))
            ->getJson("/api/v1/public/campeonatos/{$campeonatoB->id}/cartoes")
            ->assertOk();
        $jogadorB = collect($responseB->json())->firstWhere('id', $jogador->id);
        $this->assertNull($jogadorB);
    }

    private function criarCenarioCompeticao(array $config = [], ?Prefeitura $tenant = null, ?Atleta $jogador = null): array
    {
        $tenant = $tenant ?: Prefeitura::factory()->create(['slug' => 'tenant-cartao-test']);
        App::instance('current_tenant', $tenant);

        $modalidade = Modalidade::factory()->create([
            'prefeitura_id' => $tenant->id,
            'tipo' => 'coletivo',
        ]);

        $campeonato = Campeonato::factory()->create(array_merge([
            'prefeitura_id' => $tenant->id,
            'modalidade_id' => $modalidade->id,
            'tipo_competicao' => 'equipe',
            'status' => 'em_andamento',
            'suspensao_automatica_ativa' => true,
            'cartoes_amarelos_para_suspensao' => 2,
            'cartao_vermelho_suspende' => true,
        ], $config));

        $fase = Fase::factory()->create([
            'campeonato_id' => $campeonato->id,
            'tipo' => 'grupos',
            'status' => 'ativa',
        ]);

        $equipe = Equipe::factory()->create([
            'prefeitura_id' => $tenant->id,
            'campeonato_id' => $campeonato->id,
        ]);

        $jogador = $jogador ?: Atleta::factory()->create(['prefeitura_id' => $tenant->id]);
        $equipe->atletas()->attach($jogador->id, ['status' => 'aprovado']);

        return [$tenant, $campeonato, $equipe, $jogador, $fase];
    }

    private function criarPartida(Prefeitura $tenant, int $faseId, int $equipeId, string $dataJogo): Partida
    {
        $visitante = Equipe::factory()->create([
            'prefeitura_id' => $tenant->id,
            'campeonato_id' => Fase::findOrFail($faseId)->campeonato_id,
        ]);

        return Partida::create([
            'prefeitura_id' => $tenant->id,
            'fase_id' => $faseId,
            'mandante_id' => $equipeId,
            'visitante_id' => $visitante->id,
            'status' => 'finalizada',
            'data_jogo' => $dataJogo,
            'placar_mandante' => 0,
            'placar_visitante' => 0,
            'placar_bloqueado' => true,
        ]);
    }

    private function tenantHeaders(Prefeitura $tenant): array
    {
        return [
            'X-Tenant-Slug' => $tenant->slug,
            'Accept' => 'application/json',
        ];
    }
}
