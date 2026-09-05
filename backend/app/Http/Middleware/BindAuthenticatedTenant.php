<?php

namespace App\Http\Middleware;

use App\Models\Prefeitura;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class BindAuthenticatedTenant
{
    /**
     * After authentication, pin the tenant to the user's prefeitura.
     * Super Admin keeps the tenant resolved from host/header (impersonation of a municipality).
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user('sanctum') ?? $request->user();
        if (!$user) {
            return $next($request);
        }

        if ($user->perfil === 'SUPER_ADMIN') {
            return $next($request);
        }

        if (!$user->prefeitura_id) {
            return $next($request);
        }

        $tenant = Prefeitura::query()->find($user->prefeitura_id);
        if (!$tenant) {
            return response()->json(['message' => 'Prefeitura do usuário não encontrada.'], 403);
        }

        if (!$tenant->ativo) {
            return response()->json(['message' => 'Tenant is inactive.'], 403);
        }

        App::instance('current_tenant', $tenant);
        config(['app.name' => $tenant->nome]);

        return $next($request);
    }
}
