<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Fase extends Model
{
    use HasFactory;

    protected $fillable = [
        'campeonato_id',
        'nome',
        'tipo',
        'ordem',
        'status',
        'configuracao',
        'permite_edicao_manual',
    ];

    protected $casts = [
        'ordem' => 'integer',
        'configuracao' => 'array',
        'permite_edicao_manual' => 'boolean',
    ];

    public function campeonato()
    {
        return $this->belongsTo(Campeonato::class);
    }

    public function partidas()
    {
        return $this->hasMany(Partida::class);
    }

    public function grupos()
    {
        return $this->hasMany(FaseGrupo::class)->orderBy('ordem');
    }

    public function regrasClassificacao()
    {
        return $this->hasMany(FaseRegraClassificacao::class)->orderBy('ordem');
    }
}
