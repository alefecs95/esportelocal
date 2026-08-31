<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Modalidade;
use Illuminate\Http\Request;

class ModalidadeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 15);
        
        // Se per_page for -1 ou muito grande, traz tudo (dentro de um limite seguro)
        if ($perPage == -1 || $perPage > 500) {
            $perPage = 500;
        }

        // Lógica de "Shadowing" (Sobrescrita):
        // Se existir uma modalidade local com o mesmo nome de uma global, mostramos apenas a local.
        // Como o TenantScope já traz (Local OR Global), precisamos filtrar duplicatas pelo nome, priorizando a Local.
        
        // Vamos ordenar por prefeitura_id DESC, assim as locais (ID > 1) vêm antes das globais (ID 1)
        // Isso assume que o ID da prefeitura local é > 1.
        // Uma abordagem mais robusta é ordenar por "é do tenant atual" primeiro.
        
        // Na verdade, o paginate atrapalha a filtragem manual. 
        // Idealmente, fazemos isso no banco ou trazemos tudo e paginamos manualmente (se não forem muitos registros).
        // Como modalidades são poucas (< 100), podemos trazer tudo, filtrar e paginar.
        
        $allModalidades = Modalidade::all(); // O Scope já filtra (Local + Global)
        
        // Agrupar por nome e pegar a do tenant atual se existir, senão a global
        $uniqueModalidades = $allModalidades->groupBy('nome')->map(function ($group) {
            // Se tiver mais de uma (Local e Global), pegamos a que tem prefeitura_id != 1 (ou a do usuário)
            // Assumindo que o Scope garante que só vemos a NOSSA e a GLOBAL.
            if ($group->count() > 1) {
                // Retorna a que NÃO é a global (prefeitura_id 1)
                // Ou ordena por ID desc (assumindo que a local foi criada depois)
                return $group->sortByDesc('id')->first();
            }
            return $group->first();
        })->values();

        // Paginação manual da coleção filtrada
        $page = $request->input('page', 1);
        $offset = ($page - 1) * $perPage;
        
        $paginatedItems = $uniqueModalidades->slice($offset, $perPage)->values();
        
        return response()->json([
            'data' => $paginatedItems,
            'current_page' => (int)$page,
            'per_page' => (int)$perPage,
            'total' => $uniqueModalidades->count(),
            'last_page' => ceil($uniqueModalidades->count() / $perPage)
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nome' => 'required|string|max:255',
            'tipo' => 'required|in:individual,coletivo',
            'descricao' => 'nullable|string',
            'status' => 'boolean',
            'regras_config' => 'nullable|array',
        ]);

        $modalidade = Modalidade::create($validated);

        return response()->json($modalidade, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $modalidade = Modalidade::findOrFail($id);
        return response()->json($modalidade);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $modalidade = Modalidade::findOrFail($id);

        $validated = $request->validate([
            'nome' => 'sometimes|required|string|max:255',
            'tipo' => 'sometimes|required|in:individual,coletivo',
            'descricao' => 'nullable|string',
            'status' => 'boolean',
            'regras_config' => 'nullable|array',
        ]);

        // VERIFICAÇÃO DE TENANT (Shadowing)
        // Se a modalidade é global (prefeitura_id = 1) e o usuário não é da prefeitura 1,
        // criamos uma CÓPIA local e atualizamos ela, em vez de alterar a global.
        
        // Assumindo que current_tenant está disponível e tem ID != 1
        // Se o usuário for SUPER_ADMIN ou estiver no tenant 1, ele pode editar a original.
        // Vamos usar uma lógica simples: Se prefeitura_id == 1 e usuário->tenant != 1
        
        $userTenantId = null;
        if (app()->has('current_tenant')) {
            $userTenantId = app('current_tenant')->id;
        } elseif (auth()->check()) {
            $userTenantId = auth()->user()->prefeitura_id;
        }

        if ($modalidade->prefeitura_id == 1 && $userTenantId != 1) {
            // Cria cópia local (Shadowing)
            $novaModalidade = $modalidade->replicate();
            $novaModalidade->prefeitura_id = $userTenantId;
            $novaModalidade->fill($validated); // Aplica as mudanças
            $novaModalidade->save();
            
            return response()->json($novaModalidade);
        }

        // Caso normal: Edita a própria modalidade (local ou global se tiver permissão)
        $modalidade->update($validated);

        return response()->json($modalidade);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $modalidade = Modalidade::findOrFail($id);
        $modalidade->delete();

        return response()->json(null, 204);
    }
}
