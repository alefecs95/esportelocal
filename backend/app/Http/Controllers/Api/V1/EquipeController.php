<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Equipe;
use App\Models\Campeonato;
use App\Models\Atleta;
use App\Models\Inscricao;
use App\Models\Entidade;
use App\Models\Partida;
use App\Services\WhatsAppNotificationService;
use App\Services\CampeonatoService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class EquipeController extends Controller
{
    public function __construct(
        private WhatsAppNotificationService $whatsAppNotificationService,
        private CampeonatoService $campeonatoService
    ) {
    }

    public function indexByCampeonato($campeonatoId)
    {
        $equipes = Equipe::where('campeonato_id', $campeonatoId)
            ->with(['entidade'])
            ->withCount('atletas')
            ->get();
        return response()->json($equipes);
    }

    public function indexByResponsavel(Request $request)
    {
        $user = $request->user();
        $equipes = Equipe::where(function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->orWhereHas('entidade', function ($entidadeQuery) use ($user) {
                        $entidadeQuery->where('user_id', $user->id);
                    });
            })
            ->with(['campeonato', 'entidade'])
            ->withCount('atletas')
            ->orderBy('created_at', 'desc')
            ->get();
            
        return response()->json($equipes);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'campeonato_id' => 'required|exists:campeonatos,id',
            'entidade_id' => 'nullable|exists:entidades,id',
            'nome' => 'required|string|max:255',
            'responsavel' => 'nullable|string|max:255',
            'contato' => 'nullable|string|max:255',
            'user_id' => 'nullable|exists:users,id',
            'status_inscricao' => 'nullable|string|in:pendente,aprovada,rejeitada',
            'logo' => 'nullable|file|image|max:2048', // Validação de imagem
        ]);

        $campeonato = Campeonato::findOrFail($validated['campeonato_id']);

        if ($campeonato->tipo_competicao !== 'equipe') {
            return response()->json(['message' => 'Este campeonato não é por equipes.'], 400);
        }
        
        // Upload de Logo
        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('logos', 'public');
            $validated['logo'] = $path;
        }

        if ($campeonato->status !== 'inscricoes_abertas') {
            return response()->json(['message' => 'Inscrições de equipes não estão abertas para este campeonato.'], 422);
        }

        $hoje = Carbon::today();
        if ($campeonato->inicio_inscricao && $hoje->lt(Carbon::parse($campeonato->inicio_inscricao))) {
            return response()->json(['message' => 'Período de inscrição ainda não iniciou.'], 422);
        }
        if ($campeonato->fim_inscricao && $hoje->gt(Carbon::parse($campeonato->fim_inscricao))) {
            return response()->json(['message' => 'Período de inscrição já foi encerrado.'], 422);
        }
        
        $user = Auth::guard('sanctum')->user();
        
        // Se for Responsável solicitando
        if ($user && $user->perfil === 'RESPONSAVEL') {
            if (!$campeonato->permite_inscricao_online) {
                 return response()->json(['message' => 'Este campeonato não permite inscrição online.'], 403);
            }
            $validated['status_inscricao'] = 'pendente';
            $validated['user_id'] = $user->id;

            // --- Lógica de Entidade Automática ---
            // Verifica se o usuário já tem um Clube (Entidade)
            $entidade = Entidade::where('user_id', $user->id)->first();
            
            if ($entidade) {
                // Usa o clube existente
                $validated['entidade_id'] = $entidade->id;
            } else {
                // Se não tiver, cria um novo Clube usando o nome da equipe como nome do clube
                // Isso simplifica o cadastro inicial
                $entidade = Entidade::create([
                    'user_id' => $user->id,
                    'nome' => $validated['nome'], // O primeiro time define o nome do clube
                    'responsavel' => $user->name,
                    'contato' => $validated['contato'] ?? $user->email,
                    'prefeitura_id' => $campeonato->prefeitura_id // Assume mesmo tenant
                ]);
                $validated['entidade_id'] = $entidade->id;
            }
            
            // Preenche responsável automaticamente se não vier
            if (empty($validated['responsavel'])) {
                $validated['responsavel'] = $user->name;
            }
        } 
        // Se vier explicitamente como pendente (talvez via API pública, se houver)
        else if ($request->input('status_inscricao') === 'pendente') {
             if (!$campeonato->permite_inscricao_online) {
                 return response()->json(['message' => 'Este campeonato não permite inscrição online.'], 403);
            }
            $validated['status_inscricao'] = 'pendente';
             if ($user) $validated['user_id'] = $user->id;
        }
        else {
            // Admin criando direto (ou sem user logado se for permitido, mas aqui assumimos auth)
            $validated['status_inscricao'] = 'aprovada';
             if ($user) {
                $validated['user_id'] = $user->id; 
            }
        }
        
        // Sync status with status_inscricao
        $validated['status'] = $validated['status_inscricao'];

        $equipe = Equipe::create($validated);

        return response()->json($equipe, 201);
    }

    public function show($id)
    {
        $equipe = Equipe::with(['atletas', 'entidade', 'campeonato'])->findOrFail($id);
        
        // Authorization check if user is RESPONSIBLE
        $user = Auth::guard('sanctum')->user();
        if ($user && $user->perfil === 'RESPONSAVEL' && $equipe->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json($equipe);
    }
    
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pendente,aprovada,rejeitada'
        ]);
        
        $equipe = Equipe::findOrFail($id);
        $equipe->status = $request->status;
        $equipe->status_inscricao = $request->status;
        $equipe->save();
        
        return response()->json($equipe);
    }

    public function addAtleta(Request $request, $id)
    {
        $request->validate([
            'atleta_id' => 'required|exists:atletas,id',
            'inscricao_direta' => 'nullable|boolean',
        ]);

        $equipe = Equipe::findOrFail($id);
        
        // Auth check
        $user = Auth::guard('sanctum')->user();
        if ($user && $user->perfil === 'RESPONSAVEL') {
            if ($equipe->user_id !== $user->id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
            if ($equipe->status !== 'aprovada') {
                 return response()->json(['message' => 'Sua equipe precisa estar aprovada para adicionar atletas.'], 400);
            }
        }

        $atletaId = $request->atleta_id;
        $campeonato = $equipe->campeonato;
        $atleta = Atleta::findOrFail($atletaId);

        if (!$atleta->pode_participar_competicoes) {
            return response()->json([
                'success' => false,
                'message' => 'Atleta está impedido de participar de competições.'
            ], 422);
        }

        $elegibilidade = $this->campeonatoService->verificarElegibilidade($atletaId, $campeonato->id);
        if (!($elegibilidade['elegivel'] ?? false)) {
            return response()->json([
                'success' => false,
                'message' => 'Atleta não está apto para esta competição: ' . implode(' ', $elegibilidade['motivos'] ?? []),
            ], 422);
        }

        if ($campeonato->status === 'draft' || $campeonato->status === 'finalizado') {
            return response()->json([
                'success' => false,
                'message' => 'Não é permitido alterar elenco neste status da competição.'
            ], 422);
        }

        if ($campeonato->status === 'em_andamento') {
            $rodadaLimite = (int) ($campeonato->rodada_limite_inscricao_atletas ?? 0);
            $rodadaAtual = $this->getRodadaAtual($campeonato->id);

            if ($rodadaLimite < 1 || $rodadaAtual > $rodadaLimite) {
                return response()->json([
                    'success' => false,
                    'message' => 'Prazo de inscrição de atletas por rodada já foi encerrado.'
                ], 422);
            }
        }

        // --- MUDANÇA DE REGRA ---
        // Agora, adicionar o atleta à equipe CRIA a inscrição dele automaticamente no campeonato.
        // O fluxo de "notificação para o atleta confirmar" seria implementado aqui enviando um email/notificação.
        // Para simplificar agora, vamos assumir que o Responsável tem autoridade para inscrever o atleta.
        
        // 1. Verifica se já está em outra equipe neste campeonato
        $existsInAnotherTeam = Equipe::where('campeonato_id', $campeonato->id)
            ->where('id', '!=', $id)
            ->whereHas('atletas', function ($query) use ($atletaId) {
                $query->where('atletas.id', $atletaId);
            })
            ->exists();

        if ($existsInAnotherTeam) {
             return response()->json([
               "success" => false,
               "message" => "Atleta já pertence a outra equipe neste campeonato."
             ], 400);
        }
        
        // Check if already in this team
        if ($equipe->atletas()->where('atletas.id', $atletaId)->exists()) {
             return response()->json([
               "success" => false,
               "message" => "Atleta já está nesta equipe."
             ], 400);
        }

        // 2. Verify max athletes limit
        if ($campeonato->max_atletas_equipe && $equipe->atletas()->count() >= $campeonato->max_atletas_equipe) {
            return response()->json([
                "success" => false,
                "message" => "Limite máximo de atletas por equipe atingido."
            ], 400);
        }

        // 3. Criar ou Atualizar Inscrição
        // Se já existe inscrição (talvez rejeitada ou pendente de outro time que saiu), atualiza.
        // Se não, cria.
        
        $inscricaoDireta = $request->boolean('inscricao_direta') && $user && in_array($user->perfil, ['ADMIN', 'SUPER_ADMIN'], true);

        $inscricao = Inscricao::firstOrNew([
            'campeonato_id' => $campeonato->id,
            'atleta_id' => $atletaId
        ]);

        // Admin pode inscrição direta (sem convite); responsável segue fluxo por convite.
        $inscricao->status = $inscricaoDireta ? 'confirmada' : 'pendente';
        $inscricao->data_inscricao = now();
        if ($inscricaoDireta) {
            $inscricao->aceitou_termos_imagem_em = now();
            $inscricao->aceitou_regulamento_em = now();
        }
        $inscricao->save();

        $equipe->atletas()->attach($atletaId, [
            'status' => $inscricaoDireta ? 'aprovado' : 'pendente',
            'aprovado_por' => $inscricaoDireta ? $user?->id : null,
            'aprovado_em' => $inscricaoDireta ? now() : null,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        if (!$inscricaoDireta) {
            $this->whatsAppNotificationService->notifyTeamInvite($atleta, $equipe, $campeonato, $inscricao);
            return response()->json(['success' => true, 'message' => 'Convite enviado ao atleta. Aguardando aceitação.']);
        }

        return response()->json(['success' => true, 'message' => 'Atleta adicionado diretamente ao elenco com sucesso.']);
    }

    public function atletasElegiveis(Request $request, $id): JsonResponse
    {
        $equipe = Equipe::with('campeonato')->findOrFail($id);
        $user = Auth::guard('sanctum')->user();

        if ($user && $user->perfil === 'RESPONSAVEL' && $equipe->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $campeonato = $equipe->campeonato;
        $criteria = [
            'idade_min' => $campeonato->idade_min,
            'idade_max' => $campeonato->idade_max,
            'cidade_eleitoral' => $campeonato->cidade_eleitoral,
            'bairro_eleitoral' => $campeonato->bairro_eleitoral,
            'genero' => $campeonato->genero,
        ];

        $elegiveisBase = $this->campeonatoService->simularElegiveis($criteria, true);
        $idsElegiveis = collect($elegiveisBase)->pluck('id')->values()->all();

        if (count($idsElegiveis) === 0) {
            return response()->json(['success' => true, 'data' => []]);
        }

        $atletas = Atleta::query()
            ->whereIn('id', $idsElegiveis)
            ->whereDoesntHave('equipes', function ($query) use ($equipe) {
                $query->where('campeonato_id', $equipe->campeonato_id)
                    ->where('equipes.id', '!=', $equipe->id);
            })
            ->select(['id', 'nome', 'cpf', 'data_nascimento'])
            ->orderBy('nome')
            ->get();

        return response()->json(['success' => true, 'data' => $atletas]);
    }

    public function updateAtletaStatus(Request $request, $id, $atletaId): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:aprovado,rejeitado'
        ]);

        $equipe = Equipe::findOrFail($id);
        $user = Auth::guard('sanctum')->user();

        if ($user && $user->perfil === 'RESPONSAVEL') {
            return response()->json(['message' => 'Apenas administração pode aprovar ou rejeitar atletas do elenco.'], 403);
        }

        if (!$equipe->atletas()->where('atletas.id', $atletaId)->exists()) {
            return response()->json(['message' => 'Atleta não encontrado neste elenco.'], 404);
        }

        $status = $request->status;
        $equipe->atletas()->updateExistingPivot($atletaId, [
            'status' => $status,
            'aprovado_por' => $user?->id,
            'aprovado_em' => now(),
            'updated_at' => now()
        ]);

        Inscricao::where('campeonato_id', $equipe->campeonato_id)
            ->where('atleta_id', $atletaId)
            ->update([
                'status' => $status === 'aprovado' ? 'confirmada' : 'rejeitada'
            ]);

        return response()->json(['success' => true, 'message' => 'Status do atleta atualizado no elenco.']);
    }

    public function updateAtletasStatusLote(Request $request, $id): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:aprovado,rejeitado',
            'atleta_ids' => 'required|array|min:1',
            'atleta_ids.*' => 'required|integer|exists:atletas,id'
        ]);

        $equipe = Equipe::findOrFail($id);
        $user = Auth::guard('sanctum')->user();

        if ($user && $user->perfil === 'RESPONSAVEL') {
            return response()->json(['message' => 'Apenas administração pode aprovar ou rejeitar atletas do elenco.'], 403);
        }

        $atletaIds = collect($request->atleta_ids)->unique()->values()->all();
        $atletasDaEquipe = $equipe->atletas()
            ->whereIn('atletas.id', $atletaIds)
            ->pluck('atletas.id')
            ->all();

        if (count($atletasDaEquipe) !== count($atletaIds)) {
            return response()->json(['message' => 'Um ou mais atletas informados não pertencem a este elenco.'], 422);
        }

        $status = $request->status;
        $agora = now();
        foreach ($atletaIds as $atletaId) {
            $equipe->atletas()->updateExistingPivot($atletaId, [
                'status' => $status,
                'aprovado_por' => $user?->id,
                'aprovado_em' => $agora,
                'updated_at' => $agora
            ]);
        }

        Inscricao::where('campeonato_id', $equipe->campeonato_id)
            ->whereIn('atleta_id', $atletaIds)
            ->update([
                'status' => $status === 'aprovado' ? 'confirmada' : 'rejeitada'
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Status dos atletas atualizado em lote.',
            'updated_count' => count($atletaIds)
        ]);
    }

    public function removeAtleta($id, $atletaId)
    {
        $equipe = Equipe::findOrFail($id);
        
        // Auth check
        $user = Auth::guard('sanctum')->user();
        if ($user && $user->perfil === 'RESPONSAVEL' && $equipe->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $equipe->atletas()->detach($atletaId);

        return response()->json(['success' => true, 'message' => 'Atleta removido com sucesso.']);
    }

    private function getRodadaAtual(int $campeonatoId): int
    {
        $rodadaFinalizada = Partida::whereHas('fase', function ($query) use ($campeonatoId) {
            $query->where('campeonato_id', $campeonatoId);
        })
            ->where('status', 'finalizada')
            ->max('rodada');

        return $rodadaFinalizada ? ((int) $rodadaFinalizada + 1) : 1;
    }
}
