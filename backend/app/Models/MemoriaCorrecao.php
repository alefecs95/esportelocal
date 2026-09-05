<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class MemoriaCorrecao extends Model
{
    use BelongsToTenant;

    protected $table = 'memoria_correcoes';

    protected $fillable = [
        'prefeitura_id',
        'acervo_id',
        'tipo',
        'valor',
        'comentario',
        'doador_nome',
        'doador_telefone',
        'local_id',
        'equipe_id',
        'marco_id',
        'ano_sugerido',
        'fingerprint',
        'status',
        'motivo_rejeicao',
        'revisado_por',
        'revisado_em',
    ];

    protected $casts = [
        'revisado_em' => 'datetime',
        'ano_sugerido' => 'integer',
    ];

    protected $hidden = [
        'doador_telefone',
        'fingerprint',
    ];

    public function acervo()
    {
        return $this->belongsTo(MemoriaAcervo::class, 'acervo_id');
    }

    public function local()
    {
        return $this->belongsTo(MemoriaLocal::class, 'local_id');
    }

    public function equipe()
    {
        return $this->belongsTo(MemoriaEquipe::class, 'equipe_id');
    }

    public function marco()
    {
        return $this->belongsTo(MemoriaMarco::class, 'marco_id');
    }
}
