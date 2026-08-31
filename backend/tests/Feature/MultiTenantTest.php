<?php

namespace Tests\Feature;

use App\Models\Atleta;
use App\Models\Prefeitura;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Tests\TestCase;

class MultiTenantTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_isolation_via_models()
    {
        // 1. Criar dois tenants
        $tenantA = Prefeitura::factory()->create(['slug' => 'cidade-a']);
        $tenantB = Prefeitura::factory()->create(['slug' => 'cidade-b']);

        // 2. Criar dados no Tenant A
        // Simulamos que o middleware identificou o Tenant A
        App::instance('current_tenant', $tenantA);
        
        // Criando usuário (deve pegar prefeitura_id automaticamente do App::get('current_tenant'))
        $userA = User::factory()->create(['email' => 'user@cidade-a.com']);
        $atletaA = Atleta::factory()->create(['nome' => 'Atleta A']);
        
        $this->assertEquals($tenantA->id, $userA->prefeitura_id, 'User A should belong to Tenant A');
        $this->assertEquals($tenantA->id, $atletaA->prefeitura_id, 'Atleta A should belong to Tenant A');

        // 3. Criar dados no Tenant B
        App::instance('current_tenant', $tenantB);
        
        $userB = User::factory()->create(['email' => 'user@cidade-b.com']);
        $atletaB = Atleta::factory()->create(['nome' => 'Atleta B']);

        $this->assertEquals($tenantB->id, $userB->prefeitura_id, 'User B should belong to Tenant B');
        $this->assertEquals($tenantB->id, $atletaB->prefeitura_id, 'Atleta B should belong to Tenant B');

        // 4. Testar Isolamento de Leitura
        
        // Contexto: Tenant A
        App::instance('current_tenant', $tenantA);
        
        // Deve ver apenas Atleta A
        $atletasVisiveis = Atleta::all();
        $this->assertTrue($atletasVisiveis->contains('id', $atletaA->id), 'Should see Atleta A');
        $this->assertFalse($atletasVisiveis->contains('id', $atletaB->id), 'Should NOT see Atleta B');
        $this->assertCount(1, $atletasVisiveis);

        // Contexto: Tenant B
        App::instance('current_tenant', $tenantB);
        
        // Deve ver apenas Atleta B
        $atletasVisiveis = Atleta::all();
        $this->assertTrue($atletasVisiveis->contains('id', $atletaB->id), 'Should see Atleta B');
        $this->assertFalse($atletasVisiveis->contains('id', $atletaA->id), 'Should NOT see Atleta A');
        $this->assertCount(1, $atletasVisiveis);
    }

    public function test_api_tenant_resolution_and_isolation()
    {
        $tenant = Prefeitura::factory()->create(['slug' => 'teste-api']);
        $outroTenant = Prefeitura::factory()->create(['slug' => 'outro-api']);

        // Criar atleta no tenant correto
        App::instance('current_tenant', $tenant);
        $atleta = Atleta::factory()->create(['nome' => 'Atleta do Teste']);
        
        // Limpar instância para testar via API real
        App::forgetInstance('current_tenant');

        // 1. Testar resolução de tenant via header X-Tenant-Slug
        $response = $this->withHeaders([
            'X-Tenant-Slug' => 'teste-api',
            'Accept' => 'application/json'
        ])->getJson('/api/v1/atletas');
        
        // Como não estamos autenticados, deve dar 401, mas isso prova que passou pelo middleware de tenant?
        // O middleware de tenant roda antes da auth.
        // Se a rota for protegida, vai dar 401.
        // Se a rota for pública, vai dar 200.
        // Atletas Resource é protegida? Vamos assumir que sim ou verificar routes/api.php
        // Route::apiResource('atletas', AtletaController::class); está fora do auth:sanctum group?
        // Verificando api.php...
        // Route::prefix('v1')->group(function () { ... Route::apiResource('atletas', ...); ... });
        // E auth:sanctum é aplicado onde?
        // Route::middleware('auth:sanctum')->get('/user'...
        // Parece que as rotas apiResource NÃO estão dentro de auth:sanctum explicitamente no arquivo que li.
        // Elas estão no grupo v1, mas sem middleware de auth.
        // Se for assim, é publico.
        
        // Se for público:
        $response->assertStatus(200);
        $response->assertJsonFragment(['nome' => 'Atleta do Teste']);

        // 2. Testar acesso com outro tenant (não deve ver o atleta)
        $response = $this->withHeaders([
            'X-Tenant-Slug' => 'outro-api',
            'Accept' => 'application/json'
        ])->getJson('/api/v1/atletas');

        $response->assertStatus(200);
        $response->assertJsonMissing(['nome' => 'Atleta do Teste']);
        
        // 3. Testar tenant inválido
        $response = $this->withHeaders([
            'X-Tenant-Slug' => 'nao-existe',
            'Accept' => 'application/json'
        ])->getJson('/api/v1/atletas');
        
        // Middleware retorna 404 se não achar tenant
        $response->assertStatus(404);
    }
}
