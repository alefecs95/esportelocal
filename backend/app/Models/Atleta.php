<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BelongsToTenant;
use App\Traits\ResolvesMediaUrl;

class Atleta extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant, ResolvesMediaUrl;

    protected $fillable = [
        'prefeitura_id',
        'nome',
        'cpf',
        'data_nascimento',
        'sexo',
        'nome_mae',
        'nome_pai',
        'responsavel_nome',
        'responsavel_cpf',
        'responsavel_telefone',
        'titulo_eleitor',
        'zona_eleitoral',
        'secao_eleitoral',
        'bairro_eleitoral',
        'cidade_eleitoral',
        'uf_eleitoral',
        'titulo_verificado',
        'validacao_eleitoral_pendente',
        'cep',
        'endereco',
        'numero',
        'complemento',
        'bairro',
        'cidade',
        'estado',
        'telefone',
        'tamanho_camiseta',
        'contato_emergencia',
        'email',
        'foto',
        'acesso_ativo',
        'pode_participar_competicoes',
    ];

    protected $casts = [
        'acesso_ativo' => 'boolean',
        'pode_participar_competicoes' => 'boolean',
        'titulo_verificado' => 'boolean',
        'validacao_eleitoral_pendente' => 'boolean',
    ];

    protected $appends = ['foto_url'];

    protected function serializeDate(\DateTimeInterface $date): string
    {
        return $date->format('Y-m-d');
    }

    public function getDataNascimentoAttribute($value): ?string
    {
        if (!$value || $value === '0000-00-00' || $value === '0000-00-00 00:00:00') {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function getFotoUrlAttribute()
    {
        if (!$this->foto) return null;
        if (filter_var($this->foto, FILTER_VALIDATE_URL)) {
            return $this->foto;
        }

        return $this->resolveMediaUrl($this->foto);
    }

    public function arquivos()
    {
        return $this->hasMany(AtletaArquivo::class);
    }

    public function modalidades()
    {
        return $this->belongsToMany(Modalidade::class, 'atleta_modalidade');
    }

    public function equipes()
    {
        return $this->belongsToMany(Equipe::class, 'equipe_atleta');
    }

    public function inscricoes()
    {
        return $this->hasMany(Inscricao::class);
    }
}
