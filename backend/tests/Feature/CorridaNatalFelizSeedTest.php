<?php

namespace Tests\Feature;

use App\Models\Campeonato;
use App\Models\Prefeitura;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CorridaNatalFelizSeedTest extends TestCase
{
    use RefreshDatabase;

    public function test_seed_cria_corrida_e_e_idempotente(): void
    {
        $prefeitura = Prefeitura::factory()->create(['slug' => 'seed-natal-test']);

        $this->artisan('corrida:natal-feliz', ['prefeitura_id' => $prefeitura->id])->assertSuccessful();
        $this->artisan('corrida:natal-feliz', ['prefeitura_id' => $prefeitura->id])->assertSuccessful();

        $campeonatos = Campeonato::withoutGlobalScopes()
            ->where('prefeitura_id', $prefeitura->id)
            ->where('nome', 'Corrida Solidária Natal Feliz')
            ->get();

        $this->assertCount(1, $campeonatos);

        $campeonato = $campeonatos->first();
        $this->assertTrue((bool) $campeonato->inscricao_solidaria);
        $this->assertTrue((bool) $campeonato->exige_termo_menor);
        $this->assertSame('simplificado', $campeonato->modo_cadastro);
        $this->assertSame('inscricoes_abertas', $campeonato->status);
        $this->assertNull($campeonato->kit_retirada_info);

        $this->assertSame(6, $campeonato->categorias()->withoutGlobalScopes()->count());
        // Somente 5km e 3km (M/F) são competitivas de pódio; Kids e Teens são não competitivas.
        $this->assertSame(
            4,
            $campeonato->categorias()->withoutGlobalScopes()->where('competitiva', true)->count()
        );
    }

    public function test_seed_com_prefeitura_inexistente_falha_sem_criar_nada(): void
    {
        $this->artisan('corrida:natal-feliz', ['prefeitura_id' => 999999])->assertFailed();

        $this->assertSame(0, Campeonato::withoutGlobalScopes()->count());
    }
}
