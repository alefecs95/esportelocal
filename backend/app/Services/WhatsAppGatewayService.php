<?php

namespace App\Services;

use App\Models\Prefeitura;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class WhatsAppGatewayService
{
    public function sendText(string $phone, string $message, bool $requireEnabled = true): bool
    {
        [$enabled, $sessionId] = $this->resolveSession($requireEnabled);

        if (!$sessionId || ($requireEnabled && !$enabled)) {
            Log::warning('WhatsApp não configurado. Mensagem não enviada.', [
                'phone' => $phone,
            ]);
            return false;
        }

        $response = $this->request('post', '/send/text', [
            'session' => $sessionId,
            'number' => $phone,
            'text' => $message,
        ]);

        if (!$response['ok']) {
            Log::error('Falha ao enviar mensagem WhatsApp', [
                'phone' => $phone,
                'session' => $sessionId,
                'status' => $response['status'],
                'response' => $response['body'],
            ]);
            return false;
        }

        return true;
    }

    public function testConnection(): array
    {
        $status = $this->sessionStatus();

        if (!($status['ok'] ?? false)) {
            return [
                'ok' => false,
                'message' => $status['message'] ?? 'Falha de conexão com o gateway WhatsApp.',
                'status' => $status['connection'] ?? 'disconnected',
                'phone' => $status['phone'] ?? null,
                'qr' => $status['qr'] ?? null,
            ];
        }

        $connection = $status['connection'] ?? 'disconnected';

        if ($connection === 'connected') {
            return [
                'ok' => true,
                'message' => $status['phone']
                    ? "WhatsApp conectado no número {$status['phone']}."
                    : 'WhatsApp conectado e sessão persistente ativa.',
                'status' => $connection,
                'phone' => $status['phone'] ?? null,
                'qr' => null,
            ];
        }

        return [
            'ok' => false,
            'message' => $this->statusMessage($connection),
            'status' => $connection,
            'phone' => $status['phone'] ?? null,
            'qr' => $status['qr'] ?? null,
        ];
    }

    public function sessionStatus(): array
    {
        [$enabled, $sessionId] = $this->resolveSession(false);

        if (!$sessionId) {
            return [
                'ok' => false,
                'message' => 'Tenant não identificado para a sessão WhatsApp.',
                'connection' => 'disconnected',
            ];
        }

        $response = $this->request('get', "/sessions/{$sessionId}");

        if (!$response['ok']) {
            return [
                'ok' => false,
                'enabled' => $enabled,
                'message' => $response['message'] ?? 'Gateway WhatsApp indisponível.',
                'connection' => 'disconnected',
                'phone' => null,
                'qr' => null,
            ];
        }

        $payload = $response['json'] ?? [];

        return [
            'ok' => true,
            'enabled' => $enabled,
            'connection' => $payload['status'] ?? 'disconnected',
            'phone' => $payload['phone'] ?? null,
            'qr' => $payload['qr'] ?? null,
        ];
    }

    public function connectSession(bool $reset = false): array
    {
        [$enabled, $sessionId] = $this->resolveSession(false);

        if (!$sessionId) {
            return [
                'ok' => false,
                'message' => 'Tenant não identificado para a sessão WhatsApp.',
            ];
        }

        $response = $this->request('post', "/sessions/{$sessionId}/connect", [
            'reset' => $reset,
        ]);

        if (!$response['ok']) {
            return [
                'ok' => false,
                'message' => $response['message'] ?? 'Não foi possível iniciar a sessão WhatsApp.',
                'status' => $response['json']['status'] ?? 'disconnected',
                'phone' => $response['json']['phone'] ?? null,
                'qr' => $response['json']['qr'] ?? null,
            ];
        }

        $payload = $response['json'] ?? [];

        return [
            'ok' => true,
            'enabled' => $enabled,
            'message' => $this->statusMessage($payload['status'] ?? 'connecting'),
            'status' => $payload['status'] ?? 'connecting',
            'phone' => $payload['phone'] ?? null,
            'qr' => $payload['qr'] ?? null,
        ];
    }

    public function logoutSession(): array
    {
        [, $sessionId] = $this->resolveSession(false);

        if (!$sessionId) {
            return [
                'ok' => false,
                'message' => 'Tenant não identificado para a sessão WhatsApp.',
            ];
        }

        $response = $this->request('post', "/sessions/{$sessionId}/logout");

        if (!$response['ok']) {
            return [
                'ok' => false,
                'message' => $response['message'] ?? 'Não foi possível desconectar o WhatsApp.',
            ];
        }

        return [
            'ok' => true,
            'message' => 'WhatsApp desconectado. A sessão persistida foi removida.',
            'status' => 'disconnected',
            'phone' => null,
            'qr' => null,
        ];
    }

    private function statusMessage(string $status): string
    {
        return match ($status) {
            'connected' => 'WhatsApp conectado. A sessão será mantida neste servidor.',
            'qr' => 'Escaneie o QR Code no WhatsApp do celular para conectar.',
            'connecting', 'reconnecting' => 'Estabelecendo conexão com o WhatsApp...',
            default => 'WhatsApp desconectado. Clique em conectar e escaneie o QR Code.',
        };
    }

    private function request(string $method, string $path, array $payload = []): array
    {
        $baseUrl = rtrim((string) config('services.whatsapp.gateway_url'), '/');
        $token = trim((string) config('services.whatsapp.gateway_token'));

        if (!$baseUrl) {
            return [
                'ok' => false,
                'status' => 0,
                'message' => 'WHATSAPP_GATEWAY_URL não configurada.',
                'body' => null,
                'json' => [],
            ];
        }

        try {
            $http = Http::timeout(20)
                ->acceptJson()
                ->withHeaders($token ? [
                    'Authorization' => 'Bearer ' . $token,
                    'X-Gateway-Token' => $token,
                ] : []);

            $response = strtolower($method) === 'get'
                ? $http->get($baseUrl . $path)
                : $http->post($baseUrl . $path, $payload);

            $json = $response->json() ?? [];

            return [
                'ok' => $response->successful() && (($json['ok'] ?? true) !== false),
                'status' => $response->status(),
                'message' => $json['message'] ?? null,
                'body' => $response->body(),
                'json' => $json,
            ];
        } catch (Throwable $e) {
            return [
                'ok' => false,
                'status' => 0,
                'message' => 'Falha de conexão com o gateway WhatsApp local.',
                'body' => $e->getMessage(),
                'json' => [],
            ];
        }
    }

    private function resolveSession(bool $requireEnabled = true): array
    {
        $enabled = false;
        $tenantId = null;

        if (App::has('current_tenant')) {
            $tenant = App::get('current_tenant');
            if (!$tenant instanceof Prefeitura && isset($tenant->id)) {
                $tenant = Prefeitura::find($tenant->id);
            }

            $integracoes = $tenant?->configuracoes_integracoes ?? [];
            $whatsApp = $integracoes['whatsapp'] ?? [];
            $enabled = (bool) ($whatsApp['enabled'] ?? false);
            $tenantId = $tenant?->id;
        }

        if ($requireEnabled && !$enabled) {
            return [false, null];
        }

        if (!$tenantId) {
            return [$enabled, null];
        }

        return [$enabled, 'tenant-' . $tenantId];
    }
}
