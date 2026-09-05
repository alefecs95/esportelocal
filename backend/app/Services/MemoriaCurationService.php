<?php

namespace App\Services;

use App\Models\MemoriaAcervo;
use App\Models\MemoriaAuditoria;
use App\Models\MemoriaNotificacao;
use App\Models\User;

class MemoriaCurationService
{
    public function log(?User $user, string $acao, string $tipo, ?int $id, ?string $resumo): void
    {
        MemoriaAuditoria::create([
            'user_id' => $user?->id,
            'acao' => $acao,
            'entidade_tipo' => $tipo,
            'entidade_id' => $id,
            'resumo' => $resumo,
        ]);
    }

    public function notifyCuradores(MemoriaAcervo $item): void
    {
        $this->notifyInbox(
            'Novo envio da comunidade',
            ($item->doador_nome ?: 'Alguém') . ' enviou “' . $item->titulo . '”.',
            'envio'
        );
    }

    public function notifyInbox(string $titulo, string $mensagem, string $tipo = 'envio'): void
    {
        $curadores = User::query()
            ->whereIn('perfil', ['ADMIN', 'SUPER_ADMIN', 'REDATOR'])
            ->where('status', 'ATIVO')
            ->get();

        foreach ($curadores as $user) {
            MemoriaNotificacao::create([
                'user_id' => $user->id,
                'tipo' => $tipo,
                'titulo' => $titulo,
                'mensagem' => $mensagem,
                'link' => '/admin/memoria',
                'lida' => false,
            ]);
        }
    }
}
