<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    private function requireAdmin(Request $request): User
    {
        $actor = $request->user('sanctum');
        if (!$actor) {
            abort(401, 'Não autenticado.');
        }

        if (!in_array($actor->perfil, ['ADMIN', 'SUPER_ADMIN'], true)) {
            abort(403, 'Sem permissão para gerenciar usuários.');
        }

        if ($actor->perfil !== 'SUPER_ADMIN' && !$actor->prefeitura_id) {
            abort(403, 'Usuário sem prefeitura vinculada.');
        }

        return $actor;
    }

    public function searchResponsavel(Request $request)
    {
        $this->requireAdmin($request);
        $search = $request->query('q');
        
        // Permite buscar tanto RESPONSAVEL quanto ATLETA para vincular a uma entidade
        $query = User::whereIn('perfil', ['RESPONSAVEL', 'ATLETA'])
            ->where('status', 'ATIVO');
            
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('cpf', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }
        
        return response()->json($query->limit(10)->get());
    }

    public function getArbitros(Request $request)
    {
        $this->requireAdmin($request);
        $arbitros = User::whereIn('perfil', ['ARBITRO', 'ADMIN'])
            ->where('status', 'ATIVO')
            ->orderBy('name')
            ->get(['id', 'name', 'perfil']);
            
        return response()->json($arbitros);
    }

    private function resolvePrefeituraId(User $actor): int
    {
        if ($actor->prefeitura_id) {
            return (int) $actor->prefeitura_id;
        }

        if (app()->bound('current_tenant') && app('current_tenant')) {
            return (int) app('current_tenant')->id;
        }

        abort(400, 'Nenhuma prefeitura identificada para gerenciar usuários.');
    }

    public function adminIndex(Request $request)
    {
        $actor = $this->requireAdmin($request);
        $prefeituraId = $this->resolvePrefeituraId($actor);
        $search = $request->query('q');
        $perfil = $request->query('perfil');
        $status = $request->query('status');
        $perPage = max(10, min((int) $request->query('per_page', 20), 100));

        $query = User::query()
            ->where('prefeitura_id', $prefeituraId)
            ->where('perfil', '!=', 'SUPER_ADMIN')
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('cpf', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($perfil, function ($q) use ($perfil) {
                $q->where('perfil', $perfil);
            })
            ->when($status, function ($q) use ($status) {
                $q->where('status', $status);
            })
            ->orderBy('name');

        return response()->json($query->paginate($perPage));
    }

    public function adminStore(Request $request)
    {
        $actor = $this->requireAdmin($request);
        $prefeituraId = $this->resolvePrefeituraId($actor);
        $allowedPerfis = ['ADMIN', 'ARBITRO', 'RESPONSAVEL', 'REDATOR', 'ATLETA'];

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('users', 'email')->where(function ($query) use ($prefeituraId) {
                    return $query->where('prefeitura_id', $prefeituraId);
                }),
            ],
            'cpf' => [
                'required',
                'string',
                'max:20',
                Rule::unique('users', 'cpf')->where(function ($query) use ($prefeituraId) {
                    return $query->where('prefeitura_id', $prefeituraId);
                }),
            ],
            'perfil' => ['required', Rule::in($allowedPerfis)],
            'status' => ['required', Rule::in(['ATIVO', 'INATIVO'])],
            'password' => ['required', 'string', 'min:6', 'max:72'],
            'forcar_troca_senha' => ['nullable', 'boolean'],
        ]);

        $usuario = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'cpf' => preg_replace('/\D/', '', $validated['cpf']),
            'perfil' => $validated['perfil'],
            'status' => $validated['status'],
            'password' => Hash::make($validated['password']),
            'forcar_troca_senha' => $request->boolean('forcar_troca_senha', true),
            'prefeitura_id' => $prefeituraId,
        ]);

        return response()->json($usuario, 201);
    }

    public function adminUpdate(Request $request, int $id)
    {
        $actor = $this->requireAdmin($request);
        $prefeituraId = $this->resolvePrefeituraId($actor);
        $allowedPerfis = ['ADMIN', 'ARBITRO', 'RESPONSAVEL', 'REDATOR', 'ATLETA'];

        $usuario = User::where('prefeitura_id', $prefeituraId)
            ->where('perfil', '!=', 'SUPER_ADMIN')
            ->findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('users', 'email')
                    ->where(function ($query) use ($prefeituraId) {
                        return $query->where('prefeitura_id', $prefeituraId);
                    })
                    ->ignore($usuario->id),
            ],
            'cpf' => [
                'required',
                'string',
                'max:20',
                Rule::unique('users', 'cpf')
                    ->where(function ($query) use ($prefeituraId) {
                        return $query->where('prefeitura_id', $prefeituraId);
                    })
                    ->ignore($usuario->id),
            ],
            'perfil' => ['required', Rule::in($allowedPerfis)],
            'status' => ['required', Rule::in(['ATIVO', 'INATIVO'])],
            'password' => ['nullable', 'string', 'min:6', 'max:72'],
            'forcar_troca_senha' => ['nullable', 'boolean'],
        ]);

        $payload = [
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'cpf' => preg_replace('/\D/', '', $validated['cpf']),
            'perfil' => $validated['perfil'],
            'status' => $validated['status'],
            'forcar_troca_senha' => $request->boolean('forcar_troca_senha', $usuario->forcar_troca_senha),
        ];

        if (!empty($validated['password'])) {
            $payload['password'] = Hash::make($validated['password']);
            $payload['forcar_troca_senha'] = true;
        }

        $usuario->update($payload);

        return response()->json($usuario);
    }

    public function adminDestroy(Request $request, int $id)
    {
        $actor = $this->requireAdmin($request);
        $prefeituraId = $this->resolvePrefeituraId($actor);

        if ((int) $actor->id === (int) $id) {
            abort(422, 'Você não pode excluir o próprio usuário.');
        }

        $usuario = User::where('prefeitura_id', $prefeituraId)
            ->where('perfil', '!=', 'SUPER_ADMIN')
            ->findOrFail($id);

        $usuario->delete();

        return response()->json(['message' => 'Usuário excluído com sucesso.']);
    }
}
