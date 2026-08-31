<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BelongsToTenant;
use App\Traits\ResolvesMediaUrl;

class Entidade extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant, ResolvesMediaUrl;

    protected $fillable = [
        'prefeitura_id',
        'user_id',
        'nome',
        'sigla',
        'data_fundacao',
        'responsavel',
        'contato',
        'logo',
        'cidade',
        'bairro',
    ];

    protected $casts = [
        'data_fundacao' => 'date',
    ];

    protected $appends = ['logo_url'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function equipes()
    {
        return $this->hasMany(Equipe::class);
    }

    public function getLogoUrlAttribute()
    {
        if (!$this->logo) return null;

        if (filter_var($this->logo, FILTER_VALIDATE_URL)) {
            return $this->logo;
        }

        return $this->resolveMediaUrl($this->logo);
    }
}
