<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Categoria;
use Illuminate\Http\Request;

class CategoriaController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeNewsManagement($request);
        return response()->json(Categoria::orderBy('nome')->get());
    }

    public function store(Request $request)
    {
        $this->authorizeNewsManagement($request);

        $request->validate([
            'nome' => 'required|string|max:255',
            'cor' => 'nullable|string|max:7',
        ]);

        $slug = \Illuminate\Support\Str::slug($request->nome);
        
        $categoria = Categoria::create([
            'nome' => $request->nome,
            'slug' => $slug,
            'cor' => $request->cor ?? '#3b82f6', // Default blue
            'descricao' => $request->descricao
        ]);

        return response()->json($categoria, 201);
    }

    private function authorizeNewsManagement(Request $request): void
    {
        $user = $request->user();
        if (!$user || !in_array($user->perfil, ['ADMIN', 'SUPER_ADMIN', 'REDATOR'], true)) {
            abort(403, 'Sem permissão para gerenciar categorias de notícias.');
        }
    }
}
