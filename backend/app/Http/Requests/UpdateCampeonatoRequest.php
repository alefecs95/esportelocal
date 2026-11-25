<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCampeonatoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user('sanctum') ?? $this->user();

        return $user && $user->isTenantAdmin();
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('campos_cadastro'))) {
            $decoded = json_decode($this->input('campos_cadastro'), true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $this->merge(['campos_cadastro' => $decoded]);
            }
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nome' => 'sometimes|required|string|max:255',
            'local' => 'nullable|string|max:255',
            'banner' => 'nullable|file|image|max:5120',
            'modalidade_id' => 'sometimes|required|exists:modalidades,id',
            'categoria' => 'sometimes|required|string|max:255',
            'genero' => 'sometimes|required|in:masculino,feminino,misto',
            'tipo_competicao' => 'sometimes|required|in:individual,equipe',
            'data_inicio' => 'sometimes|required|date',
            'data_fim' => 'sometimes|required|date|after_or_equal:data_inicio',
            'limite_participantes' => 'nullable|integer|min:1',
            'valor_inscricao' => 'nullable|numeric|min:0',
            'regulamento' => 'nullable|string',
            'status' => 'sometimes|required|in:draft,inscricoes_abertas,inscricoes_encerradas,em_andamento,finalizado',
            'idade_min' => 'nullable|integer|min:0',
            'idade_max' => 'nullable|integer|min:0|gte:idade_min',
            'cidade_eleitoral' => 'nullable|string|max:255',
            'bairro_eleitoral' => 'nullable|string|max:255',
            'permite_inscricao_online' => 'boolean',
            'inicio_inscricao' => 'nullable|required_if:permite_inscricao_online,true|date',
            'fim_inscricao' => 'nullable|required_if:permite_inscricao_online,true|date|after_or_equal:inicio_inscricao',
            'rodada_limite_inscricao_atletas' => 'nullable|integer|min:1',
            'suspensao_automatica_ativa' => 'boolean',
            'cartoes_amarelos_para_suspensao' => 'nullable|integer|min:1|max:10',
            'cartao_vermelho_suspende' => 'boolean',
            'modo_cadastro' => 'nullable|in:completo,simplificado,personalizado',
            'campos_cadastro' => 'nullable|array',
            'inscricao_solidaria' => 'nullable|boolean',
            'exige_termo_menor' => 'nullable|boolean',
            'kit_retirada_info' => 'nullable|string',
            'categorias' => 'nullable|array',
            'categorias.*.id' => 'nullable|integer',
            'categorias.*.nome' => 'required_with:categorias|string|max:255',
            'categorias.*.genero' => 'nullable|in:M,F,Misto',
            'categorias.*.idade_min' => 'nullable|integer',
            'categorias.*.idade_max' => 'nullable|integer',
            'categorias.*.peso_min' => 'nullable|numeric',
            'categorias.*.peso_max' => 'nullable|numeric',
            'categorias.*.distancia' => 'nullable|string',
            'categorias.*.valor_inscricao' => 'nullable|numeric',
            'categorias.*.vagas_limite' => 'nullable|integer',
            'categorias.*.competitiva' => 'nullable|boolean',
            'categorias.*.contribuicao_alimento_kg' => 'nullable|numeric|min:0',
            'categorias.*.aceita_brinquedo' => 'nullable|boolean',
            'categorias.*.premiacao_descricao' => 'nullable|string|max:255',
            'categorias.*.largada_prevista' => 'nullable|string|max:20',
            'categorias.*.ordem_premiacao' => 'nullable|integer|min:1',
        ];
    }
}
