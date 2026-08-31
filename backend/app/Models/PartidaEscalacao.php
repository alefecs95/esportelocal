<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PartidaEscalacao extends Model
{
    use HasFactory, BelongsToTenant;

    protected $table = 'partida_escalacoes';

    protected $fillable = [
        'prefeitura_id',
        'partida_id',
        'equipe_id',
        'atleta_id',
        'titular',
        'capitao',
        'ordem',
    ];

    protected $casts = [
        'titular' => 'boolean',
        'capitao' => 'boolean',
        'ordem' => 'integer',
    ];

    public function partida()
    {
        return $this->belongsTo(Partida::class);
    }

    public function equipe()
    {
        return $this->belongsTo(Equipe::class);
    }

    public function atleta()
    {
        return $this->belongsTo(Atleta::class);
    }
}
