<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Atleta;
use App\Models\Campeonato;
use App\Models\Equipe;
use App\Models\Modalidade;
use App\Models\Partida;
use App\Models\Noticia;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PublicController extends Controller
{
    public function getCampeonatoBanner(int $id)
    {
        $campeonato = Campeonato::findOrFail($id);
        $banner = $campeonato->banner;

        if (!$banner || str_starts_with($banner, 'http://') || str_starts_with($banner, 'https://')) {
            abort(404);
        }

        $bannerPath = ltrim($banner, '/');
        if (str_starts_with($bannerPath, 'storage/')) {
            $bannerPath = substr($bannerPath, 8);
        }
        if (str_starts_with($bannerPath, 'public/')) {
            $bannerPath = substr($bannerPath, 7);
        }

        // Caso o disco "public" falhe, tentar caminho absoluto
        $disk = Storage::disk('public');
        if (!$disk->exists($bannerPath)) {
            if (!$disk->exists($banner)) {
                $absolutePath = storage_path('app/public/' . $bannerPath);
                if (file_exists($absolutePath)) {
                    $conteudo = file_get_contents($absolutePath);
                    $mime = mime_content_type($absolutePath) ?: 'application/octet-stream';
                    return response($conteudo, 200, [
                        'Content-Type' => $mime,
                        'Cache-Control' => 'public, max-age=86400',
                    ]);
                }
                abort(404, 'Banner não encontrado no disco.');
            }
            $bannerPath = $banner;
        }

        $conteudo = $disk->get($bannerPath);
        $mime = $disk->mimeType($bannerPath) ?: 'application/octet-stream';

        return response($conteudo, 200, [
            'Content-Type' => $mime,
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    public function getHomeData()
    {
        try {
            $stats = [
                'campeonatos_realizados' => Campeonato::where('status', 'finalizado')->count() + Campeonato::where('status', 'em_andamento')->count(),
                'atletas_cadastrados' => Atleta::count(),
                'equipes_ativas' => Equipe::count(), 
                'modalidades' => Modalidade::count(),
            ];

            $partidas = Partida::with(['fase.campeonato'])
                ->whereIn('status', ['agendada', 'em_andamento'])
                ->whereNotNull('data_jogo')
                ->where('data_jogo', '>=', Carbon::now()->subHours(4))
                ->orderBy('data_jogo', 'asc')
                ->limit(10)
                ->get()
                ->map(function ($partida) {
                    $data = Carbon::parse($partida->data_jogo);
                    $mandanteLogo = $partida->mandante->logo ?? null;
                    $visitanteLogo = $partida->visitante->logo ?? null;
                    $campeonatoNome = $partida->fase?->campeonato?->nome ?? 'Amistoso';

                    return [
                        'id' => 'p_' . $partida->id, 
                        'tipo' => 'partida',
                        'campeonato' => $campeonatoNome,
                        'titulo' => $campeonatoNome,
                        'subtitulo' => $partida->fase?->nome ?? '',
                        'data_iso' => $partida->data_jogo, 
                        'data' => $data->format('d/m'),
                        'hora' => $data->format('H:i'),
                        'local' => $partida->local ?? 'Local a definir',
                        'mandante' => [
                            'nome' => $partida->mandante->nome ?? 'A definir',
                            'logo_url' => $partida->mandante->logo_url ?? null,
                        ],
                        'visitante' => [
                            'nome' => $partida->visitante->nome ?? 'A definir',
                            'logo_url' => $partida->visitante->logo_url ?? null,
                        ],
                        'imagem_url' => null 
                    ];
                });

            return response()->json([
                'stats' => $stats,
                'proximos_eventos' => $partidas
            ]);
        } catch (\Throwable $e) {
            report($e);
            return response()->json([
                'stats' => [
                    'campeonatos_realizados' => 0,
                    'atletas_cadastrados' => 0,
                    'equipes_ativas' => 0,
                    'modalidades' => 0,
                ],
                'proximos_eventos' => [],
                'message' => 'Não foi possível carregar os dados da home.',
            ], 200);
        }
    }

    public function getPartidas(Request $request)
    {
        $perPage = (int) $request->query('per_page', 12);
        $perPage = max(6, min($perPage, 50));
        $status = $request->query('status');
        $campeonatoId = $request->query('campeonato_id');

        $query = Partida::query()
            ->with(['fase.campeonato'])
            ->whereNotNull('data_jogo')
            ->when($status, function ($q) use ($status) {
                $q->where('status', $status);
            })
            ->when($campeonatoId, function ($q) use ($campeonatoId) {
                $q->whereHas('fase', function ($faseQuery) use ($campeonatoId) {
                    $faseQuery->where('campeonato_id', $campeonatoId);
                });
            })
            ->orderByRaw("case when status = 'em_andamento' then 0 when status = 'agendada' then 1 else 2 end")
            ->orderBy('data_jogo', 'asc');

        $partidas = $query->paginate($perPage);
        $partidas->getCollection()->transform(function ($partida) {
            $partida->append(['mandante', 'visitante']);
            return $partida;
        });

        return response()->json($partidas);
    }

    public function getNoticias(Request $request)
    {
        $perPage = (int) $request->query('per_page', 10);
        $perPage = max(5, min($perPage, 50));
        $query = Noticia::query()
            ->where('publicado', true)
            ->orderByDesc('publicado_em')
            ->orderByDesc('created_at');
        return response()->json($query->paginate($perPage));
    }

    public function getNoticia($id)
    {
        $noticia = Noticia::where('publicado', true)->findOrFail($id);
        return response()->json($noticia);
    }

    public function getArtilharia($id)
    {
        $campeonato = Campeonato::findOrFail($id);
        
        $gols = DB::table('eventos_partida')
            ->join('partidas', 'eventos_partida.partida_id', '=', 'partidas.id')
            ->join('fases', 'partidas.fase_id', '=', 'fases.id')
            ->join('atletas', 'eventos_partida.jogador_id', '=', 'atletas.id')
            ->join('equipes', 'eventos_partida.time_id', '=', 'equipes.id')
            ->where('fases.campeonato_id', $id)
            ->where('eventos_partida.tipo_evento', 'GOL')
            ->select(
                'atletas.id', 
                'atletas.nome', 
                'atletas.foto', 
                'equipes.nome as equipe_nome', 
                'equipes.logo as equipe_logo', 
                DB::raw('count(*) as gols')
            )
            ->groupBy('atletas.id', 'atletas.nome', 'atletas.foto', 'equipes.nome', 'equipes.logo')
            ->orderByDesc('gols')
            ->get()
            ->map(function ($item) {
                $item->foto_url = $item->foto ? asset('storage/' . $item->foto) : null;
                $item->equipe_logo_url = $item->equipe_logo ? asset('storage/' . $item->equipe_logo) : null;
                return $item;
            });
            
        return response()->json($gols);
    }

    public function getCartoes($id)
    {
        $campeonato = Campeonato::findOrFail($id);
        $suspensaoAutomaticaAtiva = (bool) ($campeonato->suspensao_automatica_ativa ?? true);
        $cartoesAmarelosParaSuspensao = max(1, (int) ($campeonato->cartoes_amarelos_para_suspensao ?? 2));
        $cartaoVermelhoSuspende = (bool) ($campeonato->cartao_vermelho_suspende ?? true);

        $cartoes = DB::table('eventos_partida')
            ->join('partidas', 'eventos_partida.partida_id', '=', 'partidas.id')
            ->join('fases', 'partidas.fase_id', '=', 'fases.id')
            ->join('atletas', 'eventos_partida.jogador_id', '=', 'atletas.id')
            ->join('equipes', 'eventos_partida.time_id', '=', 'equipes.id')
            ->where('fases.campeonato_id', $id)
            ->where('partidas.status', 'finalizada')
            ->whereIn('eventos_partida.tipo_evento', ['CARTAO_AMARELO', 'CARTAO_VERMELHO'])
            ->select(
                'atletas.id',
                'atletas.nome',
                'atletas.foto',
                'equipes.id as equipe_id',
                'equipes.nome as equipe_nome',
                'equipes.logo as equipe_logo',
                'eventos_partida.tipo_evento',
                'partidas.data_jogo',
                'partidas.id as partida_id'
            )
            ->orderBy('partidas.data_jogo')
            ->orderBy('partidas.id')
            ->get();

        $ranking = [];
        foreach ($cartoes as $cartao) {
            $atletaId = (int) $cartao->id;
            $equipeId = (int) $cartao->equipe_id;
            $chave = "{$atletaId}_{$equipeId}";

            if (!isset($ranking[$chave])) {
                $ranking[$chave] = [
                    'id' => $atletaId,
                    'nome' => $cartao->nome,
                    'foto_url' => $cartao->foto ? asset('storage/' . $cartao->foto) : null,
                    'equipe_nome' => $cartao->equipe_nome,
                    'equipe_logo_url' => $cartao->equipe_logo ? asset('storage/' . $cartao->equipe_logo) : null,
                    'equipe_id' => $equipeId,
                    'cartoes' => [],
                    'amarelos' => 0,
                    'vermelhos' => 0,
                    'suspenso' => false,
                    'status_texto' => 'Regular',
                    'motivo_suspensao' => '',
                    'suspensao_automatica_ativa' => $suspensaoAutomaticaAtiva,
                    'cartoes_amarelos_para_suspensao' => $cartoesAmarelosParaSuspensao,
                    'cartao_vermelho_suspende' => $cartaoVermelhoSuspende,
                ];
            }

            $ranking[$chave]['cartoes'][] = [
                'partida_id' => (int) $cartao->partida_id,
                'tipo_evento' => $cartao->tipo_evento,
            ];
        }

        if (empty($ranking)) {
            return response()->json([]);
        }

        $equipesIds = array_values(array_unique(array_map(
            fn ($jogador) => (int) $jogador['equipe_id'],
            $ranking
        )));

        $partidasFinalizadasPorEquipe = [];
        foreach ($equipesIds as $equipeId) {
            $partidasFinalizadasPorEquipe[$equipeId] = Partida::query()
                ->with('fase:id,configuracao')
                ->where(function ($q) use ($equipeId) {
                    $q->where('mandante_id', $equipeId)
                      ->orWhere('visitante_id', $equipeId);
                })
                ->whereHas('fase', function ($q) use ($id) {
                    $q->where('campeonato_id', $id);
                })
                ->where('status', 'finalizada')
                ->orderBy('data_jogo')
                ->orderBy('id')
                ->get(['id', 'fase_id'])
                ->map(fn ($partida) => [
                    'id' => (int) $partida->id,
                    'fase_id' => (int) $partida->fase_id,
                    'fase_zerar_cartoes' => $this->faseZeraCartoes($partida->fase?->configuracao),
                ])
                ->all();
        }

        foreach ($ranking as &$jogador) {
            if (!$suspensaoAutomaticaAtiva) {
                unset($jogador['cartoes']);
                continue;
            }

            $cartoesPorPartida = collect($jogador['cartoes'])->groupBy('partida_id');
            $partidasDaEquipe = $partidasFinalizadasPorEquipe[$jogador['equipe_id']] ?? [];
            $amarelosAcumulados = 0;
            $suspensoesPendentes = 0;
            $motivo = '';
            $suspensaoPorVermelho = false;
            $ultimaFaseId = null;

            foreach ($partidasDaEquipe as $partidaRef) {
                $partidaId = (int) ($partidaRef['id'] ?? 0);
                $faseIdAtual = (int) ($partidaRef['fase_id'] ?? 0);
                $entrouNovaFase = $ultimaFaseId !== null && $faseIdAtual !== $ultimaFaseId;
                if ($entrouNovaFase && !empty($partidaRef['fase_zerar_cartoes'])) {
                    $suspensoesPendentes = 0;
                    $amarelosAcumulados = 0;
                    $motivo = '';
                    $suspensaoPorVermelho = false;
                }
                $ultimaFaseId = $faseIdAtual;

                if ($suspensoesPendentes > 0) {
                    $suspensoesPendentes--;
                    if ($suspensoesPendentes === 0) {
                        $amarelosAcumulados = 0;
                        $suspensaoPorVermelho = false;
                        $motivo = '';
                    }
                    continue;
                }

                $eventosPartida = $cartoesPorPartida->get($partidaId, collect());
                if ($eventosPartida->isEmpty()) {
                    continue;
                }

                $qtdAmarelos = $eventosPartida->where('tipo_evento', 'CARTAO_AMARELO')->count();
                if ($qtdAmarelos > 0) {
                    $amarelosAcumulados += $qtdAmarelos;
                }

                if ($cartaoVermelhoSuspende && $eventosPartida->contains(fn ($evento) => $evento['tipo_evento'] === 'CARTAO_VERMELHO')) {
                    $suspensoesPendentes++;
                    $suspensaoPorVermelho = true;
                    $motivo = 'Vermelho direto';
                }

                if ($amarelosAcumulados >= $cartoesAmarelosParaSuspensao) {
                    $novasSuspensoes = intdiv($amarelosAcumulados, $cartoesAmarelosParaSuspensao);
                    $suspensoesPendentes += $novasSuspensoes;
                    $amarelosAcumulados = $amarelosAcumulados % $cartoesAmarelosParaSuspensao;
                    $motivo = "Acúmulo de cartões amarelos ({$cartoesAmarelosParaSuspensao})";
                }
            }

            $jogador['amarelos'] = $amarelosAcumulados;
            $jogador['vermelhos'] = ($suspensoesPendentes > 0 && $suspensaoPorVermelho) ? 1 : 0;
            $jogador['suspenso'] = $suspensoesPendentes > 0;
            $jogador['status_texto'] = $jogador['suspenso'] ? 'Suspenso' : 'Regular';
            $jogador['motivo_suspensao'] = $jogador['suspenso'] ? $motivo : '';
            unset($jogador['cartoes']);
        }

        return response()->json(array_values($ranking));
    }

    private function faseZeraCartoes($configuracaoFase): bool
    {
        if (is_string($configuracaoFase)) {
            $configuracaoFase = json_decode($configuracaoFase, true);
        } elseif (is_object($configuracaoFase)) {
            $configuracaoFase = (array) $configuracaoFase;
        }
        $config = is_array($configuracaoFase) ? $configuracaoFase : [];
        return (bool) ($config['zerar_cartoes_na_fase'] ?? false);
    }

    public function getEquipes(Request $request)
    {
        $perPage = (int) $request->query('per_page', 12);
        $search = $request->query('search');

        $query = Equipe::query()
            ->with(['entidade', 'campeonato']) // Removido 'modalidade' que não existe diretamente em Equipe
            ->when($search, function ($q) use ($search) {
                $q->where('nome', 'like', "%{$search}%")
                  ->orWhereHas('entidade', function($q2) use ($search) {
                      $q2->where('cidade', 'like', "%{$search}%");
                  });
            })
            ->orderBy('nome');

        return response()->json($query->paginate($perPage));
    }

    public function getEquipeDetalhe($id)
    {
        $equipe = Equipe::with(['entidade.equipes.campeonato', 'campeonato.modalidade', 'atletasAprovados'])->findOrFail($id);

        $historicoCompeticoes = collect();
        if ($equipe->entidade) {
            $historicoCompeticoes = $equipe->entidade->equipes
                ->filter(function ($e) {
                    return $e->campeonato !== null;
                })
                ->map(function($e) {
                $campeonato = $e->campeonato;
                return [
                    'id' => $e->id,
                    'campeonato_nome' => $campeonato?->nome ?? 'N/A',
                    'ano' => $campeonato?->data_inicio ? \Carbon\Carbon::parse($campeonato->data_inicio)->year : null,
                    'colocacao' => $e->colocacao,
                    'modalidade' => $campeonato?->modalidade?->nome ?? 'N/A'
                ];
            });
        }

        // Últimos 5 jogos
        $ultimosJogos = Partida::query()
            ->where(function($q) use ($id) {
                $q->where('mandante_id', $id)
                  ->orWhere('visitante_id', $id);
            })
            ->where('status', 'finalizada')
            ->orderByDesc('data_jogo')
            ->limit(5)
            ->get()
            ->map(function($p) use ($id) {
                $isMandante = $p->mandante_id == $id;
                $adversario = $isMandante ? $p->visitante : $p->mandante;
                
                return [
                    'id' => $p->id,
                    'data' => $p->data_jogo,
                    'adversario_nome' => $adversario->nome ?? 'Desconhecido',
                    'adversario_logo' => $adversario->logo ? asset('storage/' . $adversario->logo) : null,
                    'placar_mandante' => $p->placar_mandante,
                    'placar_visitante' => $p->placar_visitante,
                    'is_mandante' => $isMandante,
                    'resultado' => $isMandante 
                        ? ($p->placar_mandante > $p->placar_visitante ? 'V' : ($p->placar_mandante == $p->placar_visitante ? 'E' : 'D'))
                        : ($p->placar_visitante > $p->placar_mandante ? 'V' : ($p->placar_visitante == $p->placar_mandante ? 'E' : 'D'))
                ];
            });
        
        return response()->json([
            'equipe' => $equipe,
            'historico_competicoes' => $historicoCompeticoes,
            'ultimos_jogos' => $ultimosJogos
        ]);
    }

    public function getAtletas(Request $request)
    {
        $perPage = (int) $request->query('per_page', 12);
        $search = $request->query('search');

        $query = Atleta::query()
            ->when($search, function ($q) use ($search) {
                $q->where('nome', 'like', "%{$search}%");
            })
            ->orderBy('nome');

        return response()->json($query->paginate($perPage));
    }

    public function getAtletaDetalhe($id)
    {
        $atleta = Atleta::with(['equipes.campeonato.modalidade', 'equipes.entidade', 'inscricoes.campeonato.modalidade'])->findOrFail($id);
        
        // Histórico de competições por EQUIPES
        $competicoesEquipes = $atleta->equipes->map(function($e) {
            return [
                'campeonato_id' => $e->campeonato->id,
                'campeonato' => $e->campeonato->nome,
                'modalidade' => $e->campeonato->modalidade->nome ?? 'N/A',
                'ano' => \Carbon\Carbon::parse($e->campeonato->data_inicio)->year,
                'equipe' => $e->entidade->nome ?? $e->nome, // Fallback se não tiver entidade
                'posicao' => $e->colocacao // Posição da equipe no campeonato
            ];
        });

        $campeonatosComEquipe = $competicoesEquipes->pluck('campeonato_id')->unique()->values();

        // Histórico de competições INDIVIDUAIS (Inscrições)
        $competicoesIndividuais = $atleta->inscricoes
            ->filter(function ($i) use ($campeonatosComEquipe) {
                if (!$i->campeonato) return false;
                if ($i->status !== 'confirmada') return false;
                return !$campeonatosComEquipe->contains($i->campeonato_id);
            })
            ->map(function($i) {
             return [
                'campeonato_id' => $i->campeonato->id,
                'campeonato' => $i->campeonato->nome,
                'modalidade' => $i->campeonato->modalidade->nome ?? 'N/A',
                'ano' => \Carbon\Carbon::parse($i->campeonato->data_inicio)->year,
                'equipe' => 'Individual',
                'posicao' => $i->colocacao // Posição do atleta
            ];
        });

        // Mesclar e ordenar
        $competicoes = $competicoesEquipes
            ->merge($competicoesIndividuais)
            ->unique('campeonato_id')
            ->sortByDesc('ano')
            ->values()
            ->map(function ($item) {
                unset($item['campeonato_id']);
                return $item;
            });

        // Estatísticas Gerais (Agregado de todos os eventos)
        $stats = DB::table('eventos_partida')
            ->where('jogador_id', $id)
            ->selectRaw("
                count(case when tipo_evento = 'GOL' then 1 end) as total_gols,
                count(case when tipo_evento = 'CARTAO_AMARELO' then 1 end) as total_amarelos,
                count(case when tipo_evento = 'CARTAO_VERMELHO' then 1 end) as total_vermelhos
            ")
            ->first();

        // Total de jogos (partidas onde o jogador teve evento ou estava na súmula - aqui simplificando para eventos ou partidas da equipe)
        // O ideal seria ter uma tabela de escalação. Como não temos, vamos usar o número de jogos da equipe onde ele estava inscrito?
        // Ou melhor, contar partidas onde ele teve evento? Não, isso é pouco.
        // Vamos contar partidas das equipes que ele participou (aproximação)
        // Ou criar a tabela 'escalacao' futuramente.
        // Por enquanto, vamos retornar null ou 0 para jogos se não tivermos certeza.
        
        return response()->json([
            'atleta' => $atleta,
            'competicoes' => $competicoes,
            'stats' => $stats
        ]);
    }
}
