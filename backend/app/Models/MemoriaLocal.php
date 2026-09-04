<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use App\Traits\ResolvesMediaUrl;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MemoriaLocal extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant, ResolvesMediaUrl;

    protected $table = 'memoria_locais';

    protected $fillable = [
        'prefeitura_id',
        'nome',
        'tipo',
        'endereco',
        'descricao',
        'foto',
        'publicado',
    ];

    protected $casts = [
        'publicado' => 'boolean',
    ];

    protected $appends = ['foto_url'];

    public function getFotoUrlAttribute(): ?string
    {
        if (!$this->foto) {
            return null;
        }

        return $this->resolveMediaUrl($this->foto);
    }

    public function marcos()
    {
        return $this->hasMany(MemoriaMarco::class, 'local_id');
    }

    public function acervo()
    {
        return $this->hasMany(MemoriaAcervo::class, 'local_id');
    }
}
