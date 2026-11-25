<?php

namespace App\Services;

use App\Models\Campeonato;
use App\Models\Inscricao;
use Illuminate\Support\Facades\DB;

class PremiacaoService
{
    /**
     * Quantidade de premiados por categoria competitiva (pódio).
     */
    public const POSICOES_PREMIADAS = 3;

    /**
     * Monta o pódio de cada categoria competitiva.
     * Regra de não acumular: um atleta já premiado em uma categoria (processadas por ordem_premiacao ASC NULLS LAST, depois id)
     * é pulado e o próximo colocado da categoria seguinte assume a vaga.
     *
     * @return array<int, array{categoria: array, colocados: array, pulados: array}>
     */
    public function montarPodio(Campeonato $campeonato): array
    {
        $categorias = $campeonato->categorias()
            ->where('competitiva', true)
            ->orderByRaw('CASE WHEN ordem_premiacao IS NULL THEN 1 ELSE 0 END ASC')
            ->orderBy('ordem_premiacao', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $jaPremiados = [];
        $resultado = [];

        foreach ($categorias as $categoria) {
            $candidatos = Inscricao::with('atleta:id,nome,cpf')
                ->where('campeonato_id', $campeonato->id)
                ->where('campeonato_categoria_id', $categoria->id)
                ->whereNotNull('tempo_ms')
                ->whereNotNull('colocacao_categoria')
                ->where('status', 'confirmada')
                ->orderBy('colocacao_categoria')
                ->get();

            $colocados = [];
            $pulados = [];
            foreach ($candidatos as $inscricao) {
                $atletaId = (int) $inscricao->atleta_id;
                if (isset($jaPremiados[$atletaId])) {
                    $pulados[] = [
                        'inscricao_id' => $inscricao->id,
                        'atleta_id' => $atletaId,
                        'atleta_nome' => $inscricao->atleta?->nome,
                        'numero_peito' => $inscricao->numero_peito,
                        'colocacao_original' => $inscricao->colocacao_categoria,
                        'categoria_premiada_antes' => $jaPremiados[$atletaId],
                    ];
                    continue;
                }

                $colocados[] = [
                    'inscricao_id' => $inscricao->id,
                    'atleta_id' => $atletaId,
                    'atleta_nome' => $inscricao->atleta?->nome,
                    'numero_peito' => $inscricao->numero_peito,
                    'colocacao_categoria' => count($colocados) + 1,
                    'tempo_visual' => $inscricao->tempo_visual,
                    'premiado_em' => $inscricao->premiado_em?->toIso8601String(),
                    'premiacao_descricao' => $inscricao->premiacao_descricao ?: $categoria->premiacao_descricao,
                ];

                $jaPremiados[$atletaId] = $categoria->nome;

                if (count($colocados) >= self::POSICOES_PREMIADAS) {
                    break;
                }
            }

            $resultado[] = [
                'categoria' => [
                    'id' => $categoria->id,
                    'nome' => $categoria->nome,
                    'distancia' => $categoria->distancia,
                    'ordem_premiacao' => $categoria->ordem_premiacao,
                    'premiacao_descricao' => $categoria->premiacao_descricao,
                ],
                'colocados' => $colocados,
                'pulados' => $pulados,
            ];
        }

        return $resultado;
    }

    /**
     * Registra a premiação de uma inscrição. Bloqueia se o atleta já estiver premiado no campeonato.
     */
    public function registrarPremiacao(Inscricao $inscricao, ?string $descricao = null): Inscricao
    {
        return DB::transaction(function () use ($inscricao, $descricao) {
            $inscricao = Inscricao::where('id', $inscricao->id)->lockForUpdate()->firstOrFail();

            $jaPremiado = Inscricao::where('campeonato_id', $inscricao->campeonato_id)
                ->where('atleta_id', $inscricao->atleta_id)
                ->where('id', '!=', $inscricao->id)
                ->whereNotNull('premiado_em')
                ->exists();

            if ($jaPremiado) {
                throw new \InvalidArgumentException('Este atleta já foi premiado nesta competição. Ninguém acumula premiação.');
            }

            if ($inscricao->colocacao_categoria === null || $inscricao->tempo_ms === null) {
                throw new \InvalidArgumentException('Somente atletas com tempo registrado podem ser premiados.');
            }

            $inscricao->update([
                'premiado_em' => now(),
                'premiacao_descricao' => $descricao ?: $inscricao->categoria?->premiacao_descricao,
            ]);

            return $inscricao->fresh(['atleta', 'categoria']);
        });
    }

    /**
     * Lista de medalha finisher: inscritos com finalizou_em preenchido (inclui Kids/Teens).
     */
    public function medalhasFinisher(Campeonato $campeonato)
    {
        return Inscricao::with(['atleta:id,nome,sexo', 'categoria:id,nome'])
            ->where('campeonato_id', $campeonato->id)
            ->whereNotNull('finalizou_em')
            ->orderBy('numero_peito')
            ->get();
    }

    /**
     * Marcação manual de chegada (principalmente Kids/Teens sem cronometragem).
     */
    public function marcarFinalizacaoManual(Inscricao $inscricao): Inscricao
    {
        if ($inscricao->kit_retirado_em === null) {
            throw new \InvalidArgumentException('Somente atletas que retiraram o kit podem ter a chegada registrada.');
        }

        $inscricao->update(['finalizou_em' => $inscricao->finalizou_em ?? now()]);

        return $inscricao->fresh(['atleta', 'categoria']);
    }
}
