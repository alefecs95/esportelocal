<?php

namespace App\Services;

use App\Models\ConsultaHistorico;
use App\Models\TseCache;
use Carbon\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class TseService
{
    public function getTseData(string $cpf, bool $forceRefresh = false, array $criteria = []): array
    {
        $cpfLimpo = $this->sanitizeCpf($cpf);
        $queryData = $this->prepareQueryData($cpfLimpo, $criteria);
        $canUsePersistentCache = $cpfLimpo !== '';
        $identityCacheKey = $canUsePersistentCache ? null : $this->resolveIdentityCacheKey($queryData);
        $apiCalled = false;

        try {
            if ($canUsePersistentCache && !$this->isValidCpf($cpfLimpo)) {
                throw new RuntimeException('CPF inválido para consulta eleitoral.');
            }

            $source = 'api';
            $payload = null;

            if ($canUsePersistentCache && !$forceRefresh) {
                $cache = $this->getValidCache($cpfLimpo);
                if ($cache) {
                    $payload = $cache->dados_json ?? [];
                    $source = 'cache';
                }
            }

            if ($payload === null && $canUsePersistentCache) {
                $payload = $this->runWithLock($cpfLimpo, function () use ($cpfLimpo, $forceRefresh, $queryData, &$source, &$apiCalled) {
                    if (!$forceRefresh) {
                        $cache = $this->getValidCache($cpfLimpo);
                        if ($cache) {
                            $source = 'cache';
                            return $cache->dados_json ?? [];
                        }
                    }

                    try {
                        $this->assertTenantQuota('tse');
                        $apiCalled = true;
                        $apiData = $this->consultarApiTse($queryData);
                    } catch (RuntimeException $e) {
                        if ($this->isTseUnavailableMessage($e->getMessage())) {
                            $staleCache = $this->getLatestCache($cpfLimpo);
                            if ($staleCache) {
                                $source = 'stale_cache';
                                return $staleCache->dados_json ?? [];
                            }
                        }
                        throw $e;
                    }

                    if (!$this->isComplete($apiData)) {
                        throw new RuntimeException('A API do TSE retornou dados incompletos.');
                    }

                    $agora = Carbon::now();
                    TseCache::query()->updateOrCreate(
                        ['cpf' => $cpfLimpo],
                        [
                            'dados_json' => $apiData,
                            'consultado_em' => $agora,
                            'expira_em' => $agora->copy()->addDays(30),
                        ]
                    );

                    $source = 'api';
                    return $apiData;
                });
            }

            if ($payload === null) {
                if (!$canUsePersistentCache) {
                    if (!$forceRefresh && $identityCacheKey !== null) {
                        $cachedIdentity = Cache::get($identityCacheKey);
                        if (is_array($cachedIdentity)) {
                            $source = 'cache_identity';
                            $payload = $cachedIdentity;
                        }
                    }

                    if ($payload === null) {
                        $lockKey = $this->resolveCacheKey($queryData);
                        $payload = $this->runWithLock($lockKey, function () use ($identityCacheKey, $forceRefresh, $queryData, &$source, &$apiCalled) {
                            if (!$forceRefresh && $identityCacheKey !== null) {
                                $cachedIdentity = Cache::get($identityCacheKey);
                                if (is_array($cachedIdentity)) {
                                    $source = 'cache_identity';
                                    return $cachedIdentity;
                                }
                            }

                            $this->assertTenantQuota('tse');
                            $apiCalled = true;
                            $apiData = $this->consultarApiTse($queryData);
                            if (!$this->isComplete($apiData)) {
                                throw new RuntimeException('A API do TSE retornou dados incompletos.');
                            }

                            if ($identityCacheKey !== null) {
                                Cache::put($identityCacheKey, $apiData, now()->addDays(30));
                            }

                            $source = 'api_identity';
                            return $apiData;
                        });
                    }
                } else {
                    if (!$forceRefresh) {
                        $source = 'api_no_cache';
                    }

                    $this->assertTenantQuota('tse');
                    $apiCalled = true;
                    $payload = $this->consultarApiTse($queryData);
                    if (!$this->isComplete($payload)) {
                        throw new RuntimeException('A API do TSE retornou dados incompletos.');
                    }
                }
            }

            $this->registerHistory('tse', $cpfLimpo, 'sucesso', $source, null, [
                'source' => $source,
                'force_refresh' => $forceRefresh,
                'nome_consultado' => (string) ($queryData['nome'] ?? ''),
                'api_called' => $this->wasExternalApiCalledBySource($source),
            ]);

            return $payload;
        } catch (\Throwable $e) {
            $this->registerHistory('tse', $cpfLimpo, 'erro', null, $e->getMessage(), [
                'force_refresh' => $forceRefresh,
                'nome_consultado' => (string) ($queryData['nome'] ?? ''),
                'api_called' => $apiCalled,
            ]);
            if ($e instanceof RuntimeException) {
                throw $e;
            }
            throw new RuntimeException('Falha ao consultar TSE.');
        }
    }

    private function getValidCache(string $cpf): ?TseCache
    {
        return TseCache::query()
            ->where('cpf', $cpf)
            ->where('expira_em', '>', Carbon::now())
            ->first();
    }

    private function getLatestCache(string $cpf): ?TseCache
    {
        return TseCache::query()
            ->where('cpf', $cpf)
            ->orderByDesc('consultado_em')
            ->first();
    }

    private function consultarApiTse(array $queryData): array
    {
        $url = env('DIRECTD_API_TSE_URL');
        $token = env('DIRECTD_API_TOKEN');

        if (!$url || !$token) {
            throw new RuntimeException('API de TSE não configurada.');
        }

        $params = [
            'TOKEN' => $token,
            'CPF' => $queryData['cpf'],
            'DATANASCIMENTO' => $queryData['data_nascimento'],
            'NOMEMAE' => $queryData['nome_mae'],
        ];

        try {
            $response = Http::connectTimeout(8)->timeout(20)->get($url, $params);
        } catch (\Throwable $e) {
            Log::error('Falha na consulta ao TSE', ['cpf' => $queryData['cpf'], 'error' => $e->getMessage()]);
            throw new RuntimeException('Falha de comunicação com a API do TSE.');
        }

        if (!$response->successful()) {
            Log::error('Erro HTTP na API do TSE', ['cpf' => $queryData['cpf'], 'status' => $response->status(), 'body' => $response->body()]);
            throw new RuntimeException('API do TSE indisponível no momento.');
        }

        $data = $response->json();
        if (!is_array($data)) {
            throw new RuntimeException('Resposta inválida da API do TSE.');
        }

        $resultadoId = (int) ($data['metaDados']['resultadoId'] ?? 1);
        if ($resultadoId !== 1) {
            $mensagem = (string) ($data['metaDados']['mensagem'] ?? 'Consulta eleitoral sem sucesso.');
            throw new RuntimeException($mensagem);
        }

        return $data;
    }

    private function isComplete(array $apiData): bool
    {
        $retorno = is_array($apiData['retorno'] ?? null) ? $apiData['retorno'] : [];
        $domicilio = is_array($retorno['domicilioEleitoral'] ?? null) ? $retorno['domicilioEleitoral'] : [];
        $identificacao = is_array($retorno['identificacao'] ?? null) ? $retorno['identificacao'] : [];

        $required = [
            trim((string) ($identificacao['inscricao'] ?? '')),
            trim((string) ($domicilio['zona'] ?? '')),
            trim((string) ($domicilio['secao'] ?? '')),
            trim((string) ($domicilio['bairro'] ?? '')),
            trim((string) ($domicilio['municipio'] ?? '')),
            strtoupper(trim((string) ($domicilio['uf'] ?? ''))),
        ];

        foreach ($required as $value) {
            if ($value === '') {
                return false;
            }
        }

        return true;
    }

    private function prepareQueryData(string $cpf, array $criteria): array
    {
        $cpfNormalizado = $this->sanitizeCpf($cpf);
        $dataNascimento = $this->normalizeDataNascimento((string) ($criteria['data_nascimento'] ?? ''));
        $nomeMae = $this->normalizeText((string) ($criteria['nome_mae'] ?? ''));

        if ($cpfNormalizado === '') {
            throw new RuntimeException('CPF é obrigatório para consulta do TSE.');
        }

        if ($dataNascimento === '') {
            throw new RuntimeException('Data de nascimento é obrigatória para consulta do TSE.');
        }

        if ($nomeMae === '') {
            throw new RuntimeException('Nome da mãe é obrigatório para consulta do TSE.');
        }

        return [
            'cpf' => $cpfNormalizado,
            'data_nascimento' => $dataNascimento,
            'nome_mae' => $nomeMae,
        ];
    }

    private function normalizeDataNascimento(string $value): string
    {
        $raw = trim(explode(' ', trim($value))[0] ?? '');
        if ($raw === '') {
            return '';
        }

        if (preg_match('/^\d{2}[\/-]\d{2}[\/-]\d{4}$/', $raw) === 1) {
            [$d, $m, $y] = preg_split('/[\/-]/', $raw) ?: [null, null, null];
            if (!$d || !$m || !$y) {
                return '';
            }
            return sprintf('%02d/%02d/%04d', (int) $d, (int) $m, (int) $y);
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw) === 1) {
            [$y, $m, $d] = explode('-', $raw);
            return sprintf('%02d/%02d/%04d', (int) $d, (int) $m, (int) $y);
        }

        return '';
    }

    private function normalizeText(string $value): string
    {
        return mb_strtoupper(trim($value), 'UTF-8');
    }

    private function resolveCacheKey(array $queryData): string
    {
        $cpf = (string) ($queryData['cpf'] ?? '');
        if ($cpf !== '') {
            return $cpf;
        }

        return 'IDENT:' . hash('sha256', implode('|', [
            (string) ($queryData['nome'] ?? ''),
            (string) ($queryData['data_nascimento'] ?? ''),
            (string) ($queryData['nome_mae'] ?? ''),
        ]));
    }

    private function resolveIdentityCacheKey(array $queryData): string
    {
        return 'tse-identidade-cache:' . hash('sha256', implode('|', [
            (string) ($queryData['nome'] ?? ''),
            (string) ($queryData['data_nascimento'] ?? ''),
            (string) ($queryData['nome_mae'] ?? ''),
        ]));
    }

    private function wasExternalApiCalledBySource(string $source): bool
    {
        return in_array($source, ['api', 'api_identity', 'api_no_cache'], true);
    }

    private function isTseUnavailableMessage(string $message): bool
    {
        $text = mb_strtolower(trim($message), 'UTF-8');
        return str_contains($text, 'indisponível')
            || str_contains($text, 'falha de comunicação')
            || str_contains($text, 'não configurada');
    }

    private function sanitizeCpf(string $cpf): string
    {
        return preg_replace('/\D/', '', $cpf) ?? '';
    }

    private function isValidCpf(string $cpf): bool
    {
        if (strlen($cpf) !== 11 || preg_match('/^(\d)\1{10}$/', $cpf) === 1) {
            return false;
        }

        for ($t = 9; $t < 11; $t++) {
            $sum = 0;
            for ($c = 0; $c < $t; $c++) {
                $sum += (int) $cpf[$c] * (($t + 1) - $c);
            }
            $digit = ((10 * $sum) % 11) % 10;
            if ((int) $cpf[$t] !== $digit) {
                return false;
            }
        }

        return true;
    }

    private function runWithLock(string $cpf, callable $callback): array
    {
        $store = Cache::getStore();
        if (method_exists($store, 'lock')) {
            return Cache::lock('tse-cache:' . $cpf, 10)->block(5, $callback);
        }

        return $callback();
    }

    private function assertTenantQuota(string $tipo): void
    {
        [$tenantId, $limit] = $this->resolveTenantAndLimit($tipo);

        if ($limit <= 0 || $tenantId <= 0) {
            return;
        }

        $key = 'consulta-limit:' . $tipo . ':' . $tenantId . ':' . now()->format('Ymd');
        $used = Cache::increment($key);
        if ($used === 1) {
            Cache::put($key, 1, now()->endOfDay());
        }

        if ($used > $limit) {
            throw new RuntimeException('Limite diário de consultas do tenant atingido.');
        }
    }

    private function resolveTenantAndLimit(string $tipo): array
    {
        if (!App::has('current_tenant')) {
            return [0, 0];
        }

        $tenant = App::get('current_tenant');
        $tenantId = (int) ($tenant->id ?? 0);

        $integracoes = is_array($tenant->configuracoes_integracoes ?? null) ? $tenant->configuracoes_integracoes : [];
        $consultas = is_array($integracoes['consultas'] ?? null) ? $integracoes['consultas'] : [];

        $limiteTipoTenant = (int) ($consultas['limite_diario_' . $tipo] ?? 0);
        $limiteTenant = (int) ($consultas['limite_diario'] ?? 0);
        $limiteTipoGlobal = (int) config('services.consultas.max_consultas_diarias_' . $tipo, 0);
        $limiteGlobal = (int) config('services.consultas.max_consultas_diarias_por_tenant', 0);

        $limite = $limiteTipoTenant > 0
            ? $limiteTipoTenant
            : ($limiteTenant > 0
                ? $limiteTenant
                : ($limiteTipoGlobal > 0 ? $limiteTipoGlobal : $limiteGlobal));

        return [$tenantId, $limite];
    }

    private function registerHistory(string $tipo, string $cpf, string $status, ?string $origem = null, ?string $mensagem = null, array $meta = []): void
    {
        [$tenantId] = $this->resolveTenantAndLimit($tipo);
        if ($tenantId <= 0) {
            return;
        }

        ConsultaHistorico::query()->create([
            'prefeitura_id' => $tenantId,
            'tipo' => $tipo,
            'status' => $status,
            'origem' => $origem,
            'cpf_consultado' => $this->sanitizeCpf($cpf) ?: null,
            'cpf_hash' => $cpf !== '' ? hash('sha256', $cpf) : null,
            'mensagem' => $mensagem,
            'meta' => empty($meta) ? null : $meta,
            'consultado_em' => now(),
        ]);
    }
}
