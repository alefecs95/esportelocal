<?php

namespace App\Services;

use App\Mail\MemoriaContribuicaoStatusMail;
use App\Mail\MemoriaNovoEnvioMail;
use App\Models\MemoriaAcervo;
use App\Models\MemoriaAuditoria;
use App\Models\MemoriaNotificacao;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

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
        $curadores = User::query()
            ->whereIn('perfil', ['ADMIN', 'SUPER_ADMIN', 'REDATOR'])
            ->where('status', 'ATIVO')
            ->get();

        foreach ($curadores as $user) {
            MemoriaNotificacao::create([
                'user_id' => $user->id,
                'tipo' => 'envio',
                'titulo' => 'Novo envio da comunidade',
                'mensagem' => ($item->doador_nome ?: 'Alguém') . ' enviou “' . $item->titulo . '”.',
                'link' => '/admin/memoria',
                'lida' => false,
            ]);

            if ($user->email) {
                try {
                    Mail::to($user->email)->send(new MemoriaNovoEnvioMail($item));
                } catch (\Throwable $e) {
                    Log::warning('Falha ao e-mailar curador da Memória: ' . $e->getMessage());
                }
            }
        }
    }

    public function notifyDoador(MemoriaAcervo $item, bool $aprovado, ?string $motivo = null): void
    {
        $email = $item->doador_email;
        if (!$email) {
            return;
        }

        try {
            Mail::to($email)->send(new MemoriaContribuicaoStatusMail($item, $aprovado, $motivo));
        } catch (\Throwable $e) {
            Log::warning('Falha ao e-mailar doador da Memória: ' . $e->getMessage());
        }
    }
}
