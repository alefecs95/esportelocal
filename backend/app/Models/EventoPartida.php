<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventoPartida extends Model
{
    use HasFactory;

    protected $table = 'eventos_partida';

    protected $fillable = [
        'partida_id',
        'jogador_id',
        'jogador_saiu_id',
        'time_id',
        'request_id',
        'tipo_evento',
        'minuto',
        'observacao',
    ];

    public function partida()
    {
        return $this->belongsTo(Partida::class);
    }

    public function jogador()
    {
        return $this->belongsTo(Atleta::class, 'jogador_id');
    }

    public function jogadorSaiu()
    {
        return $this->belongsTo(Atleta::class, 'jogador_saiu_id');
    }

    public function time()
    {
        return $this->belongsTo(Equipe::class, 'time_id');
    }
}
