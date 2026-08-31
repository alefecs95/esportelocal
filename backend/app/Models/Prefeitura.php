<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\ResolvesMediaUrl;

class Prefeitura extends Model
{
    use HasFactory, SoftDeletes, ResolvesMediaUrl;

    protected $fillable = [
        'nome',
        'slug',
        'dominio',
        'endereco',
        'telefone',
        'email_contato',
        'footer_titulo',
        'footer_link_transparencia',
        'footer_link_editais',
        'footer_link_contato',
        'redes_sociais',
        'logo',
        'logo_rodape',
        'banner_portal',
        'fundo_login',
        'cor_primaria',
        'cor_secundaria',
        'configuracao_estilo',
        'configuracoes_integracoes',
        'ativo'
    ];

    protected $casts = [
        'ativo' => 'boolean',
        'redes_sociais' => 'array',
        'configuracao_estilo' => 'array',
        'configuracoes_integracoes' => 'array',
    ];

    protected $appends = ['logo_url', 'logo_rodape_url', 'banner_portal_url', 'fundo_login_url'];

    public function getLogoUrlAttribute()
    {
        if (!$this->logo) return null;

        if (filter_var($this->logo, FILTER_VALIDATE_URL)) {
            return $this->logo;
        }

        return $this->resolveMediaUrl($this->logo);
    }

    public function getBannerPortalUrlAttribute()
    {
        if (!$this->banner_portal) return null;

        if (filter_var($this->banner_portal, FILTER_VALIDATE_URL)) {
            return $this->banner_portal;
        }

        return $this->resolveMediaUrl($this->banner_portal);
    }

    public function getLogoRodapeUrlAttribute()
    {
        if (!$this->logo_rodape) return null;

        if (filter_var($this->logo_rodape, FILTER_VALIDATE_URL)) {
            return $this->logo_rodape;
        }

        return $this->resolveMediaUrl($this->logo_rodape);
    }

    public function getFundoLoginUrlAttribute()
    {
        if (!$this->fundo_login) return null;

        if (filter_var($this->fundo_login, FILTER_VALIDATE_URL)) {
            return $this->fundo_login;
        }

        return $this->resolveMediaUrl($this->fundo_login);
    }
}
