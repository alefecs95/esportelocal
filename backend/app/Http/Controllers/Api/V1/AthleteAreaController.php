<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Atleta;
use App\Models\Partida;
use App\Models\Equipe;
use App\Models\Inscricao;

class AthleteAreaController extends Controller
{
    private function getAtletaFromUser($user)
    {
        $cpfClean = preg_replace('/[^0-9]/', '', $user->cpf);
        return Atleta::whereRaw("REPLACE(REPLACE(REPLACE(cpf, '.', ''), '-', ''), ' ', '') = ?", [$cpfClean])->first();
    }

    public function dashboard(Request $request)
    {
        $user = $request->user();
        $atleta = $this->getAtletaFromUser($user);

        if (!$atleta) {
            return response()->json([
                'atleta' => null,
                'equipes' => [],
                'proximos_jogos' => [],
                'ultimos_resultados' => [],
                'convites' => [],
                'message' => 'Perfil de atleta não vinculado ao usuário.'
            ]);
        }

        // Get invitations (Inscrições pendentes)
        // Precisamos encontrar inscrições pendentes e descobrir qual equipe está vinculada.
        // A lógica de addAtleta cria inscrição (Atleta <-> Campeonato) e EquipeAtleta (Equipe <-> Atleta).
        // Então, procuramos em EquipeAtleta onde a inscrição correspondente está pendente.
        
        $convites = Equipe::whereHas('atletas', function($q) use ($atleta) {
                $q->where('atleta_id', $atleta->id);
            })
            ->whereHas('campeonato.inscricoes', function($q) use ($atleta) {
                $q->where('atleta_id', $atleta->id)
                  ->where('status', 'pendente');
            })
            ->with(['campeonato', 'entidade'])
            ->get()
            ->map(function($equipe) use ($atleta) {
                // Encontrar a inscrição específica para pegar o ID se precisar, ou usar ids compostos
                $inscricao = Inscricao::where('campeonato_id', $equipe->campeonato_id)
                    ->where('atleta_id', $atleta->id)
                    ->first();
                    
                return [
                    'inscricao_id' => $inscricao->id,
                    'equipe_id' => $equipe->id,
                    'equipe_nome' => $equipe->nome,
                    'campeonato_nome' => $equipe->campeonato->nome,
                    'regulamento' => $equipe->campeonato->regulamento,
                    'entidade_nome' => $equipe->entidade ? $equipe->entidade->nome : 'N/A',
                    'data_convite' => $inscricao->created_at->format('d/m/Y')
                ];
            });

        // Get athlete's teams with championship info (Only confirmed)
        $minhasEquipes = $atleta->equipes()
            ->with(['campeonato', 'entidade'])
            ->whereHas('campeonato.inscricoes', function($q) use ($atleta) {
                $q->where('atleta_id', $atleta->id)
                  ->where('status', 'confirmada');
            })
            ->whereHas('campeonato', function($q) {
                $q->where('status', '!=', 'finalizado'); // Optional: show only active championships
            })
            ->get();

        $equipeIds = $minhasEquipes->pluck('id')->toArray();

        // Get upcoming matches (Team)
        $teamMatches = Partida::whereHas('fase.campeonato', function($q) {
                $q->where('tipo_competicao', 'equipe');
            })
            ->where(function($q) use ($equipeIds) {
                $q->whereIn('mandante_id', $equipeIds)
                  ->orWhereIn('visitante_id', $equipeIds);
            })
            ->where('status', 'agendada')
            ->with(['fase.campeonato'])
            ->orderBy('data_jogo')
            ->take(5)
            ->get();

        // Get upcoming matches (Individual)
        $individualMatches = Partida::whereHas('fase.campeonato', function($q) {
                $q->where('tipo_competicao', 'individual'); // Assuming 'individual' or '1x1'
            })
            ->where(function($q) use ($atleta) {
                $q->where('mandante_id', $atleta->id)
                  ->orWhere('visitante_id', $atleta->id);
            })
            ->where('status', 'agendada')
            ->with(['fase.campeonato'])
            ->orderBy('data_jogo')
            ->take(5)
            ->get();
            
        // Merge and sort matches
        $proximosJogos = $teamMatches->merge($individualMatches)->sortBy('data_jogo')->values()->take(5);

        // Format matches for response
        $proximosJogosFormatted = $proximosJogos->map(function($partida) {
            $mandante = $partida->mandante;
            $visitante = $partida->visitante;
            return [
                'id' => $partida->id,
                'data_jogo' => $partida->data_jogo,
                'local' => $partida->local,
                'campeonato' => $partida->fase->campeonato->nome,
                'mandante' => $mandante ? $mandante->nome : 'A definir',
                'visitante' => $visitante ? $visitante->nome : 'A definir',
                'placar_mandante' => $partida->placar_mandante,
                'placar_visitante' => $partida->placar_visitante,
                'status' => $partida->status
            ];
        });

        // Get recent results (finished matches)
        $teamResults = Partida::whereHas('fase.campeonato', function($q) {
                $q->where('tipo_competicao', 'equipe');
            })
            ->where(function($q) use ($equipeIds) {
                $q->whereIn('mandante_id', $equipeIds)
                  ->orWhereIn('visitante_id', $equipeIds);
            })
            ->where('status', 'finalizada')
            ->with(['fase.campeonato'])
            ->orderBy('data_jogo', 'desc')
            ->take(5)
            ->get();
            
        $individualResults = Partida::whereHas('fase.campeonato', function($q) {
                $q->where('tipo_competicao', 'individual');
            })
            ->where(function($q) use ($atleta) {
                $q->where('mandante_id', $atleta->id)
                  ->orWhere('visitante_id', $atleta->id);
            })
            ->where('status', 'finalizada')
            ->with(['fase.campeonato'])
            ->orderBy('data_jogo', 'desc')
            ->take(5)
            ->get();

        $ultimosResultados = $teamResults->merge($individualResults)->sortByDesc('data_jogo')->values()->take(5);

        $ultimosResultadosFormatted = $ultimosResultados->map(function($partida) {
             $mandante = $partida->mandante;
             $visitante = $partida->visitante;
             return [
                'id' => $partida->id,
                'data_jogo' => $partida->data_jogo,
                'local' => $partida->local,
                'campeonato' => $partida->fase->campeonato->nome,
                'mandante' => $mandante ? $mandante->nome : 'A definir',
                'visitante' => $visitante ? $visitante->nome : 'A definir',
                'placar_mandante' => $partida->placar_mandante,
                'placar_visitante' => $partida->placar_visitante,
                'status' => $partida->status
            ];
        });

        return response()->json([
            'atleta' => $atleta,
            'equipes' => $minhasEquipes,
            'proximos_jogos' => $proximosJogosFormatted,
            'ultimos_resultados' => $ultimosResultadosFormatted,
            'convites' => $convites
        ]);
    }

    public function responderConvite(Request $request, $id)
    {
        $request->validate([
            'aceitar' => 'required|boolean',
            'equipe_id' => 'required|integer|exists:equipes,id',
            'aceitou_termos_imagem' => 'required_if:aceitar,1|boolean',
            'aceitou_regulamento' => 'required_if:aceitar,1|boolean',
        ]);

        $user = $request->user();
        $atleta = $this->getAtletaFromUser($user);

        if (!$atleta) {
            return response()->json(['message' => 'Atleta não encontrado.'], 404);
        }

        $inscricao = Inscricao::where('id', $id)
            ->where('atleta_id', $atleta->id)
            ->where('status', 'pendente')
            ->first();

        if (!$inscricao) {
            return response()->json(['message' => 'Convite não encontrado ou já processado.'], 404);
        }

        $equipe = Equipe::where('id', (int) $request->equipe_id)
            ->where('campeonato_id', $inscricao->campeonato_id)
            ->whereHas('atletas', function ($q) use ($atleta) {
                $q->where('atleta_id', $atleta->id);
            })
            ->first();

        if (!$equipe) {
            return response()->json(['message' => 'Equipe do convite não encontrada.'], 404);
        }

        if ($request->aceitar) {
            if (!$request->boolean('aceitou_termos_imagem') || !$request->boolean('aceitou_regulamento')) {
                return response()->json(['message' => 'É obrigatório aceitar os termos de imagem e o regulamento.'], 422);
            }

            $inscricao->update([
                'status' => 'confirmada',
                'aceitou_termos_imagem_em' => now(),
                'aceitou_regulamento_em' => now(),
            ]);
            $equipe->atletas()->updateExistingPivot($atleta->id, [
                'status' => 'aprovado',
                'updated_at' => now(),
            ]);

            return response()->json(['message' => 'Convite aceito com sucesso!']);
        } else {
            $inscricao->update(['status' => 'rejeitada']);
            $equipe->atletas()->updateExistingPivot($atleta->id, [
                'status' => 'rejeitado',
                'updated_at' => now(),
            ]);
            return response()->json(['message' => 'Convite recusado.']);
        }
    }

    public function profile(Request $request)
    {
        $user = $request->user();
        $atleta = $this->getAtletaFromUser($user);

        if (!$atleta) {
            return response()->json(['message' => 'Perfil de atleta não encontrado.'], 404);
        }

        $atleta->load('arquivos');
        
        return response()->json($atleta);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();
        $atleta = $this->getAtletaFromUser($user);

        if (!$atleta) {
            return response()->json(['message' => 'Perfil de atleta não encontrado.'], 404);
        }

        // Validação - Permitir apenas campos de ENDEREÇO e CONTATO
        $validated = $request->validate([
            'telefone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'cep' => 'nullable|string|max:10',
            'endereco' => 'nullable|string|max:255',
            'numero' => 'nullable|string|max:20',
            'complemento' => 'nullable|string|max:100',
            'bairro' => 'nullable|string|max:100',
            'cidade' => 'nullable|string|max:100',
            'estado' => 'nullable|string|max:2',
        ]);

        $atleta->update($validated);
        
        // Atualizar email do usuário também se foi alterado
        if (isset($validated['email']) && $validated['email'] !== $user->email) {
            $user->email = $validated['email'];
            $user->save();
        }

        return response()->json(['message' => 'Perfil atualizado com sucesso.', 'atleta' => $atleta]);
    }

    public function uploadPhoto(Request $request)
    {
        $user = $request->user();
        $atleta = $this->getAtletaFromUser($user);

        if (!$atleta) {
            return response()->json(['message' => 'Perfil de atleta não encontrado.'], 404);
        }

        $request->validate([
            'foto' => 'required|image|max:5120', // 5MB max, imagem apenas
        ]);

        $file = $request->file('foto');
        
        // Deletar foto antiga se existir
        if ($atleta->foto && \Illuminate\Support\Facades\Storage::disk('public')->exists($atleta->foto)) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($atleta->foto);
        }

        $path = $file->store("atletas/{$atleta->id}/fotos", 'public');
        
        $atleta->foto = $path;
        $atleta->save();

        return response()->json(['message' => 'Foto atualizada com sucesso.', 'foto_url' => asset('storage/' . $path)]);
    }

    public function uploadDocument(Request $request)
    {
        $user = $request->user();
        $atleta = $this->getAtletaFromUser($user);

        if (!$atleta) {
            return response()->json(['message' => 'Perfil de atleta não encontrado.'], 404);
        }

        $request->validate([
            'file' => 'required|file|max:10240', // 10MB max
            'nome' => 'required|string|max:255'
        ]);

        $file = $request->file('file');
        $nome = $request->input('nome');
        
        $path = $file->store("atletas/{$atleta->id}", 'public');
        
        $arquivo = $atleta->arquivos()->create([
            'nome' => $nome,
            'path' => $path,
            'tipo' => $file->getMimeType()
        ]);

        return response()->json($arquivo, 201);
    }

    public function deleteDocument(Request $request, $id)
    {
        $user = $request->user();
        $atleta = $this->getAtletaFromUser($user);

        if (!$atleta) {
            return response()->json(['message' => 'Perfil de atleta não encontrado.'], 404);
        }

        $arquivo = $atleta->arquivos()->find($id);

        if (!$arquivo) {
            return response()->json(['message' => 'Arquivo não encontrado.'], 404);
        }

        // Deletar do disco
        if (\Illuminate\Support\Facades\Storage::disk('public')->exists($arquivo->path)) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($arquivo->path);
        }
        
        $arquivo->delete();

        return response()->json(['message' => 'Arquivo removido com sucesso.']);
    }
}
