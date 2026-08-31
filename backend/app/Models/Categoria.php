<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Categoria extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'nome',
        'slug',
        'cor',
        'descricao',
        'prefeitura_id',
    ];

    public function noticias()
    {
        return $this->hasMany(Noticia::class);
    }
}
