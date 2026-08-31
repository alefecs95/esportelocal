<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Entidade;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Services\TenantFileService;

class EntidadeController extends Controller
{
    protected $fileService;

    public function __construct(TenantFileService $fileService)
    {
        $this->fileService = $fileService;
    }

    public function index(Request $request)
    {
        $query = Entidade::with('user');
        
        // Se passar parâmetro ?only_mine=1, filtra pelo usuário logado
        if ($request->has('only_mine') && $request->user()) {
            $query->where('user_id', $request->user()->id);
        }
        
        $entidades = $query->orderBy('nome')->get();
        return response()->json($entidades);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nome' => 'required|string|max:255', 
            'sigla' => 'nullable|string|max:10',
            'data_fundacao' => 'nullable|date',
            'responsavel' => 'nullable|string|max:255',
            'contato' => 'nullable|string|max:255',
            'cidade' => 'nullable|string|max:255',
            'bairro' => 'nullable|string|max:255',
            'logo' => 'nullable|file|image|max:2048', 
            'user_id' => 'nullable|exists:users,id',
        ]);
        
        $data = $request->all();

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('entidades', 'public');
            $data['logo'] = $path;
        }

        $entidade = Entidade::create($data);
        return response()->json($entidade, 201);
    }
    
    public function show($id)
    {
        $entidade = Entidade::with(['equipes.campeonato', 'user'])->findOrFail($id);
        return response()->json($entidade);
    }
    
    public function update(Request $request, $id)
    {
        $entidade = Entidade::findOrFail($id);

        $validated = $request->validate([
            'nome' => ['required', 'string', 'max:255'],
            'sigla' => 'nullable|string|max:10',
            'data_fundacao' => 'nullable|date',
            'responsavel' => 'nullable|string|max:255',
            'contato' => 'nullable|string|max:255',
            'cidade' => 'nullable|string|max:255',
            'bairro' => 'nullable|string|max:255',
            'logo' => 'nullable|file|image|max:2048',
            'user_id' => 'nullable|exists:users,id',
        ]);
        
        $data = $request->all();

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('entidades', 'public');
            $data['logo'] = $path;
        }

        $entidade->update($data);
        return response()->json($entidade);
    }

    public function destroy($id)
    {
        $entidade = Entidade::findOrFail($id);
        
        // Verificar se tem equipes vinculadas?
        // Como é soft delete, não tem problema, mas bom avisar
        
        $entidade->delete();
        return response()->json(['message' => 'Entidade removida com sucesso.']);
    }
}
