<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class MemoriaOpcao extends Model
{
    use BelongsToTenant;

    protected $table = 'memoria_opcoes';

    protected $fillable = [
        'prefeitura_id',
        'grupo',
        'valor',
    ];
}
