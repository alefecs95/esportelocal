<?php

namespace Tests\Feature;

use App\Models\Atleta;
use App\Models\Campeonato;
use App\Models\Inscricao;
use App\Models\Modalidade;
use App\Models\Prefeitura;
use App\Models\User;
use App\Services\PremiacaoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PremiacaoCorridaTest extends TestCase
{
    use RefreshDatabase;

    private Prefeitura $tenant;
    private Campeonato $campeonato;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Prefeitura::factory()->create(['slug' => 'premiacao-corrida-test']);
        App::instance('current_tenant', $this->tenant);

        $modalidade = Modalidade::factory()->create([
            'prefeitura_id' => $this->tenant->id,
            'tipo' => 'individual',
        ]);

        $this->campeonato = Campeonato::factory()->create([
            'prefeitura_id' => $this->tenant->id,
            'modalidade_id' => $modalidade->id,
            'tipo_competicao' => 'individual',
            'status' => 'inscricoes_abertas',
            'inscricao_solidaria' => true,
            'exige_termo_menor' => true,
        ]);
    }

    private function categoria(string $nome, bool $competitiva, ?string $distancia = null)
    {
        App::instance('current_tenant', $this->tenant);

        return $this->campeonato->categorias()->create([
            'prefeitura_id' => $this->tenant->id,
            'nome' => $nome,
            'genero' => 'M',
            'distancia' => $distancia,
            'competitiva' => $competitiva,
            'premiacao_descricao' => $competitiva ? 'Troféu' : 'Medalha',
        ]);
    }

    private function inscricao($categoria, string $nomeAtleta, int $colocacao, int $tempoMs, array $extra = []): Inscricao
    {
        $atleta = Atleta::factory()->create([
            'prefeitura_id' => $this->tenant->id,
            'nome' => $nomeAtleta,
            'data_nascimento' => '2000-01-01',
        ]);

        return Inscricao::create(array_merge([
            'prefeitura_id' => $this->tenant->id,
            'atleta_id' => $atleta->id,
            'campeonato_id' => $this->campeonato->id,
            'campeonato_categoria_id' => $categoria->id,
            'status' => 'confirmada',
            'numero_peito' => random_int(1000, 999999),
            'tempo_ms' => $tempoMs,
            'tempo_visual' => gmdate('H:i:s', intdiv($tempoMs, 1000)),
            'colocacao' => $colocacao,
            'colocacao_categoria' => $colocacao,
        ], $extra));
    }

    public function test_podio_pega_tres_primeiros_por_categoria_competitiva(): void
    {
        $cat5 = $this->categoria('5 km Masculino', true, '5km');

        $this->inscricao($cat5, 'Primeiro', 1, 1000000);
        $this->inscricao($cat5, 'Segundo', 2, 1100000);
        $this->inscricao($cat5, 'Terceiro', 3, 1200000);
        $this->inscricao($cat5, 'Quarto', 4, 1300000);

        $podio = app(PremiacaoService::class)->montarPodio($this->campeonato);

        $this->assertCount(1, $podio);
        $nomes = collect($podio[0]['colocados'])->pluck('atleta_nome')->all();
        $this->assertSame(['Primeiro', 'Segundo', 'Terceiro'], $nomes);
    }

    public function test_mesma_pessoa_nao_aparece_duas_vezes_no_podio(): void
    {
        $cat5 = $this->categoria('5 km Masculino', true, '5km');
        $cat3 = $this->categoria('3 km Masculino', true, '3km');

        // Mesmo atleta com colocação 1 nas duas categorias (cadastro duplicado)
        $atletaDuplicado = Atleta::factory()->create(['prefeitura_id' => $this->tenant->id, 'nome' => 'Duplicado']);
        foreach ([$cat5, $cat3] as $cat) {
            Inscricao::create([
                'prefeitura_id' => $this->tenant->id,
                'atleta_id' => $atletaDuplicado->id,
                'campeonato_id' => $this->campeonato->id,
                'campeonato_categoria_id' => $cat->id,
                'status' => 'confirmada',
                'numero_peito' => random_int(1000, 999999),
                'tempo_ms' => 1000000,
                'colocacao' => 1,
                'colocacao_categoria' => 1,
            ]);
        }
        $this->inscricao($cat3, 'Outro Atleta', 2, 1100000);

        $podio = app(PremiacaoService::class)->montarPodio($this->campeonato);

        $todos = collect($podio)->flatMap(fn ($grupo) => collect($grupo['colocados'])->pluck('atleta_id'));
        $this->assertSame($todos->count(), $todos->unique()->count(), 'Nenhuma pessoa pode ser premiada duas vezes.');
    }

    public function test_kids_nao_competitiva_fica_fora_do_podio_mas_entra_na_medalha_finisher(): void
    {
        $kids = $this->categoria('Kids', false);

        $inscricao = $this->inscricao($kids, 'Criança', 1, 900000, [
            'kit_retirado_em' => now(),
            'finalizou_em' => now(),
        ]);

        $podio = app(PremiacaoService::class)->montarPodio($this->campeonato);
        $this->assertCount(0, $podio);

        $medalhas = app(PremiacaoService::class)->medalhasFinisher($this->campeonato);
        $this->assertTrue($medalhas->contains('id', $inscricao->id));
    }

    public function test_chegada_manual_exige_kit_retirado(): void
    {
        $kids = $this->categoria('Kids', false);
        $semKit = $this->inscricao($kids, 'Sem Kit', 1, 900000, ['tempo_ms' => null, 'tempo_visual' => null]);

        $this->expectException(\InvalidArgumentException::class);
        app(PremiacaoService::class)->marcarFinalizacaoManual($semKit);
    }

    public function test_chegada_manual_registra_finalizacao_para_kids(): void
    {
        $kids = $this->categoria('Kids', false);
        $inscricao = $this->inscricao($kids, 'Com Kit', 1, 900000, [
            'tempo_ms' => null,
            'tempo_visual' => null,
            'kit_retirado_em' => now(),
        ]);

        $atualizada = app(PremiacaoService::class)->marcarFinalizacaoManual($inscricao);

        $this->assertNotNull($atualizada->finalizou_em);
    }

    public function test_premiar_bloqueia_atleta_ja_premiado_no_campeonato(): void
    {
        $cat5 = $this->categoria('5 km Masculino', true, '5km');
        $cat3 = $this->categoria('3 km Masculino', true, '3km');

        $premiada = $this->inscricao($cat5, 'Já Premiado', 1, 1000000, ['premiado_em' => now()]);

        // Mesma pessoa, outra inscrição no mesmo campeonato
        $outra = Inscricao::create([
            'prefeitura_id' => $this->tenant->id,
            'atleta_id' => $premiada->atleta_id,
            'campeonato_id' => $this->campeonato->id,
            'campeonato_categoria_id' => $cat3->id,
            'status' => 'confirmada',
            'numero_peito' => random_int(1000, 999999),
            'tempo_ms' => 1100000,
            'colocacao' => 1,
            'colocacao_categoria' => 1,
        ]);

        $this->expectException(\InvalidArgumentException::class);
        app(PremiacaoService::class)->registrarPremiacao($outra);
    }

    public function test_endpoint_premiacao_exige_admin_e_retorna_podio(): void
    {
        $cat5 = $this->categoria('5 km Masculino', true, '5km');
        $this->inscricao($cat5, 'Vencedor', 1, 1000000);

        $headers = [
            'X-Tenant-Slug' => $this->tenant->slug,
            'Accept' => 'application/json',
        ];

        $this->withHeaders($headers)
            ->getJson("/api/v1/campeonatos/{$this->campeonato->id}/premiacao")
            ->assertUnauthorized();

        $admin = User::factory()->create([
            'perfil' => 'ADMIN',
            'prefeitura_id' => $this->tenant->id,
        ]);
        Sanctum::actingAs($admin);

        $this->withHeaders($headers)
            ->getJson("/api/v1/campeonatos/{$this->campeonato->id}/premiacao")
            ->assertOk()
            ->assertJsonPath('data.0.colocados.0.atleta_nome', 'Vencedor');
    }

    public function test_resultados_publicos_nao_expoem_dados_sensiveis(): void
    {
        $cat5 = $this->categoria('5 km Masculino', true, '5km');
        $this->inscricao($cat5, 'Público', 1, 1000000, ['tempo_visual' => '00:16:40']);

        $response = $this->withHeaders([
            'X-Tenant-Slug' => $this->tenant->slug,
            'Accept' => 'application/json',
        ])->getJson("/api/v1/public/campeonatos/{$this->campeonato->id}/resultados-corrida")
            ->assertOk();

        $json = $response->getContent();
        $this->assertStringNotContainsString('cpf', $json);
        $this->assertStringNotContainsString('telefone', $json);
        $this->assertStringNotContainsString('responsavel', $json);
    }

    public function test_podio_respeita_ordem_premiacao_explicita_e_informa_pulados(): void
    {
        // Cria 3km primeiro (id menor) mas com ordem_premiacao = 2; e 5km depois com ordem_premiacao = 1
        $cat3 = $this->campeonato->categorias()->create([
            'prefeitura_id' => $this->tenant->id,
            'nome' => '3 km Masculino',
            'genero' => 'M',
            'distancia' => '3km',
            'competitiva' => true,
            'ordem_premiacao' => 2,
        ]);
        $cat5 = $this->campeonato->categorias()->create([
            'prefeitura_id' => $this->tenant->id,
            'nome' => '5 km Masculino',
            'genero' => 'M',
            'distancia' => '5km',
            'competitiva' => true,
            'ordem_premiacao' => 1,
        ]);

        $atleta = Atleta::factory()->create(['prefeitura_id' => $this->tenant->id, 'nome' => 'Repetido']);
        foreach ([$cat3, $cat5] as $cat) {
            Inscricao::create([
                'prefeitura_id' => $this->tenant->id,
                'atleta_id' => $atleta->id,
                'campeonato_id' => $this->campeonato->id,
                'campeonato_categoria_id' => $cat->id,
                'status' => 'confirmada',
                'numero_peito' => random_int(1000, 999999),
                'tempo_ms' => 1000000,
                'colocacao' => 1,
                'colocacao_categoria' => 1,
            ]);
        }

        $podio = app(PremiacaoService::class)->montarPodio($this->campeonato);

        $this->assertSame('5 km Masculino', $podio[0]['categoria']['nome']);
        $this->assertSame('3 km Masculino', $podio[1]['categoria']['nome']);
        $this->assertCount(1, $podio[0]['colocados']);
        $this->assertCount(0, $podio[1]['colocados']);
        $this->assertCount(1, $podio[1]['pulados']);
        $this->assertSame('Repetido', $podio[1]['pulados'][0]['atleta_nome']);
    }
}
