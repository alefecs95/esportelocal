<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BelongsToTenant;

class CampeonatoCategoria extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'prefeitura_id',
        'campeonato_id',
        'nome',
        'descricao',
        'genero',
        'idade_min',
        'idade_max',
        'peso_min',
        'peso_max',
        'distancia',
        'valor_inscricao',
        'vagas_limite',
        'competitiva',
        'contribuicao_alimento_kg',
        'aceita_brinquedo',
        'premiacao_descricao',
        'largada_prevista',
        'ordem_premiacao',
    ];

    protected $casts = [
        'idade_min' => 'integer',
        'idade_max' => 'integer',
        'peso_min' => 'decimal:2',
        'peso_max' => 'decimal:2',
        'valor_inscricao' => 'decimal:2',
        'vagas_limite' => 'integer',
        'competitiva' => 'boolean',
        'contribuicao_alimento_kg' => 'decimal:2',
        'aceita_brinquedo' => 'boolean',
        'ordem_premiacao' => 'integer',
    ];

    public function campeonato()
    {
        return $this->belongsTo(Campeonato::class);
    }

    public function inscricoes()
    {
        return $this->hasMany(Inscricao::class, 'campeonato_categoria_id');
    }
}
