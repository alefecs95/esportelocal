<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\DiretoriaMembro;
use App\Scopes\TenantScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DiretoriaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user('sanctum');
        
        // Cenário 1: Admin Logado (tem prefeitura_id)
        if ($user && $user->prefeitura_id) {
            return response()->json(
                DiretoriaMembro::where('prefeitura_id', $user->prefeitura_id)
                               ->orderBy('ordem', 'asc')
                               ->get()
            );
        }
        
        // Cenário 2: Acesso Público (via TenantScope / Middleware de Tenant)
        // O TenantScope já filtra automaticamente se 'current_tenant' estiver no container
        if (app()->has('current_tenant')) {
             return response()->json(DiretoriaMembro::orderBy('ordem', 'asc')->get());
        }
        
        // Fallback: Se não tem usuário nem tenant identificado, retorna vazio
        return response()->json([]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = $request->user();
        if (!$user->prefeitura_id) {
            return response()->json(['message' => 'Usuário não vinculado a uma prefeitura'], 403);
        }

        $validated = $request->validate([
            'nome' => 'required|string|max:255',
            'funcao' => 'required|string|max:255',
            'foto' => 'nullable|file|image|max:5120',
            'ordem' => 'nullable|integer',
            'ativo' => 'boolean'
        ]);

        if ($request->hasFile('foto')) {
            $path = $request->file('foto')->store('diretoria/' . $user->prefeitura_id, 'public');
            $validated['foto'] = $path;
        }

        $validated['prefeitura_id'] = $user->prefeitura_id;

        $membro = DiretoriaMembro::create($validated);

        return response()->json($membro, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();
        if (!$user || !$user->prefeitura_id) {
            return response()->json(['message' => 'Usuário não vinculado a uma prefeitura'], 403);
        }

        $diretoria = DiretoriaMembro::withoutGlobalScope(TenantScope::class)
            ->where('prefeitura_id', $user->prefeitura_id)
            ->findOrFail($id);

        return response()->json($diretoria);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $user = $request->user();
        if (!$user || !$user->prefeitura_id) {
            return response()->json(['message' => 'Usuário não vinculado a uma prefeitura'], 403);
        }

        $diretoria = DiretoriaMembro::withoutGlobalScope(TenantScope::class)
            ->where('prefeitura_id', $user->prefeitura_id)
            ->findOrFail($id);

        $validated = $request->validate([
            'nome' => 'nullable|string|max:255',
            'funcao' => 'nullable|string|max:255',
            'foto' => 'nullable|file|image|max:5120',
            'ordem' => 'nullable|integer',
            'ativo' => 'boolean'
        ]);

        if ($request->hasFile('foto')) {
            if ($diretoria->foto) {
                Storage::disk('public')->delete($diretoria->foto);
            }
            $path = $request->file('foto')->store('diretoria/' . $diretoria->prefeitura_id, 'public');
            $validated['foto'] = $path;
        }

        $diretoria->update($validated);

        return response()->json($diretoria);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        if (!$user || !$user->prefeitura_id) {
            return response()->json(['message' => 'Usuário não vinculado a uma prefeitura'], 403);
        }

        $diretoria = DiretoriaMembro::withoutGlobalScope(TenantScope::class)
            ->where('prefeitura_id', $user->prefeitura_id)
            ->findOrFail($id);

        if ($diretoria->foto) {
            Storage::disk('public')->delete($diretoria->foto);
        }
        $diretoria->delete();
        return response()->json(null, 204);
    }
}
