<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class Modalidade extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'prefeitura_id',
        'nome',
        'tipo',
        'descricao',
        'status',
        'regras_config',
    ];

    protected $casts = [
        'status' => 'boolean',
        'regras_config' => 'array',
    ];

    public function atletas()
    {
        return $this->belongsToMany(Atleta::class, 'atleta_modalidade');
    }

    public function campeonatos()
    {
        return $this->hasMany(Campeonato::class);
    }
}
