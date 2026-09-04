<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use App\Traits\ResolvesMediaUrl;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class MemoriaPersonalidade extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant, ResolvesMediaUrl;

    protected $table = 'memoria_personalidades';

    protected $fillable = [
        'prefeitura_id',
        'nome',
        'slug',
        'papel',
        'mini_bio',
        'foto',
        'data_nascimento',
        'data_falecimento',
        'modalidade_id',
        'destaque',
        'publicado',
    ];

    protected $casts = [
        'data_nascimento' => 'date',
        'data_falecimento' => 'date',
        'destaque' => 'boolean',
        'publicado' => 'boolean',
    ];

    protected $appends = ['foto_url'];

    protected static function boot()
    {
        parent::boot();

        static::saving(function (self $model) {
            if (empty($model->slug) && $model->nome) {
                $model->slug = static::uniqueSlugFor($model, Str::slug($model->nome));
            }
        });
    }

    public static function uniqueSlugFor(self $model, string $base): string
    {
        $slug = $base ?: 'personalidade';
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

    public function getFotoUrlAttribute(): ?string
    {
        if (!$this->foto) {
            return null;
        }

        return $this->resolveMediaUrl($this->foto);
    }

    public function modalidade()
    {
        return $this->belongsTo(Modalidade::class);
    }

    public function marcos()
    {
        return $this->belongsToMany(MemoriaMarco::class, 'memoria_marco_personalidade', 'personalidade_id', 'marco_id');
    }

    public function acervo()
    {
        return $this->hasMany(MemoriaAcervo::class, 'personalidade_id');
    }

    public function scopePublicado($query)
    {
        return $query->where('publicado', true);
    }
}
