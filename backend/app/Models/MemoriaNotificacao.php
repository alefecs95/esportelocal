<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class MemoriaNotificacao extends Model
{
    use BelongsToTenant;

    protected $table = 'memoria_notificacoes';

    protected $fillable = [
        'prefeitura_id',
        'user_id',
        'tipo',
        'titulo',
        'mensagem',
        'link',
        'lida',
        'lida_em',
    ];

    protected $casts = [
        'lida' => 'boolean',
        'lida_em' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
