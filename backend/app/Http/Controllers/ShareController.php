<?php

namespace App\Http\Controllers;

use App\Models\Noticia;
use App\Models\Prefeitura;
use Illuminate\Http\Request;

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

        $tenant = app()->has('current_tenant') ? app('current_tenant') : null;
        if (!$tenant instanceof Prefeitura && $noticia->prefeitura_id) {
            $tenant = Prefeitura::withoutGlobalScopes()->find($noticia->prefeitura_id);
        }

        $tenantName = $tenant?->nome ?: 'SEMEL - Esporte Local';
        $description = trim((string) ($noticia->meta_description ?: 'Acompanhe notícias e atualizações esportivas do município.'));
        $image = $noticia->imagem_capa_url ?: ($tenant?->logo_url ?: null);
        $frontendUrl = $this->resolveFrontendUrl($tenant);
        $articleSlug = $noticia->slug ?: (string) $noticia->id;
        $articleUrl = rtrim($frontendUrl, '/') . '/noticias/' . $articleSlug;
        $title = $noticia->titulo . ' | ' . $tenantName;

        return response()
            ->view('share.noticia', compact('title', 'description', 'image', 'articleUrl'))
            ->header('Cache-Control', 'public, max-age=300');
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
