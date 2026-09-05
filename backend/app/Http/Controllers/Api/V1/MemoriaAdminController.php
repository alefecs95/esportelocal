<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\MemoriaAcervo;
use App\Models\MemoriaAuditoria;
use App\Models\MemoriaCorrecao;
use App\Models\MemoriaEquipe;
use App\Models\MemoriaIdentificacao;
use App\Models\MemoriaLocal;
use App\Models\MemoriaLocalMomento;
use App\Models\MemoriaMarco;
use App\Models\MemoriaNotificacao;
use App\Models\MemoriaOpcao;
use App\Models\MemoriaPersonalidade;
use App\Models\MemoriaRecorde;
use App\Models\MemoriaTag;
use App\Services\MemoriaCurationService;
use App\Services\TenantFileService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

class MemoriaAdminController extends Controller
{
    public function __construct(
        private TenantFileService $fileService,
        private MemoriaCurationService $curation
    ) {
    }

    public function dashboard()
    {
        $this->authorizeCuradoria(request());

        return response()->json([
            'marcos' => MemoriaMarco::count(),
            'marcos_publicados' => MemoriaMarco::where('publicado', true)->count(),
            'marcos_rascunho' => MemoriaMarco::where('publicado', false)->count(),
            'personalidades' => MemoriaPersonalidade::count(),
            'personalidades_publicadas' => MemoriaPersonalidade::where('publicado', true)->count(),
            'personalidades_rascunho' => MemoriaPersonalidade::where('publicado', false)->count(),
            'acervo' => MemoriaAcervo::aprovado()->count(),
            'pendentes' => MemoriaAcervo::naFila()->count(),
            'complemento' => MemoriaAcervo::where('status', 'complemento')->count(),
            'orfaos' => MemoriaAcervo::aprovado()->orfao()->count(),
            'sem_alt' => MemoriaAcervo::aprovado()->where('tipo', 'foto')->where(function ($q) {
                $q->whereNull('alt_text')->orWhere('alt_text', '');
            })->count(),
            'sem_credito' => MemoriaAcervo::aprovado()->where(function ($q) {
                $q->where(function ($q2) {
                    $q2->whereNull('credito')->orWhere('credito', '');
                })->where(function ($q2) {
                    $q2->whereNull('fonte')->orWhere('fonte', '');
                });
            })->count(),
            'sem_data' => MemoriaAcervo::aprovado()->whereNull('ano')->where('data_incerta', false)->count(),
            'locais' => MemoriaLocal::count(),
            'equipes' => MemoriaEquipe::count(),
            'identificacoes' => MemoriaIdentificacao::where('status', 'pendente')->count(),
            'correcoes' => MemoriaCorrecao::where('status', 'pendente')->count(),
            'recordes' => MemoriaRecorde::count(),
            'duplicados' => MemoriaAcervo::query()
                ->whereNotNull('hash_arquivo')
                ->select('hash_arquivo')
                ->groupBy('hash_arquivo')
                ->havingRaw('COUNT(*) > 1')
                ->get()
                ->count(),
            'visualizacoes' => (int) MemoriaAcervo::aprovado()->sum('visualizacoes'),
        ]);
    }

    public function opcoes()
    {
        $this->authorizeCuradoria(request());

        return response()->json([
            'local_tipo' => $this->collectOpcoes('local_tipo', MemoriaLocal::class, 'tipo', ['ginasio', 'estadio', 'quadra', 'pista', 'outro']),
            'marco_tipo' => $this->collectOpcoes('marco_tipo', MemoriaMarco::class, 'tipo', ['conquista', 'campeonato', 'inauguracao', 'atleta', 'marco']),
            'papel' => $this->collectOpcoes('papel', MemoriaPersonalidade::class, 'papel', ['atleta', 'tecnico', 'dirigente', 'outro']),
        ]);
    }

    public function storeOpcao(Request $request)
    {
        $this->authorizeCuradoria($request);
        $data = $request->validate([
            'grupo' => 'required|in:local_tipo,marco_tipo,papel',
            'valor' => 'required|string|max:50',
        ]);
        $valor = trim($data['valor']);
        MemoriaOpcao::firstOrCreate([
            'grupo' => $data['grupo'],
            'valor' => $valor,
        ]);

        return response()->json(['grupo' => $data['grupo'], 'valor' => $valor], 201);
    }

    public function locais()
    {
        $this->authorizeCuradoria(request());
        return response()->json(MemoriaLocal::with('momentos')->orderBy('nome')->get());
    }

    public function storeLocal(Request $request)
    {
        $this->authorizeCuradoria($request);
        $data = $this->validateLocal($request);
        unset($data['foto'], $data['foto_antes'], $data['foto_depois']);
        $data['publicado'] = filter_var($request->input('publicado', true), FILTER_VALIDATE_BOOLEAN);
        $local = MemoriaLocal::create($data);
        $this->attachLocalMedia($request, $local);
        $local->save();
        $this->rememberOpcao('local_tipo', $local->tipo);
        return response()->json($local, 201);
    }

    public function updateLocal(Request $request, $id)
    {
        $this->authorizeCuradoria($request);
        $local = MemoriaLocal::findOrFail($id);
        $data = $this->validateLocal($request);
        unset($data['foto'], $data['foto_antes'], $data['foto_depois']);
        $data['publicado'] = filter_var($request->input('publicado', true), FILTER_VALIDATE_BOOLEAN);
        $local->fill($data);
        $this->attachLocalMedia($request, $local);
        $local->save();
        $this->rememberOpcao('local_tipo', $local->tipo);
        return response()->json($local);
    }

    public function destroyLocal($id)
    {
        $this->authorizeCuradoria(request());
        MemoriaLocal::findOrFail($id)->delete();
        return response()->json(['message' => 'Local removido.']);
    }

    public function storeMomento(Request $request, $id)
    {
        $this->authorizeCuradoria($request);
        $local = MemoriaLocal::findOrFail($id);
        $data = $request->validate([
            'ano' => 'nullable|integer|min:1900|max:' . (date('Y') + 1),
            'titulo' => 'nullable|string|max:255',
            'descricao' => 'nullable|string',
            'acervo_id' => 'nullable|exists:memoria_acervo,id',
            'foto' => 'nullable|file|image|max:20480',
        ]);
        unset($data['foto']);
        $data['local_id'] = $local->id;
        $data['ordem'] = (int) $local->momentos()->max('ordem') + 1;
        $item = MemoriaLocalMomento::create($data);
        if ($request->hasFile('foto')) {
            $item->foto = $this->fileService->storeFile($request->file('foto'), 'memoria/locais/momentos');
            $item->save();
        }
        $this->curation->log($request->user(), 'criou', 'local_momento', $item->id, $item->titulo ?: $local->nome);

        return response()->json($item->load('acervo'), 201);
    }

    public function destroyMomento($id, $momentoId)
    {
        $this->authorizeCuradoria(request());
        $item = MemoriaLocalMomento::where('local_id', $id)->findOrFail($momentoId);
        if ($item->foto) {
            $this->fileService->deleteFile($item->foto);
        }
        $item->delete();

        return response()->json(['message' => 'Momento removido.']);
    }

    public function equipes()
    {
        $this->authorizeCuradoria(request());
        return response()->json(MemoriaEquipe::with('modalidade')->orderBy('nome')->get());
    }

    public function showEquipe($id)
    {
        $this->authorizeCuradoria(request());
        return response()->json(MemoriaEquipe::with(['modalidade', 'pessoas', 'marcos'])->findOrFail($id));
    }

    public function storeEquipe(Request $request)
    {
        $this->authorizeCuradoria($request);
        $item = MemoriaEquipe::create($this->validateEquipe($request));
        $this->attachEscudo($request, $item);
        $item->save();
        $this->syncEquipePessoas($item, $request);
        $this->curation->log($request->user(), 'criou', 'equipe', $item->id, $item->nome);
        return response()->json($item->load(['modalidade', 'pessoas']), 201);
    }

    public function updateEquipe(Request $request, $id)
    {
        $this->authorizeCuradoria($request);
        $item = MemoriaEquipe::findOrFail($id);
        $item->fill($this->validateEquipe($request));
        $this->attachEscudo($request, $item);
        $item->save();
        $this->syncEquipePessoas($item, $request);
        $this->curation->log($request->user(), 'editou', 'equipe', $item->id, $item->nome);
        return response()->json($item->load(['modalidade', 'pessoas']));
    }

    public function destroyEquipe($id)
    {
        $this->authorizeCuradoria(request());
        MemoriaEquipe::findOrFail($id)->delete();
        return response()->json(['message' => 'Equipe histórica removida.']);
    }

    public function tags()
    {
        $this->authorizeCuradoria(request());
        return response()->json(MemoriaTag::orderBy('nome')->get());
    }

    public function storeTag(Request $request)
    {
        $this->authorizeCuradoria($request);
        $data = $request->validate(['nome' => 'required|string|max:80']);
        $tag = MemoriaTag::query()->where('nome', $data['nome'])->first();
        if ($tag) {
            return response()->json($tag);
        }

        return response()->json(MemoriaTag::create($data), 201);
    }

    public function personalidades(Request $request)
    {
        $this->authorizeCuradoria($request);
        $query = MemoriaPersonalidade::with('modalidade')->orderBy('nome');
        if ($request->filled('q')) {
            $query->where('nome', 'like', '%' . $request->query('q') . '%');
        }
        return response()->json($query->get());
    }

    public function showPersonalidade($id)
    {
        $this->authorizeCuradoria(request());
        return response()->json(
            MemoriaPersonalidade::with(['modalidade', 'marcos', 'equipes'])->findOrFail($id)
        );
    }

    public function storePersonalidade(Request $request)
    {
        $this->authorizeCuradoria($request);
        $item = MemoriaPersonalidade::create($this->validatePersonalidade($request) + [
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);
        $this->attachFoto($request, $item, 'memoria/personalidades');
        $item->save();
        $this->rememberOpcao('papel', $item->papel);
        $this->curation->log($request->user(), 'criou', 'personalidade', $item->id, $item->nome);
        return response()->json($item->load('modalidade'), 201);
    }

    public function updatePersonalidade(Request $request, $id)
    {
        $this->authorizeCuradoria($request);
        $item = MemoriaPersonalidade::findOrFail($id);
        $item->fill($this->validatePersonalidade($request));
        $item->updated_by = $request->user()->id;
        $this->attachFoto($request, $item, 'memoria/personalidades');
        $item->save();
        $this->rememberOpcao('papel', $item->papel);
        $this->curation->log($request->user(), 'editou', 'personalidade', $item->id, $item->nome);
        return response()->json($item->load('modalidade'));
    }

    public function destroyPersonalidade($id)
    {
        $this->authorizeCuradoria(request());
        MemoriaPersonalidade::findOrFail($id)->delete();
        return response()->json(['message' => 'Personalidade removida.']);
    }

    public function marcos(Request $request)
    {
        $this->authorizeCuradoria($request);
        $query = MemoriaMarco::with(['modalidade', 'local', 'personalidades', 'equipes'])
            ->orderByDesc('ano')
            ->orderByDesc('data_evento');
        if ($request->filled('q')) {
            $query->where('titulo', 'like', '%' . $request->query('q') . '%');
        }
        return response()->json($query->get());
    }

    public function showMarco($id)
    {
        $this->authorizeCuradoria(request());
        return response()->json(
            MemoriaMarco::with(['modalidade', 'local', 'personalidades', 'equipes', 'acervo'])->findOrFail($id)
        );
    }

    public function storeMarco(Request $request)
    {
        $this->authorizeCuradoria($request);
        $data = $this->validateMarco($request);
        $ids = $this->parseIds($request->input('personalidade_ids'));
        unset($data['personalidade_ids']);
        $item = MemoriaMarco::create($data + [
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);
        $this->attachCapa($request, $item);
        $item->save();
        $item->personalidades()->sync($ids);
        $item->equipes()->sync($this->parseIds($request->input('equipe_ids')));
        $this->rememberOpcao('marco_tipo', $item->tipo);
        $this->curation->log($request->user(), 'criou', 'marco', $item->id, $item->titulo);
        return response()->json($item->load(['modalidade', 'local', 'personalidades']), 201);
    }

    public function updateMarco(Request $request, $id)
    {
        $this->authorizeCuradoria($request);
        $item = MemoriaMarco::findOrFail($id);
        $data = $this->validateMarco($request);
        $ids = $this->parseIds($request->input('personalidade_ids'));
        unset($data['personalidade_ids']);
        $item->fill($data);
        $item->updated_by = $request->user()->id;
        $this->attachCapa($request, $item);
        $item->save();
        $item->personalidades()->sync($ids);
        $item->equipes()->sync($this->parseIds($request->input('equipe_ids')));
        $this->rememberOpcao('marco_tipo', $item->tipo);
        $this->curation->log($request->user(), 'editou', 'marco', $item->id, $item->titulo);
        return response()->json($item->load(['modalidade', 'local', 'personalidades']));
    }

    public function destroyMarco($id)
    {
        $this->authorizeCuradoria(request());
        MemoriaMarco::findOrFail($id)->delete();
        return response()->json(['message' => 'Marco removido.']);
    }

    public function acervo(Request $request)
    {
        $this->authorizeCuradoria($request);
        $query = MemoriaAcervo::with(['modalidade', 'local', 'marco', 'personalidade', 'pessoas'])
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            if ($request->query('status') === 'fila') {
                $query->naFila();
            } else {
                $query->where('status', $request->query('status'));
            }
        } else {
            $query->whereNotIn('status', ['pendente', 'complemento']);
        }
        if ($request->boolean('orfaos')) {
            $query->orfao();
        }
        if ($request->filled('q')) {
            $q = $request->query('q');
            $query->where(function ($builder) use ($q) {
                $builder->where('titulo', 'like', "%{$q}%")
                    ->orWhere('doador_nome', 'like', "%{$q}%");
            });
        }

        $items = $query->paginate((int) $request->query('per_page', 20));
        $items->getCollection()->transform(function ($item) {
            return $item->makeVisible(['doador_email', 'doador_telefone']);
        });

        return response()->json($items);
    }

    public function showAcervo($id)
    {
        $this->authorizeCuradoria(request());
        $item = MemoriaAcervo::with(['modalidade', 'local', 'marco', 'personalidade', 'pessoas', 'equipes', 'marcos', 'tags'])->findOrFail($id);
        return response()->json($item->makeVisible(['doador_email', 'doador_telefone']));
    }

    public function storeAcervo(Request $request)
    {
        $this->authorizeCuradoria($request);
        $request->validate($this->acervoRules(true));
        $file = $request->file('arquivo');
        $path = $this->fileService->storeFile($file, 'memoria/acervo');

        $item = MemoriaAcervo::create(array_merge($this->acervoPayload($request, $file, $path), [
            'origem' => 'curadoria',
            'status' => 'aprovado',
            'publicado' => filter_var($request->input('publicado', true), FILTER_VALIDATE_BOOLEAN),
        ]));
        $this->syncAcervoPessoas($item, $request);
        $this->syncAcervoLinks($item, $request);
        $this->curation->log($request->user(), 'criou', 'acervo', $item->id, $item->titulo);

        $payload = $item->load(['modalidade', 'local', 'marco', 'personalidade', 'pessoas', 'equipes', 'tags']);
        $dup = $item->duplicata();

        return response()->json($payload->setAttribute('duplicata', $dup?->only(['id', 'titulo', 'slug'])), 201);
    }

    public function updateAcervo(Request $request, $id)
    {
        $this->authorizeCuradoria($request);
        $item = MemoriaAcervo::findOrFail($id);
        $request->validate($this->acervoRules(false));

        $payload = $this->acervoPayload($request);
        unset($payload['arquivo'], $payload['mime'], $payload['tamanho'], $payload['tipo']);

        if ($request->filled('tipo')) {
            $payload['tipo'] = $request->input('tipo');
        }
        if ($request->hasFile('arquivo')) {
            $file = $request->file('arquivo');
            if ($item->arquivo) {
                $this->fileService->deleteFile($item->arquivo);
            }
            $payload['arquivo'] = $this->fileService->storeFile($file, 'memoria/acervo');
            $payload['mime'] = $file->getMimeType();
            $payload['tamanho'] = $file->getSize();
            $payload['hash_arquivo'] = MemoriaAcervo::hashFromUpload($file);
        }

        $item->fill($payload);
        $item->save();
        $this->syncAcervoPessoas($item, $request);
        $this->syncAcervoLinks($item, $request);
        $this->curation->log($request->user(), 'editou', 'acervo', $item->id, $item->titulo);
        return response()->json($item->load(['modalidade', 'local', 'marco', 'personalidade', 'pessoas', 'equipes', 'tags']));
    }

    public function destroyAcervo($id)
    {
        $this->authorizeCuradoria(request());
        MemoriaAcervo::findOrFail($id)->delete();
        return response()->json(['message' => 'Item do acervo removido.']);
    }

    public function aprovar(Request $request, $id)
    {
        $this->authorizeCuradoria($request);
        $item = MemoriaAcervo::findOrFail($id);
        $item->status = 'aprovado';
        $item->publicado = true;
        $item->motivo_rejeicao = null;
        $item->revisado_por = $request->user()->id;
        $item->revisado_em = now();
        $item->publicado_em = $item->publicado_em ?: now();

        if ($request->filled('marco_id')) {
            $item->marco_id = $request->input('marco_id');
        }
        if ($request->filled('personalidade_id')) {
            $item->personalidade_id = $request->input('personalidade_id');
        }

        $item->save();
        $this->syncAcervoPessoas($item, $request);
        $this->curation->log($request->user(), 'aprovou', 'acervo', $item->id, $item->titulo);
        return response()->json($item->load(['pessoas']));
    }

    public function rejeitar(Request $request, $id)
    {
        $this->authorizeCuradoria($request);
        $request->validate(['motivo_rejeicao' => 'nullable|string|max:1000']);
        $item = MemoriaAcervo::findOrFail($id);
        $item->status = 'rejeitado';
        $item->publicado = false;
        $item->motivo_rejeicao = $request->input('motivo_rejeicao');
        $item->revisado_por = $request->user()->id;
        $item->revisado_em = now();
        $item->save();
        $this->curation->log($request->user(), 'rejeitou', 'acervo', $item->id, $item->titulo);
        return response()->json($item);
    }

    public function pedirComplemento(Request $request, $id)
    {
        $this->authorizeCuradoria($request);
        $request->validate(['motivo_rejeicao' => 'nullable|string|max:1000']);
        $item = MemoriaAcervo::findOrFail($id);
        $item->status = 'complemento';
        $item->publicado = false;
        $item->motivo_rejeicao = $request->input('motivo_rejeicao');
        $item->revisado_por = $request->user()->id;
        $item->revisado_em = now();
        $item->save();
        $this->curation->log($request->user(), 'complemento', 'acervo', $item->id, $item->titulo);

        return response()->json($item);
    }

    public function lote(Request $request)
    {
        $this->authorizeCuradoria($request);
        $data = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer',
            'acao' => 'required|in:aprovar,rejeitar,complemento',
            'motivo_rejeicao' => 'nullable|string|max:1000',
        ]);

        $items = MemoriaAcervo::whereIn('id', $data['ids'])->naFila()->get();
        foreach ($items as $item) {
            if ($data['acao'] === 'aprovar') {
                $item->status = 'aprovado';
                $item->publicado = true;
                $item->motivo_rejeicao = null;
                $item->revisado_por = $request->user()->id;
                $item->revisado_em = now();
                $item->publicado_em = $item->publicado_em ?: now();
                $item->save();
            } elseif ($data['acao'] === 'complemento') {
                $item->status = 'complemento';
                $item->publicado = false;
                $item->motivo_rejeicao = $data['motivo_rejeicao'] ?? $item->motivo_rejeicao;
                $item->revisado_por = $request->user()->id;
                $item->revisado_em = now();
                $item->save();
            } else {
                $item->status = 'rejeitado';
                $item->publicado = false;
                $item->motivo_rejeicao = $data['motivo_rejeicao'] ?? null;
                $item->revisado_por = $request->user()->id;
                $item->revisado_em = now();
                $item->save();
            }
        }

        $this->curation->log($request->user(), 'lote_' . $data['acao'], 'acervo', null, count($items) . ' itens');
        return response()->json(['atualizados' => $items->count()]);
    }

    public function duplicarMarco(Request $request, $id)
    {
        $this->authorizeCuradoria($request);
        $orig = MemoriaMarco::with('personalidades')->findOrFail($id);
        $copy = $orig->replicate(['slug']);
        $copy->titulo = $orig->titulo . ' (cópia)';
        $copy->slug = null;
        $copy->publicado = false;
        $copy->destaque = false;
        $copy->created_by = $request->user()->id;
        $copy->updated_by = $request->user()->id;
        $copy->save();
        $copy->personalidades()->sync($orig->personalidades->pluck('id'));
        $this->curation->log($request->user(), 'duplicou', 'marco', $copy->id, $copy->titulo);
        return response()->json($copy, 201);
    }

    public function duplicarPersonalidade(Request $request, $id)
    {
        $this->authorizeCuradoria($request);
        $orig = MemoriaPersonalidade::findOrFail($id);
        $copy = $orig->replicate(['slug']);
        $copy->nome = $orig->nome . ' (cópia)';
        $copy->slug = null;
        $copy->publicado = false;
        $copy->destaque = false;
        $copy->created_by = $request->user()->id;
        $copy->updated_by = $request->user()->id;
        $copy->save();
        $this->curation->log($request->user(), 'duplicou', 'personalidade', $copy->id, $copy->nome);
        return response()->json($copy, 201);
    }

    public function identificacoes(Request $request)
    {
        $this->authorizeCuradoria($request);
        $query = MemoriaIdentificacao::with(['acervo', 'personalidade'])
            ->orderByDesc('id');
        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        } else {
            $query->where('status', 'pendente');
        }

        return response()->json($query->limit(80)->get());
    }

    public function aprovarIdentificacao(Request $request, $id)
    {
        $this->authorizeCuradoria($request);
        $sugestao = MemoriaIdentificacao::with('acervo')->findOrFail($id);
        $data = $request->validate([
            'personalidade_id' => 'nullable|exists:memoria_personalidades,id',
            'fechar' => 'nullable',
        ]);

        $acervo = $sugestao->acervo;
        $nome = $sugestao->nome_sugerido;
        $atuais = array_filter(array_map('trim', explode(',', (string) $acervo->pessoas_envolvidas)));
        if ($nome && !in_array($nome, $atuais, true)) {
            $atuais[] = $nome;
            $acervo->pessoas_envolvidas = implode(', ', $atuais);
        }

        $personalidadeId = $data['personalidade_id'] ?? $sugestao->personalidade_id;
        if ($personalidadeId && !$acervo->personalidade_id) {
            $acervo->personalidade_id = $personalidadeId;
        }
        if ($personalidadeId) {
            $acervo->pessoas()->syncWithoutDetaching([
                $personalidadeId => ['papel' => 'outro', 'ordem' => 99],
            ]);
        }
        if (filter_var($request->input('fechar', false), FILTER_VALIDATE_BOOLEAN)) {
            $acervo->precisa_identificar = false;
        }
        $acervo->save();

        $sugestao->status = 'aprovado';
        $sugestao->personalidade_id = $personalidadeId;
        $sugestao->revisado_por = $request->user()->id;
        $sugestao->revisado_em = now();
        $sugestao->save();

        $this->curation->log($request->user(), 'aprovou', 'identificacao', $sugestao->id, $nome);

        return response()->json($sugestao->load(['acervo', 'personalidade']));
    }

    public function rejeitarIdentificacao(Request $request, $id)
    {
        $this->authorizeCuradoria($request);
        $request->validate(['motivo_rejeicao' => 'nullable|string|max:1000']);
        $sugestao = MemoriaIdentificacao::findOrFail($id);
        $sugestao->status = 'rejeitado';
        $sugestao->motivo_rejeicao = $request->input('motivo_rejeicao');
        $sugestao->revisado_por = $request->user()->id;
        $sugestao->revisado_em = now();
        $sugestao->save();
        $this->curation->log($request->user(), 'rejeitou', 'identificacao', $sugestao->id, $sugestao->nome_sugerido);

        return response()->json($sugestao);
    }

    public function recordes()
    {
        $this->authorizeCuradoria(request());
        return response()->json(
            MemoriaRecorde::with(['modalidade', 'campeonato', 'personalidade', 'marco'])
                ->orderByDesc('ano')
                ->get()
        );
    }

    public function storeRecorde(Request $request)
    {
        $this->authorizeCuradoria($request);
        $item = MemoriaRecorde::create($this->validateRecorde($request));
        $this->curation->log($request->user(), 'criou', 'recorde', $item->id, $item->titulo);
        return response()->json($item->load(['modalidade', 'campeonato', 'personalidade', 'marco']), 201);
    }

    public function updateRecorde(Request $request, $id)
    {
        $this->authorizeCuradoria($request);
        $item = MemoriaRecorde::findOrFail($id);
        $item->fill($this->validateRecorde($request));
        $item->save();
        $this->curation->log($request->user(), 'editou', 'recorde', $item->id, $item->titulo);
        return response()->json($item->load(['modalidade', 'campeonato', 'personalidade', 'marco']));
    }

    public function destroyRecorde($id)
    {
        $this->authorizeCuradoria(request());
        MemoriaRecorde::findOrFail($id)->delete();
        return response()->json(['message' => 'Recorde removido.']);
    }

    public function correcoes(Request $request)
    {
        $this->authorizeCuradoria($request);
        $query = MemoriaCorrecao::with(['acervo', 'local', 'equipe', 'marco'])->orderByDesc('id');
        $query->where('status', $request->query('status', 'pendente'));
        return response()->json($query->limit(80)->get()->each->makeVisible(['doador_telefone']));
    }

    public function aprovarCorrecao(Request $request, $id)
    {
        $this->authorizeCuradoria($request);
        $item = MemoriaCorrecao::with('acervo')->findOrFail($id);
        $acervo = $item->acervo;
        if ($item->tipo === 'local' && $item->local_id) {
            $acervo->local_id = $item->local_id;
        }
        if ($item->tipo === 'data' && $item->ano_sugerido) {
            $acervo->ano = $item->ano_sugerido;
            $acervo->data_incerta = false;
        }
        if ($item->tipo === 'equipe' && $item->equipe_id) {
            $acervo->equipes()->syncWithoutDetaching([$item->equipe_id]);
        }
        if ($item->tipo === 'marco' && $item->marco_id) {
            $acervo->marcos()->syncWithoutDetaching([$item->marco_id]);
            if (!$acervo->marco_id) {
                $acervo->marco_id = $item->marco_id;
            }
        }
        if (in_array($item->tipo, ['outro', 'o_que', 'pessoa'], true) && $item->valor) {
            $acervo->descricao = trim(($acervo->descricao ? $acervo->descricao . "\n" : '') . $item->valor);
        }
        $acervo->save();
        $item->status = 'aprovado';
        $item->revisado_por = $request->user()->id;
        $item->revisado_em = now();
        $item->save();
        $this->curation->log($request->user(), 'aprovou', 'correcao', $item->id, $item->tipo);
        return response()->json($item->load('acervo'));
    }

    public function rejeitarCorrecao(Request $request, $id)
    {
        $this->authorizeCuradoria($request);
        $item = MemoriaCorrecao::findOrFail($id);
        $item->status = 'rejeitado';
        $item->motivo_rejeicao = $request->input('motivo_rejeicao');
        $item->revisado_por = $request->user()->id;
        $item->revisado_em = now();
        $item->save();
        return response()->json($item);
    }

    public function auditoria(Request $request)
    {
        $this->authorizeCuradoria($request);
        return response()->json(
            MemoriaAuditoria::with('user:id,name,email')
                ->orderByDesc('id')
                ->limit(50)
                ->get()
        );
    }

    public function notificacoes(Request $request)
    {
        $this->authorizeCuradoria($request);
        $items = MemoriaNotificacao::where('user_id', $request->user()->id)
            ->orderByDesc('id')
            ->limit(20)
            ->get();
        return response()->json([
            'nao_lidas' => $items->where('lida', false)->count(),
            'items' => $items,
        ]);
    }

    public function lerNotificacoes(Request $request)
    {
        $this->authorizeCuradoria($request);
        MemoriaNotificacao::where('user_id', $request->user()->id)
            ->where('lida', false)
            ->update(['lida' => true, 'lida_em' => now()]);
        return response()->json(['ok' => true]);
    }

    private function authorizeCuradoria(Request $request): void
    {
        $user = $request->user();
        if (!$user || !in_array($user->perfil, ['ADMIN', 'SUPER_ADMIN', 'REDATOR'], true)) {
            abort(403, 'Sem permissão para curadoria da Memória.');
        }
    }

    private function validateLocal(Request $request): array
    {
        return $request->validate([
            'nome' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'tipo' => 'nullable|string|max:50',
            'endereco' => 'nullable|string|max:255',
            'lat' => 'nullable|numeric|between:-90,90',
            'lng' => 'nullable|numeric|between:-180,180',
            'descricao' => 'nullable|string',
            'ano_antes' => 'nullable|integer|min:1900|max:' . (date('Y') + 1),
            'ano_depois' => 'nullable|integer|min:1900|max:' . (date('Y') + 1),
            'publicado' => 'nullable|boolean',
            'foto' => 'nullable|file|image|max:20480',
            'foto_antes' => 'nullable|file|image|max:20480',
            'foto_depois' => 'nullable|file|image|max:20480',
        ]);
    }

    private function validatePersonalidade(Request $request): array
    {
        $data = $request->validate([
            'nome' => 'required|string|max:255',
            'apelido' => 'nullable|string|max:120',
            'slug' => 'nullable|string|max:255',
            'papel' => 'nullable|string|max:50',
            'mini_bio' => 'nullable|string',
            'data_nascimento' => 'nullable|date',
            'data_falecimento' => 'nullable|date',
            'modalidade_id' => 'nullable|exists:modalidades,id',
            'atleta_id' => 'nullable|exists:atletas,id',
            'destaque' => 'nullable',
            'publicado' => 'nullable',
            'foto' => 'nullable|file|image|max:20480',
        ]);
        $data['destaque'] = filter_var($request->input('destaque'), FILTER_VALIDATE_BOOLEAN);
        $data['publicado'] = filter_var($request->input('publicado', true), FILTER_VALIDATE_BOOLEAN);
        unset($data['foto']);
        return $data;
    }

    private function validateMarco(Request $request): array
    {
        $data = $request->validate([
            'titulo' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'resumo' => 'nullable|string|max:500',
            'conteudo' => 'nullable|string',
            'data_evento' => 'nullable|date',
            'ano' => 'nullable|integer|min:1900|max:' . (date('Y') + 1),
            'tipo' => 'nullable|string|max:50',
            'modalidade_id' => 'nullable|exists:modalidades,id',
            'local_id' => 'nullable|exists:memoria_locais,id',
            'destaque' => 'nullable',
            'publicado' => 'nullable',
            'ordem' => 'nullable|integer',
            'personalidade_ids' => 'nullable',
            'equipe_ids' => 'nullable',
            'capa' => 'nullable|file|image|max:20480',
        ]);
        $data['destaque'] = filter_var($request->input('destaque'), FILTER_VALIDATE_BOOLEAN);
        $data['publicado'] = filter_var($request->input('publicado', true), FILTER_VALIDATE_BOOLEAN);
        unset($data['capa']);
        return $data;
    }

    private function acervoRules(bool $fileRequired): array
    {
        return [
            'arquivo' => ($fileRequired ? 'required' : 'nullable') . '|file|max:51200|mimetypes:image/jpeg,image/png,image/webp,image/gif,video/mp4,video/webm,audio/mpeg,audio/mp4,audio/ogg,audio/webm,audio/wav,application/pdf',
            'titulo' => 'required|string|max:255',
            'alt_text' => 'nullable|string|max:255',
            'descricao' => 'nullable|string',
            'historia' => 'nullable|string',
            'transcricao' => 'nullable|string',
            'tipo' => 'nullable|in:foto,video,documento,audio',
            'data_aproximada' => 'nullable|date',
            'ano' => 'nullable|integer|min:1900|max:' . (date('Y') + 1),
            'local_id' => 'nullable|exists:memoria_locais,id',
            'modalidade_id' => 'nullable|exists:modalidades,id',
            'marco_id' => 'nullable|exists:memoria_marcos,id',
            'personalidade_id' => 'nullable|exists:memoria_personalidades,id',
            'pessoas_json' => 'nullable|string',
            'pessoas' => 'nullable',
            'pessoas_envolvidas' => 'nullable|string|max:255',
            'fonte' => 'nullable|string|max:255',
            'fonte_tipo' => 'nullable|in:arquivo_publico,jornal,secretaria,clube,outro',
            'doacao' => 'nullable|string|max:255',
            'credito' => 'nullable|string|max:255',
            'fotografo' => 'nullable|string|max:255',
            'licenca' => 'nullable|in:uso_institucional,doacao,arquivo_publico,direitos_reservados,nao_informado',
            'data_incerta' => 'nullable',
            'aparece_na_timeline' => 'nullable',
            'publicado' => 'nullable',
            'precisa_identificar' => 'nullable',
            'equipe_ids' => 'nullable',
            'marco_ids' => 'nullable',
            'tag_nomes' => 'nullable',
        ];
    }

    private function acervoPayload(Request $request, ?UploadedFile $file = null, ?string $path = null): array
    {
        $mime = $file ? (string) $file->getMimeType() : null;
        $tipo = $request->input('tipo');
        if (!$tipo && $mime) {
            $tipo = str_starts_with($mime, 'video/')
                ? 'video'
                : (str_starts_with($mime, 'audio/') ? 'audio' : (str_starts_with($mime, 'image/') ? 'foto' : 'documento'));
        }

        $payload = [
            'tipo' => $tipo,
            'titulo' => $request->input('titulo'),
            'alt_text' => $request->input('alt_text') ?: $request->input('titulo'),
            'descricao' => $request->input('descricao'),
            'historia' => $request->input('historia'),
            'transcricao' => $request->input('transcricao'),
            'data_aproximada' => $request->input('data_aproximada'),
            'ano' => $request->input('ano'),
            'local_id' => $request->input('local_id') ?: null,
            'modalidade_id' => $request->input('modalidade_id') ?: null,
            'marco_id' => $request->input('marco_id') ?: null,
            'personalidade_id' => $request->input('personalidade_id') ?: null,
            'pessoas_envolvidas' => $request->input('pessoas_envolvidas'),
            'fonte' => $request->input('fonte'),
            'fonte_tipo' => $request->input('fonte_tipo') ?: null,
            'doacao' => $request->input('doacao'),
            'credito' => $request->input('credito') ?: $request->input('fonte'),
            'fotografo' => $request->input('fotografo'),
            'licenca' => $request->input('licenca') ?: 'nao_informado',
            'data_incerta' => filter_var($request->input('data_incerta'), FILTER_VALIDATE_BOOLEAN),
            'aparece_na_timeline' => filter_var($request->input('aparece_na_timeline'), FILTER_VALIDATE_BOOLEAN),
            'publicado' => filter_var($request->input('publicado', true), FILTER_VALIDATE_BOOLEAN),
            'precisa_identificar' => filter_var($request->input('precisa_identificar'), FILTER_VALIDATE_BOOLEAN),
        ];

        if ($path) {
            $payload['arquivo'] = $path;
            $payload['mime'] = $mime;
            $payload['tamanho'] = $file?->getSize();
            if ($file) {
                $payload['hash_arquivo'] = MemoriaAcervo::hashFromUpload($file);
            }
        }

        return $payload;
    }

    private function validateRecorde(Request $request): array
    {
        $data = $request->validate([
            'titulo' => 'required|string|max:255',
            'categoria' => 'nullable|string|max:50',
            'valor' => 'nullable|string|max:80',
            'unidade' => 'nullable|string|max:40',
            'ano' => 'nullable|integer|min:1900|max:' . (date('Y') + 1),
            'modalidade_id' => 'nullable|exists:modalidades,id',
            'campeonato_id' => 'nullable|exists:campeonatos,id',
            'personalidade_id' => 'nullable|exists:memoria_personalidades,id',
            'marco_id' => 'nullable|exists:memoria_marcos,id',
            'atleta_nome' => 'nullable|string|max:255',
            'descricao' => 'nullable|string',
            'vigente' => 'nullable',
            'publicado' => 'nullable',
        ]);
        $data['categoria'] = $data['categoria'] ?: 'outro';
        $data['vigente'] = filter_var($request->input('vigente', true), FILTER_VALIDATE_BOOLEAN);
        $data['publicado'] = filter_var($request->input('publicado', true), FILTER_VALIDATE_BOOLEAN);

        return $data;
    }

    private function attachFoto(Request $request, $model, string $folder): void
    {
        if ($request->hasFile('foto')) {
            if ($model->foto) {
                $this->fileService->deleteFile($model->foto);
            }
            $model->foto = $this->fileService->storeFile($request->file('foto'), $folder);
        }
    }

    private function attachLocalMedia(Request $request, MemoriaLocal $local): void
    {
        $this->attachFoto($request, $local, 'memoria/locais');
        foreach (['foto_antes' => 'memoria/locais/antes', 'foto_depois' => 'memoria/locais/depois'] as $field => $folder) {
            if ($request->hasFile($field)) {
                if ($local->{$field}) {
                    $this->fileService->deleteFile($local->{$field});
                }
                $local->{$field} = $this->fileService->storeFile($request->file($field), $folder);
            }
        }
    }

    private function attachCapa(Request $request, MemoriaMarco $model): void
    {
        if ($request->hasFile('capa')) {
            if ($model->capa) {
                $this->fileService->deleteFile($model->capa);
            }
            $model->capa = $this->fileService->storeFile($request->file('capa'), 'memoria/marcos');
        }
    }

    private function parseIds($value): array
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value = json_last_error() === JSON_ERROR_NONE ? $decoded : explode(',', $value);
        }
        if (!is_array($value)) {
            return [];
        }
        return array_values(array_filter(array_map('intval', $value)));
    }

    private function parsePessoas(Request $request): array
    {
        $raw = $request->input('pessoas_json', $request->input('pessoas'));
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            $raw = json_last_error() === JSON_ERROR_NONE ? $decoded : [];
        }
        if (!is_array($raw)) {
            return [];
        }

        $out = [];
        foreach ($raw as $i => $row) {
            $id = (int) ($row['personalidade_id'] ?? $row['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            $out[] = [
                'personalidade_id' => $id,
                'papel' => (string) ($row['papel'] ?? 'outro') ?: 'outro',
                'ordem' => (int) ($row['ordem'] ?? $i),
                'observacao' => $row['observacao'] ?? null,
            ];
        }

        return $out;
    }

    private function syncAcervoPessoas(MemoriaAcervo $item, Request $request): void
    {
        if (!$request->exists('pessoas_json') && !$request->exists('pessoas') && !$request->filled('personalidade_id')) {
            if ($item->personalidade_id && $item->pessoas()->count() === 0) {
                $item->pessoas()->syncWithoutDetaching([
                    $item->personalidade_id => ['papel' => 'outro', 'ordem' => 0],
                ]);
            }
            return;
        }

        $pessoas = $this->parsePessoas($request);
        if (!$pessoas && $request->filled('personalidade_id')) {
            $pessoas[] = [
                'personalidade_id' => (int) $request->input('personalidade_id'),
                'papel' => 'outro',
                'ordem' => 0,
                'observacao' => null,
            ];
        }

        $sync = [];
        foreach ($pessoas as $row) {
            $sync[$row['personalidade_id']] = [
                'papel' => $row['papel'],
                'ordem' => $row['ordem'],
                'observacao' => $row['observacao'],
            ];
        }
        $item->pessoas()->sync($sync);
        $first = array_key_first($sync);
        $item->personalidade_id = $first ?: null;
        $item->saveQuietly();
    }

    private function syncAcervoLinks(MemoriaAcervo $item, Request $request): void
    {
        if ($request->exists('equipe_ids')) {
            $item->equipes()->sync($this->parseIds($request->input('equipe_ids')));
        }
        if ($request->exists('marco_ids') || $request->filled('marco_id')) {
            $ids = $this->parseIds($request->input('marco_ids'));
            if ($request->filled('marco_id')) {
                $ids[] = (int) $request->input('marco_id');
            }
            $ids = array_values(array_unique(array_filter($ids)));
            $item->marcos()->sync($ids);
            if (!$item->marco_id && $ids) {
                $item->marco_id = $ids[0];
                $item->saveQuietly();
            }
        }
        if ($request->exists('tag_nomes')) {
            $raw = $request->input('tag_nomes');
            if (is_string($raw)) {
                $decoded = json_decode($raw, true);
                $raw = json_last_error() === JSON_ERROR_NONE ? $decoded : preg_split('/[,;]+/', $raw);
            }
            $ids = [];
            foreach ((array) $raw as $nome) {
                $nome = trim((string) $nome);
                if ($nome === '') {
                    continue;
                }
                $tag = MemoriaTag::query()->where('nome', $nome)->first() ?: MemoriaTag::create(['nome' => $nome]);
                $ids[] = $tag->id;
            }
            $item->tags()->sync($ids);
        }
    }

    private function validateEquipe(Request $request): array
    {
        $data = $request->validate([
            'nome' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'nome_anterior' => 'nullable|string|max:255',
            'apelido' => 'nullable|string|max:120',
            'modalidade_id' => 'nullable|exists:modalidades,id',
            'ano_inicio' => 'nullable|integer|min:1900|max:' . (date('Y') + 1),
            'ano_fim' => 'nullable|integer|min:1900|max:' . (date('Y') + 1),
            'cidade' => 'nullable|string|max:120',
            'descricao' => 'nullable|string',
            'historia' => 'nullable|string',
            'publicado' => 'nullable',
            'escudo' => 'nullable|file|image|max:20480',
            'pessoas_json' => 'nullable',
        ]);
        $data['publicado'] = filter_var($request->input('publicado', true), FILTER_VALIDATE_BOOLEAN);
        unset($data['escudo'], $data['pessoas_json']);
        return $data;
    }

    private function attachEscudo(Request $request, MemoriaEquipe $item): void
    {
        if ($request->hasFile('escudo')) {
            if ($item->escudo) {
                $this->fileService->deleteFile($item->escudo);
            }
            $item->escudo = $this->fileService->storeFile($request->file('escudo'), 'memoria/equipes');
        }
    }

    private function syncEquipePessoas(MemoriaEquipe $item, Request $request): void
    {
        if (!$request->exists('pessoas_json')) {
            return;
        }
        $raw = $request->input('pessoas_json');
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            $raw = json_last_error() === JSON_ERROR_NONE ? $decoded : [];
        }
        $sync = [];
        foreach ((array) $raw as $row) {
            $id = (int) ($row['personalidade_id'] ?? 0);
            if ($id) {
                $sync[$id] = [
                    'papel' => $row['papel'] ?? 'atleta',
                    'ano_inicio' => $row['ano_inicio'] ?? null,
                    'ano_fim' => $row['ano_fim'] ?? null,
                ];
            }
        }
        $item->pessoas()->sync($sync);
    }

    private function collectOpcoes(string $grupo, string $model, string $column, array $defaults): array
    {
        $saved = MemoriaOpcao::query()->where('grupo', $grupo)->pluck('valor');
        $used = $model::query()->whereNotNull($column)->where($column, '!=', '')->distinct()->pluck($column);

        return $saved->merge($used)
            ->map(fn ($v) => trim((string) $v))
            ->filter()
            ->reject(fn ($v) => in_array($v, $defaults, true))
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    private function rememberOpcao(string $grupo, ?string $valor): void
    {
        $valor = trim((string) $valor);
        if ($valor === '') {
            return;
        }
        $defaults = [
            'local_tipo' => ['ginasio', 'estadio', 'quadra', 'pista', 'outro'],
            'marco_tipo' => ['conquista', 'campeonato', 'inauguracao', 'atleta', 'marco'],
            'papel' => ['atleta', 'tecnico', 'dirigente', 'outro'],
        ];
        if (in_array($valor, $defaults[$grupo] ?? [], true)) {
            return;
        }
        MemoriaOpcao::firstOrCreate(['grupo' => $grupo, 'valor' => $valor]);
    }
}
