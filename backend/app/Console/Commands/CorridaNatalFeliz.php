<?php

namespace App\Console\Commands;

use App\Models\Campeonato;
use App\Models\Modalidade;
use App\Models\Prefeitura;
use App\Scopes\TenantScope;
use Illuminate\Console\Command;

/**
 * Cria (de forma idempotente) a Corrida Solidária Natal Feliz 2026.
 * Não apaga nem sobrescreve registros existentes: campeonato e categorias já existentes são preservados.
 */
class CorridaNatalFeliz extends Command
{
    // CONFIRMAR COM A ORGANIZAÇÃO: faixas etárias de Kids e Teens (sugestão inicial)
    public const IDADE_MAX_KIDS = 12;
    public const IDADE_MIN_TEENS = 13; // CONFIRMAR COM A ORGANIZAÇÃO
    public const IDADE_MAX_TEENS = 17; // CONFIRMAR COM A ORGANIZAÇÃO

    // CONFIRMAR COM A ORGANIZAÇÃO: Teens sem taxa definida; segue a regra de criança (1 kg ou 1 brinquedo)
    public const TEENS_ALIMENTO_KG = 1;
    public const TEENS_ACEITA_BRINQUEDO = true;

    // Taxa de inscrição adulto: R$ 30,00 ou 4 kg de alimento ou 1 brinquedo
    public const ADULTO_VALOR = 30.00;
    public const ADULTO_ALIMENTO_KG = 4;
    public const CRIANCA_ALIMENTO_KG = 1;

    protected $signature = 'corrida:natal-feliz {prefeitura_id : ID da prefeitura (tenant) que receberá a corrida}';

    protected $description = 'Cria a Corrida Solidária Natal Feliz (idempotente, não apaga dados existentes)';

    public function handle(): int
    {
        $prefeituraId = (int) $this->argument('prefeitura_id');
        $prefeitura = Prefeitura::find($prefeituraId);
        if (!$prefeitura) {
            $this->error("Prefeitura {$prefeituraId} não encontrada.");
            return self::FAILURE;
        }

        $modalidade = Modalidade::withoutGlobalScope(TenantScope::class)->firstOrCreate(
            ['prefeitura_id' => $prefeituraId, 'nome' => 'Corrida de Rua'],
            ['tipo' => 'individual', 'status' => true]
        );

        $campeonato = Campeonato::withoutGlobalScope(TenantScope::class)
            ->where('prefeitura_id', $prefeituraId)
            ->where('nome', 'Corrida Solidária Natal Feliz')
            ->whereDate('data_inicio', '2026-12-19')
            ->first();

        if ($campeonato) {
            $this->warn("Campeonato já existe (ID {$campeonato->id}). Nenhum campo existente será alterado.");
        } else {
            $campeonato = Campeonato::withoutGlobalScope(TenantScope::class)->create([
                'prefeitura_id' => $prefeituraId,
                'nome' => 'Corrida Solidária Natal Feliz',
                'local' => 'Praça da Matriz, São Francisco do Maranhão',
                'modalidade_id' => $modalidade->id,
                'categoria' => 'Corrida de Rua',
                'genero' => 'misto',
                'tipo_competicao' => 'individual',
                'data_inicio' => '2026-12-19',
                'data_fim' => '2026-12-19',
                'limite_participantes' => null, // não definido no regulamento
                'valor_inscricao' => self::ADULTO_VALOR,
                'regulamento' => $this->textoRegulamento(),
                'status' => 'inscricoes_abertas',
                'permite_inscricao_online' => true,
                'inicio_inscricao' => '2026-11-27',
                'fim_inscricao' => '2026-12-11',
                'inscricao_solidaria' => true,
                'exige_termo_menor' => true,
                'modo_cadastro' => 'simplificado',
                'campos_cadastro' => null,
                'kit_retirada_info' => null, // data/local ainda não divulgados
            ]);
            $this->info("Campeonato criado (ID {$campeonato->id}).");
        }

        $categorias = [
            ['nome' => '5 km Masculino', 'distancia' => '5km', 'genero' => 'M', 'idade_min' => null, 'idade_max' => null, 'competitiva' => true, 'ordem_premiacao' => 1, 'valor' => self::ADULTO_VALOR, 'alimento' => self::ADULTO_ALIMENTO_KG, 'brinquedo' => true, 'premiacao' => 'Troféu + cesta básica'],
            ['nome' => '5 km Feminino', 'distancia' => '5km', 'genero' => 'F', 'idade_min' => null, 'idade_max' => null, 'competitiva' => true, 'ordem_premiacao' => 1, 'valor' => self::ADULTO_VALOR, 'alimento' => self::ADULTO_ALIMENTO_KG, 'brinquedo' => true, 'premiacao' => 'Troféu + cesta básica'],
            ['nome' => '3 km Masculino', 'distancia' => '3km', 'genero' => 'M', 'idade_min' => null, 'idade_max' => null, 'competitiva' => true, 'ordem_premiacao' => 2, 'valor' => self::ADULTO_VALOR, 'alimento' => self::ADULTO_ALIMENTO_KG, 'brinquedo' => true, 'premiacao' => 'Troféu'],
            ['nome' => '3 km Feminino', 'distancia' => '3km', 'genero' => 'F', 'idade_min' => null, 'idade_max' => null, 'competitiva' => true, 'ordem_premiacao' => 2, 'valor' => self::ADULTO_VALOR, 'alimento' => self::ADULTO_ALIMENTO_KG, 'brinquedo' => true, 'premiacao' => 'Troféu'],
            ['nome' => 'Kids', 'distancia' => null, 'genero' => 'Misto', 'idade_min' => null, 'idade_max' => self::IDADE_MAX_KIDS, 'competitiva' => false, 'ordem_premiacao' => null, 'valor' => 0, 'alimento' => self::CRIANCA_ALIMENTO_KG, 'brinquedo' => true, 'premiacao' => 'Medalha'],
            ['nome' => 'Teens', 'distancia' => null, 'genero' => 'Misto', 'idade_min' => self::IDADE_MIN_TEENS, 'idade_max' => self::IDADE_MAX_TEENS, 'competitiva' => false, 'ordem_premiacao' => null, 'valor' => 0, 'alimento' => self::TEENS_ALIMENTO_KG, 'brinquedo' => self::TEENS_ACEITA_BRINQUEDO, 'premiacao' => 'Medalha'],
        ];

        $criadas = 0;
        foreach ($categorias as $cat) {
            $existe = $campeonato->categorias()
                ->withoutGlobalScope(TenantScope::class)
                ->where('nome', $cat['nome'])
                ->exists();

            if ($existe) {
                continue;
            }

            $campeonato->categorias()->create([
                'prefeitura_id' => $prefeituraId,
                'nome' => $cat['nome'],
                'genero' => $cat['genero'],
                'idade_min' => $cat['idade_min'],
                'idade_max' => $cat['idade_max'],
                'distancia' => $cat['distancia'],
                'valor_inscricao' => $cat['valor'],
                'contribuicao_alimento_kg' => $cat['alimento'],
                'aceita_brinquedo' => $cat['brinquedo'],
                'competitiva' => $cat['competitiva'],
                'ordem_premiacao' => $cat['ordem_premiacao'],
                'premiacao_descricao' => $cat['premiacao'],
                'largada_prevista' => $cat['nome'] === 'Kids' || $cat['nome'] === 'Teens' ? '07:45' : '06:30',
            ]);
            $criadas++;
        }

        $this->info("Categorias criadas: {$criadas}. Categorias existentes foram mantidas.");
        $this->line('Premissas a confirmar com a organização: faixas etárias de Kids/Teens e taxa dos Teens (ver constantes no topo do comando).');

        return self::SUCCESS;
    }

    private function textoRegulamento(): string
    {
        return implode("\n", [
            'Corrida Solidária Natal Feliz - 19/12/2026 - Praça da Matriz, São Francisco do Maranhão.',
            'Organização: José Borges, com Prefeitura e Secretaria Municipal de Esportes.',
            'Provas: 5 km e 3 km (adulto, largada às 06:30; concentração a partir de 06:00) e categorias Kids e Teens (largada por volta de 07:45).',
            'Inscrições online de 27/11/2026 a 11/12/2026.',
            'Taxa de inscrição (adulto): R$ 30,00 ou 4 kg de alimento ou 1 brinquedo. Criança: 1 kg de alimento ou 1 brinquedo.',
            'Menor de idade: somente com autorização dos pais, por termo assinado na retirada do número de peito.',
            'Retirada do kit: somente o atleta inscrito, com documento com foto. Terceiros podem retirar levando documento do atleta.',
            'Resultado por ordem de chegada. Premiação: 3 primeiros gerais do 5 km e 3 km, masculino e feminino. Ninguém acumula premiação.',
            'Kids e Teens não competem: todos recebem medalha. Medalha finisher: inscrito que retirou o número de peito e concluiu a prova.',
        ]);
    }
}
