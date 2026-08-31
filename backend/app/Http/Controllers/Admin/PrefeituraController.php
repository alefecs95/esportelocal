<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ConsultaHistorico;
use App\Models\Prefeitura;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use App\Services\TenantFileService;

class PrefeituraController extends Controller
{
    protected $fileService;

    public function __construct(TenantFileService $fileService)
    {
        $this->fileService = $fileService;
    }

    public function index()
    {
        $q = request()->query('q');
        
        // Se passar per_page=-1, traz tudo (dentro de um limite razoável ou total)
        $perPage = request()->query('per_page');

        $query = Prefeitura::query();

        if ($q) {
            $query->where(function($builder) use ($q) {
                $builder->where('nome', 'like', "%{$q}%")
                        ->orWhere('slug', 'like', "%{$q}%")
                        ->orWhere('dominio', 'like', "%{$q}%");
            });
        }

        // Se per_page for -1, retorna todos sem paginação
        if ($perPage == -1) {
            $list = $query->orderBy('nome')->get()->map(function (Prefeitura $prefeitura) {
                return $this->enrichPrefeitura($prefeitura);
            });
            return response()->json($list);
        }

        $perPage = (int) ($perPage ?: 10);
        $perPage = max(5, min($perPage, 100)); // Aumentei o limite maximo para 100

        $result = $query->orderBy('nome')->paginate($perPage);
        $result->getCollection()->transform(function (Prefeitura $prefeitura) {
            return $this->enrichPrefeitura($prefeitura);
        });

        return response()->json($result);
    }

    public function show($id)
    {
        $prefeitura = Prefeitura::findOrFail($id);
        return response()->json($this->enrichPrefeitura($prefeitura));
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'nome' => 'required|string|max:255',
                'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9-]+$/', Rule::unique('prefeituras')->whereNull('deleted_at')],
                'dominio' => ['nullable', 'string', 'max:255', Rule::unique('prefeituras')->whereNull('deleted_at')],
                'cor_primaria' => 'required|string|max:7',
                'ativo' => 'boolean',
                'logo' => 'nullable|file|image|max:2048',
                'admin_email' => 'required|email|unique:users,email',
                'admin_password' => 'required|string|min:6',
                'limite_consultas_cpf' => 'nullable|integer|min:1|max:1000000',
                'limite_consultas_tse' => 'nullable|integer|min:1|max:1000000',
            ]);

            $integracoes = $this->mergeConsultasConfig([], $validated);
            $data = [
                'nome' => $validated['nome'],
                'slug' => Str::lower(Str::slug($validated['slug'], '-')),
                'dominio' => $validated['dominio'] ?? null,
                'cor_primaria' => $validated['cor_primaria'],
                'ativo' => $request->boolean('ativo', true),
                'configuracoes_integracoes' => $integracoes,
            ];

            // Cria a prefeitura primeiro para ter o ID
            $prefeitura = Prefeitura::create($data);

            // Se tiver logo, faz upload usando o ID da prefeitura recém criada
            if ($request->hasFile('logo')) {
                // Salva em 'tenants/{id}/assets'
                $path = $this->fileService->storeFile($request->file('logo'), 'assets', (string) $prefeitura->id);
                
                // Atualiza a prefeitura com o path do logo
                $prefeitura->update(['logo' => $path]);
            }

            // Criar usuário admin para essa prefeitura
            $admin = new User();
            $admin->prefeitura_id = $prefeitura->id;
            $admin->name = 'Admin ' . $prefeitura->nome;
            $admin->email = $validated['admin_email'];
            $admin->password = Hash::make($validated['admin_password']);
            $admin->perfil = 'ADMIN';
            $admin->status = 'ATIVO';
            $admin->cpf = preg_replace('/[^0-9]/', '', microtime(true)); // CPF fake temporário
            $admin->save();

            return response()->json($this->enrichPrefeitura($prefeitura->fresh()), 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Erro ao criar prefeitura: ' . $e->getMessage());
            return response()->json(['message' => 'Erro interno ao criar prefeitura: ' . $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $prefeitura = Prefeitura::findOrFail($id);

            $validated = $request->validate([
                'nome' => 'string|max:255',
                'slug' => ['string', 'max:255', 'regex:/^[a-z0-9-]+$/', Rule::unique('prefeituras')->ignore($prefeitura->id)->whereNull('deleted_at')],
                'dominio' => ['nullable', 'string', 'max:255', Rule::unique('prefeituras')->ignore($prefeitura->id)->whereNull('deleted_at')],
                'cor_primaria' => 'string|regex:/^#[0-9A-Fa-f]{3}(?:[0-9A-Fa-f]{3})?$/',
                'ativo' => 'boolean',
                'logo' => 'nullable|file|image|max:2048',
                'limite_consultas_cpf' => 'nullable|integer|min:1|max:1000000',
                'limite_consultas_tse' => 'nullable|integer|min:1|max:1000000',
            ]);

            if (isset($validated['slug'])) {
                $validated['slug'] = Str::lower(Str::slug($validated['slug'], '-'));
            }

            if ($request->hasFile('logo')) {
                $path = $this->fileService->storeFile($request->file('logo'), 'assets', (string) $prefeitura->id);
                $validated['logo'] = $path;
            }
            
            // Garantir cast correto para boolean se vier string "0" ou "1"
            if ($request->has('ativo')) {
                $validated['ativo'] = $request->boolean('ativo');
            }

            $validated['configuracoes_integracoes'] = $this->mergeConsultasConfig(
                is_array($prefeitura->configuracoes_integracoes) ? $prefeitura->configuracoes_integracoes : [],
                $validated
            );
            unset($validated['limite_consultas_cpf'], $validated['limite_consultas_tse']);

            $prefeitura->update($validated);

            return response()->json($this->enrichPrefeitura($prefeitura->fresh()));
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Erro ao atualizar prefeitura: ' . $e->getMessage());
            return response()->json(['message' => 'Erro interno ao atualizar prefeitura: ' . $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        $prefeitura = Prefeitura::findOrFail($id);
        $prefeitura->delete();

        return response()->json(['message' => 'Prefeitura deletada com sucesso']);
    }

    public function historicoConsultas(Request $request, $id)
    {
        $prefeitura = Prefeitura::findOrFail($id);
        $limit = max(10, min((int) $request->query('limit', 50), 200));

        $query = ConsultaHistorico::query()
            ->where('prefeitura_id', $prefeitura->id)
            ->orderByDesc('consultado_em');

        $tipo = $request->query('tipo');
        if (in_array($tipo, ['cpf', 'tse'], true)) {
            $query->where('tipo', $tipo);
        }

        $status = $request->query('status');
        if (in_array($status, ['sucesso', 'erro'], true)) {
            $query->where('status', $status);
        }

        $inicio = $request->query('inicio');
        if ($inicio) {
            $query->where('consultado_em', '>=', Carbon::parse($inicio)->startOfDay());
        }

        $fim = $request->query('fim');
        if ($fim) {
            $query->where('consultado_em', '<=', Carbon::parse($fim)->endOfDay());
        }

        $historico = $query->paginate($limit);

        $hoje = Carbon::now();
        $resumoBase = ConsultaHistorico::query()->where('prefeitura_id', $prefeitura->id);
        $resumoHoje = ConsultaHistorico::query()
            ->where('prefeitura_id', $prefeitura->id)
            ->whereDate('consultado_em', $hoje->toDateString());

        return response()->json([
            'prefeitura_id' => $prefeitura->id,
            'resumo' => [
                'total' => $resumoBase->count(),
                'total_hoje' => $resumoHoje->count(),
                'cpf_total' => (clone $resumoBase)->where('tipo', 'cpf')->count(),
                'tse_total' => (clone $resumoBase)->where('tipo', 'tse')->count(),
                'cpf_hoje' => (clone $resumoHoje)->where('tipo', 'cpf')->count(),
                'tse_hoje' => (clone $resumoHoje)->where('tipo', 'tse')->count(),
            ],
            'historico' => $historico,
        ]);
    }

    private function enrichPrefeitura(Prefeitura $prefeitura): array
    {
        $payload = $prefeitura->toArray();
        $integracoes = is_array($prefeitura->configuracoes_integracoes) ? $prefeitura->configuracoes_integracoes : [];
        $consultas = is_array($integracoes['consultas'] ?? null) ? $integracoes['consultas'] : [];

        $payload['limite_consultas_cpf'] = (int) ($consultas['limite_diario_cpf'] ?? config('services.consultas.max_consultas_diarias_cpf', 200));
        $payload['limite_consultas_tse'] = (int) ($consultas['limite_diario_tse'] ?? config('services.consultas.max_consultas_diarias_tse', 200));

        return $payload;
    }

    private function mergeConsultasConfig(array $currentIntegracoes, array $validated): array
    {
        $integracoes = $currentIntegracoes;
        $consultas = is_array($integracoes['consultas'] ?? null) ? $integracoes['consultas'] : [];

        if (array_key_exists('limite_consultas_cpf', $validated) && $validated['limite_consultas_cpf'] !== null) {
            $consultas['limite_diario_cpf'] = (int) $validated['limite_consultas_cpf'];
        }

        if (array_key_exists('limite_consultas_tse', $validated) && $validated['limite_consultas_tse'] !== null) {
            $consultas['limite_diario_tse'] = (int) $validated['limite_consultas_tse'];
        }

        $integracoes['consultas'] = $consultas;
        return $integracoes;
    }
}
