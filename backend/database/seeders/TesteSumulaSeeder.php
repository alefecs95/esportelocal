<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Campeonato;
use App\Models\Fase;
use App\Models\Equipe;
use App\Models\Partida;
use App\Models\Atleta;
use App\Models\Inscricao;
use App\Models\Modalidade;
use App\Models\Prefeitura;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;

class TesteSumulaSeeder extends Seeder
{
    public function run()
    {
        $this->command->info('Iniciando setup para Teste de Súmula...');

        $prefeitura = Prefeitura::find(1);

        if (!$prefeitura) {
            $this->command->error('Prefeitura ID 1 não encontrada.');
            return;
        }

        $this->command->info("Usando Prefeitura: {$prefeitura->nome} (ID: {$prefeitura->id})");

        $arbitro = User::firstOrCreate(
            ['email' => 'arbitro@teste.com'],
            [
                'name' => 'Árbitro de Teste',
                'password' => Hash::make('12345678'),
                'perfil' => 'ARBITRO',
                'cpf' => '00000000099',
                'status' => 'ATIVO',
                'prefeitura_id' => $prefeitura->id,
            ]
        );

        if ($arbitro->prefeitura_id !== $prefeitura->id) {
            $arbitro->update(['prefeitura_id' => $prefeitura->id]);
        }

        $this->command->info("Árbitro configurado: {$arbitro->name} (ID: {$arbitro->id})");

        $modalidade = Modalidade::firstOrCreate(
            [
                'nome' => 'Futebol de Campo',
                'prefeitura_id' => $prefeitura->id
            ]
        );

        $campeonato = Campeonato::updateOrCreate(
            [
                'prefeitura_id' => $prefeitura->id,
                'nome' => 'Copa Súmula Digital - Prefeitura 1',
            ],
            [
                'modalidade_id' => $modalidade->id,
                'categoria' => 'Adulto',
                'genero' => 'masculino',
                'status' => 'em_andamento',
                'tipo_competicao' => 'equipe',
                'formato' => 'pontos_corridos',
                'data_inicio' => Carbon::now()->subDays(10),
                'data_fim' => Carbon::now()->addDays(20),
            ]
        );
        $this->command->info("Campeonato criado: {$campeonato->nome}");

        $equipeA = Equipe::updateOrCreate(
            ['campeonato_id' => $campeonato->id, 'nome' => 'Tigres FC'],
            [
                'prefeitura_id' => $prefeitura->id,
                'status' => 'aprovada',
                'status_inscricao' => 'aprovada',
            ]
        );

        $equipeB = Equipe::updateOrCreate(
            ['campeonato_id' => $campeonato->id, 'nome' => 'Leões da Vila'],
            [
                'prefeitura_id' => $prefeitura->id,
                'status' => 'aprovada',
                'status_inscricao' => 'aprovada',
            ]
        );

        $fase = Fase::updateOrCreate(
            ['campeonato_id' => $campeonato->id, 'ordem' => 1],
            [
                'nome' => 'Fase Única',
                'tipo' => 'pontos_corridos',
                'status' => 'ativa',
            ]
        );

        $now = Carbon::now();
        $garantirElencoCompleto = function (Equipe $equipe, string $prefixoCpf) use ($prefeitura, $arbitro, $now) {
            if (!$equipe->campeonato_id) {
                return;
            }

            $prefixoNormalizado = substr(preg_replace('/\D/', '', $prefixoCpf) . '000000000', 0, 9);
            $nomeBase = preg_replace('/\s+/', ' ', trim($equipe->nome));

            for ($i = 1; $i <= 11; $i++) {
                $indice = str_pad((string) $i, 2, '0', STR_PAD_LEFT);
                $cpf = $prefixoNormalizado . $indice;

                $atleta = Atleta::updateOrCreate(
                    ['cpf' => $cpf],
                    [
                        'prefeitura_id' => $prefeitura->id,
                        'nome' => "{$nomeBase} Jogador {$i}",
                        'data_nascimento' => '2000-01-01',
                        'sexo' => 'M',
                        'nome_mae' => 'Mãe Teste',
                        'nome_pai' => 'Pai Teste',
                        'titulo_eleitor' => substr('55555555' . $cpf, 0, 10),
                        'zona_eleitoral' => '001',
                        'secao_eleitoral' => '0001',
                        'local_votacao' => 'Escola Municipal',
                        'endereco_eleitoral' => 'Rua Central',
                        'numero_eleitoral' => (string) (100 + $i),
                        'bairro_eleitoral' => 'Centro',
                        'cidade_eleitoral' => $prefeitura->nome,
                        'uf_eleitoral' => 'SP',
                        'cep' => '01001000',
                        'endereco' => 'Rua dos Atletas',
                        'numero' => (string) $i,
                        'bairro' => 'Centro',
                        'cidade' => 'Cidade Teste',
                        'estado' => 'SP',
                        'telefone' => '1199000' . str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                        'email' => strtolower('j' . $cpf . '@teste.com'),
                        'acesso_ativo' => true,
                        'pode_participar_competicoes' => true,
                    ]
                );

                Inscricao::updateOrCreate(
                    ['campeonato_id' => $equipe->campeonato_id, 'atleta_id' => $atleta->id],
                    [
                        'prefeitura_id' => $prefeitura->id,
                        'status' => 'confirmada',
                        'status_pagamento' => 'isento',
                        'data_inscricao' => $now,
                    ]
                );

                if ($equipe->atletas()->where('atletas.id', $atleta->id)->exists()) {
                    $equipe->atletas()->updateExistingPivot($atleta->id, [
                        'status' => 'aprovado',
                        'aprovado_por' => $arbitro->id,
                        'aprovado_em' => $now,
                        'updated_at' => $now,
                    ]);
                } else {
                    $equipe->atletas()->attach($atleta->id, [
                        'status' => 'aprovado',
                        'aprovado_por' => $arbitro->id,
                        'aprovado_em' => $now,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        };

        $garantirElencoCompleto($equipeA, '910000000');
        $garantirElencoCompleto($equipeB, '920000000');

        $partida = Partida::updateOrCreate(
            [
                'fase_id' => $fase->id,
                'mandante_id' => $equipeA->id,
                'visitante_id' => $equipeB->id,
                'rodada' => 1,
            ],
            [
                'prefeitura_id' => $prefeitura->id,
                'arbitro_id' => $arbitro->id,
                'data_jogo' => Carbon::now()->addHours(2),
                'local' => 'Estádio Municipal',
                'status' => 'agendada',
                'placar_mandante' => 0,
                'placar_visitante' => 0,
                'definido_manualmente' => true,
                'origem_mandante_type' => 'MANUAL',
                'origem_visitante_type' => 'MANUAL',
            ]
        );

        $partidasDoArbitro = Partida::where('prefeitura_id', $prefeitura->id)
            ->where('arbitro_id', $arbitro->id)
            ->whereIn('status', ['agendada', 'em_andamento'])
            ->get();

        $equipesIds = $partidasDoArbitro
            ->flatMap(fn ($p) => [$p->mandante_id, $p->visitante_id])
            ->filter()
            ->unique()
            ->values();

        $equipesDasPartidas = Equipe::whereIn('id', $equipesIds)->get();
        foreach ($equipesDasPartidas as $equipePartida) {
            $prefixoEquipe = '93' . str_pad((string) $equipePartida->id, 3, '0', STR_PAD_LEFT) . '0000';
            $garantirElencoCompleto($equipePartida, $prefixoEquipe);
        }

        $this->command->info("Partida criada com sucesso! ID: {$partida->id}");
        $this->command->info("Mandante: {$equipeA->nome} vs Visitante: {$equipeB->nome}");
        $this->command->info('Atletas aprovados por equipe: 11');
        $this->command->info("Árbitro Vinculado: {$arbitro->name}");
        $this->command->info("------------------------------------------------");
        $this->command->info("Para testar, faça login com:");
        $this->command->info("Email: arbitro@teste.com");
        $this->command->info("Senha: 12345678");
    }
}
