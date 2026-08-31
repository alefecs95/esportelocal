<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Campeonato;
use App\Models\Equipe;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ManagerController extends Controller
{
    public function availableCampeonatos(Request $request)
    {
        $hoje = Carbon::today();
        $user = $request->user();

        $equipesDoResponsavel = Equipe::where(function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->orWhereHas('entidade', function ($entidadeQuery) use ($user) {
                        $entidadeQuery->where('user_id', $user->id);
                    });
            })
            ->select(['id', 'campeonato_id', 'status', 'status_inscricao'])
            ->get()
            ->keyBy('campeonato_id');

        $campeonatos = Campeonato::where('status', 'inscricoes_abertas')
            ->where('tipo_competicao', 'equipe')
            ->where(function ($query) use ($hoje) {
                $query->whereNull('inicio_inscricao')
                    ->orWhereDate('inicio_inscricao', '<=', $hoje);
            })
            ->where(function ($query) use ($hoje) {
                $query->whereNull('fim_inscricao')
                    ->orWhereDate('fim_inscricao', '>=', $hoje);
            })
            ->with('modalidade')
            ->get()
            ->map(function ($campeonato) use ($equipesDoResponsavel) {
                $equipe = $equipesDoResponsavel->get($campeonato->id);
                $campeonato->ja_inscrito = (bool) $equipe;
                $campeonato->equipe_id = $equipe?->id;
                $campeonato->equipe_status = $equipe?->status ?? $equipe?->status_inscricao;
                return $campeonato;
            });
            
        return response()->json($campeonatos);
    }
}
