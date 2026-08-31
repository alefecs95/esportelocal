<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCampeonatoRequest extends FormRequest
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
            'local' => 'nullable|string|max:255',
            'banner' => 'nullable|file|image|max:5120',
            'modalidade_id' => 'required|exists:modalidades,id',
            'categoria' => 'required|string|max:255',
            'genero' => 'required|in:masculino,feminino,misto',
            'tipo_competicao' => 'required|in:individual,equipe',
            'data_inicio' => 'required|date',
            'data_fim' => 'required|date|after_or_equal:data_inicio',
            'limite_participantes' => 'nullable|integer|min:1',
            'valor_inscricao' => 'nullable|numeric|min:0',
            'regulamento' => 'nullable|string',
            'status' => 'required|in:draft,inscricoes_abertas,inscricoes_encerradas,em_andamento,finalizado',
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
            'categorias' => 'nullable|array',
            'categorias.*.nome' => 'required_with:categorias|string|max:255',
            'categorias.*.genero' => 'nullable|in:M,F,Misto',
            'categorias.*.idade_min' => 'nullable|integer',
            'categorias.*.idade_max' => 'nullable|integer',
            'categorias.*.peso_min' => 'nullable|numeric',
            'categorias.*.peso_max' => 'nullable|numeric',
            'categorias.*.distancia' => 'nullable|string',
            'categorias.*.valor_inscricao' => 'nullable|numeric',
            'categorias.*.vagas_limite' => 'nullable|integer',
        ];
    }
}
