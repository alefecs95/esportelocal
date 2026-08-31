<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BelongsToTenant;
use App\Traits\ResolvesMediaUrl;
use Illuminate\Support\Str;

class Noticia extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant, ResolvesMediaUrl;

    protected $fillable = [
        'prefeitura_id',
        'autor_id',
        'titulo',
        'slug',
        'conteudo',
        'meta_description',
        'imagem_capa',
        'galeria',
        'carrossel',
        'publicado',
        'publicado_em',
        'categoria_id',
        'visualizacoes',
        'destaque',
    ];

    protected $casts = [
        'publicado' => 'boolean',
        'publicado_em' => 'datetime',
        'galeria' => 'array',
        'carrossel' => 'array',
        'visualizacoes' => 'integer',
        'destaque' => 'boolean',
    ];

    protected $appends = ['imagem_capa_url', 'galeria_urls', 'carrossel_urls', 'reading_time'];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($noticia) {
            if (empty($noticia->slug)) {
                $noticia->slug = Str::slug($noticia->titulo);
            }
        });

        static::updating(function ($noticia) {
            if ($noticia->isDirty('titulo')) {
                $noticia->slug = Str::slug($noticia->titulo);
            }
        });
    }

    public function autor()
    {
        return $this->belongsTo(User::class, 'autor_id');
    }

    public function categoria()
    {
        return $this->belongsTo(Categoria::class);
    }

    public function comentarios()
    {
        return $this->hasMany(Comentario::class)->where('aprovado', true)->orderBy('created_at', 'desc');
    }

    public function getImagemCapaUrlAttribute()
    {
        if (!$this->imagem_capa) return null;
        if (filter_var($this->imagem_capa, FILTER_VALIDATE_URL)) {
            return $this->imagem_capa;
        }
        return $this->resolveMediaUrl($this->imagem_capa);
    }

    public function getGaleriaUrlsAttribute()
    {
        return $this->mapMediaPaths($this->galeria);
    }

    public function getCarrosselUrlsAttribute()
    {
        return $this->mapMediaPaths($this->carrossel);
    }

    public function getReadingTimeAttribute()
    {
        $content = '';
        if (is_string($this->conteudo)) {
            // Try to parse if it's JSON from editor.js
            $json = json_decode($this->conteudo, true);
            if (json_last_error() === JSON_ERROR_NONE && isset($json['blocks'])) {
                foreach ($json['blocks'] as $block) {
                    if (isset($block['data']['text'])) {
                        $content .= strip_tags($block['data']['text']) . ' ';
                    }
                }
            } else {
                $content = strip_tags($this->conteudo);
            }
        }
        
        $wordCount = str_word_count($content);
        $minutes = ceil($wordCount / 200); // 200 words per minute average
        return $minutes > 0 ? $minutes : 1;
    }

    protected function mapMediaPaths($paths)
    {
        if (!$paths || !is_array($paths)) return [];
        return array_values(array_filter(array_map(function ($path) {
            if (!$path) return null;
            if (filter_var($path, FILTER_VALIDATE_URL)) {
                return $path;
            }
            return $this->resolveMediaUrl($path);
        }, $paths)));
    }

}
