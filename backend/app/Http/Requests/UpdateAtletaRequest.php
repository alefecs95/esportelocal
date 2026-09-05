<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAtletaRequest extends FormRequest
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
        // Pega o ID do atleta da rota
        $atletaId = $this->route('atleta') ?? $this->route('id');
        
        // Se for um objeto (Model Binding), pega o ID
        if (is_object($atletaId)) {
            $atletaId = $atletaId->id;
        }

        return [
            'nome' => 'sometimes|required|string|max:255',
            'cpf' => 'sometimes|required|string|max:14|unique:atletas,cpf,' . $atletaId,
            'data_nascimento' => 'sometimes|required|date',
            'sexo' => 'nullable|string|max:20',
            'nome_mae' => 'sometimes|required|string|max:255',
            'titulo_eleitor' => 'nullable|string|max:20',
            'cep' => 'sometimes|required|string|max:9',
            'endereco' => 'sometimes|required|string|max:255',
            'bairro' => 'sometimes|required|string|max:100',
            'cidade' => 'sometimes|required|string|max:100',
            'estado' => 'sometimes|required|string|max:2',
            'numero' => 'nullable|string|max:20',
            'complemento' => 'nullable|string|max:255',
            'nome_pai' => 'nullable|string|max:255',
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
            'telefone' => 'nullable|string|max:20',
            'receive_notifications' => 'nullable|boolean',
            'email' => 'nullable|email|max:255',
            'arquivos.*' => 'nullable|file|max:10240',
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
