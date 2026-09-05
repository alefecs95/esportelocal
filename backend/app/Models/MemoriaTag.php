<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class MemoriaTag extends Model
{
    use BelongsToTenant;

    protected $table = 'memoria_tags';

    protected $fillable = [
        'prefeitura_id',
        'nome',
        'slug',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function (self $model) {
            $model->slug = Str::slug($model->slug ?: $model->nome) ?: 'tag';
            $base = $model->slug;
            $i = 1;
            while (
                static::query()
                    ->where('slug', $model->slug)
                    ->when($model->id, fn ($q) => $q->where('id', '!=', $model->id))
                    ->exists()
            ) {
                $model->slug = $base . '-' . (++$i);
            }
        });
    }

    public function acervo()
    {
        return $this->belongsToMany(MemoriaAcervo::class, 'memoria_acervo_tag', 'tag_id', 'acervo_id');
    }
}
