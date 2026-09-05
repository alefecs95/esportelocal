<?php

namespace Tests\Feature;

use App\Models\MemoriaAcervo;
use App\Models\MemoriaEquipe;
use App\Models\MemoriaMarco;
use App\Models\Prefeitura;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MemoriaEsportivaTest extends TestCase
{
    use RefreshDatabase;

    public function test_timeline_publica_omite_rascunhos(): void
    {
        $tenant = Prefeitura::factory()->create(['slug' => 'memoria-cidade']);
        App::instance('current_tenant', $tenant);

        MemoriaMarco::factory()->create([
            'titulo' => 'Título de 1998',
            'publicado' => true,
            'ano' => 1998,
        ]);
        MemoriaMarco::factory()->create([
            'titulo' => 'Rascunho',
            'publicado' => false,
            'ano' => 2001,
        ]);

        App::forgetInstance('current_tenant');

        $this->withHeaders($this->tenantHeaders($tenant))
            ->getJson('/api/v1/public/memoria/timeline')
            ->assertOk()
            ->assertJsonFragment(['titulo' => 'Título de 1998'])
            ->assertJsonMissing(['titulo' => 'Rascunho']);
    }

    public function test_contribuicao_da_comunidade_fica_pendente(): void
    {
        Storage::fake('public');
        $tenant = Prefeitura::factory()->create(['slug' => 'memoria-doacao']);
        App::instance('current_tenant', $tenant);
        App::forgetInstance('current_tenant');

        $file = UploadedFile::fake()->image('album.jpg');

        $this->withHeaders($this->tenantHeaders($tenant))
            ->post('/api/v1/public/memoria/contribuicoes', [
                'titulo' => 'Foto do ginásio antigo',
                'doador_nome' => 'Maria Silva',
                'doador_email' => 'maria@example.com',
                'arquivo' => $file,
            ])
            ->assertCreated();

        $this->assertDatabaseHas('memoria_acervo', [
            'titulo' => 'Foto do ginásio antigo',
            'status' => 'pendente',
            'publicado' => 0,
            'origem' => 'comunidade',
        ]);
    }

    public function test_admin_aprova_contribuicao(): void
    {
        $tenant = Prefeitura::factory()->create(['slug' => 'memoria-admin']);
        App::instance('current_tenant', $tenant);

        $admin = User::factory()->create([
            'perfil' => 'ADMIN',
            'prefeitura_id' => $tenant->id,
        ]);
        $item = MemoriaAcervo::factory()->create([
            'status' => 'pendente',
            'publicado' => false,
            'origem' => 'comunidade',
            'doador_nome' => 'João',
        ]);

        Sanctum::actingAs($admin);

        $this->withHeaders($this->tenantHeaders($tenant))
            ->postJson("/api/v1/memoria/contribuicoes/{$item->id}/aprovar")
            ->assertOk()
            ->assertJsonFragment(['status' => 'aprovado']);

        $this->assertDatabaseHas('memoria_acervo', [
            'id' => $item->id,
            'status' => 'aprovado',
            'publicado' => 1,
        ]);
    }

    public function test_ficha_publica_do_acervo_omite_pendente(): void
    {
        $tenant = Prefeitura::factory()->create(['slug' => 'memoria-peca']);
        App::instance('current_tenant', $tenant);

        $publicado = MemoriaAcervo::factory()->create([
            'titulo' => 'Final de 1998',
            'status' => 'aprovado',
            'publicado' => true,
            'descricao' => 'Time campeão no ginásio.',
        ]);
        MemoriaAcervo::factory()->create([
            'titulo' => 'Ainda não publicado',
            'status' => 'pendente',
            'publicado' => false,
        ]);

        App::forgetInstance('current_tenant');

        $this->withHeaders($this->tenantHeaders($tenant))
            ->getJson('/api/v1/public/memoria/acervo/' . $publicado->slug)
            ->assertOk()
            ->assertJsonFragment(['titulo' => 'Final de 1998'])
            ->assertJsonMissing(['doador_email']);

        $this->assertGreaterThan(0, $publicado->fresh()->visualizacoes);

        $this->withHeaders($this->tenantHeaders($tenant))
            ->getJson('/api/v1/public/memoria/acervo/ainda-nao-publicado')
            ->assertNotFound();
    }

    public function test_equipe_historica_publica_omite_rascunho(): void
    {
        $tenant = Prefeitura::factory()->create(['slug' => 'memoria-clube']);
        App::instance('current_tenant', $tenant);

        MemoriaEquipe::create([
            'nome' => 'Comercial FC',
            'publicado' => true,
        ]);
        MemoriaEquipe::create([
            'nome' => 'Rascunho interno',
            'publicado' => false,
        ]);

        App::forgetInstance('current_tenant');

        $this->withHeaders($this->tenantHeaders($tenant))
            ->getJson('/api/v1/public/memoria/equipes')
            ->assertOk()
            ->assertJsonFragment(['nome' => 'Comercial FC'])
            ->assertJsonMissing(['nome' => 'Rascunho interno']);
    }

    public function test_gestor_nao_admin_nao_curadoria(): void
    {
        $tenant = Prefeitura::factory()->create(['slug' => 'memoria-negado']);
        App::instance('current_tenant', $tenant);
        $user = User::factory()->create([
            'perfil' => 'RESPONSAVEL',
            'prefeitura_id' => $tenant->id,
        ]);

        Sanctum::actingAs($user);

        $this->withHeaders($this->tenantHeaders($tenant))
            ->getJson('/api/v1/memoria/dashboard')
            ->assertForbidden();
    }

    private function tenantHeaders(Prefeitura $tenant): array
    {
        return [
            'X-Tenant-Slug' => $tenant->slug,
            'Accept' => 'application/json',
        ];
    }
}
