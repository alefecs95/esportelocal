<?php

namespace Tests\Feature;

use App\Models\Atleta;
use App\Models\Campeonato;
use App\Models\Inscricao;
use App\Models\Modalidade;
use App\Models\Prefeitura;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CorridaSolidariaInscricaoTest extends TestCase
{
    use RefreshDatabase;

    private Prefeitura $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Prefeitura::factory()->create(['slug' => 'corrida-natal-test']);
        App::instance('current_tenant', $this->tenant);
    }

    private function headers(): array
    {
        return [
            'X-Tenant-Slug' => $this->tenant->slug,
            'Accept' => 'application/json',
        ];
    }

    private function criarCampeonato(array $overrides = []): Campeonato
    {
        $modalidade = Modalidade::factory()->create([
            'prefeitura_id' => $this->tenant->id,
            'tipo' => 'individual',
        ]);

        return Campeonato::factory()->create(array_merge([
            'prefeitura_id' => $this->tenant->id,
            'modalidade_id' => $modalidade->id,
            'tipo_competicao' => 'individual',
            'status' => 'inscricoes_abertas',
            'permite_inscricao_online' => true,
            'inicio_inscricao' => now()->subDay()->toDateString(),
            'fim_inscricao' => now()->addDays(10)->toDateString(),
            'data_inicio' => '2026-12-19',
            'data_fim' => '2026-12-19',
            'valor_inscricao' => 30,
            'modo_cadastro' => 'simplificado',
            'inscricao_solidaria' => true,
            'exige_termo_menor' => true,
        ], $overrides));
    }

    private function criarCategorias(Campeonato $campeonato): array
    {
        App::instance('current_tenant', $this->tenant);

        $m5 = $campeonato->categorias()->create([
            'prefeitura_id' => $this->tenant->id,
            'nome' => '5 km Masculino',
            'genero' => 'M',
            'distancia' => '5km',
            'valor_inscricao' => 30,
            'contribuicao_alimento_kg' => 4,
            'aceita_brinquedo' => true,
            'competitiva' => true,
        ]);

        $m3 = $campeonato->categorias()->create([
            'prefeitura_id' => $this->tenant->id,
            'nome' => '3 km Masculino',
            'genero' => 'M',
            'distancia' => '3km',
            'valor_inscricao' => 30,
            'contribuicao_alimento_kg' => 4,
            'aceita_brinquedo' => true,
            'competitiva' => true,
        ]);

        $kids = $campeonato->categorias()->create([
            'prefeitura_id' => $this->tenant->id,
            'nome' => 'Kids',
            'genero' => 'Misto',
            'idade_max' => 12,
            'valor_inscricao' => 0,
            'contribuicao_alimento_kg' => 1,
            'aceita_brinquedo' => true,
            'competitiva' => false,
        ]);

        return compact('m5', 'm3', 'kids');
    }

    private function payloadAdulto(array $extra = []): array
    {
        return array_merge([
            'nome' => 'Adulto Simplificado',
            'data_nascimento' => '2000-05-10',
            'sexo' => 'M',
            'cpf' => '52998224725',
            'telefone' => '(99) 99999-0001',
            'aceitou_regulamento' => true,
            'aceitou_termos_imagem' => true,
        ], $extra);
    }

    private function adminHeaders(): void
    {
        $admin = User::factory()->create([
            'perfil' => 'ADMIN',
            'prefeitura_id' => $this->tenant->id,
        ]);
        Sanctum::actingAs($admin);
    }

    public function test_campos_cadastro_publico_retorna_modo_do_campeonato(): void
    {
        $campeonato = $this->criarCampeonato();

        $this->withHeaders($this->headers())
            ->getJson("/api/v1/public/campeonatos/{$campeonato->id}/campos-cadastro")
            ->assertOk()
            ->assertJsonPath('data.modo_cadastro', 'simplificado');
    }

    public function test_cadastro_simplificado_de_adulto_sem_titulo_mae_ou_endereco(): void
    {
        $campeonato = $this->criarCampeonato();
        $categorias = $this->criarCategorias($campeonato);

        $this->withHeaders($this->headers())
            ->postJson("/api/v1/public/campeonatos/{$campeonato->id}/inscricoes", $this->payloadAdulto([
                'campeonato_categoria_id' => $categorias['m5']->id,
                'forma_contribuicao' => 'alimento',
            ]))
            ->assertCreated()
            ->assertJsonPath('data.status', 'pendente');

        $this->assertDatabaseHas('atletas', [
            'nome' => 'Adulto Simplificado',
            'cpf' => '52998224725',
            'prefeitura_id' => $this->tenant->id,
        ]);
    }

    public function test_cadastro_simplificado_de_crianca_sem_cpf_exige_responsavel(): void
    {
        $campeonato = $this->criarCampeonato();
        $categorias = $this->criarCategorias($campeonato);

        $payloadCrianca = [
            'nome' => 'Criança Teste',
            'data_nascimento' => '2014-03-01',
            'sexo' => 'F',
            'telefone' => '(99) 99999-0002',
            'campeonato_categoria_id' => $categorias['kids']->id,
            'forma_contribuicao' => 'brinquedo',
            'aceitou_regulamento' => true,
            'aceitou_termos_imagem' => true,
        ];

        // Sem responsável: deve falhar
        $this->withHeaders($this->headers())
            ->postJson("/api/v1/public/campeonatos/{$campeonato->id}/inscricoes", $payloadCrianca)
            ->assertStatus(422);

        // Com responsável: deve criar sem CPF do atleta
        $this->withHeaders($this->headers())
            ->postJson("/api/v1/public/campeonatos/{$campeonato->id}/inscricoes", array_merge($payloadCrianca, [
                'responsavel_nome' => 'Responsável Teste',
                'responsavel_telefone' => '(99) 99999-0003',
            ]))
            ->assertCreated();

        $atleta = Atleta::where('nome', 'Criança Teste')->firstOrFail();
        $this->assertNull($atleta->cpf);
        $this->assertSame('Responsável Teste', $atleta->responsavel_nome);
    }

    public function test_inscricao_solidaria_exige_escolha_quando_ha_mais_de_uma_categoria(): void
    {
        $campeonato = $this->criarCampeonato();
        $this->criarCategorias($campeonato);

        $this->withHeaders($this->headers())
            ->postJson("/api/v1/public/campeonatos/{$campeonato->id}/inscricoes", $this->payloadAdulto([
                'forma_contribuicao' => 'alimento',
            ]))
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_confirmar_contribuicao_ativa_inscricao_e_gera_numero_de_peito_sequencial(): void
    {
        $campeonato = $this->criarCampeonato();
        $categorias = $this->criarCategorias($campeonato);
        $this->adminHeaders();

        $inscricoes = [];
        foreach (['Atleta Um' => '52998224725', 'Atleta Dois' => '11144477735'] as $nome => $cpf) {
            $atleta = Atleta::factory()->create([
                'prefeitura_id' => $this->tenant->id,
                'nome' => $nome,
                'cpf' => $cpf,
                'sexo' => 'M',
                'data_nascimento' => '2000-01-01',
                'pode_participar_competicoes' => true,
                'acesso_ativo' => true,
            ]);

            $inscricoes[] = Inscricao::create([
                'prefeitura_id' => $this->tenant->id,
                'atleta_id' => $atleta->id,
                'campeonato_id' => $campeonato->id,
                'campeonato_categoria_id' => $categorias['m5']->id,
                'status' => 'pendente',
                'status_pagamento' => 'nao_pago',
                'forma_contribuicao' => 'alimento',
            ]);
        }

        // Antes da confirmação, a inscrição solidária não está confirmada e não tem número de peito
        $this->assertDatabaseHas('inscricoes', [
            'id' => $inscricoes[0]->id,
            'status' => 'pendente',
            'numero_peito' => null,
        ]);

        $numeros = [];
        foreach ($inscricoes as $inscricao) {
            $response = $this->withHeaders($this->headers())
                ->postJson("/api/v1/inscricoes/{$inscricao->id}/confirmar-contribuicao", [
                    'forma_contribuicao' => 'alimento',
                ])
                ->assertOk()
                ->assertJsonPath('data.status', 'confirmada');

            $numeros[] = $response->json('data.numero_peito');
        }

        $this->assertSame([1, 2], $numeros);
        $this->assertSame(2, Inscricao::where('campeonato_id', $campeonato->id)->whereNotNull('numero_peito')->distinct('numero_peito')->count('numero_peito'));
    }

    public function test_banco_impede_numero_de_peito_duplicado_no_mesmo_campeonato(): void
    {
        $campeonato = $this->criarCampeonato();
        $atleta1 = Atleta::factory()->create(['prefeitura_id' => $this->tenant->id]);
        $atleta2 = Atleta::factory()->create(['prefeitura_id' => $this->tenant->id]);

        Inscricao::create([
            'prefeitura_id' => $this->tenant->id,
            'atleta_id' => $atleta1->id,
            'campeonato_id' => $campeonato->id,
            'status' => 'confirmada',
            'numero_peito' => 10,
        ]);

        $this->expectException(QueryException::class);

        Inscricao::create([
            'prefeitura_id' => $this->tenant->id,
            'atleta_id' => $atleta2->id,
            'campeonato_id' => $campeonato->id,
            'status' => 'confirmada',
            'numero_peito' => 10,
        ]);
    }

    public function test_menor_sem_termo_nao_retira_kit(): void
    {
        $campeonato = $this->criarCampeonato();
        $categorias = $this->criarCategorias($campeonato);
        $this->adminHeaders();

        $crianca = Atleta::factory()->create([
            'prefeitura_id' => $this->tenant->id,
            'cpf' => null,
            'data_nascimento' => '2014-03-01',
            'responsavel_nome' => 'Responsável',
            'responsavel_telefone' => '(99) 99999-0004',
        ]);

        $inscricao = Inscricao::create([
            'prefeitura_id' => $this->tenant->id,
            'atleta_id' => $crianca->id,
            'campeonato_id' => $campeonato->id,
            'campeonato_categoria_id' => $categorias['kids']->id,
            'status' => 'confirmada',
            'numero_peito' => 1,
        ]);

        $this->withHeaders($this->headers())
            ->postJson("/api/v1/inscricoes/{$inscricao->id}/retirar-kit", [])
            ->assertStatus(422);

        $this->assertNull($inscricao->fresh()->kit_retirado_em);

        $this->withHeaders($this->headers())
            ->postJson("/api/v1/inscricoes/{$inscricao->id}/retirar-kit", [
                'termo_responsavel_assinado' => true,
            ])
            ->assertOk();

        $this->assertNotNull($inscricao->fresh()->kit_retirado_em);
        $this->assertNotNull($inscricao->fresh()->termo_responsavel_assinado_em);
    }

    public function test_retirada_por_terceiro_exige_nome_e_documento(): void
    {
        $campeonato = $this->criarCampeonato();
        $categorias = $this->criarCategorias($campeonato);
        $this->adminHeaders();

        $atleta = Atleta::factory()->create([
            'prefeitura_id' => $this->tenant->id,
            'data_nascimento' => '2000-01-01',
        ]);

        $inscricao = Inscricao::create([
            'prefeitura_id' => $this->tenant->id,
            'atleta_id' => $atleta->id,
            'campeonato_id' => $campeonato->id,
            'campeonato_categoria_id' => $categorias['m5']->id,
            'status' => 'confirmada',
            'numero_peito' => 1,
        ]);

        $this->withHeaders($this->headers())
            ->postJson("/api/v1/inscricoes/{$inscricao->id}/retirar-kit", [
                'retirado_por_terceiro' => true,
                'kit_retirado_por_nome' => 'Terceiro',
            ])
            ->assertStatus(422);

        $this->withHeaders($this->headers())
            ->postJson("/api/v1/inscricoes/{$inscricao->id}/retirar-kit", [
                'retirado_por_terceiro' => true,
                'kit_retirado_por_nome' => 'Terceiro',
                'kit_retirado_por_documento' => '123456',
            ])
            ->assertOk();

        $this->assertSame('Terceiro', $inscricao->fresh()->kit_retirado_por_nome);
    }

    public function test_inscricao_sem_confirmacao_nao_pode_retirar_kit(): void
    {
        $campeonato = $this->criarCampeonato();
        $this->adminHeaders();

        $atleta = Atleta::factory()->create([
            'prefeitura_id' => $this->tenant->id,
            'data_nascimento' => '2000-01-01',
        ]);

        $inscricao = Inscricao::create([
            'prefeitura_id' => $this->tenant->id,
            'atleta_id' => $atleta->id,
            'campeonato_id' => $campeonato->id,
            'status' => 'pendente',
        ]);

        $this->withHeaders($this->headers())
            ->postJson("/api/v1/inscricoes/{$inscricao->id}/retirar-kit", [])
            ->assertStatus(422);
    }

    public function test_campeonato_de_outra_prefeitura_nao_aparece_para_outro_tenant(): void
    {
        $outraPrefeitura = Prefeitura::factory()->create(['slug' => 'outra-prefeitura-test']);
        $campeonatoOutro = $this->criarCampeonato(['prefeitura_id' => $outraPrefeitura->id]);

        $this->withHeaders($this->headers())
            ->getJson("/api/v1/public/campeonatos/{$campeonatoOutro->id}/campos-cadastro")
            ->assertNotFound();
    }

    public function test_comprovante_exige_url_assinada_e_nao_e_acessivel_por_id_sequencial(): void
    {
        $campeonato = $this->criarCampeonato();
        $categorias = $this->criarCategorias($campeonato);

        $createResponse = $this->withHeaders($this->headers())
            ->postJson("/api/v1/public/campeonatos/{$campeonato->id}/inscricoes", $this->payloadAdulto([
                'campeonato_categoria_id' => $categorias['m5']->id,
                'forma_contribuicao' => 'alimento',
            ]))
            ->assertCreated();

        $inscricaoId = $createResponse->json('data.id');
        $comprovanteUrl = $createResponse->json('data.comprovante_url');
        $comprovantePdfUrl = $createResponse->json('data.comprovante_pdf_url');

        $this->assertNotEmpty($comprovanteUrl);
        $this->assertNotEmpty($comprovantePdfUrl);

        // Sem assinatura, a rota assinada retorna 403 e a antiga rota pública não existe mais
        $this->withHeaders($this->headers())
            ->getJson("/api/v1/signed/inscricoes/{$inscricaoId}/comprovante")
            ->assertForbidden();

        $this->withHeaders($this->headers())
            ->getJson("/api/v1/public/inscricoes/{$inscricaoId}/comprovante")
            ->assertNotFound();

        // Com URL assinada devolvida na inscrição, o comprovante funciona
        $this->withHeaders($this->headers())
            ->getJson($comprovanteUrl)
            ->assertOk()
            ->assertJsonPath('data.id', $inscricaoId)
            ->assertJsonPath('data.atleta_nome', 'Adulto Simplificado');
    }
}
