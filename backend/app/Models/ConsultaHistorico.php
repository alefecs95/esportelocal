<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsultaHistorico extends Model
{
    use HasFactory;

    protected $table = 'consulta_historicos';

    protected $fillable = [
        'prefeitura_id',
        'tipo',
        'status',
        'origem',
        'cpf_consultado',
        'cpf_hash',
        'mensagem',
        'meta',
        'consultado_em',
    ];

    protected $casts = [
        'meta' => 'array',
        'consultado_em' => 'datetime',
    ];

    public function prefeitura(): BelongsTo
    {
        return $this->belongsTo(Prefeitura::class);
    }
}
