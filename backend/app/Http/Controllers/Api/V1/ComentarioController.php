<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Comentario;
use App\Models\Noticia;
use Illuminate\Http\Request;

class ComentarioController extends Controller
{
    public function store(Request $request, $noticiaId)
    {
        $noticia = Noticia::findOrFail($noticiaId);

        $request->validate([
            'conteudo' => 'required|string|max:1000',
            // If guest logic needed, validate name/email. But for now assuming auth or simple.
            // 'nome' => 'required_without:user_id', 
        ]);

        $data = [
            'noticia_id' => $noticia->id,
            'conteudo' => $request->conteudo,
            'aprovado' => true, // Auto-approve for now
        ];

        if ($request->user()) {
            $data['user_id'] = $request->user()->id;
            $data['nome'] = $request->user()->name;
            $data['email'] = $request->user()->email;
        } else {
             // If we allow guests later
             $data['nome'] = $request->input('nome', 'Anônimo');
             $data['email'] = $request->input('email');
        }

        $comentario = Comentario::create($data);
        
        // Return with user info for immediate display
        $comentario->load('user');

        return response()->json($comentario, 201);
    }

    public function index($noticiaId)
    {
        // Public comments
        $comments = Comentario::where('noticia_id', $noticiaId)
            ->where('aprovado', true)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(20);
            
        return response()->json($comments);
    }

    public function destroy($id)
    {
        $comentario = Comentario::findOrFail($id);
        $comentario->delete();

        return response()->json(['message' => 'Comentário excluído com sucesso']);
    }

    // Admin: List all comments for a news item (including pending/deleted if needed)
    public function indexAdmin($noticiaId)
    {
        $comments = Comentario::where('noticia_id', $noticiaId)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();
            
        return response()->json($comments);
    }
}
