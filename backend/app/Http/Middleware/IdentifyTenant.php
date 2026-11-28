<?php

namespace App\Http\Middleware;

use App\Models\Prefeitura;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class IdentifyTenant
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();
        $tenantDomainHeader = $this->normalizeHost((string) $request->header('X-Tenant-Domain', ''));
        $originHost = $this->extractHostFromHeaderUrl((string) $request->headers->get('origin', ''));
        $refererHost = $this->extractHostFromHeaderUrl((string) $request->headers->get('referer', ''));
        $isLocalHost = $host === 'localhost' || filter_var($host, FILTER_VALIDATE_IP);
        $parts = explode('.', $host);
        
        // Estratégia 1: Subdomínio (ex: prefeitura.semel.com.br)
        // Ignora 'www' ou 'api' se forem reservados, mas aqui assumimos que o primeiro parte é o tenant
        // Em localhost, isso pode ser tricky. Vamos permitir um header X-Tenant-Slug para dev.
        
        $slug = null;

        // Se for localhost ou IP, tentamos header ou assumimos 'padrao' para dev
        if ($isLocalHost) {
            $slug = $request->header('X-Tenant-Slug');
        } else {
            // Em produção: tenant.domain.com
            // Se tivermos 3 partes (tenant.domain.com), o tenant é o primeiro
            // Se tivermos 2 partes (domain.com), pode ser o site principal (landing page)
            if (count($parts) >= 3) {
                $slug = $parts[0];
                // Ignorar subdomínios reservados como 'www', 'api', 'admin' se necessário
                if (in_array($slug, ['www', 'api', 'admin'])) {
                    // Lógica para domínio principal ou admin global
                    // Por enquanto, vamos assumir que não há tenant se for reservado
                    $slug = null;
                }
            }
        }

        // Estratégia 2: Header explícito (útil para API Mobile ou testes)
        if ($request->hasHeader('X-Tenant-Slug')) {
            $slug = $request->header('X-Tenant-Slug');
        }

        $tenantByDomain = $this->resolveTenantByDomains([
            $host,
            $tenantDomainHeader,
            $originHost,
            $refererHost,
        ]);
        if (!$slug && $tenantByDomain) {
            $this->registerTenant($tenantByDomain);
            return $next($request);
        }

        if (!$slug) {
            if ($isLocalHost) {
                $tenant = Prefeitura::where('ativo', true)->orderBy('id')->first();
                if ($tenant) {
                    $this->registerTenant($tenant);
                    return $next($request);
                }
            }

            // Se não identificou tenant, talvez seja a landing page global ou erro
            // Para API, retornamos erro se for um endpoint protegido
            // Mas como é middleware global, podemos deixar passar se não exigir tenant,
            // ou abortar. O requisito diz "Tratamento de erro se não existir".
            
            // Vamos permitir passar sem tenant para rotas de admin ou públicas
            if ($request->is('api/*')) {
                if (
                    $request->is('api/v1/tenant') ||
                    $request->is('api/v1/public/*') ||
                    $request->is('api/v1/login') ||
                    $request->is('api/v1/register') ||
                    $request->is('api/v1/password/forgot-whatsapp') ||
                    $request->is('api/v1/password/reset-by-token') ||
                    $request->is('api/v1/notifications/unsubscribe/*') ||
                    $request->is('api/v1/me') ||
                    $request->is('api/v1/logout') ||
                    $request->is('api/v1/update-password') ||
                    $request->is('api/v1/atletas/consulta-cpf/*') ||
                    $request->is('api/v1/atletas/consulta-titulo') ||
                    // Links de PDF/comprovante enviados por WhatsApp e-mail (sem subdomínio do tenant)
                    $request->is('api/signed/*') ||
                    $request->is('api/v1/signed/*')
                ) {
                    return $next($request);
                }

                // Se for rota de admin global ou não exigir tenant, ok.
                // Mas aqui bloqueamos por padrão para segurança.
                // Exceção: Rota /api/v1/tenant (que retorna 404 se não achar)
                if ($request->is('api/v1/tenant')) {
                    return $next($request);
                }
                
                // Permitir rotas de admin passarem sem tenant (serão validadas pelo middleware CheckSuperAdmin)
                if ($request->is('api/v1/admin/*')) {
                    return $next($request);
                }
                
                // Permitir admin global se o subdomínio for 'admin' (verificado acima que slug vira null)
                // Mas precisamos ter certeza que é admin.
                // Se o slug veio null porque era 'admin', ok.
                // Mas se veio null pq acessou por IP?
                // Vamos assumir que acesso direto por IP sem header é inválido para API multi-tenant,
                // exceto se for login de admin.
                
                // Para simplificar: Retornamos 400. O Frontend deve mandar X-Tenant-Slug ou acessar subdomínio.
                return response()->json(['message' => 'Tenant not identified or invalid.'], 400);
            }
            
            return $next($request);
        }

        $tenant = $this->findTenantBySlug((string) $slug);
        if (!$tenant && $tenantByDomain) {
            $tenant = $tenantByDomain;
        }

        if (!$tenant) {
            if ($request->is('api/*')) {
                return response()->json(['message' => 'Tenant not found.'], 404);
            }
            abort(404, 'Prefeitura não encontrada.');
        }

        if (!$tenant->ativo) {
             if ($request->is('api/*')) {
                return response()->json(['message' => 'Tenant is inactive.'], 403);
            }
            abort(403, 'Prefeitura inativa.');
        }

        // Registrar no container
        $this->registerTenant($tenant);

        return $next($request);
    }

    private function registerTenant(Prefeitura $tenant): void
    {
        App::instance('current_tenant', $tenant);
        config(['app.name' => $tenant->nome]);
    }

    private function findTenantByDomain(string $domain): ?Prefeitura
    {
        $tenants = Prefeitura::whereNotNull('dominio')->get();
        foreach ($tenants as $tenant) {
            $normalized = $this->normalizeHost((string) $tenant->dominio);
            if ($normalized !== '' && $normalized === $domain) {
                return $tenant;
            }
        }

        return null;
    }

    private function findTenantBySlug(string $slug): ?Prefeitura
    {
        $rawSlug = trim(mb_strtolower($slug, 'UTF-8'));
        if ($rawSlug === '') {
            return null;
        }

        $exact = Prefeitura::where('slug', $rawSlug)->first();
        if ($exact) {
            return $exact;
        }

        $normalizedSlug = $this->normalizeTenantToken($rawSlug);
        if ($normalizedSlug === '') {
            return null;
        }

        $tenants = Prefeitura::select(['id', 'slug', 'dominio', 'ativo', 'nome'])->get();
        foreach ($tenants as $tenant) {
            $candidate = $this->normalizeTenantToken((string) ($tenant->slug ?? ''));
            if ($candidate !== '' && $candidate === $normalizedSlug) {
                return $tenant;
            }
        }

        return null;
    }

    private function resolveTenantByDomains(array $domains): ?Prefeitura
    {
        foreach ($domains as $domain) {
            $normalized = $this->normalizeHost((string) $domain);
            if ($normalized === '') {
                continue;
            }

            $tenant = $this->findTenantByDomain($normalized);
            if ($tenant) {
                return $tenant;
            }
        }

        return null;
    }

    private function extractHostFromHeaderUrl(string $value): string
    {
        $raw = trim($value);
        if ($raw === '') {
            return '';
        }

        if (!str_contains($raw, '://')) {
            $raw = 'https://' . $raw;
        }

        $host = (string) parse_url($raw, PHP_URL_HOST);
        return $this->normalizeHost($host);
    }

    private function normalizeHost(string $value): string
    {
        $raw = trim(mb_strtolower($value, 'UTF-8'));
        if ($raw === '') {
            return '';
        }

        if (!str_contains($raw, '://')) {
            $raw = 'https://' . $raw;
        }

        $host = (string) parse_url($raw, PHP_URL_HOST);
        $host = preg_replace('/:\d+$/', '', $host ?? '') ?? '';
        $host = trim($host);
        $host = preg_replace('/^www\./', '', $host) ?? $host;

        return $host;
    }

    private function normalizeTenantToken(string $value): string
    {
        $raw = mb_strtolower(trim($value), 'UTF-8');
        if ($raw === '') {
            return '';
        }

        return preg_replace('/[^a-z0-9]/', '', $raw) ?? '';
    }
}
