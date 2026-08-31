<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Campeonato;
use App\Models\Equipe;
use App\Models\Modalidade;
use App\Models\Partida;
use App\Models\Atleta;
use App\Models\Inscricao;
use App\Services\TabelaService;
use Illuminate\Support\Facades\DB;

class TabelaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Certificar que TabelaService está disponível
        $service = app(TabelaService::class);

        // -----------------------------------------------------------------------------------------
        // 0. PREPARAÇÃO DE DADOS BÁSICOS
        // -----------------------------------------------------------------------------------------
        $modFutebol = Modalidade::firstOrCreate(['nome' => 'Futebol'], ['nome' => 'Futebol']);
        $modTenis = Modalidade::firstOrCreate(['nome' => 'Tênis'], ['nome' => 'Tênis']);
        $modVolei = Modalidade::firstOrCreate(['nome' => 'Vôlei'], ['nome' => 'Vôlei']);

        // Criar um pool de atletas se não existirem muitos
        if (Atleta::count() < 100) {
            $this->command->info("Criando 100 atletas para popular o sistema...");
            Atleta::factory()->count(100)->create();
        }
        
        $todosAtletas = Atleta::all();

        // -----------------------------------------------------------------------------------------
        // CENÁRIO 1: MATA-MATA (Copa Municipal - Equipes) - 8 Times
        // -----------------------------------------------------------------------------------------
        $this->command->info("--- Criando Copa Municipal (Mata-Mata Equipes) ---");
        
        $campMataMata = Campeonato::create([
            'nome' => 'Copa Municipal de Futebol 2026',
            'data_inicio' => now(),
            'data_fim' => now()->addMonth(2),
            'tipo_competicao' => 'equipe',
            'modalidade_id' => $modFutebol->id,
            'status' => 'inscricoes_abertas',
            'categoria' => 'Amador',
            'genero' => 'masculino',
            'cidade_eleitoral' => 'Exemplo City',
            'bairro_eleitoral' => 'Centro',
            'min_atletas_equipe' => 11,
            'max_atletas_equipe' => 22
        ]);

        $timesMataMata = ['Flamengo', 'Vasco', 'Botafogo', 'Fluminense', 'Corinthians', 'Palmeiras', 'São Paulo', 'Santos'];
        
        foreach ($timesMataMata as $index => $nome) {
            $equipe = Equipe::create([
                'campeonato_id' => $campMataMata->id,
                'nome' => $nome,
                'responsavel' => "Técnico $nome",
                'contato' => "9999-000$index"
            ]);

            // Vincular 15 atletas aleatórios para cada equipe
            $atletasEquipe = $todosAtletas->random(15);
            $equipe->atletas()->attach($atletasEquipe->pluck('id'));
        }
        $this->command->info("8 Equipes criadas com 15 atletas cada.");

        // Gerar Tabela Mata-Mata
        $service->gerarTabela($campMataMata, 'mata_mata');
        $this->command->info("Tabela gerada.");

        // Simular Quartas de Final (Fase 1)
        $jogosQuartas = Partida::whereHas('fase', function($q) use ($campMataMata) {
            $q->where('campeonato_id', $campMataMata->id)->where('ordem', 1);
        })->get();

        if ($jogosQuartas->count() >= 2) {
            $service->atualizarPlacar($jogosQuartas[0], 3, 1);
            $service->atualizarPlacar($jogosQuartas[1], 0, 2);
            $this->command->info("Jogos simulados. Vencedores avançaram.");
        }

        // -----------------------------------------------------------------------------------------
        // CENÁRIO 2: FASE DE GRUPOS (Campeonato Regional - Equipes) - 12 Times
        // -----------------------------------------------------------------------------------------
        $this->command->info("--- Criando Campeonato Regional (Grupos Equipes) ---");

        $campGrupos = Campeonato::create([
            'nome' => 'Campeonato Regional Sub-20',
            'data_inicio' => now()->addDays(5),
            'data_fim' => now()->addMonth(3),
            'tipo_competicao' => 'equipe',
            'modalidade_id' => $modFutebol->id,
            'status' => 'inscricoes_abertas',
            'categoria' => 'Sub-20',
            'genero' => 'masculino',
            'cidade_eleitoral' => 'Exemplo City',
            'bairro_eleitoral' => 'Norte'
        ]);

        $timesGrupos = [
            'Real Madrid', 'Barcelona', 'Atlético Madrid', 'Sevilla',
            'Manchester City', 'Liverpool', 'Arsenal', 'Chelsea',
            'Bayern', 'Dortmund', 'Leverkusen', 'RB Leipzig'
        ];
        
        foreach ($timesGrupos as $nome) {
            $equipe = Equipe::create([
                'campeonato_id' => $campGrupos->id,
                'nome' => $nome,
                'responsavel' => "Resp. $nome"
            ]);
            
            // Vincular atletas
            $atletasEquipe = $todosAtletas->random(11);
            $equipe->atletas()->attach($atletasEquipe->pluck('id'));
        }

        $service->gerarTabela($campGrupos, 'grupos');
        
        // Simular jogos
        $jogosGrupos = Partida::whereHas('fase', function($q) use ($campGrupos) {
            $q->where('campeonato_id', $campGrupos->id);
        })->take(6)->get();

        foreach ($jogosGrupos as $jogo) {
            $service->atualizarPlacar($jogo, rand(0, 4), rand(0, 4));
        }
        $this->command->info("Tabela de Grupos gerada e jogos simulados.");

        // -----------------------------------------------------------------------------------------
        // CENÁRIO 3: INDIVIDUAL MATA-MATA (Torneio de Tênis) - 16 Atletas
        // -----------------------------------------------------------------------------------------
        $this->command->info("--- Criando Torneio de Tênis (Individual Mata-Mata) ---");

        $campTenis = Campeonato::create([
            'nome' => 'Open de Tênis 2026',
            'data_inicio' => now()->addDays(2),
            'data_fim' => now()->addDays(15),
            'tipo_competicao' => 'individual', // Importante
            'modalidade_id' => $modTenis->id,
            'status' => 'inscricoes_abertas',
            'categoria' => 'Livre',
            'genero' => 'misto',
            'cidade_eleitoral' => 'Exemplo City',
            'bairro_eleitoral' => 'Clube'
        ]);

        // Inscrever 16 atletas individuais
        $atletasTenis = $todosAtletas->random(16);
        foreach ($atletasTenis as $atleta) {
            Inscricao::create([
                'campeonato_id' => $campTenis->id,
                'atleta_id' => $atleta->id,
                'status' => 'confirmada',
                'data_pagamento' => now()
            ]);
        }
        $this->command->info("16 Atletas inscritos individualmente.");

        // Gerar Tabela (Oitavas de Final)
        $service->gerarTabela($campTenis, 'mata_mata');
        $this->command->info("Chaveamento de Tênis gerado.");

        // Simular primeira rodada completa (Oitavas) -> avançar 8 para Quartas
        $jogosOitavas = Partida::whereHas('fase', function($q) use ($campTenis) {
            $q->where('campeonato_id', $campTenis->id)->where('ordem', 1);
        })->get();

        foreach ($jogosOitavas as $jogo) {
            // Random winner logic
            $s1 = rand(0, 3); // Sets
            $s2 = rand(0, 3);
            if ($s1 == $s2) $s1++; // Desempate
            
            $service->atualizarPlacar($jogo, $s1, $s2);
        }
        $this->command->info("Oitavas de final simuladas. 8 vencedores avançaram para Quartas.");

        // -----------------------------------------------------------------------------------------
        // CENÁRIO 4: PONTOS CORRIDOS (Liga de Vôlei) - 6 Equipes
        // -----------------------------------------------------------------------------------------
        $this->command->info("--- Criando Liga de Vôlei (Pontos Corridos) ---");

        $campVolei = Campeonato::create([
            'nome' => 'Liga Municipal de Vôlei',
            'data_inicio' => now()->addDays(10),
            'data_fim' => now()->addMonth(4),
            'tipo_competicao' => 'equipe',
            'modalidade_id' => $modVolei->id,
            'status' => 'inscricoes_abertas',
            'categoria' => 'Adulto',
            'genero' => 'feminino',
            'cidade_eleitoral' => 'Exemplo City',
            'bairro_eleitoral' => 'Ginásio'
        ]);

        $timesVolei = ['Rexona', 'Osasco', 'Minas', 'Praia Clube', 'Sesi', 'Pinheiros'];
        foreach ($timesVolei as $nome) {
            $equipe = Equipe::create([
                'campeonato_id' => $campVolei->id,
                'nome' => $nome,
                'responsavel' => "Técnico $nome"
            ]);
            $equipe->atletas()->attach($todosAtletas->random(12)->pluck('id'));
        }

        $service->gerarTabela($campVolei, 'pontos_corridos');
        
        // Simular alguns jogos
        $jogosVolei = Partida::whereHas('fase', function($q) use ($campVolei) {
            $q->where('campeonato_id', $campVolei->id);
        })->take(4)->get();

        foreach ($jogosVolei as $jogo) {
            $service->atualizarPlacar($jogo, rand(0, 3), rand(0, 3));
        }
        $this->command->info("Liga de Vôlei gerada e iniciada.");
    }
}
