<?php

namespace App\Enums;

enum CriterioClassificacaoFase: string
{
    case PONTOS = 'pontos';
    case VITORIAS = 'vitorias';
    case SALDO_GOLS = 'saldo_gols';
    case GOLS_PRO = 'gols_pro';
    case GOLS_CONTRA = 'gols_contra';
    case CONFRONTO_DIRETO = 'confronto_direto';
    case FAIR_PLAY = 'fair_play';
    case SORTEIO = 'sorteio';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
