<?php

namespace App\Listeners;

use App\Events\WhatsAppNotificationRequested;
use App\Models\Prefeitura;
use App\Models\WhatsAppNotification;
use App\Services\WhatsAppGatewayService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SendWhatsAppNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private WhatsAppGatewayService $gatewayService
    ) {
    }

    public function handle(WhatsAppNotificationRequested $event): void
    {
        $notificationService = app(\App\Services\WhatsAppNotificationService::class);
        $phone = $notificationService->normalizePhone($event->phone)
            ?: preg_replace('/\D+/', '', $event->phone);
        if (!$phone) {
            return;
        }

        if ($event->tenantId) {
            $tenant = Prefeitura::find($event->tenantId);
            if ($tenant) {
                App::instance('current_tenant', $tenant);
            }
        }

        $tenantId = $event->tenantId ?? (App::has('current_tenant') ? App::get('current_tenant')->id : null);

        $preference = WhatsAppNotification::firstOrCreate(
            [
                'prefeitura_id' => $tenantId,
                'phone' => $phone,
            ],
            [
                'prefeitura_id' => $tenantId,
                'user_id' => $event->userId,
                'receive_notifications' => true,
                'unsubscribe_token' => Str::random(64),
            ]
        );

        if ($event->userId && !$preference->user_id) {
            $preference->user_id = $event->userId;
            $preference->save();
        }

        if (!$event->force && !$preference->receive_notifications) {
            return;
        }

        $message = trim($event->message);
        if (!$event->force) {
            $backendUrl = rtrim((string) config('app.url'), '/');
            $tenantUrl = $notificationService->getTenantFrontendUrl();
            $tenantName = $notificationService->getTenantDisplayName($tenantId);
            // Se estivermos em dev (localhost:5173), precisamos da URL da API local, 
            // não da porta do frontend.
            if (app()->isLocal() || str_contains($tenantUrl, 'localhost:5173')) {
                $tenantUrl = $backendUrl;
            }
            
            $unsubscribeUrl = rtrim($tenantUrl, '/') . '/api/v1/notifications/unsubscribe/' . $preference->unsubscribe_token;
            $message .= "\n\n—\n📍 *{$tenantName}*\n🔕 Para parar notificações, clique:\n{$unsubscribeUrl}";
        }

        $sent = $this->gatewayService->sendText($phone, $message, !$event->force);

        if ($sent) {
            $preference->last_notified_at = now();
            $preference->save();
            Log::info('Mensagem WhatsApp enviada', [
                'phone' => $phone,
                'type' => $event->type,
                'tenant_id' => $tenantId,
            ]);
            return;
        }

        Log::error('Mensagem WhatsApp não enviada', [
            'phone' => $phone,
            'type' => $event->type,
            'tenant_id' => $tenantId,
        ]);

        throw new \RuntimeException('Falha ao enviar WhatsApp para ' . $phone);
    }
}
