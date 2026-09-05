<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MemoriaRecorde extends Model
{
    use BelongsToTenant, SoftDeletes;

    protected $table = 'memoria_recordes';

    protected $fillable = [
        'prefeitura_id',
        'titulo',
        'categoria',
        'valor',
        'unidade',
        'ano',
        'modalidade_id',
        'campeonato_id',
        'personalidade_id',
        'marco_id',
        'atleta_nome',
        'descricao',
        'vigente',
        'publicado',
    ];

    protected $casts = [
        'ano' => 'integer',
        'vigente' => 'boolean',
        'publicado' => 'boolean',
    ];

    public function modalidade()
    {
        return $this->belongsTo(Modalidade::class);
    }

    public function campeonato()
    {
        return $this->belongsTo(Campeonato::class);
    }

    public function personalidade()
    {
        return $this->belongsTo(MemoriaPersonalidade::class, 'personalidade_id');
    }

    public function marco()
    {
        return $this->belongsTo(MemoriaMarco::class, 'marco_id');
    }

    public function scopePublicado($query)
    {
        return $query->where('publicado', true);
    }
}
