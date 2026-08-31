<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Atleta;
use App\Http\Requests\StoreAtletaRequest;
use App\Http\Requests\UpdateAtletaRequest;
use App\Models\User;
use App\Scopes\TenantScope;
use App\Services\AtletaService;
use App\Services\CpfService;
use App\Services\TseService;
use App\Services\WhatsAppNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AtletaController extends Controller
{
    protected $atletaService;

    public function __construct(
        AtletaService $atletaService,
        private CpfService $cpfService,
        private TseService $tseService,
        private WhatsAppNotificationService $whatsAppNotificationService
    ) {
        $this->atletaService = $atletaService;
    }

    private function response(bool $success, string $message, $data = [], int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => $success,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 10);
        $search = $request->query('search');
        $filters = [
            'acesso_ativo' => $this->parseBooleanQuery($request->query('acesso_ativo')),
            'pode_participar_competicoes' => $this->parseBooleanQuery($request->query('pode_participar_competicoes')),
            'cidade' => $request->query('cidade'),
            'titulo_verificado' => $this->parseBooleanQuery($request->query('titulo_verificado')),
            'idade_min' => $request->query('idade_min'),
            'idade_max' => $request->query('idade_max'),
        ];
        $atletas = $this->atletaService->getAll($perPage, $search, $filters);
        $cpfList = $atletas->getCollection()
            ->map(fn ($atleta) => preg_replace('/\D/', '', (string) ($atleta->cpf ?? '')))
            ->filter()
            ->unique()
            ->values();

        $usersLookup = User::withoutGlobalScope(TenantScope::class)
            ->whereIn('cpf', $cpfList)
            ->get(['cpf', 'prefeitura_id'])
            ->mapWithKeys(fn ($userAtleta) => [$userAtleta->cpf . '|' . $userAtleta->prefeitura_id => true]);

        $atletas->setCollection(
            $atletas->getCollection()->map(function ($atleta) use ($usersLookup) {
                $cpfLimpo = preg_replace('/\D/', '', (string) ($atleta->cpf ?? ''));
                $chave = $cpfLimpo . '|' . $atleta->prefeitura_id;
                $atleta->possui_usuario = (bool) ($usersLookup[$chave] ?? false);
                return $atleta;
            })
        );
        return $this->response(true, 'Atletas listados com sucesso', $atletas);
    }

    public function updateStatus(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return $this->response(false, 'Não autorizado', [], 401);
        }

        $request->validate([
            'acesso_ativo' => 'required|boolean',
            'pode_participar_competicoes' => 'required|boolean',
        ]);

        $atleta = $this->atletaService->find($id);
        if (!$atleta) {
            return $this->response(false, 'Atleta não encontrado', [], 404);
        }

        $acessoAtivo = (bool) $request->boolean('acesso_ativo');
        $podeParticipar = (bool) $request->boolean('pode_participar_competicoes');

        $atleta = $this->atletaService->update((int) $id, [
            'acesso_ativo' => $acessoAtivo,
            'pode_participar_competicoes' => $podeParticipar,
        ]);
        $this->syncAtletaUserStatus($atleta, $acessoAtivo);

        return $this->response(true, 'Status do atleta atualizado com sucesso', $atleta);
    }

    public function store(StoreAtletaRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $receiveNotifications = (bool) ($data['receive_notifications'] ?? false);
            $phoneForNotification = $this->whatsAppNotificationService->normalizePhone($data['telefone'] ?? null);
            unset($data['receive_notifications']);

            $isPublicRegister = $request->user() === null;
            if ($isPublicRegister) {
                $data = $this->applyPublicRegistrationWorkflow($data);
            }

            if ($receiveNotifications && !$phoneForNotification) {
                return $this->response(
                    false,
                    'Para enviar notificação WhatsApp, informe um telefone válido com DDD.',
                    [],
                    422
                );
            }

            // Processar arquivos
            // Espera-se que o frontend envie:
            // arquivos[0][file] = (binary)
            // arquivos[0][nome] = "Nome do documento"
            
            $arquivos = [];
            if ($request->has('arquivos')) {
                // Se vier como array de objetos (dependendo de como o frontend envia)
                // Vamos iterar manualmente para montar a estrutura correta para o Service
                foreach ($request->file('arquivos') ?? [] as $index => $file) {
                    // O nome pode vir em um array paralelo ou dentro da estrutura se for form-data complexo
                    // Simplificando: vamos assumir que o frontend envia 'nomes_arquivos' como array indexado igual
                    $nome = $request->input("nomes_arquivos.$index") ?? $file->getClientOriginalName();
                    
                    $arquivos[] = [
                        'file' => $file,
                        'nome' => $nome
                    ];
                }
            }
            
            $data['arquivos'] = $arquivos;

            $atleta = $this->atletaService->create($data);
            if ($receiveNotifications) {
                $this->whatsAppNotificationService->notifyUserRegistration($atleta);
            }
            return $this->response(true, 'Cadastro realizado com sucesso. Seu login é seu CPF e sua senha inicial são os 4 últimos dígitos do CPF. No primeiro acesso será obrigatória a troca de senha.', $atleta, 201);
        } catch (\Exception $e) {
            $status = str_contains($e->getMessage(), 'dados eleitorais')
                || str_contains($e->getMessage(), 'consulta de título')
                ? 422
                : 500;
            return $this->response(false, 'Erro ao cadastrar atleta: ' . $e->getMessage(), [], $status);
        }
    }

    public function show(string $id): JsonResponse
    {
        $atleta = $this->atletaService->find($id);
        if (!$atleta) {
            return $this->response(false, 'Atleta não encontrado', [], 404);
        }
        return $this->response(true, 'Atleta encontrado com sucesso', $atleta);
    }

    public function update(UpdateAtletaRequest $request, string $id): JsonResponse
    {
        try {
            $atletaAntes = $this->atletaService->find((int) $id);
            if (!$atletaAntes) {
                return $this->response(false, 'Atleta não encontrado', [], 404);
            }

            $cpfAnterior = preg_replace('/\D/', '', (string) $atletaAntes->cpf);
            $data = $request->validated();
            
            $arquivos = [];
            if ($request->has('arquivos')) {
                foreach ($request->file('arquivos') ?? [] as $index => $file) {
                    $nome = $request->input("nomes_arquivos.$index") ?? $file->getClientOriginalName();
                    $arquivos[] = [
                        'file' => $file,
                        'nome' => $nome
                    ];
                }
            }
            $data['arquivos'] = $arquivos;

            $atleta = $this->atletaService->update($id, $data);
            $this->syncAtletaUserCpf($atleta, $cpfAnterior);
            $this->syncAtletaUserStatus($atleta, (bool) $atleta->acesso_ativo);
            return $this->response(true, 'Atleta atualizado com sucesso', $atleta);
        } catch (\Exception $e) {
            return $this->response(false, 'Erro ao atualizar atleta: ' . $e->getMessage(), [], 500);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            $this->atletaService->delete($id);
            return $this->response(true, 'Atleta removido com sucesso', []);
        } catch (\Exception $e) {
            return $this->response(false, 'Erro ao remover atleta: ' . $e->getMessage(), [], 500);
        }
    }

    public function consultarCpf(string $cpf): JsonResponse
    {
        try {
            $cpfLimpo = preg_replace('/[^0-9]/', '', $cpf);
            $atletaLocal = null;
            if (App::has('current_tenant')) {
                $atletaLocal = Atleta::where('cpf', $cpfLimpo)->first();
            }

            if ($atletaLocal) {
                return $this->response(true, 'Atleta já cadastrado no sistema', $atletaLocal);
            }

            $dadosCpf = $this->cpfService->getCpfData($cpfLimpo);
            return $this->response(true, 'Dados consultados com sucesso', $this->mapCpfPayload($dadosCpf));
        } catch (\Exception $e) {
            Log::error('Erro ao consultar CPF', ['cpf' => $cpf, 'error' => $e->getMessage()]);
            $status = str_contains(mb_strtolower($e->getMessage(), 'UTF-8'), 'limite diário') ? 429 : 422;
            return $this->response(false, 'Erro ao consultar CPF: ' . $e->getMessage(), [], $status);
        }
    }

    public function consultarTitulo(Request $request): JsonResponse
    {
        try {
            $cpf = preg_replace('/\D/', '', (string) $request->input('cpf', ''));
            $dataNascimento = trim((string) $request->input('data_nascimento', ''));
            $nomeMae = trim((string) $request->input('nome_mae', ''));
            if ($cpf === '') {
                return $this->response(false, 'CPF é obrigatório para consulta do TSE.', [], 422);
            }
            if ($dataNascimento === '') {
                return $this->response(false, 'Data de nascimento é obrigatória para consulta do TSE.', [], 422);
            }
            if ($nomeMae === '') {
                return $this->response(false, 'Nome da mãe é obrigatório para consulta do TSE.', [], 422);
            }

            $forceRefresh = $request->boolean('force_refresh', false);
            $resultado = $this->tseService->getTseData($cpf, $forceRefresh, [
                'data_nascimento' => $dataNascimento,
                'nome_mae' => $nomeMae,
            ]);
            return $this->response(true, 'Consulta realizada com sucesso', $resultado);
        } catch (\Exception $e) {
            Log::error('Erro ao consultar Título no Controller: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            $message = mb_strtolower($e->getMessage(), 'UTF-8');
            $status = 422;
            if (str_contains($message, 'limite diário')) {
                $status = 429;
            } elseif (
                str_contains($message, 'indisponível')
                || str_contains($message, 'falha de comunicação')
                || str_contains($message, 'não configurada')
            ) {
                return $this->response(true, 'Consulta do TSE indisponível no momento. Preencha os dados eleitorais manualmente.', [
                    'retorno' => null,
                    'manual_required' => true,
                    'source' => 'unavailable',
                ], 200);
            }
            return $this->response(false, 'Erro ao consultar Título: ' . $e->getMessage(), [], $status);
        }
    }

    public function revalidarTitulo(Request $request, string $id): JsonResponse
    {
        try {
            $user = $request->user();
            if (!$user || !in_array($user->perfil, ['ADMIN', 'SUPER_ADMIN'], true)) {
                return $this->response(false, 'Apenas administração pode revalidar título via API.', [], 403);
            }

            $atleta = $this->atletaService->find((int) $id);
            if (!$atleta) {
                return $this->response(false, 'Atleta não encontrado.', [], 404);
            }

            $idade = Carbon::parse($atleta->data_nascimento)->age;
            if ($idade < 16) {
                return $this->response(false, 'Atleta menor de 16 anos não precisa de validação eleitoral.', [], 422);
            }

            try {
                $resultado = $this->tseService->getTseData((string) $atleta->cpf, true, [
                    'data_nascimento' => (string) ($atleta->data_nascimento ?? ''),
                    'nome_mae' => (string) ($atleta->nome_mae ?? ''),
                ]);
            } catch (\Throwable $e) {
                $this->atletaService->update((int) $id, [
                    'titulo_verificado' => false,
                    'validacao_eleitoral_pendente' => true,
                ]);
                return $this->response(false, $e->getMessage(), [], 422);
            }

            $retorno = $resultado['retorno'] ?? null;
            if (!$retorno) {
                $this->atletaService->update((int) $id, [
                    'titulo_verificado' => false,
                    'validacao_eleitoral_pendente' => true,
                ]);
                return $this->response(false, 'A API não retornou dados eleitorais para validação.', [], 422);
            }

            $updateData = $this->extractElectoralFieldsFromRetorno($retorno);
            $dadosCompletos = $this->isElectoralDataComplete($updateData);
            $updateData['titulo_verificado'] = $dadosCompletos;
            $updateData['validacao_eleitoral_pendente'] = !$dadosCompletos;
            $updateData['acesso_ativo'] = $dadosCompletos;
            $updateData['pode_participar_competicoes'] = $dadosCompletos;

            if (!$dadosCompletos) {
                $this->atletaService->update((int) $id, $updateData);
                $atletaAtualizado = $this->atletaService->find((int) $id);
                if ($atletaAtualizado) {
                    $this->syncAtletaUserStatus($atletaAtualizado, false);
                }
                return $this->response(false, 'A API não retornou todos os dados obrigatórios para validar o título.', [], 422);
            }

            $atletaAtualizado = $this->atletaService->update((int) $id, $updateData);
            $this->syncAtletaUserStatus($atletaAtualizado, true);
            return $this->response(true, 'Título validado com sucesso.', $atletaAtualizado);
        } catch (\Exception $e) {
            Log::error('Erro ao revalidar título', ['id' => $id, 'error' => $e->getMessage()]);
            return $this->response(false, 'Erro ao revalidar título: ' . $e->getMessage(), [], 500);
        }
    }

    public function aprovarDadosEleitoraisManualmente(Request $request, string $id): JsonResponse
    {
        try {
            $user = $request->user();
            if (!$user || !in_array($user->perfil, ['ADMIN', 'SUPER_ADMIN'], true)) {
                return $this->response(false, 'Apenas administração pode aprovar dados eleitorais manualmente.', [], 403);
            }

            $atleta = $this->atletaService->find((int) $id);
            if (!$atleta) {
                return $this->response(false, 'Atleta não encontrado.', [], 404);
            }

            $idade = Carbon::parse($atleta->data_nascimento)->age;
            if ($idade < 16) {
                $atletaAtualizado = $this->atletaService->update((int) $id, [
                    'titulo_verificado' => true,
                    'validacao_eleitoral_pendente' => false,
                    'acesso_ativo' => true,
                    'pode_participar_competicoes' => true,
                ]);
                $this->syncAtletaUserStatus($atletaAtualizado, true);
                return $this->response(true, 'Atleta menor de 16 anos aprovado sem validação eleitoral.', $atletaAtualizado);
            }

            $dadosEleitorais = [
                'zona_eleitoral' => (string) ($atleta->zona_eleitoral ?? ''),
                'secao_eleitoral' => (string) ($atleta->secao_eleitoral ?? ''),
                'bairro_eleitoral' => (string) ($atleta->bairro_eleitoral ?? ''),
                'cidade_eleitoral' => (string) ($atleta->cidade_eleitoral ?? ''),
                'uf_eleitoral' => (string) ($atleta->uf_eleitoral ?? ''),
            ];

            if (!$this->isElectoralDataComplete($dadosEleitorais)) {
                return $this->response(false, 'Dados eleitorais incompletos. Preencha zona, seção, município, bairro e UF antes de aprovar manualmente.', [], 422);
            }

            $atletaAtualizado = $this->atletaService->update((int) $id, [
                'titulo_verificado' => true,
                'validacao_eleitoral_pendente' => false,
                'acesso_ativo' => true,
                'pode_participar_competicoes' => true,
            ]);
            $this->syncAtletaUserStatus($atletaAtualizado, true);

            return $this->response(true, 'Dados eleitorais aprovados manualmente com sucesso.', $atletaAtualizado);
        } catch (\Exception $e) {
            Log::error('Erro ao aprovar dados eleitorais manualmente', ['id' => $id, 'error' => $e->getMessage()]);
            return $this->response(false, 'Erro ao aprovar dados eleitorais manualmente: ' . $e->getMessage(), [], 500);
        }
    }

    public function resetPassword(Request $request, string $id): JsonResponse
    {
        try {
            $user = $request->user();
            if (!$user || !in_array($user->perfil, ['ADMIN', 'SUPER_ADMIN'], true)) {
                return $this->response(false, 'Apenas administração pode resetar senha.', [], 403);
            }

            $atleta = $this->atletaService->find((int) $id);
            if (!$atleta) {
                return $this->response(false, 'Atleta não encontrado.', [], 404);
            }

            $userAtleta = $this->findAtletaUser($atleta);

            if (!$userAtleta) {
                return $this->response(false, 'Atleta não possui usuário associado.', [], 422);
            }

            $cpfDigits = preg_replace('/\D/', '', $atleta->cpf);
            if (strlen($cpfDigits) !== 11) {
                return $this->response(false, 'CPF inválido.', [], 422);
            }

            $novaSenha = substr($cpfDigits, -4);
            $userAtleta->password = Hash::make($novaSenha);
            $userAtleta->save();

            return $this->response(true, 'Senha resetada com sucesso.', ['nova_senha' => $novaSenha]);
        } catch (\Exception $e) {
            Log::error('Erro ao resetar senha', ['id' => $id, 'error' => $e->getMessage()]);
            return $this->response(false, 'Erro ao resetar senha: ' . $e->getMessage(), [], 500);
        }
    }

    public function gerarUsuario(Request $request, string $id): JsonResponse
    {
        try {
            $user = $request->user();
            if (!$user || !in_array($user->perfil, ['ADMIN', 'SUPER_ADMIN'], true)) {
                return $this->response(false, 'Apenas administração pode gerar usuário.', [], 403);
            }

            $atleta = $this->atletaService->find((int) $id);
            if (!$atleta) {
                return $this->response(false, 'Atleta não encontrado.', [], 404);
            }

            if ($this->findAtletaUser($atleta)) {
                return $this->response(false, 'Atleta já possui usuário associado.', [], 422);
            }

            $cpfDigits = preg_replace('/\D/', '', $atleta->cpf);
            if (strlen($cpfDigits) !== 11) {
                return $this->response(false, 'CPF inválido.', [], 422);
            }

            $userMesmoNome = $this->findAtletaUserByNome($atleta);
            if ($userMesmoNome) {
                $jaExisteComCpfAtual = User::withoutGlobalScope(TenantScope::class)
                    ->where('cpf', $cpfDigits)
                    ->where('prefeitura_id', $atleta->prefeitura_id)
                    ->where('id', '!=', $userMesmoNome->id)
                    ->exists();

                if ($jaExisteComCpfAtual) {
                    return $this->response(false, 'Já existe outro usuário com o CPF informado para esta prefeitura.', [], 422);
                }

                $novaSenha = substr($cpfDigits, -4);
                $userMesmoNome->cpf = $cpfDigits;
                $userMesmoNome->status = $atleta->acesso_ativo ? 'ATIVO' : 'INATIVO';
                $userMesmoNome->password = Hash::make($novaSenha);
                $userMesmoNome->forcar_troca_senha = true;
                $userMesmoNome->save();

                return $this->response(true, 'Usuário existente atualizado e vinculado com sucesso.', ['nova_senha' => $novaSenha]);
            }

            $this->atletaService->criarUsuarioParaAtleta(
                (string) $atleta->cpf,
                (string) $atleta->nome,
                $atleta->acesso_ativo ? 'ATIVO' : 'INATIVO',
                $atleta->prefeitura_id
            );

            return $this->response(true, 'Usuário gerado com sucesso.', ['nova_senha' => substr($cpfDigits, -4)]);
        } catch (\Exception $e) {
            Log::error('Erro ao gerar usuário para atleta', ['id' => $id, 'error' => $e->getMessage()]);
            return $this->response(false, 'Erro ao gerar usuário: ' . $e->getMessage(), [], 500);
        }
    }

    private function parseBooleanQuery($value): ?bool
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (in_array($value, [true, 'true', '1', 1, 'ATIVO', 'ativo'], true)) {
            return true;
        }

        if (in_array($value, [false, 'false', '0', 0, 'INATIVO', 'inativo'], true)) {
            return false;
        }

        return null;
    }

    private function applyPublicRegistrationWorkflow(array $data): array
    {
        $data['titulo_verificado'] = false;
        $data['validacao_eleitoral_pendente'] = false;

        $dataNascimento = $data['data_nascimento'] ?? null;
        if (!$dataNascimento) {
            return $data;
        }

        $idade = Carbon::parse($dataNascimento)->age;
        if ($idade < 16) {
            $data['titulo_verificado'] = true;
            $data['validacao_eleitoral_pendente'] = false;
            $data['acesso_ativo'] = true;
            $data['pode_participar_competicoes'] = true;
            return $data;
        }

        $data['titulo_verificado'] = (bool) ($data['titulo_verificado'] ?? false);
        $eleitoralManualCompleto = $this->isElectoralDataComplete($data);
        $validado = $data['titulo_verificado'] && $eleitoralManualCompleto;
        $data['titulo_verificado'] = $validado;
        $data['validacao_eleitoral_pendente'] = !$validado;
        $data['acesso_ativo'] = $validado;
        $data['pode_participar_competicoes'] = $validado;

        return $data;
    }

    private function syncAtletaUserStatus(Atleta $atleta, bool $acessoAtivo): void
    {
        $userAtleta = $this->findAtletaUser($atleta);

        if (!$userAtleta) {
            return;
        }

        $userAtleta->status = $acessoAtivo ? 'ATIVO' : 'INATIVO';
        $userAtleta->save();
    }

    private function syncAtletaUserCpf(Atleta $atleta, string $cpfAnterior): void
    {
        $cpfAnterior = preg_replace('/\D/', '', $cpfAnterior);
        $cpfAtual = preg_replace('/\D/', '', (string) $atleta->cpf);

        if ($cpfAnterior === '' || $cpfAtual === '' || $cpfAnterior === $cpfAtual) {
            return;
        }

        $userAtleta = User::withoutGlobalScope(TenantScope::class)
            ->where('cpf', $cpfAnterior)
            ->where('prefeitura_id', $atleta->prefeitura_id)
            ->first();

        if (!$userAtleta) {
            return;
        }

        $conflitoCpf = User::withoutGlobalScope(TenantScope::class)
            ->where('cpf', $cpfAtual)
            ->where('prefeitura_id', $atleta->prefeitura_id)
            ->where('id', '!=', $userAtleta->id)
            ->exists();

        if ($conflitoCpf) {
            return;
        }

        $userAtleta->cpf = $cpfAtual;
        $userAtleta->save();
    }

    private function findAtletaUserByNome(Atleta $atleta): ?User
    {
        $nome = trim((string) $atleta->nome);
        if ($nome === '') {
            return null;
        }

        $candidatos = User::withoutGlobalScope(TenantScope::class)
            ->where('prefeitura_id', $atleta->prefeitura_id)
            ->where('perfil', 'ATLETA')
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($nome, 'UTF-8')])
            ->get();

        if ($candidatos->count() !== 1) {
            return null;
        }

        return $candidatos->first();
    }

    private function findAtletaUser(Atleta $atleta): ?User
    {
        return User::withoutGlobalScope(TenantScope::class)
            ->where('cpf', preg_replace('/[^0-9]/', '', (string) $atleta->cpf))
            ->where('prefeitura_id', $atleta->prefeitura_id)
            ->first();
    }

    private function mapCpfPayload(array $dados): array
    {
        return [
            'nome' => $dados['nome'] ?? '',
            'cpf' => $dados['cpf'] ?? '',
            'data_nascimento' => $dados['data_nascimento'] ?? null,
            'sexo' => $dados['sexo'] ?? '',
            'nome_mae' => $dados['nome_mae'] ?? '',
            'nome_pai' => '',
            'cep' => '',
            'endereco' => '',
            'bairro' => '',
            'cidade' => '',
            'estado' => '',
            'telefone' => '',
            'email' => '',
            'titulo_eleitor' => '',
            'zona_eleitoral' => '',
            'secao_eleitoral' => '',
        ];
    }

    private function extractElectoralFieldsFromRetorno(array $retorno): array
    {
        $domicilio = $retorno['domicilioEleitoral'] ?? [];
        $identificacao = $retorno['identificacao'] ?? [];

        return [
            'titulo_eleitor' => isset($identificacao['inscricao']) ? (string) $identificacao['inscricao'] : '',
            'zona_eleitoral' => isset($domicilio['zona']) ? trim((string) $domicilio['zona']) : '',
            'secao_eleitoral' => isset($domicilio['secao']) ? trim((string) $domicilio['secao']) : '',
            'bairro_eleitoral' => trim((string) ($domicilio['bairro'] ?? '')),
            'cidade_eleitoral' => trim((string) ($domicilio['municipio'] ?? '')),
            'uf_eleitoral' => strtoupper((string) ($domicilio['uf'] ?? '')),
        ];
    }

    private function isElectoralDataComplete(array $dados): bool
    {
        $obrigatorios = [
            $dados['zona_eleitoral'] ?? '',
            $dados['secao_eleitoral'] ?? '',
            $dados['bairro_eleitoral'] ?? '',
            $dados['cidade_eleitoral'] ?? '',
            $dados['uf_eleitoral'] ?? '',
        ];

        foreach ($obrigatorios as $valor) {
            if (trim((string) $valor) === '') {
                return false;
            }
        }

        return true;
    }
}
