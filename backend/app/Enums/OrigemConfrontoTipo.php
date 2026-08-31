<?php

namespace App\Enums;

enum OrigemConfrontoTipo: string
{
    case MANUAL = 'MANUAL';
    case EQUIPE = 'EQUIPE';
    case GROUP_POSITION = 'GROUP_POSITION';
    case MATCH_WINNER = 'MATCH_WINNER';
    case MATCH_LOSER = 'MATCH_LOSER';
    case BEST_THIRD = 'BEST_THIRD';
    case BEST_LOSER = 'BEST_LOSER';
    case BYE = 'BYE';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
