<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TseCache extends Model
{
    use HasFactory, BelongsToTenant;

    protected $table = 'tse_cache';

    protected $fillable = [
        'prefeitura_id',
        'cpf',
        'dados_json',
        'consultado_em',
        'expira_em',
    ];

    protected $casts = [
        'dados_json' => 'array',
        'consultado_em' => 'datetime',
        'expira_em' => 'datetime',
    ];
}
