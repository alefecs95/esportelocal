<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BelongsToTenant;
use App\Traits\ResolvesMediaUrl;
use App\Enums\FormatoCampeonato;

class Campeonato extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant, ResolvesMediaUrl;

    protected $fillable = [
        'prefeitura_id',
        'temporada_id',
        'nome',
        'local',
        'banner',
        'modalidade_id',
        'categoria',
        'genero',
        'tipo_competicao',
        'data_inicio',
        'data_fim',
        'limite_participantes',
        'valor_inscricao',
        'regulamento',
        'status',
        'idade_min',
        'idade_max',
        'cidade_eleitoral',
        'bairro_eleitoral',
        'min_atletas_equipe',
        'max_atletas_equipe',
        'formato',
        'permite_inscricao_online',
        'inicio_inscricao',
        'fim_inscricao',
        'rodada_limite_inscricao_atletas',
        'suspensao_automatica_ativa',
        'cartoes_amarelos_para_suspensao',
        'cartao_vermelho_suspende',
        'modo_cadastro',
        'campos_cadastro',
        'inscricao_solidaria',
        'exige_termo_menor',
        'kit_retirada_info',
    ];

    protected $casts = [
        'data_inicio' => 'date',
        'data_fim' => 'date',
        'inicio_inscricao' => 'date',
        'fim_inscricao' => 'date',
        'valor_inscricao' => 'decimal:2',
        'limite_participantes' => 'integer',
        'idade_min' => 'integer',
        'idade_max' => 'integer',
        'min_atletas_equipe' => 'integer',
        'max_atletas_equipe' => 'integer',
        'permite_inscricao_online' => 'boolean',
        'rodada_limite_inscricao_atletas' => 'integer',
        'suspensao_automatica_ativa' => 'boolean',
        'cartoes_amarelos_para_suspensao' => 'integer',
        'cartao_vermelho_suspende' => 'boolean',
        'formato' => FormatoCampeonato::class,
        'campos_cadastro' => 'array',
        'inscricao_solidaria' => 'boolean',
        'exige_termo_menor' => 'boolean',
    ];

    protected $appends = [
        'banner_url',
    ];

    public function getBannerUrlAttribute(): ?string
    {
        $banner = $this->attributes['banner'] ?? null;
        if (!$banner) {
            return null;
        }
        if (str_starts_with($banner, 'http://') || str_starts_with($banner, 'https://')) {
            return $banner;
        }
        return $this->resolveMediaUrl($banner);
    }

    public function modalidade()
    {
        return $this->belongsTo(Modalidade::class);
    }

    public function temporada()
    {
        return $this->belongsTo(Temporada::class);
    }

    public function equipes()
    {
        return $this->hasMany(Equipe::class);
    }

    public function fases()
    {
        return $this->hasMany(Fase::class)->orderBy('ordem');
    }

    public function inscricoes()
    {
        return $this->hasMany(Inscricao::class);
    }

    public function categorias()
    {
        return $this->hasMany(CampeonatoCategoria::class);
    }

    public function classificacaoFinal()
    {
        if ($this->tipo_competicao === 'equipe') {
            return $this->equipes()
                ->whereNotNull('colocacao')
                ->orderBy('colocacao')
                ->get();
        } else {
            // Retorna atletas ordenados pela colocação na inscrição
            return Atleta::join('inscricoes', 'atletas.id', '=', 'inscricoes.atleta_id')
                ->where('inscricoes.campeonato_id', $this->id)
                ->whereNotNull('inscricoes.colocacao')
                ->orderBy('inscricoes.colocacao')
                ->select('atletas.*')
                ->get();
        }
    }
}
