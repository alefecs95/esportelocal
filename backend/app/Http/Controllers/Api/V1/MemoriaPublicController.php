<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Jobs\NotifyMemoriaCuradoresJob;
use App\Jobs\NotifyMemoriaInboxJob;
use App\Models\Campeonato;
use App\Models\MemoriaAcervo;
use App\Models\MemoriaCorrecao;
use App\Models\MemoriaEquipe;
use App\Models\MemoriaIdentificacao;
use App\Models\MemoriaLocal;
use App\Models\MemoriaMarco;
use App\Models\MemoriaPersonalidade;
use App\Models\MemoriaRecorde;
use App\Models\MemoriaTag;
use App\Models\MemoriaVotoHall;
use App\Services\TenantFileService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class MemoriaPublicController extends Controller
{
    public function __construct(
        private TenantFileService $fileService
    ) {
    }

    public function overview()
    {
        $hoje = Carbon::now();

        $nesteDia = MemoriaMarco::publicado()
            ->with(['modalidade', 'local'])
            ->whereNotNull('data_evento')
            ->whereMonth('data_evento', $hoje->month)
            ->whereDay('data_evento', $hoje->day)
            ->orderByDesc('ano')
            ->first();

        if (!$nesteDia) {
            $nesteDia = MemoriaMarco::publicado()
                ->with(['modalidade', 'local'])
                ->where('destaque', true)
                ->orderByDesc('ano')
                ->first();
        }

        return response()->json([
            'neste_dia' => $nesteDia,
            'destaques' => MemoriaMarco::publicado()
                ->with(['modalidade', 'local'])
                ->where('destaque', true)
                ->orderByDesc('ano')
                ->limit(6)
                ->get(),
            'personalidades' => MemoriaPersonalidade::publicado()
                ->with('modalidade')
                ->withCount('votosHall as votos_hall')
                ->where('destaque', true)
                ->orderByDesc('votos_hall')
                ->orderBy('nome')
                ->limit(8)
                ->get(),
            'stats' => [
                'marcos' => MemoriaMarco::publicado()->count(),
                'personalidades' => MemoriaPersonalidade::publicado()->count(),
                'acervo' => MemoriaAcervo::aprovado()->count(),
                'contribuicoes_pendentes' => 0,
                'identificar' => MemoriaAcervo::aprovado()->where('tipo', 'foto')->where('precisa_identificar', true)->count(),
                'recordes' => MemoriaRecorde::publicado()->count(),
                'depoimentos' => MemoriaAcervo::aprovado()->where('tipo', 'audio')->count(),
            ],
        ]);
    }

    public function timeline(Request $request)
    {
        $query = MemoriaMarco::publicado()
            ->with(['modalidade', 'local', 'personalidades'])
            ->orderByDesc('ano')
            ->orderByDesc('data_evento')
            ->orderBy('ordem');

        if ($request->filled('decada')) {
            $query->where('decada', (int) $request->query('decada'));
        }
        if ($request->filled('modalidade_id')) {
            $query->where('modalidade_id', $request->query('modalidade_id'));
        }
        if ($request->filled('tipo')) {
            $query->where('tipo', $request->query('tipo'));
        }
        if ($request->filled('q')) {
            $q = $request->query('q');
            $query->where(function ($builder) use ($q) {
                $builder->where('titulo', 'like', "%{$q}%")
                    ->orWhere('resumo', 'like', "%{$q}%");
            });
        }

        $marcos = $query->get();

        $pecasQuery = MemoriaAcervo::naTimeline()->with(['modalidade', 'local']);
        if ($request->filled('decada')) {
            $pecasQuery->where('decada', (int) $request->query('decada'));
        }
        if ($request->filled('modalidade_id')) {
            $pecasQuery->where('modalidade_id', $request->query('modalidade_id'));
        }
        if ($request->filled('q')) {
            $q = $request->query('q');
            $pecasQuery->where('titulo', 'like', "%{$q}%");
        }
        $pecas = $request->filled('tipo') ? collect() : $pecasQuery->get();

        $itens = $marcos->map(fn ($m) => [
            'kind' => 'marco',
            'id' => $m->id,
            'slug' => $m->slug,
            'titulo' => $m->titulo,
            'ano' => $m->ano,
            'decada' => $m->decada,
            'tipo' => $m->tipo,
            'resumo' => $m->resumo,
            'capa_url' => $m->capa_url,
            'modalidade' => $m->modalidade,
            'local' => $m->local,
        ])->concat($pecas->map(fn ($p) => [
            'kind' => 'peca',
            'id' => $p->id,
            'slug' => $p->slug,
            'titulo' => $p->titulo,
            'ano' => $p->ano,
            'decada' => $p->decada,
            'tipo' => $p->tipo,
            'resumo' => $p->descricao,
            'capa_url' => $p->tipo === 'foto' ? $p->arquivo_url : null,
            'modalidade' => $p->modalidade,
            'local' => $p->local,
        ]))->sortByDesc('ano')->values();

        $decadas = $itens->pluck('decada')->filter()->unique()->sortDesc()->values();

        return response()->json([
            'decadas' => $decadas,
            'marcos' => $marcos,
            'itens' => $itens,
        ]);
    }

    public function showMarco($slug)
    {
        $marco = MemoriaMarco::publicado()
            ->with([
                'modalidade',
                'local',
                'personalidades',
                'equipes',
                'acervo' => fn ($q) => $q->aprovado(),
                'pecas' => fn ($q) => $q->aprovado(),
            ])
            ->where(function ($query) use ($slug) {
                $query->where('slug', $slug)->orWhere('id', $slug);
            })
            ->firstOrFail();

        $viaFk = $marco->acervo;
        $viaPivot = $marco->pecas;
        $marco->setRelation('acervo', $viaFk->concat($viaPivot)->unique('id')->values());

        $relacionados = collect();
        if ($marco->decada || $marco->modalidade_id) {
            $relacionados = MemoriaMarco::publicado()
                ->with(['modalidade', 'local'])
                ->where('id', '!=', $marco->id)
                ->where(function ($q) use ($marco) {
                    if ($marco->decada) {
                        $q->orWhere('decada', $marco->decada);
                    }
                    if ($marco->modalidade_id) {
                        $q->orWhere('modalidade_id', $marco->modalidade_id);
                    }
                })
                ->orderByDesc('destaque')
                ->orderByDesc('ano')
                ->limit(4)
                ->get();
        }

        $marco->setAttribute('relacionados', $relacionados);
        $marco->increment('visualizacoes');

        return response()->json($marco);
    }

    public function galeria(Request $request)
    {
        $query = MemoriaAcervo::aprovado()
            ->with(['modalidade', 'local', 'personalidade', 'marco', 'pessoas', 'equipes', 'tags'])
            ->orderByDesc('ano')
            ->orderByDesc('created_at');

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->query('tipo'));
        }
        if ($request->filled('decada')) {
            $query->where('decada', (int) $request->query('decada'));
        }
        if ($request->filled('modalidade_id')) {
            $query->where('modalidade_id', $request->query('modalidade_id'));
        }
        if ($request->filled('local_id')) {
            $query->where('local_id', $request->query('local_id'));
        }
        if ($request->filled('marco_id')) {
            $mid = (int) $request->query('marco_id');
            $query->where(function ($builder) use ($mid) {
                $builder->where('marco_id', $mid)
                    ->orWhereHas('marcos', fn ($q) => $q->where('memoria_marcos.id', $mid));
            });
        }
        if ($request->filled('equipe_id')) {
            $query->whereHas('equipes', fn ($q) => $q->where('memoria_equipes.id', $request->query('equipe_id')));
        }
        if ($request->filled('tag') || $request->filled('tag_id')) {
            $tag = $request->query('tag_id') ?: $request->query('tag');
            $query->whereHas('tags', function ($q) use ($tag) {
                $q->where('memoria_tags.id', $tag)->orWhere('memoria_tags.slug', $tag);
            });
        }
        if ($request->filled('personalidade_id')) {
            $pid = (int) $request->query('personalidade_id');
            $query->where(function ($builder) use ($pid) {
                $builder->where('personalidade_id', $pid)
                    ->orWhereHas('pessoas', fn ($q) => $q->where('memoria_personalidades.id', $pid));
            });
        }
        if ($request->boolean('precisa_identificar')) {
            $query->where('precisa_identificar', true)->where('tipo', 'foto');
        }
        if ($request->filled('q')) {
            $q = $request->query('q');
            $query->where(function ($builder) use ($q) {
                $builder->where('titulo', 'like', "%{$q}%")
                    ->orWhere('pessoas_envolvidas', 'like', "%{$q}%")
                    ->orWhere('fonte', 'like', "%{$q}%");
            });
        }

        return response()->json($query->paginate((int) $request->query('per_page', 24)));
    }

    public function showAcervo($slug)
    {
        $item = MemoriaAcervo::aprovado()
            ->with(['modalidade', 'local', 'marco', 'personalidade', 'pessoas', 'equipes', 'marcos', 'tags'])
            ->where(function ($query) use ($slug) {
                $query->where('slug', $slug)->orWhere('id', $slug);
            })
            ->firstOrFail();

        $relacionados = collect();
        if ($item->marco_id || ($item->local_id && $item->decada)) {
            $relacionados = MemoriaAcervo::aprovado()
                ->with(['local'])
                ->where('id', '!=', $item->id)
                ->where(function ($q) use ($item) {
                    if ($item->marco_id) {
                        $q->orWhere('marco_id', $item->marco_id);
                    }
                    if ($item->local_id && $item->decada) {
                        $q->orWhere(function ($inner) use ($item) {
                            $inner->where('local_id', $item->local_id)->where('decada', $item->decada);
                        });
                    }
                })
                ->orderByDesc('ano')
                ->limit(8)
                ->get();
        }

        $item->setAttribute('relacionados', $relacionados);
        $item->increment('visualizacoes');

        return response()->json($item);
    }

    public function personalidades(Request $request)
    {
        $query = MemoriaPersonalidade::publicado()
            ->with('modalidade')
            ->withCount('votosHall as votos_hall')
            ->orderByDesc('destaque')
            ->orderByDesc('votos_hall')
            ->orderBy('nome');

        if ($request->filled('papel')) {
            $query->where('papel', $request->query('papel'));
        }
        if ($request->filled('modalidade_id')) {
            $query->where('modalidade_id', $request->query('modalidade_id'));
        }
        if ($request->filled('q')) {
            $query->where('nome', 'like', '%' . $request->query('q') . '%');
        }

        return response()->json($query->get());
    }

    public function showPersonalidade($slug)
    {
        $item = MemoriaPersonalidade::publicado()
            ->withCount('votosHall as votos_hall')
            ->with([
                'modalidade',
                'marcos' => fn ($q) => $q->publicado(),
                'equipes' => fn ($q) => $q->publicado(),
                'acervo' => fn ($q) => $q->aprovado(),
            ])
            ->where(function ($query) use ($slug) {
                $query->where('slug', $slug)->orWhere('id', $slug);
            })
            ->firstOrFail();

        $viaFk = $item->acervo()->aprovado()->with(['local'])->get();
        $viaPivot = $item->pecas()->where('memoria_acervo.status', 'aprovado')->where('memoria_acervo.publicado', true)->with(['local'])->get();
        $item->setRelation('acervo', $viaFk->concat($viaPivot)->unique('id')->values());
        $item->increment('visualizacoes');

        return response()->json($item);
    }

    public function votarHall(Request $request, $slug)
    {
        $item = MemoriaPersonalidade::publicado()
            ->where(function ($query) use ($slug) {
                $query->where('slug', $slug)->orWhere('id', $slug);
            })
            ->firstOrFail();

        $fingerprint = hash('sha256', $request->ip() . '|' . (string) $request->userAgent());
        $existe = MemoriaVotoHall::query()
            ->where('personalidade_id', $item->id)
            ->where('fingerprint', $fingerprint)
            ->exists();

        if ($existe) {
            return response()->json(['message' => 'Você já votou nesta pessoa.', 'votos_hall' => $item->votosHall()->count()], 409);
        }

        MemoriaVotoHall::create([
            'personalidade_id' => $item->id,
            'fingerprint' => $fingerprint,
        ]);

        return response()->json([
            'message' => 'Voto registrado. Obrigado por participar do Hall da cidade.',
            'votos_hall' => $item->votosHall()->count(),
        ]);
    }

    public function locais()
    {
        return response()->json(
            MemoriaLocal::publicado()
                ->orderBy('nome')
                ->get()
        );
    }

    public function mapa()
    {
        $locais = MemoriaLocal::publicado()
            ->whereNotNull('lat')
            ->whereNotNull('lng')
            ->orderBy('nome')
            ->get(['id', 'nome', 'slug', 'tipo', 'endereco', 'lat', 'lng', 'foto']);

        $marcos = MemoriaMarco::publicado()
            ->with(['local:id,nome,slug,lat,lng'])
            ->whereHas('local', fn ($q) => $q->whereNotNull('lat')->whereNotNull('lng'))
            ->orderByDesc('ano')
            ->get(['id', 'titulo', 'slug', 'ano', 'local_id']);

        return response()->json([
            'locais' => $locais,
            'marcos' => $marcos,
        ]);
    }

    public function showLocal($slug)
    {
        $local = MemoriaLocal::publicado()
            ->with([
                'momentos.acervo',
                'marcos' => fn ($q) => $q->publicado()->orderByDesc('ano'),
                'acervo' => fn ($q) => $q->aprovado()->orderByDesc('ano'),
            ])
            ->where(function ($query) use ($slug) {
                $query->where('slug', $slug)->orWhere('id', $slug);
            })
            ->firstOrFail();

        $local->increment('visualizacoes');

        return response()->json($local);
    }

    public function equipes(Request $request)
    {
        $query = MemoriaEquipe::publicado()->with('modalidade')->orderBy('nome');
        if ($request->filled('q')) {
            $query->where('nome', 'like', '%' . $request->query('q') . '%');
        }
        if ($request->filled('modalidade_id')) {
            $query->where('modalidade_id', $request->query('modalidade_id'));
        }

        return response()->json($query->get());
    }

    public function showEquipe($slug)
    {
        $item = MemoriaEquipe::publicado()
            ->with([
                'modalidade',
                'pessoas' => fn ($q) => $q->publicado(),
                'marcos' => fn ($q) => $q->publicado()->orderByDesc('ano'),
            ])
            ->where(function ($query) use ($slug) {
                $query->where('slug', $slug)->orWhere('id', $slug);
            })
            ->firstOrFail();

        $item->setRelation(
            'acervo',
            $item->acervo()->aprovado()->with(['local'])->orderByDesc('ano')->get()
        );
        $item->increment('visualizacoes');

        return response()->json($item);
    }

    public function tags()
    {
        return response()->json(MemoriaTag::query()->orderBy('nome')->get());
    }

    public function quemE()
    {
        $fotos = MemoriaAcervo::aprovado()
            ->where('tipo', 'foto')
            ->where('precisa_identificar', true)
            ->with(['local', 'modalidade'])
            ->orderByDesc('ano')
            ->get();

        $pessoas = MemoriaPersonalidade::publicado()->orderBy('nome')->get(['id', 'nome', 'slug']);

        return response()->json([
            'fotos' => $fotos,
            'personalidades' => $pessoas,
        ]);
    }

    public function identificar(Request $request, $id)
    {
        $item = MemoriaAcervo::aprovado()
            ->where('tipo', 'foto')
            ->where('precisa_identificar', true)
            ->findOrFail($id);

        $data = $request->validate([
            'nome_sugerido' => 'required|string|max:255',
            'personalidade_id' => 'nullable|exists:memoria_personalidades,id',
            'comentario' => 'nullable|string|max:1000',
            'doador_nome' => 'nullable|string|max:255',
            'doador_telefone' => 'nullable|string|max:30',
        ]);

        $fingerprint = hash('sha256', $request->ip() . '|' . (string) $request->userAgent());
        $existe = MemoriaIdentificacao::query()
            ->where('acervo_id', $item->id)
            ->where('fingerprint', $fingerprint)
            ->where('status', 'pendente')
            ->exists();

        if ($existe) {
            return response()->json(['message' => 'Você já enviou uma sugestão para esta foto. A curadoria ainda vai avaliar.'], 409);
        }

        $sugestao = MemoriaIdentificacao::create([
            'acervo_id' => $item->id,
            'personalidade_id' => $data['personalidade_id'] ?? null,
            'nome_sugerido' => $data['nome_sugerido'],
            'comentario' => $data['comentario'] ?? null,
            'doador_nome' => $data['doador_nome'] ?? null,
            'doador_telefone' => $data['doador_telefone'] ?? null,
            'fingerprint' => $fingerprint,
            'status' => 'pendente',
        ]);

        NotifyMemoriaInboxJob::dispatch(
            (int) $item->prefeitura_id,
            'Alguém reconheceu uma pessoa',
            'Sugestão: “' . $sugestao->nome_sugerido . '” na foto “' . $item->titulo . '”.',
            'identificacao'
        );

        return response()->json([
            'message' => 'Sugestão enviada. A prefeitura confere antes de publicar o nome na foto.',
            'id' => $sugestao->id,
        ], 201);
    }

    public function corrigir(Request $request, $id)
    {
        $item = MemoriaAcervo::aprovado()->findOrFail($id);
        $data = $request->validate([
            'tipo' => 'required|in:pessoa,local,data,equipe,marco,outro,o_que',
            'valor' => 'nullable|string|max:255',
            'comentario' => 'nullable|string|max:2000',
            'doador_nome' => 'nullable|string|max:255',
            'doador_telefone' => 'nullable|string|max:30',
            'local_id' => 'nullable|exists:memoria_locais,id',
            'equipe_id' => 'nullable|exists:memoria_equipes,id',
            'marco_id' => 'nullable|exists:memoria_marcos,id',
            'ano_sugerido' => 'nullable|integer|min:1900|max:' . (date('Y') + 1),
        ]);

        $fingerprint = hash('sha256', $request->ip() . '|' . (string) $request->userAgent());
        $existe = MemoriaCorrecao::query()
            ->where('acervo_id', $item->id)
            ->where('fingerprint', $fingerprint)
            ->where('tipo', $data['tipo'])
            ->where('status', 'pendente')
            ->exists();
        if ($existe) {
            return response()->json(['message' => 'Você já enviou essa correção. A curadoria ainda vai avaliar.'], 409);
        }

        $correcao = MemoriaCorrecao::create([
            'acervo_id' => $item->id,
            'tipo' => $data['tipo'],
            'valor' => $data['valor'] ?? null,
            'comentario' => $data['comentario'] ?? null,
            'doador_nome' => $data['doador_nome'] ?? null,
            'doador_telefone' => $data['doador_telefone'] ?? null,
            'local_id' => $data['local_id'] ?? null,
            'equipe_id' => $data['equipe_id'] ?? null,
            'marco_id' => $data['marco_id'] ?? null,
            'ano_sugerido' => $data['ano_sugerido'] ?? null,
            'fingerprint' => $fingerprint,
            'status' => 'pendente',
        ]);

        NotifyMemoriaInboxJob::dispatch(
            (int) $item->prefeitura_id,
            'Sugestão no acervo',
            'Correção em “' . $item->titulo . '” (' . $correcao->tipo . ').',
            'correcao'
        );

        return response()->json([
            'message' => 'Sugestão enviada. A curadoria confere antes de alterar a ficha.',
            'id' => $correcao->id,
        ], 201);
    }

    public function depoimentos()
    {
        return response()->json(
            MemoriaAcervo::aprovado()
                ->where('tipo', 'audio')
                ->with(['local', 'personalidade', 'marco'])
                ->orderByDesc('ano')
                ->get()
        );
    }

    public function recordes()
    {
        $historico = MemoriaRecorde::publicado()
            ->with(['modalidade', 'campeonato', 'personalidade', 'marco'])
            ->orderByDesc('vigente')
            ->orderByDesc('ano')
            ->get();

        $campeonatos = Campeonato::query()
            ->with('modalidade')
            ->whereIn('status', ['em_andamento', 'finalizado'])
            ->orderByDesc('data_inicio')
            ->limit(12)
            ->get();

        $temporada = [];
        foreach ($campeonatos as $campeonato) {
            $artilheiro = DB::table('eventos_partida')
                ->join('partidas', 'eventos_partida.partida_id', '=', 'partidas.id')
                ->join('fases', 'partidas.fase_id', '=', 'fases.id')
                ->join('atletas', 'eventos_partida.jogador_id', '=', 'atletas.id')
                ->join('equipes', 'eventos_partida.time_id', '=', 'equipes.id')
                ->where('fases.campeonato_id', $campeonato->id)
                ->where('eventos_partida.tipo_evento', 'GOL')
                ->select(
                    'atletas.id',
                    'atletas.nome',
                    'equipes.nome as equipe_nome',
                    DB::raw('count(*) as gols')
                )
                ->groupBy('atletas.id', 'atletas.nome', 'equipes.nome')
                ->orderByDesc('gols')
                ->first();

            if (!$artilheiro) {
                continue;
            }

            $temporada[] = [
                'campeonato_id' => $campeonato->id,
                'campeonato' => $campeonato->nome,
                'status' => $campeonato->status,
                'modalidade' => $campeonato->modalidade?->nome,
                'categoria' => 'artilharia',
                'titulo' => 'Artilharia — ' . $campeonato->nome,
                'atleta_nome' => $artilheiro->nome,
                'valor' => (string) $artilheiro->gols,
                'unidade' => 'gols',
                'equipe_nome' => $artilheiro->equipe_nome,
            ];
        }

        return response()->json([
            'historico' => $historico,
            'temporada' => $temporada,
        ]);
    }

    public function contribuir(Request $request)
    {
        $request->validate([
            'arquivo' => 'required|file|max:20480|mimetypes:image/jpeg,image/png,image/webp,image/gif,video/mp4,video/webm,audio/mpeg,audio/mp4,audio/ogg,audio/webm,audio/wav,application/pdf',
            'titulo' => 'required|string|max:255',
            'descricao' => 'nullable|string|max:2000',
            'historia' => 'nullable|string|max:5000',
            'tipo' => 'nullable|in:foto,video,documento,audio',
            'data_aproximada' => 'nullable|date',
            'ano' => 'nullable|integer|min:1900|max:' . (date('Y') + 1),
            'local_id' => 'nullable|exists:memoria_locais,id',
            'modalidade_id' => 'nullable|exists:modalidades,id',
            'pessoas_envolvidas' => 'nullable|string|max:255',
            'fonte' => 'nullable|string|max:255',
            'doacao' => 'nullable|string|max:255',
            'alt_text' => 'nullable|string|max:255',
            'doador_nome' => 'required|string|max:255',
            'doador_email' => 'nullable|email|max:255',
            'doador_telefone' => 'nullable|string|max:30',
        ]);

        $file = $request->file('arquivo');
        $mime = (string) $file->getMimeType();
        $tipo = $request->input('tipo');
        if (!$tipo) {
            $tipo = str_starts_with($mime, 'video/')
                ? 'video'
                : (str_starts_with($mime, 'audio/') ? 'audio' : (str_starts_with($mime, 'image/') ? 'foto' : 'documento'));
        }

        $path = $this->fileService->storeFile($file, 'memoria/contribuicoes');

        $item = MemoriaAcervo::create([
            'tipo' => $tipo,
            'titulo' => $request->input('titulo'),
            'alt_text' => $request->input('alt_text') ?: $request->input('titulo'),
            'descricao' => $request->input('descricao'),
            'historia' => $request->input('historia'),
            'arquivo' => $path,
            'mime' => $mime,
            'tamanho' => $file->getSize(),
            'hash_arquivo' => MemoriaAcervo::hashFromUpload($file),
            'data_aproximada' => $request->input('data_aproximada'),
            'ano' => $request->input('ano'),
            'local_id' => $request->input('local_id'),
            'modalidade_id' => $request->input('modalidade_id'),
            'pessoas_envolvidas' => $request->input('pessoas_envolvidas'),
            'fonte' => null,
            'doacao' => $request->input('doacao') ?: $request->input('fonte'),
            'origem' => 'comunidade',
            'doador_nome' => $request->input('doador_nome'),
            'doador_email' => $request->input('doador_email'),
            'doador_telefone' => $request->input('doador_telefone'),
            'status' => 'pendente',
            'publicado' => false,
        ]);

        NotifyMemoriaCuradoresJob::dispatch($item->id, (int) $item->prefeitura_id);

        return response()->json([
            'message' => 'Contribuição enviada. A curadoria da prefeitura vai avaliar antes de publicar.',
            'id' => $item->id,
        ], 201);
    }
}
