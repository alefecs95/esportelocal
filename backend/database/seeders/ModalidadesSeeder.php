<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Modalidade;
use Illuminate\Support\Facades\DB;

class ModalidadesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Limpar modalidades existentes para evitar duplicação ou inconsistência (opcional, mas bom para garantir o padrão)
        // DB::table('modalidades')->truncate(); // Cuidado: isso apaga tudo. Melhor usar updateOrCreate.

        $modalidades = [
            [
                'nome' => 'Futebol de Campo',
                'tipo' => 'coletivo',
                'descricao' => 'Competição tradicional de futebol de campo 11x11.',
                'regras_config' => [
                    'campos_categoria' => ['sexo', 'idade'], // Sub-11, Sub-13, Amador, Veterano
                    'sistema_pontuacao' => 'gols',
                    'requer_sumula' => true,
                    'min_jogadores' => 11,
                    'max_jogadores' => 22,
                ]
            ],
            [
                'nome' => 'Futsal',
                'tipo' => 'coletivo',
                'descricao' => 'Futebol de salão 5x5.',
                'regras_config' => [
                    'campos_categoria' => ['sexo', 'idade'],
                    'sistema_pontuacao' => 'gols',
                    'requer_sumula' => true,
                    'min_jogadores' => 5,
                    'max_jogadores' => 12,
                ]
            ],
            [
                'nome' => 'Voleibol',
                'tipo' => 'coletivo',
                'descricao' => 'Vôlei de quadra 6x6.',
                'regras_config' => [
                    'campos_categoria' => ['sexo', 'idade'],
                    'sistema_pontuacao' => 'sets',
                    'requer_sumula' => true,
                    'min_jogadores' => 6,
                    'max_jogadores' => 12,
                ]
            ],
            [
                'nome' => 'Basquetebol',
                'tipo' => 'coletivo',
                'descricao' => 'Basquete de quadra 5x5.',
                'regras_config' => [
                    'campos_categoria' => ['sexo', 'idade'],
                    'sistema_pontuacao' => 'pontos',
                    'requer_sumula' => true,
                    'min_jogadores' => 5,
                    'max_jogadores' => 12,
                ]
            ],
            [
                'nome' => 'Handebol',
                'tipo' => 'coletivo',
                'descricao' => 'Handebol de quadra 7x7.',
                'regras_config' => [
                    'campos_categoria' => ['sexo', 'idade'],
                    'sistema_pontuacao' => 'gols',
                    'requer_sumula' => true,
                    'min_jogadores' => 7,
                    'max_jogadores' => 14,
                ]
            ],
            [
                'nome' => 'Corrida de Rua',
                'tipo' => 'individual',
                'descricao' => 'Corridas de rua com distâncias variadas.',
                'regras_config' => [
                    'campos_categoria' => ['sexo', 'idade', 'distancia'], // 5km, 10km, 21km
                    'sistema_pontuacao' => 'tempo',
                    'requer_sumula' => false,
                    'permite_inscricao_avulsa' => true,
                ]
            ],
            [
                'nome' => 'Ciclismo',
                'tipo' => 'individual',
                'descricao' => 'Provas de ciclismo de estrada ou MTB.',
                'regras_config' => [
                    'campos_categoria' => ['sexo', 'idade', 'distancia'],
                    'sistema_pontuacao' => 'tempo',
                    'requer_sumula' => false,
                    'permite_inscricao_avulsa' => true,
                ]
            ],
            [
                'nome' => 'Judô',
                'tipo' => 'individual',
                'descricao' => 'Competições de Judô por peso e faixa.',
                'regras_config' => [
                    'campos_categoria' => ['sexo', 'idade', 'peso'], // Peso é crucial aqui
                    'sistema_pontuacao' => 'eliminatoria',
                    'requer_sumula' => true,
                    'permite_inscricao_avulsa' => true,
                ]
            ],
            [
                'nome' => 'Jiu-Jitsu',
                'tipo' => 'individual',
                'descricao' => 'Competições de Jiu-Jitsu.',
                'regras_config' => [
                    'campos_categoria' => ['sexo', 'idade', 'peso'],
                    'sistema_pontuacao' => 'eliminatoria',
                    'requer_sumula' => true,
                    'permite_inscricao_avulsa' => true,
                ]
            ],
            [
                'nome' => 'Natação',
                'tipo' => 'individual',
                'descricao' => 'Provas de natação em piscina.',
                'regras_config' => [
                    'campos_categoria' => ['sexo', 'idade', 'distancia'], // Distância define a prova (50m, 100m)
                    'sistema_pontuacao' => 'tempo',
                    'requer_sumula' => false,
                    'permite_inscricao_avulsa' => true,
                ]
            ],
            [
                'nome' => 'Xadrez',
                'tipo' => 'individual',
                'descricao' => 'Torneios de Xadrez.',
                'regras_config' => [
                    'campos_categoria' => ['idade'], // Geralmente Misto ou Absoluto
                    'sistema_pontuacao' => 'pontos_suico',
                    'requer_sumula' => true,
                    'permite_inscricao_avulsa' => true,
                ]
            ],
            [
                'nome' => 'Tênis de Mesa',
                'tipo' => 'individual',
                'descricao' => 'Torneios de Ping-Pong / Tênis de Mesa.',
                'regras_config' => [
                    'campos_categoria' => ['sexo', 'idade'],
                    'sistema_pontuacao' => 'sets',
                    'requer_sumula' => true,
                    'permite_inscricao_avulsa' => true,
                ]
            ],
        ];

        $prefeituraId = DB::table('prefeituras')->first()->id ?? 1;

        foreach ($modalidades as $mod) {
            Modalidade::updateOrCreate(
                ['nome' => $mod['nome']], // Chave de busca
                [
                    'tipo' => $mod['tipo'],
                    'descricao' => $mod['descricao'],
                    'regras_config' => $mod['regras_config'],
                    'status' => true,
                    'prefeitura_id' => $prefeituraId,
                ]
            );
        }
    }
}
