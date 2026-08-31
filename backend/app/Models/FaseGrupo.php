<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class FaseGrupo extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'prefeitura_id',
        'fase_id',
        'nome',
        'ordem',
        'configuracao',
    ];

    protected $casts = [
        'ordem' => 'integer',
        'configuracao' => 'array',
    ];

    public function fase()
    {
        return $this->belongsTo(Fase::class);
    }

    public function partidas()
    {
        return $this->hasMany(Partida::class, 'fase_grupo_id');
    }
}
