<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\MemoriaAcervo;
use App\Models\MemoriaLocal;
use App\Models\MemoriaMarco;
use App\Models\MemoriaPersonalidade;
use App\Models\MemoriaAuditoria;
use App\Models\MemoriaNotificacao;
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
            'pendentes' => MemoriaAcervo::pendente()->count(),
            'orfaos' => MemoriaAcervo::aprovado()->orfao()->count(),
            'sem_alt' => MemoriaAcervo::aprovado()->where('tipo', 'foto')->where(function ($q) {
                $q->whereNull('alt_text')->orWhere('alt_text', '');
            })->count(),
            'locais' => MemoriaLocal::count(),
        ]);
    }

    public function locais()
    {
        $this->authorizeCuradoria(request());
        return response()->json(MemoriaLocal::orderBy('nome')->get());
    }

    public function storeLocal(Request $request)
    {
        $this->authorizeCuradoria($request);
        $data = $this->validateLocal($request);
        unset($data['foto']);
        $data['publicado'] = filter_var($request->input('publicado', true), FILTER_VALIDATE_BOOLEAN);
        $local = MemoriaLocal::create($data);
        $this->attachFoto($request, $local, 'memoria/locais');
        $local->save();
        return response()->json($local, 201);
    }

    public function updateLocal(Request $request, $id)
    {
        $this->authorizeCuradoria($request);
        $local = MemoriaLocal::findOrFail($id);
        $data = $this->validateLocal($request);
        unset($data['foto']);
        $data['publicado'] = filter_var($request->input('publicado', true), FILTER_VALIDATE_BOOLEAN);
        $local->fill($data);
        $this->attachFoto($request, $local, 'memoria/locais');
        $local->save();
        return response()->json($local);
    }

    public function destroyLocal($id)
    {
        $this->authorizeCuradoria(request());
        MemoriaLocal::findOrFail($id)->delete();
        return response()->json(['message' => 'Local removido.']);
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
            MemoriaPersonalidade::with(['modalidade', 'marcos', 'acervo'])->findOrFail($id)
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
        $query = MemoriaMarco::with(['modalidade', 'local', 'personalidades'])
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
            MemoriaMarco::with(['modalidade', 'local', 'personalidades', 'acervo'])->findOrFail($id)
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
        $query = MemoriaAcervo::with(['modalidade', 'local', 'marco', 'personalidade'])
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        } else {
            $query->where('status', '!=', 'pendente');
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
        $item = MemoriaAcervo::with(['modalidade', 'local', 'marco', 'personalidade'])->findOrFail($id);
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
        $this->curation->log($request->user(), 'criou', 'acervo', $item->id, $item->titulo);

        return response()->json($item->load(['modalidade', 'local', 'marco', 'personalidade']), 201);
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
        }

        $item->fill($payload);
        $item->save();
        $this->curation->log($request->user(), 'editou', 'acervo', $item->id, $item->titulo);
        return response()->json($item->load(['modalidade', 'local', 'marco', 'personalidade']));
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

        if ($request->filled('marco_id')) {
            $item->marco_id = $request->input('marco_id');
        }
        if ($request->filled('personalidade_id')) {
            $item->personalidade_id = $request->input('personalidade_id');
        }

        $item->save();
        $this->curation->log($request->user(), 'aprovou', 'acervo', $item->id, $item->titulo);
        $this->curation->notifyDoador($item, true);
        return response()->json($item);
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
        $this->curation->notifyDoador($item, false, $item->motivo_rejeicao);
        return response()->json($item);
    }

    public function lote(Request $request)
    {
        $this->authorizeCuradoria($request);
        $data = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer',
            'acao' => 'required|in:aprovar,rejeitar',
            'motivo_rejeicao' => 'nullable|string|max:1000',
        ]);

        $items = MemoriaAcervo::whereIn('id', $data['ids'])->where('status', 'pendente')->get();
        foreach ($items as $item) {
            if ($data['acao'] === 'aprovar') {
                $item->status = 'aprovado';
                $item->publicado = true;
                $item->motivo_rejeicao = null;
                $item->revisado_por = $request->user()->id;
                $item->revisado_em = now();
                $item->save();
                $this->curation->notifyDoador($item, true);
            } else {
                $item->status = 'rejeitado';
                $item->publicado = false;
                $item->motivo_rejeicao = $data['motivo_rejeicao'] ?? null;
                $item->revisado_por = $request->user()->id;
                $item->revisado_em = now();
                $item->save();
                $this->curation->notifyDoador($item, false, $item->motivo_rejeicao);
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
            'tipo' => 'nullable|string|max:50',
            'endereco' => 'nullable|string|max:255',
            'descricao' => 'nullable|string',
            'publicado' => 'nullable|boolean',
            'foto' => 'nullable|file|image|max:20480',
        ]);
    }

    private function validatePersonalidade(Request $request): array
    {
        $data = $request->validate([
            'nome' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'papel' => 'nullable|in:atleta,tecnico,dirigente,outro',
            'mini_bio' => 'nullable|string',
            'data_nascimento' => 'nullable|date',
            'data_falecimento' => 'nullable|date',
            'modalidade_id' => 'nullable|exists:modalidades,id',
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
            'arquivo' => ($fileRequired ? 'required' : 'nullable') . '|file|max:51200|mimetypes:image/jpeg,image/png,image/webp,image/gif,video/mp4,video/webm,application/pdf',
            'titulo' => 'required|string|max:255',
            'alt_text' => 'nullable|string|max:255',
            'descricao' => 'nullable|string',
            'historia' => 'nullable|string',
            'tipo' => 'nullable|in:foto,video,documento',
            'data_aproximada' => 'nullable|date',
            'ano' => 'nullable|integer|min:1900|max:' . (date('Y') + 1),
            'local_id' => 'nullable|exists:memoria_locais,id',
            'modalidade_id' => 'nullable|exists:modalidades,id',
            'marco_id' => 'nullable|exists:memoria_marcos,id',
            'personalidade_id' => 'nullable|exists:memoria_personalidades,id',
            'pessoas_envolvidas' => 'nullable|string|max:255',
            'fonte' => 'nullable|string|max:255',
            'fonte_tipo' => 'nullable|in:arquivo_publico,jornal,secretaria,clube,outro',
            'doacao' => 'nullable|string|max:255',
            'publicado' => 'nullable',
        ];
    }

    private function acervoPayload(Request $request, ?UploadedFile $file = null, ?string $path = null): array
    {
        $mime = $file ? (string) $file->getMimeType() : null;
        $tipo = $request->input('tipo');
        if (!$tipo && $mime) {
            $tipo = str_starts_with($mime, 'video/') ? 'video' : (str_starts_with($mime, 'image/') ? 'foto' : 'documento');
        }

        $payload = [
            'tipo' => $tipo,
            'titulo' => $request->input('titulo'),
            'alt_text' => $request->input('alt_text') ?: $request->input('titulo'),
            'descricao' => $request->input('descricao'),
            'historia' => $request->input('historia'),
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
            'publicado' => filter_var($request->input('publicado', true), FILTER_VALIDATE_BOOLEAN),
        ];

        if ($path) {
            $payload['arquivo'] = $path;
            $payload['mime'] = $mime;
            $payload['tamanho'] = $file?->getSize();
        }

        return $payload;
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
}
