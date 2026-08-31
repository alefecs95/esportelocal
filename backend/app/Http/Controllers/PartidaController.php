<?php

namespace App\Http\Controllers;

use App\Models\Partida;
use Illuminate\Http\Request;

class PartidaController extends Controller
{
    public function show($id)
    {
        $partida = Partida::with([
            'fase.campeonato',
            'eventos.jogador',
            'eventos.time',
            'arbitro',
            'escalacoes.atleta'
        ])->findOrFail($id);

        // Accessing them triggers the logic in getMandanteAttribute
        $partida->append(['mandante', 'visitante']);

        return response()->json($partida);
    }
}
