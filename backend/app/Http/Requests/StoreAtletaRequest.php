<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAtletaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
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
