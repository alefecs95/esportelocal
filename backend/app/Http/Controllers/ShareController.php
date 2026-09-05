<?php

namespace App\Http\Controllers;

use App\Models\MemoriaAcervo;
use App\Models\MemoriaEquipe;
use App\Models\MemoriaLocal;
use App\Models\MemoriaMarco;
use App\Models\MemoriaPersonalidade;
use App\Models\Noticia;
use App\Models\Prefeitura;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ShareController extends Controller
{
    public function noticia(Request $request, string $slug)
    {
        $noticia = Noticia::query()
            ->where('slug', $slug)
            ->orWhere('id', $slug)
            ->firstOrFail();

        if (!$noticia->publicado || ($noticia->publicado_em && $noticia->publicado_em->isFuture())) {
            abort(404);
        }

        $tenant = $this->resolveTenant($noticia->prefeitura_id);
        $tenantName = $tenant?->nome ?: 'SEMEL - Esporte Local';
        $description = trim((string) ($noticia->meta_description ?: 'Acompanhe notícias e atualizações esportivas do município.'));
        $image = $noticia->imagem_capa_url ?: ($tenant?->logo_url ?: null);
        $frontendUrl = $this->resolveFrontendUrl($tenant);
        $articleSlug = $noticia->slug ?: (string) $noticia->id;
        $articleUrl = rtrim($frontendUrl, '/') . '/noticias/' . $articleSlug;
        $title = $noticia->titulo . ' | ' . $tenantName;

        return $this->shareResponse($title, $description, $image, $articleUrl);
    }

    public function memoriaAcervo(string $slug)
    {
        $item = MemoriaAcervo::aprovado()
            ->where(function ($q) use ($slug) {
                $q->where('slug', $slug)->orWhere('id', $slug);
            })
            ->firstOrFail();

        $tenant = $this->resolveTenant($item->prefeitura_id);
        $tenantName = $tenant?->nome ?: 'SEMEL - Esporte Local';
        $description = trim((string) (Str::limit(strip_tags((string) ($item->descricao ?: $item->historia ?: '')), 180) ?: 'Peça do Memorial Esportivo municipal.'));
        $image = $item->tipo === 'foto' ? ($item->arquivo_url ?: ($tenant?->logo_url ?: null)) : ($tenant?->logo_url ?: null);
        $url = rtrim($this->resolveFrontendUrl($tenant), '/') . '/memoria/acervo/' . ($item->slug ?: $item->id);
        $year = $item->data_incerta ? 'Data a confirmar' : ($item->ano ?: '');
        $title = trim($item->titulo . ($year ? ' (' . $year . ')' : '')) . ' | Memorial Esportivo de ' . $tenantName;

        return $this->shareResponse($title, $description, $image, $url);
    }

    public function memoriaMarco(string $slug)
    {
        $marco = MemoriaMarco::publicado()
            ->where(function ($q) use ($slug) {
                $q->where('slug', $slug)->orWhere('id', $slug);
            })
            ->firstOrFail();

        $tenant = $this->resolveTenant($marco->prefeitura_id);
        $tenantName = $tenant?->nome ?: 'SEMEL - Esporte Local';
        $description = trim((string) ($marco->resumo ?: 'Fato histórico da Memória do Esporte municipal.'));
        $image = $marco->capa_url ?: ($tenant?->logo_url ?: null);
        $url = rtrim($this->resolveFrontendUrl($tenant), '/') . '/memoria/marcos/' . ($marco->slug ?: $marco->id);

        return $this->shareResponse($marco->titulo . ' | ' . $tenantName, $description, $image, $url);
    }

    public function memoriaPersonalidade(string $slug)
    {
        $item = MemoriaPersonalidade::publicado()
            ->where(function ($q) use ($slug) {
                $q->where('slug', $slug)->orWhere('id', $slug);
            })
            ->firstOrFail();

        $tenant = $this->resolveTenant($item->prefeitura_id);
        $tenantName = $tenant?->nome ?: 'SEMEL - Esporte Local';
        $description = trim((string) (Str::limit(strip_tags((string) ($item->mini_bio ?? '')), 180) ?: 'Personalidade da Memória do Esporte municipal.'));
        $image = $item->foto_url ?: ($tenant?->logo_url ?: null);
        $url = rtrim($this->resolveFrontendUrl($tenant), '/') . '/memoria/personalidades/' . ($item->slug ?: $item->id);

        return $this->shareResponse($item->nome . ' | ' . $tenantName, $description, $image, $url);
    }

    public function memoriaLocal(string $slug)
    {
        $item = MemoriaLocal::publicado()
            ->where(function ($q) use ($slug) {
                $q->where('slug', $slug)->orWhere('id', $slug);
            })
            ->firstOrFail();

        $tenant = $this->resolveTenant($item->prefeitura_id);
        $tenantName = $tenant?->nome ?: 'SEMEL - Esporte Local';
        $description = trim((string) ($item->descricao ?: $item->endereco ?: 'Local histórico da Memória do Esporte municipal.'));
        $image = $item->foto_url ?: $item->foto_depois_url ?: ($tenant?->logo_url ?: null);
        $url = rtrim($this->resolveFrontendUrl($tenant), '/') . '/memoria/locais/' . ($item->slug ?: $item->id);

        return $this->shareResponse($item->nome . ' | ' . $tenantName, $description, $image, $url);
    }

    public function memoriaEquipe(string $slug)
    {
        $item = MemoriaEquipe::publicado()
            ->where(function ($q) use ($slug) {
                $q->where('slug', $slug)->orWhere('id', $slug);
            })
            ->firstOrFail();

        $tenant = $this->resolveTenant($item->prefeitura_id);
        $tenantName = $tenant?->nome ?: 'SEMEL - Esporte Local';
        $description = trim((string) ($item->descricao ?: $item->historia ?: 'Equipe histórica da Memória do Esporte municipal.'));
        $image = $item->escudo_url ?: ($tenant?->logo_url ?: null);
        $url = rtrim($this->resolveFrontendUrl($tenant), '/') . '/memoria/equipes/' . ($item->slug ?: $item->id);

        return $this->shareResponse($item->nome . ' | ' . $tenantName, $description, $image, $url);
    }

    private function shareResponse(string $title, string $description, ?string $image, string $articleUrl)
    {
        return response()
            ->view('share.noticia', compact('title', 'description', 'image', 'articleUrl'))
            ->header('Cache-Control', 'public, max-age=300');
    }

    private function resolveTenant(?int $prefeituraId): ?Prefeitura
    {
        $tenant = app()->has('current_tenant') ? app('current_tenant') : null;
        if (!$tenant instanceof Prefeitura && $prefeituraId) {
            $tenant = Prefeitura::withoutGlobalScopes()->find($prefeituraId);
        }
        return $tenant;
    }

    private function resolveFrontendUrl(?Prefeitura $tenant): string
    {
        if ($tenant && !empty($tenant->dominio)) {
            $domain = (string) $tenant->dominio;
            if (!str_starts_with($domain, 'http')) {
                $domain = 'https://' . $domain;
            }
            return rtrim($domain, '/');
        }

        return rtrim((string) env('FRONTEND_URL', config('app.url')), '/');
    }
}
