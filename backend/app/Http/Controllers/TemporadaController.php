<?php

namespace App\Http\Controllers;

use App\Models\Temporada;
use App\Models\Campeonato;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TemporadaController extends Controller
{
    public function index()
    {
        return Temporada::orderBy('ano', 'desc')->get();
    }

    public function store(Request $request)
    {
        $request->validate([
            'nome' => 'required|string|max:255',
            'ano' => [
                'required',
                'integer',
                Rule::unique('temporadas')->whereNull('deleted_at')
            ],
        ]);

        // If this is the first season, make it active
        // Or if there are no seasons, the first one is active.
        // If there is an active season, this one is 'encerrada' (future/draft)
        $hasActive = Temporada::where('status', 'ativa')->exists();
        $status = $hasActive ? 'encerrada' : 'ativa';

        $temporada = Temporada::create([
            'nome' => $request->nome,
            'ano' => $request->ano,
            'status' => $status,
        ]);

        return response()->json($temporada, 201);
    }

    public function show($id)
    {
        return Temporada::with('campeonatos')->findOrFail($id);
    }

    public function ativar($id)
    {
        $temporada = Temporada::findOrFail($id);

        if ($temporada->status === 'ativa') {
            return response()->json(['message' => 'Temporada já está ativa'], 200);
        }

        // Validate if current active season can be closed
        $activeSeason = Temporada::where('status', 'ativa')->first();
        if ($activeSeason) {
            // Check for ongoing championships in the active season
            // Statuses that prevent closing: 'em_andamento', 'aberto' (Inscrições Abertas)
            $ongoingChampionships = Campeonato::where('temporada_id', $activeSeason->id)
                ->whereIn('status', ['em_andamento', 'inscricoes_abertas']) 
                ->exists();

            if ($ongoingChampionships) {
                return response()->json([
                    'message' => 'Não é possível encerrar a temporada atual pois existem campeonatos em andamento ou com inscrições abertas.',
                    'active_season' => $activeSeason
                ], 422);
            }
        }

        DB::transaction(function () use ($temporada, $activeSeason) {
            if ($activeSeason) {
                $activeSeason->update(['status' => 'encerrada']);
            }
            $temporada->update(['status' => 'ativa']);
        });

        return response()->json(['message' => 'Temporada ativada com sucesso', 'temporada' => $temporada]);
    }
    
    public function destroy($id)
    {
        $temporada = Temporada::findOrFail($id);
        
        if ($temporada->campeonatos()->exists()) {
             return response()->json(['message' => 'Não é possível excluir temporada com campeonatos vinculados.'], 422);
        }
        
        $temporada->delete();
        
        return response()->json(['message' => 'Temporada excluída com sucesso']);
    }
}
