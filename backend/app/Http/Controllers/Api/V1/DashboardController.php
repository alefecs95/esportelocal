<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Equipe;
use App\Models\Atleta;
use App\Models\Campeonato;
use App\Models\Entidade;
use App\Models\Partida;
use App\Models\Inscricao;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function getStats()
    {
        // 1. Cards Principais
        $totalAtletas = Atleta::count();
        $totalClubes = Entidade::count();
        
        $campeonatosAtivos = Campeonato::where('status', 'em_andamento')->count();
        $campeonatosInscricoes = Campeonato::where('status', 'aberto')->count();
        
        // 2. Próximos Jogos (Admin precisa ver o que está por vir para organizar)
        $proximosJogos = Partida::with(['fase.campeonato'])
            ->whereIn('status', ['agendada', 'em_andamento'])
            ->orderBy('data_jogo', 'asc')
            ->limit(5)
            ->get();

        // 3. Inscrições Recentes (Últimos 7 dias)
        $inscricoesRecentes = Inscricao::where('created_at', '>=', Carbon::now()->subDays(7))->count();

        // 4. Gráfico: Atletas por Modalidade (Top 5)
        $atletasPorModalidade = DB::table('atleta_modalidade')
            ->join('modalidades', 'atleta_modalidade.modalidade_id', '=', 'modalidades.id')
            ->select('modalidades.nome', DB::raw('count(*) as total'))
            ->groupBy('modalidades.id', 'modalidades.nome')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        // 5. Partidas Pendentes de Súmula (Realizadas mas sem status finalizado ou sem placar confirmado?)
        // Vamos considerar 'finalizada' como ok. 'em_andamento' há muito tempo pode ser um alerta.
        $jogosSemSumula = Partida::where('status', 'finalizada')
            ->whereNull('placar_mandante') // Exemplo hipotético
            ->count();
            
        // 6. Inscrições Pendentes de Aprovação
        $inscricoesPendentes = Equipe::where('status_inscricao', 'pendente')
            ->with(['campeonato', 'entidade'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'cards' => [
                'atletas' => $totalAtletas,
                'clubes' => $totalClubes,
                'campeonatos_ativos' => $campeonatosAtivos,
                'inscricoes_abertas' => $campeonatosInscricoes,
                'inscricoes_recentes' => $inscricoesRecentes
            ],
            'proximos_jogos' => $proximosJogos,
            'inscricoes_pendentes' => $inscricoesPendentes,
            'graficos' => [
                'atletas_modalidade' => $atletasPorModalidade
            ]
        ]);
    }

    public function getCalendarEvents(Request $request)
    {
        $start = $request->query('start');
        $end = $request->query('end');

        $query = Partida::with(['fase.campeonato']);

        if ($start) {
            $query->where('data_jogo', '>=', $start);
        }
        if ($end) {
            $query->where('data_jogo', '<=', $end);
        }

        $events = $query->orderBy('data_jogo', 'asc')->get();

        return response()->json($events);
    }
}
