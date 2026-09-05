<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use App\Traits\ResolvesMediaUrl;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class MemoriaEquipe extends Model
{
    use SoftDeletes, BelongsToTenant, ResolvesMediaUrl;

    protected $table = 'memoria_equipes';

    protected $fillable = [
        'prefeitura_id',
        'nome',
        'slug',
        'nome_anterior',
        'apelido',
        'escudo',
        'modalidade_id',
        'ano_inicio',
        'ano_fim',
        'cidade',
        'descricao',
        'historia',
        'publicado',
    ];

    protected $casts = [
        'publicado' => 'boolean',
        'ano_inicio' => 'integer',
        'ano_fim' => 'integer',
    ];

    protected $appends = ['escudo_url'];

    protected static function boot()
    {
        parent::boot();

        static::saving(function (self $model) {
            if ($model->slug) {
                $model->slug = static::uniqueSlugFor($model, Str::slug($model->slug) ?: 'equipe');
            } elseif ($model->nome) {
                $model->slug = static::uniqueSlugFor($model, Str::slug($model->nome) ?: 'equipe');
            }
        });
    }

    public static function uniqueSlugFor(self $model, string $base): string
    {
        $slug = $base ?: 'equipe';
        $i = 1;
        while (
            static::query()
                ->where('slug', $slug)
                ->when($model->id, fn ($q) => $q->where('id', '!=', $model->id))
                ->exists()
        ) {
            $slug = $base . '-' . (++$i);
        }

        return $slug;
    }

    public function getEscudoUrlAttribute(): ?string
    {
        return $this->escudo ? $this->resolveMediaUrl($this->escudo) : null;
    }

    public function modalidade()
    {
        return $this->belongsTo(Modalidade::class);
    }

    public function pessoas()
    {
        return $this->belongsToMany(MemoriaPersonalidade::class, 'memoria_equipe_personalidade', 'equipe_id', 'personalidade_id')
            ->withPivot(['papel', 'ano_inicio', 'ano_fim'])
            ->withTimestamps();
    }

    public function acervo()
    {
        return $this->belongsToMany(MemoriaAcervo::class, 'memoria_acervo_equipe', 'equipe_id', 'acervo_id')->withTimestamps();
    }

    public function marcos()
    {
        return $this->belongsToMany(MemoriaMarco::class, 'memoria_marco_equipe', 'equipe_id', 'marco_id')->withTimestamps();
    }

    public function scopePublicado($query)
    {
        return $query->where('publicado', true);
    }
}
