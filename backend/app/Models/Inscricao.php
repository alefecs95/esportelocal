<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BelongsToTenant;

class Inscricao extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

    protected $table = 'inscricoes';

    protected $fillable = [
        'prefeitura_id',
        'atleta_id',
        'campeonato_id',
        'campeonato_categoria_id',
        'status',
        'status_pagamento',
        'valor_pago',
        'data_pagamento',
        'data_inscricao',
        'colocacao',
        'tempo_visual',
        'tempo_ms',
        'aceitou_termos_imagem_em',
        'aceitou_regulamento_em',
    ];

    protected $casts = [
        'valor_pago' => 'decimal:2',
        'data_pagamento' => 'date',
        'data_inscricao' => 'datetime',
        'aceitou_termos_imagem_em' => 'datetime',
        'aceitou_regulamento_em' => 'datetime',
    ];

    public function atleta()
    {
        return $this->belongsTo(Atleta::class);
    }

    public function campeonato()
    {
        return $this->belongsTo(Campeonato::class);
    }

    public function categoria()
    {
        return $this->belongsTo(CampeonatoCategoria::class, 'campeonato_categoria_id');
    }
}
