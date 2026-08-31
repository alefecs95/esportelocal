<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Atleta;
use App\Models\Campeonato;
use App\Models\Equipe;
use App\Models\LandingPageSetting;
use App\Models\Partida;
use App\Models\Prefeitura;
use Illuminate\Http\Request;

class LandingPageSettingController extends Controller
{
    /**
     * Display the specified resource.
     */
    public function show()
    {
        $settings = LandingPageSetting::first();
        
        if (!$settings) {
            $settings = LandingPageSetting::create([
                'hero_title' => 'O sistema definitivo para a Gestão Esportiva',
                'hero_subtitle' => 'Digitalize sua Secretaria de Esportes. Elimine o papel, automatize tabelas de campeonatos e conecte a população com um portal exclusivo da sua cidade.',
                'video_url' => '',
                'stats_cidades' => '40+',
                'stats_atletas' => '150k',
                'stats_jogos' => '12k',
                'stats_papel' => 'Zero',
            ]);
        }

        $prefeiturasParceiras = Prefeitura::query()
            ->where('ativo', true)
            ->whereNotNull('logo')
            ->orderBy('nome')
            ->limit(10)
            ->get()
            ->map(function (Prefeitura $prefeitura) {
                return [
                    'id' => $prefeitura->id,
                    'nome' => $prefeitura->nome,
                    'logo_url' => $prefeitura->logo_url,
                ];
            })
            ->values();

        $metricas = [
            'prefeituras_ativas' => Prefeitura::query()->where('ativo', true)->count(),
            'atletas_cadastrados' => Atleta::query()->count(),
            'campeonatos_geridos' => Campeonato::query()->count(),
            'partidas_processadas' => Partida::query()->count(),
            'equipes_ativas' => Equipe::query()->count(),
        ];

        return response()->json(array_merge(
            $settings->toArray(),
            [
                'prefeituras_parceiras' => $prefeiturasParceiras,
                'metricas_ao_vivo' => $metricas,
            ]
        ));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'hero_title' => 'required|string|max:255',
            'hero_subtitle' => 'nullable|string',
            'video_url' => 'nullable|string|url|max:255',
            'stats_cidades' => 'required|string|max:50',
            'stats_atletas' => 'required|string|max:50',
            'stats_jogos' => 'required|string|max:50',
            'stats_papel' => 'required|string|max:50',
            'section_benefits_title' => 'nullable|string|max:255',
            'section_benefits_subtitle' => 'nullable|string',
            'section_how_it_works_title' => 'nullable|string|max:255',
            'section_how_it_works_subtitle' => 'nullable|string',
            'section_features_title' => 'nullable|string|max:255',
            'section_sumula_title' => 'nullable|string|max:255',
            'section_sumula_text' => 'nullable|string',
            'footer_about_text' => 'nullable|string',
            'contact_email' => 'nullable|email|max:255',
            'contact_phone' => 'nullable|string|max:50',
            'social_instagram' => 'nullable|string|url|max:255',
            'social_facebook' => 'nullable|string|url|max:255',
        ]);

        $settings = LandingPageSetting::first();

        if (!$settings) {
            $settings = LandingPageSetting::create($validated);
        } else {
            $settings->update($validated);
        }

        return response()->json($settings);
    }
}
