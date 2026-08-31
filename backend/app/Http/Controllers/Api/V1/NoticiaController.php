<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Noticia;
use App\Services\TenantFileService;
use App\Services\WhatsAppNotificationService;
use Illuminate\Http\Request;

class NoticiaController extends Controller
{
    protected $fileService;

    public function __construct(
        TenantFileService $fileService,
        private WhatsAppNotificationService $whatsAppNotificationService
    ) {
        $this->fileService = $fileService;
    }

    public function index(Request $request)
    {
        $perPage = (int) $request->query('per_page', 10);
        $perPage = max(5, min($perPage, 50));
        
        $query = Noticia::with(['categoria', 'autor']);
        
        if ($request->has('categoria_id')) {
            $query->where('categoria_id', $request->query('categoria_id'));
        }

        if ($request->has('destaque')) {
            $query->where('destaque', filter_var($request->query('destaque'), FILTER_VALIDATE_BOOLEAN));
        }

        // Se for requisição pública (sem auth ou rota específica), filtra apenas publicados
        if (!$request->user()) {
            $query->where('publicado', true)
                  ->whereNotNull('publicado_em')
                  ->where('publicado_em', '<=', now());
        }

        // Prioritize featured news if no specific sort requested
        $query->orderBy('destaque', 'desc')->orderBy('publicado_em', 'desc')->orderBy('created_at', 'desc');
        return response()->json($query->paginate($perPage));
    }

    public function show($identifier)
    {
        $noticia = Noticia::with(['autor', 'categoria', 'comentarios.user'])
            ->where('slug', $identifier)
            ->orWhere('id', $identifier)
            ->firstOrFail();
        
        // Se não estiver logado e notícia não publicada, 404
        if (!request()->user()) {
            if (!$noticia->publicado || ($noticia->publicado_em && $noticia->publicado_em > now())) {
                abort(404);
            }
            // Increment view counter
            $noticia->increment('visualizacoes');
        }

        // Buscar relacionadas (mesma categoria, excluindo a atual)
        $related = [];
        if ($noticia->categoria_id) {
            $related = Noticia::where('categoria_id', $noticia->categoria_id)
                ->where('id', '!=', $noticia->id)
                ->where('publicado', true)
                ->where('publicado_em', '<=', now())
                ->orderBy('publicado_em', 'desc')
                ->take(3)
                ->get();
        } else {
             // Fallback: Latest news if no category
             $related = Noticia::where('id', '!=', $noticia->id)
                ->where('publicado', true)
                ->where('publicado_em', '<=', now())
                ->orderBy('publicado_em', 'desc')
                ->take(3)
                ->get();
        }

        // Add related to response
        $noticia->related = $related;

        return response()->json($noticia);
    }

    public function store(Request $request)
    {
        $this->authorizeNewsManagement($request);

        $request->validate([
            'titulo' => 'required|string|max:255',
            'conteudo' => 'nullable', 
            'imagem_capa' => 'nullable|file|image|max:20480',
            'galeria.*' => 'nullable|file|image|max:20480',
            'carrossel.*' => 'nullable|file|image|max:20480',
            'publicado' => 'nullable',
            'publicado_em' => 'nullable|date',
            'categoria_id' => 'nullable|exists:categorias,id',
            'meta_description' => 'nullable|string|max:255',
        ]);

        $conteudo = $request->input('conteudo');
        
        if (is_array($conteudo)) {
            $conteudo = json_encode($conteudo);
        } elseif (is_string($conteudo)) {
            $decoded = json_decode($conteudo, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                 // Valid JSON
            }
        }

        if (empty($conteudo) || $conteudo === 'null' || $conteudo === 'undefined') {
            $conteudo = '{}';
        }

        $publicado = filter_var($request->input('publicado'), FILTER_VALIDATE_BOOLEAN);
        $publicadoEm = $request->input('publicado_em');

        // Se for publicado e não tiver data definida, define agora
        if ($publicado && empty($publicadoEm)) {
            $publicadoEm = now();
        }

        $data = [
            'titulo' => $request->input('titulo'),
            'conteudo' => $conteudo,
            'publicado' => $publicado,
            'publicado_em' => $publicadoEm,
            'autor_id' => $request->user()->id,
            'categoria_id' => $request->input('categoria_id'),
            'meta_description' => $request->input('meta_description'),
            'destaque' => filter_var($request->input('destaque'), FILTER_VALIDATE_BOOLEAN),
        ];

        $noticia = Noticia::create($data);

        if ($request->hasFile('imagem_capa')) {
            $path = $this->fileService->storeFile($request->file('imagem_capa'), 'noticias');
            $noticia->imagem_capa = $path;
        }

        if ($request->hasFile('galeria')) {
            $paths = [];
            foreach ($request->file('galeria') as $file) {
                $paths[] = $this->fileService->storeFile($file, 'noticias/galeria');
            }
            $noticia->galeria = $paths;
        }

        if ($request->hasFile('carrossel')) {
            $paths = [];
            foreach ($request->file('carrossel') as $file) {
                $paths[] = $this->fileService->storeFile($file, 'noticias/carrossel');
            }
            $noticia->carrossel = $paths;
        }

        $noticia->save();

        if ($noticia->publicado) {
            $this->whatsAppNotificationService->notifyNewsPublished($noticia);
        }

        return response()->json($noticia, 201);
    }

    public function update(Request $request, $id)
    {
        $this->authorizeNewsManagement($request);

        $noticia = Noticia::findOrFail($id);
        $wasPublished = (bool) $noticia->publicado;

        $request->validate([
            'titulo' => 'required|string|max:255',
            'conteudo' => 'nullable', 
            'imagem_capa' => 'nullable', 
            'galeria.*' => 'nullable',
            'carrossel.*' => 'nullable',
            'publicado' => 'nullable',
            'publicado_em' => 'nullable|date',
            'galeria_keep' => 'nullable',
            'carrossel_keep' => 'nullable',
            'categoria_id' => 'nullable|exists:categorias,id',
            'meta_description' => 'nullable|string|max:255',
        ]);

        $conteudo = $request->input('conteudo');
        if (is_array($conteudo)) {
            $conteudo = json_encode($conteudo);
        } elseif (is_string($conteudo)) {
             $decoded = json_decode($conteudo, true);
        }
        if (empty($conteudo) || $conteudo === 'null' || $conteudo === 'undefined') {
            $conteudo = '{}';
        }

        $publicado = filter_var($request->input('publicado'), FILTER_VALIDATE_BOOLEAN);
        $publicadoEm = $request->input('publicado_em');

        // Se mudou para publicado e não tem data, define agora
        if ($publicado && empty($publicadoEm)) {
            // Se já tinha data, mantem. Se não, agora.
            $publicadoEm = $noticia->publicado_em ?? now();
        }

        $data = [
            'titulo' => $request->input('titulo'),
            'conteudo' => $conteudo,
            'publicado' => $publicado,
            'publicado_em' => $publicadoEm,
            'categoria_id' => $request->input('categoria_id'),
            'meta_description' => $request->input('meta_description'),
            'destaque' => filter_var($request->input('destaque'), FILTER_VALIDATE_BOOLEAN),
        ];

        // Handle Cover Image
        if ($request->hasFile('imagem_capa')) {
            if ($noticia->imagem_capa) {
                $this->fileService->deleteFile($noticia->imagem_capa);
            }
            $noticia->imagem_capa = $this->fileService->storeFile($request->file('imagem_capa'), 'noticias');
        }

        // Handle Gallery (Keep + Append)
        $currentGaleria = $noticia->galeria ?? [];
        $newGaleriaPaths = [];

        // Parse galeria_keep (files to keep)
        if ($request->has('galeria_keep')) {
            $keep = $request->input('galeria_keep');
            if (is_string($keep)) $keep = json_decode($keep, true);
            if (is_array($keep)) {
                // Ensure we only keep paths that actually exist in current gallery (security/integrity)
                $newGaleriaPaths = array_intersect($currentGaleria, $keep);
            }
        } else {
            $newGaleriaPaths = $currentGaleria;
        }

        // Append new files
        if ($request->hasFile('galeria')) {
            foreach ($request->file('galeria') as $file) {
                $newGaleriaPaths[] = $this->fileService->storeFile($file, 'noticias/galeria');
            }
        }
        $noticia->galeria = array_values($newGaleriaPaths);

        // Handle Carousel (Keep + Append)
        $currentCarrossel = $noticia->carrossel ?? [];
        $newCarrosselPaths = [];

        if ($request->has('carrossel_keep')) {
            $keep = $request->input('carrossel_keep');
            if (is_string($keep)) $keep = json_decode($keep, true);
            if (is_array($keep)) {
                $newCarrosselPaths = array_intersect($currentCarrossel, $keep);
            }
        } else {
            $newCarrosselPaths = $currentCarrossel;
        }

        if ($request->hasFile('carrossel')) {
            foreach ($request->file('carrossel') as $file) {
                $newCarrosselPaths[] = $this->fileService->storeFile($file, 'noticias/carrossel');
            }
        }
        $noticia->carrossel = array_values($newCarrosselPaths);

        $noticia->fill($data);
        $noticia->save();

        if (!$wasPublished && $noticia->publicado) {
            $this->whatsAppNotificationService->notifyNewsPublished($noticia);
        }

        return response()->json($noticia);
    }

    public function destroy($id)
    {
        $this->authorizeNewsManagement(request());

        $noticia = Noticia::findOrFail($id);
        $noticia->delete();
        return response()->json(['message' => 'Notícia excluída com sucesso']);
    }

    public function toggleDestaque(Request $request, $id)
    {
        $this->authorizeNewsManagement($request);

        $noticia = Noticia::findOrFail($id);
        $noticia->destaque = !$noticia->destaque;
        $noticia->save();
        return response()->json($noticia);
    }

    private function authorizeNewsManagement(Request $request): void
    {
        $user = $request->user();
        if (!$user || !in_array($user->perfil, ['ADMIN', 'SUPER_ADMIN', 'REDATOR'], true)) {
            abort(403, 'Sem permissão para gerenciar notícias.');
        }
    }
}
