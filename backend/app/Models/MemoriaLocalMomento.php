<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use App\Traits\ResolvesMediaUrl;
use Illuminate\Database\Eloquent\Model;

class MemoriaLocalMomento extends Model
{
    use BelongsToTenant, ResolvesMediaUrl;

    protected $table = 'memoria_local_momentos';

    protected $fillable = [
        'prefeitura_id',
        'local_id',
        'ano',
        'titulo',
        'descricao',
        'foto',
        'acervo_id',
        'ordem',
    ];

    protected $casts = [
        'ano' => 'integer',
        'ordem' => 'integer',
    ];

    protected $appends = ['foto_url'];

    public function getFotoUrlAttribute(): ?string
    {
        if ($this->foto) {
            return $this->resolveMediaUrl($this->foto);
        }

        return $this->acervo?->arquivo_url;
    }

    public function local()
    {
        return $this->belongsTo(MemoriaLocal::class, 'local_id');
    }

    public function acervo()
    {
        return $this->belongsTo(MemoriaAcervo::class, 'acervo_id');
    }
}
