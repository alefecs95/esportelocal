<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BelongsToTenant;

class Comentario extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'noticia_id',
        'prefeitura_id',
        'user_id',
        'nome',
        'email',
        'conteudo',
        'aprovado',
    ];

    protected $casts = [
        'aprovado' => 'boolean',
    ];

    public function noticia()
    {
        return $this->belongsTo(Noticia::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
