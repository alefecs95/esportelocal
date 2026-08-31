<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CpfCache extends Model
{
    use HasFactory;

    protected $table = 'cpf_cache';

    protected $fillable = [
        'prefeitura_id',
        'cpf',
        'nome',
        'data_nascimento',
        'sexo',
        'dados_json',
    ];

    protected $casts = [
        'data_nascimento' => 'date',
        'dados_json' => 'array',
    ];
}
