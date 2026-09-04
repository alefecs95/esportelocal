<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use App\Traits\ResolvesMediaUrl;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class MemoriaMarco extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant, ResolvesMediaUrl;

    protected $table = 'memoria_marcos';

    protected $fillable = [
        'prefeitura_id',
        'titulo',
        'slug',
        'resumo',
        'conteudo',
        'data_evento',
        'ano',
        'decada',
        'tipo',
        'modalidade_id',
        'local_id',
        'capa',
        'destaque',
        'publicado',
        'ordem',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'data_evento' => 'date',
        'destaque' => 'boolean',
        'publicado' => 'boolean',
        'ano' => 'integer',
        'decada' => 'integer',
        'ordem' => 'integer',
    ];

    protected $appends = ['capa_url'];

    protected static function boot()
    {
        parent::boot();

        static::saving(function (self $model) {
            if ($model->slug) {
                $model->slug = static::uniqueSlugFor($model, Str::slug($model->slug) ?: 'marco');
            } elseif ($model->titulo) {
                $model->slug = static::uniqueSlugFor($model, Str::slug($model->titulo));
            }

            if (!$model->ano && $model->data_evento) {
                $model->ano = (int) $model->data_evento->format('Y');
            }

            if ($model->ano) {
                $model->decada = intdiv((int) $model->ano, 10) * 10;
            }
        });
    }

    public static function uniqueSlugFor(self $model, string $base): string
    {
        $slug = $base ?: 'marco';
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

    public function getCapaUrlAttribute(): ?string
    {
        if (!$this->capa) {
            return null;
        }

        return $this->resolveMediaUrl($this->capa);
    }

    public function modalidade()
    {
        return $this->belongsTo(Modalidade::class);
    }

    public function local()
    {
        return $this->belongsTo(MemoriaLocal::class, 'local_id');
    }

    public function personalidades()
    {
        return $this->belongsToMany(MemoriaPersonalidade::class, 'memoria_marco_personalidade', 'marco_id', 'personalidade_id');
    }

    public function acervo()
    {
        return $this->hasMany(MemoriaAcervo::class, 'marco_id');
    }

    public function scopePublicado($query)
    {
        return $query->where('publicado', true);
    }
}
