<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class RankingEquipe extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'prefeitura_id',
        'temporada_id',
        'modalidade_id',
        'equipe_id',
        'pontos',
        'participacoes',
        'titulos',
    ];

    public function temporada()
    {
        return $this->belongsTo(Temporada::class);
    }

    public function modalidade()
    {
        return $this->belongsTo(Modalidade::class);
    }

    public function equipe()
    {
        return $this->belongsTo(Equipe::class);
    }
}
