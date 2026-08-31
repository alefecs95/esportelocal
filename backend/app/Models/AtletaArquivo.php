<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\ResolvesMediaUrl;

class AtletaArquivo extends Model
{
    use HasFactory, ResolvesMediaUrl;

    protected $fillable = ['atleta_id', 'nome', 'path', 'tipo'];

    protected $appends = ['url'];

    public function atleta()
    {
        return $this->belongsTo(Atleta::class);
    }

    public function getUrlAttribute()
    {
        if (!$this->path) return null;

        if (filter_var($this->path, FILTER_VALIDATE_URL)) {
            return $this->path;
        }

        return $this->resolveMediaUrl($this->path);
    }
}
