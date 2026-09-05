<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAdmin
{
    /**
     * Permite acesso apenas para perfis administrativos.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user('sanctum') ?? $request->user();
        if (!$user || !in_array($user->perfil, ['ADMIN', 'SUPER_ADMIN'], true)) {
            return response()->json(['message' => 'Acesso negado. Apenas administradores.'], 403);
        }

        return $next($request);
    }
}
