<?php

namespace App\Http\Controllers;

use App\Models\EventoPartida;
use App\Services\EventoPartidaService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class EventoPartidaController extends Controller
{
    protected $service;

    public function __construct(EventoPartidaService $service)
    {
        $this->service = $service;
    }

    public function index($partidaId)
    {
        $eventos = $this->service->listarEventos($partidaId);
        return response()->json($eventos);
    }

    public function store(Request $request)
    {
        // Ensure user is authenticated and is ADMIN
        if (!auth()->check() || auth()->user()->perfil !== 'ADMIN') {
            return response()->json(['message' => 'Acesso negado. Apenas administradores podem registrar eventos.'], 403);
        }

        $validated = $request->validate([
            'partida_id' => 'required|exists:partidas,id',
            'jogador_id' => 'required|exists:atletas,id',
            'jogador_saiu_id' => 'nullable|exists:atletas,id|required_if:tipo_evento,SUBSTITUICAO',
            'time_id' => 'required|exists:equipes,id',
            'tipo_evento' => ['required', Rule::in(['GOL', 'GOL_CONTRA', 'CARTAO_AMARELO', 'CARTAO_VERMELHO', 'SUBSTITUICAO'])],
            'minuto' => 'required|integer|min:0',
            'observacao' => 'nullable|string',
        ]);

        try {
            $evento = $this->service->criarEvento($validated);
            return response()->json($evento, 201);
        } catch (ValidationException $e) {
            $erros = $e->errors();
            $primeiraMensagem = collect($erros)->flatten()->first() ?? 'Dados inválidos.';
            return response()->json(['message' => $primeiraMensagem, 'errors' => $erros], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function destroy($id)
    {
        // Ensure user is authenticated and is ADMIN
        if (!auth()->check() || auth()->user()->perfil !== 'ADMIN') {
            return response()->json(['message' => 'Acesso negado. Apenas administradores podem deletar eventos.'], 403);
        }

        try {
            $this->service->deletarEvento($id);
            return response()->json(['message' => 'Evento deletado com sucesso.']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
