<?php

namespace App\Http\Requests;

use App\Models\Campeonato;
use App\Services\CadastroCamposService;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\App;
use Illuminate\Validation\Rule;

class StoreAtletaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user('sanctum') ?? $this->user();

        return $user && $user->isTenantAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $campeonatoId = $this->input('campeonato_id');
        if ($campeonatoId) {
            $campeonato = Campeonato::find($campeonatoId);
            if ($campeonato) {
                $tenantId = App::has('current_tenant') ? App::get('current_tenant')?->id : ($this->user()?->prefeitura_id ?? null);
                /** @var CadastroCamposService $service */
                $service = app(CadastroCamposService::class);
                return $service->buildAtletaValidationRules($campeonato, $this->all(), $tenantId, false);
            }
        }

        return [
            'nome' => 'required|string|max:255',
            'cpf' => 'required|string|unique:atletas,cpf|max:14',
            'data_nascimento' => 'required|date',
            'sexo' => 'nullable|string|max:20',
            'nome_mae' => 'required|string|max:255',
            'titulo_eleitor' => 'nullable|string|max:20',
            'cep' => 'required|string|max:9',
            'endereco' => 'required|string|max:255',
            'bairro' => 'required|string|max:100',
            'cidade' => 'required|string|max:100',
            'estado' => 'required|string|max:2',
            'numero' => 'nullable|string|max:20',
            'complemento' => 'nullable|string|max:255',
            'nome_pai' => 'nullable|string|max:255',
            'responsavel_nome' => 'nullable|string|max:255',
            'responsavel_cpf' => 'nullable|string|max:14',
            'responsavel_telefone' => 'nullable|string|max:20',
            'tamanho_camiseta' => 'nullable|string|max:10',
            'contato_emergencia' => 'nullable|string|max:255',
            'zona_eleitoral' => [
                Rule::requiredIf(fn () => $this->isMaiorOuIgual16()),
                'nullable',
                'string',
                'max:10',
            ],
            'secao_eleitoral' => [
                Rule::requiredIf(fn () => $this->isMaiorOuIgual16()),
                'nullable',
                'string',
                'max:10',
            ],
            'bairro_eleitoral' => [
                Rule::requiredIf(fn () => $this->isMaiorOuIgual16()),
                'nullable',
                'string',
                'max:100',
            ],
            'cidade_eleitoral' => [
                Rule::requiredIf(fn () => $this->isMaiorOuIgual16()),
                'nullable',
                'string',
                'max:100',
            ],
            'uf_eleitoral' => [
                Rule::requiredIf(fn () => $this->isMaiorOuIgual16()),
                'nullable',
                'string',
                'max:2',
            ],
            'titulo_verificado' => 'nullable|boolean',
            'validacao_eleitoral_pendente' => 'nullable|boolean',
            'telefone' => 'nullable|string|max:20|required_if:receive_notifications,1,true',
            'email' => 'nullable|email|max:255',
            'receive_notifications' => 'required|boolean',
            'arquivos.*' => 'nullable|file|max:10240', // Max 10MB
            'nomes_arquivos.*' => 'nullable|string|max:255',
            'modalidades' => 'nullable|array',
            'modalidades.*' => 'exists:modalidades,id',
        ];
    }

    private function isMaiorOuIgual16(): bool
    {
        $dataNascimento = $this->input('data_nascimento');
        if (!$dataNascimento) {
            return false;
        }

        try {
            return Carbon::parse($dataNascimento)->age >= 16;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
