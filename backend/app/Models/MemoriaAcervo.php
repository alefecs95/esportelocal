<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use App\Traits\ResolvesMediaUrl;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MemoriaAcervo extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant, ResolvesMediaUrl;

    protected $table = 'memoria_acervo';

    protected $fillable = [
        'prefeitura_id',
        'tipo',
        'titulo',
        'alt_text',
        'descricao',
        'historia',
        'arquivo',
        'mime',
        'tamanho',
        'data_aproximada',
        'ano',
        'decada',
        'local_id',
        'modalidade_id',
        'marco_id',
        'personalidade_id',
        'pessoas_envolvidas',
        'fonte',
        'fonte_tipo',
        'doacao',
        'origem',
        'doador_nome',
        'doador_email',
        'doador_telefone',
        'status',
        'motivo_rejeicao',
        'publicado',
        'revisado_por',
        'revisado_em',
    ];

    protected $casts = [
        'data_aproximada' => 'date',
        'publicado' => 'boolean',
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
            if (!$model->ano && $model->data_aproximada) {
                $model->ano = (int) $model->data_aproximada->format('Y');
            }

            if ($model->ano) {
                $model->decada = intdiv((int) $model->ano, 10) * 10;
            }
        });
    }

    public function getArquivoUrlAttribute(): ?string
    {
        if (!$this->arquivo) {
            return null;
        }

        return $this->resolveMediaUrl($this->arquivo);
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

    public function scopeOrfao($query)
    {
        return $query->whereNull('marco_id')->whereNull('personalidade_id');
    }
}
