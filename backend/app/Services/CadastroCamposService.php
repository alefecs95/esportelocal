<?php

namespace App\Services;

use App\Models\Campeonato;
use Carbon\Carbon;
use Illuminate\Validation\Rule;

class CadastroCamposService
{
    public const CAMPOS_CONFIGURAVEIS = [
        'cpf',
        'sexo',
        'nome_mae',
        'nome_pai',
        'titulo_eleitor',
        'cep',
        'endereco',
        'telefone',
        'email',
        'foto',
        'tamanho_camiseta',
        'contato_emergencia',
    ];

    public const CAMPOS_FIXOS = [
        'nome' => ['visivel' => true, 'obrigatorio' => true],
        'data_nascimento' => ['visivel' => true, 'obrigatorio' => true],
    ];

    public function getPreset(string $modo): array
    {
        if ($modo === 'simplificado') {
            return [
                'cpf' => ['visivel' => true, 'obrigatorio' => true],
                'sexo' => ['visivel' => true, 'obrigatorio' => true],
                'nome_mae' => ['visivel' => false, 'obrigatorio' => false],
                'nome_pai' => ['visivel' => false, 'obrigatorio' => false],
                'titulo_eleitor' => ['visivel' => false, 'obrigatorio' => false],
                'cep' => ['visivel' => false, 'obrigatorio' => false],
                'endereco' => ['visivel' => false, 'obrigatorio' => false],
                'telefone' => ['visivel' => true, 'obrigatorio' => true],
                'email' => ['visivel' => true, 'obrigatorio' => false],
                'foto' => ['visivel' => false, 'obrigatorio' => false],
                'tamanho_camiseta' => ['visivel' => true, 'obrigatorio' => false],
                'contato_emergencia' => ['visivel' => true, 'obrigatorio' => false],
            ];
        }

        // Preset 'completo' (comportamento atual)
        return [
            'cpf' => ['visivel' => true, 'obrigatorio' => true],
            'sexo' => ['visivel' => true, 'obrigatorio' => false],
            'nome_mae' => ['visivel' => true, 'obrigatorio' => true],
            'nome_pai' => ['visivel' => true, 'obrigatorio' => false],
            'titulo_eleitor' => ['visivel' => true, 'obrigatorio' => true],
            'cep' => ['visivel' => true, 'obrigatorio' => true],
            'endereco' => ['visivel' => true, 'obrigatorio' => true],
            'telefone' => ['visivel' => true, 'obrigatorio' => false],
            'email' => ['visivel' => true, 'obrigatorio' => false],
            'foto' => ['visivel' => true, 'obrigatorio' => false],
            'tamanho_camiseta' => ['visivel' => false, 'obrigatorio' => false],
            'contato_emergencia' => ['visivel' => false, 'obrigatorio' => false],
        ];
    }

    /**
     * Retorna a configuração efetiva de campos para um campeonato (ou padrão completo se null).
     */
    public function getConfiguracaoEfetiva(?Campeonato $campeonato): array
    {
        $modo = $campeonato?->modo_cadastro ?: 'completo';
        if (!in_array($modo, ['completo', 'simplificado', 'personalizado'], true)) {
            $modo = 'completo';
        }

        if (!$campeonato || $modo === 'completo') {
            $campos = $this->getPreset('completo');
            return [
                'modo_cadastro' => 'completo',
                'is_padrao_completo' => true,
                'cidade_visivel' => true,
                'campos_fixos' => self::CAMPOS_FIXOS,
                'campos' => $campos,
            ];
        }

        if ($modo === 'simplificado') {
            $campos = $this->getPreset('simplificado');
            return [
                'modo_cadastro' => 'simplificado',
                'is_padrao_completo' => false,
                'cidade_visivel' => true,
                'campos_fixos' => self::CAMPOS_FIXOS,
                'campos' => $campos,
            ];
        }

        // Modo personalizado
        $base = $this->getPreset('completo');
        $custom = is_array($campeonato->campos_cadastro) ? $campeonato->campos_cadastro : [];

        $campos = [];
        foreach (self::CAMPOS_CONFIGURAVEIS as $campo) {
            $item = $custom[$campo] ?? $base[$campo];
            $obrigatorio = (bool) ($item['obrigatorio'] ?? false);
            $visivel = $obrigatorio ? true : (bool) ($item['visivel'] ?? false);
            $campos[$campo] = [
                'visivel' => $visivel,
                'obrigatorio' => $obrigatorio,
            ];
        }

        return [
            'modo_cadastro' => 'personalizado',
            'is_padrao_completo' => false,
            'cidade_visivel' => true,
            'campos_fixos' => self::CAMPOS_FIXOS,
            'campos' => $campos,
        ];
    }

    public function calcularIdadeNaDataEvento(?string $dataNascimento, ?Campeonato $campeonato = null): ?int
    {
        if (!$dataNascimento) {
            return null;
        }

        try {
            $nascimento = Carbon::parse($dataNascimento);
            $referencia = $campeonato?->data_inicio
                ? Carbon::parse($campeonato->data_inicio)
                : Carbon::now();

            return $nascimento->diffInYears($referencia, false);
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function isMenorDeIdade(?string $dataNascimento, ?Campeonato $campeonato = null): bool
    {
        $idade = $this->calcularIdadeNaDataEvento($dataNascimento, $campeonato);
        return $idade !== null && $idade < 18;
    }

    /**
     * Gera regras de validação Laravel respeitando a configuração do campeonato.
     */
    public function buildAtletaValidationRules(
        ?Campeonato $campeonato,
        array $input = [],
        ?int $prefeituraId = null,
        bool $isPublicEventRegistration = false
    ): array {
        $config = $this->getConfiguracaoEfetiva($campeonato);
        $campos = $config['campos'];
        $isPadraoCompleto = $config['is_padrao_completo'];

        $dataNascimento = $input['data_nascimento'] ?? null;
        $idadeHoje = null;
        if ($dataNascimento) {
            try {
                $idadeHoje = Carbon::parse($dataNascimento)->age;
            } catch (\Throwable $e) {
                $idadeHoje = null;
            }
        }
        $isMaiorOuIgual16 = $idadeHoje !== null && $idadeHoje >= 16;
        $isMenor18 = $this->isMenorDeIdade($dataNascimento, $campeonato);

        $req = fn (string $campo): string =>
            (!empty($campos[$campo]['visivel']) && !empty($campos[$campo]['obrigatorio']))
                ? 'required'
                : 'nullable';

        // Crianças/menores em cadastro simplificado ou personalizado podem se inscrever sem CPF próprio
        $cpfObrigatorio = !empty($campos['cpf']['visivel']) && !empty($campos['cpf']['obrigatorio']);
        if (!$isPadraoCompleto && $isMenor18) {
            $cpfObrigatorio = false;
        }

        $cpfUniqueRule = Rule::unique('atletas', 'cpf');
        if ($prefeituraId) {
            $cpfUniqueRule = $cpfUniqueRule->where(fn ($q) => $q->where('prefeitura_id', $prefeituraId));
        }

        $eleitoralObrigatorio = !empty($campos['titulo_eleitor']['visivel'])
            && !empty($campos['titulo_eleitor']['obrigatorio'])
            && $isMaiorOuIgual16;

        $exigeResponsavel = $isMenor18 && (!$isPadraoCompleto || (bool) ($campeonato?->exige_termo_menor));
        $responsavelCpfObrigatorio = $exigeResponsavel
            && !empty($campos['cpf']['visivel'])
            && !empty($campos['cpf']['obrigatorio'])
            && !empty($campeonato?->campos_cadastro['responsavel_cpf']['obrigatorio']);

        $rules = [
            'nome' => 'required|string|max:255',
            'cpf' => [
                $cpfObrigatorio ? 'required' : 'nullable',
                'string',
                'max:14',
                ...($isPublicEventRegistration ? [] : [$cpfUniqueRule]),
            ],
            'data_nascimento' => 'required|date',
            'sexo' => [$req('sexo'), 'string', 'max:20'],
            'nome_mae' => [$req('nome_mae'), 'string', 'max:255'],
            'nome_pai' => [$req('nome_pai'), 'string', 'max:255'],
            'titulo_eleitor' => ['nullable', 'string', 'max:20'],
            'zona_eleitoral' => [$eleitoralObrigatorio ? 'required' : 'nullable', 'string', 'max:10'],
            'secao_eleitoral' => [$eleitoralObrigatorio ? 'required' : 'nullable', 'string', 'max:10'],
            'bairro_eleitoral' => [$eleitoralObrigatorio ? 'required' : 'nullable', 'string', 'max:100'],
            'cidade_eleitoral' => [$eleitoralObrigatorio ? 'required' : 'nullable', 'string', 'max:100'],
            'uf_eleitoral' => [$eleitoralObrigatorio ? 'required' : 'nullable', 'string', 'max:2'],
            'titulo_verificado' => 'nullable|boolean',
            'validacao_eleitoral_pendente' => 'nullable|boolean',
            'cep' => [$req('cep'), 'string', 'max:9'],
            'endereco' => [$req('endereco'), 'string', 'max:255'],
            'numero' => 'nullable|string|max:20',
            'complemento' => 'nullable|string|max:255',
            'bairro' => [$req('endereco'), 'string', 'max:100'],
            // Cidade é mantida no preset simplificado; no completo continua obrigatória como hoje
            'cidade' => [$isPadraoCompleto ? 'required' : 'nullable', 'string', 'max:100'],
            'estado' => [$req('endereco'), 'string', 'max:2'],
            'telefone' => [
                $req('telefone'),
                'string',
                'max:20',
                ...($isPadraoCompleto ? ['required_if:receive_notifications,1,true'] : []),
            ],
            'email' => [$req('email'), 'email', 'max:255'],
            'foto' => [$req('foto'), 'nullable'],
            'tamanho_camiseta' => [$req('tamanho_camiseta'), 'string', 'max:10'],
            'contato_emergencia' => [$req('contato_emergencia'), 'string', 'max:255'],
            'responsavel_nome' => [$exigeResponsavel ? 'required' : 'nullable', 'string', 'max:255'],
            'responsavel_telefone' => [$exigeResponsavel ? 'required' : 'nullable', 'string', 'max:20'],
            'responsavel_cpf' => [$responsavelCpfObrigatorio ? 'required' : 'nullable', 'string', 'max:14'],
            'receive_notifications' => $isPublicEventRegistration ? 'nullable|boolean' : 'required|boolean',
            'arquivos.*' => 'nullable|file|max:10240',
            'nomes_arquivos.*' => 'nullable|string|max:255',
            'modalidades' => 'nullable|array',
            'modalidades.*' => 'exists:modalidades,id',
            'campeonato_id' => 'nullable|exists:campeonatos,id',
        ];

        return $rules;
    }
}
