<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Temporada;
use App\Services\RankingService;

class RecalcularRanking extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ranking:recalcular {temporada_id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalcula o ranking municipal da temporada ativa ou específica';

    /**
     * Execute the console command.
     */
    public function handle(RankingService $rankingService)
    {
        $temporadaId = $this->argument('temporada_id');

        if (!$temporadaId) {
            $temporada = Temporada::where('status', 'ativa')->first();
            if (!$temporada) {
                $this->error('Nenhuma temporada ativa encontrada.');
                return;
            }
            $temporadaId = $temporada->id;
        }

        $this->info("Recalculando ranking para a temporada ID: $temporadaId...");

        try {
            $rankingService->recalcularRankingTemporada($temporadaId);
            $this->info('Ranking recalculado com sucesso!');
        } catch (\Exception $e) {
            $this->error('Erro ao recalcular ranking: ' . $e->getMessage());
        }
    }
}
