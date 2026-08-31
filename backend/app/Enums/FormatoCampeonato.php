<?php

namespace App\Enums;

enum FormatoCampeonato: string
{
    case MATA_MATA = 'mata_mata';
    case GRUPOS = 'grupos';
    case PONTOS_CORRIDOS = 'pontos_corridos';
    case CORRIDA = 'corrida';

    public function label(): string
    {
        return match($this) {
            self::MATA_MATA => 'Mata-Mata',
            self::GRUPOS => 'Fase de Grupos',
            self::PONTOS_CORRIDOS => 'Pontos Corridos',
            self::CORRIDA => 'Corrida / Maratona',
        };
    }

    public static function toArray(): array
    {
        return array_map(fn($case) => [
            'id' => $case->value,
            'nome' => $case->label()
        ], self::cases());
    }
}
