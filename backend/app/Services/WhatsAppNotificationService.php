<?php

namespace App\Services;

use App\Events\WhatsAppNotificationRequested;
use App\Models\Atleta;
use App\Models\Campeonato;
use App\Models\Equipe;
use App\Models\Inscricao;
use App\Models\Noticia;
use App\Models\User;
use App\Models\WhatsAppNotification;
use App\Models\WhatsAppPasswordResetToken;
use App\Models\Prefeitura;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;

class WhatsAppNotificationService
{
    public function normalizePhone(?string $phone): ?string
    {
        if (!$phone) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone);
        if (!$digits) {
            return null;
        }

        if (strlen($digits) === 11) {
            $digits = '55' . $digits;
        }

        if (strlen($digits) < 12 || strlen($digits) > 13) {
            return null;
        }

        return $digits;
    }

    public function dispatchMessage(string $phone, string $type, string $message, ?int $userId = null, bool $force = false): void
    {
        $tenantId = $this->resolveTenantId($userId);

        event(new WhatsAppNotificationRequested(
            phone: $phone,
            type: $type,
            message: $message,
            userId: $userId,
            force: $force,
            tenantId: $tenantId
        ));
    }

    public function notifyUserRegistration(Atleta $atleta): void
    {
        $phone = $this->normalizePhone($atleta->telefone);
        if (!$phone) {
            return;
        }

        $user = User::where('cpf', preg_replace('/\D+/', '', $atleta->cpf))->first();
        
        $frontendUrl = $this->getTenantFrontendUrl();
        $loginUrl = $frontendUrl . '/login';
        $tenantName = $this->getTenantDisplayName();
        $message = "🏆 *Bem-vindo(a) ao {$tenantName}*\n\nSeu cadastro foi realizado com sucesso.\n\n🔐 *Acesse sua conta:*\n{$loginUrl}";

        $this->dispatchMessage($phone, 'user_registration', $message, $user?->id, false);
    }

    public function notifyTeamInvite(Atleta $atleta, Equipe $equipe, Campeonato $campeonato, Inscricao $inscricao): void
    {
        $phone = $this->normalizePhone($atleta->telefone);
        if (!$phone) {
            return;
        }

        $frontendUrl = $this->getTenantFrontendUrl();
        $inviteUrl = $frontendUrl . '/portal/convites/' . $inscricao->id;
        $tenantName = $this->getTenantDisplayName();
        $message = "⚽ *Convite para equipe — {$tenantName}*\n\nA equipe *{$equipe->nome}* convidou você para participar da competição *{$campeonato->nome}*.\n\n📩 *Veja o convite:*\n{$inviteUrl}";

        $user = User::where('cpf', preg_replace('/\D+/', '', $atleta->cpf))->first();
        $this->dispatchMessage($phone, 'team_invite', $message, $user?->id, false);
    }

    public function notifyNewsPublished(Noticia $noticia): void
    {
        $frontendUrl = $this->getTenantFrontendUrl();
        $newsUrl = $frontendUrl . '/portal/noticias/' . $noticia->id;
        $tenantName = $this->getTenantDisplayName();
        $message = "📰 *Nova notícia em {$tenantName}*\n\n*{$noticia->titulo}*\n\n📖 *Leia a notícia completa:*\n{$newsUrl}";
        $this->broadcastToSubscribed('new_news', $message);
    }

    public function notifyCompetitionCreated(Campeonato $campeonato): void
    {
        $frontendUrl = $this->getTenantFrontendUrl();
        $competitionUrl = $frontendUrl . '/portal/competicoes/' . $campeonato->id;
        $tenantName = $this->getTenantDisplayName();
        $message = "🏆 *Nova competição em {$tenantName}*\n\n*{$campeonato->nome}*\n\n📋 *Veja detalhes e inscrições:*\n{$competitionUrl}";
        $this->broadcastToSubscribed('new_competition', $message);
    }

    public function broadcastToSubscribed(string $type, string $message): void
    {
        WhatsAppNotification::query()
            ->where('receive_notifications', true)
            ->select(['prefeitura_id', 'user_id', 'phone'])
            ->chunk(200, function ($rows) use ($type, $message) {
                foreach ($rows as $row) {
                    event(new WhatsAppNotificationRequested(
                        phone: $row->phone,
                        type: $type,
                        message: $message,
                        userId: $row->user_id,
                        force: false,
                        tenantId: $row->prefeitura_id
                    ));
                }
            });
    }

    public function createPasswordResetToken(User $user): string
    {
        $plainToken = Str::random(64);
        $tokenHash = hash('sha256', $plainToken);

        WhatsAppPasswordResetToken::where('user_id', $user->id)
            ->whereNull('used_at')
            ->delete();

        WhatsAppPasswordResetToken::create([
            'prefeitura_id' => $this->resolveTenantId($user->id),
            'user_id' => $user->id,
            'token_hash' => $tokenHash,
            'expires_at' => Carbon::now()->addMinutes(30),
        ]);

        return $plainToken;
    }

    public function notifyPasswordRecovery(User $user, string $phone, string $resetLink): void
    {
        $normalizedPhone = $this->normalizePhone($phone);
        if (!$normalizedPhone) {
            return;
        }

        $tenantName = $this->getTenantDisplayName();
        $message = "🔑 *Recuperação de senha — {$tenantName}*\n\nClique no link abaixo para redefinir sua senha:\n{$resetLink}\n\n⏱️ Este link expira em 30 minutos.";
        $this->dispatchMessage($normalizedPhone, 'password_recovery', $message, $user->id, true);
    }

    public function getTenantFrontendUrl(): string
    {
        $defaultUrl = rtrim((string) env('FRONTEND_URL', config('app.url')), '/');
        
        if (App::has('current_tenant')) {
            $tenant = App::get('current_tenant');
            if ($tenant && !empty($tenant->dominio)) {
                $domain = $tenant->dominio;
                if (!str_starts_with($domain, 'http')) {
                    $domain = 'https://' . $domain;
                }
                return rtrim($domain, '/');
            }
            
            // Fallback to subdomain if domain is empty but we have a tenant slug
            // Note: In local dev we use standard frontend url
            if ($tenant && !empty($tenant->slug) && !app()->isLocal()) {
                 $appHost = parse_url(config('app.url'), PHP_URL_HOST);
                 $appScheme = parse_url(config('app.url'), PHP_URL_SCHEME) ?? 'https';
                 if ($appHost) {
                     return "{$appScheme}://{$tenant->slug}.{$appHost}";
                 }
            }
        }
        
        return $defaultUrl;
    }

    public function consumePasswordResetToken(string $token): ?WhatsAppPasswordResetToken
    {
        $tokenHash = hash('sha256', $token);

        $resetToken = WhatsAppPasswordResetToken::where('token_hash', $tokenHash)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->first();

        if (!$resetToken) {
            return null;
        }

        $resetToken->used_at = now();
        $resetToken->save();

        return $resetToken;
    }

    private function resolveTenantId(?int $userId): ?int
    {
        if (App::has('current_tenant')) {
            return App::get('current_tenant')->id;
        }

        if ($userId) {
            return User::withoutGlobalScopes()
                ->where('id', $userId)
                ->value('prefeitura_id');
        }

        return null;
    }

    public function getTenantDisplayName(?int $tenantId = null): string
    {
        if ($tenantId) {
            $tenant = Prefeitura::withoutGlobalScopes()->find($tenantId);
            if ($tenant && !empty($tenant->nome)) {
                return trim((string) $tenant->nome);
            }
        }

        if (App::has('current_tenant')) {
            $tenant = App::get('current_tenant');
            if ($tenant && !empty($tenant->nome)) {
                return trim((string) $tenant->nome);
            }
        }

        return 'Portal Esportivo';
    }
}
