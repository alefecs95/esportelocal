<?php

namespace Tests\Unit;

use App\Models\Campeonato;
use App\Services\CadastroCamposService;
use Tests\TestCase;

class CadastroCamposServiceTest extends TestCase
{
    private CadastroCamposService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CadastroCamposService();
    }

    public function test_preset_completo_preserva_comportamento_padrao(): void
    {
        $config = $this->service->getConfiguracaoEfetiva(null);

        $this->assertSame('completo', $config['modo_cadastro']);
        $this->assertTrue($config['is_padrao_completo']);
        $this->assertTrue($config['campos']['cpf']['obrigatorio']);
        $this->assertTrue($config['campos']['nome_mae']['obrigatorio']);
        $this->assertTrue($config['campos']['titulo_eleitor']['obrigatorio']);
        $this->assertTrue($config['campos']['endereco']['obrigatorio']);
    }

    public function test_preset_simplificado_oculta_campos_burocraticos_e_mantem_essenciais(): void
    {
        $campeonato = new Campeonato(['modo_cadastro' => 'simplificado']);
        $config = $this->service->getConfiguracaoEfetiva($campeonato);

        $this->assertSame('simplificado', $config['modo_cadastro']);
        $this->assertFalse($config['is_padrao_completo']);
        $this->assertTrue($config['campos']['cpf']['obrigatorio']);
        $this->assertTrue($config['campos']['sexo']['obrigatorio']);
        $this->assertTrue($config['campos']['telefone']['obrigatorio']);
        $this->assertTrue($config['campos']['email']['visivel']);
        $this->assertFalse($config['campos']['email']['obrigatorio']);
        $this->assertTrue($config['campos']['tamanho_camiseta']['visivel']);
        $this->assertFalse($config['campos']['nome_mae']['visivel']);
        $this->assertFalse($config['campos']['titulo_eleitor']['visivel']);
        $this->assertFalse($config['campos']['endereco']['visivel']);
        $this->assertFalse($config['campos']['foto']['visivel']);
    }

    public function test_modo_personalizado_forca_visivel_quando_obrigatorio(): void
    {
        $campeonato = new Campeonato([
            'modo_cadastro' => 'personalizado',
            'campos_cadastro' => [
                'cpf' => ['visivel' => false, 'obrigatorio' => false],
                'tamanho_camiseta' => ['visivel' => false, 'obrigatorio' => true],
            ],
        ]);

        $config = $this->service->getConfiguracaoEfetiva($campeonato);

        $this->assertFalse($config['campos']['cpf']['visivel']);
        $this->assertFalse($config['campos']['cpf']['obrigatorio']);
        $this->assertTrue($config['campos']['tamanho_camiseta']['visivel']);
        $this->assertTrue($config['campos']['tamanho_camiseta']['obrigatorio']);
    }

    public function test_validacao_simplificada_exige_responsavel_para_menor_e_libera_cpf(): void
    {
        $campeonato = new Campeonato([
            'modo_cadastro' => 'simplificado',
            'data_inicio' => '2026-12-19',
            'exige_termo_menor' => true,
        ]);

        $regrasMenor = $this->service->buildAtletaValidationRules($campeonato, [
            'data_nascimento' => '2014-05-01',
        ], 1, true);

        $this->assertContains('nullable', $regrasMenor['cpf']);
        $this->assertContains('required', $regrasMenor['responsavel_nome']);
        $this->assertContains('required', $regrasMenor['responsavel_telefone']);
        $this->assertContains('nullable', $regrasMenor['nome_mae']);
        $this->assertContains('nullable', $regrasMenor['endereco']);

        $regrasAdulto = $this->service->buildAtletaValidationRules($campeonato, [
            'data_nascimento' => '1995-05-01',
        ], 1, true);

        $this->assertContains('required', $regrasAdulto['cpf']);
        $this->assertContains('nullable', $regrasAdulto['responsavel_nome']);
    }
}
