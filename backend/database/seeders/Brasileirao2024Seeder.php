<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Prefeitura;
use App\Models\Modalidade;
use App\Models\Temporada;
use App\Models\Campeonato;
use App\Models\Entidade;
use App\Models\Equipe;
use App\Models\Atleta;
use App\Models\Fase;
use App\Models\Partida;
use App\Models\EventoPartida;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class Brasileirao2024Seeder extends Seeder
{
    public function run()
    {
        $prefeituraId = (int) env('SEED_PREFEITURA_ID', 34);
        $prefeitura = Prefeitura::find($prefeituraId);

        if (!$prefeitura) {
            $this->command->error("Prefeitura ID $prefeituraId não encontrada!");
            return;
        }

        $this->command->info("Semeando dados para: " . $prefeitura->nome);
        $faker = Faker::create('pt_BR');

        // 1. Modalidades
        $modalidadeFutebol = Modalidade::firstOrCreate(
            ['nome' => 'Futebol', 'prefeitura_id' => $prefeituraId],
            ['descricao' => 'Futebol de Campo', 'tipo' => 'coletivo']
        );
        
        $modalidadeFutsal = Modalidade::firstOrCreate(
            ['nome' => 'Futsal', 'prefeitura_id' => $prefeituraId],
            ['descricao' => 'Futebol de Salão', 'tipo' => 'coletivo']
        );

        $modalidadeVolei = Modalidade::firstOrCreate(
            ['nome' => 'Vôlei', 'prefeitura_id' => $prefeituraId],
            ['descricao' => 'Voleibol de Quadra', 'tipo' => 'coletivo']
        );
        
        $modalidadeCorrida = Modalidade::firstOrCreate(
            ['nome' => 'Corrida', 'prefeitura_id' => $prefeituraId],
            ['descricao' => 'Atletismo / Corrida de Rua', 'tipo' => 'individual']
        );

        // 2. Temporada
        $temporada = Temporada::firstOrCreate(
            ['nome' => '2024', 'prefeitura_id' => $prefeituraId],
            ['ano' => 2024, 'status' => 'ativa']
        );

        // 3. Campeonatos Atuais (2024)
        
        // 3.1 Brasileirão 2024 (Futebol)
        $campeonato = Campeonato::firstOrCreate(
            ['nome' => 'Brasileirão 2024', 'prefeitura_id' => $prefeituraId, 'temporada_id' => $temporada->id],
            [
                'modalidade_id' => $modalidadeFutebol->id,
                'data_inicio' => '2024-04-14',
                'data_fim' => '2024-12-08',
                'local' => 'Estádios Municipais',
                'status' => 'em_andamento',
                'formato' => 'pontos_corridos',
                'cidade_eleitoral' => 'São Francisco',
                'categoria' => 'Profissional',
                'genero' => 'masculino',
                'tipo_competicao' => 'equipe',
                'valor_inscricao' => 0,
                'regulamento' => 'Regulamento oficial do Brasileirão 2024'
            ]
        );

        // 3.2 Copa Municipal de Futsal 2024
        $copaFutsal = Campeonato::firstOrCreate(
            ['nome' => 'Copa Municipal de Futsal 2024', 'prefeitura_id' => $prefeituraId, 'temporada_id' => $temporada->id],
            [
                'modalidade_id' => $modalidadeFutsal->id,
                'data_inicio' => '2024-03-10',
                'data_fim' => '2024-06-20',
                'local' => 'Ginásio Poliesportivo',
                'status' => 'em_andamento',
                'formato' => 'mata_mata',
                'cidade_eleitoral' => 'São Francisco',
                'categoria' => 'Amador',
                'genero' => 'misto',
                'tipo_competicao' => 'equipe',
            ]
        );

        // 3.3 Circuito de Corrida de Rua 2024
        $circuitoCorrida = Campeonato::firstOrCreate(
            ['nome' => 'Circuito de Corrida de Rua 2024', 'prefeitura_id' => $prefeituraId, 'temporada_id' => $temporada->id],
            [
                'modalidade_id' => $modalidadeCorrida->id,
                'data_inicio' => '2024-05-01',
                'data_fim' => '2024-05-01',
                'local' => 'Orla da Cidade',
                'formato' => 'corrida',
                'cidade_eleitoral' => 'São Francisco',
                'categoria' => 'Livre',
                'genero' => 'misto',
                'tipo_competicao' => 'individual',
            ]
        );

        // Criar temporadas passadas (2023, 2022) para histórico
        $temporada2023 = Temporada::firstOrCreate(
            ['nome' => '2023', 'prefeitura_id' => $prefeituraId],
            ['ano' => 2023, 'status' => 'encerrada']
        );
        
        $temporada2022 = Temporada::firstOrCreate(
            ['nome' => '2022', 'prefeitura_id' => $prefeituraId],
            ['ano' => 2022, 'status' => 'encerrada']
        );

        // Criar campeonatos passados
        $campeonato2023 = Campeonato::firstOrCreate(
            ['nome' => 'Brasileirão 2023', 'prefeitura_id' => $prefeituraId, 'temporada_id' => $temporada2023->id],
            [
                'modalidade_id' => $modalidadeFutebol->id,
                'data_inicio' => '2023-04-15',
                'data_fim' => '2023-12-06',
                'local' => 'Estádios Municipais',
                'status' => 'finalizado',
                'formato' => 'pontos_corridos',
                'cidade_eleitoral' => 'São Francisco',
                'categoria' => 'Profissional',
                'genero' => 'masculino',
                'tipo_competicao' => 'equipe',
            ]
        );

        $campeonato2022 = Campeonato::firstOrCreate(
            ['nome' => 'Brasileirão 2022', 'prefeitura_id' => $prefeituraId, 'temporada_id' => $temporada2022->id],
            [
                'modalidade_id' => $modalidadeFutebol->id,
                'data_inicio' => '2022-04-10',
                'data_fim' => '2022-11-13',
                'local' => 'Estádios Municipais',
                'status' => 'finalizado',
                'formato' => 'pontos_corridos',
                'cidade_eleitoral' => 'São Francisco',
                'categoria' => 'Profissional',
                'genero' => 'masculino',
                'tipo_competicao' => 'equipe',
            ]
        );

        $copaVolei2023 = Campeonato::firstOrCreate(
            ['nome' => 'Copa de Vôlei 2023', 'prefeitura_id' => $prefeituraId, 'temporada_id' => $temporada2023->id],
            [
                'modalidade_id' => $modalidadeVolei->id,
                'data_inicio' => '2023-06-10',
                'data_fim' => '2023-07-20',
                'local' => 'Ginásio Municipal',
                'status' => 'finalizado',
                'formato' => 'mata_mata',
                'categoria' => 'Amador',
                'genero' => 'misto',
            ]
        );

        // 4. Times (Entidades e Equipes)
        $timesData = [
            ['nome' => 'Flamengo', 'cidade' => 'Rio de Janeiro', 'sigla' => 'FLA'],
            ['nome' => 'Palmeiras', 'cidade' => 'São Paulo', 'sigla' => 'PAL'],
            ['nome' => 'Botafogo', 'cidade' => 'Rio de Janeiro', 'sigla' => 'BOT'],
            ['nome' => 'São Paulo', 'cidade' => 'São Paulo', 'sigla' => 'SAO'],
            ['nome' => 'Grêmio', 'cidade' => 'Porto Alegre', 'sigla' => 'GRE'],
            ['nome' => 'Atlético-MG', 'cidade' => 'Belo Horizonte', 'sigla' => 'CAM'],
            ['nome' => 'Fluminense', 'cidade' => 'Rio de Janeiro', 'sigla' => 'FLU'],
            ['nome' => 'Internacional', 'cidade' => 'Porto Alegre', 'sigla' => 'INT'],
            ['nome' => 'Fortaleza', 'cidade' => 'Fortaleza', 'sigla' => 'FOR'],
            ['nome' => 'Bahia', 'cidade' => 'Salvador', 'sigla' => 'BAH'],
            ['nome' => 'Cruzeiro', 'cidade' => 'Belo Horizonte', 'sigla' => 'CRU'],
            ['nome' => 'Vasco', 'cidade' => 'Rio de Janeiro', 'sigla' => 'VAS'],
            ['nome' => 'Corinthians', 'cidade' => 'São Paulo', 'sigla' => 'COR'],
            ['nome' => 'Athletico-PR', 'cidade' => 'Curitiba', 'sigla' => 'CAP'],
            ['nome' => 'Cuiabá', 'cidade' => 'Cuiabá', 'sigla' => 'CUI'],
            ['nome' => 'Criciúma', 'cidade' => 'Criciúma', 'sigla' => 'CRI'],
            ['nome' => 'Juventude', 'cidade' => 'Caxias do Sul', 'sigla' => 'JUV'],
            ['nome' => 'Bragantino', 'cidade' => 'Bragança Paulista', 'sigla' => 'RBB'],
            ['nome' => 'Vitória', 'cidade' => 'Salvador', 'sigla' => 'VIT'],
            ['nome' => 'Atlético-GO', 'cidade' => 'Goiânia', 'sigla' => 'ACG'],
        ];

        $equipes = [];

        foreach ($timesData as $time) {
            $entidade = Entidade::firstOrCreate(
                ['nome' => $time['nome'], 'prefeitura_id' => $prefeituraId],
                ['sigla' => $time['sigla'], 'cidade' => $time['cidade'], 'data_fundacao' => '1900-01-01']
            );

            $equipe = Equipe::firstOrCreate(
                ['nome' => $time['nome'], 'campeonato_id' => $campeonato->id],
                ['prefeitura_id' => $prefeituraId, 'entidade_id' => $entidade->id, 'status' => 'aprovado']
            );

            $equipes[] = $equipe;

            // Criar histórico para a equipe (2023, 2022)
            $colocacao2023 = rand(1, 20);
                $equipe2023 = Equipe::firstOrCreate(
                    ['nome' => $time['nome'], 'campeonato_id' => $campeonato2023->id],
                    [
                        'prefeitura_id' => $prefeituraId, 
                        'entidade_id' => $entidade->id, 
                        'status' => 'aprovado',
                        'colocacao' => $colocacao2023
                    ]
                );
    
                $colocacao2022 = rand(1, 20);
                $equipe2022 = Equipe::firstOrCreate(
                    ['nome' => $time['nome'], 'campeonato_id' => $campeonato2022->id],
                    [
                        'prefeitura_id' => $prefeituraId, 
                        'entidade_id' => $entidade->id, 
                        'status' => 'aprovado',
                        'colocacao' => $colocacao2022
                    ]
                );

            // Criar Jogadores (20 por time para ter reservas)
            if ($equipe->atletas()->count() < 20) {
                for ($i = 1; $i <= 20; $i++) {
                    $atleta = Atleta::create([
                        'prefeitura_id' => $prefeituraId,
                        'nome' => $faker->name('male'),
                        'cpf' => $faker->unique()->cpf(false), // Sem pontuação
                        'data_nascimento' => $faker->dateTimeBetween('-35 years', '-18 years')->format('Y-m-d'),
                        'sexo' => 'M',
                        'nome_mae' => $faker->name('female'),
                        'nome_pai' => $faker->name('male'),
                        'titulo_eleitor' => $faker->numerify('##########'),
                        'zona_eleitoral' => $faker->numerify('###'),
                        'secao_eleitoral' => $faker->numerify('####'),
                        'cidade_eleitoral' => 'São Francisco',
                        'uf_eleitoral' => 'SP',
                        'cep' => $faker->numerify('########'),
                        'endereco' => $faker->streetName,
                        'numero' => $faker->buildingNumber,
                        'bairro' => $faker->city, // Faker doesn't have bairro, city works
                        'cidade' => 'São Francisco',
                        'estado' => 'SP',
                    ]);

                    // Vincular Atleta à Equipe atual (2024)
                    $equipe->atletas()->syncWithoutDetaching([$atleta->id => ['status' => 'aprovado']]);

                    // Adicionar atletas a outras modalidades (Multi-esportistas)
                    // Aumentei a chance para 80% para garantir que tenhamos atletas multi-esportes para teste
                    if (rand(1, 100) <= 80) { 
                         // Buscar ou criar equipe de Futsal para a mesma entidade
                         $equipeFutsal = Equipe::firstOrCreate(
                            ['nome' => $time['nome'] . ' Futsal', 'campeonato_id' => $copaFutsal->id],
                            [
                                'prefeitura_id' => $prefeituraId, 
                                'entidade_id' => $entidade->id, 
                                'status' => 'aprovado'
                            ]
                         );
                         $equipeFutsal->atletas()->syncWithoutDetaching([$atleta->id => ['status' => 'aprovado']]);
                    }

                    if (rand(1, 100) <= 60) { // Aumentei para 60%
                         // Corrida é individual, usar tabela inscricoes
                         // Primeiro verificar se já existe inscrição
                         $inscricaoCorrida = \App\Models\Inscricao::firstOrCreate(
                             ['atleta_id' => $atleta->id, 'campeonato_id' => $circuitoCorrida->id],
                             [
                                 'prefeitura_id' => $prefeituraId,
                                 'status' => 'confirmada',
                                 'data_inscricao' => now(),
                                 'colocacao' => rand(1, 100) // Simular posição
                             ]
                         );
                    }
                    
                    if (rand(1, 100) <= 70) { // Aumentei para 70%
                         $equipeVolei2023 = Equipe::firstOrCreate(
                            ['nome' => $time['nome'] . ' Vôlei', 'campeonato_id' => $copaVolei2023->id],
                            [
                                'prefeitura_id' => $prefeituraId, 
                                'entidade_id' => $entidade->id, 
                                'status' => 'aprovado',
                                'colocacao' => rand(1, 8)
                            ]
                         );
                         $equipeVolei2023->atletas()->syncWithoutDetaching([$atleta->id => ['status' => 'aprovado']]);
                    }

                    // Vincular Atleta à Equipes Passadas (Histórico)
                    // 90% de chance de ter jogado em 2023
                    if (rand(1, 100) <= 90) {
                        $equipe2023->atletas()->syncWithoutDetaching([$atleta->id => ['status' => 'aprovado']]);
                    }

                    // 80% de chance de ter jogado em 2022
                    if (rand(1, 100) <= 80) {
                        $equipe2022->atletas()->syncWithoutDetaching([$atleta->id => ['status' => 'aprovado']]);
                    }
                }
            }
        }

        // 5. Fase
        $fase = Fase::updateOrCreate(
            ['campeonato_id' => $campeonato->id, 'nome' => 'Turno Único'],
            ['tipo' => 'pontos_corridos', 'ordem' => 1, 'status' => 'ativa']
        );

        // 6. Partidas (Gerar algumas rodadas)
        if ($fase->partidas()->count() == 0) {
            $this->command->info("Gerando partidas...");
            
            $numRodadas = 10;
            $dataJogo = Carbon::parse('2024-04-14');
            
            // Para simplificar, vamos sortear confrontos a cada rodada sem repetir na mesma rodada
            // mas permitindo repetir ao longo do campeonato (não é round-robin perfeito, mas serve)
            
            for ($rodada = 1; $rodada <= $numRodadas; $rodada++) {
                $shuffledEquipes = collect($equipes)->shuffle();
                
                while ($shuffledEquipes->count() >= 2) {
                    $mandante = $shuffledEquipes->shift();
                    $visitante = $shuffledEquipes->shift();

                    // Decidir status da partida
                    // Rodadas 1-8 finalizadas, 9-10 agendada/em andamento
                    $status = $rodada <= 8 ? 'finalizada' : 'agendada';
                    
                    $placarMandante = $status == 'finalizada' ? rand(0, 4) : null;
                    $placarVisitante = $status == 'finalizada' ? rand(0, 3) : null;

                    $partida = Partida::create([
                        'prefeitura_id' => $prefeituraId,
                        'fase_id' => $fase->id,
                        'mandante_id' => $mandante->id,
                        'visitante_id' => $visitante->id,
                        'rodada' => $rodada,
                        'data_jogo' => $dataJogo->copy()->addHours(16), // Jogos às 16h
                        'local' => 'Estádio ' . $mandante->entidade->cidade,
                        'status' => $status,
                        'placar_mandante' => $placarMandante,
                        'placar_visitante' => $placarVisitante
                    ]);

                    // Se finalizada, gerar eventos (gols e cartões)
                    if ($status == 'finalizada') {
                    // Carregar jogadores
                    $jogadoresMandante = $mandante->atletas()->wherePivot('status', 'aprovado')->get();
                    $jogadoresVisitante = $visitante->atletas()->wherePivot('status', 'aprovado')->get();

                    // Gols Mandante
                    for ($g = 0; $g < $placarMandante; $g++) {
                        if ($jogadoresMandante->count() > 0) {
                            EventoPartida::create([
                                'partida_id' => $partida->id,
                                'jogador_id' => $jogadoresMandante->random()->id,
                                'time_id' => $mandante->id,
                                'tipo_evento' => 'GOL',
                                'minuto' => rand(1, 90)
                            ]);
                        }
                    }

                    // Gols Visitante
                    for ($g = 0; $g < $placarVisitante; $g++) {
                        if ($jogadoresVisitante->count() > 0) {
                            EventoPartida::create([
                                'partida_id' => $partida->id,
                                'jogador_id' => $jogadoresVisitante->random()->id,
                                'time_id' => $visitante->id,
                                'tipo_evento' => 'GOL',
                                'minuto' => rand(1, 90)
                            ]);
                        }
                    }

                    // Cartões Aleatórios
                    if (rand(1, 100) <= 70) {
                        $numCartoes = rand(1, 4);
                        for ($c = 0; $c < $numCartoes; $c++) {
                            // Escolher time
                            $isMandante = rand(0, 1);
                            $timeCartao = $isMandante ? $mandante : $visitante;
                            $jogadores = $isMandante ? $jogadoresMandante : $jogadoresVisitante;
                            
                            if ($jogadores->count() > 0) {
                                $tipo = rand(1, 100) <= 90 ? 'CARTAO_AMARELO' : 'CARTAO_VERMELHO';
                                EventoPartida::create([
                                    'partida_id' => $partida->id,
                                    'jogador_id' => $jogadores->random()->id,
                                    'time_id' => $timeCartao->id,
                                    'tipo_evento' => $tipo,
                                    'minuto' => rand(1, 90)
                                ]);
                            }
                        }
                    }
                }
            }
            
            $dataJogo->addWeek();
        }
        }
        
        // Simular alguns resultados para Copa Futsal (Em andamento)
        $faseFutsal = Fase::firstOrCreate(
            ['campeonato_id' => $copaFutsal->id, 'nome' => 'Fase de Grupos'],
            ['tipo' => 'grupos', 'ordem' => 1, 'status' => 'ativa']
        );
        
        // Simular alguns resultados para Copa Vôlei 2023 (Finalizada)
        $faseVolei = Fase::firstOrCreate(
            ['campeonato_id' => $copaVolei2023->id, 'nome' => 'Playoffs'],
            ['tipo' => 'mata_mata', 'ordem' => 1, 'status' => 'encerrada']
        );

        $this->command->info("Brasileirão 2024 e histórico expandido criado com sucesso!");
    }
}
