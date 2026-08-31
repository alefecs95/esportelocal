<?php

namespace App\Services;

use App\Models\ConsultaHistorico;
use App\Models\CpfCache;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class CpfService
{
    public function getCpfData(string $cpf): array
    {
        $cpfLimpo = $this->sanitizeCpf($cpf);

        try {
            if (!$this->isValidCpf($cpfLimpo)) {
                throw new RuntimeException('CPF inválido para consulta.');
            }

            $cache = CpfCache::query()->where('cpf', $cpfLimpo)->orderByDesc('updated_at')->first();
            if ($cache) {
                $payload = $this->toPayload($cache->toArray(), 'cache');
                $this->registerHistory('cpf', $cpfLimpo, 'sucesso', 'cache', null, [
                    'source' => 'cache',
                    'nome_consultado' => (string) ($payload['nome'] ?? ''),
                ]);
                return $payload;
            }

            $payload = $this->runWithLock($cpfLimpo, function () use ($cpfLimpo) {
                $existing = CpfCache::query()->where('cpf', $cpfLimpo)->orderByDesc('updated_at')->first();
                if ($existing) {
                    return $this->toPayload($existing->toArray(), 'cache');
                }

                $this->assertTenantQuota('cpf');
                $apiData = $this->consultarApiCpf($cpfLimpo);

                if ($this->isIndeterminado($apiData)) {
                    throw new RuntimeException('CPF indeterminado na API externa.');
                }

                $mapped = $this->mapCpfData($apiData, $cpfLimpo);
                if (!$this->isComplete($mapped)) {
                    throw new RuntimeException('A API de CPF retornou dados incompletos.');
                }

                try {
                    $created = CpfCache::query()->create([
                        'cpf' => $mapped['cpf'],
                        'nome' => $mapped['nome'],
                        'data_nascimento' => $mapped['data_nascimento'],
                        'sexo' => $mapped['sexo'],
                        'dados_json' => $apiData,
                    ]);
                } catch (QueryException $e) {
                    $existing = CpfCache::query()->where('cpf', $cpfLimpo)->orderByDesc('updated_at')->first();
                    if ($existing) {
                        return $this->toPayload($existing->toArray(), 'cache');
                    }
                    throw $e;
                }

                return $this->toPayload($created->toArray(), 'api');
            });

            $this->registerHistory('cpf', $cpfLimpo, 'sucesso', (string) ($payload['source'] ?? null), null, [
                'source' => $payload['source'] ?? null,
                'nome_consultado' => (string) ($payload['nome'] ?? ''),
            ]);

            return $payload;
        } catch (\Throwable $e) {
            $this->registerHistory('cpf', $cpfLimpo, 'erro', null, $e->getMessage());
            if ($e instanceof RuntimeException) {
                throw $e;
            }
            throw new RuntimeException('Falha ao consultar CPF.');
        }
    }

    private function consultarApiCpf(string $cpf): array
    {
        $url = env('DIRECTD_API_URL');
        $token = env('DIRECTD_API_TOKEN');

        if (!$url || !$token) {
            throw new RuntimeException('API de CPF não configurada.');
        }

        try {
            $response = Http::timeout(12)->get($url, [
                'CPF' => $cpf,
                'Token' => $token,
            ]);
        } catch (\Throwable $e) {
            Log::error('Falha na consulta de CPF', ['cpf' => $cpf, 'error' => $e->getMessage()]);
            throw new RuntimeException('Falha de comunicação com a API de CPF.');
        }

        if (!$response->successful()) {
            Log::error('Erro HTTP na API de CPF', ['cpf' => $cpf, 'status' => $response->status(), 'body' => $response->body()]);
            throw new RuntimeException('API de CPF indisponível no momento.');
        }

        $data = $response->json();
        if (!is_array($data)) {
            throw new RuntimeException('Resposta inválida da API de CPF.');
        }

        $resultadoId = (int) ($data['metaDados']['resultadoId'] ?? 1);
        if ($resultadoId !== 1) {
            $mensagem = (string) ($data['metaDados']['mensagem'] ?? 'Consulta CPF sem sucesso.');
            Log::warning('Consulta CPF sem sucesso', ['cpf' => $cpf, 'mensagem' => $mensagem, 'resultado_id' => $resultadoId]);
            throw new RuntimeException($mensagem);
        }

        return $data;
    }

    private function mapCpfData(array $apiData, string $cpfLimpo): array
    {
        $retorno = $apiData['retorno'] ?? [];
        $cadastro = is_array($retorno['cadastro'] ?? null) ? $retorno['cadastro'] : [];
        $receita = is_array($retorno['receita'] ?? null) ? $retorno['receita'] : [];

        $cpf = $this->sanitizeCpf((string) ($cadastro['cpf'] ?? $receita['numeroCPF'] ?? $cpfLimpo));
        $nome = trim((string) ($cadastro['nome'] ?? $receita['nomePessoaFisica'] ?? ''));
        $dataNascimentoRaw = (string) ($cadastro['dataNascimento'] ?? $receita['dataNascimento'] ?? '');
        $sexoRaw = (string) ($cadastro['sexo'] ?? '');

        return [
            'cpf' => $cpf,
            'nome' => $nome,
            'data_nascimento' => $this->normalizeDate($dataNascimentoRaw),
            'sexo' => $this->normalizeSexo($sexoRaw),
            'nome_mae' => $this->extractNomeMae($cadastro, $receita),
        ];
    }

    private function normalizeDate(string $value): ?string
    {
        $raw = trim(explode(' ', $value)[0] ?? '');
        if ($raw === '') {
            return null;
        }

        if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $raw) === 1) {
            [$d, $m, $y] = explode('/', $raw);
            return sprintf('%04d-%02d-%02d', (int) $y, (int) $m, (int) $d);
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw) === 1) {
            return $raw;
        }

        return null;
    }

    private function normalizeSexo(string $sexo): string
    {
        $s = strtoupper(trim($sexo));
        if (in_array($s, ['M', 'MASCULINO'], true)) {
            return 'MASCULINO';
        }
        if (in_array($s, ['F', 'FEMININO'], true)) {
            return 'FEMININO';
        }
        return '';
    }

    private function isComplete(array $mapped): bool
    {
        return $mapped['cpf'] !== ''
            && $mapped['nome'] !== ''
            && !empty($mapped['data_nascimento'])
            && $mapped['sexo'] !== '';
    }

    private function isIndeterminado(array $apiData): bool
    {
        $mensagem = mb_strtolower((string) ($apiData['metaDados']['mensagem'] ?? ''), 'UTF-8');
        return str_contains($mensagem, 'indeterminado');
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
            return Cache::lock('cpf-cache:' . $cpf, 10)->block(5, $callback);
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

    private function toPayload(array $data, string $source): array
    {
        $dataNascimento = $data['data_nascimento'] ?? null;
        if ($dataNascimento instanceof \DateTimeInterface) {
            $dataNascimento = $dataNascimento->format('Y-m-d');
        }

        return [
            'cpf' => (string) ($data['cpf'] ?? ''),
            'nome' => (string) ($data['nome'] ?? ''),
            'data_nascimento' => $dataNascimento ? (string) $dataNascimento : null,
            'sexo' => (string) ($data['sexo'] ?? ''),
            'nome_mae' => $this->resolveNomeMae($data),
            'dados_json' => $data['dados_json'] ?? null,
            'source' => $source,
        ];
    }

    private function extractNomeMae(array $cadastro, array $receita): string
    {
        $possiveis = [
            $cadastro['nomeMae'] ?? null,
            $cadastro['nome_mae'] ?? null,
            $cadastro['mae'] ?? null,
            $receita['nomeMae'] ?? null,
            $receita['nome_mae'] ?? null,
            $receita['nomeMaePessoaFisica'] ?? null,
            $receita['nomeMaeTitular'] ?? null,
            $receita['nome_mae_titular'] ?? null,
            $receita['mae'] ?? null,
        ];

        foreach ($possiveis as $valor) {
            $texto = trim((string) ($valor ?? ''));
            if ($texto !== '') {
                return $texto;
            }
        }

        return '';
    }

    private function resolveNomeMae(array $data): string
    {
        $nomeMaeDireto = trim((string) ($data['nome_mae'] ?? ''));
        if ($nomeMaeDireto !== '') {
            return $nomeMaeDireto;
        }

        $dadosJson = $data['dados_json'] ?? null;
        if (!is_array($dadosJson)) {
            return '';
        }

        $retorno = is_array($dadosJson['retorno'] ?? null) ? $dadosJson['retorno'] : [];
        $cadastro = is_array($retorno['cadastro'] ?? null) ? $retorno['cadastro'] : [];
        $receita = is_array($retorno['receita'] ?? null) ? $retorno['receita'] : [];

        return $this->extractNomeMae($cadastro, $receita);
    }
}
