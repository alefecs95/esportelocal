<?php

namespace App\Services;

use App\Repositories\AtletaRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use App\Models\Atleta;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Services\TenantFileService;
use Exception;

class AtletaService
{
    protected $atletaRepository;
    protected $fileService;

    public function __construct(AtletaRepository $atletaRepository, TenantFileService $fileService)
    {
        $this->atletaRepository = $atletaRepository;
        $this->fileService = $fileService;
    }

    public function getAll(int $perPage = 10, ?string $search = null, array $filters = []): LengthAwarePaginator
    {
        return $this->atletaRepository->getAll($perPage, $search, $filters);
    }

    public function create(array $data): Atleta
    {
        DB::beginTransaction();
        try {
            $atleta = $this->atletaRepository->create($data);
            
            if (isset($data['arquivos']) && is_array($data['arquivos'])) {
                $this->uploadArquivos($atleta, $data['arquivos']);
            }

            if (isset($data['modalidades']) && is_array($data['modalidades'])) {
                $atleta->modalidades()->sync($data['modalidades']);
            }

            // Criar usuário automaticamente
            $this->criarUsuarioParaAtleta(
                $atleta->cpf,
                $atleta->nome,
                $atleta->acesso_ativo ? 'ATIVO' : 'INATIVO',
                $atleta->prefeitura_id
            );

            DB::commit();

            return $atleta->load(['arquivos', 'modalidades']);
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function criarUsuarioParaAtleta(string $cpf, string $nome, string $status = 'ATIVO', ?int $prefeituraId = null): int
    {
        // Limpar CPF (apenas números)
        $cpfLimpo = preg_replace('/[^0-9]/', '', $cpf);

        // Validar se já existe usuário com esse CPF
        if (
            User::withoutGlobalScope(\App\Scopes\TenantScope::class)
                ->where('cpf', $cpfLimpo)
                ->where('prefeitura_id', $prefeituraId)
                ->exists()
        ) {
            throw new Exception("Usuário já cadastrado para este CPF.");
        }

        // Extrair últimos 4 dígitos para senha
        $senha = substr($cpfLimpo, -4);

        // Criar usuário
        $user = User::create([
            'name' => $nome,
            'cpf' => $cpfLimpo,
            'password' => Hash::make($senha),
            'perfil' => 'ATLETA',
            'status' => $status,
            'forcar_troca_senha' => true,
            'prefeitura_id' => $prefeituraId,
        ]);

        return $user->id;
    }

    public function find(int $id): ?Atleta
    {
        $atleta = $this->atletaRepository->find($id);
        if (!$atleta) {
            return null;
        }

        try {
            $atleta->load(['arquivos']);
        } catch (Exception $e) {
            Log::warning('Falha ao carregar arquivos do atleta', [
                'atleta_id' => $id,
                'error' => $e->getMessage(),
            ]);
        }

        try {
            $atleta->load(['modalidades']);
        } catch (Exception $e) {
            Log::warning('Falha ao carregar modalidades do atleta', [
                'atleta_id' => $id,
                'error' => $e->getMessage(),
            ]);
        }

        return $atleta;
    }

    public function update(int $id, array $data): Atleta
    {
        $atleta = $this->atletaRepository->find($id);
        if (!$atleta) {
            throw new Exception("Atleta não encontrado.");
        }
        
        $this->atletaRepository->update($atleta, $data);

        if (isset($data['arquivos']) && is_array($data['arquivos'])) {
            $this->uploadArquivos($atleta, $data['arquivos']);
        }

        if (isset($data['modalidades']) && is_array($data['modalidades'])) {
            $atleta->modalidades()->sync($data['modalidades']);
        }

        return $atleta->load(['arquivos', 'modalidades']);
    }

    protected function uploadArquivos(Atleta $atleta, array $arquivos)
    {
        foreach ($arquivos as $arquivoData) {
            // $arquivoData deve conter 'file' (UploadedFile) e 'nome' (string)
            if (isset($arquivoData['file']) && $arquivoData['file'] instanceof \Illuminate\Http\UploadedFile) {
                $file = $arquivoData['file'];
                $nome = $arquivoData['nome'] ?? $file->getClientOriginalName();
                
                // Usa o TenantFileService para salvar no contexto do tenant
                $path = $this->fileService->storeFile($file, 'atletas/' . $atleta->id);

                $atleta->arquivos()->create([
                    'nome' => $nome,
                    'path' => $path,
                    'tipo' => $file->getMimeType(),
                ]);
            }
        }
    }

    public function delete(int $id): bool
    {
        $atleta = $this->atletaRepository->find($id);
        if (!$atleta) {
            throw new Exception("Atleta não encontrado.");
        }
        return $this->atletaRepository->delete($atleta);
    }

    public function consultarCpf(string $cpf)
    {
        $url = env('DIRECTD_API_URL');
        $token = env('DIRECTD_API_TOKEN');
        $cpfLimpo = preg_replace('/[^0-9]/', '', $cpf);

        if (strlen($cpfLimpo) !== 11) {
            throw new Exception("CPF inválido para consulta.");
        }

        if (!$url || !$token) {
            Log::warning("API de CPF não configurada. Retornando fallback para preenchimento manual.", ['cpf' => $cpfLimpo]);
            return $this->buildCpfFallback($cpfLimpo);
        }

        Log::info("Consultando CPF na API externa", ['url' => $url, 'cpf' => $cpfLimpo]);

        try {
            $response = Http::timeout(10)->get($url, [
                'CPF' => $cpfLimpo,
                'Token' => $token
            ]);
    
            if ($response->successful()) {
                $data = $response->json();
                Log::info("Retorno API CPF", ['status' => $response->status(), 'data' => $data]);
                if (!is_array($data)) {
                    return $this->buildCpfFallback($cpfLimpo);
                }
                $resultadoId = (int) ($data['metaDados']['resultadoId'] ?? 1);
                if ($resultadoId !== 1) {
                    Log::warning('Consulta CPF sem sucesso na Directd', [
                        'cpf' => $cpfLimpo,
                        'resultado_id' => $resultadoId,
                        'mensagem' => $data['metaDados']['mensagem'] ?? null,
                    ]);
                    return $this->buildCpfFallback($cpfLimpo);
                }
                return $data;
            }

            Log::error("Erro API CPF", ['status' => $response->status(), 'body' => $response->body()]);
            return $this->buildCpfFallback($cpfLimpo);

        } catch (\Exception $e) {
            Log::error("Exceção ao consultar CPF", ['message' => $e->getMessage()]);
            return $this->buildCpfFallback($cpfLimpo);
        }
    }

    private function buildCpfFallback(string $cpfLimpo): array
    {
        return [
            'fallback' => true,
            'retorno' => [
                'receita' => [
                    'numeroCPF' => $cpfLimpo,
                    'nomePessoaFisica' => '',
                    'dataNascimento' => null,
                    'situacaoCadastral' => ''
                ]
            ]
        ];
    }

    public function consultarTitulo(array $dados)
    {
        $url = env('DIRECTD_API_TSE_URL');
        $token = env('DIRECTD_API_TOKEN');

        if (!$url || !$token) {
            throw new Exception("API URL do TSE ou Token não configurados.");
        }

        $dataNascimento = $this->normalizeDataNascimentoToDirectd($dados['data_nascimento'] ?? null);
        $cpfLimpo = preg_replace('/[^0-9]/', '', (string) ($dados['cpf'] ?? ''));
        $nome = trim((string) ($dados['nome'] ?? ''));
        $nomeMae = trim((string) ($dados['nome_mae'] ?? ''));

        if ($nome === '' && $cpfLimpo === '') {
            throw new Exception("Dados insuficientes para consulta de título.");
        }

        $params = ['TOKEN' => $token];
        $modoConsulta = '';

        if ($nome !== '') {
            if ($dataNascimento === null || $nomeMae === '') {
                throw new Exception("Dados insuficientes para consulta de título.");
            }
            $params['NOME'] = mb_strtoupper($nome, 'UTF-8');
            $params['DATANASCIMENTO'] = $dataNascimento;
            $params['NOMEMAE'] = mb_strtoupper($nomeMae, 'UTF-8');
            $modoConsulta = 'NOME';
        } else {
            $params['CPF'] = $cpfLimpo;
            $modoConsulta = 'CPF';
        }

        Log::info('Consultando TSE', [
            'url' => $url,
            'cpf' => $cpfLimpo,
            'nome' => $nome,
            'data_nascimento' => $dataNascimento,
            'modo_consulta' => $modoConsulta,
            'nome_informado' => $nome !== '',
            'nome_mae_informado' => $nomeMae !== ''
        ]);

        try {
            $response = Http::connectTimeout(8)
                ->timeout(20)
                ->retry(1, 400, function ($exception) {
                    return $exception instanceof ConnectionException;
                })
                ->get($url, $params);
            
            Log::info('Resposta TSE', ['status' => $response->status(), 'body' => $response->body()]);

            if ($response->successful()) {
                $data = $response->json();
                if (!is_array($data)) {
                    return ['error' => 'A consulta eleitoral retornou um formato inválido.'];
                }

                $resultadoId = (int) ($data['metaDados']['resultadoId'] ?? 1);
                $apiMessage = $data['metaDados']['mensagem'] ?? null;
                if ($resultadoId !== 1) {
                    if ($apiMessage) {
                        return ['error' => 'Não foi possível consultar os dados eleitorais: ' . $apiMessage . '.'];
                    }
                    return ['error' => 'Não foi possível consultar os dados eleitorais no momento.'];
                }

                $retorno = $data['retorno'] ?? null;
                if (!is_array($retorno) || empty($retorno)) {
                    return ['error' => 'A consulta eleitoral não retornou dados válidos.'];
                }

                return $data;
            }

            $payload = $response->json();
            $apiMessage = is_array($payload) ? ($payload['metaDados']['mensagem'] ?? null) : null;

            if ($response->status() === 403 && $apiMessage) {
                return ['error' => 'Serviço de consulta eleitoral indisponível no momento: ' . $apiMessage . '.'];
            }

            if ($apiMessage) {
                return ['error' => 'Não foi possível consultar os dados eleitorais: ' . $apiMessage . '.'];
            }

            return ['error' => 'Não foi possível consultar os dados eleitorais no momento (HTTP ' . $response->status() . ').'];
        } catch (ConnectionException $e) {
            Log::error('Erro na requisição TSE: ' . $e->getMessage());
            return ['error' => 'A consulta eleitoral está indisponível no momento. Tente novamente em alguns minutos.'];
        } catch (\Throwable $e) {
            Log::error('Erro na requisição TSE: ' . $e->getMessage());
            return ['error' => 'Falha ao consultar dados eleitorais. Tente novamente em alguns minutos.'];
        }
    }

    private function normalizeDataNascimentoToDirectd(?string $dataNascimento): ?string
    {
        if (!$dataNascimento) {
            return null;
        }

        $raw = trim(explode(' ', $dataNascimento)[0]);

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw)) {
            return date('d-m-Y', strtotime($raw));
        }

        if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $raw)) {
            return str_replace('/', '-', $raw);
        }

        if (preg_match('/^\d{2}\-\d{2}\-\d{4}$/', $raw)) {
            return $raw;
        }

        return null;
    }
}
