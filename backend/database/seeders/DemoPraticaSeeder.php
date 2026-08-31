<?php

namespace Database\Seeders;

use App\Models\Atleta;
use App\Models\Campeonato;
use App\Models\Equipe;
use App\Models\Fase;
use App\Models\Modalidade;
use App\Models\Partida;
use App\Models\Prefeitura;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoPraticaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $targetPrefeituraId = (int) env('SEED_PREFEITURA_ID', 0);
        $prefeitura = $targetPrefeituraId > 0
            ? Prefeitura::find($targetPrefeituraId)
            : null;

        if (!$prefeitura) {
            $prefeitura = Prefeitura::firstOrCreate(
                ['slug' => 'pratica'],
                [
                    'nome' => 'Prefeitura de Prática',
                    'dominio' => 'esporte.pratica.sp.gov.br',
                    'cor_primaria' => '#10b981',
                    'ativo' => true,
                ]
            );
        }

        // Definir contexto para TenantScope
        App::instance('current_tenant', $prefeitura);

        // 2. Criar Usuário Admin
        $user = User::firstOrCreate(
            ['email' => 'admin@pratica.com'],
            [
                'name' => 'Admin Prática',
                'cpf' => '99999999999',
                'password' => Hash::make('123456'),
                'perfil' => 'ADMIN',
                'status' => 'ATIVO',
                'prefeitura_id' => $prefeitura->id,
            ]
        );

        $this->command->info("Usuário criado: admin@pratica.com / 123456");

        // 3. Criar Modalidades
        $futebol = Modalidade::firstOrCreate(
            ['nome' => 'Futebol de Campo', 'prefeitura_id' => $prefeitura->id],
            ['tipo' => 'coletivo', 'descricao' => 'Futebol 11x11', 'status' => true]
        );

        $futsal = Modalidade::firstOrCreate(
            ['nome' => 'Futsal', 'prefeitura_id' => $prefeitura->id],
            ['tipo' => 'coletivo', 'descricao' => 'Futebol de Salão', 'status' => true]
        );

        // 4. Criar Campeonato (Copa Prática 2026)
        $campeonato = Campeonato::firstOrCreate(
            ['nome' => 'Copa Prática 2026', 'prefeitura_id' => $prefeitura->id],
            [
                'modalidade_id' => $futebol->id,
                'tipo_competicao' => 'equipe',
                'genero' => 'masculino',
                'categoria' => 'Adulto',
                'data_inicio' => Carbon::now()->startOfYear(),
                'data_fim' => Carbon::now()->endOfYear(),
                'status' => 'em_andamento',
                'formato' => 'grupos',
                // 'descricao' => 'Campeonato demonstrativo para prática do sistema.', // Removido
            ]
        );

        // 5. Criar Equipes e Atletas
        $times = [
            'Real Prática' => ['cor' => 'Branco', 'sigla' => 'RPR'],
            'Inter de Prática' => ['cor' => 'Vermelho', 'sigla' => 'INT'],
            'Prática United' => ['cor' => 'Azul', 'sigla' => 'PRU'],
            'Atlético Prática' => ['cor' => 'Preto', 'sigla' => 'ATL'],
        ];

        $equipesObjs = [];

        foreach ($times as $nomeTime => $info) {
            $equipe = Equipe::firstOrCreate(
                ['nome' => $nomeTime, 'campeonato_id' => $campeonato->id],
                [
                    'prefeitura_id' => $prefeitura->id,
                    'responsavel' => 'Responsável ' . $info['sigla'],
                    'contato' => '11999999999',
                ]
            );
            $equipesObjs[$nomeTime] = $equipe;

            // Criar 15 Atletas para cada time
            for ($i = 1; $i <= 15; $i++) {
                $cpf = str_pad(rand(0, 99999999999), 11, '0', STR_PAD_LEFT);
                $atleta = Atleta::firstOrCreate(
                    ['cpf' => $cpf],
                    [
                        'nome' => "Jogador $i ($info[sigla])",
                        'prefeitura_id' => $prefeitura->id,
                        'data_nascimento' => '2000-01-01',
                        'nome_mae' => 'Mãe do Atleta',
                        'titulo_eleitor' => '1234567890',
                        'cep' => '18000-000',
                        'endereco' => 'Rua de Teste',
                        'bairro' => 'Centro',
                        'cidade' => 'Sorocaba',
                        'estado' => 'SP',
                        'sexo' => 'masculino',
                    ]
                );

                // Vincular atleta à equipe (tabela pivot: equipe_atleta)
                $exists = DB::table('equipe_atleta')
                    ->where('atleta_id', $atleta->id)
                    ->where('equipe_id', $equipe->id)
                    ->exists();

                if (!$exists) {
                    DB::table('equipe_atleta')->insert([
                        'atleta_id' => $atleta->id,
                        'equipe_id' => $equipe->id,
                        'status' => 'aprovado',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        // 6. Criar Fase de Grupos
        $faseGrupos = Fase::firstOrCreate(
            ['campeonato_id' => $campeonato->id, 'ordem' => 1],
            [
                'nome' => 'Fase de Grupos',
                'tipo' => 'grupos',
                'status' => 'ativa',
                'configuracao' => json_encode(['criterios' => ['pontos', 'vitorias', 'saldo_gols', 'gols_pro']]),
            ]
        );

        // Criar Grupo A
        $grupoA = $faseGrupos->grupos()->firstOrCreate(
            ['nome' => 'Grupo A'],
            ['ordem' => 1]
        );

        // 7. Criar Partidas
        // Jogo 1: Real vs Inter (Finalizado)
        Partida::firstOrCreate(
            [
                'fase_id' => $faseGrupos->id,
                'mandante_id' => $equipesObjs['Real Prática']->id,
                'visitante_id' => $equipesObjs['Inter de Prática']->id,
            ],
            [
                'prefeitura_id' => $prefeitura->id,
                'fase_grupo_id' => $grupoA->id,
                'rodada' => 1,
                'status' => 'finalizada',
                'placar_mandante' => 2,
                'placar_visitante' => 1,
                'vencedor_id' => $equipesObjs['Real Prática']->id,
                'data_jogo' => Carbon::yesterday()->setHour(16),
                'local' => 'Estádio Municipal',
            ]
        );

        // Jogo 2: United vs Atlético (Agendada - Para testar súmula)
        Partida::firstOrCreate(
            [
                'fase_id' => $faseGrupos->id,
                'mandante_id' => $equipesObjs['Prática United']->id,
                'visitante_id' => $equipesObjs['Atlético Prática']->id,
            ],
            [
                'prefeitura_id' => $prefeitura->id,
                'fase_grupo_id' => $grupoA->id,
                'rodada' => 1,
                'status' => 'agendada', // Agendada para permitir edição
                'placar_mandante' => null,
                'placar_visitante' => null,
                'data_jogo' => Carbon::tomorrow()->setHour(14),
                'local' => 'Campo do Centro',
            ]
        );

        // 8. Criar Fase Mata-Mata (Vazia, para praticar automação)
        $faseFinal = Fase::firstOrCreate(
            ['campeonato_id' => $campeonato->id, 'ordem' => 2],
            [
                'nome' => 'Final',
                'tipo' => 'mata_mata',
                'status' => 'planejada',
            ]
        );

        // Criar confronto da Final esperando classificados
        Partida::firstOrCreate(
            [
                'fase_id' => $faseFinal->id,
                'chave' => 'Final',
            ],
            [
                'prefeitura_id' => $prefeitura->id,
                'status' => 'agendada',
                'origem_mandante_type' => 'GROUP_POSITION',
                'origem_mandante_payload' => json_encode(['group' => 'Grupo A', 'position' => 1]),
                'origem_visitante_type' => 'GROUP_POSITION',
                'origem_visitante_payload' => json_encode(['group' => 'Grupo A', 'position' => 2]),
                'definido_manualmente' => false,
            ]
        );

        $this->command->info("Ambiente de Prática criado com sucesso!");
        $this->command->info("Acesse com: admin@pratica.com / 123456");
    }
}
