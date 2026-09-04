<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class MemoriaAuditoria extends Model
{
    use BelongsToTenant;

    protected $table = 'memoria_auditoria';

    protected $fillable = [
        'prefeitura_id',
        'user_id',
        'acao',
        'entidade_tipo',
        'entidade_id',
        'resumo',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
