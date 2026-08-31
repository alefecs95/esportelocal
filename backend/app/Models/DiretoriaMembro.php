<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;
use App\Traits\ResolvesMediaUrl;

class DiretoriaMembro extends Model
{
    use HasFactory, BelongsToTenant, ResolvesMediaUrl;

    protected $fillable = [
        'prefeitura_id',
        'nome',
        'funcao',
        'foto',
        'ordem',
        'ativo'
    ];

    protected $appends = ['foto_url'];

    public function getFotoUrlAttribute()
    {
        if (!$this->foto) return null;
        if (filter_var($this->foto, FILTER_VALIDATE_URL)) {
            return $this->foto;
        }

        return $this->resolveMediaUrl($this->foto);
    }
}
