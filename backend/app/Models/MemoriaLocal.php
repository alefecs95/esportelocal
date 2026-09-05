<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use App\Traits\ResolvesMediaUrl;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class MemoriaLocal extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant, ResolvesMediaUrl;

    protected $table = 'memoria_locais';

    protected $fillable = [
        'prefeitura_id',
        'nome',
        'slug',
        'tipo',
        'endereco',
        'lat',
        'lng',
        'descricao',
        'foto',
        'foto_antes',
        'foto_depois',
        'ano_antes',
        'ano_depois',
        'publicado',
    ];

    protected $casts = [
        'publicado' => 'boolean',
        'lat' => 'float',
        'lng' => 'float',
        'ano_antes' => 'integer',
        'ano_depois' => 'integer',
    ];

    protected $appends = ['foto_url', 'foto_antes_url', 'foto_depois_url'];

    protected static function boot()
    {
        parent::boot();

        static::saving(function (self $model) {
            if ($model->slug) {
                $model->slug = static::uniqueSlugFor($model, Str::slug($model->slug) ?: 'local');
            } elseif ($model->nome) {
                $model->slug = static::uniqueSlugFor($model, Str::slug($model->nome));
            }
        });
    }

    public static function uniqueSlugFor(self $model, string $base): string
    {
        $slug = $base ?: 'local';
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
        return $this->foto ? $this->resolveMediaUrl($this->foto) : null;
    }

    public function getFotoAntesUrlAttribute(): ?string
    {
        return $this->foto_antes ? $this->resolveMediaUrl($this->foto_antes) : null;
    }

    public function getFotoDepoisUrlAttribute(): ?string
    {
        return $this->foto_depois ? $this->resolveMediaUrl($this->foto_depois) : null;
    }

    public function marcos()
    {
        return $this->hasMany(MemoriaMarco::class, 'local_id');
    }

    public function acervo()
    {
        return $this->hasMany(MemoriaAcervo::class, 'local_id');
    }

    public function momentos()
    {
        return $this->hasMany(MemoriaLocalMomento::class, 'local_id')->orderBy('ano')->orderBy('ordem');
    }

    public function scopePublicado($query)
    {
        return $query->where('publicado', true);
    }
}
