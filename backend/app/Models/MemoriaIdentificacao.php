<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class MemoriaIdentificacao extends Model
{
    use BelongsToTenant;

    protected $table = 'memoria_identificacoes';

    protected $fillable = [
        'prefeitura_id',
        'acervo_id',
        'personalidade_id',
        'nome_sugerido',
        'comentario',
        'doador_nome',
        'doador_telefone',
        'fingerprint',
        'status',
        'motivo_rejeicao',
        'revisado_por',
        'revisado_em',
    ];

    protected $casts = [
        'revisado_em' => 'datetime',
    ];

    public function acervo()
    {
        return $this->belongsTo(MemoriaAcervo::class, 'acervo_id');
    }

    public function personalidade()
    {
        return $this->belongsTo(MemoriaPersonalidade::class, 'personalidade_id');
    }

    public function revisadoPor()
    {
        return $this->belongsTo(User::class, 'revisado_por');
    }
}
