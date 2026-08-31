<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Inscricao;
use App\Models\Atleta;
use App\Models\Campeonato;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class InscricaoController extends Controller
{
    private function response(bool $success, string $message, $data = [], int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => $success,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    public function indexByCampeonato($id): JsonResponse
    {
        $inscricoes = Inscricao::with(['atleta', 'categoria'])
            ->where('campeonato_id', $id)
            ->get();

        return $this->response(true, 'Inscrições listadas com sucesso', $inscricoes);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'atleta_id' => 'required|exists:atletas,id',
            'campeonato_id' => 'required|exists:campeonatos,id',
            'campeonato_categoria_id' => 'nullable|exists:campeonato_categorias,id',
        ]);

        $atleta = Atleta::find($request->atleta_id);
        $campeonato = Campeonato::with('categorias')->find($request->campeonato_id);

        if (!$atleta->pode_participar_competicoes) {
            return $this->response(false, 'Atleta está impedido de participar de competições.', [], 422);
        }

        // 0. Validar status do campeonato
        if ($campeonato->status !== 'inscricoes_abertas') {
            return $this->response(false, 'Inscrições não estão abertas para esta competição.', [], 422);
        }

        if ($campeonato->inicio_inscricao && now()->lt(\Carbon\Carbon::parse($campeonato->inicio_inscricao))) {
            return $this->response(false, 'Período de inscrição ainda não iniciou.', [], 422);
        }

        if ($campeonato->fim_inscricao && now()->gt(\Carbon\Carbon::parse($campeonato->fim_inscricao)->endOfDay())) {
            return $this->response(false, 'Período de inscrição já foi encerrado.', [], 422);
        }

        // 1. Verificar duplicidade
        $exists = Inscricao::where('atleta_id', $atleta->id)
            ->where('campeonato_id', $campeonato->id)
            ->where('status', '!=', 'cancelada') // Permite reinscrever se foi cancelada anteriormente? Discutível. Vamos bloquear se já existir qualquer registro ativo.
            ->exists();
        
        if ($exists) {
            // Se já existe e está cancelada, talvez permitir reativar? Por enquanto, bloqueia duplicidade simples.
            // Para ser mais preciso: se existe uma inscrição ativa (pendente/confirmada), bloqueia.
            // Se existe uma cancelada, poderia permitir nova. Vamos ajustar a query.
             $activeExists = Inscricao::where('atleta_id', $atleta->id)
                ->where('campeonato_id', $campeonato->id)
                ->whereIn('status', ['pendente', 'confirmada'])
                ->exists();
             
             if ($activeExists) {
                 return $this->response(false, 'Atleta já possui inscrição ativa neste campeonato.', [], 422);
             }
        }

        // 2. Validar limite de participantes
        if ($campeonato->limite_participantes > 0) {
            $totalInscritos = Inscricao::where('campeonato_id', $campeonato->id)
                ->whereIn('status', ['pendente', 'confirmada'])
                ->count();
            
            if ($totalInscritos >= $campeonato->limite_participantes) {
                return $this->response(false, 'Limite de participantes atingido.', [], 422);
            }
        }

        // 3. Validar elegibilidade Global
        if (!$this->validarElegibilidade($atleta, $campeonato)) {
            return $this->response(false, 'Atleta não atende aos critérios do campeonato.', [], 422);
        }

        // 4. Validar Categoria Específica
        $categoriaId = null;
        if ($campeonato->categorias->isNotEmpty()) {
             $idade = Carbon::parse($atleta->data_nascimento)->age;
             $sexoAtleta = strtolower($atleta->sexo); // 'masculino', 'feminino', 'outro'
             
             $matchingCategories = $campeonato->categorias->filter(function($cat) use ($idade, $sexoAtleta) {
                 // Check Gender
                 if ($cat->genero) {
                     $catGenero = strtolower($cat->genero);
                     if ($catGenero !== 'misto' && $catGenero !== $sexoAtleta) {
                         return false;
                     }
                 }
                 // Check Age
                 if ($cat->idade_min && $idade < $cat->idade_min) return false;
                 if ($cat->idade_max && $idade > $cat->idade_max) return false;
                 
                 return true;
             });
             
             if ($matchingCategories->isEmpty()) {
                 return $this->response(false, 'Atleta não se encaixa em nenhuma categoria deste campeonato (Idade/Gênero).', [], 422);
             }
             
             if ($request->campeonato_categoria_id) {
                 if (!$matchingCategories->contains('id', $request->campeonato_categoria_id)) {
                      return $this->response(false, 'Categoria selecionada inválida para este atleta.', [], 422);
                 }
                 $categoriaId = $request->campeonato_categoria_id;
             } else {
                 // Auto-assign the first matching one
                 $categoriaId = $matchingCategories->first()->id;
             }
        }

        // 5. Definir status iniciais
        $status = 'pendente';
        $statusPagamento = 'nao_pago';
        
        if ($campeonato->valor_inscricao == 0) {
            $status = 'confirmada';
            $statusPagamento = 'isento';
        }

        $inscricao = Inscricao::create([
            'atleta_id' => $atleta->id,
            'campeonato_id' => $campeonato->id,
            'campeonato_categoria_id' => $categoriaId,
            'status' => $status,
            'status_pagamento' => $statusPagamento,
        ]);

        return $this->response(true, 'Inscrição realizada com sucesso', $inscricao, 201);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $inscricao = Inscricao::find($id);
        if (!$inscricao) {
            return $this->response(false, 'Inscrição não encontrada', [], 404);
        }

        $request->validate([
            'status' => 'sometimes|in:pendente,confirmada,cancelada,rejeitada',
            'status_pagamento' => 'sometimes|in:nao_pago,pago,isento',
            'valor_pago' => 'nullable|numeric',
            'data_pagamento' => 'nullable|date',
        ]);

        $inscricao->update($request->all());

        return $this->response(true, 'Inscrição atualizada com sucesso', $inscricao);
    }

    private function validarElegibilidade($atleta, $campeonato)
    {
        // idade
        $idade = Carbon::parse($atleta->data_nascimento)->age;

        if ($campeonato->idade_min && $idade < $campeonato->idade_min) {
            return false;
        }

        if ($campeonato->idade_max && $idade > $campeonato->idade_max) {
            return false;
        }

        // cidade eleitoral
        if ($campeonato->cidade_eleitoral && 
            $atleta->cidade_eleitoral !== $campeonato->cidade_eleitoral) {
            return false;
        }

        // bairro eleitoral
        if ($campeonato->bairro_eleitoral && 
            $atleta->bairro_eleitoral !== $campeonato->bairro_eleitoral) {
            return false;
        }

        return true;
    }
}
