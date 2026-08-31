<?php

namespace App\Http\Controllers;

use App\Models\Atleta;
use App\Models\Partida;
use App\Models\Inscricao;
use App\Models\Equipe;
use App\Models\Prefeitura;
use App\Models\PartidaEscalacao;
use App\Models\EventoPartida;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

use App\Models\Campeonato;

class PdfController extends Controller
{
    private function assertLinkNotExpired(Request $request): void
    {
        $expires = (int) $request->query('expires', 0);
        if ($expires > 0 && now()->timestamp > $expires) {
            abort(403, 'Link expirado.');
        }
    }

    // Métodos para gerar URLs assinadas (API)
    public function getUrlListaInscritos($campeonatoId)
    {
        // Verifica se o usuário tem permissão para ver este campeonato (policy ou scope já cuida)
        $campeonato = Campeonato::findOrFail($campeonatoId);
        
        $url = URL::signedRoute('pdf.participantes', ['campeonatoId' => $campeonato->id], Carbon::now()->addMinutes(30));
        
        return response()->json(['url' => $url]);
    }

    public function getUrlCarteirinha(Request $request, $atletaId, $campeonatoId = null)
    {
        $atleta = Atleta::findOrFail($atletaId);
        $campeonatoIdFinal = $campeonatoId ?? $request->query('campeonato_id');
        if (!$campeonatoIdFinal) {
            return response()->json(['message' => 'campeonato_id é obrigatório.'], 422);
        }
        Campeonato::findOrFail($campeonatoIdFinal);
        
        $url = URL::signedRoute('pdf.carteirinha', [
            'atletaId' => $atleta->id, 
            'campeonatoId' => $campeonatoIdFinal
        ], Carbon::now()->addMinutes(30));
        
        return response()->json(['url' => $url]);
    }

    public function getUrlSumula($partidaId)
    {
        $partida = Partida::findOrFail($partidaId);
        
        $url = URL::signedRoute('pdf.sumula', ['partidaId' => $partida->id], Carbon::now()->addMinutes(60)); // Súmula pode demorar mais
        
        return response()->json(['url' => $url]);
    }

    public function getUrlEquipeAtletas($equipeId)
    {
        $equipe = Equipe::with('campeonato')->findOrFail($equipeId);
        $url = URL::signedRoute('pdf.equipe-atletas', ['equipeId' => $equipe->id], Carbon::now()->addMinutes(30));
        return response()->json(['url' => $url]);
    }


    // Métodos de Geração de PDF (Protegidos por Signed URL)
    public function gerarListaInscritos(Request $request, $campeonatoId)
    {
        $this->assertLinkNotExpired($request);

        $campeonato = Campeonato::findOrFail($campeonatoId);
        
        $inscricoes = Inscricao::with(['atleta', 'categoria', 'atleta.equipes' => function($q) use ($campeonatoId) {
                // Filter teams for this championship only
                $q->where('campeonato_id', $campeonatoId); 
            }])
            ->where('campeonato_id', $campeonatoId)
            ->get();

        // Group by Category Name
        $inscricoesPorCategoria = $inscricoes->groupBy(function($item) {
            return $item->categoria ? $item->categoria->nome : 'Geral';
        })->sortKeys();

        $data = [
            'campeonato' => $campeonato,
            'inscricoesPorCategoria' => $inscricoesPorCategoria,
            'total' => $inscricoes->count(),
            'data_emissao' => Carbon::now()->format('d/m/Y H:i'),
        ];

        $pdf = Pdf::loadView('pdf.lista_inscritos', $data);
        return $pdf->stream("participantes_{$campeonato->id}.pdf");
    }

    public function gerarCarteirinha(Request $request, $atletaId, $campeonatoId)
    {
        $this->assertLinkNotExpired($request);

        // Buscar atleta e verificar inscrição no campeonato
        $inscricao = Inscricao::with([
            'atleta.arquivos',
            'campeonato',
            'atleta.equipes' => function ($q) use ($campeonatoId) {
                $q->where('campeonato_id', $campeonatoId);
            },
            'atleta.equipes.entidade',
        ])
            ->where('atleta_id', $atletaId)
            ->where('campeonato_id', $campeonatoId)
            ->firstOrFail();

        $atleta = $inscricao->atleta;
        $campeonato = $inscricao->campeonato;
        $equipe = $atleta->equipes->first(); // Equipe neste campeonato
        $prefeitura = Prefeitura::find($campeonato->prefeitura_id);

        $fotoAtleta = $this->resolvePdfAssetPath($atleta->foto);
        if (!$fotoAtleta) {
            $arquivoFoto = $atleta->arquivos->first(function ($arquivo) {
                $tipo = strtolower((string) $arquivo->tipo);
                return str_contains($tipo, 'foto') || str_contains($tipo, 'image');
            });
            $fotoAtleta = $this->resolvePdfAssetPath($arquivoFoto?->path);
        }

        $logoSecretaria = $this->resolvePdfAssetPath($prefeitura?->logo ?: $prefeitura?->logo_rodape);
        $logoEquipe = $this->resolvePdfAssetPath($equipe?->logo ?: $equipe?->entidade?->logo);

        $data = [
            'atleta' => $atleta,
            'campeonato' => $campeonato,
            'equipe' => $equipe,
            'prefeitura' => $prefeitura,
            'credencial_id' => str_pad((string) $inscricao->id, 6, '0', STR_PAD_LEFT),
            'logo_secretaria' => $logoSecretaria,
            'logo_equipe' => $logoEquipe,
            'foto_atleta' => $fotoAtleta,
            'validade' => Carbon::parse($campeonato->data_fim)->format('d/m/Y'),
            'data_emissao' => Carbon::now()->format('d/m/Y'),
        ];

        try {
            $pdf = Pdf::loadView('pdf.carteirinha', $data);
            $pdf->setPaper([0, 0, 360, 220]);
            $conteudo = $pdf->output();

            if (strlen($conteudo) < 1500) {
                throw new \RuntimeException('PDF da carteirinha gerado sem conteúdo visual suficiente.');
            }

            return response($conteudo, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => "inline; filename=carteirinha_{$atleta->id}.pdf",
            ]);
        } catch (\Throwable $e) {
            Log::error('Erro ao renderizar carteirinha moderna', [
                'atleta_id' => $atletaId,
                'campeonato_id' => $campeonatoId,
                'erro' => $e->getMessage(),
            ]);

            $pdf = Pdf::loadView('pdf.carteirinha_fallback', $data);
            $pdf->setPaper([0, 0, 360, 220]);
            return $pdf->stream("carteirinha_{$atleta->id}.pdf");
        }
    }

    public function gerarSumula(Request $request, $partidaId)
    {
        $this->assertLinkNotExpired($request);

        $partida = Partida::with([
            'fase.campeonato',
            'arbitro' // User
        ])->findOrFail($partidaId);

        $prefeitura = null;
        if ($partida->fase?->campeonato?->prefeitura_id) {
            $prefeitura = Prefeitura::find($partida->fase->campeonato->prefeitura_id);
        }

        $escalacoesPorEquipe = PartidaEscalacao::with('atleta')
            ->where('partida_id', $partida->id)
            ->get()
            ->groupBy('equipe_id');

        $montarAtletasParaPdf = function (?Equipe $equipe, int $equipeId) use ($escalacoesPorEquipe) {
            if (!$equipe) {
                return collect();
            }

            $escalacoesEquipe = collect($escalacoesPorEquipe->get($equipeId, []))
                ->sortBy([
                    ['titular', 'desc'],
                    ['ordem', 'asc'],
                ])
                ->values();

            if ($escalacoesEquipe->isEmpty()) {
                return $equipe->atletasAprovados->map(function ($atleta) {
                    $atleta->setAttribute('escala_tipo', '');
                    $atleta->setAttribute('escala_capitao', false);
                    return $atleta;
                })->values();
            }

            return $escalacoesEquipe
                ->map(function ($esc) {
                    if (!$esc->atleta) {
                        return null;
                    }
                    $esc->atleta->setAttribute('escala_tipo', $esc->titular ? 'TIT' : 'RES');
                    $esc->atleta->setAttribute('escala_capitao', (bool) $esc->capitao);
                    return $esc->atleta;
                })
                ->filter()
                ->values();
        };

        $partida->mandante_equipe = Equipe::with(['atletasAprovados' => function($q) {
            $q->orderBy('nome');
        }])->find($partida->mandante_id);
        if ($partida->mandante_equipe) {
            $partida->mandante_equipe->setRelation('atletas', $montarAtletasParaPdf($partida->mandante_equipe, (int) $partida->mandante_id));
        }

        $partida->visitante_equipe = Equipe::with(['atletasAprovados' => function($q) {
            $q->orderBy('nome');
        }])->find($partida->visitante_id);
        if ($partida->visitante_equipe) {
            $partida->visitante_equipe->setRelation('atletas', $montarAtletasParaPdf($partida->visitante_equipe, (int) $partida->visitante_id));
        }

        $eventos = EventoPartida::with(['jogador', 'jogadorSaiu'])
            ->where('partida_id', $partida->id)
            ->orderBy('minuto')
            ->orderBy('id')
            ->get();

        $estatisticasAtleta = [];
        $sumarioEventos = [
            (int) $partida->mandante_id => ['gols' => [], 'ca' => [], 'cv' => [], 'subs' => []],
            (int) $partida->visitante_id => ['gols' => [], 'ca' => [], 'cv' => [], 'subs' => []],
        ];

        foreach ($eventos as $evento) {
            $jogadorId = (int) ($evento->jogador_id ?? 0);
            if ($jogadorId > 0 && !isset($estatisticasAtleta[$jogadorId])) {
                $estatisticasAtleta[$jogadorId] = ['gols' => 0, 'ca' => 0, 'cv' => 0];
            }

            $timeId = (int) ($evento->time_id ?? 0);
            $minuto = (int) ($evento->minuto ?? 0);
            $nomeJogador = trim((string) ($evento->jogador?->nome ?? 'Atleta'));
            $descricao = "{$minuto}' - {$nomeJogador}";

            if ($evento->tipo_evento === 'GOL') {
                if ($jogadorId > 0) {
                    $estatisticasAtleta[$jogadorId]['gols']++;
                }
                if (isset($sumarioEventos[$timeId])) {
                    $sumarioEventos[$timeId]['gols'][] = $descricao;
                }
                continue;
            }

            if ($evento->tipo_evento === 'GOL_CONTRA') {
                if ($jogadorId > 0) {
                    $estatisticasAtleta[$jogadorId]['gols']++;
                }
                $timeBeneficiado = $timeId === (int) $partida->mandante_id
                    ? (int) $partida->visitante_id
                    : (int) $partida->mandante_id;
                if (isset($sumarioEventos[$timeBeneficiado])) {
                    $sumarioEventos[$timeBeneficiado]['gols'][] = "{$minuto}' - {$nomeJogador} (GC)";
                }
                continue;
            }

            if ($evento->tipo_evento === 'CARTAO_AMARELO') {
                if ($jogadorId > 0) {
                    $estatisticasAtleta[$jogadorId]['ca']++;
                }
                if (isset($sumarioEventos[$timeId])) {
                    $sumarioEventos[$timeId]['ca'][] = $descricao;
                }
                continue;
            }

            if ($evento->tipo_evento === 'CARTAO_VERMELHO') {
                if ($jogadorId > 0) {
                    $estatisticasAtleta[$jogadorId]['cv']++;
                }
                if (isset($sumarioEventos[$timeId])) {
                    $sumarioEventos[$timeId]['cv'][] = $descricao;
                }
                continue;
            }

            if ($evento->tipo_evento === 'SUBSTITUICAO') {
                $nomeEntrou = trim((string) ($evento->jogador?->nome ?? 'Atleta'));
                $nomeSaiu = trim((string) ($evento->jogadorSaiu?->nome ?? 'Atleta'));
                if (isset($sumarioEventos[$timeId])) {
                    $sumarioEventos[$timeId]['subs'][] = "{$minuto}' - Saiu: {$nomeSaiu} | Entrou: {$nomeEntrou}";
                }
            }
        }

        $data = [
            'partida' => $partida,
            'campeonato' => $partida->fase->campeonato,
            'data_jogo' => $partida->data_jogo ? Carbon::parse($partida->data_jogo)->format('d/m/Y H:i') : 'A definir',
            'estatisticas_atleta' => $estatisticasAtleta,
            'sumario_eventos' => $sumarioEventos,
            'data_geracao' => Carbon::now()->format('d/m/Y H:i'),
            'plataforma_url' => $prefeitura?->dominio ?: config('app.url'),
        ];

        $pdf = Pdf::loadView('pdf.sumula', $data);
        $dompdf = $pdf->getDomPDF();
        $dompdf->render();
        $canvas = $dompdf->getCanvas();
        $font = $dompdf->getFontMetrics()->getFont('DejaVu Sans', 'normal');
        $plataformaUrl = trim((string) (($data['plataforma_url'] ?: config('app.url')) ?: request()->getSchemeAndHttpHost()));
        if ($plataformaUrl !== '' && !preg_match('#^https?://#i', $plataformaUrl)) {
            $plataformaUrl = 'https://' . ltrim($plataformaUrl, '/');
        }
        $partesUrl = parse_url($plataformaUrl);
        $sitePlataforma = $plataformaUrl;
        if (is_array($partesUrl) && !empty($partesUrl['host'])) {
            $sitePlataforma = ($partesUrl['scheme'] ?? request()->getScheme()) . '://' . $partesUrl['host'];
            if (!empty($partesUrl['port'])) {
                $sitePlataforma .= ':' . $partesUrl['port'];
            }
        }
        $rodapeInfo = 'Plataforma: ' . $sitePlataforma . ' | Gerado em: ' . $data['data_geracao'];
        $y = $canvas->get_height() - 18;

        $canvas->page_text(34, $y, 'Página {PAGE_NUM} de {PAGE_COUNT}', $font, 8, [0.28, 0.33, 0.41]);
        $canvas->page_text(170, $y, $rodapeInfo, $font, 8, [0.28, 0.33, 0.41]);

        $conteudo = $dompdf->output();

        return response($conteudo, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "inline; filename=sumula_partida_{$partida->id}.pdf",
        ]);
    }

    public function gerarFichaAtleta(Request $request, $atletaId)
    {
        $atleta = Atleta::with(['modalidades', 'arquivos', 'prefeitura'])->findOrFail($atletaId);
        $instituicao = $atleta->prefeitura;

        if (!$instituicao && App::has('current_tenant')) {
            $instituicao = App::get('current_tenant');
        }

        $instituicaoLogo = null;
        if ($instituicao && !empty($instituicao->logo)) {
            if (filter_var($instituicao->logo, FILTER_VALIDATE_URL)) {
                $instituicaoLogo = $instituicao->logo;
            } else {
                $logoPath = storage_path('app/public/' . ltrim($instituicao->logo, '/'));
                if (file_exists($logoPath)) {
                    $instituicaoLogo = $logoPath;
                }
            }
        }

        $fotoAtleta = null;
        if (!empty($atleta->foto)) {
            $fotoPath = storage_path('app/public/' . ltrim($atleta->foto, '/'));
            if (file_exists($fotoPath)) {
                $fotoAtleta = $fotoPath;
            }
        }

        if (!$fotoAtleta) {
            $arquivoFoto = $atleta->arquivos
                ->first(function ($arquivo) {
                    return str_contains((string) $arquivo->tipo, 'image') || strtolower((string) $arquivo->tipo) === 'foto';
                });

            if ($arquivoFoto && !empty($arquivoFoto->path)) {
                $fotoPath = storage_path('app/public/' . ltrim($arquivoFoto->path, '/'));
                if (file_exists($fotoPath)) {
                    $fotoAtleta = $fotoPath;
                }
            }
        }

        $data = [
            'instituicao' => $instituicao,
            'instituicao_logo' => $instituicaoLogo,
            'atleta' => $atleta,
            'foto_atleta' => $fotoAtleta,
            'data_emissao' => Carbon::now()->format('d/m/Y H:i'),
        ];

        $pdf = Pdf::loadView('pdf.ficha_atleta', $data);
        $pdf->setPaper('a4', 'portrait');

        return $pdf->download("ficha_atleta_{$atleta->id}.pdf");
    }

    public function gerarEquipeAtletas(Request $request, $equipeId)
    {
        $this->assertLinkNotExpired($request);

        $equipe = Equipe::with(['campeonato', 'entidade'])->findOrFail($equipeId);
        $campeonato = $equipe->campeonato;
        $prefeitura = $campeonato ? Prefeitura::find($campeonato->prefeitura_id) : null;

        $atletas = $equipe->atletas()
            ->orderBy('nome')
            ->get(['atletas.id', 'atletas.nome', 'atletas.cpf', 'atletas.data_nascimento', 'atletas.nome_mae']);

        $logoSecretaria = $this->resolvePdfAssetPath($prefeitura?->logo);
        $logoRodape = $this->resolvePdfAssetPath($prefeitura?->logo_rodape);
        $logoEquipe = $this->resolvePdfAssetPath($equipe->logo ?: $equipe->entidade?->logo);

        $data = [
            'equipe' => $equipe,
            'campeonato' => $campeonato,
            'prefeitura' => $prefeitura,
            'atletas' => $atletas,
            'logo_secretaria' => $logoSecretaria,
            'logo_rodape' => $logoRodape,
            'logo_equipe' => $logoEquipe,
            'data_emissao' => Carbon::now()->format('d/m/Y H:i'),
        ];

        $pdf = Pdf::loadView('pdf.equipe_atletas', $data);
        $pdf->setPaper('a4', 'portrait');

        return $pdf->stream("equipe_{$equipe->id}_atletas.pdf");
    }

    private function resolvePdfAssetPath(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        $raw = trim($value);
        if ($raw === '') {
            return null;
        }

        if (file_exists($raw)) {
            return $raw;
        }

        $normalized = ltrim(str_replace('\\', '/', $raw), '/');
        $fromUrl = null;
        if (filter_var($raw, FILTER_VALIDATE_URL)) {
            $path = parse_url($raw, PHP_URL_PATH);
            if (is_string($path) && $path !== '') {
                $fromUrl = ltrim($path, '/');
            }
        }

        $paths = array_filter([
            storage_path('app/public/' . $normalized),
            public_path($normalized),
            public_path('storage/' . preg_replace('#^storage/#', '', $normalized)),
            $normalized ? storage_path('app/public/' . preg_replace('#^storage/#', '', $normalized)) : null,
            $fromUrl ? storage_path('app/public/' . preg_replace('#^storage/#', '', $fromUrl)) : null,
            $fromUrl ? public_path($fromUrl) : null,
        ]);

        foreach ($paths as $path) {
            if (is_string($path) && file_exists($path)) {
                return $path;
            }
        }

        return null;
    }
}
