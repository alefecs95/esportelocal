<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class Partida extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'prefeitura_id',
        'fase_id',
        'fase_grupo_id',
        'mandante_id',
        'visitante_id',
        'placar_mandante',
        'placar_visitante',
        'data_jogo',
        'local',
        'status',
        'rodada',
        'chave',
        'ordem_na_fase',
        'proxima_partida_id',
        'vencedor_id',
        'origem_mandante_type',
        'origem_mandante_payload',
        'origem_visitante_type',
        'origem_visitante_payload',
        'definido_manualmente',
        'arbitro_id',
        'placar_bloqueado',
        'assinatura_arbitro',
        'assinatura_mandante',
        'assinatura_visitante',
    ];

    protected $casts = [
        'data_jogo' => 'datetime',
        'placar_mandante' => 'integer',
        'placar_visitante' => 'integer',
        'rodada' => 'integer',
        'ordem_na_fase' => 'integer',
        'proxima_partida_id' => 'integer',
        'vencedor_id' => 'integer',
        'origem_mandante_payload' => 'array',
        'origem_visitante_payload' => 'array',
        'definido_manualmente' => 'boolean',
        'placar_bloqueado' => 'boolean',
    ];

    protected $hidden = [
        'assinatura_arbitro',
        'assinatura_mandante',
        'assinatura_visitante',
    ];

    // Removendo appends globais para evitar que chamem $this->fase implicitamente
    // protected $appends = ['mandante', 'visitante'];

    public function fase()
    {
        return $this->belongsTo(Fase::class);
    }

    public function arbitro()
    {
        return $this->belongsTo(User::class, 'arbitro_id');
    }

    public function grupo()
    {
        return $this->belongsTo(FaseGrupo::class, 'fase_grupo_id');
    }

    // Relacionamentos dinâmicos dependendo do tipo do campeonato
    // Como não usamos polimorfismo padrão do Laravel, precisamos acessar via lógica ou definir getters auxiliares
    
    // Assumindo que o campeonato define o tipo, podemos ter métodos auxiliares
    
    public function getMandanteAttribute()
    {
        if (empty($this->mandante_id)) return null;
        
        try {
            // Se já tivermos a relação fase->campeonato carregada
            if ($this->relationLoaded('fase') && $this->fase && $this->fase->relationLoaded('campeonato') && $this->fase->campeonato) {
                 $tipo = $this->fase->campeonato->tipo_competicao ?? 'equipe';
                 if ($tipo === 'equipe') {
                    $equipe = Equipe::find($this->mandante_id);
                    if ($equipe) $equipe->append('logo_url');
                    return $equipe;
                 }
                 return Atleta::find($this->mandante_id);
            }
            
            // Se não, vamos tentar carregar o mínimo necessário sem causar loop
            // Assumimos 'equipe' como padrão se der erro
            $tipo = 'equipe';
            if (!empty($this->fase_id)) {
                 $fase = \App\Models\Fase::with('campeonato')->find($this->fase_id);
                 if ($fase && $fase->campeonato) {
                     $tipo = $fase->campeonato->tipo_competicao ?? 'equipe';
                 }
            }
            
            if ($tipo === 'equipe') {
                $equipe = Equipe::find($this->mandante_id);
                if ($equipe) $equipe->append('logo_url');
                return $equipe;
            } else {
                return Atleta::find($this->mandante_id);
            }
        } catch (\Throwable $e) {
            // Em caso de qualquer erro, retornamos null para não quebrar a API
            return null;
        }
    }

    public function getVisitanteAttribute()
    {
        if (empty($this->visitante_id)) return null;
        
        try {
            if ($this->relationLoaded('fase') && $this->fase && $this->fase->relationLoaded('campeonato') && $this->fase->campeonato) {
                 $tipo = $this->fase->campeonato->tipo_competicao ?? 'equipe';
                 if ($tipo === 'equipe') {
                    $equipe = Equipe::find($this->visitante_id);
                    if ($equipe) $equipe->append('logo_url');
                    return $equipe;
                 }
                 return Atleta::find($this->visitante_id);
            }

            $tipo = 'equipe';
            if (!empty($this->fase_id)) {
                 $fase = \App\Models\Fase::with('campeonato')->find($this->fase_id);
                 if ($fase && $fase->campeonato) {
                     $tipo = $fase->campeonato->tipo_competicao ?? 'equipe';
                 }
            }
            
            if ($tipo === 'equipe') {
                $equipe = Equipe::find($this->visitante_id);
                if ($equipe) $equipe->append('logo_url');
                return $equipe;
            } else {
                return Atleta::find($this->visitante_id);
            }
        } catch (\Throwable $e) {
            return null;
        }
    }
    
    // Relacionamento com a próxima partida (para onde o vencedor vai)
    public function proximaPartida()
    {
        return $this->belongsTo(Partida::class, 'proxima_partida_id');
    }

    public function eventos()
    {
        return $this->hasMany(EventoPartida::class);
    }

    public function escalacoes()
    {
        return $this->hasMany(PartidaEscalacao::class);
    }
}
