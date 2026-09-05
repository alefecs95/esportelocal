<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use App\Traits\ResolvesMediaUrl;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class MemoriaAcervo extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant, ResolvesMediaUrl;

    protected $table = 'memoria_acervo';

    protected $fillable = [
        'prefeitura_id',
        'tipo',
        'titulo',
        'slug',
        'alt_text',
        'descricao',
        'historia',
        'transcricao',
        'arquivo',
        'mime',
        'tamanho',
        'hash_arquivo',
        'data_aproximada',
        'ano',
        'decada',
        'data_incerta',
        'local_id',
        'modalidade_id',
        'marco_id',
        'personalidade_id',
        'pessoas_envolvidas',
        'fonte',
        'fonte_tipo',
        'doacao',
        'credito',
        'fotografo',
        'licenca',
        'origem',
        'doador_nome',
        'doador_email',
        'doador_telefone',
        'status',
        'motivo_rejeicao',
        'publicado',
        'publicado_em',
        'aparece_na_timeline',
        'precisa_identificar',
        'revisado_por',
        'revisado_em',
    ];

    protected $casts = [
        'data_aproximada' => 'date',
        'publicado' => 'boolean',
        'data_incerta' => 'boolean',
        'publicado_em' => 'datetime',
        'aparece_na_timeline' => 'boolean',
        'precisa_identificar' => 'boolean',
        'ano' => 'integer',
        'decada' => 'integer',
        'tamanho' => 'integer',
        'revisado_em' => 'datetime',
    ];

    protected $hidden = [
        'doador_email',
        'doador_telefone',
    ];

    protected $appends = ['arquivo_url'];

    protected static function boot()
    {
        parent::boot();

        static::saving(function (self $model) {
            if ($model->slug) {
                $model->slug = static::uniqueSlugFor($model, Str::slug($model->slug) ?: 'peca');
            } elseif ($model->titulo) {
                $model->slug = static::uniqueSlugFor($model, Str::slug($model->titulo) ?: 'peca');
            }

            if (!$model->ano && $model->data_aproximada) {
                $model->ano = (int) $model->data_aproximada->format('Y');
            }

            if ($model->ano) {
                $model->decada = intdiv((int) $model->ano, 10) * 10;
            }

            if ($model->publicado && $model->status === 'aprovado' && !$model->publicado_em) {
                $model->publicado_em = now();
            }

            if (!$model->credito && $model->fonte) {
                $model->credito = $model->fonte;
            }

            if (!$model->licenca) {
                $model->licenca = 'nao_informado';
            }
        });
    }

    public static function uniqueSlugFor(self $model, string $base): string
    {
        $slug = $base ?: 'peca';
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

    public function getArquivoUrlAttribute(): ?string
    {
        if (!$this->arquivo) {
            return null;
        }

        return $this->resolveMediaUrl($this->arquivo);
    }

    public static function hashFromUpload(UploadedFile $file): ?string
    {
        $path = $file->getRealPath();
        if (!$path) {
            return null;
        }

        return hash_file('sha256', $path) ?: null;
    }

    public function duplicata(): ?self
    {
        if (!$this->hash_arquivo) {
            return null;
        }

        return static::query()
            ->where('hash_arquivo', $this->hash_arquivo)
            ->where('id', '!=', $this->id)
            ->orderBy('id')
            ->first();
    }

    public function local()
    {
        return $this->belongsTo(MemoriaLocal::class, 'local_id');
    }

    public function modalidade()
    {
        return $this->belongsTo(Modalidade::class);
    }

    public function marco()
    {
        return $this->belongsTo(MemoriaMarco::class, 'marco_id');
    }

    public function personalidade()
    {
        return $this->belongsTo(MemoriaPersonalidade::class, 'personalidade_id');
    }

    public function pessoas()
    {
        return $this->belongsToMany(
            MemoriaPersonalidade::class,
            'memoria_acervo_personalidade',
            'acervo_id',
            'personalidade_id'
        )->withPivot(['papel', 'ordem', 'observacao'])->withTimestamps()->orderByPivot('ordem');
    }

    public function equipes()
    {
        return $this->belongsToMany(MemoriaEquipe::class, 'memoria_acervo_equipe', 'acervo_id', 'equipe_id')->withTimestamps();
    }

    public function marcos()
    {
        return $this->belongsToMany(MemoriaMarco::class, 'memoria_acervo_marco', 'acervo_id', 'marco_id')->withTimestamps();
    }

    public function tags()
    {
        return $this->belongsToMany(MemoriaTag::class, 'memoria_acervo_tag', 'acervo_id', 'tag_id')->withTimestamps();
    }

    public function scopeNaTimeline($query)
    {
        return $query->aprovado()->where('aparece_na_timeline', true)->whereNotNull('ano');
    }

    public function identificacoes()
    {
        return $this->hasMany(MemoriaIdentificacao::class, 'acervo_id');
    }

    public function revisadoPor()
    {
        return $this->belongsTo(User::class, 'revisado_por');
    }

    public function scopeAprovado($query)
    {
        return $query->where('status', 'aprovado')->where('publicado', true);
    }

    public function scopePendente($query)
    {
        return $query->where('status', 'pendente');
    }

    public function scopeNaFila($query)
    {
        return $query->whereIn('status', ['pendente', 'complemento']);
    }

    public function scopeOrfao($query)
    {
        return $query->whereNull('marco_id')->whereNull('personalidade_id');
    }
}
