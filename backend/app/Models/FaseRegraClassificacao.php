<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FaseRegraClassificacao extends Model
{
    use HasFactory, BelongsToTenant;

    protected $table = 'fase_regras_classificacao';

    protected $fillable = [
        'prefeitura_id',
        'fase_id',
        'criterio',
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
}
