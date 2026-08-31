<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Prefeitura;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class TenantController extends Controller
{
    public function show(Request $request)
    {
        if (!App::has('current_tenant')) {
            return response()->json(['message' => 'Nenhum tenant identificado.'], 404);
        }

        $tenant = App::get('current_tenant');
        if (!$tenant instanceof Prefeitura && isset($tenant->id)) {
            $tenant = Prefeitura::withoutGlobalScopes()->find($tenant->id);
        }

        if (!$tenant instanceof Prefeitura) {
            return response()->json(['message' => 'Nenhum tenant identificado.'], 404);
        }

        try {
            $logoUrl = $tenant->logo_url;
        } catch (\Throwable $e) {
            $logoUrl = null;
        }

        try {
            $bannerPortalUrl = $tenant->banner_portal_url;
        } catch (\Throwable $e) {
            $bannerPortalUrl = null;
        }

        try {
            $logoRodapeUrl = $tenant->logo_rodape_url;
        } catch (\Throwable $e) {
            $logoRodapeUrl = null;
        }

        try {
            $fundoLoginUrl = $tenant->fundo_login_url;
        } catch (\Throwable $e) {
            $fundoLoginUrl = null;
        }

        return response()->json([
            'id' => $tenant->id,
            'nome' => $tenant->nome,
            'slug' => $tenant->slug,
            'dominio' => $tenant->dominio,
            'cor_primaria' => $tenant->cor_primaria,
            'cor_secundaria' => $tenant->cor_secundaria,
            'logo' => is_string($tenant->logo) ? $tenant->logo : null,
            'logo_rodape' => is_string($tenant->logo_rodape) ? $tenant->logo_rodape : null,
            'banner_portal' => is_string($tenant->banner_portal) ? $tenant->banner_portal : null,
            'fundo_login' => is_string($tenant->fundo_login) ? $tenant->fundo_login : null,
            'logo_url' => is_string($logoUrl) ? $logoUrl : null,
            'logo_rodape_url' => is_string($logoRodapeUrl) ? $logoRodapeUrl : null,
            'banner_portal_url' => is_string($bannerPortalUrl) ? $bannerPortalUrl : null,
            'fundo_login_url' => is_string($fundoLoginUrl) ? $fundoLoginUrl : null,
            'footer_titulo' => is_string($tenant->footer_titulo) ? $tenant->footer_titulo : null,
            'footer_link_transparencia' => is_string($tenant->footer_link_transparencia) ? $tenant->footer_link_transparencia : null,
            'footer_link_editais' => is_string($tenant->footer_link_editais) ? $tenant->footer_link_editais : null,
            'footer_link_contato' => is_string($tenant->footer_link_contato) ? $tenant->footer_link_contato : null,
            'redes_sociais' => is_array($tenant->redes_sociais) ? $tenant->redes_sociais : [],
            'ativo' => (bool) $tenant->ativo,
        ]);
    }
}
