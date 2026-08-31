<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BelongsToTenant;
use App\Traits\ResolvesMediaUrl;

class Equipe extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant, ResolvesMediaUrl;

    protected $fillable = [
        'prefeitura_id',
        'user_id',
        'campeonato_id',
        'entidade_id',
        'nome',
        'logo',
        'responsavel',
        'contato',
        'colocacao',
        'status',
        'status_inscricao',
    ];

    protected $appends = ['logo_url'];

    public function getLogoUrlAttribute()
    {
        if ($this->logo) {
            if (filter_var($this->logo, FILTER_VALIDATE_URL)) {
                return $this->logo;
            }

            return $this->resolveMediaUrl($this->logo);
        }

        if ($this->relationLoaded('entidade') && $this->entidade) {
            return $this->entidade->logo_url;
        }

        if ($this->entidade_id) {
            $logoEntidade = Entidade::query()->whereKey($this->entidade_id)->value('logo');
            if (!$logoEntidade) {
                return null;
            }
            if (filter_var($logoEntidade, FILTER_VALIDATE_URL)) {
                return $logoEntidade;
            }
            return $this->resolveMediaUrl($logoEntidade);
        }

        return null;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function campeonato()
    {
        return $this->belongsTo(Campeonato::class);
    }

    public function entidade()
    {
        return $this->belongsTo(Entidade::class);
    }

    public function atletas()
    {
        return $this->belongsToMany(Atleta::class, 'equipe_atleta')
            ->withPivot(['status', 'aprovado_por', 'aprovado_em'])
            ->withTimestamps();
    }

    public function atletasAprovados()
    {
        return $this->belongsToMany(Atleta::class, 'equipe_atleta')
            ->wherePivot('status', 'aprovado')
            ->withPivot(['status', 'aprovado_por', 'aprovado_em'])
            ->withTimestamps();
    }
}
