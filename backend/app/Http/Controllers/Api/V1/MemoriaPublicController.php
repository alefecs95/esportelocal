<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\MemoriaAcervo;
use App\Models\MemoriaLocal;
use App\Models\MemoriaMarco;
use App\Models\MemoriaPersonalidade;
use App\Services\TenantFileService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class MemoriaPublicController extends Controller
{
    public function __construct(private TenantFileService $fileService)
    {
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
                ->where('destaque', true)
                ->orderBy('nome')
                ->limit(8)
                ->get(),
            'stats' => [
                'marcos' => MemoriaMarco::publicado()->count(),
                'personalidades' => MemoriaPersonalidade::publicado()->count(),
                'acervo' => MemoriaAcervo::aprovado()->count(),
                'contribuicoes_pendentes' => 0,
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
        $decadas = $marcos->pluck('decada')->filter()->unique()->sortDesc()->values();

        return response()->json([
            'decadas' => $decadas,
            'marcos' => $marcos,
        ]);
    }

    public function showMarco($slug)
    {
        $marco = MemoriaMarco::publicado()
            ->with([
                'modalidade',
                'local',
                'personalidades',
                'acervo' => fn ($q) => $q->aprovado(),
            ])
            ->where(function ($query) use ($slug) {
                $query->where('slug', $slug)->orWhere('id', $slug);
            })
            ->firstOrFail();

        return response()->json($marco);
    }

    public function galeria(Request $request)
    {
        $query = MemoriaAcervo::aprovado()
            ->with(['modalidade', 'local', 'personalidade', 'marco'])
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

    public function personalidades(Request $request)
    {
        $query = MemoriaPersonalidade::publicado()
            ->with('modalidade')
            ->orderByDesc('destaque')
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
            ->with([
                'modalidade',
                'marcos' => fn ($q) => $q->publicado(),
                'acervo' => fn ($q) => $q->aprovado(),
            ])
            ->where(function ($query) use ($slug) {
                $query->where('slug', $slug)->orWhere('id', $slug);
            })
            ->firstOrFail();

        return response()->json($item);
    }

    public function locais()
    {
        return response()->json(
            MemoriaLocal::query()
                ->where('publicado', true)
                ->orderBy('nome')
                ->get()
        );
    }

    public function contribuir(Request $request)
    {
        $request->validate([
            'arquivo' => 'required|file|max:20480|mimetypes:image/jpeg,image/png,image/webp,image/gif,video/mp4,video/webm,application/pdf',
            'titulo' => 'required|string|max:255',
            'descricao' => 'nullable|string|max:2000',
            'tipo' => 'nullable|in:foto,video,documento',
            'data_aproximada' => 'nullable|date',
            'ano' => 'nullable|integer|min:1900|max:' . (date('Y') + 1),
            'local_id' => 'nullable|exists:memoria_locais,id',
            'modalidade_id' => 'nullable|exists:modalidades,id',
            'pessoas_envolvidas' => 'nullable|string|max:255',
            'fonte' => 'nullable|string|max:255',
            'doador_nome' => 'required|string|max:255',
            'doador_email' => 'required|email|max:255',
            'doador_telefone' => 'nullable|string|max:30',
        ]);

        $file = $request->file('arquivo');
        $mime = (string) $file->getMimeType();
        $tipo = $request->input('tipo');
        if (!$tipo) {
            $tipo = str_starts_with($mime, 'video/') ? 'video' : (str_starts_with($mime, 'image/') ? 'foto' : 'documento');
        }

        $path = $this->fileService->storeFile($file, 'memoria/contribuicoes');

        $item = MemoriaAcervo::create([
            'tipo' => $tipo,
            'titulo' => $request->input('titulo'),
            'descricao' => $request->input('descricao'),
            'arquivo' => $path,
            'mime' => $mime,
            'tamanho' => $file->getSize(),
            'data_aproximada' => $request->input('data_aproximada'),
            'ano' => $request->input('ano'),
            'local_id' => $request->input('local_id'),
            'modalidade_id' => $request->input('modalidade_id'),
            'pessoas_envolvidas' => $request->input('pessoas_envolvidas'),
            'fonte' => $request->input('fonte'),
            'origem' => 'comunidade',
            'doador_nome' => $request->input('doador_nome'),
            'doador_email' => $request->input('doador_email'),
            'doador_telefone' => $request->input('doador_telefone'),
            'status' => 'pendente',
            'publicado' => false,
        ]);

        return response()->json([
            'message' => 'Contribuição enviada. A curadoria da prefeitura vai avaliar antes de publicar.',
            'id' => $item->id,
        ], 201);
    }
}
