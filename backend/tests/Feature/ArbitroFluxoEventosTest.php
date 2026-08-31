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
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ArbitroFluxoEventosTest extends TestCase
{
    use RefreshDatabase;

    public function test_deve_sincronizar_evento_em_lote_com_idempotencia_por_request_id(): void
    {
        [$tenant, $arbitro, $partida, $mandante, $visitante, $jogadorMandante] = $this->criarCenarioBase();

        Sanctum::actingAs($arbitro);

        $payload = [
            'eventos' => [[
                'jogador_id' => $jogadorMandante->id,
                'time_id' => $mandante->id,
                'request_id' => 'req-idempotente-1',
                'tipo_evento' => 'GOL',
                'minuto' => 7,
            ]],
        ];

        $this->withHeaders($this->tenantHeaders($tenant))
            ->postJson("/api/v1/arbitro/partidas/{$partida->id}/eventos/lote", $payload)
            ->assertOk()
            ->assertJsonFragment(['sincronizados' => ['req-idempotente-1']]);

        $this->withHeaders($this->tenantHeaders($tenant))
            ->postJson("/api/v1/arbitro/partidas/{$partida->id}/eventos/lote", $payload)
            ->assertOk()
            ->assertJsonFragment(['sincronizados' => ['req-idempotente-1']]);

        $this->assertDatabaseCount('eventos_partida', 1);
        $partida->refresh();
        $this->assertEquals(1, $partida->placar_mandante);
        $this->assertEquals(0, $partida->placar_visitante);
    }

    public function test_deve_descartar_evento_invalido_no_lote_e_processar_evento_valido(): void
    {
        [$tenant, $arbitro, $partida, $mandante, $visitante, $jogadorMandante] = $this->criarCenarioBase();

        $jogadorNaoAprovado = Atleta::factory()->create([
            'prefeitura_id' => $tenant->id,
        ]);

        Sanctum::actingAs($arbitro);

        $payload = [
            'eventos' => [
                [
                    'jogador_id' => $jogadorNaoAprovado->id,
                    'time_id' => $mandante->id,
                    'request_id' => 'req-invalido-1',
                    'tipo_evento' => 'CARTAO_AMARELO',
                    'minuto' => 5,
                ],
                [
                    'jogador_id' => $jogadorMandante->id,
                    'time_id' => $mandante->id,
                    'request_id' => 'req-valido-1',
                    'tipo_evento' => 'GOL',
                    'minuto' => 8,
                ],
            ],
        ];

        $this->withHeaders($this->tenantHeaders($tenant))
            ->postJson("/api/v1/arbitro/partidas/{$partida->id}/eventos/lote", $payload)
            ->assertOk()
            ->assertJsonFragment(['sincronizados' => ['req-valido-1']])
            ->assertJsonFragment(['descartados' => ['req-invalido-1']]);

        $this->assertDatabaseCount('eventos_partida', 1);
        $partida->refresh();
        $this->assertEquals(1, $partida->placar_mandante);
    }

    public function test_deve_finalizar_partida_com_assinaturas_e_bloquear_nova_edicao(): void
    {
        [$tenant, $arbitro, $partida] = $this->criarCenarioBase();

        Sanctum::actingAs($arbitro);

        $assinaturas = [
            'arbitro' => 'data:image/png;base64,AAA',
            'mandante' => 'data:image/png;base64,BBB',
            'visitante' => 'data:image/png;base64,CCC',
        ];

        $this->withHeaders($this->tenantHeaders($tenant))
            ->putJson("/api/v1/arbitro/partidas/{$partida->id}/placar", [
                'placar_mandante' => 2,
                'placar_visitante' => 1,
                'finalizar' => true,
                'assinaturas' => $assinaturas,
            ])
            ->assertOk()
            ->assertJsonFragment(['message' => 'Placar atualizado com sucesso.']);

        $this->assertDatabaseHas('partidas', [
            'id' => $partida->id,
            'placar_mandante' => 2,
            'placar_visitante' => 1,
            'status' => 'finalizada',
            'placar_bloqueado' => 1,
            'assinatura_arbitro' => $assinaturas['arbitro'],
            'assinatura_mandante' => $assinaturas['mandante'],
            'assinatura_visitante' => $assinaturas['visitante'],
        ]);

        $this->withHeaders($this->tenantHeaders($tenant))
            ->putJson("/api/v1/arbitro/partidas/{$partida->id}/placar", [
                'placar_mandante' => 3,
                'placar_visitante' => 2,
                'finalizar' => false,
            ])
            ->assertStatus(400)
            ->assertJsonFragment(['error' => 'Partida já finalizada.']);
    }

    public function test_deve_bloquear_evento_para_atleta_suspenso_na_proxima_partida(): void
    {
        [$tenant, $arbitro, $partida, $mandante, $visitante, $jogadorMandante] = $this->criarCenarioBase();

        $partidaAnterior = Partida::create([
            'prefeitura_id' => $tenant->id,
            'fase_id' => $partida->fase_id,
            'mandante_id' => $mandante->id,
            'visitante_id' => $visitante->id,
            'status' => 'finalizada',
            'data_jogo' => '2026-03-10 10:00:00',
            'placar_mandante' => 1,
            'placar_visitante' => 1,
            'arbitro_id' => $arbitro->id,
            'placar_bloqueado' => true,
        ]);

        $partida->update([
            'data_jogo' => '2026-03-11 10:00:00',
        ]);

        EventoPartida::create([
            'partida_id' => $partidaAnterior->id,
            'jogador_id' => $jogadorMandante->id,
            'time_id' => $mandante->id,
            'tipo_evento' => 'CARTAO_VERMELHO',
            'minuto' => 65,
        ]);

        Sanctum::actingAs($arbitro);

        $this->withHeaders($this->tenantHeaders($tenant))
            ->postJson("/api/v1/arbitro/partidas/{$partida->id}/eventos", [
                'jogador_id' => $jogadorMandante->id,
                'time_id' => $mandante->id,
                'request_id' => 'req-suspenso-1',
                'tipo_evento' => 'GOL',
                'minuto' => 5,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('jogador_id');
    }

    public function test_deve_salvar_escalacao_e_bloquear_atleta_nao_escalado(): void
    {
        [$tenant, $arbitro, $partida, $mandante, $visitante, $jogadorMandante, $jogadorVisitante] = $this->criarCenarioBase();
        $reservaMandante = Atleta::factory()->create(['prefeitura_id' => $tenant->id]);
        $mandante->atletas()->attach($reservaMandante->id, ['status' => 'aprovado']);

        Sanctum::actingAs($arbitro);

        $this->withHeaders($this->tenantHeaders($tenant))
            ->putJson("/api/v1/arbitro/partidas/{$partida->id}/escalacao", [
                'time_id' => $mandante->id,
                'atletas' => [
                    ['atleta_id' => $jogadorMandante->id, 'titular' => true, 'capitao' => true],
                ],
            ])
            ->assertOk()
            ->assertJsonFragment(['message' => 'Escalação salva com sucesso.']);

        $this->withHeaders($this->tenantHeaders($tenant))
            ->putJson("/api/v1/arbitro/partidas/{$partida->id}/escalacao", [
                'time_id' => $visitante->id,
                'atletas' => [
                    ['atleta_id' => $jogadorVisitante->id, 'titular' => true, 'capitao' => true],
                ],
            ])
            ->assertOk();

        $this->withHeaders($this->tenantHeaders($tenant))
            ->postJson("/api/v1/arbitro/partidas/{$partida->id}/eventos", [
                'jogador_id' => $reservaMandante->id,
                'time_id' => $mandante->id,
                'request_id' => 'req-nao-escalado',
                'tipo_evento' => 'GOL',
                'minuto' => 10,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('jogador_id');
    }

    private function criarCenarioBase(): array
    {
        $tenant = Prefeitura::factory()->create([
            'slug' => 'tenant-arbitro-test',
        ]);

        App::instance('current_tenant', $tenant);

        $arbitro = User::factory()->create([
            'prefeitura_id' => $tenant->id,
            'perfil' => 'ARBITRO',
            'status' => 'ATIVO',
        ]);

        $modalidade = Modalidade::factory()->create([
            'prefeitura_id' => $tenant->id,
            'tipo' => 'coletivo',
        ]);

        $campeonato = Campeonato::factory()->create([
            'prefeitura_id' => $tenant->id,
            'modalidade_id' => $modalidade->id,
            'tipo_competicao' => 'equipe',
            'status' => 'em_andamento',
        ]);

        $fase = Fase::factory()->create([
            'campeonato_id' => $campeonato->id,
            'tipo' => 'grupos',
            'status' => 'ativa',
        ]);

        $mandante = Equipe::factory()->create([
            'prefeitura_id' => $tenant->id,
            'campeonato_id' => $campeonato->id,
            'nome' => 'Mandante Teste',
        ]);

        $visitante = Equipe::factory()->create([
            'prefeitura_id' => $tenant->id,
            'campeonato_id' => $campeonato->id,
            'nome' => 'Visitante Teste',
        ]);

        $jogadorMandante = Atleta::factory()->create(['prefeitura_id' => $tenant->id]);
        $jogadorVisitante = Atleta::factory()->create(['prefeitura_id' => $tenant->id]);

        $mandante->atletas()->attach($jogadorMandante->id, ['status' => 'aprovado']);
        $visitante->atletas()->attach($jogadorVisitante->id, ['status' => 'aprovado']);

        $partida = Partida::create([
            'prefeitura_id' => $tenant->id,
            'fase_id' => $fase->id,
            'mandante_id' => $mandante->id,
            'visitante_id' => $visitante->id,
            'status' => 'agendada',
            'placar_mandante' => 0,
            'placar_visitante' => 0,
            'arbitro_id' => $arbitro->id,
            'placar_bloqueado' => false,
        ]);

        return [$tenant, $arbitro, $partida, $mandante, $visitante, $jogadorMandante, $jogadorVisitante];
    }

    private function tenantHeaders(Prefeitura $tenant): array
    {
        return [
            'X-Tenant-Slug' => $tenant->slug,
            'Accept' => 'application/json',
        ];
    }
}
