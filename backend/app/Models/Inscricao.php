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
        'numero_peito',
        'status',
        'status_pagamento',
        'forma_contribuicao',
        'contribuicao_quantidade',
        'contribuicao_recebida_em',
        'kit_retirado_em',
        'kit_retirado_por_nome',
        'kit_retirado_por_documento',
        'termo_responsavel_assinado_em',
        'responsavel_nome',
        'valor_pago',
        'data_pagamento',
        'data_inscricao',
        'colocacao',
        'colocacao_categoria',
        'tempo_visual',
        'tempo_ms',
        'finalizou_em',
        'premiado_em',
        'premiacao_descricao',
        'aceitou_termos_imagem_em',
        'aceitou_regulamento_em',
    ];

    protected $casts = [
        'numero_peito' => 'integer',
        'valor_pago' => 'decimal:2',
        'contribuicao_quantidade' => 'decimal:2',
        'data_pagamento' => 'date',
        'data_inscricao' => 'datetime',
        'contribuicao_recebida_em' => 'datetime',
        'kit_retirado_em' => 'datetime',
        'termo_responsavel_assinado_em' => 'datetime',
        'colocacao' => 'integer',
        'colocacao_categoria' => 'integer',
        'finalizou_em' => 'datetime',
        'premiado_em' => 'datetime',
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
