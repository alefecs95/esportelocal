<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSuperAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Verifica se o usuário está autenticado e se é Super Admin
        if (!$request->user() || !$request->user()->isSuperAdmin()) {
            return response()->json(['message' => 'Acesso negado. Apenas Super Admins.'], 403);
        }

        return $next($request);
    }
}
