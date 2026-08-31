<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Atleta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FiltroController extends Controller
{
    public function cidadesEleitorais(): JsonResponse
    {
        $cidades = Atleta::query()
            ->select('cidade_eleitoral')
            ->distinct()
            ->whereNotNull('cidade_eleitoral')
            ->orderBy('cidade_eleitoral')
            ->pluck('cidade_eleitoral');

        return response()->json($cidades);
    }

    public function bairrosEleitorais(Request $request): JsonResponse
    {
        $cidade = $request->input('cidade');

        $query = Atleta::query()
            ->select('bairro_eleitoral')
            ->distinct()
            ->whereNotNull('bairro_eleitoral');

        if ($cidade) {
            $query->where('cidade_eleitoral', $cidade);
        }

        $bairros = $query->orderBy('bairro_eleitoral')
            ->pluck('bairro_eleitoral');

        return response()->json($bairros);
    }
}
